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
 * Taxonomías de atributo que arma el sidebar de filtros, en un solo
 * lugar: filter-sidebar.blade.php (las opciones) y archive-product.blade.php
 * (las chips de filtros activos) necesitan exactamente la misma lista, y
 * tenerla duplicada en dos archivos es como se desincroniza sin que nadie
 * lo note hasta que un filtro "desaparecido" sigue generando una chip.
 *
 * pa_grupo y pa_talla-cuadro no están: existen como vocabulario en
 * WooCommerce > Atributos pero ningún producto los tiene asignados. La
 * talla real vive en pa_talla (antes repartida entre esa taxonomía y un
 * atributo de texto libre "talla"; ver scripts/migrate-custom-attributes.php).
 *
 * pa_disciplina y pa_material tampoco: auditoría de catálogo del
 * 2026-09-17 mostró que solo 1 de 63 productos publicados tiene cada uno
 * asignado (2%). Mostrar un filtro que vacía la tienda en el 98% de sus
 * opciones es peor que no mostrarlo. Se reactivan cuando el catálogo
 * tenga cobertura real (ver scripts/audit-catalog.php para medirla).
 *
 * El filtro de Color usa pa_color-familia (9 familias: Azul, Rojo...), no
 * pa_color directo — pa_color tiene ~76 tonos exactos de fabricante
 * ("Halo Silver - Tanzanite (Gloss)"), correctos para la ficha de
 * producto pero inservibles como filtro. Ver
 * scripts/migrate-color-families.php para el mapeo.
 */
function rb_filter_taxonomies(): array
{
    return [
        'pa_marca' => __('Marca', 'sage'),
        'pa_talla' => __('Talla', 'sage'),
        'pa_longitud-de-biela' => __('Longitud de biela', 'sage'),
        'pa_color-familia' => __('Color', 'sage'),
    ];
}

/**
 * Devuelve [slug_de_término => cantidad_de_productos] para una taxonomía
 * de filtro, contando solo dentro del contexto actual: la categoría o
 * etiqueta que se esté viendo (si aplica) más los filtros ya activos en
 * las DEMÁS taxonomías de filtro (en AND entre taxonomías, igual que hace
 * WooCommerce). El propio término evaluado no arrastra la selección
 * previa de su misma taxonomía: la pregunta que responde cada número es
 * "¿cuántos productos vería si marco (también) esta opción?".
 *
 * Corregido el 2026-09-17 (auditoría en vivo tras quitar el modo
 * mantenimiento): la primera versión hacía una WP_Query POR TÉRMINO
 * (~46 consultas repartidas en las 3 taxonomías del sidebar), y cada una
 * se volvía más pesada con cada filtro activo — medido en producción,
 * filtrar por 2 atributos a la vez tardaba 6-7 segundos. Ahora son 2
 * consultas por taxonomía en total: una para resolver el contexto actual
 * a una lista de IDs de producto (igual de compleja que antes, pero UNA
 * sola vez), y una segunda que cuenta por término con un solo GROUP BY
 * sobre esa lista, en vez de repetir la primera 8-29 veces.
 */
