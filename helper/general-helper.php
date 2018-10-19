<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
include_once(WOO_DISCOUNT_DIR . '/helper/purchase.php');
include_once(WOO_DISCOUNT_DIR . '/helper/woo-function.php');
/**
 * Class FlycartWooDiscountRulesGeneralHelper
 */
if ( ! class_exists( 'FlycartWooDiscountRulesGeneralHelper' ) ) {
    class FlycartWooDiscountRulesGeneralHelper
    {

        public $isPro;

        /**
         * @var string
         */
        public $default_page = 'pricing-rules';

        /**
         * To Process the View.
         *
         * @param $path
         * @param $data
         * @return bool|string
         */
        public function processBaseView($path, $data)
        {
            if (!file_exists($path)) return false;
            $this->checkPluginState();
            $purchase = new FlycartWooDiscountRulesPurchase();
            $suffix = $purchase->getSuffix();
            ob_start();
            $config = $data;
            $pro = $this->isPro;
            $category = $this->getCategoryList();
            //$users = $this->getUserList();
            FlycartWoocommerceVersion::wcVersion('3.0')? $flycart_wdr_woocommerce_version = 3: $flycart_wdr_woocommerce_version = 2;
            $userRoles = $this->getUserRoles();
            $userRoles['woo_discount_rules_guest'] = esc_html__('Guest', 'woo-discount-rules');
            $countries = $this->getAllCountries();
            
            if (!isset($config)) return false;
            if (!isset($path) or is_null($config)) return false;
            include($path);
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        }

        public function checkPluginState()
        {
            $purchase = new FlycartWooDiscountRulesPurchase();
            $this->isPro = $purchase->isPro();
            return $this->isPro;
        }

        /**
         * To Retrieve the list of Users.
         *
         * @return array
         */
        public function getUserList()
        {
            $result = array();
            foreach (get_users() as $user) {
                $result[$user->ID] = '#' . $user->ID . ' ' . $user->user_email;
            }
            return $result;
        }

        /**
         * To Retrieve the active tab.
         *
         * @return string
         */
        public function getCurrentTab()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $tab = $this->default_page;
            $empty_tab = $postData->get('tab', null);
            if (!empty($empty_tab) && $postData->get('tab', '') != '') {
                $tab = sanitize_text_field($postData->get('tab', ''));
            }
            return $tab;
        }

        /**
         * To Get All Countries.
         *
         * @return array
         */
        public function getAllCountries()
        {
            $countries = new WC_Countries();

            if ($countries && is_array($countries->countries)) {
                return array_merge(array(), $countries->countries);
            } else {
                return array();
            }
        }

        /**
         * To Get All Capabilities list.
         *
         * @return array
         */
        public function getCapabilitiesList()
        {
            $capabilities = array();

            if (class_exists('Groups_User') && class_exists('Groups_Wordpress') && function_exists('_groups_get_tablename')) {

                global $wpdb;
                $capability_table = _groups_get_tablename('capability');
                $all_capabilities = $wpdb->get_results('SELECT capability FROM ' . $capability_table);

                if ($all_capabilities) {
                    foreach ($all_capabilities as $capability) {
                        $capabilities[$capability->capability] = $capability->capability;
                    }
                }
            } else {
                global $wp_roles;

                if (!isset($wp_roles)) {
                    get_role('administrator');
                }

                $roles = $wp_roles->roles;

                if (is_array($roles)) {
                    foreach ($roles as $rolename => $atts) {
                        if (isset($atts['capabilities']) && is_array($atts['capabilities'])) {
                            foreach ($atts['capabilities'] as $capability => $value) {
                                if (!in_array($capability, $capabilities)) {
                                    $capabilities[$capability] = $capability;
                                }
                            }
                        }
                    }
                }
            }

            return array_merge(array(), $capabilities);
        }

        /**
         * @return array
         */
        public function getUserRoles()
        {
            global $wp_roles;

            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
            $roles = array();
            foreach ($wp_roles->get_names() as $key => $wp_role ){
                if(function_exists('translate_user_role')) $roles[$key] = translate_user_role($wp_role);
                else $roles[$key] = $wp_role;
            }

            return $roles;
        }

        /**
         * Get list of roles assigned to current user
         *
         * @access public
         * @return array
         */
        public static function getCurrentUserRoles()
        {
            $current_user = wp_get_current_user();
            $userRoles = $current_user->roles;
            if(get_current_user_id() == 0){
                $userRoles[] = 'woo_discount_rules_guest';
            }
            return $userRoles;
        }

        /**
         * @return array
         */
        public function getCategoryList()
        {
            $result = array();
            $taxonomies = apply_filters('woo_discount_rules_accepted_taxonomy_for_category', array('product_cat'));
            $post_categories_raw = get_terms($taxonomies, array('hide_empty' => 0));
            $post_categories_raw_count = count($post_categories_raw);

            foreach ($post_categories_raw as $post_cat_key => $post_cat) {
                $category_name = $post_cat->name;

                if ($post_cat->parent) {
                    $parent_id = $post_cat->parent;
                    $has_parent = true;

                    // Make sure we don't have an infinite loop here (happens with some kind of "ghost" categories)
                    $found = false;
                    $i = 0;

                    while ($has_parent && ($i < $post_categories_raw_count || $found)) {

                        // Reset each time
                        $found = false;
                        $i = 0;

                        foreach ($post_categories_raw as $parent_post_cat_key => $parent_post_cat) {

                            $i++;

                            if ($parent_post_cat->term_id == $parent_id) {
                                $category_name = $parent_post_cat->name . ' &rarr; ' . $category_name;
                                $found = true;

                                if ($parent_post_cat->parent) {
                                    $parent_id = $parent_post_cat->parent;
                                } else {
                                    $has_parent = false;
                                }

                                break;
                            }
                        }
                    }
                }

                $result[$post_cat->term_id] = $category_name;
            }

            return $result;
        }

        /**
         * Get Category by passing product ID or Product.
         *
         * @param $item
         * @param bool $is_id
         * @return array
         */
        public static function getCategoryByPost($item, $is_id = false)
        {
            if ($is_id) {
                $id = $item;
            } else {
                $id = FlycartWoocommerceProduct::get_id($item['data']);
            }
            $product = FlycartWoocommerceProduct::wc_get_product($id);
            $categories = FlycartWoocommerceProduct::get_category_ids($product);

            return $categories;
        }

        /**
         * To Parsing the Array from String to Int.
         *
         * @param array $array
         */
        public static function toInt(array &$array)
        {
            foreach ($array as $index => $item) {
                $array[$index] = intval($item);
            }
        }

        /**
         * @param $html
         * @return bool|mixed
         */
        static function makeString($html)
        {
            if (is_null($html) || empty($html) || !isset($html)) return false;
            $out = $html;
            // This Process only helps, single level array.
            if (is_array($html)) {
                foreach ($html as $id => $value) {
                    self::escapeCode($value);
                    $html[$id] = $value;
                }
                return $out;
            } else {
                self::escapeCode($html);
                return $html;
            }
        }

        /**
         * Re-Arrange the Index of Array to Make Usable.[2-D Array Only]
         * @param $rules
         */
        public static function reArrangeArray(&$rules)
        {
            $result = array();
            foreach ($rules as $index => $item) {
                foreach ($item as $id => $value) {
                    $result[$id] = $value;
                }
            }
            $rules = $result;
        }

        /**
         * @param $value
         */
        static function escapeCode(&$value)
        {
            if(is_string($value)){
                // Four Possible tags for PHP to Init.
                $value = preg_replace(array('/^<\?php.*\?\>/', '/^<\%.*\%\>/', '/^<\?.*\?\>/', '/^<\?=.*\?\>/'), '', $value);
                $value = self::delete_all_between('<?php', '?>', $value);
                $value = self::delete_all_between('<?', '?>', $value);
                $value = self::delete_all_between('<?=', '?>', $value);
                $value = self::delete_all_between('<%', '%>', $value);
                $value = str_replace(array('<?php', '<?', '<?=', '<%', '?>'), '', $value);
            }
        }


        /**
         * @param $beginning
         * @param $end
         * @param $string
         * @return mixed
         */
        static function delete_all_between($beginning, $end, $string)
        {

            if (!is_string($string)) return false;

            $beginningPos = strpos($string, $beginning);
            $endPos = strpos($string, $end);
            if ($beginningPos === false || $endPos === false) {
                return $string;
            }

            $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

            return str_replace($textToDelete, '', $string);
        }

        /**
         * To get slider content through curl
         * */
        public static function getSideBarContent(){
            $html = '';
            if(is_callable('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://www.flycart.org/updates/woo-discount-rules.json');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $contents = curl_exec($ch);
                $contents_decode = json_decode($contents);
                if(isset($contents_decode['0']->promo_html)){
                    $html = $contents_decode['0']->promo_html;
                }
            }

            return $html;
        }

        /**
         * Check do the discount rules need to execute
         * */
        public static function doIHaveToRun(){
            $status = true;
            if(is_admin()){
                $status = false;
            }
            if(defined('DOING_AJAX') && DOING_AJAX){
                $status = true;
                $postData = \FlycartInput\FInput::getInstance();
                $action = $postData->get('action', '');
                $form = $postData->get('from', '');
                if($action == 'saveCartRule' || $action == 'savePriceRule'){
                    $status = false;
                } else if(($action == 'UpdateStatus' || $action == 'RemoveRule') && ($form == 'cart-rules' || $form == 'pricing-rules')){
                    $status = false;
                } else if($action == 'saveConfig' && $form == 'settings'){
                    $status = false;
                }
            }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
                $status = false;
            }

            return $status;
        }

        public static function haveToApplyTheRules(){
            $status = true;
            $config = new FlycartWooDiscountBase();
            $do_not_run_while_have_third_party_coupon = $config->getConfigData('do_not_run_while_have_third_party_coupon', 0);
            if($do_not_run_while_have_third_party_coupon){
                $hasCoupon = self::hasCouponInCart();
                if($hasCoupon){
                    $status = false;
                }
            }

            return $status;
        }

        /**
         * Check has coupon - not related to woo discount rules
         * */
        public static function hasCouponInCart(){
            $result = false;
            $cartRules = new FlycartWooDiscountRulesCartRules();
            $coupon_code = $cartRules->getCouponCode();
            $coupon_code = strtolower($coupon_code);
            global $woocommerce;
            if (!empty($woocommerce->cart->applied_coupons)) {
                $appliedCoupons = $woocommerce->cart->applied_coupons;
                if(in_array($coupon_code, $appliedCoupons)){
                    if(count($appliedCoupons) > 1){
                        $result = true;
                    }
                } else {
                    $result = true;
                }
            }

            return $result;
        }

        /**
         * Get Current date and time based on Wordpress time zone
         *
         * @param string $date
         * @return string
         * */
        public static function getCurrentDateAndTimeBasedOnTimeZone($date = ''){
            if(empty($date)){
                $current_date = new DateTime('now', new DateTimeZone('UTC'));
                $date = $current_date->format('Y-m-d H:i:s');
            }
            $offset = get_option('gmt_offset');
            if(empty($offset)){
                $offset = 0;
            }
            //$time_zone = get_option('timezone_string');
            return date("Y-m-d H:i:s", strtotime($date) + (3600 * $offset) );
        }

        /**
         * Validate the start and end date
         *
         * @param string $date_from
         * @param string $date_to
         * @return boolean
         * */
        public static function validateDateAndTime($date_from, $date_to){
            $valid = true;
            $current_date = self::getCurrentDateAndTimeBasedOnTimeZone();
            if($date_from != ''){
                if(!(strtotime($date_from) <= strtotime($current_date))) $valid = false;
            }
            if($date_to != ''){
                if(!(strtotime($date_to) >= strtotime($current_date))) $valid = false;
            }

            return $valid;
        }

        /**
         * Reorder the rule if order id already exists
         *
         * @param int $id
         * @param int $order_id
         * @param string $post_type
         * @return int
         * */
        public static function reOrderRuleIfExists($id, $order_id, $post_type){
            $posts = get_posts(array('post_type' => $post_type, 'numberposts' => '-1', 'exclude' => array($id)));
            $greaterId = $alreadyExists = 0;
            if (!empty($posts) && count($posts) > 0) {
                foreach ($posts as $index => $item) {
                    $orderId = get_post_meta($item->ID, 'rule_order', true);
                    if(!empty($orderId)){
                        if((int)$order_id == (int)$orderId){
                            $alreadyExists = 1;
                        }
                        if($orderId > $greaterId){
                            $greaterId = $orderId;
                        }
                    }
                }
            }
            if($alreadyExists){
                $greaterId++;
                return $greaterId;
            }

            return $order_id;
        }

        /**
         * Get all sub categories
         * */
        public static function getAllSubCategories($cat){
            $taxonomies = apply_filters('woo_discount_rules_accepted_taxonomy_for_category', array('product_cat'));
            $category_with_sub_cat = $cat;
            foreach ($taxonomies as $taxonomy){
                $category_with_sub = self::getAllSubCategoriesRecursive($cat, $taxonomy);
                $category_with_sub_cat = array_merge($category_with_sub_cat, $category_with_sub);
            }

            return $category_with_sub_cat;
//            $category_with_sub_cat = $cat;
//            foreach($cat as $c) {
//                $args = array('hierarchical' => 1,
//                    'show_option_none' => '',
//                    'hide_empty' => 0,
//                    'parent' => $c,
//                    'taxonomy' => 'product_cat');
//                $categories = get_categories( $args );
//                foreach($categories as $category) {
//                    //$category_with_sub_cat[] = $category->term_id;
//                    $category_with_sub_cat = array_merge($category_with_sub_cat, self::getAllSubCategories(array($category->term_id)));
//                }
//            }
//            $category_with_sub_cat = array_unique($category_with_sub_cat);
//
//            return $category_with_sub_cat;
        }

        /**
         * Get all sub categories
         * */
        protected static function getAllSubCategoriesRecursive($cat, $taxonomy = 'product_cat'){
            $category_with_sub_cat = $cat;
            foreach($cat as $c) {
                $args = array('hierarchical' => 1,
                    'show_option_none' => '',
                    'hide_empty' => 0,
                    'parent' => $c,
                    'taxonomy' => $taxonomy);
                $categories = get_categories( $args );
                foreach($categories as $category) {
                    //$category_with_sub_cat[] = $category->term_id;
                    $category_with_sub_cat = array_merge($category_with_sub_cat, self::getAllSubCategoriesRecursive(array($category->term_id), $taxonomy));
                }
            }
            $category_with_sub_cat = array_unique($category_with_sub_cat);

            return $category_with_sub_cat;
        }

        /**
         * Docs HTML
         * @param string $url
         * @param string $utm_term
         * @return string
         * */
        public static function docsURL($url, $utm_term){
            $url_prefix = 'https://docs.flycart.org/woocommerce-discount-rules/';
            $utm = '?utm_source=woo-discount-rules&utm_campaign=doc&utm_medium=text-click&';
            $url = trim($url, '/');

            return $url_prefix.$url.$utm.$utm_term;
        }

        /**
         * Docs HTML
         * @param string $url
         * @param string $utm_term
         * @return string
         * */
        public static function docsURLHTML($url, $utm_term, $additional_class = '', $text = ''){
            if(!empty($additional_class)){
                $additional_class = 'wdr_read_doc '.$additional_class;
            } else {
                $additional_class = 'wdr_read_doc';
            }
            if(empty($text)) $text = esc_html__('Read Docs', 'woo-discount-rules');
            $html = '<a class="'.$additional_class.'" href="'.self::docsURL($url, $utm_term).'" target="_blank">'.$text.'</a>';

            return $html;
        }

        /**
         * Docs HTML
         * @param string $url
         * @param string $utm_term
         * @return string
         * */
        public static function docsURLHTMLForDocumentation($url, $utm_term, $text, $description_text = '', $additional_class = ''){
            if(!empty($additional_class)){
                $additional_class = 'wdr_read_documentation '.$additional_class;
            } else {
                $additional_class = 'wdr_read_documentation';
            }
            $html = '<div class="wdr_read_documentation_con">';
            $html .= '<a class="'.$additional_class.'" href="'.self::docsURL($url, $utm_term).'" target="_blank">'.$text.'</a>';
            if(!empty($description_text))
            $html .= '<p class="">'.$description_text.'</p>';
            $html .= '</div>';

            return $html;
        }

        /**
         * Remove Coupon price in cart
         * @param array $coupons
         * */
        public static function removeCouponPriceInCart($coupons = array()){
            $config = new FlycartWooDiscountBase();
            $remove_zero_coupon_price = $config->getConfigData('remove_zero_coupon_price', 0);
            if($remove_zero_coupon_price){
                $has_coupon = false;
                $styles = '';
                foreach ($coupons as $coupon){
                    $wc = WC();
                    if(!empty($wc) && !empty($wc->cart)){
                        if(method_exists($wc->cart, 'has_discount')){
                            if($wc->cart->has_discount($coupon)){
                                $coupon_amount = $wc->cart->get_coupon_discount_amount($coupon);
                                if($coupon_amount == 0){
                                    $has_coupon = true;
                                    $style = '.coupon-'.strtolower($coupon).' .amount{display: none}.coupon-'.strtolower($coupon).' td::first-letter{font-size: 0}';
                                    $styles .= apply_filters('woo_discount_rules_apply_style_for_zero_price_coupon', $style, $coupon);
                                }
                            }
                        }
                    }
                }
                if($has_coupon){
                    if(!empty($styles)){
                        $is_ajax = is_ajax();
                        $wc_ajax = isset($_REQUEST['wc-ajax'])? $_REQUEST['wc-ajax']: false;
                        if(!$is_ajax || in_array($wc_ajax, array('apply_coupon'))){
                            echo "<style>".$styles."</style>";
                        }
                    }
                }
            }
        }
    }
}
