<?php
/**
 * Plugin Name: Skycode Anti-Spam Shield
 * Description: Sistema de defensa anti-spam ligero, autónomo y 100% gratuito. Protege comentarios de WordPress y reseñas de WooCommerce sin APIs externas ni captchas molestos. Genérico y reutilizable entre proyectos.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-anti-spam
 */

if (!defined('ABSPATH')) {
    exit;
}

class RB_Anti_Spam_Shield {

    const MIN_SUBMISSION_TIME = 2.5; // Segundos mínimos para considerar una respuesta humana
    const MAX_ALLOWED_LINKS = 2;     // Máximo de URLs permitidas por comentario
    const HONEYPOT_FIELD_NAME = 'rb_security_aux_field';

    public function __construct() {
        // Inyección de campos de seguridad en formularios de comentarios y reseñas
        add_action('comment_form_after_fields', [$this, 'inject_security_fields']);
        add_action('comment_form_logged_in_after', [$this, 'inject_security_fields']);
        add_action('comment_form', [$this, 'inject_security_fields']);

        // Verificación de comentarios y reseñas antes de guardarse
        add_filter('preprocess_comment', [$this, 'verify_comment_submission'], 1);

        // Panel de estadísticas en el Dashboard
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * Inyecta campo trampa (Honeypot) y timestamp firmado criptográficamente.
     */
    public function inject_security_fields() {
        static $injected = false;
        if ($injected) return;
        $injected = true;

        $currentTime = time();
        $token = $this->generate_time_token($currentTime);
        $hpName = self::HONEYPOT_FIELD_NAME;

        ?>
        <div style="position: absolute !important; left: -9999px !important; width: 1px !important; height: 1px !important; opacity: 0 !important; overflow: hidden !important; pointer-events: none !important;" aria-hidden="true" tabindex="-1">
            <label for="<?php echo esc_attr($hpName); ?>"><?php _e('No llenar este campo si eres humano', 'skycode-anti-spam'); ?></label>
            <input type="text" name="<?php echo esc_attr($hpName); ?>" id="<?php echo esc_attr($hpName); ?>" value="" autocomplete="off" tabindex="-1" />
            <input type="hidden" name="rb_ast_time" value="<?php echo esc_attr($currentTime); ?>" />
            <input type="hidden" name="rb_ast_token" value="<?php echo esc_attr($token); ?>" />
        </div>
        <?php
    }

    /**
     * Valida el envío del comentario frente a los vectores de ataque de spam.
     */
    public function verify_comment_submission($commentdata) {
        // Permitir que administradores y editores publiquen sin restricciones
        if (current_user_can('moderate_comments')) {
            return $commentdata;
        }

        // Si es una petición de importación interna o WP-CLI, no bloquear
        if (defined('WP_CLI') && WP_CLI) {
            return $commentdata;
        }

        // 1. Verificación del campo Honeypot
        $honeypot = isset($_POST[self::HONEYPOT_FIELD_NAME]) ? trim($_POST[self::HONEYPOT_FIELD_NAME]) : '';
        if (!empty($honeypot)) {
            $this->log_and_block_spam('Honeypot activado por bot automatizado.');
        }

        // 2. Verificación de velocidad de envío (Bots envían en < 2.5s)
        $clientTime = isset($_POST['rb_ast_time']) ? intval($_POST['rb_ast_time']) : 0;
        $clientToken = isset($_POST['rb_ast_token']) ? sanitize_text_field($_POST['rb_ast_token']) : '';

        if ($clientTime > 0 && !empty($clientToken)) {
            // Verificar autenticidad del token
            $expectedToken = $this->generate_time_token($clientTime);
            if (!hash_equals($expectedToken, $clientToken)) {
                $this->log_and_block_spam('Token de tiempo no válido o manipulado.');
            }

            $elapsedTime = time() - $clientTime;
            if ($elapsedTime < self::MIN_SUBMISSION_TIME) {
                $this->log_and_block_spam("Envío demasiado rápido ({$elapsedTime}s). Comportamiento característico de bot.");
            }
        }

        // 3. Verificación de densidad excesiva de enlaces URL
        $content = $commentdata['comment_content'] ?? '';
        $linkCount = preg_match_all('/https?:\/\/[^\s]+/i', $content, $matches);
        if ($linkCount > self::MAX_ALLOWED_LINKS) {
            $this->log_and_block_spam("Exceso de URLs detectadas ({$linkCount} enlaces).");
        }

        // 4. Verificación de patrones de inyección comunes (scripts / caracteres ocultos)
        if (preg_match('/<script|<iframe|base64_decode|eval\(|document\.cookie/i', $content)) {
            $this->log_and_block_spam('Código ejecutable sospechoso en el contenido.');
        }

        return $commentdata;
    }

    /**
     * Genera un hash HMAC seguro con la clave única del sitio.
     */
    private function generate_time_token($timestamp) {
        $key = defined('AUTH_KEY') ? AUTH_KEY : 'rb_anti_spam_fallback_salt_1998';
        return hash_hmac('sha256', (string) $timestamp, $key);
    }

    /**
     * Registra el bloqueo y detiene la ejecución.
     */
    private function log_and_block_spam($reason) {
        // Incrementar contador de bloqueos
        $count = (int) get_option('rb_antispam_blocked_count', 0);
        update_option('rb_antispam_blocked_count', $count + 1);

        // Guardar en el log de depuración si está habilitado
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Racing Bike Anti-Spam] Bloqueado: ' . $reason . ' IP: ' . ($this->get_client_ip()));
        }