function rb_layered_nav_term_counts(string $renderingTaxonomy): array
{
    global $wpdb;

    static $cache = [];

    $queriedObject = get_queried_object();
    $contextId = ($queriedObject instanceof \WP_Term) ? ($queriedObject->taxonomy . '_' . $queriedObject->term_id) : 'archive';
    $getHash = md5(serialize($_GET));
    $cacheKey = $renderingTaxonomy . '_' . $contextId . '_' . $getHash;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $terms = get_terms(['taxonomy' => $renderingTaxonomy, 'hide_empty' => true, 'fields' => 'id=>slug']);

    if (is_wp_error($terms) || empty($terms) || ! function_exists('WC')) {
        return $cache[$cacheKey] = [];
    }

    $baseTaxQuery = [
        ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['exclude-from-catalog'], 'operator' => 'NOT IN'],
    ];

    if ($queriedObject instanceof \WP_Term && in_array($queriedObject->taxonomy, ['product_cat', 'product_tag'], true)) {
        $baseTaxQuery[] = [
            'taxonomy' => $queriedObject->taxonomy,
            'field' => 'term_id',
            'terms' => [$queriedObject->term_id],
            'include_children' => true,
        ];
    }

    if (class_exists('WC_Query')) {
        foreach (\WC_Query::get_layered_nav_chosen_attributes() as $taxonomy => $data) {
            if ($taxonomy === $renderingTaxonomy) {
                continue; // esta taxonomía se resuelve por término abajo, no aquí
            }

            $baseTaxQuery[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $data['terms'],
                'operator' => 'and' === $data['query_type'] ? 'AND' : 'IN',
            ];
        }
    }

    // Paso 1: resolver el contexto actual (categoría + otros filtros) a
    // una lista de IDs — una sola vez, sin filtrar todavía por
    // $renderingTaxonomy.
    $baseQuery = new \WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'tax_query' => $baseTaxQuery,
        'suppress_filters' => false,
    ]);

    $productIds = $baseQuery->posts;
    $counts = array_fill_keys(array_values($terms), 0);

    if (empty($productIds)) {
        return $cache[$cacheKey] = $counts;
    }

    // Paso 2: un solo GROUP BY para contar cuántos de esos IDs tiene cada
    // término de $renderingTaxonomy, en vez de una consulta por término.
    $placeholders = implode(',', array_fill(0, count($productIds), '%d'));

    $sql = "
        SELECT tt.term_id, COUNT(DISTINCT tr.object_id) AS total
        FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
        WHERE tt.taxonomy = %s
          AND tr.object_id IN ({$placeholders})
        GROUP BY tt.term_id
    ";

    $rows = $wpdb->get_results(
        $wpdb->prepare($sql, array_merge([$renderingTaxonomy], $productIds))
    );

    foreach ($rows as $row) {
        $termId = (int) $row->term_id;

        if (isset($terms[$termId])) {
            $counts[$terms[$termId]] = (int) $row->total;
        }
    }

    return $cache[$cacheKey] = $counts;
}

/**
 * Orden ascendente para longitudes de biela (ej: 165 mm, 170 mm, 172.5 mm, 175 mm).
 */
function rb_crank_length_rank(mixed $val): float
{
    if (is_object($val) && isset($val->name)) {
        $str = $val->name;
    } elseif (is_object($val) && isset($val->slug)) {
        $str = $val->slug;
    } elseif (is_array($val) && isset($val['name'])) {
        $str = $val['name'];
    } else {
        $str = (string) $val;
    }

    if (preg_match('/(\d+(?:\.\d+)?)/', $str, $matches)) {
        return (float) $matches[1];
    }

    return 0.0;
}

