<?php

/**
 * Conteos contextuales para el sidebar de filtros del catálogo.
 *
 * `$term->count` (lo que devuelve get_terms()) es un conteo GLOBAL de todo
 * el catálogo, no del resultado que el visitante está viendo. Dentro de
 * /bicicletas/ el filtro mostraba "Shimano (7)" — son grupos de
 * transmisión, cero de esas 7 son bicicletas — y el clic siempre daba una
 * tienda vacía. Este archivo calcula, para cada taxonomía de filtro, cuántos
 * productos del contexto actual (categoría/etiqueta si aplica, más
 * cualquier otro filtro ya activo) tendría cada término SI se marcara.
 *
 * WooCommerce 11 ya trae una solución nativa para esto
 * (Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer), pero
 * depende de la "tabla de búsqueda de atributos de producto" — una
 * característica que hay que habilitar y regenerar por separado (Ajustes
 * de WooCommerce > Avanzado > Funciones) y que en este sitio está
 * deshabilitada; probarla devolvió conteos que no correspondían a ningún
 * término real. Mientras esa tabla no se habilite y regenere, se calcula
 * aquí con una WP_Query por término.
 *
 * Con el tamaño actual del catálogo (~60-70 productos, taxonomías de
 * filtro de hasta ~30 términos) esto son unas pocas decenas de consultas
 * por carga de página, triviales sobre índices. Si el catálogo crece a
 * cientos de productos, la solución correcta deja de ser esto y pasa a
 * ser habilitar + regenerar esa tabla de WooCommerce en vez de escalar
 * este bucle.
 */

namespace App;

/**
 * Devuelve [slug_de_término => cantidad_de_productos] para una taxonomía
 * de filtro, contando solo dentro del contexto actual: la categoría o
 * etiqueta que se esté viendo (si aplica) más los filtros ya activos en
 * las DEMÁS taxonomías de filtro (en AND entre taxonomías, igual que hace
 * WooCommerce). El propio término evaluado no arrastra la selección
 * previa de su misma taxonomía: la pregunta que responde cada número es
 * "¿cuántos productos vería si marco (también) esta opción?".
 */
function rb_layered_nav_term_counts(string $renderingTaxonomy): array
{
    static $cache = [];

    if (array_key_exists($renderingTaxonomy, $cache)) {
        return $cache[$renderingTaxonomy];
    }

    $terms = get_terms(['taxonomy' => $renderingTaxonomy, 'hide_empty' => true, 'fields' => 'id=>slug']);

    if (is_wp_error($terms) || empty($terms) || ! function_exists('WC')) {
        return $cache[$renderingTaxonomy] = [];
    }

    $baseTaxQuery = [
        ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['exclude-from-catalog'], 'operator' => 'NOT IN'],
    ];

    $queriedObject = get_queried_object();
    if ($queriedObject instanceof \WP_Term && in_array($queriedObject->taxonomy, ['product_cat', 'product_tag'], true)) {
        $baseTaxQuery[] = [
            'taxonomy' => $queriedObject->taxonomy,
            'field' => 'term_id',
            'terms' => [$queriedObject->term_id],
        ];
    }

    if (class_exists('WC_Query')) {
        foreach (\WC_Query::get_layered_nav_chosen_attributes() as $taxonomy => $data) {
            if ($taxonomy === $renderingTaxonomy) {
                continue; // esta taxonomía se resuelve término a término abajo
            }

            $baseTaxQuery[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $data['terms'],
                'operator' => 'and' === $data['query_type'] ? 'AND' : 'IN',
            ];
        }
    }

    $counts = [];

    foreach ($terms as $termId => $slug) {
        $taxQuery = $baseTaxQuery;
        $taxQuery[] = ['taxonomy' => $renderingTaxonomy, 'field' => 'term_id', 'terms' => [(int) $termId]];

        $query = new \WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'tax_query' => $taxQuery,
            'suppress_filters' => false,
        ]);

        $counts[$slug] = (int) $query->found_posts;
    }

    return $cache[$renderingTaxonomy] = $counts;
}

/**
 * Orden legible para pa_talla en el sidebar de filtros.
 *
 * orderby=menu_order (el default de WooCommerce) mezclaba tallas
 * numéricas de cuadro con tallas en letra según el orden en que cada una
 * se creó, no según ningún criterio de lectura:
 * 44, 47, 49...62, ml, xl, xs, xxl, xxs, 13, 15, 17, 19, l, m, s.
 * Aquí se reordena a: numéricas ascendente, luego letras XXS→XXL, luego
 * cualquier término que no encaje en ninguno de los dos (alfabético, al
 * final) — sin tocar el orden real en la base de datos, que puede seguir
 * usando otras vistas.
 */
function rb_sort_size_terms(array $terms): array
{
    $letterOrder = ['xxs' => 0, 'xs' => 1, 's' => 2, 'm' => 3, 'ml' => 4, 'l' => 5, 'xl' => 6, 'xxl' => 7];

    usort($terms, function (\WP_Term $a, \WP_Term $b) use ($letterOrder) {
        $rank = static function (\WP_Term $term) use ($letterOrder) {
            $slug = strtolower($term->slug);

            if (is_numeric($slug)) {
                return [0, (float) $slug, $slug];
            }

            if (isset($letterOrder[$slug])) {
                return [1, $letterOrder[$slug], $slug];
            }

            return [2, 0, $slug];
        };

        return $rank($a) <=> $rank($b);
    });

    return $terms;
}
