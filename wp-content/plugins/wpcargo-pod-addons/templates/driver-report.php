<form id="wpcpod-export" method="POST" class="container-fluid" action="<?php echo get_the_permalink(); ?>" >
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <h2 class="mb-4"><?php esc_html_e('Driver Report', 'wpcargo-pod'); ?></h2>
            <?php if( !wpcargo_pod_is_driver() ): ?>
            <section class="form-group">
                <label for="assign_driver" class=""><?php esc_html_e('Driver', 'wpcargo-pod'); ?></label>
                <select name="assign_driver" class="form-control browser-default custom-select" id="assign_driver" required>
                    <option value=""><?php esc_html_e('-- Select Driver --', 'wpcargo-pod'); ?></option>
                    <?php foreach( wpcargo_pod_get_drivers() as $driverID => $driver_name ): ?>
                        <option value="<?php echo $driverID; ?>"><?php echo $driver_name; ?></option>
                    <?php endforeach; ?>   
                </select>
            </section>
            <?php endif; ?>
            <section class="form-group">
                <label for="shipment_status" class=""><?php esc_html_e('Status', 'wpcargo-pod'); ?></label>
                <select name="shipment_status" class="form-control browser-default custom-select" id="shipment_status">
                    <option value=""><?php esc_html_e('-- Select Status --', 'wpcargo-pod'); ?></option>
                    <?php foreach( $wpcargo->status as $status ): ?>
                        <option value="<?php  echo $status; ?>" ><?php echo $status; ?></option>
                    <?php endforeach; ?>      
                </select>
            </section>
            <section class="form-row mb-4">
                <label for="date_from" class="col-sm-12"><?php esc_html_e( 'Date Range', 'wpcargo-pod' ); ?></label>
                <div class="col">
                    <input type="text" id="date_from" class="form-control wpccf-datepicker" name="date-from" placeholder="<?php esc_html_e('From', 'wpcargo-pod'); ?>" value="" required>
                </div>
                <div class="col">
                    <input type="text" id="date_to" class="form-control wpccf-datepicker" name="date-to" placeholder="<?php esc_html_e('To', 'wpcargo-pod'); ?>" value="" required>
                </div>
            </section>
            <input type="submit" class="btn btn-primary btn-sm m-0" value="<?php esc_html_e( 'Export Report', 'wpc-import-export' ); ?>" />
        </div>
        <div id="wpcpod-export-progress" class="col-lg-8 col-md-6">
        </div>
    </div>
</form>