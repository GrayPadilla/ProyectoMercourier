<?php
/*
 * Plugin Name: WPCargo Address Book Add-ons
 * Plugin URI: https://www.wpcargo.com/product/wpcargo-address-book-add-ons/
 * Description: WPCargo Address Book Add-ons help you manage shipper/receiver addresses. Available shortcode [wpc-address-book]
 * Author: <a href="http://www.wptaskforce.com/">WPTaskForce</a>
 * Text Domain: wpcargo-address-book
 * Domain Path: /languages
 * Version: 5.0.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
//* Defined constant
define('WPCARGO_ADDRESS_BOOK_VERSION', '5.0.0');
define('WPCARGO_ADDRESS_TEXTDOMAIN', 'wpcargo-address-book');
define('WPCARGO_ADDRESS_BOOK_URL', plugin_dir_url(__FILE__));
define('WPCARGO_ADDRESS_BOOK_PATH', plugin_dir_path(__FILE__));
define('WPCARGO_ADDRESS_BOOK_BASENAME', plugin_basename(__FILE__));



$phpversion = floatval(phpversion());
$phpversion = floor($phpversion);
if ($phpversion == 8) {
    define('WPCARGO_ADDRESS_BOOK_UPDATE_REMOTE', 'updates-8.1');
} else {
    define('WPCARGO_ADDRESS_BOOK_UPDATE_REMOTE', 'updates-7.2');
}


//** Load plugin text Domain
add_action('plugins_loaded', 'wpcargo_address_book_load_textdomain');
function wpcargo_address_book_load_textdomain()
{
    load_plugin_textdomain('wpcargo-address-book', false, '/wpcargo-address-book-add-ons/languages');
}
//* Includes files
require_once(WPCARGO_ADDRESS_BOOK_PATH . 'admin/admin.php');
