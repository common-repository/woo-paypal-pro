<?php

/**
 * @since      1.0.0
 * @package    Woo_PayPal_Pro
 * @subpackage Woo_PayPal_Pro/includes
 * @author     easypayment <wpeasypayment@gmail.com>
 */
class Premiumdev_Woo_PayPal_Pro_Gateway extends WC_Payment_Gateway_CC {

    public function __construct() {
        try {
            $this->id = 'paypal_pro';
            $this->api_version = '119';
            $this->method_title = __('PayPal Pro', 'woo-paypal-pro');
            $this->method_description = __('PayPal Website Payments Pro allows you to accept credit cards directly on your site without any redirection through PayPal.  You host the checkout form on your own web server, so you will need an SSL certificate to ensure your customer data is protected.', 'woo-paypal-pro');
            $this->icon = apply_filters('woocommerce_pal_pro_icon', plugins_url('/images/cards.png', plugin_basename(dirname(__FILE__))));
            $this->has_fields = true;
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('premium_title');
            $this->description = $this->get_option('premium_description');
            $this->enabled = $this->get_option('premium_enabled');
            $this->supports = array(
                'products',
                'refunds',
                'pay_button'
            );
            $this->testmode = $this->get_option('premium_testmode', "no") === "yes" ? true : false;
            $this->debug = $this->get_option('premium_debug_log', "no") === "yes" ? true : false;
            $this->send_items = $this->get_option('premium_send_item_details', "no") === "yes" ? true : false;
            $this->paymentaction = $this->get_option('premium_action');
            $this->invoice_prefix = $this->get_option('premium_invoice_prefix', 'WC-PayPal-Pro');
            $this->available_card_types = premiumdev_woo_paypal_pro_get_available_card_type();
            $this->log = "";
            $this->post_data = array();
            $this->ITEMAMT = 0;
            $this->fee_total = 0;
            $this->item_loop = 0;
            $this->pal_pro_notifyurl = site_url('?Woo_PayPal_Pro&action=ipn_handler');
            $this->pre_wc_30 = version_compare(WC_VERSION, '3.0', '<');
            if ($this->testmode) {
                $this->Pay_URL = "https://api-3t.sandbox.paypal.com/nvp";
                $this->api_username = ($this->get_option('premium_sandbox_username')) ? trim($this->get_option('premium_sandbox_username')) : '';
                $this->api_password = ($this->get_option('premium_sandbox_password')) ? trim($this->get_option('premium_sandbox_password')) : '';
                $this->api_signature = ($this->get_option('premium_sandbox_signature')) ? trim($this->get_option('premium_sandbox_signature')) : '';
            } else {
                $this->Pay_URL = "https://api-3t.paypal.com/nvp";
                $this->api_username = ($this->get_option('premium_live_username')) ? trim($this->get_option('premium_live_username')) : '';
                $this->api_password = ($this->get_option('premium_live_password')) ? trim($this->get_option('premium_live_password')) : '';
                $this->api_signature = ($this->get_option('premium_live_signature')) ? trim($this->get_option('premium_live_signature')) : '';
            }
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } catch (Exception $ex) {
            wc_add_notice('<strong>' . __('Payment error', 'woo-paypal-pro') . '</strong>: ' . $ex->getMessage(), 'error');
            return;
        }
    }

    public function init_form_fields() {
        return $this->form_fields = premiumdev_woo_paypal_pro_setting_field();
    }

    public function is_available() {
        if ($this->enabled === "yes") {
            if (!is_ssl() && !$this->testmode) {
                return false;
            }
            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_pro_allowed_currencies', array('AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HUF', 'JPY', 'NOK', 'NZD', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'USD')))) {
                return false;
            }
            if (!$this->api_username || !$this->api_password || !$this->api_signature) {
                return false;
            }
            return isset($this->available_card_types[WC()->countries->get_base_country()]);
        }

