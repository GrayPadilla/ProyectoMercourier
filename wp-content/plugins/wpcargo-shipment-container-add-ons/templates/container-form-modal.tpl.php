<!-- Modal -->
<?php
  $shipper_display 	= get_option('container_shipper_display');
	$receiver_display = get_option('container_receiver_display');
	$shipper_label		= wpc_shipment_container_get_field_label( $shipper_display );
	$receiver_label 	= wpc_shipment_container_get_field_label( $receiver_display );
?>
<div class="modal fade top" id="shipmentListModalPreview" tabindex="-1" role="dialog" aria-labelledby="shipmentListModalPreviewLabel" aria-hidden="true">
  <div class="modal-dialog modal-fluid modal-full-height modal-top" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shipmentListModalPreviewLabel"><?php echo wpc_scpt_assing_shipment_label(); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="container">
            <section class="row">
                <div id="shipment-options-wrapper">
                    <!-- Table -->
                    <table id="shipment-options-table" class="table table-hover mb-0">
                    <!-- Table head -->
                        <thead>
                            <tr>
                                <?php foreach( wpcsc_datatable_info_callback() as $header ): ?>
                                <th class="th-lg"><?php echo $header; ?></th>
                                <?php endforeach; ?>
                                <th class="th-lg text-center"><?php echo __( 'Actions', 'wpcargo-shipment-container' ); ?></th>
                            </tr>
                        </thead>
                        <!-- Table head -->
                        <!-- Table body -->
                        <tbody>
                        </tbody>
                        <!-- Table body -->
                    </table>
                </div>
            </section>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal"><?php echo __( 'Close', 'wpcargo-shipment-container' ); ?></button>
      </div>
    </div>
  </div>
</div>
<!-- Modal -->