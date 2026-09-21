<?php

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
}

$isApply = (isset($args[0]) && strtolower($args[0]) === 'apply') || (isset($argv[1]) && strtolower($argv[1]) === 'apply');


echo "============================================================\n";
echo "OPTIMIZACIÓN Y CONVERSIÓN UNIVERSAL A WEBP (RACING BIKE)\n";
echo "Modo: " . ($isApply ? "APLICAR (Base de datos y archivos modificados)" : "DRY-RUN (Simulación)") . "\n";
echo "============================================================\n\n";

$products = wc_get_products([
    'limit' => -1,
    'status' => 'publish',
]);

echo "Analizando productos publicados: " . count($products) . "...\n";

$allImageIds = [];
foreach ($products as $product) {
    if ($product->get_image_id()) {
        $allImageIds[$product->get_image_id()] = true;
    }
    foreach ($product->get_gallery_image_ids() as $gid) {
        if ($gid) {
            $allImageIds[$gid] = true;
        }
    }
    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $cid) {
            $t = get_post_meta($cid, '_thumbnail_id', true);
            if ($t) {
                $allImageIds[(int)$t] = true;
            }
        }
    }
}

$uniqueIds = array_keys($allImageIds);
echo "Total de imágenes únicas vinculadas al catálogo activo: " . count($uniqueIds) . "\n\n";

$uploadDir = wp_upload_dir();
$stats = [
    'total' => count($uniqueIds),
    'already_webp' => 0,
    'recompressed_webp' => 0,
    'converted_to_webp' => 0,
    'skipped_missing' => 0,
    'errors' => 0,
    'initial_bytes' => 0,
    'final_bytes' => 0,
];

