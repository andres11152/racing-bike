<?php
/**
 * Plugin Name: Skycode Wishlist
 * Description: Lista de deseos / productos guardados para WooCommerce. Gestiona los datos y lógica de la lista de deseos independiente del tema.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-wishlist
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

const SKYCODE_WISHLIST_META_KEY = '_skycode_wishlist';
const FIVEAM_WISHLIST_META_KEY = '_5am_wishlist'; // Compatibilidad retroactiva
const SKYCODE_WISHLIST_ENDPOINT = 'wishlist';
const FIVEAM_WISHLIST_ENDPOINT = SKYCODE_WISHLIST_ENDPOINT; // Compatibilidad retroactiva

/* -------------------------------------------------------------------------
 | 1. Storage
 |
 | Signed-in shoppers persist to user meta. Guests keep their list in
 | localStorage on the client, so every read here returns an empty list for
 | them — the browser is the source of truth until they sign in, at which
 | point skycode_wishlist_merge() folds it into their account.
 * ---------------------------------------------------------------------- */

/**
 * Product IDs on the current user's wishlist.
 *
 * @return int[]
 */
function skycode_wishlist_get(): array {
    if (! is_user_logged_in()) {
        return [];
    }

    $userId = get_current_user_id();
    $ids = get_user_meta($userId, SKYCODE_WISHLIST_META_KEY, true);

    // Compatibilidad retroactiva con _5am_wishlist
    if (! is_array($ids) || empty($ids)) {
        $legacy = get_user_meta($userId, FIVEAM_WISHLIST_META_KEY, true);
        if (is_array($legacy) && ! empty($legacy)) {
            $ids = $legacy;
            update_user_meta($userId, SKYCODE_WISHLIST_META_KEY, $ids);
        }
    }

    if (! is_array($ids)) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map('absint', $ids))));
}

/**
 * Replace the stored wishlist for the current user.
 *
 * @param int[] $ids
 */
function skycode_wishlist_save(array $ids): void {
    if (! is_user_logged_in()) {
        return;
    }

    $clean = array_values(array_unique(array_filter(array_map('absint', $ids))));

    update_user_meta(get_current_user_id(), SKYCODE_WISHLIST_META_KEY, $clean);
}

/**
 * Whether a product is already saved.
 *
 * Guests always read false here; their state is applied client-side from
 * localStorage once the page loads.
 */
function skycode_wishlist_has(int $productId): bool {
    return in_array(absint($productId), skycode_wishlist_get(), true);
}

/**
 * Add or remove a product, returning the resulting state.
 *
 * @return array{saved: bool, count: int}
 */
function skycode_wishlist_toggle(int $productId): array {
    $productId = absint($productId);
    $ids = skycode_wishlist_get();
    $index = array_search($productId, $ids, true);

    if ($index === false) {
        $ids[] = $productId;
        $saved = true;
    } else {
        unset($ids[$index]);
        $saved = false;
    }

    skycode_wishlist_save($ids);

    return [
        'saved' => $saved,
        'count' => count(skycode_wishlist_get()),
    ];
}

/**
 * Fold a guest's locally stored list into the signed-in user's account.
 *
 * Union rather than replace: signing in should never drop something the
 * shopper had already saved to their account on another device.
 *
 * @param int[] $ids
 * @return int[] The merged list.
 */
function skycode_wishlist_merge(array $ids): array {
    $merged = array_merge(skycode_wishlist_get(), array_map('absint', $ids));

    // Only keep IDs that still resolve to a published product.
    $merged = array_filter($merged, function ($id) {
        $product = function_exists('wc_get_product') ? wc_get_product($id) : null;

        return $product && $product->get_status() === 'publish';
    });

    skycode_wishlist_save($merged);

    return skycode_wishlist_get();
}

/* -------------------------------------------------------------------------
 | 2. AJAX
 |
 | Reuses the theme's `rb_cart_nonce`, which is already published to the
 | browser as window.rbAjax.nonce, so there is only one nonce for the
 | whole storefront rather than a second one to keep in step.
 * ---------------------------------------------------------------------- */

add_action('wp_ajax_rb_toggle_wishlist', 'skycode_wishlist_ajax_toggle');
add_action('wp_ajax_nopriv_rb_toggle_wishlist', 'skycode_wishlist_ajax_toggle');

function skycode_wishlist_ajax_toggle(): void {
    if (! check_ajax_referer('rb_cart_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token'], 403);
    }

    $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if (! $productId || ! function_exists('wc_get_product') || ! wc_get_product($productId)) {
        wp_send_json_error(['message' => 'Product not found'], 404);
    }

    // Guests are handled entirely in the browser; the request still succeeds
    // so the button's optimistic state does not have to be rolled back.
    if (! is_user_logged_in()) {
        wp_send_json_success(['guest' => true]);
    }

    wp_send_json_success(skycode_wishlist_toggle($productId));
}

add_action('wp_ajax_rb_merge_wishlist', 'skycode_wishlist_ajax_merge');

