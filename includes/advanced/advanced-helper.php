<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class FlycartWooDiscountRulesAdvancedHelper
 */
if (!class_exists('FlycartWooDiscountRulesAdvancedHelper')) {
    class FlycartWooDiscountRulesAdvancedHelper
    {
        /**
         * Get all hierarchical taxonomy terms
         *
         * @access public
         * @param string $taxonomy
         * @param array $ids
         * @param string $query
         * @return array
         */
        public static function get_all_hierarchical_taxonomy_terms($taxonomy, $ids = array(), $query = '')
        {
            $items = array();

            // Get terms
            $terms = get_terms(array($taxonomy), array('hide_empty' => 0));
            if(!empty($terms)){

                // Iterate over terms
                foreach ($terms as $term_key => $term) {
                    if(isset($term->name)){
                        $term_count = count($terms);
                        // Get term name
                        $term_name = $term->name;
                        $term_slug = $term->slug;

                        // Term has parent
                        if ($term->parent) {

                            $parent_id = $term->parent;
                            $has_parent = true;

                            // Make sure we don't have an infinite loop here (happens with some kind of "ghost" terms)
                            $found = false;
                            $i = 0;

                            while ($has_parent && ($i < $term_count || $found)) {

                                // Reset each time
                                $found = false;
                                $i = 0;

                                // Iterate over terms again
                                foreach ($terms as $parent_term_key => $parent_term) {

                                    $i++;

                                    if ($parent_term->term_id == $parent_id) {

                                        $term_name = $parent_term->name . ' → ' . $term_name;
                                        $found = true;

                                        if ($parent_term->parent) {
                                            $parent_id = $parent_term->parent;
                                        }
                                        else {
                                            $has_parent = false;
                                        }

                                        break;
                                    }
                                }
                            }
                        }

                        // Get term id
                        $term_id = (string) $term->term_id;

                        // Skip this item if we don't need it
                        if (!empty($ids) && !in_array($term_id, $ids, true)) {
                            continue;
                        }

                        // Add item
                        $items[] = array(
                            'id'    => $term_id,
                            'text'  => $term_name,
                            'slug'  => $term_slug
                        );
                    }
                }
            }

            return $items;
        }

        /**
         * Get all product attributes based on criteria
         *
         * @access public
         * @param array $ids
         * @param string $query
         * @return array
         */
        public static function get_all_product_attributes($ids = array(), $query = '')
        {
            global $wc_product_attributes;

            $items = array();

            // Iterate over product attributes
            foreach ($wc_product_attributes as $attribute_key => $attribute) {

                // Get attribute name
                $attribute_name = !empty($attribute->attribute_label) ? $attribute->attribute_label : $attribute->attribute_name;

                // Get terms for this attribute
                $terms = self::get_all_hierarchical_taxonomy_terms($attribute_key, $ids, $query);

                // Iterate over subitems and make a list of item/subitem pairs
                foreach ($terms as $term) {
                    $items[] = array(
                        'id'    => $term['id'],
                        'text'  => $attribute_name . ': ' . $term['text'],
                    );
                }
            }

            return $items;
        }

        /**
         * Validate the cart item has the attributes selected
         *
         * @param array $variations
         * @param array $selectedAttributes
         * @return array
         * */
        public static function validateCartItemInSelectedAttributes($variations, $selectedAttributes, $all_attr = false){
            $attributeMatches = 0;
            if(!empty($variations)){
                if($all_attr){
                    $available_attributes = array();
                    foreach ($variations as $key =>  $variation) {
                        $name = substr($key, 10);//Remove attribute_
                        $terms = self::get_all_hierarchical_taxonomy_terms($name);
                        if(!empty($terms)){
                            foreach ($terms as $term) {
                                if(strtolower($term['slug']) === strtolower($variation)){
                                    $available_attributes[] = $term['id'];
                                }
                            }
                        }
                    }
                    if(!empty($available_attributes)){
                        foreach ($selectedAttributes as $attribute_list_item){
                            if(!in_array($attribute_list_item, $available_attributes)){
                                $attributeMatches = 0;
                                break;
                            }
                            $attributeMatches = 1;
                        }
                    }
                } else {
                    foreach ($variations as $key =>  $variation) {
                        $name = substr($key, 10);//Remove attribute_
                        $terms = self::get_all_hierarchical_taxonomy_terms($name);
                        if(!empty($terms)){
                            foreach ($terms as $term) {
                                if(strtolower($term['slug']) === strtolower($variation)){
                                    if(in_array($term['id'], $selectedAttributes)){
                                        $attributeMatches = 1;
                                        break;
                                    }
                                }
                            }
                            if($attributeMatches){
                                break;
                            }
                        }
                    }
                }
            }

            return $attributeMatches;
        }

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
        public static function get_calculated_item_subtotal() {
            $cart_total = new FlycartWooDiscountRulesCartTotals();
            $total = $cart_total->calculate_item_subtotals();
            return $total;
        }

    }
}