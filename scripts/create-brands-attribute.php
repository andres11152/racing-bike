<?php
// Cargar WordPress de forma segura
define('WP_USE_THEMES', false);
$_SERVER['HTTP_HOST'] = 'localhost:8080';
require_once(__DIR__ . '/../wp-load.php');

if (!function_exists('wc_create_attribute')) {
    echo "Error: WooCommerce no está activo o inicializado.\n";
    exit(1);
}

global $wpdb;

$slug = 'marca';
$label = 'Marca';

// Validar si el atributo ya existe en la base de datos de WooCommerce
$attribute_id = wc_attribute_taxonomy_id_by_name($slug);

if (!$attribute_id) {
    $attribute_id = wc_create_attribute([
        'name'         => $label,
        'slug'         => $slug,
        'type'         => 'select',
        'order_by'     => 'menu_order',
        'has_archives' => true,
    ]);
    echo "Atributo 'pa_marca' creado con éxito con ID: $attribute_id\n";
} else {
    echo "El atributo 'pa_marca' ya existe.\n";
}

// Registrar temporalmente en esta ejecución para poder insertar términos
register_taxonomy(
    'pa_marca',
    ['product'],
    [
        'hierarchical' => true,
        'show_ui'      => false,
        'query_var'    => true,
        'rewrite'      => false,
    ]
);

// Listado de marcas predeterminadas a insertar
$brands = [
    'GW'      => 'gw',
    'Shimano' => 'shimano',
    'Trek'    => 'trek',
    'Orbea'   => 'orbea',
    'Magene'  => 'magene',
    'Cliff'   => 'cliff',
];

foreach ($brands as $name => $slug) {
    if (!term_exists($slug, 'pa_marca')) {
        $result = wp_insert_term($name, 'pa_marca', ['slug' => $slug]);
        if (!is_wp_error($result)) {
            echo "Marca insertada: $name ($slug)\n";
        } else {
            echo "Error insertando marca $name: " . $result->get_error_message() . "\n";
        }
    } else {
        echo "La marca '$name' ya existe.\n";
    }
}

// Limpiar permalinks
flush_rewrite_rules();
echo "Proceso finalizado correctamente.\n";
