<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

register_activation_hook(__FILE__, 'onWooDiscountActivate');
register_deactivation_hook(__FILE__, 'onWooDiscountDeactivation');

if (!function_exists('onWooDiscountActivate')) {
    function onWooDiscountActivate() {
        // Dependency Check.
        if (!in_array('woocommerce/woocommerce.php', get_option('active_plugins'))) wp_die('Please Install WooCommerce to Continue !');
    }
}
if (!function_exists('onWooDiscountDeactivation')) {
    function onWooDiscountDeactivation() {}
}