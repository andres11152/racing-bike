<?php
/**
 * Importa reseñas reales (recopiladas por WhatsApp, redes, etc.) desde un CSV,
 * para que la tienda no arranque con la sección de reseñas vacía.
 *
 * Requiere el plugin racing-bike-reviews activo.
 *
 * Ejecutar:
 *   docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/import-reviews.php
 *
 * Por defecto lee /scripts/reviews-import.csv — ver reviews-import.example.csv
 * para el formato de columnas. Es idempotente: una fila ya importada (mismo
 * producto + autor + texto) no se duplica en corridas posteriores.
 *
 * Columnas esperadas (con encabezado):
 *   product_sku, rating, author, email, title, content, height_cm,
 *   size_bought, discipline, months_used, date, photo_url
 *
 * - date: formato YYYY-MM-DD. Si se omite, se usa la fecha actual.
 * - photo_url: una o más URLs separadas por "|". Se descargan y se
 *   adjuntan al producto. Puede omitirse.
 * - Las reseñas importadas quedan aprobadas y marcadas como "compra
 *   verificada": son un respaldo de reseñas reales ya vetadas a mano,
 *   no envíos anónimos del formulario público.
 */

if ( ! function_exists( 'rb_reviews_recalculate_rating' ) ) {
    WP_CLI::error( 'El plugin racing-bike-reviews no está activo.' );
}

$csv_path = getenv( 'RB_REVIEWS_CSV' ) ?: '/scripts/reviews-import.csv';

if ( ! file_exists( $csv_path ) ) {
    WP_CLI::error( "No se encontró el archivo CSV en {$csv_path}. Define RB_REVIEWS_CSV o coloca el archivo en esa ruta." );
}

$handle = fopen( $csv_path, 'r' );
if ( ! $handle ) {
    WP_CLI::error( "No se pudo abrir {$csv_path}." );
}

$header = fgetcsv( $handle );
if ( ! $header ) {
    WP_CLI::error( 'El CSV está vacío o no tiene encabezado.' );
}

$header = array_map( 'trim', $header );

$imported = 0;
$skipped  = 0;
$touched_products = array();
$row_num  = 1;

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function rb_import_row_to_assoc( array $header, array $row ): array
{
    $row = array_pad( $row, count( $header ), '' );
    return array_combine( $header, array_map( 'trim', $row ) );
}

function rb_import_hash( int $product_id, string $author, string $content ): string
{
    return md5( $product_id . '|' . $author . '|' . $content );
}

