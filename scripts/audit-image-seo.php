<?php

/**
 * Auditoría de SEO de imagen para TODO adjunto de imagen del sitio, no solo
 * las de producto: portada de entradas rb_slide, thumbnail de categorías
 * de producto (product_cat), logos de marca (pa_marca), fotos de reseña
 * con foto (rb_reviews) y cualquier imagen adjunta que quede fuera de esos
 * roles ("huérfana" — sirve para decidir si hace falta revisarla a mano).
 *
 * Sólo lee. No escribe nada en la base de datos — es el insumo para decidir
 * qué scripts de corrección (alt/título/descripción/nombre de archivo)
 * hacen falta y en qué orden, antes de tocar producción.
 *
 * Reporta, por imagen: rol de uso, si el nombre de archivo es genérico
 * (IMG_1234, DSC00234, screenshot, sólo números/hash), si falta ALT,
 * título o descripción, y si el archivo pesa más de 200KB o no es WebP
 * (duplica la auditoría de audit-all-product-images.php a propósito, para
 * que un único reporte cubra TODO el sitio, no sólo catálogo).
 *
 * Usage: wp --skip-themes eval-file scripts/audit-image-seo.php
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

const RB_BRAND_TAXONOMY = 'pa_marca';

/**
 * Patrones de nombre de archivo que no dicen nada sobre el contenido de la
 * imagen: nombres de cámara/captura, o sólo dígitos/hash de subida.
 */
function rb_is_generic_filename(string $filename): bool
{
    $name = pathinfo($filename, PATHINFO_FILENAME);

    $genericPatterns = [
        '/^img[-_]?\d+/i',
        '/^dsc[-_]?\d+/i',
        '/^dji[-_]?\d+/i',
        '/^screenshot/i',
        '/^captura/i',
        '/^photo[-_]?\d+/i',
        '/^image\d*/i',
        '/^\d+$/',                      // sólo números
        '/^[a-f0-9]{8,}$/i',            // hash/uuid sin palabras
        '/^untitled/i',
        '/^sin[-_]?t[íi]tulo/i',
    ];

    foreach ($genericPatterns as $pattern) {
        if (preg_match($pattern, $name)) {
            return true;
        }
    }

    return false;
}

function rb_attachment_report(int $attachmentId, string $role, string $context): array
{
    $file = get_attached_file($attachmentId);
    $filename = $file ? basename($file) : '(sin archivo)';
    $alt = trim((string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true));
    $post = get_post($attachmentId);
    $title = $post ? trim($post->post_title) : '';
    $caption = $post ? trim($post->post_excerpt) : '';
    $description = $post ? trim($post->post_content) : '';

    $sizeKb = ($file && file_exists($file)) ? round(filesize($file) / 1024, 1) : null;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $issues = [];
    if ($alt === '') {
        $issues[] = 'sin ALT';
    }
    if ($title === '' || $title === pathinfo($filename, PATHINFO_FILENAME)) {
        $issues[] = 'título = nombre de archivo (sin editar)';
    }
    if ($description === '') {
        $issues[] = 'sin descripción';
    }
    if (rb_is_generic_filename($filename)) {
        $issues[] = 'nombre de archivo genérico';
    }
    if ($ext !== 'webp') {
        $issues[] = "formato $ext (no WebP)";
    }
    if ($sizeKb !== null && $sizeKb > 200) {
        $issues[] = "{$sizeKb} KB (pesada)";
    }
    if ($sizeKb === null) {
        $issues[] = 'archivo no encontrado en disco';
    }

    return [
        'id' => $attachmentId,
        'role' => $role,
        'context' => $context,
        'filename' => $filename,
        'alt' => $alt,
        'title' => $title,
        'caption' => $caption,
        'description' => $description,
        'issues' => $issues,
    ];
}

$rows = [];
$seenIds = [];

function rb_add_row(array &$rows, array &$seenIds, int $id, string $role, string $context): void
{
    if (! $id || isset($seenIds[$id . '|' . $role])) {
        return;
    }
    $seenIds[$id . '|' . $role] = true;
    $rows[] = rb_attachment_report($id, $role, $context);
}

// 1) Productos: imagen principal, galería y variaciones.
$productIds = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'private'],
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($productIds as $productId) {
    $product = wc_get_product($productId);
    if (! $product) {
        continue;
    }

    $name = $product->get_name();

    $mainId = $product->get_image_id();
    if ($mainId) {
        rb_add_row($rows, $seenIds, (int) $mainId, 'producto:principal', "[$productId] $name");
    }

    foreach ($product->get_gallery_image_ids() as $galleryId) {
        rb_add_row($rows, $seenIds, (int) $galleryId, 'producto:galeria', "[$productId] $name");
    }

    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variationId) {
            $thumbId = get_post_meta($variationId, '_thumbnail_id', true);
            if ($thumbId) {
                rb_add_row($rows, $seenIds, (int) $thumbId, 'producto:variacion', "[$productId] $name (var $variationId)");
            }
        }
    }
}

