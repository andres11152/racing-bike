<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_action('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    if (! Vite::isRunningHot()) {
        $dependencies = json_decode(Vite::content('editor.deps.json'));

        foreach ($dependencies as $dependency) {
            if (! wp_script_is($dependency)) {
                wp_enqueue_script($dependency);
            }
        }
    }
    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Disable on-demand block asset loading.
 *
 * @link https://core.trac.wordpress.org/ticket/61965
 */
add_filter('should_load_separate_core_block_assets', '__return_false');

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Navegación principal', 'sage'),
    ]);

    /**
     * WooCommerce: usar las plantillas Blade del theme y habilitar la galería.
     */
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register required WooCommerce frontend scripts for quick-view modals.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    if (class_exists('WooCommerce')) {
        wp_enqueue_script('wc-add-to-cart-variation');
    }
}, 100);

/**
 * Permitir framing y ejecución de scripts para simuladores móviles y vistas responsivas.
 */
add_action('send_headers', function () {
    if (! is_admin()) {
        header_remove('X-Frame-Options');
        header('Access-Control-Allow-Origin: *');
        header("Content-Security-Policy: default-src * 'unsafe-inline' 'unsafe-eval' data: blob:; script-src * 'unsafe-inline' 'unsafe-eval' data: blob:; frame-ancestors *;");
    }
}, 100);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});

/**
 * Register Customizer settings so the client can edit the announcement bar
 * without touching code.
 *
 * @return void
 */
/**
 * Register the homepage slide and announcement post types.
 *
 * Both the hero carousel and top announcement bar are client-editable content.
 * Managed in wp-admin rather than hardcoded.
 *
 * @return void
 */
