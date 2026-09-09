<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MD_Frontend {

    private static $instance = null;
    private $candidates = array();

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp', array( $this, 'collect_candidates' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
        add_action( 'wp_footer', array( $this, 'print_modals' ) );
    }

    public function collect_candidates() {
        if ( is_admin() ) {
            return;
        }

        $campaigns = SKC_MD_Campaign::all_campaigns( 'active' );
        $limit     = max( 1, (int) SKC_MD_Settings::get( 'max_active_modals_per_page' ) );
        $found     = array();

        foreach ( $campaigns as $post ) {
            $config = SKC_MD_Campaign::get_config( $post->ID );

            if ( ! SKC_MD_Targeting::is_candidate( $post->ID, $config ) ) {
                continue;
            }

            $found[] = array( $post->ID, $config );

            if ( count( $found ) >= $limit * 3 ) {
                // Margen extra: el motor JS aún filtrará por reglas de cliente
                // (frecuencia, UTM, primera visita), así que no cortamos en 1.
                break;
            }
        }

        $this->candidates = $found;
    }

    public function maybe_enqueue() {
        if ( empty( $this->candidates ) ) {
            return;
        }

        wp_enqueue_style( 'skc-md-modal', SKC_MD_URL . 'assets/css/modal.css', array(), SKC_MD_VERSION );

        $skins_used = array_unique( array_map( function ( $c ) {
            return $c[1]['skin'];
        }, $this->candidates ) );

        foreach ( $skins_used as $skin ) {
            $path = SKC_MD_DIR . 'templates/skins/' . $skin . '.css';
            if ( file_exists( $path ) ) {
                wp_enqueue_style( 'skc-md-skin-' . $skin, SKC_MD_URL . 'templates/skins/' . $skin . '.css', array( 'skc-md-modal' ), SKC_MD_VERSION );
            }
        }

        wp_enqueue_script( 'skc-md-modal', SKC_MD_URL . 'assets/js/modal.js', array(), SKC_MD_VERSION, true );

        $payload = array_map( function ( $item ) {
            list( $post_id, $config ) = $item;
            return array(
                'id'                  => $post_id,
                'priority'            => (int) $config['priority'],
                'trigger'             => array(
                    'type'     => $config['trigger_type'],
                    'value'    => (int) $config['trigger_value'],
                    'selector' => $config['trigger_selector'],
                ),
                'clientRules'         => SKC_MD_Targeting::client_rules( $config ),
                'variants'            => $config['variants'],
                'redirectAfterSubmit' => $config['redirect_after_submit'],
            );
        }, $this->candidates );

        wp_localize_script( 'skc-md-modal', 'skcMdCampaigns', $payload );
        wp_localize_script( 'skc-md-modal', 'skcMdConfig', array(
            'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
            'trackNonce'              => wp_create_nonce( 'skc_md_track' ),
            'minFillSeconds'          => (int) SKC_MD_Settings::get( 'min_fill_seconds' ),
            'maxActiveModalsPerPage'  => (int) SKC_MD_Settings::get( 'max_active_modals_per_page' ),
        ) );
    }

    public function print_modals() {
        foreach ( $this->candidates as $item ) {
            list( $post_id, $config ) = $item;
            echo SKC_MD_Renderer::render( $post_id, $config ); // phpcs:ignore WordPress.Security.EscapeOutput -- ya escapado en la plantilla.
        }
    }
}
