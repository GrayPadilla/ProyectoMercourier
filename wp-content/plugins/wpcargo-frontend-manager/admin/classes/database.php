<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class WPCFE_DATABASE{
    public static function create_report(){
        global $wpdb;
        $report_table = $wpdb->prefix.WPCFE_DB_REPORTS;
        if($wpdb->get_var("SHOW TABLES LIKE '$report_table'") != $report_table) {
			$charset_collate = $wpdb->get_charset_collate();
			$report_sql    = "CREATE TABLE IF NOT EXISTS $report_table (
							`id` int(100) NOT NULL auto_increment,
							`post_id` int(100),
							`post_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
							`from_status` VARCHAR(60) NOT NULL,
							`to_status` VARCHAR(60) NOT NULL,
							`updated_by` VARCHAR(60) NOT NULL,
							PRIMARY KEY  (`id`)
							) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($report_sql);
		}
    }
}
