<?php

/**
 * scripts/seo-fix-title-keyword-mismatch.php (rank_math_title) no fue
 * suficiente: el SEO Analyzer de Rank Math sigue marcando estas mismas
 * 5 páginas después de aplicarlo, porque ese test compara la keyword
 * contra el post_title real, no contra el SEO Title override. Ahora se
 * renombra directamente el post_title.
 *
 * Verificado antes de tocar nada que esto es seguro:
 * - Ninguna de las 5 páginas está referenciada en un menú de navegación
 *   de WordPress (el header/footer de este theme son Blade hardcodeado,
 *   no wp_nav_menu) — no hay riesgo de que cambie el texto de un enlace.
 * - 115 (Políticas y Legales), 114 (Sobre Nosotros) y 76 (Preguntas
 *   Frecuentes) tienen su H1 escrito a mano en el Blade de su plantilla
 *   (template-legal/about/faqs.blade.php) — el post_title no se ve en
 *   ningún lado del front-end, es solo el nombre interno en wp-admin.
 * - 82 (Encuentra tu talla) usa la plantilla de página genérica, cuyo H1
 *   SÍ es get_the_title() (ver app/View/Composers/Post.php) — el
 *   post_title se vuelve visible como encabezado de la página.
 * - 5 (Tienda) es la página de tienda de WooCommerce; su H1 en
 *   woocommerce/archive-product.blade.php usa woocommerce_page_title(),
 *   que a su vez llama a get_the_title() sobre esta misma página — el
 *   post_title también se vuelve visible ahí.
 *
 * Para 82 y 5 el nuevo texto es un reemplazo natural, no una keyword
 * pegada sin sentido, así que el cambio visible es aceptable.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-page-post-title.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_PAGINAS_POST_TITLE = [
    115 => 'Garantía Bicicletas Racing Bike - Políticas y Legales',
    114 => 'Racing Bike 1998 - Sobre Nosotros',
    82 => 'Encuentra tu Talla de Bicicleta',
    76 => 'Preguntas Frecuentes sobre Bicicletas',
    5 => 'Tienda de Bicicletas en Bogotá',
];

const RB_PAGINAS_CON_H1_VISIBLE = [82, 5];

foreach (RB_PAGINAS_POST_TITLE as $pageId => $nuevoTitulo) {
    $actual = get_the_title($pageId);
    $visible = in_array($pageId, RB_PAGINAS_CON_H1_VISIBLE, true);

    printf(
        "[%d] \"%s\" -> \"%s\"%s\n",
        $pageId,
        $actual,
        $nuevoTitulo,
        $visible ? '  (cambia el H1 visible de la página)' : '  (solo interno, el H1 real está aparte en el Blade)'
    );

    if ($apply) {
        wp_update_post([
            'ID' => $pageId,
            'post_title' => $nuevoTitulo,
        ]);
        echo "  guardado.\n";
    }
}

echo "\n" . ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
