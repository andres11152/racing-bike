<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * [skycode_modal id="123"] fuerza el render/enqueue de una campaña
 * concreta en la página actual, saltando la selección automática por
 * targeting (útil para enlazar un botón "Suscríbete" a un modal fijo).
 */
class SKC_MD_Shortcode {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'skycode_modal', array( $this, 'render' ) );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0, 'label' => __( 'Abrir', 'skycode-modal' ) ), $atts, 'skycode_modal' );
        $post_id = absint( $atts['id'] );

        if ( ! $post_id || SKC_MD_Campaign::POST_TYPE !== get_post_type( $post_id ) ) {
            return '';
        }

        wp_enqueue_style( 'skc-md-modal', SKC_MD_URL . 'assets/css/modal.css', array(), SKC_MD_VERSION );
        wp_enqueue_script( 'skc-md-modal', SKC_MD_URL . 'assets/js/modal.js', array(), SKC_MD_VERSION, true );

        $config = SKC_MD_Campaign::get_config( $post_id );

        add_action( 'wp_footer', function () use ( $post_id, $config ) {
            echo SKC_MD_Renderer::render( $post_id, $config ); // phpcs:ignore WordPress.Security.EscapeOutput
        } );

        wp_localize_script( 'skc-md-modal', 'skcMdConfig', array(
            'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
            'trackNonce'             => wp_create_nonce( 'skc_md_track' ),
            'minFillSeconds'         => (int) SKC_MD_Settings::get( 'min_fill_seconds' ),
            'maxActiveModalsPerPage' => 1,
        ) );

        return sprintf(
            '<button type="button" class="skc-md-shortcode-trigger" onclick="document.getElementById(\'skc-md-modal-%1$d\').hidden=false;document.getElementById(\'skc-md-modal-%1$d\').classList.add(\'skc-md-open\');">%2$s</button>',
            $post_id,
            esc_html( $atts['label'] )
        );
    }
}
