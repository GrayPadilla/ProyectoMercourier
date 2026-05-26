<div id="container-history-wrapper" class="col-sm-12 my-4">
    <p class="section-header h5-responsive font-weight-normal pb-2 border-bottom"><?php echo apply_filters( 'wpcsc_history_label', __('History', 'wpcargo-shipment-container') ); ?></p>
    <div class="table-responsive">
        <table id="container-history" class="table wpcargo-table">
            <thead>
                <tr>
                    <?php foreach ( wpcargo_history_fields() as $key => $value): ?>
                        <th><?php echo $value['label']; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $history as $_value ): ?>
                    <tr>
                        <?php foreach ( wpcargo_history_fields() as $key => $__value): ?>
                            <?php 
                            $hvalue = '';
                            if( array_key_exists( $key, $_value ) ){
                                $hvalue = $_value[$key];
                            }
                            ?>  
                            <td><?php echo $hvalue; ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>   
</div>