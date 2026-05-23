<?php do_action( 'wpcfe_before_table_bol_section', $shipmentDetails ); ?>
<table style="width:100%;">
    <?php do_action( 'wpcfe_start_bol_section', $shipmentDetails ); ?>
    <tr>
        <td style="width:50%;">
            <?php do_action( 'wpcfe_bol_from_info', $shipmentDetails ); ?>
        </td>
        <td style="width:50%; vertical-align: middle !important; text-align: center !important;" rowspan="2">
            <?php do_action( 'wpcfe_bol_barcode_info', $shipmentDetails ); ?>
        </td>
    </tr>
    <tr>
        <td>
            <?php do_action( 'wpcfe_bol_to_info', $shipmentDetails ); ?>
        </td>
    </tr>
    <?php do_action( 'wpcfe_middle_bol_section', $shipmentDetails ); ?>
    <?php do_action( 'wpcfe_end_bol_section', $shipmentDetails ); ?>
</table>
<?php do_action( 'wpcfe_after_table_bol_section', $shipmentDetails ); ?>