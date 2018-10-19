<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class FlycartWooDiscountRulesCartTotals
 */
if (!class_exists('FlycartWooDiscountRulesCartTotals')) {
    class FlycartWooDiscountRulesCartTotals
    {
        protected $items = array();
        protected $calculate_tax = true;

        /**
         * Subtotals are costs before discounts.
         *
         * To prevent rounding issues we need to work with the inclusive price where possible.
         * otherwise we'll see errors such as when working with a 9.99 inc price, 20% VAT which would.
         * be 8.325 leading to totals being 1p off.
         *
         * Pre tax coupons come off the price the customer thinks they are paying - tax is calculated.
         * afterwards.
         *
         * e.g. $100 bike with $10 coupon = customer pays $90 and tax worked backwards from that.
         *
         * @since 3.2.0
         */
        public function calculate_item_subtotals() {
            $this->cart = WC()->cart;
            if(method_exists($this->cart, 'get_customer')){
                $customer = $this->cart->get_customer();
            } else {
                $customer = WC()->customer;
            }
            $this->calculate_tax = wc_tax_enabled() && ! $customer->get_is_vat_exempt();
            $this->get_items_from_cart();
            foreach ( $this->items as $item_key => $item ) {
                if ( $item->price_includes_tax ) {
                    if ( $customer->get_is_vat_exempt() ) {
                        $item = $this->remove_item_base_taxes( $item );
                    } elseif ( apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
                        $item = $this->adjust_non_base_location_price( $item );
                    }
                }

                $item->subtotal = $item->price;
                $subtotal_taxes = array();

                if ($this->calculate_tax && $item->product->is_taxable() ) {
                    $subtotal_taxes     = WC_Tax::calc_tax( $item->subtotal, $item->tax_rates, $item->price_includes_tax );
                    $item->subtotal_tax = array_sum( array_map( array( $this, 'round_line_tax' ), $subtotal_taxes ) );

                    if ( $item->price_includes_tax ) {
                        // Use unrounded taxes so we can re-calculate from the orders screen accurately later.
                        $item->subtotal = $item->subtotal - array_sum( $subtotal_taxes );
                    }
                }
            }
            $item_subtotal =  array_sum( array_map( 'round', array_values( wp_list_pluck( $this->items, 'subtotal' ) ) ) );
            $items_subtotal_tax =  array_sum( array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) );
            $subtotal = $item_subtotal;
            if(get_option('woocommerce_tax_display_cart', 'incl') == 'incl'){
                $subtotal = $item_subtotal + $items_subtotal_tax;
            }
            if(function_exists('wc_remove_number_precision_deep')){
                return wc_remove_number_precision_deep( $subtotal );
            } else {
                return $subtotal;
            }
        }
        /**
         * Ran to remove all base taxes from an item. Used when prices include tax, and the customer is tax exempt.
         *
         * @since 3.2.2
         * @param object $item Item to adjust the prices of.
         * @return object
         */
        protected function remove_item_base_taxes( $item ) {
            if ( $item->price_includes_tax && $item->taxable ) {
                $base_tax_rates           = WC_Tax::get_base_tax_rates( $item->product->get_tax_class( 'unfiltered' ) );

                // Work out a new base price without the shop's base tax.
                $taxes                    = WC_Tax::calc_tax( $item->price, $base_tax_rates, true );

                // Now we have a new item price (excluding TAX).
                $item->price              = round( $item->price - array_sum( $taxes ) );
                $item->price_includes_tax = false;
            }
            return $item;
        }

        /**
         * Only ran if woocommerce_adjust_non_base_location_prices is true.
         *
         * If the customer is outside of the base location, this removes the base
         * taxes. This is off by default unless the filter is used.
         *
         * Uses edit context so unfiltered tax class is returned.
         *
         * @since 3.2.0
         * @param object $item Item to adjust the prices of.
         * @return object
         */
        protected function adjust_non_base_location_price( $item ) {
            if ( $item->price_includes_tax && $item->taxable ) {
                $base_tax_rates = WC_Tax::get_base_tax_rates( $item->product->get_tax_class( 'unfiltered' ) );

                if ( $item->tax_rates !== $base_tax_rates ) {
                    // Work out a new base price without the shop's base tax.
                    $taxes       = WC_Tax::calc_tax( $item->price, $base_tax_rates, true );
                    $new_taxes   = WC_Tax::calc_tax( $item->price - array_sum( $taxes ), $item->tax_rates, false );

                    // Now we have a new item price.
                    $item->price = round( $item->price - array_sum( $taxes ) + array_sum( $new_taxes ) );
                }
            }
            return $item;
        }

        /**
         * Should we round at subtotal level only?
         *
         * @return bool
         */
        protected function round_at_subtotal() {
            return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
        }

        /**
         * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
         *
         * @since  3.2.6
         * @param  float $value Tax value.
         * @return float
         */
        protected function round_line_tax( $value ) {
            if ( ! $this->round_at_subtotal() ) {
                $value = wc_round_tax_total( $value, 0 );
            }
            return $value;
        }

        /**
         * Handles a cart or order object passed in for calculation. Normalises data
         * into the same format for use by this class.
         *
         * Each item is made up of the following props, in addition to those returned by get_default_item_props() for totals.
         * 	- key: An identifier for the item (cart item key or line item ID).
         *  - cart_item: For carts, the cart item from the cart which may include custom data.
         *  - quantity: The qty for this line.
         *  - price: The line price in cents.
         *  - product: The product object this cart item is for.
         *
         * @since 3.2.0
         */
        protected function get_items_from_cart() {
            $this->items = array();

            foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                if(function_exists('wc_add_number_precision_deep')){
                    $price = wc_add_number_precision_deep( $cart_item['data']->get_price() * $cart_item['quantity'] );
                } else {
                    $price = $cart_item['data']->get_price() * $cart_item['quantity'];
                }
                $item                          = $this->get_default_item_props();
                $item->key                     = $cart_item_key;
                $item->object                  = $cart_item;
                $item->tax_class               = $cart_item['data']->get_tax_class();
                $item->taxable                 = 'taxable' === $cart_item['data']->get_tax_status();
                $item->price_includes_tax      = wc_prices_include_tax();
                $item->quantity                = $cart_item['quantity'];
                $item->price                   = $price;
                $item->product                 = $cart_item['data'];
                $item->tax_rates               = $this->get_item_tax_rates( $item );
                $this->items[ $cart_item_key ] = $item;
            }
        }

        /**
         * Get default blank set of props used per item.
         *
         * @since  3.2.0
         * @return array
         */
        protected function get_default_item_props() {
            return (object) array(
                'object'             => null,
                'tax_class'          => '',
                'taxable'            => false,
                'quantity'           => 0,
                'product'            => false,
                'price_includes_tax' => false,
                'subtotal'           => 0,
                'subtotal_tax'       => 0,
                'total'              => 0,
                'total_tax'          => 0,
                'taxes'              => array(),
            );
        }

        /**
         * Get tax rates for an item. Caches rates in class to avoid multiple look ups.
         *
         * @param  object $item Item to get tax rates for.
         * @return array of taxes
         */
        protected function get_item_tax_rates( $item ) {
            $tax_class = $item->product->get_tax_class();
            if(method_exists($this->cart, 'get_customer')){
                return isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class(), $this->cart->get_customer() );
            } else {
                return isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class(), WC()->customer );
            }

        }
    }
}