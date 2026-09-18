<?php

/**
 * Renombra a slugs SEO los archivos de imagen del catálogo (producto
 * principal/galería/variación), los slides del hero y las miniaturas de
 * categoría. De "81093_1_1400x.jpg" o "6fe3a0fc-afbf-...-1800x1800.webp"
 * a "casco-de-ciclismo-gw-rc.webp" — un nombre de archivo genérico no le
 * dice nada a Google Imágenes ni a nadie que comparta el link directo de
 * la foto.
 *
 * ESTRATEGIA "COPIAR, NO MOVER": el archivo original queda intacto en su
 * ruta de siempre. Se copia a la ruta nueva (mismo directorio año/mes) y
 * SOLO se actualizan los punteros de WordPress (_wp_attached_file, guid,
 * metadata de tamaños) para que el sitio sirva el archivo nuevo a partir
 * de ahora. Cualquier URL vieja ya indexada por Google Imágenes o
 * guardada en algún lado sigue respondiendo 200 con el archivo de
 * siempre — no hay redirects que mantener ni enlaces que se rompan. El
 * costo es duplicar espacio en disco de las imágenes tocadas; frente al
 * riesgo de un 404 en producción, es el trade-off correcto.
 *
 * No toca: SVG de marca (son pocos y ya se listan aparte si hace falta),
 * ni las 197 imágenes sin rol de uso detectado por audit-image-seo.php
 * (renombrar algo que nadie sirve no tiene ningún beneficio de SEO).
 *
 * También deja el título del adjunto (post_title) en el mismo texto legible
 * que ya usa el ALT, para que la biblioteca de medios y la (noindexada)
 * página de adjunto dejen de mostrar el nombre de archivo crudo.
 *
 * Usage: wp --skip-themes eval-file scripts/apply-enterprise-image-filenames.php apply
 * Sin "apply" solo imprime qué haría (dry run) — no copia ningún archivo.
 *
 * Se puede acotar a un solo rol con un segundo/tercer argumento, p.ej.:
 *   wp ... eval-file scripts/apply-enterprise-image-filenames.php apply hero
 *   wp ... eval-file scripts/apply-enterprise-image-filenames.php apply categoria
 * Roles válidos: hero, categoria, producto (default: todos).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$args = $args ?? [];
$apply = in_array('apply', $args, true);
$onlyRole = null;
foreach (['hero', 'categoria', 'producto'] as $roleFilter) {
    if (in_array($roleFilter, $args, true)) {
        $onlyRole = $roleFilter;
    }
}

echo $apply ? "MODO: aplicando cambios (copiando archivos)\n" : "MODO: dry-run (no se copia ningún archivo; agrega \"apply\" para ejecutar)\n";
if ($onlyRole) {
    echo "Filtro de rol: $onlyRole\n";
}
echo "\n";

$usedSlugs = [];

// Una misma imagen (mismo attachment ID) puede estar asignada como galería
// Y como thumbnail de variación a la vez — visto en el dry-run: varios
// Orbea Orca (M30, M30i, distintas tallas/colores) comparten sólo 3 fotos
// reales entre decenas de combinaciones. Sin este mapa, ese archivo se
// copiaba una vez por cada rol en el que aparece, con un nombre distinto
// cada vez, y sólo la última copia quedaba activa — las anteriores se
// perdían como basura en disco sin que el reporte lo dejara ver. Cada ID
// se renombra una única vez, con el primer nombre que le toque.
$processedIds = [];

/**
 * Slug único dentro de este batch. sanitize_title() ya lo hace único
 * frente a otros POSTS con wp_unique_post_slug, pero acá el "slug" es un
 * nombre de archivo, no un post_name, así que la unicidad hay que
 * garantizarla a mano contra lo ya asignado en esta misma corrida.
 */
function rb_unique_slug(string $base, array &$used): string
{
    $slug = sanitize_title($base);
    if ($slug === '') {
        $slug = 'imagen';
    }

    $candidate = $slug;
    $i = 2;
    while (isset($used[$candidate])) {
        $candidate = $slug . '-' . $i;
        $i++;
    }

    $used[$candidate] = true;

    return $candidate;
}

