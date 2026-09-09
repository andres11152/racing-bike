<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MD_Renderer {

    public static function render( $post_id, $config ) {
        ob_start();
        include SKC_MD_DIR . 'templates/modal.php';
        return ob_get_clean();
    }
}
