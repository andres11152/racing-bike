<?php
/**
 * Override WooCommerce Cart template for Sage 10 Blade.
 */
echo \Roots\view('woocommerce.cart.cart', get_defined_vars())->render();
