<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( WPCFE_PATH.'admin/includes/dompdf/autoload.inc.php' );
// require_once( WPCFE_PATH.'admin/includes/dompdf/lib/php-font-lib/src/FontLib/Autoloader.php' );
require_once( WPCFE_PATH.'admin/includes/dompdf/src/Options.php' );
// require_once( WPCFE_PATH.'admin/includes/dompdf/src/Autoloader.php' );

// Dompdf\WPCFE_Autoloader::register();
use Dompdf\Dompdf;
// use Dompdf\WPCFE_Options;
// Function helper
function wpcfe_print_template_path_helper_callback( $template_path, $print_type ){
    global $wpcargo_print_admin, $wpcargo_cf_form_builder;
    if( !file_exists( $template_path ) ){
        $waybill_template = class_exists( 'WPCargo_CF_Form_Builder' ) ? $wpcargo_cf_form_builder->print_label_url_callback( $template_path ) : $wpcargo_print_admin->print_label_template_callback();
        $template_path = $print_type == 'waybill' ? $waybill_template : WPCFE_PATH.'templates/print/'.$print_type.'.php'; 
    }
    

    return $template_path;
}

// Bulk Print AJAX handler
add_action( 'wp_ajax_wpcfe_bulkprint', 'wpcfe_bulkprint_ajax_callback' );
function wpcfe_bulkprint_ajax_callback(){
    try {
        global $wpdb, $WPCCF_Fields, $wpcargo;
        
        // Increase time limit and memory for bulk printing
        @ini_set('max_execution_time', 300);
        @ini_set('memory_limit', '512M');
        
        // Log the incoming request for debugging
        error_log( 'WPCargo Frontend Manager - Bulk Print Request: printType=' . (isset($_POST['printType']) ? $_POST['printType'] : 'not set') . ', shipments count=' . (isset($_POST['selectedShipment']) ? count($_POST['selectedShipment']) : 0) );
        
        // Validate required POST data
        if( empty($_POST['selectedShipment']) || empty($_POST['printType']) ){
            error_log( 'WPCargo Frontend Manager - Bulk Print Error: Missing required POST data' );
            echo json_encode( array() );
            wp_die();
        }
        
        $directory      = WPCFE_PATH.'admin/includes/file-container/';
        
        // Create directory if it doesn't exist
        if( !file_exists($directory) ){
            wp_mkdir_p($directory);
        }
        
        // Clean directory before adding new file
        foreach( glob($directory.'*.pdf') as $pdf_file){
            @unlink($pdf_file);
        }
        $wpcfe_pdf_dpi  = apply_filters( 'wpcfe_pdf_dpi', 160 );
        $shipment_ids   = $_POST['selectedShipment'];
        $print_type     = $_POST['printType'];
        $waybill_title 	= $print_type.'-'.time();
        
        // Validate print type exists
        $print_papers   = wpcfe_print_paper();
        if( !isset($print_papers[$print_type]) ){
            error_log( 'WPCargo Frontend Manager - Bulk Print Error: Invalid print type: ' . $print_type );
            echo json_encode( array() );
            wp_die();
        }
        $print_paper    = $print_papers[$print_type];
        
        error_log( 'WPCargo Frontend Manager - Starting HTML generation for ' . count($shipment_ids) . ' shipments' );
        
    // instantiate and use the dompdf class
    // $options 		= new WPCFE_Options();
    // $options->setDpi( $wpcfe_pdf_dpi );

    $dompdf 		= new Dompdf(  );
    $dompdf->set_option('isRemoteEnabled', true);
    
    $html_content = wpcfe_bulkprint_template_path( $shipment_ids, $waybill_title, $print_type );
    error_log( 'WPCargo Frontend Manager - HTML generated, length: ' . strlen($html_content) );
    
    $dompdf->loadHtml( $html_content );
    $dompdf->setPaper( $print_paper['size'], $print_paper['orient']);
    
    error_log( 'WPCargo Frontend Manager - Starting PDF render' );
    // Render the HTML as PDF
    $dompdf->render();
    error_log( 'WPCargo Frontend Manager - PDF rendered, adding pagination' );
    wpcfe_pdf_pagination( $dompdf, $print_type );

    // Output the generated PDF to Browser
    $output = $dompdf->output();
    $data_info = array();
    error_log( 'WPCargo Frontend Manager - Saving PDF file' );
    if( file_put_contents( $directory.$waybill_title.'.pdf', $output) ){
        error_log( 'WPCargo Frontend Manager - PDF file saved successfully' );
        $data_info = array(
            'file_url' => WPCFE_URL.'admin/includes/file-container/'.$waybill_title.'.pdf',
            'file_name' => $waybill_title
        );  
    }else{
        error_log( 'WPCargo Frontend Manager - Failed to save PDF file' );
    }
    echo json_encode( $data_info );
    } catch( Exception $e ) {
        error_log( 'WPCargo Frontend Manager - Bulk Print Exception: ' . $e->getMessage() );
        error_log( 'WPCargo Frontend Manager - Stack trace: ' . $e->getTraceAsString() );
        echo json_encode( array() );
    }
    wp_die();
}
function wpcfe_bulkprint_template_path( $shipment_ids, $waybill_title, $print_type ){
    ob_start();
    global $WPCCF_Fields, $wpcargo, $wpcargo_print_admin;
    if( wpcfe_enable_label_multiple_print() && $print_type == 'label' ){
        $print_type         = $print_type.'-packages';
    } 
    $custom_template_path   = get_stylesheet_directory() .'/wpcargo/'. $print_type.'.tpl.php';
    $mp_settings            = get_option('wpc_mp_settings');
    $setting_options        = get_option('wpcargo_option_settings');
    $logo                   = '';
    if( !empty( $setting_options['settings_shipment_ship_logo'] ) ){
        $logo 		= '<img style="width: 180px;" id="logo" src="'.$setting_options['settings_shipment_ship_logo'].'">';
    }
    if( get_option('wpcargo_label_header') ){
        $siteInfo = get_option('wpcargo_label_header');
    }else{
        $siteInfo  = $logo;
        $siteInfo .= '<h2 style="margin:0;padding:0;">'.get_bloginfo('name').'</h2>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 14px;">'.get_bloginfo('description').'</p>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 8px;">'.get_bloginfo('wpurl').'</p>';
    }
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?> <?php echo is_rtl() ? 'dir="rtl"' : '' ; ?>>
        <head>
            <title><?php echo $waybill_title; ?></title>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <?php do_action( 'wpcfe_print_html_head' ); ?>
            <style type="text/css">
                div.copy-section { border: 2px solid #000; margin-bottom: 18px; }
                .copy-section table { border-collapse: collapse; }
                .copy-section table td.align-center{ text-align: center; }
                .copy-section table td { border: 1px solid #000; }
                table tr td{ padding:6px; }
            </style>
        </head>
        <body>
            <?php
            if( !empty( $shipment_ids ) ){
                $counter        = 1;
                $shipment_num   = count( $shipment_ids );
                foreach ( $shipment_ids as $shipment_id ) {
                    $shipmentID             = $shipment_id;
                    $packages               = maybe_unserialize( get_post_meta( $shipmentID,'wpc-multiple-package', TRUE) );
                    $shipmentDetails 	= array(
                        'shipmentID'	=> $shipment_id,
                        'barcode'		=> $wpcargo->barcode( $shipment_id ),
                        'packageSettings'	=> $mp_settings,
                        'cargoSettings'	=> $setting_options,
                        'packages'		=> $packages,
                        'logo'			=> $logo,
                        'siteInfo'		=> $siteInfo
                    );
                    $template_path = wpcfe_print_template_path_helper_callback( $custom_template_path, $print_type );
                    $template_path = apply_filters( 'wpcfe_print_template_path_'.wpcfe_to_slug($print_type), $template_path, $shipment_id );
                    include( $template_path );
                    do_action( 'wpcfe_after_bulkprint_template', $counter, $shipment_num, $print_type );
                    $counter++;
                }   
            }
            ?>
        </body>
    </html>
    <?php
    $output = ob_get_clean();
	return $output;
}
// Print Shipment Functionality - Print Button with dropdown
add_action( 'wp_ajax_wpcfe_print_shipment', 'wpcfe_print_shipment_ajax_callback' );
add_action( 'wp_ajax_nopriv_wpcfe_print_shipment', 'wpcfe_print_shipment_ajax_callback' );
function wpcfe_print_shipment_ajax_callback(){
    global $wpdb, $WPCCF_Fields, $wpcargo;
    // Variables
    $wpcfe_pdf_dpi  = apply_filters( 'wpcfe_pdf_dpi', 160 );
    $shipment_id    = $_POST['shipmentID'];
    $print_type     = $_POST['printType'];

    $print_paper    = wpcfe_print_paper()[$print_type];
    $directory      = WPCFE_PATH.'admin/includes/file-container/';
    // Clean directory before adding new file
    foreach( glob($directory.'*.pdf') as $pdf_file){
  //      unlink($pdf_file);
    }
    $waybill_title  = $print_type.'-'.preg_replace("/[^A-Za-z0-9 ]/", '', get_the_title($shipment_id) ).'-'.time();

    // instantiate and use the dompdf class
    // $options 		= new WPCFE_Options();
    // $options->setDpi( $wpcfe_pdf_dpi );
    
    $dompdf 		= new Dompdf( );
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml( wpcfe_print_shipment_template_path( $shipment_id, $waybill_title, $print_type ) );
    
    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper( $print_paper['size'], $print_paper['orient']);

    // Render the HTML as PDF
    $dompdf->render();
    wpcfe_pdf_pagination( $dompdf, $print_type );
    // // Output the generated PDF to Browser
    $output = $dompdf->output();

 $data_info = array();
    if( file_put_contents( $directory.$waybill_title.'.pdf', $output) ){
        $data_info = array(
            'file_url' => WPCFE_URL.'admin/includes/file-container/'.$waybill_title.'.pdf',
            'file_name' => $waybill_title
        );  
    }
 
    echo json_encode( $data_info );
    wp_die();
}




// Template Path
function wpcfe_print_shipment_template_path( $shipment_id, $waybill_title, $print_type ){
    ob_start();
    global $WPCCF_Fields, $wpcargo;
    $shipmentID             = $shipment_id;
    $packages               = maybe_unserialize( get_post_meta( $shipmentID,'wpc-multiple-package', TRUE) );
    if( !empty( $packages ) && wpcfe_enable_label_multiple_print() && $print_type == 'label' ){
        $print_type         = $print_type.'-packages';
    }
    
    $custom_template_path   = get_stylesheet_directory() .'/wpcargo/'. $print_type.'.tpl.php';
    $mp_settings            = get_option('wpc_mp_settings');
    $setting_options        = get_option('wpcargo_option_settings');
    
    // Obtener el cliente (WPCargo Client) que creó el envío
    $shipment_post = get_post($shipmentID);
    $client_id = 0;
    
    // Primero intentar con el meta registered_shipper
    $registered_shipper = get_post_meta($shipmentID, 'registered_shipper', true);
    if (!empty($registered_shipper)) {
        $client_id = $registered_shipper;
    } else {
        // Si no existe, usar el autor del post
        $client_id = $shipment_post->post_author;
    }
    
    // Verificar que el usuario sea un WPCargo Client
    $user = get_userdata($client_id);
    $user_logo = '';
    
    if ($user && in_array('wpcargo_client', $user->roles)) {
        // Es un cliente, obtener su avatar
        $user_logo = get_user_meta($client_id, 'wpcargo_user_avatar', true);
    }
    
    // Configurar el logo
    $logo_profile = '';
    if (!empty($user_logo)) {
        $logo_profile = '<img style="width: 180px;" id="logo-profile" src="'.$user_logo.'">';
    }
    
    // Logo para la variable (usar logo de perfil del cliente si existe, sino el de configuración)
    $logo = '';
    if (!empty($user_logo)) {
        $logo = $logo_profile;
    } elseif (!empty($setting_options['settings_shipment_ship_logo'])) {
        $logo = '<img style="width: 180px;" id="logo" src="'.$setting_options['settings_shipment_ship_logo'].'">';
    }
    
    if( get_option('wpcargo_label_header') ){
        $siteInfo = get_option('wpcargo_label_header');
    }else{
        $siteInfo  = $logo;
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 18px;">'.get_bloginfo('name').'</p>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 14px;">'.get_bloginfo('description').'</p>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 8px;">'.get_bloginfo('wpurl').'</p>';
    }
    $shipmentDetails 	= array(
        'shipmentID'	=> $shipment_id,
        'barcode'		=> $wpcargo->barcode( $shipment_id ),
        'packageSettings'	=> $mp_settings,
        'cargoSettings'	=> $setting_options,
        'packages'		=> $packages,
        'logo'			=> $logo,
        'siteInfo'		=> $siteInfo
    );
  ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?> <?php echo is_rtl() ? 'dir="rtl"' : '' ; ?>>
        <head>
            <title><?php echo $waybill_title; ?></title>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <?php do_action( 'wpcfe_print_html_head' ); ?>
            <style type="text/css">
                div.copy-section { border: 2px solid #000; margin-bottom: 18px; }
                .copy-section table { border-collapse: collapse; }
                .copy-section table td.align-center{ text-align: center; }
                .copy-section table td { border: 1px solid #000; }
                table tr td{ padding:6px; }
                #logo-footer { text-align: right; margin-top: 10px; }
            </style>
        </head>
        <body>
            <?php
            $template_path = wpcfe_print_template_path_helper_callback( $custom_template_path, $print_type );
            $template_path = apply_filters( 'wpcfe_print_template_path_'.wpcfe_to_slug($print_type), $template_path, $shipment_id );
            include_once( $template_path );
            ?>
        </body>
    </html>
    <?php
    $output = ob_get_clean();    
	return $output;
}