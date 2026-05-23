<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcfe_dashboard_favicon_url(){
    return apply_filters( 'wpcfe_dashboard_favicon_url', WPCFE_URL."/assets/images/favicon.ico" );
}
function wpcfe_report_color_pallete(){
    $pallet = array(
        '263238', '7283a7', '6d4c41', '757575', '388e3c', '9ea441', '0288d1', '26a69a', '5e35b1', '4a148c', 'ad1457', '948649', 'ff5722', 'bf360c', 'b1736c', '00c853', '00b8d4', '004d40', '311b92', 'd50000', 'aa00ff', '4a148c', 'ff3d00', 'b9f6ca', 'BD70E6', '796BF2', 'E6AD87', '65732D', 'BCCD77', '593D2B', 'BCC6CC'
    );
    return apply_filters( 'wpcfe_report_color_pallete', $pallet );
}
function wpcfe_month_list(){
    return array(
        __('January', 'wpcargo-frontend-manager' ),
        __('February', 'wpcargo-frontend-manager' ),
        __('March', 'wpcargo-frontend-manager' ),
        __('April', 'wpcargo-frontend-manager' ),
        __('May', 'wpcargo-frontend-manager' ),
        __('June', 'wpcargo-frontend-manager' ),
        __('July ', 'wpcargo-frontend-manager' ),
        __('August', 'wpcargo-frontend-manager' ),
        __('September', 'wpcargo-frontend-manager' ),
        __('October', 'wpcargo-frontend-manager' ),
        __('November', 'wpcargo-frontend-manager' ),
        __('December', 'wpcargo-frontend-manager' ),
    );
}
function wpcfe_date_range_filter( ){
    return absint( get_option( 'wpcfe_date_range_filter', 60 ) );
}
function wpcfe_date_first_day( $date ){
    return date( 'Y-m', strtotime($date) ).'-01';
}
function wpcfe_date_last_day( $date ){
    return date( 'Y-m-t', strtotime($date) );
}
function wpcfe_formatted_date( $date ){
    return date( get_option('date_format'), strtotime($date) );
}
function wpcfe_first_last_date( $date = '' ){
    $dateString = strtotime( current_time( 'mysql' ) );
    if( !empty( $date ) ){
        $dateString = strtotime( $date );  
    }
    //Last date of current month.
    $lastDateOfMonth    = date("Y-m-t", $dateString);
    $firstDateOfMonth   = date("Y-m-", $dateString).'01';
    return array(
        'first' => $firstDateOfMonth,
        'last'  => $lastDateOfMonth
    );
}
function wpcfe_print_barcode_sizes( $type = '' ){
    $_height    = 80;
    $_width     = 250;
    if( 'waybill' == $type ){
        $_height    = 120;
        $_width     = 300;
    }

    $default_size = array( 'height' => $_height, 'width' => $_width );
    if( function_exists( 'wpcargo_print_barcode_sizes' ) && !empty($type) ){
        $barcode_size = wpcargo_print_barcode_sizes();
        if( !array_key_exists( $type, $barcode_size ) ){
            return $default_size;
        }
        $height = $barcode_size[$type]['height'] ? $barcode_size[$type]['height'] : $_height ;
        $width  = $barcode_size[$type]['width'] ? $barcode_size[$type]['width'] : $_width ;
        return array( 'height' => $height, 'width' => $width );
    }
    return $default_size;
}

function wpcef_get_dates( $date_from, $date_to ){
    // Date format : YYYY-MM-DD
    $dates = array();
    $period = new DatePeriod(
        new DateTime($date_from),
        new DateInterval('P1D'),
        new DateTime($date_to)
    );
    foreach ($period as $key => $value) {
        $_date = $value->format('Y-m-d');
        if( in_array( $_date, $dates ) ){
            continue;
        }
        $dates[] = $_date;  
    }
    $dates[] = $date_to;  
    return $dates;
}