/**
 * Copia el adjunto $attachmentId (archivo principal + todos los tamaños
 * generados) a un nuevo nombre base $newBase en el mismo directorio, y
 * actualiza los punteros de WordPress. No borra ni toca el archivo viejo.
 *
 * Devuelve true si (habría) hecho el cambio.
 */
function rb_rename_attachment(int $attachmentId, string $newBase, string $humanTitle, bool $apply, array &$processedIds): bool
{
    if (isset($processedIds[$attachmentId])) {
        echo "  [$attachmentId] ya renombrado en esta corrida como \"{$processedIds[$attachmentId]}\" (imagen compartida entre varios productos/variaciones) — se omite\n";
        return false;
    }

    $oldFile = get_attached_file($attachmentId);

    if (! $oldFile || ! file_exists($oldFile)) {
        echo "  [$attachmentId] SKIP: archivo no encontrado en disco ($oldFile)\n";
        return false;
    }

    $ext = strtolower(pathinfo($oldFile, PATHINFO_EXTENSION));
    $dir = dirname($oldFile);
    $oldBasename = pathinfo($oldFile, PATHINFO_FILENAME);

    if ($oldBasename === $newBase) {
        $processedIds[$attachmentId] = $newBase;
        return false; // ya tiene el nombre que le tocaría, nada que hacer
    }

    $newFile = $dir . '/' . $newBase . '.' . $ext;

    // Colisión real de archivo en disco (no de slug: eso ya lo evita
    // rb_unique_slug). Muy improbable, pero si pasa, mejor abortar ese
    // archivo puntual que pisar algo de otro attachment.
    if (file_exists($newFile)) {
        echo "  [$attachmentId] SKIP: ya existe un archivo en {$newFile}\n";
        return false;
    }

    $attachedFile = get_post_meta($attachmentId, '_wp_attached_file', true); // ej. "2026/09/82570_10.webp"
    $relativeDir = trim(dirname($attachedFile), '.');
    $newAttachedFile = ($relativeDir !== '' ? $relativeDir . '/' : '') . $newBase . '.' . $ext;

    echo "  [$attachmentId] {$oldBasename}.{$ext}  ->  {$newBase}.{$ext}\n";
    $processedIds[$attachmentId] = $newBase;

    if (! $apply) {
        return true;
    }

    if (! copy($oldFile, $newFile)) {
        echo "    ERROR: no se pudo copiar el archivo principal, se aborta este adjunto\n";
        return false;
    }

    $metadata = wp_get_attachment_metadata($attachmentId);
    $sizesCopied = [];

    if (is_array($metadata) && ! empty($metadata['sizes'])) {
        foreach ($metadata['sizes'] as $sizeName => $sizeData) {
            $oldSizeFile = $dir . '/' . $sizeData['file'];
            if (! file_exists($oldSizeFile)) {
                continue;
            }

            $sizeExt = strtolower(pathinfo($sizeData['file'], PATHINFO_EXTENSION));
            $newSizeFilename = sprintf('%s-%dx%d.%s', $newBase, $sizeData['width'], $sizeData['height'], $sizeExt);
            $newSizeFile = $dir . '/' . $newSizeFilename;

            if (! file_exists($newSizeFile) && copy($oldSizeFile, $newSizeFile)) {
                $metadata['sizes'][$sizeName]['file'] = $newSizeFilename;
                $sizesCopied[] = $sizeName;
            }
        }
    }

    if (is_array($metadata)) {
        $metadata['file'] = $newAttachedFile;
        wp_update_attachment_metadata($attachmentId, $metadata);
    }

    update_post_meta($attachmentId, '_wp_attached_file', $newAttachedFile);

    // El guid no es obligatorio actualizarlo (WP no lo usa para construir
    // la URL servida; eso sale de _wp_attached_file), pero dejarlo
    // apuntando al archivo viejo es un dato inconsistente que confunde a
    // cualquier herramienta que sí lo lea directo de la base de datos.
    $uploadDir = wp_get_upload_dir();
    $newGuid = trailingslashit($uploadDir['baseurl']) . $newAttachedFile;

    global $wpdb;
    $wpdb->update($wpdb->posts, ['guid' => $newGuid, 'post_title' => $humanTitle], ['ID' => $attachmentId]);
    clean_post_cache($attachmentId);

    echo '    tamaños copiados: ' . (empty($sizesCopied) ? '(ninguno adicional)' : implode(', ', $sizesCopied)) . "\n";

    return true;
}

