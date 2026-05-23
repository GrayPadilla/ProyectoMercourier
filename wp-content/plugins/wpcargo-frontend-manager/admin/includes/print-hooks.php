<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
add_action( 'plugins_loaded', 'wpcfe_load_print_hooks' );
function wpcfe_load_print_hooks(){
    // Print Label Hooks  
    add_action( 'wpcfe_before_label_content', 'wpcfe_before_label_content_callback', 10, 1 );
    add_action( 'wpcfe_label_site_info', 'wpcfe_label_site_info_callback', 10, 1 );
    add_action( 'wpcfe_label_from_info', 'wpcfe_label_from_info_callback', 10, 1 );
    add_action( 'wpcfe_label_to_info', 'wpcfe_label_to_info_callback', 10, 1 );
    add_action( 'wpcfe_end_label_section', 'wpcargo_qrcode_callback', 100, 1 );
    add_action( 'wpcfe_end_label_section', 'wpcfe_end_label_section_callback', 100, 1 );
    
    // Print Label Hooks - Pagination
    add_action( 'wpcfe_label_from_info', 'wpcfe_label_pagination_callback', 5, 4 );
    // Print Invoice Hooks
    add_action( 'wpcfe_before_invoice_content', 'wpcfe_before_invoice_content_callback', 10, 1 );
    add_action( 'wpcfe_invoice_site_info', 'wpcfe_invoice_site_info_callback', 10, 1 );
    add_action( 'wpcfe_invoice_barcode_info', 'wpcfe_invoice_barcode_info_callback', 10, 1 );
    add_action( 'wpcfe_invoice_shipper_info', 'wpcfe_invoice_shipper_info_callback', 10, 1 );
    add_action( 'wpcfe_invoice_receiver_info', 'wpcfe_invoice_receiver_info_callback', 10, 1 );
    add_action( 'wpcfe_end_invoice_section', 'wpcfe_end_invoice_section_callback', 100, 1 );
    add_action( 'wpcfe_middle_invoice_section', 'wpcfe_middle_invoice_section_callback', 10, 1 );
    add_action( 'wpcfe_after_middle_invoice_section', 'wpcfe_assigned_client_section', 20, 1 );

    // Print Bill of Lading
    add_action( 'wpcfe_before_table_bol_section', 'wpcfe_before_table_bol_section_callback', 10, 1 );
    add_action( 'wpcfe_start_bol_section', 'wpcfe_start_bol_section_callback', 10, 1 );
    add_action( 'wpcfe_bol_from_info', 'wpcfe_bol_from_info_callback', 10, 1 );
    add_action( 'wpcfe_bol_to_info', 'wpcfe_bol_to_info_callback', 10, 1 );
    add_action( 'wpcfe_bol_barcode_info', 'wpcfe_bol_barcode_info_callback', 10, 1 );
    add_action( 'wpcfe_middle_bol_section', 'wpcfe_bol_shipment_info_callback', 10, 1 );
    add_action( 'wpcfe_middle_bol_section', 'wpcfe_bol_shipment_package_callback', 10, 1 );
}
// Print Label hook callback
function wpcfe_before_label_content_callback(){
    $font = get_option('wpcargo_print_ffamily');
    ?>
    <style>
        table{ border-collapse: collapse; }
        table td{ vertical-align:top; padding: 15px; }
        table td *{ margin:0; padding:0; }
        img#log{ width:50% !important; }
        #section-to p{
            font-size: 14px !important;
            line-height:normal;
        }
        .vertical-text {
            position: absolute;
            -webkit-transform: rotate(-90deg); 
            -webkit-transform-origin: center top auto; 
            font-weight:700 !important; 
            left:88% !important;
            top: 15% !important;
        }
    </style>
    <?php
}

