<?php

/**
 * Los 4 productos de marca propia RB (40, 42, 44, 46) quedaron con el
 * H2 de características genérico ("Características") y el CTA genérico
 * ("Encuentra este producto...") en vez del patrón con la keyword que
 * usa el resto del catálogo — porque en el momento en que se generaron
 * no tenían pa_marca (son RB_MARCA_INVALIDA en
 * seo-structure-descriptions.php) y tomaron una rama de texto distinta.
 * Eso les hace fallar keywordInSubheadings y keywordIn10Percent (6
 * puntos entre las dos).
 *
 * Ajusta solo esos dos fragmentos de texto, dejando todo lo demás
 * (specs, imagen, enlaces internos) intacto.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-rb-housebrand-headings.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_IDS = [40, 42, 44, 46];

foreach (RB_IDS as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        printf("[%d] producto no encontrado, se omite.\n", $productId);
        continue;
    }

    $html = (string) $product->get_description();
    $original = $html;
    $nombre = $product->get_name();

    $marcaH2 = '<h2 id="caracteristicas-' . $productId . '">Características</h2>';
    $nuevoH2 = '<h2 id="caracteristicas-' . $productId . '">Características de ' . $nombre . '</h2>';
    $tieneH2 = str_contains($html, $marcaH2);
    $html = str_replace($marcaH2, $nuevoH2, $html);

    $marcaCta = 'Encuentra este producto junto a';
    $nuevoCta = 'Encuentra ' . $nombre . ' junto a';
    $tieneCta = str_contains($html, $marcaCta);
    $html = str_replace($marcaCta, $nuevoCta, $html);

    printf(
        "[%d] %s\n  H2 genérico encontrado: %s | CTA genérico encontrado: %s\n\n",
        $productId,
        $nombre,
        $tieneH2 ? 'sí' : 'NO',
        $tieneCta ? 'sí' : 'NO'
    );

    if (! $tieneH2 || ! $tieneCta) {
        echo "  *** falta alguna marca esperada — revisar a mano, no se escribe nada para este producto. ***\n\n";
        continue;
    }

    if (! $apply) {
        echo "  --- HTML resultante (vista previa) ---\n{$html}\n  --- fin ---\n\n";
    }

    if ($apply && $html !== $original) {
        $product->set_description($html);
        $product->save();
        echo "  guardado.\n\n";
    }
}

echo ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
