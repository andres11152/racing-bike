<?php
/**
 * Override WooCommerce Cart Totals template for Sage 10 Blade.
 */
echo \Roots\view('woocommerce.cart.cart-totals', get_defined_vars())->render();
