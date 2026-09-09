<?php
/**
 * Plugin Name: Racing Bike Reviews
 * Description: Reseñas de clientes con foto y datos de ajuste (estatura, talla, disciplina) para la ficha de producto. Se apoya en el sistema nativo de reseñas de WooCommerce.
 * Version: 1.3.0
 * Author: Skycode Agency
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'RB_REVIEWS_VERSION', '1.3.0' );
define( 'RB_REVIEWS_DIR', plugin_dir_path( __FILE__ ) );
define( 'RB_REVIEWS_URL', plugins_url( '/', __FILE__ ) );

// -----------------------------------------------------------------------
// Activación — programa el cron de solicitud post-compra
// -----------------------------------------------------------------------

register_activation_hook( __FILE__, function () {
    if ( ! wp_next_scheduled( 'rb_reviews_solicit_cron' ) ) {
        wp_schedule_event( time(), 'daily', 'rb_reviews_solicit_cron' );
    }
} );

register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'rb_reviews_solicit_cron' );
} );

// -----------------------------------------------------------------------
// Assets
// -----------------------------------------------------------------------

function rb_reviews_register_assets() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }

    wp_enqueue_style(
        'rb-reviews-css',
        RB_REVIEWS_URL . 'assets/css/reviews.css',
        array(),
        RB_REVIEWS_VERSION
    );

    wp_enqueue_script(
        'rb-reviews-js',
        RB_REVIEWS_URL . 'assets/js/reviews.js',
        array(),
        RB_REVIEWS_VERSION,
        true
    );

    global $product;
    $product_id = $product instanceof WC_Product ? $product->get_id() : get_the_ID();

    wp_localize_script( 'rb-reviews-js', 'rbReviews', array(
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'rb_reviews_nonce' ),
        'productId' => $product_id,
        'autoOpen'  => isset( $_GET['rb_write_review'] ) && '1' === $_GET['rb_write_review'],
        // Del enlace del correo de solicitud post-compra — ver
        // rb_reviews_review_token(). Si están presentes, reviews.js los
        // reenvía con el formulario para que la reseña salga marcada como
        // compra verificada de forma verificable, no autodeclarada.
        'reviewOrder' => isset( $_GET['rb_order'] ) ? absint( $_GET['rb_order'] ) : 0,
        'reviewToken' => isset( $_GET['rb_token'] ) ? sanitize_text_field( wp_unslash( $_GET['rb_token'] ) ) : '',
        'i18n'      => array(
            'sending'      => __( 'Enviando…', 'racing-bike-reviews' ),
            'sent'         => __( '¡Gracias! Tu reseña quedó en revisión.', 'racing-bike-reviews' ),
            'error'        => __( 'No pudimos enviar tu reseña. Intenta de nuevo.', 'racing-bike-reviews' ),
            'maxPhotos'    => __( 'Máximo 3 fotos por reseña.', 'racing-bike-reviews' ),
            'loadingMore'  => __( 'Cargando…', 'racing-bike-reviews' ),
        ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'rb_reviews_register_assets' );

// -----------------------------------------------------------------------
// Cálculo de rating (compatible con WC_Product::get_average_rating())
// -----------------------------------------------------------------------

/**
 * WooCommerce cachea el promedio y el conteo en post meta del producto.
 * Como insertamos las reseñas con wp_insert_comment() en lugar del flujo
 * estándar de comment_post, esa caché no se actualiza sola: hay que
 * recalcularla nosotros en los mismos eventos (alta y cambio de estado).
 */
