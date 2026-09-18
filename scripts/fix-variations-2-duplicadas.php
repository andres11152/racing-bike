<?php

/**
 * FASE 2 del plan de corrección de variaciones.
 *
 * Dos problemas, ambos hoy en BICICLETA GW HAWK (832):
 *
 *  a) 12 pares de variaciones con la combinación EXACTA repetida (misma
 *     talla + mismo color). Cada una con su propio stock de 3 unidades, así
 *     que el inventario aparenta el doble, y WooCommerce elige una de las
 *     dos de forma arbitraria: el precio/stock que ve el cliente puede no
 *     ser el que se descuenta. Las viejas arrastran un SKU genérico
 *     repetido ("GW-HAWK"); las nuevas ya traen el SKU específico correcto.
 *
 *  b) 1 variación "comodín" con TODOS sus atributos vacíos, que para
 *     WooCommerce significa "cualquier talla / cualquier color" y puede
 *     tapar a las variaciones específicas.
 *
 * Criterio para elegir cuál se conserva en cada grupo duplicado (en orden):
 *   1. La que tenga un SKU específico (no compartido con otras variaciones
 *      del mismo producto) — es la que identifica de verdad la combinación.
 *   2. Si empatan, la creada más recientemente.
 *
 * Seguridad: nunca se borra una variación referenciada en un pedido. Se
 * comprueba contra woocommerce_order_itemmeta antes de tocar nada, y si
 * apareciera una se informa y se deja intacta.
 *
 * Uso: wp --skip-themes eval-file scripts/fix-variations-2-duplicadas.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

global $wpdb;

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

/** IDs de variación que aparecen en algún pedido: intocables. */
function rb_variaciones_con_pedidos(array $ids): array
{
    global $wpdb;

    if (empty($ids)) {
        return [];
    }

    $in = implode(',', array_map('intval', $ids));

    $encontradas = $wpdb->get_col(
        "SELECT DISTINCT meta_value
         FROM {$wpdb->prefix}woocommerce_order_itemmeta
         WHERE meta_key = '_variation_id' AND meta_value IN ($in)"
    );

    return array_map('intval', $encontradas ?: []);
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$totalBorrar = 0;
$totalProtegidas = 0;

foreach ($ids as $productId) {
    $product = wc_get_product($productId);

    if (! $product || ! $product->is_type('variable')) {
        continue;
    }

    $children = $product->get_children();

    if (count($children) < 2) {
        continue;
    }

    // Cuántas variaciones comparten cada SKU (para saber cuál es "específico").
    $usoDeSku = [];
    $datos = [];

    foreach ($children as $variationId) {
        $variation = wc_get_product($variationId);
        if (! $variation) {
            continue;
        }

        $attrs = $variation->get_attributes();
        $sku = (string) $variation->get_sku();

        $datos[$variationId] = [
            'obj' => $variation,
            'attrs' => $attrs,
            'sku' => $sku,
            'firma' => wp_json_encode($attrs),
            'fecha' => (string) get_post_field('post_date', $variationId),
            'vacia' => empty(array_filter($attrs, fn ($v) => (string) $v !== '')),
        ];

        if ($sku !== '') {
            $usoDeSku[$sku] = ($usoDeSku[$sku] ?? 0) + 1;
        }
    }

    $aBorrar = [];

    // a) Comodines: solo si el producto tiene además variaciones con valores.
    $tieneEspecificas = (bool) array_filter($datos, fn ($d) => ! $d['vacia']);

    foreach ($datos as $variationId => $d) {
        if ($d['vacia'] && $tieneEspecificas) {
            $aBorrar[$variationId] = sprintf('comodín (todos los atributos vacíos), sku=%s', $d['sku'] ?: '-');
        }
    }

    // b) Duplicadas por combinación exacta.
    $grupos = [];
    foreach ($datos as $variationId => $d) {
        if ($d['vacia']) {
            continue;
        }
        $grupos[$d['firma']][] = $variationId;
    }

    foreach ($grupos as $firma => $grupo) {
        if (count($grupo) < 2) {
            continue;
        }

        usort($grupo, function ($a, $b) use ($datos, $usoDeSku) {
            $skuEspecificoA = $datos[$a]['sku'] !== '' && ($usoDeSku[$datos[$a]['sku']] ?? 0) === 1;
            $skuEspecificoB = $datos[$b]['sku'] !== '' && ($usoDeSku[$datos[$b]['sku']] ?? 0) === 1;

            if ($skuEspecificoA !== $skuEspecificoB) {
                return $skuEspecificoA ? -1 : 1;
            }

            return strcmp($datos[$b]['fecha'], $datos[$a]['fecha']);
        });

        $conservar = array_shift($grupo);

        printf(
            "[%d] %s\n  combinación %s\n    CONSERVA  %d (sku=%s, %s)\n",
            $productId,
            $product->get_name(),
            implode('/', $datos[$conservar]['attrs']),
            $conservar,
            $datos[$conservar]['sku'] ?: '-',
            $datos[$conservar]['fecha']
        );

        foreach ($grupo as $variationId) {
            printf("    BORRA     %d (sku=%s, %s)\n", $variationId, $datos[$variationId]['sku'] ?: '-', $datos[$variationId]['fecha']);
            $aBorrar[$variationId] = sprintf('duplicada de %d', $conservar);
        }
    }

    if (empty($aBorrar)) {
        continue;
    }

    // Protección: nada que tenga pedidos.
    $conPedidos = rb_variaciones_con_pedidos(array_keys($aBorrar));

    foreach ($conPedidos as $variationId) {
        printf("[%d] *** variación %d tiene pedidos asociados: NO se borra. ***\n", $productId, $variationId);
        unset($aBorrar[$variationId]);
        $totalProtegidas++;
    }

    foreach ($aBorrar as $variationId => $motivo) {
        if (! isset($datos[$variationId])) {
            continue;
        }

        printf("[%d] borrar variación %d — %s\n", $productId, $variationId, $motivo);
        $totalBorrar++;

        if ($apply) {
            $datos[$variationId]['obj']->delete(true);
        }
    }

    if ($apply) {
        WC_Product_Variable::sync($productId);
        wc_delete_product_transients($productId);
        printf("[%d] variaciones tras la limpieza: %d\n", $productId, count(wc_get_product($productId)->get_children()));
    }

    echo "\n";
}

printf(
    "\n%s: %d variación(es) a borrar. Protegidas por tener pedidos: %d.\n",
    $apply ? 'Aplicado' : 'Se aplicaría',
    $totalBorrar,
    $totalProtegidas
);
