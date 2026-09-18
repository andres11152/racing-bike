<?php

/**
 * Ajusta la meta descripción de Rank Math para que incluya la keyword
 * focal cuando no la tiene — keywordInMetaDescription vale 2 puntos y
 * afectaba a 37 de 49 productos: las meta descripciones se generaron
 * antes (scripts/generate-meta-descriptions.php) a partir del resumen
 * corto, sin tener en cuenta la keyword focal que se asignó por
 * separado.
 *
 * No reescribe la meta descripción existente: si la keyword no aparece,
 * se la antepone tal cual ("{keyword}. {descripción existente}",
 * recortando a 155 caracteres si hace falta) — se conserva el texto ya
 * escrito, solo se ajusta el orden.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-meta-description-keyword.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

function rb_norm(string $s): string
{
    $s = mb_strtolower(wp_strip_all_tags($s));
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);

    return trim(preg_replace('/\s+/u', ' ', $s));
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$actualizados = 0;
$yaTenian = 0;
$sinKeyword = 0;

foreach ($ids as $productId) {
    $keyword = trim(explode(',', (string) get_post_meta($productId, 'rank_math_focus_keyword', true))[0]);
    $description = trim((string) get_post_meta($productId, 'rank_math_description', true));

    if ($keyword === '') {
        $sinKeyword++;
        continue;
    }

    if ($description === '' || str_contains(rb_norm($description), rb_norm($keyword))) {
        $yaTenian++;
        continue;
    }

    $new = $keyword . '. ' . $description;
    if (mb_strlen($new) > 155) {
        $new = mb_substr($new, 0, 155);
        $lastSpace = mb_strrpos($new, ' ');
        if ($lastSpace !== false) {
            $new = mb_substr($new, 0, $lastSpace);
        }
        $new = rtrim($new, " ,.;:-") . '…';
    }

    printf("[%d] %s\n  antes: \"%s\"\n  ahora: \"%s\"\n\n", $productId, get_the_title($productId), $description, $new);

    if ($apply) {
        update_post_meta($productId, 'rank_math_description', $new);
    }

    $actualizados++;
}

echo "\n";
printf(
    "%s: %d meta descripción(es). Ya tenían la keyword: %d. Sin keyword asignada: %d.\n",
    $apply ? 'Aplicado' : 'Se aplicaría',
    $actualizados,
    $yaTenian,
    $sinKeyword
);
