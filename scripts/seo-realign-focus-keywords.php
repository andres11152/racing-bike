<?php

/**
 * Fase 1+2 del plan de puntaje SEO: realinea la keyword focal de cada
 * producto con el título REAL que tiene hoy.
 *
 * Origen del problema: apply-rename-titles.php renombró los 58 productos
 * al formato [Tipo] [Marca] [Modelo], pero las keywords focales se
 * quedaron apuntando a los títulos VIEJOS ("bidon rb 750 ml" para lo que
 * hoy se llama "Caramañola de Ciclismo Racing Bike RB 750ml"). La prueba
 * keywordInTitle de Rank Math pesa 38 de 99 puntos posibles y exige la
 * keyword como frase contigua dentro del título — con keywords
 * desalineadas, es una pérdida garantizada de 38 (+3 de
 * titleStartWithKeyword) en 38 de 49 productos.
 *
 * Nueva keyword = título actual, quitando el bloque final entre
 * paréntesis si lo hay ("(32T 10-51T)" → nada). Es SIEMPRE una substring
 * literal y un prefijo del título real, así que keywordInTitle y
 * titleStartWithKeyword quedan garantizados sin necesidad de revisar
 * caso por caso.
 *
 * NO se toca el slug/permalink: el módulo de Redirecciones de Rank Math
 * está inactivo en este sitio (verificado con
 * get_option('rank_math_modules')), así que cambiar la URL del producto
 * no dejaría ningún 301 automático — cualquier enlace externo o
 * resultado ya indexado de Google apuntando al slug viejo se rompería.
 * keywordInPermalink (5 pts) queda como pérdida aceptada a cambio de no
 * arriesgar tráfico/indexación ya ganada.
 *
 * Contenido: se reemplaza toda ocurrencia LITERAL (case-insensitive) de
 * la keyword vieja por la nueva dentro de la descripción — cubre los
 * productos donde seo-structure-descriptions.php escribió el texto
 * alrededor de la keyword vieja (H2, primera frase, cierre). En los
 * productos donde ese script usó el título completo en su lugar (los de
 * la marca propia RB), el reemplazo no encuentra nada que cambiar, pero
 * tampoco hace falta: la nueva keyword YA es una substring del título
 * que el contenido repite, así que keywordInContent/InSubheadings/
 * InImageAlt/In10Percent quedan cubiertas de por sí.
 *
 * Meta description: mismo reemplazo literal si la keyword vieja
 * aparecía ahí.
 *
 * El script recalcula el mismo diagnóstico de seo-diagnose.php ANTES y
 * DESPUÉS de simular el cambio, para poder revisar el impacto real
 * producto por producto antes de aplicar nada.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-realign-focus-keywords.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega \"apply\" para ejecutar)\n\n";

function rb_norm(string $s): string
{
    $s = mb_strtolower(wp_strip_all_tags($s));
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
    $s = str_replace('×', 'x', $s);

    return trim(preg_replace('/\s+/u', ' ', $s));
}

const RB_SEO_PESOS = [
    'keywordInTitle'            => 38,
    'contentHasAssets'          => 6,
    'keywordDensity'            => 6,
    'keywordInPermalink'        => 5,
    'linksHasInternal'          => 5,
    'permalinkLength'           => 4,
    'linksHasExternals'         => 4,
    'keywordIn10Percent'        => 3,
    'keywordInContent'          => 3,
    'keywordInSubheadings'      => 3,
    'contentHasShortParagraphs' => 3,
    'titleStartWithKeyword'     => 3,
    'keywordInImageAlt'         => 2,
    'keywordInMetaDescription'  => 2,
    'linksNotAllExternals'      => 2,
    'contentHasTOC'             => 2,
    'hasProductSchema'          => 2,
    'isReviewEnabled'           => 2,
    'lengthContent'             => 2,
    'titleHasNumber'            => 1,
    'titleHasPowerWords'        => 1,
];

const RB_SEO_POWERWORDS_ES = ['increíble','asombroso','maravilloso','único','hermoso','felicidad','brillante','cautivador','carismático','impactante','claro','completamente','confidencial','confianza','significativo','creativo','definitivamente','delicioso','demostrar','apresúrate','decidido','digno','dinámico','impresionante','esencial','inspirador','innovador','intenso','eficaz','mágico','magnífico','histórico','importante','indispensable','inolvidable','irresistible','legendario','luminoso','lujo','majestuoso','memorable','milagroso','motivador','necesario','nuevo','oficial','perfecto','apasionado','persuasivo','fenomenal','placer','popular','poder','prestigioso','prodigioso','profundo','próspero','poderoso','calidad','radiante','rápido','exitoso','revolucionario','satisfecho','seguridad','sensacional','sereno','suntuoso','espléndido','sublime','sorprendente','talentoso','terrorífico','valor','vibrante','victorioso','vivo','verdaderamente','celoso','auténtico','aventurero','espectacular','exclusivo','garantizado','extraordinario','fabuloso','fascinante','formidable','genial','grandioso','gratuito','hábil','ilimitado','impecable','infalible','infinitamente','influyente','ingenioso','irremplazable','líder','maestro','notable','novedoso','pionero','reconocido','superior','triunfante','ultra','valiente','valioso','vanguardista','vigoroso','visionario','voluntad','vital','triunfo','glorioso','imparable','inigualable','inteligente','invencible','libertad','orgullo','paz','progreso','renovado','sabiduría','satisfacción','seguro','serenidad','superación','talento','transcendente','transformador','valentía','victoria'];

/**
 * Corre el mismo set de pruebas que seo-diagnose.php sobre un juego de
 * datos dado (para poder simular "antes" y "después" sin volver a leer
 * la base de datos).
 */
