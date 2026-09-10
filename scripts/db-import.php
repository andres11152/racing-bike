<?php

/**
 * Restaurador que hace pareja con scripts/db-export.php, para el mismo
 * motivo: `wp db import` depende de proc_open (llama a mysql por shell),
 * deshabilitado en este hosting a nivel de cuenta.
 *
 * Ejecuta cada sentencia por separado vía $wpdb->query(), dividiendo el
 * archivo por el delimitador que escribe db-export.php — nunca por ";",
 * que puede aparecer dentro de un valor de texto (contenido de un post,
 * por ejemplo) y partiría una sentencia por la mitad.
 *
 * SIEMPRE pide confirmación explícita: es una operación destructiva
 * (DROP TABLE + recrear) sobre la base de datos completa.
 *
 * Uso: wp --skip-themes eval-file scripts/db-import.php /ruta/al/dump.sql CONFIRMO
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

global $wpdb;

const RB_DUMP_STATEMENT_DELIMITER = '--RB-DUMP-STATEMENT-END--';

$inputPath = $args[0] ?? null;
$confirmed = ($args[1] ?? '') === 'CONFIRMO';

if (! $inputPath || ! file_exists($inputPath)) {
    echo "Falta el archivo o no existe. Uso: wp eval-file scripts/db-import.php /ruta/al/dump.sql CONFIRMO\n";
    exit(1);
}

if (! $confirmed) {
    echo "Esto va a BORRAR Y REEMPLAZAR todas las tablas de la base de datos actual\n";
    echo "con el contenido de {$inputPath}. No hay deshacer.\n\n";
    echo "Si estás seguro, vuelve a correrlo agregando CONFIRMO al final:\n";
    echo "  wp eval-file scripts/db-import.php {$inputPath} CONFIRMO\n";
    exit(1);
}

$content = file_get_contents($inputPath);
$statements = explode(RB_DUMP_STATEMENT_DELIMITER, $content);

$wpdb->query('SET FOREIGN_KEY_CHECKS=0');

$executed = 0;
$errors = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);

    if ($statement === '' || str_starts_with($statement, '--') || str_starts_with($statement, 'SET ')) {
        continue;
    }

    $result = $wpdb->query($statement);

    if ($result === false) {
        $errors++;
        echo 'ERROR en sentencia: ' . substr($statement, 0, 120) . "...\n";
        echo '  ' . $wpdb->last_error . "\n";
    } else {
        $executed++;
    }
}

$wpdb->query('SET FOREIGN_KEY_CHECKS=1');

echo "\nSentencias ejecutadas: {$executed}\n";
echo "Errores: {$errors}\n";
echo $errors === 0 ? "Restauración completa.\n" : "Restauración con errores — revisar arriba.\n";
