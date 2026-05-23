<?php
/*
 * Plugin Name: WPCargo Shipment Container Add-ons
 * Plugin URI: https://www.wpcargo.com/product/wpcargo-shipment-container-add-ons/
 * Description: WPCargo Shipment Container Add-ons helps manage shipment into container. Shorcode available for frontend [wpcargo-container-track-form pageredirect="page_id"], [wpcargo-container-track-result]
 * Author: <a href="http://www.wptaskforce.com/">WPTaskForce</a>
 * Text Domain: wpcargo-shipment-container
 * Domain Path: /languages
 * Version: 6.0.2
 *  */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
//* Defined constant
define('WPCARGO_SHIPMENT_CONTAINER_VERSION', '6.0.2');
define('WPCARGO_SHIPMENT_CONTAINER_TEXTDOMAIN', 'wpcargo-shipment-container');
define('WPCARGO_SHIPMENT_CONTAINER_FILE', __FILE__);
define('WPCARGO_SHIPMENT_CONTAINER_URL', plugin_dir_url(__FILE__));
define('WPCARGO_SHIPMENT_CONTAINER_PATH', plugin_dir_path(__FILE__));
define('WPCARGO_SHIPMENT_CONTAINER_BASENAME', plugin_basename(__FILE__));
define('WPCARGO_SHIPMENT_CONTAINER_PAGER', 12);
define('WPCARGO_SHIPMENT_CONTAINER_UPDATE_REMOTE', 'updates-8.3');

//* Includes files
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/includes/translation.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/includes/helpers.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/includes/functions.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/includes/hooks.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/includes/ajax-handler.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/classes/class-api.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/classes/class-container.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/classes/class-container-user.php');
require_once(WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/classes/class-container-scripts.php');
