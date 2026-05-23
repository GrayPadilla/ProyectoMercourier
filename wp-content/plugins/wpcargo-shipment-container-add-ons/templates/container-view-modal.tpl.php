<div class="modal fade" id="assgnShipmentModal" tabindex="-1" role="dialog" aria-labelledby="assgnShipmentModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="margin-top: 36px;" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assgnShipmentModalTitle"><?php _e('Assigned Shipment list', 'wpcargo-shipment-container') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
            
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php _e('Close', 'wpcargo-shipment-container') ?></button>
      </div>
    </div>
  </div>
</div>
<script>
    jQuery(document).ready(function($){
        $('#container-list').on('click', '.openAssShipmentModal', function(e){
            e.preventDefault();
            const assignedShipments = $(this).data('shipments');
            $('#assgnShipmentModal .modal-body').html('');
            $('#assgnShipmentModal .modal-body').prepend('<div class="container"><div id="assgListWrapper" class="row"></div></div>');
            $.each( assignedShipments, function( key, value ) {
                $('#assgnShipmentModal .modal-body').find('#assgListWrapper').append( 
                    `
                    <div class="col-md-6 p-2 border text-center">
                        <a href="${value.url}" target="_blank">${value.barcode}
                        ${value.number}</a>
                    </div>
                    `
                );
            });
        });
    });
</script>