function rb_score(WC_Product $product, string $keyword, string $content, string $metaDescription): array
{
    $keywordNorm = rb_norm($keyword);
    $keywordNorm = trim(explode(',', $keywordNorm)[0]);
    $contentPlano = rb_norm($content);
    $palabras = $contentPlano === '' ? 0 : str_word_count($contentPlano);
    $tituloPlantilla = (string) get_post_meta($product->get_id(), 'rank_math_title', true);
    $titulo = rb_norm($tituloPlantilla !== '' ? $tituloPlantilla : $product->get_name() . ' - Tienda Oficial Racing Bike 1998');
    $descripcionNorm = rb_norm($metaDescription);
    $slug = $product->get_slug();

    $r = [];
    $r['keywordInTitle'] = $keywordNorm !== '' && str_contains($titulo, $keywordNorm);
    $r['titleStartWithKeyword'] = $keywordNorm !== '' && str_starts_with($titulo, $keywordNorm);
    $r['keywordInMetaDescription'] = $keywordNorm !== '' && $descripcionNorm !== '' && str_contains($descripcionNorm, $keywordNorm);
    $r['keywordInPermalink'] = $keywordNorm !== '' && str_contains($slug, sanitize_title($keywordNorm));
    $r['permalinkLength'] = strlen($slug) <= 75;
    $r['keywordInContent'] = $keywordNorm !== '' && str_contains($contentPlano, $keywordNorm);
    $r['keywordIn10Percent'] = $keywordNorm !== '' && $contentPlano !== ''
        && str_contains(mb_substr($contentPlano, 0, max(1, (int) ceil(mb_strlen($contentPlano) * 0.1))), $keywordNorm);
    $r['keywordInSubheadings'] = $keywordNorm !== '' && preg_match('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/is', $content, $m)
        && str_contains(rb_norm($m[1] ?? ''), $keywordNorm);
    $r['keywordInImageAlt'] = $keywordNorm !== '' && preg_match('/<img[^>]+alt=["\']([^"\']*)["\']/i', $content, $ma)
        && str_contains(rb_norm($ma[1] ?? ''), $keywordNorm);
    $r['contentHasAssets'] = (bool) preg_match('/<img|<video|\[gallery|wp-block-image/i', $content);
    $r['contentHasTOC'] = (bool) preg_match('/<a[^>]+href=["\']#/i', $content);
    $r['linksHasInternal'] = (bool) preg_match('/<a[^>]+href=["\']([^"\']*racingbike\.com\.co|\/)[^"\']*["\']/i', $content);
    $r['linksHasExternals'] = (bool) preg_match('/<a[^>]+href=["\']https?:\/\/(?!(?:www\.)?racingbike\.com\.co)/i', $content);
    $r['linksNotAllExternals'] = $r['linksHasInternal'] || ! $r['linksHasExternals'];
    $r['lengthContent'] = $palabras >= 600;

    $r['contentHasShortParagraphs'] = true;
    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $ps)) {
        foreach ($ps[1] as $p) {
            if (str_word_count(wp_strip_all_tags($p)) > 120) {
                $r['contentHasShortParagraphs'] = false;
                break;
            }
        }
    }

    $r['titleHasNumber'] = (bool) preg_match('/\d/', $titulo);

    $tituloConTildes = mb_strtolower(wp_strip_all_tags($tituloPlantilla !== '' ? $tituloPlantilla : $product->get_name() . ' - Tienda Oficial Racing Bike 1998'));
    $r['titleHasPowerWords'] = false;
    foreach (RB_SEO_POWERWORDS_ES as $pw) {
        if (str_contains($tituloConTildes, mb_strtolower($pw))) {
            $r['titleHasPowerWords'] = true;
            break;
        }
    }

    $r['hasProductSchema'] = true;
    $r['isReviewEnabled'] = (bool) $product->get_reviews_allowed();

    $ocurrencias = ($keywordNorm !== '' && $palabras > 0) ? substr_count($contentPlano, $keywordNorm) : 0;
    $densidad = $palabras > 0 ? $ocurrencias / $palabras * 100 : 0;
    $r['keywordDensity'] = $densidad >= 0.5 && $densidad <= 2.5;

    $puntos = 0;
    $maxPosible = array_sum(RB_SEO_PESOS);
    foreach (RB_SEO_PESOS as $prueba => $peso) {
        if (! empty($r[$prueba])) {
            $puntos += $peso;
        }
    }

    return [
        'score' => (int) round($puntos / $maxPosible * 100),
        'density' => round($densidad, 2),
        'fallos' => array_keys(array_filter($r, fn ($v) => ! $v)),
    ];
}

