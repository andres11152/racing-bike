<?php

/**
 * Corrige un efecto secundario de apply-enterprise-image-filenames.php: 11
 * fotos están compartidas entre una miniatura de categoría y la imagen
 * principal o de galería de un producto (p.ej. la miniatura de la
 * categoría "Cascos" es literalmente la foto principal del "Casco de
 * Ciclismo GW RC"). Como ese script procesa categorías antes que
 * productos y cada attachment sólo se renombra una vez por corrida, esas
 * 11 imágenes quedaron con el nombre de la CATEGORÍA
 * (categoria-cascos.webp) en vez del nombre del PRODUCTO
 * (casco-de-ciclismo-gw-rc.webp) — técnicamente sirven bien (la URL
 * resuelve, WooCommerce sigue apuntando al mismo attachment ID), pero
 * para SEO de imagen el nombre del producto es el que importa: la ficha
 * de producto es la página con tráfico real, la miniatura de categoría es
 * decorativa.
 *
 * Misma estrategia "copiar, no mover" que el script original: copia otra
 * vez desde el archivo actual (el que ya tiene nombre de categoría) hacia
 * el nombre de producto, y actualiza los punteros. Ningún archivo
 * anterior se borra.
 *
 * Usage: wp --skip-themes eval-file scripts/fix-shared-category-product-image-names.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (no se copia ningún archivo; agrega \"apply\" para ejecutar)\n\n";

function rb_rename_to_product_name(int $attachmentId, string $newBase, string $humanTitle, bool $apply): bool
{
    $oldFile = get_attached_file($attachmentId);

    if (! $oldFile || ! file_exists($oldFile)) {
        echo "  [$attachmentId] SKIP: archivo no encontrado en disco ($oldFile)\n";
        return false;
    }

    $ext = strtolower(pathinfo($oldFile, PATHINFO_EXTENSION));
    $dir = dirname($oldFile);
    $oldBasename = pathinfo($oldFile, PATHINFO_FILENAME);

    if ($oldBasename === $newBase) {
        return false;
    }

    $newFile = $dir . '/' . $newBase . '.' . $ext;
    if (file_exists($newFile)) {
        echo "  [$attachmentId] SKIP: ya existe un archivo en {$newFile}\n";
        return false;
    }

    $attachedFile = get_post_meta($attachmentId, '_wp_attached_file', true);
    $relativeDir = trim(dirname($attachedFile), '.');
    $newAttachedFile = ($relativeDir !== '' ? $relativeDir . '/' : '') . $newBase . '.' . $ext;

    echo "  [$attachmentId] {$oldBasename}.{$ext}  ->  {$newBase}.{$ext}\n";

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

    $uploadDir = wp_get_upload_dir();
    $newGuid = trailingslashit($uploadDir['baseurl']) . $newAttachedFile;

    global $wpdb;
    $wpdb->update($wpdb->posts, ['guid' => $newGuid, 'post_title' => $humanTitle], ['ID' => $attachmentId]);
    clean_post_cache($attachmentId);

    echo '    tamaños copiados: ' . (empty($sizesCopied) ? '(ninguno adicional)' : implode(', ', $sizesCopied)) . "\n";

    return true;
}

// [attachmentId => [productId, posiciónDeGalería|null]] — la lista exacta
// que salió del diagnóstico contra producción. La posición de galería es
// la que realmente ocupa esa foto en get_gallery_image_ids() (base 0),
// convertida al mismo esquema "index+2" que usó el script original
// (index 0 de la galería = "-2", porque "sin sufijo" ya es la principal) —
// NO simplemente "-2" a ciegas: attachment 1437 ocupa la posición 3 de la
// galería de su producto, y "-2" ya lo tenía otra foto real de esa misma
// galería (bicicleta-mtb-poseidon-keto-10v-ltwoo-rin-29-2.webp), como
// confirmó el intento en seco.
$overlaps = [
    2064 => [2056, null], // Casco de Ciclismo GW RC — principal
    // La misma foto 2064 también está en la galería del mismo producto:
    // con nombrarla una vez alcanza (es el mismo archivo, mismo attachment).
    1970 => [1950, null], // Grupo MTB Shimano Deore M5100
    439  => [1867, null], // Simulador Inteligente Magene T600 Eco
    1839 => [1845, null], // Pedales con Potenciómetro Magene P715 S
    // 1843 (P715 K) comparte la misma foto 1839 que 1845 (P715 S) — mismo
    // caso: un solo nombre por archivo, se queda con el primero.
    1801 => [1799, 0],    // Grupo de Ruta Shimano Dura-Ace Di2 R9270 — galería, posición 0 -> "-2"
    1710 => [1692, null], // Bicicleta de Ruta Trek Domane AL 2 2026
    1437 => [1117, 3],    // Bicicleta MTB Poseidon Keto — galería, posición 3 -> "-5"
    800  => [818, null],  // Luz Trasera Inteligente Magene L308
    784  => [397, 0],     // Ciclocomputador GPS Magene C706 Smart — galería, posición 0 -> "-2"
];

$renamed = 0;

foreach ($overlaps as $attachmentId => [$productId, $galleryPosition]) {
    $product = wc_get_product($productId);
    if (! $product) {
        echo "  [$attachmentId] SKIP: producto $productId no encontrado\n";
        continue;
    }

    $name = $product->get_name();
    $suffix = $galleryPosition === null ? '' : ('-' . ($galleryPosition + 2));
    $base = sanitize_title($name . $suffix);
    $title = $galleryPosition === null ? $name : sprintf('%s - vista %d', $name, $galleryPosition + 2);

    if (rb_rename_to_product_name($attachmentId, $base, $title, $apply)) {
        $renamed++;
    }
}

echo "\n";
printf("%s: %d imágenes movidas de nombre de categoría a nombre de producto.\n", $apply ? 'Aplicado' : 'Se aplicaría', $renamed);
