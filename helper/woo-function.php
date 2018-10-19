<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FlycartWoocommerceVersion')){
    class FlycartWoocommerceVersion{
        /**
         * @param $version
         * @return bool|mixed
         */
        public static function wcVersion($version)
        {
            if (defined('WC_VERSION') && WC_VERSION) {
                return version_compare(WC_VERSION, $version, '>=');
            } else if (defined('WOOCOMMERCE_VERSION') && WOOCOMMERCE_VERSION) {
                return version_compare(WOOCOMMERCE_VERSION, $version, '>=');
            } else {
                return false;
            }
        }

        /**
         * backwardCompatibilityStringToArray
         * @param $data
         * @return array
         * */
        public static function backwardCompatibilityStringToArray($data)
        {
            if(!empty($data) && !is_array($data)) $data = explode(',', $data);

            return $data;
        }

        /**
         * Is WooCommerce version 3x
         * @return boolean
         * */
        public static function isWCVersion3x(){
            return self::wcVersion('3.0');
        }
    }
}

if(!class_exists('FlycartWoocommerceProduct')){
    class FlycartWoocommerceProduct{
        /**
         * Get WooCommerce product
         *
         * @access public
         * @param int $product_id
         * @return object
         */
        public static function wc_get_product($product_id)
        {
            return FlycartWoocommerceVersion::wcVersion('2.2') ? wc_get_product($product_id) : get_product($product_id);
        }

        /**
         * Get WooCommerce product format name
         *
         * @access public
         * @param array $product
         * @return int
         */
        public static function get_formatted_name($product)
        {
            if(FlycartWoocommerceVersion::wcVersion('3.0') || method_exists($product , 'get_formatted_name')){
                return $product->get_formatted_name();
            } else {
                $post_id = self::get_id($product);
                return '#' . $post_id . ' ' . get_the_title($post_id);
            }
        }

        /**
         * Get WooCommerce product id
         *
         * @access public
         * @param array $product
         * @return int
         */
        public static function get_id($product)
        {
            if(FlycartWoocommerceVersion::wcVersion('3.0') || method_exists($product , 'get_id')){
                return $product->get_id();
            } else {
                $_product_id = $product->id;
                if(isset($product->variation_id)) $_product_id = $product->variation_id;
                return $_product_id;
            }
        }

        /**
         * Get WooCommerce price
         *
         * @access public
         * @param float $amount
         * @return int/float
         */
        public static function wc_price($amount){
            return FlycartWoocommerceVersion::wcVersion('2.1') ? wc_price($amount) : woocommerce_price($amount);
        }

        /**
         * Get WooCommerce price html
         *
         * @access public
         * @param array $product
         * @return int/float
         */
        public static function get_price_html($product){
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $product->get_price_html() : $product->get_price_html();
        }

        //get_price_html

        /**
         * Get WooCommerce product parent id
         *
         * @access public
         * @param array $product
         * @return int
         */
        public static function get_parent_id($product){
            if(FlycartWoocommerceVersion::wcVersion('3.0') || method_exists($product, 'get_parent_id')){
                return $product->get_parent_id();
            } else {
                if(isset($product->parent) && !empty($product->parent)){
                    if(isset($product->parent->id)){
                        return $product->parent->id;
                    }
                }
                return $product->parent_id;
            }
        }

        /**
         * Get WooCommerce product children
         *
         * @access public
         * @param array $product
         * @return array
         */
        public static function get_children($product){
            if(FlycartWoocommerceVersion::wcVersion('3.0') || method_exists($product, 'get_children')){
                return $product->get_children();
            } else {
                if(isset($product->children)){
                    return $product->children;
                }
                return '';
            }
        }

        /**
         * Get WooCommerce product price
         *
         * @access public
         * @param array $product
         * @return int/float
         */
        public static function get_price($product){
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $product->get_price() : $product->price;
        }

        /**
         * Get WooCommerce product regular price
         *
         * @access public
         * @param array $product
         * @return int/float
         */
        public static function get_regular_price($product){
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $product->get_regular_price() : $product->regular_price;
        }

        /**
         * Get WooCommerce product sale price
         *
         * @access public
         * @param array $product
         * @return int/float
         */
        public static function get_sale_price($product){
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $product->get_sale_price() : $product->sale_price;
        }

        /**
         * Set WooCommerce product price
         *
         * @access public
         * @param array $product
         */
        public static function set_price($product, $amount){
            if(FlycartWoocommerceVersion::wcVersion('3.0')){
                $product->set_price($amount);
            } else {
                $product->price = $amount;
            }
        }

        /**
         * Get product price including tax
         *
         * @access public
         * @param object $product
         * @param int $quantity
         * @param float $price
         * @return int
         */
        public static function get_price_including_tax($product, $quantity = 1, $price = '')
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? wc_get_price_including_tax($product, array('qty' => $quantity, 'price' => $price)) : $product->get_price_including_tax($quantity, $price);
        }

        /**
         * Get product price excluding tax
         *
         * @access public
         * @param object $product
         * @param int $quantity
         * @param float $price
         * @return float
         */
        public static function get_price_excluding_tax($product, $quantity = 1, $price = '')
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? wc_get_price_excluding_tax($product, array('qty' => $quantity, 'price' => $price)) : $product->get_price_excluding_tax($quantity, $price);
        }

        //wc_get_price_excluding_tax

        /**
         * Get WooCommerce product title
         *
         * @access public
         * @param array $product
         * @return string
         */
        public static function get_title($product){
            if(FlycartWoocommerceVersion::wcVersion('3.0') || method_exists($product , 'get_title')){
                return $product->get_title();
            } else if(isset($product->title)){
                return $product->title;
            } else {
                if(isset($product->post->post_title))
                    return $product->post->post_title;
            }
            return '';
        }

        /**
         * Get WooCommerce product get_permalink
         *
         * @access public
         * @param array $product
         * @return string
         */
        public static function get_permalink($product){
            if(FlycartWoocommerceVersion::wcVersion('3.0') || method_exists($product , 'get_permalink')){
                return $product->get_permalink();
            } else if(isset($product->get_permalink) && $product->get_permalink != ''){
                return $product->get_permalink;
            } else {
                return get_permalink(self::get_id($product));
            }
        }

        /**
         * Get WooCommerce product attributes
         *
         * @access public
         * @param array $product
         * @return array
         */
        public static function get_attributes($product){
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $product->get_attributes() : $product->attributes;
        }

        /**
         * Get WooCommerce get_variant_ids
         *
         * @access public
         * @param int $product_id
         * @return array
         */
        public static function get_variant_ids($product_id)
        {
            $ids = array();
            $productV = new WC_Product_Variable( $product_id );
            $variations = $productV->get_available_variations();
            if(!empty($variations))
                foreach ($variations as $variation) {
                    $ids[] = $variation['variation_id'];
                }
            return $ids;
        }

        /**
         * Get WooCommerce get_category_ids
         *
         * @access public
         * @param object $product
         * @return array
         */
        public static function get_category_ids($product)
        {
            $parent = self::get_parent_id($product);
            if($parent) $product = self::wc_get_product($parent);
            $cat_id = array();
            if(FlycartWoocommerceVersion::wcVersion('3.0') || method_exists($product, 'get_category_ids')){
                $cat_id = $product->get_category_ids();
                $cat_id = apply_filters('woo_discount_rules_load_additional_taxonomy', $cat_id, self::get_id($product));
            } else {
                $terms = get_the_terms ( self::get_id($product), 'product_cat' );
                if(!empty($terms))
                    foreach ( $terms as $term ) {
                        $cat_id[] = $term->term_id;
                    }
            }
            return $cat_id;
        }

        /**
         * Get category by id
         *
         * @access public
         * @param int $category_id
         * @return string
         */
        public static function get_product_category_by_id( $category_id ) {
            $term_name = '';
            $taxonomies = apply_filters('woo_discount_rules_accepted_taxonomy_for_category', array('product_cat'));
            foreach ($taxonomies as $taxonomy){
                $term = get_term_by( 'id', $category_id, $taxonomy, 'ARRAY_A' );
                if(!empty($term['name'])){
                    $term_name = $term['name'];
                    break;
                }
            }

            return $term_name;
        }

        /*
         * Get WooCommerce get product select box
         *
         * @access public
         * @param object $products_list
         * @param string $name
         * @return string
         * */
        public static function getProductAjaxSelectBox($products_list, $name){
            $html = '';
            if(FlycartWoocommerceVersion::wcVersion('3.0')){
                $html .= '<select class="wc-product-search" style="min-width: 250px" multiple="multiple" name="'.$name.'[]" data-placeholder="'.esc_attr__( 'Search for a product&hellip;', 'woocommerce' ).'" data-action="woocommerce_json_search_products_and_variations">';
                if(!empty($products_list) && count($products_list))
                    foreach ( $products_list as $product_id ) {
                        $product = self::wc_get_product($product_id);
                        if ( is_object( $product ) ) {
                            $html .= '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( self::get_formatted_name($product) ) . '</option>';
                        }
                    }
                $html .= '</select>';
            } else {
                $html .= '<input type="hidden" class="wc-product-search" style="width: 250px" data-multiple="true" name="'.$name.'" data-placeholder="'.esc_attr__( 'Search for a product&hellip;', 'woocommerce' ).'" data-action="woocommerce_json_search_products_and_variations" data-selected="';
                $json_ids = array();
                if(!empty($products_list)){
                    if(!is_array($products_list)) $products_list = explode(',', $products_list);
                    if(!empty($products_list) && count($products_list)){
                        foreach ( $products_list as $product_id ) {
                            $product = self::wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                $json_ids[ $product_id ] = wp_kses_post( self::get_formatted_name($product) );
                            }
                        }
                        $html .= esc_attr( json_encode( $json_ids ) );
                    }
                }


                $html .= '" value="'.implode( ',', array_keys( $json_ids ) ).'" /> ';
            }

            return $html;
        }

        /*
         * Get WooCommerce get product select box
         *
         * @access public
         * @param object $users_list
         * @param string $name
         * @return string
         * */
        public static function getUserAjaxSelectBox($users_list, $name){
            $html = '';
            if(FlycartWoocommerceVersion::wcVersion('3.0')){
                $html .= '<select class="wc-customer-search" style="width: 250px" multiple="multiple" name="'.$name.'[]" data-placeholder="'.esc_attr__( 'Search for a user&hellip;', 'woocommerce' ).'" data-allow_clear="true">';

                if(!empty($users_list) && count($users_list))
                    foreach ( $users_list as $user_id ) {
                        $user = get_userdata($user_id);
                        if ( is_object( $user ) ) {
                            $formattedName = $user->user_firstname.' '.$user->user_lastname.' (#'.$user->ID.' - '. $user->user_email.')';
                            $html .= '<option value="' . esc_attr( $user_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post($formattedName) . '</option>';
                        }
                    }
                $html .= '</select>';
            } else {
                $html .= '<input type="hidden" class="wc-customer-search" style="width: 250px" data-multiple="true" name="'.$name.'" data-placeholder="'.esc_attr__( 'Search for a user&hellip;', 'woocommerce' ).'" data-allow_clear="true" data-selected="';
                $json_ids = array();
                if(!empty($users_list)){
                    if(!is_array($users_list)) $users_list = explode(',', $users_list);
                    if(!empty($users_list) && count($users_list)){
                        foreach ( $users_list as $user_id ) {
                            $user = get_userdata($user_id);
                            if ( is_object( $user ) ) {
                                $formattedName = $user->user_firstname.' '.$user->user_lastname.' (#'.$user->ID.' - '. $user->user_email.')';
                                $json_ids[ $user_id ] = wp_kses_post( $formattedName );
                            }
                        }
                        $html .= esc_attr( json_encode( $json_ids ) );
                    }
                }


                $html .= '" value="'.implode( ',', array_keys( $json_ids ) ).'" /> ';
            }

            return $html;
        }
    }
}

