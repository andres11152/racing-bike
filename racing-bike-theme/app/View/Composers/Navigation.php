<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Post;

class Navigation extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'sections.header',
        'components.mega-menu',
        'components.mobile-nav',
    ];

    /**
     * Data passed to the view before rendering.
     *
     * Uses `with()` so the menu arrives as a plain array. Auto-exposed public
     * methods are wrapped in `InvokableComponentVariable`, which Blade escapes
     * when handed to a component attribute — and escaping an array fatals.
     *
     * @return array
     */
    protected function with()
    {
        return [
            'primaryMenu' => $this->primaryMenu(),
        ];
    }

    /**
     * The primary navigation as a nested tree.
     *
     * WordPress returns menu items as a flat list with `menu_item_parent`
     * pointers. The mega menu needs them grouped by parent, so the tree is
     * assembled once here instead of fighting a custom Walker in the view.
     *
     * @return array<int, array{id: int, title: string, url: string, current: bool, image: ?string, children: array}>
     */
    protected function primaryMenu(): array
    {
        if (! has_nav_menu('primary_navigation')) {
            return [];
        }

        $locations = get_nav_menu_locations();
        $items = wp_get_nav_menu_items($locations['primary_navigation'] ?? 0);

        if (! $items) {
            return [];
        }

        $byParent = [];

        foreach ($items as $item) {
            $byParent[(int) $item->menu_item_parent][] = $item;
        }

        return $this->buildBranch($byParent, 0);
    }

    /**
     * Recursively assemble one level of the menu tree.
     *
     * @param  array<int, array<int, WP_Post>>  $byParent
     */
    protected function buildBranch(array $byParent, int $parentId): array
    {
        return collect($byParent[$parentId] ?? [])
            ->map(function (WP_Post $item) use ($byParent) {
                $url = $item->url;
                if ($url && is_string($url)) {
                    // Corregir enlaces residuales a /shop/ reemplazándolos por /tienda/
                    $url = preg_replace('#/shop(/|\?|$)#', '/tienda$1', $url);
                }

                $title = str_ireplace(['Bicicletas y Frames', 'Frames'], ['Bicicletas y Marcos', 'Marcos'], $item->title);

                return [
                    'id' => (int) $item->ID,
                    'title' => $title,
                    'url' => $url,
                    'current' => $this->isCurrent($item),
                    'image' => $this->thumbnail($item),
                    'children' => $this->buildBranch($byParent, (int) $item->ID),
                ];
            })
            ->all();
    }

    /**
     * Whether the item points at whatever is currently being viewed.
     */
    protected function isCurrent(WP_Post $item): bool
    {
        return in_array('current-menu-item', $item->classes ?? [], true)
            || in_array('current-menu-ancestor', $item->classes ?? [], true);
    }

    /**
     * Category thumbnail, used as the featured image inside the mega panel.
     */
    protected function thumbnail(WP_Post $item): ?string
    {
        if ($item->object !== 'product_cat') {
            return null;
        }

        $thumbnailId = get_term_meta((int) $item->object_id, 'thumbnail_id', true);

        if (! $thumbnailId) {
            return null;
        }

        return wp_get_attachment_image_url((int) $thumbnailId, 'medium_large') ?: null;
    }
}
