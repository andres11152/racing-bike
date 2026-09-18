<?php

/**
 * Rellena el ALT (_wp_attachment_image_alt) que audit-image-seo.php marcó
 * como faltante en los 4 roles con uso real detectado y que NO cubría
 * generate-alt-text.php (ese script sólo tocaba imagen principal y galería
 * de producto):
 *
 *  - Variaciones de producto: nombre del producto + resumen de atributos
 *    de esa variación (color/talla), que es justo lo que distingue una foto
 *    de otra dentro del mismo producto.
 *  - Slides del hero de home: sin ALT propio, hero-carousel.blade.php cae
 *    al título del slide (ver Home.php) — un eslogan de marketing ("NO
 *    PIERDAS ESTA OPORTUNIDAD"), no una descripción de la imagen. El sitio
 *    entero pasa por este componente, así que es el de mayor impacto real.
 *  - Miniaturas de categoría y logos de marca: el ALT en pantalla YA es
 *    correcto hoy (front-page.blade.php y template-about.blade.php lo
 *    escriben directo como `alt="{{ $category['name'] }}"` / `alt="{{
 *    $brand->name }}"`, sin pasar por este metadato). Se rellena aquí de
 *    todas formas por si algún día se reutiliza la imagen vía
 *    wp_get_attachment_image() en otro lugar que sí lea el metadato.
 *
 * Solo escribe donde el ALT está vacío: no pisa nada escrito a mano.
 *
 * Usage: wp --skip-themes eval-file scripts/fill-image-alt-gaps.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

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

$stats = ['variacion' => 0, 'hero' => 0, 'categoria' => 0, 'marca' => 0];

// 1) Variaciones de producto.
echo "== Variaciones de producto ==\n";
$productIds = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'private'],
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($productIds as $productId) {
    $product = wc_get_product($productId);
    if (! $product || ! $product->is_type('variable')) {
        continue;
    }

    $name = $product->get_name();
    if ($name === '') {
        continue;
    }

    foreach ($product->get_children() as $variationId) {
        $thumbId = get_post_meta($variationId, '_thumbnail_id', true);
        if (! $thumbId) {
            continue;
        }

        $variation = wc_get_product($variationId);
        $summary = $variation ? $variation->get_attribute_summary() : '';
        $alt = $summary !== '' ? sprintf('%s - %s', $name, $summary) : $name;

        if (rb_set_alt_if_empty((int) $thumbId, $alt, $apply)) {
            $stats['variacion']++;
        }
    }
}

// 2) Slides del hero de home.
echo "\n== Slides del hero ==\n";
$slideIds = get_posts([
    'post_type' => 'rb_slide',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($slideIds as $slideId) {
    $thumbId = get_post_thumbnail_id($slideId);
    if (! $thumbId) {
        continue;
    }

    $title = get_the_title($slideId);
    // El eslogan del slide identifica la promoción, no lo que se ve en la
    // foto — se agrega el nombre del sitio para dar contexto real sin
    // inventar contenido visual que el script no puede saber que existe.
    $alt = $title !== '' ? sprintf('%s - Racing Bike 1998', $title) : 'Racing Bike 1998';

    if (rb_set_alt_if_empty((int) $thumbId, $alt, $apply)) {
        $stats['hero']++;
    }
}

// 3) Miniaturas de categoría de producto.
echo "\n== Miniaturas de categoría ==\n";
$categoryTerms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
if (! is_wp_error($categoryTerms)) {
    foreach ($categoryTerms as $term) {
        $thumbId = get_term_meta($term->term_id, 'thumbnail_id', true);
        if (! $thumbId) {
            continue;
        }

        if (rb_set_alt_if_empty((int) $thumbId, $term->name, $apply)) {
            $stats['categoria']++;
        }
    }
}

// 4) Logos de marca (pa_marca).
echo "\n== Logos de marca ==\n";
if (taxonomy_exists('pa_marca')) {
    $brandTerms = get_terms(['taxonomy' => 'pa_marca', 'hide_empty' => false]);
    if (! is_wp_error($brandTerms)) {
        foreach ($brandTerms as $term) {
            $logoId = get_term_meta($term->term_id, '_rb_brand_logo_id', true);
            if (! $logoId) {
                continue;
            }

            $alt = sprintf('Logo %s', $term->name);

            if (rb_set_alt_if_empty((int) $logoId, $alt, $apply)) {
                $stats['marca']++;
            }
        }
    }
}

echo "\n";
printf(
    "%s: %d ALT de variación, %d de hero, %d de categoría, %d de marca.\n",
    $apply ? 'Aplicado' : 'Se aplicaría',
    $stats['variacion'],
    $stats['hero'],
    $stats['categoria'],
    $stats['marca']
);
