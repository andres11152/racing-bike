<?php

/**
 * Auditoría de integridad del catálogo — SOLO LECTURA.
 *
 * Revisa lo que rompe la experiencia de compra o la navegación y que no
 * se ve a simple vista en wp-admin: categorías duplicadas, jerarquía
 * inconsistente, productos sin categoría o sin precio, variaciones
 * incompletas, atributos huérfanos y enlaces del menú apuntando a
 * categorías que ya no existen o están vacías.
 *
 * No escribe nada. Es el insumo para decidir qué arreglar y en qué
 * orden.
 *
 * Uso: wp --skip-themes eval-file scripts/audit-catalog-integrity.php
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

global $wpdb;

function rb_h(string $titulo): void
{
    echo "\n" . str_repeat('=', 64) . "\n" . $titulo . "\n" . str_repeat('=', 64) . "\n";
}

/**
 * Acumulador de hallazgos.
 *
 * El store es `static` dentro de la propia función, no una variable
 * global: `wp eval-file` ejecuta el archivo DENTRO del scope de una
 * función suya, así que lo que aquí parece "nivel superior" en realidad
 * es una variable local de ese wrapper — un `global $problemas` desde
 * otra función apuntaba a una variable global distinta y siempre vacía,
 * y el resumen final salía en cero aunque hubiera hallazgos.
 */
function rb_prob(?string $sev = null, ?string $area = null, ?string $msg = null): array
{
    static $problemas = [];

    if ($sev === null) {
        return $problemas;
    }

    $problemas[] = ['sev' => $sev, 'area' => $area, 'msg' => $msg];

    return $problemas;
}

// ============================================================
// 1. CATEGORÍAS: duplicados, jerarquía, vacías, sin imagen
// ============================================================
rb_h('1. CATEGORÍAS DE PRODUCTO');

$terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
if (is_wp_error($terms)) {
    echo "ERROR al leer product_cat\n";
    return;
}

echo 'Total de categorías: ' . count($terms) . "\n\n";

// Duplicados por nombre normalizado
$porNombre = [];
foreach ($terms as $t) {
    $clave = mb_strtolower(trim($t->name));
    $porNombre[$clave][] = $t;
}

echo "-- Nombres duplicados --\n";
$hayDup = false;
foreach ($porNombre as $clave => $lista) {
    if (count($lista) > 1) {
        $hayDup = true;
        echo "  \"{$lista[0]->name}\" aparece " . count($lista) . " veces:\n";
        foreach ($lista as $t) {
            $padre = $t->parent ? get_term($t->parent, 'product_cat') : null;
            printf(
                "    [%d] slug=%s | padre=%s | productos=%d\n",
                $t->term_id,
                $t->slug,
                $padre && ! is_wp_error($padre) ? $padre->name : '(raíz)',
                $t->count
            );
        }
        rb_prob('ALTA', 'categorias', "Categoría duplicada: \"{$lista[0]->name}\" (" . count($lista) . ' términos distintos)');
    }
}
if (! $hayDup) {
    echo "  (ninguno)\n";
}

// Vacías
echo "\n-- Categorías sin productos --\n";
$vacias = array_filter($terms, fn ($t) => (int) $t->count === 0);
if ($vacias) {
    foreach ($vacias as $t) {
        $hijos = get_term_children($t->term_id, 'product_cat');
        printf("  [%d] %-32s slug=%-28s subcategorías=%d\n", $t->term_id, $t->name, $t->slug, count($hijos));
    }
    rb_prob('MEDIA', 'categorias', count($vacias) . ' categorías sin ningún producto asignado');
} else {
    echo "  (ninguna)\n";
}

// Sin thumbnail (la home y el archivo las pintan con imagen)
echo "\n-- Categorías sin imagen (thumbnail) --\n";
$sinImg = [];
foreach ($terms as $t) {
    if (! get_term_meta($t->term_id, 'thumbnail_id', true) && (int) $t->count > 0) {
        $sinImg[] = $t;
        printf("  [%d] %-32s (%d productos)\n", $t->term_id, $t->name, $t->count);
    }
}
if (! $sinImg) {
    echo "  (ninguna con productos)\n";
} else {
    rb_prob('MEDIA', 'categorias', count($sinImg) . ' categorías CON productos pero sin imagen');
}