// 2) Slides del hero de la home (rb_slide).
$slideIds = get_posts([
    'post_type' => 'rb_slide',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($slideIds as $slideId) {
    $thumbId = get_post_thumbnail_id($slideId);
    if ($thumbId) {
        rb_add_row($rows, $seenIds, (int) $thumbId, 'slide:hero', '[' . $slideId . '] ' . get_the_title($slideId));
    }
}

// 3) Miniaturas de categoría de producto.
$categoryTerms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
if (! is_wp_error($categoryTerms)) {
    foreach ($categoryTerms as $term) {
        $thumbId = get_term_meta($term->term_id, 'thumbnail_id', true);
        if ($thumbId) {
            rb_add_row($rows, $seenIds, (int) $thumbId, 'categoria:thumbnail', $term->name);
        }
    }
}

// 4) Logos de marca (pa_marca) — term meta `_rb_brand_logo_id`, ver
// app/product-brands.php::BRAND_LOGO_META_KEY.
if (taxonomy_exists(RB_BRAND_TAXONOMY)) {
    $brandTerms = get_terms(['taxonomy' => RB_BRAND_TAXONOMY, 'hide_empty' => false]);
    if (! is_wp_error($brandTerms)) {
        foreach ($brandTerms as $term) {
            $logoId = get_term_meta($term->term_id, '_rb_brand_logo_id', true);
            if ($logoId) {
                rb_add_row($rows, $seenIds, (int) $logoId, 'marca:logo', $term->name);
            }
        }
    }
}

// 5) Fotos de reseña (rb_reviews) — comment meta `rb_photo_ids`, lista de
// IDs separados por coma (ver racing-bike-reviews.php).
global $wpdb;
$reviewPhotoLists = $wpdb->get_col(
    "SELECT DISTINCT meta_value FROM {$wpdb->commentmeta}
     WHERE meta_key = 'rb_photo_ids' AND meta_value != ''"
);
foreach ($reviewPhotoLists as $list) {
    foreach (array_filter(array_map('absint', explode(',', $list))) as $photoId) {
        rb_add_row($rows, $seenIds, $photoId, 'resena:foto', 'Reseña con foto');
    }
}

// 6) El resto: cualquier adjunto de imagen que no cayó en ningún rol de arriba.
$allImageIds = get_posts([
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_status' => 'inherit',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$claimedIds = [];
foreach ($rows as $r) {
    $claimedIds[$r['id']] = true;
}

$orphanCount = 0;
foreach ($allImageIds as $imgId) {
    if (! isset($claimedIds[$imgId])) {
        rb_add_row($rows, $seenIds, (int) $imgId, 'sin_rol_detectado', get_the_title($imgId) ?: '(sin título)');
        $orphanCount++;
    }
}

// ---- Reporte ----

echo "============================================================\n";
echo "AUDITORÍA SEO DE IMÁGENES — TODO EL SITIO (RACING BIKE)\n";
echo "============================================================\n\n";

echo 'Total de adjuntos de imagen en la biblioteca: ' . count($allImageIds) . "\n";
echo 'Total de imágenes con rol de uso identificado: ' . (count($rows) - $orphanCount) . "\n";
echo "Imágenes sin rol detectado (huérfanas o de contenido libre): $orphanCount\n\n";

$byRole = [];
foreach ($rows as $r) {
    $byRole[$r['role']]['total'] = ($byRole[$r['role']]['total'] ?? 0) + 1;
    if ($r['issues']) {
        $byRole[$r['role']]['con_problemas'] = ($byRole[$r['role']]['con_problemas'] ?? 0) + 1;
    }
}

echo "POR ROL DE USO:\n";
foreach ($byRole as $role => $stats) {
    printf("  - %-24s %3d imágenes, %3d con al menos un problema\n", $role, $stats['total'], $stats['con_problemas'] ?? 0);
}
echo "\n";

$issueCounts = [];
foreach ($rows as $r) {
    foreach ($r['issues'] as $issue) {
        // Agrupar issues de peso/formato bajo una etiqueta genérica para el resumen.
        $key = preg_match('/^\d+(\.\d+)? KB/', $issue) ? 'pesada (>200KB)' : (preg_match('/^formato \w+/', $issue) ? 'no WebP' : $issue);
        $issueCounts[$key] = ($issueCounts[$key] ?? 0) + 1;
    }
}
arsort($issueCounts);

echo "PROBLEMAS MÁS FRECUENTES:\n";
foreach ($issueCounts as $issue => $count) {
    printf("  - %-45s %d imágenes\n", $issue, $count);
}
echo "\n";

echo "DETALLE POR IMAGEN CON PROBLEMAS (agrupado por rol):\n";
echo "------------------------------------------------------------\n";
$currentRole = null;
foreach ($rows as $r) {
    if (! $r['issues']) {
        continue;
    }
    if ($r['role'] !== $currentRole) {
        $currentRole = $r['role'];
        echo "\n[$currentRole]\n";
    }
    printf("  ID %-6d %-40s -> %s\n", $r['id'], mb_substr($r['context'], 0, 40), implode(', ', $r['issues']));
    printf("             archivo: %s\n", $r['filename']);
}

echo "\n============================================================\n";
echo "Fin de la auditoría. Nada se modificó.\n";
