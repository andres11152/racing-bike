<?php
/**
 * Aplicación masiva de SKUs Enterprise a todos los productos y variaciones en producción.
 */
require_once __DIR__ . '/wp-load.php';

$colorDictionary = [
    'espace-green-matt-ivory-white-gloss' => 'EGRN',
    'halo-silver-tanzanite-gloss' => 'SLVTNZ',
    'magnetic-bronze-matt-cosmic-bronze-gloss' => 'MBRZ',
    'slate-blue-matt-halo-silver-gloss' => 'SBLU',
    'cobalt-blue-carbon-raw' => 'CBLU',
    'diamond-c-view-titan-grey' => 'DGRY',
    'iris-white-lilac' => 'WHTLIL',
    'ivory-white-titan-bronze-gloss' => 'IVYBRZ',

    'crimson-to-dark-carmine-fade' => 'CRIM',
    'fury-red' => 'FRED',
    'fury-red-lithium-grey-fade' => 'FREDGRY',
    'gloss-lavender-haze' => 'GLAV',
    'lava' => 'LAVA',
    'lavender-haze' => 'LAV',
    'magic-mint' => 'MINT',
    'matte-dark-web-clear-gloss' => 'MDWEB',
    'matte-lichen-keswick-green-fade' => 'MLGRN',
    'matte-lithium-grey' => 'MLGRY',
    'miami-green-dark-aquatic-fade' => 'MGRN',
    'mulsanne-blue' => 'MBLU',
    'satin-trek-black-lithium-grey' => 'STBLK',
    'slate-prismatic-black-prismatic-fade' => 'SPBLK',

    'azul' => 'BLU',
    'azul-cobalto' => 'CBLU',
    'azul-humo' => 'HBLU',
    'azul-metalico' => 'MBLU',
    'azul-neptuno' => 'NBLU',
    'azul-noche-turquesa' => 'BLUTUR',
    'azul-noche-verde-neon' => 'BLUNGRN',
    'azul-petroleo-verde-neon' => 'PBLUGRN',
    'azul-polish' => 'PBLU',
    'azul-prisma' => 'PRBLU',
    'azul-real' => 'RBLU',
    'azul-zafiro-gris-plata' => 'BLUSLV',

    'blanco' => 'WHT',
    'blanco-brillante-negro-brillante' => 'WHTBLK',
    'blanco-cuarzo' => 'QWHT',
    'blanco-mate' => 'MWHT',
    'blanco-metalico' => 'MTWHT',

    'fucsia-fuego-negro-brillante' => 'FUCBLK',

    'gris' => 'GRY',
    'gris-abedul-negro-brillante' => 'GRYBLK',
    'gris-brillante-rojo' => 'GRYRED',
    'gris-claro' => 'LGRY',
    'gris-darkness' => 'DGRY',
    'gris-mercury' => 'MGRY',
    'gris-plata' => 'SLV',
    'gris-platino' => 'PLT',
    'gris-tornasol' => 'CHRM',

    'morado-carmin-negro-brillante' => 'PURBLK',

    'negro' => 'BLK',
    'negro-brillante-blanco-brillante' => 'BLKWHT',
    'negro-brillante-gris-plata' => 'BLKSLV',
    'negro-gris' => 'BLKGRY',
    'negro-mate' => 'MBLK',
    'negro-mate-azul-claro' => 'BLKBLU',
    'negro-mate-gris-mate' => 'BLKGM',
    'negro-naranja' => 'BLKORG',
    'negro-oro' => 'BLKGOL',
    'negro-perlado' => 'PBLK',
    'negro-rojo' => 'BLKRED',
    'negro-rojo-zebra' => 'BLKREDZ',
    'negro-rosado' => 'BLKPNK',

    'nickel-chocolate' => 'BRWN',

    'rojo' => 'RED',
    'rojo-chile-negro-brillante' => 'CHLBLK',
    'rojo-jaspe' => 'JRED',
    'rojo-marron' => 'MRED',
    'rojo-metalico' => 'MTRED',
    'rojo-rubi' => 'RRED',

    'verde' => 'GRN',
    'verde-metalico' => 'MGRN',
    'verde-oliva-negro-brillante' => 'OLVBLK',
    'verde-pino' => 'PGRN',
    'verde-turkish' => 'TGRN',
];

