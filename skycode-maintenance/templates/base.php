<?php
/**
 * Plantilla del modo en construcción / mantenimiento.
 * $settings está disponible desde SKC_MM_Renderer::render().
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logo_url    = $settings['logo_id'] ? wp_get_attachment_image_url( $settings['logo_id'], 'medium' ) : '';
$is_video_bg = 'video' === $settings['bg_type'] && $settings['bg_value'];
$bg_css      = 'image' === $settings['bg_type'] && $settings['bg_value']
    ? 'background-image:url(' . esc_url( $settings['bg_value'] ) . ');background-size:cover;background-position:center;'
    : 'background-color:' . ( $is_video_bg ? '#000' : esc_attr( $settings['bg_value'] ) ) . ';';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $settings['headline'] ? $settings['headline'] : get_bloginfo( 'name' ) ); ?></title>
<?php if ( $settings['seo_noindex'] ) : ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syncopate:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{
    --skc-accent: <?php echo esc_attr( $settings['accent'] ); ?>;
    --skc-text: <?php echo esc_attr( $settings['text_color'] ); ?>;
    --skc-font-display: "Syncopate", sans-serif;
    --skc-font-sans: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
html,body{margin:0;padding:0;height:100%;}
body{
    <?php echo $bg_css; // phpcs:ignore ?>
    color: var(--skc-text);
    font-family: var(--skc-font-sans);
    display:flex;align-items:center;justify-content:center;min-height:100vh;
    box-sizing:border-box;
}
*, *::before, *::after{box-sizing:inherit;}
<?php if ( $is_video_bg ) : ?>
.skc-mm-bg-video{position:fixed;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:-1;}
.skc-mm-wrap{position:relative;z-index:1;}
<?php endif; ?>
<?php echo SKC_MM_Renderer::skin_css( $settings['skin'] ); // phpcs:ignore ?>
.skc-mm-headline{font-family:var(--skc-font-display);text-transform:uppercase;letter-spacing:0.1em;font-weight:700;}
<?php if ( $settings['custom_css'] ) : ?>
<?php echo $settings['custom_css']; // phpcs:ignore -- se sanea como texto plano al guardar. ?>
<?php endif; ?>
</style>
</head>
<body>
<?php if ( $is_video_bg ) : ?>
    <video class="skc-mm-bg-video" autoplay muted loop playsinline<?php echo $settings['bg_poster'] ? ' poster="' . esc_url( $settings['bg_poster'] ) . '"' : ''; ?>>
        <?php if ( $settings['bg_value_mobile'] ) : ?>
            <source src="<?php echo esc_url( $settings['bg_value_mobile'] ); ?>" media="(max-width: 767px)">
        <?php endif; ?>
        <source src="<?php echo esc_url( $settings['bg_value'] ); ?>">
    </video>
<?php endif; ?>
<main class="skc-mm-wrap">
    <?php if ( $logo_url ) : ?>
        <img class="skc-mm-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
    <?php endif; ?>

    <?php if ( $settings['headline'] ) : ?>
        <h1 class="skc-mm-headline"><?php echo esc_html( $settings['headline'] ); ?></h1>
    <?php endif; ?>

    <?php if ( $settings['subtext'] ) : ?>
        <p class="skc-mm-subtext"><?php echo esc_html( $settings['subtext'] ); ?></p>
    <?php endif; ?>

    <?php if ( $settings['countdown_to'] > time() ) : ?>
        <div class="skc-mm-countdown" data-until="<?php echo esc_attr( $settings['countdown_to'] * 1000 ); ?>">
            <span class="skc-mm-cd-item"><span data-unit="days">00</span><span class="skc-mm-cd-label"><?php esc_html_e( 'días', 'skycode-maintenance' ); ?></span></span>
            <span class="skc-mm-cd-item"><span data-unit="hours">00</span><span class="skc-mm-cd-label"><?php esc_html_e( 'horas', 'skycode-maintenance' ); ?></span></span>
            <span class="skc-mm-cd-item"><span data-unit="minutes">00</span><span class="skc-mm-cd-label"><?php esc_html_e( 'min', 'skycode-maintenance' ); ?></span></span>
            <span class="skc-mm-cd-item"><span data-unit="seconds">00</span><span class="skc-mm-cd-label"><?php esc_html_e( 'seg', 'skycode-maintenance' ); ?></span></span>
        </div>
        <script>
        (function(){
            var until = <?php echo (int) ( $settings['countdown_to'] * 1000 ); ?>;
            var el = document.currentScript.previousElementSibling;
            function tick(){
                var diff = Math.max(0, until - Date.now());
                var s = Math.floor(diff/1000);
                var d = Math.floor(s/86400); s -= d*86400;
                var h = Math.floor(s/3600); s -= h*3600;
                var m = Math.floor(s/60); s -= m*60;
                el.querySelector('[data-unit="days"]').textContent = String(d).padStart(2,'0');
                el.querySelector('[data-unit="hours"]').textContent = String(h).padStart(2,'0');
                el.querySelector('[data-unit="minutes"]').textContent = String(m).padStart(2,'0');
                el.querySelector('[data-unit="seconds"]').textContent = String(s).padStart(2,'0');
            }
            tick();
            setInterval(tick, 1000);
        })();
        </script>
    <?php endif; ?>

    <?php if ( $settings['subscribe_enabled'] ) : ?>
        <form class="skc-mm-subscribe" id="skc-mm-subscribe-form">
            <input type="email" name="email" placeholder="<?php esc_attr_e( 'Tu correo electrónico', 'skycode-maintenance' ); ?>" required>
            <input type="text" name="skc_mm_hp" class="skc-mm-hp" tabindex="-1" autocomplete="off">
            <button type="submit"><?php esc_html_e( 'Avísame', 'skycode-maintenance' ); ?></button>
            <p class="skc-mm-subscribe-msg" aria-live="polite"></p>
        </form>
        <script>
        (function(){
            var form = document.getElementById('skc-mm-subscribe-form');
            if (!form) return;
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var msg = form.querySelector('.skc-mm-subscribe-msg');
                var data = new FormData(form);
                data.append('action', 'skc_mm_subscribe');
                data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'skc_mm_subscribe' ) ); ?>');
                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin'
                }).then(function(r){ return r.json(); }).then(function(res){
                    msg.textContent = res.data && res.data.message ? res.data.message : '';
                    if (res.success) { form.reset(); }
                }).catch(function(){
                    msg.textContent = '<?php echo esc_js( __( 'Ha ocurrido un error, inténtalo de nuevo.', 'skycode-maintenance' ) ); ?>';
                });
            });
        })();
        </script>
    <?php endif; ?>

    <?php if ( ! empty( $settings['socials'] ) ) : ?>
        <div class="skc-mm-socials">
            <?php foreach ( $settings['socials'] as $url ) : ?>
                <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_parse_url( $url, PHP_URL_HOST ) ); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( $settings['custom_html'] ) : ?>
        <div class="skc-mm-custom">
            <?php echo $settings['custom_html']; // phpcs:ignore -- saneado con wp_kses_post al guardar. ?>
        </div>
    <?php endif; ?>

    <?php if ( $settings['footer_text'] ) : ?>
        <footer class="skc-mm-footer"><?php echo esc_html( $settings['footer_text'] ); ?></footer>
    <?php endif; ?>
</main>
</body>
</html>
