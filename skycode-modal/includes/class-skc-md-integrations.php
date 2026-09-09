<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Punto único de salida hacia sistemas externos. El webhook genérico
 * (POST JSON) cubre Zapier/Make/n8n y, por tanto, la mayoría de ESPs
 * (Mailchimp, Brevo, HubSpot...) sin acoplar el plugin a un SDK concreto.
 * `skc_md_lead_added` queda disponible para integraciones a medida.
 */
class SKC_MD_Integrations {

    public static function maybe_notify( $lead, $config ) {
        $webhook = ! empty( $config['webhook'] ) ? $config['webhook'] : SKC_MD_Settings::get( 'default_webhook' );

        if ( '' === $webhook ) {
            return;
        }

        wp_remote_post( $webhook, array(
            'timeout'  => 5,
            'blocking' => false,
            'headers'  => array( 'Content-Type' => 'application/json' ),
            'body'     => wp_json_encode( array(
                'campaign_id' => $lead['campaign_id'],
                'email'       => $lead['email'],
                'name'        => $lead['name'],
                'phone'       => $lead['phone'],
                'consent'     => (bool) $lead['consent'],
                'site'        => home_url(),
                'source_url'  => $lead['source_url'],
                'created'     => current_time( 'mysql' ),
            ) ),
        ) );
    }
}
