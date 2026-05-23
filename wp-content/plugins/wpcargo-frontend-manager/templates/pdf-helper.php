<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once( WPCFE_PATH.'admin/includes/dompdf/lib/html5lib/Parser.php' );
require_once( WPCFE_PATH.'admin/includes/dompdf/lib/php-font-lib/src/FontLib/Autoloader.php' );
require_once( WPCFE_PATH.'admin/includes/dompdf/src/Options.php' );
require_once( WPCFE_PATH.'admin/includes/dompdf/src/Autoloader.php' );
Dompdf\Autoloader::register();
use Dompdf\Dompdf;
use Dompdf\Options;
add_action( 'init', 'wpcfe_pdf_helper' );
function wpcfe_pdf_helper(){
    global $WPCCF_Fields, $wpcargo;
    if( isset( $_GET['wpcfe-waybill'] ) && is_wpcfe_shipment( $_GET['wpcfe-waybill'] ) && is_user_shipment( (int)$_GET['wpcfe-waybill'] ) ){
        $shipment_id 	= $_GET['wpcfe-waybill'];	
        $paper_size     = 'A4';	
        $paper_orient   = 'landscape';
        // instantiate and use the dompdf class
        $options 		= new Options();
        $options->setDpi(150);
        $dompdf 		= new Dompdf( $options );
        //$dompdf->set_option('defaultFont', 'Courier');
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml( wpcfe_waybill_template_path() );
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper( $paper_size , $paper_orient);
        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream( get_the_title( $shipment_id  ) );	
    }
}
function wpcfe_waybill_template_path(){
    ob_start();
    global $WPCCF_Fields, $wpcargo;
    $wpcargo_style_path     = plugins_url().'/wpcargo/assets/css/main.min.css';
    $shipment_id 	        = $_GET['wpcfe-waybill'];
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/waybill.tpl.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = apply_filters( 'wpcfe_waybill_template', wpcfe_waybill_template( $shipment_id ), $shipment_id );
    }
    include_once( $template_path );	
    $output = ob_get_clean();
	return $output;
}
