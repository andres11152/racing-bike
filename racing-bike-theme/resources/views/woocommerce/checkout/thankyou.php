<?php
/**
 * Override WooCommerce Order Received (Thank You) template for Sage Blade.
 */
echo \Roots\view('woocommerce.checkout.thankyou', get_defined_vars())->render();
