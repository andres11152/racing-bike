<?php

/**
 * Registra como adjuntos en WordPress las 7 imágenes WebP (<200kb) generadas
 * y las asigna a cada una de sus variaciones correspondientes:
 *
 * 1. gw-zebra-mtb-29-blanco.webp (78KB)
 *    -> [1645] GW Zebra MTB 29 | Color: 'blanco'
 *
 * 2. trek-marlin-6-gen-3-lava.webp (76KB)
 *    -> [1133] Trek Marlin 6 2026 | Color: 'Lava'
 *
 * 3. trek-marlin-6-gen-3-lichen-keswick-green-fade.webp (65KB)
 *    -> [1133] Trek Marlin 6 2026 | Color: 'Matte Lichen/Keswick Green Fade'
 *
 * 4. trek-procaliber-6-satin-black-lithium-grey.webp (58KB)
 *    -> [973] Trek Procaliber 6 2026 | Color: 'Satin Trek Black/Lithium Grey'
 *
 * 5. trek-marlin-7-gen-3-magic-mint.webp (66KB)
 *    -> [954] Trek Marlin 7 2026 | Color: 'Magic Mint'
 *
 * 6. trek-marlin-7-gen-3-dark-web.webp (64KB)
 *    -> [954] Trek Marlin 7 2026 | Color: 'Matte Dark Web/Clear Gloss'
 *
 * 7. orbea_alma_halo_silver_tanzanite.webp (144KB)
 *    -> [882] Orbea Alma H30 2026 | Color: 'halo-silver-tanzanite-gloss'
 *    -> [861] Orbea Alma H20 2025 | Color: 'Halo Silver - Tanzanite (Gloss)'
 *    -> [852] Orbea Alma H30 2025 | Color: 'halo-silver-tanzanite-gloss'
 *
 * Uso: wp --skip-themes eval-file scripts/register-and-assign-variation-webp.php [apply]
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

require_once ABSPATH . 'wp-admin/includes/image.php';

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "=== MODO: APLICANDO CAMBIOS EN PRODUCCIÓN ===\n\n" : "=== MODO: DRY-RUN (no se escribe nada) ===\n\n";

// Definición de las imágenes y su asignación
$webpAssignments = [
    [
        'file' => 'gw-zebra-mtb-29-blanco.webp',
        'title' => 'Bicicleta Zebra MTB 29 Blanco',
        'alt' => 'Bicicleta Zebra MTB 29 GW Blanco',
        'targets' => [
            [
                'product_id' => 1645,
                'color' => 'blanco',
            ],
        ],
    ],
    [
        'file' => 'trek-marlin-6-gen-3-lava.webp',
        'title' => 'Trek Marlin 6 Gen 3 2026 Lava',
        'alt' => 'Trek Marlin 6 Gen 3 2026 Lava Rojo',
        'targets' => [
            [
                'product_id' => 1133,
                'color' => 'Lava',
            ],
        ],
    ],
    [
        'file' => 'trek-marlin-6-gen-3-lichen-keswick-green-fade.webp',
        'title' => 'Trek Marlin 6 Gen 3 2026 Matte Lichen Keswick Green Fade',
        'alt' => 'Trek Marlin 6 Gen 3 2026 Matte Lichen Keswick Green Fade',
        'targets' => [
            [
                'product_id' => 1133,
                'color' => 'Matte Lichen/Keswick Green Fade',
            ],
        ],
    ],
    [
        'file' => 'trek-procaliber-6-satin-black-lithium-grey.webp',
        'title' => 'Trek Procaliber 6 2026 Satin Trek Black Lithium Grey',
        'alt' => 'Trek Procaliber 6 2026 Satin Trek Black Lithium Grey',
        'targets' => [
            [
                'product_id' => 973,
                'color' => 'Satin Trek Black/Lithium Grey',
            ],
        ],
    ],
    [
        'file' => 'trek-marlin-7-gen-3-magic-mint.webp',
        'title' => 'Trek Marlin 7 Gen 3 2026 Magic Mint',
        'alt' => 'Trek Marlin 7 Gen 3 2026 Magic Mint',
        'targets' => [
            [
                'product_id' => 954,
                'color' => 'Magic Mint',
            ],
        ],
    ],
    [
        'file' => 'trek-marlin-7-gen-3-dark-web.webp',
        'title' => 'Trek Marlin 7 Gen 3 2026 Matte Dark Web Clear Gloss',
        'alt' => 'Trek Marlin 7 Gen 3 2026 Matte Dark Web Clear Gloss',
        'targets' => [
            [
                'product_id' => 954,
                'color' => 'Matte Dark Web/Clear Gloss',
            ],
        ],
    ],
    [
        'file' => 'orbea_alma_halo_silver_tanzanite.webp',
        'title' => 'Orbea Alma Halo Silver Tanzanite Gloss',
        'alt' => 'Orbea Alma Halo Silver Tanzanite Gloss',
        'targets' => [
            [
                'product_id' => 882,
                'color' => 'halo-silver-tanzanite-gloss',
            ],
            [
                'product_id' => 861,
                'color' => 'Halo Silver - Tanzanite (Gloss)',
            ],
            [
                'product_id' => 852,
                'color' => 'halo-silver-tanzanite-gloss',
            ],
        ],
    ],
];

$uploadDir = wp_upload_dir();
$uploadSubdir = $uploadDir['basedir'] . '/2026/09';

foreach ($webpAssignments as $item) {
    $fileName = $item['file'];
    $filePath = $uploadSubdir . '/' . $fileName;

    if (! file_exists($filePath)) {
        echo "ERROR: Archivo no existe en servidor: $filePath\n";
        continue;
    }

    $fileSizeKb = round(filesize($filePath) / 1024, 1);
    echo "============================================================\n";
    echo "Procesando: $fileName ({$fileSizeKb} KB - WebP < 200KB OK)\n";

    // Buscar si ya existe el attachment
    global $wpdb;
    $existingId = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
        '%/' . $fileName
    ));

    $attachId = 0;
    if ($existingId) {
        $attachId = (int) $existingId;
        echo "  - Adjunto ya registrado en WP: ID $attachId\n";
    } else {
        echo "  - Creando nuevo adjunto en WP...\n";
        if ($apply) {
            $attachment = [
                'guid'           => $uploadDir['baseurl'] . '/2026/09/' . $fileName,
                'post_mime_type' => 'image/webp',
                'post_title'     => $item['title'],
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            $attachId = wp_insert_attachment($attachment, $filePath);
            if ($attachId && ! is_wp_error($attachId)) {
                $attachData = wp_generate_attachment_metadata($attachId, $filePath);
                wp_update_attachment_metadata($attachId, $attachData);
                update_post_meta($attachId, '_wp_attachment_image_alt', $item['alt']);
                echo "  - Creado exitosamente: ID $attachId\n";
            } else {
                echo "  - ERROR al crear adjunto: " . ($attachId->get_error_message() ?? 'desconocido') . "\n";
                continue;
            }
        } else {
            $attachId = 99999; // Simulado para dry run
            echo "  - (Dry-run) Se crearía adjunto con título '{$item['title']}'\n";
        }
    }

    // Asignar a variaciones de cada producto destino
    foreach ($item['targets'] as $target) {
        $prodId = $target['product_id'];
        $targetColor = $target['color'];
        $product = wc_get_product($prodId);
        if (! $product) {
            echo "  - Producto $prodId no encontrado\n";
            continue;
        }

        echo "  -> Asignando a [{$prodId}] {$product->get_name()} (Color '$targetColor'):\n";
        $assignedCount = 0;

        foreach ($product->get_children() as $varId) {
            $varColor = get_post_meta($varId, 'attribute_pa_color', true) ?: get_post_meta($varId, 'attribute_color', true);
            $normalizedTarget = sanitize_title($targetColor);
            $normalizedVar = sanitize_title($varColor);

            if ($normalizedVar === $normalizedTarget || $varColor === $targetColor) {
                $currentThumb = (int) get_post_meta($varId, '_thumbnail_id', true);
                printf("     Var %d (color '%s'): foto actual %d -> NUEVA FOTO %d\n", $varId, $varColor, $currentThumb, $attachId);

                if ($apply) {
                    update_post_meta($varId, '_thumbnail_id', $attachId);
                    clean_post_cache($varId);
                }
                $assignedCount++;
            }
        }

        echo "     Total variaciones actualizadas: $assignedCount\n";

        if ($apply && $assignedCount > 0) {
            clean_post_cache($prodId);
            wc_delete_product_transients($prodId);
        }
    }

    echo "\n";
}

echo "=== FIN DEL PROCESO ===\n";
