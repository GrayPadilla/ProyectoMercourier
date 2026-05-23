<?php do_action( 'wpcfe_before_label_content', $shipmentDetails, null, null, null ); ?>
<table style="width:100%;">
    <?php do_action( 'wpcfe_start_label_section', $shipmentDetails, null, null, null ); ?>
    <tr>
        <td style="width:70% !important; vertical-align: top !important;  border-right:2px solid #000; border-bottom: 2px solid #000;">
            <?php do_action( 'wpcfe_label_from_info', $shipmentDetails, null, null, null ); ?>
        </td>
        <td style="width:30% !important; padding-right:18px; border-bottom: 2px solid #000;">
            <?php do_action( 'wpcfe_label_site_info', $shipmentDetails, null, null, null ); ?>
        </td>
    </tr>
    <?php do_action( 'wpcfe_middle_label_section', $shipmentDetails, null, null, null ); ?>
    <?php do_action( 'wpcfe_label_to_info', $shipmentDetails, null, null, null ); ?>
    <?php do_action( 'wpcfe_end_label_section', $shipmentDetails, null, null, null ); ?>
</table>
<?php do_action( 'wpcfe_after_label_content', $shipmentDetails, null, null, null ); ?>
