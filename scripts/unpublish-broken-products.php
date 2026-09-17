<?php
/**
 * Despublica (pone en borrador) los productos que hoy representan riesgo
 * de pérdida de dinero o basura visible en /tienda/:
 *
 *  - 7 productos fantasma sin nombre real, precio, imagen ni categoría
 *    ("Producto", IDs de importación fallida).
 *  - 7 productos publicados y comprables a $0 (todas sus variaciones en
 *    $0, o precio simple en $0) — cualquiera los agrega al carrito gratis.
 *  - 1 producto variable sin imagen principal ni en ninguna variación
 *    (tarjeta en blanco en toda la tienda).
 *
 * Ninguno se borra: pasan a "draft" (post_status), reversible con un
 * solo wp post update --post_status=publish si se corrige el dato de
 * origen (precio real o fotos).
 *
 * Dry-run (por defecto): solo imprime qué haría, no escribe nada.
 * Modo "apply": aplica el cambio de estado (run-prod-script.sh ya toma
 * un backup de la BD automáticamente antes de este modo).
 *
 * Uso vía deploy/run-prod-script.sh:
 *   deploy/run-prod-script.sh scripts/unpublish-broken-products.php
 *   deploy/run-prod-script.sh scripts/unpublish-broken-products.php apply
 */

if (! defined('ABSPATH')) { exit; }

$apply = in_array('apply', $args ?? [], true);

// IDs verificados por scripts/audit-catalog.php el 2026-09-17: 7 productos
// "Producto" vacíos, 7 a precio $0 (simple o todas sus variaciones), y el
// Orbea Alma H20 sin ninguna imagen (ni propia ni heredable de variación).
$targets = [
    1666 => 'Producto (vacío, sin precio/imagen/categoría)',
    1667 => 'Producto (vacío, sin precio/imagen/categoría)',
    1668 => 'Producto (vacío, sin precio/imagen/categoría)',
    1669 => 'Producto (vacío, sin precio/imagen/categoría)',
    1670 => 'Producto (vacío, sin precio/imagen/categoría)',
    1671 => 'Producto (vacío, sin precio/imagen/categoría)',
    1672 => 'Producto (vacío, sin precio/imagen/categoría)',
    1133 => 'TREK MARLIN 6 2026 (24 variaciones a $0)',
    954  => 'TREK MARLIN 7 2026 (21 variaciones a $0)',
    911  => 'TREK MARLIN 5 2026 (21 variaciones a $0)',
    973  => 'TREK PROCALIBER 6 2026 (10 variaciones a $0)',
    1765 => 'Grupo Shimano 105 R7120 (6 variaciones a $0)',
    1828 => 'P515 BIELAS MAGENE (5 variaciones a $0)',
    1855 => 'T110 MAGENE SMART TRAINER (simple, $0)',
    861  => 'ORBEA ALMA H20 2025 (sin imagen en producto ni en 8 variaciones)',
];

echo $apply ? "=== MODO APLICAR ===\n\n" : "=== DRY-RUN (sin cambios) ===\n\n";

$done = 0;
$skipped = 0;

foreach ($targets as $id => $reason) {
    $post = get_post($id);

    if (! $post || $post->post_type !== 'product') {
        printf("  [%d] OMITIDO — no existe o ya no es un producto (%s)\n", $id, $reason);
        $skipped++;
        continue;
    }

    if ($post->post_status !== 'publish') {
        printf("  [%d] OMITIDO — ya está en estado \"%s\" (%s)\n", $id, $post->post_status, $reason);
        $skipped++;
        continue;
    }

    printf("  [%d] %s → draft   (%s)\n", $id, $post->post_title ?: '(sin título)', $reason);

    if ($apply) {
        $result = wp_update_post(['ID' => $id, 'post_status' => 'draft'], true);

        if (is_wp_error($result)) {
            printf("        ERROR: %s\n", $result->get_error_message());
            continue;
        }
    }

    $done++;
}

echo "\n";
printf(
    "%s: %d producto(s), %d omitido(s) de %d evaluados.\n",
    $apply ? 'Aplicado' : 'Se aplicaría',
    $done,
    $skipped,
    count($targets)
);

if (! $apply) {
    echo "\nPara aplicar de verdad: deploy/run-prod-script.sh scripts/unpublish-broken-products.php apply\n";
}
