<?php

/**
 * Formulario de contacto — endpoint AJAX.
 *
 * El tema no tenía ninguna infraestructura de formularios: los endpoints AJAX
 * que ya existen en filters.php (carrito, quick view) no verifican nonce y no
 * hay wp_localize_script. Así que este manejador trae su propio nonce, que la
 * plantilla imprime en el markup.
 *
 * El correo sale por wp_mail(), que el plugin racing-bike-smtp intercepta en
 * phpmailer_init: no hay que configurar nada de transporte aquí.
 */

namespace App;

/**
 * Motivos de contacto y la bandeja a la que enruta cada uno.
 *
 * Es una allowlist a propósito: el destinatario jamás sale del POST, sólo el
 * identificador del motivo, que se usa para buscar aquí.
 *
 * @return array<string, array{label: string, email: string}>
 */
function contact_form_topics(): array
{
    $contact = contact_info();

    return [
        'ventas' => [
            'label' => __('Ventas y asesoría comercial', 'sage'),
            'email' => $contact['email_ventas'],
        ],
        'garantias' => [
            'label' => __('Garantías y servicio técnico', 'sage'),
            'email' => $contact['email_garantias'],
        ],
        'pagos' => [
            'label' => __('Pagos y facturación', 'sage'),
            'email' => $contact['email_pagos'],
        ],
        'otro' => [
            'label' => __('Otro asunto', 'sage'),
            'email' => $contact['email_contacto'],
        ],
    ];
}

/**
 * Firma HMAC del instante en que se renderizó el formulario.
 *
 * Mismo patrón que racing-bike-anti-spam: el timestamp viaja en claro y firmado,
 * de modo que el servidor puede confiar en él sin guardar estado.
 */
function contact_form_time_token($timestamp): string
{
    $key = defined('AUTH_KEY') ? AUTH_KEY : 'rb_contact_fallback_salt_1998';

    return hash_hmac('sha256', (string) $timestamp, $key);
}

/**
 * IP del visitante, para el límite de envíos.
 */
function contact_form_client_ip(): string
{
    return isset($_SERVER['REMOTE_ADDR'])
        ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
        : '';
}

add_action('wp_ajax_rb_contact_submit', __NAMESPACE__.'\\handle_contact_submit');
add_action('wp_ajax_nopriv_rb_contact_submit', __NAMESPACE__.'\\handle_contact_submit');

/**
 * Recibe, valida y envía el mensaje del formulario de contacto.
 */