// Sin descripción (afecta meta description de su archivo)
$sinDesc = array_filter($terms, fn ($t) => trim(strip_tags($t->description)) === '' && (int) $t->count > 0);
echo "\n-- Categorías con productos pero sin descripción: " . count($sinDesc) . " --\n";
if ($sinDesc) {
    echo '  ' . implode(', ', array_map(fn ($t) => $t->name, array_slice($sinDesc, 0, 15))) . (count($sinDesc) > 15 ? ', …' : '') . "\n";
    rb_prob('MEDIA', 'categorias', count($sinDesc) . ' categorías sin descripción (afecta su meta description y el texto del archivo)');
}

// Jerarquía: nivel de anidamiento y huérfanas
echo "\n-- Jerarquía --\n";
$raiz = array_filter($terms, fn ($t) => (int) $t->parent === 0);
echo '  Categorías raíz: ' . count($raiz) . "\n";
foreach ($raiz as $t) {
    $hijos = get_terms(['taxonomy' => 'product_cat', 'parent' => $t->term_id, 'hide_empty' => false]);
    printf("    [%d] %-24s productos=%-4d subcategorías=%d\n", $t->term_id, $t->name, $t->count, is_wp_error($hijos) ? 0 : count($hijos));
}

foreach ($terms as $t) {
    if ($t->parent && ! get_term($t->parent, 'product_cat')) {
        rb_prob('ALTA', 'categorias', "Categoría [{$t->term_id}] {$t->name} cuelga de un padre inexistente ({$t->parent})");
        echo "  HUÉRFANA: [{$t->term_id}] {$t->name} -> padre {$t->parent} no existe\n";
    }
}

// Naming inconsistente (minúsculas / inglés)
echo "\n-- Nombres con formato inconsistente --\n";
foreach ($terms as $t) {
    $n = $t->name;
    if ($n !== '' && mb_strtolower($n) === $n) {
        echo "  [{$t->term_id}] \"$n\" — todo en minúscula\n";
        rb_prob('BAJA', 'categorias', "Categoría \"$n\" escrita en minúscula (inconsistente con el resto)");
    }
    if (in_array(mb_strtolower($n), ['uncategorized', 'sin categoria', 'sin categoría'], true)) {
        echo "  [{$t->term_id}] \"$n\" — categoría por defecto de WordPress, en inglés (productos: {$t->count})\n";
        rb_prob((int) $t->count > 0 ? 'ALTA' : 'MEDIA', 'categorias', "Categoría por defecto \"$n\" presente ({$t->count} productos)");
    }
}

// ============================================================
// 2. PRODUCTOS: datos incompletos
// ============================================================
rb_h('2. PRODUCTOS');

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'private'],
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$porEstado = [];
$sinCategoria = [];
$sinPrecio = [];
$sinSku = [];
$skus = [];
$sinImagen = [];
$sinGaleria = [];
$agotados = [];
$sinPeso = [];
$sinResumen = [];
$reviewsOff = [];
$variablesSinVariacion = [];
$variacionesIncompletas = [];

