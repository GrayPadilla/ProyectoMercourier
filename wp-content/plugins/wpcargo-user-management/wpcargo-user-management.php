<?php
/*
 * Plugin Name: WPCargo User Management
 * Plugin URI: http://wptaskforce.com/
 * Description: Manage user access
 * Author: <a href="http://www.wptaskforce.com/">WPTaskForce</a>
 * Text Domain: wpcargo-umanagement
 * Domain Path: /languages
 * Version: 2.0.3
 */
// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}
//* Defined constant
define('WPCU_MANAGEMENT_FILE', __FILE__);
define('WPCU_MANAGEMENT_VERSION', '2.0.3');
define('WPCU_MANAGEMENT_DB_VERSION', '1.0.2');
define('WPCU_MANAGEMENT_DB_USER_GROUP', 'wpcsr_user_group'); // wpcumanage_users_group
define('WPCU_MANAGEMENT_URL', plugin_dir_url(WPCU_MANAGEMENT_FILE));
define('WPCU_MANAGEMENT_PATH', plugin_dir_path(WPCU_MANAGEMENT_FILE));
define('WPCU_MANAGEMENT_HOME_URL', home_url());
define('WPCU_MANAGEMENT_BASENAME', plugin_basename(WPCU_MANAGEMENT_FILE));
define('WPCU_MANAGEMENT_REMOTE', 'updates-8.1');
define('WPCU_MANAGEMENT_ITEMS_PAGE', 24);
// Include files
require_once(WPCU_MANAGEMENT_PATH . 'includes/intl.php');
require_once(WPCU_MANAGEMENT_PATH . 'includes/pages.php');
require_once(WPCU_MANAGEMENT_PATH . 'includes/functions.php');
require_once(WPCU_MANAGEMENT_PATH . 'includes/scripts.php');
require_once(WPCU_MANAGEMENT_PATH . 'includes/hooks.php');
require_once(WPCU_MANAGEMENT_PATH . 'includes/users.php');
require_once(WPCU_MANAGEMENT_PATH . 'includes/install-db.php');
require_once(WPCU_MANAGEMENT_PATH . 'includes/ajax.php');