$brandCodes = [
    'Orbea' => 'ORB',
    'Trek' => 'TRK',
    'GW' => 'GW',
    'Shimano' => 'SHI',
    'Magene' => 'MAG',
    'Poseidon' => 'POS',
    'Racing Bike' => 'RB',
];

$categoryCodes = [
    'Bicicleta de Ruta' => 'ROA',
    'Bicicleta MTB' => 'MTB',
    'Bicicleta Eléctrica' => 'EBK',
    'Grupo de Ruta' => 'GRP',
    'Grupo MTB' => 'GRP',
    'Grupo Gravel' => 'GRP',
    'Casco de Ciclismo' => 'CAS',
    'Simulador Inteligente' => 'SIM',
    'Pedales con Potenciómetro' => 'POT',
    'Bielas con Potenciómetro' => 'POT',
    'Ciclocomputador GPS' => 'ELE',
    'Luz Trasera Inteligente' => 'ELE',
    'Luz Trasera con Radar' => 'ELE',
    'Banda Cardíaca' => 'ELE',
    'Soporte para Ciclocomputador' => 'ACC',
    'Soporte de Manubrio' => 'ACC',
    'Ruedas de Carbono' => 'ACC',
    'Sillín de Ciclismo' => 'ACC',
    'Guantes de Ciclismo' => 'ACC',
    'Caramañola de Ciclismo' => 'ACC',
];

$modelCodes = [
    741 => 'AVANT-H50-26',
    676 => 'ORCA-M30-25',
    677 => 'ORCA-M30I-25',
    1128 => 'ALMA-H20-26',
    882 => 'ALMA-H30-26',
    861 => 'ALMA-H20-25',
    852 => 'ALMA-H30-25',
    1736 => 'DOM-AL4-26',
    1713 => 'EMON-ALR5-26',
    1692 => 'DOM-AL2-26',
    1133 => 'MARL-6-26',
    973 => 'PROC-6-26',
    954 => 'MARL-7-26',
    911 => 'MARL-5-26',
    518 => 'ZONC-105-12V',
    515 => 'SPRNT-RUTA',
    509 => 'FLAM-DISC-10V',
    512 => 'LETR-D-RUTA',
    506 => 'FLAM-CLAR-8V',
    1645 => 'ZEBRA-29',
    841 => 'ALLIG-29-11V',
    832 => 'HAWK-29-12V',
    834 => 'LYNX-29-7V',
    835 => 'MONK-29-7V',
    1117 => 'KETO-29-10V',
    359 => 'BOG-350W',
    2056 => 'RC',
    2008 => 'DEO-M6100-12V',
    1993 => 'GRX-RX610-12V',
    1977 => 'CUES-U4000-9V',
    1950 => 'DEO-M5100-11V',
    1934 => 'CUES-U6030-10V',
    1923 => 'NS-12V',
    1808 => 'TIAG-R4000-11V',
    1799 => 'DURA-R9270-12V',
    1784 => 'ULTE-R8170-12V',
    1775 => '105-R7170-12V',
    1765 => '105-R7120-12V',
    1760 => '105-R7000-11V',
    1867 => 'T600-ECO',
    1862 => 'T200',
    1855 => 'T110',
    1845 => 'P715S',
    1843 => 'P715K',
    1828 => 'P515',
    1824 => 'SUP-BAS',
    1819 => 'SUP-INT',
    818 => 'L308',
    821 => 'L508',
    395 => 'C606-V2',
    396 => 'C606-PRO',
    397 => 'C706',
    399 => 'H603',
    400 => 'H613',
    40 => 'CARB-50',
    42 => 'RACE',
    44 => 'PRO',
    46 => '750ML',
];

