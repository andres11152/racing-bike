<?php
/**
 * Siembra los slides del carrusel y las imágenes de categoría de la home.
 *
 * Ejecutar:
 *   docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-home.php
 *
 * Idempotente.
 */

/**
 * Genera un banner de marcador de posición con GD.
 *
 * Bandas diagonales sobre carbón: sugiere velocidad sin pretender ser una foto.
 */
function rb_banner(string $slug, string $label, int $w = 2000, int $h = 900): int
{
    $existing = get_posts([
        'post_type' => 'attachment',
        'name' => sanitize_title("rb-banner-{$slug}"),
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    if ($existing) {
        return (int) $existing[0];
    }

    $im = imagecreatetruecolor($w, $h);
    imagefilledrectangle($im, 0, 0, $w, $h, imagecolorallocate($im, 0x18, 0x18, 0x18));

    // Bandas diagonales de contraste bajo.
    $band = imagecolorallocate($im, 0x26, 0x26, 0x26);
    for ($x = -$h; $x < $w; $x += 180) {
        $poly = [$x, $h, $x + 90, $h, $x + 90 + $h, 0, $x + $h, 0];
        imagefilledpolygon($im, $poly, $band);
    }

    // Silueta de bicicleta a la derecha, fuera de la zona de texto.
    $line = imagecolorallocate($im, 0x55, 0x55, 0x55);
    imagesetthickness($im, 14);
    $cx = (int) ($w * 0.68);
    $cy = (int) ($h * 0.52);
    imageellipse($im, $cx - 230, $cy + 130, 330, 330, $line);
    imageellipse($im, $cx + 230, $cy + 130, 330, 330, $line);
    imageline($im, $cx - 230, $cy + 130, $cx - 20, $cy - 140, $line);
    imageline($im, $cx - 20, $cy - 140, $cx + 100, $cy - 140, $line);
    imageline($im, $cx + 100, $cy - 140, $cx + 230, $cy + 130, $line);
    imageline($im, $cx - 20, $cy - 140, $cx - 90, $cy + 130, $line);
    imageline($im, $cx - 20, $cy - 140, $cx + 45, $cy + 10, $line);
    imageline($im, $cx + 45, $cy + 10, $cx + 230, $cy + 130, $line);
    imageline($im, $cx + 100, $cy - 140, $cx + 145, $cy - 250, $line);
    imageline($im, $cx + 145, $cy - 250, $cx + 225, $cy - 250, $line);

    ob_start();
    imagejpeg($im, null, 88);
    $data = ob_get_clean();
    imagedestroy($im);

    $upload = wp_upload_bits("rb-banner-{$slug}.jpg", null, $data);

    if (! empty($upload['error'])) {
        WP_CLI::warning("No se pudo subir el banner {$slug}");

        return 0;
    }

    $id = wp_insert_attachment([
        'post_mime_type' => 'image/jpeg',
        'post_title' => $label,
        'post_name' => sanitize_title("rb-banner-{$slug}"),
        'post_status' => 'inherit',
    ], $upload['file']);

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
    update_post_meta($id, '_wp_attachment_image_alt', $label);

    return (int) $id;
}

/* --- Slides del carrusel --- */

$shopUrl = get_permalink(wc_get_page_id('shop'));

$slides = [
    [
        'slug' => 'ruta',
        'title' => 'La ruta no perdona',
        'eyebrow' => 'Colección Ruta',
        'cta' => 'Ver bicicletas de ruta',
        'url' => get_term_link(get_term_by('name', 'Ruta', 'product_cat')),
        'order' => 1,
    ],
    [
        'slug' => 'gravel',
        'title' => 'Donde termina el asfalto',
        'eyebrow' => 'Colección Gravel',
        'cta' => 'Ver bicicletas gravel',
        'url' => get_term_link(get_term_by('name', 'Gravel', 'product_cat')),
        'order' => 2,
    ],
    [
        'slug' => 'taller',
        'title' => 'Armado y ajuste incluidos',
        'eyebrow' => 'Servicio',
        'cta' => 'Ver catálogo',
        'url' => $shopUrl,
        'order' => 3,
    ],
];

foreach ($slides as $slide) {
    $existing = get_posts([
        'post_type' => 'rb_slide',
        'name' => sanitize_title($slide['title']),
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    if ($existing) {
        WP_CLI::log("· Slide «{$slide['title']}» ya existe.");
        continue;
    }

    $id = wp_insert_post([
        'post_type' => 'rb_slide',
        'post_title' => $slide['title'],
        'post_name' => sanitize_title($slide['title']),
        'post_excerpt' => $slide['eyebrow'],
        'post_status' => 'publish',
        'menu_order' => $slide['order'],
    ]);

    update_post_meta($id, '_rb_slide_url', is_string($slide['url']) ? $slide['url'] : $shopUrl);
    update_post_meta($id, '_rb_slide_cta', $slide['cta']);

    if ($imageId = rb_banner($slide['slug'], $slide['title'])) {
        set_post_thumbnail($id, $imageId);
    }

    WP_CLI::log("✓ Slide «{$slide['title']}»");
}

/* --- Imágenes de las categorías principales (mega menú + tira de la home) --- */

foreach (['Bicicletas', 'Componentes', 'Accesorios'] as $categoryName) {
    $term = get_term_by('name', $categoryName, 'product_cat');

    if (! $term || get_term_meta($term->term_id, 'thumbnail_id', true)) {
        continue;
    }

    if ($imageId = rb_banner('cat-' . sanitize_title($categoryName), $categoryName, 1200, 900)) {
        update_term_meta($term->term_id, 'thumbnail_id', $imageId);
        WP_CLI::log("✓ Imagen de categoría «{$categoryName}»");
    }
}

/* --- La home debe usar front-page.blade.php, no el listado del blog --- */

update_option('show_on_front', 'posts');

WP_CLI::success('Home sembrada.');
