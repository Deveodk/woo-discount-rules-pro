<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
$active = 'pricing-rules';
include_once(WOO_DISCOUNT_DIR . '/view/includes/header.php');
include_once(WOO_DISCOUNT_DIR . '/view/includes/sub-menu.php');

$config = (isset($config)) ? $config : '{}';
$rule_id = 0;
$form = '';

$status = 'publish';

if (is_string($config)) {
    $data = json_decode($config);
} elseif (is_object($config)) {
    if (isset($config->form)) {
        $form = $config->form;
    }
}
$data = $config;
$rule_id = (isset($data->ID)) ? $data->ID : 0;

$flycartWooDiscountRulesPurchase = new FlycartWooDiscountRulesPurchase();
$isPro = $flycartWooDiscountRulesPurchase->isPro();
$attributes = array();
if($isPro){
    $attributes = FlycartWooDiscountRulesAdvancedHelper::get_all_product_attributes();
}
$woo_settings = new FlycartWooDiscountBase();
$do_not_run_while_have_third_party_coupon = $woo_settings->getConfigData('do_not_run_while_have_third_party_coupon', 0);
$current_date_and_time = FlycartWooDiscountRulesGeneralHelper::getCurrentDateAndTimeBasedOnTimeZone();
?>
<div class="container-fluid woo_discount_loader_outer">
    <form id="form_price_rule">
        <div class="row-fluid">
            <div class="<?php echo $isPro? 'col-md-12': 'col-md-8'; ?>">
                <div class="col-md-12 rule_buttons_con" align="right">
                    <input type="submit" id="savePriceRule" value="<?php esc_html_e('Save Rule', 'woo-discount-rules'); ?>" class="btn btn-primary">
                    <a href="?page=woo_discount_rules" class="btn btn-warning"><?php esc_html_e('Cancel and go back to list', 'woo-discount-rules'); ?></a>
                    <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('introduction/price-discount-rules', 'price_rules', 'btn btn-info'); ?>
                </div>
                <?php if ($rule_id == 0) { ?>
                    <div class="col-md-12"><h2><?php esc_html_e('New Price Rule', 'woo-discount-rules'); ?></h2></div>
                <?php } else { ?>
                    <div class="col-md-12"><h2><?php esc_html_e('Edit Price Rule', 'woo-discount-rules'); ?>
                            | <?php echo(isset($data->rule_name) ? $data->rule_name : ''); ?></h2></div>
                <?php } ?>
                <div class="col-md-12" id="general_block"><h4 class="text text-muted"> <?php esc_html_e('General', 'woo-discount-rules'); ?></h4>
                    <hr>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3"><label> <?php esc_html_e('Order :', 'woo-discount-rules'); ?> <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="<?php esc_html_e('The Simple Ranking concept to said, which one is going to execute first and so on.', 'woo-discount-rules'); ?>"></i></label>
                            </div>
                            <div class="col-md-6"><input type="number" class="rule_order"
                                                         id="rule_order"
                                                         name="rule_order"
                                                         min=1
                                                         value="<?php echo(isset($data->rule_order) ? $data->rule_order : '-'); ?>"
                                                         placeholder="<?php esc_html_e('ex. 1', 'woo-discount-rules'); ?>">
                                <code><?php esc_html_e('WARNING: More than one rule should not have same priority.', 'woo-discount-rules'); ?> </code>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3"><label> <?php esc_html_e('Rule Name', 'woo-discount-rules'); ?> <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="<?php esc_attr_e('Rule Descriptions.', 'woo-discount-rules'); ?>"></i></label></div>
                            <div class="col-md-6"><input type="text" class="form-control rule_descr"
                                                         id="rule_name"
                                                         name="rule_name"
                                                         value="<?php echo(isset($data->rule_name) ? $data->rule_name : ''); ?>"
                                                         placeholder="<?php esc_attr_e('ex. Standard Rule.', 'woo-discount-rules'); ?>"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3"><label> <?php esc_html_e('Rule Description', 'woo-discount-rules'); ?> <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="<?php esc_attr_e('Rule Descriptions.', 'woo-discount-rules'); ?>"></i></label></div>
                            <div class="col-md-6"><input type="text" class="form-control rule_descr"
                                                         name="rule_descr"
                                                         value="<?php echo(isset($data->rule_descr) ? $data->rule_descr : ''); ?>"
                                                         id="rule_descr"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3"><label> <?php esc_html_e('Method', 'woo-discount-rules'); ?> <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="<?php esc_attr_e('Method to Apply.', 'woo-discount-rules'); ?>"></i></label></div>
                            <?php $opt = (isset($data->rule_method) ? $data->rule_method : ''); ?>
                            <div class="col-md-6"><select class="form-control"
                                                          name="rule_method" id="price_rule_method">
                                    <option
                                            value="qty_based" <?php if ($opt == 'qty_based') { ?> selected=selected <?php } ?>>
                                        <?php esc_html_e('Quantity based by product/category and BOGO deals', 'woo-discount-rules'); ?>
                                    </option>
                                    <option
                                        <?php if (!$pro) { ?> disabled <?php } else { ?> value="product_based" <?php
                                        }
                                        if ($opt == 'product_based') { ?> selected=selected <?php } ?>>
                                        <?php if (!$pro) { ?>
                                            <?php esc_html_e('Dependant product based discount', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                        <?php } else { ?>
                                            <?php esc_html_e('Dependant product based discount', 'woo-discount-rules'); ?>
                                        <?php } ?>
                                    </option>
                                </select></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3"><label> <?php esc_html_e('Validity', 'woo-discount-rules'); ?>
                                    <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Period of Rule Active. Format: month/day/Year Hour:Min', 'woo-discount-rules'); ?>"></span></label></div>
                            <div class="col-md-6">
                                <?php
                                $date_from = (isset($data->date_from) ? $data->date_from : '');
                                $date_to = (isset($data->date_to) ? $data->date_to : '');
                                if($date_from != '') $date_from = date( 'm/d/Y H:i', strtotime($date_from));
                                if($date_to != '') $date_to = date( 'm/d/Y H:i', strtotime($date_to));
                                ?>
                                <div class="form-inline">
                                    <input type="text"
                                           name="date_from"
                                           class="form-control datepicker"
                                           value="<?php echo $date_from; ?>"
                                           placeholder="<?php esc_attr_e('From', 'woo-discount-rules'); ?>">
                                    <input type="text" name="date_to"
                                           class="form-control datepicker"
                                           value="<?php echo $date_to; ?>"
                                           placeholder="<?php esc_attr_e('To - Leave Empty if No Expiry', 'woo-discount-rules'); ?>"></div>
                                <span class="wdr_current_date_and_time_string"><?php echo sprintf(esc_html__('Current date and time: %s', 'woo-discount-rules'), date('m/d/Y h:i', strtotime($current_date_and_time))); ?></span>
                            </div>
                        </div>
                        <div align="right">
                            <input type="button" class="btn btn-success restriction_tab" value="<?php esc_attr_e('Next', 'woo-discount-rules'); ?>">
                        </div>
                    </div>
                </div>

                <div class="col-md-12 wdr_hide" id="restriction_block"><h4 class="text text-muted"> <?php esc_html_e('Discount Conditions', 'woo-discount-rules'); ?></h4>
                    <hr>
                    <div class="qty_based_condition_cont price_discount_condition_con">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> <?php esc_html_e('Apply To', 'woo-discount-rules'); ?> </label></div>
                                <?php $opt = (isset($data->apply_to) ? $data->apply_to : ''); ?>
                                <div class="col-md-9"><select class="selectpicker"
                                                              name="apply_to" id="apply_to">
                                        <option
                                                value="all_products" <?php if ($opt == 'all_products') { ?> selected=selected <?php } ?>>
                                            <?php esc_html_e('All products', 'woo-discount-rules'); ?>
                                        </option>
                                        <option
                                                value="specific_products" <?php if ($opt == 'specific_products') { ?> selected=selected <?php } ?>>
                                            <?php esc_html_e('Specific products', 'woo-discount-rules'); ?>
                                        </option>
                                        <option
                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="specific_category" <?php }
                                            if ($opt == 'specific_category') { ?> selected=selected <?php } ?>>
                                            <?php if (!$pro) { ?>
                                                <?php esc_html_e('Specific categories', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                            <?php } else { ?>
                                                <?php esc_html_e('Specific categories', 'woo-discount-rules'); ?>
                                            <?php } ?>
                                        </option>
                                        <option
                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="specific_attribute" <?php }
                                            if ($opt == 'specific_attribute') { ?> selected=selected <?php } ?>>
                                            <?php if (!$pro) { ?>
                                                <?php esc_html_e('Specific attributes', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                            <?php } else { ?>
                                                <?php esc_html_e('Specific attributes', 'woo-discount-rules'); ?>
                                            <?php } ?>
                                        </option>
                                    </select>
                                    <div class="form-group" id="product_list">
                                        <?php $products_list = json_decode((isset($data->product_to_apply) ? $data->product_to_apply : '{}'), true);
                                        echo FlycartWoocommerceProduct::getProductAjaxSelectBox($products_list, 'product_to_apply');
                                        ?>
                                    </div>
                                    <?php $is_cumulative_for_products = (isset($data->is_cumulative_for_products))? $data->is_cumulative_for_products : 0 ?>
                                    <div class="form-group" id="cumulative_for_products_cont">
                                        <input type="checkbox" name="is_cumulative_for_products" id="is_cumulative_for_products" value="1" <?php if($is_cumulative_for_products) { echo "checked"; } ?>> <label class="checkbox_label" for="is_cumulative_for_products"><?php esc_html_e('Check this box to count quantities cumulatively across products', 'woo-discount-rules'); ?></label>
                                    </div>
                                    <div class="form-group" id="category_list">
                                        <?php $category_list = json_decode((isset($data->category_to_apply) ? $data->category_to_apply : '{}'), true); ?>
                                        <select class="category_list selectpicker" multiple title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                                name="category_to_apply[]">
                                            <?php foreach ($category as $index => $value) { ?>
                                                <option
                                                        value="<?php echo $index; ?>"<?php if (in_array($index, $category_list)) { ?> selected=selected <?php } ?>><?php echo $value; ?></option>
                                            <?php } ?>
                                        </select>
                                        <?php $is_cumulative = (isset($data->is_cumulative))? $data->is_cumulative : 0 ?>
                                        <input type="checkbox" name="is_cumulative" id="is_cumulative" value="1" <?php if($is_cumulative) { echo "checked"; } ?>> <label class="checkbox_label" for="is_cumulative"><?php esc_html_e('Check this box to count quantities cumulatively across category(ies)', 'woo-discount-rules'); ?></label>
                                        <div class="apply_child_categories">
                                            <?php $apply_child_categories = (isset($data->apply_child_categories))? $data->apply_child_categories : 0 ?>
                                            <input type="checkbox" name="apply_child_categories" id="apply_child_categories" value="1" <?php if($apply_child_categories) { echo "checked"; } ?>> <label class="checkbox_label" for="apply_child_categories"><?php esc_html_e('Check this box to apply child category(ies)', 'woo-discount-rules'); ?></label>
                                        </div>
                                    </div>
                                    <div class="form-group" id="product_attributes_list">
                                        <?php $attribute_list = json_decode((isset($data->attribute_to_apply) ? $data->attribute_to_apply : '{}'), true); ?>
                                        <select class="attribute_list selectpicker" multiple title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                                name="attribute_to_apply[]">
                                            <?php foreach ($attributes as $index => $value) { ?>
                                                <option
                                                        value="<?php echo $value['id']; ?>"<?php if (in_array($value['id'], $attribute_list)) { ?> selected=selected <?php } ?>><?php echo $value['text']; ?></option>
                                            <?php } ?>
                                        </select>
                                        <?php $is_cumulative_attribute = (isset($data->is_cumulative_attribute))? $data->is_cumulative_attribute : 0 ?>
                                        <div class="form-group">
                                            <input type="checkbox" name="is_cumulative_attribute" id="is_cumulative_attribute" value="1" <?php if($is_cumulative_attribute) { echo "checked"; } ?>> <label class="checkbox_label" for="is_cumulative_attribute"><?php esc_html_e('Check this box to count quantities cumulatively across attribute', 'woo-discount-rules'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="product_exclude_list">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-3"><label><?php esc_html_e('Exclude products', 'woo-discount-rules'); ?></label></div>
                                    <div class="col-md-9">
                                        <?php
                                        if(isset($data->product_to_exclude)){
                                            if(is_array($data->product_to_exclude))
                                                $product_exclude_list = $data->product_to_exclude;
                                            else
                                                $product_exclude_list = json_decode((isset($data->product_to_exclude) ? $data->product_to_exclude : '{}'), true);
                                        } else {
                                            $product_exclude_list = array();
                                        }

                                        echo FlycartWoocommerceProduct::getProductAjaxSelectBox($product_exclude_list, 'product_to_exclude');
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-3"><label for="exclude_sale_items"><?php esc_html_e('Exclude sale items', 'woo-discount-rules'); ?></label></div>
                                    <div class="col-md-9">
                                        <?php
                                        if($pro){
                                            $exclude_sale_items = (isset($data->exclude_sale_items))? $data->exclude_sale_items : 0; ?>
                                            <input type="checkbox" name="exclude_sale_items" id="exclude_sale_items" value="1" <?php if($exclude_sale_items) { echo "checked"; } ?>> <label class="checkbox_label" for="exclude_sale_items"><?php esc_html_e('Check this box if the rule should not apply to items on sale.', 'woo-discount-rules'); ?></label>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="woo-support-in_pro">
                                                <?php
                                                esc_html_e('Supported in PRO version', 'woo-discount-rules');
                                                ?>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> <?php esc_html_e('Customers', 'woo-discount-rules'); ?> </label></div>
                                <?php $opt = (isset($data->customer) ? $data->customer : ''); ?>
                                <div class="col-md-9"><select class="selectpicker"
                                                              name="customer" id="apply_customer">
                                        <option value="all" <?php if ($opt == 'all') { ?> selected=selected <?php } ?>>
                                            <?php esc_html_e('All', 'woo-discount-rules'); ?>
                                        </option>
                                        <option
                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="only_given" <?php
                                            }
                                            if ($opt == 'only_given') { ?> selected=selected <?php } ?>>
                                            <?php if (!$pro) { ?>
                                                <?php esc_html_e('Only Given', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                            <?php } else { ?>
                                                <?php esc_html_e('Only Given', 'woo-discount-rules'); ?>
                                            <?php } ?>
                                        </option>
                                    </select>
                                    <div class="form-group" id="user_list">
                                        <?php $users_list = json_decode((isset($data->users_to_apply) ? $data->users_to_apply : '{}'), true);
                                        echo FlycartWoocommerceProduct::getUserAjaxSelectBox($users_list, 'users_to_apply');
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> <?php esc_html_e('User roles', 'woo-discount-rules') ?> </label></div>
                                <div class="col-md-9">
                                    <?php
                                    if($pro){
                                        $roles_list = json_decode((isset($data->user_roles_to_apply) ? $data->user_roles_to_apply : '{}'), true); ?>
                                        <select class="roles_list selectpicker" id="product_roles_list" multiple name="user_roles_to_apply[]" title="<?php esc_html_e('Do not use', 'woo-discount-rules'); ?>">
                                            <?php foreach ($userRoles as $index => $user) { ?>
                                                <option value="<?php echo $index; ?>"<?php if (in_array($index, $roles_list)) { ?> selected=selected <?php } ?>><?php echo $user; ?></option>
                                            <?php } ?>
                                        </select>
                                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('role-based-discounts/user-role-based-discount-rules', 'role_based'); ?>
                                        <?php
                                    } else {
                                        ?>
                                        <div class="woo-support-in_pro">
                                            <?php
                                            esc_html_e('Supported in PRO version', 'woo-discount-rules');
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> <?php esc_html_e('Coupon', 'woo-discount-rules') ?> </label></div>
                                <div class="col-md-9">
                                    <?php
                                    if($pro){
                                        $coupons_to_apply_option = isset($data->coupons_to_apply_option) ? $data->coupons_to_apply_option : 'none';
                                        $coupons_to_apply = isset($data->coupons_to_apply) ? $data->coupons_to_apply : '';
                                        ?>
                                        <select class="selectpicker" id="coupon_option_price_rule" name="coupons_to_apply_option">
                                            <option value="none"<?php if ($coupons_to_apply_option == 'none') { ?> selected=selected <?php } ?>><?php esc_html_e('Do not use', 'woo-discount-rules'); ?></option>
                                            <option value="any_selected"<?php if ($coupons_to_apply_option == 'any_selected') { ?> selected=selected <?php } ?>><?php esc_html_e('Apply if any one coupon applied', 'woo-discount-rules'); ?></option>
                                            <option value="all_selected"<?php if ($coupons_to_apply_option == 'all_selected') { ?> selected=selected <?php } ?>><?php esc_html_e('Apply if all coupon applied', 'woo-discount-rules'); ?></option>
                                        </select>
                                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('coupon-based-discounts/activate-discount-rule-using-a-coupon-code-in-woocommerce', 'coupon'); ?>
                                        <div class="coupons_to_apply_price_rule_con">
                                            <span class="woo-discount-hint">
                                                <?php
                                                esc_html_e('Enter the coupon code separated by comma(,)', 'woo-discount-rules');
                                                ?>
                                                <a target="_blank" href="https://docs.flycart.org/woocommerce-discount-rules/coupon-based-discounts/activate-discount-rule-using-a-coupon-code-in-woocommerce">
                                                <?php
                                                esc_html_e('Make sure you have created the coupon already', 'woo-discount-rules');
                                                ?>
                                                </a>
                                            </span>
                                            <input class="form-control coupons_to_apply" id="coupons_to_apply" name="coupons_to_apply" value="<?php echo $coupons_to_apply; ?>"/>
                                            <?php
                                            if($do_not_run_while_have_third_party_coupon){
                                                ?>
                                            <div class="notice notice-warning">
                                                <p>
                                                    <?php esc_html_e('To get this condition work,', 'woo-discount-rules'); ?> <a target="_blank" href="?page=woo_discount_rules&tab=settings"><?php esc_html_e('please change the option', 'woo-discount-rules'); ?> <b><?php esc_html_e('Disable the rules while have coupon(Third party)', 'woo-discount-rules'); ?></b> <?php esc_html_e('in cart to', 'woo-discount-rules'); ?> <b><?php esc_html_e('No', 'woo-discount-rules'); ?></b></a>.
                                                </p>
                                            </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                    } else {
                                        ?>
                                        <div class="woo-support-in_pro">
                                            <?php
                                            esc_html_e('Supported in PRO version', 'woo-discount-rules');
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> <?php esc_html_e('Subtotal', 'woo-discount-rules') ?> </label></div>
                                <div class="col-md-9">
                                    <?php
                                    if($pro){
                                        $woocommerce3 = FlycartWoocommerceVersion::isWCVersion3x();
                                        if($woocommerce3){
                                            $subtotal_to_apply_option = isset($data->subtotal_to_apply_option) ? $data->subtotal_to_apply_option : 'none';
                                            $subtotal_to_apply = isset($data->subtotal_to_apply) ? $data->subtotal_to_apply : '';
                                            ?>
                                            <select class="selectpicker" id="subtotal_option_price_rule" name="subtotal_to_apply_option">
                                                <option value="none"<?php if ($subtotal_to_apply_option == 'none') { ?> selected=selected <?php } ?>><?php esc_html_e('Do not use', 'woo-discount-rules'); ?></option>
                                                <option value="atleast"<?php if ($subtotal_to_apply_option == 'atleast') { ?> selected=selected <?php } ?>><?php esc_html_e('Subtotal atleast', 'woo-discount-rules'); ?></option>
                                            </select>
                                            <div class="subtotal_to_apply_price_rule_con">
                                            <span class="woo-discount-hint">
                                                <?php
                                                esc_html_e('Enter the amount', 'woo-discount-rules');
                                                ?>
                                            </span>
                                                <input class="form-control subtotal_to_apply" id="subtotal_to_apply" name="subtotal_to_apply" value="<?php echo $subtotal_to_apply; ?>"/>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="woo-support-in_pro">
                                                <?php
                                                esc_html_e('Supported in WooCommerce 3.x', 'woo-discount-rules');
                                                ?>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        ?>
                                        <div class="woo-support-in_pro">
                                            <?php
                                            esc_html_e('Supported in PRO version', 'woo-discount-rules');
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label><?php esc_html_e('Purchase History', 'woo-discount-rules'); ?></label></div>
                                <?php $based_on_purchase_history = (isset($data->based_on_purchase_history) ? $data->based_on_purchase_history : 0); ?>
                                <div class="col-md-9">
                                    <?php
                                    if($pro){
                                        ?>
                                        <select class="selectpicker" id="based_on_purchase_history" name="based_on_purchase_history">
                                            <option value="0"<?php if ($based_on_purchase_history == '0') { ?> selected=selected <?php } ?>><?php esc_html_e('Do not use', 'woo-discount-rules'); ?></option>
                                            <option value="1"<?php if ($based_on_purchase_history == '1') { ?> selected=selected <?php } ?>><?php esc_html_e('Purchased amount', 'woo-discount-rules'); ?></option>
                                            <option value="2"<?php if ($based_on_purchase_history == '2') { ?> selected=selected <?php } ?>><?php esc_html_e('Number of orders', 'woo-discount-rules'); ?></option>
                                            <option value="3"<?php if ($based_on_purchase_history == '3') { ?> selected=selected <?php } ?>><?php esc_html_e('Purchased product', 'woo-discount-rules'); ?></option>
                                        </select>
                                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('purchase-history-based-discounts/purchase-history-based-discount', 'purchase_history'); ?>
                                        <?php
                                    } else {
                                        ?>
                                        <div class="woo-support-in_pro">
                                            <?php
                                            esc_html_e('Supported in PRO version', 'woo-discount-rules');
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    if($pro){
                                        ?>
                                        <div class="form-group" id="based_on_purchase_history_fields">
                                            <?php
                                            $purchased_history_amount = (isset($data->purchased_history_amount) ? $data->purchased_history_amount : 0);
                                            $purchased_history_type = (isset($data->purchased_history_type) ? $data->purchased_history_type : 'atleast');
                                            ?>
                                            <div class="form-group wdr_hide" id="purchase_history_products">
                                                <?php
                                                if(isset($data->purchase_history_products)){
                                                    if(is_array($data->purchase_history_products))
                                                        $product_purchase_history_list = $data->purchase_history_products;
                                                    else
                                                        $product_purchase_history_list = json_decode((isset($data->purchase_history_products) ? $data->purchase_history_products : '{}'), true);
                                                } else {
                                                    $product_purchase_history_list = array();
                                                }

                                                echo FlycartWoocommerceProduct::getProductAjaxSelectBox($product_purchase_history_list, 'purchase_history_products');
                                                ?>
                                            </div>
                                            <select class="selectpicker purchased_history_type" name="purchased_history_type">
                                                <option value="atleast"<?php echo ($purchased_history_type == 'atleast')? ' selected="selected"': ''; ?>><?php esc_html_e('Greater than or equal to', 'woo-discount-rules'); ?></option>
                                                <option value="less_than_or_equal"<?php echo ($purchased_history_type == 'less_than_or_equal')? ' selected="selected"': ''; ?>><?php esc_html_e('Less than or equal to', 'woo-discount-rules'); ?></option>
                                            </select>
                                            <input type="text" value="<?php echo $purchased_history_amount; ?>" name="purchased_history_amount"/>
                                            <label><?php esc_html_e('In Order status', 'woo-discount-rules'); ?></label>
                                            <?php
                                            $woocommerce_order_status = wc_get_order_statuses();
                                            $purchase_history_status_list = json_decode((isset($data->purchase_history_status_list) ? $data->purchase_history_status_list : '{}'), true);
                                            if(empty($purchase_history_status_list)){
                                                $purchase_history_status_list[] = 'wc-completed';
                                            }
                                            ?>
                                            <select class="purchase_history_status_list selectpicker" multiple title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>" name="purchase_history_status_list[]">
                                                <?php foreach ($woocommerce_order_status as $index => $value) { ?>
                                                    <option
                                                            value="<?php echo $index; ?>"<?php if (in_array($index, $purchase_history_status_list)) { ?> selected=selected <?php } ?>><?php echo $value; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="product_based_condition_cont price_discount_condition_con">
                        <?php
                        $product_based_conditions = json_decode((isset($data->product_based_condition) ? $data->product_based_condition : '{}'), true);
                        $product_based_condition_product_buy_type = isset($product_based_conditions['product_buy_type']) ? $product_based_conditions['product_buy_type'] : 'any';
                        $product_based_condition_product_quantity_rule = isset($product_based_conditions['product_quantity_rule']) ? $product_based_conditions['product_quantity_rule'] : 'more';
                        $product_based_condition_product_quantity_from = isset($product_based_conditions['product_quantity_from']) ? $product_based_conditions['product_quantity_from'] : '';
                        $product_based_condition_product_quantity_to = isset($product_based_conditions['product_quantity_to']) ? $product_based_conditions['product_quantity_to'] : '';
                        $product_based_condition_product_to_buy = isset($product_based_conditions['product_to_buy']) ? $product_based_conditions['product_to_buy'] : array();
                        $product_based_condition_product_to_apply = isset($product_based_conditions['product_to_apply']) ? $product_based_conditions['product_to_apply'] : array();
                        $product_based_condition_product_to_apply_count_option = isset($product_based_conditions['product_to_apply_count_option']) ? $product_based_conditions['product_to_apply_count_option'] : 'all';
                        $product_based_condition_product_to_apply_count = isset($product_based_conditions['product_to_apply_count']) ? $product_based_conditions['product_to_apply_count'] : '';
                        ?>
                        <div class="form-group" id="product_list">
                            <label ><?php esc_html_e('Buy', 'woo-discount-rules') ?></label>
                            <select class="selectpicker" name="product_based_condition[product_buy_type]">
                                <option value="any"<?php echo ($product_based_condition_product_buy_type == 'any')? ' selected="selected"': ''; ?>><?php esc_html_e('Any', 'woo-discount-rules') ?></option>
                                <option value="each"<?php echo ($product_based_condition_product_buy_type == 'each')? ' selected="selected"': ''; ?>><?php esc_html_e('Each', 'woo-discount-rules') ?></option>
                                <option value="combine"<?php echo ($product_based_condition_product_buy_type == 'combine')? ' selected="selected"': ''; ?>><?php esc_html_e('Combine', 'woo-discount-rules') ?></option>
                            </select>
                            <select class="selectpicker" id="product_based_condition_quantity_rule" name="product_based_condition[product_quantity_rule]">
                                <option value="more"<?php echo ($product_based_condition_product_quantity_rule == 'more')? ' selected="selected"': ''; ?>><?php esc_html_e('More than', 'woo-discount-rules') ?></option>
                                <option value="less"<?php echo ($product_based_condition_product_quantity_rule == 'less')? ' selected="selected"': ''; ?>><?php esc_html_e('Less than', 'woo-discount-rules') ?></option>
                                <option value="equal"<?php echo ($product_based_condition_product_quantity_rule == 'equal')? ' selected="selected"': ''; ?>><?php esc_html_e('Equal', 'woo-discount-rules') ?></option>
                                <option value="from"<?php echo ($product_based_condition_product_quantity_rule == 'from')? ' selected="selected"': ''; ?>><?php esc_html_e('From', 'woo-discount-rules') ?></option>
                            </select>
                            <input placeholder="<?php esc_html_e('Quantity', 'woo-discount-rules') ?>" type="text" name="product_based_condition[product_quantity_from]" value="<?php echo $product_based_condition_product_quantity_from; ?>"/ >
                            <div class="product_based_condition_to">
                                <label ><?php esc_html_e('to', 'woo-discount-rules')?></label>
                                <input placeholder="<?php esc_html_e('Quantity', 'woo-discount-rules') ?>" type="text" name="product_based_condition[product_quantity_to]" value="<?php echo $product_based_condition_product_quantity_to; ?>"/ >
                            </div>
                            <div class="product_based_condition_product_from">
                                <label ><?php esc_html_e('Product(s) from', 'woo-discount-rules')?></label>
                                <?php echo FlycartWoocommerceProduct::getProductAjaxSelectBox($product_based_condition_product_to_buy, 'product_based_condition[product_to_buy]'); ?>
                            </div>
                            <div class="product_based_condition_get_product_discount">
                                <?php $product_based_condition_get_discount_type = isset($product_based_conditions['get_discount_type']) ? $product_based_conditions['get_discount_type'] : 'product'; ?>
                                <select class="selectpicker" id="product_based_condition_get_discount_type" name="product_based_condition[get_discount_type]">
                                    <option value="product"<?php echo ($product_based_condition_get_discount_type == 'product')? ' selected="selected"': ''; ?>><?php esc_html_e('Apply discount in product(s)', 'woo-discount-rules') ?></option>
                                    <option value="category"<?php echo ($product_based_condition_get_discount_type == 'category')? ' selected="selected"': ''; ?>><?php esc_html_e('Apply discount in category(ies)', 'woo-discount-rules') ?></option>
                                </select>
                            </div>
                            <div class="product_based_condition_get_product_discount get_discount_type_product_tag">
                                <label ><?php esc_html_e('and get discount in ', 'woo-discount-rules') ?></label>
                                <select class="selectpicker" id="product_based_condition_product_to_apply_count_option" name="product_based_condition[product_to_apply_count_option]">
                                    <option value="all"<?php echo ($product_based_condition_product_to_apply_count_option == 'all')? ' selected="selected"': ''; ?>><?php esc_html_e('All', 'woo-discount-rules') ?></option>
                                    <option value="apply_first"<?php echo ($product_based_condition_product_to_apply_count_option == 'apply_first')? ' selected="selected"': ''; ?>><?php esc_html_e('First quantity(s)', 'woo-discount-rules') ?></option>
                                    <option value="skip_first"<?php echo ($product_based_condition_product_to_apply_count_option == 'skip_first')? ' selected="selected"': ''; ?>><?php esc_html_e('Skip first quantity(s)', 'woo-discount-rules') ?></option>
                                </select>
                                <input placeholder="<?php esc_html_e('Quantity', 'woo-discount-rules') ?>" type="text" name="product_based_condition[product_to_apply_count]" id="product_based_condition_product_to_apply_count" value="<?php echo $product_based_condition_product_to_apply_count; ?>"/ >
                            </div>
                            <div class="product_based_condition_get_product_discount get_discount_type_product_tag">
                                <label ><?php esc_html_e(' Product(s) ', 'woo-discount-rules') ?></label>
                                <?php echo FlycartWoocommerceProduct::getProductAjaxSelectBox($product_based_condition_product_to_apply, 'product_based_condition[product_to_apply]'); ?>
                            </div>
                            <div class="product_based_condition_get_product_discount get_discount_type_category_tag">
                                <label ><?php esc_html_e('Category(ies)', 'woo-discount-rules') ?></label>
                                <?php $product_based_condition_category_to_apply = isset($product_based_conditions['category_to_apply']) ? $product_based_conditions['category_to_apply'] : array(); ?>
                                <select class="category_list selectpicker" multiple title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                        name="product_based_condition[category_to_apply][]">
                                    <?php foreach ($category as $index => $value) { ?>
                                        <option
                                            value="<?php echo $index; ?>"<?php if (in_array($index, $product_based_condition_category_to_apply)) { ?> selected=selected <?php } ?>><?php echo $value; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('', 'product_dependent#dependant-product-based-rules', 'btn btn-info', esc_html__('Document for product dependent rules', 'woo-discount-rules')); ?>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div align="right">
                                <input type="button" class="btn btn-warning general_tab" value="<?php esc_attr_e('Previous', 'woo-discount-rules'); ?>">
                                <input type="button" class="btn btn-success discount_tab" value="<?php esc_attr_e('Next', 'woo-discount-rules'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- TODO: Implement ForEach Concept -->
                <div class="col-md-12 wdr_hide" id="discount_block">
                    <h4 class="text text-muted"> <?php esc_html_e('Discount', 'woo-discount-rules'); ?></h4>
                    <div class="qty_based_discount_cont price_discounts_con">
                        <a href=javascript:void(0) class="button button-primary" id="addNewDiscountRange"><i
                                    class="glyphicon glyphicon-plus"></i> <?php esc_html_e('Add New Range', 'woo-discount-rules'); ?></a>
                        <hr>
                        <div id="discount_rule_list">
                            <?php
                            $discount_range = new stdClass();
                            if (isset($data->discount_range)) {
                                if (is_string($data->discount_range)) {
                                    $discount_range = json_decode($data->discount_range);
                                } else {
                                    $discount_range = $data->discount_range;
                                }
                            }

                            // Make Dummy Element.
                            if ($discount_range == '') $discount_range = array(0 => '');
                            $fieldIndex = 1;
                            foreach ($discount_range as $index => $discount) {
                                ?>
                                <div class="discount_rule_list">
                                    <div class="form-group">
                                        <label><?php esc_html_e('Min Quantity', 'woo-discount-rules'); ?>
                                            <input type="text"
                                                   name="discount_range[<?php echo $fieldIndex; ?>][min_qty]"
                                                   class="form-control"
                                                   value="<?php echo(isset($discount->min_qty) ? $discount->min_qty : ''); ?>"
                                                   placeholder="<?php esc_html_e('ex. 1', 'woo-discount-rules'); ?>">
                                        </label>
                                        <label><?php esc_html_e('Max Quantity', 'woo-discount-rules'); ?>
                                            <input type="text"
                                                   name="discount_range[<?php echo $fieldIndex; ?>][max_qty]"
                                                   class="form-control"
                                                   value="<?php echo(isset($discount->max_qty) ? $discount->max_qty : ''); ?>"
                                                   placeholder="<?php esc_html_e('ex. 50', 'woo-discount-rules'); ?>"> </label>
                                        <label><?php esc_html_e('Adjustment Type', 'woo-discount-rules'); ?>
                                            <select class="form-control price_discount_type"
                                                    name="discount_range[<?php echo $fieldIndex; ?>][discount_type]">
                                                <?php $opt = (isset($discount->discount_type) ? $discount->discount_type : ''); ?>
                                                <option
                                                        value="percentage_discount" <?php if ($opt == 'percentage_discount') { ?> selected=selected <?php } ?> >
                                                    <?php esc_html_e('Percentage Discount', 'woo-discount-rules'); ?>
                                                </option>

                                                <option
                                                    <?php if (!$pro) { ?> disabled <?php } else { ?> value="price_discount" <?php
                                                    }
                                                    if ($opt == 'price_discount') { ?> selected=selected <?php } ?>>
                                                    <?php if (!$pro) { ?>
                                                        <?php esc_html_e('Price Discount', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                    <?php } else { ?>
                                                        <?php esc_html_e('Price Discount', 'woo-discount-rules'); ?>
                                                    <?php } ?>
                                                </option>
                                                <option
                                                    <?php if (!$pro) { ?> disabled <?php } else { ?> value="product_discount" <?php
                                                    }
                                                    if ($opt == 'product_discount') { ?> selected=selected <?php } ?>>
                                                    <?php if (!$pro) { ?>
                                                        <?php esc_html_e('Product Discount', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                    <?php } else { ?>
                                                        <?php esc_html_e('Product Discount', 'woo-discount-rules'); ?>
                                                    <?php } ?>
                                                </option>
                                            </select></label>
                                        <label><span class="hide-for-product-discount"><?php esc_html_e('Value', 'woo-discount-rules'); ?></span>
                                            <input type="text"
                                                   name="discount_range[<?php echo $fieldIndex; ?>][to_discount]"
                                                   class="form-control price_discount_amount"
                                                   value="<?php echo(isset($discount->to_discount) ? $discount->to_discount : ''); ?>"
                                                   placeholder="<?php esc_attr_e('ex. 50', 'woo-discount-rules'); ?>">
                                            <?php
                                            $products_list = (isset($discount->discount_product) ? $discount->discount_product : array());
                                            $discount_product_option = (isset($discount->discount_product_option) ? $discount->discount_product_option : 'all');
                                            ?>
                                            <div class="price_discount_product_list_con">
                                                <?php esc_html_e('Apply for', 'woo-discount-rules') ?>
                                                <select class="selectpicker discount_product_option" name="discount_range[<?php echo $fieldIndex; ?>][discount_product_option]">
                                                    <option value="all"<?php echo ($discount_product_option == 'all')? ' selected="selected"': '' ?>><?php esc_html_e('All selected', 'woo-discount-rules') ?></option>
                                                    <option value="same_product"<?php echo ($discount_product_option == 'same_product')? ' selected="selected"': '' ?>><?php esc_html_e('Same product', 'woo-discount-rules') ?></option>
                                                    <option value="any_cheapest"<?php echo ($discount_product_option == 'any_cheapest')? ' selected="selected"': '' ?>><?php esc_html_e('Any one cheapest from selected', 'woo-discount-rules') ?></option>
                                                    <option value="any_cheapest_from_all"<?php echo ($discount_product_option == 'any_cheapest_from_all')? ' selected="selected"': '' ?>><?php esc_html_e('Any one cheapest from all products', 'woo-discount-rules') ?></option>
                                                    <option value="more_than_one_cheapest_from_cat"<?php echo ($discount_product_option == 'more_than_one_cheapest_from_cat')? ' selected="selected"': '' ?>><?php esc_html_e('More than one cheapest from selected category', 'woo-discount-rules') ?></option>
                                                    <option value="more_than_one_cheapest"<?php echo ($discount_product_option == 'more_than_one_cheapest')? ' selected="selected"': '' ?>><?php esc_html_e('More than one cheapest from selected', 'woo-discount-rules') ?></option>
                                                    <option value="more_than_one_cheapest_from_all"<?php echo ($discount_product_option == 'more_than_one_cheapest_from_all')? ' selected="selected"': '' ?>><?php esc_html_e('More than one cheapest from all', 'woo-discount-rules') ?></option>
                                                </select>
                                                <div class="discount_product_option_bogo_con hide">
                                                    <label><?php esc_html_e('Free quantity', 'woo-discount-rules'); ?> <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Number of quantity(ies) in each selected product(s)', 'woo-discount-rules'); ?>"></span>
                                                        <input type="text"
                                                               name="discount_range[<?php echo $fieldIndex; ?>][discount_bogo_qty]"
                                                               class="form-control"
                                                               value="<?php echo(isset($discount->discount_bogo_qty) ? $discount->discount_bogo_qty : 1); ?>"
                                                               placeholder="<?php esc_attr_e('ex. 1', 'woo-discount-rules'); ?>" />
                                                    </label>
                                                </div>
                                                <div class="discount_product_option_more_cheapest_con hide">
                                                    <?php
                                                    $discount_product_item_type = (isset($discount->discount_product_item_type) ? $discount->discount_product_item_type : 'static');
                                                    ?>
                                                    <select class="selectpicker discount_product_item_count_type" name="discount_range[<?php echo $fieldIndex; ?>][discount_product_item_type]">
                                                        <option value="dynamic"<?php echo ($discount_product_item_type == 'dynamic')? ' selected="selected"': '' ?>><?php esc_html_e('Dynamic item count', 'woo-discount-rules') ?></option>
                                                        <option value="static"<?php echo ($discount_product_item_type == 'static')? ' selected="selected"': '' ?>><?php esc_html_e('Fixed item count', 'woo-discount-rules') ?></option>
                                                    </select>
                                                    <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Fixed item count - You need to provide item count manually. Dynamic item count - System will choose dynamically based on cart', 'woo-discount-rules'); ?>"></span>
                                                    <label class="discount_product_items_count_field"><?php esc_html_e('Item count', 'woo-discount-rules'); ?> <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Discount for number of item(s) in cart', 'woo-discount-rules'); ?>"></span>
                                                        <input type="text"
                                                               name="discount_range[<?php echo $fieldIndex; ?>][discount_product_items]"
                                                               class="form-control discount_product_items_count_field"
                                                               value="<?php echo(isset($discount->discount_product_items) ? $discount->discount_product_items : ''); ?>"
                                                               placeholder="<?php esc_attr_e('ex. 1', 'woo-discount-rules'); ?>" />
                                                    </label>
                                                    <label><?php esc_html_e('Item quantity', 'woo-discount-rules'); ?> <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Discount for number of quantity(ies) in each item', 'woo-discount-rules'); ?>"></span>
                                                        <input type="text"
                                                               name="discount_range[<?php echo $fieldIndex; ?>][discount_product_qty]"
                                                               class="form-control"
                                                               value="<?php echo(isset($discount->discount_product_qty) ? $discount->discount_product_qty : ''); ?>"
                                                               placeholder="<?php esc_attr_e('ex. 1', 'woo-discount-rules'); ?>" />
                                                    </label>
                                                </div>
                                                <div class="discount_product_option_list_con hide">
                                                    <?php
                                                    echo FlycartWoocommerceProduct::getProductAjaxSelectBox($products_list, "discount_range[".$fieldIndex."][discount_product]");
                                                    ?>
                                                </div>
                                                <div class="discount_category_option_list_con hide">
                                                    <?php
                                                    $discount_category_selected = (isset($discount->discount_category) ? $discount->discount_category : array());
                                                    ?>
                                                    <select class="category_list selectpicker" multiple title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                                            name="<?php echo "discount_range[".$fieldIndex."][discount_category][]"; ?>">
                                                        <?php foreach ($category as $index => $value) { ?>
                                                            <option value="<?php echo $index; ?>"<?php if (in_array($index, $discount_category_selected)) { ?> selected=selected <?php } ?>><?php echo $value; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="discount_product_percent_con hide">
                                                    <?php
                                                    $discount_product_discount_type = (isset($discount->discount_product_discount_type) ? $discount->discount_product_discount_type : '');
                                                    ?> <?php esc_html_e('and', 'woo-discount-rules'); ?>
                                                    <select class="selectpicker discount_product_discount_type" name="discount_range[<?php echo $fieldIndex; ?>][discount_product_discount_type]">
                                                        <option value=""<?php echo ($discount_product_discount_type == '')? ' selected="selected"': '' ?>><?php esc_html_e('100% percent', 'woo-discount-rules') ?></option>
                                                        <option value="limited_percent"<?php echo ($discount_product_discount_type == 'limited_percent')? ' selected="selected"': '' ?>><?php esc_html_e('Limited percent', 'woo-discount-rules') ?></option>
                                                    </select>
                                                    <span class="discount_product_percent_field">
                                                    <input type="text"
                                                           name="discount_range[<?php echo $fieldIndex; ?>][discount_product_percent]"
                                                           class="discount_product_percent_field"
                                                           value="<?php echo(isset($discount->discount_product_percent) ? $discount->discount_product_percent : ''); ?>"
                                                           placeholder="<?php esc_attr_e('ex. 10', 'woo-discount-rules'); ?>" /><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Percentage', 'woo-discount-rules'); ?>"></span>
                                                </span>
                                                    <?php esc_html_e('as discount', 'woo-discount-rules'); ?>
                                                </div>
                                            </div>
                                        </label>
                                        <label><a href=javascript:void(0)
                                                  class="btn btn-danger form-control remove_discount_range"><?php esc_html_e('Remove', 'woo-discount-rules'); ?></a></label>

                                    </div>
                                </div>
                                <?php $fieldIndex++; } ?>
                        </div>
                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('buy-one-get-one-deals/how-to-create-a-perfect-bogo-discount-rule-in-woocommerce', 'bogo_rules', 'btn btn-info', esc_html__('Document to create perfect BOGO rules', 'woo-discount-rules')); ?>
                    </div>
                    <div class="product_based_discount_cont price_discounts_con">
                        <div class="price_discount_product_list_con">
                            <?php
                            $product_based_discounts = json_decode((isset($data->product_based_discount) ? $data->product_based_discount : '{}'), true);
                            $product_based_discount_type = isset($product_based_discounts['discount_type']) ? $product_based_discounts['discount_type'] : 'percentage_discount';
                            $product_based_discount_value = isset($product_based_discounts['discount_value']) ? $product_based_discounts['discount_value'] : '';
                            ?>
                            <select class="selectpicker" name="product_based_discount[discount_type]">
                                <option value="percentage_discount"<?php echo ($product_based_discount_type == 'percentage_discount')? ' selected="selected"': ''; ?>><?php esc_html_e('Percent', 'woo-discount-rules') ?></option>
                                <option value="price_discount"<?php echo ($product_based_discount_type == 'price_discount')? ' selected="selected"': ''; ?>><?php esc_html_e('Fixed', 'woo-discount-rules') ?></option>
                            </select> <label><?php esc_html_e('Value', 'woo-discount-rules') ?></label>
                            <input type="text" name="product_based_discount[discount_value]" value="<?php echo $product_based_discount_value; ?>" />
                        </div>
                    </div>
                    <div align="right">
                        <input type="button" class="btn btn-warning restriction_tab" value="<?php esc_attr_e('Previous', 'woo-discount-rules'); ?>">
                    </div>
                </div>
            </div>
        </div>
        <?php if(!$isPro){ ?>
            <div class="col-md-1"></div>
            <!-- Sidebar -->
            <?php include_once(__DIR__ . '/template/sidebar.php'); ?>
            <!-- Sidebar END -->
        <?php } ?>
        <input type="hidden" name="rule_id" id="rule_id" value="<?php echo $rule_id; ?>">
        <input type="hidden" name="form" value="<?php echo $form; ?>">
        <input type="hidden" id="ajax_path" value="<?php echo admin_url('admin-ajax.php'); ?>">
        <input type="hidden" id="admin_path"
               value="<?php echo admin_url('admin.php?page=woo_discount_rules'); ?>">
        <input type="hidden" id="pro_suffix" value="<?php echo $suffix; ?>">
        <input type="hidden" id="is_pro" value="<?php echo $pro; ?>">
        <input type="hidden" id="flycart_wdr_woocommerce_version" value="<?php echo $flycart_wdr_woocommerce_version; ?>">
    </form>
    <div class="woo_discount_loader">
        <div class="lds-ripple"><div></div><div></div></div>
    </div>
</div>
<?php include_once(WOO_DISCOUNT_DIR . '/view/includes/footer.php'); ?>
