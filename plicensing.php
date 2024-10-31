<?php
/**
 * Plugin Name: Product Licensing System
 * Plugin URI: http://panonda.com
 * Description: Licensing management plugin that help you sell licenses easily.
 * Version: 1.0
 * Author: Panonda
 *
 * Text Domain: plicense
 */
if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('PLICENSE_VERSION', '1.0');
define('PLICENSE_PLUG_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('PLICENSE_PLUG_URL', untrailingslashit(plugins_url('/', __FILE__)));
define('PLICENSE_PLUG_NAME', plugin_basename( __FILE__ ));

require(PLICENSE_PLUG_PATH.'/include/function.php');
require(PLICENSE_PLUG_PATH.'/include/license-manager-page.php');
require(PLICENSE_PLUG_PATH.'/include/setting-page.php');
require(PLICENSE_PLUG_PATH.'/include/integrate-woocommerce.php');

register_activation_hook(__FILE__, 'plicense_plugin_activate');
function plicense_plugin_activate ()
{
	plicensing_install();
}
?>