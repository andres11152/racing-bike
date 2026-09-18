<?php

/**
 * Fase 3 del plan de puntaje SEO: agrega una power word real (verificada
 * contra assets/vendor/powerwords/es.php del propio plugin, no una lista
 * inventada) a la plantilla global de título de producto de Rank Math.
 *
 * Activa titleHasPowerWords (+1 punto) en los 49 productos publicados a
 * la vez, con un solo cambio. titleHasNumber ya pasaba en todos gracias
 * al "1998" del sufijo — no hace falta tocar nada por ese lado.
 * titleSentiment se queda igual de "fallando" a propósito: esa prueba
 * solo aplica en inglés (isApplicable === "en" === locale, ver
 * assets/admin/js/analyzer.js), así que en un sitio en español no cuenta
 * ni a favor ni en contra — Rank Math la excluye del total posible.
 *
 * racing-bike-seo-config.php ya trae el nuevo valor por defecto para
 * cuando el plugin se reactive desde cero, pero ese archivo solo corre
 * en activation_hook — no toca la opción ya guardada en la base de
 * datos. Este script actualiza esa opción en vivo, cambiando solo la
 * clave pt_product_title dentro de rank-math-options-titles (deja las
 * demás ~90 claves de esa opción intactas).
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-title-template.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_ESPERADO = '%title% %sep% Comprar en Racing Bike 1998';
const RB_NUEVO = '%title% %sep% Tienda Oficial Racing Bike 1998';

$titles = get_option('rank-math-options-titles');

if (! is_array($titles)) {
    echo "No se pudo leer la opción rank-math-options-titles (¿Rank Math está activo?). Se aborta.\n";
    return;
}

$actual = $titles['pt_product_title'] ?? null;

if ($actual !== RB_ESPERADO) {
    printf(
        "El valor actual (\"%s\") ya no es el esperado (\"%s\") — alguien lo cambió a mano en el panel. Se omite para no pisarlo.\n",
        $actual,
        RB_ESPERADO
    );

    return;
}

printf("pt_product_title:\n  \"%s\"\n  -> \"%s\"\n\n", $actual, RB_NUEVO);

if ($apply) {
    $titles['pt_product_title'] = RB_NUEVO;
    update_option('rank-math-options-titles', $titles);
    echo "Aplicado.\n";
} else {
    echo "Se aplicaría. Ninguna otra clave de la opción se toca.\n";
}
