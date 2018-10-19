<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class FlycartWooDiscountRulesPriceProductDependent
 */
if (!class_exists('FlycartWooDiscountRulesPriceProductDependent')) {
    class FlycartWooDiscountRulesPriceProductDependent
    {
        /**
         * Get discount in each products
         * */
        public static function getDiscountInEachProducts($item, $rule, $checkRuleMatches, $productPage = 0, $for_product_id = 0){
            $discounts = array();
            $product_to_apply_count_option = isset($rule['product_based_condition']['product_to_apply_count_option'])? $rule['product_based_condition']['product_to_apply_count_option']: 'all';
            $product_to_apply_count = isset($rule['product_based_condition']['product_to_apply_count'])? $rule['product_based_condition']['product_to_apply_count']: '';
            $product_to_apply = isset($rule['product_based_condition']['product_to_apply'])? $rule['product_based_condition']['product_to_apply']: array();
            $discount_type = isset($rule['product_based_discount']['discount_type'])? $rule['product_based_discount']['discount_type']: 'percentage_discount';
            $discount_value = isset($rule['product_based_discount']['discount_value'])? $rule['product_based_discount']['discount_value']: 0;

            $product_discount_details = array();
            $product_discount_details['discount_type'] = $discount_type;
            $product_discount_details['discount_value'] = $discount_value;
            $product_discount_details['discount_quantity'] = 0;
            $product_discount_details['discount_price'] = '';

            if($discount_type == 'percentage_discount')
                $originalDiscount = array('percentage_discount' => $discount_value);
            else
                $originalDiscount = array('price_discount' => $discount_value);
            if($product_to_apply_count_option == 'all'){
                foreach ($product_to_apply as $product_id){
                    $discounts[$product_id] = $originalDiscount;
                }
            } else if($product_to_apply_count_option == 'skip_first'){
                $product_to_apply_count_to_apply = $product_to_apply_count;
                $cart = FlycartWoocommerceCart::get_cart();
                foreach ($cart as $cart_item_key => $values) {
                    $_product = $values['data'];
                    $quantity = $values['quantity'];
                    if($product_to_apply_count_to_apply > 0){
                        $product_id = FlycartWoocommerceProduct::get_id($_product);
                        if($productPage && $product_id == $for_product_id){
                            $quantity++;
                        }
                        if (in_array($product_id, $product_to_apply)){
                            if($product_to_apply_count_to_apply >= $quantity){
                                $discounts[$product_id] = array('percentage_discount' => 0);
                                $product_to_apply_count_to_apply -= $quantity;
                            } else if($product_to_apply_count_to_apply < $quantity){
                                $apply_for_only_quantity = $quantity - $product_to_apply_count_to_apply;
                                $price_discount = self::getDiscountForLimitedProductCount($_product, $discount_type, $discount_value, $quantity, $apply_for_only_quantity);
                                $price_discount_details = $product_discount_details;
                                $price_discount_details['discount_price'] = $price_discount['single_product_discount'];
                                $price_discount_details['discount_quantity'] = $apply_for_only_quantity;
                                $price_discount = $price_discount['discount'];
                                $discounts[$product_id] = array('price_discount' => $price_discount, 'product_discount_details' => $price_discount_details);
                                $product_to_apply_count_to_apply -= $quantity;
                            }
                        }
                    }
                }
                foreach ($product_to_apply as $product_id){
                    if(!isset($discounts[$product_id]))
                        $discounts[$product_id] = $originalDiscount;
                }
            } else {
                $product_to_apply_count_to_apply = $product_to_apply_count;
                $cart = FlycartWoocommerceCart::get_cart();
                foreach ($cart as $cart_item_key => $values) {
                    $_product = $values['data'];
                    $quantity = $values['quantity'];
                    if($product_to_apply_count_to_apply > 0){
                        $product_id = FlycartWoocommerceProduct::get_id($_product);
                        if (in_array($product_id, $product_to_apply)){
                            if($product_to_apply_count_to_apply > 0){
                                if($product_to_apply_count_to_apply >= $quantity){
                                    $discounts[$product_id] = $originalDiscount;
                                    $product_to_apply_count_to_apply -= $quantity;
                                } elseif ($product_to_apply_count_to_apply < $quantity){
                                    $apply_for_only_quantity = $product_to_apply_count_to_apply;
                                    $price_discount = self::getDiscountForLimitedProductCount($_product, $discount_type, $discount_value, $quantity, $apply_for_only_quantity);
                                    $price_discount_details = $product_discount_details;
                                    $price_discount_details['discount_price'] = $price_discount['single_product_discount'];
                                    $price_discount_details['discount_quantity'] = $apply_for_only_quantity;
                                    $price_discount = $price_discount['discount'];
                                    $discounts[$product_id] = array('price_discount' => $price_discount, 'product_discount_details' => $price_discount_details);
                                    $product_to_apply_count_to_apply = 0;
                                }
                            }
                        }
                    }
                }
                foreach ($product_to_apply as $product_id){
                    if(!isset($discounts[$product_id])){
                        if($product_to_apply_count_to_apply > 0)
                            $discounts[$product_id] = $originalDiscount;
                        else
                            $discounts[$product_id] = array('percentage_discount' => 0);
                    }
                }
            }

            return $discounts;
        }

        /**
         * Get cheapest product
         * */
        public static function getDiscountForLimitedProductCount($_product, $discount_type, $discount_value, $quantity, $discount_quantity = 1){
            $productPrice = FlycartWoocommerceProduct::get_price($_product);

            if($discount_type == 'percentage_discount')
                $discountPrice = $productPrice * ($discount_value / 100);
            else
                $discountPrice = $discount_value;
            if($discount_quantity > $quantity)
                $discount_price = $discountPrice - (($discountPrice/($quantity)) * ($quantity-$quantity));
            else
                $discount_price = $discountPrice - (($discountPrice/($quantity)) * ($quantity-$discount_quantity));
            return array('discount' => $discount_price, 'single_product_discount' => $discountPrice);
        }
    }
}