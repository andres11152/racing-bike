<?php
// Seeder de 6 reseñas de prueba con imagen de demostración.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$product_id = 18; // RB Gravel Pro
$product = wc_get_product($product_id);
if (!$product) {
    WP_CLI::error("No se encontró el producto con ID 18.");
}

$mock_reviews = [
    [
        'author' => 'Juan Carlos Pérez',
        'email' => 'juan.perez@example.com',
        'title' => 'Excelente respuesta en terrenos mixtos',
        'content' => 'Llevo 3 meses usando esta bicicleta en trochas de Cundinamarca y se comporta espectacular. La rigidez y comodidad del marco son inmejorables.',
        'rating' => 5,
        'height' => 175,
        'size' => 'M',
        'discipline' => 'Gravel',
        'months' => 3
    ],
    [
        'author' => 'Camila Restrepo',
        'email' => 'camila.res@example.com',
        'title' => 'Una máquina brutal',
        'content' => 'El grupo Apex responde muy bien en las subidas más empinadas. La entrega fue super rápida en Medellín y llegó 100% armada como prometieron.',
        'rating' => 5,
        'height' => 165,
        'size' => 'S',
        'discipline' => 'Gravel / Bikepacking',
        'months' => 2
    ],
    [
        'author' => 'Mateo Gómez',
        'email' => 'mateo.g@example.com',
        'title' => 'Muy buena relación calidad-precio',
        'content' => 'La relación de transmisión es perfecta para el relieve colombiano. He hecho varias rutas largas y cero problemas. La recomiendo totalmente.',
        'rating' => 5,
        'height' => 182,
        'size' => 'L',
        'discipline' => 'Gravel',
        'months' => 5
    ],
    [
        'author' => 'Valentina Hoyos',
        'email' => 'vale.hoyos@example.com',
        'title' => 'Súper cómoda y ágil',
        'content' => 'Me encanta lo ligera y manejable que es en destapado. Los frenos tienen un agarre excelente en descensos húmedos.',
        'rating' => 5,
        'height' => 163,
        'size' => 'S',
        'discipline' => 'Recreativo / Gravel',
        'months' => 1
    ],
    [
        'author' => 'Andrés Felipe Muñoz',
        'email' => 'andres.felipe@example.com',
        'title' => 'Superó mis expectativas',
        'content' => 'Excelente servicio y atención en la tienda de Bogotá. Me asesoraron muy bien con la talla biomecánica. La bici vuela en el plano y es muy estable en bajada.',
        'rating' => 5,
        'height' => 177,
        'size' => 'M',
        'discipline' => 'Gravel / Endurance',
        'months' => 4
    ],
    [
        'author' => 'Santiago Arango',
        'email' => 'santi.arango@example.com',
        'title' => 'Resistente y lista para rodar',
        'content' => 'Hice el viaje de Bogotá a Villa de Leyva por trocha y la bicicleta aguantó el castigo sin ningún desajuste. Muy feliz con la compra.',
        'rating' => 4,
        'height' => 170,
        'size' => 'M',
        'discipline' => 'Bikepacking',
        'months' => 3
    ]
];

$source_img = '/var/www/html/wp-content/themes/racing-bike-theme/../scripts/customer_review_test.jpg';
if (!file_exists($source_img)) {
    WP_CLI::error("No se encontró la imagen de prueba en: " . $source_img);
}

WP_CLI::log("Iniciando importación de reseñas...");

foreach ($mock_reviews as $index => $r) {
    // Insertar comentario
    $comment_id = wp_insert_comment([
        'comment_post_ID'      => $product_id,
        'comment_author'       => $r['author'],
        'comment_author_email' => $r['email'],
        'comment_content'      => $r['content'],
        'comment_type'         => 'review',
        'comment_approved'     => 1,
        'comment_date'         => current_time('mysql'),
    ]);

    if (!$comment_id) {
        WP_CLI::warning("No se pudo crear la reseña para " . $r['author']);
        continue;
    }

    update_comment_meta($comment_id, 'rating', $r['rating']);
    update_comment_meta($comment_id, 'verified', 1);
    update_comment_meta($comment_id, 'rb_title', $r['title']);
    update_comment_meta($comment_id, 'rb_height_cm', $r['height']);
    update_comment_meta($comment_id, 'rb_size_bought', $r['size']);
    update_comment_meta($comment_id, 'rb_discipline', $r['discipline']);
    update_comment_meta($comment_id, 'rb_months_used', $r['months']);

    // Sideload de la imagen de prueba
    $tmp_file = tempnam(sys_get_temp_dir(), 'review_img_');
    copy($source_img, $tmp_file);

    $file_array = [
        'name' => 'resena_test_' . ($index + 1) . '.jpg',
        'tmp_name' => $tmp_file
    ];

    $attach_id = media_handle_sideload($file_array, $product_id);

    if (is_wp_error($attach_id)) {
        @unlink($tmp_file);
        WP_CLI::warning("No se pudo adjuntar la imagen para " . $r['author'] . ": " . $attach_id->get_error_message());
    } else {
        update_comment_meta($comment_id, 'rb_photo_ids', $attach_id);
    }
}

// Recalcular ratings del producto
if (function_exists('rb_reviews_recalculate_rating')) {
    rb_reviews_recalculate_rating($product_id);
}

WP_CLI::success("Se crearon 6 reseñas con imagen de prueba para el producto ID 18.");
