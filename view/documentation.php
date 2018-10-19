<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$active = 'settings';
include_once(WOO_DISCOUNT_DIR . '/view/includes/header.php');
include_once(WOO_DISCOUNT_DIR . '/view/includes/menu.php');

$data = $config;

if (is_string($data)) $data = json_decode($data, true);
$flycartWooDiscountRulesPurchase = new FlycartWooDiscountRulesPurchase();
$isPro = $flycartWooDiscountRulesPurchase->isPro();
?>

<div class="container-fluid woo_discount_loader_outer">
    <div class="row-fluid">
        <div class="<?php echo $isPro? 'col-md-12': 'col-md-8'; ?>">
            <div class="row form-group">
                <div class="col-md-12">
                    <br/>
                    <h4><?php esc_html_e('Documentation', 'woo-discount-rules'); ?></h4>
                    <hr>
                </div>
            </div>
            <div class="row form-group enable_variable_product_cache_con">
                <div class="col-md-12">
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTMLForDocumentation('introduction/getting-started', 'getting_started', esc_html__('Getting started', 'woo-discount-rules'), esc_html__('Welcome onboard', 'woo-discount-rules')); ?>
                </div>
                <div class="col-md-12">
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTMLForDocumentation('introduction/price-discount-rules', 'price_rules', esc_html__('Price Discount Rules', 'woo-discount-rules'), esc_html__('Learn all about creating a price discount rules', 'woo-discount-rules')); ?>
                </div>
                <div class="col-md-12">
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTMLForDocumentation('introduction/cart-discount-rules', 'cart_rules', esc_html__('Cart Discount Rules', 'woo-discount-rules'), esc_html__('Cart based discount rules with examples.', 'woo-discount-rules')); ?>
                </div>
                <div class="col-md-12">
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTMLForDocumentation('buy-one-get-one-deals/how-to-create-a-perfect-bogo-discount-rule-in-woocommerce', 'perfect_bogo', esc_html__('How to create a perfect BOGO discount rule in WooCommerce', 'woo-discount-rules'), esc_html__('Buy One Get One deals can be simple to complex. Learn how to get them working correct in your online store', 'woo-discount-rules')); ?>
                </div>
                <div class="col-md-12">
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTMLForDocumentation('role-based-discounts/user-role-based-discount-rules', 'role_based', esc_html__('User Role based discount rules', 'woo-discount-rules'), esc_html__('Learn how to create user role based / customer group based discount in WooCommerce', 'woo-discount-rules')); ?>
                </div>
                <div class="col-md-12">
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTMLForDocumentation('coupon-based-discounts/activate-discount-rule-using-a-coupon-code-in-woocommerce', 'coupon_based', esc_html__('Activate discount rule using a coupon code in WooCommerce', 'woo-discount-rules'), esc_html__('Apply the dynamic discount rules after the customer enters a valid coupon code', 'woo-discount-rules')); ?>
                </div>
                <div class="col-md-12">
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTMLForDocumentation('purchase-history-based-discounts/purchase-history-based-discount', 'purchase_history', esc_html__('Purchase History Based Discount', 'woo-discount-rules'), esc_html__('Price Rule and Cart Rule which gives discount based on the purchase history', 'woo-discount-rules')); ?>
                </div>
            </div>
        </div>
        <?php if(!$isPro){ ?>
            <div class="col-md-1"></div>
            <!-- Sidebar -->
            <?php include_once(__DIR__ . '/template/sidebar.php'); ?>
            <!-- Sidebar END -->
        <?php } ?>
    </div>
    <div class="woo_discount_loader">
        <div class="lds-ripple"><div></div><div></div></div>
    </div>
</div>