function wpcfe_label_site_info_callback( $shipmentDetails ){
    //declare variabes needed for the template
    $shipmentUpdate     = get_post_meta( $shipmentDetails['shipmentID'], 'wpcargo_shipments_update', true );

    $firstShipment      ='';
    if (count($shipmentUpdate) > 0 ) {
        $firstShipment      = array_shift( $shipmentUpdate );
   }

    $shipdate           = get_post_meta( $shipmentDetails['shipmentID'], 'wpcargo_pickup_date_picker', true ) ? get_post_meta( $shipmentDetails['shipmentID'], 'wpcargo_pickup_date_picker', true ) : $firstShipment['date'];
    $packages           = get_post_meta( $shipmentDetails['shipmentID'], 'wpc-multiple-package', true );
    $weight             = 0;
    
    foreach( $packages as $key => $package ){
        $weight += (float)$package['wpc-pm-weight'];
    }

    ?>
    <section>
        <p><?php esc_html_e('SHIPDATE:', 'wpcargo-frontend-manager'); ?><?php echo date_format( date_create( $shipdate ), 'YMd' );?></p>
        <p><?php esc_html_e('ACTIVWGT:', 'wpcargo-frontend-manager'); ?><?php echo number_format( $weight, 2 );?></p>
    </section>
    <?php
}

function wpcfe_label_from_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    ?>
    <section style = "min-height:150px;">
        <h4><span style = "margin-right: 30px;"><?php esc_html_e('SHIPMENTID:', 'wpcargo-frontend-manager'); ?><?php echo $shipmentDetails['shipmentID'];?></span><span><?php echo get_the_title( $shipmentDetails['shipmentID'] ); ?></span></h4>
    <?php
    echo wpcfe_label_print_data( 'shipper_info', $shipmentDetails['shipmentID']);
    ?>
    </section>
    <?php
}

function wpcfe_label_to_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    $receiver_name      = get_post_meta( $shipmentDetails['shipmentID'], 'wpcargo_receiver_name', true );
    $qinvoice_id        = get_post_meta( $shipmentDetails['shipmentID'], '__wpcinvoice_id', true ) ? get_post_meta( $shipmentDetails['shipmentID'], '__wpcinvoice_id', true ): 'N/A';
    $origin = get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_origin', true ) ? get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_origin', true ) : get_post_meta( $shipmentDetails['shipmentID'],'wpcargo_origin_field', true );
    $dest = get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_destination', true ) ? get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_destination', true ) : get_post_meta( $shipmentDetails['shipmentID'],'wpcargo_destination', true );
    ?>
    <tr style = "border_bottom: 3px solid #000;padding:30px; height: 300px;position:relative !important;">
        <td colspan = "2">
    <p><?php esc_html_e('TO ', 'wpcargo-frontend-manager'); ?></p>
    <h3 style = "padding-left: 24px;"><strong><span style = "font-size:20px;"><?php echo strtoupper( $receiver_name ); ?></strong></span></h3>
    <div id="section-to" style = "padding-left: 20px;padding-bottom:15px; position:relative !important;min-height:150px; width: 100%!important;">
        <?php echo wpcfe_label_print_data( 'receiver_info', $shipmentDetails['shipmentID'] ); ?>
        
        <div class ="vertical-text">
            <p style = "font-size:10px !important; font-weight:700 !important;margin:0 !important; width:200px;"><?php echo get_the_title( $shipmentDetails['shipmentID'] ); ?><br />
            <?php esc_html_e('INVOICEID ', 'wpcargo-frontend-manager');  echo $qinvoice_id; 
            
            if( !empty( $origin ) | !empty( $dest ) ): ?>
            <br />
                <span style = "font-size:12px !important; font-weight:700 !important;"><?php echo strtoupper( $origin ); ?> - <?php echo strtoupper( $dest ); ?></span>
            <?php endif; ?></p>
        </div>
    </div>    
    
        
        </td>
        </tr>
    <?php
    
}

