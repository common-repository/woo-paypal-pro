<?php

if (!function_exists('premiumdev_woo_paypal_pro_setting_field')) {

    function premiumdev_woo_paypal_pro_setting_field() {
        return array(
            'premium_enabled' => array(
                'title' => __('Enable/Disable', 'woo-paypal-pro'),
                'label' => __('Enable PayPal Pro', 'woo-paypal-pro'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'premium_title' => array(
                'title' => __('Title', 'woo-paypal-pro'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => __('Credit card', 'woo-paypal-pro')
            ),
            'premium_description' => array(
                'title' => __('Description', 'woo-paypal-pro'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => __("Pay with your credit card.", 'woo-paypal-pro')
            ),
            'premium_testmode' => array(
                'title' => __('Test Mode', 'woo-paypal-pro'),
                'type' => 'checkbox',
                'default' => 'yes',
                'description' => __('Place the payment gateway in development mode.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'label' => __('Enable PayPal Sandbox/Test Mode', 'woo-paypal-pro')
            ),
            'premium_sandbox_username' => array(
                'title' => __('Sandbox API Username', 'woo-paypal-pro'),
                'type' => 'text',
                'description' => __('Get your API credentials from PayPal.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'label' => __('Create sandbox accounts and obtain API credentials from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'woo-paypal-pro'),
                'default' => ''
            ),
            'premium_sandbox_password' => array(
                'title' => __('Sandbox API Password', 'woo-paypal-pro'),
                'type' => 'password',
                'description' => __('Get your API credentials from PayPal.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => ''
            ),
            'premium_sandbox_signature' => array(
                'title' => __('Sandbox API Signature', 'woo-paypal-pro'),
                'type' => 'password',
                'description' => __('Get your API credentials from PayPal.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => ''
            ),
            'premium_live_username' => array(
                'title' => __('API Username', 'woo-paypal-pro'),
                'type' => 'text',
                'label' => __('Get your live account API credentials from your PayPal account profile under the API Access section <br />or by using <a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'woo-paypal-pro'),
                'description' => __('Get your API credentials from PayPal.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => ''
            ),
            'premium_live_password' => array(
                'title' => __('API Password', 'woo-paypal-pro'),
                'type' => 'password',
                'description' => __('Get your API credentials from PayPal.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => ''
            ),
            'premium_live_signature' => array(
                'title' => __('API Signature', 'woo-paypal-pro'),
                'type' => 'password',
                'description' => __('Get your API credentials from PayPal.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => ''
            ),
            'premium_invoice_prefix' => array(
                'title' => __('Invoice ID Prefix', 'woo-paypal-pro'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => 'WC-PayPal-Pro'
            ),
            'premium_action' => array(
                'title' => __('Payment Action', 'woo-paypal-pro'),
                'type' => 'select',
                'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'options' => array(
                    'sale' => __('Sale', 'woo-paypal-pro'),
                    'authorization' => __('Authorization', 'woo-paypal-pro'),
                ),
            ),
            'premium_send_item_details' => array(
                'title' => __('Send Item Details', 'woo-paypal-pro'),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('Sends line items to PayPal. If you experience rounding errors this can be disabled.', 'woo-paypal-pro'),
                'desc_tip' => true,
                'label' => __(' Send Line Items to PayPal', 'woo-paypal-pro')
            ),
            'premium_debug_log' => array(
                'title' => __('Debug Log', 'woo-paypal-pro'),
                'type' => 'checkbox',
                'description' => __('Enable Log Pal Pro', 'woo-paypal-pro'),
                'desc_tip' => true,
                'default' => 'no'
            )
        );
    }

}

if (!function_exists('premiumdev_woo_paypal_pro_notice_count')) {

    function premiumdev_woo_paypal_pro_notice_count($notice_type = '') {
        if (function_exists('wc_notice_count')) {
            return wc_notice_count($notice_type);
        }
        return 0;
    }

}
if (!function_exists('premiumdev_woo_paypal_pro_get_available_card_type')) {

    function premiumdev_woo_paypal_pro_get_available_card_type() {
        $available_card_types = apply_filters('woocommerce_paypal_pro_available_card_types', array(
            'GB' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Maestro' => 'Maestro/Switch',
                'Solo' => 'Solo'
            ),
            'US' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'Discover' => 'Discover',
                'AmEx' => 'American Express'
            ),
            'CA' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard'
            ),
            'AU' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard'
            ),
            'JP' => array(
                'Visa' => 'Visa',
                'MasterCard' => 'MasterCard',
                'JCB' => 'JCB'
            )
        ));

        return apply_filters('woocommerce_paypal_pro_avaiable_card_types', $available_card_types);
    }

}
if (!function_exists('premiumdev_woo_paypal_pro_is_card_details')) {

    function premiumdev_woo_paypal_pro_is_card_details($posted) {
        $card_number = isset($posted['paypal_pro-card-number']) ? wc_clean($posted['paypal_pro-card-number']) : '';
        $card_cvc = isset($posted['paypal_pro-card-cvc']) ? wc_clean($posted['paypal_pro-card-cvc']) : '';
        $card_expiry = isset($posted['paypal_pro-card-expiry']) ? wc_clean($posted['paypal_pro-card-expiry']) : '';

        // Format values
        $card_number = str_replace(array(' ', '-'), '', $card_number);
        $card_expiry = array_map('trim', explode('/', $card_expiry));
        $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
        $card_exp_year = isset($card_expiry[1]) ? $card_expiry[1] : '';

        if (isset($_POST['paypal_pro-card-start'])) {
            $card_start = wc_clean($_POST['paypal_pro-card-start']);
            $card_start = array_map('trim', explode('/', $card_start));
            $card_start_month = str_pad($card_start[0], 2, "0", STR_PAD_LEFT);
            $card_start_year = $card_start[1];
        } else {
            $card_start_month = '';
            $card_start_year = '';
        }

        if (strlen($card_exp_year) == 2) {
            $card_exp_year += 2000;
        }

        if (strlen($card_start_year) == 2) {
            $card_start_year += 2000;
        }

        return (object) array(
                    'number' => $card_number,
                    'type' => '',
                    'cvc' => $card_cvc,
                    'exp_month' => $card_exp_month,
                    'exp_year' => $card_exp_year,
                    'start_month' => $card_start_month,
                    'start_year' => $card_start_year
        );
    }

}
if (!function_exists('premiumdev_woo_paypal_pro_get_user_ip')) {

    function premiumdev_woo_paypal_pro_get_user_ip() {
        return (isset($_SERVER['HTTP_X_FORWARD_FOR']) && !empty($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
    }

}