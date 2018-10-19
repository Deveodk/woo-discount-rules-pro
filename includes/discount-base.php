<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $woocommerce;

/**
 * Class FlycartWooDiscountBase
 */
if (!class_exists('FlycartWooDiscountBase')) {
    class FlycartWooDiscountBase
    {
        /**
         * @var string
         */
        public $default_page = 'pricing-rules';

        /**
         * @var string
         */
        public $default_option = 'woo-discount-config';

        /**
         * @var array
         */
        private $instance = array();

        public $has_free_shipping = 0;

        /**
         * FlycartWooDiscountBase constructor.
         */
        public function __construct() {}

        /**
         * Singleton Instance maker.
         *
         * @param $name
         * @return bool
         */
        public function getInstance($name)
        {
            if (!isset($this->instance[$name])) {
                if (class_exists($name)) {
                    $this->instance[$name] = new $name;
                    $instance = $this->instance[$name];
                } else {
                    $instance = false;
                }
            } else {
                $instance = $this->instance[$name];
            }
            return $instance;
        }

        /**
         * Managing discount of Price and Cart.
         */
        public function handleDiscount()
        {
            global $woocommerce;

            $price_discount = $this->getInstance('FlycartWooDiscountRulesPricingRules');
            $cart_discount = $this->getInstance('FlycartWooDiscountRulesCartRules');

            $price_discount->analyse($woocommerce);
            $cart_discount->analyse($woocommerce);
        }

        /**
         * Managing discount of Cart.
         */
        public function handleCartDiscount($free_shipping_check = 0)
        {
            global $woocommerce;
            $cart_discount = $this->getInstance('FlycartWooDiscountRulesCartRules');
            $cart_discount->analyse($woocommerce, $free_shipping_check);
            if($free_shipping_check){
                $this->has_free_shipping = $cart_discount->has_free_shipping;
            }
        }

        /**
         * Managing discount of Price.
         */
        public function handlePriceDiscount()
        {
            global $woocommerce;
            $price_discount = $this->getInstance('FlycartWooDiscountRulesPricingRules');
            $price_discount->analyse($woocommerce);
        }

        /**
         * For adding script in checkout page
         * */
        public function addScriptInCheckoutPage(){
            $script = '<script type="text/javascript">
                    jQuery( function( $ ) {
                        $(document).ready(function() {
                            $( document.body ).on( "blur", "input#billing_email", function() {
                                $("select#billing_country").trigger("change");
                            });
                        }); 
                    });
                </script>';
            echo $script;
        }

        /**
         * WooCommerce hook to change the name of a product.
         *
         * @param $title
         * @return mixed
         */
        public function modifyName($title)
        {
            //
            return $title;
        }

        /**
         * Finally, on triggering the "Thank You" hook by WooCommerce,
         * Overall session data's are stored to the order's meta as "woo_discount_log".
         *
         * @param integer $order_id Order ID.
         */
        public function storeLog($order_id)
        {
            $log['price_discount'] = WC()->session->get('woo_price_discount', array());
            $log['cart_discount'] = WC()->session->get('woo_cart_discount', array());

            add_post_meta($order_id, 'woo_discount_log', json_encode($log), 1);

            // Reset the Coupon Status.
            WC()->session->set('woo_coupon_removed', '');
        }

        /**
         * Create New Menu On WooCommerce.
         */
        public function adminMenu()
        {
            if (!is_admin()) return;

            global $submenu;
            if (isset($submenu['woocommerce'])) {
                add_submenu_page(
                    'woocommerce',
                    'Woo Discount Rules',
                    'Woo Discount Rules',
                    'edit_posts',
                    'woo_discount_rules',
                    array($this, 'viewManager')
                );
            }
        }

        /**
         * Update the Status of the Rule Set.
         */
        public function updateStatus()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $id = $postData->get('id', false);
            if ($id) {
                $status = get_post_meta($id, 'status', false);
                if (isset($status[0])) {
                    $state = ($status[0] == 'publish') ? 'disable' : 'publish';
                    update_post_meta($id, 'status', $state);
                } else {
                    add_post_meta($id, 'status', 'disable');
                    $state = 'disable';
                }
                echo ucfirst($state);
            }
            die();
        }

        /**
         * Remove the Rule Set.
         */
        public function removeRule()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $id = $postData->get('id', false);
            if ($id) {
                try {
                    $id = intval($id);
                    if (!$id) return false;
                    wp_delete_post($id);
                } catch (Exception $e) {
                    //
                }
            }
            die();
        }