function wpcargo_qrcode_callback( $shipmentDetails ){
    $qr_code        =  wpcargo_generate_qrcode( get_the_title( $shipmentDetails['shipmentID'] ) );
    
    


require_once WPCARGO_PLUGIN_PATH.'lib/barcode-generator/vendor/autoload.php';

$options = new QROptions(
  [
    'eccLevel' => QRCode::ECC_L,
    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'version' => 5,
  ]
);


$render_name= 'SHIPID'.$shipmentDetails['shipmentID'];
$qr_code_render_name = apply_filters( 'wpcfe_qr_code_render_label', $render_name);

$qr_code_render= apply_filters( 'wpcfe_qr_code_render_label', get_the_title($shipmentDetails['shipmentID'] ) );

 $qr_code  = (new QRCode($options))->render( $qr_code_render );
 

    $shipmentcode   = 'SHIPID'.$shipmentDetails['shipmentID'];
    $details        = get_option('wpcargo_option_settings');
    $logo           = $details['settings_shipment_ship_logo'];
    $origin         = get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_origin', true ) ? get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_origin', true ) : get_post_meta( $shipmentDetails['shipmentID'],'wpcargo_origin_field', true );
    $dest           = get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_destination', true ) ? get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_destination', true ) : get_post_meta( $shipmentDetails['shipmentID'],'wpcargo_destination', true );
    ?>
    <tr style = "border_bottom: 3px solid #000;padding:30px; height: 300px;position:relative !important;">
        <td colspan = "2">
            <div  id="section-to" style = "padding-left: 20px;padding-bottom:15px; position:relative !important; width: 100%">
            <table >
                <tr style = "vertical-align:top !important;">
                    <td style = "text-align:right !important;">
                        <img style = "width:100px !important; height: auto !important; text-align:right !important;" src="<?php echo $qr_code; ?>" alt="<?php echo $qr_code; ?>" />
                        <p style = "font-size:10px !important; font-weight:700 !important;text-align:center;"> <?php echo $qr_code_render_name; ?></p>
                        <?php if( !empty( $origin ) | !empty( $dest ) ): ?>
                            <p style = "font-size:10px !important; font-weight:700 !important;text-align:center;"><?php echo strtoupper( $origin ); ?> - <?php echo strtoupper( $dest ); ?></p>
                        <?php endif; ?>
                    </td>
                    <td style = "padding:10px; text-align:center !important; min-width:280px !important;">
                        <img style = "width:75% !important; height:auto !important; text-align:center !important;" src="<?php echo $logo; ?>" alt="<?php echo $logo; ?>" />
                        <p style = "font-size:10px !important; font-weight:400 !important;text-align:center;margin:0 !important;"><?php echo get_bloginfo('description'); ?></p>
                        <p style = "font-size:10px !important; font-weight:400 !important;text-align:center;margin:0 !important;"><?php echo ( get_bloginfo('admin_email') ); ?></p>
                    </td>
                    
                </tr>
            </table>
             <div class ="vertical-text">
                  <p style = "font-size:17px !important; font-weight:700 !important;text-align:center;margin:0 !important;"><?php echo $shipmentcode; ?>
                  </p>
              </div>
            </div>
            
        </td>
    </tr>
   
    <?php
}
function wpcfe_end_label_section_callback( $shipmentDetails  ){
    global $wpcargo;
    $origin = get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_origin', true ) ? get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_origin', true ) : get_post_meta( $shipmentDetails['shipmentID'],'wpcargo_origin_field', true );
    $dest = get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_destination', true ) ? get_post_meta( $shipmentDetails['shipmentID'], 'wpcsr_destination', true ) : get_post_meta( $shipmentDetails['shipmentID'],'wpcargo_destination', true );
    ?>
    <tr>
        <td colspan = "2" style = "text-align:center !important;">
            <h6 style="text-align:left !important; font-size:18px !important; width:100% !important;font-weight:700 !important;">TRK# <?php echo get_the_title( $shipmentDetails['shipmentID']); ?></h6>
            <?php if( !empty( $origin ) | !empty( $dest ) ): ?>
                <p style = "font-size:18px !important; font-weight:700 !important; text-align:left !important;"><?php echo strtoupper( $origin ); ?> - <?php echo strtoupper( $dest ); ?></p>
            <?php endif; ?>
            <img id="frontend-label-barcode" class="label-barcode" style="width:75% !important; text-align:center !important; padding:10px 0 !important;" src="<?php echo $wpcargo->barcode_url( $shipmentDetails['shipmentID'] ); ?>">
        </td>
    </tr>
    <?php
}

