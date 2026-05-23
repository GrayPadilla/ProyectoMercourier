<?php $_totalCount  = count( $packages ); ?>
<?php $_counter     = 1; ?>
<?php foreach ($packages as $package ): ?> 
    <?php do_action( 'wpcfe_before_label_content', $shipmentDetails, $packages, $package, $_counter ); ?>
    <table style="width:100%;">
        <?php do_action( 'wpcfe_start_label_section', $shipmentDetails, $packages, $package, $_counter ); ?>
        <tr>
            <td style="width:70% !important; vertical-align: top !important;  border-right:2px solid #000; border-bottom: 2px solid #000;">
                <?php do_action( 'wpcfe_label_from_info', $shipmentDetails, null, null, null ); ?>
            </td>
            <td style="width:30% !important; padding-right:18px; border-bottom: 2px solid #000;">
                <?php do_action( 'wpcfe_label_site_info', $shipmentDetails, null, null, null ); ?>
            </td>
        </tr>
        <?php do_action( 'wpcfe_middle_label_section', $shipmentDetails, $packages, $package, $_counter ); ?>
        <tr>
            <td colspan="2" style = "vertical-align: top; border-bottom: 2px solid #000; position:relative;">
                <?php do_action( 'wpcfe_label_to_info', $shipmentDetails, $packages, $package, $_counter ); ?>
            </td>
        </tr>
        <?php do_action( 'wpcfe_end_label_section', $shipmentDetails, $packages, $package, $_counter ); ?>
    </table>
    <?php do_action( 'wpcfe_after_label_content', $shipmentDetails, $packages, $package, $_counter ); ?>
    <?php if( $_totalCount == $_counter ){ continue; } ?>
    <div class="page_break"></div>
    <?php $_counter++; ?>
<?php endforeach; ?>