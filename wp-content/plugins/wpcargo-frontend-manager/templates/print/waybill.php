<?php do_action( 'wpcfe_before_waybill_content', $shipmentDetails ); ?>
<table style="width:100%;">
    <?php //do_action( 'wpcfe_start_waybill_section', $shipmentDetails ); ?>
    <tr>
        <td style="width:50%;">
            <?php //do_action( 'wpcfe_waybill_site_info', $shipmentDetails ); ?>
        </td>
        <td style="width:50%;">
            <?php //do_action( 'wpcfe_waybill_from_info', $shipmentDetails ); ?>
        </td>
    </tr>
    <?php //do_action( 'wpcfe_middle_waybill_section', $shipmentDetails ); ?>
    <tr>
        <td colspan="2">
            <?php //do_action( 'wpcfe_waybill_to_info', $shipmentDetails ); ?>
        </td>
    </tr>
    <?php //do_action( 'wpcfe_end_waybill_section', $shipmentDetails ); ?>
</table>
<?php do_action( 'wpcfe_after_waybill_content', $shipmentDetails ); ?>