        wp_die(
            '<div style="text-align:center; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">' .
            '<h2 style="color: #e11d48;">🛡️ Verificación de Seguridad Anti-Spam</h2>' .
            '<p style="color: #4b5563; font-size: 15px; max-width: 500px; margin: 15px auto;">' .
            __('Tu envío fue detenido preventivamente por el escudo de seguridad de RACING BIKE. Si eres un cliente real, asegúrate de tomarte tu tiempo al redactar tu reseña.', 'skycode-anti-spam') .
            '</p>' .
            '<a href="javascript:history.back()" style="display:inline-block; margin-top: 15px; padding: 10px 20px; background: #10b981; color: white; border-radius: 8px; text-decoration: none; font-weight: bold;">← Regresar e intentar de nuevo</a>' .
            '</div>',
            __('Envío bloqueado por Anti-Spam Shield', 'skycode-anti-spam'),
            ['response' => 403]
        );
    }

    private function get_client_ip() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    }

    /**
     * Widget en el panel de control de WordPress.
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'rb_antispam_widget',
            '🛡️ Escudo Anti-Spam RACING BIKE',
            [$this, 'render_dashboard_widget']
        );
    }

    public function render_dashboard_widget() {
        $blockedCount = (int) get_option('rb_antispam_blocked_count', 0);
        ?>
        <div style="padding: 10px 0;">
            <p style="font-size: 14px; margin-bottom: 12px;">
                <?php _e('El sistema anti-spam autónomo está activo y protegiendo tu tienda contra ataques de bots en reseñas y comentarios.', 'skycode-anti-spam'); ?>
            </p>
            <div style="display: flex; align-items: center; gap: 15px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 8px;">
                <span style="font-size: 28px;">🛡️</span>
                <div>
                    <span style="display: block; font-size: 22px; font-weight: bold; color: #166534; line-height: 1;">
                        <?php echo number_format_i18n($blockedCount); ?>
                    </span>
                    <span style="font-size: 12px; color: #15803d; font-weight: 600; text-transform: uppercase;">
                        <?php _e('Spams y bots bloqueados con éxito', 'skycode-anti-spam'); ?>
                    </span>
                </div>
            </div>
            <p style="font-size: 11px; color: #6b7280; margin-top: 10px;">
                ✓ 100% Autónomo &middot; ✓ Cero costos de suscripción &middot; ✓ Cero impacto en velocidad
            </p>
        </div>
        <?php
    }

    public function add_admin_menu() {
        add_options_page(
            __('Anti-Spam Shield', 'skycode-anti-spam'),
            __('Anti-Spam Shield', 'skycode-anti-spam'),
            'manage_options',
            'rb-anti-spam',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        $blockedCount = (int) get_option('rb_antispam_blocked_count', 0);
        ?>
        <div class="wrap">
            <h1>🛡️ <?php _e('Racing Bike Anti-Spam Shield', 'skycode-anti-spam'); ?></h1>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; max-width: 650px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; color: #111827;"><?php _e('Estado de Protección', 'skycode-anti-spam'); ?></h2>
                <p style="color: #4b5563; line-height: 1.6;">
                    <?php _e('Tu tienda está protegida con trampas invisibles Honeypot, validación de marcas de tiempo criptográficas y filtrado inteligente de URLs. No necesitas Akismet ni suscripciones de pago.', 'skycode-anti-spam'); ?>
                </p>

                <div style="margin: 20px 0; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <strong style="font-size: 13px; text-transform: uppercase; color: #64748b;"><?php _e('Total de amenazas y bots neutralizados:', 'skycode-anti-spam'); ?></strong>
                    <div style="font-size: 32px; font-weight: 800; color: #0f766e; margin-top: 5px;">
                        <?php echo number_format_i18n($blockedCount); ?>
                    </div>
                </div>

                <ul style="list-style: disc; padding-left: 20px; color: #475569; font-size: 13px; line-height: 1.8;">
                    <li><strong><?php _e('Honeypot Dinámico:', 'skycode-anti-spam'); ?></strong> <?php _e('Trampas ocultas que los bots rellenan automáticamente.', 'skycode-anti-spam'); ?></li>
                    <li><strong><?php _e('Control de Velocidad:', 'skycode-anti-spam'); ?></strong> <?php _e('Bloquea envíos automatizados menores a 2.5 segundos.', 'skycode-anti-spam'); ?></li>
                    <li><strong><?php _e('Límite de Enlaces:', 'skycode-anti-spam'); ?></strong> <?php _e('Descarta mensajes con más de 2 URLs no autorizadas.', 'skycode-anti-spam'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}

new RB_Anti_Spam_Shield();
