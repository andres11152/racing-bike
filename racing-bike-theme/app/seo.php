<?php

/**
 * Meta description, OpenGraph, Twitter Card y datos estructurados del negocio.
 *
 * Antes de esto ninguna página emitía meta description ni etiquetas og:, así
 * que compartir un producto por WhatsApp —el canal de venta principal del
 * negocio, con botón flotante en todo el sitio— salía sin foto ni precio.
 * Todo vive en un único hook de wp_head para no repartir SEO por plantilla.
 */

namespace App;

use WC_Product;

/**
 * Descripción de la página actual, para <meta name="description"> y og:description.
 */
function rb_seo_description(): string
{
    if (is_front_page() || is_home()) {
        return __('Bicicletas de ruta, gravel y pista, componentes y accesorios en Bogotá desde 1998. Venta, armado y ajuste profesional, con envíos a toda Colombia.', 'sage');
    }

    if (function_exists('is_shop') && is_shop()) {
        $shopPageId = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
        $description = $shopPageId > 0 ? trim(strip_tags(get_the_excerpt($shopPageId))) : '';

        return $description ?: __('Catálogo de bicicletas, componentes y accesorios Racing Bike 1998. Todo probado y ajustado en nuestro taller de Bogotá.', 'sage');
    }

    if (function_exists('is_product_category') && (is_product_category() || is_product_tag())) {
        $description = trim(strip_tags(term_description()));

        return $description ?: sprintf(__('Descubre %s en Racing Bike 1998, tienda y taller de bicicletas en Bogotá desde 1998.', 'sage'), single_term_title('', false));
    }

    if (function_exists('is_product') && is_product()) {
        global $product;
        $wcProduct = $product instanceof WC_Product ? $product : wc_get_product(get_the_ID());
        $short = $wcProduct ? trim(strip_tags($wcProduct->get_short_description())) : '';

        return $short ?: trim(strip_tags(get_the_excerpt())) ?: sprintf(__('%s disponible en Racing Bike 1998, Bogotá. Envíos a toda Colombia.', 'sage'), get_the_title());
    }

    if (is_singular()) {
        $excerpt = trim(strip_tags(get_the_excerpt()));

        if ($excerpt) {
            return $excerpt;
        }

        // strip_shortcodes() antes de recortar: si no, una página como
        // /cart/ o /my-account/ —cuyo post_content es literalmente
        // "[woocommerce_cart]"— termina con ese corchete crudo como meta
        // description.
        $fromContent = wp_trim_words(strip_tags(strip_shortcodes(get_the_content())), 30, '…');

        if ($fromContent) {
            return $fromContent;
        }

        // Estas plantillas construyen su copy directo en el Blade, no en
        // post_content editable desde WordPress — get_the_excerpt() y
        // get_the_content() siempre vuelven vacíos aquí, así que sin este
        // mapa la meta description sale en blanco en 4 páginas del sitio.
        $templateDescriptions = [
            'template-about.blade.php' => __('La historia de Racing Bike 1998: taller y tienda de bicicletas en Bogotá, armando y ajustando cada bicicleta a mano desde hace más de dos décadas.', 'sage'),
            'template-legal.blade.php' => __('Política de privacidad, términos de servicio y condiciones de garantía de Racing Bike 1998.', 'sage'),
            'template-faqs.blade.php' => __('Respuestas a las preguntas más frecuentes sobre pagos, envíos, garantía y armado de bicicletas en Racing Bike 1998.', 'sage'),
        ];

        $template = get_page_template_slug();

        if (isset($templateDescriptions[$template])) {
            return $templateDescriptions[$template];
        }

        return get_bloginfo('description') ?: get_the_title();
    }

    return get_bloginfo('description');
}

/**
 * URL de imagen para og:image / twitter:image de la página actual.
 */
function rb_seo_image_url(): ?string
{
    if (function_exists('is_product') && is_product()) {
        $imageId = get_post_thumbnail_id();

        if ($imageId) {
            return wp_get_attachment_image_url($imageId, 'large') ?: null;
        }
    }

    if (is_singular() && has_post_thumbnail()) {
        return get_the_post_thumbnail_url(null, 'large') ?: null;
    }

    $siteIconId = get_option('site_icon');

    return $siteIconId ? wp_get_attachment_image_url($siteIconId, 'full') : null;
}

