<?php

/**
 * Fase 1 del plan de puntaje SEO: reestructura la descripción de cada
 * producto para sumar ~31 de los 100 puntos posibles con contenido que
 * YA EXISTE (no inventa specs ni copy de marketing) — solo le da la
 * forma que las pruebas de Rank Math necesitan:
 *
 *   contentHasAssets (6)          imagen del producto dentro del cuerpo
 *   linksHasInternal (5)          enlace a su categoría + un relacionado
 *   linksHasExternals (4)         enlace al sitio oficial de la marca
 *   linksNotAllExternals (2)      ya cubierto por tener ambos tipos
 *   keywordInSubheadings (3)      la keyword en los 2 <h2>
 *   contentHasShortParagraphs (3) ningún <p> supera 120 palabras (el
 *                                 límite real de Rank Math — ver
 *                                 assets/admin/js/analyzer.js)
 *   keywordIn10Percent (3)        keyword en la primera oración
 *   keywordInContent (3)          keyword en el cuerpo
 *   keywordInImageAlt (2)         ALT de la imagen = keyword
 *   contentHasTOC (2)             2 enlaces ancla (#) al inicio
 *   keywordDensity (6, parcial)   apunta a >1% sin forzar repetición
 *
 * El texto original (specs, características) se conserva íntegro:
 * las líneas que ya son una lista ("- Algo: valor") pasan a <ul><li>,
 * el resto queda como párrafos, partidos en trozos de ≤120 palabras si
 * hace falta (buscando el punto más cercano, nunca a mitad de frase).
 *
 * Enlace externo SOLO si la marca asignada es una marca real de
 * bicicletas/componentes (Trek, Orbea, Shimano, Magene, GW — URLs
 * verificadas con búsqueda web, no adivinadas). Se omite a propósito en:
 *   - productos sin marca asignada
 *   - productos "Cliff" (sin sitio oficial propio, solo revendedores)
 *   - los 4 productos de línea propia "RB" que tienen una marca ajena
 *     mal asignada (ej. "Bidón RB 750 ml" con marca "Shimano") — ver
 *     nota aparte al cliente, es un bug de datos distinto a este plan.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-structure-descriptions.php
 *      wp --skip-themes eval-file scripts/seo-structure-descriptions.php apply
 *      wp --skip-themes eval-file scripts/seo-structure-descriptions.php apply 861   (un solo producto)
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$args = $args ?? [];
$apply = in_array('apply', $args, true);
$soloId = null;
foreach ($args as $a) {
    if (ctype_digit((string) $a)) {
        $soloId = (int) $a;
    }
}

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

// URLs oficiales verificadas por búsqueda web el 2026-09-18. "Cliff" no
// tiene sitio propio (solo revendedores colombianos) y se omite a
// propósito.
const RB_BRAND_URLS = [
    'trek' => 'https://www.trekbikes.com',
    'orbea' => 'https://www.orbea.com',
    'shimano' => 'https://bike.shimano.com/home.html',
    'magene' => 'https://www.magene.com',
    'gw' => 'https://gwbicycles.com',
];

// Los 4 productos de línea propia "RB" con una marca ajena mal
// asignada en pa_marca — dato roto, no se usa para el enlace externo.
// Reportado aparte, no se toca aquí (está fuera del alcance de este
// script: es un problema de taxonomía, no de estructura de contenido).
const RB_MARCA_INVALIDA = [46, 44, 42, 40];

// 3 productos sin pa_marca asignado (dato incompleto — ver el mensaje al
// cliente) pero cuyo nombre no deja ninguna duda sobre la marca real:
// R7170/Dura Ace son códigos de modelo exclusivos de Shimano, y "SHIMANO"
// aparece literal en el nombre de 1799. No reemplaza corregir pa_marca en
// sí, solo evita perder el enlace externo mientras tanto.
const RB_MARCA_POR_NOMBRE = [
    1862 => 'magene',  // SIMULADOR MAGENE T200
    1775 => 'shimano', // GRUPO RUTA 105 R7170 DI2 (R7170 = código de modelo Shimano 105 Di2)
    1799 => 'shimano', // GRUPO RUTA DURA ACE DI2 12 VEL... SHIMANO
];

/** Registra bullets ("- algo") como lista, el resto como prose. */
function rb_seo_split_lines(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $bullets = [];
    $prose = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^[-•*]\s*(.+)$/u', $line, $m)) {
            $bullets[] = trim($m[1]);
        } else {
            $prose[] = $line;
        }
    }

    return [$prose, $bullets];
}

