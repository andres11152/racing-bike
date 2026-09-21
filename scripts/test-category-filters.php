<?php
/**
 * Test de conteos de filtros por categoría.
 */
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-content/themes/racing-bike-theme/app/catalog-filters.php';

$testCats = [
    'bicicletas' => 'Bicicletas',
    'ruta' => 'Bicicletas de Ruta',
    'mtb' => 'Bicicletas MTB',
    'grupos-de-mtb' => 'Grupos de MTB',
    'grupos-de-ruta' => 'Grupos de Ruta',
    'cascos' => 'Cascos',
    'simuladores-rodillos' => 'Simuladores',
    'ciclocomputadores' => 'Ciclocomputadores',
];

$taxes = [
    'pa_marca' => 'Marca',
    'pa_talla' => 'Talla',
    'pa_longitud-de-biela' => 'Longitud de biela',
    'pa_color-familia' => 'Color',
];

foreach ($testCats as $slug => $label) {
    echo "========================================\n";
    echo "CATEGORÍA: {$label} ({$slug})\n";
    echo "========================================\n";

    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        echo "Categoría no encontrada: {$slug}\n\n";
        continue;
    }

    // Simular queried object
    global $wp_query;
    $wp_query->queried_object = $term;

    foreach ($taxes as $tax => $taxLabel) {
        $counts = \App\rb_layered_nav_term_counts($tax);
        $nonZero = array_filter($counts, fn($c) => $c > 0);
        $total = array_sum($counts);

        echo "  [{$taxLabel}] ({$tax}) -> Total disponibles: {$total}\n";
        if (!empty($nonZero)) {
            $termsStr = [];
            foreach ($nonZero as $tSlug => $c) {
                $termsStr[] = "{$tSlug} ({$c})";
            }
            echo "     Opciones activas: " . implode(', ', $termsStr) . "\n";
        } else {
            echo "     * SIN OPCIONES EN ESTA CATEGORÍA *\n";
        }
    }
    echo "\n";
}
