<?php

/**
 * Auditoría de solo lectura del estado de las variaciones de todos los
 * productos variables. No escribe nada nunca — es el insumo para decidir
 * qué corregir y en qué orden.
 *
 * Revisa, por producto:
 *  - Atributos duplicados (el mismo atributo como taxonomía pa_* y como
 *    texto libre a la vez, secuela de scripts/migrate-custom-attributes.php).
 *  - Atributos marcados "usado para variaciones" vs. los que no.
 *  - Productos variables sin ninguna variación.
 *  - Variaciones sin precio (no se pueden comprar).
 *  - Variaciones cuyo valor de atributo no existe en las opciones del padre
 *    (combinación rota: WooCommerce nunca la puede mostrar).
 *  - Combinaciones faltantes (opciones del padre sin variación que las cubra).
 *  - Variaciones duplicadas (dos con la misma combinación exacta).
 *  - Atributos por defecto que apuntan a un valor inexistente.
 *  - Stock: variaciones agotadas / producto entero sin stock comprable.
 *
 * Uso: wp --skip-themes eval-file scripts/audit-variations.php
 *      wp --skip-themes eval-file scripts/audit-variations.php detalle
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$detalle = in_array('detalle', $args ?? [], true);

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$problemas = [];
$totalVariables = 0;

/**
 * Clave canónica de un atributo. WooCommerce guarda la meta de cada
 * variación como sanitize_title() del nombre del atributo ("Largo de
 * Biela" -> "largo-de-biela", "pa_talla" -> "pa_talla"), así que
 * comparar el nombre crudo contra esa clave da falsos positivos.
 */
function rb_clave_attr(string $nombre): string
{
    return sanitize_title($nombre);
}

function rb_add(array &$problemas, string $tipo, int $productId, string $nombre, string $detalle): void
{
    $problemas[$tipo][] = ['id' => $productId, 'nombre' => $nombre, 'detalle' => $detalle];
}