function rb_sort_crank_length_terms(array $terms): array
{
    usort($terms, fn ($a, $b) => rb_crank_length_rank($a) <=> rb_crank_length_rank($b));
    return $terms;
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
function rb_size_rank(mixed $val): array
{
    if (is_object($val) && isset($val->slug)) {
        $str = $val->slug;
    } elseif (is_array($val) && isset($val['label'])) {
        $str = $val['label'];
    } elseif (is_array($val) && isset($val['slug'])) {
        $str = $val['slug'];
    } else {
        $str = (string) $val;
    }

    $slug = strtolower(trim($str));
    $slug = str_replace([' ', '_', '/'], '-', $slug);

    if (is_numeric($slug)) {
        return [0, (float) $slug, $slug];
    }

    if (preg_match('/^(\d+(?:\.\d+)?)/', $slug, $matches)) {
        return [0, (float) $matches[1], $slug];
    }

    static $letterOrder = [
        '3xs' => 0,
        'xxs' => 1,
        '2xs' => 1,
        'xs' => 2,
        's' => 3,
        's-m' => 4,
        'sm' => 4,
        'm' => 5,
        'ml' => 6,
        'm-l' => 6,
        'l' => 7,
        'l-xl' => 8,
        'lxl' => 8,
        'xl' => 9,
        'xxl' => 10,
        '2xl' => 10,
        'xxxl' => 11,
        '3xl' => 11,
        '4xl' => 12,
        'u' => 90,
        'tu' => 90,
        'unica' => 90,
        'one-size' => 90,
    ];

    if (isset($letterOrder[$slug])) {
        return [1, $letterOrder[$slug], $slug];
    }

    return [2, 0, $slug];
}

function rb_compare_sizes(mixed $a, mixed $b): int
{
    return rb_size_rank($a) <=> rb_size_rank($b);
}

function rb_sort_size_terms(array $terms): array
{
    usort($terms, __NAMESPACE__ . '\\rb_compare_sizes');
    return $terms;
}

/**
 * En la ficha de producto (PDP) y donde se obtengan los términos de variación,
 * garantiza que las tallas se listen de la más pequeña a la más grande (ej: S, M, L, XL).
 */
add_filter('woocommerce_get_product_terms', function ($terms, $product_id, $taxonomy, $args) {
    if (is_array($terms) && (stripos($taxonomy, 'talla') !== false || stripos($taxonomy, 'size') !== false)) {
        usort($terms, __NAMESPACE__ . '\\rb_compare_sizes');
    }
    return $terms;
}, 10, 4);

add_filter('woocommerce_dropdown_variation_attribute_options_args', function ($args) {
    $attr = $args['attribute'] ?? '';
    if ((stripos($attr, 'talla') !== false || stripos($attr, 'size') !== false) && ! empty($args['options']) && is_array($args['options'])) {
        usort($args['options'], __NAMESPACE__ . '\\rb_compare_sizes');
    }
    return $args;
}, 10, 1);

/**
 * Precios reales de todos los productos del contexto actual (categoría/
 * etiqueta si aplica), ordenados — base compartida por
 * rb_catalog_price_bounds(), rb_catalog_price_histogram() y
 * rb_catalog_price_presets() para no repetir el mismo JOIN/WHERE en tres
 * consultas separadas. Ignora los productos a $0 — hoy son datos rotos
 * (ver scripts/unpublish-broken-products.php), no un precio real de
 * catálogo, y descuadrarían tanto el rango como los tramos.
 */
function rb_catalog_prices(): array
{
    global $wpdb;

    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $join = "INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_price'";
    $where = "p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_value > 0";

    $queriedObject = get_queried_object();
    if ($queriedObject instanceof \WP_Term && in_array($queriedObject->taxonomy, ['product_cat', 'product_tag'], true)) {
        $join .= " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
                   INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.term_id = " . (int) $queriedObject->term_id;
    }

    $prices = $wpdb->get_col("SELECT pm.meta_value + 0 FROM {$wpdb->posts} p {$join} WHERE {$where} ORDER BY pm.meta_value + 0 ASC");

    return $cache = array_map('floatval', $prices);
}

/**
 * Rango de precio ($min, $max) del contexto actual, para dimensionar el
 * slider del filtro de precio.
 */
function rb_catalog_price_bounds(): array
{
    $prices = rb_catalog_prices();

    if (empty($prices)) {
        return ['min' => 0, 'max' => 0];
    }

    return ['min' => $prices[0], 'max' => $prices[count($prices) - 1]];
}

/**
 * Histograma del precio en $buckets tramos iguales, para dibujar la
 * distribución de productos detrás del slider (como Amazon o Airbnb):
 * ver de un vistazo dónde se concentra el catálogo antes de mover el
 * control, en vez de adivinar arrastrando a ciegas.
 */
function rb_catalog_price_histogram(int $buckets = 12): array
{
    $prices = rb_catalog_prices();

    if (count($prices) < 2) {
        return array_fill(0, $buckets, 0);
    }

    $min = $prices[0];
    $max = $prices[count($prices) - 1];
    $range = $max - $min;

    $counts = array_fill(0, $buckets, 0);

    if ($range <= 0) {
        $counts[0] = count($prices);

        return $counts;
    }

    foreach ($prices as $price) {
        $bucket = (int) floor((($price - $min) / $range) * $buckets);
        $bucket = min($bucket, $buckets - 1); // el precio máximo cae justo en el borde superior
        $counts[$bucket]++;
    }

    $peak = max($counts);

    // Normalizado a 0-100 (altura en %) para que la plantilla no tenga que
    // calcular nada, solo pintar barras.
    return $peak > 0 ? array_map(fn ($c) => (int) round(($c / $peak) * 100), $counts) : $counts;
}

/**
 * 3 tramos de precio para clics rápidos ("Hasta $X", "$X - $Y", "Desde
 * $Y"), calculados sobre los precios reales del catálogo (terciles), no
 * bandas fijas inventadas — con un catálogo que va de bidones a
 * bicicletas de $13M, un corte fijo cada $2M dejaría casi todo en el
 * primer tramo. Redondeado a la decena de mil más cercana para que se
 * lea como un precio real, no como un cálculo.
 */
function rb_catalog_price_presets(): array
{
    $prices = rb_catalog_prices();

    if (count($prices) < 3) {
        return [];
    }

    $round = static fn (float $n): int => (int) (round($n / 10000) * 10000);

    $p33 = $round($prices[(int) floor(count($prices) * 0.33)]);
    $p66 = $round($prices[(int) floor(count($prices) * 0.66)]);
    $min = $round($prices[0]);
    $max = $round($prices[count($prices) - 1]);

    return [
        ['min' => $min, 'max' => $p33, 'label' => sprintf(__('Hasta %s', 'sage'), rb_format_cop($p33))],
        ['min' => $p33, 'max' => $p66, 'label' => sprintf('%s – %s', rb_format_cop($p33), rb_format_cop($p66))],
        ['min' => $p66, 'max' => $max, 'label' => sprintf(__('Desde %s', 'sage'), rb_format_cop($p66))],
    ];
}

/** "$1.500.000" sin decimales, para las etiquetas de precio del sidebar. */
function rb_format_cop(float $amount): string
{
    return '$' . number_format_i18n($amount, 0);
}

/**
 * Chips de filtros activos (Marca: GW ✕, Talla: M ✕, Precio: $0–$500.000 ✕)
 * para quitar uno a la vez sin pasar por "Limpiar todo". Antes la única
 * forma de deshacer una sola selección era abrir el sidebar de nuevo y
 * desmarcarla ahí, o borrar todos los filtros de un golpe.
 */
function rb_active_filter_chips(array $taxonomies): array
{
    $chips = [];
    $currentUrl = preg_replace('#/page/\d+/?$#', '/', strtok($_SERVER['REQUEST_URI'] ?? '', '?'));

    foreach ($taxonomies as $taxonomy => $label) {
        $paramKey = 'filter_' . str_replace('pa_', '', $taxonomy);
        $queryTypeKey = 'query_type_' . str_replace('pa_', '', $taxonomy);

        if (empty($_GET[$paramKey])) {
            continue;
        }

        $slugs = array_filter(explode(',', wp_unslash($_GET[$paramKey])));

        foreach ($slugs as $slug) {
            $term = get_term_by('slug', sanitize_title($slug), $taxonomy);

            if (! $term) {
                continue;
            }

            $remaining = array_values(array_diff($slugs, [$slug]));
            $queryParams = $_GET;
            unset($queryParams['paged']);

            if ($remaining) {
                $queryParams[$paramKey] = implode(',', $remaining);
                $queryParams[$queryTypeKey] = 'or';
            } else {
                unset($queryParams[$paramKey], $queryParams[$queryTypeKey]);
            }

            $chips[] = [
                'label' => $label . ': ' . $term->name,
                'url' => $currentUrl . (! empty($queryParams) ? '?' . http_build_query($queryParams) : ''),
            ];
        }
    }

    if (! empty($_GET['min_price']) || ! empty($_GET['max_price'])) {
        $queryParams = $_GET;
        unset($queryParams['paged'], $queryParams['min_price'], $queryParams['max_price']);

        $min = $_GET['min_price'] ?? null;
        $max = $_GET['max_price'] ?? null;

        // html_entity_decode además de strip_tags: wc_price() devuelve
        // "&#36;&nbsp;1.000.000" — sin decodificar, Blade escapa el "&" al
        // imprimir la chip y el visitante ve "&#36;" literal en vez de "$".
        $formatPrice = static fn ($amount) => html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES);

        if ($min && $max) {
            $priceLabel = sprintf(__('Precio: %s – %s', 'sage'), $formatPrice($min), $formatPrice($max));
        } elseif ($min) {
            $priceLabel = sprintf(__('Precio: desde %s', 'sage'), $formatPrice($min));
        } else {
            $priceLabel = sprintf(__('Precio: hasta %s', 'sage'), $formatPrice($max));
        }

        $chips[] = [
            'label' => $priceLabel,
            'url' => $currentUrl . (! empty($queryParams) ? '?' . http_build_query($queryParams) : ''),
        ];
    }

    return $chips;
}