// Label pagination hook
function wpcfe_label_pagination_callback( $shipmentDetails, $packages, $package, $counter  ){
    if( !$packages ){
        return false;
    }
    $totalCount     = count( $packages );
    $str_find       = array_keys( wpcfe_package_shortcode() );
    $str_replce     = wpcfe_package_shortcode_map( $package, $counter, $totalCount );
    $ppage          = str_replace($str_find, $str_replce, wpcfe_label_pagination_template() );
    ?>
    <p style="float:right;padding-right:12px;"><?php echo $ppage ; ?></p>
    <?php
}


// Print Invoice hook callback
function wpcfe_before_invoice_content_callback(){
    ?>
    <style>
        table{ border-collapse: collapse; }
        table td{ vertical-align:top; padding: 8px; }
        table td *{ margin:0; padding:0; }
        table#package-table td,
        table#package-table th{ border:1px solid #000; padding: 6px; }
        table#package-table th{ white-space:nowrap; }
        .border-bottom{ border-bottom: 1px solid #cecece; }
        .space-topbottom{ padding-top:18px !important; padding-bottom:18px !important; }
        img#log{ width:50% !important; }
    </style>
    <?php
}
function wpcfe_invoice_site_info_callback( $shipmentDetails ){
    $options 		= get_option('wpcargo_option_settings');
	if( $options ){
		if( array_key_exists('wpcargo_base_color', $options) ){
			$baseColor = ( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : $baseColor ;
		}
	}
    $baseColor 		= $options ? $options['wpcargo_base_color'] : '#000';
    $company_name       = get_bloginfo('name');
	$logo_url           = $shipmentDetails['cargoSettings']['settings_shipment_ship_logo'];
    $header_details = $logo_url ? '<img style="width: 70%;" src="'.$logo_url.'"/>' : '<h3 style="color:'.$baseColor.';font-size: 48px !important; font-weight: 900;" >'.$company_name.'</h3>' ;
    ?>
    <table style="width: 100%">
        <tr>
            <td  class="no-padding" colspan="2" valign="top" align="left"><?php echo $header_details; ?></td>
        </tr>
        <tr>
            <td  class="no-padding" colspan="2" valign="top" align = "left">
                <?php echo strtoupper( $company_name ); ?>
            </td>
        </tr>
    </table>                 
    <?php
}
function wpcfe_invoice_shipper_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    ?>
    <h1 style="margin-bottom:18px;"><?php esc_html_e('SHIPPER DETAILS:', 'wpcargo-frontend-manager'); ?></h1>
    <?php
    echo wpcfe_print_data( 'shipper_info', $shipmentDetails['shipmentID']);
}
function wpcfe_invoice_receiver_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    ?>
    <h1 style="margin-bottom:18px;"><?php esc_html_e('RECEIVER DETAILS:', 'wpcargo-frontend-manager'); ?></h1>
    <section id="section-to"><?php echo wpcfe_print_data( 'receiver_info', $shipmentDetails['shipmentID']); ?></section>
    <?php
}
function wpcfe_invoice_barcode_info_callback( $shipmentDetails ){
    global $wpcargo;
    $barcode_height   = wpcfe_print_barcode_sizes('invoice')['height'];
    $barcode_width    = wpcfe_print_barcode_sizes('invoice')['width'];
    ?>
    <section style="text-align:center;" >
        <img id="frontend-invoice-barcode" class="invoice-barcode" style="height: <?php echo absint($barcode_height).'px'; ?>; width: <?php echo absint($barcode_width).'px'; ?>" src="<?php echo $wpcargo->barcode_url( $shipmentDetails['shipmentID'] ); ?>">
        <p style="font-size:18px;"><?php echo get_the_title( $shipmentDetails['shipmentID']); ?><p>
    </section>
    <?php
}

