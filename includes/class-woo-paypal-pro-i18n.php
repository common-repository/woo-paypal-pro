<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woo_PayPal_Pro
 * @subpackage Woo_PayPal_Pro/includes
 * @author     easypayment <wpeasypayment@gmail.com>
 */
class Woo_PayPal_Pro_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
                'woo-paypal-pro', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

}
