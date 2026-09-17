<?php

/**
 * Rellena el texto ALT de las imágenes de producto que no tienen ninguno
 * (auditoría del 2026-09-17: 55/55 imágenes principales publicadas sin
 * ALT). No inventa descripciones de la foto en sí — usa el nombre del
 * producto, que es exactamente lo que Google y un lector de pantalla
 * necesitan para entender qué se ve ("qué es esto", no "cómo se ve").
 *
 * Solo escribe donde el ALT está vacío: no pisa ningún texto que alguien
 * ya haya escrito a mano.
 *
 * Usage: wp --skip-themes eval-file scripts/generate-alt-text.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

/**
 * Pone el ALT si está vacío. Devuelve true si (habría) escrito algo.
 */
function rb_set_alt_if_empty(int $imageId, string $alt, bool $apply): bool
{
    $current = trim((string) get_post_meta($imageId, '_wp_attachment_image_alt', true));

    if ($current !== '') {
        return false;
    }

    echo "  imagen {$imageId} -> \"{$alt}\"\n";

    if ($apply) {
        update_post_meta($imageId, '_wp_attachment_image_alt', $alt);
    }

    return true;
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'private'],
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$stats = ['main' => 0, 'gallery' => 0];

foreach ($ids as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        continue;
    }

    $name = $product->get_name();

    if ($name === '') {
        continue; // los productos fantasma sin nombre no tienen de dónde sacar un ALT razonable
    }

    echo "[{$productId}] {$name}\n";

    $mainImageId = $product->get_image_id();

    if ($mainImageId && rb_set_alt_if_empty($mainImageId, $name, $apply)) {
        $stats['main']++;
    }

    $galleryIds = $product->get_gallery_image_ids();

    foreach ($galleryIds as $i => $galleryId) {
        $alt = sprintf('%s - vista %d', $name, $i + 2); // +2: la "vista 1" es la imagen principal, ya cubierta arriba
        if (rb_set_alt_if_empty($galleryId, $alt, $apply)) {
            $stats['gallery']++;
        }
    }
}

echo "\n";
printf("%s: %d ALT de imagen principal, %d ALT de galería.\n", $apply ? 'Aplicado' : 'Se aplicaría', $stats['main'], $stats['gallery']);