function wpcfe_middle_invoice_section_callback( $shipmentDetails ){
    ?>
    <tr>
		<td class="no-padding" colspan = "2" style = "background-color:#cecece; margin-bottom:10px !important;" align = "center">
			<p style = "color:#000; text-align:center; padding: 10px; font-size: 18px;"><?php echo wpcfe_default_label_invoice(); ?></p>
		</td>
	</tr>
    <?php
}
function wpcfe_end_invoice_section_callback( $shipmentDetails ){
    if( empty(wpcargo_get_package_data( $shipmentDetails['shipmentID'] ))){
        return false;
    }
    ?>
    <tr>
        <td colspan="2">
            <h1 style="margin-bottom:18px;"><?php esc_html_e('PACKAGE DETAILS:', 'wpcargo-frontend-manager'); ?></h1>
            <table id="package-table" style="width:100%;">
                <thead>
                    <tr>
                        <?php foreach ( wpcargo_package_fields() as $key => $value): ?>
                            <?php  if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){ continue; }
                            ?>
                            <th><?php echo $value['label']; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( wpcargo_get_package_data( $shipmentDetails['shipmentID'] ) as $data_key => $data_value): ?>
                    <tr>
                        <?php foreach ( wpcargo_package_fields() as $field_key => $field_value): ?>
                            <?php if( in_array( $field_key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){ continue; } ?>
                            <td>
                                <?php 
                                    $package_data = array_key_exists( $field_key, $data_value ) ? $data_value[$field_key] : '' ;
                                    echo is_array( $package_data ) ? implode(',', $package_data ) : $package_data; 
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </td>
    </tr>
    <?php
}

function wpcfe_assigned_client_section( $shipmentDetails ){
	$assigned_client	= get_post_meta( $shipmentDetails['shipmentID'], 'registered_shipper', true );
	$firstname			= get_user_meta( $assigned_client, 'billing_first_name', true );
	$lastname			= get_user_meta( $assigned_client, 'billing_last_name', true );
	$address1			= get_user_meta( $assigned_client, 'billing_address_1', true );
	$address2			= get_user_meta( $assigned_client, 'billing_address_2', true );
	$city				= get_user_meta( $assigned_client, 'billing_city', true );
	$billing_postcode	= get_user_meta( $assigned_client, 'billing_postcode', true );
	$billing_state		= get_user_meta( $assigned_client, 'billing_state', true );
	$billing_country	= get_user_meta( $assigned_client, 'billing_country', true );


	//Concatenated Details
	$username		= $firstname. ' ' .$lastname;
	$addressline	= $address1. ' '.$address2;

if(!$firstname){
    return 0;
}
    //invoice details
    if( function_exists('wpcsr_get_shipment_order') ){
		$order_id   = wpcsr_get_shipment_order( $shipmentDetails['shipmentID'] ) ? wpcsr_get_shipment_order( $shipmentDetails['shipmentID'] ) : '000000';
	}else{
		$order_id	= '000000';
	}
   
	$invoice_id     = get_post_meta( $shipmentDetails['shipmentID'], '__wpcinvoice_id', true );
    $invoice_number = $invoice_id ? get_the_title( $invoice_id ) :$shipmentDetails['shipmentID'];
	$date_created	= get_the_date( 'm/d/Y', $shipmentDetails['shipmentID'] );

	?>
    <td align = "center" width = "50%">
        <table style="width: 100%">
            <tr>
                <td  class="no-padding" colspan="2" valign="top" align="center">
                    <p><strong><?php echo $username; ?></strong></p>
                    <p><?php echo $addressline; ?></p>
                    <p><?php echo $city; ?> <?php echo $billing_postcode; ?></p>
                    <p><?php echo $billing_state; ?></p>
                    <p><?php echo $billing_country; ?></p>	
                </td>
            </tr>
        </table>	
    </td>
    <td align = "center" width = "50%">
        <table style="width: 100%" style = "border: 1px solid #444;">
            <tr style = "padding:0 5px !important;">
                <td style = "padding: 5px !important;"><?php esc_html_e( 'Date:', 'wpcargo-invoice' ); ?></td>
                <td style = "padding: 5px !important;"><?php echo $date_created; ?></td>
            </tr>
            <tr style = "padding:0 5px !important;">
                <td style = "padding: 5px !important;"><?php esc_html_e( 'Invoice No.:', 'wpcargo-invoice' ); ?></td>
                <td style = "padding: 5px !important;"><?php echo $invoice_number; ?></td>
            </tr>
            <tr style = "padding:0 5px !important;">
                <td style = "padding: 5px !important;"><?php esc_html_e( 'Order No.:', 'wpcargo-invoice' ); ?></td>
                <td style = "padding: 5px !important;"><?php echo $order_id; ?></td>
            </tr>
            <tr style = "padding:0 5px !important;">
                <td style = "padding: 5px !important;"><?php esc_html_e( 'Waybill No.:', 'wpcargo-invoice' ); ?></td>
                <td style = "padding: 5px !important;"><?php echo get_the_title( $shipmentDetails['shipmentID'] ); ?></td>
            </tr>
        </table>
    </td>
	<?php
}


