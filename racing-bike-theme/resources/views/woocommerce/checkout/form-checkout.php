<?php
/**
 * Override WooCommerce Checkout Form template for Sage 10 Blade.
 */
echo \Roots\view('woocommerce.checkout.form-checkout', get_defined_vars())->render();