/** Parte un bloque de texto en párrafos de máximo $maxWords palabras cada uno, cortando en el punto más cercano. */
function rb_seo_chunk_paragraphs(string $text, int $maxWords = 100): array
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if ($text === '') {
        return [];
    }

    // Separar por oraciones (punto/exclamación/interrogación seguido de espacio y mayúscula
    // o fin de cadena) sin partir decimales ni abreviaturas comunes de specs (p.ej. "12.5").
    $sentences = preg_split('/(?<=[.!?])\s+(?=[A-ZÁÉÍÓÚÑ0-9])/u', $text) ?: [$text];

    // Excepción: alguna ficha de proveedor viene como una sola oración
    // gigante con las specs unidas por " - " en vez de puntos ("Cambios
    // Shimano - Comando Analógico - Información de pantalla..."). Si una
    // "oración" ya es más larga que el límite por sí sola, se corta
    // también en esos guiones antes de intentar cualquier otra cosa.
    $expanded = [];
    foreach ($sentences as $sentence) {
        if (str_word_count($sentence) > $maxWords && str_contains($sentence, ' - ')) {
            array_push($expanded, ...preg_split('/\s+-\s+/u', $sentence));
        } else {
            $expanded[] = $sentence;
        }
    }
    $sentences = $expanded;

    $paragraphs = [];
    $current = '';
    $currentWords = 0;

    foreach ($sentences as $sentence) {
        $words = str_word_count($sentence);
        if ($currentWords > 0 && $currentWords + $words > $maxWords) {
            $paragraphs[] = trim($current);
            $current = '';
            $currentWords = 0;
        }
        $current .= ($current === '' ? '' : ' ') . $sentence;
        $currentWords += $words;
    }
    if (trim($current) !== '') {
        $paragraphs[] = trim($current);
    }

    return $paragraphs;
}

function rb_seo_norm(string $s): string
{
    $s = mb_strtolower(wp_strip_all_tags($s));
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);

    return trim(preg_replace('/\s+/u', ' ', $s));
}

/**
 * 11 de los 49 productos ya traen HTML real en la descripción
 * (importados con <strong>Etiqueta:</strong> valor, típico de fichas de
 * proveedor pegadas tal cual) en vez de texto plano con guiones. Tratar
 * eso como texto plano y volver a unir todo con espacios (lo que hacía
 * la primera versión de este script) destruye los saltos de párrafo
 * reales del origen: un producto con 51 bloques cortos separados por
 * línea en blanco se convertía en UN párrafo de 700+ palabras.
 *
 * Aquí se respeta cada bloque separado por línea en blanco como su
 * propio párrafo (conservando negritas/enlaces existentes vía
 * wp_kses_post), y solo si un bloque en particular supera igual las 120
 * palabras (raro — la mayoría de fichas de proveedor ya vienen cortas)
 * se le quita el HTML y se parte por oraciones como al texto plano.
 */
function rb_seo_build_html_blocks(string $originalDescription): array
{
    $rawBlocks = preg_split('/\n\s*\n/', trim($originalDescription)) ?: [$originalDescription];
    $blocks = [];

    foreach ($rawBlocks as $raw) {
        $raw = trim($raw);
        if ($raw === '') {
            continue;
        }

        if (str_word_count(wp_strip_all_tags($raw)) <= 120) {
            $blocks[] = '<p>' . wp_kses_post($raw) . '</p>';
            continue;
        }

        // Excepción: bloque sin cortes naturales y demasiado largo. Se
        // pierde el formato inline (negritas) SOLO de este bloque
        // puntual para poder partirlo con seguridad por oraciones.
        foreach (rb_seo_chunk_paragraphs(wp_strip_all_tags($raw), 100) as $chunk) {
            $blocks[] = '<p>' . esc_html($chunk) . '</p>';
        }
    }

    return $blocks;
}

