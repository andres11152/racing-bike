<?php

/**
 * El SEO Analyzer de Rank Math marca "Títulos de la entrada sin palabras
 * clave objetivo": 5 páginas donde el nombre de la página (post_title) no
 * contiene la keyword focal asignada.
 *
 * (Nota: el propio Analyzer también contaba 1 producto ahí, pero era un
 * falso positivo de mi primer diagnóstico — get_the_title() pasa por
 * wptexturize(), que convierte "x" entre números en "×" solo para
 * mostrarlo en pantalla; el post_title real ya tenía "x" normal e
 * igual a la keyword. Verificado leyendo el campo crudo con
 * get_post_field('post_title', ...): ningún producto queda con mismatch
 * real. No se tocó ningún producto por esto.)
 *
 * El nombre de estas 5 páginas en wp-admin es corto y de navegación a
 * propósito (Tienda, Sobre Nosotros, etc.), y el H1 real está escrito a
 * mano en el Blade de cada plantilla — cambiar el post_title afectaría
 * menús/breadcrumbs sin necesidad. En vez de eso, se define un SEO Title
 * específico (rank_math_title) que sí lleva la keyword completa: es el
 * campo que realmente controla la etiqueta <title> que ve Google, sin
 * tocar nada visible en el sitio.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-title-keyword-mismatch.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_PAGINAS_SEO_TITLE = [
    115 => 'Garantía Bicicletas Racing Bike | Políticas y Legales %sep% %sitename%',
    114 => 'Racing Bike 1998 | Sobre Nosotros',
    82 => 'Encuentra tu Talla de Bicicleta %sep% %sitename%',
    76 => 'Preguntas Frecuentes sobre Bicicletas %sep% %sitename%',
    5 => 'Tienda de Bicicletas en Bogotá %sep% %sitename%',
];

foreach (RB_PAGINAS_SEO_TITLE as $pageId => $seoTitle) {
    $actual = get_post_meta($pageId, 'rank_math_title', true);

    printf("[%d] %s\n  actual: \"%s\"\n  nuevo:  \"%s\"\n", $pageId, get_the_title($pageId), $actual ?: '(sin definir)', $seoTitle);

    if ($apply) {
        update_post_meta($pageId, 'rank_math_title', $seoTitle);
        echo "  guardado.\n";
    }
}

echo "\n" . ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