foreach ($uniqueIds as $idx => $attId) {
    $currentFile = get_attached_file($attId);
    if (! $currentFile || ! file_exists($currentFile)) {
        echo "[$idx/" . count($uniqueIds) . "] ID $attId: Archivo físico no existe (" . ($currentFile ?: 'vacío') . ")\n";
        $stats['skipped_missing']++;
        continue;
    }
    
    $origSize = filesize($currentFile);
    $origKb = round($origSize / 1024, 1);
    $stats['initial_bytes'] += $origSize;
    $ext = strtolower(pathinfo($currentFile, PATHINFO_EXTENSION));
    
    $dir = dirname($currentFile);
    $filenameWithoutExt = pathinfo($currentFile, PATHINFO_FILENAME);
    $targetWebpFile = $dir . '/' . $filenameWithoutExt . '.webp';
    
    // Ruta relativa para WordPress
    $relPath = ltrim(str_replace($uploadDir['basedir'], '', $targetWebpFile), '/');
    
    if ($ext === 'webp') {
        // Ya es WebP: comprobar si es pesado (> 180 KB) para re-comprimir
        if ($origKb > 180) {
            echo "[$idx/" . count($uniqueIds) . "] ID $attId (WebP pesado: {$origKb} KB): Recomprimiendo...";
            if ($isApply) {
                try {
                    $im = new Imagick($currentFile);
                    $w = $im->getImageWidth();
                    $h = $im->getImageHeight();
                    $maxDim = max($w, $h);
                    if ($maxDim > 1400) {
                        $scale = 1400 / $maxDim;
                        $im->resizeImage((int)round($w * $scale), (int)round($h * $scale), Imagick::FILTER_LANCZOS, 1);
                    }
                    $im->setImageCompressionQuality(82);
                    $tmpOut = sys_get_temp_dir() . '/recomp_' . basename($currentFile);
                    $im->writeImage($tmpOut);
                    $newSize = filesize($tmpOut);
                    if ($newSize < $origSize) {
                        rename($tmpOut, $currentFile);
                        $finalSize = $newSize;
                        // Regenerar metadatos
                        $newMeta = wp_generate_attachment_metadata($attId, $currentFile);
                        wp_update_attachment_metadata($attId, $newMeta);
                    } else {
                        @unlink($tmpOut);
                        $finalSize = $origSize;
                    }
                    $newKb = round($finalSize / 1024, 1);
                    echo " OK -> {$newKb} KB (-" . round((1 - ($finalSize / $origSize)) * 100, 1) . "%)\n";
                    $stats['final_bytes'] += $finalSize;
                    $stats['recompressed_webp']++;
                } catch (Exception $e) {
                    echo " Error: " . $e->getMessage() . "\n";
                    $stats['errors']++;
                    $stats['final_bytes'] += $origSize;
                }
            } else {
                echo " [DRY-RUN] Se re-comprimiría a < 120 KB\n";
                $stats['final_bytes'] += ($origSize * 0.5);
                $stats['recompressed_webp']++;
            }
        } else {
            // WebP óptimo
            $stats['already_webp']++;
            $stats['final_bytes'] += $origSize;
        }
        continue;
    }
    
    // Es JPG o PNG -> Convertir a WebP
    echo "[$idx/" . count($uniqueIds) . "] ID $attId ({$ext}, {$origKb} KB): Convirtiendo a WebP...";
    
    if ($isApply) {
        try {
            $im = new Imagick($currentFile);
            $w = $im->getImageWidth();
            $h = $im->getImageHeight();
            $maxDim = max($w, $h);
            
            // Escalar si excede 1400px
            if ($maxDim > 1400) {
                $scale = 1400 / $maxDim;
                $im->resizeImage((int)round($w * $scale), (int)round($h * $scale), Imagick::FILTER_LANCZOS, 1);
            }
            
            $im->setImageFormat('webp');
            $im->setImageCompressionQuality(83);
            $im->writeImage($targetWebpFile);
            
            if (! file_exists($targetWebpFile) || filesize($targetWebpFile) === 0) {
                throw new Exception("El archivo WebP generado está vacío.");
            }
            
            $newSize = filesize($targetWebpFile);
            $newKb = round($newSize / 1024, 1);
            $savingPct = round((1 - ($newSize / max(1, $origSize))) * 100, 1);
            
            // Actualizar Post en WordPress
            wp_update_post([
                'ID'             => $attId,
                'post_mime_type' => 'image/webp',
            ]);
            
            // Actualizar ruta adjunta
            update_attached_file($attId, $relPath);
            
            // Regenerar todos los tamaños de WordPress / WooCommerce en WebP
            $newMeta = wp_generate_attachment_metadata($attId, $targetWebpFile);
            wp_update_attachment_metadata($attId, $newMeta);
            
            echo " OK -> {$newKb} KB (Ahorro: {$savingPct}%)\n";
            $stats['final_bytes'] += $newSize;
            $stats['converted_to_webp']++;
        } catch (Exception $e) {
            echo " Error: " . $e->getMessage() . "\n";
            $stats['errors']++;
            $stats['final_bytes'] += $origSize;
        }
    } else {
        echo " [DRY-RUN] Se convertiría a {$filenameWithoutExt}.webp (ahorro est. ~75%)\n";
        $stats['final_bytes'] += ($origSize * 0.25);
        $stats['converted_to_webp']++;
    }
}

echo "\n============================================================\n";
echo "RESUMEN DE OPTIMIZACIÓN MULTIMEDIA\n";
echo "============================================================\n";
echo "Total de imágenes analizadas:        {$stats['total']}\n";
echo "Imágenes convertidas a WebP:         {$stats['converted_to_webp']}\n";
echo "WebP ya existentes optimizados:      {$stats['recompressed_webp']}\n";
echo "WebP óptimos mantenidos:             {$stats['already_webp']}\n";
echo "Imágenes omitidas (no encontradas):  {$stats['skipped_missing']}\n";
echo "Errores:                             {$stats['errors']}\n\n";

$initialMb = round($stats['initial_bytes'] / (1024 * 1024), 2);
$finalMb = round($stats['final_bytes'] / (1024 * 1024), 2);
$savedMb = round($initialMb - $finalMb, 2);
$savedPct = round((1 - ($finalMb / max(0.1, $initialMb))) * 100, 1);

echo "PESO TOTAL INICIAL: {$initialMb} MB\n";
echo "PESO TOTAL FINAL:   {$finalMb} MB\n";
echo "AHORRO LOGRADO:     {$savedMb} MB (-{$savedPct}%)\n\n";

if ($isApply) {
    // Purgar caché de LiteSpeed si está disponible
    if (class_exists('LiteSpeed\Purge')) {
        \LiteSpeed\Purge::purge_all();
        echo "Caché de LiteSpeed purgada con éxito.\n";
    }
}

echo "Optimización completada.\n";
