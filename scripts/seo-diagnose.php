<?php

/**
 * Diagnóstico SEO por producto — SOLO LECTURA.
 *
 * Rank Math calcula su puntaje ÚNICAMENTE en el JavaScript del editor: su
 * propia clase PHP (Content_Analysis_Data) lo dice literalmente, "the
 * actual test logic only exists client-side in JS and has no PHP port".
 * Por eso el puntaje guardado en rank_math_seo_score solo se actualiza
 * cuando alguien abre y guarda el producto en el editor, y 15 de los 49
 * productos publicados ni siquiera tienen puntaje.
 *
 * Este script REPLICA en PHP las mismas pruebas que hace ese JS, con los
 * pesos reales extraídos del bundle del plugin
 * (assets/admin/js/analyzer.js), para poder ver qué falla en los 49
 * productos sin abrirlos uno por uno. No escribe nada ni toca el puntaje
 * de Rank Math: es un diagnóstico para saber dónde está el trabajo.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-diagnose.php
 *      wp --skip-themes eval-file scripts/seo-diagnose.php detalle
 */

if (! defined('ABSPATH')) { exit; }

$detalle = in_array('detalle', $args ?? [], true);

// Copia literal de assets/vendor/powerwords/es.php del plugin Rank Math
// instalado (seo-by-rank-math), no una lista inventada.
const RB_SEO_POWERWORDS_ES = ['increíble','asombroso','maravilloso','único','hermoso','felicidad','brillante','cautivador','carismático','impactante','claro','completamente','confidencial','confianza','significativo','creativo','definitivamente','delicioso','demostrar','apresúrate','decidido','digno','dinámico','impresionante','esencial','inspirador','innovador','intenso','eficaz','mágico','magnífico','histórico','importante','indispensable','inolvidable','irresistible','legendario','luminoso','lujo','majestuoso','memorable','milagroso','motivador','necesario','nuevo','oficial','perfecto','apasionado','persuasivo','fenomenal','placer','popular','poder','prestigioso','prodigioso','profundo','próspero','poderoso','calidad','radiante','rápido','exitoso','revolucionario','satisfecho','seguridad','sensacional','sereno','suntuoso','espléndido','sublime','sorprendente','talentoso','terrorífico','valor','vibrante','victorioso','vivo','verdaderamente','celoso','auténtico','aventurero','espectacular','exclusivo','garantizado','extraordinario','fabuloso','fascinante','formidable','genial','grandioso','gratuito','hábil','ilimitado','impecable','infalible','infinitamente','influyente','ingenioso','irremplazable','líder','maestro','notable','novedoso','pionero','reconocido','superior','triunfante','ultra','valiente','valioso','vanguardista','vigoroso','visionario','voluntad','vital','triunfo','glorioso','imparable','inigualable','inteligente','invencible','libertad','orgullo','paz','progreso','renovado','sabiduría','satisfacción','seguro','serenidad','superación','talento','transcendente','transformador','valentía','victoria'];

// Pesos reales sacados de assets/admin/js/analyzer.js del plugin.
const RB_SEO_PESOS = [
    'keywordInTitle'            => 38, // 38 en es / 36 en en — el más pesado con diferencia
    'contentHasAssets'          => 6,
    'keywordDensity'            => 6,  // escalonado: 0 / 2 / 3 / 6
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
    // titleSentiment NO está aquí a propósito: su propia condición
    // isApplicable en el JS del plugin exige locale "en" — en un sitio
    // en español Rank Math ni la evalúa ni la cuenta en el total
    // posible, así que sumarla infla el "máximo" y hace ver el puntaje
    // real más bajo de lo que Rank Math realmente reportaría.
];

function rb_norm(string $s): string
{
    $s = mb_strtolower(wp_strip_all_tags($s));
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);

    return trim(preg_replace('/\s+/u', ' ', $s));
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$fallosPorPrueba = array_fill_keys(array_keys(RB_SEO_PESOS), 0);
$puntajes = [];
$maxPosible = array_sum(RB_SEO_PESOS);

