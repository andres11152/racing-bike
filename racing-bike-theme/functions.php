<?php

@ini_set('memory_limit', '512M');

use App\Providers\ThemeServiceProvider;
use Roots\Acorn\Application;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

Application::configure()
    ->withProviders([
        ThemeServiceProvider::class,
    ])
    ->boot();

/*
|--------------------------------------------------------------------------
| Silence premature textdomain notice from woo-discount-rules
|--------------------------------------------------------------------------
|
| Advanced Woo Discount Rules loads its translations before the 'init'
| hook, which WP 6.7+ flags via _doing_it_wrong(). This is a bug in the
| plugin itself (not fixable from the theme), so we only suppress this
| specific notice rather than hiding doing_it_wrong() warnings in general.
|
*/
add_filter('doing_it_wrong_trigger_error', function ($trigger, $function_name, $message) {
    if ($function_name === '_load_textdomain_just_in_time' && str_contains($message, 'woo-discount-rules')) {
        return false;
    }

    return $trigger;
}, 10, 3);

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['contact', 'contact-form', 'product-brands', 'setup', 'seo', 'filters', 'catalog-filters', 'pwa'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });

/*
|--------------------------------------------------------------------------
| Auto-purge Acorn compiled views on deployment / version bump
|--------------------------------------------------------------------------
|
| WordPress unzips new themes without touching wp-content/cache/acorn/.
| When deploying to a server, this hook ensures compiled Blade views are
| automatically purged so new templates and logic execute immediately.
|
*/
add_action('after_setup_theme', function () {
    $theme = wp_get_theme('racing-bike-theme');
    $version = $theme->exists() ? $theme->get('Version') : '1.0.1';
    if (get_option('rb_deployed_theme_version') !== $version) {
        $view_cache = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR.'/cache/acorn/framework/views' : null;
        if ($view_cache && is_dir($view_cache)) {
            $files = glob($view_cache.'/*.php');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
        update_option('rb_deployed_theme_version', $version);
    }
});
