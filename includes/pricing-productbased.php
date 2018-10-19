<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * Class FlycartWooDiscountRulesPriceProductBased
 */
if (!class_exists('FlycartWooDiscountRulesPriceProductBased')) {
    class FlycartWooDiscountRulesPriceProductBased
    {
        /**
         * Verify Quantity
         * */
        public static function verifyQuantity($quantity_rule, $quantity, $quantity_from, $quantity_to, $buy_type){
            $quantityMatched = array();
            switch ($quantity_rule) {
                case 'less':
                    foreach($quantity as $quantityValue){
                        $quantityMatched[] = ($quantityValue <= $quantity_from) ? 1: 0;
                    }
                    break;
                case 'equal':
                    foreach($quantity as $quantityValue){
                        $quantityMatched[] = ($quantityValue == $quantity_from) ? 1: 0;
                    }
                    break;
                case 'from':
                    foreach($quantity as $quantityValue){
                        $quantityMatched[] = (($quantityValue >= $quantity_from) && ($quantityValue <= $quantity_to)) ? 1: 0;
                    }
                    break;
                case 'more':
                default:
                foreach($quantity as $quantityValue){
                    $quantityMatched[] = ($quantityValue >= $quantity_from) ? 1: 0;
                }
                break;
            }

            return FlycartWooDiscountRulesPriceProductBased::verifyBuyTypeWithQuantityMatched($buy_type, $quantityMatched);
        }

        /**
         * Verify Buy type with Quantity matched
         * */
        public static function verifyBuyTypeWithQuantityMatched($buy_type, $quantityMatched){
            $result = 0;
            if(!empty($quantityMatched)){
                switch ($buy_type) {
                    case 'combine':
                    case 'any':
                        if(in_array(1, $quantityMatched)){
                            $result = 1;
                        }
                        break;
                    case 'each':
                    default:
                        $result = (in_array(0, $quantityMatched)) ? 0 : 1;
                        break;
                }
            }

            return $result;
        }

        /**
         * Adjust Quantity
         * */
        public static function adjustQuantity($buy_type, $quantity_by_products){
            switch ($buy_type) {
                case 'combine':
                    $quantityCount = 0;
                    foreach($quantity_by_products as $quantity_by_product){
                        $quantityCount += $quantity_by_product;
                    }
                    $quantity = array($quantityCount);
                    break;
                case 'any':
                case 'each':
                default:
                    $quantity = $quantity_by_products;
                    break;
            }
            return $quantity;
        }
    }
}