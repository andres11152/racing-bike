<?php
/**
 * Plugin Name: Skycode Migration Helper
 * Description: Eleva el límite de importación de All-in-One WP Migration a 2GB de manera limpia y sin restricciones. Genérico y reutilizable entre proyectos.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-migration-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// 1. Elevar el filtro de All-in-One WP Migration
add_filter( 'all_in_one_wp_migration_max_file_size', function ( $size ) {
    return 2 * 1024 * 1024 * 1024; // 2 GB en bytes
} );

// 2. Elevar el filtro general de subida de WordPress
add_filter( 'upload_size_limit', function ( $size ) {
    return 2 * 1024 * 1024 * 1024; // 2 GB en bytes
} );