function cleanColorCode($val, $dict) {
    $slug = sanitize_title($val);
    if (isset($dict[$slug])) return $dict[$slug];
    if (isset($dict[$val])) return $dict[$val];
    return strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $slug), 0, 5));
}

function cleanSizeCode($val) {
    return strtoupper(preg_replace('/[^a-z0-9]/i', '', $val));
}

function cleanBielaCode($val) {
    $num = preg_replace('/[^0-9]/', '', $val);
    return 'B' . $num;
}

echo "=== APLICANDO SKUS ENTERPRISE A PRODUCCIÓN ===" . PHP_EOL . PHP_EOL;

$products = wc_get_products([
    'limit' => -1,
    'status' => ['publish', 'draft'],
]);

$parentUpdates = 0;
$variationUpdates = 0;
$errors = 0;

foreach ($products as $p) {
    if (str_contains(strtolower($p->get_name()), 'personalizada') || str_contains($p->get_slug(), 'personalizada')) {
        continue;
    }

    $id = $p->get_id();
    $name = $p->get_name();

    $brand = 'RB';
    foreach ($brandCodes as $bName => $bCode) {
        if (str_contains($name, $bName)) {
            $brand = $bCode;
            break;
        }
    }

    $cat = 'ACC';
    foreach ($categoryCodes as $cName => $cCode) {
        if (str_starts_with($name, $cName)) {
            $cat = $cCode;
            break;
        }
    }

    $model = $modelCodes[$id] ?? 'ITEM';
    $parentSku = "{$brand}-{$cat}-{$model}";

    try {
        $p->set_sku($parentSku);
        $p->save();
        $parentUpdates++;
        echo "[P] ID {$id}: SKU -> {$parentSku}\n";
    } catch (\Exception $e) {
        echo "[!] Error en Padre ID {$id}: " . $e->getMessage() . "\n";
        $errors++;
    }

    if ($p->is_type('variable')) {
        foreach ($p->get_children() as $childId) {
            $var = wc_get_product($childId);
            if (!$var) continue;

            $attrs = $var->get_attributes();
            $tokens = [];

            if (!empty($attrs['pa_color'])) {
                $tokens[] = cleanColorCode($attrs['pa_color'], $colorDictionary);
            } elseif (!empty($attrs['color'])) {
                $tokens[] = cleanColorCode($attrs['color'], $colorDictionary);
            }

            if (!empty($attrs['pa_talla'])) {
                $tokens[] = cleanSizeCode($attrs['pa_talla']);
            } elseif (!empty($attrs['talla'])) {
                $tokens[] = cleanSizeCode($attrs['talla']);
            }

            foreach ($attrs as $k => $v) {
                if (in_array($k, ['pa_color', 'color', 'pa_talla', 'talla'])) continue;
                if (str_contains($k, 'biela') || str_contains($k, 'crank')) {
                    $tokens[] = cleanBielaCode($v);
                } else {
                    $tokens[] = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $v), 0, 4));
                }
            }

            $varSku = $parentSku . '-' . implode('-', $tokens);

            try {
                $var->set_sku($varSku);
                $var->save();
                $variationUpdates++;
            } catch (\Exception $e) {
                echo "[!] Error en Variación ID {$childId}: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
}

echo PHP_EOL;
echo "=== RESUMEN DE ACTUALIZACIÓN DE SKUS ===" . PHP_EOL;
echo "Productos Padre actualizados: {$parentUpdates}\n";
echo "Variaciones actualizadas: {$variationUpdates}\n";
echo "Errores: {$errors}\n";
echo "Total SKUs actualizados: " . ($parentUpdates + $variationUpdates) . "\n";
