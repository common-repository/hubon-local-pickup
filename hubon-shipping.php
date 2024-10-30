<?php

/**
 * Plugin Name: HubOn Local Pickup 
 * Plugin URI: https://woo.com/products/hubon-local-pickup/
 * Description: HubOn is a 3rd party service offering local pickup options at a nearby store. In order to use the HubOn Local Pickup plugin, you must first register at letshubon.com. HubOn partners with local stores serving as pickup locations. This is often a cheaper, greener, and safer alternative than sending products via traditional carriers or couriers to your customer address. HubOn also offers your customers more flexibility to pick up their orders at their convenient time.
 * Version: 1.0.0
 * Author: HubOn
 * Author URI: https://letshubon.com/
 * Developer: HubOn
 * Developer URI: https://letshubon.com/
 * Text Domain: hubon-local-pickup
 * Domain Path: /languages
 * Requires at least: 5.2
 * Requires PHP: 7.2
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 * WC requires at least: 2.2
 * WC tested up to: 2.3
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('WPINC')) {
    exit;
}

define('HUBON_VERSION', '1.0.0');

define('HUBON_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HUBON_PLUGIN_URL', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));

define('HUBON_CLIENT_ID', 'hubon-woocommerce-app');
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false) {
    define('HUBON_API_URL', 'https://hub-on-api--hub-on-gardeneur.sandboxes.run');
    define('HUBON_WEB_URL', 'https://hub-on-web--hub-on-gardeneur.sandboxes.run');
} else {
    define('HUBON_API_URL', 'https://api.letshubon.com');
    define('HUBON_WEB_URL', 'https://letshubon.com');
}

function activate_hubon()
{
}

function deactivate_hubon()
{
}

function uninstall_hubon()
{
}

register_activation_hook(__FILE__, 'activate_hubon');
register_deactivation_hook(__FILE__, 'deactivate_hubon');
register_uninstall_hook(__FILE__, 'uninstall_hubon');

require plugin_dir_path(__FILE__) . 'includes/class-hubon.php';

function run_hubon()
{
    $plugin = new Hubon();
    $plugin->run();
}
run_hubon();
