<?php

/**
 * 1862, 1775 y 1799 heredan del feed de origen (Cyclewear) un párrafo
 * de specs con los ítems pegados sin espacio ("...cassette 11-34T.Shimano
 * 105 Di2 R7170.Transmisión...") — no es algo que haya introducido la
 * reestructuración de descripciones, ya venía así en el contenido
 * original y quedó intacto porque las reparaciones de contenido
 * duplicado (scripts/seo-fix-duplicated-content.php) preservaron ese
 * párrafo tal cual. Además de verse mal para cualquier lector, hace que
 * ese párrafo supere las ~120 palabras y falle contentHasShortParagraphs.
 *
 * Convierte ese párrafo en una lista <ul><li> separando cada ítem por el
 * patrón "letra/número + punto + Mayúscula" (el punto de cierre de cada
 * ítem, pegado al siguiente) — el mismo criterio que ya usa
 * seo-structure-descriptions.php para separar oraciones fusionadas.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-specs-list-format.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_IDS = [1862, 1775, 1799];

foreach (RB_IDS as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        printf("[%d] producto no encontrado, se omite.\n", $productId);
        continue;
    }

    $html = (string) $product->get_description();
    $original = $html;

    if (! preg_match('#<p>\s*([^<]{300,})</p>#u', $html, $m)) {
        printf("[%d] no se encontró el párrafo largo de specs, se omite.\n", $productId);
        continue;
    }

    $bloque = $m[0];
    $texto = trim($m[1]);

    // El punto de cierre de cada ítem queda pegado al siguiente sin
    // espacio; el separador puede venir después de una letra en
    // mayúscula (p. ej. "11-34T.Bielas") o de un número (p. ej.
    // "Di2.12 velocidades"), así que el lookbehind cubre ambos casos —
    // este bloque nunca trae precios con puntos de miles, así que no
    // hay riesgo de cortar un número como "2.210.000".
    $items = preg_split('/(?<=[A-Za-zÁÉÍÓÚÑáéíóúñ0-9%])\.\s*(?=[A-ZÁÉÍÓÚÑ0-9])/u', $texto);
    $items = array_values(array_filter(array_map('trim', $items), fn ($s) => $s !== ''));

    if (count($items) < 3) {
        printf("[%d] el párrafo no se pudo separar en suficientes ítems (%d), se omite.\n", $productId, count($items));
        continue;
    }

    $li = array_map(function ($item) {
        $item = rtrim($item, '.');

        return '<li>' . esc_html($item) . '.</li>';
    }, $items);

    $lista = '<ul>' . implode('', $li) . '</ul>';
    $html = str_replace($bloque, $lista, $html);

    printf("[%d] %s\n  párrafo de %d caracteres -> lista de %d ítems\n", $productId, $product->get_name(), strlen($texto), count($items));

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
