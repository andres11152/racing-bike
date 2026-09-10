<?php

/**
 * Exportador de base de datos en PHP puro, para hosting con proc_open
 * deshabilitado (este servidor lo bloquea a nivel de cuenta, así que
 * `wp db export` —que llama a mysqldump por shell— no puede funcionar
 * nunca aquí; falla "silenciosamente exitoso": WP-CLI imprime el error
 * pero termina con código 0 igual, así que un script que no revise la
 * salida lo confunde con un backup real).
 *
 * Usa $wpdb para leer cada tabla en bloques y escribe un .sql restaurable
 * con `wp db import` o `mysql < archivo.sql`.
 *
 * Restaurar con scripts/db-import.php (`wp db import` también depende de
 * proc_open — falla igual que export, esta vez sí con código de error).
 * Cada sentencia se cierra con una línea delimitadora propia en vez de
 * splitear por ";" al restaurar: un punto y coma dentro de un texto
 * (contenido de un post, por ejemplo) rompería un split ingenuo.
 *
 * Uso: wp --skip-themes eval-file scripts/db-export.php /tmp/salida.sql
 */

const RB_DUMP_STATEMENT_DELIMITER = '--RB-DUMP-STATEMENT-END--';

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

global $wpdb;

$outputPath = $args[0] ?? null;

if (! $outputPath) {
    echo "Falta la ruta de salida. Uso: wp eval-file scripts/db-export.php /tmp/salida.sql\n";
    exit(1);
}

$fh = fopen($outputPath, 'w');

if (! $fh) {
    echo "No se pudo abrir {$outputPath} para escritura.\n";
    exit(1);
}

fwrite($fh, "-- Backup generado con scripts/db-export.php el " . gmdate('Y-m-d H:i:s') . " UTC\n");
fwrite($fh, "SET NAMES utf8mb4;\n");
fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
fwrite($fh, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

$tables = $wpdb->get_col('SHOW TABLES');
$chunkSize = 500;
$totalRows = 0;

foreach ($tables as $table) {
    echo "Exportando {$table}...\n";

    fwrite($fh, "-- ----------------------------\n-- Tabla: {$table}\n-- ----------------------------\n");
    fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n" . RB_DUMP_STATEMENT_DELIMITER . "\n");

    $createRow = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
    fwrite($fh, $createRow[1] . ";\n" . RB_DUMP_STATEMENT_DELIMITER . "\n\n");

    $columns = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
    $columnList = '`' . implode('`, `', $columns) . '`';

    $rowCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");

    for ($offset = 0; $offset < $rowCount; $offset += $chunkSize) {
        $rows = $wpdb->get_results("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}", ARRAY_A);

        if (empty($rows)) {
            break;
        }

        $tuples = [];

        foreach ($rows as $row) {
            $values = [];

            foreach ($row as $value) {
                $values[] = $value === null
                    ? 'NULL'
                    : "'" . $wpdb->_real_escape($value) . "'";
            }

            $tuples[] = '(' . implode(', ', $values) . ')';
        }

        fwrite($fh, "INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $tuples) . ";\n" . RB_DUMP_STATEMENT_DELIMITER . "\n");
        $totalRows += count($rows);
    }

    fwrite($fh, "\n");
}

fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

$size = filesize($outputPath);
echo "\n{$totalRows} filas exportadas en " . count($tables) . " tablas.\n";
echo 'Archivo: ' . $outputPath . ' (' . round($size / 1024 / 1024, 2) . " MB)\n";