// Common Functions
function wpcfe_field_generator( $field_data, $field_meta, $value = '', $class='' ){
	$required = $field_data['required'] == 'true' ? 'required' : '';    
	if( $field_data['field'] == 'textarea' ){
		$field = '<textarea class="'.$class.' '.$field_meta.'" name="'.$field_meta.'" '.$required.'>'.$value.'</textarea>';
	}elseif( $field_data['field'] == 'select' ){
		$field = '<select class="'.$class.' '.$field_meta.'" name="'.$field_meta.'" '.$required.'>';
		$field .= '<option value="">'.esc_html__('-- Select Type --','wpcargo').'</option>';
		if( !empty( $field_data['options'] ) ){
			foreach ( $field_data['options'] as $_value) {
				$field .= '<option value="'.trim($_value).'" '.selected( $value, trim($_value), false ).'>'.trim($_value).'</option>';
			}
		}
		$field .= '</select>';
	}elseif( $field_data['field'] == 'radio' ){
		if( !empty( $field_data['options'] ) ){
			$field      = '';
			foreach ( $field_data['options'] as $_value) {
                $checked    = $value == $_value ? 'checked' : '' ;
				$field .= '<p><input class="'.$class.' '.$field_meta.' '.$field_meta.'_'.$_value.'" type="'.$field_data['field'].'" name="'.$field_meta.'" value="'.$_value.'" '.$checked.' '.$required.'>';
				$field .= '<label for="'.$field_meta.'_'.$_value.'">'.$_value.'</label></p>';
			}
		}
	}elseif( $field_data['field'] == 'checkbox' ){    
        if( empty( $value ) ){
            $value = array();
        }else{
            $value = is_array( $value ) ? $value : array_map( 'trim', explode(",", $value ) );
        }
		if( !empty( $field_data['options'] ) ){
			$field = '';
			foreach ( $field_data['options'] as $_value) {
                $checked    = in_array( $_value, $value ) ? 'checked' : '' ;
				$field .= '<p><input class="'.$class.' '.$field_meta.' '.$field_meta.'_'.$_value.'" type="'.$field_data['field'].'" name="'.$field_meta.'" value="'.$_value.'" '.$checked.' '.$required.'>';
				$field .= '<label for="'.$field_meta.'_'.$_value.'">'.$_value.'</label></p>';
			}
		}
	}else{
		$field = '<input class="'.$class.' '.$field_meta.'" type="'.$field_data['field'].'" name="'.$field_meta.'" value="'.$value.'" '.$required.'>';
	}
	return $field;
}
function wpcfe_to_slug( $string = '' ){
    $string = strtolower( preg_replace('/\s+/', '_', trim( $string ) ) );
    return substr( preg_replace('/[^A-Za-z0-9_\-]/', '', $string ), 0, 60 );
}
function wpcfe_print_paper( ){
    $sizes = array(
        'label' => array(
            'size' => 'A5',
            'orient' => 'portrait'
        ),
        'invoice' => array(
            'size' => 'Letter',
            'orient' => 'portrait'
        ),
        'waybill' => array(
            'size' => wpcfe_waybill_paper_size(),
            'orient' => wpcfe_waybill_paper_orient()
        ),
        'bol' => array(
            'size' => 'A4',
            'orient' => 'portrait'
        ),
     
    );
    return apply_filters( 'wpcfe_print_paper_size', $sizes );
}
function wpcfe_print_options(){
    $options = array(
        //'invoice'   => esc_html__('Invoice', 'wpcargo-frontend-manager'),
        //'label'     => esc_html__('Label', 'wpcargo-frontend-manager'),
        'waybill'   => esc_html__('Etiqueta para el paquete', 'wpcargo-frontend-manager')
    );
    return apply_filters( 'wpcfe_print_options', $options );
}
function wpcfe_print_cfields( $flag = '' ){
    global $wpdb;
    $table_prefix = $wpdb->prefix;
    //** Flag value Parameter
    //* @shipper_info
    //* @receiver_info
    //* @shipment_info
    $result_fields = $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "%'.$flag.'%" ORDER BY ABS(weight)', ARRAY_A );
    $fields = array();
    $user_role = array( 'useraccess_not_logged_in' );
    if( is_user_logged_in() ){
        $current_user = wp_get_current_user();
        $user_role = $current_user->roles;
    }
    $counter = 0;
    foreach ($result_fields as $value) {
        $flags 				= maybe_unserialize( $value['display_flags'] ) ? maybe_unserialize( $value['display_flags'] ) : array() ;
        $role_intersected 	= array_intersect($flags, $user_role);
        if( !empty( $role_intersected ) && count( $role_intersected ) <= count( $user_role )  ){
            continue;
        }
        $fields[$counter] = $value;
        $counter++;
    }
    return $fields;
}
function wpcfe_print_data( $flag = '', $shipment_id = 0, $attachment_image = true ){
    global $wpcargo;
    $field_keys = wpcfe_print_cfields( $flag );
    ob_start();
    if( !empty( $field_keys ) ){
        foreach( $field_keys as $field ){
            $field_data = maybe_unserialize( get_post_meta( $shipment_id, $field['field_key'], TRUE ) );
            if( is_array( $field_data ) ){
                $field_data = implode(", ", $field_data);
            }
            if( $field['field_type'] == 'file' ){
                $files = array_filter( array_map( 'trim', explode(",", $field_data) ) );
                if( !empty( $files ) ){
                    ?>
                    <div class="wpccfe-files-data">
                        <label><?php echo stripslashes( $field['label'] ); ?></label>
                        <div id="wpcargo-gallery-container_<?php echo $field['id'];?>">
                            <ul class="wpccf_uploads">
                                <?php
                                    foreach ( $files as $file_id ) {
                                        $att_meta = wp_get_attachment_metadata( $file_id );
                                        ?>
                                        <li class="image">
                                            <a href="<?php echo wp_get_attachment_url($file_id); ?>" download>
                                                <?php if( $attachment_image ): ?>
                                                <?php echo wp_get_attachment_image($file_id, 'thumbnail', TRUE); ?>
                                                <?php endif; ?>
                                                <span class="img-title" title="<?php echo get_the_title($file_id); ?>"><?php echo get_the_title($file_id); ?></span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                }
            }elseif( $field['field_type'] == 'url' ){
                $url_data = maybe_unserialize( get_post_meta( $shipment_id, $field['field_key'], TRUE ) );
                $target   = count( $url_data ) > 2 ? '_blank' : '' ;
                $url 	  = $url_data[1] ? $url_data[1] : '#' ;
                $label 	  = $url_data[0];
                ?><p><?php echo stripslashes( $field['label'] ); ?>: <a href="<?php echo $url; ?>" target="<?php echo $target; ?>"><?php echo $label; ?></a></p><?php
            }else{
                ?><p><?php echo stripslashes( $field['label'] ); ?>: <?php echo $field_data; ?></p><?php
            }	
        }
    }
    $output = ob_get_clean();
    return $output;
}

//Print_label function dynamic
function wpcfe_label_print_data( $flag = '', $shipment_id = 0, $attachment_image = true ){
    global $wpcargo;
    $field_keys = wpcfe_print_cfields( $flag );
    ob_start();
    if( !empty( $field_keys ) ){
        foreach( $field_keys as $field ){
            $field_data = maybe_unserialize( get_post_meta( $shipment_id, $field['field_key'], TRUE ) );
            if( $field['field_key'] == 'wpcargo_receiver_name'){
                continue;
            }
            if( is_array( $field_data ) ){
                $field_data = implode(", ", $field_data);
            }
            if( $field['field_type'] == 'file' ){
                $files = array_filter( array_map( 'trim', explode(",", $field_data) ) );
                if( !empty( $files ) ){
                    ?>
                    <div class="wpccfe-files-data">
                        <label><?php echo stripslashes( $field['label'] ); ?></label>
                        <div id="wpcargo-gallery-container_<?php echo $field['id'];?>">
                            <ul class="wpccf_uploads">
                                <?php
                                    foreach ( $files as $file_id ) {
                                        $att_meta = wp_get_attachment_metadata( $file_id );
                                        ?>
                                        <li class="image">
                                            <a href="<?php echo wp_get_attachment_url($file_id); ?>" download>
                                                <?php if( $attachment_image ): ?>
                                                <?php echo wp_get_attachment_image($file_id, 'thumbnail', TRUE); ?>
                                                <?php endif; ?>
                                                <span class="img-title" title="<?php echo get_the_title($file_id); ?>"><?php echo get_the_title($file_id); ?></span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                }
            }elseif( $field['field_type'] == 'url' ){
                $url_data = maybe_unserialize( get_post_meta( $shipment_id, $field['field_key'], TRUE ) );
                $target   = count( $url_data ) > 2 ? '_blank' : '' ;
                $url 	  = $url_data[1] ? $url_data[1] : '#' ;
                $label 	  = $url_data[0];
                ?><p><?php echo stripslashes( $field['label'] ); ?>: <a href="<?php echo $url; ?>" target="<?php echo $target; ?>"><?php echo $label; ?></a></p><?php
            }else{
                if( $field['field_key'] == 'wpcargo_shipper_email' || $field['field_key'] == 'wpcargo_receiver_email' ){
                    ?><p><?php echo $field_data; ?></p><?php
                }else{
                    ?><p><?php echo strtoupper( $field_data );?></p><?php
                }
            }	
        }
    }
    $output = ob_get_clean();
    return $output;
}

function wpcfe_get_users( $role ){
    global $wpcargo;
    $user_args = array(
        'role__in'  => array($role),
        'orderby'   => 'display_name',
        'order'     => 'ASC'
    );
    $user_list  = get_users( apply_filters( "wpcfe_get_users_{$role}_args", $user_args ) );
    $users      = array();
    if( !empty( $user_list ) ){
        foreach ( $user_list as $user ) {
           $users[$user->ID] = $wpcargo->user_fullname( $user->ID );
        }
    }
    return apply_filters( "wpcfe_get_users_{$role}_list", $users );
}

// function wpcfe_get_clients(){
//     global $wpcargo;
//     $args = array(
//         'role__in'  => array('wpcargo_client'),
//         'orderby'   => 'display_name',
//         'order'     => 'ASC'
//     );
//     $args = apply_filters( 'wpcfe_get_clients_arguments', $args );
//     $users  = array();
//     if( !empty( get_users( $args ) ) ){
//         foreach ( get_users( $args ) as $user ) {
//            $users[$user->ID] = $wpcargo->user_fullname( $user->ID );
//         }
//     }
//     return $users;
// }

function wpcfe_get_cfsections_opt(){
    return !empty( get_option( 'wpcargo_cf_option_settings' ) ) ? get_option( 'wpcargo_cf_option_settings' ) : array(); 
}

function wpcfe_get_shipment_sections(){
    global $wpdb;
    $sections           = $wpdb->get_col( "SELECT `section` FROM `{$wpdb->prefix}wpcargo_custom_fields` GROUP BY `section` ORDER BY `weight`" );
    $formatted_section  = array();
    $cfsections_opt     = wpcfe_get_cfsections_opt();
    if( !empty( $sections ) ){
        foreach ( $sections as $section ) { 
            
            if( array_key_exists('wpc_cf_disable_shipper', $cfsections_opt) && $section == 'shipper_info' ){
                continue;
            }
            if( array_key_exists('wpc_cf_disable_receiver', $cfsections_opt) && $section == 'receiver_info' ){
                continue;
            }
            if( array_key_exists('wpc_cf_disable_shipment', $cfsections_opt) && $section == 'shipment_info' ){
                continue;
            }
            $header = '';
            if( $section == 'shipper_info' ){
                $header = apply_filters( 'wpcfe_shipper_label', __('Shipper Information', 'wpcargo-frontend-manager' ) ); 
            }elseif( $section == 'receiver_info' ){
                $header = apply_filters( 'wpcfe_receiver_label', __('Receiver Information', 'wpcargo-frontend-manager' ) );
            }elseif( $section == 'shipment_info' ){
                $header = apply_filters( 'wpcfe_shipment_label', __('Shipment Information', 'wpcargo-frontend-manager' ) );
            }else{
                $header = ucwords( str_replace('_', ' ', $section ?? '') );
            } 
            $formatted_section[$section] = $header;
        }
    }
    return apply_filters( 'wpcfe_shipment_sections', $formatted_section );
}
function  wpcfe_get_shipment_type( $shipment_id ){
    $shipment_type 	            = get_post_meta( $shipment_id, '__shipment_type', true ) ? get_post_meta( $shipment_id, '__shipment_type', true ) : '';
    $shipment_type_list 	    = wpcfe_shipment_type_list();
    $shipment_type_label        = isset( $shipment_type_list[$shipment_type] ) ? $shipment_type_list[$shipment_type] : __('Default', 'wpcargo-frontend-manager');
    return $shipment_type_label;
}
function wpcfe_dashboard_logo_url(){
	global $wpcargo;
	$logo_url = WPCFE_URL.'assets/images/wpcargo-logo.png';
	if( $wpcargo->logo ){
		$logo_url = $wpcargo->logo;
	}
	return $logo_url;
}
// Multiple Package settings
function wpcfe_package_shortcode(){
    $shortcode  = array( 
        '{current_page}' =>  __('Current page', 'wpcargo-frontend-manager' ),
        '{total_page}' =>  __('Total page', 'wpcargo-frontend-manager' ),
    );
    $fields     = wpcargo_package_fields();
    if( !empty( $fields ) ){
        foreach ($fields as $key => $value) {
            $shortcode['{'.$key.'}'] = $value['label'];
        }
    }
    return $shortcode;
}
function wpcfe_package_shortcode_map( $package, $current, $total ){
    $delimiter  = array("{", "}");
    $mapped     = array( $current, $total );
    $shortcodes = wpcfe_package_shortcode();
    unset( $shortcodes['{current_page}'] );
    unset( $shortcodes['{total_page}'] );
    $keys       = array_keys( $shortcodes );
    foreach ($keys as $key ) {
        $key    = trim( str_replace( $delimiter, '', $key ) );
        $value  = '';
        if( array_key_exists( $key, $package ) ){
            $value = $package[$key]; 
        }
        $mapped[] = $value;
    }
    return $mapped;
}

function wpcfe_mpack_enable(){
    $options = get_option( 'wpc_mp_settings' );
    if( !empty( $options ) && array_key_exists('wpc_mp_enable_admin', $options ) ){
        return true;
    }
    return false;
}
function wpcfe_mpack_dim_enable(){
    $options = get_option( 'wpc_mp_settings' );
    if( !empty( $options ) && array_key_exists('wpc_mp_enable_dimension_unit', $options ) ){
        return true;
    }
    return false;
}
function wpcfe_mpack_dim_unit(){
    $options = get_option( 'wpc_mp_settings' );
    if( !empty( $options ) && array_key_exists('wpc_mp_dimension_unit', $options ) ){
        return !empty( $options['wpc_mp_dimension_unit'] ) ? $options['wpc_mp_dimension_unit'] : 'cm';
    }
    return 'cm';
}
function wpcfe_mpack_weight_unit(){
    $options = get_option( 'wpc_mp_settings' );
    if( !empty( $options ) && array_key_exists('wpc_mp_weight_unit', $options ) ){
        return !empty( $options['wpc_mp_weight_unit'] ) ? $options['wpc_mp_weight_unit'] : 'kg';
    }
    return 'lbs';
}
function wpcfe_mpack_piece_type(){
    $options = get_option( 'wpc_mp_settings' );
    if( !empty( $options ) && array_key_exists('wpc_mp_piece_type', $options ) ){
        if( !empty( $options['wpc_mp_piece_type'] ) ){
            return array_filter( array_map( 'trim', explode( ',', $options['wpc_mp_piece_type'] ) ) );
        }else{
            return array();
        } 
    }
    return array();
}
function wpcfe_report_status( ){
    global $wpcargo;
    $status = apply_filters( 'wpcfe_report_status', $wpcargo->status );
    $status = is_array( $status ) ? $status : array();
    return array_values( array_unique( $status ) );
}
function wpcfe_admin_page(){
	$wpcfe_admin = get_option( 'wpcfe_admin' );
	if( wpcfe_has_wpml() ){
		global $wpdb;
		$current_language = ICL_LANGUAGE_CODE;
		$langs = icl_get_languages('skip_missing=0&orderby=KEY&order=DIR&link_empty_to=str');
		if( $current_language != 'en' ){
			$original_page = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id = $wpcfe_admin", OBJECT );
			$translated_page = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."icl_translations WHERE trid=".$original_page->trid." AND language_code='".$current_language."'", OBJECT );
			$wpcfe_admin = $translated_page->element_id;
		}
	}
	return $wpcfe_admin;
}
function wpcfe_customfont_enable(){
    $wpcfe_customfont_enable = get_option( 'wpcfe_customfont_enable' ) ? get_option( 'wpcfe_customfont_enable' ) : false;
    return $wpcfe_customfont_enable;
}
function wpcfe_nsequence_enable(){
    return get_option( 'wpcfe_nsequence_enable' ) ? get_option( 'wpcfe_nsequence_enable' ) : false;
}
function wpcfe_nsequence_start(){
    return get_option( 'wpcfe_nsequence_start' ) ? get_option( 'wpcfe_nsequence_start' ) : 0;
}
function wpcfe_nsequence_digit(){
    return get_option( 'wpcfe_nsequence_digit' ) ? get_option( 'wpcfe_nsequence_digit' ) : 6;
}
function wpcfe_bol_enable(){
    $wpcfe_bol_enable = get_option( 'wpcfe_bol_enable' ) ? get_option( 'wpcfe_bol_enable' ) : false;
    return $wpcfe_bol_enable;
}
function wpcfe_rtl_enable(){
    $wpcfe_rtl_enable = get_option( 'wpcfe_rtl_enable' ) ? get_option( 'wpcfe_rtl_enable' ) : false;
    return $wpcfe_rtl_enable;
}
function wpcfe_approval_registration(){
    return get_option( 'wpcfe_approval_registration' ) ? get_option( 'wpcfe_approval_registration' ) : false;
}
function wpcfe_disable_registration(){
    return get_option( 'wpcfe_disable_registration' ) ? get_option( 'wpcfe_disable_registration' ) : false;
}
function wpcfe_add_shipment_deactivated(){
    $add_shipment_active = get_option( 'wpcfe_add_shipment_deactivated' ) ? get_option( 'wpcfe_add_shipment_deactivated' ) : false;
    return $add_shipment_active;
}
function wpcfe_employee_all_access(){
    $all_access = get_option( 'wpcfe_employee_all_access' ) ? get_option( 'wpcfe_employee_all_access' ) : false ;
    return $all_access;
}
function wpcfe_client_can_add_shipment(){
    $return = get_option( 'wpcfe_client_can_add_shipment' ) ? get_option( 'wpcfe_client_can_add_shipment' ) : false ;
    return $return;
}
function wpcfe_default_status(){
    global $wpcargo;
    $falldown_status = array_shift( $wpcargo->status );
    $return = get_option( 'wpcfe_default_status' ) ? get_option( 'wpcfe_default_status' ) : $falldown_status ;
    return $return;
}
function wpcfe_waybill_paper_size(){
    $paper_size = get_option( 'wpcfe_waybill_paper_size' ) ? get_option( 'wpcfe_waybill_paper_size' ) : 'A4' ;
    return $paper_size;
}
function wpcfe_waybill_paper_orient(){
    $paper_orient = get_option( 'wpcfe_waybill_paper_orient' ) ? get_option( 'wpcfe_waybill_paper_orient' ) : 'portrait' ;
    return $paper_orient;
}
function wpcfe_enable_label_multiple_print(){
    $multiple_print = get_option( 'wpcfe_enable_label_multiple_print' ) ? get_option( 'wpcfe_enable_label_multiple_print' ) : 0 ;
    return $multiple_print;
}
function wpcfe_label_pagination_template(){
    $pagination_template = trim(get_option( 'wpcfe_label_pagination_template' )) ? trim(get_option( 'wpcfe_label_pagination_template' )) : '{current_page} of {total_page}' ;
    return $pagination_template;
}

function wpcfe_get_shipment_history( $shipment_id, $rows = 12 ){
    global $wpcargo;
    $history = !empty( $wpcargo->history( $shipment_id ) ) ? array_slice( array_reverse( $wpcargo->history( $shipment_id ) ), 0, $rows ) : array() ; 
    return $history;
}
function wpcfe_get_shipment_status( $shipment_id ){
    return get_post_meta( $shipment_id, 'wpcargo_status', true );
}
function get_wpcfe_order_shipment_number( $order_id ){
    if( !(int)$order_id ){
        return false;
    }
    $order 		= wc_get_order( $order_id );
    $items     	= $order->get_items();
    if( empty( $items ) ){
        return false;
    }
    foreach ( $items as $item ) {
        if( empty( $item->get_meta_data() ) ){
            return false;
        }
        $shipment =  $item->get_meta_data()[0]->value;
        if( is_wpcfe_shipment( $shipment ) ){
            return get_the_title( $shipment );
        }elseif( wpcfe_shipment_id( $shipment ) ) {
            return $shipment;
        }
        return false;
    }
}
function wpcfe_regroles_on_plugin_update(){
    if( version_compare( WPCFE_VERSION, '5.3.2' ) >= 0 ){
        wpcfe_regroles_on_plugin_activation();
    }
}
function wpcfe_regroles_on_plugin_activation() {
	add_role( 
		'wpcargo_pending_client', 
		esc_html__( 'Pending Client', 'wpcargo-frontend-manager'), 
		array( ) 
	);
}
function wpcfe_regroles_on_plugin_deactivation(){
	remove_role( 'wpcargo_pending_client' );
}

function is_wpcfe_shipment( $id ){
	global $wpdb;
	$sql 	= "SELECT `ID` FROM {$wpdb->posts} WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'wpcargo_shipment' AND `ID` = %d LIMIT 1";
    $sql    = apply_filters( 'is_wpcfe_shipment_sql', $wpdb->prepare( $sql, $id ), $id );
	$result = $wpdb->get_var( $sql );
	return $result;
}
function wpcfe_shipment_id( $shipment_number ){
    global $wpdb;
    $sql    = "SELECT `ID` FROM {$wpdb->posts} WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'wpcargo_shipment' AND `post_title` = %s LIMIT 1";
    $sql    = apply_filters( 'wpcfe_shipment_id_sql', $wpdb->prepare( $sql, $shipment_number ), $shipment_number );
    $result = $wpdb->get_var( $sql );
    return $result;
}
function wpcfe_get_field_list(  ){
    global $wpdb;
    $fields = $wpdb->get_results( "SELECT `field_key`, `label` FROM `{$wpdb->prefix}wpcargo_custom_fields`" );
    return $fields;
}
function is_user_shipment( $shipment_id ){
    $user_roles = wpcfe_current_user_role();
    $user_id    = get_current_user_id();
    $employee   = get_post_meta( $shipment_id, 'wpcargo_employee', true );
    $agent      = get_post_meta( $shipment_id, 'agent_fields', true );
    $driver     = get_post_meta( $shipment_id, 'wpcargo_driver', true );
    $shipper    = get_post_meta( $shipment_id, 'registered_shipper', true );
    $branch     = get_post_meta( $shipment_id, 'shipment_branch', true );
    $result = false;
    if( wpcfe_is_super_admin() ){
        $result = true;
    }elseif( in_array( 'wpcargo_branch_manager', $user_roles ) ){ // wpcargo_branch_manager
        $user_branch   = get_user_meta( get_current_user_id(), 'wpc_user_branch', true );
        $user_branch   = ( $user_branch ) ? $user_branch : 0 ; 
        if( $branch == $user_branch ){
            $result = true;
        }
    }elseif( in_array( 'cargo_agent', $user_roles ) && $agent == $user_id ){
        $result = true;
    }elseif( in_array( 'wpcargo_driver', $user_roles ) && $driver == $user_id ){
        $result = true;
    }elseif( in_array( 'wpcargo_client', $user_roles ) && $shipper == $user_id ){
        $result = true;
    }elseif( in_array( 'wpcargo_employee', $user_roles ) && $employee == $user_id ){
        $result = true;
    }elseif( in_array( 'wpcargo_client', $user_roles ) && $shipper == $user_id ){
        $result = true;
    }
    return apply_filters( 'wpcfe_is_user_shipment', $result, $shipment_id );
}

function wpcfe_is_super_admin(  ){
    $user_roles     = wpcfe_current_user_role();
    $admin_role     = array( 'administrator' );
    if( wpcfe_employee_all_access() ){
        $admin_role[] = 'wpcargo_employee';
    }
    $admin_role     = apply_filters( 'wpcfe_super_admin_roles', $admin_role );
    $result         = false;
    if( array_intersect( $admin_role , $user_roles ) ){
        $result = true;
    }
    return $result;
}

function can_wpcfe_update_shipment_number( ){
    return apply_filters( 'can_wpcfe_update_shipment_number', wpcfe_is_super_admin() );
}

function can_wpcfe_add_shipment(  ){
    $user_roles     = wpcfe_current_user_role();
    $result         = false;
    if( array_intersect( wpcfe_add_shipment_role(), $user_roles ) ){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_add_shipment', $result );
}
function can_wpcfe_update_shipment(  ){
    $user_roles     = wpcfe_current_user_role();
    $result         = false;
    if( array_intersect( wpcfe_update_shipment_role(), $user_roles ) || in_array( 'administrator', $user_roles )){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_update_shipment', $result );
}
function can_wpcfe_delete_shipment(  ){
    $user_roles     = wpcfe_current_user_role();
    $result         = false;
    if( array_intersect( wpcfe_delete_shipment_role(), $user_roles ) || in_array( 'administrator', $user_roles )){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_delete_shipment', $result );
}
function can_wpcfe_access_dashboard( ){
    $user_roles     = wpcfe_current_user_role();
    $result         = false;
    if( array_intersect( wpcfe_access_dashboard_role(), $user_roles ) || in_array( 'administrator', $user_roles )){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_access_dashboard', $result );
}
function wpcfe_add_shipment_role(){
    $roles = array('wpcargo_employee', 'administrator', 'cargo_agent');
    if( wpcfe_client_can_add_shipment() ){
        $roles[] = 'wpcargo_client'; 
    }
    return apply_filters( 'wpcfe_add_shipment_role', $roles );
}
function wpcfe_assign_driver_roles( ){
    return apply_filters( 'wpcfe_assign_driver_roles', array('wpcargo_employee', 'administrator', 'cargo_agent', 'wpcargo_branch_manager') );
}
function wpcfe_assign_manager( ){
    return apply_filters( 'wpcfe_assign_manager', array('wpcargo_employee', 'administrator') );
}
function wpcfe_assign_agent( ){
    return apply_filters( 'wpcfe_assign_agent', array('wpcargo_employee', 'administrator', 'wpcargo_branch_manager') );
}
function wpcfe_assign_client( ){
    return apply_filters( 'wpcfe_assign_client', array('wpcargo_employee', 'administrator', 'wpcargo_branch_manager' ) );
}
function wpcfe_can_edit_fields_roles( ){
    return apply_filters( 'wpcfe_can_edit_fields_roles', array('wpcargo_employee', 'administrator', 'wpcargo_branch_manager') );
}
function wpcfe_update_shipment_role(){
    $update_shipment_role = get_option( 'wpcfe_update_shipment_role' ) ? get_option( 'wpcfe_update_shipment_role' ) : array( 'administrator', 'wpcargo_employee' ) ;
    if( !in_array( 'administrator', $update_shipment_role ) ){
        $update_shipment_role[] = 'administrator';
    }
    return $update_shipment_role;
}
function wpcfe_delete_shipment_role(){
    $delete_shipment_role = get_option( 'wpcfe_delete_shipment_role' ) ? get_option( 'wpcfe_delete_shipment_role' ) : array( 'administrator', 'wpcargo_employee' ) ;
    if( !in_array( 'administrator', $delete_shipment_role ) ){
        $delete_shipment_role[] = 'administrator';
    }
    return $delete_shipment_role;
}
function wpcfe_access_dashboard_role(){
    $access_dashboard_role = get_option( 'wpcfe_access_dashboard_role' ) ? get_option( 'wpcfe_access_dashboard_role' ) : array( 'administrator', 'wpcargo_employee', 'cargo_agent', 'wpcargo_client' ) ;
    if( !in_array( 'administrator', $access_dashboard_role ) ){
        $access_dashboard_role[] = 'administrator';
    }
    return $access_dashboard_role;
}
function wpcfe_current_user_role(){
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    return $user_roles;
}
function wpcfe_can_edit_fields( ){
    if( array_intersect( wpcfe_can_edit_fields_roles(), wpcfe_current_user_role() ) ){
        return true;
    }
    return false;
}
function can_wpcfe_client_assign_user( ){
    return apply_filters( 'can_wpcfe_client_assign_user', false );
}
function can_wpcfe_assign_agent( ){
    $result = false;
    if( array_intersect( wpcfe_assign_agent(), wpcfe_current_user_role() ) ){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_assign_agent', $result );
}
function can_wpcfe_assign_client( ){
    $result = false;
    if( array_intersect( wpcfe_assign_client(), wpcfe_current_user_role() ) ){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_assign_client', $result );
}
function can_wpcfe_assign_manager( ){
    $result = false;
    if( array_intersect( wpcfe_assign_manager(), wpcfe_current_user_role() ) ){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_assign_manager', $result );
}
function can_wpcfe_assign_driver( ){
    $result = false;
    if( array_intersect( wpcfe_assign_driver_roles(), wpcfe_current_user_role() ) ){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_assign_driver', $result );
}
function can_wpcfe_assign_employee( ){
    $result = false;
    if( in_array( 'administrator', wpcfe_current_user_role() ) ){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_assign_employee', $result );
}
function is_wpcfe_agent(){
    if( in_array( 'cargo_agent', wpcfe_current_user_role() ) ){
        return true;
    }
    return false;
}
function is_wpcfe_client(){
    if( in_array( 'wpcargo_client', wpcfe_current_user_role() ) ){
        return true;
    }
    return false;
}
function wpcfe_shipment_type_list(){
	$shipment_type = array(
        'wpcargo_default' => __('Default', 'wpcargo-frontend-manager')
    );
	return apply_filters( 'wpcfe_shipment_type_list', $shipment_type );
}
/*
 * Save shipment information
 * Parameter
 * @ data - POST value ( array() )
 * @ shipment_id =  post id
 */
function wpcfe_save_shipment( $data, $shipment_id = 0 ){
    global $wpcargo, $WPCCF_Fields;
    
    do_action( 'before_wpcfe_save_shipment', $shipment_id, $data );

    $meta_keys       = $WPCCF_Fields->get_field_key_list();
    if( $shipment_id  ){
        $shipment_number = apply_filters( 'wpcfe_shipment_number_update',  get_the_title($shipment_id), $data );
    }else{
        $shipment_number = apply_filters( 'wpcfe_shipment_number',  $wpcargo->create_shipment_number(), $data );
    }
    if( $shipment_id ){
        // Check if can Shipment update
        if( !is_user_shipment( $shipment_id ) ) {
            $_POST['wpcfe-notification'] = array(
                'status'    => 'danger',
                'icon'      => 'exclamation',
                'message'   => __('Something went wrong saving your shipment. Please reload and try again', 'wpcargo-frontend-manager' )
            );
            return false;
        }

        if( can_wpcfe_update_shipment_number() ){
            // Create post object
            $shipment_arg = array(
                'ID'           => $shipment_id,
                'post_title'    => wp_strip_all_tags( sanitize_text_field( $shipment_number ) ),
            );
            $post_id = wp_update_post( $shipment_arg );
        }else{
            $post_id = $shipment_id;
        }   
    }else{
        // Create post object
        $shipment_arg = array(
          'post_title'    => wp_strip_all_tags( sanitize_text_field( $shipment_number ) ),
          'post_type'     => 'wpcargo_shipment',
          'post_status'   => 'publish',
        );
        // Insert the post into the database
        $post_id = wp_insert_post( $shipment_arg );
    }
    if( $post_id && !empty( $meta_keys ) && ( can_wpcfe_update_shipment() || can_wpcfe_add_shipment() ) ){
		$data_keys = array_keys($data);

        foreach ( $data as $key => $value ) {
            // Check if meta key exist
            if( !in_array( $key, $meta_keys ) ){
               continue;	
            }
            $value = is_array(  $value ) ?  $value : sanitize_text_field( $value );
            update_post_meta( $post_id, $key, $value );
        }
		
		//Custom field key saving
		foreach( $meta_keys as $_key ){
			if( in_array( $_key, $data_keys) ){
				continue;
			}
			$type = wpccf_get_field_type( $_key );
			
			if( $type == 'checkbox' ){
				update_post_meta( $post_id, $_key, '' );
			}

		}
        do_action( 'after_wpcfe_save_shipment', $post_id, $data );
    }
    if( $post_id ){
        $_POST['wpcfe-notification'] = array(
            'status'    => 'success',
            'icon'      => 'check',
            'message'   => __('Shipment ', 'wpcargo-frontend-manager' ).' '.get_the_title( $post_id ).' '.__(' has been successfully saved.', 'wpcargo-frontend-manager' )
        );
    }else{
        $_POST['wpcfe-notification'] = array(
            'status'    => 'danger',
            'icon'      => 'exclamation',
            'message'   => __('Something went wrong saving your shipment. Please reload and try again', 'wpcargo-frontend-manager' )
        );
    }
}
/*
 * Display table header
 * Parameter
 * @ shipper
 * @ receiver
 */
function wpcfe_table_header( $section ){
    $shipper_column = get_option( $section.'_column');
    $header_data    = array();
	if( !$shipper_column ){
		if( $section == 'shipper' ){
			$header_data = array(
				'label' => __('Shipper Name', 'wpcargo-frontend-manager' ),
				'field_key' => 'wpcargo_shipper_name'
			);
		}elseif( $section == 'receiver' ){
			$header_data = array(
				'label' => __('Receiver Name', 'wpcargo-frontend-manager' ),
				'field_key' => 'wpcargo_receiver_name'
			);
		}
	}else{
        if( function_exists('wpccf_get_field_by_metakey') ){
            $header_data = wpccf_get_field_by_metakey( $shipper_column );
        }
	}	
    return apply_filters( 'wpcfe_table_header', $header_data, $section );
}
/*
 * get Options metadata
 */
function wpcfe_get_meta_values( $key = '', $value = '', $type = 'wpcargo_shipment', $status = 'publish' ) {
    global $wpdb;
    if( empty( $key ) ){
        return;
    }
    $value ='%'.$value.'%';
    $sql = $wpdb->prepare( "
        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '%s'
        AND pm.meta_value LIKE '%s'
        AND p.post_status = '%s'
        AND p.post_type = '%s'
        GROUP BY pm.meta_value
    ", $key, $value, $status, $type );
    $sql    = apply_filters( 'wpcfe_get_meta_values_sql', $sql, $key, $value );
    $r      = $wpdb->get_col( $sql );
    return apply_filters( 'wpcfe_get_meta_values', $r, $key, $value );
}
// My Account Functions
function wpcfe_user_avatar( $size = 128, $class = "photo-inner" ){
    $current_user = wp_get_current_user();
    echo get_avatar( $current_user->ID, $size, '', '', array( 'class'=> $class ) );
}
function wpcfe_user_avatar_url(){
    $current_user       = wp_get_current_user();
    $wpcdm_user_avatar  = get_user_meta( $current_user->ID, 'wpcargo_user_avatar', true );
    return $wpcdm_user_avatar;
}
// Dashboard filter query
function wpcfe_save_report( $shipment_id, $from_status = '', $to_status = '' ){
    global $wpcargo, $wpdb;
    // Disabled this function
    return apply_filters( 'wpcfe_save_report', false );

    if( ($from_status == $to_status ) || empty( $to_status ) ){
        return false;
    }
    $current_date   = current_time('mysql');
    $save_repost    = $wpdb->insert(
        $wpdb->prefix . WPCFE_DB_REPORTS,
        array(
            'post_id' 		=> $shipment_id,
            'post_date'     => $current_date,
            'from_status'   => $from_status,
            'to_status'     => $to_status,
            'updated_by'    => $wpcargo->user_fullname( get_current_user_id() )
        ),
        array(
            '%d', '%s', '%s', '%s', '%s'
        )
    );
}
function wpcfe_get_report_count( $date, $status ){
    global $wpcargo, $wpdb;
    $parameter  = array( "Paid" );
    $user_roles = wpcfe_current_user_role();
    $date_start = $date.' 00:00:00';
    $date_end   = $date.' 23:59:59';
    $parameter  = array( $date_start, $date_end, $status );
    $sql = "SELECT COUNT(*) FROM {$wpdb->posts} AS tblpost";
    $sql .= " INNER JOIN {$wpdb->postmeta} AS tblstatus ON tblpost.ID = tblstatus.post_id";
    if( !wpcfe_is_super_admin() ){ 
        $sql .= " LEFT JOIN  {$wpdb->postmeta} AS tbluser ON tblpost.ID = tbluser.post_id";
    }
    $sql .= " WHERE tblpost.post_status LIKE 'publish' AND tblpost.post_type LIKE 'wpcargo_shipment' AND tblpost.post_date BETWEEN %s AND %s";
    $sql .= " AND tblstatus.meta_key LIKE 'wpcargo_status' AND tblstatus.meta_value LIKE %s";
    if( !wpcfe_is_super_admin() ){ 
        $meta_key = 'registered_shipper';
        if( in_array( 'wpcargo_branch_manager', $user_roles ) ){
            $meta_key = 'wpcargo_branch_manager';
        }elseif( in_array( 'cargo_agent', $user_roles ) ){
            $meta_key = 'agent_fields';
        }elseif( in_array( 'wpcargo_driver', $user_roles ) ){
            $meta_key = 'wpcargo_driver';
        }elseif( in_array( 'wpcargo_employee', $user_roles ) ){
            $meta_key = 'wpcargo_employee';
        }
        $sql .= " AND tbluser.meta_key LIKE %s AND tbluser.meta_value = %d";
        $parameter[] = $meta_key;
        $parameter[] = get_current_user_id();
    }
    $prepared_sql   = $wpdb->prepare( $sql, $parameter );
    $prepared_sql   = apply_filters( 'wpcfe_get_report_count_sql', $prepared_sql, $status, $date );
    return $wpdb->get_var( $prepared_sql );
}
function wpcfe_get_all_shipment_count( $date_start, $date_end ){
    global $wpdb;
    $user_roles = wpcfe_current_user_role();
    $parameter  = array( 'wpcargo_shipment', $date_start.' 00:00:00', $date_end.' 23:59:59' );
    $sql        = "SELECT count(tbl1.ID) 
        FROM `{$wpdb->prefix}posts` AS tbl1";
    if( !wpcfe_is_super_admin() ){ 
        $sql .= " LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl2 ON tbl1.ID = tbl2.post_id";
    }
    $sql .= " LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl3 ON tbl1.ID = tbl3.post_id";
    $sql .= " LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl4 ON tbl1.ID = tbl4.post_id";
    $sql .= " WHERE tbl1.post_status LIKE 'publish' 
        AND tbl1.post_type LIKE %s";
    $sql .= " AND tbl3.meta_key LIKE 'wpcargo_status'";
    $sql .= " AND tbl1.post_date BETWEEN %s AND %s";
    
    if( !wpcfe_is_super_admin() ){ 
        $meta_key = 'registered_shipper';
        if( in_array( 'wpcargo_branch_manager', $user_roles ) ){
            $meta_key = 'wpcargo_branch_manager';
        }elseif( in_array( 'cargo_agent', $user_roles ) ){
            $meta_key = 'agent_fields';
        }elseif( in_array( 'wpcargo_driver', $user_roles ) ){
            $meta_key = 'wpcargo_driver';
        }elseif( in_array( 'wpcargo_employee', $user_roles ) ){
            $meta_key = 'wpcargo_employee';
        }
        $sql .= " AND tbl2.meta_key LIKE %s AND tbl2.meta_value = %d";
        $parameter[] = $meta_key;
        $parameter[] = get_current_user_id();
    }
    $prepared_sql   = $wpdb->prepare( $sql, $parameter );
    $prepared_sql   = apply_filters( 'wpcfe_get_all_shipment_count_sql', $prepared_sql, $parameter );
    return $wpdb->get_var( $prepared_sql );
}
function wpcfe_get_shipment_status_count( $status, $date_start, $date_end ){
    global $wpdb;
    $user_roles = wpcfe_current_user_role();
    $parameter  = array( $status, $date_start.' 00:00:00', $date_end.' 23:59:59' );
    $sql = "SELECT count(tbl1.ID)
        FROM `{$wpdb->prefix}posts` AS tbl1 
        LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl2 ON tbl1.ID = tbl2.post_id";
    
    if( !wpcfe_is_super_admin() ){ 
        $sql .= " LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl3 ON tbl1.ID = tbl3.post_id";
    }
    $sql .= " WHERE tbl1.post_status LIKE 'publish' 
        AND tbl1.post_type  LIKE 'wpcargo_shipment' 
        AND tbl2.meta_key  LIKE 'wpcargo_status' 
        AND tbl2.meta_value  LIKE %s";
    $sql .= " AND tbl1.post_date BETWEEN %s AND %s";

    if( !wpcfe_is_super_admin() ){ 
        $meta_key = 'registered_shipper';
        if( in_array( 'wpcargo_branch_manager', $user_roles ) ){
            $meta_key = 'wpcargo_branch_manager';
        }elseif( in_array( 'cargo_agent', $user_roles ) ){
            $meta_key = 'agent_fields';
        }elseif( in_array( 'wpcargo_driver', $user_roles ) ){
            $meta_key = 'wpcargo_driver';
        }elseif( in_array( 'wpcargo_employee', $user_roles ) ){
            $meta_key = 'wpcargo_employee';
        }
        $sql .= " AND tbl3.meta_key LIKE %s AND tbl3.meta_value = %d";
        $parameter[] = $meta_key;
        $parameter[] = get_current_user_id();
    }
    $prepared_sql   = $wpdb->prepare( $sql, $parameter );
    $prepared_sql   = apply_filters( 'wpcfe_get_shipment_status_count_sql', $prepared_sql, $status );
    return $wpdb->get_var( $prepared_sql );
}
function wpcfe_dashboard_meta_query_filter_callback( $meta_query ){
    $user_roles = wpcfe_current_user_role();
    if( wpcfe_is_super_admin() ){
        //return $meta_query;
    }elseif( in_array( 'wpcargo_branch_manager', $user_roles ) ){ // wpcargo_branch_manager
        $user_branch   = get_user_meta( get_current_user_id(), 'wpc_user_branch', true );
        $user_branch   = ( $user_branch ) ? $user_branch : 0 ;
        $meta_query['__assignment']  = array(
			'key'       => 'wpcargo_branch_manager',
			'value'     => get_current_user_id(),
			'compare'   => '='
        );
    }elseif( in_array( 'cargo_agent', $user_roles ) ){
        $meta_query['__assignment'] = array(
            'key'       => 'agent_fields',
            'value'     => get_current_user_id(),
            'compare'   => '='
        );
    }elseif( in_array( 'wpcargo_driver', $user_roles ) ){
        $meta_query['__assignment'] = array(
            'key'       => 'wpcargo_driver',
            'value'     => get_current_user_id(),
            'compare'   => '='
        );
    }elseif( in_array( 'wpcargo_employee', $user_roles ) ){
        $meta_query['__assignment'] = array(
            'key'       => 'wpcargo_employee',
            'value'     => get_current_user_id(),
            'compare'   => '='
        );
    }else{
        $meta_query['__assignment'] = array(
            'key'       => 'registered_shipper',
            'value'     => get_current_user_id(),
            'compare'   => '='
        );
    }
    return $meta_query;
}
add_filter( 'wpcfe_dashboard_meta_query', 'wpcfe_dashboard_meta_query_filter_callback', 10, 1 );

/**
 * WordPress Bootstrap Pagination
 */
function wpcfe_bootstrap_pagination( $args = array() ) {
    $defaults = array(
        'range'           => 4,
        'custom_query'    => FALSE,
        'previous_string' => __( 'Previous', 'wpcargo-frontend-manager' ),
        'next_string'     => __( 'Next', 'wpcargo-frontend-manager' ),
        'before_output'   => '<nav class="post-nav" aria-label="'.__('Shipment Pagination', 'wpcargo-frontend-manager').'"><ul class="pagination pg-blue justify-content-center">',
        'after_output'    => '</ul></nav>'
    );
    
    $args = wp_parse_args( 
        $args, 
        apply_filters( 'wp_bootstrap_pagination_defaults', $defaults )
    );
    
    $args['range'] = (int) $args['range'] - 1;
    if ( !$args['custom_query'] )
        $args['custom_query'] = @$GLOBALS['wp_query'];
    $count = (int) $args['custom_query']->max_num_pages;
    $page  = intval( get_query_var( 'paged' ) );
    $ceil  = ceil( $args['range'] / 2 );
    
    if ( $count <= 1 )
        return FALSE;
    
    if ( !$page )
        $page = 1;
    
    if ( $count > $args['range'] ) {
        if ( $page <= $args['range'] ) {
            $min = 1;
            $max = $args['range'] + 1;
        } elseif ( $page >= ($count - $ceil) ) {
            $min = $count - $args['range'];
            $max = $count;
        } elseif ( $page >= $args['range'] && $page < ($count - $ceil) ) {
            $min = $page - $ceil;
            $max = $page + $ceil;
        }
    } else {
        $min = 1;
        $max = $count;
    }
    
    $echo = '';
    $previous = intval($page) - 1;
    $previous = esc_attr( get_pagenum_link($previous) );
    
    $firstpage = esc_attr( get_pagenum_link(1) );
    if ( $firstpage && (1 != $page) )
        $echo .= '<li class="previous page-item"><a class="page-link waves-effect waves-effect" href="' . $firstpage . '">' . __( 'First', 'wpcargo-frontend-manager' ) . '</a></li>';
    if ( $previous && (1 != $page) )
        $echo .= '<li class="page-item" ><a class="page-link waves-effect waves-effect" href="' . $previous . '" title="' . __( 'previous', 'wpcargo-frontend-manager') . '">' . $args['previous_string'] . '</a></li>';
    
    if ( !empty($min) && !empty($max) ) {
        for( $i = $min; $i <= $max; $i++ ) {
            if ($page == $i) {
                $echo .= '<li class="page-item active"><span class="page-link waves-effect waves-effect">' . str_pad( (int)$i, 2, '0', STR_PAD_LEFT ) . '</span></li>';
            } else {
                $echo .= sprintf( '<li class="page-item"><a class="page-link waves-effect waves-effect" href="%s">%002d</a></li>', esc_attr( get_pagenum_link($i) ), $i );
            }
        }
    }
    
    $next = intval($page) + 1;
    $next = esc_attr( get_pagenum_link($next) );
    if ($next && ($count != $page) )
        $echo .= '<li class="page-item"><a class="page-link waves-effect waves-effect" href="' . $next . '" title="' . __( 'next', 'wpcargo-frontend-manager') . '">' . $args['next_string'] . '</a></li>';
    
    $lastpage = esc_attr( get_pagenum_link($count) );
    if ( $lastpage ) {
        $echo .= '<li class="next page-item"><a class="page-link waves-effect waves-effect" href="' . $lastpage . '">' . __( 'Last', 'wpcargo-frontend-manager' ) . '</a></li>';
    }
    if ( isset($echo) )
        echo $args['before_output'] . $echo . $args['after_output'];
}
function wpcfe_get_total_results(){
    global $wpdb;
    $user_roles     = wpcfe_current_user_role();
	$shipper_data   = wpcfe_table_header('shipper');
    $receiver_data  = wpcfe_table_header('receiver');
    $add_shipment_role = wpcfe_add_shipment_role();
    if (($key = array_search( 'wpcargo_client', $add_shipment_role)) !== false) {
        unset($add_shipment_role[$key]);
    }
	
    // $sql = "SELECT tblPost.ID, tblPost.post_date, tblPost.post_title FROM `{$wpdb->prefix}posts` AS tblPost ";
    $sql = "SELECT tblPost.ID FROM `{$wpdb->prefix}posts` AS tblPost ";
    $sql .= "INNER JOIN `{$wpdb->prefix}postmeta` as tblMeta1 ON tblMeta1.post_id = tblPost.ID ";
    if( isset($_GET['shipper']) && !empty( $_GET['shipper'] ) ){
        $sql .= "INNER JOIN `{$wpdb->prefix}postmeta` as tblMeta3 ON tblMeta3.post_id = tblPost.ID ";
    }
    if( isset($_GET['receiver']) && !empty( $_GET['receiver'] ) ){
        $sql .= "INNER JOIN `{$wpdb->prefix}postmeta` as tblMeta4 ON tblMeta4.post_id = tblPost.ID ";
    }
    if( !array_intersect( $add_shipment_role, $user_roles ) ){
        $sql .= "INNER JOIN `{$wpdb->prefix}postmeta` as tblMeta5 ON tblMeta5.post_id = tblPost.ID ";
    }
    $sql .= "WHERE tblPost.post_type LIKE 'wpcargo_shipment' AND tblPost.post_status LIKE 'publish' ";
    if( isset($_GET['status']) && !empty( $_GET['status'] ) ){
        $sql .= "AND tblMeta1.meta_key LIKE 'wpcargo_status' AND tblMeta1.meta_value IN ( '".urldecode( $_GET['status'] )."' ) ";
    }
    if( isset($_GET['shipper']) && !empty( $_GET['shipper'] ) ){
        $sql .= "AND tblMeta3.meta_key LIKE 'registered_shipper' AND tblMeta3.meta_value = ".(int)$_GET['shipper']." ";
    }
    if( isset($_GET['receiver']) && !empty( $_GET['receiver'] ) ){
        $sql .= "AND tblMeta4.meta_key LIKE '".$receiver_data['field_key']."' AND tblMeta4.meta_value = ".(int)$_GET['receiver']." ";
    }
    if( array_intersect( $add_shipment_role, $user_roles ) ){
        $sql .= "";
    }elseif( in_array( 'wpcargo_branch_manager', $user_roles ) ){ // wpcargo_branch_manager
        $sql .= "AND tblMeta5.meta_key LIKE 'wpc_user_branch' AND tblMeta5.meta_value = ".get_current_user_id()." ";
    }elseif( in_array( 'cargo_agent', $user_roles ) ){
        $sql .= "AND tblMeta5.meta_key LIKE 'agent_fields' AND tblMeta5.meta_value = ".get_current_user_id()." ";
    }elseif( in_array( 'wpcargo_driver', $user_roles ) ){
        $sql .= "AND tblMeta5.meta_key LIKE 'wpcargo_driver' AND tblMeta5.meta_value = ".get_current_user_id()." ";
    }else{
        $sql .= "AND tblMeta5.meta_key LIKE 'registered_shipper' AND tblMeta5.meta_value = ".get_current_user_id()." ";
    }
    $sql .= "GROUP BY tblPost.ID ";
	
    return count( $wpdb->get_col( $sql ) );
}
function wpcfe_get_user_unseen_shipments(){
    global $wpdb;

    $current_user   = wp_get_current_user();
    $seen_metakey   = '_wpcfe_seen_'.$current_user->ID;
    $user_metakey   = '';
    $user_roles     = $current_user->roles;
    $is_admin       = in_array( 'administrator', $user_roles );

    if( wpcfe_is_super_admin() ){
        $is_admin = true;
    }
    
    if( in_array( 'wpcargo_branch_manager', $user_roles ) ){
        $user_metakey = 'wpcargo_branch_manager';
    }elseif( in_array( 'wpcargo_employee', $user_roles ) ){
        $user_metakey = 'wpcargo_employee';
    }elseif( in_array( 'wpcargo_driver', $user_roles ) ){
        $user_metakey = 'wpcargo_driver';
    }elseif( in_array( 'cargo_agent', $user_roles ) ){
        $user_metakey = 'agent_fields';
    }elseif( in_array( 'wpcargo_client', $user_roles ) ){
        $user_metakey = 'registered_shipper';
    }
  
    $sql = "SELECT ID";
    $sql .= " FROM `{$wpdb->posts}` AS tbl1";
    $sql .= " LEFT JOIN `{$wpdb->postmeta}` AS tbl2 ON  tbl1.ID = tbl2.post_id AND tbl2.meta_key = %s";

    if( !$is_admin ){
        $sql .= " LEFT JOIN `{$wpdb->postmeta}` AS tbl3 ON tbl1.ID = tbl3.post_id";
    }

    $sql .= " WHERE tbl1.post_type LIKE 'wpcargo_shipment' AND tbl1.post_status LIKE 'publish'";
    $sql .= " AND tbl2.post_id IS NULL";
    
    if( !$is_admin ){
        $sql .= " AND tbl3.meta_key LIKE %s AND tbl3.meta_value = %d";
        $sql .= " LIMIT 10";
        $sql = $wpdb->prepare( $sql, $seen_metakey, $user_metakey, $current_user->ID );
    }else{
        $sql .= " LIMIT 10";
        $sql = $wpdb->prepare( $sql, $seen_metakey );
    }
    $sql     = apply_filters( 'wpcfe_get_user_unseen_shipments_sql', $sql );
    $results = $wpdb->get_results( $sql, ARRAY_A );

    return count( $results );
}

function wpcfe_get_shipment_id( $shipment_number ){
    global $wpdb;
    $sql = "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` LIKE 'wpcargo_shipment' AND `post_status` LIKE 'publish' AND `post_title` LIKE %s";
    return $wpdb->get_var( $wpdb->prepare( $sql, trim( $shipment_number ) ) );
}
function wpcfe_include_template( $file_name ){
    $file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/wpcargo-frontend-manager/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPCFE_PATH.'templates/'.$file_name.'.php';
        $template_path  = apply_filters( "wpcfe_locate_template_{$file_slug}", $template_path );
    }
	return $template_path;
}
function wpcfe_personal_info_fields(){
	$user_roles = wpcfe_current_user_role();
	$wpcfe_personal_info_fields = array(
        'first_name' => array(
			'id'			=> 'first_name',
            'label'			=> __('First Name', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'first_name'
        ),
        'last_name' => array(
			'id'			=> 'last_name',
            'label'			=> __('Last Name', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'last_name'
        ),
        'phone'     => array(
			'id'			=> 'phone',
            'label'			=> __('Phone', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'phone'
        )
    );
    return apply_filters( 'wpcfe_personal_info_fields', $wpcfe_personal_info_fields );
}
function wpcfe_billing_address_fields(){
	$wpcfe_billing_address_fields = array(
        'billing_email' => array(
			'id'			=> 'billing_email',
            'label'			=> __('Email', 'wpcargo-frontend-manager'),
            'field'			=> 'email',
            'field_type'	=> 'email',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'billing_email'
        ),
        'billing_company' => array(
			'id'			=> 'billing_company',
            'label'			=> __('Company', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'billing_company'
        ),
        'billing_address_1' => array(
			'id'			=> 'billing_address_1',
            'label'			=> __('Address line 1', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'billing_address_1'
        ),
        'billing_address_2' => array(
			'id'			=> 'billing_address_2',
            'label'			=> __('Address line 2', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'billing_address_2'
        ),
        'billing_city' => array(
			'id'			=> 'billing_city',
            'label'			=> __('City', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'billing_city'
        ),
        'billing_postcode' => array(
			'id'			=> 'billing_postcode',
            'label'			=> __('Postcode / ZIP', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'billing_postcode'
        ),
        'billing_country' => array(
			'id'			=> 'billing_address_1',
            'label'			=> __('Country', 'wpcargo-frontend-manager'),
            'field'			=> 'select',
            'field_type'	=> 'select',
            'required'		=> false,
            'options'		=> wpcfe_country_list(),
			'field_data'	=> wpcfe_country_list(),
			'field_key'		=> 'billing_country'
        ),
        'billing_state' => array(
			'id'			=> 'billing_state',
            'label'			=> __('State / County', 'wpcargo-frontend-manager'),
            'field'			=> 'text',
            'field_type'	=> 'text',
            'required'		=> false,
            'options'		=> array(),
			'field_data'	=> array(),
			'field_key'		=> 'billing_state'
        ),
    );
    return apply_filters( 'wpcfe_billing_address_fields', $wpcfe_billing_address_fields );
}
function wpcfe_registered_styles(){
    $styles = array(
        'sgr_main', // simple-google-recaptcha - Plugin
        'wpcfe-bootstrap-styles',
        'wpcfe-font-awesome-styles',
        'wpcfe-select2-styles',
        'wpcfe-mdb-styles',
        'wpcfe-croppie-styles',
        'wpcfe-styles',
        'wpcfe-wpcfm-styles',
        'media-views',
        'imgareaselect',
        'wpcargo-styles',
        'wpcargo-custom-bootstrap-styles'
    );
    return apply_filters( 'wpcfe_registered_styles', $styles );
}
function wpcfe_registered_scripts(){
    $scripts = array(
        'jquery', 
        'wp-mediaelement', 
        'sgr_main', // simple-google-recaptcha - Plugin
        'wpcfe-dashboard-theme', 
        'wpcfe-popper-scripts', 
        'wpcfe-bootstrap-scripts', 
        'wpcfe-mdb-scripts',
        'wpcfe-bootstrap-datepicker-scripts',
        'wpcfe-select2-scripts',
        'wpcfe-croppie-scripts',
        'wpcfe-repeater-js',
        'wpcfe-datetime-scripts',
        'wpcfe-chart-scripts',
        'wpcfe-chart-util-scripts',
        'wpcfe-registration-scripts',
        'wpcfe-scripts',
        'media-editor',
        'media-audiovideo',
    );
    return apply_filters( 'wpcfe_registered_scripts', $scripts );
}

function wpc_orders_get_frontend_page(){
	global $wpdb;
	$sql 			= "SELECT `ID` FROM {$wpdb->prefix}posts WHERE `post_content` LIKE '%[wpcfe_orders]%' AND `post_status` LIKE 'publish' LIMIT 1";
	$shortcode_id 	= $wpdb->get_var( $sql );
	if( ! $shortcode_id && in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
		// Create post object
		$orders = array(
			'post_title'    => wp_strip_all_tags( __('Orders', 'wpcargo-frontend-manager') ),
			'post_content'  => '[wpcfe_orders]',
			'post_status'   => 'publish',
			'post_type'   	=> 'page',
			'post_name'		=> 'wpcfe-orders',
		);	
		// Insert the post into the database
		$shortcode_id = wp_insert_post( $orders );		
	}
	if( $shortcode_id ){
		update_post_meta( $shortcode_id, '_wp_page_template', 'dashboard.php');
	}
	return $shortcode_id;
}
function wpc_profile_get_frontend_page(){
	global $wpdb;
	$sql 			= "SELECT `ID` FROM {$wpdb->prefix}posts WHERE `post_content` LIKE '%[wpcfe_profile]%' AND `post_status` LIKE 'publish' LIMIT 1";
	$shortcode_id 	= $wpdb->get_var( $sql );
		if( ! $shortcode_id ){
		// Create post object
		$profile = array(
			'post_title'    => wp_strip_all_tags( __('Profile', 'wpcargo-frontend-manager') ),
			'post_content'  => '[wpcfe_profile]',
			'post_status'   => 'publish',
			'post_type'   	=> 'page',
			'post_name'		=> 'wpcfe-profile',
		);	
		// Insert the post into the database
		$shortcode_id = wp_insert_post( $profile );
	}
	if( $shortcode_id ){
		update_post_meta( $shortcode_id, '_wp_page_template', 'dashboard.php');
	}
	return $shortcode_id;
}

function wpcfe_after_sidebar_menu_items(){
	$user_roles = wpcfe_current_user_role();
    // Shipments
    // $unseen_shipments  = count(wpcfe_get_user_unseen_shipments());
    // $unseen  = $unseen_shipments > 9 ? '9&#43;' : $unseen_shipments ;
    // $menu_label        = __('Shipments', 'wpcargo-frontend-manager');
    // if( $unseen_shipments ){
    //     $menu_label        = sprintf( __('Shipments <span class="badge badge-pill bg-danger align-top">%s</span>', 'wpcargo-frontend-manager'), $unseen );
    // }
    $menu_items = array();
	// $menu_items = array(
	// 	'shipments-menu' => array(
	// 		'page-id' => wpcfe_admin_page(),
	// 		'label' => $menu_label,
	// 		'permalink' => get_the_permalink( wpcfe_admin_page() ),
	// 		'icon' => 'fa-cubes'
	// 	)
	// );
	if ( in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ) ) && in_array( 'wpcargo_client', $user_roles ) && enable_woointeg_order() ) {
		$menu_items['orders-menu'] = array(
			'page-id' => wpc_orders_get_frontend_page(),
			'label' => __('Orders', 'wpcargo-frontend-manager'),
			'permalink' => get_the_permalink( wpc_orders_get_frontend_page() ),
			'icon' => 'fa-list'
		);
	}
	return apply_filters( 'wpcfe_after_sidebar_menu_items', $menu_items );
}

function wpcfe_after_sidebar_menus(){
	$menu_items = array( );
	return apply_filters( 'wpcfe_after_sidebar_menus', $menu_items );
}

function enable_woointeg_order(){
    return apply_filters( 'enable_woointeg_order', false);
}

function wpcfe_get_orders(){
	$user_id = get_current_user_id();
	$customer_orders = get_posts( array(
                    'meta_key'    => '_customer_user',
                    'meta_value'  => $user_id,
                    'post_type'   => 'shop_order',
                    'post_status' => array_keys( wc_get_order_statuses() ),
                    'numberposts' => 2
                ));
	$wpcfe_orders = array();
	foreach( $customer_orders as $customer_order ){
		$order_id = $customer_order->ID;
		$order_date = $customer_order->post_date;
		$order_permalink = $customer_order->guid;
		$orders = wc_get_order( $order_id );
		$order_status = $orders->get_status();
		$order_items = $orders->get_items();
		foreach( $order_items as $order_item ){
			$order_total = $order_item->get_total();
		}
		$wpcfe_orders[$order_id] = array(
								'order-date' => $order_date,
								'order-permalink' => $order_permalink,
								'order-status' => $order_status,
								'order-total' => $order_total,
								'order-items' => $order_items,
							);
	}
	return apply_filters( 'wpcfe_get_orders', $wpcfe_orders );
}

function wpcfe_disable_unseen( ){
    $value = false;
    return apply_filters( 'wpcfe_disable_unseen', $value );
}


function override(){
    $value = true;
    return $value;
}
// add_filter( 'wpcfe_disable_unseen', 'override' );


