<?php do_action( 'wpcsc_pdf_after_header_manifest', $container_id ); ?>
<table class="container-info manifest">
    <tr>
        <td><strong><?php echo wpcsc_csv_delivery_manifest_header_label(); ?></strong></td>
    </tr>
    <tr>
        <td id ="section1">
            <table >
                <thead>
                    <tr>
                        <th class="no-bg"></th>
                        <th class="no-bg"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($merged_container_details)): ?>
                        <?php foreach($merged_container_details as $key => $val): ?>
                            <tr>
                                <td><?php echo $key; ?></td>
                                <td class='border_below '><?php echo $val; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </td>
        <td id ="section2">
            <table>
                <thead>
                    <tr>
                        <?php if(!empty($wpcsc_csv_delivery_zone_detail_headers)): ?>
                            <?php foreach($wpcsc_csv_delivery_zone_detail_headers as $header): ?>
                                <th><?php echo $header;?></th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($delivery_zone_detail_values)): ?>
                        <?php foreach($delivery_zone_detail_values as $value): ?>
                            <tr>
                                <td><?php echo $value['actual_zone']; ?></td>
                                <td><?php echo $value['total_cbm'].' '.wpc_shipment_container_wpc_mp_weight_unit(); ?></td>
                                <td><?php echo $value['total_boxes']; ?></td>
                                <?php if(!empty($box_types_arr) && class_exists('WPCargo_ShipmentBox')): ?>
                                    <?php foreach($box_types_arr as $type): ?>
                                        <td><?php echo $value[$type] ?? 0; ?></td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12"><?php echo apply_filters('wpcsc_empty_data_msg', __('No assigned shipments.', 'wpcargo-container')); ?></td>
                            </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </td>
    </tr>
</table>
<?php do_action( 'wpcsc_pdf_after_header_container_details', $container_id ); ?>