$stats = ['hero' => 0, 'categoria' => 0, 'producto' => 0];

// 1) Slides del hero.
if (! $onlyRole || $onlyRole === 'hero') {
    echo "== Slides del hero ==\n";
    $slideIds = get_posts(['post_type' => 'rb_slide', 'posts_per_page' => -1, 'fields' => 'ids']);

    foreach ($slideIds as $slideId) {
        $thumbId = get_post_thumbnail_id($slideId);
        if (! $thumbId) {
            continue;
        }

        $title = get_the_title($slideId) ?: 'Racing Bike 1998';
        $base = rb_unique_slug('hero-' . $title, $usedSlugs);

        if (rb_rename_attachment((int) $thumbId, $base, $title . ' - Racing Bike 1998', $apply, $processedIds)) {
            $stats['hero']++;
        }
    }
    echo "\n";
}

// 2) Miniaturas de categoría.
if (! $onlyRole || $onlyRole === 'categoria') {
    echo "== Miniaturas de categoría ==\n";
    $categoryTerms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

    if (! is_wp_error($categoryTerms)) {
        foreach ($categoryTerms as $term) {
            $thumbId = get_term_meta($term->term_id, 'thumbnail_id', true);
            if (! $thumbId) {
                continue;
            }

            $base = rb_unique_slug('categoria-' . $term->name, $usedSlugs);

            if (rb_rename_attachment((int) $thumbId, $base, $term->name, $apply, $processedIds)) {
                $stats['categoria']++;
            }
        }
    }
    echo "\n";
}

// 3) Imágenes de producto: principal, galería y variaciones.
if (! $onlyRole || $onlyRole === 'producto') {
    echo "== Imágenes de producto ==\n";
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
        if ($name === '') {
            continue;
        }

        $mainId = $product->get_image_id();
        if ($mainId) {
            $base = rb_unique_slug($name, $usedSlugs);
            if (rb_rename_attachment((int) $mainId, $base, $name, $apply, $processedIds)) {
                $stats['producto']++;
            }
        }

        $galleryIndex = 2; // la "1" es la imagen principal, ya asignada arriba
        foreach ($product->get_gallery_image_ids() as $galleryId) {
            $base = rb_unique_slug($name . '-' . $galleryIndex, $usedSlugs);
            if (rb_rename_attachment((int) $galleryId, $base, sprintf('%s - vista %d', $name, $galleryIndex), $apply, $processedIds)) {
                $stats['producto']++;
            }
            $galleryIndex++;
        }

        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $variationId) {
                $thumbId = get_post_meta($variationId, '_thumbnail_id', true);
                if (! $thumbId) {
                    continue;
                }

                $variation = wc_get_product($variationId);
                $summary = $variation ? $variation->get_attribute_summary() : '';
                $base = rb_unique_slug($name . ($summary !== '' ? '-' . $summary : ''), $usedSlugs);
                $title = $summary !== '' ? sprintf('%s - %s', $name, $summary) : $name;

                if (rb_rename_attachment((int) $thumbId, $base, $title, $apply, $processedIds)) {
                    $stats['producto']++;
                }
            }
        }
    }
    echo "\n";
}

printf(
    "%s: %d imágenes de hero, %d de categoría, %d de producto.\n",
    $apply ? 'Renombrado' : 'Se renombraría',
    $stats['hero'],
    $stats['categoria'],
    $stats['producto']
);

if ($apply) {
    echo "\nRecuerda purgar el caché de página completa (LiteSpeed) para que se sirvan las URLs nuevas de inmediato.\n";
}
