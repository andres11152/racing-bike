<?php
/**
 * Audit Production Readiness for Racing Bike 1998
 * Run via: wp --skip-themes eval-file scripts/audit-production-readiness.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "\n=======================================================\n";
echo "   AUDITORÍA DE PREPARACIÓN PARA PRODUCCIÓN REAL\n";
echo "   RACING BIKE 1998 — " . date('Y-m-d H:i:s') . "\n";
echo "=======================================================\n\n";

$issues = array();
$warnings = array();
$passed = array();

// -----------------------------------------------------------------
// 1. MODO EN CONSTRUCCIÓN / ACCESO PÚBLICO
// -----------------------------------------------------------------
echo "--- [1] MODO EN CONSTRUCCIÓN Y ACCESO PÚBLICO ---\n";
$skc_mm = get_option('skc_mm_settings', array());
$mm_mode = isset($skc_mm['mode']) ? $skc_mm['mode'] : 'off';
$wc_coming_soon = get_option('woocommerce_coming_soon', 'no');
$blog_public = get_option('blog_public', '1');

echo "• Skycode Maintenance Mode: " . strtoupper($mm_mode) . "\n";
if ($mm_mode !== 'off') {
    $issues[] = "CRÍTICO: Skycode Maintenance Mode está activo ('$mm_mode'). La tienda está bloqueada al público general y muestra página 'Coming Soon'.";
} else {
    $passed[] = "Skycode Maintenance Mode está desactivado (sitio público).";
}

echo "• WooCommerce Coming Soon: " . $wc_coming_soon . "\n";
if ($wc_coming_soon === 'yes') {
    $issues[] = "CRÍTICO: 'woocommerce_coming_soon' está en 'yes'. Bloquea las páginas de la tienda para usuarios no logueados.";
} else {
    $passed[] = "WooCommerce Coming Soon está en 'no'.";
}

echo "• Visibilidad para motores de búsqueda (blog_public): " . ($blog_public ? 'Indexable (1)' : 'Desactivado (0 - No indexar)') . "\n";
if (!$blog_public) {
    $issues[] = "CRÍTICO: 'blog_public' es 0 (Disuadir a los motores de búsqueda de indexar este sitio). Google no indexará la tienda.";
} else {
    $passed[] = "Los motores de búsqueda tienen permiso de indexar (blog_public = 1).";
}

// -----------------------------------------------------------------
// 2. PASARELAS DE PAGO (PAYMENTS)
// -----------------------------------------------------------------
echo "\n--- [2] PASARELAS DE PAGO ---\n";
if (class_exists('WC_Payment_Gateways')) {
    $gateways = WC()->payment_gateways()->payment_gateways();
    $enabled_gateways = array();
    foreach ($gateways as $id => $gw) {
        $status = ($gw->enabled === 'yes') ? 'ACTIVA' : 'inactiva';
        echo "• [{$status}] {$id} - {$gw->title}\n";
        if ($gw->enabled === 'yes') {
            $enabled_gateways[$id] = $gw;
        }
    }

    if (empty($enabled_gateways)) {
        $issues[] = "CRÍTICO: NO HAY NINGUNA PASARELA DE PAGO HABILITADA. Los clientes no pueden pagar.";
    } else {
        $passed[] = "Pasarelas habilitadas: " . implode(', ', array_keys($enabled_gateways));
    }

    // Inspección específica Mercado Pago
    foreach ($gateways as $id => $gw) {
        if (strpos($id, 'mercadopago') !== false || strpos($id, 'woo-mercado-pago') !== false) {
            $mp_options = get_option('woocommerce_' . $id . '_settings', array());
            $mp_sandbox = get_option('_mp_sandbox_mode', null);
            $mp_public = get_option('_mp_public_key_prod', '');
            $mp_access = get_option('_mp_access_token_prod', '');
            echo "  -> MercadoPago Prod Public Key: " . (empty($mp_public) ? 'VACÍA' : substr($mp_public, 0, 15) . '...') . "\n";
            echo "  -> MercadoPago Prod Access Token: " . (empty($mp_access) ? 'VACÍO' : 'CONFIGURADO (' . strlen($mp_access) . ' chars)') . "\n";
            $custom_checkout = get_option('woocommerce_woo-mercado-pago-custom_settings', array());
            $ticket_checkout = get_option('woocommerce_woo-mercado-pago-ticket_settings', array());
            $basic_checkout = get_option('woocommerce_woo-mercado-pago-basic_settings', array());
            break;
        }
    }

    // Inspección Addi
    if (isset($gateways['addi'])) {
        $addi_settings = get_option('woocommerce_addi_settings', array());
        $addi_client_id = isset($addi_settings['client_id']) ? $addi_settings['client_id'] : '';
        $addi_test = isset($addi_settings['testmode']) ? $addi_settings['testmode'] : '';
        echo "  -> Addi Client ID: " . (empty($addi_client_id) ? 'VACÍO' : 'CONFIGURADO') . " | Test mode: " . $addi_test . "\n";
    }
} else {
    $issues[] = "CRÍTICO: WooCommerce no está cargado o activo.";
}

// -----------------------------------------------------------------
// 3. ENVÍOS Y LOGÍSTICA (SHIPPING)
// -----------------------------------------------------------------
echo "\n--- [3] MÉTODOS DE ENVÍO ---\n";
$zones = WC_Shipping_Zones::get_zones();
echo "• Zonas de envío configuradas: " . count($zones) . "\n";
$active_shipping_methods = 0;
foreach ($zones as $z) {
    echo "  Zona: {$z['zone_name']}\n";
    foreach ($z['shipping_methods'] as $m) {
        $st = ($m->enabled === 'yes') ? 'ACTIVO' : 'inactivo';
        echo "    - [{$st}] {$m->id}: {$m->title}\n";
        if ($m->enabled === 'yes') {
            $active_shipping_methods++;
        }
    }
}
$default_zone = new WC_Shipping_Zone(0);
echo "  Zona por defecto (Resto del mundo):\n";
foreach ($default_zone->get_shipping_methods() as $m) {
    $st = ($m->enabled === 'yes') ? 'ACTIVO' : 'inactivo';
    echo "    - [{$st}] {$m->id}: {$m->title}\n";
    if ($m->enabled === 'yes') {
        $active_shipping_methods++;
    }
}

if ($active_shipping_methods === 0) {
    $issues[] = "CRÍTICO: No hay ningún método de envío activo. Ningún cliente podrá finalizar la compra si los productos requieren envío.";
} else {
    $passed[] = "Hay $active_shipping_methods método(s) de envío activo(s).";
}

// -----------------------------------------------------------------
// 4. PÁGINAS CRÍTICAS DE WOOCOMMERCE
// -----------------------------------------------------------------
echo "\n--- [4] PÁGINAS CRÍTICAS DE WOOCOMMERCE ---\n";
$pages_to_check = array(
    'woocommerce_shop_page_id'      => array('name' => 'Tienda / Catálogo', 'required_shortcode' => null),
    'woocommerce_cart_page_id'      => array('name' => 'Carrito', 'required_shortcode' => '[woocommerce_cart]'),
    'woocommerce_checkout_page_id'  => array('name' => 'Finalizar compra (Checkout)', 'required_shortcode' => '[woocommerce_checkout]'),
    'woocommerce_myaccount_page_id' => array('name' => 'Mi Cuenta', 'required_shortcode' => '[woocommerce_my_account]'),
    'woocommerce_terms_page_id'     => array('name' => 'Términos y Condiciones', 'required_shortcode' => null),
    'wp_page_for_privacy_policy'    => array('name' => 'Política de Privacidad', 'required_shortcode' => null),
);

foreach ($pages_to_check as $opt => $info) {
    $page_id = get_option($opt);
    if (!$page_id) {
        if ($opt === 'woocommerce_terms_page_id' || $opt === 'wp_page_for_privacy_policy') {
            $warnings[] = "LEGAL: La página de '{$info['name']}' no está asignada en Ajustes.";
            echo "• {$info['name']}: NO ASIGNADA (opción '$opt' vacía)\n";
        } else {
            $issues[] = "CRÍTICO: La página '{$info['name']}' no está configurada en WooCommerce.";
            echo "• {$info['name']}: NO ASIGNADA\n";
        }
        continue;
    }

    $post = get_post($page_id);
    if (!$post || $post->post_status !== 'publish') {
        $issues[] = "CRÍTICO: La página '{$info['name']}' (ID $page_id) no existe o no está publicada (estado: " . ($post ? $post->post_status : 'no existe') . ").";
        echo "• {$info['name']} (ID $page_id): NO PUBLICADA\n";
        continue;
    }

    $shortcode_ok = true;
    if ($info['required_shortcode']) {
        if (strpos($post->post_content, $info['required_shortcode']) === false) {
            $issues[] = "ERROR DE PLANTILLA: '{$info['name']}' (ID $page_id) no contiene el shortcode {$info['required_shortcode']}. Si usa bloques de Gutenberg, romperá el diseño del Sage Theme.";
            $shortcode_ok = false;
        }
    }

    $status_str = $shortcode_ok ? 'OK' : 'ALERTA SHORTCODE';
    echo "• {$info['name']} (ID $page_id - {$post->post_name}): PUBLICADA [{$status_str}]\n";
}

// -----------------------------------------------------------------
// 5. AJUSTES DE MONEDA Y CHECKOUT
// -----------------------------------------------------------------
echo "\n--- [5] AJUSTES DE MONEDA Y CHECKOUT ---\n";
$currency = get_option('woocommerce_currency');
$decimals = get_option('woocommerce_price_num_decimals');
$thousand_sep = get_option('woocommerce_price_thousand_sep');
$guest_checkout = get_option('woocommerce_enable_guest_checkout');
$signup_checkout = get_option('woocommerce_enable_signup_and_login_from_checkout');

echo "• Moneda: $currency (decimales: $decimals, miles: '$thousand_sep')\n";
if ($currency !== 'COP' || $decimals != 0 || $thousand_sep !== '.') {
    $warnings[] = "Moneda no estándar para Colombia: moneda=$currency, decimales=$decimals, separador miles='$thousand_sep'. Se recomienda COP, 0 decimales, '.' para miles.";
} else {
    $passed[] = "Moneda y formato de precios configurados correctamente para Colombia (COP, 0 decimales, '.' miles).";
}

echo "• Compra como invitado (Guest Checkout): " . ($guest_checkout === 'yes' ? 'HABILITADO' : 'DESHABILITADO') . "\n";
echo "• Crear cuenta durante el checkout: " . ($signup_checkout === 'yes' ? 'HABILITADO' : 'DESHABILITADO') . "\n";

// -----------------------------------------------------------------
// 6. CATÁLOGO E INVENTARIO
// -----------------------------------------------------------------
echo "\n--- [6] CATÁLOGO E INVENTARIO ---\n";
global $wpdb;

$total_products = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
$draft_products = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'draft'");
$pending_products = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'pending'");

echo "• Productos publicados: $total_products\n";
echo "• Productos en borrador / pendientes: $draft_products / $pending_products\n";

if ($total_products === 0) {
    $issues[] = "CRÍTICO: No hay ningún producto publicado en el catálogo.";
} else {
    $passed[] = "Catálogo con $total_products productos publicados.";
}

// Productos sin precio
$no_price_posts = $wpdb->get_results("
    SELECT p.ID, p.post_title FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_price'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND (pm.meta_value IS NULL OR pm.meta_value = '')
");
echo "• Productos publicados sin precio: " . count($no_price_posts) . "\n";
if (!empty($no_price_posts)) {
    foreach ($no_price_posts as $np) {
        echo "    ⚠️ ID {$np->ID}: '{$np->post_title}'\n";
        $issues[] = "Producto sin precio: ID {$np->ID} ('{$np->post_title}') está publicado pero no tiene precio.";
    }
}

// Productos sin imagen destacada
$no_image = $wpdb->get_var("
    SELECT COUNT(p.ID) FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0')
");
echo "• Productos publicados sin imagen destacada: $no_image\n";
if ((int) $no_image > 0) {
    $warnings[] = "Hay $no_image producto(s) publicado(s) sin imagen de portada.";
}

// Productos agotados (out of stock)
$out_of_stock = $wpdb->get_var("
    SELECT COUNT(p.ID) FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_status'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    AND pm.meta_value = 'outofstock'
");
echo "• Productos publicados agotados: $out_of_stock\n";

// -----------------------------------------------------------------
// 7. CORREOS ELECTRÓNICOS Y NOTIFICACIONES (SMTP)
// -----------------------------------------------------------------
echo "\n--- [7] CORREOS TRANSACCIONALES (SMTP / EMAIL) ---\n";
$admin_email = get_option('admin_email');
$wc_email_from_name = get_option('woocommerce_email_from_name');
$wc_email_from_address = get_option('woocommerce_email_from_address');
echo "• Admin Email: $admin_email\n";
echo "• WooCommerce Remitente: '$wc_email_from_name' <$wc_email_from_address>\n";

$skc_smtp_settings = get_option('skycode_smtp_settings', get_option('skc_smtp_settings', array()));
echo "• Skycode SMTP Config: " . (!empty($skc_smtp_settings) ? json_encode($skc_smtp_settings) : 'NO CONFIGURADO') . "\n";
if (empty($skc_smtp_settings) || empty($skc_smtp_settings['host'])) {
    $warnings[] = "SMTP: Skycode SMTP no tiene servidor configurado. Los correos saldrán con PHP mail() de Hostinger, lo que suele irse a SPAM o bloquearse.";
}

// -----------------------------------------------------------------
// 8. LOGS DE ERROR Y DEBUG
// -----------------------------------------------------------------
echo "\n--- [8] LOGS DE ERROR Y DEBUG ---\n";
$debug_log_path = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log_path)) {
    $log_size = filesize($debug_log_path);
    echo "• debug.log existe: " . round($log_size / 1024, 2) . " KB\n";
    if ($log_size > 0) {
        $fp = fopen($debug_log_path, 'r');
        if ($fp) {
            $offset = max(0, $log_size - 1500);
            fseek($fp, $offset);
            $tail = fread($fp, 1500);
            fclose($fp);
            echo "  Últimos logs en debug.log:\n" . trim($tail) . "\n";
            $warnings[] = "debug.log contiene registros recientes (" . round($log_size / 1024, 2) . " KB).";
        }
    }
} else {
    echo "• debug.log no existe (o no ha habido errores registrados).\n";
}

// -----------------------------------------------------------------
// 9. RESUMEN FINAL
// -----------------------------------------------------------------
echo "\n=======================================================\n";
echo "                   RESUMEN EJECUTIVO\n";
echo "=======================================================\n";
echo "TOTAL APROBADOS (PASS): " . count($passed) . "\n";
echo "TOTAL ADVERTENCIAS (WARN): " . count($warnings) . "\n";
echo "TOTAL BLOQUEANTES / CRÍTICOS (FAIL): " . count($issues) . "\n\n";

if (!empty($issues)) {
    echo "🚨 PROBLEMAS BLOQUEANTES PARA PRODUCCIÓN:\n";
    foreach ($issues as $idx => $iss) {
        echo "  " . ($idx + 1) . ". $iss\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️ ADVERTENCIAS / RECOMENDACIONES:\n";
    foreach ($warnings as $idx => $warn) {
        echo "  " . ($idx + 1) . ". $warn\n";
    }
    echo "\n";
}

if (empty($issues)) {
    echo "✅ EL SITIO ESTÁ TÉCNICAMENTE LISTO PARA ABRIR AL PÚBLICO.\n";
} else {
    echo "❌ EL SITIO NO ESTÁ LISTO PARA PRODUCCIÓN REAL HASTA RESOLVER LOS PROBLEMAS CRÍTICOS.\n";
}
echo "=======================================================\n";
