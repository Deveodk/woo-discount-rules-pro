<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
include_once(WOO_DISCOUNT_DIR . '/helper/general-helper.php');
include_once(WOO_DISCOUNT_DIR . '/includes/pricing-productbased.php');

/**
 * Class FlycartWooDiscountRulesPricingRules
 */
if (!class_exists('FlycartWooDiscountRulesPricingRules')) {
    class FlycartWooDiscountRulesPricingRules
    {
        /**
         * @var string
         */
        private $option_name = 'woo_discount_price_option';

        /**
         * @var string
         */
        public $post_type = 'woo_discount';

        /**
         * @var bool
         */
        public $discount_applied = false;

        /**
         * @var
         */
        private $rules;

        /**
         * @var
         */
        public $rule_sets;

        /**
         * @var
         */
        public $matched_sets;
        public $matched_sets_for_product;

        public static $matched_discounts = array();
        public static $applied_discount_rules = array();

        /**
         * @var
         */
        public $baseConfig;

        /**
         * @var
         */
        public $apply_to;

        /**
         * @var
         */
        public $products_has_discount = array();

        /**
         * @var string
         */
        public $default_option = 'woo-discount-config';

        public $last_update_time_field = 'wdr_price_rule_last_update';

        public $postData;

        public $bogo_matches;

        public static $rules_loaded = 0;
        public static $rules_applied_already = 0;
        public static $pricingRules;
        public static $product_categories = array();
        public static $product_attributes = array();
        public static $product_on_sale = array();
        public static $product_has_strike_out = array();
        public static $product_strike_out_price = array();

        /**
         * FlycartWooDiscountRulesPricingRules constructor.
         */
        public function __construct()
        {
            $this->updateBaseConfig();
            $this->postData = \FlycartInput\FInput::getInstance();
        }

        /**
         * Update the Base config with live.
         */
        public function updateBaseConfig()
        {
            $base = new FlycartWooDiscountBase();
            $base = $base->getBaseConfig();
            if (is_string($base)) $base = json_decode($base, true);
            $this->baseConfig = $base;
            $this->apply_to = (isset($this->baseConfig['price_setup']) ? $this->baseConfig['price_setup'] : 'all');
        }

        /**
         * Saving the Price Rule Set.
         *
         * @param $request
         * @return bool
         */
        public function save($request)
        {
            $id = (isset($request['rule_id']) ? $request['rule_id'] : false);

            $id = intval($id);
            if (!$id && $id != 0) return false;
            $title = $request['rule_name'] = (isset($request['rule_name']) ? str_replace('\'', '', $request['rule_name']) : 'New');
            $slug = str_replace(' ', '-', strtolower($title));

            // To Lowercase.
            $slug = strtolower($slug);

            // Encoding String with Space.
            $slug = str_replace(' ', '-', $slug);

            $request['rule_descr'] = (isset($request['rule_descr']) ? str_replace('\'', '', $request['rule_descr']) : '');

            if ($id) {
                $post = array(
                    'ID' => $id,
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_content' => 'New Rule',
                    'post_type' => $this->post_type,
                    'post_status' => 'publish'
                );
                wp_update_post($post);
            } else {
                $post = array(
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_content' => 'New Rule',
                    'post_type' => $this->post_type,
                    'post_status' => 'publish'
                );
                $id = wp_insert_post($post);
            }

            $form = array(
                'rule_name',
                'rule_descr',
                'rule_method',
                'qty_based_on',
                'date_from',
                'date_to',
                'apply_to',
                'customer',
                'min_qty',
                'max_qty',
                'discount_type',
                'to_discount',
                'status',
                'customer',
                'discount_range',
                'product_based_condition',
                'product_based_discount',
                'rule_order',
                'product_to_exclude',
                'coupons_to_apply_option',
                'coupons_to_apply',
                'subtotal_to_apply_option',
                'subtotal_to_apply',
                'exclude_sale_items'
            );

            //----------------------------------------------------------------------------------------------------------
            // Manage Products with it's ID or Category.
            $apply_to = 'all_products';

            if (isset($request['apply_to'])) $apply_to = $request['apply_to'];

            $request['rule_order'] = FlycartWooDiscountRulesGeneralHelper::reOrderRuleIfExists($id, $request['rule_order'], $this->post_type);

            if ($apply_to == 'specific_category') {
                $apply_to = 'category_to_apply';
                if(isset($request['is_cumulative']) && $request['is_cumulative'] == 1){
                    $request['is_cumulative'] = 1;
                } else {
                    $request['is_cumulative'] = 0;
                }
                $form[] = 'is_cumulative';

                if(isset($request['apply_child_categories']) && $request['apply_child_categories'] == 1){
                    $request['apply_child_categories'] = 1;
                } else {
                    $request['apply_child_categories'] = 0;
                }
                $form[] = 'apply_child_categories';

            } elseif ($apply_to == 'specific_products') {
                $apply_to = 'product_to_apply';
            } elseif ($apply_to == 'specific_attribute') {
                $apply_to = 'attribute_to_apply';
                if(isset($request['is_cumulative_attribute']) && $request['is_cumulative_attribute'] == 1){
                    $request['is_cumulative_attribute'] = 1;
                } else {
                    $request['is_cumulative_attribute'] = 0;
                }
                $form[] = 'is_cumulative_attribute';
            }
            $form[] = $apply_to;

            if(isset($request['is_cumulative_for_products']) && $request['is_cumulative_for_products'] == 1){
                $request['is_cumulative_for_products'] = 1;
            } else {
                $request['is_cumulative_for_products'] = 0;
            }
            $form[] = 'is_cumulative_for_products';

            if(isset($request['exclude_sale_items']) && $request['exclude_sale_items'] == 1){
                $request['exclude_sale_items'] = 1;
            } else {
                $request['exclude_sale_items'] = 0;
            }


            if (isset($request[$apply_to])) $request[$apply_to] = json_encode($request[$apply_to]);
            //----------------------------------------------------------------------------------------------------------

            // Manage Users.
            $apply_to = 'all';

            if (isset($request['customer'])) $apply_to = $request['customer'];

            if ($apply_to == 'only_given') {
                $apply_to = 'users_to_apply';
            }
            $form[] = $apply_to;
            if (isset($request[$apply_to])) $request[$apply_to] = json_encode($request[$apply_to]);

            $form[] = 'user_roles_to_apply';
            if (!isset($request['user_roles_to_apply'])) $request['user_roles_to_apply'] = array();
            $request['user_roles_to_apply'] = json_encode($request['user_roles_to_apply']);

            $based_on_purchase_history = 0;
            if (isset($request['based_on_purchase_history'])) $based_on_purchase_history = $request['based_on_purchase_history'];
            $request['based_on_purchase_history'] = $based_on_purchase_history;
            $form[] = 'based_on_purchase_history';
            if($based_on_purchase_history){
                $form[] = 'purchased_history_amount';
                $form[] = 'purchased_history_type';
                $form[] = 'purchase_history_status_list';
                $form[] = 'purchase_history_products';
                if (isset($request['purchase_history_status_list'])) $request['purchase_history_status_list'] = json_encode($request['purchase_history_status_list']);
                else $request['purchase_history_status_list'] = json_encode(array('wc-completed'));
            }

            //----------------------------------------------------------------------------------------------------------

            // Manage list of Discount Ranges.
            if (isset($request['discount_range'])) {

                foreach ($request['discount_range'] as $index => $value) {
                    $request['discount_range'][$index] = FlycartWooDiscountRulesGeneralHelper::makeString($value);
                    $request['discount_range'][$index]['title'] = isset($request['rule_name']) ? $request['rule_name'] : '';

                }

                $request['discount_range'] = json_encode($request['discount_range']);
            } else {
                // Reset the Discount Range, if its empty.
                $request['discount_range'] = '';
            }
            if(isset($request['rule_method']) && $request['rule_method'] == 'product_based'){
                $request['product_based_condition'] = json_encode($request['product_based_condition']);
                $request['product_based_discount'] = json_encode($request['product_based_discount']);
            } else {
                $request['product_based_condition'] = '{}';
                $request['product_based_discount'] = '{}';
            }

            if(!isset($request['product_to_exclude'])) $request['product_to_exclude'] = array();

            $request['status'] = 'publish';

            if (is_null($id) || !isset($id)) return false;
            foreach ($request as $index => $value) {
                if (in_array($index, $form)) {
                    if (get_post_meta($id, $index)) {
                        update_post_meta($id, $index, $value);
                    } else {
                        add_post_meta($id, $index, $value);
                    }
                }
            }

            //For update the last update time of rule
            $this->updateLastUpdateTimeOfRule();
        }


        /**
         * For update the last update time of rule
         *
         * @return bool
         */
        public function updateLastUpdateTimeOfRule(){
            $now = new DateTime("now", new DateTimeZone('UTC'));
            $time = $now->getTimestamp();
            if (get_option($this->last_update_time_field)) {
                return update_option($this->last_update_time_field, $time);
            } else {
                return add_option($this->last_update_time_field, $time);
            }
        }

        /**
         * load price from cookie
         *
         * @return int
         */
        public function loadPriceTableFromCookie($cookie_set_time){
            $time = get_option($this->last_update_time_field);
            if (!empty($time) && !empty($cookie_set_time)) {
                if($cookie_set_time >= $time){
                    return 1;
                }
            }

            return 0;
        }

        /**
         * Load View with Specif post id.
         *
         * @param $option
         * @param integer $id Post ID.
         * @return string mixed response.
         */
        public function view($option, $id)
        {
            $id = intval($id);
            if (!$id) return false;
            $post = get_post($id, 'OBJECT');
            if (isset($post)) {
                if (isset($post->ID)) {
                    $post->meta = get_post_meta($post->ID);
                }
            }
            return $post;
        }

        // -------------------------------------------------RULE IMPLEMENTATION---------------------------------------------

        /**
         * To Analyzing the Pricing Rules to Apply the Discount in terms of price.
         */
        public function analyse($woocommerce, $product_page = 0, $cart_page_strikeout = 0)
        {
            $this->organizeRules();
            $this->applyRules($product_page);
            if(!$product_page) $this->initAdjustment($cart_page_strikeout);
        }

        /**
         * To Organizing the rules to make possible sets.
         */
        public function organizeRules()
        {
            // Loads the Rules to Global.
            $this->getRules();
            // Validate and Re-Assign the Rules.
            $this->filterRules();
        }

        /**
         * To Get Set of Rules.
         *
         * @return mixed
         */
        public function getRules($onlyCount = false)
        {
            if(self::$rules_loaded) return $this->rules = self::$pricingRules;

            $post_args = array('post_type' => $this->post_type, 'numberposts' => '-1');
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            if(is_admin() && isset($request['page']) && $request['page'] == 'woo_discount_rules'){
                $post_args['meta_key'] = 'rule_order';
                $post_args['orderby'] = 'meta_value_num';
                $post_args['order'] = 'DESC';
                if(isset($request['order']) && in_array($request['order'], array('asc', 'desc'))){
                    if($request['order'] == 'asc') $post_args['order'] = 'ASC';
                }
            }
            $posts = get_posts($post_args);

            if ($onlyCount) return count($posts);
            if (isset($posts) && count($posts) > 0) {
                foreach ($posts as $index => $item) {
                    $posts[$index]->meta = get_post_meta($posts[$index]->ID);
                }
                $this->rules = $posts;
            }
            self::$rules_loaded = 1;
            self::$pricingRules = $posts;
            return $posts;
        }

        /**
         * To Updating the Log of Implemented Price Discounts.
         *
         * @return bool
         */
        public function makeLog()
        {
            if (is_null($this->matched_sets)) return false;

            $discount_log = array(
                'line_discount' => $this->matched_sets,
            );
            if(function_exists('WC')){
                if(!empty(WC()->session)){
                    if(method_exists(WC()->session, 'set')){
                        WC()->session->set('woo_price_discount', json_encode($discount_log));
                    }
                }
            }
        }

        /**
         * @return array
         */
        public function getBaseConfig()
        {
            $option = get_option($this->default_option);
            if (!$option || is_null($option)) {
                return array();
            } else {
                return $option;
            }
        }

        /**
         * List of Checklist.
         */
        public function checkPoint()
        {
            // Apply rules with products.
            // NOT YET USED.
            if ($this->discount_applied) return true;
        }

        /**
         * Filter the Rules with some validations.
         */
        public function filterRules()
        {
            $rules = $this->rules;

            if (is_null($rules) || !isset($rules)) return false;

            // Start with empty set.
            $rule_set = array();

            foreach ($rules as $index => $rule) {
                $status = (isset($rule->status) ? $rule->status : false);

                // To Check as Plugin Active - InActive.
                if ($status == 'publish') {
                    $date_from = (isset($rule->date_from) ? $rule->date_from : false);
                    $date_to = (isset($rule->date_to) ? $rule->date_to : false);
                    $validateDate = FlycartWooDiscountRulesGeneralHelper::validateDateAndTime($date_from, $date_to);
                    // Validating Rule with Date of Expiry.
                    if ($validateDate) {

                        // Validating the Rule with its Order ID.
                        if (isset($rule->rule_order)) {
                            // If Order ID is '-', then this rule not going to implement.
                            if ($rule->rule_order !== '-') {
                                $rule_set[] = $rule;
                            }
                        }
                    }
                }
            }
            $this->rules = $rule_set;

            // To Order the Rules, based on its order ID.
            $this->orderRules();
        }

        /**
         * Ordering the Set of Rules.
         *
         * @return bool
         */
        public function orderRules()
        {
            if (empty($this->rules)) return false;

            $ordered_rules = array();

            // Make associative array with Order ID.
            foreach ($this->rules as $index => $rule) {
                if (isset($rule->rule_order)) {
                    if ($rule->rule_order != '') {
                        $ordered_rules[$rule->rule_order] = $rule;
                    }
                }
            }
            // Order the Rules with it's priority.
            ksort($ordered_rules);

            $this->rules = $ordered_rules;
        }

        /**
         * Apply the Rules to line items for BOGO.
         *
         * @param string $cart_item_key
         * @param int $product_id
         * @param int $quantity
         * @param int $variation_id
         * @param array $variation
         * @param array $cart_item_data
         * @return boolean
         * */
        public function handleBOGODiscount($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
        {
            global $woocommerce;

            $this->organizeRules();

            // If there is no rules, then return false.
            if (!isset($this->rules)) return false;

            // Check point having list of checklist to apply.
            if ($this->checkPoint()) return false;

            // To Generate Valid Rule sets.
            $this->generateRuleSets($woocommerce);
            // Sort cart by price ascending
            $product_id_new = $product_id;
            if($variation_id) $product_id_new = $variation_id;
            $product = FlycartWoocommerceProduct::wc_get_product($product_id_new);

            if(empty($cart_item_data) || empty($cart_item_data['data'])){
                $cart_item_data = array_merge( $cart_item_data, array(
                    'key'          => $cart_item_key,
                    'product_id'   => $product_id,
                    'variation_id' => $variation_id,
                    'variation'    => $variation,
                    'quantity'     => $quantity,
                    'data'         => $product,
                ) );
            }
            $carts = FlycartWoocommerceCart::get_cart();
            if(!empty($carts)){
                foreach ($carts as $cart_key => $cart){
                    if($cart_key == $cart_item_key){
                        $cart_item_data['quantity'] = $cart['quantity'];
                    }
                }
            }
            $this->bogo_matches = array();
            $this->matchRules($cart_item_key, $cart_item_data, 1, 1);
            if(!empty($this->bogo_matches)){
                if(count($this->bogo_matches) > 0){
                    foreach ($this->bogo_matches as $free_product_id => $bogo_match){
                        $found = false;
                        foreach ($carts as $cart_item) {
                            $cart_product_id = $cart_item['product_id'];
                            if($cart_item['variation_id']) $cart_product_id = $cart_item['variation_id'];
                            if ($free_product_id == $cart_product_id) {
                                $found = true;
                                $quantity = $cart_item['quantity'];
                                $cart_item_key = $cart_item['key'];
                                break;
                            }
                        }
                        if ($found) {
                            if($quantity < $bogo_match['count']) FlycartWoocommerceCart::set_quantity($cart_item_key, $bogo_match['count']);
                        } else {
                            remove_action('woocommerce_add_to_cart', array($this, 'handleBOGODiscount'));
                            $product = FlycartWoocommerceProduct::wc_get_product($free_product_id);
                            $productParentId = FlycartWoocommerceProduct::get_parent_id($product);
                            if($productParentId){
                                FlycartWoocommerceCart::add_to_cart($productParentId, $bogo_match['count'], $free_product_id, FlycartWoocommerceProduct::get_attributes($product));
                            } else {
                                FlycartWoocommerceCart::add_to_cart($free_product_id, $bogo_match['count']);
                            }
                            add_action('woocommerce_add_to_cart', array($this, 'handleBOGODiscount'), 10, 6);
                        }
                    }
                }
            }
        }

        /**
         * Handle coupon after apply coupon code
         *
         * @param $coupon_code
         * */
        public function handleBOGODiscountAfterApplyCoupon($coupon_code){
            $carts = FlycartWoocommerceCart::get_cart();
            foreach ($carts as $cart_item_key => $cart_item){
                $quantity = $old_quantity = $cart_item['quantity'];
                do_action( 'woocommerce_after_cart_item_quantity_update', $cart_item_key, $quantity, $old_quantity, $carts );
            }
        }

        /**
         * Apply the Rules to line items for BOGO on quantity update.
         *
         * @param string $cart_item_key
         * @param int $quantity
         * @param int $old_quantity
         * @param array $cart
         * @return void
         * */
        public function handleBOGODiscountOnUpdateQuantity($cart_item_key, $quantity, $old_quantity, $cart = array()){
            $cart_data = array();
            if(isset($cart->cart_contents) && !empty($cart->cart_contents)){
                foreach ($cart->cart_contents as $cart_key => $cartItem){
                    if($cart_item_key  == $cart_key){
                        $cart_data = $cartItem;
                        break;
                    }
                }
            } else {
                $carts = FlycartWoocommerceCart::get_cart();
                foreach ($carts as $key => $cart_item) {
                    if($cart_item_key  == $key){
                        $cart_data = $cart_item;
                        break;
                    }
                }
            }
            if(!empty($cart_data)){
                $carts = FlycartWoocommerceCart::get_cart();
                if(!empty($carts)){
                    global $woocommerce;

                    $this->organizeRules();

                    // If there is no rules, then return false.
                    if (!isset($this->rules)) return false;

                    // Check point having list of checklist to apply.
                    if ($this->checkPoint()) return false;

                    // To Generate Valid Rule sets.
                    $this->generateRuleSets($woocommerce);
                    // Sort cart by price ascending
                    $product_id_new = $cart_data['product_id'];
                    if($cart_data['variation_id']) $product_id_new = $cart_data['variation_id'];
                    $this->bogo_matches = array();
                    $this->matchRules($cart_item_key, $cart_data, 1, 1);
                    if(!empty($this->bogo_matches)){
                        if(count($this->bogo_matches) > 0){
                            foreach ($this->bogo_matches as $free_product_id => $bogo_match){
                                $found = false;
                                foreach ($carts as $cart_item) {
                                    $cart_product_id = $cart_item['product_id'];
                                    if($cart_item['variation_id']) $cart_product_id = $cart_item['variation_id'];
                                    if ($free_product_id == $cart_product_id) {
                                        $found = true;
                                        $quantity = $cart_item['quantity'];
                                        $cart_item_key = $cart_item['key'];
                                        break;
                                    }
                                }
                                if ($found) {
                                    if($quantity < $bogo_match['count']) FlycartWoocommerceCart::set_quantity($cart_item_key, $bogo_match['count']);
                                } else {
                                    remove_action('woocommerce_after_cart_item_quantity_update', array($this, 'handleBOGODiscountOnUpdateQuantity'));
                                    $product = FlycartWoocommerceProduct::wc_get_product($free_product_id);
                                    $productParentId = FlycartWoocommerceProduct::get_parent_id($product);
                                    if($productParentId){
                                        FlycartWoocommerceCart::add_to_cart($productParentId, $bogo_match['count'], $free_product_id, FlycartWoocommerceProduct::get_attributes($product));
                                    } else {
                                        FlycartWoocommerceCart::add_to_cart($free_product_id, $bogo_match['count']);
                                    }
                                    $woocommerce_v3 = FlycartWoocommerceVersion::wcVersion('3.0');
                                    if($woocommerce_v3)
                                        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'handleBOGODiscountOnUpdateQuantity'), 10, 4);
                                    else
                                        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'handleBOGODiscountOnUpdateQuantity'), 10, 3);
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Apply the Rules to line items.
         *
         * @return bool
         */
        public function applyRules($product_page = 0)
        {
            global $woocommerce;

            // If there is no rules, then return false.
            if (!isset($this->rules)) return false;

            // Check point having list of checklist to apply.
            if ($this->checkPoint()) return false;

            // To Generate Valid Rule sets.
            $this->generateRuleSets($woocommerce);
            // Sort cart by price ascending

            $cart_contents = array();
            if(!empty($woocommerce)){
                if(!empty($woocommerce->cart)){
                    if(!empty($woocommerce->cart->cart_contents)){
                        $cart_contents = $this->sortCartPrice($woocommerce->cart->cart_contents, 'asc');
                    }
                }
            }


            $this->matched_sets = array();
            if(!empty($cart_contents))
                foreach ($cart_contents as $index => $item) {
                    $this->matchRules($index, $item, $product_page);
                }

            $this->makeLog();
        }

        /**
         * Generate the Suitable and active rule sets.
         *
         * @param $woocommerce
         * @return bool
         */
        public function generateRuleSets($woocommerce)
        {
            $rule_sets = array();

            if (!isset($this->rules)) return false;

            // Loop the Rules set to collect matched rules.
            foreach ($this->rules as $index => $rule) {
                // General Rule Info.
                $rule_sets[$index]['discount_type'] = 'price_discount';
                $rule_sets[$index]['name'] = (isset($rule->rule_name) ? $rule->rule_name : 'Rule_' . $index);
                $rule_sets[$index]['descr'] = (isset($rule->rule_descr) ? $rule->rule_descr : '');
                $rule_sets[$index]['method'] = (isset($rule->rule_method) ? $rule->rule_method : 'qty_based');
                $rule_sets[$index]['qty_based_on'] = (isset($rule->qty_based_on) ? $rule->qty_based_on : 'each_product');
                $rule_sets[$index]['date_from'] = (isset($rule->date_from) ? $rule->date_from : false);
                $rule_sets[$index]['date_to'] = (isset($rule->date_to) ? $rule->date_to : false);
                $rule_sets[$index]['allow']['purchase_history'] = 'yes';
                // Default setup for all customers.
                $rule_sets[$index]['allow']['users'] = 'all';
                $rule_sets[$index]['allow']['user_role'] = $rule_sets[$index]['allow']['subtotal'] = true;
                $rule_sets[$index]['allow']['coupon'] = 1;
                $rule_sets[$index]['exclude_sale_items'] = 0;

                // For quantity based discount
                if($rule_sets[$index]['method'] == 'qty_based'){
                    // List the type of apply, by Product or by Category.
                    if (isset($rule->apply_to)) {
                        // If Rule is processed by Specific Products, then..
                        if ($rule->apply_to == 'specific_products') {
                            if (isset($rule->product_to_apply)) {
                                $rule_sets[$index]['type']['specific_products'] = $this->checkWithProducts($rule, $woocommerce);
                            }
                            if (isset($rule->is_cumulative_for_products) && $rule->is_cumulative_for_products) {
                                $rule_sets[$index]['is_cumulative_for_products'] = 1;
                            } else {
                                $rule_sets[$index]['is_cumulative_for_products'] = 0;
                            }
                        } else if ($rule->apply_to == 'specific_category') {
                            if (isset($rule->apply_child_categories) && $rule->apply_child_categories) {
                                $rule_sets[$index]['type']['apply_child_categories'] = 1;
                            } else {
                                $rule_sets[$index]['type']['apply_child_categories'] = 0;
                            }

                            if (isset($rule->category_to_apply)) {
                                $rule_sets[$index]['type']['specific_category'] = $this->checkWithCategory($rule, $woocommerce);
                                if($rule_sets[$index]['type']['apply_child_categories']){
                                    $cat = $rule_sets[$index]['type']['specific_category'];
                                    $rule_sets[$index]['type']['specific_category'] =  FlycartWooDiscountRulesGeneralHelper::getAllSubCategories($cat);
                                }
                            }
                            if (isset($rule->is_cumulative) && $rule->is_cumulative) {
                                $rule_sets[$index]['type']['is_cumulative'] = 1;
                            } else {
                                $rule_sets[$index]['type']['is_cumulative'] = 0;
                            }
                            $rule_sets[$index]['product_to_exclude'] = $this->getExcludeProductsFromRule($rule);
                            if (isset($rule->exclude_sale_items) && $rule->exclude_sale_items) $rule_sets[$index]['exclude_sale_items'] = 1;
                        } else if ($rule->apply_to == 'specific_attribute') {
                            $rule_sets[$index]['type']['specific_attribute'] = $this->getAttributeFromRule($rule, $woocommerce);
                            $rule_sets[$index]['product_to_exclude'] = $this->getExcludeProductsFromRule($rule);
                            if (isset($rule->exclude_sale_items) && $rule->exclude_sale_items) $rule_sets[$index]['exclude_sale_items'] = 1;
                            if (isset($rule->is_cumulative_attribute) && $rule->is_cumulative_attribute) {
                                $rule_sets[$index]['type']['is_cumulative'] = 1;
                            } else {
                                $rule_sets[$index]['type']['is_cumulative'] = 0;
                            }
                        } else {
                            $rule_sets[$index]['type'] = 'all';
                            $rule_sets[$index]['product_to_exclude'] = $this->getExcludeProductsFromRule($rule);
                            if (isset($rule->exclude_sale_items) && $rule->exclude_sale_items) $rule_sets[$index]['exclude_sale_items'] = 1;
                            if (isset($rule->is_cumulative_for_products) && $rule->is_cumulative_for_products) {
                                $rule_sets[$index]['is_cumulative_for_products'] = 1;
                            } else {
                                $rule_sets[$index]['is_cumulative_for_products'] = 0;
                            }
                        }

                        $rule_sets[$index]['discount'] = 0;
                        if (isset($rule->discount_range)) {
                            if ($rule->discount_range != '') {
                                $rule_sets[$index]['discount'] = $this->getDiscountRangeList($rule);
                            }
                        }

                        // If Rule is processed by Specific Customers, then..
                        if ($rule->customer == 'only_given') {
                            if (isset($rule->users_to_apply)) {
                                $rule_sets[$index]['allow']['users'] = $this->checkWithUsers($rule, $woocommerce);
                            }
                        }
                        $rule_sets[$index]['apply_to'] = $rule->apply_to;

                        // Default setup for purchase history
                        if(isset($rule->based_on_purchase_history) && $rule->based_on_purchase_history){
                            $rule_sets[$index]['allow']['purchase_history'] = $this->checkWithUsersPurchaseHistory($rule, $woocommerce);
                        }

                        // check for user roles
                        if(isset($rule->user_roles_to_apply)){
                            $rule_sets[$index]['allow']['user_role'] = $this->checkWithUserRoles($rule);
                        }

                        // check for subtotal
                        if(isset($rule->subtotal_to_apply_option)){
                            $is_woocommerce3 = FlycartWoocommerceVersion::isWCVersion3x();
                            if($is_woocommerce3){
                                $rule_sets[$index]['allow']['subtotal'] = $this->checkSubtotalMatches($rule);
                            }
                        }

                        // check for coupon
                        if(isset($rule->coupons_to_apply_option)){
                            $rule_sets[$index]['allow']['coupon'] = $this->checkWithCouponApplied($rule);
                            if($rule_sets[$index]['allow']['coupon']){
                                if(!empty($rule->coupons_to_apply)){
                                    $coupons = explode(',', $rule->coupons_to_apply);
                                    FlycartWooDiscountRulesGeneralHelper::removeCouponPriceInCart($coupons);
                                }
                            }
                        }
                    }

                    // If Current Customer is not Allowed to use this discount, then it's going to be removed.
                    if ($rule_sets[$index]['allow']['users'] == 'no' || !$rule_sets[$index]['allow']['user_role'] || $rule_sets[$index]['allow']['purchase_history'] == 'no' || !($rule_sets[$index]['allow']['coupon']) || !($rule_sets[$index]['allow']['subtotal'])) {
                        $failed_due_to = array(
                            'user' => ($rule_sets[$index]['allow']['users'] == 'no')? false: true,
                            'purchase_history' => ($rule_sets[$index]['allow']['purchase_history'] == 'no')? false: true,
                            'user_role' => ($rule_sets[$index]['allow']['user_role'])? true: false,
                            'coupon' => ($rule_sets[$index]['allow']['coupon'])? true: false,
                            'subtotal' => ($rule_sets[$index]['allow']['subtotal'])? true: false,
                        );
                        do_action('woo_discount_rules_failed_to_apply', $rule, $failed_due_to);
                        unset($rule_sets[$index]);
                    }

                } else if($rule_sets[$index]['method'] == 'product_based'){
                    $rule_sets[$index]['product_based_condition'] = json_decode((isset($rule->product_based_condition) ? $rule->product_based_condition : '{}'), true);
                    $rule_sets[$index]['product_based_discount'] = json_decode((isset($rule->product_based_discount) ? $rule->product_based_discount : '{}'), true);
                }
            }
            $this->rule_sets = $rule_sets;
        }

        /**
         * Check with users roles
         * */
        public function checkWithUserRoles($rule){
            $user_roles_to_apply = json_decode($rule->user_roles_to_apply, true);
            if(!empty($user_roles_to_apply)){
                if (count(array_intersect(FlycartWooDiscountRulesGeneralHelper::getCurrentUserRoles(), $user_roles_to_apply)) == 0) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Check coupon applied
         * */
        public function checkWithCouponApplied($rule){
            $allowed = 1;
            if(isset($rule->coupons_to_apply_option)){
                if($rule->coupons_to_apply_option == 'any_selected'){
                    if(isset($rule->coupons_to_apply) && $rule->coupons_to_apply != ''){
                        $allowed = $this->validatePriceCouponAppliedAnyOne($rule->coupons_to_apply);
                    }
                } elseif ($rule->coupons_to_apply_option == 'all_selected'){
                    if(isset($rule->coupons_to_apply) && $rule->coupons_to_apply != ''){
                        $allowed = $this->validatePriceCouponAppliedAllSelected($rule->coupons_to_apply);
                    }
                }
            }

            return $allowed;
        }

        /**
         * Check subtotal matches
         * */
        public function checkSubtotalMatches($rule){
            $allowed = 1;
            if(isset($rule->subtotal_to_apply_option)){
                if($rule->subtotal_to_apply_option == 'atleast'){
                    if(isset($rule->subtotal_to_apply) && $rule->subtotal_to_apply > 0){
                        $sub_total = FlycartWooDiscountRulesAdvancedHelper::get_calculated_item_subtotal();
                        if(!($rule->subtotal_to_apply <= $sub_total)){
                            $allowed = 0;
                        }
                    }
                }
            }

            return $allowed;
        }

        /**
         * check the any one of the selected coupon applied
         * */
        protected function validatePriceCouponAppliedAnyOne($coupons_selected){
            global $woocommerce;
            $allowed = 0;
            $coupons = explode(',', $coupons_selected);
            foreach ($coupons as $coupon){
                if($woocommerce->cart->has_discount($coupon)){
                    $allowed = 1;
                    break;
                }
            }

            return $allowed;
        }

        /**
         * check the all the selected coupon applied
         * */
        protected function validatePriceCouponAppliedAllSelected($coupons_selected){
            global $woocommerce;
            $allowed = 0;
            $coupons = explode(',', $coupons_selected);
            foreach ($coupons as $coupon){
                if(!$woocommerce->cart->has_discount($coupon)){
                    $allowed = 0;
                    break;
                } else {
                    $allowed = 1;
                }
            }

            return $allowed;
        }

        /**
         * Check with users purchase history
         * */
        public function checkWithUsersPurchaseHistory($rule, $woocommerce)
        {
            $allowed = 'no';
            $user = get_current_user_id();
            if($user){
                if(isset($rule->purchased_history_amount) && isset($rule->purchase_history_status_list)){
                    if($rule->purchased_history_amount >= 0){
                        $purchase_history_status_list = json_decode($rule->purchase_history_status_list, true);
                        $customerOrders = get_posts( array(
                            'numberposts' => -1,
                            'meta_key'    => '_customer_user',
                            'meta_value'  => $user,
                            'post_type'   => wc_get_order_types(),
                            'post_status' => $purchase_history_status_list,
                        ) );
                        $totalPurchasedAmount = $totalOrder = 0;
                        if(!empty($customerOrders)){
                            foreach ($customerOrders as $customerOrder) {
                                $order = FlycartWoocommerceOrder::wc_get_order($customerOrder->ID);
                                $total = FlycartWoocommerceOrder::get_total($order);
                                if($rule->based_on_purchase_history == 3){
                                    $products = $this->getPurchasedProductsFromRule($rule);
                                    $product_ids = FlycartWoocommerceOrder::get_product_ids($order);
                                    if(!empty($products)){
                                        if (!count(array_intersect($products, $product_ids)) > 0) {
                                            continue;
                                        }
                                    }
                                }
                                $totalPurchasedAmount += $total;
                                $totalOrder++;
                            }
                        }
                        $totalAmount = $totalPurchasedAmount;
                        if($rule->based_on_purchase_history == 2 || $rule->based_on_purchase_history == 3){
                            $totalAmount = $totalOrder;
                        }
                        $purchased_history_type = isset($rule->purchased_history_type)? $rule->purchased_history_type: 'atleast';
                        if($purchased_history_type == 'less_than_or_equal'){
                            if($totalAmount <= $rule->purchased_history_amount){
                                $allowed = 'yes';
                            }
                        } else {
                            if($totalAmount >= $rule->purchased_history_amount){
                                $allowed = 'yes';
                            }
                        }

                    }
                }
            }

            return $allowed;
        }

        /**
         * To format rules to apply
         *
         * @param array $discount_amount
         * @param string $rule_name
         * @param string $cart_key
         * @param int $product_id
         * @param int $rule_order
         * @param array $additional_keys
         * @return array
         * */
        public function formatRulesToApply($discount_amount, $rule_name, $cart_key, $product_id, $rule_order = 0, $additional_keys = array()){
            $toApply = array();
            $toApply['amount'] = $discount_amount;
            $toApply['name'] = $rule_name;
            $toApply['item'] = $cart_key;
            $toApply['id'] = $product_id;
            if($rule_order)
                $toApply['rule_order'] = $rule_order;
            if(!empty($additional_keys)) foreach ($additional_keys as $key => $additional_key) $toApply[$key] = $additional_key;

            return $toApply;
        }

        public function getBOGORules()
        {
            if(!FlycartWooDiscountRulesGeneralHelper::haveToApplyTheRules()) return false;
            $bogo_rules = array();
            $i = 0;
            if(!empty($this->rule_sets)){
                foreach ($this->rule_sets as $id => $rule) {
                    if(isset($rule['method']) && $rule['method'] == 'qty_based'){
                        if (isset($rule['type']) && isset($rule['apply_to'])) {
                            //Check for product_discount to apply the rule only once
                            if(isset($rule['discount'])) {
                                if (!empty($rule['discount'])) {
                                    $hasBOGO = 0;
                                    foreach ($rule['discount'] as $discount_rules) {
                                        if (isset($discount_rules->discount_type) && $discount_rules->discount_type == 'product_discount') {
                                            $hasBOGO = 1;
                                        }
                                    }
                                    if($hasBOGO){
                                        $bogo_rules[] = $rule;
                                    }
                                }
                            }
                        }
                    }

                    $i++;
                }
            }

            return $bogo_rules;
        }

        /**
         * Fetch back the Matched rules.
         *
         * @param string $index
         * @param array $item
         * @param int $product_page
         * @param int $bogo
         * @return void
         */
        public function matchRules($index, $item, $product_page = 0, $bogo = 0)
        {
            if(!isset($item['data']) || empty($item['data'])) return false;
            if(!FlycartWooDiscountRulesGeneralHelper::haveToApplyTheRules()) return false;
            $applied_rules = array();
            $quantity = (isset($item['quantity']) ? $item['quantity'] : 0);
            $i = 0;
            if(!empty($this->rule_sets))
                foreach ($this->rule_sets as $id => $rule) {
                    $quantity = (isset($item['quantity']) ? $item['quantity'] : 0);
                    if(isset($rule['method']) && $rule['method'] == 'qty_based'){
                        if (isset($rule['type']) && isset($rule['apply_to'])) {
                            if($product_page && !$bogo){
                                //Check for product_discount to apply the rule only once
                                if(isset($rule['discount'])) {
                                    if (!empty($rule['discount'])) {
                                        $hasBOGO = 0;
                                        foreach ($rule['discount'] as $discount_rules) {
                                            if (isset($discount_rules->discount_type) && $discount_rules->discount_type == 'product_discount') {
                                                $hasBOGO = 1;
                                            }
                                        }
                                        if($hasBOGO) continue;
                                    }
                                }
                            }

                            // Working with Products and Category.
                            switch ($rule['apply_to']) {

                                case 'specific_products':
                                    if ($this->isItemInProductList($rule['type']['specific_products'], $item)) {
                                        if(isset($rule['is_cumulative_for_products']) && $rule['is_cumulative_for_products']){
                                            $quantity = $this->getProductQuantityForCumulativeSpecificProducts($item, $product_page, $rule, $rule['type']['specific_products']);
                                        }
                                        $discount_amount = $this->getAdjustmentAmount($item, $quantity, $this->array_first($rule['discount']), $product_page, $bogo);
                                        $applied_rules[$i] = $this->formatRulesToApply($discount_amount, $rule['name'], $index, $item['product_id'], $id);
                                    }
                                    break;

                                case 'specific_category':
                                    $notInProductList = !$this->isItemInProductList($rule['product_to_exclude'], $item);
                                    $is_not_in_exclude_sale_items = !$this->isItemInSaleItems($rule['exclude_sale_items'], $item['data']);
                                    if ($this->isItemInCategoryList($rule['type']['specific_category'], $item) && $notInProductList && $is_not_in_exclude_sale_items) {
                                        $alreadyExists = 0;
                                        if(isset($rule['type']['is_cumulative']) && $rule['type']['is_cumulative']){
                                            $totalQuantityInThisCategory = $this->getProductQuantityInThisCategory($rule['type']['specific_category'], $rule['product_to_exclude'], $rule['exclude_sale_items']);
                                            if($product_page){
                                                $quantity = $quantity+$totalQuantityInThisCategory;
                                            } else {
                                                $quantity = $totalQuantityInThisCategory;
                                            }
                                            //Check for product_discount to apply the rule only once
                                            if(isset($rule['discount'])){
                                                if(!empty($rule['discount'])){
                                                    foreach($rule['discount'] as $discount_rules){
                                                        if(isset($discount_rules->discount_type) && $discount_rules->discount_type == 'product_discount'){
                                                            if(!empty($this->matched_sets)){
                                                                foreach($this->matched_sets as $machedRules){
                                                                    foreach($machedRules as $machedRule){
                                                                        if(isset($machedRule['rule_order']) && $machedRule['rule_order'] == $id){
                                                                            $alreadyExists = 1;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if(!$alreadyExists){
                                            $discount_amount = $this->getAdjustmentAmount($item, $quantity, $this->array_first($rule['discount']), $product_page, $bogo, $rule['product_to_exclude']);
                                            $applied_rules[$i] = $this->formatRulesToApply($discount_amount, $rule['name'], $index, $item['product_id'], $id);
                                        }
                                    }
                                    break;
                                case 'specific_attribute':
                                    $notInProductList = !$this->isItemInProductList($rule['product_to_exclude'], $item);
                                    $is_not_in_exclude_sale_items = !$this->isItemInSaleItems($rule['exclude_sale_items'], $item['data']);
                                    if ($this->isItemInAttributeList($rule['type']['specific_attribute'], $item, $id) && $notInProductList && $is_not_in_exclude_sale_items) {
                                        $alreadyExists = 0;
                                        if(isset($rule['type']['is_cumulative']) && $rule['type']['is_cumulative']){
                                            $totalQuantityInThisAttribute = $this->getProductQuantityInThisAttribute($rule['type']['specific_attribute'], $rule['product_to_exclude'], $rule['exclude_sale_items'], $id);
                                            if($product_page){
                                                $quantity = $quantity+$totalQuantityInThisAttribute;
                                            } else {
                                                $quantity = $totalQuantityInThisAttribute;
                                            }
                                            //Check for product_discount to apply the rule only once
                                            if(isset($rule['discount'])){
                                                if(!empty($rule['discount'])){
                                                    foreach($rule['discount'] as $discount_rules){
                                                        if(isset($discount_rules->discount_type) && $discount_rules->discount_type == 'product_discount'){
                                                            if(!empty($this->matched_sets)){
                                                                foreach($this->matched_sets as $machedRules){
                                                                    foreach($machedRules as $machedRule){
                                                                        if(isset($machedRule['rule_order']) && $machedRule['rule_order'] == $id){
                                                                            $alreadyExists = 1;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if(!$alreadyExists){
                                            $discount_amount = $this->getAdjustmentAmount($item, $quantity, $this->array_first($rule['discount']), $product_page, $bogo, $rule['product_to_exclude']);
                                            $applied_rules[$i] = $this->formatRulesToApply($discount_amount, $rule['name'], $index, $item['product_id'], $id);
                                        }
                                    }
                                    break;

                                case 'all_products':
                                default:
                                    $is_not_in_exclude_sale_items = !$this->isItemInSaleItems($rule['exclude_sale_items'], $item['data']);
                                    if (!$this->isItemInProductList($rule['product_to_exclude'], $item) && $is_not_in_exclude_sale_items) {
                                        if(isset($rule['is_cumulative_for_products']) && $rule['is_cumulative_for_products']){
                                            $quantity = $this->getProductQuantityForCumulativeProducts($item, $product_page, $rule);
                                        }
                                        $discount_amount = $this->getAdjustmentAmount($item, $quantity, $this->array_first($rule['discount']), $product_page, $bogo, $rule['product_to_exclude']);
                                        $applied_rules[$i] = $this->formatRulesToApply($discount_amount, $rule['name'], $index, $item['product_id'], $id);
                                    }

                                    break;
                            }
                            if(isset($applied_rules[$i]['amount']['product_ids'])){
                                if(!empty($applied_rules[$i]['amount']['product_ids'])){
                                    $applyToProducts = $applied_rules[$i]['amount']['product_ids'];
                                    $applyPercent = $applied_rules[$i]['amount'];
                                    $applied_rules = array();
                                    foreach ($applyToProducts as $key => $productId) {
                                        $cart = FlycartWoocommerceCart::get_cart();
                                        foreach ($cart as $cart_item_key => $values) {
                                            $_product = $values['data'];
                                            if (FlycartWoocommerceProduct::get_id($_product) == $productId){
                                                $additionalKeys = array('apply_from' => $item['product_id']);
                                                $this->matched_sets[$cart_item_key][] = $this->formatRulesToApply($applyPercent, $rule['name'], $cart_item_key, $productId, $id, $additionalKeys);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else if(isset($rule['method']) && $rule['method'] == 'product_based'){
                        $checkRuleMatches = $this->checkProductBasedRuleMatches($rule, $item, $quantity);
                        if(!empty($checkRuleMatches)){
                            if(class_exists('FlycartWooDiscountRulesPriceProductDependent'))
                                $discountInEachProducts = FlycartWooDiscountRulesPriceProductDependent::getDiscountInEachProducts($item, $rule, $checkRuleMatches, $product_page, $index);
                            foreach ($checkRuleMatches['apply_to']['products'] as $key => $productId) {
                                if($product_page && $productId == $index){
                                    $additionalKeys = array('apply_from' => $item['product_id']);
                                    if(isset($discountInEachProducts[$productId]))
                                        $discount_amount = $discountInEachProducts[$productId];
                                    else
                                        $discount_amount = $checkRuleMatches['amount'];
                                    $applied_rules_new = $this->formatRulesToApply($discount_amount, $rule['name'], $index, $productId, $id, $additionalKeys);
                                    $this->matched_sets[$index][] = $applied_rules_new;
                                } else {
                                    $cart = FlycartWoocommerceCart::get_cart();
                                    foreach ($cart as $cart_item_key => $values) {
                                        $_product = $values['data'];
                                        if (FlycartWoocommerceProduct::get_id($_product) == $productId){
                                            $additionalKeys = array('apply_from' => $item['product_id']);
                                            if(isset($discountInEachProducts[$productId]))
                                                $discount_amount = $discountInEachProducts[$productId];
                                            else
                                                $discount_amount = $checkRuleMatches['amount'];
                                            $applied_rules_new = $this->formatRulesToApply($discount_amount, $rule['name'], $cart_item_key, $productId, $id, $additionalKeys);
                                            $alreadyExists = 0;
                                            if(!empty($this->matched_sets[$cart_item_key])){
                                                foreach($this->matched_sets[$cart_item_key] as $machedRules){
                                                    if(isset($machedRules['rule_order']) && $machedRules['rule_order'] == $id){
                                                        $alreadyExists = 1;
                                                        break;
                                                    }
                                                }
                                            }
                                            if(!$alreadyExists) $this->matched_sets[$cart_item_key][] = $applied_rules_new;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $i++;
                }
            if(isset($this->matched_sets[$index]) && !empty($this->matched_sets[$index])){
                $this->matched_sets[$index] = array_merge($this->matched_sets[$index], $applied_rules);
            } else {
                $this->matched_sets[$index] = $applied_rules;
            }
            $this->matched_sets_for_product = $this->matched_sets;
        }

        /**
         * Check Product based rules matches
         * */
        public function checkProductBasedRuleMatches($rule, $item, $quantity){
            $result = array();
            if(isset($rule['product_based_condition']) && !empty($rule['product_based_condition'])){
                $product_based_conditions = $rule['product_based_condition'];
                $buy_type = isset($product_based_conditions['product_buy_type']) ? $product_based_conditions['product_buy_type'] : 'any';
                $quantity_rule = isset($product_based_conditions['product_quantity_rule']) ? $product_based_conditions['product_quantity_rule'] : 'more';
                $quantity_from = isset($product_based_conditions['product_quantity_from']) ? $product_based_conditions['product_quantity_from'] : '';
                $quantity_to = isset($product_based_conditions['product_quantity_to']) ? $product_based_conditions['product_quantity_to'] : '';
                $product_to_buy = isset($product_based_conditions['product_to_buy']) ? $product_based_conditions['product_to_buy'] : array();
                $product_to_buy = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($product_to_buy);
                $product_to_apply = isset($product_based_conditions['product_to_apply']) ? $product_based_conditions['product_to_apply'] : array();
                $product_to_apply = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($product_to_apply);
                $category_to_apply = isset($product_based_conditions['category_to_apply']) ? $product_based_conditions['category_to_apply'] : array();
                $get_discount_type = isset($product_based_conditions['get_discount_type']) ? $product_based_conditions['get_discount_type'] : 'product';

                $product_based_discounts = isset($rule['product_based_discount']) ? $rule['product_based_discount'] : array();
                $discount_type = isset($product_based_discounts['discount_type']) ? $product_based_discounts['discount_type'] : 'percentage_discount';
                $discount_value = isset($product_based_discounts['discount_value']) ? $product_based_discounts['discount_value'] : '';
                $cart = FlycartWoocommerceCart::get_cart();
                $_quantity = array();
                if(isset($item['variation_id']) && $item['variation_id'])
                    $product_id = $item['variation_id'];
                else
                    $product_id = $item['product_id'];
                if($get_discount_type == 'category'){
                    $product_to_apply = array();
                    if ( sizeof( $cart ) > 0 ) {
                        foreach ($cart as $cart_item_key => $values) {
                            $resultInCat = $this->isItemInCategoryList($category_to_apply, $values);
                            if($resultInCat){
                                if(isset($values['variation_id']) && $values['variation_id'])
                                    $product_id_in_cat = $values['variation_id'];
                                else
                                    $product_id_in_cat = $values['product_id'];
                                $product_to_apply[] = $product_id_in_cat;
                            }
                        }
                    }
                }
                if ( sizeof( $cart ) > 0 ) {
                    foreach ($product_to_buy as $key => $productId) {
                        foreach ($cart as $cart_item_key => $values) {
                            $_product = $values['data'];
                            if (FlycartWoocommerceProduct::get_id($_product) == $productId){
                                $_quantity[$productId] = $values['quantity'];
                            }
                        }
                    }
                }
                $quantity = FlycartWooDiscountRulesPriceProductBased::adjustQuantity($buy_type, $_quantity);
                if((in_array($product_id, $product_to_buy) || in_array($product_id, $product_to_apply)) && !empty($_quantity)){
                    $proceed = 1;
                    if($buy_type == 'each'){
                        $allProductsInCart = array_keys($_quantity);
                        $matchedProducts = array_intersect($allProductsInCart, $product_to_buy);
                        if(count($product_to_buy) != count($matchedProducts)) $proceed = 0;
                    }
                    if($proceed){
                        $quantityMatched = FlycartWooDiscountRulesPriceProductBased::verifyQuantity($quantity_rule, $quantity, $quantity_from, $quantity_to, $buy_type);
                        if($quantityMatched){
                            $result['amount'][$discount_type] = $discount_value;
                            $result['apply_to']['products'] = $product_to_apply;
                        }
                    }
                }
            }
            return $result;
        }

        /**
         * Get quantity of products in specific category
         * */
        public function getProductQuantityInThisCategory($category, $product_to_exclude, $exclude_sale_items){
            global $woocommerce;
            $hasExcludeProduct = $quantity = 0;
            if(!empty($product_to_exclude) && is_array($product_to_exclude) && count($product_to_exclude)) $hasExcludeProduct = 1;
            if(count($woocommerce->cart->cart_contents)){
                foreach ($woocommerce->cart->cart_contents as $cartItem) {
                    //Exclude the bundled products items
                    if(isset($cartItem['bundled_item_id']) && !empty($cartItem['bundled_item_id'])){
                        continue;
                    }
//                    if(isset($cartItem['variation']) && !empty($cartItem['variation'])){
//                        if(isset($cartItem['variation']['Type']) && !empty($cartItem['variation']['Type'])){
//                            if($cartItem['variation']['Type'] == "Free Item") continue;
//                        }
//                    }
                    $is_exclude_sale_items = $this->isItemInSaleItems($exclude_sale_items, $cartItem['data']);
                    if($is_exclude_sale_items){
                        continue;
                    }
                    if($hasExcludeProduct){
                        $product_id = FlycartWoocommerceProduct::get_id($cartItem['data']);
                        if(in_array($product_id, $product_to_exclude)){
                            continue;
                        }
                    }
                    $product_id_parent = $cartItem['product_id'];
                    if(!$product_id_parent){
                        if(isset($cartItem['variation_id'])){
                            $product_id_parent = $cartItem['variation_id'];
                        }
                    }
                    if(isset(self::$product_categories[$product_id_parent])){
                        $terms = self::$product_categories[$product_id_parent];
                    } else {
                        $terms = FlycartWoocommerceProduct::get_category_ids(FlycartWoocommerceProduct::wc_get_product($product_id_parent));
                        self::$product_categories[$product_id_parent] = $terms;
                    }

                    if($terms){
                        $has = 0;
                        foreach ($terms as $term) {
                            if(in_array($term, $category)){
                                $has = 1;
                            }
                        }
                        if($has){
                            $quantity = $quantity + $cartItem['quantity'];
                        }
                    }
                }
            }
            return $quantity;
        }

        /**
         * Get quantity of products in specific attribute
         * */
        public function getProductQuantityInThisAttribute($attribute, $product_to_exclude, $exclude_sale_items, $rule_id){
            global $woocommerce;
            $hasExcludeProduct = $quantity = $alreadyExists = 0;
            if(!empty($product_to_exclude) && is_array($product_to_exclude) && count($product_to_exclude)) $hasExcludeProduct = 1;
            if(count($woocommerce->cart->cart_contents)){
                foreach ($woocommerce->cart->cart_contents as $cartItem) {
                    $is_exclude_sale_items = $this->isItemInSaleItems($exclude_sale_items, $cartItem['data']);
                    if($is_exclude_sale_items){
                        continue;
                    }
                    $product_id = FlycartWoocommerceProduct::get_id($cartItem['data']);
                    if($hasExcludeProduct){
                        if(in_array($product_id, $product_to_exclude)){
                            continue;
                        }
                    }
                    if(isset(self::$product_attributes[$rule_id])){
                        if(isset(self::$product_attributes[$rule_id][$product_id])){
                            $alreadyExists = 1;
                            $hasAttribute = self::$product_attributes[$rule_id][$product_id];
                        }
                    }
                    if(!$alreadyExists){
                        $hasAttribute = $this->isItemInAttributeList($attribute, $cartItem, $rule_id);
                        self::$product_attributes[$rule_id][$product_id] = $hasAttribute;
                    }

                    if($hasAttribute){
                        $quantity = $quantity + $cartItem['quantity'];
                    }
                }
            }
            return $quantity;
        }

        /**
         * Get quantity of products from all products
         * */
        public function getProductQuantityForCumulativeSpecificProducts($item, $product_page, $rules, $specific_products){
            global $woocommerce;
            $quantity = 0;
            if($product_page) $quantity++;
            if(count($woocommerce->cart->cart_contents)){
                foreach ($woocommerce->cart->cart_contents as $cartItem) {
                    $product_id = $cartItem['product_id'];
                    if(isset($cartItem['variation_id']) && $cartItem['variation_id']){
                        $product_id = $cartItem['variation_id'];
                    }
                    if(in_array($product_id, $specific_products)){
                        $quantity = $quantity + $cartItem['quantity'];
                    }

                }
            }
            return $quantity;
        }

        /**
         * Get quantity of products from all products
         * */
        public function getProductQuantityForCumulativeProducts($item, $product_page, $rules){
            $product_to_exclude = $rules['product_to_exclude'];
            $exclude_sale_items = isset($rules['exclude_sale_items']) ? $rules['exclude_sale_items']: 0;
            global $woocommerce;
            $hasExcludeProduct = $quantity = 0;
            if($product_page) $quantity++;
            if(!empty($product_to_exclude) && is_array($product_to_exclude) && count($product_to_exclude)) $hasExcludeProduct = 1;
            if(count($woocommerce->cart->cart_contents)){
                foreach ($woocommerce->cart->cart_contents as $cartItem) {
                    $is_exclude_sale_items = $this->isItemInSaleItems($exclude_sale_items, $cartItem['data']);
                    if($is_exclude_sale_items){
                        continue;
                    }
                    if($hasExcludeProduct){
                        $product_id = $cartItem['product_id'];
                        if(isset($cartItem['variation_id']) && $cartItem['variation_id']){
                            $product_id = $cartItem['variation_id'];
                        }
                        if(in_array($product_id, $product_to_exclude)){
                            continue;
                        }
                    }
                    $quantity = $quantity + $cartItem['quantity'];
                }
            }
            return $quantity;
        }

        /**
         * Return the First index.
         *
         * @param $array
         * @return mixed
         */
        public function array_first($array)
        {
            if (is_object($array)) $array = (array)$array;
            if (is_array($array)) return $array;
            foreach ($array as $first) {
                return $first;
            }
        }

        /**
         * Return the Adjustment amount.
         *
         * @param $quantity
         * @param $discount_ranges
         * @param $product_page
         * @return array|bool
         */
        public function getAdjustmentAmount($item, $quantity, $discount_ranges, $product_page, $bogo, $product_to_exclude = array())
        {
            $adjustment = array();
            foreach($discount_ranges as $discount_range) {
                if (!is_array($discount_range) && !is_object($discount_range)) return false;
                $range = is_array($discount_range) ? (object) $discount_range : $discount_range;
                $min = (isset($range->min_qty) ? $range->min_qty : 0);
                $max = (isset($range->max_qty) ? $range->max_qty : false);
                if($max == 0 || $max == '' || $max == false) $max = 999;

                $type = (isset($range->discount_type) ? $range->discount_type : 'price_discount');

                if ($max == false) continue;

                if ((int)$min <= (int)$quantity && (int)$max >= (int)$quantity) {
                    if($type == 'product_discount'){
                        $discount_product_option = isset($range->discount_product_option) ? $range->discount_product_option : 'all';
                        $productIds = isset($range->discount_product) ? $range->discount_product : array();
                        $productIds = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($productIds);
                        if($discount_product_option == 'same_product'){
                            $productId = FlycartWoocommerceProduct::get_id($item['data']);
                            $discount_quantity = isset($range->discount_bogo_qty)? $range->discount_bogo_qty: 1000;
                            $productCheapest = $this->getCheapestProductFromCart(array($productId), 0, $discount_quantity, $range, $product_to_exclude);
                            if(!empty($productCheapest)){
                                $adjustment = array ( 'price_discount' => $productCheapest['percent'], 'product_ids' => array($productCheapest['product']), 'product_cart_item_keys' => array($productCheapest['product_cart_item_key']), 'product_discount_details' => $productCheapest['discount_details']) ;
                            }
                        } else if($discount_product_option == 'any_cheapest_from_all'){
                            $productCheapest = $this->getCheapestProductFromCart($productIds, 1, 1, $range, $product_to_exclude);
                            if(!empty($productCheapest)){
                                $adjustment = array ( 'price_discount' => $productCheapest['percent'], 'product_ids' => array($productCheapest['product']), 'product_cart_item_keys' => array($productCheapest['product_cart_item_key']) ) ;
                            }
                        } else if($discount_product_option == 'any_cheapest'){
                            $productCheapest = $this->getCheapestProductFromCart($productIds,0, 1, $range, $product_to_exclude);
                            if(!empty($productCheapest)){
                                $adjustment = array ( 'price_discount' => $productCheapest['percent'], 'product_ids' => array($productCheapest['product']), 'product_cart_item_keys' => array($productCheapest['product_cart_item_key']) ) ;
                            }
                        } else if($discount_product_option == 'more_than_one_cheapest' || $discount_product_option == 'more_than_one_cheapest_from_all'|| $discount_product_option == 'more_than_one_cheapest_from_cat'){
                            $discount_product_items = (isset($range->discount_product_items) ? $range->discount_product_items : 1);
                            if($discount_product_items < 1) $discount_product_items = 1;
                            $discount_product_qty = (isset($range->discount_product_qty) ? $range->discount_product_qty : 1);
                            if($discount_product_qty < 1) $discount_product_qty = 1;
                            $productCheapest = $this->getMoreThanOneCheapestProductFromCart($productIds, $discount_product_qty, $discount_product_items, $range, $product_to_exclude);
                            if(!empty($productCheapest)){
                                $adjustment = $productCheapest ;
                            }
                        } else {
                            //To handle BOGO
                            if(!empty($productIds)) {
                                $bogo_count = isset($range->discount_bogo_qty)? (int)$range->discount_bogo_qty: 1;
                                if($bogo_count < 1) $bogo_count = 1;
                                if($bogo){
                                    $free_product = $this->bogo_matches;
                                    foreach ($productIds as $productId){

                                        if(isset($free_product[$productId])){
                                            $free_product[$productId]['count'] = $free_product[$productId]['count']+$bogo_count;
                                        } else {
                                            $free_product[$productId]['count'] = $bogo_count;
                                            $free_product[$productId]['rule_name'] = $range->title;
                                        }
                                    }
                                    $this->bogo_matches = $free_product;
                                    $adjustment[$type] = $productIds;
                                } else {
                                    $productCheapest = $this->getMoreThanOneCheapestProductFromCart($productIds, $bogo_count, count($productIds), $range, $product_to_exclude);
                                    if (!empty($productCheapest)) {
                                        $adjustment = $productCheapest;
                                    }
                                }
                            }

                        }
                    } else {
                        $adjustment[$type] = (isset($range->to_discount) ? $range->to_discount : 0);
                        $product_discount_details = array();
                        if($type == 'percentage_discount'){
                            $product_discount_details['discount_type'] = 'percent';
                            $productPrice = FlycartWoocommerceProduct::get_price($item['data']);
                            $discount_price = $productPrice * ($range->to_discount / 100);
                        } else {
                            $product_discount_details['discount_type'] = 'price_discount';
                            $discount_price = $range->to_discount;
                        }
                        $product_discount_details['discount_value'] = $range->to_discount;
                        $product_discount_details['discount_quantity'] = $quantity;
                        $product_discount_details['discount_price'] = $discount_price;
                        $adjustment['product_discount_details'] = $product_discount_details;
                    }
                }
            }
            return $adjustment;
        }

        /**
         * Get More than one cheapest item
         * */
        public function getMoreThanOneCheapestProductFromCart($productIds, $discount_quantity = 1, $discount_item = 1, $range, $product_to_exclude = array()){
            $discount_product_option = isset($range->discount_product_option) ? $range->discount_product_option : 'more_than_one_cheapest';
            $discount_product_item_type = isset($range->discount_product_item_type) ? $range->discount_product_item_type : 'static';
            if($discount_product_item_type == 'dynamic'){
                $cart_item_details = $this->getTotalQuantitiesAndItems();
                if(!empty($cart_item_details['total_items'])) $discount_item = $cart_item_details['total_items'];
            }
            $adjustment = array();
            $adjustmentValues = array();
            if($discount_product_option == "more_than_one_cheapest_from_all")
                $productIds = $this->getAllProductsFromCart();
            else if($discount_product_option == "more_than_one_cheapest_from_cat")
                $productIds = $this->getAllProductsFromCartAndSelectedCategory($range->discount_category);
            for ($i = 1; $i <= $discount_item; $i++){
                $productCheapest = $this->getCheapestProductFromCart($productIds, 0, $discount_quantity, $range, $product_to_exclude);
                if(!empty($productCheapest)){
                    $index = array_search($productCheapest['product'], $productIds);
                    if ( $index !== false ) {
                        unset( $productIds[$index] );
                    }
                    $adjustment['price_discount'] = $productCheapest['percent'];
                    $adjustment['product_ids'][] = $productCheapest['product'];
                    $adjustment['product_cart_item_keys'][] = $productCheapest['product_cart_item_key'];
                    $adjustmentValues[$productCheapest['product']] = $productCheapest['percent'];
                    $adjustment['product_discount_adjustment'] = $adjustmentValues;
                    $adjustment['product_discount_details'][$productCheapest['product']] = $productCheapest['discount_details'];
                    if($discount_product_item_type == 'dynamic'){
                        $applied_quantity = $productCheapest['applied_quantity'];
                        if($applied_quantity) $discount_quantity -= $applied_quantity;
                        if($discount_quantity <= 0) break;
                    }
                } else {
                    break;
                }
            }
            return $adjustment;
        }

        /**
         * Get total quantities and items from Cart
         * */
        protected function getTotalQuantitiesAndItems(){
            $cart_item_details = array();
            $cart_item_details['total_items'] = $cart_item_details['total_quantities'] = 0;
            $cart = FlycartWoocommerceCart::get_cart();
            foreach ( $cart as $cart_item ) {
                $cart_item_details['total_items']++;
                $cart_item_details['total_quantities'] += $cart_item['quantity'];
            }

            return $cart_item_details;
        }

        /**
         * Get all Products from Cart
         * */
        protected function getAllProductsFromCart(){
            $products = array();
            $cart = FlycartWoocommerceCart::get_cart();
            foreach ( $cart as $cart_item ) {
                if($cart_item['variation_id'] && $cart_item['variation_id'])
                    $products[] = $cart_item['variation_id'];
                else
                    $products[] = $cart_item['product_id'];
            }
            return $products;
        }

        /**
         * Get Products from Cart (selected category)
         * */
        protected function getAllProductsFromCartAndSelectedCategory($category){
            $products = array();
            if(!empty($category) && is_array($category)){
                $cart = FlycartWoocommerceCart::get_cart();
                foreach ( $cart as $cart_item ) {
                    $result = $this->isItemInCategoryList($category, $cart_item);
                    if($result){
                        if(isset($cart_item['variation_id']) && $cart_item['variation_id']){
                            $products[] = $cart_item['variation_id'];
                        } else {
                            $products[] = $cart_item['product_id'];
                        }
                    }
                }
            }

            return $products;
        }

        /**
         * Get cheapest product
         * */
        public function getCheapestProductFromCart($products, $all = 0, $discount_quantity = 1, $range, $product_to_exclude = array()){
            if(!$all){
                if(empty($products)) return array();
            }
            $donot_apply_for_free_product = apply_filters('woo_discount_rules_do_not_apply_discount_for_free_product', true);
            if($donot_apply_for_free_product){
                $check_cheapestProductValue = $cheapestProductValue = 0;
            } else {
                $check_cheapestProductValue = $cheapestProductValue = -1;
            }
            $cart = FlycartWoocommerceCart::get_cart();
            foreach ($cart as $cart_item_key => $values) {
                $_product = $values['data'];
                $productId = FlycartWoocommerceProduct::get_id($_product);
                if(!empty($product_to_exclude) && is_array($product_to_exclude)){
                    if(in_array($productId, $product_to_exclude)) continue;
                }
                if(!in_array($productId, $products) && !$all) continue;
                $skip_free_product = apply_filters('woo_discount_rules_skip_discount_for_free_product', false, $values);
                if($skip_free_product){
                    $reduce_quantity = apply_filters('woo_discount_rules_reduce_qty_skip_discount_for_free_product', false, $values);
                    if($reduce_quantity){
                        $discount_quantity -= (int)$reduce_quantity;
                    }
                    continue;
                }

                if($cheapestProductValue == $check_cheapestProductValue){
                    $cheapestProductValue = FlycartWoocommerceProduct::get_price($_product);
                    $cheapestProduct = FlycartWoocommerceProduct::get_id($_product);
                    $cheapestProductCartItemKey = $cart_item_key;
                    $quantity = $values['quantity'];
                } else if($cheapestProductValue > FlycartWoocommerceProduct::get_price($_product)){
                    $cheapestProductValue = FlycartWoocommerceProduct::get_price($_product);
                    $cheapestProduct = FlycartWoocommerceProduct::get_id($_product);
                    $cheapestProductCartItemKey = $cart_item_key;
                    $quantity = $values['quantity'];
                }
            }
            $product_discount_details = array();
            $product_discount_details['discount_type'] = 'percent';
            $product_discount_details['discount_value'] = 100;
            $product_discount_details['discount_quantity'] = $discount_quantity;
            $product_discount_details['discount_price'] = $cheapestProductValue;
            if($cheapestProductValue > 0){
                if(isset($range->discount_product_discount_type) && $range->discount_product_discount_type == "limited_percent"){
                    if(isset($range->discount_product_percent) && $range->discount_product_percent > 0){
                        $cheapestProductValue = $cheapestProductValue * ($range->discount_product_percent / 100);
                    }
                    $product_discount_details['discount_price'] = $cheapestProductValue;
                }

                //discount_price = (original_price - ((original_price / (buy_qty + free_qty))*buy_qty))
                if($discount_quantity > $quantity)
                    $discount_price = $cheapestProductValue - (($cheapestProductValue/($quantity)) * ($quantity-$quantity));
                else
                    $discount_price = $cheapestProductValue - (($cheapestProductValue/($quantity)) * ($quantity-$discount_quantity));
                return array('product' => $cheapestProduct, 'product_cart_item_key' => $cheapestProductCartItemKey, 'percent' => $discount_price, 'discount_details' => $product_discount_details, 'applied_quantity' => $quantity);
            }
            return array();
        }

        /**
         * Validating the Active user with rule sets.
         *
         * @param $rule
         * @return string
         */
        public function manageUserAccess($rule)
        {
            $allowed = 'no';
            if (!isset($rule->users_to_apply)) return $allowed;

            $users = $rule->users_to_apply;

            if (is_string($users)) $users = json_decode($users, true);

            $users = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($users);

            if (!is_array($users)) return $allowed;

            $user = get_current_user_id();

            if (count(array_intersect($users, array($user))) > 0) {
                $allowed = 'yes';
            }

            return $allowed;
        }

        /**
         * To Check active cart items are in the rules list item.
         *
         * @param $product_list
         * @param $product
         * @return bool
         */
        public function isItemInProductList($product_list, $product)
        {
            if (!isset($product['product_id'])) return false;
            $product_ids = array($product['product_id']);
            if(!empty($product['variation_id'])) $product_ids[] = $product['variation_id'];
            if (!is_array($product_list)) $product_list = (array)$product_list;
            if (count(array_intersect($product_list, $product_ids)) >= 1) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * To Check in sale items.
         *
         * @param int $rule_exclude_sale_items
         * @param $product
         * @return bool
         */
        public function isItemInSaleItems($rule_exclude_sale_items, $product)
        {
            if (!$rule_exclude_sale_items) return false;
            $sale_price = FlycartWoocommerceProduct::get_sale_price($product);
            if($sale_price > 0) return true;

            return false;
        }

        /**
         * To Check that the items are in specified category.
         *
         * @param $category_list
         * @param $product
         * @return bool
         */
        public function isItemInCategoryList($category_list, $product)
        {
            if (!isset($product['product_id'])) return false;
            $product_category = FlycartWooDiscountRulesGeneralHelper::getCategoryByPost($product);
            $status = false;
            if(!empty($category_list) && !empty($product_category)){
                //check any one of category matches
                if(is_array($category_list))
                    $matching_cats = array_intersect($product_category, $category_list);
                else if(is_string($category_list))
                    $matching_cats = in_array($category_list, $product_category)? array($category_list): array();
                else
                    $matching_cats = array();

                $result = !empty( $matching_cats );
                if($result){
                    $status = true;
                }
            }

            return $status;
        }

        /**
         * To Check that the items are in specified attribute.
         *
         * @param $attribute_list
         * @param $product
         * @return bool
         */
        public function isItemInAttributeList($attribute_list, $product, $rule_order_id = 0)
        {
            $parent_product = FlycartWoocommerceProduct::wc_get_product($product['product_id']);
            $parent_id = FlycartWoocommerceProduct::get_parent_id($parent_product);
            if($parent_id){
                $parent_product = FlycartWoocommerceProduct::wc_get_product($parent_id);
            }
            $status = $this->hasAttributeInParentProduct($parent_product, $attribute_list, apply_filters('woo_discount_rules_price_rule_check_in_all_selected_attributes', false, $rule_order_id));

            if($status) return true;

            if (!isset($product['variation_id']) || !$product['variation_id'] ) return false;
            if (!isset($product['variation']) || empty($product['variation']) ) return false;
            if(empty($attribute_list)) return false;
            $status = FlycartWooDiscountRulesAdvancedHelper::validateCartItemInSelectedAttributes($product['variation'], $attribute_list, apply_filters('woo_discount_rules_price_rule_check_in_all_selected_attributes', false, $rule_order_id));

            return $status;
        }

        /**
         * Check default attributes for products without variants
         *
         * @param object $product
         * @param array $attribute_list
         * @param boolean $all_attr
         * @return boolean
         * */
        protected function hasAttributeInParentProduct($product, $attribute_list, $all_attr = false){
            $available_attributes = array();
            $status = false;
            if(FlycartWoocommerceVersion::wcVersion('3.0')){
                $attributes_parent = $product->get_attributes();
            } else {
                $attributes_parent = $product->product_attributes;
            }
            if(!empty($attributes_parent) && is_array($attributes_parent)){
                foreach ($attributes_parent as $attributes){
                    if(FlycartWoocommerceVersion::wcVersion('3.0')){
                        if(!empty($attributes) && is_object($attributes)){
                            $variation = $attributes->get_variation();
                            if(!(int)$variation){
                                $options = $attributes->get_options();

                                if(!empty($options) && is_array($options)){
                                    $available_attributes = array_merge($available_attributes, $options);
                                }
                            }
                        }
                    } else {
                        if(!empty($attributes)){
                            $variation = $attributes['is_variation'];
                            if(!(int)$variation){
                                $attribute_terms = get_the_terms($product->id, $attributes['name']);
                                if(!empty($attribute_terms)){
                                    $options = array();
                                    foreach ($attribute_terms as $attribute_term){
                                        $options[] = $attribute_term->term_id;
                                    }
                                    if(!empty($options) && is_array($options)){
                                        $available_attributes = array_merge($available_attributes, $options);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(!empty($available_attributes)){
                if($all_attr){
                    foreach ($attribute_list as $attribute_list_item){
                        if(!in_array($attribute_list_item, $available_attributes)){
                            $status = false;
                            break;
                        }
                        $status = true;
                    }
                } else {
                    foreach ($attribute_list as $attribute_list_item){
                        if(in_array($attribute_list_item, $available_attributes)){
                            $status = true;
                            break;
                        }
                    }
                }
            }

            return $status;
        }

        /**
         * Sort cart by price
         *
         * @access public
         * @param array $cart
         * @param string $order
         * @return array
         */
        public function sortCartPrice($cart, $order)
        {
            $cart_sorted = array();

            foreach ($cart as $cart_item_key => $cart_item) {
                $cart_sorted[$cart_item_key] = $cart_item;
            }

            uasort($cart_sorted, array($this, 'sortCartByPrice_' . $order));

            return $cart_sorted;
        }

        /**
         * Sort cart by price uasort collable - ascending
         *
         * @access public
         * @param mixed $first
         * @param mixed $second
         * @return bool
         */
        public function sortCartByPrice_asc($first, $second)
        {
            if (isset($first['data'])) {
                if (FlycartWoocommerceProduct::get_price($first['data']) == FlycartWoocommerceProduct::get_price($second['data'])) {
                    return 0;
                }
            }
            return (FlycartWoocommerceProduct::get_price($first['data']) < FlycartWoocommerceProduct::get_price($second['data'])) ? -1 : 1;
        }

        /**
         * Sort cart by price uasort collable - descending
         *
         * @access public
         * @param mixed $first
         * @param mixed $second
         * @return bool
         */
        public function sortCartByPrice_desc($first, $second)
        {
            if (isset($first['data'])) {
                if (FlycartWoocommerceProduct::get_price($first['data']) == FlycartWoocommerceProduct::get_price($second['data'])) {
                    return 0;
                }
            }
            return (FlycartWoocommerceProduct::get_price($first['data']) > FlycartWoocommerceProduct::get_price($second['data'])) ? -1 : 1;
        }

        /**
         * Return the List of Products to Apply.
         *
         * @param $woocommerce
         * @param $rule
         * @return array
         */
        public function checkWithProducts($rule, $woocommerce)
        {
            $specific_product_list = array();
            if (is_string($rule->product_to_apply)) {
                $specific_product_list = json_decode($rule->product_to_apply, true);
                $specific_product_list = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($specific_product_list);
            }
            return $specific_product_list;
        }

        /**
         * Check with category list.
         *
         * @param $rule
         * @param $woocommerce
         * @return array|mixed
         */
        public function checkWithCategory($rule, $woocommerce)
        {
            $specific_category_list = array();
            if (is_string($rule->category_to_apply)) {
                $specific_category_list = json_decode($rule->category_to_apply, true);
            }
            return $specific_category_list;
        }

        /**
         * Check with attribute list.
         *
         * @param $rule
         * @param $woocommerce
         * @return array|mixed
         */
        public function getAttributeFromRule($rule, $woocommerce)
        {
            $specific_attribute_list = array();
            if (is_string($rule->attribute_to_apply)) {
                $specific_attribute_list = json_decode($rule->attribute_to_apply, true);
            }
            return $specific_attribute_list;
        }

        /**
         * Check with User list.
         *
         * @param $rule
         * @param $woocommerce
         * @return array|mixed
         */
        public function checkWithUsers($rule, $woocommerce)
        {
            // Return as , User is allowed to use this discount or not.
            // Working Users.
            return $this->manageUserAccess($rule);
        }

        /**
         * To Return the Discount Ranges.
         *
         * @param $rule
         * @return array|mixed
         */
        public function getDiscountRangeList($rule)
        {
            $discount_range_list = array();
            if (is_string($rule->discount_range)) {
                $discount_range_list = json_decode($rule->discount_range);
            }
            return $discount_range_list;
        }

        /**
         * For Display the price discount of a product.
         */
        public function priceTable()
        {
            global $product;
            if(!empty($product)){
                if($product->is_type(array('variable', 'subscription_variation', 'variable-subscription'))){
                    $product_id = FlycartWoocommerceProduct::get_id($product);
                    echo '<div class="woo_discount_rules_variant_table" data-id="'.$product_id.'"></div>';
                } else {
                    $this->loadPriceTable($product);
                }
            }
        }

        public function getWooDiscountedPriceTableForVariant(){
            $data = array('cookie' => 0, 'html' => '', 'time' => '');
            if (isset($_REQUEST['id']) && $_REQUEST['id']) {
                $html = '';
                $cookie_set_time = '';
                if(isset($_REQUEST['time']) && !empty($_REQUEST['time'])){
                    $cookie_set_time = $_REQUEST['time'];
                }
                $loadFromCookie = $this->loadPriceTableFromCookie($cookie_set_time);
                if(!$loadFromCookie){
                    $product = FlycartWoocommerceProduct::wc_get_product($_REQUEST['id']);
                    ob_start();
                    $this->loadPriceTable($product);
                    $html = ob_get_contents();
                    ob_clean();
                    ob_get_clean();
                }

                $data['cookie'] = $this->loadPriceTableFromCookie($cookie_set_time);
                $data['html'] = $html;
                $now = new DateTime("now", new DateTimeZone('UTC'));
                $data['time'] = $now->getTimestamp();
                echo json_encode($data);
                exit;
            }
            echo json_encode($data);exit;
        }

        public function loadPriceTable($product)
        {
            $config = $this->baseConfig;
            $show_discount = true;
            // Base Config to Check whether display table or not.
            if (isset($config['show_discount_table'])) {
                if ($config['show_discount_table'] == 'show') {
                    $show_discount = true;
                } else {
                    $show_discount = false;
                }
            }
            // If Only allowed to display, then only its display the table.
            if ($show_discount) {
                $table_data = $this->generateDiscountTableData($product);
                $path_from_template = $this->getTemplateOverride('discount-table.php');
                $path = WOO_DISCOUNT_DIR . '/view/template/discount-table.php';
                if($path_from_template){
                    $path = $path_from_template;
                }
                $this->generateTableHtml($table_data, $path, $product);
            }
        }

        /**
         * Get template override
         * @param string $template_name
         * @return string
         * */
        public function getTemplateOverride($template_name){
            $template = locate_template(
                array(
                    trailingslashit( dirname(WOO_DISCOUNT_PLUGIN_BASENAME) ) . $template_name,
                    $template_name,
                )
            );

            return $template;
        }

        /**
         * To generate the Discount table data.
         *
         * @param $product
         * @return array|bool|string
         */
        public function generateDiscountTableData($product)
        {
            if(empty($product)){
                global $product;
            }
            if(empty($product)) return false;
            $product_id = FlycartWoocommerceProduct::get_id($product);
            $id = (($product_id != 0 && $product_id != null) ? $product_id : 0);
            if ($id == 0) return false;

            $this->organizeRules();

            $discount_range = array();
            if(is_array($this->rules) && count($this->rules) > 0) {
                foreach ($this->rules as $index => $rule) {
                    $status = false;
                    if(isset($rule->rule_method) && $rule->rule_method == 'qty_based'){
                        // Check with Active User Filter.
                        if (isset($rule->customer)) {
                            $status = false;
                            if ($rule->customer == 'all') {
                                $status = true;
                            } else {
                                $users = (is_string($rule->users_to_apply) ? json_decode($rule->users_to_apply, true) : array());
                                $users = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($users);
                                if(empty($users)) $users = array();
                                $user_id = get_current_user_id();
                                if (count(array_intersect($users, array($user_id))) > 0) {
                                    $status = true;
                                }
                            }
                        }
                        $status = apply_filters('woo_discount_rules_rule_matches_to_display_in_table', $status, $product, $rule);
                        if($status){
                            if ($rule->apply_to == 'specific_products') {

                                // Check with Product Filter.
                                $products_to_apply = json_decode($rule->product_to_apply);
                                $products_to_apply = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($products_to_apply);

                                if ($rule->product_to_apply == null) $status = true;

                                if ($rule->product_to_apply != null) {
                                    $status = false;
                                    if (in_array($id, $products_to_apply)) {
                                        $status = true;
                                    }
                                    $variations = FlycartWoocommerceProduct::get_variant_ids($product_id);
                                    if(!empty($variations)){
                                        if (count(array_intersect($variations, $products_to_apply)) > 0) {
                                            $status = true;
                                        }
                                    }
                                }
                            } elseif ($rule->apply_to == 'specific_category') {
                                // Check with Product Category Filter.
                                $category = FlycartWooDiscountRulesGeneralHelper::getCategoryByPost($id, true);

                                if ($rule->category_to_apply == null) $status = true;

                                if ($rule->category_to_apply != null) {
                                    $productToExclude = $this->getExcludeProductsFromRule($rule);
                                    $category_to_apply = json_decode($rule->category_to_apply);
                                    if (isset($rule->apply_child_categories) && $rule->apply_child_categories == 1) {
                                        $category_to_apply = FlycartWooDiscountRulesGeneralHelper::getAllSubCategories($category_to_apply);
                                    }
                                    FlycartWooDiscountRulesGeneralHelper::toInt($category_to_apply);
                                    $status = false;
                                    if(!in_array($id, $productToExclude))
                                        if (count(array_intersect($category_to_apply, $category)) > 0) {
                                            $status = true;
                                        }
                                }
                            } elseif ($rule->apply_to == 'specific_attribute') {
                                $status = false;
                                if(!empty($rule->attribute_to_apply)){
                                    $status = $this->checkProductMatchedForSpecificAttributes($rule->attribute_to_apply, $product, $index);
                                }

                                //false;
                            } else if ($rule->apply_to == 'all_products') {
                                $productToExclude = $this->getExcludeProductsFromRule($rule);
                                $status = false;
                                if(!in_array($id, $productToExclude))
                                    $status = true;
                            }

                            // check for user roles
                            if(isset($rule->user_roles_to_apply)){
                                $statusRoles = $this->checkWithUserRoles($rule);
                                if($statusRoles === false){
                                    $status = false;
                                }
                            }

//                            // check for subtotal
//                            if(isset($rule->subtotal_to_apply_option)){
//                                $is_woocommerce3 = FlycartWoocommerceVersion::isWCVersion3x();
//                                if($is_woocommerce3){
//                                    $subtotalStatus = $this->checkSubtotalMatches($rule);
//                                    if(!$subtotalStatus){
//                                        $status = false;
//                                    }
//                                }
//                            }
//
//                            // check for COUPON
//                            if(isset($rule->coupons_to_apply_option)){
//                                $statusCoupon = $this->checkWithCouponApplied($rule);
//                                if(!$statusCoupon){
//                                    $status = false;
//                                }
//                            }
                        }
                        $status = apply_filters('woo_discount_rules_rule_matches_to_display_in_table', $status, $product, $rule);
                        if ($status) {
                            $discount_range_data = (isset($rule->discount_range) ? json_decode($rule->discount_range) : array());
                            if(!empty($discount_range_data)){
                                foreach ($discount_range_data as $discount_range_each){
                                    if(isset($discount_range_each->title)) $discount_range_each->title = $rule->rule_name;
                                }
                            }
                            $discount_range[] = $discount_range_data;//(isset($rule->discount_range) ? json_decode($rule->discount_range) : array());
                        }
                    } else if(isset($rule->rule_method) && $rule->rule_method == 'product_based'){
                        $product_based_conditions = json_decode((isset($rule->product_based_condition) ? $rule->product_based_condition : '{}'), true);
                        $product_to_buy = isset($product_based_conditions['product_to_buy']) ? $product_based_conditions['product_to_buy'] : array();
                        $product_to_buy = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($product_to_buy);
                        $product_to_apply = isset($product_based_conditions['product_to_apply']) ? $product_based_conditions['product_to_apply'] : array();
                        $product_to_apply = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($product_to_apply);
                        if (in_array($id, $product_to_buy) || in_array($id, $product_to_apply)) {
                            $product_based_discounts = json_decode((isset($rule->product_based_discount) ? $rule->product_based_discount : '{}'), true);
                            $product_based_discount_type = isset($product_based_discounts['discount_type']) ? $product_based_discounts['discount_type'] : 'percentage_discount';
                            $product_based_discount_value = isset($product_based_discounts['discount_value']) ? $product_based_discounts['discount_value'] : '';
                            $newTableContent = new stdClass();
                            $newTableContent->rule_method = $rule->rule_method;
                            $newTableContent->discount_type = $product_based_discount_type;
                            $newTableContent->to_discount = $product_based_discount_value;
                            $newTableContent->title = $rule->rule_name;
                            $condition = $this->getTextForProductDiscountCondition($rule);
                            $newTableContent->condition = $condition;
                            $discount_range[][] = $newTableContent;
                        }
                    }

                }
            }

            return $discount_range;
        }

        protected function checkProductMatchedForSpecificAttributes($attribute_to_apply, $product, $rule_order_id){
            $status = 0;
            $attribute_to_apply = json_decode($attribute_to_apply);
            $attribute_to_apply = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($attribute_to_apply);
            if(!empty($attribute_to_apply)){
                $product_id = FlycartWoocommerceProduct::get_id($product);
                $item['product_id'] = $product_id;
                $item['data'] = $product;
                $status = $this->isItemInAttributeList($attribute_to_apply, $item, $rule_order_id);
                if($status){
                    return true;
                }
                if($product->is_type(array('variable', 'subscription_variation', 'variable-subscription'))){
                    $childProducts = array();
                    if(FlycartWoocommerceVersion::wcVersion('3.1.0')){
                        $children         = array_filter( array_map( 'wc_get_product', FlycartWoocommerceProduct::get_children($product) ), 'wc_products_array_filter_visible_grouped' );
                        foreach ( $children as $child ) {
                            if ( '' !== FlycartWoocommerceProduct::get_price($child) ) {
                                $childProducts[] = FlycartWoocommerceProduct::get_id($child);
                            }
                        }
                    } else {
                        $childProducts = $product->get_children();
                    }
                    if(!empty($childProducts)){
                        foreach ($childProducts as $childProductId){
                            $product = FlycartWoocommerceProduct::wc_get_product($childProductId);
                            $item['product_id'] = $childProductId;
                            $item['data'] = $product;
                            // To display the strike out price in product page for variant (specific attribute rule)
                            if($product->get_type() == 'variation'){
                                if(FlycartWoocommerceVersion::wcVersion('3.1.0')){
                                    $p_data = $product->get_data();
                                    if(!empty($p_data['attributes'])){
                                        $attr = array();
                                        foreach ($p_data['attributes'] as $key => $value){
                                            $attr['attribute_'.$key] = $value;
                                        }
                                        $item['variation'] = $attr;
                                        $item['variation_id'] = $product_id;
                                    }
                                } else {
                                    $item['variation'] = $product->get_variation_attributes();
                                    $item['variation_id'] = $product_id;
                                }
                            }
                            $status = $this->isItemInAttributeList($attribute_to_apply, $item, $rule_order_id);
                            if($status){
                                return true;
                            }
                        }
                    }
                }
            }

            return $status;
        }

        /**
         * To get purchased product from rule
         * */
        public function getPurchasedProductsFromRule($rule){
            $purchasedProduct = array();
            if(!isset($rule->purchase_history_products)) return $purchasedProduct;
            if(is_array($rule->purchase_history_products)) $purchasedProduct = $rule->purchase_history_products;
            else if(is_string($rule->purchase_history_products)){
                $purchasedProduct = json_decode($rule->purchase_history_products);
                $purchasedProduct = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($purchasedProduct);
            }
            if(!is_array($purchasedProduct)){
                $purchasedProduct = array();
            }
            return $purchasedProduct;
        }

        /**
         * To get product to exclude
         * */
        public function getExcludeProductsFromRule($rule){
            $productToExclude = array();
            if(is_array($rule->product_to_exclude)) $productToExclude = $rule->product_to_exclude;
            else if(is_string($rule->product_to_exclude)){
                $productToExclude = json_decode($rule->product_to_exclude);
                $productToExclude = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($productToExclude);
            }
            if(!is_array($productToExclude)){
                $productToExclude = array();
            }
            return $productToExclude;
        }

        public function getTextForProductDiscountCondition($rule){
            $product_based_conditions = json_decode((isset($rule->product_based_condition) ? $rule->product_based_condition : '{}'), true);
            $product_buy_type = isset($product_based_conditions['product_buy_type']) ? $product_based_conditions['product_buy_type'] : 'any';
            $product_quantity_rule = isset($product_based_conditions['product_quantity_rule']) ? $product_based_conditions['product_quantity_rule'] : 'more';
            $product_quantity_from = isset($product_based_conditions['product_quantity_from']) ? $product_based_conditions['product_quantity_from'] : '';
            $product_quantity_to = isset($product_based_conditions['product_quantity_to']) ? $product_based_conditions['product_quantity_to'] : '';
            $product_to_buy = isset($product_based_conditions['product_to_buy']) ? $product_based_conditions['product_to_buy'] : array();
            $product_to_buy = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($product_to_buy);
            $product_to_apply = isset($product_based_conditions['product_to_apply']) ? $product_based_conditions['product_to_apply'] : array();
            $category_to_apply = isset($product_based_conditions['category_to_apply']) ? $product_based_conditions['category_to_apply'] : array();
            $get_discount_type = isset($product_based_conditions['get_discount_type']) ? $product_based_conditions['get_discount_type'] : 'product';
            $product_to_apply = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($product_to_apply);
            $product_to_apply_count_option = isset($product_based_conditions['product_to_apply_count_option']) ? $product_based_conditions['product_to_apply_count_option'] : 'all';
            $product_to_apply_count = isset($product_based_conditions['product_to_apply_count']) ? $product_based_conditions['product_to_apply_count'] : 0;
            $condition = esc_html__('Buy', 'woo-discount-rules');

            switch ($product_quantity_rule) {
                case 'less':
                    $quantity_text = esc_html__(' less than or equal to ', 'woo-discount-rules').$product_quantity_from.esc_html__(' Quantity', 'woo-discount-rules');
                    break;
                case 'equal':
                    $quantity_text = ' '.$product_quantity_from.esc_html__(' Quantity ', 'woo-discount-rules');
                    break;
                case 'from':
                    $quantity_text = '( '.$product_quantity_from.' - '.$product_quantity_to.' )'.esc_html__(' Quantity', 'woo-discount-rules');
                    break;
                case 'more':
                default:
                    $quantity_text = ' '.$product_quantity_from.esc_html__(' or more Quantity', 'woo-discount-rules');
            }

            switch ($product_buy_type) {
                case 'combine':
                case 'any':
                    if(count($product_to_buy) == 1){
                        $condition .= $quantity_text;
                    } else {
                        $condition .= esc_html__(' any ', 'woo-discount-rules').$quantity_text.esc_html__(' products from ','woo-discount-rules');
                    }
                    break;
                case 'each':
                    if(count($product_to_buy) == 1){
                        $condition .= $quantity_text;
                    } else {
                        $condition .= ' '.$quantity_text.esc_html__(' in each products', 'woo-discount-rules');
                    }
                    break;
            }
            if(count($product_to_buy)){
                $htmlProduct = '';
                foreach ($product_to_buy as $product_id){
                    $product = FlycartWoocommerceProduct::wc_get_product($product_id);
                    $htmlProduct .= '<a href="'.FlycartWoocommerceProduct::get_permalink($product).'">'.FlycartWoocommerceProduct::get_title($product).'</a>, ';
                }
                $condition .= ' '.trim($htmlProduct, ', ').' ';
            }
            $condition .= esc_html__(' and get discount in ', 'woo-discount-rules');
            if($get_discount_type == 'product'){
                if($product_to_apply_count_option == 'apply_first'){
                    $condition .= esc_html__(' first ', 'woo-discount-rules');
                    $condition .= $product_to_apply_count;
                    $condition .= esc_html__(' quantity of product(s) - ', 'woo-discount-rules');
                } else if($product_to_apply_count_option == 'skip_first'){
                    $condition .= esc_html__(' after first ', 'woo-discount-rules');
                    $condition .= $product_to_apply_count;
                    $condition .= esc_html__(' quantity of product(s) - ', 'woo-discount-rules');
                }
                if(count($product_to_apply)){
                    $htmlProduct = '';
                    foreach ($product_to_apply as $product_id){
                        $product = FlycartWoocommerceProduct::wc_get_product($product_id);
                        $htmlProduct .= '<a href="'.FlycartWoocommerceProduct::get_permalink($product).'">'.FlycartWoocommerceProduct::get_title($product).'</a>, ';
                    }
                    $condition .= trim($htmlProduct, ', ');
                }
            } else {
                if(count($category_to_apply)){
                    $htmlCategories = '';
                    foreach ($category_to_apply as $category_id){
                        $htmlCategories .= FlycartWoocommerceProduct::get_product_category_by_id($category_id).', ';
                    }
                    $condition .= esc_html__('Category(ies) ').' '.trim($htmlCategories, ', ');
                }
            }
            
            return $condition;
        }

        /**
         * To Return the HTML table for show available discount ranges.
         *
         * @param $table_data
         * @param $path
         * @return bool|string
         */
        public function generateTableHtml($table_data, $path, $product)
        {
            //ob_start();
            if (!isset($table_data)) return false;
            if (!isset($path) || empty($path) || is_null($path)) return false;
            if (!file_exists($path)) return false;
            $data = $this->getBaseConfig();
            $table_data_content = $this->getDiscountTableContentInHTML($table_data, $data);
            include($path);
            //$html = ob_get_contents();
            // ob_clean();
            //ob_get_clean();
        }

        /**
         * get Discount table content in html
         * */
        private function getDiscountTableContentInHTML($table_data, $data){
            $dataReturn = array();
            $table = $table_data;
            foreach ($table as $index => $item) {
                if ($item) {
                    foreach ($item as $id => $value) {
                        if(isset($value->rule_method) && $value->rule_method == 'product_based'){
                            $title = $value->title;
                            $condition = $value->condition;
                            if ($value->discount_type == 'percentage_discount') {
                                $discount = $value->to_discount.' %';
                            } else {
                                $discount = FlycartWoocommerceProduct::wc_price($value->to_discount);
                            }
                        } else {
                            $title = isset($value->title) ? $value->title : '';
                            $min = isset($value->min_qty) ? $value->min_qty : 0;
                            $max = isset($value->max_qty) ? $value->max_qty : 0;
                            if($max == 0 || $max == '' || $max == false) $max = 999;
                            $discount_type = isset($value->discount_type) ? $value->discount_type : 0;
                            $to_discount = isset($value->to_discount) ? $value->to_discount : 0;
                            $product_discount = isset($value->discount_product) ? $value->discount_product : array();
                            $category_discount = isset($value->discount_category) ? $value->discount_category : array();
                            $discount_product_option = isset($value->discount_product_option) ? $value->discount_product_option : 'all';
                            $discount_product_discount_type = isset($value->discount_product_discount_type) ? $value->discount_product_discount_type : '';
                            $discount_product_percent = isset($value->discount_product_percent) ? $value->discount_product_percent : 0;
                            $discount_product_item_type = isset($value->discount_product_item_type) ? $value->discount_product_item_type : 'static';
                            $discount_product_items = isset($value->discount_product_items) ? $value->discount_product_items : 1;
                            $discount_product_qty = isset($value->discount_product_qty) ? $value->discount_product_qty : 1;
                            $product_discount = FlycartWoocommerceVersion::backwardCompatibilityStringToArray($product_discount);
                            if (isset($base_config['show_discount_title_table'])) {
                            }
                            $condition = $min .' - ' . $max;
                            if ($discount_type == 'product_discount') {
                                $htmlProduct = '';
                                $htmlProduct .= esc_html__('Get ', 'woo-discount-rules');
                                if($discount_product_discount_type == "limited_percent" && $discount_product_percent>0){
                                    $htmlProduct .= $discount_product_percent.esc_html__('% discount in ', 'woo-discount-rules');
                                }
                                if($discount_product_option == 'same_product'){
                                    $htmlProduct .= esc_html__('same product', 'woo-discount-rules');
                                } elseif($discount_product_option == 'any_cheapest_from_all'){
                                    $htmlProduct .= esc_html__('any cheapest one from cart', 'woo-discount-rules');
                                } else {
                                    if($discount_product_option == 'any_cheapest'){
                                        $htmlProduct .= esc_html__('any cheapest one of ', 'woo-discount-rules');
                                    }
                                    if($discount_product_option == 'more_than_one_cheapest' || $discount_product_option == 'more_than_one_cheapest_from_all' || $discount_product_option == 'more_than_one_cheapest_from_cat'){
                                        if($discount_product_item_type == 'static'){
                                            $htmlProduct .= $discount_product_qty;
                                            $htmlProduct .= esc_html__(' quantity of any ', 'woo-discount-rules');
                                            $htmlProduct .= $discount_product_items;
                                            $htmlProduct .= esc_html__(' cheapest item ', 'woo-discount-rules');
                                        } else {
                                            $htmlProduct .= $discount_product_qty;
                                            $htmlProduct .= esc_html__(' quantity of cheapest item ', 'woo-discount-rules');
                                        }
                                    }
                                    if($discount_product_option == 'more_than_one_cheapest_from_cat'){
                                        $htmlProduct .= esc_html__('from the category ', 'woo-discount-rules');
                                        $htmlCategories = '';
                                        foreach ($category_discount as $category_id){
                                            $htmlCategories .= FlycartWoocommerceProduct::get_product_category_by_id($category_id).', ';
                                        }
                                        $htmlProduct .= trim($htmlCategories, ', ');
                                    } else if(count($product_discount) && $discount_product_option != 'more_than_one_cheapest_from_all'){
                                        foreach ($product_discount as $product_id){
                                            $product = FlycartWoocommerceProduct::wc_get_product($product_id);
                                            $htmlProduct .= "<a href='".FlycartWoocommerceProduct::get_permalink($product)."'>";
                                            $htmlProduct .= FlycartWoocommerceProduct::get_title($product);
                                            $htmlProduct .= "</a>";
                                            $htmlProduct .= ' ('.FlycartWoocommerceProduct::get_price_html($product).')<br>';
                                        }
                                    }
                                }
                                $discount = trim($htmlProduct, '<br>');
                            } else if ($discount_type == 'percentage_discount') {
                                $discount = $to_discount.' %';
                            } else {
                                $discount = FlycartWoocommerceProduct::wc_price($to_discount);
                            }

                        }
                        $dataReturn[$index.$id]['title'] = $title;
                        $dataReturn[$index.$id]['condition'] = $condition;
                        $dataReturn[$index.$id]['discount'] = $discount;
                    }
                }
            }
            return $dataReturn;
        }

        /**
         * Start Implementing the adjustments.
         *
         * @return bool
         */
        public function initAdjustment($cart_page_strikeout = 0)
        {
            if(!FlycartWooDiscountRulesGeneralHelper::haveToApplyTheRules()) return false;

            global $woocommerce;

            // Get settings
            $config = new FlycartWooDiscountBase();
            $config = $config->getBaseConfig();
            if (is_string($config)) $config = json_decode($config, true);
            if(isset($config['price_setup'])){
                $type = $config['price_setup'];
            } else {
                $type = 'all';
            }

            $cart_items = $woocommerce->cart->cart_contents;

            if($cart_page_strikeout){
                if(self::$rules_applied_already) return false;
            }
            if(!self::$rules_applied_already) self::$rules_applied_already = 1;

            foreach ($cart_items as $cart_item_key => $cart_item) {
                $this->applyAdjustment($cart_item, $cart_item_key, $type);
            }
        }

        /**
         * Start Implement adjustment on individual items in the cart.
         *
         * @param $cart_item
         * @param $cart_item_key
         * @param $type
         * @return bool
         */
        public function applyAdjustment($cart_item, $cart_item_key, $type)
        {
            global $woocommerce;

            // All Sets are Collected properly, just process with that.
            if (!isset($cart_item)) return false;

            // If Product having the rule sets then,
            if (!isset($this->matched_sets[$cart_item_key])) return false;
            if (empty($this->matched_sets[$cart_item_key])) return false;

            $adjustment_set = $this->matched_sets[$cart_item_key];
            $product = $woocommerce->cart->cart_contents[$cart_item_key]['data'];
            $product_id = FlycartWoocommerceProduct::get_id($product);
            $original_product = FlycartWoocommerceProduct::wc_get_product($product_id);

            //Check for wholesale price
            $hasWholesalePrice = apply_filters('woo_discount_rules_has_price_override', false, $product, 'on_calculate_discount', $woocommerce->cart->cart_contents[$cart_item_key]);
            if($hasWholesalePrice){
                $price = FlycartWoocommerceProduct::get_price($product);
            } else {
                $price = FlycartWoocommerceProduct::get_price($original_product);
            }

            //To reset the adjustment set if the Product discount adjustment exists
            $adjustment_set = $this->resetTheDiscountIfProductDiscountAdjustmentExists($adjustment_set, $product_id, $cart_item_key);
            $additionalDetails = array();
            $product_page = 0;
            if ($type == 'first') {
                // For Apply the First Rule.
                $discount = $this->getAmount($adjustment_set, $price, 'first');
                if(is_array($discount)){
                    $additionalDetails = $discount['details'];
                    $discount = $discount['amount'];
                }
                $amount = apply_filters('woo_discount_rules_price_rule_final_amount_applied', $price - $discount, $price, $discount, $additionalDetails, $product, $product_page);//$price - $discount;
                if($amount < 0) $amount = 0;
                $log = 'Discount | ' . $discount;
                $this->applyDiscount($cart_item_key, $amount, $log, $additionalDetails);
            } else if ($type == 'biggest') {
                // For Apply the Biggest Discount.
                $discount = $this->getAmount($adjustment_set, $price, 'biggest');
                if(is_array($discount)){
                    $additionalDetails = $discount['details'];
                    $discount = $discount['amount'];
                }
                $amount = apply_filters('woo_discount_rules_price_rule_final_amount_applied', $price - $discount, $price, $discount, $additionalDetails, $product, $product_page);//$price - $discount;
                if($amount < 0) $amount = 0;
                $log = 'Discount | ' . $discount;
                $this->applyDiscount($cart_item_key, $amount, $log, $additionalDetails);
            } else {
                // For Apply All Rules.
                $discount = $this->getAmount($adjustment_set, $price);
                if(is_array($discount)){
                    $additionalDetails = $discount['details'];
                    $discount = $discount['amount'];
                }
                $amount = apply_filters('woo_discount_rules_price_rule_final_amount_applied', $price - $discount, $price, $discount, $additionalDetails, $product, $product_page);//$price - $discount;
                if($amount < 0) $amount = 0;
                $log = 'Discount | ' . $discount;
                $this->applyDiscount($cart_item_key, $amount, $log, $additionalDetails);
            }
        }

        /**
         * To reset the adjustment set if the Product discount adjustment exists
         * */
        protected function resetTheDiscountIfProductDiscountAdjustmentExists($adjustment_sets, $product_id, $cart_item_key){
            foreach ($adjustment_sets as $key => $adjustment_set){
                if(isset($adjustment_set['amount']['product_discount_adjustment']) && !empty($adjustment_set['amount']['product_discount_adjustment'])){
                    if(isset($adjustment_set['amount']['product_discount_adjustment'][$product_id])){
                        if(isset($adjustment_set['amount']['product_cart_item_keys'])){
                            if(!in_array($cart_item_key, $adjustment_set['amount']['product_cart_item_keys'])){
                                unset($adjustment_sets[$key]);
                                continue;
                            }
                        }
                        $adjustment_sets[$key]['amount']['price_discount'] = $adjustment_set['amount']['product_discount_adjustment'][$product_id];
                        $adjustment_sets[$key]['amount']['product_ids'] = array($product_id);
                    }
                }
                if(isset($adjustment_set['amount']['product_discount_details']) && !empty($adjustment_set['amount']['product_discount_details'])){
                    if(isset($adjustment_set['amount']['product_discount_details'][$product_id])){
                        $adjustment_sets[$key]['amount']['product_discount_details'] = $adjustment_set['amount']['product_discount_details'][$product_id];
                    }
                }
            }

            return $adjustment_sets;
        }

        /**
         * To Get Amount based on the Setting that specified.
         *
         * @param $sets
         * @param $price
         * @param string $by
         * @return bool|float|int
         */
        public function getAmount($sets, $price, $by = 'all', $product_page = 0, $product = array())
        {
            $discount = 0;
            $overall_discount = 0;

            if (!isset($sets) || empty($sets)) return false;

            if ($price == 0) return $price;

            // For the biggest price, it compares the current product's price.
            if ($by == 'biggest') {
                $discount = $this->getBiggestDiscount($sets, $price, $product_page, $product);
                return $discount;
            }
            $details = array();
            foreach ($sets as $id => $set) {
                // For the First price, it will return the amount after get hit.
                if ($by == 'first') {
                    if(empty($set['amount'])){
                        continue;
                    }
                    if (isset($set['amount']['percentage_discount'])) {
                        $discount = ($price / 100) * $set['amount']['percentage_discount'];
                    } else if (isset($set['amount']['price_discount'])) {
                        $discount = $set['amount']['price_discount'];
                        if($product_page){
                            if(get_option('woocommerce_prices_include_tax', 'no') == 'no'){
                                if(get_option('woocommerce_tax_display_shop', 'incl') == 'incl'){
                                    $discount = FlycartWoocommerceProduct::get_price_including_tax($product, 1, $discount);
                                }
                            }
                        }
                    }
                    $details[] = isset($set['amount']['product_discount_details'])? $set['amount']['product_discount_details'] : array();
                    return array('amount' => $discount, 'details' => $details);
                } else {
                    // For All, All rules going to apply.
                    if (isset($set['amount']['percentage_discount'])) {
                        $discount = ($price / 100) * $set['amount']['percentage_discount'];
                        // Append all Discounts.
                        $overall_discount = $overall_discount + $discount;
                    } else if (isset($set['amount']['price_discount'])) {
                        $discount = $set['amount']['price_discount'];
                        if($product_page){
                            if(get_option('woocommerce_prices_include_tax', 'no') == 'no'){
                                if(get_option('woocommerce_tax_display_shop', 'incl') == 'incl'){
                                    $discount = FlycartWoocommerceProduct::get_price_including_tax($product, 1, $discount);
                                }
                            }
                        }
                        // Append all Discounts.
                        $overall_discount = $overall_discount + $discount;
                    }
                    $details[] = isset($set['amount']['product_discount_details'])? $set['amount']['product_discount_details'] : array();
                }
            }

            return array('amount' => $overall_discount, 'details' => $details);
        }

        /**
         * To Return the Biggest Discount across the available rule sets.
         *
         * @param $discount_list
         * @param $price
         * @return float|int
         */
        public function getBiggestDiscount($discount_list, $price, $product_page = 0, $product = array())
        {
            $big = $amount = 0;
            $details = array();
            foreach ($discount_list as $id => $discount_item) {
                $amount_type = (isset($discount_item['amount']['percentage_discount']) ? 'percentage_discount' : 'price_discount');
                if ($amount_type == 'percentage_discount') {
                    if (isset($discount_item['amount']['percentage_discount'])) {
                        $amount = (($price / 100) * $discount_item['amount']['percentage_discount']);
                    }
                } else {
                    if (isset($discount_item['amount']['price_discount'])) {
                        $amount = $discount_item['amount']['price_discount'];
                        if($product_page){
                            if(get_option('woocommerce_prices_include_tax', 'no') == 'no'){
                                if(get_option('woocommerce_tax_display_shop', 'incl') == 'incl'){
                                    $amount = FlycartWoocommerceProduct::get_price_including_tax($product, 1, $amount);
                                }
                            }
                        }
                    }
                }

                if ($big < $amount) {
                    $big = $amount;
                    $details = isset($discount_item['amount']['product_discount_details'])? $discount_item['amount']['product_discount_details'] : array();
                }
            }
            if(!empty($details)) $details = array($details);

            return array('amount' => $big,'details' => $details);
        }

        /**
         * Finally Apply the Discount to the Cart item by update to WooCommerce Instance.
         *
         * @param $item
         * @param $amount
         * @param $log
         */
        public function applyDiscount($item, $amount, $log, $additionalDetails = array())
        {
            global $woocommerce;
            // Make sure item exists in cart
            if (!isset($woocommerce->cart->cart_contents[$item])) {
                return;
            }
            $product =  $woocommerce->cart->cart_contents[$item]['data'];

            $product_id = FlycartWoocommerceProduct::get_id($product);

            //Check for price get override
            $hasWholesalePrice = apply_filters('woo_discount_rules_has_price_override', false, $product, 'on_apply_discount', $woocommerce->cart->cart_contents[$item]);
            if($hasWholesalePrice){
                $original_product = $product;
            } else {
                $original_product = FlycartWoocommerceProduct::wc_get_product($product_id);
                if(isset($product->woo_discount_rules_applied) && $product->woo_discount_rules_applied) return ;
            }

            // Log changes
            $woocommerce->cart->cart_contents[$item]['woo_discount'] = array(
                'original_price' => get_option('woocommerce_tax_display_cart') == 'excl' ? FlycartWoocommerceProduct::get_price_excluding_tax($original_product) : FlycartWoocommerceProduct::get_price_including_tax($original_product),
                'log' => $log,
                'additional_details' => $additionalDetails,
            );

            global $WOOCS;
            if(isset($WOOCS)){
                if (method_exists($WOOCS, 'get_currencies')){
                    $currencies = $WOOCS->get_currencies();
                    $is_geoip_manipulation = $WOOCS->is_geoip_manipulation;
                    $woocs_is_fixed_enabled = $WOOCS->is_fixed_enabled;
                    //woocs_is_geoip_manipulation //woocs_is_fixed_enabled
                    if($is_geoip_manipulation || $woocs_is_fixed_enabled){
                        $amount = $amount / $currencies[$WOOCS->current_currency]['rate'];
                    }
                }
            }

            // Actually adjust price in cart
//            $woocommerce->cart->cart_contents[$item]['data']->price = $amount;
            FlycartWoocommerceProduct::set_price($product, $amount);
            $product->woo_discount_rules_applied = 1;

            // To get the applied discount in cart
            if(isset($this->matched_sets[$item])){
                self::$matched_discounts[$item] = $this->matched_sets[$item];
                foreach (self::$matched_discounts[$item] as $matched_discounts){
                    $rule_order_id = $matched_discounts['rule_order'];
                    if(isset($this->rule_sets[$rule_order_id])){
                        self::$applied_discount_rules[$rule_order_id] = $this->rule_sets[$rule_order_id];
                    }
                }

            }
        }

        protected function hasToSplitTheStrikeOutInCart($cart_item, $additional_details){
            $run_multiple_strikeout = true;
            $quantity = $cart_item['quantity'];
            foreach ($additional_details as $detail){
                if(isset($detail['discount_quantity']))
                    if($detail['discount_quantity'] >= $quantity){
                        $run_multiple_strikeout = false;
                    }
            }
            return $run_multiple_strikeout;
        }

        /**
         * For Show the Actual Discount of a product.
         *
         * @param integer $item_price Actual Price.
         * @param object $cart_item Cart Items.
         * @param string $cart_item_key to identify the item from cart.
         * @return string processed price of a product.
         */
        public function replaceVisiblePricesCart($item_price, $cart_item = array(), $cart_item_key = null)
        {
            if(function_exists('is_user_logged_in')) if(!is_user_logged_in()){
                global $woocommerce;
                $this->analyse($woocommerce, 0, 1);
            }

            $config = new FlycartWooDiscountBase();
            $show_strikeout_in_cart = $config->getConfigData('show_strikeout_in_cart', 1);
            if (!isset($cart_item['woo_discount']) || !$show_strikeout_in_cart) {
                return $item_price;
            }

            // Get price to display
            $price = get_option('woocommerce_tax_display_cart') == 'excl' ? FlycartWoocommerceProduct::get_price_excluding_tax($cart_item['data']) : FlycartWoocommerceProduct::get_price_including_tax($cart_item['data']);

            // Format price to display
            $price_to_display = FlycartWoocommerceProduct::wc_price($price);
            $original_price_to_display = FlycartWoocommerceProduct::wc_price($cart_item['woo_discount']['original_price']);

            if ($cart_item['woo_discount']['original_price'] > $price) {
                $quantity = $cart_item['quantity'];
                if(!empty($cart_item['woo_discount']['additional_details']) && count($cart_item['woo_discount']['additional_details'])){
                    $additional_details = $cart_item['woo_discount']['additional_details'];
                    $hasToRunMultipleStrikeOut = true;
                    if(count($additional_details) > 1){
                        $hasToRunMultipleStrikeOut = $this->hasToSplitTheStrikeOutInCart($cart_item, $additional_details);
                    }
                    if($hasToRunMultipleStrikeOut){
                        $item_price_first = '<div style="float: left;"><span class="cart_price">' . $original_price_to_display . '</span></div>';
                        $item_price = '';
                        $haslimitedDiscount = 0;
                        foreach ($additional_details as $key => $additional_detail){
                            if(!empty($additional_detail) && isset($additional_detail['discount_price'])){
                                $haslimitedDiscount = 1;
                                if(get_option('woocommerce_prices_include_tax', 'no') == 'no'){
                                    if(get_option('woocommerce_tax_display_cart', 'incl') == 'incl'){
                                        $additional_detail['discount_price'] = FlycartWoocommerceProduct::get_price_including_tax($cart_item['data'], 1, $additional_detail['discount_price']);
                                    }
                                } else {
                                    if(get_option('woocommerce_tax_display_cart') == 'excl'){
                                        $additional_detail['discount_price'] = FlycartWoocommerceProduct::get_price_excluding_tax($cart_item['data'], 1, $additional_detail['discount_price']);
                                    }
                                }
                                $new_price_to_display = $cart_item['woo_discount']['original_price'] - $additional_detail['discount_price'];
                                if($new_price_to_display < 0) $new_price_to_display = 0;
                                $new_price_to_display = FlycartWoocommerceProduct::wc_price($new_price_to_display);
                                $quantity -= $additional_detail['discount_quantity'];
                                $item_price .= '<div style="float: left;">';
                                $item_price .= '<span class="cart_price"><del>' . $original_price_to_display . '</del> <ins>' . $new_price_to_display . '</ins></span>';
                                $item_price .= '</div>';
                                $item_price .= '<div style="float: right; padding-left: 1em;">';
                                $item_price .= 'x '.$additional_detail['discount_quantity'];
                                $item_price .= '</div>';
                                $item_price .= '<div style="clear: both;"></div>';
                            }
                        }
                        if($haslimitedDiscount){
                            $item_price_first .= '<div style="float: right; padding-left: 1em;">';
                            $item_price_first .= 'x '.$quantity;
                            $item_price_first .= '</div>';
                            $item_price_first .= '<div style="clear: both;"></div>';
                        }

                        if($quantity <= 0 || !$haslimitedDiscount){
                            $item_price = '<span class="cart_price"><del>' . $original_price_to_display . '</del> <ins>' . $price_to_display . '</ins></span>';
                        } else {
                            $item_price = $item_price_first.$item_price;
                        }
                    } else {
                        $item_price = '<span class="cart_price"><del>' . $original_price_to_display . '</del> <ins>' . $price_to_display . '</ins></span>';
                    }
                } else {
                    $item_price = '<span class="cart_price"><del>' . $original_price_to_display . '</del> <ins>' . $price_to_display . '</ins></span>';
                }
                $item_price_cont = '<div style="display: inline-block;" class="woo-discount-rules-cart-strikeout-con">';
                $item_price_cont .= $item_price;
                $item_price_cont .= '</div>';
                $item_price = $item_price_cont;
            } else {
                $item_price = $price_to_display;
            }

            return $item_price;
        }

        /**
         * Replace visible price if rule matches for variants
         * */
        public function replaceVisiblePricesForVariant($data, $product, $variations)
        {
            if(FlycartWoocommerceVersion::wcVersion('3.0')) return $data;
            $item_price = $data['price_html'];
            $notAdmin = FlycartWooDiscountRulesGeneralHelper::doIHaveToRun();
            $show_price_discount_on_product_page = (isset($this->baseConfig['show_price_discount_on_product_page']))? $this->baseConfig['show_price_discount_on_product_page']: 'dont';
            if($show_price_discount_on_product_page == 'show' && $notAdmin){
                $discountPrice = $this->getDiscountPriceForTheProduct($product, FlycartWoocommerceProduct::get_price($variations));
                $product_id = FlycartWoocommerceProduct::get_id($variations);
                if(isset(self::$product_strike_out_price[$product_id]) && !empty(self::$product_strike_out_price[$product_id])){
                    return self::$product_strike_out_price[$product_id];
                }
                if($discountPrice > 0 || ($this->hasDiscountForProductId($product_id))){
                    $price_to_display = FlycartWoocommerceProduct::wc_price($discountPrice);
                    $item_price = preg_replace('/<del>.*<\/del>/', '', $item_price);
                    $item_price = '<del>' . $item_price . '</del> <ins>' . ($price_to_display).$product->get_price_suffix() . '</ins>';
                }
                self::$product_strike_out_price[$product_id] = $item_price;
            }

            $data['price_html'] = $item_price;
            return $data;
        }

        /**
         * Replace visible price if rule matches
         * */
        public function replaceVisiblePrices($item_price, $product)
        {
            $run_variation_strike_out_with_ajax = apply_filters('woo_discount_rules_run_variation_strike_out_with_ajax', true, $product);
            if($run_variation_strike_out_with_ajax) {
                if (!(defined('WOO_DISCOUNT_DOING_AJAX'))) {
                    $parent_id = FlycartWoocommerceProduct::get_parent_id($product);
                    if ($parent_id) {
                        return $item_price;
                    }
                }
            }

            $notAdmin = FlycartWooDiscountRulesGeneralHelper::doIHaveToRun();
            $show_price_discount_on_product_page = (isset($this->baseConfig['show_price_discount_on_product_page']))? $this->baseConfig['show_price_discount_on_product_page']: 'dont';
            if($show_price_discount_on_product_page == 'show' && $notAdmin){
                $discountPrice = $this->getDiscountPriceForTheProduct($product);
                $product_id = FlycartWoocommerceProduct::get_id($product);
                self::$product_on_sale[$product_id] = 0;
                self::$product_has_strike_out[$product_id]['has_strikeout'] = 0;
                self::$product_has_strike_out[$product_id]['new_strikeout_html'] = '';
                if($discountPrice > 0 || ($this->hasDiscountForProductId($product_id))){
                    self::$product_on_sale[$product_id] = 1;
                    $price_to_display = FlycartWoocommerceProduct::wc_price($discountPrice);
                    $show_original = 0;
                    if(FlycartWoocommerceVersion::wcVersion('3.0'))
                        $price_to_display = $this->checkForHighestVariantIfExists($product, $price_to_display, $show_original);
                    if(!$show_original){
                        self::$product_has_strike_out[$product_id]['has_strikeout'] = 1;
                        self::$product_has_strike_out[$product_id]['new_strikeout_html'] = ($price_to_display).$product->get_price_suffix();
                        $item_price = preg_replace('/<del>.*<\/del>/', '', $item_price);
                        $item_price = apply_filters('woo_discount_rules_price_strikeout_before_discount_price', $item_price, $product);
                        $item_price = '<span class="cart_price"><del>' . $item_price . '</del> <ins>' . ($price_to_display).$product->get_price_suffix() . '</ins></span>';
                        $item_price = apply_filters('woo_discount_rules_price_strikeout_after_discount_price', $item_price, $product);
                    }
                }
            }

            return $item_price;
        }

        /**
         * Replace visible price if rule matches Optimized
         * */
        public function replaceVisiblePricesOptimized($item_price, $product)
        {
            $run_product_price_strikeout = apply_filters('woo_discount_rules_run_product_price_strikeout', true, $product);
            if(!$run_product_price_strikeout){
                return $item_price;
            }

            if(isset($product->woo_discount_rules_do_not_run_strikeout) && $product->woo_discount_rules_do_not_run_strikeout) return $item_price;

            $runTheRulesEvenInAjax = apply_filters('woo_discount_rules_run_strike_out_for_ajax', false, $product);

            $run_variation_strike_out_with_ajax = apply_filters('woo_discount_rules_run_variation_strike_out_with_ajax', true, $product);
            if($run_variation_strike_out_with_ajax){
                if (!(defined('WOO_DISCOUNT_DOING_AJAX'))) {
                    $parent_id = FlycartWoocommerceProduct::get_parent_id($product);
                    if($parent_id){
                        return $item_price;
                    }
                }
            }

            $notAdmin = FlycartWooDiscountRulesGeneralHelper::doIHaveToRun();
            $show_price_discount_on_product_page = (isset($this->baseConfig['show_price_discount_on_product_page']))? $this->baseConfig['show_price_discount_on_product_page']: 'dont';
            if($show_price_discount_on_product_page == 'show' && ($notAdmin || $runTheRulesEvenInAjax)){
                $product_id = FlycartWoocommerceProduct::get_id($product);
                if(isset(self::$product_strike_out_price[$product_id]) && !empty(self::$product_strike_out_price[$product_id])){
                    return self::$product_strike_out_price[$product_id];
                }
                if(isset(self::$product_has_strike_out[$product_id]) && self::$product_has_strike_out[$product_id]['has_strikeout']){
                    if(self::$product_has_strike_out[$product_id]['has_strikeout'] && !empty(self::$product_has_strike_out[$product_id]['new_strikeout_html'])){
                        $item_price = preg_replace('/<del>.*<\/del>/', '', $item_price);
                        $item_price = apply_filters('woo_discount_rules_price_strikeout_before_discount_price', $item_price, $product);
                        $item_price = '<span class="cart_price"><del>' . $item_price . '</del> <ins>' . (self::$product_has_strike_out[$product_id]['new_strikeout_html']) . '</ins></span>';
                        $item_price = apply_filters('woo_discount_rules_price_strikeout_after_discount_price', $item_price, $product);
                    }
                } else {
                    $item_price = $this->replaceVisiblePrices($item_price, $product);
                }
                self::$product_strike_out_price[$product_id] = $item_price;
            }

            return $item_price;
        }

        /**
         * Display Product sale tag on the product page optimized
         * */
        public function displayProductIsOnSaleTagOptimized($on_sale, $product){
            $runTheRulesEvenInAjax = apply_filters('woo_discount_rules_run_sale_tag_for_ajax', false, $product);
            $notAdmin = FlycartWooDiscountRulesGeneralHelper::doIHaveToRun();
            $show_price_discount_on_product_page = (isset($this->baseConfig['show_sale_tag_on_product_page']))? $this->baseConfig['show_sale_tag_on_product_page']: 'dont';
            if($show_price_discount_on_product_page == 'show' && ($notAdmin || $runTheRulesEvenInAjax)){
                $product_id = FlycartWoocommerceProduct::get_id($product);
                if(isset(self::$product_on_sale[$product_id])){
                    if(self::$product_on_sale[$product_id]){
                        $on_sale = true;
                    }
                } else {
                    $show_price_discount_on_product_page = (isset($this->baseConfig['show_price_discount_on_product_page']))? $this->baseConfig['show_price_discount_on_product_page']: 'dont';
                    $optimize_sale_and_price_strikeout = apply_filters('woo_discount_rules_do_sale_tag_through_strikeout_price', true, $product);
                    if($show_price_discount_on_product_page == 'show' && $optimize_sale_and_price_strikeout){
                        $this->replaceVisiblePrices('', $product);
                        if(isset(self::$product_on_sale[$product_id])){
                            if(self::$product_on_sale[$product_id]){
                                $on_sale = true;
                            }
                        }
                    } else {
                        $on_sale = $this->displayProductIsOnSaleTag($on_sale, $product);
                    }
                }
            }
            return $on_sale;
        }

        public function getWooDiscountedPriceForVariant(){
            if (isset($_REQUEST['id']) && $_REQUEST['id'] && isset($_REQUEST['price_html']) && $_REQUEST['price_html'] != '') {
                if (!defined('WOO_DISCOUNT_DOING_AJAX')) define('WOO_DISCOUNT_DOING_AJAX', 1);
                $product = FlycartWoocommerceProduct::wc_get_product($_REQUEST['id']);
                $price_html_request = stripslashes($_REQUEST['price_html']);
                $price_html = $this->replaceVisiblePrices($price_html_request, $product);
                $return['status'] = 1;
                $return['price_html'] = $price_html;
                echo json_encode($return);
                exit;
            }
        }

        public function checkForHighestVariantIfExists($product, $price_to_display, &$show_original){
            $display_only_lowest_price = apply_filters('woo_discount_rules_load_minimum_product_variant_price', false, $product);
            $tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
            $child_prices     = array();

            if(FlycartWoocommerceVersion::wcVersion('3.1.0')){
                $children         = array_filter( array_map( 'wc_get_product', FlycartWoocommerceProduct::get_children($product) ), 'wc_products_array_filter_visible_grouped' );
                foreach ( $children as $child ) {
                    if ( '' !== FlycartWoocommerceProduct::get_price($child) ) {
                        $child_prices[FlycartWoocommerceProduct::get_id($child)] = 'incl' === $tax_display_mode ? FlycartWoocommerceProduct::get_price_including_tax($child) : FlycartWoocommerceProduct::get_price_excluding_tax( $child );
                    }
                }
            } else {
                $children = $product->get_children();
                foreach ( $children as $child_id ) {
                    $child = FlycartWoocommerceProduct::wc_get_product($child_id);
                    if ( '' !== FlycartWoocommerceProduct::get_price($child) ) {
                        $child_prices[FlycartWoocommerceProduct::get_id($child)] = 'incl' === $tax_display_mode ? FlycartWoocommerceProduct::get_price_including_tax($child) : FlycartWoocommerceProduct::get_price_excluding_tax( $child );
                    }
                }
            }

            $maxProductId = 0;
            $minProductId = 0;
            if ( ! empty( $child_prices ) ) {
                $min_price = min( $child_prices );
                $max_price = max( $child_prices );
                if($min_price != $max_price){
                    $maxProductIds = array_keys($child_prices, $max_price);
                    $minProductIds = array_keys($child_prices, $min_price);
                    if(isset($maxProductIds[0]))
                        $maxProductId = $maxProductIds[0];
                    if(isset($minProductIds[0]))
                        $minProductId = $minProductIds[0];
                }
            }
            if($maxProductId){
                $maxProduct = FlycartWoocommerceProduct::wc_get_product($maxProductId);
                $greatestDiscountPrice = $this->getDiscountPriceForTheProduct($maxProduct);
                if($greatestDiscountPrice > 0 || ($this->hasDiscountForProductId($maxProductId))){
                    $greatestDiscountPrice = FlycartWoocommerceProduct::wc_price($greatestDiscountPrice);
                } else {
                    $greatestDiscountPrice = FlycartWoocommerceProduct::wc_price(FlycartWoocommerceProduct::get_price($maxProduct));
                }
                if($minProductId){
                    $minProduct = FlycartWoocommerceProduct::wc_get_product($minProductId);
                    $leastDiscountPrice = $this->getDiscountPriceForTheProduct($minProduct);
                    if($leastDiscountPrice > 0 || ($this->hasDiscountForProductId($minProductId))){
                        $leastDiscountPrice = FlycartWoocommerceProduct::wc_price($leastDiscountPrice);
                    } else {
                        $leastDiscountPrice = FlycartWoocommerceProduct::wc_price(FlycartWoocommerceProduct::get_price($minProduct));
                    }
                    if($display_only_lowest_price){
                        $price_to_display = $leastDiscountPrice;
                    } else {
                        $price_to_display = $leastDiscountPrice.' - '.$greatestDiscountPrice;
                    }
                } else {
                    if(!$display_only_lowest_price)
                        $price_to_display .= ' - '.$greatestDiscountPrice;
                }
            } else {
                if($product->is_type(array('variable', 'subscription_variation', 'variable-subscription'))){
                    if ( ! empty( $child_prices ) ) {
                        $child_products = array_keys($child_prices);
                        if(isset($child_products[0])){
                            $product_new = FlycartWoocommerceProduct::wc_get_product($child_products[0]);
                            $discountPrice = $this->getDiscountPriceForTheProduct($product_new);
                            if($discountPrice <= 0 || !($this->hasDiscountForProductId($child_products[0]))){
                                $show_original = 1;
                            } else {
                                $price_to_display = FlycartWoocommerceProduct::wc_price($discountPrice);
                            }
                        }
                    }
                }
            }

            return $price_to_display;
        }

        /**
         * Display Product sale tag on the product page
         * */
        public function displayProductIsOnSaleTag($on_sale, $product){
            $runTheRulesEvenInAjax = apply_filters('woo_discount_rules_run_sale_tag_for_ajax', false, $product);
            $notAdmin = FlycartWooDiscountRulesGeneralHelper::doIHaveToRun();
            $show_price_discount_on_product_page = (isset($this->baseConfig['show_sale_tag_on_product_page']))? $this->baseConfig['show_sale_tag_on_product_page']: 'dont';
            if($show_price_discount_on_product_page == 'show' && ($notAdmin || $runTheRulesEvenInAjax)){
                $product_id = FlycartWoocommerceProduct::get_id($product);
                self::$product_on_sale[$product_id] = 0;
                $discountPrice = $this->getDiscountPriceForTheProduct($product);
                if($discountPrice > 0){
                    $on_sale = true;
                    self::$product_on_sale[$product_id] = 1;
                } else {
                    if($this->hasDiscountForProductId($product_id)){
                        $on_sale = true;
                        self::$product_on_sale[$product_id] = 1;
                    }
                }

                if(!$on_sale){
                    if($product->is_type(array('variable', 'subscription_variation', 'variable-subscription'))){
                        if(FlycartWoocommerceVersion::wcVersion('3.1.0')){
                            $children         = array_filter( array_map( 'wc_get_product', FlycartWoocommerceProduct::get_children($product) ), 'wc_products_array_filter_visible_grouped' );
                            foreach ( $children as $child ) {
                                if ( '' !== FlycartWoocommerceProduct::get_price($child) ) {
                                    $discountPrice = $this->getDiscountPriceForTheProduct($child);
                                    if($discountPrice > 0){
                                        $on_sale = true;
                                        self::$product_on_sale[$product_id] = 1;
                                        break;
                                    } else {
                                        $product_id = FlycartWoocommerceProduct::get_id($child);
                                        if($this->hasDiscountForProductId($product_id)){
                                            $on_sale = true;
                                            self::$product_on_sale[$product_id] = 1;
                                            break;
                                        }
                                    }
                                }
                            }
                        } else {
                            $children = $product->get_children();
                            foreach ( $children as $child_id ) {
                                $child = FlycartWoocommerceProduct::wc_get_product($child_id);
                                if ( '' !== FlycartWoocommerceProduct::get_price($child) ) {
                                    $discountPrice = $this->getDiscountPriceForTheProduct($child);
                                    if($discountPrice > 0){
                                        $on_sale = true;
                                        self::$product_on_sale[$product_id] = 1;
                                        break;
                                    } else {
                                        $product_id = FlycartWoocommerceProduct::get_id($child);
                                        if($this->hasDiscountForProductId($product_id)){
                                            $on_sale = true;
                                            self::$product_on_sale[$product_id] = 1;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $on_sale;
        }

        /**
         * Has discount for the product Id
         * */
        protected function hasDiscountForProductId($product_id){
            $has_discount = false;
            if(isset($this->products_has_discount[$product_id]) && $this->products_has_discount[$product_id] == 1){
                $has_discount = true;
            }

            return $has_discount;
        }

        /**
         * Display Product sale tag on the product page
         * */
        public function displayProductIsOnSaleTagNew($on_sale, $product){
            $notAdmin = FlycartWooDiscountRulesGeneralHelper::doIHaveToRun();
            $show_price_discount_on_product_page = (isset($this->baseConfig['show_sale_tag_on_product_page']))? $this->baseConfig['show_sale_tag_on_product_page']: 'dont';
            if($show_price_discount_on_product_page == 'show' && $notAdmin){
                global $product;
                $rules = $this->generateDiscountTableData($product);
                if(!empty($rules)){
                    if(is_array($rules) && count($rules)){
                        $on_sale = true;
                    }
                }
            }
            return $on_sale;
        }

        /**
         * To check discount for this product or not
         * */
        public function getDiscountPriceForTheProduct($product, $variationPrice = 0){
            $discountPrice = 0;
            $product_id = FlycartWoocommerceProduct::get_id($product);
            $item['product_id'] = $product_id;
            $item['data'] = $product;
            $item['quantity'] = ($this->getQuantityOfProductInCart($product_id))+1;

            // To display the strike out price in product page for variant (specific attribute rule)
            if($product->get_type() == 'variation'){
                if(FlycartWoocommerceVersion::wcVersion('3.1.0')){
                    $p_data = $product->get_data();
                    if(!empty($p_data['attributes'])){
                        $attr = array();
                        foreach ($p_data['attributes'] as $key => $value){
                            $attr['attribute_'.$key] = $value;
                        }
                        $item['variation'] = $attr;
                        $item['variation_id'] = $product_id;
                    }
                } else {
                    $item['variation'] = $product->get_variation_attributes();
                    $item['variation_id'] = $product_id;
                }
            }

            global $woocommerce;
            $this->analyse($woocommerce, 1);
            $this->matched_sets = array();
            $this->matchRules($product_id, $item, 1);
            if(isset($this->matched_sets[$product_id])){
                if($variationPrice){
                    $discountPrice = $this->getAdjustmentDiscountedPrice($product, $product_id, $this->apply_to, $variationPrice);
                } else {
                    $discountPrice = $this->getAdjustmentDiscountedPrice($product, $product_id, $this->apply_to);
                }
            }
            return $discountPrice;
        }

        /**
         * To check discount for this product or not
         * */
        public function getDiscountPriceOfProduct($product, $variationPrice = 0){
            global $flycart_woo_discount_rules;
            remove_action('woocommerce_before_calculate_totals', array($flycart_woo_discount_rules, 'applyDiscountRules'), 1000);
            $discountPrice = null;
            $product_id = FlycartWoocommerceProduct::get_id($product);
            $item['product_id'] = $product_id;
            $item['data'] = $product;
            $qty = 1;
            $cart = FlycartWoocommerceCart::get_cart();
            foreach ( $cart as $cart_item ) {
                if($cart_item['product_id'] == $product_id ){
                    $qty =  $cart_item['quantity'];
                    break; // stop the loop if product is found
                }
            }
            $item['quantity'] = $qty;

            // To display the strike out price in product page for variant (specific attribute rule)
            if($product->get_type() == 'variation'){
                $variationPrice = $product->get_price();
                if(FlycartWoocommerceVersion::wcVersion('3.1.0')){
                    $p_data = $product->get_data();
                    if(!empty($p_data['attributes'])){
                        $attr = array();
                        foreach ($p_data['attributes'] as $key => $value){
                            $attr['attribute_'.$key] = $value;
                        }
                        $item['variation'] = $attr;
                        $item['variation_id'] = $product_id;
                    }
                } else {
                    $item['variation'] = $product->get_variation_attributes();
                    $item['variation_id'] = $product_id;
                }
            }

            global $woocommerce;
            $this->analyse($woocommerce, 1);
            $this->matched_sets_for_product = array();
            $this->matchRules($product_id, $item, 1);
            if(isset($this->matched_sets_for_product[$product_id]) && !empty($this->matched_sets_for_product[$product_id])){
                if($variationPrice){
                    $discountPrice = $this->getAdjustmentDiscountedPrice($product, $product_id, $this->apply_to, $variationPrice);
                } else {
                    $discountPrice = $this->getAdjustmentDiscountedPrice($product, $product_id, $this->apply_to);
                }
            }
            add_action('woocommerce_before_calculate_totals', array($flycart_woo_discount_rules, 'applyDiscountRules'), 1000);
            return $discountPrice;
        }

        /**
         * Get Quantity of product in cart
         * */
        protected function getQuantityOfProductInCart($productId){
            $qty = 0;
            $cart = FlycartWoocommerceCart::get_cart();
            foreach ( $cart as $cart_item ) {
                if($cart_item['product_id'] == $productId ){
                    $qty =  $cart_item['quantity'];
                    break; // stop the loop if product is found
                }
            }
            return $qty;
        }

        /**
         * get discounted value
         * */
        public function getAdjustmentDiscountedPrice($cart_item, $cart_item_key, $type, $price = 0)
        {
            // All Sets are Collected properly, just process with that.
            if (!isset($cart_item)) return false;
            // If Product having the rule sets then,
            if (!isset($this->matched_sets[$cart_item_key])) return false;

            $product_id = FlycartWoocommerceProduct::get_id($cart_item);
            $adjustment_set = $this->matched_sets[$cart_item_key];
            if(!($price > 0)){
                $price = get_option('woocommerce_tax_display_shop') == 'excl' ? FlycartWoocommerceProduct::get_price_excluding_tax($cart_item) : FlycartWoocommerceProduct::get_price_including_tax($cart_item);
            }

            if(!($price > 0)){
                $children = FlycartWoocommerceProduct::get_children($cart_item);
                if(!empty($children) && is_array($children)){
                    if(isset($children[0])){
                        $product = FlycartWoocommerceProduct::wc_get_product($children[0]);
                        $product_id = FlycartWoocommerceProduct::get_id($product);
                        $price = get_option('woocommerce_tax_display_shop') == 'excl' ? FlycartWoocommerceProduct::get_price_excluding_tax($product) : FlycartWoocommerceProduct::get_price_including_tax($product);
                    }
                }
            }

            $amount = 0;
            $discount = 0;
            $additionalDetails = array();
            if ($type == 'first') {
                // For Apply the First Rule.
                $discount = $this->getAmount($adjustment_set, $price, 'first', 1, $cart_item);
                if(is_array($discount)){
                    $additionalDetails = $discount['details'];
                    $discount = $discount['amount'];
                }

            } else if ($type == 'biggest') {
                // For Apply the Biggest Discount.
                $discount = $this->getAmount($adjustment_set, $price, 'biggest', 1, $cart_item);
                if(is_array($discount)){
                    $additionalDetails = $discount['details'];
                    $discount = $discount['amount'];
                }
            } else {
                // For Apply All Rules.
                $discount = $this->getAmount($adjustment_set, $price, 'all', 1, $cart_item);
                if(is_array($discount)){
                    $additionalDetails = $discount['details'];
                    $discount = $discount['amount'];
                }
            }
            $product_page = 1;
            if($discount > 0){
                $this->products_has_discount[$product_id] = 1;
                $amount = apply_filters('woo_discount_rules_price_rule_final_amount_applied', $price - $discount, $price, $discount, $additionalDetails, $cart_item, $product_page);//$price - $discount;
            }
            if($amount < 0) $amount = 0;

            return $amount;
        }
    }
}
