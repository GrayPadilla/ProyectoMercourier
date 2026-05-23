<?php $options = !empty($options)? $options : array(); ?>
<form method="post" id="wpc-receiving" action="cannot-add">
    <?php wp_nonce_field( 'wpcfe_add_action', 'wpcfe_add_form_fields' ); ?>
    <div class="row wpc-receiving-tbl">
        <div class="col-md-12 mb-4">
            <div class="receiving-input">
                <input type="checkbox" id="clear-fields" name="clear-fields" value="1" class="form-check-input ">
                <label for="clear-fields"><strong><?php esc_html_e('Borre todos los campos después de escanearlos.', 'wpcargo-receiving' );?></strong></label>
            </div>
            <div class="receiving-input">
                <input type="checkbox" id="add-not-found" name="add-not-found" value="1" class="form-check-input" onclick="togglePackagesSection()">
                <label for="add-not-found"><strong>
                    <?php esc_html_e('Añadir cuando no se encuentra el envío.', 'wpcargo-receiving'); ?>
                </strong></label>
            </div>
        </div>

        <!-- Sección de detalle del pedido - SIEMPRE VISIBLE -->
        <div class="col-md-12 mb-4" id="order-detail-section" style="background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px;">
            <p id="order-detail-text" style="margin: 0; font-size: 18px; font-weight: 600;">
                <strong>Pedido asignado a motorizado:</strong> <span id="driver-name-display"></span>
            </p>
        </div>

        <div class="row col-md-12 mb-4">
            <?php 
            do_action( 'wpcr_before_receiving_form_fields' ,$options );
            
            // Solo mostrar el campo de Estado
            foreach( $wpcr_dfields as $history_name => $history_value ): 
                // Solo mostrar el campo "status"
                if ($history_name != 'status') {
                    continue;
                }
                
                $select_class = ( $history_value['field'] == 'select' ) ? 'browser-default' : '';
            ?>
            <div class="col-md-6 mb-4 receiving-input">
                <label for="wpc-receiving-<?php echo $history_name; ?>"><?php echo $history_value['label'];?></label><br />
                <?php echo wpcargo_field_generator( $history_value, $history_name, '', 'form-control wpc-receiving-'.$history_name.' '.$select_class ); ?>
            </div>
            <?php 
            endforeach;
            
            // Campos ocultos con valores automáticos
            echo '<input type="hidden" name="date" value="'.current_time( $wpcargo->date_format ).'" />';
            echo '<input type="hidden" name="time" value="'.current_time( $wpcargo->time_format ).'" />';
            echo '<input type="hidden" name="location" value="" />';
            echo '<input type="hidden" name="remarks" value="" />';
            echo '<input type="hidden" name="updated-by" value="'.get_current_user_id().'" />';
            echo '<input type="hidden" name="updated-name" value="'.$wpcargo->user_fullname( get_current_user_id() ).'" />';
            
            do_action( 'wpcr_after_receiving_form_fields' , $options );
            ?>
        </div>

        <div class="col-md-12 mb-4 alert alert-info">
            <h3><?php _e('Introduzca el número de envío', 'wpcargo-receiving' );?>: </h3>
            <div class="receiving-input">
                <input type="text" class="form-control wpc-receiving-shipment" placeholder="<?php esc_html_e('Escanee su código de barras de envío para actualizar o introduzca el número de seguimiento y pulse ENTER', 'wpcargo-receiving');?>" id="wpc-tracking-number" name="wpc-tracking-number">
            </div>
        </div>
        <?php do_action( 'wpcr_after_receiving_shipment_fields' , $options ); ?>
    </div>
</form>

<div class="wpc-receiver-notif alert"></div>