foreach ($ids as $id) {
    $p = wc_get_product($id);
    if (! $p) {
        continue;
    }

    $estado = get_post_status($id);
    $porEstado[$estado] = ($porEstado[$estado] ?? 0) + 1;

    $cats = wp_get_post_terms($id, 'product_cat', ['fields' => 'ids']);
    $catsReales = array_diff($cats, [(int) get_option('default_product_cat')]);
    if (! $catsReales) {
        $sinCategoria[] = $p;
    }

    if ($p->get_price() === '' || $p->get_price() === null) {
        $sinPrecio[] = $p;
    }

    $sku = $p->get_sku();
    if ($sku === '') {
        $sinSku[] = $p;
    } else {
        $skus[$sku][] = $id;
    }

    if (! $p->get_image_id()) {
        $sinImagen[] = $p;
    }
    if (! $p->get_gallery_image_ids()) {
        $sinGaleria[] = $p;
    }
    if (! $p->is_in_stock()) {
        $agotados[] = $p;
    }
    if (! $p->get_weight()) {
        $sinPeso[] = $p;
    }
    if (trim((string) $p->get_short_description()) === '') {
        $sinResumen[] = $p;
    }
    if (! $p->get_reviews_allowed()) {
        $reviewsOff[] = $p;
    }

    if ($p->is_type('variable')) {
        $hijos = $p->get_children();
        if (! $hijos) {
            $variablesSinVariacion[] = $p;
        }
        foreach ($hijos as $vid) {
            $v = wc_get_product($vid);
            if (! $v) {
                continue;
            }
            $faltan = [];
            if ($v->get_price() === '' || $v->get_price() === null) {
                $faltan[] = 'precio';
            }
            if (! $v->get_image_id()) {
                $faltan[] = 'imagen';
            }
            if ($v->get_sku() === '') {
                $faltan[] = 'sku';
            }
            foreach ($v->get_attributes() as $k => $val) {
                if ($val === '' || $val === null) {
                    $faltan[] = "atributo vacío ($k)";
                }
            }
            if ($faltan) {
                $variacionesIncompletas[] = [$p, $vid, $faltan];
            }
        }
    }
}

echo 'Total de productos: ' . count($ids) . "\n";
foreach ($porEstado as $estado => $n) {
    echo "  $estado: $n\n";
}

$bloques = [
    ['Sin categoría real (solo la por defecto o ninguna)', $sinCategoria, 'ALTA'],
    ['Sin precio', $sinPrecio, 'ALTA'],
    ['Sin imagen principal', $sinImagen, 'ALTA'],
    ['Variables sin ninguna variación', $variablesSinVariacion, 'ALTA'],
    ['Sin SKU', $sinSku, 'MEDIA'],
    ['Sin galería de imágenes', $sinGaleria, 'BAJA'],
    ['Agotados / sin stock', $agotados, 'MEDIA'],
    ['Sin peso (afecta cálculo de envío)', $sinPeso, 'MEDIA'],
    ['Sin resumen corto (se usa en meta description y ficha)', $sinResumen, 'MEDIA'],
    ['Con reseñas deshabilitadas (pierde 2 pts de SEO y prueba social)', $reviewsOff, 'BAJA'],
];

foreach ($bloques as [$titulo, $lista, $sev]) {
    echo "\n-- $titulo: " . count($lista) . " --\n";
    foreach (array_slice($lista, 0, 12) as $p) {
        printf("  [%d] %s (%s)\n", $p->get_id(), $p->get_name(), get_post_status($p->get_id()));
    }
    if (count($lista) > 12) {
        echo '  … y ' . (count($lista) - 12) . " más\n";
    }
    if ($lista) {
        rb_prob($sev, 'productos', count($lista) . ' productos: ' . $titulo);
    }
}

// SKU duplicados
echo "\n-- SKU duplicados --\n";
$dupSku = array_filter($skus, fn ($v) => count($v) > 1);
if ($dupSku) {
    foreach ($dupSku as $sku => $lista) {
        echo "  \"$sku\" -> productos " . implode(', ', $lista) . "\n";
    }
    rb_prob('ALTA', 'productos', count($dupSku) . ' SKU duplicados entre productos distintos');
} else {
    echo "  (ninguno)\n";
}