foreach ($ids as $productId) {
    $product = wc_get_product($productId);

    if (! $product || ! $product->is_type('variable')) {
        continue;
    }

    $totalVariables++;
    $nombre = $product->get_name();
    $attributes = $product->get_attributes();

    // --- 1) Atributos duplicados: "talla" como taxonomía y como texto libre.
    $porNombreNormalizado = [];
    foreach ($attributes as $attr) {
        $clave = strtolower(str_replace(['pa_', '-', '_'], ['', ' ', ' '], $attr->get_name()));
        $clave = trim(preg_replace('/\s+/', ' ', $clave));
        $porNombreNormalizado[$clave][] = $attr;
    }

    foreach ($porNombreNormalizado as $clave => $lista) {
        if (count($lista) > 1) {
            $descripciones = array_map(function ($a) {
                return sprintf(
                    '%s (%s, %s variaciones)',
                    $a->get_name(),
                    $a->is_taxonomy() ? 'taxonomía' : 'texto libre',
                    $a->get_variation() ? 'usado en' : 'NO usado en'
                );
            }, $lista);
            rb_add($problemas, 'atributos_duplicados', $productId, $nombre, sprintf('"%s": %s', $clave, implode(' + ', $descripciones)));
        }
    }

    // --- 2) Atributos para variaciones y sus opciones válidas.
    // Cómo guarda WooCommerce el valor de una variación según el tipo de
    // atributo: para taxonomías (pa_*) guarda el SLUG del término; para
    // atributos de texto libre guarda la opción TAL CUAL está escrita en el
    // padre ("S", "Ivory White - Titan Bronze (Gloss)"). Comparar ambos
    // casos contra una versión normalizada da falsos positivos masivos, así
    // que se acepta como válida la coincidencia cruda o la saneada.
    $attrsVariacion = [];
    foreach ($attributes as $attr) {
        if (! $attr->get_variation()) {
            continue;
        }
        $opciones = $attr->is_taxonomy()
            ? wc_get_product_terms($productId, $attr->get_name(), ['fields' => 'slugs'])
            : $attr->get_options();

        $validos = [];
        foreach ($opciones as $opcion) {
            $validos[] = (string) $opcion;
            $validos[] = sanitize_title((string) $opcion);
        }

        $attrsVariacion[rb_clave_attr($attr->get_name())] = [
            'mostrar' => $opciones,
            'validos' => array_unique($validos),
        ];
    }

    if (empty($attrsVariacion)) {
        rb_add($problemas, 'sin_atributo_de_variacion', $productId, $nombre, 'producto variable sin ningún atributo marcado "usado para variaciones"');
    }

    // --- 3) Variaciones.
    $children = $product->get_children();

    if (empty($children)) {
        rb_add($problemas, 'sin_variaciones', $productId, $nombre, 'producto variable sin ninguna variación creada');
        continue;
    }

    $combinaciones = [];
    $cubiertas = [];
    $skus = [];

    foreach ($children as $variationId) {
        $variation = wc_get_product($variationId);

        if (! $variation) {
            rb_add($problemas, 'variacion_rota', $productId, $nombre, sprintf('la variación %d no se puede cargar', $variationId));
            continue;
        }

        $varAttrs = $variation->get_attributes(); // ['pa_talla' => 'l', ...]

        // Precio.
        if ($variation->get_price() === '' || $variation->get_price() === null) {
            rb_add($problemas, 'variacion_sin_precio', $productId, $nombre, sprintf('variación %d (%s) sin precio', $variationId, implode('/', $varAttrs) ?: 'sin atributos'));
        }

        // Valores que no existen en el padre.
        foreach ($varAttrs as $attrName => $valor) {
            $clave = rb_clave_attr($attrName);

            if (! isset($attrsVariacion[$clave])) {
                rb_add($problemas, 'valor_huerfano', $productId, $nombre, sprintf('variación %d usa el atributo "%s", que el producto padre no marca para variaciones', $variationId, $attrName));
                continue;
            }

            if ($valor !== '' && ! in_array($valor, $attrsVariacion[$clave]['validos'], true)) {
                rb_add($problemas, 'valor_huerfano', $productId, $nombre, sprintf('variación %d tiene %s="%s", valor que NO está en las opciones del padre (%s)', $variationId, $attrName, $valor, implode(', ', $attrsVariacion[$clave]['mostrar']) ?: 'ninguna'));
            }
        }

        // Variación "comodín": todos sus atributos vacíos. WooCommerce la
        // interpreta como "cualquier valor", así que puede tapar a las
        // variaciones específicas y hacer que se compre la combinación
        // equivocada.
        $valoresNoVacios = array_filter($varAttrs, fn ($v) => (string) $v !== '');
        if ($varAttrs && empty($valoresNoVacios)) {
            rb_add($problemas, 'variacion_comodin', $productId, $nombre, sprintf('variación %d tiene TODOS sus atributos vacíos ("cualquiera"); sku=%s', $variationId, $variation->get_sku() ?: '-'));
        }

        // SKU repetido entre variaciones del mismo producto.
        $sku = $variation->get_sku();
        if ($sku !== '') {
            if (isset($skus[$sku])) {
                rb_add($problemas, 'sku_duplicado', $productId, $nombre, sprintf('las variaciones %d y %d comparten el SKU "%s"', $skus[$sku], $variationId, $sku));
            } else {
                $skus[$sku] = $variationId;
            }
        }

        // Duplicados por combinación exacta.
        $firma = wp_json_encode($varAttrs);
        if (isset($combinaciones[$firma])) {
            rb_add($problemas, 'variacion_duplicada', $productId, $nombre, sprintf('variaciones %d y %d tienen la misma combinación (%s)', $combinaciones[$firma], $variationId, implode('/', $varAttrs) ?: 'vacía'));
        } else {
            $combinaciones[$firma] = $variationId;
        }

        $cubiertas[] = $varAttrs;
    }

    // --- 4) Combinaciones faltantes (solo si hay UN atributo de variación,
    // que es el caso de este catálogo; con más de uno el producto cartesiano
    // no siempre se quiere completo y marcarlo sería ruido).
    if (count($attrsVariacion) === 1) {
        $attrName = array_key_first($attrsVariacion);
        $usados = [];
        foreach ($cubiertas as $varAttrs) {
            foreach ($varAttrs as $k => $v) {
                if (rb_clave_attr($k) === $attrName && $v !== '') {
                    $usados[] = (string) $v;
                    $usados[] = sanitize_title((string) $v);
                }
            }
        }
        $faltan = [];
        foreach ($attrsVariacion[$attrName]['mostrar'] as $opcion) {
            if (! in_array((string) $opcion, $usados, true) && ! in_array(sanitize_title((string) $opcion), $usados, true)) {
                $faltan[] = $opcion;
            }
        }
        if ($faltan) {
            rb_add($problemas, 'combinacion_faltante', $productId, $nombre, sprintf('el padre ofrece %s pero no hay variación para: %s', $attrName, implode(', ', $faltan)));
        }
    }

    // --- 5) Atributo por defecto inválido.
    foreach ($product->get_default_attributes() as $attrName => $valor) {
        $clave = rb_clave_attr($attrName);
        if ($valor === '') {
            continue;
        }
        if (! isset($attrsVariacion[$clave]) || ! in_array($valor, $attrsVariacion[$clave]['validos'], true)) {
            rb_add($problemas, 'default_invalido', $productId, $nombre, sprintf('atributo por defecto %s="%s" no existe entre las opciones', $attrName, $valor));
        }
    }

    // --- 6) Stock: ninguna variación comprable.
    $comprables = 0;
    foreach ($children as $variationId) {
        $variation = wc_get_product($variationId);
        if ($variation && $variation->is_purchasable() && $variation->is_in_stock()) {
            $comprables++;
        }
    }
    if ($comprables === 0) {
        rb_add($problemas, 'sin_variacion_comprable', $productId, $nombre, sprintf('ninguna de sus %d variaciones es comprable (sin stock o sin precio)', count($children)));
    }
}