//    -------------------------------------- PRICE RULES ---------------------------------------------------------------
        /**
         * Saving the Price Rule.
         *
         * @return bool
         */
        public function savePriceRule()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $params = array();
            if (!isset($request['data'])) return false;
            parse_str($request['data'], $params);

            $pricing_rule = $this->getInstance('FlycartWooDiscountRulesPricingRules');
            $pricing_rule->save($params);
            die();
        }

//    -------------------------------------- CART RULES ----------------------------------------------------------------
        /**
         * Saving the Cart Rule.
         *
         * @return bool
         */
        public function saveCartRule()
        {

            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $params = array();
            if (!isset($request['data'])) return false;
            parse_str($request['data'], $params);
            $this->parseFormWithRules($params, true);
            $pricing_rule = $this->getInstance('FlycartWooDiscountRulesCartRules');
            $pricing_rule->save($params);
            die();
        }

        /**
         * load product select box
         *
         * @return bool
         */
        public function loadProductSelectBox() {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            if (!isset($request['name'])) return false;
            echo FlycartWoocommerceProduct::getProductAjaxSelectBox(array(), $request['name']);
            die();
        }

        /**
         * Making the reliable end data to store.
         *
         * @param $cart_rules
         * @param bool $isCartRules
         */
        public function parseFormWithRules(&$cart_rules, $isCartRules = false)
        {
            $cart_rules['discount_rule'] = $this->generateFormData($cart_rules, $isCartRules);
        }

        /**
         * @param $cart_rules
         * @param bool $isCartRules
         * @return array
         */
        public function generateFormData($cart_rules, $isCartRules = false)
        {
            $link = $this->fieldLink();

            $discount_list = array();
            // Here, Eliminating the Cart's rule with duplicates.
            $discount_rule = (isset($cart_rules['discount_rule']) ? $cart_rules['discount_rule'] : array());
            if ($isCartRules) {
                foreach ($discount_rule as $index => $value) {

                    // The Type of Option should get value from it's native index.
                    // $link[$value['type']] will gives the native index of the "type"

                    if (isset($link[$value['type']])) {
                        if(is_array($link[$value['type']])){
                            foreach ($link[$value['type']] as $fields){
                                $discount_list[$index][$value['type']][$fields] = $value[$fields];
                            }
                        } else if (isset($value[$link[$value['type']]])) {
                            $discount_list[$index][$value['type']] = $value[$link[$value['type']]];
                        }
                    } else {
                        $discount_list[$index][$value['type']] = $value['option_value'];
                    }
                }
            }
            return $discount_list;

        }

        /**
         * @return array
         */
        public function fieldLink()
        {
            // TODO: Check Subtotal Link
            return array(
                'products_atleast_one' => 'product_to_apply',
                'products_not_in' => 'product_to_apply',

                'categories_atleast_one' => 'category_to_apply',
                'categories_not_in' => 'category_to_apply',
                'categories_in' => 'category_to_apply',
                'in_each_category' => 'category_to_apply',
                'atleast_one_including_sub_categories' => 'category_to_apply',

                'users_in' => 'users_to_apply',
                'roles_in' => 'user_roles_to_apply',
                'shipping_countries_in' => 'countries_to_apply',
                'customer_based_on_purchase_history' => array('purchase_history_order_status', 'purchased_history_amount', 'purchased_history_type'),
                'customer_based_on_purchase_history_order_count' => array('purchase_history_order_status', 'purchased_history_amount', 'purchased_history_type'),
                'customer_based_on_purchase_history_product_order_count' => array('purchase_history_order_status', 'purchased_history_amount', 'purchased_history_type', 'purchase_history_products'),
            );
        }

        // ----------------------------------------- CART RULES END --------------------------------------------------------


        // -------------------------------------------SETTINGS--------------------------------------------------------------

        /**
         *
         */
        public function saveConfig($licenceValidate = 0)
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $params = array();
            if (isset($request['data'])) {
                parse_str($request['data'], $params);
            }

            if (is_array($request)) {
                if(isset($params['show_draft']) && $params['show_draft']){
                    $params['show_draft'] = 1;
                } else {
                    $params['show_draft'] = 0;
                }
                foreach ($params as $index => $item) {
//                $params[$index] = FlycartWooDiscountRulesGeneralHelper::makeString($item);
                    $params[$index] = $item;
                }
                $params = json_encode($params);
            }
