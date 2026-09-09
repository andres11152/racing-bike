<?php
/**
 * Plugin Name: Skycode SMTP Mailer
 * Description: Envío ultra ligero y seguro de correos transaccionales vía SMTP para WooCommerce y WordPress. Cero publicidad, máxima seguridad y soporte para variables de entorno .env. Genérico y reutilizable entre proyectos.
 * Version: 1.0.0
 * Author: Skycode Agency
 * License: GPL2
 * Text Domain: skycode-smtp
 */

if (!defined('ABSPATH')) {
    exit;
}

class RB_SMTP_Mailer {

    public function __construct() {
        // Enlazar con el inicializador PHPMailer de WordPress
        add_action('phpmailer_init', [$this, 'configure_phpmailer']);

        // Asegurar que el remitente corporativo se aplique en todos los correos
        add_filter('wp_mail_from', [$this, 'get_from_email']);
        add_filter('wp_mail_from_name', [$this, 'get_from_name']);

        // Panel de configuración y pruebas en WP-Admin
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_rb_smtp_send_test', [$this, 'handle_test_email']);
    }

    /**
     * Helpers de obtención de datos con fallback inteligente a .env / constantes
     */
    public static function get_host() {
        $val = get_option('rb_smtp_host', '');
        return !empty($val) ? trim($val) : (defined('SMTP_HOST') ? trim(SMTP_HOST) : '');
    }

    public static function get_port() {
        $val = get_option('rb_smtp_port', '');
        return !empty($val) ? intval($val) : (defined('SMTP_PORT') && !empty(SMTP_PORT) ? intval(SMTP_PORT) : 587);
    }

    public static function get_user() {
        $val = get_option('rb_smtp_user', '');
        return !empty($val) ? trim($val) : (defined('SMTP_USER') ? trim(SMTP_USER) : '');
    }

    public static function get_pass() {
        $val = get_option('rb_smtp_pass', '');
        return !empty($val) ? $val : (defined('SMTP_PASS') ? SMTP_PASS : '');
    }

    public static function get_secure() {
        $val = get_option('rb_smtp_secure', '');
        if (!empty($val)) {
            return $val === 'none' ? '' : trim($val);
        }
        if (defined('SMTP_SECURE') && !empty(SMTP_SECURE)) {
            return SMTP_SECURE === 'none' ? '' : trim(SMTP_SECURE);
        }
        return 'tls'; // Default
    }

    public function get_from_email($original = '') {
        $val = get_option('rb_smtp_from_email', '');
        if (!empty($val) && is_email($val)) {
            return trim($val);
        }
        if (defined('SMTP_FROM_EMAIL') && is_email(SMTP_FROM_EMAIL)) {
            return trim(SMTP_FROM_EMAIL);
        }
        return $original ?: get_option('admin_email');
    }

    public function get_from_name($original = '') {
        $val = get_option('rb_smtp_from_name', '');
        if (!empty($val)) {
            return trim($val);
        }
        if (defined('SMTP_FROM_NAME') && !empty(SMTP_FROM_NAME)) {
            return trim(SMTP_FROM_NAME);
        }
        return $original ?: get_bloginfo('name');
    }

    public static function is_active() {
        return !empty(self::get_host()) && !empty(self::get_user());
    }

