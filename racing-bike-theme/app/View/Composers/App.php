<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

use function App\contact_info;
use function App\site_links;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        '*',
    ];

    /**
     * Data passed to every view before rendering.
     *
     * Todo va por `with()` y nada como método público: Acorn deja de extraer
     * los métodos públicos en cuanto `with()` devuelve algo (ver
     * `Roots\Acorn\View\Composer::merge()`), así que mezclarlos silenciaría
     * unos u otros. Además, los métodos públicos se envuelven en
     * `InvokableComponentVariable`, y pasar uno de esos a un atributo de
     * componente hace que Blade lo escape, lo que revienta con arrays.
     *
     * @return array
     */
    protected function with()
    {
        return [
            'siteName' => get_bloginfo('name', 'display'),
            'contact' => contact_info(),
            'links' => site_links(),
            // Nonce para los endpoints AJAX del carrito, leído por app.js desde
            // window.rbAjax (Vite carga app.js como módulo directo, sin pasar por
            // wp_enqueue_script(), así que wp_localize_script() no aplica aquí).
            'ajax' => [
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rb_cart_nonce'),
            ],
        ];
    }
}
