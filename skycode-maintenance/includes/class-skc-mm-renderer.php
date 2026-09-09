<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SKC_MM_Renderer {

    public static function render( $mode ) {
        self::send_headers( $mode );

        $settings = SKC_MM_Settings::all();

        $template = locate_template( 'skycode-maintenance/base.php' );
        if ( ! $template ) {
            $template = SKC_MM_DIR . 'templates/base.php';
        }

        include $template;
    }

    private static function send_headers( $mode ) {
        if ( 'maintenance' === $mode ) {
            status_header( 503 );
            $retry_after = (int) SKC_MM_Settings::get( 'retry_after' );
            if ( $retry_after > 0 ) {
                header( 'Retry-After: ' . $retry_after );
            }
        } else {
            status_header( 200 );
        }

        nocache_headers();
        header( 'Cache-Control: no-store' );

        if ( SKC_MM_Settings::get( 'seo_noindex' ) ) {
            header( 'X-Robots-Tag: noindex, nofollow' );
        }
    }

    public static function skin_css( $skin ) {
        $skin = sanitize_key( $skin );
        $file = SKC_MM_DIR . 'templates/skins/' . $skin . '.css';
        if ( ! file_exists( $file ) ) {
            $file = SKC_MM_DIR . 'templates/skins/minimal.css';
        }
        return (string) file_get_contents( $file );
    }
}
