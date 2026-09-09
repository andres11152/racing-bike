<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registro de canales disponibles. El editor de secuencia y el
 * programador recorren esta lista; los canales aún no implementados
 * (SMS, WhatsApp) se registran igual pero is_ready() devuelve false y
 * la interfaz los muestra en gris como "Próximamente".
 */
class RB_CR_Channels {

    private static $channels = array();

    public static function register( RB_CR_Channel_Interface $channel ) {
        self::$channels[ $channel->slug() ] = $channel;
    }

    public static function get( $slug ) {
        return isset( self::$channels[ $slug ] ) ? self::$channels[ $slug ] : null;
    }

    public static function all() {
        return self::$channels;
    }

    public static function planned() {
        return array(
            'sms'      => __( 'SMS (próximamente)', 'skycode-cart-recovery' ),
            'whatsapp' => __( 'WhatsApp — API oficial de Meta (próximamente)', 'skycode-cart-recovery' ),
        );
    }
}
