<form id="wpcpod-export" method="POST" class="container-fluid" action="">
    <div id="wpcpod-export-progress" class="col-lg-8 col-md-6"></div>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Driver', 'wpcargo-pod'); ?></th>
            <td>
                <select name="assign_driver" class="form-control browser-default custom-select" id="assign_driver" required>
                    <option value=""><?php esc_html_e('-- Select Driver --', 'wpcargo-pod'); ?></option>
                    <?php foreach( wpcargo_pod_get_drivers() as $driverID => $driver_name ): ?>
                        <option value="<?php echo $driverID; ?>"><?php echo $driver_name; ?></option>
                    <?php endforeach; ?>   
                </select>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e('Status', 'wpcargo-pod'); ?></th>
            <td>
                <select name="shipment_status" class="form-control browser-default custom-select" id="shipment_status">
                    <option value=""><?php esc_html_e('-- Select Status --', 'wpcargo-pod'); ?></option>
                    <?php foreach( $wpcargo->status as $status ): ?>
                        <option value="<?php  echo $status; ?>" ><?php echo $status; ?></option>
                    <?php endforeach; ?>      
                </select>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Date Range', 'wpcargo-pod' ); ?></th>
            <td>
                <?php esc_html_e( 'From', 'wpcargo-pod' ); ?>: <input type="text" id="date_from" class="form-control wpcpod-datepicker" name="date-from" placeholder="<?php esc_html_e('From', 'wpcargo-pod'); ?>" value="" required autocomplete="off"> 
                <?php esc_html_e( 'To', 'wpcargo-pod' ); ?>: <input type="text" id="date_to" class="form-control wpcpod-datepicker" name="date-to" placeholder="<?php esc_html_e('To', 'wpcargo-pod'); ?>" value="" required autocomplete="off">
            </td>
        </tr>
        <tr>
            <th>&nbsp;</th>
            <td><input type="submit" class="button button-primary button-sm" value="<?php esc_html_e( 'Export Report', 'wpc-import-export' ); ?>" /></td>
        </tr>
    </table>
</form>