/**
 * Construye la descripción reestructurada. Devuelve null si el producto
 * no tiene ni descripción ni descripción corta de origen (nada que
 * reestructurar sin inventar contenido).
 */
function rb_seo_build_description(WC_Product $product, string $keyword): ?array
{
    $originalDescription = trim((string) $product->get_description());
    $originalExcerpt = trim(wp_strip_all_tags((string) $product->get_short_description()));

    if ($originalDescription === '' && $originalExcerpt === '') {
        return null;
    }

    $productId = $product->get_id();
    $name = $product->get_name();
    $slug = $product->get_slug();

    // 11 de los 49 productos ya traen HTML real (<strong>...) en vez de
    // texto plano con guiones — ver rb_seo_build_html_blocks().
    $hasHtml = $originalDescription !== wp_strip_all_tags($originalDescription);
    $bullets = [];
    $htmlBlocks = [];
    $paragraphs = [];
    $firstLineForIntro = '';

    if ($hasHtml) {
        $htmlBlocks = rb_seo_build_html_blocks($originalDescription);
        $rawFirstBlock = preg_split('/\n\s*\n/', trim($originalDescription))[0] ?? '';
        $firstLineForIntro = trim(wp_strip_all_tags($rawFirstBlock));
        $prosaText = wp_strip_all_tags($originalDescription);
    } else {
        [$prose, $bullets] = rb_seo_split_lines($originalDescription);
        $firstLineForIntro = $prose[0] ?? '';
    }

    // Intro: el resumen corto si existe (suele ser 1-2 frases ya
    // pensadas para esto), si no la primera línea de la descripción.
    $intro = $originalExcerpt !== '' ? $originalExcerpt : ($firstLineForIntro !== '' ? $firstLineForIntro : $name);
    // Si la keyword no aparece ya en la intro, se antepone de forma
    // natural — así queda garantizada dentro del primer 10% del texto
    // (keywordIn10Percent) sin alterar la frase original.
    if (! str_contains(rb_seo_norm($intro), rb_seo_norm($keyword))) {
        $intro = $keyword . ': ' . lcfirst($intro);
    }

    if ($hasHtml) {
        // Si la intro salió del primer bloque (no había resumen corto),
        // se quita de "Características" para no repetirlo dos veces.
        if ($originalExcerpt === '' && ! empty($htmlBlocks)) {
            array_shift($htmlBlocks);
        }
    } else {
        // Si la intro salió de la primera línea de la descripción larga
        // (no había resumen corto), se quita de "Características" para
        // no repetirla dos veces.
        $prosaBody = $prose;
        if ($originalExcerpt === '' && ! empty($prosaBody)) {
            array_shift($prosaBody);
        }

        $prosaText = implode(' ', $prosaBody);
        $paragraphs = rb_seo_chunk_paragraphs($prosaText, 100);
    }

    // Cuántas veces mencionar la keyword, adaptado al largo real del
    // contenido — no un número fijo. keywordDensity de Rank Math es
    // ocurrencias/palabras_totales (sin ponderar por el largo de la
    // keyword, ver la nota más abajo), y apunta a 1%-2.5%: con
    // contenido muy corto (un bidón con 2 líneas de specs), repetir la
    // keyword en la intro Y en un encabezado ya pasaba de 22% de
    // densidad — "alta", que Rank Math puntúa en 0, no en 6.
    $estimatedWords = str_word_count(rb_seo_norm($intro))
        + str_word_count(rb_seo_norm(implode(' ', $bullets)))
        + str_word_count(rb_seo_norm($prosaText))
        + 55; // overhead fijo de la plantilla: TOC, títulos, CTA, frase de marca
    $targetOccurrences = max(1, min(3, (int) round($estimatedWords * 0.015)));

    // Imagen: la propia del producto (ya tiene ALT real desde la
    // limpieza de accesibilidad anterior); aquí se le agrega ADEMÁS un
    // segundo ALT centrado en la keyword para esta prueba puntual, sin
    // pisar el ALT "de verdad" guardado en la biblioteca de medios.
    $imageId = $product->get_image_id();
    $imageUrl = $imageId ? wp_get_attachment_image_url($imageId, 'large') : null;

    // Categoría principal, para el enlace interno.
    $catTerms = get_the_terms($productId, 'product_cat');
    $primaryCat = ($catTerms && ! is_wp_error($catTerms)) ? end($catTerms) : null;
    $catLink = $primaryCat ? get_term_link($primaryCat) : null;
    $catName = $primaryCat ? $primaryCat->name : null;

    // Un producto relacionado real (misma categoría/atributos —
    // wc_get_related_products ya usa esa lógica nativa de WooCommerce).
    $relatedIds = wc_get_related_products($productId, 1);
    $relatedProduct = $relatedIds ? wc_get_product($relatedIds[0]) : null;

    // Marca, solo si es una de verdad (ver RB_BRAND_URLS / RB_MARCA_INVALIDA).
    $brandTerms = get_the_terms($productId, 'pa_marca');
    $brandSlug = ($brandTerms && ! is_wp_error($brandTerms)) ? $brandTerms[0]->slug : null;
    $brandName = ($brandTerms && ! is_wp_error($brandTerms)) ? $brandTerms[0]->name : null;

    // Respaldo puntual para los 3 IDs de RB_MARCA_POR_NOMBRE (ver la
    // constante, arriba) — sin pa_marca asignado pero sin ambigüedad
    // posible sobre cuál es la marca real.
    if (! $brandSlug && isset(RB_MARCA_POR_NOMBRE[$productId])) {
        $brandSlug = RB_MARCA_POR_NOMBRE[$productId];
        $brandName = ucfirst($brandSlug);
    }

    $brandUrl = (! in_array($productId, RB_MARCA_INVALIDA, true) && $brandSlug && isset(RB_BRAND_URLS[$brandSlug]))
        ? RB_BRAND_URLS[$brandSlug]
        : null;

    $tocId1 = 'caracteristicas-' . $productId;
    $tocId2 = 'comprar-' . $productId;

    $html = [];

    // Intro primero, TOC después — no solo mejor orden de lectura (un
    // enlace de salto antes de cualquier contenido es raro), sino que
    // keywordIn10Percent mide sobre el primer 10% del TEXTO PLANO total:
    // en productos con poco contenido de origen, el propio texto de los
    // enlaces del TOC ("Características · Dónde comprarla") consumía
    // ese margen y cortaba la keyword justo antes de que apareciera en
    // la intro.
    $html[] = '<p>' . esc_html($intro) . '</p>';

    // TOC (2 enlaces ancla).
    $html[] = sprintf(
        '<p class="rb-seo-toc"><a href="#%s">%s</a> · <a href="#%s">%s</a></p>',
        esc_attr($tocId1),
        esc_html__('Características', 'sage'),
        esc_attr($tocId2),
        esc_html__('Dónde comprarla', 'sage')
    );

    // Imagen con ALT centrado en la keyword.
    if ($imageUrl) {
        $html[] = sprintf(
            '<img src="%s" alt="%s" loading="lazy" style="max-width:100%%;height:auto;" />',
            esc_url($imageUrl),
            esc_attr($keyword)
        );
    }

    // Características: H2 + bullets (si hay) + resto en párrafos cortos.
    // La keyword va en este título solo si el contenido es lo bastante
    // largo como para sostener una 2ª mención sin disparar la densidad
    // (ver $targetOccurrences); keywordInSubheadings solo exige que
    // aparezca en UN encabezado como mínimo, no en todos.
    $h2Caracteristicas = $targetOccurrences >= 2
        ? sprintf(__('Características de %s', 'sage'), $keyword)
        : __('Características', 'sage');
    $html[] = sprintf('<h2 id="%s">%s</h2>', esc_attr($tocId1), esc_html($h2Caracteristicas));

    if ($bullets) {
        $html[] = '<ul>' . implode('', array_map(fn ($b) => '<li>' . esc_html($b) . '</li>', $bullets)) . '</ul>';
    }

    if ($hasHtml) {
        // Ya vienen envueltos en <p> y saneados con wp_kses_post() desde
        // rb_seo_build_html_blocks(); no se vuelven a escapar.
        foreach ($htmlBlocks as $block) {
            $html[] = $block;
        }
    } else {
        foreach ($paragraphs as $p) {
            $html[] = '<p>' . esc_html($p) . '</p>';
        }
    }

    if (! $bullets && ! $paragraphs && ! $htmlBlocks) {
        // Único caso posible: solo había resumen corto, ya usado en la
        // intro. Se repite como característica para que la sección no
        // quede vacía.
        $html[] = '<p>' . esc_html($intro) . '</p>';
    }

    // Por qué comprar aquí: H2 + enlaces internos + externo (si hay marca
    // real). keywordInSubheadings solo exige la keyword en UN encabezado
    // (ver assets/admin/js/analyzer.js: "one or more subheadings") — este
    // segundo <h2> no la repite a propósito. Para productos con poco
    // contenido de origen, repetirla en los 2 encabezados + la intro +
    // el ALT ya empujaba la densidad muy por encima del 2.5% que Rank
    // Math considera "alta" (score 0 en vez de 6).
    $html[] = sprintf('<h2 id="%s">%s</h2>', esc_attr($tocId2), esc_html__('¿Por qué comprar aquí?', 'sage'));

    $ctaParts = [];
    // 3ª mención de la keyword solo si el contenido es lo bastante largo
    // ($targetOccurrences === 3); si no, "este producto" — no un
    // pronombre de género ("Encuéntrala/Encuéntralo"), el catálogo
    // mezcla masculinos y femeninos (casco, bicicleta, bidón...) y no
    // hay forma de saber el género gramatical del nombre sin analizarlo
    // caso por caso.
    $ctaParts[] = $targetOccurrences >= 3
        ? esc_html(sprintf(__('Encuentra %s junto a toda nuestra línea de', 'sage'), $keyword))
        : esc_html__('Encuentra este producto junto a toda nuestra línea de', 'sage');
    if ($catLink && ! is_wp_error($catLink)) {
        $ctaParts[] = sprintf('<a href="%s">%s</a>', esc_url($catLink), esc_html($catName));
    } else {
        $ctaParts[] = esc_html__('productos relacionados', 'sage');
    }
    $ctaParts[] = esc_html__('en nuestra tienda física en Bogotá, con envío a toda Colombia y asesoría de nuestros mecánicos.', 'sage');

    if ($relatedProduct) {
        $ctaParts[] = esc_html__('También puede interesarte', 'sage')
            . sprintf(' <a href="%s">%s</a>.', esc_url($relatedProduct->get_permalink()), esc_html($relatedProduct->get_name()));
    }

    $html[] = '<p>' . implode(' ', $ctaParts) . '</p>';

    if ($brandUrl) {
        $html[] = '<p>' . sprintf(
            esc_html__('Para conocer las especificaciones oficiales de %1$s, visita %2$s.', 'sage'),
            esc_html($brandName),
            sprintf('<a href="%s" target="_blank" rel="nofollow noopener">%s</a>', esc_url($brandUrl), esc_html(sprintf(__('el sitio oficial de %s', 'sage'), $brandName)))
        ) . '</p>';
    }

    $finalHtml = implode("\n", $html);

    // Estadísticas para verificar antes de aplicar.
    $plainText = rb_seo_norm(wp_strip_all_tags($finalHtml));
    $totalWords = str_word_count($plainText);
    $kwNorm = rb_seo_norm($keyword);
    // Fórmula real de Rank Math (assets/admin/js/analyzer.js): ocurrencias
    // de la FRASE completa / palabras totales — sin multiplicar por el
    // largo de la keyword. Esa multiplicación era un bug de una versión
    // anterior de este mismo script: para keywords largas ("Grupo
    // Shimano CUES U4000 9VEL" = 5 palabras) inflaba la densidad
    // calculada muy por encima de la real.
    $occurrences = substr_count($plainText, $kwNorm);
    $density = $totalWords > 0 ? round($occurrences / $totalWords * 100, 2) : 0;

    $maxParagraphWords = 0;
    foreach ($paragraphs as $p) {
        $maxParagraphWords = max($maxParagraphWords, str_word_count($p));
    }
    foreach ($htmlBlocks as $block) {
        $maxParagraphWords = max($maxParagraphWords, str_word_count(wp_strip_all_tags($block)));
    }
    $introWords = str_word_count(rb_seo_norm($intro));

    return [
        'html' => $finalHtml,
        'stats' => [
            'palabras_totales' => $totalWords,
            'ocurrencias_keyword' => $occurrences,
            'densidad' => $density,
            'parrafo_mas_largo' => $maxParagraphWords,
            'palabras_intro' => $introWords,
            'tiene_imagen' => (bool) $imageUrl,
            'tiene_bullets' => (bool) $bullets,
            'enlace_categoria' => (bool) ($catLink && ! is_wp_error($catLink)),
            'enlace_relacionado' => (bool) $relatedProduct,
            'enlace_marca' => (bool) $brandUrl,
        ],
    ];
}

