<?php

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

echo "============================================================\n";
echo "AUDITORÍA TÉCNICA DE IMÁGENES EN EL CATÁLOGO (RACING BIKE)\n";
echo "============================================================\n\n";

$products = wc_get_products([
    'limit' => -1,
    'status' => 'publish',
]);

echo "Total de productos publicados encontrados: " . count($products) . "\n\n";

$allImageIds = [];
$productImageMap = [];
$nonWebpCount = 0;
$heavyCount = 0; // > 200KB
$lowResCount = 0; // width < 800 or height < 800
$oversizedCount = 0; // width > 2400 or height > 2400
$missingFiles = 0;

$auditData = [];

foreach ($products as $product) {
    $pId = $product->get_id();
    $name = $product->get_name();
    $type = $product->get_type();
    
    $featuredId = $product->get_image_id();
    $galleryIds = $product->get_gallery_image_ids();
    
    $productImages = [];
    if ($featuredId) {
        $productImages[] = ['role' => 'featured', 'id' => (int)$featuredId];
    }
    foreach ($galleryIds as $gid) {
        if ($gid) {
            $productImages[] = ['role' => 'gallery', 'id' => (int)$gid];
        }
    }
    
    if ($type === 'variable') {
        $children = $product->get_children();
        foreach ($children as $cId) {
            $cThumb = get_post_meta($cId, '_thumbnail_id', true);
            if ($cThumb) {
                $productImages[] = ['role' => 'variation_' . $cId, 'id' => (int)$cThumb];
            }
        }
    }
    
    $auditData[$pId] = [
        'name' => $name,
        'type' => $type,
        'images' => $productImages,
    ];
    
    foreach ($productImages as $img) {
        $allImageIds[$img['id']] = true;
    }
}

echo "Total de IDs de imágenes únicas asociadas a productos: " . count($allImageIds) . "\n\n";

// Analizar cada archivo adjunto único
$imageStats = [];
$formatDistribution = [];
$sizeIssues = [];
$resolutionIssues = [];

foreach (array_keys($allImageIds) as $attId) {
    $file = get_attached_file($attId);
    $meta = wp_get_attachment_metadata($attId);
    $mime = get_post_mime_type($attId);
    
    if (! $file || ! file_exists($file)) {
        $missingFiles++;
        $imageStats[$attId] = [
            'id' => $attId,
            'status' => 'missing',
            'file' => $file,
        ];
        continue;
    }
    
    $fileSize = filesize($file);
    $sizeKb = round($fileSize / 1024, 2);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $width = isset($meta['width']) ? (int)$meta['width'] : 0;
    $height = isset($meta['height']) ? (int)$meta['height'] : 0;
    
    if ($width === 0 || $height === 0) {
        $imgSize = @getimagesize($file);
        if ($imgSize) {
            $width = $imgSize[0];
            $height = $imgSize[1];
        }
    }
    
    $formatDistribution[$ext] = ($formatDistribution[$ext] ?? 0) + 1;
    
    $isWebp = ($ext === 'webp' || $mime === 'image/webp');
    if (! $isWebp) {
        $nonWebpCount++;
    }
    
    if ($sizeKb > 200) {
        $heavyCount++;
        $sizeIssues[] = "ID $attId ($sizeKb KB, $ext): " . basename($file);
    }
    
    if ($width > 0 && ($width < 800 || $height < 800)) {
        $lowResCount++;
        $resolutionIssues[] = "ID $attId ($width x $height px): " . basename($file) . " (Baja resolución)";
    } elseif ($width > 2400 || $height > 2400) {
        $oversizedCount++;
        $resolutionIssues[] = "ID $attId ($width x $height px): " . basename($file) . " (Resolución excesiva)";
    }
    
    $imageStats[$attId] = [
        'id' => $attId,
        'filename' => basename($file),
        'ext' => $ext,
        'mime' => $mime,
        'size_kb' => $sizeKb,
        'width' => $width,
        'height' => $height,
        'is_webp' => $isWebp,
    ];
}

echo "DISTRIBUCIÓN DE FORMATOS:\n";
foreach ($formatDistribution as $fmt => $cnt) {
    echo "  - $fmt: $cnt imágenes (" . round(($cnt / max(1, count($allImageIds))) * 100, 1) . "%)\n";
}
echo "\n";

echo "RESUMEN DE DIAGNÓSTICO:\n";
echo "  - Imágenes no WebP: $nonWebpCount\n";
echo "  - Imágenes pesadas (> 200 KB): $heavyCount\n";
echo "  - Imágenes con baja resolución (< 800px): $lowResCount\n";
echo "  - Imágenes con resolución excesiva (> 2400px): $oversizedCount\n";
echo "  - Archivos físicos faltantes en disco: $missingFiles\n\n";

if (! empty($sizeIssues)) {
    echo "TOP 15 IMÁGENES MÁS PESADAS:\n";
    uasort($imageStats, function($a, $b) {
        return ($b['size_kb'] ?? 0) <=> ($a['size_kb'] ?? 0);
    });
    $top = array_slice($imageStats, 0, 15);
    foreach ($top as $st) {
        if (isset($st['size_kb'])) {
            echo "  - ID {$st['id']}: {$st['filename']} | {$st['size_kb']} KB | {$st['width']}x{$st['height']} px | {$st['ext']}\n";
        }
    }
    echo "\n";
}

// Detalle por producto
echo "DETALLE DE PRODUCTOS CON IMÁGENES NO-WEBP O PESADAS:\n";
$needsOptimizationCount = 0;
foreach ($auditData as $pId => $pData) {
    $productNeedsWork = false;
    $issues = [];
    foreach ($pData['images'] as $img) {
        $st = $imageStats[$img['id']] ?? null;
        if (! $st || $st['status'] ?? '' === 'missing') {
            $productNeedsWork = true;
            $issues[] = "{$img['role']}: imagen no encontrada";
            continue;
        }
        if (! $st['is_webp']) {
            $productNeedsWork = true;
            $issues[] = "{$img['role']} (ID {$img['id']}): {$st['ext']} ({$st['size_kb']} KB, {$st['width']}x{$st['height']})";
        } elseif ($st['size_kb'] > 200) {
            $productNeedsWork = true;
            $issues[] = "{$img['role']} (ID {$img['id']}): WEBP PESADO ({$st['size_kb']} KB)";
        }
    }
    
    if ($productNeedsWork) {
        $needsOptimizationCount++;
        echo "[$pId] {$pData['name']} ({$pData['type']}):\n";
        foreach ($issues as $iss) {
            echo "    -> $iss\n";
        }
    }
}

echo "\nTotal de productos que requieren optimización de imágenes: $needsOptimizationCount de " . count($products) . "\n";
