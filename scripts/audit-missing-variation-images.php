<?php

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$products = wc_get_products(['type' => 'variable', 'limit' => -1]);
echo "Total variable products in store: " . count($products) . "\n\n";

$missing = [];

foreach ($products as $p) {
    $pid = $p->get_id();
    $name = $p->get_name();
    $cats = wp_get_post_terms($pid, 'product_cat', ['fields' => 'names']);
    $children = $p->get_children();
    $parentImgId = (int) $p->get_image_id();
    
    // Group children by color attribute
    $byColor = [];
    foreach ($children as $cid) {
        $c = wc_get_product($cid);
        if (! $c) continue;
        $color = get_post_meta($cid, 'attribute_pa_color', true) 
              ?: get_post_meta($cid, 'attribute_color', true)
              ?: 'sin-color';
        $thumbId = (int) get_post_meta($cid, '_thumbnail_id', true);
        if (! isset($byColor[$color])) {
            $byColor[$color] = [];
        }
        $byColor[$color][] = ['var_id' => $cid, 'thumb_id' => $thumbId];
    }
    
    // If the product only has 1 color, or has no color attribute (e.g. only sizes)
    $hasColorAttr = count(array_filter(array_keys($byColor), fn($k) => $k !== 'sin-color')) > 0;
    
    foreach ($byColor as $color => $vars) {
        $thumbs = array_unique(array_column($vars, 'thumb_id'));
        $thumb = $thumbs[0];
        
        // Let's determine if this color legitimately has its own photo:
        // A color is missing photo if:
        // 1. Has no thumbnail (0) and there are multiple colors in the product (so inheriting parent means showing the WRONG color if parent is another color).
        // 2. Or thumb is set, but it equals parent image which is of another color.
        
        $missingPhoto = false;
        $reason = '';
        
        if ($hasColorAttr) {
            // Check if parent photo matches this color or a different color
            if ($thumb === 0) {
                // If it inherits parent, let's see if another color also inherits parent or if parent is explicitly another color
                $missingPhoto = true;
                $reason = 'Variación sin _thumbnail_id (hereda padre)';
            } else {
                // Variation has a thumb, check if multiple different colors share the EXACT same thumb
                // (which was the bug in Orbea Avant H50)
            }
        } else {
            // Product has no color variation (e.g. only sizes, like handlebars or cassette)
            if ($thumb === 0 && $parentImgId === 0) {
                $missingPhoto = true;
                $reason = 'Producto sin imagen padre ni de variación';
            }
        }
        
        if ($missingPhoto) {
            $missing[] = [
                'product_id' => $pid,
                'product_name' => $name,
                'categories' => implode(', ', $cats),
                'color' => $color,
                'parent_img_id' => $parentImgId,
                'parent_img_file' => $parentImgId ? basename(get_attached_file($parentImgId)) : 'ninguna',
                'current_thumb_id' => $thumb,
                'var_count' => count($vars),
                'var_ids' => array_column($vars, 'var_id'),
                'reason' => $reason,
            ];
        }
    }
}

echo "=== VARIACIONES SIN FOTO PROPIA ===\n";
echo "Total colores/grupos sin foto propia: " . count($missing) . "\n\n";
foreach ($missing as $m) {
    echo "Producto [{$m['product_id']}] {$m['product_name']} ({$m['categories']})\n";
    echo "  - Color: '{$m['color']}' ({$m['var_count']} vars)\n";
    echo "  - Razón: {$m['reason']}\n";
    echo "  - Foto actual: {$m['current_thumb_id']} (Padre: {$m['parent_img_id']} - {$m['parent_img_file']})\n\n";
}