function rb_import_hash_exists( int $product_id, string $hash ): bool
{
    global $wpdb;

    $comment_id = $wpdb->get_var( $wpdb->prepare( "
        SELECT cm.comment_id
        FROM {$wpdb->commentmeta} cm
        INNER JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
        WHERE cm.meta_key = 'rb_import_hash'
          AND cm.meta_value = %s
          AND c.comment_post_ID = %d
        LIMIT 1
    ", $hash, $product_id ) );

    return (bool) $comment_id;
}

while ( ( $row = fgetcsv( $handle ) ) !== false ) {
    $row_num++;

    if ( count( array_filter( $row ) ) === 0 ) {
        continue; // Línea en blanco.
    }

    $data = rb_import_row_to_assoc( $header, $row );

    $sku = $data['product_sku'] ?? '';
    if ( ! $sku ) {
        WP_CLI::warning( "Fila {$row_num}: sin product_sku, se omite." );
        $skipped++;
        continue;
    }

    $product_id = wc_get_product_id_by_sku( $sku );
    if ( ! $product_id ) {
        WP_CLI::warning( "Fila {$row_num}: no existe un producto con SKU '{$sku}'." );
        $skipped++;
        continue;
    }

    $rating = isset( $data['rating'] ) ? absint( $data['rating'] ) : 0;
    $author = $data['author'] ?? '';
    $content = $data['content'] ?? '';

    if ( $rating < 1 || $rating > 5 || ! $author || strlen( $content ) < 5 ) {
        WP_CLI::warning( "Fila {$row_num}: rating, author o content inválidos, se omite." );
        $skipped++;
        continue;
    }

    $hash = rb_import_hash( $product_id, $author, $content );

    if ( rb_import_hash_exists( $product_id, $hash ) ) {
        $skipped++;
        continue; // Ya importada en una corrida anterior.
    }

    $email = $data['email'] ?? '';
    if ( ! is_email( $email ) ) {
        $email = sanitize_title( $author ) . '@clientes.racingbike1998.local';
    }

    $date = $data['date'] ?? '';
    $comment_date = $date && strtotime( $date ) ? date( 'Y-m-d H:i:s', strtotime( $date ) ) : current_time( 'mysql' );

    $comment_id = wp_insert_comment( array(
        'comment_post_ID'      => $product_id,
        'comment_author'       => $author,
        'comment_author_email' => $email,
        'comment_content'      => $content,
        'comment_type'         => 'review',
        'comment_approved'     => 1,
        'comment_date'         => $comment_date,
        'comment_date_gmt'     => get_gmt_from_date( $comment_date ),
    ) );

    if ( ! $comment_id ) {
        WP_CLI::warning( "Fila {$row_num}: no se pudo insertar la reseña." );
        $skipped++;
        continue;
    }

    update_comment_meta( $comment_id, 'rating', $rating );
    update_comment_meta( $comment_id, 'verified', 1 );
    update_comment_meta( $comment_id, 'rb_import_hash', $hash );

    if ( ! empty( $data['title'] ) ) {
        update_comment_meta( $comment_id, 'rb_title', sanitize_text_field( $data['title'] ) );
    }
    if ( ! empty( $data['height_cm'] ) ) {
        update_comment_meta( $comment_id, 'rb_height_cm', absint( $data['height_cm'] ) );
    }
    if ( ! empty( $data['size_bought'] ) ) {
        update_comment_meta( $comment_id, 'rb_size_bought', sanitize_text_field( $data['size_bought'] ) );
    }
    if ( ! empty( $data['discipline'] ) ) {
        update_comment_meta( $comment_id, 'rb_discipline', sanitize_text_field( $data['discipline'] ) );
    }
    if ( ! empty( $data['months_used'] ) ) {
        update_comment_meta( $comment_id, 'rb_months_used', absint( $data['months_used'] ) );
    }

    if ( ! empty( $data['photo_url'] ) ) {
        $urls = array_filter( array_map( 'trim', explode( '|', $data['photo_url'] ) ) );
        $attach_ids = array();

        foreach ( array_slice( $urls, 0, 3 ) as $url ) {
            $tmp_file = download_url( $url );

            if ( is_wp_error( $tmp_file ) ) {
                WP_CLI::warning( "Fila {$row_num}: no se pudo descargar {$url} — " . $tmp_file->get_error_message() );
                continue;
            }

            $file_array = array(
                'name'     => sanitize_file_name( basename( wp_parse_url( $url, PHP_URL_PATH ) ) ),
                'tmp_name' => $tmp_file,
            );

            $attach_id = media_handle_sideload( $file_array, $product_id );

            if ( is_wp_error( $attach_id ) ) {
                @unlink( $tmp_file );
                WP_CLI::warning( "Fila {$row_num}: no se pudo adjuntar la foto — " . $attach_id->get_error_message() );
                continue;
            }

            $attach_ids[] = $attach_id;
        }

        if ( $attach_ids ) {
            update_comment_meta( $comment_id, 'rb_photo_ids', implode( ',', $attach_ids ) );
        }
    }

    $touched_products[ $product_id ] = true;
    $imported++;
}

fclose( $handle );

foreach ( array_keys( $touched_products ) as $product_id ) {
    rb_reviews_recalculate_rating( (int) $product_id );
}

WP_CLI::success( "Importación completa: {$imported} reseñas nuevas, {$skipped} omitidas, " . count( $touched_products ) . ' productos con rating recalculado.' );
