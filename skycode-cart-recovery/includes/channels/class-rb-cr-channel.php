<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Contrato de canal. Email lo implementa hoy; SMS y WhatsApp implementarán
 * la misma interfaz en versiones futuras sin tocar captura, detección,
 * secuencia, restauración ni atribución.
 */
interface RB_CR_Channel_Interface {

    public function slug();

    public function label();

    public function is_ready();

    public function can_reach( array $cart_row );

    /**
     * @return bool true si el envío fue exitoso.
     */
    public function send( array $cart_row, array $step );
}