/**
 * Detecta inteligentemente la disciplina de una bicicleta o marco:
 * 'mtb' (Montaña), 'road' (Ruta) o 'gravel' (Gravel).
 *
 * @param int|\WC_Product $product Objeto producto o ID
 * @return string|null 'mtb'|'road'|'gravel' o null si no es bicicleta/marco
 */
function rb_get_product_discipline(int|\WC_Product $product): ?string
{
    if (is_numeric($product)) {
        $product = wc_get_product($product);
    }

    if (! $product instanceof \WC_Product) {
        return null;
    }

    $productId = $product->get_id();

    // 1. Categorías de producto
    $terms = wc_get_product_terms($productId, 'product_cat', ['fields' => 'slugs']);
    if (! empty($terms) && ! is_wp_error($terms)) {
        foreach ($terms as $slug) {
            $slug = strtolower($slug);
            if (str_contains($slug, 'gravel')) {
                return 'gravel';
            }
            if ($slug === 'mtb' || str_contains($slug, 'mtb') || str_contains($slug, 'montana')) {
                return 'mtb';
            }
            if ($slug === 'ruta' || str_contains($slug, 'ruta') || str_contains($slug, 'road')) {
                return 'road';
            }
        }
    }

    // 2. Taxonomía pa_disciplina
    if (taxonomy_exists('pa_disciplina')) {
        $discTerms = wc_get_product_terms($productId, 'pa_disciplina', ['fields' => 'slugs']);
        if (! empty($discTerms) && ! is_wp_error($discTerms)) {
            foreach ($discTerms as $slug) {
                $slug = strtolower($slug);
                if (str_contains($slug, 'gravel')) {
                    return 'gravel';
                }
                if (str_contains($slug, 'mtb') || str_contains($slug, 'montana')) {
                    return 'mtb';
                }
                if (str_contains($slug, 'ruta') || str_contains($slug, 'road')) {
                    return 'road';
                }
            }
        }
    }

    // 3. Heurística por nombre de producto
    $name = strtolower($product->get_name());
    if (preg_match('/\b(gravel)\b/i', $name)) {
        return 'gravel';
    }
    if (preg_match('/\b(mtb|mountain|monta[nñ]a|trail|cross[ -]?country|xc)\b/i', $name)) {
        return 'mtb';
    }
    if (preg_match('/\b(ruta|road|carretera|aero)\b/i', $name)) {
        return 'road';
    }

    // 4. Heurística por tallas de variaciones (pulgadas vs centímetros)
    if ($product->is_type('variable')) {
        $attributes = $product->get_variation_attributes();
        $sizeOptions = $attributes['pa_talla'] ?? $attributes['talla'] ?? [];
        $numericSizes = [];

        foreach ($sizeOptions as $opt) {
            if (is_numeric($opt)) {
                $numericSizes[] = (float) $opt;
            }
        }

        if (! empty($numericSizes)) {
            $avgSize = array_sum($numericSizes) / count($numericSizes);
            if ($avgSize < 30) {
                return 'mtb';
            }
            if ($avgSize >= 40) {
                return 'road';
            }
        }
    }

    return null;
}
