<?php
class Hubon
{
    protected $loader;

    protected $plugin_name;

    protected $version;
    protected $plugin_public;

    public function __construct()
    {
        if (defined('HUBON_VERSION')) {
            $this->version = HUBON_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'hubon';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-hubon-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-hubon-rest-adapter.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-hubon-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-hubon-public.php';

        $this->loader = new Hubon_Loader();
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new Hubon_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'on_loaded');
        $this->loader->add_action('rest_api_init', $plugin_admin, "register_hubon_failed_get_routes");
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_pickup_date_metabox');
    }

    private function define_public_hooks()
    {
        $plugin_public = new Hubon_Public($this->get_plugin_name(), $this->get_version());
        $this->plugin_public = $plugin_public;
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_filter('plugin_action_links_' . plugin_basename(plugin_dir_path(dirname(__FILE__))) . '/hubon-shipping.php', $this, 'action_links');
        $this->loader->add_action('woocommerce_shipping_init', $this, 'load_shipping_method');
        $this->loader->add_filter('woocommerce_shipping_methods', $this, 'register_shipping_methods');
        $this->loader->add_action('woocommerce_after_order_notes', $plugin_public, 'add_custom_pickup_date_field');
        $this->loader->add_action('woocommerce_thankyou', $plugin_public, 'create_transport_order');
        $this->loader->add_action('woocommerce_checkout_process', $plugin_public, 'validate_pickup_date_field');
        $this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_public, 'save_pickup_date_field');
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_version()
    {
        return $this->version;
    }

    public function load_shipping_method()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-hubon-shipping-method.php';
    }

    public function action_links($links)
    {
        $setting_link = get_admin_url() . "admin.php?page=wc-settings&tab=shipping&section=hubon";
        $plugin_links = array(
            '<a href="' . esc_url($setting_link) . '">' . __('Settings', 'hubon-local-pickup') . '</a>',
            '<a href="https://letshubon.com/accounts/integration" target="_blank">' . __('Support', 'hubon-local-pickup') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    public function register_shipping_methods($methods)
    {
        $methods['hubon'] = 'Hubon_Shipping_Method';
        return $methods;
    }
}
