<?php

/**
 * Ajuste fino post seo-fix-rb-housebrand-headings.php: con el H2 y el
 * CTA llevando la keyword además del intro, estos 4 productos (40, 42,
 * 44, 46) tienen contenido tan corto (69-75 palabras base) que 3
 * repeticiones de la keyword superan el 2.5% de densidad que exige
 * keywordDensity (test escalonado, vale 6 puntos — más caro que los 3+3
 * de keywordInSubheadings/keywordIn10Percent que se querían arreglar).
 *
 * Revierte el CTA a su forma genérica (vuelve a 2 repeticiones: intro +
 * H2) y agrega una frase corta y genuina a las specs de cada producto
 * para subir el conteo de palabras lo suficiente (se necesitan ~80
 * palabras totales para que 2 repeticiones bajen de 2.5%). No es
 * relleno para inflar longitud: es una frase real sobre el producto,
 * distinta para cada uno.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-rb-housebrand-density.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_FRASES = [
    40 => 'Un componente pensado para ciclistas que buscan más velocidad y rigidez en ruta.',
    42 => 'Un componente pensado para mejorar la comodidad en salidas largas.',
    44 => 'Un accesorio pensado para mejorar el agarre y la comodidad en cada salida.',
    46 => 'Un accesorio pensado para mantenerte hidratado en cada salida en bici.',
];

foreach (RB_FRASES as $productId => $fraseExtra) {
    $product = wc_get_product($productId);

    if (! $product) {
        printf("[%d] producto no encontrado, se omite.\n", $productId);
        continue;
    }

    $html = (string) $product->get_description();
    $original = $html;
    $nombre = $product->get_name();

    // 1) Revertir el CTA a la forma genérica (vuelve a 2 repeticiones).
    $ctaConKeyword = 'Encuentra ' . $nombre . ' junto a';
    $ctaGenerico = 'Encuentra este producto junto a';
    $tieneCtaConKeyword = str_contains($html, $ctaConKeyword);
    $html = str_replace($ctaConKeyword, $ctaGenerico, $html);

    // 2) Agregar la frase extra al final del párrafo de specs (el que
    // sigue justo al primer H2), si todavía no está.
    $yaTieneFrase = str_contains($html, $fraseExtra);
    if (! $yaTieneFrase) {
        $patron = '#(<h2 id="caracteristicas-' . $productId . '">[^<]*</h2>\s*<p>)((?:(?!</p>).)*)(</p>)#us';
        $html = preg_replace_callback($patron, function ($m) use ($fraseExtra) {
            return $m[1] . rtrim($m[2]) . ' ' . $fraseExtra . $m[3];
        }, $html, 1, $count);
        $fraseAgregada = $count > 0;
    } else {
        $fraseAgregada = true;
    }

    printf(
        "[%d] %s\n  CTA revertido a genérico: %s | frase extra agregada: %s\n",
        $productId,
        $nombre,
        $tieneCtaConKeyword ? 'sí' : 'ya estaba genérico',
        $fraseAgregada ? 'sí' : 'NO — revisar a mano'
    );

    if (! $fraseAgregada) {
        echo "  *** no se pudo insertar la frase — no se escribe nada para este producto. ***\n\n";
        continue;
    }

    if (! $apply) {
        echo "  --- HTML resultante (vista previa) ---\n{$html}\n  --- fin ---\n\n";
    }

    if ($apply && $html !== $original) {
        $product->set_description($html);
        $product->save();
        echo "  guardado.\n\n";
    } elseif ($apply) {
        echo "  sin cambios.\n\n";
    }
}

echo ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