// Variaciones incompletas
echo "\n-- Variaciones incompletas: " . count($variacionesIncompletas) . " --\n";
$porProducto = [];
foreach ($variacionesIncompletas as [$p, $vid, $faltan]) {
    $porProducto[$p->get_id()]['nombre'] = $p->get_name();
    $porProducto[$p->get_id()]['items'][] = "$vid (" . implode(', ', array_unique($faltan)) . ')';
}
foreach (array_slice($porProducto, 0, 10, true) as $pid => $data) {
    printf("  [%d] %s — %d variaciones con faltantes\n", $pid, $data['nombre'], count($data['items']));
    foreach (array_slice($data['items'], 0, 3) as $it) {
        echo "      $it\n";
    }
    if (count($data['items']) > 3) {
        echo '      … y ' . (count($data['items']) - 3) . " más\n";
    }
}
if (count($porProducto) > 10) {
    echo '  … y ' . (count($porProducto) - 10) . " productos más\n";
}
if ($variacionesIncompletas) {
    rb_prob('ALTA', 'variaciones', count($variacionesIncompletas) . ' variaciones con precio, imagen, SKU o atributo faltante (en ' . count($porProducto) . ' productos)');
}

// ============================================================
// 3. ATRIBUTOS
// ============================================================
rb_h('3. ATRIBUTOS Y TÉRMINOS');

$attrs = wc_get_attribute_taxonomies();
foreach ($attrs as $a) {
    $tax = wc_attribute_taxonomy_name($a->attribute_name);
    $ts = get_terms(['taxonomy' => $tax, 'hide_empty' => false]);
    if (is_wp_error($ts)) {
        continue;
    }
    $usados = array_filter($ts, fn ($t) => (int) $t->count > 0);
    printf(
        "  %-22s términos=%-4d en uso=%-4d huérfanos=%d\n",
        $tax,
        count($ts),
        count($usados),
        count($ts) - count($usados)
    );
    if (count($ts) - count($usados) > 0 && count($usados) === 0) {
        rb_prob('MEDIA', 'atributos', "Atributo $tax tiene " . count($ts) . ' términos y NINGUNO en uso (vocabulario muerto)');
    }
}

// ============================================================
// 4. NAVEGACIÓN: menú -> categorías
// ============================================================
rb_h('4. MENÚ DE NAVEGACIÓN');

$locations = get_nav_menu_locations();
$menuId = $locations['primary_navigation'] ?? 0;
if (! $menuId) {
    echo "  No hay menú asignado a 'primary_navigation'\n";
    rb_prob('ALTA', 'navegacion', "No hay ningún menú asignado a la ubicación 'primary_navigation'");
} else {
    $items = wp_get_nav_menu_items($menuId);
    echo '  Ítems en el menú: ' . count($items) . "\n";
    foreach ($items as $it) {
        $problema = null;
        if ($it->type === 'taxonomy') {
            $t = get_term((int) $it->object_id, $it->object);
            if (! $t || is_wp_error($t)) {
                $problema = 'apunta a una categoría que ya no existe';
            } elseif ((int) $t->count === 0) {
                $problema = 'apunta a una categoría VACÍA (' . $t->name . ')';
            }
        } elseif ($it->type === 'post_type') {
            $st = get_post_status((int) $it->object_id);
            if (! $st) {
                $problema = 'apunta a una página que ya no existe';
            } elseif ($st !== 'publish') {
                $problema = "apunta a contenido en estado '$st'";
            }
        }
        if ($problema) {
            printf("    [%d] \"%s\" -> %s\n", $it->ID, $it->title, $problema);
            rb_prob('ALTA', 'navegacion', "Menú: \"{$it->title}\" $problema");
        }
    }
}

// ============================================================
// RESUMEN
// ============================================================
rb_h('RESUMEN DE HALLAZGOS');

$porSev = ['ALTA' => [], 'MEDIA' => [], 'BAJA' => []];
foreach (rb_prob() as $p) {
    $porSev[$p['sev']][] = $p;
}
foreach (['ALTA', 'MEDIA', 'BAJA'] as $sev) {
    echo "\n[$sev] " . count($porSev[$sev]) . " hallazgos\n";
    foreach ($porSev[$sev] as $p) {
        printf("  (%s) %s\n", $p['area'], $p['msg']);
    }
}
