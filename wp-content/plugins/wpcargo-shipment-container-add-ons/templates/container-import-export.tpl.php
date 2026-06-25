<style>
    div#tc-import-result{ background-color: #eee; padding: 8px; }
    div#tc-import-result > p.bold { font-weight: bold; }
    div#tc-import-result p.finish { color: #28a745!important; }
    #import_loading_wapper{ width: 100%; background-color: #eee; }
    #import_loading_wapper #loading_percentage{ height: 100%; background-color: #f8d7da; border-color: #f5c6cb; width: 0%; }
</style>
<section id="wpcsc-importexport-navigation" class="pb-2 mb-2 border-bottom">
    <a href="<?php echo $page_url; ?>?wpcsc=import" class="btn btn-info btn-sm <?php echo $_GET['wpcsc'] == 'import' ? 'disabled' : ''; ?>"><i class="fa fa-cloud-upload text-white"></i> <?php echo wpc_scpt_import_container_label(); ?></a>
    <a href="<?php echo $page_url; ?>?wpcsc=export" class="btn btn-info btn-sm <?php echo $_GET['wpcsc'] == 'export' ? 'disabled' : ''; ?>"><i class="fa fa-download text-white"></i> <?php echo wpc_scpt_export_container_label(); ?></a>
</section>
<section id="wpcsc-import-export-content">
    <form id="wpcsc-<?php echo urlencode( strtolower( $_GET['wpcsc'] ) ); ?>-form" action="POST" class="container-fluid" <?php echo $_GET['wpcsc'] == 'import' ? 'enctype="multipart/form-data"' : ''; ?>>
        <section class="row">
            <?php if( $_GET['wpcsc'] == 'export' ): ?>
                <div class="col-md-4">
                    <?php do_action( 'wpcsc_before_import_fields' ); ?>
                    <?php if( !empty($users) && !wpcie_is_client() ): ?>
                        <section class="form-group">
                            <label for="registered_shipper"><?php _e( 'Registered Shipper', 'wpcargo-shipment-container' ); ?></label>
                            <select name="shipment_author" class="form-control browser-default custom-select _group-data" id="registered_shipper">
                                <option value=""><?php _e('-- Registered Shipper --', 'wpcargo-shipment-container' ); ?></option>
                                <?php foreach( $users as $user ): ?>
                                    <option value="<?php  echo $user->ID; ?>"><?php echo $wpcargo->user_fullname( $user->ID ); ?></option>
                                <?php endforeach; ?>      
                            </select>
                        </section>
                    <?php endif; ?>
                    <?php if( !empty( $wpcargo->status ) ): ?>
                        <section class="form-group">
                            <label for="shipment_status"><?php _e( 'Status', 'wpcargo-shipment-container' ); ?></label>
                            <select name="wpcargo_status" class="form-control browser-default custom-select _group-data" id="shipment_status">
                                <option value=""><?php _e('-- Status --', 'wpcargo-shipment-container' ); ?></option>
                                <?php foreach( $wpcargo->status as $status ): ?>
                                    <option value="<?php  echo $status; ?>" ><?php echo $status; ?></option>
                                <?php endforeach; ?>      
                            </select>
                        </section>
                    <?php endif; ?>
                    <section class="form-row mb-4">
                        <div class="col-md-6 mb-4">
                            <div class="md-form">
                                <input placeholder="<?php _e('YYYY-MM-DD', 'wpcargo-shipment-container'); ?>" type="text" id="startingDate" name="date-from" class="form-control datepicker _group-data" required>
                                <label for="startingDate"><?php _e( 'Start', 'wpcargo-shipment-container' ); ?></label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                            <div class="md-form">
                                <input placeholder="<?php _e('YYYY-MM-DD', 'wpcargo-shipment-container'); ?>" type="text" id="endingDate" name="date-to" class="form-control datepicker _group-data" required>
                                <label for="endingDate"><?php _e( 'End', 'wpcargo-shipment-container' ); ?></label>
                            </div>
                        </div>
                    </section>
                    <?php do_action( 'wpcsc_after_import_fields' ); ?>
                </div>
            <?php else: ?>
                <input type="hidden" name="action" value="wpcsc_import">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpcsc_import_export_nonce'); ?>">
                <div class="col-md-4">
                    <div class="input-group mb-3">
                        <div class="form-group">
                            <label for="uploadedfile"><?php esc_html_e( 'Import CSV File', 'wpcargo-shipment-container' ); ?></label>
                            <input type="file" class="form-control-file" id="uploadedfile" name="uploadedfile" 
                            accept="
                                application/octet-stream,
                                application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, 
                                application/vnd.ms-excel,
                                text/comma-separated-values,
                                text/x-comma-separated-values,
                                text/tab-separated-values,
                                text/csv,
                                application/csv,
                                application/x-csv,
                                .csv
                            " required>
                        </div>
                    </div>
                    <h4 class="description"><?php echo wpc_scpt_import_instruction_message(); ?></h4>
                    <ol id="import-instruction">
                        <li><a href="#" id="wpcsc_download-csv-template" class="description"><?php esc_html_e('Download CSV template', 'wpcargo-shipment-container'); ?></a> <?php esc_html_e('as template for Importing data.', 'wpcargo-shipment-container' ); ?></li>
                        <li><?php esc_html_e( 'Delete Column(s) that are not needed, make sure no empty header column this cause data mapping error.', 'wpcargo-shipment-container' ); ?></li>
                        <li><?php esc_html_e( 'Add Data to each Cell.', 'wpcargo-shipment-container' ); ?></li>
                        <li><?php esc_html_e( 'Import CSV template.', 'wpcargo-shipment-container' ); ?></li>
                    </ol>
                </div>
            <?php endif; ?>
            <div id="wpcscie-form_notification" class="col-md-8">

            </div>
            <div class="col-md-12">
                <input type="submit" class="btn btn-primary btn-sm" name="export_shipment" value="<?php echo urlencode( strtoupper( $_GET['wpcsc'] ) ); ?>">
            </div>
        </section>
    </form>
</section>