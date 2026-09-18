<?php

/**
 * El "1 Producto" que el SEO Analyzer de Rank Math sigue marcando en
 * "Títulos de la entrada sin palabras clave objetivo" (junto a las 5
 * páginas, ya corregidas en seo-fix-page-post-title.php) es esto:
 * productos 1950 y 506 tienen "1x11" / "2x8" con una x normal en el
 * post_title guardado, pero WordPress aplica wptexturize() a
 * get_the_title() al renderizar — convierte una "x" entre números en el
 * signo de multiplicación tipográfico "×" antes de imprimirla. Rank Math
 * arma el <title> real (el que de verdad indexa Google) usando esa
 * misma función, así que la etiqena que se sirve dice "1×11" / "2×8",
 * mientras que la keyword focal asignada dice "1x11" / "2x8" con x
 * normal — de ahí el mismatch real.
 *
 * (Confirmado con get_post_field('post_title', ...): el dato crudo en
 * la base de datos SÍ tiene "x" normal, por eso mi primer diagnóstico
 * —que usaba ese campo crudo— no encontró nada que arreglar aquí. El
 * mismatch solo existe en la versión ya renderizada del título, que es
 * justo la que Rank Math usa para el <title> y probablemente para este
 * test del Analyzer.)
 *
 * No cambia el post_title (el slug y el resto del catálogo ya usan "x"
 * normal, cambiarlo rompería esa consistencia) — en vez de eso ajusta la
 * keyword focal para que coincida con lo que realmente se renderiza e
 * indexa.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-keyword-multiplication-sign.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_PRODUCTOS = [1950, 506];

foreach (RB_PRODUCTOS as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        printf("[%d] producto no encontrado, se omite.\n", $productId);
        continue;
    }

    $tituloRenderizado = get_the_title($productId);
    $keywordActual = trim(explode(',', (string) get_post_meta($productId, 'rank_math_focus_keyword', true))[0]);
    $keywordNueva = str_replace('x', '×', $keywordActual);

    printf(
        "[%d] título renderizado=\"%s\"\n  keyword actual=\"%s\"\n  keyword nueva=\"%s\"\n",
        $productId,
        $tituloRenderizado,
        $keywordActual,
        $keywordNueva
    );

    if ($keywordActual === $keywordNueva) {
        echo "  sin cambios (ya coincide), se omite.\n\n";
        continue;
    }

    if ($apply) {
        update_post_meta($productId, 'rank_math_focus_keyword', $keywordNueva);
        echo "  guardado.\n\n";
    } else {
        echo "\n";
    }
}

echo ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
