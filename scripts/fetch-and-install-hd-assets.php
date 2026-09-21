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
echo "DESCARGA E INSTALACIÓN DE ACTIVOS HD OFICIALES (RACING BIKE)\n";
echo "Modo: " . ($isApply ? "APLICAR (Base de datos modificada)" : "DRY-RUN (Simulación)") . "\n";
echo "============================================================\n\n";

$assetsToFetch = [
    'trek_domane_al2_crimson' => [
        'url' => 'https://cdn.shoplightspeed.com/shops/616818/files/58341899/1024x1024x2/trek-trek-domane-al-2-gen-4-crimson-to-dark-carmin.jpg',
        'target_name' => 'trek-domane-al-2-gen-4-crimson-to-dark-carmine-fade-hd.webp',
        'title' => 'Bicicleta Trek Domane AL 2 Gen 4 Crimson to Dark Carmine Fade HD',
    ],
    'trek_domane_al2_grey' => [
        'url' => 'https://www.elmycycles.co.uk/smsimg/287/7637-0-full-domaneal2-24-41587-a-primary-287.jpg',
        'target_name' => 'trek-domane-al-2-gen-4-matte-lithium-grey-hd.webp',
        'title' => 'Bicicleta Trek Domane AL 2 Gen 4 Matte Lithium Grey HD',
    ],
    'trek_emonda_alr5_slate' => [
        'url' => 'https://bikes.com.au/cdn/shop/files/EmondaALR5p1.webp?v=1709271493',
        'target_name' => 'trek-emonda-alr-5-slate-prismatic-hd.webp',
        'title' => 'Bicicleta Trek Émonda ALR 5 Slate Prismatic HD',
    ],
    'trek_emonda_alr5_crimson' => [
        'url' => 'https://bumsonthesaddle.com/cdn/shop/files/trek-emonda-alr-5-crimson-to-dark-carmine-fade-bumsonthesaddle-1.png?v=1760682837&width=1080',
        'target_name' => 'trek-emonda-alr-5-crimson-to-dark-carmine-fade-hd.webp',
        'title' => 'Bicicleta Trek Émonda ALR 5 Crimson to Dark Carmine Fade HD',
    ],
    'orbea_orca_titan_grey' => [
        'url' => 'https://tourynativabicicletas.com/wp-content/uploads/2026/03/BICICLETA-ORBEA-ORCA-M30-2026-RUTA-CAR-TIT-GREY-12VEL-8434446986314-2-1.jpg',
        'target_name' => 'orbea-orca-m30-diamond-carbon-view-titan-grey-hd.webp',
        'title' => 'Bicicleta Orbea Orca M30 Diamond Carbon View Titan Grey HD',
    ],
    'orbea_orca_iris_white' => [
        'url' => 'https://tourynativabicicletas.com/wp-content/uploads/2026/03/BICICLETA-ORBEA-ORCA-M30-2026-RUTA-WHI-LIL-CARBON-12VEL-8434446986253-2.jpg',
        'target_name' => 'orbea-orca-m30-iris-white-lilac-hd.webp',
        'title' => 'Bicicleta Orbea Orca M30 Iris White Lilac HD',
    ],
    'orbea_orca_cobalt_blue' => [
        'url' => 'https://cycledistrict.mx/cdn/shop/files/T106TTCC-A5-SIDE-ORCA_M30_over.jpg',
        'target_name' => 'orbea-orca-m30-cobalt-blue-carbon-raw-hd.webp',
        'title' => 'Bicicleta Orbea Orca M30 Cobalt Blue Carbon Raw HD',
    ],
    'shimano_deore_m5100' => [
        'url' => 'https://ae-pic-a1.aliexpress-media.com/kf/S8f25ffa7b4394b35a2dd1275628bd853W.jpg',
        'target_name' => 'shimano-deore-m5100-groupset-hd.webp',
        'title' => 'Grupo MTB Shimano Deore M5100 1x11V HD',
    ],
    'shimano_cues_10x2' => [
        'url' => 'https://cdn.shopify.com/s/files/1/0752/1271/8253/files/Cuesruta10x2.jpg',
        'target_name' => 'shimano-cues-u6030-2x10-groupset-hd.webp',
        'title' => 'Grupo Shimano CUES U6030 2x10V HD',
    ],
    'magene_soporte_gps' => [
        'url' => 'https://katrinabikeshop.com/cdn/shop/files/soporteparaciclocomputadormagene1.png?v=1778811470&width=1200',
        'target_name' => 'soporte-aerodinamico-gps-magene-hd.webp',
        'title' => 'Soporte Aerodinámico para Ciclocomputador Magene HD',
    ],
];