$etiquetas = [
    'atributos_duplicados' => 'Atributo duplicado (taxonomía + texto libre a la vez)',
    'sin_atributo_de_variacion' => 'Variable sin atributo marcado para variaciones',
    'sin_variaciones' => 'Variable sin ninguna variación',
    'variacion_rota' => 'Variación que no carga',
    'variacion_sin_precio' => 'Variación sin precio',
    'valor_huerfano' => 'Variación con valor que no existe en el padre',
    'variacion_duplicada' => 'Variaciones duplicadas (misma combinación)',
    'variacion_comodin' => 'Variación comodín (atributos vacíos)',
    'sku_duplicado' => 'SKU repetido entre variaciones',
    'combinacion_faltante' => 'Opción del padre sin variación que la cubra',
    'default_invalido' => 'Atributo por defecto inválido',
    'sin_variacion_comprable' => 'Ninguna variación comprable',
];

printf("Productos variables publicados: %d\n\n", $totalVariables);
echo "=== RESUMEN POR TIPO DE PROBLEMA ===\n\n";

$totalProblemas = 0;
foreach ($etiquetas as $tipo => $etiqueta) {
    $n = count($problemas[$tipo] ?? []);
    $totalProblemas += $n;
    printf("%-55s %d\n", $etiqueta, $n);
}

printf("\nTOTAL de hallazgos: %d\n", $totalProblemas);

if (! $detalle) {
    echo "\n(agrega el argumento \"detalle\" para ver producto por producto)\n";
    return;
}

foreach ($etiquetas as $tipo => $etiqueta) {
    if (empty($problemas[$tipo])) {
        continue;
    }

    printf("\n\n=== %s (%d) ===\n\n", strtoupper($etiqueta), count($problemas[$tipo]));
    foreach ($problemas[$tipo] as $p) {
        printf("[%d] %s\n  %s\n", $p['id'], $p['nombre'], $p['detalle']);
    }
}
