<?php

/**
 * PWA (Progressive Web App) Support — Racing Bike 1998
 */

namespace App;

/**
 * Inject PWA meta tags & manifest link into the HTML head.
 */
add_action('wp_head', function () {
    $manifestUrl = get_theme_file_uri('public/manifest.webmanifest');
    $icon192 = get_theme_file_uri('public/images/pwa/icon-192.png');
    $icon512 = get_theme_file_uri('public/images/pwa/icon-512.png');
    $appleTouchIcon = get_theme_file_uri('public/images/pwa/apple-touch-icon.png');
    $favicon32 = get_theme_file_uri('public/images/pwa/favicon-32.png');
    ?>
    <!-- PWA Manifest & App Metas -->
    <link rel="manifest" href="<?php echo esc_url($manifestUrl); ?>">
    <meta name="theme-color" content="#0B0C0E">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Racing Bike">
    <meta name="application-name" content="Racing Bike 1998">
    <meta name="msapplication-TileColor" content="#0B0C0E">
    <meta name="msapplication-navbutton-color" content="#0B0C0E">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url($appleTouchIcon); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url($favicon32); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url($icon192); ?>">
    <?php
}, 1);

/**
 * Intercept direct requests to /sw.js or /manifest.webmanifest if needed
 * to guarantee correct HTTP headers on all web servers (Apache, Nginx, Hostinger).
 */
add_action('init', function () {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $parsedPath = parse_url($requestUri, PHP_URL_PATH);

    // Servir Service Worker desde raíz con permisos globales
    if ($parsedPath === '/sw.js') {
        $swFile = get_theme_file_path('public/sw.js');
        if (file_exists($swFile)) {
            header('Content-Type: application/javascript; charset=utf-8');
            header('Service-Worker-Allowed: /');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($swFile);
            exit;
        }
    }

    // Servir Manifest desde raíz si se solicita
    if ($parsedPath === '/manifest.webmanifest' || $parsedPath === '/manifest.json') {
        $manifestFile = get_theme_file_path('public/manifest.webmanifest');
        if (file_exists($manifestFile)) {
            header('Content-Type: application/manifest+json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            readfile($manifestFile);
            exit;
        }
    }
});