foreach ($ids as $id) {
    $product = wc_get_product($id);
    if (! $product) { continue; }

    $keyword = rb_norm((string) get_post_meta($id, 'rank_math_focus_keyword', true));
    $keyword = trim(explode(',', $keyword)[0]); // Rank Math guarda varias separadas por coma
    $content = (string) $product->get_description();
    $contentPlano = rb_norm($content);
    $palabras = $contentPlano === '' ? 0 : str_word_count($contentPlano);

    // Título tal como lo renderiza la plantilla global de Rank Math.
    $plantilla = (string) get_post_meta($id, 'rank_math_title', true);
    $titulo = rb_norm($plantilla !== '' ? $plantilla : $product->get_name() . ' - Tienda Oficial Racing Bike 1998');
    $descripcion = rb_norm((string) get_post_meta($id, 'rank_math_description', true));
    $slug = $product->get_slug();

    $r = [];
    $r['keywordInTitle'] = $keyword !== '' && str_contains($titulo, $keyword);
    $r['titleStartWithKeyword'] = $keyword !== '' && str_starts_with($titulo, $keyword);
    $r['keywordInMetaDescription'] = $keyword !== '' && $descripcion !== '' && str_contains($descripcion, $keyword);
    $r['keywordInPermalink'] = $keyword !== '' && str_contains($slug, sanitize_title($keyword));
    $r['permalinkLength'] = strlen($slug) <= 75;
    $r['keywordInContent'] = $keyword !== '' && str_contains($contentPlano, $keyword);
    $r['keywordIn10Percent'] = $keyword !== '' && $contentPlano !== ''
        && str_contains(mb_substr($contentPlano, 0, max(1, (int) ceil(mb_strlen($contentPlano) * 0.1))), $keyword);
    $r['keywordInSubheadings'] = $keyword !== '' && preg_match('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/is', $content, $m)
        && str_contains(rb_norm($m[1] ?? ''), $keyword);
    $r['keywordInImageAlt'] = $keyword !== '' && preg_match('/<img[^>]+alt=["\']([^"\']*)["\']/i', $content, $ma)
        && str_contains(rb_norm($ma[1] ?? ''), $keyword);
    $r['contentHasAssets'] = (bool) preg_match('/<img|<video|\[gallery|wp-block-image/i', $content);
    $r['contentHasTOC'] = (bool) preg_match('/<a[^>]+href=["\']#/i', $content);
    $r['linksHasInternal'] = (bool) preg_match('/<a[^>]+href=["\']([^"\']*racingbike\.com\.co|\/)[^"\']*["\']/i', $content);
    $r['linksHasExternals'] = (bool) preg_match('/<a[^>]+href=["\']https?:\/\/(?!(?:www\.)?racingbike\.com\.co)/i', $content);
    $r['linksNotAllExternals'] = $r['linksHasInternal'] || ! $r['linksHasExternals'];
    $r['lengthContent'] = $palabras >= 600;
    $r['titleHasNumber'] = (bool) preg_match('/\d/', $titulo);

    // Lista real de assets/vendor/powerwords/es.php del propio plugin
    // (RB_SEO_POWERWORDS_ES, más abajo en este mismo archivo — inlineada
    // para que el script siga siendo un solo archivo autocontenido, como
    // el resto de scripts/*.php: run-prod-script.sh solo sube ESTE
    // archivo al servidor, no una carpeta). Con acentos incluidos, así
    // que se compara sobre el título SIN quitar tildes (rb_norm() ya las
    // quita para las pruebas de keyword, pero esta se hace aparte con el
    // texto original).
    $tituloConTildes = mb_strtolower(wp_strip_all_tags($plantilla !== '' ? $plantilla : $product->get_name() . ' - Tienda Oficial Racing Bike 1998'));
    $r['titleHasPowerWords'] = false;
    foreach (RB_SEO_POWERWORDS_ES as $pw) {
        if (str_contains($tituloConTildes, mb_strtolower($pw))) {
            $r['titleHasPowerWords'] = true;
            break;
        }
    }

    // titleSentiment: no se evalúa (ver la nota en RB_SEO_PESOS), pero
    // NO se marca como fallo — no cuenta ni a favor ni en contra.

    $r['hasProductSchema'] = true;  // Rank Math lo emite por plantilla para todos los productos
    $r['isReviewEnabled'] = (bool) $product->get_reviews_allowed();

    // Densidad: 1%-2.5% es lo óptimo para Rank Math.
    $ocurrencias = ($keyword !== '' && $palabras > 0) ? substr_count($contentPlano, $keyword) : 0;
    $densidad = $palabras > 0 ? ($ocurrencias * str_word_count($keyword)) / $palabras * 100 : 0;
    $r['keywordDensity'] = $densidad >= 0.5 && $densidad <= 2.5;

    $puntos = 0;
    foreach (RB_SEO_PESOS as $prueba => $peso) {
        if (! empty($r[$prueba])) {
            $puntos += $peso;
        } else {
            $fallosPorPrueba[$prueba]++;
        }
    }

    $estimado = (int) round($puntos / $maxPosible * 100);
    $guardado = get_post_meta($id, 'rank_math_seo_score', true);
    $puntajes[$id] = [
        'nombre' => $product->get_name(),
        'estimado' => $estimado,
        'guardado' => $guardado === '' ? null : (int) $guardado,
        'palabras' => $palabras,
        'kw' => $keyword,
        'fallos' => array_keys(array_filter($r, fn ($v) => ! $v)),
    ];
}

