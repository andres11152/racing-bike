<?php
/**
 * Override WooCommerce Orders template for Sage 10 Blade.
 */
echo \Roots\view('woocommerce.myaccount.orders', get_defined_vars())->render();
