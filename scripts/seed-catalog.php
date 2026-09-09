<?php
/**
 * Siembra un catálogo realista para RACING BIKE 1998.
 *
 * Ejecutar:
 *   docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-catalog.php
 *
 * Es idempotente: vuelve a ejecutarse sin duplicar productos ni términos.
 */

if (! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce no está activo.');
}

/* -------------------------------------------------------------------------
 | Categorías de producto
 * ---------------------------------------------------------------------- */

function rb_term(string $name, string $taxonomy, int $parent = 0): int
{
    $existing = get_term_by('name', $name, $taxonomy);

    if ($existing) {
        return (int) $existing->term_id;
    }

    $term = wp_insert_term($name, $taxonomy, ['parent' => $parent]);

    if (is_wp_error($term)) {
        WP_CLI::warning("No se pudo crear el término {$name}: " . $term->get_error_message());

        return 0;
    }

    return (int) $term['term_id'];
}

$catBicicletas = rb_term('Bicicletas', 'product_cat');
$catRuta       = rb_term('Ruta', 'product_cat', $catBicicletas);
$catGravel     = rb_term('Gravel', 'product_cat', $catBicicletas);
$catPista      = rb_term('Pista', 'product_cat', $catBicicletas);
$catComponentes = rb_term('Componentes', 'product_cat');
$catAccesorios  = rb_term('Accesorios', 'product_cat');

WP_CLI::log('Categorías listas.');

/* -------------------------------------------------------------------------
 | Atributos globales (taxonomías pa_*) — alimentan el sidebar de filtros
 * ---------------------------------------------------------------------- */

$attributeDefs = [
    'talla-cuadro' => ['label' => 'Talla del marco', 'terms' => ['XS', 'S', 'M', 'L']],
    'disciplina'   => ['label' => 'Disciplina',      'terms' => ['Ruta', 'Gravel', 'Pista']],
    'material'     => ['label' => 'Material',        'terms' => ['Carbono', 'Aluminio', 'Acero']],
    'grupo'        => ['label' => 'Grupo',           'terms' => ['Shimano 105', 'Shimano Ultegra', 'SRAM Rival', 'SRAM Force']],
];

