<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    function woodiscountfree_shipping_method() {
        if ( ! class_exists( 'WooDiscountFree_Shipping_Method' ) ) {
            class WooDiscountFree_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 */
                public function __construct() {
                    $this->id                 = 'woodiscountfree';
                    $this->method_title       = __( 'WooDiscount Free Shipping', 'woo-discount-rules' );
                    $this->method_description = __( 'Custom Shipping Method for Woocommerce Discount Rules', 'woo-discount-rules' );
                    $this->init();

                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    global $flycart_woo_discount_rules;
                    //$this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Free Shipping', 'woo-discount-rules' );
                    $title = $flycart_woo_discount_rules->discountBase->getConfigData('free_shipping_text', 'Free Shipping');
                    if(empty($title)) $title = 'Free Shipping';
                    $this->title = __( $title, 'woo-discount-rules' );
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields();
                    $this->init_settings();

                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                    add_filter( 'woocommerce_shipping_'.$this->id.'_is_available', array($this, 'woodiscount_hide_shipping_when_is_available'), 100 );
                }

                function woodiscount_hide_shipping_when_is_available(){
                    $discountBase = new FlycartWooDiscountBase();
                    $discountBase->handleCartDiscount(1);
                    if($discountBase->has_free_shipping){
                        return true;
                    } else {
                        return false;
                    }
                }

                /**
                 * Does this method have a settings page?
                 * @return bool
                 */
                public function has_settings() {
                    return false; //$this->instance_id ? $this->supports( 'instance-settings' ) : $this->supports( 'settings' );
                }

                /**
                 * Define settings field for this shipping
                 * @return void
                 */
                function init_form_fields() {
                    global $flycart_woo_discount_rules;
                    $title = $flycart_woo_discount_rules->discountBase->getConfigData('free_shipping_text', __( 'Free Shipping', 'woo-discount-rules' ));
                    if(empty($title)) $title = 'Free Shipping';
                    $this->form_fields = array(

                        'enabled' => array(
                            'title' => __( 'Enable', 'woo-discount-rules' ),
                            'type' => 'checkbox',
                            'description' => __( 'Enable this shipping.', 'woo-discount-rules' ),
                            'default' => 'yes'
                        ),

                        'title' => array(
                            'title' => __( 'Title', 'woo-discount-rules' ),
                            'type' => 'text',
                            'description' => __( 'Title to be display on site', 'woo-discount-rules' ),
                            'default' => __( $title, 'woo-discount-rules' ),
                        )
                    );

                }

                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {

                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => 0,
                        'taxes'   => false,
			            'package' => $package
                    );

                    $this->add_rate( $rate );

                }
            }
        }
    }
    $enable_free_shipping = $flycart_woo_discount_rules->discountBase->getConfigData('enable_free_shipping', "none");
    if($enable_free_shipping == "woodiscountfree"){
        add_action( 'woocommerce_shipping_init', 'woodiscountfree_shipping_method' );

        function add_woodiscountfree_shipping_method( $methods ) {
            $methods[] = 'WooDiscountFree_Shipping_Method';
            return $methods;
        }

        add_filter( 'woocommerce_shipping_methods', 'add_woodiscountfree_shipping_method' );

    } else if($enable_free_shipping == "free_shipping"){
        function woodiscount_hide_free_shipping_when_is_available(){
            $discountBase = new FlycartWooDiscountBase();
            $discountBase->handleCartDiscount(1);
            if($discountBase->has_free_shipping){
                return true;
            } else {
                return false;
            }
        }
        add_filter( 'woocommerce_shipping_free_shipping_is_available', 'woodiscount_hide_free_shipping_when_is_available');
    }

    if($enable_free_shipping != "none"){
        function reset_default_shipping_method_woo_discount( $method, $available_methods ) {
            if(!empty($available_methods) && is_array($available_methods)) {
                $shipping_methods = array_keys($available_methods);
                if(!empty($shipping_methods)){
                    foreach ($shipping_methods as $key => $shipping_method) {
                        if (strpos($shipping_method, 'free_shipping') === 0) {
                            $method = $shipping_method;
                        }
                    }
                    if(in_array('woodiscountfree', $shipping_methods)) $method = 'woodiscountfree';
                }
            }

            return $method;
        }

        add_filter('woocommerce_shipping_chosen_method', 'reset_default_shipping_method_woo_discount', 100, 2);
    }
}