function skycode_wishlist_ajax_merge(): void {
    if (! check_ajax_referer('rb_cart_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token'], 403);
    }

    $raw = isset($_POST['ids']) ? (array) $_POST['ids'] : [];

    wp_send_json_success([
        'items' => skycode_wishlist_merge(array_map('absint', $raw)),
    ]);
}

/* -------------------------------------------------------------------------
 | 3. Frontend configuration
 |
 | Published separately from the theme so the plugin stays self-contained:
 | deactivate it and nothing in the theme breaks.
 * ---------------------------------------------------------------------- */

add_action('wp_head', function () {
    $config = [
        'isLoggedIn' => is_user_logged_in(),
        'items' => skycode_wishlist_get(),
        'endpointUrl' => function_exists('wc_get_account_endpoint_url')
            ? wc_get_account_endpoint_url(SKYCODE_WISHLIST_ENDPOINT)
            : '',
    ];

    echo '<script>window.SkycodeWishlist = window.FiveAmWishlist = ' . wp_json_encode($config) . ';</script>' . "\n";
}, 6);

/* -------------------------------------------------------------------------
 | 4. My Account endpoint
 * ---------------------------------------------------------------------- */

add_action('init', function () {
    add_rewrite_endpoint(SKYCODE_WISHLIST_ENDPOINT, EP_ROOT | EP_PAGES);
});

add_filter('woocommerce_get_query_vars', function ($vars) {
    $vars[SKYCODE_WISHLIST_ENDPOINT] = SKYCODE_WISHLIST_ENDPOINT;

    return $vars;
});

/**
 * Slot the tab in just above "Account details" rather than appending it,
 * which would otherwise land it after "Log out".
 */
add_filter('woocommerce_account_menu_items', function ($items) {
    $position = array_search('edit-account', array_keys($items), true);
    $entry = [SKYCODE_WISHLIST_ENDPOINT => __('My Wishlist', 'skycode-wishlist')];

    if ($position === false) {
        return $items + $entry;
    }

    return array_slice($items, 0, $position, true)
        + $entry
        + array_slice($items, $position, null, true);
});

add_action('woocommerce_account_' . SKYCODE_WISHLIST_ENDPOINT . '_endpoint', function () {
    $ids = skycode_wishlist_get();
    $products = [];

    foreach ($ids as $id) {
        $product = wc_get_product($id);

        if ($product && $product->get_status() === 'publish') {
            $products[] = $product;
        }
    }

    // Rendered through the theme's own Blade view so the grid matches the
    // rest of the storefront. Guarded: with a non-Sage theme active the
    // plugin degrades to a plain list instead of fataling.
    if (function_exists('\Roots\view')) {
        echo \Roots\view('woocommerce.myaccount.wishlist', [
            'products' => $products,
        ])->render();

        return;
    }

    echo '<ul>';
    foreach ($products as $product) {
        printf(
            '<li><a href="%s">%s</a></li>',
            esc_url($product->get_permalink()),
            esc_html($product->get_name())
        );
    }
    echo '</ul>';
});

/* -------------------------------------------------------------------------
 | 5. Activation
 |
 | The endpoint above adds a rewrite rule, which only takes effect once the
 | rules are flushed — doing it here means /my-account/wishlist/ works from
 | the moment the plugin is switched on, with no manual permalink re-save.
 * ---------------------------------------------------------------------- */

register_activation_hook(__FILE__, function () {
    add_rewrite_endpoint(SKYCODE_WISHLIST_ENDPOINT, EP_ROOT | EP_PAGES);
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

/* -------------------------------------------------------------------------
 | 6. Backward compatibility aliases
 * ---------------------------------------------------------------------- */

if (! function_exists('fiveam_wishlist_get')) {
    function fiveam_wishlist_get(): array {
        return skycode_wishlist_get();
    }
}

if (! function_exists('fiveam_wishlist_save')) {
    function fiveam_wishlist_save(array $ids): void {
        skycode_wishlist_save($ids);
    }
}

if (! function_exists('fiveam_wishlist_has')) {
    function fiveam_wishlist_has(int $productId): bool {
        return skycode_wishlist_has($productId);
    }
}

if (! function_exists('fiveam_wishlist_toggle')) {
    function fiveam_wishlist_toggle(int $productId): array {
        return skycode_wishlist_toggle($productId);
    }
}

if (! function_exists('fiveam_wishlist_merge')) {
    function fiveam_wishlist_merge(array $ids): array {
        return skycode_wishlist_merge($ids);
    }
}

if (! function_exists('fiveam_wishlist_ajax_toggle')) {
    function fiveam_wishlist_ajax_toggle(): void {
        skycode_wishlist_ajax_toggle();
    }
}

if (! function_exists('fiveam_wishlist_ajax_merge')) {
    function fiveam_wishlist_ajax_merge(): void {
        skycode_wishlist_ajax_merge();
    }
}