foreach ($attributeDefs as $slug => $def) {
    $taxonomy = wc_attribute_taxonomy_name($slug);

    if (! taxonomy_exists($taxonomy)) {
        $id = wc_attribute_taxonomy_id_by_name($slug);

        if (! $id) {
            $id = wc_create_attribute([
                'name'         => $def['label'],
                'slug'         => $slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);

            if (is_wp_error($id)) {
                WP_CLI::error("No se pudo crear el atributo {$slug}: " . $id->get_error_message());
            }
        }

        // Registrar en caliente para poder insertar términos en esta misma ejecución.
        register_taxonomy($taxonomy, ['product'], [
            'hierarchical' => false,
            'show_ui'      => false,
            'query_var'    => true,
            'rewrite'      => false,
        ]);
    }

    foreach ($def['terms'] as $term) {
        rb_term($term, $taxonomy);
    }
}

delete_transient('wc_attribute_taxonomies');
WP_CLI::log('Atributos globales listos.');

/* -------------------------------------------------------------------------
 | Imagen destacada de marcador de posición (GD)
 * ---------------------------------------------------------------------- */

function rb_placeholder_image(string $title, string $sku): int
{
    $existing = get_posts([
        'post_type'      => 'attachment',
        'name'           => sanitize_title("rb-ph-{$sku}"),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    if ($existing) {
        return (int) $existing[0];
    }

    $w = 1000;
    $h = 1250;
    $im = imagecreatetruecolor($w, $h);

    $bg   = imagecolorallocate($im, 0x22, 0x22, 0x22);
    $line = imagecolorallocate($im, 0x6a, 0x6a, 0x6a);
    $ink  = imagecolorallocate($im, 0xcc, 0xcc, 0xcc);

    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // Silueta esquemática de bicicleta, centrada.
    imagesetthickness($im, 9);
    $cx = $w / 2;
    $cy = $h / 2;
    imageellipse($im, $cx - 210, $cy + 120, 300, 300, $line);
    imageellipse($im, $cx + 210, $cy + 120, 300, 300, $line);
    imageline($im, $cx - 210, $cy + 120, $cx - 20, $cy - 130, $line);
    imageline($im, $cx - 20, $cy - 130, $cx + 90, $cy - 130, $line);
    imageline($im, $cx + 90, $cy - 130, $cx + 210, $cy + 120, $line);
    imageline($im, $cx - 20, $cy - 130, $cx - 80, $cy + 120, $line);
    imageline($im, $cx - 20, $cy - 130, $cx + 40, $cy + 10, $line);
    imageline($im, $cx + 40, $cy + 10, $cx + 210, $cy + 120, $line);
    imageline($im, $cx + 90, $cy - 130, $cx + 130, $cy - 230, $line);
    imageline($im, $cx + 130, $cy - 230, $cx + 200, $cy - 230, $line);

    imagestring($im, 5, 60, $h - 90, strtoupper($title), $ink);

    ob_start();
    imagejpeg($im, null, 86);
    $data = ob_get_clean();
    imagedestroy($im);

    $upload = wp_upload_bits("rb-ph-{$sku}.jpg", null, $data);

    if (! empty($upload['error'])) {
        return 0;
    }

    $attachmentId = wp_insert_attachment([
        'post_mime_type' => 'image/jpeg',
        'post_title'     => $title,
        'post_name'      => sanitize_title("rb-ph-{$sku}"),
        'post_status'    => 'inherit',
    ], $upload['file']);

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata(
        $attachmentId,
        wp_generate_attachment_metadata($attachmentId, $upload['file'])
    );

    return (int) $attachmentId;
}

/* -------------------------------------------------------------------------
 | Catálogo
 * ---------------------------------------------------------------------- */

$bikes = [
    [
        'sku' => 'RB-APEX-CARBON', 'name' => 'RB Apex Carbon', 'price' => 8900000, 'sale' => null,
        'cats' => [$catBicicletas, $catRuta], 'featured' => true,
        'attrs' => ['disciplina' => 'Ruta', 'material' => 'Carbono', 'grupo' => 'Shimano Ultegra'],
        'sizes' => ['XS', 'S', 'M', 'L'], 'agotadas' => [],
        'desc' => 'Marco monocasco de carbono de alto módulo, geometría de competencia y transmisión Ultegra. Concebida para la ruta rápida y la escalada sostenida.',
    ],
    [
        'sku' => 'RB-GRAVEL-PRO', 'name' => 'RB Gravel Pro', 'price' => 6500000, 'sale' => 5200000,
        'cats' => [$catBicicletas, $catGravel], 'featured' => true,
        'attrs' => ['disciplina' => 'Gravel', 'material' => 'Carbono', 'grupo' => 'SRAM Rival'],
        'sizes' => ['S', 'M', 'L'], 'agotadas' => ['L'],
        'desc' => 'Gravel de carbono con holgura para cubiertas de 45 mm y montajes para alforjas. Estable en descenso, eficiente en tramos largos de destapado.',
    ],
    [
        'sku' => 'RB-SPRINT-ALLOY', 'name' => 'RB Sprint Alloy', 'price' => 4200000, 'sale' => null,
        'cats' => [$catBicicletas, $catPista], 'featured' => true,
        'attrs' => ['disciplina' => 'Pista', 'material' => 'Aluminio', 'grupo' => 'Shimano 105'],
        'sizes' => ['XS', 'S', 'M'], 'agotadas' => [],
        'desc' => 'Aluminio hidroformado con rigidez de pedaleo excepcional. La puerta de entrada al velódromo sin renunciar a la precisión.',
    ],
    [
        'sku' => 'RB-ENDURANCE-R2', 'name' => 'RB Endurance R2', 'price' => 7300000, 'sale' => null,
        'cats' => [$catBicicletas, $catRuta], 'featured' => true,
        'attrs' => ['disciplina' => 'Ruta', 'material' => 'Carbono', 'grupo' => 'SRAM Force'],
        'sizes' => ['S', 'M'], 'agotadas' => ['S', 'M'],
        'desc' => 'Geometría de resistencia con absorción vertical calibrada. Pensada para jornadas de más de 200 km sin castigo.',
    ],
    [
        'sku' => 'RB-TRAIL-GRAVEL-X', 'name' => 'RB Trail Gravel X', 'price' => 5400000, 'sale' => null,
        'cats' => [$catBicicletas, $catGravel], 'featured' => false,
        'attrs' => ['disciplina' => 'Gravel', 'material' => 'Aluminio', 'grupo' => 'Shimano 105'],
        'sizes' => ['XS', 'S', 'M', 'L'], 'agotadas' => [],
        'desc' => 'Aluminio de doble butido con transmisión 1x. La opción versátil para quien alterna asfalto y trocha.',
    ],
];

$simples = [
    ['sku' => 'RB-CASCO-AERO', 'name' => 'Casco RB Aero', 'price' => 890000, 'sale' => null, 'cat' => $catAccesorios,
     'desc' => 'Casco aerodinámico certificado, ventilación interna y retención micrométrica. 240 g en talla M.'],
    ['sku' => 'RB-RUEDAS-C50', 'name' => 'Juego de ruedas RB Carbon 50', 'price' => 3200000, 'sale' => 2720000, 'cat' => $catComponentes,
     'desc' => 'Perfil de 50 mm en carbono, bujes de cerámica y radios planos. 1.480 g el juego.'],
    ['sku' => 'RB-SILLIN-RACE', 'name' => 'Sillín RB Race', 'price' => 420000, 'sale' => null, 'cat' => $catComponentes,
     'desc' => 'Base de carbono con canal central de alivio y raíles de titanio.'],
    ['sku' => 'RB-GUANTES-PRO', 'name' => 'Guantes RB Pro', 'price' => 180000, 'sale' => null, 'cat' => $catAccesorios,
     'desc' => 'Palma con gel de doble densidad y dorso en malla transpirable.'],
    ['sku' => 'RB-BIDON-750', 'name' => 'Bidón RB 750 ml', 'price' => 65000, 'sale' => null, 'cat' => $catAccesorios,
     'desc' => 'Polipropileno libre de BPA con válvula de alto flujo. Apto para lavavajillas.'],
];

/* --- Bicicletas: productos variables por talla de cuadro --- */

foreach ($bikes as $bike) {
    if (wc_get_product_id_by_sku($bike['sku'])) {
        WP_CLI::log("· {$bike['name']} ya existe, se omite.");
        continue;
    }

    $product = new WC_Product_Variable();
    $product->set_name($bike['name']);
    $product->set_sku($bike['sku']);
    $product->set_description($bike['desc']);
    $product->set_short_description($bike['desc']);
    $product->set_category_ids($bike['cats']);
    $product->set_featured($bike['featured']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');

    $attributes = [];
    $position = 0;

    // Talla de cuadro: usada para variaciones.
    $sizeTax = wc_attribute_taxonomy_name('talla-cuadro');
    $sizeAttr = new WC_Product_Attribute();
    $sizeAttr->set_id(wc_attribute_taxonomy_id_by_name('talla-cuadro'));
    $sizeAttr->set_name($sizeTax);
    $sizeAttr->set_options(array_map(
        fn ($s) => (int) get_term_by('name', $s, $sizeTax)->term_id,
        $bike['sizes']
    ));
    $sizeAttr->set_position($position++);
    $sizeAttr->set_visible(true);
    $sizeAttr->set_variation(true);
    $attributes[] = $sizeAttr;

    // Resto: sólo para filtrado/ficha, no generan variaciones.
    foreach ($bike['attrs'] as $slug => $value) {
        $tax = wc_attribute_taxonomy_name($slug);
        $attr = new WC_Product_Attribute();
        $attr->set_id(wc_attribute_taxonomy_id_by_name($slug));
        $attr->set_name($tax);
        $attr->set_options([(int) get_term_by('name', $value, $tax)->term_id]);
        $attr->set_position($position++);
        $attr->set_visible(true);
        $attr->set_variation(false);
        $attributes[] = $attr;
    }

    $product->set_attributes($attributes);
    $productId = $product->save();

    if ($imageId = rb_placeholder_image($bike['name'], $bike['sku'])) {
        $product->set_image_id($imageId);
        $product->save();
    }

    foreach ($bike['sizes'] as $size) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($productId);
        $variation->set_attributes([$sizeTax => sanitize_title($size)]);
        $variation->set_regular_price((string) $bike['price']);

        if ($bike['sale']) {
            $variation->set_sale_price((string) $bike['sale']);
        }

        $variation->set_stock_status(
            in_array($size, $bike['agotadas'], true) ? 'outofstock' : 'instock'
        );
        $variation->save();
    }

    WC_Product_Variable::sync($productId);
    WP_CLI::log("✓ {$bike['name']} (variable, " . count($bike['sizes']) . ' tallas)');
}

/* --- Componentes y accesorios: productos simples --- */

foreach ($simples as $item) {
    if (wc_get_product_id_by_sku($item['sku'])) {
        WP_CLI::log("· {$item['name']} ya existe, se omite.");
        continue;
    }

    $product = new WC_Product_Simple();
    $product->set_name($item['name']);
    $product->set_sku($item['sku']);
    $product->set_description($item['desc']);
    $product->set_short_description($item['desc']);
    $product->set_category_ids([$item['cat']]);
    $product->set_regular_price((string) $item['price']);

    if ($item['sale']) {
        $product->set_sale_price((string) $item['sale']);
    }

    $product->set_stock_status('instock');
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $productId = $product->save();

    if ($imageId = rb_placeholder_image($item['name'], $item['sku'])) {
        $product->set_image_id($imageId);
        $product->save();
    }

    WP_CLI::log("✓ {$item['name']} (simple)");
}

WP_CLI::success('Catálogo sembrado.');
