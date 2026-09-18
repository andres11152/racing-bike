<?php

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$bikes = wc_get_products([
    'category' => ['bicicletas'],
    'limit' => -1,
    'type' => 'variable',
]);

echo "Total variable bikes: " . count($bikes) . "\n\n";

foreach ($bikes as $bike) {
    $name = $bike->get_name();
    $id = $bike->get_id();
    $colors = $bike->get_attribute('pa_color');
    $gallery = $bike->get_gallery_image_ids();
    $children = $bike->get_children();

    echo "============================================================\n";
    echo "[$id] $name\n";
    echo "  Colores definidos: $colors\n";
    echo "  Imagen padre (_thumbnail_id): " . $bike->get_image_id() . "\n";
    echo "  Galería padre: " . implode(', ', $gallery) . "\n";

    $colorThumbs = [];
    foreach ($children as $childId) {
        $c = wc_get_product($childId);
        if (! $c) continue;
        $color = $c->get_attribute('pa_color') ?: get_post_meta($childId, 'attribute_pa_color', true);
        $thumb = get_post_meta($childId, '_thumbnail_id', true);
        if (! isset($colorThumbs[$color])) {
            $colorThumbs[$color] = [];
        }
        $colorThumbs[$color][] = $thumb ?: 'ninguna(hereda)';
    }

    foreach ($colorThumbs as $color => $thumbs) {
        $uniqueThumbs = array_unique($thumbs);
        echo "    Color '$color' (" . count($thumbs) . " vars): " . implode(', ', $uniqueThumbs) . "\n";
    }
}
