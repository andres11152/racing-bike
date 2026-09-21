<?php

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$isApply = (isset($args[0]) && strtolower($args[0]) === 'apply') || (isset($argv[1]) && strtolower($argv[1]) === 'apply');

echo "============================================================\n";
echo "DEPURACIÓN SEGURA DE RESIDUOS JPG Y PNG (RACING BIKE)\n";
echo "Modo: " . ($isApply ? "APLICAR (Archivos eliminados tras backup)" : "DRY-RUN (Simulación)") . "\n";
echo "============================================================\n\n";

// 1. Obtener todos los adjuntos activos en la base de datos para protección absoluta
global $wpdb;
$activeAttachedFiles = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'");
$protectedBasenames = [];
foreach ($activeAttachedFiles as $relPath) {
    $protectedBasenames[basename($relPath)] = true;
}

$siteIconId = get_option('site_icon');
if ($siteIconId) {
    $f = get_attached_file($siteIconId);
    if ($f) $protectedBasenames[basename($f)] = true;
}

echo "Total de archivos adjuntos protegidos en la base de datos: " . count($protectedBasenames) . "\n";

// 2. Localizar productos y sus imágenes WebP activas
$products = wc_get_products([
    'limit' => -1,
    'status' => 'publish',
]);

$productImageIds = [];
foreach ($products as $p) {
    if ($p->get_image_id()) $productImageIds[$p->get_image_id()] = true;
    foreach ($p->get_gallery_image_ids() as $gid) if ($gid) $productImageIds[$gid] = true;
    if ($p->is_type('variable')) {
        foreach ($p->get_children() as $cid) {
            $t = get_post_meta($cid, '_thumbnail_id', true);
            if ($t) $productImageIds[(int)$t] = true;
        }
    }
}

echo "Total de imágenes activas en el catálogo de productos: " . count($productImageIds) . "\n\n";

// 3. Identificar residuos huérfanos que tienen un WebP activo y válido
$filesToDelete = [];
$bytesRecoverable = 0;

foreach (array_keys($productImageIds) as $attId) {
    $webpFile = get_attached_file($attId);
    if (! $webpFile || ! file_exists($webpFile) || filesize($webpFile) === 0) {
        // Si no hay WebP válido, saltar por seguridad
        continue;
    }
    
    $dir = dirname($webpFile);
    $base = pathinfo($webpFile, PATHINFO_FILENAME);
    
    // Buscar todos los archivos residuales JPG/PNG con el mismo prefijo
    $residuals = glob($dir . '/' . $base . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
    if (! $residuals) continue;
    
    foreach ($residuals as $resFile) {
        $resBase = basename($resFile);
        
        // Regla de seguridad: Si la base de datos todavía apunta a este archivo específico, NO TOCAR
        if (isset($protectedBasenames[$resBase])) {
            continue;
        }
        
        $sz = filesize($resFile);
        $filesToDelete[$resFile] = $sz;
        $bytesRecoverable += $sz;
    }
}

$totalFiles = count($filesToDelete);
$totalMb = round($bytesRecoverable / (1024 * 1024), 2);

echo "============================================================\n";
echo "RESULTADO DEL ESCANEO\n";
echo "============================================================\n";
echo "Archivos residuales JPG/PNG huérfanos identificados: $totalFiles\n";
echo "Espacio en disco a liberar:                        $totalMb MB\n\n";

if ($totalFiles === 0) {
    echo "No hay archivos residuales pendientes de depurar.\n";
    exit(0);
}

if (! $isApply) {
    echo "[DRY-RUN] Muestra de los primeros 15 archivos que se eliminarán en modo apply:\n";
    $sample = array_slice($filesToDelete, 0, 15, true);
    foreach ($sample as $f => $s) {
        echo "  - " . basename($f) . " (" . round($s / 1024, 1) . " KB)\n";
    }
    echo "\nPara aplicar la eliminación segura, ejecuta con el argumento 'apply'.\n";
    exit(0);
}

// MODO APLICAR: Crear backup zip antes de borrar
echo "Creando archivo comprimido de respaldo previo con ZipArchive...\n";
$backupDir = ABSPATH . 'wp-content/uploads/backups-purged-images';
if (! is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$zipFile = $backupDir . '/backup-purged-jpg-png-' . date('Y-m-d_His') . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "[ERROR CRÍTICO] No se pudo crear el archivo zip de respaldo. Abortando.\n";
    exit(1);
}

foreach (array_keys($filesToDelete) as $fPath) {
    $zip->addFile($fPath, basename($fPath));
}
$zip->close();

if (! file_exists($zipFile) || filesize($zipFile) === 0) {
    echo "[ERROR CRÍTICO] El archivo zip de respaldo está vacío. Abortando.\n";
    exit(1);
}

$zipMb = round(filesize($zipFile) / (1024 * 1024), 2);
echo "Backup empaquetado exitosamente en: $zipFile ($zipMb MB)\n\n";


// Proceder al borrado verificado
echo "Eliminando $totalFiles archivos residuales...\n";
$deletedCount = 0;
$deletedBytes = 0;

foreach ($filesToDelete as $filePath => $size) {
    if (@unlink($filePath)) {
        $deletedCount++;
        $deletedBytes += $size;
    }
}



$deletedMb = round($deletedBytes / (1024 * 1024), 2);
echo "\n============================================================\n";
echo "DEPURACIÓN COMPLETADA CON ÉXITO\n";
echo "============================================================\n";
echo "Archivos eliminados:       $deletedCount de $totalFiles\n";
echo "Espacio en disco liberado: $deletedMb MB\n";
echo "Respaldo conservado en:    $tarFile\n";
echo "============================================================\n";