//        else {
//            $params = FlycartWooDiscountRulesGeneralHelper::makeString($params);
//        }

            if (get_option($this->default_option)) {
                update_option($this->default_option, $params);
            } else {
                add_option($this->default_option, $params);
            }
            if(!$licenceValidate)
                die();
        }

        public function resetWDRCache(){
            $price_discount = $this->getInstance('FlycartWooDiscountRulesPricingRules');
            $result = $price_discount->updateLastUpdateTimeOfRule();
            if($result){
                esc_html_e('Cache cleared successfully', 'woo-discount-rules');
            } else {
                esc_html_e('Failed to clear cache', 'woo-discount-rules');
            }
            die();
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
         * Get Config data
         *
         * @param String $key
         * @param mixed $default
         * @return mixed
         * */
        public function getConfigData($key, $default = ''){
            $config = $this->getBaseConfig();
            if (is_string($config)) $config = json_decode($config, true);
            return isset($config[$key])? $config[$key] : $default;
        }

        // -------------------------------------------SETTINGS END----------------------------------------------------------

        /**
         * @param $request
         * @return bool
         */
        public function checkSubmission($request)
        {
            if (isset($request['form']) && !empty($request['form'])) {
                $form = sanitize_text_field($request['form']);
                if (strpos($form, '_save') === false) return false;
                // For Saving Form
                $form = str_replace('_save', '', $form);
                // To Verify, the submitted form is in the Registered List or Not
                if (in_array($form, $this->formList())) {
                    if (isset($request['page'])) {
                        switch ($form) {
                            case 'pricing_rules':
                                die(123);
                                $pricing_rule = $this->getInstance('FlycartWooDiscountRulesPricingRules');
                                $pricing_rule->save($request);
                                break;
                            case 'cart_rules':
                                $cart_rules = $this->getInstance('FlycartWooDiscountRulesCartRules');
                                $cart_rules->save($request);
                                break;
                            case 'settings':
                                $this->save($request);
                                break;
                            default:
                                // Invalid Submission.
                                break;
                        }
                    }
                }
            }
        }

        /**
         * @param $option
         */
        public function checkAccess(&$option)
        {
            $postData = \FlycartInput\FInput::getInstance();
            // Handling View
            if ($postData->get('view', false)) {
                $option = $option . '-view';
                // Type : Price or Cart Discounts.
            } elseif ($postData->get('type', false)) {
                if ($postData->get('tab', false)) {
                    if ($postData->get('tab', '') == 'cart-rules') {
                        $option = 'cart-rules-new';
                        if ($postData->get('type', '') == 'view') $option = 'cart-rules-view';
                    }
                } else {
                    $option = $option . '-' . $postData->get('type', '');
                }
            }
        }

        /**
         * @param $request
         */
        public function save($request)
        {
            // Save General Settings of the Plugin.
        }

        /**
         * Do bulk action
         * */
        public function doBulkAction(){
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            if(empty($request['bulk_action'])){
                echo esc_html__('Failed to do action', 'woo-discount-rules');exit;
            }
            $result = array();
            $had_action = 0;
            if(!empty($request['post'])){
                foreach ($request['post'] as $key => $id){
                    $had_action = 1;
                    $result[$key] = 0;
                    if($id){
                        switch ($request['bulk_action']){
                            case 'unpublish':
                                $status = get_post_meta($id, 'status', true);
                                if (!empty($status)) {
                                    $result[$key] = update_post_meta($id, 'status', 'disable');
                                }
                                break;
                            case 'delete':
                                try {
                                    $id = intval($id);
                                    if ($id) $result[$key] = wp_delete_post($id);
                                } catch (Exception $e) {
                                }
                                break;
                            default:
                                $status = get_post_meta($id, 'status', true);

                                if (!empty($status)) {
                                    $result[$key] = update_post_meta($id, 'status', 'publish');
                                }
                                break;
                        }
                    }
                }
            }
            if(!$had_action){
                echo esc_html__('Failed to do action', 'woo-discount-rules');
            } else{
                switch ($request['bulk_action']){
                    case 'unpublish':
                        echo esc_html__('Disabled successfully', 'woo-discount-rules');
                        break;
                    case 'delete':
                        echo esc_html__('Deleted successfully', 'woo-discount-rules');
                        break;
                    default:
                        echo esc_html__('Enabled successfully', 'woo-discount-rules');
                        break;
                }
            }
            die();
        }

        /**
         * Create a duplicate rule
         * */
        public function createDuplicateRule(){
            $purchase = new FlycartWooDiscountRulesPurchase();
            $isPro = $purchase->isPro();
            if(!$isPro) return false;
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            if(!empty($request['id']) && (int)$request['id'] && !empty($request['type'])){
                $post = get_post( (int)$request['id'] );
                if(!empty($post)){
                    $post_new = array(
                        'post_title' => $post->post_title.' - '.esc_html__('copy', 'woo-discount-rules'),
                        'post_name' => $post->post_title.' - '.esc_html__('copy', 'woo-discount-rules'),
                        'post_content' => 'New Rule',
                        'post_type' => $post->post_type,
                        'post_status' => 'publish'
                    );
                    $id = wp_insert_post($post_new);
                    if($id){
                        /*
                         * duplicate all post meta just in two SQL queries
                         */
                        global $wpdb;
                        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post->ID");
                        if (count($post_meta_infos)!=0) {
                            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                            foreach ($post_meta_infos as $meta_info) {
                                $meta_key = $meta_info->meta_key;
                                if( $meta_key == 'rule_order' ) $meta_info->meta_value = FlycartWooDiscountRulesGeneralHelper::reOrderRuleIfExists($id, 1, $post->post_type);
                                if( $meta_key == 'rule_name' ) $meta_info->meta_value = $meta_info->meta_value.' - '.esc_html__('copy', 'woo-discount-rules');
                                if( $meta_key == 'status' ) $meta_info->meta_value = 'disable';
                                $meta_value = addslashes($meta_info->meta_value);
                                $sql_query_sel[]= "SELECT $id, '$meta_key', '$meta_value'";
                            }
                            $sql_query.= implode(" UNION ALL ", $sql_query_sel);
                            $wpdb->query($sql_query);
                        }
                        echo esc_html__('Duplicate rule created successfully', 'woo-discount-rules'); die();
                    }
                }
            }
            echo esc_html__('Failed to create duplicate rule', 'woo-discount-rules'); die();
        }

        /**
         * @return array
         */
        public function formList()
        {
            return array(
                'pricing_rules',
                'cart_rules',
                'settings'
            );
        }

        /**
         *
         */
        public function viewManager()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $this->checkSubmission($request);

            // Adding Plugin Page Script
            //$this->woo_discount_adminPageScript();

            // Loading Instance.
            $generalHelper = $this->getInstance('FlycartWooDiscountRulesGeneralHelper');
            // Sanity Check.
            if (!$generalHelper) return;
            // Getting Active Tab.
            $tab = $generalHelper->getCurrentTab();

            $path = $this->getPath($tab);

            // Manage Tab.
            $tab = (isset($tab) ? $tab : $this->default_page);
            $html = '';
            // File Check.
            if (file_exists($path)) {
                $data = array();
                $this->fetchData($tab, $data);
                // Processing View.
                $html = $generalHelper->processBaseView($path, $data);
            }
            echo $html;
        }

        /**
         * @param $tab
         * @return mixed
         */
        public function getPath(&$tab)
        {
            $this->checkAccess($tab);
            $pages = $this->adminPages();
            // Default tab.
            $path = $pages[$this->default_page];

            // Comparing Available Tab with Active Tab.
            if (isset($pages[$tab])) {
                $path = $pages[$tab];
            }
            return $path;
        }

        /**
         * @param $type
         * @param $data
         */
        public function fetchData($type, &$data)
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();

            $helper = new FlycartWooDiscountRulesGeneralHelper();
            $isPro = $helper->checkPluginState();

            switch ($type) {
                // Managing Price Rules View.
                case 'pricing-rules':
                    $pricing_rule = $this->getInstance('FlycartWooDiscountRulesPricingRules');
                    $data = $pricing_rule->getRules();
                    break;
                // Managing Cart Rules View.
                case 'cart-rules':
                    $cart_rule = $this->getInstance('FlycartWooDiscountRulesCartRules');
                    $data = $cart_rule->getRules();
                    break;
                // Managing View of Settings.
                case 'settings':
                    $data = $this->getBaseConfig();
                    break;
                case 'documentation':
                    break;

                // Managing View of Pricing Rules.
                case 'pricing-rules-new':
                    $data = new stdClass();
                    $data->form = 'pricing_rules_save';
                    if (!$isPro) {
                        $pricing_rule = $this->getInstance('FlycartWooDiscountRulesPricingRules');
                        $data = $pricing_rule->getRules();
                        if (count($data) >= 3) die('You are restricted to process this action.');
                    }
                    break;

                // Managing View of Pricing Rules.
                case 'pricing-rules-view':

                    $view = false;
                    // Handling View
                    if (isset($request['view'])) {
                        $view = $request['view'];
                    }
                    $html = $this->getInstance('FlycartWooDiscountRulesPricingRules');
                    $out = $html->view($type, $view);
                    if (isset($out) && !empty($out)) {
                        $data = $out;
                    }
                    $data->form = 'pricing_rules_save';
                    break;

                // Managing View of Cart Rules.
                case 'cart-rules-view':
                    $view = false;
                    // Handling View
                    if (isset($request['view'])) {
                        $view = $request['view'];
                    } else {

                        if (!$isPro) {
                            $cart_rule = $this->getInstance('FlycartWooDiscountRulesCartRules');
                            $total_record = $cart_rule->getRules(true);
                            if ($total_record >= 3) wp_die('You are restricted to process this action.');
                        }
                    }

                    $html = $this->getInstance('FlycartWooDiscountRulesCartRules');
                    $out = $html->view($type, $view);
                    if (isset($out) && !empty($out)) {
                        $data[] = $out;
                    }
                    break;
                // Managing View of Cart Rules.
                case 'cart-rules-new':
                    if (!$isPro) {
                        $cart_rule = $this->getInstance('FlycartWooDiscountRulesCartRules');
                        $total_record = $cart_rule->getRules(true);
                        if ($total_record >= 3) wp_die('You are restricted to process this action.');
                    }
                    break;

                default:
                    $data = array();

                    break;
            }

        }

        /**
         * @return array
         */
        public function adminPages()
        {
            return array(
                $this->default_page => WOO_DISCOUNT_DIR . '/view/pricing-rules.php',
                'cart-rules' => WOO_DISCOUNT_DIR . '/view/cart-rules.php',
                'settings' => WOO_DISCOUNT_DIR . '/view/settings.php',
                'documentation' => WOO_DISCOUNT_DIR . '/view/documentation.php',

                // New Rule also access the same "View" to process
                'pricing-rules-new' => WOO_DISCOUNT_DIR . '/view/view-pricing-rules.php',
                'cart-rules-new' => WOO_DISCOUNT_DIR . '/view/view-cart-rules.php',

                // Edit Rules
                'pricing-rules-view' => WOO_DISCOUNT_DIR . '/view/view-pricing-rules.php',
                'cart-rules-view' => WOO_DISCOUNT_DIR . '/view/view-cart-rules.php'
            );
        }

        /**
         *
         */
        public function getOption()
        {

        }

        /**
         * Adding Admin Page Script.
         */
        function woo_discount_adminPageScript()
        {
            $status = false;
            $postData = \FlycartInput\FInput::getInstance();
            // Plugin scripts should run only in plugin page.
            if (is_admin()) {
                if ($postData->get('page', false) == 'woo_discount_rules') {
                    $status = true;
                }
                // By Default, the landing page also can use this script.
            } elseif (!is_admin()) {
                //  $status = true;
            }

            if ($status) {

                $config = $this->getBaseConfig();
                if (is_string($config)) $config = json_decode($config, true);
                $enable_bootstrap = isset($config['enable_bootstrap'])? $config['enable_bootstrap']: 1;

                wp_register_style('woo_discount_style', WOO_DISCOUNT_URI . '/assets/css/style.css', array(), WOO_DISCOUNT_VERSION);
                wp_enqueue_style('woo_discount_style');

                wp_register_style('woo_discount_style_custom', WOO_DISCOUNT_URI . '/assets/css/custom.css', array(), WOO_DISCOUNT_VERSION);
                wp_enqueue_style('woo_discount_style_custom');

                wp_register_style('woo_discount_style_tab', WOO_DISCOUNT_URI . '/assets/css/tabbablePanel.css', array(), WOO_DISCOUNT_VERSION);
                wp_enqueue_style('woo_discount_style_tab');

                // For Implementing Select Picker Library.
                wp_register_style('woo_discount_style_select', WOO_DISCOUNT_URI . '/assets/css/bootstrap.select.min.css', array(), WOO_DISCOUNT_VERSION);
                wp_enqueue_style('woo_discount_style_select');

                wp_enqueue_script('woo_discount_script_select', WOO_DISCOUNT_URI . '/assets/js/bootstrap.select.min.js', array(), WOO_DISCOUNT_VERSION);

                wp_register_style('woo_discount_bootstrap', WOO_DISCOUNT_URI . '/assets/css/bootstrap.min.css', array(), WOO_DISCOUNT_VERSION);
                wp_enqueue_style('woo_discount_bootstrap');

                if($enable_bootstrap){
                    wp_register_script('woo_discount_jquery_ui_js_2', WOO_DISCOUNT_URI . '/assets/js/bootstrap.min.js', array(), WOO_DISCOUNT_VERSION);
                    wp_enqueue_script('woo_discount_jquery_ui_js_2');
                }

                wp_register_style('woo_discount_jquery_ui_css', WOO_DISCOUNT_URI . '/assets/css/jquery-ui.css', array(), WOO_DISCOUNT_VERSION);
                wp_enqueue_style('woo_discount_jquery_ui_css');
                wp_register_style('woo_discount_datetimepicker_css', WOO_DISCOUNT_URI . '/assets/css/bootstrap-datetimepicker.min.css', array(), WOO_DISCOUNT_VERSION);
                wp_enqueue_style('woo_discount_datetimepicker_css');

                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-core');
                wp_enqueue_script('jquery-ui-datepicker');
                wp_enqueue_script( 'woocommerce_admin' );
                wp_enqueue_script( 'wc-enhanced-select' );

                wp_enqueue_script('woo_discount_datetimepicker_js', WOO_DISCOUNT_URI . '/assets/js/bootstrap-datetimepicker.min.js', array('woocommerce_admin', 'wc-enhanced-select'), WOO_DISCOUNT_VERSION, true);
                wp_enqueue_script('woo_discount_script', WOO_DISCOUNT_URI . '/assets/js/app.js', array(), WOO_DISCOUNT_VERSION);
                $localization_data = $this->getLocalizationData();
                wp_localize_script( 'woo_discount_script', 'woo_discount_localization', $localization_data);

                //To load woocommerce product select
                wp_enqueue_style( 'woocommerce_admin_styles' );
            }
        }

        /**
         * Get localisation script
         * */
        protected function getLocalizationData(){
            return array(
                'please_fill_this_field' => esc_html__('Please fill this field', 'woo-discount-rules'),
                'please_enter_the_rule_name' => esc_html__('Please Enter the Rule Name to Create / Save.', 'woo-discount-rules'),
                'saving' => esc_html__('Saving...', 'woo-discount-rules'),
                'save_rule' => esc_html__('Save Rule', 'woo-discount-rules'),
                'please_enter_a_key' => esc_html__('Please enter a Key', 'woo-discount-rules'),
                'min_quantity' => esc_html__('Min Quantity', 'woo-discount-rules'),
                'max_quantity' => esc_html__('Max Quantity', 'woo-discount-rules'),
                'place_holder_ex_1' => esc_html__('ex. 1', 'woo-discount-rules'),
                'place_holder_ex_10' => esc_html__('ex. 10', 'woo-discount-rules'),
                'place_holder_ex_50' => esc_html__('ex. 50', 'woo-discount-rules'),
                'place_holder_search_for_a_user' => esc_html__('Search for a user', 'woo-discount-rules'),
                'adjustment_type' => esc_html__('Adjustment Type', 'woo-discount-rules'),
                'percentage_discount' => esc_html__('Percentage Discount', 'woo-discount-rules'),
                'price_discount' => esc_html__('Price Discount', 'woo-discount-rules'),
                'product_discount' => esc_html__('Product Discount', 'woo-discount-rules'),
                'product_discount_not_work_on_subtotal_based' => esc_html__('Product Discount - Not support for subtotal based rule', 'woo-discount-rules'),
                'value_text' => esc_html__('Value', 'woo-discount-rules'),
                'apply_for' => esc_html__('Apply for', 'woo-discount-rules'),
                'all_selected' => esc_html__('All selected', 'woo-discount-rules'),
                'same_product' => esc_html__('Same product', 'woo-discount-rules'),
                'any_one_cheapest_from_selected' => esc_html__('Any one cheapest from selected', 'woo-discount-rules'),
                'any_one_cheapest_from_all_products' => esc_html__('Any one cheapest from all products', 'woo-discount-rules'),
                'more_than_one_cheapest_from_selected_category' => esc_html__('More than one cheapest from selected category', 'woo-discount-rules'),
                'more_than_one_cheapest_from_selected' => esc_html__('More than one cheapest from selected', 'woo-discount-rules'),
                'more_than_one_cheapest_from_all' => esc_html__('More than one cheapest from all', 'woo-discount-rules'),
                'free_quantity' => esc_html__('Free quantity', 'woo-discount-rules'),
                'number_of_quantities_in_each_products' => esc_html__('Number of quantity(ies) in each selected product(s)', 'woo-discount-rules'),
                'fixed_item_count' => esc_html__('Fixed item count', 'woo-discount-rules'),
                'dynamic_item_count' => esc_html__('Dynamic item count', 'woo-discount-rules'),
                'fixed_item_count_tooltip' => esc_html__('Fixed item count - You need to provide item count manually. Dynamic item count - System will choose dynamically based on cart', 'woo-discount-rules'),
                'item_count' => esc_html__('Item count', 'woo-discount-rules'),
                'discount_number_of_item_tooltip' => esc_html__('Discount for number of item(s) in cart', 'woo-discount-rules'),
                'discount_number_of_each_item_tooltip' => esc_html__('Discount for number of quantity(ies) in each item', 'woo-discount-rules'),
                'item_quantity' => esc_html__('Item quantity', 'woo-discount-rules'),
                'place_holder_search_for_products' => esc_html__('Search for a products', 'woo-discount-rules'),
                'and_text' => esc_html__('and', 'woo-discount-rules'),
                'percent_100' => esc_html__('100% percent', 'woo-discount-rules'),
                'limited_percent' => esc_html__('Limited percent', 'woo-discount-rules'),
                'percentage_tooltip' => esc_html__('Percentage', 'woo-discount-rules'),
                'as_discount' => esc_html__('as discount', 'woo-discount-rules'),
                'remove_text' => esc_html__('Remove', 'woo-discount-rules'),
                'none_text' => esc_html__('none', 'woo-discount-rules'),
                'are_you_sure_to_remove_this' => esc_html__('Are you sure to remove this ?', 'woo-discount-rules'),
                'enable_text' => esc_html__('Enable', 'woo-discount-rules'),
                'disable_text' => esc_html__('Disable', 'woo-discount-rules'),
                'are_you_sure_to_remove' => esc_html__('Are you sure to remove ?', 'woo-discount-rules'),
                'type_text' => esc_html__('Type', 'woo-discount-rules'),
                'cart_subtotal' => esc_html__('Cart Subtotal', 'woo-discount-rules'),
                'subtotal_at_least' => esc_html__('Subtotal at least', 'woo-discount-rules'),
                'subtotal_less_than' => esc_html__('Subtotal less than', 'woo-discount-rules'),
                'cart_item_count' => esc_html__('Cart Item Count', 'woo-discount-rules'),
                'number_of_line_items_in_cart_at_least' => esc_html__('Number of line items in the cart (not quantity) at least', 'woo-discount-rules'),
                'number_of_line_items_in_cart_less_than' => esc_html__('Number of line items in the cart (not quantity) less than', 'woo-discount-rules'),
                'quantity_sum' => esc_html__('Quantity Sum', 'woo-discount-rules'),
                'total_number_of_quantities_in_cart_at_least' => esc_html__('Total number of quantities in the cart at least', 'woo-discount-rules'),
                'total_number_of_quantities_in_cart_less_than' => esc_html__('Total number of quantities in the cart less than', 'woo-discount-rules'),
                'categories_in_cart' => esc_html__('Categories in cart', 'woo-discount-rules'),
                'atleast_one_including_sub_categories' => esc_html__('Including sub-categories in cart', 'woo-discount-rules'),
                'customer_details_must_be_logged_in' => esc_html__('Customer Details (must be logged in)', 'woo-discount-rules'),
                'user_in_list' => esc_html__('User in list', 'woo-discount-rules'),
                'user_role_in_list' => esc_html__('User role in list', 'woo-discount-rules'),
                'shipping_country_list' => esc_html__('Shipping country in list', 'woo-discount-rules'),
                'customer_email' => esc_html__('Customer Email', 'woo-discount-rules'),
                'customer_email_tld' => esc_html__('Email with TLD (Ege: edu)', 'woo-discount-rules'),
                'customer_email_domain' => esc_html__('Email with Domain (Eg: gmail.com)', 'woo-discount-rules'),
                'customer_billing_details' => esc_html__('Customer Billing Details', 'woo-discount-rules'),
                'customer_billing_city' => esc_html__('Billing city', 'woo-discount-rules'),
                'customer_shipping_details' => esc_html__('Customer Shipping Details', 'woo-discount-rules'),
                'customer_shipping_state' => esc_html__('Shipping state', 'woo-discount-rules'),
                'customer_shipping_city' => esc_html__('Shipping city', 'woo-discount-rules'),
                'customer_shipping_zip_code' => esc_html__('Shipping zip code', 'woo-discount-rules'),
                'purchase_history' => esc_html__('Purchase History', 'woo-discount-rules'),
                'purchased_amount' => esc_html__('Purchased amount', 'woo-discount-rules'),
                'number_of_order_purchased' => esc_html__('Number of order purchased', 'woo-discount-rules'),
                'number_of_order_purchased_in_product' => esc_html__('Number of order purchased in products', 'woo-discount-rules'),
                'coupon_applied' => esc_html__('Coupon applied', 'woo-discount-rules'),
                'atleast_any_one' => esc_html__('Atleast any one', 'woo-discount-rules'),
                'greater_than_or_equal_to' => esc_html__('Greater than or equal to', 'woo-discount-rules'),
                'less_than_or_equal_to' => esc_html__('Less than or equal to', 'woo-discount-rules'),
                'in_order_status' => esc_html__('In Order status', 'woo-discount-rules'),
                'action_text' => esc_html__('Action', 'woo-discount-rules'),
                'save_text' => esc_html__('Save', 'woo-discount-rules'),
                'saved_successfully' => esc_html__('Saved Successfully!', 'woo-discount-rules'),
                'none_selected' => esc_html__('None selected', 'woo-discount-rules'),
                'in_each_category_cart' => esc_html__('In each category', 'woo-discount-rules'),
                'show_text' => esc_html__('Show', 'woo-discount-rules'),
                'hide_text' => esc_html__('Hide', 'woo-discount-rules'),
                'please_select_at_least_one_checkbox' => esc_html__('Please select at least one rule', 'woo-discount-rules'),
                'please_select_bulk_action' => esc_html__('Please select an action to apply', 'woo-discount-rules'),
                'are_you_sure_to_delete' => esc_html__('Are you sure to remove the selected rules', 'woo-discount-rules'),
            );
        }
    }
}