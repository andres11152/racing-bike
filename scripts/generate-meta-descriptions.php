<?php

/**
 * Rellena la meta descripción de Rank Math (rank_math_description) de
 * productos que no tienen una propia. La plantilla global de Rank Math
 * para productos es "%excerpt%" (el resumen corto) — si el resumen corto
 * está vacío, Google no tiene nada que mostrar bajo el título en el
 * resultado de búsqueda.
 *
 * No inventa texto de marketing: solo REUTILIZA contenido que el
 * producto ya tiene (resumen corto, o si no hay, el primer párrafo de la
 * descripción larga), recortado a ~155 caracteres. Un producto sin
 * resumen NI descripción se reporta al final para que alguien escriba el
 * texto a mano — fabricar una descripción de venta sin conocer el
 * producto es peor que dejarla vacía.
 *
 * Solo toca productos donde rank_math_description está vacío: no pisa
 * ninguna meta descripción que ya se haya escrito a mano.
 *
 * Usage: wp --skip-themes eval-file scripts/generate-meta-descriptions.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

/**
 * Recorta a $limit caracteres sin cortar una palabra a la mitad.
 */
function rb_truncate_at_word(string $text, int $limit): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));

    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    $truncated = mb_substr($text, 0, $limit);
    $lastSpace = mb_strrpos($truncated, ' ');

    if ($lastSpace !== false) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }

    return rtrim($truncated, " ,.;:-") . '…';
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$stats = ['filled' => 0, 'skipped_has_own' => 0, 'skipped_no_content' => 0];
$noContent = [];

foreach ($ids as $productId) {
    $existing = trim((string) get_post_meta($productId, 'rank_math_description', true));

    if ($existing !== '') {
        $stats['skipped_has_own']++;
        continue;
    }

    $product = wc_get_product($productId);

    if (! $product) {
        continue;
    }

    $source = wp_strip_all_tags($product->get_short_description());

    if ($source === '') {
        $description = wp_strip_all_tags($product->get_description());
        // Primer párrafo real (hasta el primer doble salto de línea del HTML original).
        $firstParagraph = preg_split('/\n\s*\n/', $description)[0] ?? $description;
        $source = trim($firstParagraph);
    }

    if ($source === '') {
        $noContent[] = [$productId, $product->get_name()];
        $stats['skipped_no_content']++;
        continue;
    }

    $metaDescription = rb_truncate_at_word($source, 155);

    echo "[{$productId}] {$product->get_name()}\n  -> \"{$metaDescription}\"\n";

    if ($apply) {
        update_post_meta($productId, 'rank_math_description', $metaDescription);
    }

    $stats['filled']++;
}

echo "\n";
printf(
    "%s: %d meta descripciones. Ya tenían la suya: %d. Sin contenido de origen (revisar a mano): %d.\n",
    $apply ? 'Aplicado' : 'Se aplicaría',
    $stats['filled'],
    $stats['skipped_has_own'],
    $stats['skipped_no_content']
);

if ($noContent) {
    echo "\nProductos sin resumen ni descripción — necesitan copy escrito a mano:\n";
    foreach ($noContent as [$id, $name]) {
        echo "  [{$id}] {$name}\n";
    }
}