/**
 * URL canónica de la página actual.
 *
 * No se usa wp_get_canonical_url(): en este sitio `show_on_front` está en
 * "posts" pero el tema fuerza front-page.blade.php como portada igual, y esa
 * combinación hace que el helper de WordPress devuelva la URL de la última
 * entrada del blog en vez de la home — un dato verificado, no una hipótesis.
 * Más simple y más fiable resolver la canónica nosotros mismos caso por caso.
 */
function rb_seo_canonical_url(): string
{
    if (is_front_page() || is_home()) {
        return home_url('/');
    }

    if (function_exists('is_shop') && is_shop()) {
        $shopPageId = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;

        return ($shopPageId > 0 ? get_permalink($shopPageId) : false) ?: home_url('/tienda/');
    }

    if (is_singular()) {
        return get_permalink() ?: home_url('/');
    }

    if (function_exists('is_product_category') && (is_product_category() || is_product_tag() || is_category() || is_tag())) {
        return get_term_link(get_queried_object()) ?: home_url('/');
    }

    global $wp;

    return home_url(add_query_arg([], $wp->request ?? ''));
}

// Cuando Rank Math está activo, él gestiona meta description, OpenGraph,
// Twitter Cards, canonical y schema — emitir los nuestros duplicaría todo.
// Las funciones rb_seo_description(), rb_seo_image_url() y rb_seo_canonical_url()
// siguen disponibles como helpers para cualquier plantilla que las necesite.
if ( ! class_exists( 'RankMath' ) ) {
    add_action('wp_head', function () {
        $description = rb_seo_description();
        $canonical = rb_seo_canonical_url();
        $image = rb_seo_image_url();
        $siteName = get_bloginfo('name');
        $ogType = (function_exists('is_product') && is_product()) ? 'product' : (is_singular('post') ? 'article' : 'website');

        echo "\n<!-- Racing Bike SEO: meta description, OpenGraph, Twitter Card -->\n";

        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));

        $trustCoreCanonical = is_singular() && wp_get_canonical_url();

        if (! $trustCoreCanonical) {
            printf('<link rel="canonical" href="%s" />' . "\n", esc_url($canonical));
        }

        printf('<meta property="og:site_name" content="%s" />' . "\n", esc_attr($siteName));
        printf('<meta property="og:locale" content="es_CO" />' . "\n");
        printf('<meta property="og:type" content="%s" />' . "\n", esc_attr($ogType));
        printf('<meta property="og:title" content="%s" />' . "\n", esc_attr(wp_get_document_title()));
        printf('<meta property="og:description" content="%s" />' . "\n", esc_attr($description));
        printf('<meta property="og:url" content="%s" />' . "\n", esc_url($canonical));

        if ($image) {
            printf('<meta property="og:image" content="%s" />' . "\n", esc_url($image));
        }

        if ($ogType === 'product' && function_exists('is_product') && is_product()) {
            global $product;
            $wcProduct = $product instanceof WC_Product ? $product : wc_get_product(get_the_ID());

            if ($wcProduct) {
                printf('<meta property="product:price:amount" content="%s" />' . "\n", esc_attr($wcProduct->get_price()));
                printf('<meta property="product:price:currency" content="%s" />' . "\n", esc_attr(get_woocommerce_currency()));
            }
        }

        printf('<meta name="twitter:card" content="%s" />' . "\n", $image ? 'summary_large_image' : 'summary');
        printf('<meta name="twitter:title" content="%s" />' . "\n", esc_attr(wp_get_document_title()));
        printf('<meta name="twitter:description" content="%s" />' . "\n", esc_attr($description));

        if ($image) {
            printf('<meta name="twitter:image" content="%s" />' . "\n", esc_url($image));
        }

        if (is_front_page() || is_home()) {
            $contact = contact_info();

            $orgSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'BicycleStore',
                'name' => $siteName,
                'url' => home_url('/'),
                'description' => rb_seo_description(),
                'foundingDate' => (string) $contact['founded'],
                'telephone' => '+'.$contact['whatsapp'],
                'email' => $contact['email'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $contact['address'],
                    'addressLocality' => $contact['city'],
                    'addressRegion' => $contact['region'],
                    'addressCountry' => $contact['country'],
                ],
                'sameAs' => array_values(array_filter([$contact['instagram'], $contact['facebook'], $contact['tiktok'] ?? ''])),
            ];

            echo '<script type="application/ld+json">'
                . wp_json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>' . "\n";
        }

        echo "<!-- /Racing Bike SEO -->\n";
    }, 5);
}