if(!class_exists('FlycartWoocommerceCartProduct')){
    class FlycartWoocommerceCart{
        /**
         * Get cart
         *
         * @access public
         * @return array
         */
        public static function get_cart()
        {
            if(!empty(WC()->cart)){
                return WC()->cart->get_cart();
            }
            return array();
        }

        /**
         * Remove cart item
         *
         * @access public
         * @return boolean
         */
        public static function remove_cart_item($_cart_item_key)
        {
            return WC()->cart->remove_cart_item( $_cart_item_key );
        }

        /**
         * Add cart item
         *
         * @access public
         * @param int $product_id
         * @param int $quantity
         * @param int $variation_id
         * @param array $variation
         * @param array $cart_item_data
         * @return boolean
         */
        public static function add_to_cart($product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array())
        {
            if(FlycartWoocommerceVersion::wcVersion('3.0')){
                return WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data);
            } else {
                ob_start();
                $addToCart = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data);
                ob_end_flush();
                return $addToCart;
            }
        }

        /**
         * set quantity
         *
         * @access public
         * @param string $cart_item_key
         * @param int $quantity
         * @param boolean $refresh_totals
         * @return boolean
         */
        public static function set_quantity( $cart_item_key, $quantity = 1, $refresh_totals = true ){
            return WC()->cart->set_quantity($cart_item_key, $quantity, $refresh_totals);
        }
    }
}

