<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_PayPal_Pro
 * @subpackage Woo_PayPal_Pro/includes
 * @author     easypayment <wpeasypayment@gmail.com>
 */
class Woo_PayPal_Pro {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Pal_Pro_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->plugin_name = 'woo-paypal-pro';
        $this->version = '6.0.0';

        $this->load_dependencies();
        $this->set_locale();
        $this->woo_gateway_hooks();
        add_action('parse_request', array($this, 'handle_api_requests'), 0);
        add_action('woo_paypal_pro_api_ipn_handler', array($this, 'premiumdev_woo_paypal_pro_api_ipn_handler'));
        $prefix = is_network_admin() ? 'network_admin_' : '';
        add_filter("{$prefix}plugin_action_links_" . PAYPAL_PRO_PLUGIN_BASENAME, array($this, 'paypal_pro_plugin_action_links'), 10, 4);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Pal_Pro_Loader. Orchestrates the hooks of the plugin.
     * - Pal_Pro_i18n. Defines internationalization functionality.
     * - Pal_Pro_Admin. Defines all hooks for the admin area.
     * - Pal_Pro_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-paypal-pro-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-paypal-pro-i18n.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-premiumdev-woo-paypal-pro-common-function.php';
        
        if (class_exists('WC_Payment_Gateway')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-premiumdev-woo-paypal-pro-gateway.php';
        }

        
        $this->loader = new Woo_PayPal_Pro_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Pal_Pro_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Woo_PayPal_Pro_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Add Payment Gateways Woocommerce Section
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    
    private function woo_gateway_hooks() {
        add_filter('woocommerce_payment_gateways', array($this, 'premiumdev_methods_woo_paypal_pro_gateways'), 10, 1);        
    }
    
    public function handle_api_requests() {

        global $wp;
        if (isset($_GET['action']) && $_GET['action'] == 'ipn_handler') {
            $wp->query_vars['Woo_PayPal_Pro'] = wc_clean($_GET['action']);
        }
        if (!empty($wp->query_vars['Woo_PayPal_Pro'])) {
            ob_start();
            $api = strtolower(esc_attr($wp->query_vars['Woo_PayPal_Pro']));
            do_action('woo_paypal_pro_api_' . strtolower($api));
            ob_end_clean();
            die('1');
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Pal_Pro_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
    
    public function premiumdev_methods_woo_paypal_pro_gateways($methods) {
        $methods[] = 'Premiumdev_Woo_PayPal_Pro_Gateway';
        return $methods;
    }
    
    public function premiumdev_woo_paypal_pro_api_ipn_handler() {           

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-premiumdev-woo-paypal-pro-paypal-listner.php';
        $Pal_Pro_PayPal_listner = new Premiumdev_Woo_PayPal_Pro_PayPal_listner();
        if ($Pal_Pro_PayPal_listner->premiumdev_woo_paypal_pro_check_ipn_request()) {            
            $Pal_Pro_PayPal_listner->premiumdev_woo_paypal_pro_successful_request($IPN_status = true);
        } else {            
            $Pal_Pro_PayPal_listner->premiumdev_woo_paypal_pro_successful_request($IPN_status = false);
        }
    }
    
    public function paypal_pro_plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
        $custom_actions = array(
            'configure' => sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=checkout&section=paypal_pro'), __('Configure', 'donation-button')),
            'docs' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.premiumdev.com/product/paypal-pro-for-woocommerce/', __('Docs', 'donation-button')),
            'support' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/woo-paypal-pro', __('Support', 'donation-button')),
            'review' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/view/plugin-reviews/woo-paypal-pro', __('Write a Review', 'donation-button')),
        );

        return array_merge($custom_actions, $actions);
    }
}
