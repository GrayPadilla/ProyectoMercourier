<?php
global $wpdb;
if( version_compare( WPCU_MANAGEMENT_DB_VERSION, '1.0.2' ) <= 0  ){	
	$user_group_table = $wpdb->prefix . WPCU_MANAGEMENT_DB_USER_GROUP;
	if($wpdb->get_var("SHOW TABLES LIKE '$user_group_table'") != $user_group_table) {
		$charset_collate = $wpdb->get_charset_collate();
		$user_group_sql  = "CREATE TABLE IF NOT EXISTS $user_group_table (
						`user_group_id` int(100) NOT NULL auto_increment,
						`label` VARCHAR(100) NOT NULL,
						`description` VARCHAR(255) NOT NULL,
						`users` VARCHAR(100) NULL,
						PRIMARY KEY  (`user_group_id`)
						) $charset_collate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($user_group_sql);
	}
}