if(!class_exists('FlycartWoocommerceOrder')){
    class FlycartWoocommerceOrder{
        /**
         * Get order id
         *
         * @access public
         * @param object $order
         * @return int
         */
        public static function get_id($order)
        {
            FlycartWoocommerceVersion::wcVersion('3.0') ? $order->get_id() : $order->id;
        }

        /**
         * Get WooCommerce order
         *
         * @access public
         * @param int $order_id
         * @return object
         */
        public static function wc_get_order($order_id)
        {
            return FlycartWoocommerceVersion::wcVersion('2.2') ? wc_get_order($order_id) : new WC_Order($order_id);
        }

        /**
         * Get order total
         *
         * @access public
         * @param object $order
         * @return float
         */
        public static function get_total($order)
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $order->get_total() : $order->order_total;
        }

        /**
         * Get order billing_email
         *
         * @access public
         * @param object $order
         * @return float
         */
        public static function get_billing_email($order)
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $order->get_billing_email() : $order->billing_email;
        }

        /**
         * Get order billing city
         *
         * @access public
         * @param object $order
         * @return float
         */
        public static function get_billing_city($order)
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $order->get_billing_city() : $order->billing_city;
        }

        /**
         * Get order shipping state
         *
         * @access public
         * @param object $order
         * @return float
         */
        public static function get_shipping_state($order)
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $order->get_shipping_state() : $order->shipping_state;
        }

        /**
         * Get order shipping city
         *
         * @access public
         * @param object $order
         * @return float
         */
        public static function get_shipping_city($order)
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $order->get_shipping_city() : $order->shipping_city;
        }

        /**
         * Get order product ids
         *
         * @access public
         * @param object $order
         * @return array
         */
        public static function get_product_ids($order)
        {
            $items = $order->get_items();
            $productIds = array();
            if(!empty($items)){
                foreach ($items as $item){
                    $product_id = FlycartWoocommerceVersion::wcVersion('3.0') ? $item->get_product_id() : $item['product_id'];
                    $variant_id = FlycartWoocommerceVersion::wcVersion('3.0') ? $item->get_variation_id() : $item['variation_id'];
                    if($variant_id){
                        $productIds[] = $variant_id;
                    } else {
                        $productIds[] = $product_id;
                    }
                }
            }

            return $productIds;
        }
    }
}

if(!class_exists('FlycartWoocommerceCoupon')){
    class FlycartWoocommerceCoupon{

        /**
         * Get WooCommerce Coupon
         *
         * @access public
         * @param int $coupon_code
         * @return object
         */
        public static function wc_get_coupon($coupon_code)
        {
            return new WC_Coupon($coupon_code);
        }

        /**
         * Get Coupon individual_use
         *
         * @access public
         * @param object $coupon
         * @return boolean
         */
        public static function get_individual_use($coupon)
        {
            return FlycartWoocommerceVersion::wcVersion('3.0') ? $coupon->get_individual_use() : $coupon->individual_use;
        }
    }
}