function rb_reviews_recalculate_rating( $product_id ) {
    $product_id = absint( $product_id );
    if ( ! $product_id ) {
        return;
    }

    global $wpdb;

    $results = $wpdb->get_row( $wpdb->prepare( "
        SELECT COUNT(*) AS cnt, AVG(cm.meta_value + 0) AS avg_rating
        FROM {$wpdb->comments} c
        INNER JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID AND cm.meta_key = 'rating'
        WHERE c.comment_post_ID = %d
          AND c.comment_approved = '1'
          AND c.comment_type = 'review'
    ", $product_id ) );

    $count   = $results ? (int) $results->cnt : 0;
    $average = $results && $results->avg_rating ? round( (float) $results->avg_rating, 2 ) : 0;

    update_post_meta( $product_id, '_wc_review_count', $count );
    update_post_meta( $product_id, '_wc_average_rating', $average );

    if ( function_exists( 'wc_delete_product_transients' ) ) {
        wc_delete_product_transients( $product_id );
    }

    delete_transient( 'rb_reviews_stats_' . $product_id );
    delete_transient( 'rb_reviews_photo_wall_10' );
}

function rb_reviews_on_status_change( $new_status, $old_status, $comment ) {
    if ( 'review' !== $comment->comment_type ) {
        return;
    }
    rb_reviews_recalculate_rating( $comment->comment_post_ID );
}
add_action( 'transition_comment_status', 'rb_reviews_on_status_change', 10, 3 );

function rb_reviews_on_comment_touch( $comment_id ) {
    $comment = get_comment( $comment_id );
    if ( $comment && 'review' === $comment->comment_type ) {
        rb_reviews_recalculate_rating( $comment->comment_post_ID );
    }
}
add_action( 'comment_post', 'rb_reviews_on_comment_touch' );
add_action( 'deleted_comment', 'rb_reviews_on_comment_touch' );
add_action( 'trashed_comment', 'rb_reviews_on_comment_touch' );

// -----------------------------------------------------------------------
// Estadísticas y listado
// -----------------------------------------------------------------------

/**
 * @return array{count:int,average:float,distribution:array<int,int>,photo_count:int}
 */
function rb_reviews_get_stats( $product_id ) {
    $product_id = absint( $product_id );
    $cache_key  = 'rb_reviews_stats_' . $product_id;
    $cached     = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $comments = get_comments( array(
        'post_id' => $product_id,
        'type'    => 'review',
        'status'  => 'approve',
    ) );

    $distribution = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
    $photo_count  = 0;
    $sum          = 0;
    $count        = 0;

    foreach ( $comments as $comment ) {
        $rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
        if ( $rating >= 1 && $rating <= 5 ) {
            $distribution[ $rating ]++;
            $sum += $rating;
            $count++;
        }

        if ( get_comment_meta( $comment->comment_ID, 'rb_photo_ids', true ) ) {
            $photo_count++;
        }
    }

    $stats = array(
        'count'        => $count,
        'average'      => $count ? round( $sum / $count, 1 ) : 0,
        'distribution' => $distribution,
        'photo_count'  => $photo_count,
    );

    set_transient( $cache_key, $stats, DAY_IN_SECONDS );

    return $stats;
}

/**
 * @return array{items:WP_Comment[],total:int}
 */
function rb_reviews_get_list( $product_id, $args = array() ) {
    $defaults = array(
        'offset'       => 0,
        'per_page'     => 5,
        'photos_only'  => false,
    );
    $args = wp_parse_args( $args, $defaults );

    // Sólo los filtros van aquí — number/offset se añaden por separado para
    // el lote y de nuevo, sin ellos, para el conteo. Antes esta función traía
    // *todas* las reseñas aprobadas del producto y recortaba la página con
    // array_slice() en PHP: con cientos de reseñas, "Cargar más" pedía la
    // tabla completa a la base de datos sólo para devolver 5. Ahora LIMIT/
    // OFFSET y COUNT(*) los hace SQL.
    $filter_args = array(
        'post_id' => absint( $product_id ),
        'type'    => 'review',
        'status'  => 'approve',
    );

    if ( $args['photos_only'] ) {
        $filter_args['meta_query'] = array(
            array(
                'key'     => 'rb_photo_ids',
                'compare' => 'EXISTS',
            ),
        );
    }

    $items = get_comments( array_merge( $filter_args, array(
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
        'number'  => absint( $args['per_page'] ),
        'offset'  => absint( $args['offset'] ),
    ) ) );

    // En la primera página, si el admin destacó una reseña, sube al frente
    // dentro del lote ya traído — no vale la pena una consulta aparte sólo
    // para adelantar una reseña que casi siempre ya está entre las recientes.
    if ( 0 === absint( $args['offset'] ) && count( $items ) > 1 ) {
        usort( $items, function ( $a, $b ) {
            $featured_a = (int) get_comment_meta( $a->comment_ID, 'rb_featured', true );
            $featured_b = (int) get_comment_meta( $b->comment_ID, 'rb_featured', true );
            return $featured_b <=> $featured_a;
        } );
    }

    $total = (int) get_comments( array_merge( $filter_args, array( 'count' => true ) ) );

    return array( 'items' => $items, 'total' => $total );
}

// -----------------------------------------------------------------------
// Render — sección completa de reseñas (llamada desde el theme)
// -----------------------------------------------------------------------

function rb_reviews_discipline_label( $key ) {
    $labels = array(
        'road'   => __( 'Ruta', 'racing-bike-reviews' ),
        'mtb'    => __( 'Montaña', 'racing-bike-reviews' ),
        'gravel' => __( 'Gravel', 'racing-bike-reviews' ),
        'urban'  => __( 'Urbana', 'racing-bike-reviews' ),
    );
    return $labels[ $key ] ?? '';
}

function rb_reviews_render_stars( $value, $size = 'size-3.5' ) {
    $value = round( (float) $value );
    $out   = '<div class="rb-reviews-stars" aria-hidden="true">';
    for ( $i = 1; $i <= 5; $i++ ) {
        $filled = $i <= $value ? 'is-filled' : '';
        $out   .= '<span class="rb-reviews-star ' . $filled . '">★</span>';
    }
    $out .= '</div>';
    return $out;
}

function rb_reviews_render_review_item( $comment ) {
    $rating      = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
    $verified    = (int) get_comment_meta( $comment->comment_ID, 'verified', true );
    $height      = get_comment_meta( $comment->comment_ID, 'rb_height_cm', true );
    $size_bought = get_comment_meta( $comment->comment_ID, 'rb_size_bought', true );
    $discipline  = get_comment_meta( $comment->comment_ID, 'rb_discipline', true );
    $months_used = get_comment_meta( $comment->comment_ID, 'rb_months_used', true );
    $photo_ids   = get_comment_meta( $comment->comment_ID, 'rb_photo_ids', true );
    $photo_ids   = $photo_ids ? array_filter( array_map( 'absint', explode( ',', $photo_ids ) ) ) : array();
    $title       = get_comment_meta( $comment->comment_ID, 'rb_title', true );

    ob_start();
    ?>
    <article class="rb-review-item" data-carousel-slide>
        <header class="rb-review-item__head">
            <?php echo rb_reviews_render_stars( $rating ); ?>
            <span class="rb-review-item__author">
                <?php echo esc_html( $comment->comment_author ); ?>
                <?php if ( $verified ) : ?>
                    <span class="rb-review-badge"><?php esc_html_e( 'Compra verificada', 'racing-bike-reviews' ); ?></span>
                <?php endif; ?>
            </span>
            <time class="rb-review-item__date" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>">
                <?php echo esc_html( get_comment_date( 'j M Y', $comment ) ); ?>
            </time>
        </header>

        <?php if ( $title ) : ?>
            <h4 class="rb-review-item__title"><?php echo esc_html( $title ); ?></h4>
        <?php endif; ?>

        <?php if ( $height || $size_bought || $discipline || $months_used ) : ?>
            <ul class="rb-review-item__fit">
                <?php if ( $height ) : ?>
                    <li><?php printf( esc_html__( 'Mide %s cm', 'racing-bike-reviews' ), esc_html( $height ) ); ?></li>
                <?php endif; ?>
                <?php if ( $size_bought ) : ?>
                    <li><?php printf( esc_html__( 'Talla %s', 'racing-bike-reviews' ), esc_html( strtoupper( $size_bought ) ) ); ?></li>
                <?php endif; ?>
                <?php if ( $discipline ) : ?>
                    <li><?php echo esc_html( rb_reviews_discipline_label( $discipline ) ); ?></li>
                <?php endif; ?>
                <?php if ( $months_used ) : ?>
                    <li><?php printf( esc_html__( '%s meses de uso', 'racing-bike-reviews' ), esc_html( $months_used ) ); ?></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <p class="rb-review-item__text" data-rb-review-text><?php echo esc_html( $comment->comment_content ); ?></p>

        <?php
        // El texto recortado a 4 líneas (ver reviews.css) sigue completo aquí
        // arriba, accesible a lectores de pantalla; este botón es lo que lo
        // hace también accesible a quien lee con los ojos, que si no, no
        // tenía forma de ver la reseña completa.
        if ( mb_strlen( $comment->comment_content ) > 220 ) :
        ?>
            <button type="button" class="rb-review-item__more" data-rb-read-more data-more-label="<?php esc_attr_e( 'Leer más', 'racing-bike-reviews' ); ?>" data-less-label="<?php esc_attr_e( 'Leer menos', 'racing-bike-reviews' ); ?>">
                <?php esc_html_e( 'Leer más', 'racing-bike-reviews' ); ?>
            </button>
        <?php endif; ?>

        <?php if ( $photo_ids ) : ?>
            <div class="rb-review-item__photos">
                <?php foreach ( $photo_ids as $photo_id ) :
                    $thumb = wp_get_attachment_image_url( $photo_id, 'thumbnail' );
                    $full  = wp_get_attachment_image_url( $photo_id, 'large' );
                    if ( ! $thumb ) {
                        continue;
                    }
                    ?>
                    <button type="button" class="rb-review-item__photo" data-rb-lightbox="<?php echo esc_url( $full ?: $thumb ); ?>">
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php esc_attr_e( 'Foto de cliente', 'racing-bike-reviews' ); ?>" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
    return ob_get_clean();
}

function rb_reviews_render_section( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $product_id  = $product->get_id();
    $stats       = rb_reviews_get_stats( $product_id );
    $list        = rb_reviews_get_list( $product_id, array( 'per_page' => 5 ) );
    $sizes       = array();

    if ( $product->is_type( 'variable' ) ) {
        $available = $product->get_available_variations();
        foreach ( $available as $variation ) {
            $value = $variation['attributes']['attribute_pa_talla-cuadro'] ?? null;
            if ( $value && ! in_array( strtoupper( $value ), $sizes, true ) ) {
                $sizes[] = strtoupper( $value );
            }
        }
    }

    ob_start();
    ?>
    <section class="rb-reviews" id="rb-reviews" data-rb-reviews data-product-id="<?php echo esc_attr( $product_id ); ?>">
        <div class="rb-reviews__header">
            <div>
                <h2 class="rb-reviews__heading"><?php esc_html_e( 'Reseñas de clientes', 'racing-bike-reviews' ); ?></h2>

                <?php if ( $stats['count'] > 0 ) : ?>
                    <div class="rb-reviews__summary">
                        <span class="rb-reviews__average"><?php echo esc_html( number_format_i18n( $stats['average'], 1 ) ); ?></span>
                        <div>
                            <?php echo rb_reviews_render_stars( $stats['average'] ); ?>
                            <p class="rb-reviews__count">
                                <?php printf(
                                    esc_html( _n( '%d reseña', '%d reseñas', $stats['count'], 'racing-bike-reviews' ) ),
                                    (int) $stats['count']
                                ); ?>
                                <?php if ( $stats['photo_count'] > 0 ) : ?>
                                    &middot;
                                    <?php printf(
                                        esc_html( _n( '%d con foto', '%d con foto', $stats['photo_count'], 'racing-bike-reviews' ) ),
                                        (int) $stats['photo_count']
                                    ); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="rb-reviews__distribution">
                        <?php for ( $i = 5; $i >= 1; $i-- ) :
                            $n   = $stats['distribution'][ $i ] ?? 0;
                            $pct = $stats['count'] ? round( ( $n / $stats['count'] ) * 100 ) : 0;
                            ?>
                            <div class="rb-reviews__bar-row">
                                <span><?php echo esc_html( $i ); ?>★</span>
                                <span class="rb-reviews__bar-track"><span class="rb-reviews__bar-fill" style="width: <?php echo esc_attr( $pct ); ?>%"></span></span>
                                <span class="rb-reviews__bar-count"><?php echo esc_html( $n ); ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php else : ?>
                    <p class="rb-reviews__empty"><?php esc_html_e( 'Todavía no hay reseñas. Sé el primero en compartir tu experiencia.', 'racing-bike-reviews' ); ?></p>
                <?php endif; ?>
            </div>

            <div class="rb-reviews__actions">
                <?php if ( $stats['photo_count'] > 0 ) : ?>
                    <button type="button" class="rb-reviews__filter" data-rb-filter-photos>
                        <?php esc_html_e( 'Ver solo con foto', 'racing-bike-reviews' ); ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="rb-reviews__cta" data-rb-open-form>
                    <?php esc_html_e( 'Escribir una reseña', 'racing-bike-reviews' ); ?>
                </button>
            </div>
        </div>

        <?php if ( $list['items'] ) : ?>
            <div
                class="rb-reviews__carousel"
                data-carousel
                data-rb-reviews-carousel
                data-autoplay="true"
                data-interval="7000"
                data-ga4-context-id="product_reviews_<?php echo esc_attr( $product_id ); ?>"
                role="region"
                aria-roledescription="<?php esc_attr_e( 'carrusel', 'racing-bike-reviews' ); ?>"
                aria-label="<?php esc_attr_e( 'Reseñas de clientes', 'racing-bike-reviews' ); ?>"
            >
                <div class="rb-reviews__carousel-live" aria-live="polite" aria-atomic="true" data-carousel-live></div>

                <div class="rb-reviews__list" data-carousel-track data-rb-reviews-list>
                    <?php foreach ( $list['items'] as $comment ) : ?>
                        <?php echo rb_reviews_render_review_item( $comment ); ?>
                    <?php endforeach; ?>
                </div>

                <?php if ( count( $list['items'] ) > 1 ) : ?>
                    <button type="button" class="rb-reviews__carousel-arrow rb-reviews__carousel-arrow--prev" data-carousel-prev aria-label="<?php esc_attr_e( 'Anterior', 'racing-bike-reviews' ); ?>">
                        <span aria-hidden="true">&#8249;</span>
                    </button>
                    <button type="button" class="rb-reviews__carousel-arrow rb-reviews__carousel-arrow--next" data-carousel-next aria-label="<?php esc_attr_e( 'Siguiente', 'racing-bike-reviews' ); ?>">
                        <span aria-hidden="true">&#8250;</span>
                    </button>

                    <div class="rb-reviews__carousel-controls">
                        <div class="rb-reviews__dots" data-carousel-dots aria-label="<?php esc_attr_e( 'Ir a la reseña', 'racing-bike-reviews' ); ?>"></div>

                        <button
                            type="button"
                            class="rb-reviews__carousel-pause"
                            data-carousel-pause
                            data-pause-label="<?php esc_attr_e( 'Pausar carrusel', 'racing-bike-reviews' ); ?>"
                            data-resume-label="<?php esc_attr_e( 'Reanudar carrusel', 'racing-bike-reviews' ); ?>"
                            aria-label="<?php esc_attr_e( 'Pausar carrusel', 'racing-bike-reviews' ); ?>"
                        >
                            <span class="rb-reviews__carousel-pause-icon" data-carousel-pause-icon></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $list['total'] > count( $list['items'] ) ) : ?>
            <div class="rb-reviews__load-more-wrap">
                <button type="button" class="rb-reviews__load-more" data-rb-load-more data-offset="<?php echo esc_attr( count( $list['items'] ) ); ?>">
                    <?php esc_html_e( 'Cargar más reseñas', 'racing-bike-reviews' ); ?>
                </button>
            </div>
        <?php endif; ?>

        <?php echo rb_reviews_render_form_modal( $product, $sizes ); ?>
        <div class="rb-reviews__lightbox" data-rb-lightbox-overlay hidden>
            <button type="button" class="rb-reviews__lightbox-close" data-rb-lightbox-close aria-label="<?php esc_attr_e( 'Cerrar', 'racing-bike-reviews' ); ?>">&times;</button>
            <button type="button" class="rb-reviews__lightbox-nav rb-reviews__lightbox-nav--prev" data-rb-lightbox-prev aria-label="<?php esc_attr_e( 'Foto anterior', 'racing-bike-reviews' ); ?>">&#8249;</button>
            <img src="" alt="" data-rb-lightbox-img>
            <button type="button" class="rb-reviews__lightbox-nav rb-reviews__lightbox-nav--next" data-rb-lightbox-next aria-label="<?php esc_attr_e( 'Foto siguiente', 'racing-bike-reviews' ); ?>">&#8250;</button>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function rb_reviews_render_form_modal( $product, $sizes ) {
    ob_start();
    ?>
    <div class="rb-reviews__modal-overlay" data-rb-reviews-modal hidden>
        <div class="rb-reviews__modal" role="dialog" aria-modal="true" aria-labelledby="rb-reviews-modal-title">
            <button type="button" class="rb-reviews__modal-close" data-rb-close-form aria-label="<?php esc_attr_e( 'Cerrar', 'racing-bike-reviews' ); ?>">&times;</button>

            <h3 id="rb-reviews-modal-title" class="rb-reviews__modal-title"><?php esc_html_e( 'Cuéntanos tu experiencia', 'racing-bike-reviews' ); ?></h3>

            <form data-rb-review-form novalidate>
                <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
                <input type="text" name="rb_website" class="rb-reviews__honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

                <div class="rb-form-field">
                    <label><?php esc_html_e( 'Tu calificación', 'racing-bike-reviews' ); ?></label>
                    <div class="rb-reviews__rating-input" data-rb-rating-input>
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <button type="button" class="rb-reviews__rating-star" data-value="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Calificar con %d de 5 estrellas', 'racing-bike-reviews' ), $i ) ); ?>">★</button>
                        <?php endfor; ?>
                        <input type="hidden" name="rating" value="0">
                    </div>
                </div>

                <div class="rb-form-field">
                    <label for="rb-review-title"><?php esc_html_e( 'Título (opcional)', 'racing-bike-reviews' ); ?></label>
                    <input type="text" id="rb-review-title" name="title" maxlength="80">
                </div>

                <div class="rb-form-field">
                    <label for="rb-review-text"><?php esc_html_e( 'Tu reseña', 'racing-bike-reviews' ); ?></label>
                    <textarea id="rb-review-text" name="content" rows="4" required minlength="10" maxlength="2000"></textarea>
                </div>

                <div class="rb-form-grid">
                    <div class="rb-form-field">
                        <label for="rb-review-height"><?php esc_html_e( 'Tu estatura (cm)', 'racing-bike-reviews' ); ?></label>
                        <input type="number" id="rb-review-height" name="height_cm" min="120" max="220">
                    </div>

                    <div class="rb-form-field">
                        <label for="rb-review-size"><?php esc_html_e( 'Talla que compraste', 'racing-bike-reviews' ); ?></label>
                        <?php if ( $sizes ) : ?>
                            <select id="rb-review-size" name="size_bought">
                                <option value=""><?php esc_html_e( 'Selecciona', 'racing-bike-reviews' ); ?></option>
                                <?php foreach ( $sizes as $size ) : ?>
                                    <option value="<?php echo esc_attr( $size ); ?>"><?php echo esc_html( $size ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="text" id="rb-review-size" name="size_bought" maxlength="10">
                        <?php endif; ?>
                    </div>

                    <div class="rb-form-field">
                        <label for="rb-review-discipline"><?php esc_html_e( 'Disciplina', 'racing-bike-reviews' ); ?></label>
                        <select id="rb-review-discipline" name="discipline">
                            <option value=""><?php esc_html_e( 'Selecciona', 'racing-bike-reviews' ); ?></option>
                            <option value="road"><?php esc_html_e( 'Ruta', 'racing-bike-reviews' ); ?></option>
                            <option value="mtb"><?php esc_html_e( 'Montaña', 'racing-bike-reviews' ); ?></option>
                            <option value="gravel"><?php esc_html_e( 'Gravel', 'racing-bike-reviews' ); ?></option>
                            <option value="urban"><?php esc_html_e( 'Urbana', 'racing-bike-reviews' ); ?></option>
                        </select>
                    </div>

                    <div class="rb-form-field">
                        <label for="rb-review-months"><?php esc_html_e( 'Meses de uso', 'racing-bike-reviews' ); ?></label>
                        <input type="number" id="rb-review-months" name="months_used" min="0" max="240">
                    </div>
                </div>

                <div class="rb-form-field">
                    <label id="rb-review-photos-label"><?php esc_html_e( 'Fotos (hasta 3)', 'racing-bike-reviews' ); ?></label>

                    <?php
                    // El texto "Choose Files / No file chosen" de un <input type="file">
                    // nativo lo pinta el navegador según su propio idioma — no hay CSS
                    // que lo traduzca. Se oculta el input (sigue accesible: cubre toda
                    // la zona, recibe foco y Enter/clic lo abren igual) y se construye
                    // encima una zona de arrastrar-y-soltar con texto propio en español.
                    ?>
                    <div class="rb-reviews__dropzone" data-rb-dropzone>
                        <input
                            type="file"
                            id="rb-review-photos"
                            name="photos[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            data-rb-photo-input
                            aria-labelledby="rb-review-photos-label"
                        >
                        <svg class="rb-reviews__dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M4 16.5v2.25A2.25 2.25 0 0 0 6.25 21h11.5A2.25 2.25 0 0 0 20 18.75V16.5M16 8l-4-4m0 0L8 8m4-4v12" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p class="rb-reviews__dropzone-text" data-rb-dropzone-text>
                            <strong><?php esc_html_e( 'Arrastra tus fotos aquí', 'racing-bike-reviews' ); ?></strong>
                            <?php esc_html_e( 'o haz clic para elegir', 'racing-bike-reviews' ); ?>
                        </p>
                        <p class="rb-reviews__dropzone-hint" data-rb-dropzone-hint>
                            <?php esc_html_e( 'JPG, PNG o WEBP · hasta 3 fotos', 'racing-bike-reviews' ); ?>
                        </p>
                    </div>

                    <div class="rb-reviews__photo-preview" data-rb-photo-preview></div>
                </div>

                <?php if ( ! is_user_logged_in() ) : ?>
                    <div class="rb-form-grid">
                        <div class="rb-form-field">
                            <label for="rb-review-name"><?php esc_html_e( 'Tu nombre', 'racing-bike-reviews' ); ?></label>
                            <input type="text" id="rb-review-name" name="author" required maxlength="60">
                        </div>
                        <div class="rb-form-field">
                            <label for="rb-review-email"><?php esc_html_e( 'Tu correo', 'racing-bike-reviews' ); ?></label>
                            <input type="email" id="rb-review-email" name="email" required maxlength="100">
                        </div>
                    </div>
                <?php endif; ?>

                <p class="rb-reviews__form-note"><?php esc_html_e( 'Tu reseña se publica luego de una breve revisión.', 'racing-bike-reviews' ); ?></p>

                <button type="submit" class="rb-reviews__submit" data-rb-submit-btn>
                    <?php esc_html_e( 'Enviar reseña', 'racing-bike-reviews' ); ?>
                </button>

                <p class="rb-reviews__form-status" data-rb-form-status role="status" aria-live="polite"></p>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// -----------------------------------------------------------------------
// AJAX — envío de reseña
// -----------------------------------------------------------------------

/**
 * IP del visitante. `REMOTE_ADDR` es la del último salto — si el sitio corre
 * detrás de Cloudflare (terminando TLS ahí, como suele configurarse un sitio
 * WooCommerce en producción), esa IP es la del propio Cloudflare y no la del
 * visitante, lo que deja el rate-limit por IP inútil: todo el tráfico
 * comparte la misma clave. `CF-Connecting-IP` no se puede falsificar cuando
 * Cloudflare sí está delante — lo pone él, no el cliente. No se usa
 * `X-Forwarded-For` a secas: ese sí lo controla el cliente y sería una forma
 * trivial de burlar el límite.
 */
function rb_reviews_client_ip() {
    if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
        return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
    }

    return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

function rb_reviews_rate_limited( $product_id ) {
    $ip  = rb_reviews_client_ip();
    $key = 'rb_reviews_rl_' . md5( $ip . '_' . $product_id );

    if ( get_transient( $key ) ) {
        return true;
    }

    set_transient( $key, 1, 10 * MINUTE_IN_SECONDS );
    return false;
}

/**
 * Tope aparte, por IP sola sin importar el producto: el límite de arriba es
 * por producto, así que nada impedía antes dejar una reseña en veinte
 * productos distintos en un minuto.
 */
function rb_reviews_globally_rate_limited() {
    $ip    = rb_reviews_client_ip();
    $key   = 'rb_reviews_grl_' . md5( $ip );
    $count = (int) get_transient( $key );

    if ( $count >= 3 ) {
        return true;
    }

    set_transient( $key, $count + 1, HOUR_IN_SECONDS );
    return false;
}

/**
 * Quita metadatos EXIF (incluida la ubicación GPS, si el teléfono la grabó)
 * de la foto ya subida. Los tamaños intermedios que genera WordPress
 * normalmente ya salen limpios al recodificarse, pero el archivo original
 * subido se sirve tal cual desde su propia URL — sin esto, la ubicación del
 * cliente podía quedar accesible ahí aunque nunca se vea en la miniatura.
 */
function rb_reviews_strip_exif( $file_path, $mime_type ) {
    if ( 'image/webp' === $mime_type ) {
        return; // WebP rara vez lleva GPS y wp_get_image_editor falla en algunos hosts para este formato.
    }

    if ( class_exists( 'Imagick' ) ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->stripImage();
            $imagick->writeImage( $file_path );
            $imagick->destroy();
            return;
        } catch ( \Exception $e ) {
            // Si Imagick falla con este archivo, se intenta con el editor de WP más abajo.
        }
    }

    // Reescribir con el editor de imagen de WP fuerza una recodificación
    // completa — con GD (lo más común en hosting compartido), eso descarta
    // el EXIF por sí solo.
    $editor = wp_get_image_editor( $file_path );
    if ( ! is_wp_error( $editor ) ) {
        $editor->save( $file_path );
    }
}

function rb_reviews_handle_photo_uploads( $product_id, $comment_id ) {
    if ( empty( $_FILES['photos'] ) ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $allowed_mimes = array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
    $max_size      = 5 * MB_IN_BYTES;
    $files         = $_FILES['photos'];
    $count         = is_array( $files['name'] ) ? count( $files['name'] ) : 0;
    $ids           = array();

    for ( $i = 0; $i < $count && $i < 3; $i++ ) {
        if ( empty( $files['name'][ $i ] ) || UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
            continue;
        }

        if ( $files['size'][ $i ] > $max_size ) {
            continue;
        }

        $file_type = wp_check_filetype( $files['name'][ $i ], $allowed_mimes );
        if ( ! $file_type['ext'] ) {
            continue;
        }

        $file = array(
            'name'     => $files['name'][ $i ],
            'type'     => $files['type'][ $i ],
            'tmp_name' => $files['tmp_name'][ $i ],
            'error'    => $files['error'][ $i ],
            'size'     => $files['size'][ $i ],
        );

        $overrides = array( 'test_form' => false, 'mimes' => $allowed_mimes );
        $uploaded  = wp_handle_upload( $file, $overrides );

        if ( isset( $uploaded['error'] ) ) {
            continue;
        }

        rb_reviews_strip_exif( $uploaded['file'], $uploaded['type'] );

        $attachment = array(
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name( $files['name'][ $i ] ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_parent'    => $product_id,
        );

        $attach_id = wp_insert_attachment( $attachment, $uploaded['file'], $product_id );

        if ( ! is_wp_error( $attach_id ) && $attach_id ) {
            $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            update_comment_meta( $comment_id, 'rb_photo_source', $comment_id );
            $ids[] = $attach_id;
        }
    }

    if ( $ids ) {
        update_comment_meta( $comment_id, 'rb_photo_ids', implode( ',', $ids ) );
    }
}

/**
 * Token que va en el enlace del email de solicitud post-compra (ver
 * rb_reviews_build_items_html()). Con esto, marcar una reseña como
 * "compra verificada" deja de depender de creerle al remitente el correo
 * que escribió en el formulario — sólo quien de verdad recibió el correo del
 * pedido puede haber llegado con un token que valide.
 */
function rb_reviews_review_token( $order_id, $product_id ) {
    return substr( wp_hash( 'rb_review_token_' . $order_id . '_' . $product_id ), 0, 20 );
}

/**
 * Corre el contenido por Akismet, si está instalado, sin pasar por todo el
 * pipeline de wp_new_comment() (que este plugin evita a propósito: el envío
 * es por AJAX, con su propio formulario y validación). `preprocess_comment`
 * es el mismo filtro que Akismet engancha para decidir; invocarlo a mano es
 * la forma soportada de usarlo fuera del flujo estándar de comentarios.
 */
function rb_reviews_is_spam( $commentdata ) {
    if ( ! class_exists( 'Akismet' ) ) {
        return false;
    }

    $checked = apply_filters( 'preprocess_comment', $commentdata );

    return isset( $checked['akismet_result'] ) && 'true' === $checked['akismet_result'];
}

function rb_reviews_ajax_submit() {
    check_ajax_referer( 'rb_reviews_nonce', 'nonce' );

    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $rating     = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
    $content    = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
    $title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
    $honeypot   = isset( $_POST['rb_website'] ) ? trim( wp_unslash( $_POST['rb_website'] ) ) : '';

    if ( '' !== $honeypot ) {
        wp_send_json_error( array( 'message' => __( 'Error de validación.', 'racing-bike-reviews' ) ) );
    }

    $product = $product_id ? wc_get_product( $product_id ) : null;

    if ( ! $product || $rating < 1 || $rating > 5 || strlen( $content ) < 10 ) {
        wp_send_json_error( array( 'message' => __( 'Revisa los campos obligatorios.', 'racing-bike-reviews' ) ) );
    }

    if ( rb_reviews_rate_limited( $product_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Ya enviaste una reseña hace poco. Intenta más tarde.', 'racing-bike-reviews' ) ) );
    }

    if ( rb_reviews_globally_rate_limited() ) {
        wp_send_json_error( array( 'message' => __( 'Ya enviaste varias reseñas en poco tiempo. Intenta más tarde.', 'racing-bike-reviews' ) ) );
    }

    if ( is_user_logged_in() ) {
        $user       = wp_get_current_user();
        $author     = $user->display_name;
        $email      = $user->user_email;
        $user_id    = $user->ID;
    } else {
        $author  = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '';
        $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $user_id = 0;

        if ( ! $author || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Ingresa tu nombre y un correo válido.', 'racing-bike-reviews' ) ) );
        }
    }

    $client_ip = rb_reviews_client_ip();
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

    $commentdata = array(
        'comment_post_ID'      => $product_id,
        'comment_author'       => $author,
        'comment_author_email' => $email,
        'comment_author_url'   => '',
        'comment_content'      => $content,
        'comment_type'         => 'review',
        'comment_approved'     => 0,
        'user_id'              => $user_id,
        'comment_author_IP'    => $client_ip,
        'comment_agent'        => $user_agent,
    );

    // Si Akismet lo marca spam, igual se guarda (para no perder falsos
    // positivos sin dejar rastro) pero directo a la papelera de spam de
    // WordPress, sin entrar a la cola de moderación normal — y al
    // remitente se le responde el mismo mensaje de éxito de siempre, para
    // no confirmarle a un spammer que lo detectamos.
    if ( rb_reviews_is_spam( $commentdata ) ) {
        $commentdata['comment_approved'] = 'spam';
    }

    $is_spam = 'spam' === $commentdata['comment_approved'];

    $comment_id = wp_insert_comment( $commentdata );

    if ( ! $comment_id ) {
        wp_send_json_error( array( 'message' => __( 'No se pudo guardar la reseña.', 'racing-bike-reviews' ) ) );
    }

    update_comment_meta( $comment_id, 'rating', $rating );

    // "Compra verificada" con evidencia real: el enlace del correo de
    // solicitud post-compra trae un token firmado por pedido y producto
    // (ver rb_reviews_review_token()) que sólo pudo llegar a quien recibió
    // ese correo. Si el envío viene por ahí y el pedido calza con el correo
    // y el producto, el sello es imposible de falsificar. Si no — el botón
    // "Escribir una reseña" normal, sin ese contexto — se cae al heurístico
    // anterior (coincide con el historial de compras), que sigue siendo una
    // señal razonable pero no a prueba de que alguien escriba el correo de
    // otra persona.
    $order_id       = isset( $_POST['rb_order'] ) ? absint( $_POST['rb_order'] ) : 0;
    $token          = isset( $_POST['rb_token'] ) ? sanitize_text_field( wp_unslash( $_POST['rb_token'] ) ) : '';
    $token_verified = false;

    if ( $order_id && $token && hash_equals( rb_reviews_review_token( $order_id, $product_id ), $token ) ) {
        $order = wc_get_order( $order_id );

        if ( $order && strtolower( $order->get_billing_email() ) === strtolower( $email ) ) {
            foreach ( $order->get_items() as $order_item ) {
                if ( (int) $order_item->get_product_id() === $product_id || (int) $order_item->get_variation_id() === $product_id ) {
                    $token_verified = true;
                    break;
                }
            }
        }
    }

    $verified = $token_verified || ( function_exists( 'wc_customer_bought_product' ) && wc_customer_bought_product( $email, $user_id, $product_id ) );
    update_comment_meta( $comment_id, 'verified', $verified ? 1 : 0 );

    if ( $title ) {
        update_comment_meta( $comment_id, 'rb_title', $title );
    }

    $height     = isset( $_POST['height_cm'] ) ? absint( $_POST['height_cm'] ) : 0;
    $size       = isset( $_POST['size_bought'] ) ? sanitize_text_field( wp_unslash( $_POST['size_bought'] ) ) : '';
    $discipline = isset( $_POST['discipline'] ) ? sanitize_text_field( wp_unslash( $_POST['discipline'] ) ) : '';
    $months     = isset( $_POST['months_used'] ) ? absint( $_POST['months_used'] ) : 0;

    if ( $height ) {
        update_comment_meta( $comment_id, 'rb_height_cm', $height );
    }
    if ( $size ) {
        update_comment_meta( $comment_id, 'rb_size_bought', $size );
    }
    if ( $discipline ) {
        update_comment_meta( $comment_id, 'rb_discipline', $discipline );
    }
    if ( $months ) {
        update_comment_meta( $comment_id, 'rb_months_used', $months );
    }

    // Sin fotos para lo que ya se marcó como spam: no vale la pena procesar
    // ni dejar servido el archivo subido para una entrada que no se va a mostrar.
    if ( ! $is_spam ) {
        rb_reviews_handle_photo_uploads( $product_id, $comment_id );
    }

    // wp_insert_comment() no dispara el correo de "tienes un comentario para
    // moderar" que sí manda wp_new_comment() — sin este aviso manual, el
    // administrador de la tienda no se entera de que hay una reseña
    // esperando a menos que entre a revisar la pantalla a mano.
    if ( ! $is_spam ) {
        wp_new_comment_notify_moderator( $comment_id );
    }

    wp_send_json_success( array(
        'message' => __( '¡Gracias! Tu reseña quedó en revisión.', 'racing-bike-reviews' ),
    ) );
}
add_action( 'wp_ajax_rb_submit_review', 'rb_reviews_ajax_submit' );
add_action( 'wp_ajax_nopriv_rb_submit_review', 'rb_reviews_ajax_submit' );

// -----------------------------------------------------------------------
// AJAX — cargar más reseñas
// -----------------------------------------------------------------------

function rb_reviews_ajax_load_more() {
    check_ajax_referer( 'rb_reviews_nonce', 'nonce' );

    $product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $offset      = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
    $photos_only = ! empty( $_POST['photos_only'] );

    if ( ! $product_id ) {
        wp_send_json_error();
    }

    $list = rb_reviews_get_list( $product_id, array(
        'offset'      => $offset,
        'per_page'    => 5,
        'photos_only' => $photos_only,
    ) );

    $html = '';
    foreach ( $list['items'] as $comment ) {
        $html .= rb_reviews_render_review_item( $comment );
    }

    wp_send_json_success( array(
        'html'    => $html,
        'hasMore' => ( $offset + count( $list['items'] ) ) < $list['total'],
    ) );
}
add_action( 'wp_ajax_rb_load_reviews', 'rb_reviews_ajax_load_more' );
add_action( 'wp_ajax_nopriv_rb_load_reviews', 'rb_reviews_ajax_load_more' );

// -----------------------------------------------------------------------
// SEO — extender los datos estructurados de producto que ya emite WooCommerce
// -----------------------------------------------------------------------

function rb_reviews_structured_data( $markup, $product ) {
    $stats = rb_reviews_get_stats( $product->get_id() );

    if ( $stats['count'] < 1 ) {
        return $markup;
    }

    $markup['aggregateRating'] = array(
        '@type'       => 'AggregateRating',
        'ratingValue' => $stats['average'],
        'reviewCount' => $stats['count'],
    );

    $list = rb_reviews_get_list( $product->get_id(), array( 'per_page' => 10 ) );
    $reviews = array();

    foreach ( $list['items'] as $comment ) {
        $rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
        if ( ! $rating ) {
            continue;
        }

        $reviews[] = array(
            '@type'         => 'Review',
            'reviewRating'  => array(
                '@type'       => 'Rating',
                'ratingValue' => $rating,
            ),
            'author'        => array(
                '@type' => 'Person',
                'name'  => $comment->comment_author,
            ),
            'reviewBody'    => wp_strip_all_tags( $comment->comment_content ),
            'datePublished' => get_comment_date( 'c', $comment ),
        );
    }

    if ( $reviews ) {
        $markup['review'] = $reviews;
    }

    return $markup;
}
add_filter( 'woocommerce_structured_data_product', 'rb_reviews_structured_data', 10, 2 );

// -----------------------------------------------------------------------
// Solicitud post-compra — cron diario
// -----------------------------------------------------------------------

/**
 * Días tras completar el pedido antes de pedir la reseña — tiempo de rodar la bici.
 */
function rb_reviews_solicit_days() {
    return (int) apply_filters( 'rb_reviews_solicit_days', 10 );
}

/**
 * Días tras el primer correo antes de un único recordatorio.
 */
function rb_reviews_reminder_days() {
    return (int) apply_filters( 'rb_reviews_solicit_reminder_days', 17 );
}

function rb_reviews_optout_token( $order_id ) {
    return substr( wp_hash( 'rb_reviews_optout_' . $order_id ), 0, 20 );
}

function rb_reviews_mail_content_type() {
    return 'text/html';
}

/**
 * @return bool true si el cliente ya dejó al menos una reseña de algún producto del pedido.
 */
function rb_reviews_order_already_reviewed( $order ) {
    $email = $order->get_billing_email();
    if ( ! $email ) {
        return false;
    }

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        if ( ! $product_id ) {
            continue;
        }

        $existing = get_comments( array(
            'post_id'      => $product_id,
            'type'         => 'review',
            'author_email' => $email,
            'number'       => 1,
            'count'        => true,
        ) );

        if ( $existing ) {
            return true;
        }
    }

    return false;
}

function rb_reviews_build_items_html( $order ) {
    $html = '';

    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) {
            continue;
        }

        $link = add_query_arg( array(
            'rb_write_review' => '1',
            'rb_order'        => $order->get_id(),
            'rb_token'        => rb_reviews_review_token( $order->get_id(), $product->get_id() ),
        ), $product->get_permalink() ) . '#rb-reviews';

        $html .= sprintf(
            '<tr><td style="padding:12px 0;border-bottom:1px solid #eeeeee;font-size:14px;"><a href="%s" style="color:#111111;text-decoration:none;font-weight:600;">%s &rarr;</a></td></tr>',
            esc_url( $link ),
            esc_html( $product->get_name() )
        );
    }

    return $html;
}

function rb_reviews_send_request_email( $order, $is_reminder = false ) {
    $email = $order->get_billing_email();
    if ( ! $email || ! is_email( $email ) ) {
        return false;
    }

    $items_html = rb_reviews_build_items_html( $order );
    if ( ! $items_html ) {
        return false;
    }

    $optout_url = add_query_arg( array(
        'rb_review_optout' => '1',
        'order'             => $order->get_id(),
        'token'              => rb_reviews_optout_token( $order->get_id() ),
    ), home_url( '/' ) );

    $subject = $is_reminder
        ? __( '¿Ya la probaste? Cuéntanos qué te pareció', 'racing-bike-reviews' )
        : __( '¿Cómo te fue con tu compra?', 'racing-bike-reviews' );

    ob_start();
    include RB_REVIEWS_DIR . 'templates/email-review-request.php';
    $body = ob_get_clean();

    add_filter( 'wp_mail_content_type', 'rb_reviews_mail_content_type' );
    $sent = wp_mail( $email, $subject, $body );
    remove_filter( 'wp_mail_content_type', 'rb_reviews_mail_content_type' );

    return $sent;
}

function rb_reviews_run_solicitations() {
    if ( ! function_exists( 'wc_get_orders' ) ) {
        return;
    }

    $now          = time();
    $solicit_secs = rb_reviews_solicit_days() * DAY_IN_SECONDS;
    $remind_secs  = rb_reviews_reminder_days() * DAY_IN_SECONDS;

    // --- Primer correo: pedidos completados que aún no recibieron solicitud ---
    $pending = wc_get_orders( array(
        'status'     => 'completed',
        'limit'      => 50,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'meta_query' => array(
            array( 'key' => '_rb_review_request_sent', 'compare' => 'NOT EXISTS' ),
        ),
    ) );

    foreach ( $pending as $order ) {
        $completed = $order->get_date_completed();
        if ( ! $completed ) {
            continue;
        }

        $age = $now - $completed->getTimestamp();

        if ( $age < $solicit_secs ) {
            continue; // Todavía no toca.
        }

        // Backlog histórico (pedidos ya viejos cuando se activó el plugin): no
        // se les manda un correo "post-compra" que llegaría fuera de contexto.
        if ( $age > $solicit_secs + ( 30 * DAY_IN_SECONDS ) ) {
            $order->update_meta_data( '_rb_review_request_sent', 'skipped_old' );
            $order->save();
            continue;
        }

        rb_reviews_send_request_email( $order, false );
        $order->update_meta_data( '_rb_review_request_sent', current_time( 'mysql' ) );
        $order->save();
    }

    // --- Recordatorio único: pedidos que recibieron el primer correo hace rb_reviews_reminder_days() ---
    $awaiting_reminder = wc_get_orders( array(
        'status'     => 'completed',
        'limit'      => 50,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'meta_query' => array(
            'relation' => 'AND',
            array( 'key' => '_rb_review_request_sent', 'compare' => 'EXISTS' ),
            array( 'key' => '_rb_review_reminder_sent', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_rb_review_optout', 'compare' => 'NOT EXISTS' ),
        ),
    ) );

    foreach ( $awaiting_reminder as $order ) {
        if ( 'skipped_old' === $order->get_meta( '_rb_review_request_sent' ) ) {
            $order->update_meta_data( '_rb_review_reminder_sent', 'not_applicable' );
            $order->save();
            continue;
        }

        $completed = $order->get_date_completed();
        if ( ! $completed || ( $now - $completed->getTimestamp() ) < $remind_secs ) {
            continue;
        }

        if ( rb_reviews_order_already_reviewed( $order ) ) {
            $order->update_meta_data( '_rb_review_reminder_sent', 'not_needed' );
            $order->save();
            continue;
        }

        rb_reviews_send_request_email( $order, true );
        $order->update_meta_data( '_rb_review_reminder_sent', current_time( 'mysql' ) );
        $order->save();
    }
}
add_action( 'rb_reviews_solicit_cron', 'rb_reviews_run_solicitations' );

/**
 * Enlace de "no quiero recibir esta solicitud" en el correo — sin login,
 * validado con un token firmado (wp_hash) en lugar de nonce porque el
 * click llega días después, fuera de cualquier sesión.
 */
function rb_reviews_handle_optout() {
    if ( empty( $_GET['rb_review_optout'] ) || empty( $_GET['order'] ) ) {
        return;
    }

    $order_id = absint( $_GET['order'] );
    $token    = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

    if ( ! hash_equals( rb_reviews_optout_token( $order_id ), $token ) ) {
        wp_die(
            esc_html__( 'Enlace inválido o vencido.', 'racing-bike-reviews' ),
            esc_html__( 'Enlace inválido', 'racing-bike-reviews' ),
            array( 'response' => 400 )
        );
    }

    $order = wc_get_order( $order_id );

    if ( $order ) {
        $order->update_meta_data( '_rb_review_optout', 1 );
        $order->save();
    }

    wp_die(
        esc_html__( 'Listo — no volveremos a escribirte para pedir una reseña de este pedido.', 'racing-bike-reviews' ),
        esc_html__( 'Solicitud registrada', 'racing-bike-reviews' ),
        array( 'response' => 200 )
    );
}
add_action( 'template_redirect', 'rb_reviews_handle_optout' );

// -----------------------------------------------------------------------
// Muro de reseñas con foto — para la pasarela de prueba social de la home
// -----------------------------------------------------------------------

/**
 * Reseñas con foto más recientes de todo el catálogo, para el muro de la home.
 * A diferencia de rb_reviews_get_list(), no está acotado a un producto.
 *
 * @return array<int, array{photo:string,rating:int,author:string,excerpt:string,product_name:string,product_url:string}>
 */
function rb_reviews_get_photo_wall( $limit = 10 ) {
    $cache_key = 'rb_reviews_photo_wall_' . absint( $limit );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $comments = get_comments( array(
        'type'       => 'review',
        'status'     => 'approve',
        'number'     => absint( $limit ),
        'orderby'    => 'comment_date_gmt',
        'order'      => 'DESC',
        'meta_query' => array(
            array( 'key' => 'rb_photo_ids', 'compare' => 'EXISTS' ),
        ),
    ) );

    $items = array();

    foreach ( $comments as $comment ) {
        $photo_ids = get_comment_meta( $comment->comment_ID, 'rb_photo_ids', true );
        $photo_ids = $photo_ids ? array_filter( array_map( 'absint', explode( ',', $photo_ids ) ) ) : array();

        if ( ! $photo_ids ) {
            continue;
        }

        $photo_id  = reset( $photo_ids );
        $photo_url = wp_get_attachment_image_url( $photo_id, 'large' );

        if ( ! $photo_url ) {
            continue;
        }

        $product = wc_get_product( $comment->comment_post_ID );

        if ( ! $product ) {
            continue;
        }

        $items[] = array(
            // 'photo' se mantiene por compatibilidad (fallback si el tema no
            // pide srcset); 'photo_id' es lo que permite al tema pedir un
            // wp_get_attachment_image() responsivo en vez de servir siempre
            // el tamaño "large" (1024px) aunque la tarjeta se pinte a ~280px.
            'photo'        => $photo_url,
            'photo_id'     => $photo_id,
            'rating'       => (int) get_comment_meta( $comment->comment_ID, 'rating', true ),
            'author'       => $comment->comment_author,
            'excerpt'      => wp_trim_words( $comment->comment_content, 26 ),
            'product_name' => $product->get_name(),
            'product_url'  => $product->get_permalink() . '#rb-reviews',
            'featured'     => (int) get_comment_meta( $comment->comment_ID, 'rb_featured', true ),
            'verified'     => (bool) get_comment_meta( $comment->comment_ID, 'verified', true ),
        );
    }

    // Lo que el admin marcó como destacado (ver la acción "Destacar" en
    // Comentarios) va primero en el muro.
    usort( $items, function ( $a, $b ) {
        return $b['featured'] <=> $a['featured'];
    } );

    set_transient( $cache_key, $items, HOUR_IN_SECONDS );

    return $items;
}

// -----------------------------------------------------------------------
// Moderación en el admin — columnas con foto/ajuste y reseña destacada
// -----------------------------------------------------------------------

/**
 * Ninguna de las dos pantallas de moderación de reseñas mostraba la foto ni
 * el dato de ajuste — un moderador tenía que aprobar una reseña con foto
 * sin haber visto la foto. WooCommerce 11 ya no reutiliza la tabla clásica
 * de Comentarios para esto: tiene su propia pantalla ("WooCommerce >
 * Reseñas de producto", admin.php?page=product-reviews, implementada en
 * ReviewsListTable) con su propio punto de extensión oficial
 * (`woocommerce_product_reviews_table_columns`). Se engancha ahí — que es
 * donde un admin de esta versión realmente modera — y también en el hook
 * genérico de Comentarios por si el sitio corre alguna vez con una versión
 * de WooCommerce más antigua que sí use la pantalla clásica.
 */
function rb_reviews_admin_render_photo_column( $comment_id ) {
    $photo_ids = get_comment_meta( $comment_id, 'rb_photo_ids', true );
    $photo_ids = $photo_ids ? array_filter( array_map( 'absint', explode( ',', $photo_ids ) ) ) : array();

    if ( ! $photo_ids ) {
        echo '&mdash;';
        return;
    }

    foreach ( array_slice( $photo_ids, 0, 3 ) as $photo_id ) {
        $thumb = wp_get_attachment_image_url( $photo_id, 'thumbnail' );
        if ( $thumb ) {
            printf(
                '<a href="%1$s" target="_blank" rel="noopener"><img src="%1$s" style="width:48px;height:48px;object-fit:cover;border-radius:4px;margin:2px;display:inline-block;" alt=""></a>',
                esc_url( $thumb )
            );
        }
    }
}

function rb_reviews_admin_render_fit_column( $comment_id ) {
    $bits = array();

    $rating = (int) get_comment_meta( $comment_id, 'rating', true );
    if ( $rating ) {
        $bits[] = str_repeat( '★', $rating );
    }

    $height = get_comment_meta( $comment_id, 'rb_height_cm', true );
    if ( $height ) {
        $bits[] = $height . ' cm';
    }

    $size = get_comment_meta( $comment_id, 'rb_size_bought', true );
    if ( $size ) {
        $bits[] = sprintf( __( 'Talla %s', 'racing-bike-reviews' ), strtoupper( $size ) );
    }

    if ( get_comment_meta( $comment_id, 'verified', true ) ) {
        $bits[] = '&#10003; ' . __( 'verificada', 'racing-bike-reviews' );
    }

    if ( get_comment_meta( $comment_id, 'rb_featured', true ) ) {
        $bits[] = '&#9733; ' . __( 'destacada', 'racing-bike-reviews' );
    }

    echo $bits ? esc_html( implode( ' · ', $bits ) ) : '&mdash;';
}

// Pantalla nativa de WooCommerce 11 ("WooCommerce > Reseñas de producto").
add_filter( 'woocommerce_product_reviews_table_columns', function ( $columns ) {
    $columns['rb_review_photo'] = __( 'Foto', 'racing-bike-reviews' );
    $columns['rb_review_fit']   = __( 'Ajuste', 'racing-bike-reviews' );
    return $columns;
} );

add_action( 'woocommerce_product_reviews_table_column_rb_review_photo', function ( $comment ) {
    if ( $comment instanceof WP_Comment && 'review' === $comment->comment_type ) {
        rb_reviews_admin_render_photo_column( $comment->comment_ID );
    }
} );

add_action( 'woocommerce_product_reviews_table_column_rb_review_fit', function ( $comment ) {
    if ( $comment instanceof WP_Comment && 'review' === $comment->comment_type ) {
        rb_reviews_admin_render_fit_column( $comment->comment_ID );
    }
} );

// Respaldo: pantalla clásica de Comentarios, por si el sitio corre alguna
// vez con una versión de WooCommerce anterior a la 11.
add_filter( 'manage_edit-comments_columns', function ( $columns ) {
    $columns['rb_review_photo'] = __( 'Foto', 'racing-bike-reviews' );
    $columns['rb_review_fit']   = __( 'Reseña', 'racing-bike-reviews' );
    return $columns;
} );

add_action( 'manage_comments_custom_column', function ( $column, $comment_id ) {
    $comment = get_comment( $comment_id );

    if ( ! $comment || 'review' !== $comment->comment_type ) {
        return;
    }

    if ( 'rb_review_photo' === $column ) {
        rb_reviews_admin_render_photo_column( $comment_id );
    } elseif ( 'rb_review_fit' === $column ) {
        rb_reviews_admin_render_fit_column( $comment_id );
    }
}, 10, 2 );

/**
 * Acción "Destacar" en la fila de cada reseña — sube esa reseña al frente
 * del muro de fotos de la home (ver rb_reviews_get_photo_wall()) y, en la
 * primera página, al frente del carrusel de la ficha de producto.
 */
add_filter( 'comment_row_actions', function ( $actions, $comment ) {
    if ( 'review' !== $comment->comment_type || ! current_user_can( 'moderate_comments' ) ) {
        return $actions;
    }

    $is_featured = (bool) get_comment_meta( $comment->comment_ID, 'rb_featured', true );

    $url = wp_nonce_url(
        add_query_arg( array(
            'action'     => 'rb_toggle_featured',
            'comment_id' => $comment->comment_ID,
        ), admin_url( 'admin-post.php' ) ),
        'rb_toggle_featured_' . $comment->comment_ID
    );

    $actions['rb_toggle_featured'] = sprintf(
        '<a href="%s">%s</a>',
        esc_url( $url ),
        $is_featured
            ? esc_html__( 'Quitar destacado', 'racing-bike-reviews' )
            : esc_html__( 'Destacar', 'racing-bike-reviews' )
    );

    return $actions;
}, 10, 2 );

add_action( 'admin_post_rb_toggle_featured', function () {
    $comment_id = isset( $_GET['comment_id'] ) ? absint( $_GET['comment_id'] ) : 0;

    if ( ! $comment_id || ! current_user_can( 'moderate_comments' ) ) {
        wp_die( esc_html__( 'No tienes permiso para hacer esto.', 'racing-bike-reviews' ) );
    }

    check_admin_referer( 'rb_toggle_featured_' . $comment_id );

    $comment = get_comment( $comment_id );

    if ( $comment && 'review' === $comment->comment_type ) {
        $is_featured = (bool) get_comment_meta( $comment_id, 'rb_featured', true );
        update_comment_meta( $comment_id, 'rb_featured', $is_featured ? 0 : 1 );
        delete_transient( 'rb_reviews_photo_wall_10' );
    }

    wp_safe_redirect( wp_get_referer() ?: admin_url( 'edit-comments.php' ) );
    exit;
} );
