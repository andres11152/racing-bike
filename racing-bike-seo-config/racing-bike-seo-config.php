<?php
/**
 * Plugin Name:       Racing Bike SEO Config
 * Plugin URI:        https://racingbike1998.co/
 * Description:       Configura Rank Math SEO automáticamente con ajustes optimizados para Racing Bike 1998. Activar una vez después de instalar Rank Math.
 * Version:           1.0.1
 * Author:            Racing Bike 1998
 * Author URI:        https://racingbike1998.co/
 * License:           GPL-3.0+
 * Text Domain:       racing-bike-seo-config
 * Requires Plugins:  seo-by-rank-math
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asignar Palabras Clave Principales (Focus Keywords) si aún no tienen una.
 * Se ejecuta en admin_init para garantizar que WooCommerce ya registró el post_type 'product'.
 */
function rb_seo_ensure_focus_keywords() {
    $pages_map = [
        'inicio'               => 'Bicicletas Bogotá',
        'home'                 => 'Bicicletas Bogotá',
        'tienda'               => 'Tienda de bicicletas Bogotá',
        'shop'                 => 'Tienda de bicicletas Bogotá',
        'sobre-nosotros'       => 'Racing Bike 1998',
        'contacto'             => 'Taller de bicicletas Bogotá',
        'encuentra-tu-talla'   => 'Talla de bicicleta',
        'armar-bicicleta'      => 'Armar bicicleta',
        'preguntas-frecuentes' => 'Preguntas frecuentes bicicletas',
        'politicas'            => 'Garantía bicicletas Racing Bike',
    ];

    foreach ( [ 'page', 'product', 'post' ] as $pt ) {
        $posts = get_posts([
            'post_type'      => $pt,
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ]);
        foreach ( $posts as $p ) {
            $existing = get_post_meta( $p->ID, 'rank_math_focus_keyword', true );
            if ( empty( $existing ) ) {
                $kw = ( $pt === 'page' && isset( $pages_map[ $p->post_name ] ) )
                    ? $pages_map[ $p->post_name ]
                    : $p->post_title;
                update_post_meta( $p->ID, 'rank_math_focus_keyword', sanitize_text_field( $kw ) );
            }
        }
    }
}
add_action( 'admin_init', function () {
    rb_seo_ensure_focus_keywords();

    if ( get_option( 'rb_seo_config_plugin_version' ) !== '1.0.1' ) {
        if ( function_exists( 'rb_seo_config_activate' ) ) {
            rb_seo_config_activate();
        }
        update_option( 'rb_seo_config_plugin_version', '1.0.1' );
    }
} );

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * ACTIVACIÓN: escribe TODAS las opciones de Rank Math de golpe.
 * ─────────────────────────────────────────────────────────────────────────────
 */
register_activation_hook( __FILE__, 'rb_seo_config_activate' );