<div class="wpc-receivier-notes">
    <p><?php esc_html_e('Notas:', 'wpcargo-receiving');?></p>
    <ol>
        <li><?php esc_html_e('Si ha conectado su escáner de código de barras, escanee directamente al código de barras y actualizará automáticamente el estado del envío', 'wpcargo-receiving');?></li>
        <li>
            <?php _e( "Si no tiene un escáner de código de barras, introduzca el número de seguimiento y pulse <i>Enter</i> en su teclado", 'wpcargo-receiving' ); ?>
        </li>
    </ol>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('Script de detalle de pedido cargado');
    
    var lastTrackingNumber = '';
    var resetTimeout = null;
    var currentDriverName = '....................';
    
    // Función para buscar información del motorizado
    function buscarMotorizado(trackingNumber) {
        console.log('🔍 Buscando motorizado para:', trackingNumber);
        
        // Cancelar cualquier reseteo pendiente
        if (resetTimeout) {
            clearTimeout(resetTimeout);
            resetTimeout = null;
        }
        
        // Mostrar loading
        $('#driver-name-display').html('<em style="color: #0c5460;">Buscando...</em>');
        
        // Hacer petición AJAX
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'get_order_driver_info',
                tracking_number: trackingNumber,
                nonce: '<?php echo wp_create_nonce("wpc_get_order_info"); ?>'
            },
            success: function(response) {
                console.log('📦 Respuesta AJAX completa:', response);
                
                if (response.success && response.data) {
                    console.log('✓ has_driver:', response.data.has_driver);
                    console.log('✓ driver_name:', response.data.driver_name);
                    console.log('✓ driver_id:', response.data.driver_id);
                    
                    // Verificar si tiene motorizado asignado
                    if (response.data.has_driver && response.data.driver_name) {
                        var driverName = response.data.driver_name;
                        
                        currentDriverName = '<span style="color: #155724; font-weight: bold;">✅ ' + driverName + '</span>';
                        $('#driver-name-display').html(currentDriverName);
                        
                        // Preseleccionar el conductor en el dropdown si existe
                        if (response.data.driver_id) {
                            $('#wpc-receiving-conductor').val(response.data.driver_id);
                        }
                        
                        console.log('✅ Motorizado encontrado:', driverName);
                    } else {
                        // NO hay motorizado asignado
                        console.log('⚠️ Sin motorizado asignado');
                        currentDriverName = '<span style="color: #856404; font-weight: bold;">⚠️ Sin motorizado asignado</span>';
                        $('#driver-name-display').html(currentDriverName);
                    }
                    
                    // Programar reseteo después de 3 segundos
                    resetTimeout = setTimeout(function() {
                        console.log('🔄 Reseteando display...');
                        $('#driver-name-display').text('....................');
                        currentDriverName = '....................';
                        resetTimeout = null;
                    }, 3000);
                    
                } else {
                    // Error en la respuesta o pedido no encontrado
                    console.log('❌ Pedido no encontrado');
                    currentDriverName = '<span style="color: #721c24; font-weight: bold;">❌ Pedido no encontrado</span>';
                    $('#driver-name-display').html(currentDriverName);
                    
                    resetTimeout = setTimeout(function() {
                        $('#driver-name-display').text('....................');
                        currentDriverName = '....................';
                        resetTimeout = null;
                    }, 3000);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                console.error('Respuesta completa:', xhr.responseText);
                currentDriverName = '<span style="color: #721c24; font-weight: bold;">❌ Error al buscar</span>';
                $('#driver-name-display').html(currentDriverName);
                
                resetTimeout = setTimeout(function() {
                    $('#driver-name-display').text('....................');
                    currentDriverName = '....................';
                    resetTimeout = null;
                }, 3000);
            }
        });
    }
    
    // Interceptar TODOS los cambios en el input
    $('#wpc-tracking-number').on('input keyup paste', function(e) {
        var currentValue = $(this).val().trim();
        
        if (currentValue && currentValue !== lastTrackingNumber) {
            console.log('📝 Nuevo valor detectado:', currentValue);
            lastTrackingNumber = currentValue;
            
            setTimeout(function() {
                var finalValue = $('#wpc-tracking-number').val().trim();
                if (finalValue) {
                    buscarMotorizado(finalValue);
                }
            }, 100);
        }
    });
    
    // Interceptar cuando se presiona Enter
    $('#wpc-tracking-number').on('keypress', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            var trackingNumber = $(this).val().trim();
            if (trackingNumber) {
                buscarMotorizado(trackingNumber);
            }
        }
    });
    
    // Monitorear cuando el campo se limpia
    var checkInterval = setInterval(function() {
        var currentValue = $('#wpc-tracking-number').val().trim();
        
        if (currentValue === '' && lastTrackingNumber !== '') {
            console.log('🧹 Campo limpiado');
            lastTrackingNumber = '';
        }
    }, 500);
    
    $(window).on('beforeunload', function() {
        clearInterval(checkInterval);
        if (resetTimeout) {
            clearTimeout(resetTimeout);
        }
    });
});
</script>