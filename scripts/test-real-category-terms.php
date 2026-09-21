<?php
/**
 * Test de conteos reales sin cache estática por categoría.
 */

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

global $wpdb;

foreach ($testCats as $slug => $label) {
    echo "========================================\n";
    echo "CATEGORÍA: {$label} ({$slug})\n";
    echo "========================================\n";

    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        echo "Categoría no encontrada: {$slug}\n\n";
        continue;
    }

    // Obtener productos de esta categoría
    $productIds = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => [$term->term_id],
            ]
        ]
    ]);

    echo "Total productos en categoría: " . count($productIds) . "\n";
    if (empty($productIds)) {
        echo "\n";
        continue;
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '%d'));

    foreach ($taxes as $tax => $taxLabel) {
        $sql = "
            SELECT t.name, t.slug, COUNT(DISTINCT tr.object_id) AS total
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tt.taxonomy = %s
              AND tr.object_id IN ({$placeholders})
            GROUP BY tt.term_id
            ORDER BY total DESC
        ";
        $rows = $wpdb->get_results($wpdb->prepare($sql, array_merge([$tax], $productIds)));

        $totalInCat = 0;
        $activeOptions = [];
        foreach ($rows as $row) {
            $totalInCat += (int)$row->total;
            $activeOptions[] = "{$row->name} ({$row->total})";
        }

        echo "  [{$taxLabel}] ({$tax}) -> Total disponibles: {$totalInCat}\n";
        if (!empty($activeOptions)) {
            echo "     Opciones activas: " . implode(', ', $activeOptions) . "\n";
        } else {
            echo "     * 0 PRODUCTOS EN ESTA CATEGORÍA (NO DEBE MOSTRARSE) *\n";
        }
    }
    echo "\n";
}
