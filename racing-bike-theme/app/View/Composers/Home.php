<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Post;
use WP_Query;

class Home extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'front-page',
    ];

    /**
     * Data passed to the view before rendering.
     *
     * Returned through `with()` rather than as public methods on purpose:
     * Acorn wraps auto-exposed public methods in `InvokableComponentVariable`,
     * and passing one of those into a component attribute (`:slides="$slides"`)
     * makes Blade escape it, which fatals on arrays. `with()` hands the view
     * plain values instead.
     *
     * @return array
     */
    protected function with()
    {
        return [
            'slides' => $this->slides(),
            'categories' => $this->categories(),
            'featuredProducts' => $this->featuredProducts(),
            'saleProducts' => $this->saleProducts(),
            'photoReviews' => $this->photoReviews(),
            'shopUrl' => $this->shopUrl(),
            'faqsUrl' => $this->faqsUrl(),
        ];
    }

    protected function slides(): array
    {
        $query = new WP_Query([
            'post_type' => 'rb_slide',
            'posts_per_page' => 12,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
        ]);

        return collect($query->posts)
            ->map(fn (WP_Post $slide) => [
                'id' => $slide->ID,
                'title' => get_the_title($slide),
                'eyebrow' => $slide->post_excerpt,
                'url' => get_post_meta($slide->ID, '_rb_slide_url', true),
                'cta' => get_post_meta($slide->ID, '_rb_slide_cta', true),
                'image' => get_the_post_thumbnail_url($slide, 'full') ?: null,
                'image_desktop' => get_the_post_thumbnail_url($slide, 'full') ?: null,
                'image_mobile' => get_post_meta($slide->ID, '_rb_slide_image_mobile', true) ?: get_the_post_thumbnail_url($slide, 'full') ?: null,
                'video_desktop' => get_post_meta($slide->ID, '_rb_slide_video_desktop', true) ?: null,
                'video_mobile' => get_post_meta($slide->ID, '_rb_slide_video_mobile', true) ?: null,
                'alt' => get_post_meta(get_post_thumbnail_id($slide), '_wp_attachment_image_alt', true) ?: '',
            ])
            ->all();
    }

    /**
     * Top-level product categories, shown as the navigation strip.
     *
     * @return array<int, array{name: string, url: string, count: int, image: ?string}>
     */
    protected function categories(): array
    {
        if (! function_exists('wc_get_page_id')) {
            return [];
        }

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'hide_empty' => true,
            'exclude' => [get_option('default_product_cat')],
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        return collect($terms)
            ->map(function ($term) {
                $thumbnailId = get_term_meta($term->term_id, 'thumbnail_id', true);
                $link = get_term_link($term);

                return [
                    'name' => $term->name,
                    'url' => is_wp_error($link) ? '' : $link,
                    'count' => (int) $term->count,
                    'image' => $thumbnailId ? wp_get_attachment_image_url((int) $thumbnailId, 'large') : null,
                ];
            })
            ->all();
    }

    /**
     * Featured products, falling back to the most recent when none are flagged.
     *
     * @return array<int, \WC_Product>
     */
    protected function featuredProducts(): array
    {
        if (! function_exists('wc_get_products')) {
            return [];
        }

        $products = wc_get_products([
            'featured' => true,
            'limit' => 4,
            'status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (! $products) {
            $products = wc_get_products([
                'limit' => 4,
                'status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
        }

        return $products;
    }

    /**
     * Products currently on sale, for the offers band.
     *
     * @return array<int, \WC_Product>
     */
    protected function saleProducts(): array
    {
        if (! function_exists('wc_get_product_ids_on_sale')) {
            return [];
        }

        $ids = wc_get_product_ids_on_sale();

        if (! $ids) {
            return [];
        }

        // La lista mezcla productos y variaciones. Una variación en oferta debe
        // mostrarse como su producto padre, o las bicicletas rebajadas se pierden.
        $parentIds = collect($ids)
            ->map(function ($id) {
                $product = wc_get_product($id);

                if (! $product) {
                    return null;
                }

                return $product->get_parent_id() ?: $product->get_id();
            })
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();

        if (! $parentIds) {
            return [];
        }

        return wc_get_products([
            'include' => $parentIds,
            'limit' => 4,
            'status' => 'publish',
        ]);
    }

    /**
     * Reseñas con foto de todo el catálogo, para el muro de prueba social.
     * Viene del plugin racing-bike-reviews — puede no estar activo.
     *
     * @return array<int, array>
     */
    protected function photoReviews(): array
    {
        return function_exists('rb_reviews_get_photo_wall') ? rb_reviews_get_photo_wall(10) : [];
    }

    /**
     * Permalink of the shop page, used by the section CTAs.
     */
    protected function shopUrl(): string
    {
        return function_exists('wc_get_page_id')
            ? (get_permalink(wc_get_page_id('shop')) ?: home_url('/'))
            : home_url('/');
    }

    /**
     * URL of the FAQs page.
     */
    protected function faqsUrl(): string
    {
        $pages = get_pages([
            'meta_key' => '_wp_page_template',
            'meta_value' => 'template-faqs.blade.php',
            'number' => 1,
        ]);

        return ! empty($pages) ? get_permalink($pages[0]->ID) : home_url('/preguntas-frecuentes');
    }
}
