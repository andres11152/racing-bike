<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Admin {

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
        add_action( 'admin_bar_menu', array( $this, 'admin_bar_badge' ), 100 );
        add_action( 'admin_notices', array( $this, 'active_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

        add_action( 'admin_post_skc_mm_save_general', array( $this, 'save_general' ) );
        add_action( 'admin_post_skc_mm_save_design', array( $this, 'save_design' ) );
        add_action( 'admin_post_skc_mm_save_access', array( $this, 'save_access' ) );
        add_action( 'admin_post_skc_mm_generate_token', array( $this, 'generate_token' ) );
        add_action( 'admin_post_skc_mm_revoke_token', array( $this, 'revoke_token' ) );
        add_action( 'admin_post_skc_mm_export_subscribers', array( $this, 'export_subscribers' ) );
        add_action( 'admin_post_skc_mm_export_settings', array( $this, 'export_settings' ) );
        add_action( 'admin_post_skc_mm_import_settings', array( $this, 'import_settings' ) );
        add_action( 'admin_post_skc_mm_save_uninstall', array( $this, 'save_uninstall' ) );

        add_action( 'template_redirect', array( $this, 'maybe_preview' ) );
    }

    public function menu() {
        add_menu_page(
            __( 'Mantenimiento', 'skycode-maintenance' ),
            __( 'Mantenimiento', 'skycode-maintenance' ),
            self::CAP,
            'skc-mm-general',
            array( $this, 'render_general' ),
            'dashicons-shield',
            80
        );

        add_submenu_page( 'skc-mm-general', __( 'General', 'skycode-maintenance' ), __( 'General', 'skycode-maintenance' ), self::CAP, 'skc-mm-general', array( $this, 'render_general' ) );
        add_submenu_page( 'skc-mm-general', __( 'Diseño', 'skycode-maintenance' ), __( 'Diseño', 'skycode-maintenance' ), self::CAP, 'skc-mm-design', array( $this, 'render_design' ) );
        add_submenu_page( 'skc-mm-general', __( 'Acceso', 'skycode-maintenance' ), __( 'Acceso', 'skycode-maintenance' ), self::CAP, 'skc-mm-access', array( $this, 'render_access' ) );
        add_submenu_page( 'skc-mm-general', __( 'Suscriptores', 'skycode-maintenance' ), __( 'Suscriptores', 'skycode-maintenance' ), self::CAP, 'skc-mm-subscribers', array( $this, 'render_subscribers' ) );
        add_submenu_page( 'skc-mm-general', __( 'Herramientas', 'skycode-maintenance' ), __( 'Herramientas', 'skycode-maintenance' ), self::CAP, 'skc-mm-tools', array( $this, 'render_tools' ) );
    }

    public function assets( $hook ) {
        if ( strpos( $hook, 'skc-mm-' ) === false ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style( 'skc-mm-admin', SKC_MM_URL . 'assets/css/admin.css', array(), SKC_MM_VERSION );
        wp_enqueue_script( 'skc-mm-admin', SKC_MM_URL . 'assets/js/admin.js', array( 'jquery' ), SKC_MM_VERSION, true );
        wp_localize_script( 'skc-mm-admin', 'skcMmAdmin', array(
            'selectLogoTitle' => __( 'Selecciona un logo', 'skycode-maintenance' ),
            'bgImageLabel'     => __( 'URL de la imagen de fondo', 'skycode-maintenance' ),
            'bgVideoLabel'     => __( 'URL del video de fondo (.mp4)', 'skycode-maintenance' ),
            'bgColorLabel'     => __( 'Color de fondo', 'skycode-maintenance' ),
        ) );
    }

    private function render( $view, array $vars = array() ) {
        extract( $vars );
        include SKC_MM_DIR . 'includes/admin/views/' . $view . '.php';
    }

    public function admin_bar_badge( $wp_admin_bar ) {
        if ( 'off' === SKC_MM_Gatekeeper::effective_mode() || ! current_user_can( self::CAP ) ) {
            return;
        }

        $wp_admin_bar->add_node( array(
            'id'    => 'skc-mm-active',
            'title' => '🔴 ' . __( 'Mantenimiento activo', 'skycode-maintenance' ),
            'href'  => admin_url( 'admin.php?page=skc-mm-general' ),
        ) );
    }

    public function active_notice() {
        if ( 'off' === SKC_MM_Gatekeeper::effective_mode() || ! current_user_can( self::CAP ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( $screen && false !== strpos( $screen->id, 'skc-mm-' ) ) {
            return;
        }
        ?>
        <div class="notice notice-warning">
            <p>
                <?php esc_html_e( 'Skycode Maintenance Mode está activo: el sitio público está bloqueado.', 'skycode-maintenance' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=skc-mm-general' ) ); ?>"><?php esc_html_e( 'Administrar', 'skycode-maintenance' ); ?></a>
            </p>
        </div>
        <?php
    }

    // ------------------------------------------------------------------
    // General
    // ------------------------------------------------------------------

    public function render_general() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $settings = SKC_MM_Settings::all();
        $this->render( 'general', compact( 'settings' ) );
    }

    public function save_general() {
        $this->check_permissions( 'skc_mm_save_general' );

        $values = array(
            'mode'         => isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'off',
            'seo_noindex'  => ! empty( $_POST['seo_noindex'] ),
            'retry_after'  => isset( $_POST['retry_after'] ) ? absint( $_POST['retry_after'] ) : 3600,
        );

        if ( ! empty( $_POST['schedule_start'] ) && ! empty( $_POST['schedule_end'] ) ) {
            $start = strtotime( sanitize_text_field( wp_unslash( $_POST['schedule_start'] ) ) );
            $end   = strtotime( sanitize_text_field( wp_unslash( $_POST['schedule_end'] ) ) );
            $values['schedule_start'] = $start ? $start : 0;
            $values['schedule_end']   = $end ? $end : 0;
        } else {
            $values['schedule_start'] = 0;
            $values['schedule_end']   = 0;
        }

        SKC_MM_Settings::update( $values );
        SKC_MM_Logger::record( 'settings_saved', array( 'tab' => 'general', 'mode' => $values['mode'] ) );
        do_action( 'skc_mm_compat_purge_cache' );

        $this->redirect_back( 'skc-mm-general' );
    }

    // ------------------------------------------------------------------
    // Diseño
    // ------------------------------------------------------------------

    public function render_design() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $settings = SKC_MM_Settings::all();
        $skins    = array( 'minimal', 'gradient', 'split', 'industrial', 'dark' );
        $this->render( 'design', compact( 'settings', 'skins' ) );
    }

    public function save_design() {
        $this->check_permissions( 'skc_mm_save_design' );

        $countdown = 0;
        if ( ! empty( $_POST['countdown_to'] ) ) {
            $ts = strtotime( sanitize_text_field( wp_unslash( $_POST['countdown_to'] ) ) );
            $countdown = $ts ? $ts : 0;
        }

        $socials = array();
        if ( ! empty( $_POST['socials'] ) ) {
            foreach ( explode( "\n", wp_unslash( $_POST['socials'] ) ) as $url ) {
                $url = trim( $url );
                if ( $url ) {
                    $socials[] = esc_url_raw( $url );
                }
            }
        }

        $values = array(
            'skin'         => isset( $_POST['skin'] ) ? sanitize_text_field( wp_unslash( $_POST['skin'] ) ) : 'minimal',
            'logo_id'      => isset( $_POST['logo_id'] ) ? absint( $_POST['logo_id'] ) : 0,
            'bg_type'         => isset( $_POST['bg_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bg_type'] ) ) : 'color',
            'bg_value'        => isset( $_POST['bg_value'] ) ? sanitize_text_field( wp_unslash( $_POST['bg_value'] ) ) : '',
            'bg_value_mobile' => isset( $_POST['bg_value_mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['bg_value_mobile'] ) ) : '',
            'bg_poster'       => isset( $_POST['bg_poster'] ) ? sanitize_text_field( wp_unslash( $_POST['bg_poster'] ) ) : '',
            'accent'       => isset( $_POST['accent'] ) ? sanitize_text_field( wp_unslash( $_POST['accent'] ) ) : '',
            'text_color'   => isset( $_POST['text_color'] ) ? sanitize_text_field( wp_unslash( $_POST['text_color'] ) ) : '',
            'headline'     => isset( $_POST['headline'] ) ? sanitize_text_field( wp_unslash( $_POST['headline'] ) ) : '',
            'subtext'      => isset( $_POST['subtext'] ) ? sanitize_text_field( wp_unslash( $_POST['subtext'] ) ) : '',
            'footer_text'  => isset( $_POST['footer_text'] ) ? sanitize_text_field( wp_unslash( $_POST['footer_text'] ) ) : '',
            'countdown_to' => $countdown,
            'socials'      => $socials,
            'custom_css'   => isset( $_POST['custom_css'] ) ? wp_unslash( $_POST['custom_css'] ) : '',
            'custom_html'  => isset( $_POST['custom_html'] ) ? wp_unslash( $_POST['custom_html'] ) : '',
            'subscribe_enabled' => ! empty( $_POST['subscribe_enabled'] ),
            'subscribe_webhook' => isset( $_POST['subscribe_webhook'] ) ? sanitize_text_field( wp_unslash( $_POST['subscribe_webhook'] ) ) : '',
        );

        SKC_MM_Settings::update( $values );
        SKC_MM_Logger::record( 'settings_saved', array( 'tab' => 'design' ) );
        do_action( 'skc_mm_compat_purge_cache' );

        $this->redirect_back( 'skc-mm-design' );
    }

    // ------------------------------------------------------------------
    // Acceso
    // ------------------------------------------------------------------

    public function render_access() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $settings   = SKC_MM_Settings::all();
        $roles      = wp_roles()->get_names();
        $token_url  = get_transient( 'skc_mm_new_token_' . get_current_user_id() );
        delete_transient( 'skc_mm_new_token_' . get_current_user_id() );
        $this->render( 'access', compact( 'settings', 'roles', 'token_url' ) );
    }

    public function save_access() {
        $this->check_permissions( 'skc_mm_save_access' );

        $ips = array();
        if ( ! empty( $_POST['allowed_ips'] ) ) {
            foreach ( explode( "\n", wp_unslash( $_POST['allowed_ips'] ) ) as $ip ) {
                $ip = trim( sanitize_text_field( $ip ) );
                if ( $ip ) {
                    $ips[] = $ip;
                }
            }
        }

        $paths = array();
        if ( ! empty( $_POST['excluded_paths'] ) ) {
            foreach ( explode( "\n", wp_unslash( $_POST['excluded_paths'] ) ) as $path ) {
                $path = trim( sanitize_text_field( $path ) );
                if ( $path ) {
                    $paths[] = $path;
                }
            }
        }

        $values = array(
            'bypass_roles'   => isset( $_POST['bypass_roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['bypass_roles'] ) ) : array(),
            'allowed_ips'    => $ips,
            'excluded_paths' => $paths,
        );

        SKC_MM_Settings::update( $values );
        SKC_MM_Logger::record( 'settings_saved', array( 'tab' => 'access' ) );

        $this->redirect_back( 'skc-mm-access' );
    }

    public function generate_token() {
        $this->check_permissions( 'skc_mm_generate_token' );

        $expires_option = isset( $_POST['token_expires'] ) ? sanitize_text_field( wp_unslash( $_POST['token_expires'] ) ) : '';
        $expires = 0;
        if ( $expires_option ) {
            $ts = strtotime( $expires_option );
            $expires = $ts ? $ts : 0;
        }

        SKC_MM_Settings::update( array( 'token_expires' => $expires ) );
        $token = SKC_MM_Access::generate_token();
        SKC_MM_Logger::record( 'token_generated' );

        $url = add_query_arg( 'skc_access', $token, home_url( '/' ) );
        set_transient( 'skc_mm_new_token_' . get_current_user_id(), $url, 60 );

        $this->redirect_back( 'skc-mm-access' );
    }

    public function revoke_token() {
        $this->check_permissions( 'skc_mm_revoke_token' );
        SKC_MM_Access::revoke_token();
        SKC_MM_Logger::record( 'token_revoked' );
        $this->redirect_back( 'skc-mm-access' );
    }

    // ------------------------------------------------------------------
    // Suscriptores
    // ------------------------------------------------------------------

    public function render_subscribers() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $subscribers = SKC_MM_Subscribers::all();
        $total       = SKC_MM_Subscribers::count();
        $this->render( 'subscribers', compact( 'subscribers', 'total' ) );
    }

    public function export_subscribers() {
        $this->check_permissions( 'skc_mm_export_subscribers' );

        $csv = SKC_MM_Subscribers::export_csv();
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="skc-mm-subscribers.csv"' );
        echo $csv; // phpcs:ignore -- CSV generado internamente con fputcsv.
        exit;
    }

    // ------------------------------------------------------------------
    // Herramientas
    // ------------------------------------------------------------------

    public function render_tools() {
        if ( ! current_user_can( self::CAP ) ) {
            return;
        }
        $settings = SKC_MM_Settings::all();
        $log      = SKC_MM_Logger::recent( 30 );
        $this->render( 'tools', compact( 'settings', 'log' ) );
    }

    public function export_settings() {
        $this->check_permissions( 'skc_mm_export_settings' );

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="skc-mm-settings.json"' );
        echo SKC_MM_Settings::export(); // phpcs:ignore -- JSON generado con wp_json_encode.
        exit;
    }

    public function import_settings() {
        $this->check_permissions( 'skc_mm_import_settings' );

        if ( empty( $_FILES['settings_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['settings_file']['error'] ) {
            $this->redirect_back( 'skc-mm-tools', array( 'skc_mm_error' => 'upload' ) );
        }

        $contents = file_get_contents( $_FILES['settings_file']['tmp_name'] ); // phpcs:ignore -- ruta de subida temporal validada por WP.
        $result   = SKC_MM_Settings::import( $contents );

        if ( is_wp_error( $result ) ) {
            $this->redirect_back( 'skc-mm-tools', array( 'skc_mm_error' => 'invalid' ) );
        }

        SKC_MM_Logger::record( 'settings_imported' );
        $this->redirect_back( 'skc-mm-tools', array( 'skc_mm_imported' => '1' ) );
    }

    public function save_uninstall() {
        $this->check_permissions( 'skc_mm_save_uninstall' );
        SKC_MM_Settings::update( array( 'delete_on_uninstall' => ! empty( $_POST['delete_on_uninstall'] ) ) );
        $this->redirect_back( 'skc-mm-tools' );
    }

    // ------------------------------------------------------------------
    // Preview
    // ------------------------------------------------------------------

    public function maybe_preview() {
        if ( empty( $_GET['skc_preview'] ) || ! current_user_can( self::CAP ) ) {
            return;
        }

        $mode = isset( $_GET['skc_preview_mode'] ) ? sanitize_text_field( wp_unslash( $_GET['skc_preview_mode'] ) ) : 'coming_soon';
        if ( ! in_array( $mode, array( 'coming_soon', 'maintenance' ), true ) ) {
            $mode = 'coming_soon';
        }

        SKC_MM_Renderer::render( $mode );
        exit;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function check_permissions( $action ) {
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes.', 'skycode-maintenance' ) );
        }
        check_admin_referer( $action );
    }

    private function redirect_back( $page, $extra = array() ) {
        $args = array_merge( array( 'page' => $page, 'skc_mm_saved' => '1' ), $extra );
        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }
}
