<?php
/**
 * Crea la zona de envío "Colombia" con un método honesto: costo $0 en el
 * checkout, coordinado por WhatsApp después del pedido.
 *
 * El dueño del negocio confirmó que el envío "se cotiza aparte con cada
 * cliente" — no es gratis ni tiene tarifa fija. No usamos el método nativo
 * "Free Shipping" de WooCommerce porque la promesa real no es que sea
 * gratis, es que se coordina caso por caso; el copy del sitio se corrigió
 * en el mismo sentido.
 *
 * Sin al menos un método de envío, WooCommerce oculta TODOS los métodos de
 * pago en el checkout para productos físicos — independientemente de qué
 * pasarelas estén activas. Esto es lo que desbloquea el checkout.
 *
 * Ejecutar:
 *   docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-shipping.php
 *
 * Idempotente.
 */

if (! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce no está activo.');
}

$zoneName = 'Colombia';
$existingZoneId = null;

foreach (WC_Shipping_Zones::get_zones() as $zone) {
    if ($zone['zone_name'] === $zoneName) {
        $existingZoneId = (int) $zone['id'];
        break;
    }
}

if ($existingZoneId) {
    WP_CLI::log("· Zona '{$zoneName}' ya existe (ID {$existingZoneId}), se omite la creación.");
    $zone = new WC_Shipping_Zone($existingZoneId);
} else {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name($zoneName);
    $zone->set_zone_order(0);
    $zone->add_location('CO', 'country');
    $zone->save();
    WP_CLI::log("✓ Zona '{$zoneName}' creada (CO) — ID {$zone->get_id()}.");
}

$hasFlatRate = false;

foreach ($zone->get_shipping_methods() as $method) {
    if ($method->id === 'flat_rate') {
        $hasFlatRate = true;
        break;
    }
}

if ($hasFlatRate) {
    WP_CLI::log('· El método de envío ya existe en la zona, se omite.');
} else {
    $instanceId = $zone->add_shipping_method('flat_rate');

    if ($instanceId) {
        $settings = get_option("woocommerce_flat_rate_{$instanceId}_settings", []);
        $settings['title'] = 'Envío a coordinar';
        $settings['cost'] = '0';
        $settings['tax_status'] = 'none';
        update_option("woocommerce_flat_rate_{$instanceId}_settings", $settings);

        WP_CLI::log("✓ Método 'Envío a coordinar' añadido a la zona (instancia {$instanceId}).");
    } else {
        WP_CLI::warning('No se pudo añadir el método de envío.');
    }
}

delete_transient('wc_shipping_zones');

WP_CLI::success('Envío configurado según la política real del negocio.');
