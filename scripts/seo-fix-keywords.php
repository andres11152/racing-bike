<?php

/**
 * Fase 2 del plan de puntaje SEO: corrige la keyword focal (Rank Math)
 * de 7 productos donde no coincide con el título real del producto.
 *
 * keywordInTitle vale 38 de 100 puntos posibles — el más pesado de las
 * 22 pruebas de Rank Math por lejos — y titleStartWithKeyword otros 3.
 * Detectado con scripts/seo-diagnose.php: 5 productos tenían una keyword
 * que no aparece como subcadena del título (orden de palabras invertido,
 * specs técnicas que no coinciden con el producto real, o directamente
 * la keyword de OTRO producto — ver GRUPO NS 32T, que tenía la keyword
 * de un grupo con cassette y velocidades distintas), y 6 no arrancaban
 * el título con la keyword.
 *
 * Cada reemplazo se verificó antes contra las 3 pruebas relacionadas
 * (keywordInTitle, titleStartWithKeyword, keywordInPermalink) con
 * scripts/seo_kw_verify.php. En 4 de los 7 casos la keyword nueva
 * también corrige el permalink de regalo; en los otros 3 el slug ya
 * está en producción con las palabras de la keyword vieja "horneadas"
 * adentro (p. ej. t200-magene, sin "simulador") — cambiar la URL de un
 * producto ya indexado para perseguir esos 5 puntos no vale el riesgo
 * de reseteo de la sepal/backlinks, así que se acepta perder esa única
 * prueba (34-41 puntos ganados igual, contra 5 perdidos).
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-keywords.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

// producto => [keyword vieja esperada (para no pisar si alguien ya la cambió a mano), keyword nueva]
const RB_KEYWORD_FIXES = [
    2103 => ['Bicicleta Personalizada (Configurada)', 'Bicicleta Personalizada'],
    1977 => ['Grupo Shimano Cues U4000 1x9', 'Grupo Shimano CUES U4000 9VEL'],
    1923 => ['MTB Grupo NS 32T 11-46T 1x10V Shimano', 'GRUPO NS 32T'],
    1862 => ['T200 MAGENE', 'SIMULADOR MAGENE T200'],
    1775 => ['RUTA SHIMANO 105 DI2 R7170', 'GRUPO RUTA 105 R7170 DI2'],
    841  => ['BICICLETA MTB 29P 1X11 VELOCIDADES', 'BICICLETA ALLIGATOR MTB 29P 1X11 VELOCIDADES'],
    1799 => ['GRUPO RUTA DURA ACE DI2 12 VEL DISCO 52/36T 30T SHIMANO', 'DURA ACE DI2'],
];

$actualizadas = 0;
$omitidas = 0;

foreach (RB_KEYWORD_FIXES as $productId => [$esperada, $nueva]) {
    $product = wc_get_product($productId);

    if (! $product) {
        printf("  [%d] producto no encontrado, se omite.\n", $productId);
        $omitidas++;
        continue;
    }

    $actual = trim(explode(',', (string) get_post_meta($productId, 'rank_math_focus_keyword', true))[0]);

    if ($actual !== $esperada) {
        printf(
            "  [%d] %s: la keyword actual (\"%s\") ya no es la esperada (\"%s\") — alguien la cambió a mano, se omite.\n",
            $productId,
            $product->get_name(),
            $actual,
            $esperada
        );
        $omitidas++;
        continue;
    }

    printf("  [%d] %s\n      \"%s\" -> \"%s\"\n", $productId, $product->get_name(), $actual, $nueva);

    if ($apply) {
        update_post_meta($productId, 'rank_math_focus_keyword', $nueva);
    }

    $actualizadas++;
}

echo "\n";
printf("%s: %d keyword(s). Omitidas: %d.\n", $apply ? 'Aplicado' : 'Se aplicaría', $actualizadas, $omitidas);