add_action('init', function () {
    register_post_type('rb_slide', [
        'labels' => [
            'name' => __('Slides', 'sage'),
            'singular_name' => __('Slide', 'sage'),
            'add_new_item' => __('Añadir slide', 'sage'),
            'edit_item' => __('Editar slide', 'sage'),
            'menu_name' => __('Slides', 'sage'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => false,
        'menu_icon' => 'dashicons-images-alt2',
        'menu_position' => 21,
        'supports' => ['title', 'excerpt', 'thumbnail', 'page-attributes'],
        'has_archive' => false,
        'rewrite' => false,
    ]);

    register_post_type('rb_announcement', [
        'labels' => [
            'name' => __('Anuncios', 'sage'),
            'singular_name' => __('Anuncio', 'sage'),
            'add_new_item' => __('Añadir anuncio', 'sage'),
            'edit_item' => __('Editar anuncio', 'sage'),
            'menu_name' => __('Anuncios', 'sage'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => false,
        'menu_icon' => 'dashicons-megaphone',
        'menu_position' => 22,
        'supports' => ['title', 'page-attributes'],
        'has_archive' => false,
        'rewrite' => false,
    ]);
});

/**
 * Custom fields for Slide and Announcement.
 *
 * @return void
 */
add_action('add_meta_boxes', function () {
    add_meta_box('rb_slide_link', __('Enlace del slide', 'sage'), function ($post) {
        wp_nonce_field('rb_slide_link', 'rb_slide_link_nonce');

        $url = get_post_meta($post->ID, '_rb_slide_url', true);
        $cta = get_post_meta($post->ID, '_rb_slide_cta', true);
        $imageMobile = get_post_meta($post->ID, '_rb_slide_image_mobile', true);
        $videoDesktop = get_post_meta($post->ID, '_rb_slide_video_desktop', true);
        $videoMobile = get_post_meta($post->ID, '_rb_slide_video_mobile', true);

        printf(
            '<p><label for="rb_slide_url"><strong>%s</strong></label>
             <input type="url" id="rb_slide_url" name="rb_slide_url" value="%s" class="widefat" placeholder="https://"></p>
             <p><label for="rb_slide_cta"><strong>%s</strong></label>
             <input type="text" id="rb_slide_cta" name="rb_slide_cta" value="%s" class="widefat" placeholder="%s"></p>
             <p><label for="rb_slide_image_mobile"><strong>%s</strong></label>
             <input type="url" id="rb_slide_image_mobile" name="rb_slide_image_mobile" value="%s" class="widefat" placeholder="https://..."></p>
             <p><label for="rb_slide_video_desktop"><strong>%s</strong></label>
             <input type="url" id="rb_slide_video_desktop" name="rb_slide_video_desktop" value="%s" class="widefat" placeholder="https://..."></p>
             <p><label for="rb_slide_video_mobile"><strong>%s</strong></label>
             <input type="url" id="rb_slide_video_mobile" name="rb_slide_video_mobile" value="%s" class="widefat" placeholder="https://..."></p>',
            esc_html__('URL de destino', 'sage'),
            esc_attr($url),
            esc_html__('Texto del botón', 'sage'),
            esc_attr($cta),
            esc_attr__('Ver colección', 'sage'),
            esc_html__('URL Imagen Móvil (Opcional)', 'sage'),
            esc_attr($imageMobile),
            esc_html__('URL Video Desktop (Opcional)', 'sage'),
            esc_attr($videoDesktop),
            esc_html__('URL Video Móvil (Opcional)', 'sage'),
            esc_attr($videoMobile)
        );
    }, 'rb_slide', 'normal', 'high');

    add_meta_box('rb_announcement_fields', __('Opciones del anuncio', 'sage'), function ($post) {
        wp_nonce_field('rb_announcement_fields', 'rb_announcement_fields_nonce');

        $icon = get_post_meta($post->ID, '_rb_announcement_icon', true);
        $url = get_post_meta($post->ID, '_rb_announcement_url', true);

        printf(
            '<p><label for="rb_announcement_icon"><strong>%s</strong></label>
             <input type="text" id="rb_announcement_icon" name="rb_announcement_icon" value="%s" class="widefat" placeholder="truck, star, user, shield-check"></p>
             <p><label for="rb_announcement_url"><strong>%s</strong></label>
             <input type="url" id="rb_announcement_url" name="rb_announcement_url" value="%s" class="widefat" placeholder="https:// (Opcional)"></p>',
            esc_html__('Ícono (Slug en el tema, ej: truck, star, user, shield-check)', 'sage'),
            esc_attr($icon),
            esc_html__('Enlace de destino (Opcional)', 'sage'),
            esc_attr($url)
        );
    }, 'rb_announcement', 'normal', 'high');
});

/**
 * Persist the slide and announcement meta fields.
 *
 * @return void
 */
add_action('save_post', function ($postId) {
    // Save Slide Meta
    if (isset($_POST['rb_slide_link_nonce']) && wp_verify_nonce($_POST['rb_slide_link_nonce'], 'rb_slide_link')) {
        if (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
            if (current_user_can('edit_post', $postId)) {
                update_post_meta($postId, '_rb_slide_url', esc_url_raw($_POST['rb_slide_url'] ?? ''));
                update_post_meta($postId, '_rb_slide_cta', sanitize_text_field($_POST['rb_slide_cta'] ?? ''));
                update_post_meta($postId, '_rb_slide_image_mobile', esc_url_raw($_POST['rb_slide_image_mobile'] ?? ''));
                update_post_meta($postId, '_rb_slide_video_desktop', esc_url_raw($_POST['rb_slide_video_desktop'] ?? ''));
                update_post_meta($postId, '_rb_slide_video_mobile', esc_url_raw($_POST['rb_slide_video_mobile'] ?? ''));
            }
        }
    }

    // Save Announcement Meta
    if (isset($_POST['rb_announcement_fields_nonce']) && wp_verify_nonce($_POST['rb_announcement_fields_nonce'], 'rb_announcement_fields')) {
        if (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
            if (current_user_can('edit_post', $postId)) {
                update_post_meta($postId, '_rb_announcement_icon', sanitize_text_field($_POST['rb_announcement_icon'] ?? ''));
                update_post_meta($postId, '_rb_announcement_url', esc_url_raw($_POST['rb_announcement_url'] ?? ''));
            }
        }
    }
});

/**
 * Drop WooCommerce's default stylesheets.
 *
 * They ship an opinionated look (yellow sale badges, grey buttons, their own
 * grid) that fights the theme at every turn. The storefront is styled entirely
 * with Tailwind instead — see `resources/css/woocommerce.css` for the handful
 * of WooCommerce-generated elements that still need rules.
 *
 * @return array
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Drop WordPress's default global-styles stylesheet on the front-end.
 *
 * This is a classic theme with a 921-byte theme.json (just editor a11y
 * defaults) and Tailwind handling every visual decision — but WordPress still
 * inlines its FULL default preset stylesheet (every core color, spacing and
 * aspect-ratio custom property, block-by-block) into every single page load:
 * 108 KB, uncacheable because it's inline, on every product, category and
 * template. The handful of blocks actually in use (paragraph, the WooCommerce
 * account shortcode, nav links) render fine without it — they don't touch
 * these preset custom properties.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
}, 1);

/**
 * Remove WooCommerce's default loop decorations.
 *
 * The product grid is rendered by the `product-card` Blade component, so the
 * hook-driven markup would only duplicate it.
 *
 * @return void
 */
add_action('init', function () {
    remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
    remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

    // La miga de pan la dibuja el componente `breadcrumbs`.
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
});

/**
 * Show 12 products per page — a clean 2/3/4-column grid at every breakpoint.
 *
 * @return int
 */
add_filter('loop_shop_per_page', fn () => 12, 20);

/**
 * Register missing Smash Balloon dependency to prevent Acorn ErrorException crash.
 */
add_action('init', function () {
    $register_dependency = function () {
        if (! wp_style_is('sbi-tokens-local', 'registered')) {
            wp_register_style('sbi-tokens-local', false);
        }
    };
    add_action('wp_enqueue_scripts', $register_dependency, 1);
    add_action('admin_enqueue_scripts', $register_dependency, 1);
    add_action('enqueue_block_assets', $register_dependency, 1);
});

/**
 * Patch for WooCommerce Mercado Pago PHP 8.4 / Acorn compatibility.
 * Ensures that expected POST keys are initialized during AJAX updates.
 */
add_action('admin_init', function () {
    if (wp_doing_ajax() && isset($_POST['action']) && $_POST['action'] === 'mp_update_store_info') {
        if (! isset($_POST['store_debug_mode'])) {
            $_POST['store_debug_mode'] = 'no';
        }
    }
});
