<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly
$unsupported_plugins = array(
        'woocommerce-dynamic-pricing/woocommerce-dynamic-pricing.php' => 'WooCommerce Dynamic Pricing',
        'pricing-deals-for-woocommerce/vt-pricing-deals.php' => 'VarkTech Pricing Deals for WooCommerce',
        'dynamic-pricing-and-discounts-for-woocommerce-basic-version/dynamic-pricing-and-discounts-for-woocommerce-basic-version.php' => 'Dynamic Pricing and Discounts for WooCommerce Basic Version',
        'wc-dynamic-pricing-and-discounts/wc-dynamic-pricing-and-discounts.php' => 'WooCommerce Dynamic Pricing & Discounts');
foreach ($unsupported_plugins as $plugin_path => $unsupported_plugin){
    $is_active = is_plugin_active($plugin_path);
    if($is_active){
        ?>
        <div class="notice inline notice-warning notice-alt">
            <p>
                <?php echo sprintf(esc_html__("An another discount plugin %s is active. Please disable this plugin, Woo Discount Rules might get conflict.", 'woo-discount-rules'), $unsupported_plugin); ?>
            </p>
        </div>
        <?php
    }
}
?>
<span id="woo-admin-message"></span>