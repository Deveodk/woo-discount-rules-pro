<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<div class="col-md-3">
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="col-md-12">
                <br>
                <a href="https://www.flycart.org/products/wordpress/woocommerce-discount-rules?utm_source=wpwoodiscountrules&utm_medium=plugin&utm_campaign=inline&utm_content=woo-discount-rules" target="_blank" class="btn btn-success"><?php esc_html_e('Looking for more features? Upgrade to PRO', 'woo-discount-rules'); ?></a>
            </div>
            <div class="col-md-12">
                <div id="" align="right">
                    <div class="woo-side-button" class="hide-on-click">
                        <span id="sidebar_text"><?php esc_html_e('Hide', 'woo-discount-rules'); ?></span>
                        <span id="sidebar_icon" class="dashicons dashicons-arrow-left"></span>
                    </div>
                </div>
                <div class="woo-side-panel">
                    <?php
                    echo FlycartWooDiscountRulesGeneralHelper::getSideBarContent();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>