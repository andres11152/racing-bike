<?php
/**
 * Desactiva (pasa a estado "draft" / borrador) todos los productos publicados
 * en producción que no tienen imagen o no tienen precio válido (precio <= 0 o vacío).
 *
 * Criterios de desactivación:
 * 1. SIN IMAGEN:
 *    - No tiene _thumbnail_id (imagen destacada), ni galería, ni ninguna variación con imagen válida.
 *    - O el attachment ID configurado ya no existe en la biblioteca de medios.
 *
 * 2. SIN PRECIO:
 *    - Producto simple: get_price() está vacío o es <= 0.
 *    - Producto variable: ningún precio de variación está configurado o todas las variaciones tienen precio 0 / vacío.
 *
 * Modo dry-run (por defecto): lista todos los productos afectados con el motivo exacto, sin modificar la BD.
 * Modo apply: cambia post_status de 'publish' a 'draft' y purga caché.
 *
 * Uso:
 *   deploy/run-prod-script.sh scripts/deactivate-products-no-image-or-price.php
 *   deploy/run-prod-script.sh scripts/deactivate-products-no-image-or-price.php apply
 */

if (! defined('ABSPATH')) {
    exit;
}

$apply = in_array('apply', $args ?? [], true);

echo "========================================================================\n";
echo " AUDITORÍA DE PRODUCTOS SIN IMAGEN O SIN PRECIO EN PRODUCCIÓN\n";
echo " " . ($apply ? ">>> MODO APLICAR: Pasando productos a BORRADOR (draft) <<<" : ">>> MODO DRY-RUN: Solo diagnóstico, no modifica la BD <<<") . "\n";
echo "========================================================================\n\n";

// Obtener todos los productos publicados
$productIds = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'ID',
    'order'          => 'ASC',
]);

$totalPublished = count($productIds);
echo "Total de productos publicados encontrados: {$totalPublished}\n\n";

$toDeactivate = [];
$stats = [
    'no_image_only' => 0,
    'no_price_only' => 0,
    'both'          => 0,
];

foreach ($productIds as $productId) {
    $product = wc_get_product($productId);
    if (! $product) {
        continue;
    }

    $title = $product->get_name();
    $type  = $product->get_type();
    $reasons = [];

    // --- 1. Verificación de Imagen ---
    $hasImage = false;
    $mainImageId = $product->get_image_id();

    if ($mainImageId && wp_get_attachment_image_url($mainImageId, 'full')) {
        $hasImage = true;
    }

    // Si no tiene imagen principal, verificar si la galería tiene alguna válida
    if (! $hasImage) {
        $galleryIds = $product->get_gallery_image_ids();
        foreach ($galleryIds as $gid) {
            if ($gid && wp_get_attachment_image_url($gid, 'full')) {
                $hasImage = true;
                break;
            }
        }
    }

    // Si es variable y aún no tiene imagen, verificar si alguna variación tiene imagen
    if (! $hasImage && $type === 'variable') {
        $children = $product->get_children();
        foreach ($children as $childId) {
            $childThumb = get_post_thumbnail_id($childId);
            if ($childThumb && wp_get_attachment_image_url($childThumb, 'full')) {
                $hasImage = true;
                break;
            }
        }
    }

    if (! $hasImage) {
        $reasons[] = 'SIN IMAGEN (ni destacada, ni galería, ni variación)';
    }

    // --- 2. Verificación de Precio ---
    $hasValidPrice = false;

    if ($type === 'variable') {
        $prices = $product->get_variation_prices();
        $variationPrices = $prices['price'] ?? [];
        
        $validCount = 0;
        foreach ($variationPrices as $p) {
            if ($p !== '' && $p !== null && floatval($p) > 0) {
                $validCount++;
            }
        }

        if ($validCount > 0) {
            $hasValidPrice = true;
        } else {
            $reasons[] = 'SIN PRECIO (producto variable sin precios o con todas sus variaciones en $0)';
        }
    } else {
        $price = $product->get_price();
        if ($price !== '' && $price !== null && floatval($price) > 0) {
            $hasValidPrice = true;
        } else {
            $currentPriceVal = ($price === '' || $price === null) ? 'vacío' : '$' . $price;
            $reasons[] = "SIN PRECIO (precio actual: {$currentPriceVal})";
        }
    }

    // --- Determinar si requiere desactivación ---
    if (! empty($reasons)) {
        $noImg = ! $hasImage;
        $noPrc = ! $hasValidPrice;

        if ($noImg && $noPrc) {
            $stats['both']++;
        } elseif ($noImg) {
            $stats['no_image_only']++;
        } else {
            $stats['no_price_only']++;
        }

        $toDeactivate[] = [
            'id'      => $productId,
            'title'   => $title,
            'type'    => $type,
            'reasons' => $reasons,
        ];
    }
}

// --- Listar productos identificados ---
if (empty($toDeactivate)) {
    echo "¡Excelente! El 100% de los productos publicados cuentan con imagen y precio válido.\n";
    echo "No hay productos que requieran pasar a borrador.\n";
    return;
}

echo sprintf("Se encontraron %d productos para DESACTIVAR (pasar a borrador):\n\n", count($toDeactivate));

foreach ($toDeactivate as $item) {
    echo sprintf(
        "  - [ID: %d] [%s] %s\n    Motivo(s): %s\n",
        $item['id'],
        strtoupper($item['type']),
        $item['title'],
        implode(' | ', $item['reasons'])
    );
}

echo "\n------------------------------------------------------------------------\n";
echo "RESUMEN:\n";
echo "  - Total evaluados: {$totalPublished}\n";
echo "  - Productos a desactivar: " . count($toDeactivate) . "\n";
echo "    * Sin imagen y sin precio: {$stats['both']}\n";
echo "    * Solo sin precio ($0 o vacío): {$stats['no_price_only']}\n";
echo "    * Solo sin imagen: {$stats['no_image_only']}\n";
echo "  - Productos que permanecerán publicados: " . ($totalPublished - count($toDeactivate)) . "\n";
echo "------------------------------------------------------------------------\n\n";

// --- Aplicar cambios si se solicitó ---
if ($apply) {
    echo "Aplicando cambios (pasando a post_status = 'draft')...\n";
    $success = 0;
    $errors  = 0;

    foreach ($toDeactivate as $item) {
        $res = wp_update_post([
            'ID'          => $item['id'],
            'post_status' => 'draft',
        ], true);

        if (is_wp_error($res)) {
            echo "  [ERROR] ID {$item['id']}: " . $res->get_error_message() . "\n";
            $errors++;
        } else {
            echo "  [OK] ID {$item['id']} pasado a BORRADOR exitosamente.\n";
            $success++;
        }
    }

    // Limpiar cachés de WooCommerce y LiteSpeed
    if (function_exists('wc_delete_product_transients')) {
        foreach ($toDeactivate as $item) {
            wc_delete_product_transients($item['id']);
        }
    }

    echo "\n>>> Proceso completado: {$success} productos desactivados (borrador), {$errors} errores.\n";
} else {
    echo "Para ejecutar la desactivación en producción con backup automático:\n";
    echo "deploy/run-prod-script.sh scripts/deactivate-products-no-image-or-price.php apply\n";
}
