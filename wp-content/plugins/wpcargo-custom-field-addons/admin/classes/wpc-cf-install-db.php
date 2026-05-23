<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit; 
}
/*---------------------------------Create Custom Fields Table---------------------------------*/
class WPCargo_Custom_Fields_Install_DB{
    public static function plugin_activated(){
        global $wpdb;
		$options = get_option('wpcargo_option_settings');
        $wpcargo_countries = wpcargo_country_list();
        $table_name = $wpdb->prefix . 'wpcargo_custom_fields';
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql             = "CREATE TABLE IF NOT EXISTS $table_name (
							id int(100) NOT NULL auto_increment,
							label VARCHAR(100) NOT NULL,
							description VARCHAR(1000) NOT NULL,
							field_type VARCHAR(100) NOT NULL,
							field_key VARCHAR(100) NOT NULL,
							required VARCHAR(100) NOT NULL,
							weight bigint(20) NOT NULL,
							section VARCHAR(100) NOT NULL,
							display_flags VARCHAR(800) NOT NULL,
							field_data longtext NOT NULL,
							status VARCHAR(100) NOT NULL,
                            classes VARCHAR(60) NOT NULL,
                            html longtext NOT NULL,
							
							PRIMARY  KEY (id) 
							) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            /* --------------------------Create Shipper Info Section-------------------------- */
            $wpdb->insert($table_name, array(
                "label" => __( "Shipper Name", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_shipper_name",
                "required" => "",
                "weight" => 1,
				"section" => "shipper_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize(""),
                "status" => ""
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Phone Number", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_shipper_phone",
                "required" => "",
                "weight" => 2,
				"section" => "shipper_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Address", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_shipper_address",
                "required" => "",
                "weight" => 3,
				"section" => "shipper_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Email", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "email",
                "field_key" => "wpcargo_shipper_email",
                "required" => "",
                "weight" => 4,
				"section" => "shipper_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            /* --------------------------End Here-------------------------- */
            /* --------------------------Create Receiver Info Section-------------------------- */
            $wpdb->insert($table_name, array(
                "label" => __("Receiver Name", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_receiver_name",
                "required" => "",
                "weight" => 5,
				"section" => "receiver_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize(""),
                "status" => ""
            ));
            $wpdb->insert($table_name, array(
                "label" => __( "Phone Number", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_receiver_phone",
                "required" => "",
                "weight" => 6,
				"section" => "receiver_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $wpdb->insert($table_name, array(
                "label" => __( "Address", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_receiver_address",
                "required" => "",
                "weight" => 7,
				"section" => "receiver_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Email", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "email",
                "field_key" => "wpcargo_receiver_email",
                "required" => "",
                "weight" => 8,
				"section" => "receiver_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            /* --------------------------End Here-------------------------- */
            /* --------------------------Create Shipment Info Section-------------------------- */
            $shipment_types =array();
			$settings_shipment_type = array_key_exists( 'settings_shipment_type', $options ) ? $options['settings_shipment_type'] : array();
			if( !empty($settings_shipment_type) ){
				$settings_shipment_type = explode(",", $settings_shipment_type);
				foreach( $settings_shipment_type as $shipment_option){
					$shipment_types[] = trim( $shipment_option );
				}
			}
            $options_type_ship = serialize($shipment_types);
            $wpdb->insert($table_name, array(
                "label" => __("Type of Shipment", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "select",
                "field_key" => "wpcargo_type_of_shipment",
                "required" => "",
                "weight" => 10,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => $options_type_ship
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Courier", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_courier",
                "required" => "",
                "weight" => 12,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
			$shipment_modes = array();
			$shipment_mode_options = array_key_exists( 'settings_shipment_wpcargo_mode', $options ) ? $options['settings_shipment_wpcargo_mode'] : array();
			if( !empty( $shipment_mode_options ) ){
				$shipment_mode_options = explode(",", $shipment_mode_options);
				foreach( $shipment_mode_options as $mode_option ){
					$shipment_modes[] = trim( $mode_option );
				}
			}
            $options_mode = serialize($shipment_modes);
            $wpdb->insert($table_name, array(
                "label" => __("Mode", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "select",
                "field_key" => "wpcargo_mode_field",
                "required" => "",
                "weight" => 14,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => $options_mode
            ));
			$payment_modes = array();
			$wpcargo_payment_mode = array_key_exists( 'settings_shipment_wpcargo_payment_mode', $options ) ? $options['settings_shipment_wpcargo_payment_mode'] : array();
			if( !empty( $wpcargo_payment_mode ) ){
				$payment_mode_list = explode(",", $wpcargo_payment_mode);
				foreach( $payment_mode_list as $payment_mode_option ){
					$payment_modes[] = trim( $payment_mode_option );
				}
			}
            $options_payment_mode = serialize($payment_modes);
            $wpdb->insert($table_name, array(
                "label" => __("Payment Mode", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "select",
                "field_key" => "payment_wpcargo_mode_field",
                "required" => "",
                "weight" => 17,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => $options_payment_mode
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Total Freight", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_total_freight",
                "required" => "",
                "weight" => 18,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
			$shipment_carrier_list = array();
			$shipment_carrier = array_key_exists( 'settings_shipment_wpcargo_carrier', $options ) ? $options['settings_shipment_wpcargo_carrier'] : array();
			if( !empty( $shipment_carrier ) ){
				$shipment_carrier = explode(",", $shipment_carrier);
				foreach( $shipment_carrier as $carrier ){
					$shipment_carrier_list[] = trim( $carrier );
				}
			}
            $options_carrier_mode = serialize($shipment_carrier_list);
            $wpdb->insert($table_name, array(
                "label" => __("Carrier", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "select",
                "field_key" => "wpcargo_carrier_field",
                "required" => "",
                "weight" => 19,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => $options_carrier_mode
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Carrier Reference No.", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "text",
                "field_key" => "wpcargo_carrier_ref_number",
                "required" => "",
                "weight" => 20,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Departure Time", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "time",
                "field_key" => "wpcargo_departure_time_picker",
                "required" => "",
                "weight" => 21,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $shipment_country_list = array();
			$shipment_country = array_key_exists( 'settings_shipment_country', $options ) ? $options['settings_shipment_country'] : array();
			if( !empty( $shipment_country ) ){
				$shipment_country = explode(",", $shipment_country);
				foreach( $shipment_country as $country ){
					$shipment_country_list[] = trim( $country );
				}
			}
            $options_countries = serialize($shipment_country_list);
            $wpdb->insert($table_name, array(
                "label" => __("Origin", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "select",
                "field_key" => "wpcargo_origin_field",
                "required" => "",
                "weight" => 22,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => $options_countries
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Destination", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "select",
                "field_key" => "wpcargo_destination",
                "required" => "",
                "weight" => 23,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => $options_countries
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Pickup Date", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "date",
                "field_key" => "wpcargo_pickup_date_picker",
                "required" => "",
                "weight" => 24,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Pickup Time", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "time",
                "field_key" => "wpcargo_pickup_time_picker",
                "required" => "",
                "weight" => 25,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
			
			$general_status_list = array();
            $shipment_status   = $options['settings_shipment_status'];
			if( !empty( $shipment_status ) ){
				$shipment_status = explode(",", $shipment_status);
				foreach( $shipment_status as $status ){
					$general_status_list[] = trim( $status );
				}
			}
            $options_general_status = serialize($general_status_list);
            $wpdb->insert($table_name, array(
                "label" => __("Expected Delivery Date", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "date",
                "field_key" => "wpcargo_expected_delivery_date_picker",
                "required" => "",
                "weight" => 27,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            $wpdb->insert($table_name, array(
                "label" => __("Comments", 'wpcargo-custom-field' ),
                "description" => "",
                "field_type" => "textarea",
                "field_key" => "wpcargo_comments",
                "required" => "",
                "weight" => 28,
				"section" => "shipment_info",
                "display_flags" => serialize(array('result')),
                "field_data" => serialize("")
            ));
            /* --------------------------End Here-------------------------- */
        }
    }
    public static function add_sample_shipment(){
        global $wpdb;
        $get_default_title = $wpdb->get_results("SELECT post_title FROM $wpdb->posts WHERE post_type = 'wpcargo_shipment' AND post_status IN ('publish', 'draft') AND post_title = 'WPCARGO-123'");
        if (empty($get_default_title)) {
            $sample_add_shipment = array(
                'post_title' => 'WPCARGO-123',
                'post_type' => 'wpcargo_shipment',
                'post_status' => 'publish'
            );
            $get_the_id = wp_insert_post($sample_add_shipment);
            update_post_meta($get_the_id, 'wpcargo_shipper_name', 'Juan dela Cruz');
            update_post_meta($get_the_id, 'wpcargo_shipper_phone', '0928-254-3569');
            update_post_meta($get_the_id, 'wpcargo_shipper_address', '7114 Kundiman Street, Sampaloc, 1008 Manila, Philippines');
            update_post_meta($get_the_id, 'wpcargo_shipper_email', 'juandelacruz@mail.com');
            update_post_meta($get_the_id, 'wpcargo_receiver_name', 'Tomas Morato');
            update_post_meta($get_the_id, 'wpcargo_receiver_phone', '0956-258-9857');
            update_post_meta($get_the_id, 'wpcargo_receiver_address', 'Brgy. 178, Caloocan City, Metro Manila, 1422, Philippines');
            update_post_meta($get_the_id, 'wpcargo_receiver_email', 'tomasmorato@mail.com');
            update_post_meta($get_the_id, 'agent_fields', '');
            update_post_meta($get_the_id, 'wpcargo_type_of_shipment', '');
            update_post_meta($get_the_id, 'wpcargo_weight', '400kg');
            update_post_meta($get_the_id, 'wpcargo_courier', 'Courier 1');
            update_post_meta($get_the_id, 'wpcargo_packages', 'Package 101');
            update_post_meta($get_the_id, 'wpcargo_mode_field', '');
            update_post_meta($get_the_id, 'wpcargo_product', 'Product 20301');
            update_post_meta($get_the_id, 'wpcargo_qty', '150 box');
            update_post_meta($get_the_id, 'payment_wpcargo_mode_field', '');
            update_post_meta($get_the_id, 'wpcargo_total_freight', "$20,000");
            update_post_meta($get_the_id, 'wpcargo_carrier_field', '');
            update_post_meta($get_the_id, 'wpcargo_carrier_ref_number', '213412434');
            update_post_meta($get_the_id, 'wpcargo_departure_time_picker', '6:00am');
            update_post_meta($get_the_id, 'wpcargo_origin_field', 'Philippines');
            update_post_meta($get_the_id, 'wpcargo_destination', 'Philippines');
            update_post_meta($get_the_id, 'wpcargo_pickup_date_picker', '2016-04-28');
            update_post_meta($get_the_id, 'wpcargo_pickup_time_picker', '6:00am');
            update_post_meta($get_the_id, 'wpcargo_status', '');
            update_post_meta($get_the_id, 'wpcargo_expected_delivery_date_picker', '2016-08-28');
            update_post_meta($get_the_id, 'wpcargo_comments', 'Sample Comments Here...');
        }
    }
    public static function update_table( ){
       /* global $wpdb;
        $options = get_option('wpcargo_option_settings');
        $table_name = $wpdb->prefix . 'wpcargo_custom_fields';

        // Add new table column "Class"
        if( version_compare( WPCARGO_CUSTOM_FIELD_VERSION, '5.0.0', '>=') && !get_option( 'wpccf_version_5_0_0_check' ) ){
            // Check if "Class" table column 
            $existing_columns = $wpdb->get_col("DESC {$table_name}", 0);
            if( !in_array( 'classes', $existing_columns ) ){
                $wpdb->query("ALTER TABLE $table_name ADD classes VARCHAR(60) NOT NULL DEFAULT ''");
                update_option( 'wpccf_version_5_0_0_check', 1 );
            }
        }*/

    }
    public static function add_conditional_logic_table_columns(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpcargo_custom_fields';
        $table_columns = $wpdb->get_col( "DESC " . $table_name, 0 );
        if( !in_array( 'condition_logic_enable', $table_columns) ){
            $condition_logic_enable_sql = "ALTER TABLE `{$table_name}` ADD `condition_logic_enable` TEXT NOT NULL AFTER `status`";
            $wpdb->query($condition_logic_enable_sql);
        }
        if( !in_array( 'condition_show_hide', $table_columns) ){
            $condition_show_hide_sql = "ALTER TABLE `{$table_name}` ADD `condition_show_hide` TEXT NOT NULL AFTER `condition_logic_enable`";
            $wpdb->query($condition_show_hide_sql);
        }
        if( !in_array( 'condition_options', $table_columns) ){
            $condition_options_sql = "ALTER TABLE `{$table_name}` ADD `condition_options` TEXT NOT NULL AFTER `condition_show_hide`";
            $wpdb->query($condition_options_sql);
        }
        if( !in_array( 'condition_list', $table_columns) ){
            $condition_list_sql = "ALTER TABLE `{$table_name}` ADD `condition_list` LONGTEXT NOT NULL AFTER `condition_options`";
            $wpdb->query($condition_list_sql);
        }
    }
    public static function add_html_calculator_table_columns(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpcargo_custom_fields';
        $table_columns = $wpdb->get_col( "DESC " . $table_name, 0 );
        if( !in_array( 'html', $table_columns) ){
            $condition_logic_enable_sql = "ALTER TABLE `{$table_name}` ADD `html` LONGTEXT NOT NULL AFTER `status`";
            $wpdb->query($condition_logic_enable_sql);
        }
		if( !in_array( 'uid', $table_columns) ){
            $condition_logic_enable_sql = "ALTER TABLE `{$table_name}` ADD `uid` VARCHAR(60) NOT NULL AFTER `status`";
            $wpdb->query($condition_logic_enable_sql);
        }
		if( !in_array( 'format', $table_columns) ){
            $condition_logic_enable_sql = "ALTER TABLE `{$table_name}` ADD `format` VARCHAR(100) NOT NULL AFTER `status`";
            $wpdb->query($condition_logic_enable_sql);
        }
		if( !in_array( 'formula', $table_columns) ){
            $condition_logic_enable_sql = "ALTER TABLE `{$table_name}` ADD `formula` TEXT NOT NULL AFTER `status`";
            $wpdb->query($condition_logic_enable_sql);
        }
		if( !in_array( 'currency', $table_columns) ){
            $condition_logic_enable_sql = "ALTER TABLE `{$table_name}` ADD `currency` TEXT NOT NULL AFTER `status`";
            $wpdb->query($condition_logic_enable_sql);
        }
        
    }
}
