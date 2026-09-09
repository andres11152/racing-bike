<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Evalúa, en servidor, si una campaña es "candidata" para la petición
 * actual. Lo que no puede saberse en servidor (frecuencia por navegador,
 * UTM, primera visita) se delega al motor JS con las reglas ya calculadas.
 */
class SKC_MD_Targeting {

    public static function is_candidate( $post_id, $config ) {
        if ( 'active' !== $config['status'] ) {
            return false;
        }

        if ( ! self::within_schedule( $config ) ) {
            return false;
        }

        if ( ! self::matches_page( $post_id, $config ) ) {
            return false;
        }

        if ( ! self::matches_user_state( $config ) ) {
            return false;
        }

        if ( ! self::matches_device( $config ) ) {
            return false;
        }

        return true;
    }

    private static function within_schedule( $config ) {
        $now = time();

        if ( ! empty( $config['schedule_start'] ) && $now < $config['schedule_start'] ) {
            return false;
        }

        if ( ! empty( $config['schedule_end'] ) && $now > $config['schedule_end'] ) {
            return false;
        }

        return true;
    }

    private static function matches_page( $post_id, $config ) {
        if ( 'all' === $config['target_pages'] ) {
            return true;
        }

        $current_id = get_queried_object_id();
        $in_list    = $current_id && in_array( $current_id, $config['target_post_ids'], true );

        if ( 'include' === $config['target_pages'] ) {
            return $in_list;
        }

        // exclude
        return ! $in_list;
    }

    private static function matches_user_state( $config ) {
        $logged_in = is_user_logged_in();

        if ( 'guest' === $config['target_user_state'] && $logged_in ) {
            return false;
        }

        if ( 'logged_in' === $config['target_user_state'] && ! $logged_in ) {
            return false;
        }

        if ( ! empty( $config['target_roles'] ) ) {
            if ( ! $logged_in ) {
                return false;
            }
            $user = wp_get_current_user();
            if ( ! array_intersect( $config['target_roles'], (array) $user->roles ) ) {
                return false;
            }
        }

        return true;
    }

    private static function matches_device( $config ) {
        if ( 'all' === $config['target_device'] ) {
            return true;
        }

        $is_mobile = function_exists( 'wp_is_mobile' ) && wp_is_mobile();

        if ( 'mobile' === $config['target_device'] ) {
            return $is_mobile;
        }

        return ! $is_mobile; // desktop
    }

    /**
     * Reglas que el navegador debe resolver por sí mismo (localStorage,
     * cookies, query string) porque el servidor no tiene esa información
     * o porque la caché de página impediría reevaluarlas por request.
     */
    public static function client_rules( $config ) {
        return array(
            'freqMaxImpressions' => (int) $config['freq_max_impressions'],
            'freqCooldownDays'   => (int) $config['freq_cooldown_days'],
            'freqHideOnConvert'  => (bool) $config['freq_hide_on_convert'],
            'firstVisit'         => $config['target_first_visit'],
            'utmSource'          => $config['target_utm_source'],
        );
    }
}