$ids = $soloId ? [$soloId] : get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$actualizados = 0;
$omitidos = 0;

foreach ($ids as $productId) {
    $product = wc_get_product($productId);
    if (! $product) {
        continue;
    }

    $keyword = trim(explode(',', (string) get_post_meta($productId, 'rank_math_focus_keyword', true))[0]);
    if ($keyword === '') {
        $keyword = $product->get_name();
    }

    $built = rb_seo_build_description($product, $keyword);

    if ($built === null) {
        printf("[%d] %s -> SIN CONTENIDO DE ORIGEN, se omite (necesita descripción escrita a mano).\n", $productId, $product->get_name());
        $omitidos++;
        continue;
    }

    $s = $built['stats'];
    printf(
        "[%d] %s\n  %d palabras | keyword x%d (densidad %.2f%%) | párrafo más largo: %d palabras | intro: %d palabras\n  imagen=%s bullets=%s enlace_cat=%s enlace_relacionado=%s enlace_marca=%s\n",
        $productId,
        $product->get_name(),
        $s['palabras_totales'],
        $s['ocurrencias_keyword'],
        $s['densidad'],
        $s['parrafo_mas_largo'],
        $s['palabras_intro'],
        $s['tiene_imagen'] ? 'si' : 'NO',
        $s['tiene_bullets'] ? 'si' : 'no',
        $s['enlace_categoria'] ? 'si' : 'NO',
        $s['enlace_relacionado'] ? 'si' : 'no',
        $s['enlace_marca'] ? 'si' : 'no (sin marca real asignada)'
    );

    if ($s['parrafo_mas_largo'] > 120) {
        echo "  *** ADVERTENCIA: un párrafo supera 120 palabras, contentHasShortParagraphs fallaría. ***\n";
    }

    if ($soloId) {
        echo "\n--- HTML COMPLETO ---\n" . $built['html'] . "\n---------------------\n";
    }

    if ($apply) {
        $product->set_description($built['html']);
        $product->save();
    }

    $actualizados++;
}

echo "\n";
printf("%s: %d producto(s). Omitidos (sin contenido de origen): %d.\n", $apply ? 'Aplicado' : 'Se aplicaría', $actualizados, $omitidos);
