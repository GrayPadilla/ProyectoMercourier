<?php
$shipper_fields 	= $WPCCF_Fields->get_fields_data('shipper_info', $shipment_id );
$receiver_fields 	= $WPCCF_Fields->get_fields_data('receiver_info', $shipment_id );
$shipment_field_data 	= $WPCCF_Fields->get_fields_data('shipment_info', $shipment_id );
$shipment_fields        = $WPCCF_Fields->get_custom_fields('shipment_info' );
?>
<style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        padding:0;
        margin:0;
    }
    p{
        padding:6px;
    }
    .wpcargo-container{
        width:100%;
        padding:28px;
        display:block;
    }
    .header, .details-section{
        width:100%;
    }
    .header{
        text-align:center;
    }
    .header .logo{
        padding-bottom:18px;
        font-size:36px;
    }
    .shipper-section, .receiver-section{
        width:48%;
        float:left;
        margin-right:4%;
    }
    .receiver-section{
        margin-right:0;
    }
    .header-title{
        border-bottom:1px solid #000;
        padding-bottom:18px;
        margin-bottom:18px;
    }
    table{
        width:100%;
        border-collapse: collapse;
    }
    table td{
        padding:6 18px;
    }
    table td strong{
        display:block;
        margin-left: -12px;
    }
</style>
<div id="waybill" class="wpcargo-container">
    <div class="wpcargo-row">
        <div class="header wpcargo-col-md-12">
            <section class="logo"><?php echo apply_filters( 'wpcfe_waybill_header_logo', get_bloginfo('name') ); ?></section>
            <?php echo $wpcargo->barcode( $shipment_id, true ); ?>
            <p><?php echo get_the_title( $shipment_id ); ?></p>
        </div>
        <div class="shipper-section shipment-details wpcargo-col-md-6">
            <h3 class="header-title"><?php echo apply_filters('result_shipper_address', __('Shipper Address', 'wpcargo-custom-field' )); ?></h3>
            <?php echo $shipper_fields; ?>
        </div>
        <div class="receiver-section shipment-details wpcargo-col-md-6">
            <h3 class="header-title"><?php echo apply_filters('result_receiver_address', __('Receiver Address', 'wpcargo-custom-field' )); ?></h3>
            <?php echo $receiver_fields; ?>
        </div>
        <div class="details-section shipment-details wpcargo-col-md-12">
            <h3 class="header-title"><?php echo apply_filters('result_shipment_information', __('Shipment Information', 'wpcargo-custom-field')); ?></h3>
            <?php 
            if( !empty( $shipment_fields ) ){
                ?><table><?php
                    $counter = 1;
                    foreach ( $shipment_fields as $value) {
                        echo ( $counter == 1 ) ? '<tr>' : '' ;
                        ?>
                            <td><?php echo '<strong>'.$value['label'].'</strong> '.wpcargo_get_postmeta( $shipment_id, $value['field_key'], $value['field_type'] ); ?></td>
                        <?php
                        echo ( $counter == 3 ) ? '</tr>' : '' ;
                        $counter = ( $counter == 3 ) ? 1 : $counter + 1 ;
                    }
                ?></table><?php
            }
            ?>
        </div>
    </div>
</div>