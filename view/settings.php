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
            <form method="post" id="discount_config">
                <div class="col-md-12" align="right">
                    <br/>
                    <input type="submit" id="saveConfig" value="<?php esc_html_e('Save', 'woo-discount-rules'); ?>" class="btn btn-success"/>
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('introduction/discount-price-rules-settings', 'settings', 'btn btn-info'); ?>
                </div>
                <div class="row">
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#wdr_s_general"><?php esc_html_e('General', 'woo-discount-rules'); ?></a></li>
                        <li><a data-toggle="tab" href="#wdr_s_price_rules"><?php esc_html_e('Price rules', 'woo-discount-rules'); ?></a></li>
                        <li><a data-toggle="tab" href="#wdr_s_cart_rules"><?php esc_html_e('Cart rules', 'woo-discount-rules'); ?></a></li>
                        <li><a data-toggle="tab" href="#wdr_s_performance"><?php esc_html_e('Performance', 'woo-discount-rules'); ?></a></li>
                        <li><a data-toggle="tab" href="#wdr_s_promotion"><?php esc_html_e('Promotion', 'woo-discount-rules'); ?></a></li>
                    </ul>

                    <div class="tab-content">
                        <div id="wdr_s_general" class="tab-pane fade in active">
                            <div class="">
                                <br/>
                                <h4><?php esc_html_e('General Settings', 'woo-discount-rules'); ?></h4>
                                <hr>
                            </div>
                            <div class="">
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('License Key :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="" name="license_key" id="woo-disc-license-key"
                                               value="<?php if (isset($data['license_key'])) echo $data['license_key']; ?>"
                                               placeholder="<?php esc_attr_e('Your Unique License Key', 'woo-discount-rules'); ?>">
                                        <input type="button" id="woo-disc-license-check" value="<?php esc_attr_e('Validate Key', 'woo-discount-rules'); ?>" class="button button-info">
                                        <?php
                                        $verifiedLicense = get_option('woo_discount_rules_verified_key', 0);
                                        if (isset($data['license_key']) && $data['license_key'] != '') {
                                            if ($verifiedLicense) {
                                                ?>
                                                <span class="license-success">&#10004;</span>
                                                <?php
                                            } else {
                                                ?>
                                                <div class="license-failed notice-message error inline notice-error notice-alt">
                                                    <?php esc_html_e('License key seems to be Invalid. Please enter a valid license key', 'woo-discount-rules'); ?>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('introduction/license-key-activation', 'license'); ?>
                                        <br>
                                        <div id="woo-disc-license-check-msg">

                                        </div>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Enable Bootstrap', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['enable_bootstrap'] = (isset($data['enable_bootstrap']) ? $data['enable_bootstrap'] : 1); ?>
                                    <div class="col-md-6">
                                        <label><input type="radio" name="enable_bootstrap" value="1" <?php echo ($data['enable_bootstrap'] == 1)? 'checked': '' ?>/> <?php esc_html_e('Yes', 'woo-discount-rules'); ?></label>
                                        <label><input type="radio" name="enable_bootstrap" value="0" <?php echo ($data['enable_bootstrap'] == 0)? 'checked': '' ?> /> <?php esc_html_e('No', 'woo-discount-rules'); ?></label>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Force refresh the cart widget while add and remove item to cart', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['force_refresh_cart_widget'] = (isset($data['force_refresh_cart_widget']) ? $data['force_refresh_cart_widget'] : 0); ?>
                                    <div class="col-md-6">
                                        <label><input type="radio" name="force_refresh_cart_widget" value="1" <?php echo ($data['force_refresh_cart_widget'] == 1)? 'checked': '' ?>/> <?php esc_html_e('Yes', 'woo-discount-rules'); ?></label>
                                        <label><input type="radio" name="force_refresh_cart_widget" value="0" <?php echo ($data['force_refresh_cart_widget'] == 0)? 'checked': '' ?> /> <?php esc_html_e('No', 'woo-discount-rules'); ?></label>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Disable the rules while have coupon(Third party) in cart', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['do_not_run_while_have_third_party_coupon'] = (isset($data['do_not_run_while_have_third_party_coupon']) ? $data['do_not_run_while_have_third_party_coupon'] : 0); ?>
                                    <div class="col-md-6">
                                        <label><input type="radio" name="do_not_run_while_have_third_party_coupon" value="1" <?php echo ($data['do_not_run_while_have_third_party_coupon'] == 1)? 'checked': '' ?>/> <?php esc_html_e('Yes', 'woo-discount-rules'); ?></label>
                                        <label><input type="radio" name="do_not_run_while_have_third_party_coupon" value="0" <?php echo ($data['do_not_run_while_have_third_party_coupon'] == 0)? 'checked': '' ?> /> <?php esc_html_e('No', 'woo-discount-rules'); ?></label>
                                    </div>
                                </div>
                                <?php if($isPro){ ?>
                                    <div class="row form-group">
                                        <div class="col-md-2">
                                            <label>
                                                <?php esc_html_e('Hide $0.00 (zero value) of coupon codes in the totals column. Useful when a coupon used with discount rule conditions', 'woo-discount-rules'); ?>
                                            </label>
                                        </div>
                                        <?php $data['remove_zero_coupon_price'] = (isset($data['remove_zero_coupon_price']) ? $data['remove_zero_coupon_price'] : 0); ?>
                                        <div class="col-md-6">
                                            <label><input type="radio" name="remove_zero_coupon_price" value="1" <?php echo ($data['remove_zero_coupon_price'] == 1)? 'checked': '' ?>/> <?php esc_html_e('Yes', 'woo-discount-rules'); ?></label>
                                            <label><input type="radio" name="remove_zero_coupon_price" value="0" <?php echo ($data['remove_zero_coupon_price'] == 0)? 'checked': '' ?> /> <?php esc_html_e('No', 'woo-discount-rules'); ?></label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div id="wdr_s_price_rules" class="tab-pane fade">
                            <div class="">
                                <div class="row form-group">
                                    <div class="col-md-12">
                                        <br/>
                                        <h4><?php esc_html_e('Price rules settings', 'woo-discount-rules'); ?></h4>
                                        <hr>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <?php $data['price_setup'] = (isset($data['price_setup']) ? $data['price_setup'] : 'first'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Rule Setup for Price:', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="price_setup">
                                            <option <?php if ($data['price_setup'] == 'first') { ?> selected=selected <?php } ?>
                                                    value="first" selected="selected"><?php esc_html_e('Apply first matched rule', 'woo-discount-rules'); ?>
                                            </option>
                                            <option
                                                    value="all" <?php if (!$pro) { ?> disabled <?php }
                                            if ($data['price_setup'] == 'all') { ?> selected=selected <?php } ?>>
                                                <?php if (!$pro) { ?>
                                                    <?php esc_html_e('Apply all matched rules', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                <?php } else { ?>
                                                    <?php esc_html_e('Apply all matched rules', 'woo-discount-rules'); ?>
                                                <?php } ?>
                                            </option>
                                            <option
                                                    value="biggest" <?php if (!$pro) { ?> disabled <?php }
                                            if ($data['price_setup'] == 'biggest') { ?> selected=selected <?php } ?>>
                                                <?php if (!$pro) { ?>
                                                    <?php esc_html_e('Apply biggest discount', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                <?php } else { ?>
                                                    <?php esc_html_e('Apply biggest discount', 'woo-discount-rules'); ?>
                                                <?php } ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <?php $data['show_price_discount_on_product_page'] = (isset($data['show_price_discount_on_product_page']) ? $data['show_price_discount_on_product_page'] : 'dont'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Show Price discount on product page :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="show_price_discount_on_product_page" id="show_price_discount_on_product_page">
                                            <option <?php if ($data['show_price_discount_on_product_page'] == 'show') { ?> selected=selected <?php } ?>
                                                    value="show"><?php esc_html_e('Show', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['show_price_discount_on_product_page'] == 'dont') { ?> selected=selected <?php } ?>
                                                    value="dont"><?php esc_html_e("Don't Show", 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                        <div class="notice notice-info"><p><?php esc_html_e('It displays only if any rule matches', 'woo-discount-rules'); ?></p></div>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <?php $data['show_sale_tag_on_product_page'] = (isset($data['show_sale_tag_on_product_page']) ? $data['show_sale_tag_on_product_page'] : 'dont'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Show Sale tag on product page :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="show_sale_tag_on_product_page">
                                            <option <?php if ($data['show_sale_tag_on_product_page'] == 'show') { ?> selected=selected <?php } ?>
                                                    value="show"><?php esc_html_e('Show', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['show_sale_tag_on_product_page'] == 'dont') { ?> selected=selected <?php } ?>
                                                    value="dont"><?php esc_html_e("Don't Show", 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                        <div class="notice notice-info"><p><?php esc_html_e('It displays only if any rule matches', 'woo-discount-rules'); ?></p></div>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <?php $data['show_discount_table'] = (isset($data['show_discount_table']) ? $data['show_discount_table'] : 'show'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Discount Table :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="show_discount_table" id="show_discount_table">
                                            <option <?php if ($data['show_discount_table'] == 'show') { ?> selected=selected <?php } ?>
                                                    value="show"><?php esc_html_e('Show', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['show_discount_table'] == 'dont') { ?> selected=selected <?php } ?>
                                                    value="dont"><?php esc_html_e("Don't Show", 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group discount_table_options">
                                    <?php $data['discount_table_placement'] = (isset($data['discount_table_placement']) ? $data['discount_table_placement'] : 'before_cart_form'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Table placement:', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="discount_table_placement">
                                            <option <?php if ($data['discount_table_placement'] == 'before_cart_form') { ?> selected=selected <?php } ?>
                                                    value="before_cart_form"><?php esc_html_e('Before cart form', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['discount_table_placement'] == 'after_cart_form') { ?> selected=selected <?php } ?>
                                                    value="after_cart_form"><?php esc_html_e("After cart form", 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group discount_table_options">
                                    <?php $data['show_discount_title_table'] = (isset($data['show_discount_title_table']) ? $data['show_discount_title_table'] : 'show'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Show column title on table :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="show_discount_title_table">
                                            <option <?php if ($data['show_discount_title_table'] == 'show') { ?> selected=selected <?php } ?>
                                                    value="show"><?php esc_html_e('Show', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['show_discount_title_table'] == 'dont') { ?> selected=selected <?php } ?>
                                                    value="dont"><?php esc_html_e("Don't Show", 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group discount_table_options">
                                    <?php $data['show_column_range_table'] = (isset($data['show_column_range_table']) ? $data['show_column_range_table'] : 'show'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Show column discount range on table :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="show_column_range_table">
                                            <option <?php if ($data['show_column_range_table'] == 'show') { ?> selected=selected <?php } ?>
                                                    value="show"><?php esc_html_e('Show', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['show_column_range_table'] == 'dont') { ?> selected=selected <?php } ?>
                                                    value="dont"><?php esc_html_e("Don't Show", 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group discount_table_options">
                                    <?php $data['show_column_discount_table'] = (isset($data['show_column_discount_table']) ? $data['show_column_discount_table'] : 'show'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Show column discount on table :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="show_column_discount_table">
                                            <option <?php if ($data['show_column_discount_table'] == 'show') { ?> selected=selected <?php } ?>
                                                    value="show"><?php esc_html_e('Show', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['show_column_discount_table'] == 'dont') { ?> selected=selected <?php } ?>
                                                    value="dont"><?php esc_html_e("Don't Show", 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Show strikeout discount in cart item', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['show_strikeout_in_cart'] = (isset($data['show_strikeout_in_cart']) ? $data['show_strikeout_in_cart'] : 1); ?>
                                    <div class="col-md-6">
                                        <label><input type="radio" name="show_strikeout_in_cart" value="1" <?php echo ($data['show_strikeout_in_cart'] == 1)? 'checked': '' ?>/> <?php esc_html_e('Yes', 'woo-discount-rules'); ?></label>
                                        <label><input type="radio" name="show_strikeout_in_cart" value="0" <?php echo ($data['show_strikeout_in_cart'] == 0)? 'checked': '' ?> /> <?php esc_html_e('No', 'woo-discount-rules'); ?></label>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Auto add free product on coupon applied (For coupon based rules)', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['add_free_product_on_coupon_applied'] = (isset($data['add_free_product_on_coupon_applied']) ? $data['add_free_product_on_coupon_applied'] : 0); ?>
                                    <div class="col-md-6">
                                        <label><input type="radio" name="add_free_product_on_coupon_applied" value="1" <?php echo ($data['add_free_product_on_coupon_applied'] == 1)? 'checked': '' ?>/> <?php esc_html_e('Yes', 'woo-discount-rules'); ?></label>
                                        <label><input type="radio" name="add_free_product_on_coupon_applied" value="0" <?php echo ($data['add_free_product_on_coupon_applied'] == 0)? 'checked': '' ?> /> <?php esc_html_e('No', 'woo-discount-rules'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="wdr_s_cart_rules" class="tab-pane fade">
                            <div class="">
                                <div class="row form-group">
                                    <div class="col-md-12">
                                        <br/>
                                        <h4><?php esc_html_e('Cart rules settings', 'woo-discount-rules'); ?></h4>
                                        <hr>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Coupon Name to be displayed :', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="" name="coupon_name"
                                               value="<?php if (isset($data['coupon_name'])) echo $data['coupon_name']; ?>"
                                               placeholder="<?php esc_html_e('Discount Coupon Name', 'woo-discount-rules'); ?>">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <?php $data['cart_setup'] = (isset($data['cart_setup']) ? $data['cart_setup'] : 'first'); ?>
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Rule Setup for Cart:', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="cart_setup">
                                            <option <?php if ($data['cart_setup'] == 'first') { ?> selected=selected <?php } ?>
                                                    value="first"><?php esc_html_e('Apply first matched rule', 'woo-discount-rules'); ?>
                                            </option>
                                            <option
                                                    value="all" <?php if (!$pro) { ?> disabled <?php }
                                            if ($data['cart_setup'] == 'all') { ?> selected=selected <?php } ?>>
                                                <?php if (!$pro) { ?>
                                                    <?php esc_html_e('Apply all matched rules', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                <?php } else { ?>
                                                    <?php esc_html_e('Apply all matched rules', 'woo-discount-rules'); ?>
                                                <?php } ?>
                                            </option>
                                            <option
                                                    value="biggest" <?php if (!$pro) { ?> disabled <?php }
                                            if ($data['cart_setup'] == 'biggest') { ?> selected=selected <?php } ?>>
                                                <?php if (!$pro) { ?>
                                                    <?php esc_html_e('Apply biggest discount', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                <?php } else { ?>
                                                    <?php esc_html_e('Apply biggest discount', 'woo-discount-rules'); ?>
                                                <?php } ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Enable free shipping option', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['enable_free_shipping'] = (isset($data['enable_free_shipping']) ? $data['enable_free_shipping'] : "none"); ?>
                                    <div class="col-md-6">
                                        <?php
                                        if(!$isPro){
                                            esc_html_e('Supported in PRO version', 'woo-discount-rules');
                                            ?>
                                            <select name="enable_free_shipping" id="enable_free_shipping" style="display: none">
                                                <option value="none"><?php esc_html_e('Disabled', 'woo-discount-rules'); ?></option>
                                            </select>
                                            <?php
                                        } else {
                                            ?>
                                            <select class="selectpicker" name="enable_free_shipping" id="enable_free_shipping">
                                                <option <?php if ($data['enable_free_shipping'] == "none") { ?> selected=selected <?php } ?>
                                                        value="none"><?php esc_html_e('Disabled', 'woo-discount-rules'); ?>
                                                </option>
                                                <option <?php if ($data['enable_free_shipping'] == "free_shipping") { ?> selected=selected <?php } ?>
                                                        value="free_shipping"><?php esc_html_e('Use Woocommerce free shipping', 'woo-discount-rules'); ?>
                                                </option>
                                                <option <?php if ($data['enable_free_shipping'] == "woodiscountfree") { ?> selected=selected <?php } ?>
                                                        value="woodiscountfree"><?php esc_html_e('Use Woo-Discount free shipping', 'woo-discount-rules'); ?>
                                                </option>
                                            </select>
                                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('shipping-based-discounts/free-shipping-cart-based-rule', 'free_shipping');
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                                if($isPro){
                                    ?>
                                    <div class="row form-group" id="woodiscount_settings_free_shipping_con">
                                        <div class="col-md-2">
                                            <label>
                                                <?php esc_html_e('Free shipping text to be displayed', 'woo-discount-rules'); ?>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <?php $data['free_shipping_text'] = ((isset($data['free_shipping_text']) && !empty($data['free_shipping_text'])) ? $data['free_shipping_text'] : __( 'Free Shipping', 'woo-discount-rules' )); ?>
                                            <input type="text" class="" name="free_shipping_text"
                                                   value="<?php echo $data['free_shipping_text']; ?>"
                                                   placeholder="<?php esc_html_e('Free Shipping title', 'woo-discount-rules'); ?>">
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class="row form-group" style="display: none"><!-- Hide this because it is not required after v1.4.36 -->
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Draft', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <?php
                                        $checked = 0;
                                        if (isset($data['show_draft']) && $data['show_draft'] == 1){
                                            $checked = 1;
                                        } ?>
                                        <input type="checkbox" class="" id="show_draft_1" name="show_draft"
                                               value="1" <?php if($checked){ echo 'checked'; } ?>> <label class="checkbox_label" for="show_draft_1"><?php esc_html_e('Exclude Draft products in product select box.', 'woo-discount-rules'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="wdr_s_performance" class="tab-pane fade">
                            <div class="">
                                <div class="row form-group">
                                    <div class="col-md-12">
                                        <br/>
                                        <h4><?php esc_html_e('Performance settings', 'woo-discount-rules'); ?></h4>
                                        <hr>
                                    </div>
                                </div>
                                <?php $data['enable_variable_product_cache'] = (isset($data['enable_variable_product_cache']) ? $data['enable_variable_product_cache'] : 0); ?>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Enable cache for variable products table content', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>

                                    <div class="col-md-6">
                                        <label><input type="radio" name="enable_variable_product_cache" value="1" <?php echo ($data['enable_variable_product_cache'] == 1)? 'checked': '' ?>/> <?php esc_html_e('Yes', 'woo-discount-rules'); ?></label>
                                        <label><input type="radio" name="enable_variable_product_cache" value="0" <?php echo ($data['enable_variable_product_cache'] == 0)? 'checked': '' ?> /> <?php esc_html_e('No', 'woo-discount-rules'); ?></label>
                                    </div>
                                </div>
                                <div class="row form-group enable_variable_product_cache_con">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Clear cache', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="button" id="refresh_wdr_cache" value="<?php esc_attr_e('Clear cache', 'woo-discount-rules'); ?>" class="btn btn-warning">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="wdr_s_promotion" class="tab-pane fade">
                            <div class="">
                                <div class="row form-group">
                                    <div class="col-md-12">
                                        <br/>
                                        <h4><?php esc_html_e('Promotion settings', 'woo-discount-rules'); ?></h4>
                                        <hr>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Message on apply price rules in cart', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['message_on_apply_price_discount'] = (isset($data['message_on_apply_price_discount']) ? $data['message_on_apply_price_discount'] : "no"); ?>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="message_on_apply_price_discount" id="message_on_apply_price_discount">
                                            <option <?php if ($data['message_on_apply_price_discount'] == "no") { ?> selected=selected <?php } ?>
                                                    value="no"><?php esc_html_e('Disabled', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['message_on_apply_price_discount'] == "yes") { ?> selected=selected <?php } ?>
                                                    value="yes"><?php esc_html_e('Enable', 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group message_on_apply_price_discount_options">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Text', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['message_on_apply_price_discount_text'] = (isset($data['message_on_apply_price_discount_text']) ? $data['message_on_apply_price_discount_text'] : "Discount <strong>\"{{title}}\"</strong> has been applied to your cart."); ?>
                                    <div class="col-md-6">
                                        <textarea name="message_on_apply_price_discount_text" class="message_on_apply_discount_textarea" value="<?php echo esc_attr($data['message_on_apply_price_discount_text']); ?>"><?php echo $data['message_on_apply_price_discount_text']; ?></textarea>
                                        <div class="wdr_desc_text_con">
                                            <span class="wdr_desc_text">
                                                <?php esc_html_e('{{title}} -> displays title', 'woo-discount-rules'); ?><br>
                                                <?php esc_html_e('{{description}} -> displays description', 'woo-discount-rules'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Message on apply cart rules in cart', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['message_on_apply_cart_discount'] = (isset($data['message_on_apply_cart_discount']) ? $data['message_on_apply_cart_discount'] : "no"); ?>
                                    <div class="col-md-6">
                                        <select class="selectpicker" name="message_on_apply_cart_discount" id="message_on_apply_cart_discount">
                                            <option <?php if ($data['message_on_apply_cart_discount'] == "no") { ?> selected=selected <?php } ?>
                                                    value="no"><?php esc_html_e('Disabled', 'woo-discount-rules'); ?>
                                            </option>
                                            <option <?php if ($data['message_on_apply_cart_discount'] == "yes") { ?> selected=selected <?php } ?>
                                                    value="yes"><?php esc_html_e('Enable', 'woo-discount-rules'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group message_on_apply_cart_discount_options">
                                    <div class="col-md-2">
                                        <label>
                                            <?php esc_html_e('Text', 'woo-discount-rules'); ?>
                                        </label>
                                    </div>
                                    <?php $data['message_on_apply_cart_discount_text'] = (isset($data['message_on_apply_cart_discount_text']) ? $data['message_on_apply_cart_discount_text'] : "Discount <strong>\"{{title}}\"</strong> has been applied to your cart."); ?>
                                    <div class="col-md-6">
                                        <textarea name="message_on_apply_cart_discount_text" class="message_on_apply_discount_textarea" value="<?php echo esc_attr($data['message_on_apply_cart_discount_text']); ?>"><?php echo $data['message_on_apply_cart_discount_text']; ?></textarea>
                                        <div class="wdr_desc_text_con">
                                            <span class="wdr_desc_text">
                                                <?php esc_html_e('{{title}} -> displays title', 'woo-discount-rules'); ?><br>
                                                <?php esc_html_e('{{description}} -> displays description', 'woo-discount-rules'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="ajax_path" value="<?php echo admin_url('admin-ajax.php') ?>">
            </form>
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