<?php
do_action('wpcsc_pdf_before_content_container_details', $container_id);
?>
<div id="container-shipments">
    <?php if(!empty($delivery_zone_detail_values)): ?>
        <?php foreach($delivery_zone_detail_values as $value): ?>
            <table>
                <caption><strong><?php echo $value['actual_zone']; ?></strong></caption>
                <thead>
                    <tr>
                        <?php if(!empty($shipments_per_dz_detail_headers)): ?>
                            <?php foreach($shipments_per_dz_detail_headers as $index => $header): 
                                if($index === 1){
                                    $header = __('Barcode', 'wpcargo-container');
                                }
                                ?>
                                <td><?php echo $header; ?></td>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $item_count = 0;
                        $shipments = $value['shipments'] ?: array();
                    ?>
                    <?php if(!empty($shipments)): ?>
                        <?php foreach($shipments as $ship_id): 
                            $item_count++;
                            $barcode_url = $wpcargo->barcode( $ship_id, true );
                            $track_num = get_the_title($ship_id);
                            $barcode_url .= "</br>{$track_num}";
                            $meta_data = wpcsc_formatted_shipment_meta_data($ship_id);
                            $additional_meta_data = array($item_count, $barcode_url, 1);
                            $merged_meta_data = array_merge($additional_meta_data, $meta_data);
                        ?>
                        <tr>
                            <?php if(!empty($merged_meta_data)): ?>
                                <?php foreach($merged_meta_data as $index => $data): 
                                    $width = 'auto';
                                    if($index === 1){
                                        $width = '15%';
                                    }
                                    ?>
                                    <td style="text-align: center; width: <?php echo $width; ?>"><?php echo $data; ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
do_action('wpcsc_pdf_after_content_container_details', $container_id);
?>