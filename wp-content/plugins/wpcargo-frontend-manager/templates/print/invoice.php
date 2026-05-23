<?php do_action( 'wpcfe_before_invoice_content', $shipmentDetails ); ?>
<table style="width:100%;">
    <?php do_action( 'wpcfe_start_invoice_section', $shipmentDetails ); ?>
    <tr>
        <td style="width:50%; vertical-align: middle !important; text-align: center !important;" class="border-bottom">
            <?php do_action( 'wpcfe_invoice_site_info', $shipmentDetails ); ?>
        </td>
        <td style="width:50%; vertical-align: middle !important; text-align: center !important;" class="border-bottom">
            <?php do_action( 'wpcfe_invoice_barcode_info', $shipmentDetails ); ?>
        </td>
    </tr>
    <?php do_action( 'wpcfe_middle_invoice_section', $shipmentDetails ); ?>
    <tr style = "border-bottom: 1px solid #cecece;">
        <?php do_action( 'wpcfe_after_middle_invoice_section', $shipmentDetails ); ?>
    </tr>
    
    <tr>
        <td style="width:50%;" class="space-topbottom">
            <?php do_action( 'wpcfe_invoice_shipper_info', $shipmentDetails ); ?>
        </td>
        <td style="width:50%;" class="space-topbottom">
            <?php do_action( 'wpcfe_invoice_receiver_info', $shipmentDetails ); ?>
        </td>
    </tr>
    <?php do_action( 'wpcfe_end_invoice_section', $shipmentDetails ); ?>
</table>
<?php do_action( 'wpcfe_after_invoice_content', $shipmentDetails ); ?>