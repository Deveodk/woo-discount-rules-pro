<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php
$proText = $purchase->getProText();
?>
<i><h2><?php esc_html_e('Woo Discount Rules', 'woo-discount-rules'); ?> <?php echo $proText; ?> <span class="woo-discount-version">v<?php echo WOO_DISCOUNT_VERSION; ?></span></h2></i>
<hr>
<h3 class="nav-tab-wrapper">
    <a class="nav-tab general_tab nav-tab-active" href=javascript:void(0)>
        <i class="fa fa-tags" style="font-size: 0.8em;"></i> <?php esc_html_e('General', 'woo-discount-rules'); ?> </a>
    <a class="nav-tab restriction_tab" href=javascript:void(0)>
        <i class="fa fa-shopping-cart" style="font-size: 0.8em;"></i> <?php esc_html_e('Condition', 'woo-discount-rules'); ?> </a>
    <a class="nav-tab discount_tab" href=javascript:void(0)>
        <i class="fa fa-cogs" style="font-size: 0.8em;"></i> <?php esc_html_e('Discount', 'woo-discount-rules'); ?> </a>
</h3>
