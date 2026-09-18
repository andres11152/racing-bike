<?php

/**
 * Repara 1862, 1775 y 1799: quedaron con contenido duplicado porque
 * seo-structure-descriptions.php se corrió una segunda vez (para
 * agregarles el enlace de marca por nombre) DESPUÉS de que ya llevaran
 * la descripción reestructurada de la primera pasada. El script leyó su
 * propio HTML como si fuera texto de origen y lo volvió a envolver
 * encima.
 *
 * IMPORTANTE: corre enteramente contra la base de datos de producción
 * vía wp-cli — nunca usa ningún archivo de respaldo local. Se descubrió
 * que el backup local descargado antes de esa segunda pasada
 * (backups/racingbike-2026-09-18_105147-*.sql.gz) tiene TODOS los
 * símbolos "%" reemplazados por un hash largo — corrupción de la copia
 * local, confirmada ausente en la base de datos real (una consulta
 * directa a producción encontró el símbolo "%" intacto). Por eso esta
 * reparación reconstruye a partir del HTML actual ya en producción, que
 * nunca tuvo ese problema, y no usa ese backup para nada.
 *
 * Estrategia: en vez de un regex genérico para "la forma que tomó la
 * corrupción" (que varía: a veces son 2 <p> separados, a veces se
 * fusionaron en uno solo), se buscan y quitan 3 fragmentos de texto
 * EXACTOS, calculados a partir de contenido que sigue intacto en el
 * propio producto (el intro del primer párrafo, la keyword, y los
 * nombres reales de categoría/relacionado del CTA correcto que quedó
 * más abajo):
 *
 *   A) "Características · Dónde comprarla {intro exacto}"  (duplicado
 *      del TOC + intro, en texto plano)
 *   B) "Características de {keyword} "  (duplicado del título del 1er
 *      H2, pegado al inicio de las specs)
 *   C) "¿Por qué comprar aquí? Encuentra {keyword} junto a toda nuestra
 *      línea de {categoría} en nuestra tienda física en Bogotá, con
 *      envío a toda Colombia y asesoría de nuestros mecánicos. También
 *      puede interesarte {relacionado}."  (duplicado del título del 2º
 *      H2 + el CTA completo, en texto plano) — todo lo que sigue
 *      después de esto y antes del 2º <h2> real se recorta también.
 *
 * Lo que queda entre B y C (las specs mezcladas + los párrafos de
 * prosa genuina) se conserva tal cual — es contenido real, solo faltaba
 * quitarle el ruido de alrededor.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-duplicated-content.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_IDS_A_REPARAR = [1862, 1775, 1799];

foreach (RB_IDS_A_REPARAR as $productId) {
    $product = wc_get_product($productId);

    if (! $product) {
        printf("[%d] producto no encontrado, se omite.\n", $productId);
        continue;
    }

    $html = (string) $product->get_description();
    $original = $html;

    $keyword = trim(explode(',', (string) get_post_meta($productId, 'rank_math_focus_keyword', true))[0]) ?: $product->get_name();

    // Intro real: el texto del primer <p> (nunca se corrompió).
    if (! preg_match('#<p>((?:(?!</p>).)*)</p>#us', $html, $mi)) {
        printf("[%d] no se pudo leer el primer párrafo (intro), se omite.\n", $productId);
        continue;
    }
    $introPlano = trim(wp_strip_all_tags($mi[1]));

    // A) Duplicado de TOC + intro (texto plano).
    $marcaA = 'Características · Dónde comprarla ' . $introPlano;
    $tieneA = str_contains($html, $marcaA);
    $html = str_replace($marcaA, '', $html);

    // B) Duplicado del título del 1er H2 pegado a las specs. El del
    // <h2> real no lleva espacio antes de "</h2>", así que con espacio
    // al final esta marca solo puede coincidir con la copia duplicada.
    $marcaB = 'Características de ' . $keyword . ' ';
    $tieneB = str_contains($html, $marcaB);
    $html = str_replace($marcaB, '', $html);

    // C) El título del 2º H2 se cuela DENTRO de la prosa antes del <h2>
    // real (a veces seguido, en el mismo <p> o en uno nuevo, de un
    // duplicado en texto plano del CTA) — no vale la pena reconstruir
    // esa cola letra por letra: todo lo que hay entre esa primera
    // aparición del título y el <h2> real es basura, se descarta entero.
    preg_match('#<h2 id="comprar-' . $productId . '">([^<]*)</h2>#u', $html, $h2m);
    $h2Titulo = $h2m[1] ?? '';
    $posH2Real = $h2m[0] ?? '';
    $posTituloColado = $h2Titulo !== '' ? strpos($html, $h2Titulo) : false;
    $posH2RealOffset = $posH2Real !== '' ? strpos($html, $posH2Real) : false;

    $tieneC = $posTituloColado !== false && $posH2RealOffset !== false && $posTituloColado < $posH2RealOffset;
    if ($tieneC) {
        $antes = rtrim(substr($html, 0, $posTituloColado));
        if (! str_ends_with($antes, '</p>')) {
            $antes .= '</p>';
        }
        $html = $antes . "\n" . substr($html, $posH2RealOffset);
    }

    printf(
        "[%d] %s\n  antes: %d caracteres | después: %d caracteres\n  A) duplicado TOC+intro: %s | B) título de H2 pegado a specs: %s | C) título del 2º H2 + CTA duplicado: %s\n\n",
        $productId,
        $product->get_name(),
        strlen($original),
        strlen($html),
        $tieneA ? 'quitado' : 'NO ENCONTRADO',
        $tieneB ? 'quitado' : 'NO ENCONTRADO',
        $tieneC ? 'quitado' : 'NO ENCONTRADO'
    );

    if (! $tieneA || ! $tieneB || ! $tieneC) {
        echo "  *** falta alguna marca esperada — revisar a mano antes de aplicar, no se escribe nada para este producto. ***\n\n";
        continue;
    }

    if (! $apply) {
        echo "  --- HTML resultante (vista previa) ---\n{$html}\n  --- fin ---\n\n";
    }

    if ($apply) {
        $product->set_description($html);
        $product->save();
        echo "  guardado.\n\n";
    }
}

echo ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
