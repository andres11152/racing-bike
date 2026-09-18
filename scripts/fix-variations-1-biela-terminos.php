<?php

/**
 * FASE 1 del plan de corrección de variaciones — la única que bloquea ventas.
 *
 * Problema: en 3 productos, el atributo pa_longitud-de-biela del producto
 * PADRE tiene un único término que concatena varios valores con "|"
 * ("165 mm | 170 mm | 172.5 mm | 175 mm"), secuela de una importación que
 * no separó el campo. Las variaciones sí apuntan a los valores individuales
 * correctos (165-mm, 170-mm, 172-5-mm, 175-mm), pero como el padre no los
 * ofrece, WooCommerce pinta un desplegable con una sola opción sin sentido
 * y NINGUNA variación coincide con ella: el cliente elige y recibe
 * "producto no disponible, elige otra combinación". No se puede comprar.
 *
 * Arreglo: para cada producto se toman los valores reales de sus propias
 * variaciones (fuente de verdad), se crean los términos que falten en la
 * taxonomía (165 mm y 172.5 mm no existen todavía) y se le asignan al
 * padre, reemplazando el término fusionado.
 *
 * Los términos fusionados quedan sin ningún producto asignado; se borran al
 * final solo si efectivamente quedaron en cero, y nunca se borra un término
 * que siga en uso.
 *
 * Uso: wp --skip-themes eval-file scripts/fix-variations-1-biela-terminos.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_TAXONOMIA = 'pa_longitud-de-biela';
const RB_PRODUCTOS = [2008, 1993, 1934];

/**
 * Nombre legible a partir del slug que ya usan las variaciones:
 * "165-mm" -> "165 mm", "172-5-mm" -> "172.5 mm".
 */
function rb_nombre_desde_slug(string $slug): string
{
    if (preg_match('/^([\d-]+)-mm$/', $slug, $m)) {
        return str_replace('-', '.', $m[1]) . ' mm';
    }

    return ucfirst(str_replace('-', ' ', $slug));
}

$terminosFusionados = [];

foreach (RB_PRODUCTOS as $productId) {
    $product = wc_get_product($productId);

    if (! $product || ! $product->is_type('variable')) {
        printf("[%d] no es un producto variable, se omite.\n\n", $productId);
        continue;
    }

    printf("[%d] %s\n", $productId, $product->get_name());

    $actuales = wc_get_product_terms($productId, RB_TAXONOMIA, ['fields' => 'slugs']);
    printf("  padre ahora: %s\n", implode(', ', $actuales) ?: '(ninguno)');

    // Fuente de verdad: los valores que realmente usan las variaciones.
    $deseados = [];
    foreach ($product->get_children() as $variationId) {
        $variation = wc_get_product($variationId);
        if (! $variation) {
            continue;
        }
        $valor = $variation->get_attributes()[RB_TAXONOMIA] ?? '';
        if ($valor !== '') {
            $deseados[] = $valor;
        }
    }
    $deseados = array_values(array_unique($deseados));
    sort($deseados, SORT_NATURAL);

    if (empty($deseados)) {
        echo "  *** sus variaciones no declaran ningún valor de biela — revisar a mano, no se toca. ***\n\n";
        continue;
    }

    printf("  padre debería: %s\n", implode(', ', $deseados));

    // Términos fusionados que se van a quitar de este producto.
    foreach (array_diff($actuales, $deseados) as $sobra) {
        if (str_contains($sobra, '-mm-')) {
            $terminosFusionados[$sobra] = true;
        }
    }

    // Crear los términos que falten.
    foreach ($deseados as $slug) {
        if (get_term_by('slug', $slug, RB_TAXONOMIA)) {
            continue;
        }

        $nombre = rb_nombre_desde_slug($slug);
        printf("  falta el término \"%s\" (slug %s): se crea\n", $nombre, $slug);

        if ($apply) {
            $creado = wp_insert_term($nombre, RB_TAXONOMIA, ['slug' => $slug]);
            if (is_wp_error($creado)) {
                printf("    ERROR creando el término: %s\n", $creado->get_error_message());
            }
        }
    }

    if ($apply) {
        wp_set_object_terms($productId, $deseados, RB_TAXONOMIA, false);

        // Que WooCommerce recalcule rango de precios, variaciones válidas y
        // sus cachés: sin esto la ficha puede seguir mostrando lo viejo.
        WC_Product_Variable::sync($productId);
        wc_delete_product_transients($productId);

        $quedaron = wc_get_product_terms($productId, RB_TAXONOMIA, ['fields' => 'slugs']);
        printf("  guardado. padre ahora: %s\n", implode(', ', $quedaron));
    }

    echo "\n";
}

// Limpieza de los términos fusionados, solo si quedaron sin uso.
if ($terminosFusionados) {
    echo "--- Términos fusionados que quedan sin uso ---\n\n";

    foreach (array_keys($terminosFusionados) as $slug) {
        $term = get_term_by('slug', $slug, RB_TAXONOMIA);

        if (! $term) {
            printf("  %s: ya no existe.\n", $slug);
            continue;
        }

        // Releer el conteo real: get_term_by puede traerlo cacheado.
        $enUso = get_objects_in_term([$term->term_id], RB_TAXONOMIA);
        $enUso = is_wp_error($enUso) ? [] : $enUso;

        if (! empty($enUso)) {
            printf("  \"%s\" sigue asignado a %d producto(s) (%s): NO se borra.\n", $term->name, count($enUso), implode(', ', $enUso));
            continue;
        }

        printf("  \"%s\" (slug %s) quedó sin productos: se borra.\n", $term->name, $term->slug);

        if ($apply) {
            wp_delete_term($term->term_id, RB_TAXONOMIA);
        }
    }

    echo "\n";
}

echo ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
