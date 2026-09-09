<?php

/**
 * Datos oficiales de la empresa — fuente única de verdad.
 *
 * El número de WhatsApp, el correo y la dirección estaban repetidos a mano en
 * el footer, el FAQ, la ficha de producto, el carrito, el checkout y el plugin
 * de CRO. Cuando cambió el número quedaron valores en conflicto conviviendo en
 * el mismo sitio. Todo lo que muestre un dato de contacto debe leerlo de aquí.
 */

namespace App;

/**
 * Datos de contacto y de identidad de la empresa.
 *
 * @return array<string, mixed>
 */
function contact_info(): array
{
    static $info = null;

    if ($info !== null) {
        return $info;
    }

    $founded = 1998;

    // El número en formato E.164 (sin "+") es el que consume wa.me.
    $whatsapp = '573118485643';

    $info = apply_filters('rb_contact_info', [
        'whatsapp'         => $whatsapp,
        'whatsapp_display' => '311 848 5643',
        'whatsapp_url'     => 'https://wa.me/'.$whatsapp,
        'email'            => 'contacto@racingbike.com.co',
        'email_contacto'   => 'contacto@racingbike.com.co',
        'email_garantias'  => 'garantias@racingbike.com.co',
        'email_pagos'      => 'pagos@racingbike.com.co',
        'email_ventas'     => 'ventas@racingbike.com.co',
        'emails'           => [
            'contacto'  => 'contacto@racingbike.com.co',
            'garantias' => 'garantias@racingbike.com.co',
            'pagos'     => 'pagos@racingbike.com.co',
            'ventas'    => 'ventas@racingbike.com.co',
        ],
        'address'          => 'Avenida Calle 45A Sur #52C-47',
        'maps_url'         => 'https://maps.app.goo.gl/LwjNSDvNEy6UpGcm9',
        // El enlace corto de arriba no funciona dentro de un iframe; este sí, y
        // apunta a la ficha del negocio, no a una búsqueda por dirección.
        'maps_embed'       => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3977.098!2d-74.1359943!3d4.5949229!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e3f9f94f608be3b%3A0x36b97f6b086cee3!2sRacing%20Bike%20Shop!5e0!3m2!1ses-419!2sco!4v1714000000000!5m2!1ses-419!2sco',
        'city'             => 'Bogotá',
        'region'           => 'Cundinamarca',
        'country'          => 'CO',
        'nit'              => '1022436363-1',
        'founded'          => $founded,
        // Se calcula: escribir "28 años" a mano garantiza que quede obsoleto.
        'years'            => max(0, (int) current_time('Y') - $founded),
        'instagram'        => 'https://www.instagram.com/racing_bike98/',
        'facebook'         => 'https://www.facebook.com/RACINBIKE/',
        'tiktok'           => 'https://www.tiktok.com/@racing.bike1998',
    ]);

    return $info;
}

/**
 * URL de una página por slug, resuelta una sola vez por request.
 *
 * Devuelve la home si la página aún no existe, para que el footer nunca
 * imprima un `href="#"` muerto como pasaba antes.
 */
function page_url(string $slug): string
{
    static $cache = [];

    if (! isset($cache[$slug])) {
        $page = get_page_by_path($slug);

        $cache[$slug] = ($page && $page->post_status === 'publish')
            ? get_permalink($page)
            : home_url('/');
    }

    return $cache[$slug];
}

/**
 * Enlaces institucionales usados por el footer y la navegación secundaria.
 *
 * @return array<string, string>
 */
function site_links(): array
{
    return [
        'about'     => page_url('sobre-nosotros'),
        'contact'   => page_url('contacto'),
        'faqs'      => page_url('preguntas-frecuentes'),
        'legal'     => page_url('politicas'),
        'terms'     => page_url('politicas').'#terminos',
        'shipping'  => page_url('politicas').'#envios',
        'returns'   => page_url('politicas').'#cambios',
        'privacy'   => page_url('politicas').'#privacidad',
        'warranty'  => page_url('politicas').'#garantias',
        'sizeGuide' => page_url('encuentra-tu-talla'),
        'builder'   => page_url('armar-bicicleta'),
        'road'      => product_cat_url('ruta'),
        'gravel'    => product_cat_url('gravel'),
        'parts'     => product_cat_url('componentes'),
    ];
}

/**
 * URL de una categoría de producto, con la tienda como respaldo si el término
 * no existe (catálogos recién instalados, o si se renombra la categoría).
 */
function product_cat_url(string $slug): string
{
    static $cache = [];

    if (! isset($cache[$slug])) {
        $link = get_term_link($slug, 'product_cat');

        $cache[$slug] = is_wp_error($link)
            ? (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/'))
            : $link;
    }

    return $cache[$slug];
}

/**
 * Enlace de WhatsApp con un mensaje inicial opcional.
 */
function whatsapp_url(string $message = ''): string
{
    $url = contact_info()['whatsapp_url'];

    return $message
        ? $url.'?text='.rawurlencode($message)
        : $url;
}