    /**
     * Inyecta la configuración SMTP en el objeto PHPMailer
     */
    public function configure_phpmailer($phpmailer) {
        if (!self::is_active()) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = self::get_host();
        $phpmailer->Port = self::get_port();
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = self::get_user();
        $phpmailer->Password = self::get_pass();

        $secure = self::get_secure();
        $phpmailer->SMTPSecure = $secure; // 'tls', 'ssl' o ''
        $phpmailer->AutoTLS = ($secure === 'tls');

        // Remitente
        $fromEmail = $this->get_from_email();
        $fromName  = $this->get_from_name();
        $phpmailer->setFrom($fromEmail, $fromName);

        // Opciones de depuración para desarrollo local
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $phpmailer->SMTPDebug = 0;
            $phpmailer->Debugoutput = 'error_log';
        }
    }

    /**
     * Registrar la página de ajustes en WP-Admin
     */
    public function add_admin_menu() {
        add_options_page(
            __('Configuración SMTP', 'skycode-smtp'),
            __('Configuración SMTP', 'skycode-smtp'),
            'manage_options',
            'rb-smtp-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('rb_smtp_settings_group', 'rb_smtp_host', 'sanitize_text_field');
        register_setting('rb_smtp_settings_group', 'rb_smtp_port', 'absint');
        register_setting('rb_smtp_settings_group', 'rb_smtp_secure', 'sanitize_text_field');
        register_setting('rb_smtp_settings_group', 'rb_smtp_user', 'sanitize_text_field');
        register_setting('rb_smtp_settings_group', 'rb_smtp_pass'); // Contraseña sin escapar
        register_setting('rb_smtp_settings_group', 'rb_smtp_from_email', 'sanitize_email');
        register_setting('rb_smtp_settings_group', 'rb_smtp_from_name', 'sanitize_text_field');
    }

    /**
     * Manejador de envío de correo de prueba
     */
    public function handle_test_email() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Acceso no autorizado', 'skycode-smtp'));
        }

        check_admin_referer('rb_smtp_test_action', 'rb_smtp_test_nonce');

        $toEmail = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
        if (empty($toEmail) || !is_email($toEmail)) {
            wp_redirect(admin_url('options-general.php?page=rb-smtp-settings&test=invalid_email'));
            exit;
        }

        $subject = sprintf(__('✅ Correo de Prueba Exitoso — %s', 'skycode-smtp'), get_bloginfo('name'));
        $message = "¡Hola!\n\nEste es un mensaje de prueba enviado desde tu tienda " . get_bloginfo('name') . ".\n\nEl servidor SMTP está correctamente configurado y listo para entregar correos de WooCommerce (pedidos, clientes, confirmaciones).\n\nFecha y hora: " . current_time('mysql') . "\nHost SMTP: " . self::get_host() . "\nRemitente: " . $this->get_from_email() . "\n\n— Equipo Racing Bike 1998";

        // Capturar errores durante el envío
        $error_message = '';
        $mail_failed_handler = function($wp_error) use (&$error_message) {
            if (is_wp_error($wp_error)) {
                $error_message = $wp_error->get_error_message();
            }
        };
        add_action('wp_mail_failed', $mail_failed_handler);

        $sent = wp_mail($toEmail, $subject, $message);
        remove_action('wp_mail_failed', $mail_failed_handler);

        if ($sent) {
            wp_redirect(admin_url('options-general.php?page=rb-smtp-settings&test=success&to=' . urlencode($toEmail)));
        } else {
            $errParam = !empty($error_message) ? '&err=' . urlencode($error_message) : '';
            wp_redirect(admin_url('options-general.php?page=rb-smtp-settings&test=failed' . $errParam));
        }
        exit;
    }

    /**
     * Render de la vista de ajustes
     */
    public function render_settings_page() {
        $testStatus = isset($_GET['test']) ? sanitize_text_field($_GET['test']) : '';
        $testErr    = isset($_GET['err']) ? sanitize_text_field(urldecode($_GET['err'])) : '';
        $testTo     = isset($_GET['to']) ? sanitize_email(urldecode($_GET['to'])) : '';

        $envHost = defined('SMTP_HOST') ? SMTP_HOST : '';
        $envUser = defined('SMTP_USER') ? SMTP_USER : '';
        $envPort = defined('SMTP_PORT') ? SMTP_PORT : '';
        $envFrom = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '';
        ?>
        <div class="wrap">
            <h1>📬 <?php _e('Configuración de Correo SMTP', 'skycode-smtp'); ?></h1>
            <p><?php _e('Conecta tu servidor SMTP (Brevo, SendGrid, Amazon SES, Mailgun, Hostinger, cPanel, etc.) para asegurar que todos los correos de WooCommerce y WordPress lleguen a la bandeja de entrada.', 'skycode-smtp'); ?></p>

            <?php if ($testStatus === 'success'): ?>
                <div class="notice notice-success is-dismissible" style="padding: 12px;">
                    <p style="font-size: 14px; font-weight: bold; margin: 0; color: #15803d;">
                        ✅ <?php printf(__('¡Correo de prueba enviado con éxito a %s! Revisa tu bandeja de entrada o carpeta de spam.', 'skycode-smtp'), esc_html($testTo)); ?>
                    </p>
                </div>
            <?php elseif ($testStatus === 'failed'): ?>
                <div class="notice notice-error is-dismissible" style="padding: 12px;">
                    <p style="font-size: 14px; font-weight: bold; margin: 0; color: #b91c1c;">
                        ❌ <?php _e('Error al enviar el correo de prueba. Revisa las credenciales de tu servidor SMTP.', 'skycode-smtp'); ?>
                    </p>
                    <?php if ($testErr): ?>
                        <p style="font-family: monospace; font-size: 12px; background: #fee2e2; padding: 8px; border-radius: 4px; margin-top: 8px;">
                            <?php echo esc_html($testErr); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php elseif ($testStatus === 'invalid_email'): ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php _e('Por favor ingresa un correo electrónico válido para la prueba.', 'skycode-smtp'); ?></p>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-top: 20px;">
                <!-- Formulario de Configuración -->
                <div style="flex: 2; min-width: 320px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0;"><?php _e('Parámetros del Servidor SMTP', 'skycode-smtp'); ?></h2>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('rb_smtp_settings_group'); ?>
                        <?php do_settings_sections('rb_smtp_settings_group'); ?>

                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><label for="rb_smtp_host"><?php _e('Servidor SMTP (Host)', 'skycode-smtp'); ?></label></th>
                                <td>
                                    <input type="text" id="rb_smtp_host" name="rb_smtp_host" value="<?php echo esc_attr(get_option('rb_smtp_host')); ?>" class="regular-text" placeholder="smtp-relay.brevo.com / smtp.gmail.com" />
                                    <?php if ($envHost): ?>
                                        <p class="description" style="color: #059669; font-size: 11px;">
                                            💡 <?php printf(__('Valor en .env (Respaldo): %s', 'skycode-smtp'), esc_html($envHost)); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="rb_smtp_port"><?php _e('Puerto SMTP', 'skycode-smtp'); ?></label></th>
                                <td>
                                    <input type="number" id="rb_smtp_port" name="rb_smtp_port" value="<?php echo esc_attr(get_option('rb_smtp_port', '587')); ?>" class="small-text" placeholder="587" />
                                    <span class="description"><?php _e('(Comúnmente 587 para TLS, 465 para SSL o 2525)', 'skycode-smtp'); ?></span>
                                    <?php if ($envPort): ?>
                                        <p class="description" style="color: #059669; font-size: 11px;">
                                            💡 <?php printf(__('Valor en .env (Respaldo): %s', 'skycode-smtp'), esc_html($envPort)); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="rb_smtp_secure"><?php _e('Cifrado de Seguridad', 'skycode-smtp'); ?></label></th>
                                <td>
                                    <?php $currentSecure = get_option('rb_smtp_secure', 'tls'); ?>
                                    <select id="rb_smtp_secure" name="rb_smtp_secure">
                                        <option value="tls" <?php selected($currentSecure, 'tls'); ?>>TLS (Recomendado)</option>
                                        <option value="ssl" <?php selected($currentSecure, 'ssl'); ?>>SSL</option>
                                        <option value="none" <?php selected($currentSecure, 'none'); ?>>Ninguno</option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="rb_smtp_user"><?php _e('Usuario / Correo SMTP', 'skycode-smtp'); ?></label></th>
                                <td>
                                    <input type="text" id="rb_smtp_user" name="rb_smtp_user" value="<?php echo esc_attr(get_option('rb_smtp_user')); ?>" class="regular-text" placeholder="tu-cuenta@tudominio.com" autocomplete="off" />
                                    <?php if ($envUser): ?>
                                        <p class="description" style="color: #059669; font-size: 11px;">
                                            💡 <?php printf(__('Valor en .env (Respaldo): %s', 'skycode-smtp'), esc_html($envUser)); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="rb_smtp_pass"><?php _e('Contraseña SMTP', 'skycode-smtp'); ?></label></th>
                                <td>
                                    <input type="password" id="rb_smtp_pass" name="rb_smtp_pass" value="<?php echo esc_attr(get_option('rb_smtp_pass')); ?>" class="regular-text" placeholder="••••••••••••••••" autocomplete="new-password" />
                                    <p class="description"><?php _e('Se almacena de forma segura para autenticar el envío de correos.', 'skycode-smtp'); ?></p>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="rb_smtp_from_email"><?php _e('Correo del Remitente', 'skycode-smtp'); ?></label></th>
                                <td>
                                    <input type="email" id="rb_smtp_from_email" name="rb_smtp_from_email" value="<?php echo esc_attr(get_option('rb_smtp_from_email')); ?>" class="regular-text" placeholder="pedidos@racingbike1998.com" />
                                    <?php if ($envFrom): ?>
                                        <p class="description" style="color: #059669; font-size: 11px;">
                                            💡 <?php printf(__('Valor en .env (Respaldo): %s', 'skycode-smtp'), esc_html($envFrom)); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="rb_smtp_from_name"><?php _e('Nombre del Remitente', 'skycode-smtp'); ?></label></th>
                                <td>
                                    <input type="text" id="rb_smtp_from_name" name="rb_smtp_from_name" value="<?php echo esc_attr(get_option('rb_smtp_from_name', get_bloginfo('name'))); ?>" class="regular-text" placeholder="RACING BIKE 1998" />
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Guardar Configuración SMTP', 'skycode-smtp')); ?>
                    </form>
                </div>

                <!-- Herramienta de Prueba y Estado -->
                <div style="flex: 1; min-width: 280px; display: flex; flex-direction: column; gap: 20px;">
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <h3 style="margin-top: 0; color: #111827;">🧪 <?php _e('Enviar Correo de Prueba', 'skycode-smtp'); ?></h3>
                        <p style="color: #4b5563; font-size: 13px;">
                            <?php _e('Prueba si tu servidor SMTP está configurado correctamente enviando un mensaje de prueba a cualquier correo.', 'skycode-smtp'); ?>
                        </p>
                        
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="rb_smtp_send_test" />
                            <?php wp_nonce_field('rb_smtp_test_action', 'rb_smtp_test_nonce'); ?>
                            
                            <p>
                                <label for="test_email" style="font-weight: 600; font-size: 13px;"><?php _e('Enviar a:', 'skycode-smtp'); ?></label>
                                <input type="email" id="test_email" name="test_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="widefat" required style="margin-top: 5px;" />
                            </p>
                            
                            <button type="submit" class="button button-primary" style="width: 100%; padding: 6px; font-weight: bold; background: #0f766e; border-color: #0f766e;">
                                🚀 <?php _e('Enviar Prueba Ahora', 'skycode-smtp'); ?>
                            </button>
                        </form>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                        <h4 style="margin-top: 0; color: #334155;">🛡️ <?php _e('Ventajas del Plugin Propio', 'skycode-smtp'); ?></h4>
                        <ul style="margin: 0; padding-left: 18px; color: #64748b; font-size: 12px; line-height: 1.7;">
                            <li>✓ <?php _e('Cero publicidad y cero consumo de memoria extra.', 'skycode-smtp'); ?></li>
                            <li>✓ <?php _e('Soporte nativo para variables de entorno .env en producción.', 'skycode-smtp'); ?></li>
                            <li>✓ <?php _e('Compatible 100% con todos los correos de WooCommerce.', 'skycode-smtp'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

new RB_SMTP_Mailer();