        return false;
    }

    public function admin_options() {
        parent::admin_options();
        ?>      
        <script type="text/javascript">
            jQuery('#woocommerce_paypal_pro_premium_testmode').change(function () {
                var sandbox = jQuery('#woocommerce_paypal_pro_premium_sandbox_username, #woocommerce_paypal_pro_premium_sandbox_password, #woocommerce_paypal_pro_premium_sandbox_signature').closest('tr'),
                        production = jQuery('#woocommerce_paypal_pro_premium_live_username, #woocommerce_paypal_pro_premium_live_password, #woocommerce_paypal_pro_premium_live_signature').closest('tr');
                if (jQuery(this).is(':checked')) {
                    sandbox.show();
                    production.hide();
                } else {
                    sandbox.hide();
                    production.show();
                }
            }).change();
        </script> 
        <?php

    }

    public function payment_fields() {
        if ($this->description) {
            echo '<p>' . wp_kses_post($this->description);
            if ($this->testmode == "yes") {
                echo '<p>';
                _e('NOTICE: SANDBOX (TEST) MODE ENABLED.', 'woo-paypal-pro');
                echo '<br />';
                _e('For testing purposes you can use the card number 4012 8888 8888 1881 with any CVC and a valid expiration date.', 'woo-paypal-pro');
                echo '</p>';
            }
        }
        $this->form();
    }

    public function validate_fields() {
        try {
            $card = premiumdev_woo_paypal_pro_is_card_details(wc_clean($_POST));
            if (empty($card->exp_month) || empty($card->exp_year)) {
                throw new Exception(__('Card expiration date is invalid', 'woo-paypal-pro'));
            }
            if (!ctype_digit($card->cvc)) {
                throw new Exception(__('Card security code is invalid (only digits are allowed)', 'woo-paypal-pro'));
            }
            if (
                    !ctype_digit($card->exp_month) ||
                    !ctype_digit($card->exp_year) ||
                    $card->exp_month > 12 ||
                    $card->exp_month < 1 ||
                    $card->exp_year < date('y')
            ) {
                throw new Exception(__('Card expiration date is invalid', 'woo-paypal-pro'));
            }

            if (empty($card->number) || !ctype_digit($card->number)) {
                throw new Exception(__('Card number is invalid', 'woo-paypal-pro'));
            }
            return true;
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return false;
        }
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_transaction_id() || !$this->api_username || !$this->api_password || !$this->api_signature) {
            return false;
        }
        $details = $this->premiumdev_woo_paypal_pro_transaction_details($order->get_transaction_id());
        if ($details && strtolower($details['PENDINGREASON']) === 'authorization') {
            $order->add_order_note(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'woo-paypal-pro'));
            $this->premiumdev_woo_paypal_pro_log_write('Refund order # ', $order_id . ': authorized only transactions need to use cancel/void instead.');
            throw new Exception(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'woo-paypal-pro'));
        }
        $this->post_data = array(
            'VERSION' => $this->api_version,
            'SIGNATURE' => $this->api_signature,
            'USER' => $this->api_username,
            'PWD' => $this->api_password,
            'METHOD' => 'RefundTransaction',
            'TRANSACTIONID' => $order->get_transaction_id(),
            'REFUNDTYPE' => is_null($amount) ? 'Full' : 'Partial'
        );
        if (!is_null($amount)) {
            $this->post_data['AMT'] = number_format($amount, 2, '.', '');
            $this->post_data['CURRENCY'] = version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency();
        }
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $this->post_data['NOTE'] = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $response = wp_remote_post($this->Pay_URL, array(
            'method' => 'POST',
            'body' => $this->post_data,
            'timeout' => 70,
            'user-agent' => 'woo-paypal-pro',
            'httpversion' => '1.1'
        ));
        if (is_wp_error($response)) {
            $this->log('Error ' . print_r($response->get_error_message(), true));
            $this->premiumdev_woo_paypal_pro_log_write('Error ', $response->get_error_message());
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'woo-paypal-pro'));
        }
        parse_str($response['body'], $parsed_response);
        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                $order->add_order_note(sprintf(__('Refunded %s - Refund ID: %s', 'woo-paypal-pro'), $parsed_response['GROSSREFUNDAMT'], $parsed_response['REFUNDTRANSACTIONID']));
                return true;
            default:
                $this->premiumdev_woo_paypal_pro_log_write('Parsed Response (refund)  ', $parsed_response);
                break;
        }
        return false;
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $this->premiumdev_woo_paypal_pro_log_write('Processing order #', $order_id);
        $card = premiumdev_woo_paypal_pro_is_card_details(wc_clean($_POST));
        return $this->premiumdev_woo_paypal_pro_do_payment($order, $card);
    }

    public function premiumdev_woo_paypal_pro_do_payment($order, $card) {
        try {
            $this->post_data = array(
                'VERSION' => $this->api_version,
                'SIGNATURE' => $this->api_signature,
                'USER' => $this->api_username,
                'PWD' => $this->api_password,
                'METHOD' => 'DoDirectPayment',
                'PAYMENTACTION' => $this->paymentaction,
                'IPADDRESS' => premiumdev_woo_paypal_pro_get_user_ip(),
                'AMT' => number_format($order->get_total(), 2, '.', ','),
                'INVNUM' => $this->invoice_prefix . preg_replace("/[^0-9,.]/", "", $order->get_order_number()),
                'CURRENCYCODE' => version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency(),
                'CREDITCARDTYPE' => $card->type,
                'ACCT' => $card->number,
                'EXPDATE' => $card->exp_month . $card->exp_year,
                'STARTDATE' => $card->start_month . $card->start_year,
                'CVV2' => $card->cvc,
                'NOTIFYURL' => $this->pal_pro_notifyurl,
                'EMAIL' => $this->pre_wc_30 ? $order->billing_email : $order->get_billing_email(),
                'FIRSTNAME' => $this->pre_wc_30 ? $order->billing_first_name : $order->get_billing_first_name(),
                'LASTNAME' => $this->pre_wc_30 ? $order->billing_last_name : $order->get_billing_last_name(),
                'STREET' => $this->pre_wc_30 ? trim($order->billing_address_1 . ' ' . $order->billing_address_2) : trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
                'CITY' => $this->pre_wc_30 ? $order->billing_city : $order->get_billing_city(),
                'STATE' => $this->pre_wc_30 ? $order->billing_state : $order->get_billing_state(),
                'ZIP' => $this->pre_wc_30 ? $order->billing_postcode : $order->get_billing_postcode(),
                'COUNTRYCODE' => $this->pre_wc_30 ? $order->billing_country : $order->get_billing_country(),
                'SHIPTONAME' => $this->pre_wc_30 ? ( $order->shipping_first_name . ' ' . $order->shipping_last_name ) : ( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
                'SHIPTOSTREET' => $this->pre_wc_30 ? $order->shipping_address_1 : $order->get_shipping_address_1(),
                'SHIPTOSTREET2' => $this->pre_wc_30 ? $order->shipping_address_2 : $order->get_shipping_address_2(),
                'SHIPTOCITY' => $this->pre_wc_30 ? $order->shipping_city : $order->get_shipping_city(),
                'SHIPTOSTATE' => $this->pre_wc_30 ? $order->shipping_state : $order->get_shipping_state(),
                'SHIPTOCOUNTRYCODE' => $this->pre_wc_30 ? $order->shipping_country : $order->get_shipping_country(),
                'SHIPTOZIP' => $this->pre_wc_30 ? $order->shipping_postcode : $order->get_shipping_postcode(),
                'BUTTONSOURCE' => 'mbjtechnolabs_SP'
            );
            if ($this->send_items) {
                $item_loop = 0;
                if (count($order->get_items()) > 0) {
                    $ITEMAMT = 0;
                    foreach ($order->get_items() as $item) {
                        if ($item['qty']) {
                            $item_name = $item['name'];
                            if ($pre_wc_30) {
                                $item_meta = new WC_Order_Item_Meta($item);
                                if ($formatted_meta = $item_meta->display(true, true)) {
                                    $item_name .= ' ( ' . $formatted_meta . ' )';
                                }
                            } else {
                                $item_meta = new WC_Order_Item_Product($item);
                                if ($formatted_meta = $item_meta->get_formatted_meta_data()) {
                                    foreach ($formatted_meta as $meta) {
                                        $item_name .= ' ( ' . $meta->display_key . ': ' . $meta->value . ' )';
                                    }
                                }
                            }
                            $item_price = $this->round($order->get_item_subtotal($item, false, false));
                            $this->post_data['L_NUMBER' . $item_loop] = $item_loop;
                            $this->post_data['L_NAME' . $item_loop] = $item_name;
                            $this->post_data['L_AMT' . $item_loop] = $item_price;
                            $this->post_data['L_QTY' . $item_loop] = $item['qty'];
                            $ITEMAMT += $item_price * $item['qty'];
                            $item_loop++;
                        }
                    }
                    foreach ($order->get_fees() as $fee) {
                        $fee_amount = $this->round($fee['line_total']);
                        $this->post_data['L_NUMBER' . $item_loop] = $item_loop;
                        $this->post_data['L_NAME' . $item_loop] = trim(substr($fee['name'], 0, 127));
                        $this->post_data['L_AMT' . $item_loop] = $fee_amount;
                        $this->post_data['L_QTY' . $item_loop] = 1;
                        $ITEMAMT += $fee_amount;
                        $item_loop++;
                    }
                    if ($order->get_total_discount() > 0) {
                        $discount_total = $this->round($order->get_total_discount());
                        $this->post_data['L_NUMBER' . $item_loop] = $item_loop;
                        $this->post_data['L_NAME' . $item_loop] = __('Order Discount', 'woocommerce-gateway-paypal-pro');
                        $this->post_data['L_AMT' . $item_loop] = "-$discount_total";
                        $this->post_data['L_QTY' . $item_loop] = 1;
                        $ITEMAMT -= $discount_total;
                        $item_loop++;
                    }
                    if ($order->get_total_shipping() > 0) {
                        $this->post_data['SHIPPINGAMT'] = $this->round($order->get_total_shipping());
                    }
                    $item_subtotal = $this->round($order->get_total()) - $this->round($order->get_total_shipping()) - $this->round($order->get_total_tax());
                    if (absint($item_subtotal * 100) !== absint($ITEMAMT * 100)) {
                        $this->post_data['L_NUMBER' . $item_loop] = $item_loop;
                        $this->post_data['L_NAME' . $item_loop] = __('Rounding Amendment', 'woocommerce-gateway-paypal-pro');
                        $this->post_data['L_AMT' . $item_loop] = ( absint($item_subtotal * 100) - absint($ITEMAMT * 100) ) / 100;
                        $this->post_data['L_QTY' . $item_loop] = 1;
                    }
                    $this->post_data['ITEMAMT'] = $this->round($item_subtotal);
                    $this->post_data['TAXAMT'] = $this->round($order->get_total_tax());
                }
            }
            $this->premiumdev_woo_paypal_pro_log_write('Do payment request ', $this->post_data);
            $response = $this->premiumdev_woo_paypal_pro_wp_safe_remote_post($order);
            if (is_wp_error($response)) {
                $this->premiumdev_woo_paypal_pro_log_write('Error ', $response->get_error_message());
                throw new Exception(__('There was a problem connecting to the payment gateway.', 'woo-paypal-pro'));
            }
            if (empty($response['body'])) {
                $this->premiumdev_woo_paypal_pro_log_write('Empty response! ', $response->get_error_message());
                throw new Exception(__('Empty Paypal response.', 'woo-paypal-pro'));
            }
            parse_str($response['body'], $parsed_response);
            $this->premiumdev_woo_paypal_pro_log_write('Parsed Response ', $parsed_response);
            return $this->premiumdev_woo_paypal_pro_update_notes($parsed_response, $order);
        } catch (Exception $e) {
            wc_add_notice('<strong>' . __('Payment error', 'woo-paypal-pro') . '</strong>: ' . $e->getMessage(), 'error');
            return;
        }
    }

    public function premiumdev_woo_paypal_pro_wp_safe_remote_post($order) {
        return wp_safe_remote_post($this->Pay_URL, array(
            'method' => 'POST',
            'headers' => array(
                'PAYPAL-NVP' => 'Y'
            ),
            'body' => $this->post_data,
            'timeout' => 70,
            'user-agent' => 'woo-paypal-pro',
            'httpversion' => '1.1'
        ));
    }

    public function premiumdev_woo_paypal_pro_update_notes($parsed_response, $order) {
        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                $txn_id = (!empty($parsed_response['TRANSACTIONID']) ) ? wc_clean($parsed_response['TRANSACTIONID']) : '';
                $correlation_id = (!empty($parsed_response['CORRELATIONID']) ) ? wc_clean($parsed_response['CORRELATIONID']) : '';
                $details = $this->premiumdev_woo_paypal_pro_transaction_details($txn_id);
                if ($details && strtolower($details['PAYMENTSTATUS']) === 'pending' && strtolower($details['PENDINGREASON']) === 'authorization') {
                    update_post_meta($order->id, '_paypalpro_charge_captured', 'no');
                    add_post_meta($order->id, '_transaction_id', $txn_id, true);
                    $order->update_status('on-hold', sprintf(__('PayPal Pro charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'woo-paypal-pro'), $txn_id));
                    $order->reduce_order_stock();
                } else {
                    $order->add_order_note(sprintf(__('PayPal Pro payment completed (Transaction ID: %s, Correlation ID: %s)', 'woo-paypal-pro'), $txn_id, $correlation_id));
                    $order->payment_complete($txn_id);
                }
                WC()->cart->empty_cart();
                if (method_exists($order, 'get_checkout_order_received_url')) {
                    $redirect = $order->get_checkout_order_received_url();
                } else {
                    $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                }
                return array(
                    'result' => 'success',
                    'redirect' => $redirect
                );
                break;
            case 'failure':
            default:
                if (!empty($parsed_response['L_LONGMESSAGE0'])) {
                    $error_message = $parsed_response['L_LONGMESSAGE0'];
                } elseif (!empty($parsed_response['L_SHORTMESSAGE0'])) {
                    $error_message = $parsed_response['L_SHORTMESSAGE0'];
                } elseif (!empty($parsed_response['L_SEVERITYCODE0'])) {
                    $error_message = $parsed_response['L_SEVERITYCODE0'];
                } elseif ($this->testmode) {
                    $error_message = print_r($parsed_response, true);
                }
                $order->update_status('failed', sprintf(__('PayPal Pro payment failed (Correlation ID: %s). Payment was rejected due to an error: ', 'woo-paypal-pro'), $parsed_response['CORRELATIONID']) . '(' . $parsed_response['L_ERRORCODE0'] . ') ' . '"' . $error_message . '"');
                throw new Exception($error_message);
                break;
        }
    }

    public function premiumdev_woo_paypal_pro_transaction_details($transaction_id = 0) {
        $this->post_data = array(
            'VERSION' => $this->api_version,
            'SIGNATURE' => $this->api_signature,
            'USER' => $this->api_username,
            'PWD' => $this->api_password,
            'METHOD' => 'GetTransactionDetails',
            'TRANSACTIONID' => $transaction_id
        );
        $response = wp_safe_remote_post($this->Pay_URL, array(
            'method' => 'POST',
            'headers' => array(
                'PAYPAL-NVP' => 'Y'
            ),
            'body' => $this->post_data,
            'timeout' => 70,
            'user-agent' => 'woo-paypal-pro',
            'httpversion' => '1.1'
        ));
        if (is_wp_error($response)) {
            $this->premiumdev_woo_paypal_pro_log_write('Error ', $response->get_error_message());
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'woo-paypal-pro'));
        }
        parse_str($response['body'], $parsed_response);
        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                return $parsed_response;
                break;
        }
        return false;
    }

    public function premiumdev_woo_paypal_pro_log_write($text, $message) {
        if ($this->debug) {
            if (empty($this->log)) {
                $this->log = new WC_Logger();
            }
            if (is_array($message) && count($message) > 0) {
                $message = $this->premiumdev_woo_paypal_pro_personal_detail_square($message);
            }
            $this->log->add('woo_paypal_pro', $text . ' ' . print_r($message, true));
        }
    }

    public function premiumdev_woo_paypal_pro_personal_detail_square($message) {
        foreach ($message as $key => $value) {
            if ($key == "USER" || $key == "PWD" || $key == "SIGNATURE" || $key == "ACCT" || $key == "EXPDATE" || $key == "CVV2") {
                $str_length = strlen($value);
                $ponter_data = "";
                for ($i = 0; $i <= $str_length; $i++) {
                    $ponter_data .= '*';
                }
                $message[$key] = $ponter_data;
            }
        }
        return $message;
    }

    protected function round($number, $precision = 2) {
        return round((float) $number, $precision);
    }

    protected function decimal_to_cents($amount, $rounding_behaviour = 'round') {
        if ('round' === $rounding_behaviour) {
            $amount = $this->round($amount);
        }

        return $amount * 100;
    }

}
