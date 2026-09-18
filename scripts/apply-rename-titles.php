<?php
/**
 * Aplicación de renombramiento estándar a todos los productos de producción:
 * [Tipo de producto] + [Marca] + [Modelo]
 */
require_once __DIR__ . '/wp-load.php';

$mapping = [
    // --- CASCOS Y PROTECCIÓN ---
    2056 => [
        'tipo' => 'Casco de Ciclismo',
        'marca' => 'GW',
        'modelo' => 'RC',
    ],

    // --- GRUPOS SHIMANO MTB ---
    2008 => [
        'tipo' => 'Grupo MTB',
        'marca' => 'Shimano',
        'modelo' => 'Deore M6100 1x12V (32T 10-51T)',
    ],
    1977 => [
        'tipo' => 'Grupo MTB',
        'marca' => 'Shimano',
        'modelo' => 'CUES U4000 Monoplato 9V (32T 11-46T)',
    ],
    1950 => [
        'tipo' => 'Grupo MTB',
        'marca' => 'Shimano',
        'modelo' => 'Deore M5100 1x11V (32T 11-51T)',
    ],
    1923 => [
        'tipo' => 'Grupo MTB',
        'marca' => 'Shimano',
        'modelo' => 'NS 1x12V (32T 10-51T)',
    ],

    // --- GRUPO SHIMANO GRAVEL ---
    1993 => [
        'tipo' => 'Grupo Gravel',
        'marca' => 'Shimano',
        'modelo' => 'GRX RX610 Disco 2x12V (46-30T 11-36T)',
    ],

    // --- GRUPOS SHIMANO RUTA ---
    1934 => [
        'tipo' => 'Grupo de Ruta',
        'marca' => 'Shimano',
        'modelo' => 'CUES U6030 2x10V (46-32T 11-39T)',
    ],
    1808 => [
        'tipo' => 'Grupo de Ruta',
        'marca' => 'Shimano',
        'modelo' => 'Tiagra R4000 Disco 2x11V (52-36T 11-36T)',
    ],
    1799 => [
        'tipo' => 'Grupo de Ruta',
        'marca' => 'Shimano',
        'modelo' => 'Dura-Ace Di2 R9270 Disco 12V (52-36T 11-30T)',
    ],
    1784 => [
        'tipo' => 'Grupo de Ruta',
        'marca' => 'Shimano',
        'modelo' => 'Ultegra Di2 R8170 Disco 12V',
    ],
    1775 => [
        'tipo' => 'Grupo de Ruta',
        'marca' => 'Shimano',
        'modelo' => '105 Di2 R7170 Disco 12V (52-36T 11-34T)',
    ],
    1765 => [
        'tipo' => 'Grupo de Ruta',
        'marca' => 'Shimano',
        'modelo' => '105 R7120 Disco 12V',
    ],
    1760 => [
        'tipo' => 'Grupo de Ruta',
        'marca' => 'Shimano',
        'modelo' => '105 R7000 Disco 11V',
    ],

    // --- TECNOLOGÍA / SIMULADORES / ACCESORIOS MAGENE ---
    1867 => [
        'tipo' => 'Simulador Inteligente',
        'marca' => 'Magene',
        'modelo' => 'T600 Eco',
    ],
    1862 => [
        'tipo' => 'Simulador Inteligente',
        'marca' => 'Magene',
        'modelo' => 'T200',
    ],
    1855 => [
        'tipo' => 'Simulador Inteligente',
        'marca' => 'Magene',
        'modelo' => 'T110 Smart Trainer',
    ],
    1845 => [
        'tipo' => 'Pedales con Potenciómetro',
        'marca' => 'Magene',
        'modelo' => 'P715 S (Single)',
    ],
    1843 => [
        'tipo' => 'Pedales con Potenciómetro',
        'marca' => 'Magene',
        'modelo' => 'P715 K (Dual)',
    ],
    1828 => [
        'tipo' => 'Bielas con Potenciómetro',
        'marca' => 'Magene',
        'modelo' => 'P515',
    ],
    1824 => [
        'tipo' => 'Soporte para Ciclocomputador',
        'marca' => 'Magene',
        'modelo' => 'Básico',
    ],
    1819 => [
        'tipo' => 'Soporte de Manubrio',
        'marca' => 'Magene',
        'modelo' => 'Integrado de Aluminio',
    ],
    818 => [
        'tipo' => 'Luz Trasera Inteligente',
        'marca' => 'Magene',
        'modelo' => 'L308 Expression',
    ],
    821 => [
        'tipo' => 'Luz Trasera con Radar',
        'marca' => 'Magene',
        'modelo' => 'L508 Radar',
    ],
    395 => [
        'tipo' => 'Ciclocomputador GPS',
        'marca' => 'Magene',
        'modelo' => 'C606 V2 Smart',
    ],
    396 => [
        'tipo' => 'Ciclocomputador GPS',
        'marca' => 'Magene',
        'modelo' => 'C606 Pro Smart',
    ],
    397 => [
        'tipo' => 'Ciclocomputador GPS',
        'marca' => 'Magene',
        'modelo' => 'C706 Smart',
    ],
    399 => [
        'tipo' => 'Banda Cardíaca',
        'marca' => 'Magene',
        'modelo' => 'H603 Heart Rate',
    ],
    400 => [
        'tipo' => 'Banda Cardíaca',
        'marca' => 'Magene',
        'modelo' => 'H613 Heart Rate',
    ],

    // --- BICICLETAS TREK RUTA ---
    1736 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'Trek',
        'modelo' => 'Domane AL 4 Gen 4 2026',
    ],
    1713 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'Trek',
        'modelo' => 'Émonda ALR 5 2026',
    ],
    1692 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'Trek',
        'modelo' => 'Domane AL 2 2026',
    ],

    // --- BICICLETAS TREK MTB ---
    1133 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Trek',
        'modelo' => 'Marlin 6 2026',
    ],
    973 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Trek',
        'modelo' => 'Procaliber 6 2026',
    ],
    954 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Trek',
        'modelo' => 'Marlin 7 2026',
    ],
    911 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Trek',
        'modelo' => 'Marlin 5 2026',
    ],

    // --- BICICLETAS ORBEA MTB ---
    1128 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Orbea',
        'modelo' => 'Alma H20 2026',
    ],
    882 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Orbea',
        'modelo' => 'Alma H30 2026',
    ],
    861 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Orbea',
        'modelo' => 'Alma H20 2025',
    ],
    852 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Orbea',
        'modelo' => 'Alma H30 2025',
    ],

    // --- BICICLETAS ORBEA RUTA ---
    741 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'Orbea',
        'modelo' => 'Avant H50 2026',
    ],
    676 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'Orbea',
        'modelo' => 'Orca M30 2025',
    ],
    677 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'Orbea',
        'modelo' => 'Orca M30i 2025 OMR',
    ],

    // --- BICICLETAS GW RUTA ---
    518 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'GW',
        'modelo' => 'Zoncolan 700C 105 2x12V',
    ],
    515 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'GW',
        'modelo' => 'Sprinter',
    ],
    509 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'GW',
        'modelo' => 'Flamma Disco Tiagra 10V',
    ],
    512 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'GW',
        'modelo' => 'Letras+D',
    ],
    506 => [
        'tipo' => 'Bicicleta de Ruta',
        'marca' => 'GW',
        'modelo' => 'Flamma 700C Claris 2x8V',
    ],

    // --- BICICLETAS GW MTB ---
    1645 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'GW',
        'modelo' => 'Zebra 29',
    ],
    841 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'GW',
        'modelo' => 'Alligator 29 1x11V',
    ],
    832 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'GW',
        'modelo' => 'Hawk 1x12V Rin 29',
    ],
    834 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'GW',
        'modelo' => 'Lynx Rin 29',
    ],
    835 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'GW',
        'modelo' => 'Monkey 3x7V Rin 29',
    ],

    // --- BICICLETA POSEIDON MTB ---
    1117 => [
        'tipo' => 'Bicicleta MTB',
        'marca' => 'Poseidon',
        'modelo' => 'Keto 10V LTWOO Rin 29',
    ],

    // --- BICICLETA ELÉCTRICA ---
    359 => [
        'tipo' => 'Bicicleta Eléctrica',
        'marca' => 'GW',
        'modelo' => 'Bogotá 350W',
    ],

    // --- COMPONENTES / ACCESORIOS RACING BIKE ---
    40 => [
        'tipo' => 'Ruedas de Carbono',
        'marca' => 'Racing Bike',
        'modelo' => 'RB Carbon 50',
    ],
    42 => [
        'tipo' => 'Sillín de Ciclismo',
        'marca' => 'Racing Bike',
        'modelo' => 'RB Race',
    ],
    44 => [
        'tipo' => 'Guantes de Ciclismo',
        'marca' => 'Racing Bike',
        'modelo' => 'RB Pro',
    ],
    46 => [
        'tipo' => 'Caramañola de Ciclismo',
        'marca' => 'Racing Bike',
        'modelo' => 'RB 750ml',
    ],
];