echo "=== PRUEBAS QUE FALLAN, ordenadas por impacto (productos afectados x peso) ===\n\n";
$impacto = [];
foreach ($fallosPorPrueba as $prueba => $n) {
    $impacto[$prueba] = $n * RB_SEO_PESOS[$prueba];
}
arsort($impacto);
printf("%-28s %-8s %-7s %s\n", 'PRUEBA', 'PESO', 'FALLAN', 'PUNTOS PERDIDOS EN TOTAL');
foreach ($impacto as $prueba => $perdidos) {
    if ($fallosPorPrueba[$prueba] === 0) { continue; }
    printf("%-28s %-8d %-7d %d\n", $prueba, RB_SEO_PESOS[$prueba], $fallosPorPrueba[$prueba], $perdidos);
}

$estimados = array_column($puntajes, 'estimado');
echo "\n=== PUNTAJE ESTIMADO ACTUAL ===\n";
printf("productos: %d | promedio: %.1f | min: %d | max: %d\n", count($estimados), array_sum($estimados)/max(count($estimados),1), min($estimados), max($estimados));
printf("  >=90: %d | 80-89: %d | 50-79: %d | <50: %d\n",
    count(array_filter($estimados, fn($v)=>$v>=90)),
    count(array_filter($estimados, fn($v)=>$v>=80 && $v<90)),
    count(array_filter($estimados, fn($v)=>$v>=50 && $v<80)),
    count(array_filter($estimados, fn($v)=>$v<50))
);

echo "\n=== PALABRAS DE DESCRIPCIÓN (lengthContent pide 600+) ===\n";
$w = array_column($puntajes, 'palabras');
sort($w);
printf("min: %d | mediana: %d | max: %d | con 600+: %d de %d\n", min($w), $w[(int)(count($w)/2)], max($w), count(array_filter($w, fn($v)=>$v>=600)), count($w));

if ($detalle) {
    echo "\n=== DETALLE POR PRODUCTO ===\n";
    foreach ($puntajes as $id => $d) {
        printf("\n[%d] %s\n  estimado=%d guardado=%s palabras=%d kw=\"%s\"\n  falla: %s\n",
            $id, $d['nombre'], $d['estimado'], $d['guardado'] ?? 'N/A', $d['palabras'], $d['kw'], implode(', ', $d['fallos']));
    }
}