function wpcfe_default_label_invoice(){
    $label_invoice = __('INVOICE','wpcargo-invoice');
    return apply_filters( 'wpcfe_default_label_invoice', $label_invoice );
}
// Bill of Lading Callback
function wpcfe_before_table_bol_section_callback(){
    ?>
    <style>
        .padding-default{ padding:8px; }
        table{ border-collapse: collapse; }
        table td{ vertical-align:top; border:1px solid #000; }
        table td *{ margin:0; padding:0; }
        table#package-table th{ white-space:nowrap; }
        .shipment-info td { padding:6; border:none; }
        #package-table td, #package-table th{ padding:6px; border:1px solid #000; }
        .border-bottom{ border-bottom: 1px solid #000; }
        .space-topbottom{ padding-top:18px; padding-bottom:18px; }
        img#log{ width:50% !important; }
        .section-title{ margin-bottom:8px; text-align:center; background-color:#333; color:#fff; padding: 4px 0; }        
    </style>
    <?php
}
function wpcfe_start_bol_section_callback( $shipmentDetails ){
    ?>
    <tr>
        <td colspan="2" class="padding-default" style="text-transform: uppercase;text-align:center; font-size:24px;"><?php esc_html_e('Bill of Lading', 'wpcargo-frontend-manager') ?></td>
    </tr>
    <?php
}
function wpcfe_bol_from_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    ?>
    <h1 class="section-title"><?php esc_html_e('SHIP FROM', 'wpcargo-frontend-manager'); ?></h1>
    <div class="padding-default">
        <?php echo wpcfe_print_data( 'shipper_info', $shipmentDetails['shipmentID']); ?>
    </div>
    <?php
}
function wpcfe_bol_to_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    ?>
    <h1 class="section-title"><?php esc_html_e('SHIP TO', 'wpcargo-frontend-manager'); ?></h1>
    <div class="padding-default">
        <?php echo wpcfe_print_data( 'receiver_info', $shipmentDetails['shipmentID']); ?>
    </div>
    <?php
}
function wpcfe_bol_barcode_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    $barcode_height     = wpcfe_print_barcode_sizes('waybill')['height'];
    $barcode_width      = wpcfe_print_barcode_sizes('waybill')['width'];
    $details        = get_option('wpcargo_option_settings');
    $logo           = $details['settings_shipment_ship_logo'];
    ?>
    <section class="padding-default" style="text-align:center; border-bottom: 1px solid #cecece;" >
        <img style = "width:75% !important; height:auto !important; text-align:center !important;" src="<?php echo $logo; ?>" alt="<?php echo $logo; ?>" />
        <img id="frontend-bol-barcode" class="invoice-bol" style="height: <?php echo absint($barcode_height).'px'; ?>; width: <?php echo absint($barcode_width).'px'; ?>" src="<?php echo $wpcargo->barcode_url( $shipmentDetails['shipmentID'] ); ?>">
        <p style="font-size:18px;"><?php echo get_the_title( $shipmentDetails['shipmentID']); ?><p>
    </section>
    <section class="padding-default" style="text-align:left;">
        <h1 class="section-title"><?php esc_html_e('NOTIFY PARTY', 'wpcargo-frontend-manager'); ?></h1>
        <div class="padding-default">
            <?php echo wpcfe_print_data( 'receiver_info', $shipmentDetails['shipmentID']); ?>
        </div>
    </section>
    <?php
}
function wpcfe_bol_shipment_info_callback( $shipmentDetails ){
    global $WPCCF_Fields, $wpcargo;
    ?>
    <tr>
        <td colspan="2">
            <h1 class="section-title"><?php esc_html_e('ADDITIONAL INFORMATION', 'wpcargo-frontend-manager'); ?></h1>
            <div class="padding-default">
                <table style="width:100%;" class="shipment-info">
                    <?php
                    $field_keys = $WPCCF_Fields->get_custom_fields( 'shipment_info' );
                    if( !empty( $field_keys ) ){
                        $counter = 1;
                        foreach ( $field_keys as $field ) {
                            $field_data = maybe_unserialize( get_post_meta( $shipmentDetails['shipmentID'], $field['field_key'], TRUE ) );
                            if( is_array( $field_data ) ){
                                $field_data = implode(", ", $field_data);
                            }
                            if( $counter == 1 ){
                                echo '<tr>';
                            }

                            // table data
                            echo '<td>';
                                if( $field['field_type'] == 'file' ){
                                    $files = array_filter( array_map( 'trim', explode(",", $field_data) ) );
                                    if( !empty( $files ) ){
                                        ?>
                                        <div class="wpccfe-files-data">
                                            <label><?php echo $field['label']; ?></label><br/>
                                            <div id="wpcargo-gallery-container_<?php echo $field['id'];?>">
                                                <ul class="wpccf_uploads">
                                                    <?php
                                                        foreach ( $files as $file_id ) {
                                                            $att_meta = wp_get_attachment_metadata( $file_id );
                                                            ?>
                                                            <li class="image">
                                                                <?php echo get_the_title($file_id); ?>
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
                                    ?><p><?php echo $field['label']; ?>:<br/><a href="<?php echo $url; ?>" target="<?php echo $target; ?>"><?php echo $label; ?></a></p><?php
                                }else{
                                    ?><p><?php echo $field['label']; ?>:<br/><?php echo $field_data; ?></p><?php
                                }	
                            echo '</td>';
                            if( $counter == 3 ){
                                echo '</tr>';
                                $counter = 1;
                                continue;
                            }
                            $counter++;
                        }
                    }
                    ?>
                </table>
            </div>
        </td>
    </tr>
    <?php
}
function wpcfe_bol_shipment_package_callback( $shipmentDetails ){
    if( empty(wpcargo_get_package_data( $shipmentDetails['shipmentID'] ))){
        return false;
    }
    ?>
    <tr>
        <td colspan="2">
            <h1 class="section-title no-margin"><?php esc_html_e('PACKAGE DETAILS', 'wpcargo-frontend-manager'); ?></h1>
            <div class="padding-default">
                <table id="package-table" style="width:100%;">
                    <thead>
                        <tr>
                            <?php foreach ( wpcargo_package_fields() as $key => $value): ?>
                                <?php  if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){ continue; }
                                ?>
                                <th><?php echo $value['label']; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( wpcargo_get_package_data( $shipmentDetails['shipmentID'] ) as $data_key => $data_value): ?>
                        <tr>
                            <?php foreach ( wpcargo_package_fields() as $field_key => $field_value): ?>
                                <?php if( in_array( $field_key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){ continue; } ?>
                                <td>
                                    <?php 
                                        $package_data = array_key_exists( $field_key, $data_value ) ? $data_value[$field_key] : '' ;
                                        echo is_array( $package_data ) ? implode(',', $package_data ) : $package_data; 
                                    ?>

                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
    <?php
}
// PDF Function Helpers
function wpcfe_pdf_pagination( $dompdf, $print_type ){
    $has_pagination = apply_filters( 'wpcfe_has_pdf_pagination', '__return_true' );
    if( !$has_pagination ){
        return false;
    }

    $font_family    = apply_filters( 'wpcfe_pdf_pagination_font_family', 'Helvetica', $print_type );
    $font_type      = apply_filters( 'wpcfe_pdf_pagination_font_type', 'normal', $print_type );
    $x              = apply_filters( 'wpcfe_pdf_pagination_x_axis', 505, $print_type );
    $y              = apply_filters( 'wpcfe_pdf_pagination_y_axis', 790, $print_type );
    $text           = apply_filters( 'wpcfe_pdf_pagination_label', "{PAGE_NUM} of {PAGE_COUNT}", $print_type );   
    $font           = $dompdf->getFontMetrics()->get_font($font_family, $font_type);   
    $size           = apply_filters( 'wpcfe_pdf_pagination_font_size', 10, $print_type );    
    $color          = array(0,0,0);
    $word_space     = 0.0;
    $char_space     = 0.0;
    $angle          = 0.0;
    $dompdf->getCanvas()->page_text(
        $x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle
    );
}


add_action( 'wpcfe_print_html_head', 'track_result_printer_style' );
function  track_result_printer_style(){
	?>
    <link rel="stylesheet" id="wpcargo-styles-css" href="<?php echo WPCARGO_PLUGIN_URL . 'assets/css/wpcargo-style.css';?>" media="all">
    <link rel="stylesheet" id="wpcargo-custom-bootstrap-styles-css" href="<?php echo WPCARGO_PLUGIN_URL . 'assets/css/main.min.css'; ?>" media="all">

<?php 	
}
	

add_action( 'wp_head', 'remove_print_button_class_action' );
function remove_print_button_class_action() {
	global $wpcargo_print;
	remove_action( 'wpcargo_print_btn', array($wpcargo_print, 'wpcargo_print_results') );
}


//add_action( 'wpcargo_print_btn', 'custom_wpcargo_print_results' );

function  custom_wpcargo_print_results(){
    $shipment_id = wpcfe_shipment_id( $_POST['wpcargo_tracking_number'] );
	?>
    <style>
        div.print-shipment a.shipment-checkout {
            text-decoration: none !important;
            border-radius: 0.5rem !important;
        }
    </style>
	<div class="wpcargo-print-btn print-shipment">
	    <a class="shipment-checkout button button-primary" data-id="<?php echo $shipment_id; ?>" type="button" data-type="label" ><span class="dashicons dashicons-printer" style="font-family: 'dashicons' !important;"></span> <?php echo apply_filters( 'wpcargo_print_label_label', esc_html__( 'Print Label', 'wpcargo') ); ?></a>
	    <a class="shipment-checkout button button-primary" data-id="<?php echo $shipment_id; ?>" type="button" data-type="invoice" ><span class="dashicons dashicons-printer" style="font-family: 'dashicons' !important;"></span> <?php echo apply_filters( 'wpcargo_print_invoice_label', esc_html__( 'Print Invoice', 'wpcargo') ); ?></a>
	</div>
<?php 	
}


add_filter( 'wpcfe_print_paper_size', '_custom_wpcfe_print_paper_size', 1 );
function _custom_wpcfe_print_paper_size( $sizes ){
  // custom-scripts - is your enqueued script handle
    $track=array(
            'size' => 'A4',
            'orient' => 'portrait'
        
      );
	
    $sizes["track"] = $track;
	
	return $sizes;
}