function handle_contact_submit(): void
{
    check_ajax_referer('rb_contact_nonce', 'nonce');

    $contact = contact_info();

    // 1. Honeypot. Si el bot lo rellenó, le respondemos éxito para que no
    //    reintente con otra estrategia, pero no enviamos nada.
    if (! empty($_POST['rb_contact_aux'])) {
        wp_send_json_success(['message' => __('Mensaje enviado. Te responderemos muy pronto.', 'sage')]);
    }

    // 2. El formulario tardó menos de 3 s en enviarse: no lo llenó una persona.
    $timestamp = isset($_POST['rb_contact_time']) ? (int) $_POST['rb_contact_time'] : 0;
    $token = isset($_POST['rb_contact_token']) ? sanitize_text_field(wp_unslash($_POST['rb_contact_token'])) : '';

    if (! $timestamp || ! hash_equals(contact_form_time_token($timestamp), $token)) {
        wp_send_json_error(['message' => __('No pudimos validar el formulario. Recarga la página e inténtalo de nuevo.', 'sage')], 400);
    }

    if ((time() - $timestamp) < 3) {
        wp_send_json_error(['message' => __('Tómate un momento para revisar el mensaje antes de enviarlo.', 'sage')], 429);
    }

    // 3. Límite de envíos por IP.
    $rate_key = 'rb_contact_rate_'.md5(contact_form_client_ip());
    $attempts = (int) get_transient($rate_key);

    if ($attempts >= 3) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s is the WhatsApp number */
                __('Ya enviaste varios mensajes. Si es urgente, escríbenos por WhatsApp al %s.', 'sage'),
                $contact['whatsapp_display']
            ),
        ], 429);
    }

    // 4. Saneado y validación de los campos.
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $topic = isset($_POST['topic']) ? sanitize_key(wp_unslash($_POST['topic'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
    $consent = ! empty($_POST['consent']);

    $topics = contact_form_topics();

    if ($name === '' || $message === '') {
        wp_send_json_error(['message' => __('Necesitamos tu nombre y un mensaje para poder responderte.', 'sage')], 400);
    }

    if (! is_email($email)) {
        wp_send_json_error(['message' => __('Revisa el correo: no parece una dirección válida.', 'sage')], 400);
    }

    if (! isset($topics[$topic])) {
        $topic = 'otro';
    }

    if (! $consent) {
        wp_send_json_error(['message' => __('Necesitamos tu autorización para tratar tus datos y poder responderte.', 'sage')], 400);
    }

    // 5. Envío. El destinatario sale de la allowlist, nunca del POST.
    $to = $topics[$topic]['email'];
    $topic_label = $topics[$topic]['label'];

    $subject = sprintf('[Racing Bike] %s — %s', $topic_label, $name);

    $body = contact_form_email_body([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'topic' => $topic_label,
        'message' => $message,
    ]);

    // Reply-To con el correo del cliente: responder desde la bandeja va directo
    // a quien escribió, sin tener que copiar la dirección a mano.
    $headers = [
        sprintf('Reply-To: %s <%s>', $name, $email),
    ];

    add_filter('wp_mail_content_type', __NAMESPACE__.'\\contact_form_mail_content_type');
    $sent = wp_mail($to, $subject, $body, $headers);
    remove_filter('wp_mail_content_type', __NAMESPACE__.'\\contact_form_mail_content_type');

    if (! $sent) {
        error_log(sprintf('[Racing Bike] Falló el envío del formulario de contacto a %s (motivo: %s).', $to, $topic));

        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s is the WhatsApp number */
                __('No pudimos enviar el mensaje. Escríbenos por WhatsApp al %s y te atendemos de inmediato.', 'sage'),
                $contact['whatsapp_display']
            ),
        ], 500);
    }

    set_transient($rate_key, $attempts + 1, HOUR_IN_SECONDS);

    do_action('rb_contact_form_sent', $topic, $email);

    wp_send_json_success(['message' => __('¡Gracias! Recibimos tu mensaje y te responderemos muy pronto.', 'sage')]);
}

/**
 * Cuerpo HTML del correo interno.
 *
 * @param  array<string, string>  $data
 */
function contact_form_email_body(array $data): string
{
    $rows = [
        __('Nombre', 'sage') => $data['name'],
        __('Correo', 'sage') => $data['email'],
        __('Teléfono', 'sage') => $data['phone'] !== '' ? $data['phone'] : '—',
        __('Motivo', 'sage') => $data['topic'],
    ];

    $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111;">';
    $html .= '<h2 style="margin:0 0 16px;font-size:18px;">'.esc_html__('Nuevo mensaje desde el formulario de contacto', 'sage').'</h2>';
    $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;margin-bottom:16px;">';

    foreach ($rows as $label => $value) {
        $html .= sprintf(
            '<tr><td style="border:1px solid #e5e5e5;background:#fafafa;font-weight:bold;">%s</td><td style="border:1px solid #e5e5e5;">%s</td></tr>',
            esc_html($label),
            esc_html($value)
        );
    }

    $html .= '</table>';
    $html .= '<p style="font-weight:bold;margin:0 0 6px;">'.esc_html__('Mensaje', 'sage').'</p>';
    $html .= '<div style="border:1px solid #e5e5e5;padding:12px;white-space:pre-wrap;">'.esc_html($data['message']).'</div>';
    $html .= '<p style="margin-top:20px;font-size:12px;color:#666;">';
    $html .= esc_html(sprintf(
        /* translators: %s is the site URL */
        __('Enviado desde %s', 'sage'),
        home_url('/')
    ));
    $html .= '</p></div>';

    return $html;
}

/**
 * Content type del correo del formulario. Se añade y se quita alrededor de la
 * llamada a wp_mail() para no afectar al resto de correos del sitio.
 */
function contact_form_mail_content_type(): string
{
    return 'text/html';
}