/**
 * Título real menos el bloque final entre paréntesis, p.ej.
 * "Grupo MTB Shimano Deore M6100 1x12V (32T 10-51T)" -> "Grupo MTB
 * Shimano Deore M6100 1x12V". Es siempre substring y prefijo literal del
 * título, así que keywordInTitle/titleStartWithKeyword quedan
 * garantizados sin revisión caso por caso.
 */
function rb_derive_keyword(string $title): string
{
    $core = preg_replace('/\s*\([^)]*\)\s*$/u', '', $title);
    $core = trim($core);

    return $core !== '' ? $core : $title;
}

$ids = [2056, 2008, 1993, 1977, 1950, 1934, 1923, 1867, 1862, 1845, 1843, 1824, 1819, 1808, 1799, 1775, 1760, 1645, 1117, 841, 835, 834, 832, 821, 818, 741, 518, 515, 512, 509, 506, 397, 396, 395, 46, 44, 42, 40];

$stats = ['mejoraron' => 0, 'sin_cambio' => 0, 'empeoraron' => 0, 'suma_antes' => 0, 'suma_despues' => 0];

foreach ($ids as $id) {
    $product = wc_get_product($id);
    if (! $product) {
        echo "[$id] SKIP: producto no encontrado\n";
        continue;
    }

    $title = $product->get_name();
    $oldKeyword = trim((string) get_post_meta($id, 'rank_math_focus_keyword', true));
    $oldKeywordFirst = trim(explode(',', $oldKeyword)[0]);
    $content = (string) $product->get_description();
    $metaDescription = (string) get_post_meta($id, 'rank_math_description', true);

    $newKeyword = rb_derive_keyword($title);

    $before = rb_score($product, $oldKeyword, $content, $metaDescription);

    // Reemplazo literal, case-insensitive, de la keyword vieja por la
    // nueva — sólo donde de verdad aparece. Si no aparece (contenido
    // genérico basado en el título completo, no en la keyword), no hace
    // falta: la nueva keyword ya es substring del título repetido.
    $newContent = $content;
    $newMetaDescription = $metaDescription;
    $contentChanged = false;
    $metaChanged = false;

    if ($oldKeywordFirst !== '' && stripos($content, $oldKeywordFirst) !== false) {
        $newContent = str_ireplace($oldKeywordFirst, $newKeyword, $content);
        $contentChanged = $newContent !== $content;
    }

    if ($oldKeywordFirst !== '' && stripos($metaDescription, $oldKeywordFirst) !== false) {
        $newMetaDescription = str_ireplace($oldKeywordFirst, $newKeyword, $metaDescription);
        $metaChanged = $newMetaDescription !== $metaDescription;
    }

    $after = rb_score($product, $newKeyword, $newContent, $newMetaDescription);

    $delta = $after['score'] - $before['score'];
    $stats['suma_antes'] += $before['score'];
    $stats['suma_despues'] += $after['score'];
    if ($delta > 0) {
        $stats['mejoraron']++;
    } elseif ($delta < 0) {
        $stats['empeoraron']++;
    } else {
        $stats['sin_cambio']++;
    }

    printf(
        "[%d] %s\n  kw: \"%s\" -> \"%s\"\n  score: %d -> %d (%+d) | densidad: %.2f%% -> %.2f%% | contenido %s | meta %s\n  fallos restantes: %s\n\n",
        $id,
        $title,
        $oldKeywordFirst,
        $newKeyword,
        $before['score'],
        $after['score'],
        $delta,
        $before['density'],
        $after['density'],
        $contentChanged ? '(actualizado)' : '(sin cambios)',
        $metaChanged ? '(actualizada)' : '(sin cambios)',
        implode(', ', $after['fallos'])
    );

    if ($apply) {
        update_post_meta($id, 'rank_math_focus_keyword', $newKeyword);

        if ($contentChanged) {
            $product->set_description($newContent);
            $product->save();
        }

        if ($metaChanged) {
            update_post_meta($id, 'rank_math_description', $newMetaDescription);
        }
    }
}

echo "============================================================\n";
printf(
    "%s: %d mejoraron, %d sin cambio, %d empeoraron.\n",
    $apply ? 'Aplicado' : 'Se aplicaría',
    $stats['mejoraron'],
    $stats['sin_cambio'],
    $stats['empeoraron']
);
printf("Promedio estimado: %.1f -> %.1f\n", $stats['suma_antes'] / count($ids), $stats['suma_despues'] / count($ids));
