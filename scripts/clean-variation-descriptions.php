<?php

/**
 * Vacía la "descripción de variación" de las variaciones que solo repiten
 * la descripción del producto padre.
 *
 * WooCommerce inyecta ese campo dentro del formulario de compra, JUSTO
 * ENCIMA del botón "Añadir al carrito", en cuanto se elige una talla.
 * Auditoría del 2026-09-17: 199 de 489 variaciones (10 productos) tenían
 * ahí la descripción COMPLETA del producto — más de 1.000 caracteres —
 * así que elegir una talla empujaba el botón de compra fuera de la
 * pantalla. Revisadas una por una: ninguna aporta información propia de
 * esa talla o color, todas son copias del texto del producto, que ya se
 * muestra en la sección "Descripción" de la ficha.
 *
 * Criterio de seguridad: solo se vacía si el texto de la variación está
 * contenido en la descripción del padre (o al revés). Una descripción de
 * variación que diga algo realmente distinto ("esta talla viene con biela
 * de 165mm") NO se toca — se reporta al final para revisarla a mano.
 *
 * El CSS del theme además oculta .woocommerce-variation-description, así
 * que el síntoma ya no se ve aunque alguien vuelva a pegar texto ahí
 * desde el admin; esto limpia el dato de origen.
 *
 * Uso: wp --skip-themes eval-file scripts/clean-variation-descriptions.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

/** Normaliza para comparar: sin HTML, sin dobles espacios, en minúsculas. */
function rb_normalize_text(string $html): string
{
    return trim(mb_strtolower(preg_replace('/\s+/u', ' ', wp_strip_all_tags($html))));
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'private'],
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$vaciadas = 0;
$conservadas = [];

foreach ($ids as $productId) {
    $product = wc_get_product($productId);

    if (! $product || ! $product->is_type('variable')) {
        continue;
    }

    $parentText = rb_normalize_text($product->get_description());
    $printedHeader = false;

    foreach ($product->get_children() as $variationId) {
        $variation = wc_get_product($variationId);

        if (! $variation) {
            continue;
        }

        $variationText = rb_normalize_text($variation->get_description());

        if ($variationText === '') {
            continue;
        }

        // ¿Es una copia (total o parcial) del texto del padre?
        $esCopia = $parentText !== '' && (
            str_contains($parentText, mb_substr($variationText, 0, 120))
            || str_contains($variationText, mb_substr($parentText, 0, 120))
        );

        if (! $esCopia) {
            $conservadas[] = [$productId, $variationId, mb_substr($variationText, 0, 120)];
            continue;
        }

        if (! $printedHeader) {
            echo "=== [{$productId}] {$product->get_name()} ===\n";
            $printedHeader = true;
        }

        printf("  variación %d: se vacía (%d caracteres duplicados del padre)\n", $variationId, mb_strlen($variationText));

        if ($apply) {
            $variation->set_description('');
            $variation->save();
        }

        $vaciadas++;
    }
}

echo "\n";
printf("%s: %d descripción(es) de variación vaciadas.\n", $apply ? 'Aplicado' : 'Se aplicaría', $vaciadas);

if ($conservadas) {
    echo "\nNO se tocaron (texto propio, distinto al del producto — revisar a mano):\n";
    foreach ($conservadas as [$productId, $variationId, $preview]) {
        echo "  [prod {$productId} / var {$variationId}] {$preview}...\n";
    }
} else {
    echo "\nNinguna variación tenía texto propio distinto al del producto.\n";
}
