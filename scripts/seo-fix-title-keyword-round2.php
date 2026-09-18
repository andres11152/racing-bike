<?php

/**
 * El SEO Analyzer de Rank Math seguía marcando "2 Páginas, 3 Productos"
 * después de los dos arreglos anteriores. Investigado a fondo con una
 * comparación case-sensitive y sin normalizar (para replicar lo más
 * fielmente posible lo que compara Rank Math), aparecen 3 causas reales
 * distintas — ninguna relacionada con lo que se pensaba antes:
 *
 * 1) Productos 1950 y 506: wptexturize() de WordPress SOLO convierte una
 *    "x" MINÚSCULA entre números en el signo "×" al renderizar — una "X"
 *    mayúscula no se toca (probado con wptexturize() directamente en el
 *    servidor). El resto del catálogo ya usa "X" mayúscula en este
 *    patrón (p.ej. "1X11", "2X8" en otros títulos), así que la solución
 *    correcta no era tocar la keyword (como hizo el commit anterior,
 *    equivocadamente) sino usar "X" mayúscula en el título — así el
 *    título nunca se transforma al renderizar y el problema desaparece
 *    de raíz. Se revierte la keyword a "x" minúscula normal.
 *
 * 2) Producto 1775: el título tiene un espacio NBSP (U+00A0, \xc2\xa0)
 *    pegado a un espacio normal entre "R7170" y "DI2" — probablemente
 *    quedó de un copy-paste desde una ficha en PDF/Word. Visualmente es
 *    idéntico a un espacio simple, pero como cadena de texto rompe la
 *    coincidencia exacta con la keyword "...R7170 DI2..." (espacio
 *    simple). Se reemplaza el NBSP y se colapsa a un solo espacio.
 *
 * 3) Páginas 76 y 5: el post_title que se les puso en el commit anterior
 *    intercaló palabras nuevas ("sobre", "en") en medio de la frase de
 *    la keyword en vez de mantenerla como subcadena literal —
 *    "Preguntas Frecuentes SOBRE Bicicletas" ya no contiene literalmente
 *    "Preguntas frecuentes bicicletas", y "Tienda de Bicicletas EN
 *    Bogotá" ya no contiene literalmente "Tienda de bicicletas Bogotá".
 *    Se ajustan a la keyword exacta.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-title-keyword-round2.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

echo "--- 1) Título de 1950 y 506: 'x' minúscula -> 'X' mayúscula (evita wptexturize) ---\n\n";

const RB_PRODUCTOS_X_MAYUSCULA = [1950, 506];

foreach (RB_PRODUCTOS_X_MAYUSCULA as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        printf("[%d] producto no encontrado, se omite.\n", $productId);
        continue;
    }

    $titulo = $product->get_name();
    $nuevoTitulo = preg_replace('/(\d)x(\d)/', '$1X$2', $titulo);
    $keywordActual = trim(explode(',', (string) get_post_meta($productId, 'rank_math_focus_keyword', true))[0]);
    $keywordNueva = str_replace('×', 'x', $keywordActual);

    printf("[%d] título \"%s\" -> \"%s\"\n  keyword \"%s\" -> \"%s\"\n", $productId, $titulo, $nuevoTitulo, $keywordActual, $keywordNueva);

    if ($apply) {
        if ($titulo !== $nuevoTitulo) {
            $product->set_name($nuevoTitulo);
            $product->save();
        }
        if ($keywordActual !== $keywordNueva) {
            update_post_meta($productId, 'rank_math_focus_keyword', $keywordNueva);
        }
        echo "  guardado.\n";
    }
}

echo "\n--- 2) Producto 1775: quitar el espacio NBSP pegado al espacio normal ---\n\n";

$product1775 = wc_get_product(1775);
if ($product1775) {
    $titulo = $product1775->get_name();
    $nuevoTitulo = preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $titulo));

    printf("[1775] \"%s\" -> \"%s\"\n", $titulo, $nuevoTitulo);

    if ($apply && $titulo !== $nuevoTitulo) {
        $product1775->set_name($nuevoTitulo);
        $product1775->save();
        echo "  guardado.\n";
    }
} else {
    echo "[1775] producto no encontrado, se omite.\n";
}

echo "\n--- 3) Páginas 76 y 5: ajustar el post_title a la keyword literal ---\n\n";

const RB_PAGINAS_AJUSTE = [
    76 => 'Preguntas Frecuentes Bicicletas',
    5 => 'Tienda de Bicicletas Bogotá',
];

foreach (RB_PAGINAS_AJUSTE as $pageId => $nuevoTitulo) {
    $actual = get_the_title($pageId);

    printf("[%d] \"%s\" -> \"%s\"\n", $pageId, $actual, $nuevoTitulo);

    if ($apply) {
        wp_update_post([
            'ID' => $pageId,
            'post_title' => $nuevoTitulo,
        ]);
        echo "  guardado.\n";
    }
}

echo "\n" . ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