echo "=== APLICANDO RENOMBRAMIENTO ESTÁNDAR A PRODUCCIÓN ===" . PHP_EOL . PHP_EOL;

$updatedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($mapping as $id => $parts) {
    $post = get_post($id);
    if (! $post) {
        echo "[-] ID {$id} no encontrado.\n";
        $errorCount++;
        continue;
    }

    $newTitle = "{$parts['tipo']} {$parts['marca']} {$parts['modelo']}";
    $currentTitle = $post->post_title;

    if ($currentTitle === $newTitle) {
        echo "[=] ID {$id}: Ya tiene el título estándar '{$newTitle}'\n";
        $skippedCount++;
        continue;
    }

    $result = wp_update_post([
        'ID' => $id,
        'post_title' => $newTitle,
    ], true);

    if (is_wp_error($result)) {
        echo "[!] Error actualizando ID {$id}: " . $result->get_error_message() . "\n";
        $errorCount++;
    } else {
        echo "[+] ID {$id}: '{$currentTitle}' -> '{$newTitle}'\n";
        $updatedCount++;
        clean_post_cache($id);
    }
}

echo PHP_EOL;
echo "=== RESUMEN DE EJECUCIÓN ===" . PHP_EOL;
echo "Actualizados con éxito: {$updatedCount}\n";
echo "Sin cambios (ya correctos): {$skippedCount}\n";
echo "Errores: {$errorCount}\n";
echo "Total procesados: " . count($mapping) . PHP_EOL;
