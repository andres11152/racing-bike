<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MD_Admin {

    private static $instance;
    const CAP = 'manage_options';

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

        add_action( 'admin_post_skc_md_create_campaign', array( $this, 'create_campaign' ) );
        add_action( 'admin_post_skc_md_save_campaign', array( $this, 'save_campaign' ) );
        add_action( 'admin_post_skc_md_delete_campaign', array( $this, 'delete_campaign' ) );
        add_action( 'admin_post_skc_md_duplicate_campaign', array( $this, 'duplicate_campaign' ) );
        add_action( 'admin_post_skc_md_export_leads', array( $this, 'export_leads' ) );
        add_action( 'admin_post_skc_md_save_settings', array( $this, 'save_settings' ) );
    }

    public function menu() {
        add_menu_page(
            __( 'Modal Pro', 'skycode-modal' ),
            __( 'Modal Pro', 'skycode-modal' ),
            self::CAP,
            'skc-md-campaigns',
            array( $this, 'render_campaigns' ),
            'dashicons-megaphone',
            81
        );

        add_submenu_page( 'skc-md-campaigns', __( 'Campañas', 'skycode-modal' ), __( 'Campañas', 'skycode-modal' ), self::CAP, 'skc-md-campaigns', array( $this, 'render_campaigns' ) );
        add_submenu_page( 'skc-md-campaigns', __( 'Suscriptores', 'skycode-modal' ), __( 'Suscriptores', 'skycode-modal' ), self::CAP, 'skc-md-leads', array( $this, 'render_leads' ) );
        add_submenu_page( 'skc-md-campaigns', __( 'Estadísticas', 'skycode-modal' ), __( 'Estadísticas', 'skycode-modal' ), self::CAP, 'skc-md-stats', array( $this, 'render_stats' ) );
        add_submenu_page( 'skc-md-campaigns', __( 'Ajustes', 'skycode-modal' ), __( 'Ajustes', 'skycode-modal' ), self::CAP, 'skc-md-settings', array( $this, 'render_settings' ) );

        // Página oculta (parent_slug null): accesible por URL desde la lista de
        // campañas sin aparecer como entrada de menú propia.
        add_submenu_page( null, __( 'Editor de campaña', 'skycode-modal' ), '', self::CAP, 'skc-md-editor', array( $this, 'render_editor' ) );
    }

    public function assets( $hook ) {
        if ( strpos( $hook, 'skc-md-' ) === false ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );

        // La vista previa del editor reutiliza el CSS real del modal y de
        // todos los skins, para que lo que se ve ahí sea 1:1 con el sitio.
        wp_enqueue_style( 'skc-md-modal', SKC_MD_URL . 'assets/css/modal.css', array(), SKC_MD_VERSION );
        foreach ( array( 'center', 'slide-in', 'bottom-bar', 'fullscreen', 'sidebar' ) as $skin ) {
            wp_enqueue_style( 'skc-md-skin-' . $skin, SKC_MD_URL . 'templates/skins/' . $skin . '.css', array( 'skc-md-modal' ), SKC_MD_VERSION );
        }

        wp_enqueue_style( 'skc-md-admin', SKC_MD_URL . 'assets/css/admin.css', array( 'skc-md-modal' ), SKC_MD_VERSION );
        wp_enqueue_script( 'skc-md-admin', SKC_MD_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), SKC_MD_VERSION, true );
        wp_localize_script( 'skc-md-admin', 'skcMdAdmin', array(
            'selectImageTitle' => __( 'Selecciona una imagen', 'skycode-modal' ),
        ) );
    }

    private function render( $view, array $vars = array() ) {
        extract( $vars );
        include SKC_MD_DIR . 'includes/admin/views/' . $view . '.php';
    }

    // -------------------------------------------------------------
    // Pantallas
    // -------------------------------------------------------------

    public function render_campaigns() {
        $campaigns = SKC_MD_Campaign::all_campaigns();
        $this->render( 'campaigns', array( 'campaigns' => $campaigns ) );
    }

    public function render_editor() {
        $post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $post    = $post_id ? get_post( $post_id ) : null;

        if ( ! $post || SKC_MD_Campaign::POST_TYPE !== $post->post_type ) {
            wp_die( esc_html__( 'Campaña no encontrada.', 'skycode-modal' ) );
        }

        $config = SKC_MD_Campaign::get_config( $post_id );
        $stats  = SKC_MD_Events::stats_for_campaign( $post_id );
        $this->render( 'editor', array( 'post' => $post, 'config' => $config, 'stats' => $stats ) );
    }

    public function render_leads() {
        $campaign_id = isset( $_GET['campaign'] ) ? absint( $_GET['campaign'] ) : 0;
        $leads       = SKC_MD_Leads::get_by_campaign( $campaign_id );
        $campaigns   = SKC_MD_Campaign::all_campaigns();
        $this->render( 'leads', array( 'leads' => $leads, 'campaigns' => $campaigns, 'campaign_id' => $campaign_id ) );
    }

    public function render_stats() {
        $campaign_id = isset( $_GET['campaign'] ) ? absint( $_GET['campaign'] ) : 0;
        $campaigns   = SKC_MD_Campaign::all_campaigns();

        $stats    = $campaign_id ? SKC_MD_Events::stats_for_campaign( $campaign_id ) : null;
        $variants = $campaign_id ? SKC_MD_Events::variant_stats( $campaign_id ) : array();

        $this->render( 'stats', array(
            'campaigns'   => $campaigns,
            'campaign_id' => $campaign_id,
            'stats'       => $stats,
            'variants'    => $variants,
        ) );
    }

    public function render_settings() {
        $settings = SKC_MD_Settings::all();
        $this->render( 'settings', array( 'settings' => $settings ) );
    }

    // -------------------------------------------------------------
    // Acciones (admin-post)
    // -------------------------------------------------------------

    public function create_campaign() {
        check_admin_referer( 'skc_md_create_campaign' );
        $this->require_cap();

        $title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $post_id = SKC_MD_Campaign::create( $title );

        wp_safe_redirect( admin_url( 'admin.php?page=skc-md-editor&id=' . absint( $post_id ) ) );
        exit;
    }

    public function save_campaign() {
        check_admin_referer( 'skc_md_save_campaign' );
        $this->require_cap();

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id || SKC_MD_Campaign::POST_TYPE !== get_post_type( $post_id ) ) {
            wp_die( esc_html__( 'Campaña no válida.', 'skycode-modal' ) );
        }

        if ( isset( $_POST['title'] ) ) {
            wp_update_post( array( 'ID' => $post_id, 'post_title' => sanitize_text_field( wp_unslash( $_POST['title'] ) ) ) );
        }

        $values = wp_unslash( $_POST );
        unset( $values['title'], $values['post_id'], $values['_wpnonce'], $values['_wp_http_referer'], $values['action'] );

        if ( isset( $values['variants_json'] ) ) {
            $decoded = json_decode( $values['variants_json'], true );
            $values['variants'] = is_array( $decoded ) ? $decoded : array();
            unset( $values['variants_json'] );
        }

        // Checkboxes ausentes en $_POST cuando están desmarcados.
        foreach ( array( 'fields_name', 'fields_phone', 'require_consent', 'freq_hide_on_convert' ) as $checkbox ) {
            $values[ $checkbox ] = ! empty( $values[ $checkbox ] );
        }

        if ( isset( $values['schedule_start'] ) ) {
            $values['schedule_start'] = $values['schedule_start'] ? strtotime( $values['schedule_start'] ) : 0;
        }
        if ( isset( $values['schedule_end'] ) ) {
            $values['schedule_end'] = $values['schedule_end'] ? strtotime( $values['schedule_end'] ) : 0;
        }

        SKC_MD_Campaign::save_config( $post_id, $values );

        wp_safe_redirect( admin_url( 'admin.php?page=skc-md-editor&id=' . $post_id . '&saved=1' ) );
        exit;
    }

    public function delete_campaign() {
        check_admin_referer( 'skc_md_delete_campaign' );
        $this->require_cap();

        $post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( $post_id && SKC_MD_Campaign::POST_TYPE === get_post_type( $post_id ) ) {
            wp_delete_post( $post_id, true );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=skc-md-campaigns&deleted=1' ) );
        exit;
    }

    public function duplicate_campaign() {
        check_admin_referer( 'skc_md_duplicate_campaign' );
        $this->require_cap();

        $post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $new_id  = $post_id ? SKC_MD_Campaign::duplicate( $post_id ) : false;

        wp_safe_redirect( $new_id
            ? admin_url( 'admin.php?page=skc-md-editor&id=' . $new_id )
            : admin_url( 'admin.php?page=skc-md-campaigns' )
        );
        exit;
    }

    public function export_leads() {
        check_admin_referer( 'skc_md_export_leads' );
        $this->require_cap();

        $campaign_id = isset( $_GET['campaign'] ) ? absint( $_GET['campaign'] ) : 0;
        $csv         = SKC_MD_Leads::export_csv( $campaign_id );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="skycode-modal-leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput -- CSV plano generado internamente.
        exit;
    }

    public function save_settings() {
        check_admin_referer( 'skc_md_save_settings' );
        $this->require_cap();

        $values = wp_unslash( $_POST );
        unset( $values['_wpnonce'], $values['_wp_http_referer'], $values['action'] );

        foreach ( array( 'gdpr_require_consent', 'respect_reduced_motion', 'watermark_enabled', 'delete_on_uninstall' ) as $checkbox ) {
            $values[ $checkbox ] = ! empty( $values[ $checkbox ] );
        }

        SKC_MD_Settings::update( $values );

        wp_safe_redirect( admin_url( 'admin.php?page=skc-md-settings&saved=1' ) );
        exit;
    }

    private function require_cap() {
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes.', 'skycode-modal' ) );
        }
    }
}
