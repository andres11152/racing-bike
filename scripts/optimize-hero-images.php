<?php
/**
 * optimize-hero-images.php v2
 * Recomprime imágenes hero con Imagick usando blob + setImageBlob para
 * forzar la re-codificación real con los parámetros de calidad deseados.
 */

if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__, 5) . '/wp-load.php');
}

echo "<pre>\n=== OPTIMIZE HERO IMAGES v2 ===\n\n";

$uploadsDir = wp_upload_dir();
$baseDir    = $uploadsDir['basedir'];

$heroImages = [
    [
        'path'      => $baseDir . '/2026/08/hero-1-mobile.webp',
        'max_width' => 820,
        'quality'   => 72,
        'label'     => 'Hero 1 Mobile LCP (2.2MB → objetivo ~180KB)',
    ],
    [
        'path'      => $baseDir . '/2026/09/GW_Bogota_1920x840.webp',
        'max_width' => 1920,
        'quality'   => 65,
        'label'     => 'GW Bogotá Banner (189KB → ~80KB)',
    ],
    [
        'path'      => $baseDir . '/2026/08/Racing_Bike_Preventa_1920x840.webp',
        'max_width' => 1920,
        'quality'   => 68,
        'label'     => 'Racing Bike Preventa Banner (158KB → ~75KB)',
    ],
    [
        'path'      => $baseDir . '/2026/08/Bicicleta_Promocion_1920x840.webp',
        'max_width' => 1920,
        'quality'   => 68,
        'label'     => 'Bicicleta Promoción Banner (143KB → ~70KB)',
    ],
    [
        'path'      => $baseDir . '/2026/08/hero-siente-la-velocidad.webp',
        'max_width' => 1920,
        'quality'   => 68,
        'label'     => 'Hero Siente la Velocidad (143KB → ~70KB)',
    ],
];

$hasImagick = extension_loaded('imagick') && class_exists('Imagick');

echo "Imagick: " . ($hasImagick ? 'SÍ' : 'NO') . "\n\n";

if (!$hasImagick) {
    echo "ERROR: Imagick no disponible.\n</pre>";
    exit(1);
}

function rb_optimize_imagick(string $path, int $maxWidth, int $quality, string $label): array
{
    echo "▸ {$label}\n  Archivo: " . basename($path) . "\n";

    if (!file_exists($path)) {
        echo "  ⚠ No encontrado.\n\n";
        return [0, 0];
    }

    $originalSize = filesize($path);
    echo "  Original: " . number_format($originalSize / 1024, 1) . " KB\n";

    // Backup
    $backup = $path . '.bak';
    if (!file_exists($backup)) copy($path, $backup);

    try {
        $im = new Imagick();
        $im->readImage($path);

        // Aplanar frames animados si los hay
        $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        // Redimensionar si es necesario
        $origW = $im->getImageWidth();
        $origH = $im->getImageHeight();
        if ($origW > $maxWidth) {
            $newH = (int) round(($maxWidth / $origW) * $origH);
            $im->resizeImage($maxWidth, $newH, Imagick::FILTER_LANCZOS, 1, true);
            echo "  Redimensionado: {$origW}x{$origH} → {$maxWidth}x{$newH}\n";
        }

        // Quitar perfil ICC y metadatos para reducir tamaño
        $im->stripImage();

        // Forzar WebP con compresión lossy explícita
        $im->setImageFormat('webp');
        $im->setOption('webp:method', '6');
        $im->setOption('webp:lossless', 'false');
        $im->setOption('webp:alpha-quality', '80');
        $im->setOption('webp:near-lossless', '0');
        $im->setImageCompressionQuality($quality);
        $im->setInterlaceScheme(Imagick::INTERLACE_NO);

        // Escribir a fichero temporal primero, luego mover
        $tmpPath = $path . '.tmp';
        $result = file_put_contents($tmpPath, $im->getImageBlob());
        $im->destroy();

        if ($result === false || !file_exists($tmpPath)) {
            throw new Exception('No se pudo escribir el archivo temporal.');
        }

        rename($tmpPath, $path);

        $newSize = filesize($path);
        $saving  = $originalSize - $newSize;
        $pct     = $originalSize > 0 ? round(($saving / $originalSize) * 100, 1) : 0;
        echo "  ✓ Resultado: " . number_format($newSize / 1024, 1) . " KB (ahorro: " . number_format($saving / 1024, 1) . " KB = {$pct}%)\n\n";
        return [$originalSize, $newSize];

    } catch (Exception $e) {
        // Restaurar backup si algo falla
        if (file_exists($backup)) copy($backup, $path);
        @unlink($path . '.tmp');
        echo "  ✗ Error: " . $e->getMessage() . " — archivo restaurado.\n\n";
        return [$originalSize, $originalSize];
    }
}

$totalBefore = 0;
$totalAfter  = 0;

foreach ($heroImages as $img) {
    [$before, $after] = rb_optimize_imagick($img['path'], $img['max_width'], $img['quality'], $img['label']);
    $totalBefore += $before;
    $totalAfter  += $after;
}

// Purgar cachés
if (function_exists('wp_cache_flush')) wp_cache_flush();
if (defined('LSCWP_V')) do_action('litespeed_purge_all');

$totalSaving = $totalBefore - $totalAfter;
$pct = $totalBefore > 0 ? round(($totalSaving / $totalBefore) * 100, 1) : 0;

echo "=== RESUMEN ===\n";
echo "Antes:   " . number_format($totalBefore / 1024, 1) . " KB\n";
echo "Después: " . number_format($totalAfter / 1024, 1) . " KB\n";
echo "Ahorro:  " . number_format($totalSaving / 1024, 1) . " KB ({$pct}%)\n";
echo "\n✅ Completado.\n</pre>";
