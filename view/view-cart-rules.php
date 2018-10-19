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
// Dummy Object.
$obj = new stdClass();

$data = (isset($config[0]) ? $config[0] : array());
$rule_id = (isset($data->ID)) ? $data->ID : 0;

$discounts = array();
$discount_rules = array();
if (isset($data->discount_rule)) {
    $discount_rules = (is_string($data->discount_rule) ? json_decode($data->discount_rule, true) : array('' => ''));
}

foreach ($discount_rules as $index => $rule) {
    foreach ($rule as $id => $value) {
        $discounts[$id] = $value;
    }
}
$discount_rules = $discounts;
if (empty($discount_rules)) {
    $discount_rules = array(0 => '');
    $type = 'subtotal_least';
}
$flycartWooDiscountRulesPurchase = new FlycartWooDiscountRulesPurchase();
$isPro = $flycartWooDiscountRulesPurchase->isPro();
$woo_settings = new FlycartWooDiscountBase();
$do_not_run_while_have_third_party_coupon = $woo_settings->getConfigData('do_not_run_while_have_third_party_coupon', 0);
$current_date_and_time = FlycartWooDiscountRulesGeneralHelper::getCurrentDateAndTimeBasedOnTimeZone();
?>
    <div class="container-fluid woo_discount_loader_outer">
        <form id="form_cart_rule">
            <div class="row-fluid">
                <div class="<?php echo $isPro? 'col-md-12': 'col-md-9'; ?>">
                    <div class="col-md-12 rule_buttons_con" align="right">
                        <input type="submit" id="saveCartRule" value="<?php esc_html_e('Save Rule', 'woo-discount-rules'); ?>" class="btn btn-primary">
                        <a href="?page=woo_discount_rules&tab=cart-rules" class="btn btn-warning"><?php esc_html_e('Cancel and go back to list', 'woo-discount-rules'); ?></a>
                        <?php echo FlycartWooDiscountRulesGeneralHelper::docsURLHTML('introduction/cart-discount-rules', 'cart_rules', 'btn btn-info'); ?>
                    </div>
                    <?php if ($rule_id == 0) { ?>
                        <div class="col-md-12"><h2><?php esc_html_e('New Cart Rule', 'woo-discount-rules'); ?></h2></div>
                    <?php } else { ?>
                        <div class="col-md-12"><h2><?php esc_html_e('Edit Cart Rule', 'woo-discount-rules'); ?>
                                | <?php echo(isset($data->rule_name) ? $data->rule_name : ''); ?></h2></div>
                    <?php } ?>
                    <div class="col-md-12" id="general_block"><h4 class="text text-muted"> <?php esc_html_e('General', 'woo-discount-rules'); ?></h4>
                        <hr>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label><?php esc_html_e('Order :', 'woo-discount-rules') ?> <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="<?php esc_attr_e('The Simple Ranking concept to said, which one is going to execute first and so on.', 'woo-discount-rules'); ?>"></i></label>
                                </div>
                                <div class="col-md-6"><input type="number" class="rule_order"
                                                             id="rule_order"
                                                             name="rule_order"
                                                             value="<?php echo(isset($data->rule_order) ? $data->rule_order : ''); ?>"
                                                             placeholder="<?php esc_attr_e('ex. 1', 'woo-discount-rules'); ?>">
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
                                <div class="col-md-3"><label> <?php esc_html_e('Validity', 'woo-discount-rules'); ?>
                                        <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Period of Rule Active. Format: month/day/Year Hour:Min', 'woo-discount-rules'); ?>"></label></div>
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
                                               placeholder="<?php esc_attr_e('To', 'woo-discount-rules'); ?>"></div>
                                    <span class="wdr_current_date_and_time_string"><?php echo sprintf(esc_html__('Current date and time: %s', 'woo-discount-rules'), date('m/d/Y h:i', strtotime($current_date_and_time))); ?></span>
                                </div>
                            </div>
                        </div>
                        <div align="right">
                            <input type="button" class="btn btn-success restriction_tab" value="<?php esc_attr_e('Next', 'woo-discount-rules'); ?>">
                        </div>
                    </div>

                    <div class="col-md-12 wdr_hide" id="restriction_block"><h4 class="text text-muted"> <?php esc_html_e('Cart Conditions', 'woo-discount-rules'); ?> </h4>
                        <a href=javascript:void(0) id="add_cart_rule" class="button button-primary"><i
                                class="glyphicon glyphicon-plus"></i>
                            <?php esc_html_e('Add Condition', 'woo-discount-rules'); ?></a>
                        <hr>
                        <div class="form-group">
                            <div id="cart_rules_list">
                                <?php
                                $i = 0;
                                foreach ($discount_rules as $rule_type => $rule) {

                                    if (!empty($discount_rules)) {
                                        if (!isset($discount_rules[0])) {
                                            $type = $rule_type;
                                        }
                                    }
                                    // Dummy Entry for One Rule at starting.
                                    // Note : Must having at least one rule on starting.
                                    $rule = (!is_null($rule) ? $rule : array(0 => '1'));
                                    ?>
                                    <div class="cart_rules_list row">
                                        <div class="col-md-3 form-group">
                                            <label>
                                                <?php esc_html_e('Type', 'woo-discount-rules'); ?>
                                                <select class="form-control cart_rule_type"
                                                        id="cart_condition_type_<?php echo $i; ?>"
                                                        name="discount_rule[<?php echo $i; ?>][type]">
                                                    <optgroup label="<?php esc_attr_e('Cart Subtotal', 'woo-discount-rules'); ?>">
                                                        <option
                                                            value="subtotal_least"<?php if ($type == 'subtotal_least') { ?> selected=selected <?php } ?>>
                                                            <?php esc_html_e('Subtotal at least', 'woo-discount-rules'); ?>
                                                        </option>
                                                        <option
                                                            value="subtotal_less"<?php if ($type == 'subtotal_less') { ?> selected=selected <?php } ?>>
                                                            <?php esc_html_e('Subtotal less than', 'woo-discount-rules'); ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Cart Item Count', 'woo-discount-rules'); ?>">
                                                        <option
                                                            value="item_count_least"<?php if ($type == 'item_count_least') { ?> selected=selected <?php } ?>>
                                                            <?php esc_html_e('Number of line items in the cart (not quantity) at least', 'woo-discount-rules'); ?>
                                                        </option>
                                                        <option
                                                            value="item_count_less"<?php if ($type == 'item_count_less') { ?> selected=selected <?php } ?>>
                                                            <?php esc_html_e('Number of line items in the cart (not quantity) less than', 'woo-discount-rules'); ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Quantity Sum', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="quantity_least" <?php
                                                            }
                                                            if ($type == 'quantity_least') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Total number of quantities in the cart at least', 'woo-discount-rules'); ?>
                                                                <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Total number of quantities in the cart at least', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>

                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="quantity_less" <?php
                                                            }
                                                            if ($type == 'quantity_less') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Total number of quantities in the cart less than', 'woo-discount-rules'); ?>
                                                                <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Total number of quantities in the cart less than', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Categories In Cart', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="categories_in" <?php
                                                            } ?>
                                                            <?php if ($type == 'categories_in') { ?> selected="selected"
                                                            <?php } ?>><?php esc_html_e('Categories in cart', 'woo-discount-rules'); ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="atleast_one_including_sub_categories" <?php
                                                            } ?>
                                                            <?php if ($type == 'atleast_one_including_sub_categories') { ?> selected="selected"
                                                            <?php } ?>><?php esc_html_e('Including sub-categories in cart', 'woo-discount-rules'); ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="in_each_category" <?php
                                                            } ?>
                                                            <?php if ($type == 'in_each_category') { ?> selected="selected"
                                                            <?php } ?>><?php esc_html_e('In each category', 'woo-discount-rules'); ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Customer Details (must be logged in)', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="users_in" <?php
                                                            }
                                                            if ($type == 'users_in') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('User in list', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('User in list', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="roles_in" <?php
                                                            }
                                                            if ($type == 'roles_in') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('User role in list', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('User role in list', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="shipping_countries_in" <?php
                                                            }
                                                            if ($type == 'shipping_countries_in') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Shipping country in list', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Shipping country in list', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Customer Email', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_email_tld" <?php
                                                            }
                                                            if ($type == 'customer_email_tld') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Email with TLD (Eg: edu)', 'woo-discount-rules'); ?><b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Email with TLD (Eg: edu)', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_email_domain" <?php
                                                            }
                                                            if ($type == 'customer_email_domain') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Email with Domain (Eg: gmail.com)', 'woo-discount-rules'); ?><b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Email with Domain (Eg: gmail.com)', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Customer Billing Details', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_billing_city" <?php
                                                            }
                                                            if ($type == 'customer_billing_city') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Billing city', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Billing city', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Customer Shipping Details', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_shipping_state" <?php
                                                            }
                                                            if ($type == 'customer_shipping_state') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Shipping state', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Shipping state', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_shipping_city" <?php
                                                            }
                                                            if ($type == 'customer_shipping_city') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Shipping city', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Shipping city', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_shipping_zip_code" <?php
                                                            }
                                                            if ($type == 'customer_shipping_zip_code') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Shipping zip code', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Shipping zip code', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Purchase History', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_based_on_purchase_history" <?php
                                                            }
                                                            if ($type == 'customer_based_on_purchase_history') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Purchased amount', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Purchased amount', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_based_on_purchase_history_order_count" <?php
                                                            }
                                                            if ($type == 'customer_based_on_purchase_history_order_count') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Number of order purchased', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Number of order purchased', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_based_on_purchase_history_product_order_count" <?php
                                                            }
                                                            if ($type == 'customer_based_on_purchase_history_product_order_count') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Number of order purchased in products', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Number of order purchased in products', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="<?php esc_attr_e('Coupon applied', 'woo-discount-rules'); ?>">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="coupon_applied_any_one" <?php
                                                            }
                                                            if ($type == 'coupon_applied_any_one') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('Atleast any one', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('Atleast any one', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="coupon_applied_all_selected" <?php
                                                            }
                                                            if ($type == 'coupon_applied_all_selected') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                <?php esc_html_e('All selected', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                <?php esc_html_e('All selected', 'woo-discount-rules'); ?>
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                </select>
                                            </label>
                                        </div>
                                        <div class="col-md-3 form-group">
                                            <label> <?php esc_html_e('Value', 'woo-discount-rules'); ?>
                                                <?php
                                                $users_list = array();
                                                $class = 'style="display:none"';
                                                $hit = false;
                                                if ($type == 'users_in') {
                                                    $users_list = $discount_rules[$type];
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                }
                                                ?>
                                                <div id="user_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <?php
                                                    echo FlycartWoocommerceProduct::getUserAjaxSelectBox($users_list, "discount_rule[".$i."][users_to_apply]");
                                                    ?>
                                                </div>
                                                <?php
                                                $category_list = array();
                                                $class = 'style="display:none"';
                                                if (in_array($type, array('categories_atleast_one', 'categories_not_in', 'categories_in', 'in_each_category', 'atleast_one_including_sub_categories'))) {
                                                    $category_list = $discount_rules[$type];
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                }
                                                ?>
                                                <div id="category_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="category_list selectpicker"
                                                            id="cart_category_list_<?php echo $i; ?>"
                                                            multiple
                                                            title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                                            name="discount_rule[<?php echo $i; ?>][category_to_apply][]">
                                                        <?php foreach ($category as $index => $cat) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>"<?php if (in_array($index, $category_list)) { ?> selected=selected <?php } ?>><?php echo $cat; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                $roles_list = array();
                                                $class = 'style="display:none"';
                                                if ($type == 'roles_in') {
                                                    $roles_list = $discount_rules[$type];
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                } ?>
                                                <div id="roles_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="roles_list selectpicker"
                                                            id="cart_roles_list_<?php echo $i; ?>" multiple
                                                            title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                                            name="discount_rule[<?php echo $i; ?>][user_roles_to_apply][]">
                                                        <?php foreach ($userRoles as $index => $user) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>"<?php if (in_array($index, $roles_list)) { ?> selected=selected <?php } ?>><?php echo $user; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                $countries_list = array();
                                                $class = 'style="display:none"';
                                                if ($type == 'shipping_countries_in') {
                                                    $countries_list = $discount_rules[$type];
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                } ?>
                                                <div id="countries_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="country_list selectpicker"
                                                            data-live-search="true"
                                                            id="cart_countries_list_<?php echo $i; ?>"
                                                            multiple
                                                            title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                                            name="discount_rule[<?php echo $i; ?>][countries_to_apply][]">
                                                        <?php foreach ($countries as $index => $country) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>"<?php if (in_array($index, $countries_list)) { ?> selected=selected <?php } ?>><?php echo $country; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                $order_status_list = array();
                                                $class = 'style="display:none"';
                                                $woocommerce_order_status = wc_get_order_statuses();
                                                $purchased_history_amount = '';
                                                $purchased_history_type = 'atleast';
                                                $purchase_history_status_list = $product_purchase_history_list = array();
                                                if ($type == 'customer_based_on_purchase_history' || $type == 'customer_based_on_purchase_history_order_count' || $type == 'customer_based_on_purchase_history_product_order_count') {
                                                    $purchase_history_status_list = isset($discount_rules[$type]['purchase_history_order_status'])? $discount_rules[$type]['purchase_history_order_status'] : array();
                                                    $purchased_history_amount = isset($discount_rules[$type]['purchased_history_amount'])? $discount_rules[$type]['purchased_history_amount'] : 0;
                                                    $purchased_history_type = isset($discount_rules[$type]['purchased_history_type'])? $discount_rules[$type]['purchased_history_type'] : 'atleast';
                                                    if(empty($purchase_history_status_list)){
                                                        $purchase_history_status_list[] = 'wc-completed';
                                                    }
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                    $purchase_history_products = isset($discount_rules[$type]['purchase_history_products'])? $discount_rules[$type]['purchase_history_products'] : array();
                                                    if(isset($purchase_history_products)){
                                                        if(is_array($purchase_history_products))
                                                            $product_purchase_history_list = $purchase_history_products;
                                                        else
                                                            $product_purchase_history_list = json_decode((isset($purchase_history_products) ? $purchase_history_products : '{}'), true);
                                                    } else {
                                                        $product_purchase_history_list = array();
                                                    }
                                                } ?>
                                                <div id="purchase_history_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <div class="form-group<?php echo ($type == 'customer_based_on_purchase_history_product_order_count')? '': ' wdr_hide';?>" id="purchase_history_products_list_<?php echo $i; ?>">
                                                        <?php
                                                        echo FlycartWoocommerceProduct::getProductAjaxSelectBox($product_purchase_history_list, 'discount_rule['.$i.'][purchase_history_products]');
                                                        ?>
                                                    </div>
                                                    <select class="selectpicker purchased_history_type" name="discount_rule[<?php echo $i; ?>][purchased_history_type]">
                                                        <option value="atleast"<?php echo ($purchased_history_type == 'atleast')? ' selected="selected"': ''; ?>><?php esc_html_e('Greater than or equal to', 'woo-discount-rules'); ?></option>
                                                        <option value="less_than_or_equal"<?php echo ($purchased_history_type == 'less_than_or_equal')? ' selected="selected"': ''; ?>><?php esc_html_e('Less than or equal to', 'woo-discount-rules'); ?></option>
                                                    </select>
                                                    <input name="discount_rule[<?php echo $i; ?>][purchased_history_amount]" value="<?php echo $purchased_history_amount; ?>" type="text"/> <?php esc_html_e('In Order status', 'woo-discount-rules'); ?>
                                                    <select class="order_status_list selectpicker"
                                                            data-live-search="true"
                                                            id="order_status_list_<?php echo $i; ?>"
                                                            multiple
                                                            title="<?php esc_html_e('None selected', 'woo-discount-rules'); ?>"
                                                            name="discount_rule[<?php echo $i; ?>][purchase_history_order_status][]">
                                                        <?php foreach ($woocommerce_order_status as $index => $woocommerce_order_sts) { ?>
                                                            <option
                                                                    value="<?php echo $index; ?>"<?php if (in_array($index, $purchase_history_status_list)) { ?> selected=selected <?php } ?>><?php echo $woocommerce_order_sts; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                if ($hit) {
                                                    $class = 'style="display:none"';
                                                } else {
                                                    $class = 'style="display:block"';
                                                }
                                                ?>
                                                <div id="general_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <input type="text"
                                                           value="<?php echo(isset($discount_rules[$type]) && !is_array($discount_rules[$type]) ? $discount_rules[$type] : ''); ?>"
                                                           name="discount_rule[<?php echo $i; ?>][option_value]">
                                                </div>
                                                <?php
                                                if (in_array($type, array('coupon_applied_any_one', 'coupon_applied_all_selected')))
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
                                            </label>
                                        </div>
                                        <div class="col-md-1"><label> <?php esc_html_e('Action', 'woo-discount-rules'); ?> </label><br>
                                            <a href=javascript:void(0) class="btn btn-danger remove_cart_rule"><?php esc_html_e('Remove', 'woo-discount-rules'); ?></a>
                                        </div>
                                    </div>
                                    <?php
                                    $i++;
                                }
                                ?>
                            </div>
                        </div>
                        <div align="right">
                            <input type="button" class="btn btn-warning general_tab" value="<?php esc_attr_e('Previous', 'woo-discount-rules'); ?>">
                            <input type="button" class="btn btn-success discount_tab" value="<?php esc_attr_e('Next', 'woo-discount-rules'); ?>">
                        </div>
                    </div>

                    <!-- TODO: Implement ForEach Concept -->
                    <div class="col-md-12 wdr_hide" id="discount_block"><h4 class="text text-muted"> <?php esc_html_e('Discount', 'woo-discount-rules'); ?></h4>
                        <?php
                        $discount_type = 'percentage_discount';
                        $to_discount = 0;
                        if (isset($data)) {
                            if (isset($data->discount_type)) {
                                $discount_type = $data->discount_type;
                            }
                            if (isset($data->to_discount)) {
                                $to_discount = $data->to_discount;
                            }
                        }
                        ?>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label> <?php esc_html_e('Discount Type :', 'woo-discount-rules'); ?>
                                        <select class="form-control" id="cart_rule_discount_type" name="discount_type">
                                            <option
                                                value="percentage_discount" <?php if ($discount_type == 'percentage_discount') { ?> selected=selected <?php } ?>>
                                                <?php esc_html_e('Percentage Discount', 'woo-discount-rules'); ?>
                                            </option>
                                            <option
                                                <?php if (!$pro) { ?> disabled <?php } else { ?> value="price_discount" <?php }
                                                if ($discount_type == 'price_discount') { ?> selected=selected <?php } ?>>
                                                <?php if (!$pro) { ?>
                                                    <?php esc_html_e('Price Discount', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                <?php } else { ?>
                                                    <?php esc_html_e('Price Discount', 'woo-discount-rules'); ?>
                                                <?php } ?>
                                            </option>
                                            <option
                                                <?php if (!$pro) { ?> disabled <?php } else { ?> value="shipping_price" <?php }
                                                if ($discount_type == 'shipping_price') { ?> selected=selected <?php } ?>>
                                                <?php if (!$pro) { ?>
                                                    <?php esc_html_e('Free shipping', 'woo-discount-rules'); ?> <b><?php echo $suffix; ?></b>
                                                <?php } else { ?>
                                                    <?php esc_html_e('Free shipping', 'woo-discount-rules'); ?>
                                                <?php } ?>
                                            </option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2" id="cart_rule_discount_value_con" >
                                <div class="form-group">
                                    <label> <?php esc_html_e('value :', 'woo-discount-rules'); ?>
                                        <input type="text" name="to_discount" class="form-control"
                                               value="<?php echo $to_discount; ?>">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div align="right">
                            <input type="button" class="btn btn-warning restriction_tab" value="<?php esc_attr_e('Previous', 'woo-discount-rules'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <?php if(!$isPro){ ?>
                <!-- Sidebar -->
                <?php include_once(__DIR__ . '/template/sidebar.php'); ?>
                <!-- Sidebar END -->
            <?php } ?>
            <input type="hidden" name="rule_id" id="rule_id" value="<?php echo $rule_id; ?>">
            <input type="hidden" name="form" value="<?php echo $form; ?>">
            <input type="hidden" id="ajax_path" value="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" id="admin_path" value="<?php echo admin_url('admin.php?page=woo_discount_rules'); ?>">
            <input type="hidden" id="pro_suffix" value="<?php echo $suffix; ?>">
            <input type="hidden" id="is_pro" value="<?php echo $pro; ?>">
            <input type="hidden" id="flycart_wdr_woocommerce_version" value="<?php echo $flycart_wdr_woocommerce_version; ?>">
        </form>
        <div class="woo_discount_loader">
            <div class="lds-ripple"><div></div><div></div></div>
        </div>
    </div>

<?php include_once(WOO_DISCOUNT_DIR . '/view/includes/footer.php'); ?>