$uploadDir = wp_upload_dir();
$baseDir = $uploadDir['basedir'] . '/2026/09';
if (! is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

$installedAttachmentIds = [];

foreach ($assetsToFetch as $key => $info) {
    echo "Procesando activo: {$key}...\n";
    $finalPath = $baseDir . '/' . $info['target_name'];
    $relativeFile = '2026/09/' . $info['target_name'];
    
    // Verificar si ya existe adjunto registrado
    $existing = get_posts([
        'post_type' => 'attachment',
        'meta_query' => [
            ['key' => '_wp_attached_file', 'value' => $relativeFile]
        ],
        'posts_per_page' => 1,
    ]);
    
    if (! empty($existing)) {
        $attId = $existing[0]->ID;
        echo "  -> Ya existe attachment registrado con ID: $attId\n";
        $installedAttachmentIds[$key] = $attId;
        continue;
    }
    
    if ($isApply) {
        // Descargar a temporal
        $tmpFile = download_url($info['url'], 30);
        if (is_wp_error($tmpFile)) {
            echo "  [ERROR] Falló descarga de {$info['url']}: " . $tmpFile->get_error_message() . "\n";
            continue;
        }
        
        try {
            $im = new Imagick($tmpFile);
            $im->setImageFormat('webp');
            $im->setImageCompressionQuality(83);
            
            // Escalar si es excesivamente grande (> 1400px)
            $w = $im->getImageWidth();
            $h = $im->getImageHeight();
            $maxDim = max($w, $h);
            if ($maxDim > 1400) {
                $scale = 1400 / $maxDim;
                $im->resizeImage((int)round($w * $scale), (int)round($h * $scale), Imagick::FILTER_LANCZOS, 1);
            }
            
            $im->writeImage($finalPath);
            @unlink($tmpFile);
            
            $sizeKb = round(filesize($finalPath) / 1024, 1);
            $finalW = $im->getImageWidth();
            $finalH = $im->getImageHeight();
            echo "  -> Convertido a WebP: {$info['target_name']} ({$finalW}x{$finalH} px, {$sizeKb} KB)\n";
            
            // Crear Attachment Post
            $attachment = [
                'post_mime_type' => 'image/webp',
                'post_title'     => $info['title'],
                'post_content'   => '',
                'post_status'    => 'inherit',
                'guid'           => $uploadDir['baseurl'] . '/' . $relativeFile,
            ];
            
            $attId = wp_insert_attachment($attachment, $finalPath);
            $metaData = wp_generate_attachment_metadata($attId, $finalPath);
            wp_update_attachment_metadata($attId, $metaData);
            
            echo "  -> Registrado Attachment ID $attId\n";
            $installedAttachmentIds[$key] = $attId;
        } catch (Exception $e) {
            echo "  [ERROR] Error procesando con Imagick: " . $e->getMessage() . "\n";
            @unlink($tmpFile);
        }
    } else {
        echo "  [DRY-RUN] Se descargaría y crearía {$info['target_name']} en WebP\n";
    }
}

echo "\n============================================================\n";
echo "ACTUALIZACIÓN DE PRODUCTOS Y VARIACIONES\n";
echo "============================================================\n\n";

// 1. Trek Émonda ALR 5 (ID 1716-1731)
if (isset($installedAttachmentIds['trek_emonda_alr5_crimson'])) {
    $attCrimson = $installedAttachmentIds['trek_emonda_alr5_crimson'];
    $crimsonVars = [1716, 1717, 1718, 1719, 1720, 1721, 1722, 1723];
    foreach ($crimsonVars as $varId) {
        if ($isApply) {
            set_post_thumbnail($varId, $attCrimson);
        }
        echo "Émonda ALR 5 Var $varId (Crimson): asignada a Attachment $attCrimson\n";
    }
}

if (isset($installedAttachmentIds['trek_emonda_alr5_slate'])) {
    $attSlate = $installedAttachmentIds['trek_emonda_alr5_slate'];
    $slateVars = [1724, 1725, 1726, 1727, 1728, 1729, 1730, 1731];
    foreach ($slateVars as $varId) {
        if ($isApply) {
            set_post_thumbnail($varId, $attSlate);
        }
        echo "Émonda ALR 5 Var $varId (Slate): asignada a Attachment $attSlate\n";
    }
}

// 2. Trek Domane AL 2 Gen 4
if (isset($installedAttachmentIds['trek_domane_al2_crimson'])) {
    $attDomaneCrimson = $installedAttachmentIds['trek_domane_al2_crimson'];
    $crimsonVars = [1702, 1703, 1704, 1705, 1706, 1707, 1708];
    foreach ($crimsonVars as $varId) {
        if ($isApply) {
            set_post_thumbnail($varId, $attDomaneCrimson);
        }
        echo "Domane AL 2 Var $varId (Crimson): asignada a Attachment $attDomaneCrimson\n";
    }
}

if (isset($installedAttachmentIds['trek_domane_al2_grey'])) {
    $attDomaneGrey = $installedAttachmentIds['trek_domane_al2_grey'];
    $greyVars = [1695, 1696, 1697, 1698, 1699, 1700, 1701];
    foreach ($greyVars as $varId) {
        if ($isApply) {
            set_post_thumbnail($varId, $attDomaneGrey);
        }
        echo "Domane AL 2 Var $varId (Lithium Grey): asignada a Attachment $attDomaneGrey\n";
    }
}

// 3. Orbea Orca M30 (#676) y M30i (#677)
$orbeaProds = [676, 677];
foreach ($orbeaProds as $pId) {
    $p = wc_get_product($pId);
    if (! $p) continue;
    foreach ($p->get_children() as $cId) {
        $c = wc_get_product($cId);
        if (! $c) continue;
        $color = $c->get_attribute('pa_color');
        $targetAtt = null;
        if (strpos($color, 'Titan Grey') !== false && isset($installedAttachmentIds['orbea_orca_titan_grey'])) {
            $targetAtt = $installedAttachmentIds['orbea_orca_titan_grey'];
        } elseif (strpos($color, 'Iris White') !== false && isset($installedAttachmentIds['orbea_orca_iris_white'])) {
            $targetAtt = $installedAttachmentIds['orbea_orca_iris_white'];
        } elseif (strpos($color, 'Cobalt Blue') !== false && isset($installedAttachmentIds['orbea_orca_cobalt_blue'])) {
            $targetAtt = $installedAttachmentIds['orbea_orca_cobalt_blue'];
        }
        
        if ($targetAtt) {
            if ($isApply) {
                set_post_thumbnail($cId, $targetAtt);
            }
            echo "Orbea Orca Product $pId Var $cId ($color): asignada a Attachment $targetAtt\n";
        }
    }
}

// 4. GW Sprinter (#515) -> Asignar ID 232 (82540_1800x1800.webp)
$sprinter = wc_get_product(515);
if ($sprinter) {
    if ($isApply) {
        set_post_thumbnail(515, 232);
    }
    echo "GW Sprinter #515 (Destacada): asignada a Attachment 232 (1000x1000 WebP)\n";
    $blancoVars = [577, 583, 589, 595, 601, 607];
    foreach ($blancoVars as $vId) {
        if ($isApply) {
            set_post_thumbnail($vId, 232);
        }
        echo "GW Sprinter Var $vId (Blanco): asignada a Attachment 232\n";
    }
}

// 5. GW Flamma Disco Tiagra 10V (#509) -> Asignar ID 655 (cb212ae5...jpg) reemplazando screenshot 654
$flamma = wc_get_product(509);
if ($flamma) {
    if ($isApply) {
        set_post_thumbnail(509, 655);
    }
    echo "GW Flamma Disco Tiagra #509 (Destacada): asignada a Attachment 655 (1000x1000 Studio)\n";
    $flammaVars = [552, 553, 554, 555, 556];
    foreach ($flammaVars as $vId) {
        if ($isApply) {
            set_post_thumbnail($vId, 655);
        }
        echo "GW Flamma Var $vId: asignada a Attachment 655\n";
    }
}

// 6. Grupo Shimano Deore M5100 (#1950) -> Reemplazar ID 1954 en galería
if (isset($installedAttachmentIds['shimano_deore_m5100'])) {
    $attDeore = $installedAttachmentIds['shimano_deore_m5100'];
    $pDeore = wc_get_product(1950);
    if ($pDeore) {
        $gallery = $pDeore->get_gallery_image_ids();
        $gallery = array_diff($gallery, [1954]);
        $gallery[] = $attDeore;
        if ($isApply) {
            $pDeore->set_gallery_image_ids($gallery);
            $pDeore->save();
        }
        echo "Grupo Shimano Deore #1950: galería actualizada con Attachment $attDeore (removido ID 1954)\n";
    }
}

// 7. Grupo Shimano CUES U6030 (#1934) -> Reemplazar ID 1935 en galería
if (isset($installedAttachmentIds['shimano_cues_10x2'])) {
    $attCues = $installedAttachmentIds['shimano_cues_10x2'];
    $pCues = wc_get_product(1934);
    if ($pCues) {
        $gallery = $pCues->get_gallery_image_ids();
        $gallery = array_diff($gallery, [1935]);
        $gallery[] = $attCues;
        if ($isApply) {
            $pCues->set_gallery_image_ids($gallery);
            $pCues->save();
        }
        echo "Grupo Shimano CUES #1934: galería actualizada con Attachment $attCues (removido ID 1935)\n";
    }
}

// 8. Soporte para Ciclocomputador Magene Básico (#1824) -> Reemplazar imagen destacada
if (isset($installedAttachmentIds['magene_soporte_gps'])) {
    $attSoporte = $installedAttachmentIds['magene_soporte_gps'];
    if ($isApply) {
        set_post_thumbnail(1824, $attSoporte);
    }
    echo "Soporte Magene #1824: destacada actualizada con Attachment $attSoporte\n";
}

echo "\nProceso de activos HD finalizado con éxito.\n";
