<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * Class FlycartWooDiscountRulesPurchase
 */
if ( ! class_exists( 'FlycartWooDiscountRulesPurchase' ) ) {
    class FlycartWooDiscountRulesPurchase
    {

        /**
         * @var
         */
        private $user;
        private $slug='woo-discount-rules';
        private $force_license_check=false;

        public $pluginName;
        public $siteURL = 'https://www.flycart.org/';

        /**
         * FlycartWooDiscountRulesPurchase constructor.
         */
        public function __construct()
        {
            $this->pluginName = esc_html__('Woo Discount Rules', 'woo-discount-rules').' '.$this->getProText();
        }

        public function init()
        {

            $force_update = false;
            // If a plugin has both a valid license and is using a CORE version then force update
            if ( $this->isPro() === false && $this->validateLicenseKey() ) {
                // can upgrade to PRO
                $force_update = true;
            }

            $option_exists = (get_option($this->slug.'-force-update-pro', null) !== null);

            if($option_exists){
                update_option($this->slug.'-force-update-pro', $force_update);
            }
            else {
                add_option($this->slug.'-force-update-pro', $force_update);
            }
        }

        public function errorNoticeInAdminPages(){
            $pro = $this->isPro();
            $base = new FlycartWooDiscountBase();
            $config = $base->getBaseConfig();
            if (is_string($config)) $config = json_decode($config, true);
            $htmlPrefix = '<div class="notice notice-warning"><p>';
            $htmlSuffix = '</p></div>';
            $hasMessage = 0;
            if($pro){
                if ( isset( $config['license_key'] ) && !empty($config['license_key']) ) {
                    $verifiedLicense = get_option('woo_discount_rules_verified_key', 0);
                    if(!$verifiedLicense){
                        $msg = esc_html__('The license key for ', 'woo-discount-rules').$this->pluginName.' '.esc_html__('seems invalid.', 'woo-discount-rules').' <a href="admin.php?page=woo_discount_rules&tab=settings">'.esc_html__('Please enter a valid license key', 'woo-discount-rules').'</a>. '.esc_html__('You can get it from', 'woo-discount-rules').' <a href="'.$this->siteURL.'">'.esc_html__('our website', 'woo-discount-rules').'</a>.';
                        $hasMessage = 1;
                    }
                } else {
                    $msg = esc_html__('License key for the ', 'woo-discount-rules').$this->pluginName.' '.esc_html__('is not entered.', 'woo-discount-rules').' <a href="admin.php?page=woo_discount_rules&tab=settings">'.esc_html__('Please enter a valid license key', 'woo-discount-rules').'</a>. '.esc_html__('You can get it from', 'woo-discount-rules').' <a href="'.$this->siteURL.'">'.esc_html__('our website', 'woo-discount-rules').'</a>.';
                    $hasMessage = 1;
                }
            } else {
                if ( isset( $config['license_key'] ) && !empty($config['license_key']) ) {
                    $verifiedLicense = get_option('woo_discount_rules_verified_key', 0);
                    if($verifiedLicense){
                        $msg = $this->pluginName.esc_html__(': You are using CORE version. Please Update to PRO version.', 'woo-discount-rules');
                        $hasMessage = 1;
                    }
                }
            }
            if($hasMessage){
                echo $htmlPrefix.$msg.$htmlSuffix;
            }
        }


        /**
         * @return bool
         */
        public function isPro()
        {
            return true;
        }

        /**
         * @return string
         */
        public function getSuffix()
        {
            return esc_html__('-PRO-', 'woo-discount-rules');
        }

        /**
         * @return string
         */
        public function getProText()
        {
            if($this->isPro()){
                return esc_html__('Pro', 'woo-discount-rules');
            } else {
                return esc_html__('Core', 'woo-discount-rules');
            }
        }

        /**
         * Hook to check and display updates below plugin in Admin Plugins section
         * This plugin checks for license key validation and displays error notices
         * @param string $plugin_file our plugin file
         * @param string $plugin_data Plugin details
         * @param string $status
         * */
        function woodisc_after_plugin_row (  $plugin_file, $plugin_data, $status ){
            if( isset($plugin_data['TextDomain']) && $plugin_data['TextDomain'] == 'woo-discount-rules' ){
                if ( ! $this->isPro() ) {
                    return;
                }
                
                // TODO: based on plugin_data display the update info text too. We can also make the subscription expiry chk in same request 
                // and display just the notice as a warning

                $base = new FlycartWooDiscountBase();
                $config = $base->getBaseConfig();
                if (is_string($config)) $config = json_decode($config, true);

                if ( isset( $config['license_key'] ) && !empty($config['license_key']) ) {
                    if (!$this->validateLicenseKey()) {
                        $message = esc_html__('The license key for ', 'woo-discount-rules').$this->pluginName.' '.esc_html__('seems invalid.', 'woo-discount-rules').' <a href="admin.php?page=woo_discount_rules&tab=settings">'.esc_html__('Please enter a valid license key', 'woo-discount-rules').'</a>. '.esc_html__('You can get it from', 'woo-discount-rules').' <a href="'.$this->siteURL.'">'.esc_html__('our website', 'woo-discount-rules').'</a>.';
                    }
                }else{
                    $message = esc_html__('License key for the ', 'woo-discount-rules').$this->pluginName.' '.esc_html__('is not entered.', 'woo-discount-rules').' <a href="admin.php?page=woo_discount_rules&tab=settings">'.esc_html__('Please enter a valid license key', 'woo-discount-rules').'</a>. '.esc_html__('You can get it from', 'woo-discount-rules').' <a href="'.$this->siteURL.'">'.esc_html__('our website', 'woo-discount-rules').'</a>.';
                }
                
                if (!empty($message)) {
                    // If an update is available ?
                    // prevent update if error occurs
                    echo '<tr>';
                    echo '<td> </td>';
                    echo '<td colspan="2"> <div class="notice-message error inline notice-error notice-alt"><p>'.$message.'</p></div></td>';
                    echo '</tr>';
                }
            }

        }

        /**
         * Ajax request for license key validation
         * */
        public function forceValidateLicenseKey()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $params = array();
            if (!isset($request['data'])) return false;
            parse_str($request['data'], $params);

            $this->force_license_check = true;

            $resp = array();
            if (empty($params['license_key'])) {
                $resp['error'] = esc_html__('Please enter a valid license key', 'woo-discount-rules');
                echo json_encode( $resp );
                die();
            }

            $base = new FlycartWooDiscountBase();
            $base->saveConfig(1);

            if ( $this->validateLicenseKey() ) {
                $resp['success'] = esc_html__('License key check : Passed.', 'woo-discount-rules');
                // TODO: passed tick mark with a flag
            } else {
                // return an error
                $resp['error'] = esc_html__('License key seems to be Invalid. Please enter a valid license key', 'woo-discount-rules');
            }
            
            echo json_encode( $resp );
            die();
        }

        public function validateLicenseKey()
        {
            $run = $this->doIHaveToRunValidateLicense();

            $already_check = get_option('woo_discount_rules_verified_key', 0);
            
            $cache_license_check = ( $already_check == 1 ) ? true : false ;

            if($run){
                $base = new FlycartWooDiscountBase();
                $config = $base->getBaseConfig();
                if (is_string($config)) $config = json_decode($config, true);
                if ( isset( $config['license_key'] ) && !empty($config['license_key']) ) {

                    // check the license
                    $license_url = $this->getUpdateURL('licensecheck');
                    $result = wp_remote_get( $license_url , array());

                    //Try to parse the json response
                    $status = $this->validateApiResponse($result);
                    $metadata = null;
                    
                    update_option('woo_discount_rules_num_runs', get_option('woo_discount_rules_num_runs',1)+1);

                    if ( !is_wp_error($status) ){
                        $json = json_decode( $result['body'] );
                        if ( is_object($json) && isset($json->license_check)) {
                            update_option('woo_discount_rules_updated_time', time());
                            $verified = (bool)$json->license_check;
                            if($verified){
                                update_option('woo_discount_rules_verified_key', 1);
                            } else {
                                update_option('woo_discount_rules_verified_key', 0);
                            }
                            return $verified;
                        }
                    }
                }
            }
            return $cache_license_check;
        }

        /**
         * Check to run validate license check
         * */
        protected function doIHaveToRunValidateLicense()
        {
            $option_exists = (get_option('woo_discount_rules_updated_time', null) !== null);

            if(!$option_exists){
                add_option('woo_discount_rules_updated_time', 0);
                add_option('woo_discount_rules_verified_key', 0);
                add_option('woo_discount_rules_num_runs',0);
            }
            $lastRunUnix = get_option('woo_discount_rules_updated_time', 0);
            $lastRunCount = get_option('woo_discount_rules_num_runs', 0);
            $nextRunUnix = $lastRunUnix;
            
            $hours = 12;
            $countLimit = 8;

            $nextRunUnix += $hours * 3600;
            $now = time();

            $time_factor = (bool) ($now >= $nextRunUnix );
            $count_factor = (bool) ($lastRunCount < $countLimit);
            
            if ($time_factor && !$count_factor) {
                update_option('woo_discount_rules_num_runs', 0);
            }
            
            $already_valid_check = get_option('woo_discount_rules_verified_key', false);

            return (($time_factor && $count_factor && $already_valid_check) || $this->force_license_check );
        }

        public function getUpdateURL($type='updatecheck')
        {
            $update_url = 'https://www.flycart.org/?wpaction='.$type.'&wpslug='.$this->slug;
            $purchase_helper = new FlycartWooDiscountRulesPurchase();
            $update_url .= '&pro='.(int)$purchase_helper->isPro();
            $dlid = '';
            $base = new FlycartWooDiscountBase();
            $config = $base->getBaseConfig();
            if (is_string($config)) $config = json_decode($config);
            $dlid = isset($config->license_key)? $config->license_key: null;
            if ( !empty($dlid) ) {
                $update_url .= '&dlid='.$dlid;
            }
            return $update_url;
        }

        /**
         * Check if $result is a successful update API response.
         *
         * @param array|WP_Error $result
         * @return true|WP_Error
         */
        protected function validateApiResponse($result) {
            if ( is_wp_error($result) ) { /** @var WP_Error $result */
                return new WP_Error($result->get_error_code(), 'WP HTTP Error: ' . $result->get_error_message());
            }

            if ( !isset($result['response']['code']) ) {
                return new WP_Error(
                    'puc_no_response_code',
                    'wp_remote_get() returned an unexpected result.'
                );
            }

            if ( $result['response']['code'] !== 200 ) {
                return new WP_Error(
                    'puc_unexpected_response_code',
                    'HTTP response code is ' . $result['response']['code'] . ' (expected: 200)'
                );
            }

            if ( empty($result['body']) ) {
                return new WP_Error('puc_empty_response', 'The metadata file appears to be empty.');
            }

            return true;
        }

    }
}