function rb_seo_config_activate() {
    // ─── Módulos activos ─────────────────────────────────────────────────
    $modules = [
        'sitemap',
        'rich-snippet',
        'woocommerce',
        'local-seo',
        'seo-analysis',
        'image-seo',
        'links',
        'robots-txt',
        'role-manager',
    ];
    update_option( 'rank_math_modules', $modules );

    // ─── Opciones Generales ──────────────────────────────────────────────
    $general = [
        'setup_mode'                          => 'advanced',
        'strip_category_base'                 => 'off',
        'attachment_redirect_urls'            => 'on',
        'attachment_redirect_default'         => home_url(),
        'nofollow_external_links'             => 'off',
        'nofollow_image_links'                => 'off',
        'new_window_external_links'           => 'on',
        'add_img_alt'                         => 'on',
        'img_alt_format'                      => '%title%',
        'add_img_title'                       => 'on',
        'img_title_format'                    => '%title%',
        'breadcrumbs'                         => 'on',
        'breadcrumbs_separator'               => '›',
        'breadcrumbs_home'                    => 'on',
        'breadcrumbs_home_label'              => 'Inicio',
        'breadcrumbs_archive_format'          => 'Archivo de %s',
        'breadcrumbs_search_format'           => 'Resultados de %s',
        'breadcrumbs_404_label'               => 'Página no encontrada',
        'breadcrumbs_ancestor_categories'     => 'on',
        'breadcrumbs_blog_page'               => 'off',
        '404_monitor_mode'                    => 'simple',
        '404_monitor_limit'                   => 100,
        '404_monitor_ignore_query_parameters' => 'on',
        'redirections_header_code'            => '301',
        'redirections_debug'                  => 'off',
        'console_caching_control'             => '90',
        'console_email_reports'               => 'on',
        'console_email_frequency'             => 'monthly',
        'wc_remove_product_base'              => 'off',
        'wc_remove_category_base'             => 'off',
        'wc_remove_category_parent_slugs'     => 'off',
        'wc_remove_generator'                 => 'on',
        'remove_shop_snippet_data'            => 'on',
        'rss_before_content'                  => '',
        'rss_after_content'                   => 'El artículo %post_link% fue publicado primero en %site_link%.',
        'frontend_seo_score'                  => 'off',
        'analytics_stats'                     => 'on',
        'content_ai_post_types'               => [ 'post', 'page', 'product' ],
        'content_ai_country'                  => 'co',
        'content_ai_tone'                     => 'Formal',
        'content_ai_audience'                 => 'Cycling Enthusiasts',
        'content_ai_language'                 => 'es',
        'toc_block_title'                     => 'Contenido',
        'toc_block_list_style'                => 'ul',
    ];
    update_option( 'rank-math-options-general', $general );

    // ─── Títulos y Meta ──────────────────────────────────────────────────
    $titles = [
        'title_separator'              => '|',
        'capitalize_titles'            => 'off',
        'twitter_card_type'            => 'summary_large_image',
        'noindex_empty_taxonomies'     => 'on',
        'noindex_search'               => 'on',
        'noindex_archive_subpages'     => 'off',
        'noindex_password_protected'   => 'on',

        // Homepage
        'homepage_title'               => 'Racing Bike 1998 %sep% Bicicletas de Alto Rendimiento en Bogotá',
        'homepage_description'         => 'Bicicletas de ruta, gravel y pista en Bogotá desde 1998. Venta de bicicletas, componentes y accesorios con envíos a toda Colombia. Taller profesional.',
        'homepage_custom_robots'       => 'off',

        // Knowledge Graph / Organization
        'knowledgegraph_type'          => 'company',
        'knowledgegraph_name'          => 'Racing Bike 1998',
        'website_name'                 => 'Racing Bike 1998',
        'website_alternate_name'       => 'Racing Bike',
        'knowledgegraph_url'           => 'https://racingbike1998.co/',
        'knowledgegraph_logo'          => '',
        'knowledgegraph_phone'         => '+573118485643',
        'knowledgegraph_contact_type'  => 'sales',
        'knowledgegraph_email'         => 'contacto@racingbike.com.co',

        // Local Business
        'local_business_type'          => 'BicycleStore',
        'local_address'                => [
            'streetAddress'   => 'Avenida Calle 45A Sur #52C-47',
            'addressLocality' => 'Bogotá',
            'addressRegion'   => 'Cundinamarca',
            'postalCode'      => '',
            'addressCountry'  => 'CO',
        ],
        'local_address_format'         => '{address} {locality}, {region} {postalcode}',
        'opening_hours'                => [
            [ 'day' => 'monday',    'time' => '09:00-18:00' ],
            [ 'day' => 'tuesday',   'time' => '09:00-18:00' ],
            [ 'day' => 'wednesday', 'time' => '09:00-18:00' ],
            [ 'day' => 'thursday',  'time' => '09:00-18:00' ],
            [ 'day' => 'friday',    'time' => '09:00-18:00' ],
            [ 'day' => 'saturday',  'time' => '09:00-14:00' ],
        ],
        'opening_hours_format'         => 'off',
        'phone_numbers'                => [
            [ 'type' => 'sales', 'number' => '+573118485643' ],
        ],

        // Social
        'social_url_facebook'          => 'https://www.facebook.com/RACINBIKE/',
        'social_url_instagram'         => 'https://www.instagram.com/racing_bike98/',
        'social_url_tiktok'            => 'https://www.tiktok.com/@racing.bike1998',
        'social_url_twitter'           => '',
        'social_url_youtube'           => '',
        'social_url_linkedin'          => '',
        'social_url_pinterest'         => '',

        // Author Archives
        'disable_author_archives'      => 'off',
        'author_custom_robots'         => 'on',
        'author_robots'                => [ 'noindex' ],
        'author_archive_title'         => '%name% %sep% %sitename% %page%',
        'author_add_meta_box'          => 'on',
        'author_slack_enhanced_sharing' => 'on',

        // Date Archives
        'disable_date_archives'        => 'on',
        'date_archive_title'           => '%date% %page% %sep% %sitename%',
        'date_archive_robots'          => [ 'noindex' ],

        // Search & 404
        'search_title'                 => 'Resultados de "%search_query%" %page% %sep% %sitename%',
        '404_title'                    => 'Página no encontrada %sep% %sitename%',

        // Posts
        'pt_post_title'                      => '%title% %sep% %sitename%',
        'pt_post_description'                => '%excerpt%',
        'pt_post_robots'                     => [ 'index' ],
        'pt_post_custom_robots'              => 'off',
        'pt_post_default_rich_snippet'       => 'article',
        'pt_post_default_article_type'       => 'BlogPosting',
        'pt_post_default_snippet_name'       => '%seo_title%',
        'pt_post_default_snippet_desc'       => '%seo_description%',
        'pt_post_archive_title'              => 'Blog %page% %sep% %sitename%',
        'pt_post_add_meta_box'               => 'on',
        'pt_post_bulk_editing'               => 'editing',
        'pt_post_link_suggestions'           => 'on',
        'pt_post_primary_taxonomy'           => 'category',
        'pt_post_slack_enhanced_sharing'     => 'on',
        'pt_post_ls_use_fk'                  => 'titles',

        // Pages
        'pt_page_title'                      => '%title% %sep% %sitename%',
        'pt_page_description'                => '%excerpt%',
        'pt_page_robots'                     => [ 'index' ],
        'pt_page_custom_robots'              => 'off',
        'pt_page_default_rich_snippet'       => 'article',
        'pt_page_default_article_type'       => 'Article',
        'pt_page_default_snippet_name'       => '%seo_title%',
        'pt_page_default_snippet_desc'       => '%seo_description%',
        'pt_page_add_meta_box'               => 'on',
        'pt_page_bulk_editing'               => 'editing',
        'pt_page_link_suggestions'           => 'on',
        'pt_page_slack_enhanced_sharing'     => 'on',
        'pt_page_ls_use_fk'                  => 'titles',

        // Products
        // "Oficial" está en la lista real de power words en español de
        // Rank Math (assets/vendor/powerwords/es.php) — activa
        // titleHasPowerWords (+1 pt) en los 49 productos a la vez. El
        // "1998" ya cubre titleHasNumber por sí solo, sin que haga falta
        // nada más aquí.
        'pt_product_title'                   => '%title% %sep% Tienda Oficial Racing Bike 1998',
        'pt_product_description'             => '%excerpt%',
        'pt_product_robots'                  => [ 'index' ],
        'pt_product_custom_robots'           => 'off',
        'pt_product_default_rich_snippet'    => 'product',
        'pt_product_default_article_type'    => 'Article',
        'pt_product_default_snippet_name'    => '%seo_title%',
        'pt_product_default_snippet_desc'    => '%seo_description%',
        'pt_product_archive_title'           => 'Tienda %page% %sep% Racing Bike 1998',
        'pt_product_add_meta_box'            => 'on',
        'pt_product_bulk_editing'            => 'editing',
        'pt_product_link_suggestions'        => 'on',
        'pt_product_primary_taxonomy'        => 'product_cat',
        'pt_product_slack_enhanced_sharing'  => 'on',
        'pt_product_ls_use_fk'              => 'titles',

        // Attachment
        'pt_attachment_title'                => '%title% %sep% %sitename%',
        'pt_attachment_description'          => '%excerpt%',
        'pt_attachment_robots'               => [ 'noindex' ],
        'pt_attachment_custom_robots'        => 'on',
        'pt_attachment_default_rich_snippet' => 'off',
        'pt_attachment_default_article_type' => 'Article',
        'pt_attachment_add_meta_box'         => 'off',

        // Taxonomy: product_cat
        'tax_product_cat_title'                  => '%term% %sep% %sitename% %page%',
        'tax_product_cat_description'            => '%term_description%',
        'tax_product_cat_robots'                 => [ 'index' ],
        'tax_product_cat_custom_robots'          => 'off',
        'tax_product_cat_add_meta_box'           => 'on',
        'tax_product_cat_slack_enhanced_sharing' => 'on',
        'tax_product_cat_bulk_editing'           => 0,
        'remove_product_cat_snippet_data'        => 'off',

        // Taxonomy: product_tag
        'tax_product_tag_title'                  => '%term% %sep% %sitename% %page%',
        'tax_product_tag_description'            => '%term_description%',
        'tax_product_tag_robots'                 => [ 'noindex' ],
        'tax_product_tag_custom_robots'          => 'on',
        'tax_product_tag_add_meta_box'           => 'off',
        'tax_product_tag_slack_enhanced_sharing' => 'on',
        'tax_product_tag_bulk_editing'           => 0,
        'remove_product_tag_snippet_data'        => 'on',

        // Taxonomy: category
        'tax_category_title'                  => '%term% %sep% %sitename% %page%',
        'tax_category_description'            => '%term_description%',
        'tax_category_robots'                 => [ 'index' ],
        'tax_category_custom_robots'          => 'off',
        'tax_category_add_meta_box'           => 'on',
        'tax_category_slack_enhanced_sharing' => 'on',
        'tax_category_bulk_editing'           => 0,

        // Taxonomy: post_tag
        'tax_post_tag_title'                  => '%term% %sep% %sitename% %page%',
        'tax_post_tag_description'            => '%term_description%',
        'tax_post_tag_robots'                 => [ 'noindex' ],
        'tax_post_tag_custom_robots'          => 'on',
        'tax_post_tag_add_meta_box'           => 'off',
        'tax_post_tag_slack_enhanced_sharing' => 'on',
        'tax_post_tag_bulk_editing'           => 0,

        // WC attribute taxonomies (pa_*)
        'tax_pa_marca_title'                  => '%term% %sep% %sitename%',
        'tax_pa_marca_robots'                 => [ 'noindex' ],
        'tax_pa_marca_custom_robots'          => 'on',
        'tax_pa_marca_add_meta_box'           => 'off',
        'tax_pa_marca_slack_enhanced_sharing' => 'on',
        'remove_pa_marca_snippet_data'        => 'on',

        'tax_pa_disciplina_title'              => '%term% %sep% %sitename%',
        'tax_pa_disciplina_robots'             => [ 'noindex' ],
        'tax_pa_disciplina_custom_robots'      => 'on',
        'tax_pa_disciplina_add_meta_box'       => 'off',
        'remove_pa_disciplina_snippet_data'    => 'on',

        'tax_pa_grupo_title'                   => '%term% %sep% %sitename%',
        'tax_pa_grupo_robots'                  => [ 'noindex' ],
        'tax_pa_grupo_custom_robots'           => 'on',
        'tax_pa_grupo_add_meta_box'            => 'off',
        'remove_pa_grupo_snippet_data'         => 'on',

        'tax_pa_material_title'                => '%term% %sep% %sitename%',
        'tax_pa_material_robots'               => [ 'noindex' ],
        'tax_pa_material_custom_robots'        => 'on',
        'tax_pa_material_add_meta_box'         => 'off',
        'remove_pa_material_snippet_data'      => 'on',

        'tax_pa_talla-cuadro_title'            => '%term% %sep% %sitename%',
        'tax_pa_talla-cuadro_robots'           => [ 'noindex' ],
        'tax_pa_talla-cuadro_custom_robots'    => 'on',
        'tax_pa_talla-cuadro_add_meta_box'     => 'off',
        'remove_pa_talla-cuadro_snippet_data'  => 'on',
    ];

    // Logo from site_icon
    $site_icon_id = get_option( 'site_icon' );
    if ( $site_icon_id ) {
        $logo_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
        if ( $logo_url ) {
            $titles['knowledgegraph_logo'] = $logo_url;
        }
    }

    update_option( 'rank-math-options-titles', $titles );

    // ─── Sitemap ─────────────────────────────────────────────────────────
    $sitemap = [
        'items_per_page'              => 200,
        'include_images'              => 'on',
        'include_featured_image'      => 'on',
        'exclude_roles'               => [ 'subscriber', 'contributor' ],
        'html_sitemap'                => 'on',
        'html_sitemap_display'        => 'shortcode',
        'html_sitemap_sort'           => 'published',
        'html_sitemap_seo_titles'     => 'titles',
        'authors_sitemap'             => 'off',
        'pt_post_sitemap'             => 'on',
        'pt_page_sitemap'             => 'on',
        'pt_product_sitemap'          => 'on',
        'pt_attachment_sitemap'       => 'off',
        'tax_category_sitemap'        => 'on',
        'tax_product_cat_sitemap'     => 'on',
        'tax_post_tag_sitemap'        => 'off',
        'tax_product_tag_sitemap'     => 'off',
        'tax_pa_marca_sitemap'        => 'off',
        'tax_pa_disciplina_sitemap'   => 'off',
        'tax_pa_grupo_sitemap'        => 'off',
        'tax_pa_material_sitemap'     => 'off',
        'tax_pa_talla-cuadro_sitemap' => 'off',
    ];
    update_option( 'rank-math-options-sitemap', $sitemap );

    // Asignar Focus Keywords
    rb_seo_ensure_focus_keywords();

    // ─── Marcar como configurado ─────────────────────────────────────────
    update_option( 'rank_math_wizard_completed', true );
    update_option( 'rank_math_registration_skip', true );
    update_option( 'rank_math_review_posts_converted', true );

    // Flush rewrite rules para sitemaps
    flush_rewrite_rules();

    // Transient para aviso admin
    set_transient( 'rb_seo_config_activated', true, 60 );
}

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * RUNTIME: Ajustes que se aplican en cada request cuando ambos plugins activos.
 * ─────────────────────────────────────────────────────────────────────────────
 */
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'RankMath' ) ) {
        return;
    }

    // Auto Image ALT: garantizar que ninguna imagen de la biblioteca quede con alt vacío
    add_filter( 'wp_get_attachment_image_attributes', function( $attr, $attachment ) {
        if ( empty( $attr['alt'] ) ) {
            $att_id = is_object( $attachment ) ? ( $attachment->ID ?? 0 ) : (int) $attachment;
            $title = $att_id ? get_the_title( $att_id ) : '';
            if ( $title ) {
                $attr['alt'] = $title;
            }
        }
        return $attr;
    }, 20, 2 );

    // Locale español-Colombia para Open Graph
    add_filter( 'rank_math/opengraph/locale', function () {
        return 'es_CO';
    });

    // Mejorar descripción de productos automáticamente
    add_filter( 'rank_math/frontend/description', function ( $description ) {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            return $description;
        }
        if ( ! empty( $description ) ) {
            return $description;
        }
        global $product;
        $wc_product = ( $product instanceof \WC_Product ) ? $product : wc_get_product( get_the_ID() );
        if ( ! $wc_product ) {
            return $description;
        }
        $short = trim( wp_strip_all_tags( $wc_product->get_short_description() ) );
        if ( $short ) {
            return wp_trim_words( $short, 30, '…' );
        }
        return sprintf(
            '%s disponible en Racing Bike 1998, Bogotá. Envíos a toda Colombia.',
            get_the_title()
        );
    });

    // Schema: enrichir producto con moneda COP
    add_filter( 'rank_math/snippet/rich_snippet_product_entity', function ( $entity ) {
        if ( ! isset( $entity['offers'] ) ) {
            return $entity;
        }
        if ( is_array( $entity['offers'] ) && ! isset( $entity['offers']['priceCurrency'] ) ) {
            $entity['offers']['priceCurrency'] = 'COP';
        }
        return $entity;
    });

    // Excluir páginas utilitarias del sitemap
    add_filter( 'rank_math/sitemap/entry', function ( $url, $type, $object ) {
        if ( 'post' !== $type || ! is_object( $object ) ) {
            return $url;
        }
        $excluded_slugs = [ 'carrito', 'cart', 'mi-cuenta', 'my-account', 'checkout', 'finalizar-compra', 'wishlist' ];
        if ( isset( $object->post_name ) && in_array( $object->post_name, $excluded_slugs, true ) ) {
            return false;
        }
        return $url;
    }, 10, 3 );

    // robots.txt optimizado
    add_filter( 'rank_math/frontend/robots_txt', function ( $robots ) {
        $custom  = "User-agent: *\n";
        $custom .= "Allow: /\n";
        $custom .= "Disallow: /wp-admin/\n";
        $custom .= "Disallow: /wp-includes/\n";
        $custom .= "Disallow: /cart/\n";
        $custom .= "Disallow: /carrito/\n";
        $custom .= "Disallow: /checkout/\n";
        $custom .= "Disallow: /finalizar-compra/\n";
        $custom .= "Disallow: /mi-cuenta/\n";
        $custom .= "Disallow: /my-account/\n";
        $custom .= "Disallow: /*?add-to-cart=*\n";
        $custom .= "Disallow: /*?orderby=*\n";
        $custom .= "Disallow: /*?filter_*\n";
        $custom .= "\n";
        $custom .= "# Bloquear bots agresivos\n";
        $custom .= "User-agent: AhrefsBot\n";
        $custom .= "Crawl-delay: 10\n";
        $custom .= "\n";
        $custom .= "User-agent: SemrushBot\n";
        $custom .= "Crawl-delay: 10\n";
        $custom .= "\n";
        $custom .= "User-agent: MJ12bot\n";
        $custom .= "Disallow: /\n";
        $custom .= "\n";
        $custom .= "User-agent: DotBot\n";
        $custom .= "Disallow: /\n";
        $custom .= "\n";
        $custom .= "Sitemap: " . home_url( '/sitemap_index.xml' ) . "\n";
        return $custom;
    });

    // Breadcrumbs: insertar Tienda como ancestro en productos
    add_filter( 'rank_math/frontend/breadcrumb/items', function ( $crumbs ) {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            return $crumbs;
        }
        $shop_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
        if ( $shop_page_id > 0 ) {
            $shop_url   = get_permalink( $shop_page_id );
            $shop_title = get_the_title( $shop_page_id );
            $has_shop = false;
            foreach ( $crumbs as $c ) {
                if ( isset( $c[1] ) && $c[1] === $shop_url ) {
                    $has_shop = true;
                    break;
                }
            }
            if ( ! $has_shop && count( $crumbs ) >= 2 ) {
                array_splice( $crumbs, 1, 0, [ [ $shop_title, $shop_url ] ] );
            }
        }
        return $crumbs;
    });
});

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * ADMIN: Aviso de éxito tras activar.
 * ─────────────────────────────────────────────────────────────────────────────
 */
add_action( 'admin_notices', function () {
    if ( ! get_transient( 'rb_seo_config_activated' ) ) {
        return;
    }
    delete_transient( 'rb_seo_config_activated' );
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong>Racing Bike SEO Config:</strong> Rank Math ha sido configurado con ajustes optimizados para Racing Bike 1998. ✅</p>
    </div>
    <?php
});
