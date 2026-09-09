<?php
/**
 * Corrige la asignación de imágenes de producto.
 *
 * Un agente anterior subió 4 fotografías reales de bicicletas y las repartió
 * al azar entre los 10 productos — incluidos accesorios como el bidón, el
 * casco o el sillín, que terminaron mostrando una bicicleta completa como
 * imagen principal. Esto reasigna las fotos reales sólo a las bicicletas
 * (por disciplina) y regenera un marcador de posición honesto para los
 * accesorios, que no tienen fotografía propia.
 *
 * Ejecutar:
 *   docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/fix-product-images.php
 *
 * Idempotente.
 */

if (! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce no está activo.');
}

/** Busca un adjunto ya subido por nombre de archivo (sin extensión). */
function rb_find_attachment_by_filename(string $needle): int
{
    global $wpdb;

    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
        '%/' . $needle . '.jpg'
    ));

    return (int) $id;
}

/** Genera (o reutiliza) un marcador de posición esquemático para un accesorio. */
function rb_accessory_placeholder(string $title, string $sku): int
{
    $slug = sanitize_title("rb-ph-{$sku}");

    $existing = get_posts([
        'post_type' => 'attachment',
        'name' => $slug,
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    if ($existing) {
        return (int) $existing[0];
    }

    $w = 1000;
    $h = 1250;
    $im = imagecreatetruecolor($w, $h);

    $bg = imagecolorallocate($im, 0x22, 0x22, 0x22);
    $line = imagecolorallocate($im, 0x6a, 0x6a, 0x6a);
    $ink = imagecolorallocate($im, 0xcc, 0xcc, 0xcc);

    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // Silueta esquemática genérica de accesorio (círculo + etiqueta), para
    // no fingir ser una fotografía real de producto.
    imagesetthickness($im, 6);
    imageellipse($im, (int) ($w / 2), (int) ($h / 2) - 40, 260, 260, $line);
    imagestring($im, 5, 60, $h - 90, strtoupper($title), $ink);

    ob_start();
    imagejpeg($im, null, 86);
    $data = ob_get_clean();
    imagedestroy($im);

    $upload = wp_upload_bits("{$slug}.jpg", null, $data);

    if (! empty($upload['error'])) {
        WP_CLI::warning("No se pudo generar el marcador para {$title}");

        return 0;
    }

    $attachmentId = wp_insert_attachment([
        'post_mime_type' => 'image/jpeg',
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => 'inherit',
    ], $upload['file']);

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($attachmentId, wp_generate_attachment_metadata($attachmentId, $upload['file']));
    update_post_meta($attachmentId, '_wp_attachment_image_alt', $title);

    return (int) $attachmentId;
}

/* --- Bicicletas: fotografía real, asignada por disciplina --- */

$bikeAssignments = [
    'RB-APEX-CARBON' => 'bike-aero-race',
    'RB-ENDURANCE-R2' => 'bike-road-pro',
    'RB-GRAVEL-PRO' => 'bike-gravel-pro',
    'RB-TRAIL-GRAVEL-X' => 'bike-gravel-pro',
    'RB-SPRINT-ALLOY' => 'bike-track-endurance',
];

foreach ($bikeAssignments as $sku => $filename) {
    $productId = wc_get_product_id_by_sku($sku);
    $imageId = rb_find_attachment_by_filename($filename);

    if (! $productId || ! $imageId) {
        WP_CLI::warning("Sin coincidencia para {$sku} / {$filename}");
        continue;
    }

    $product = wc_get_product($productId);
    $product->set_image_id($imageId);
    // Ninguna bicicleta debe arrastrar fotos de otro modelo en su galería.
    $product->set_gallery_image_ids([]);
    $product->save();

    WP_CLI::log("✓ {$product->get_name()} → {$filename}.jpg");
}

/* --- Accesorios y componentes: marcador de posición propio, no una bici --- */

$accessorySkus = [
    'RB-CASCO-AERO',
    'RB-RUEDAS-C50',
    'RB-SILLIN-RACE',
    'RB-GUANTES-PRO',
    'RB-BIDON-750',
];

foreach ($accessorySkus as $sku) {
    $productId = wc_get_product_id_by_sku($sku);

    if (! $productId) {
        continue;
    }

    $product = wc_get_product($productId);
    $imageId = rb_accessory_placeholder($product->get_name(), $sku);

    if ($imageId) {
        $product->set_image_id($imageId);
        $product->set_gallery_image_ids([]);
        $product->save();
        WP_CLI::log("✓ {$product->get_name()} → marcador de posición");
    }
}

WP_CLI::success('Imágenes de producto corregidas.');
