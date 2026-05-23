<?php 
	$packages = maybe_unserialize( get_post_meta( $shipment->ID, 'wpc-multiple-package', true ) ); 
	$shipment_type = get_post_meta( get_post_meta( $shipment->ID, '__shipment_type', true ) );
?>
<div id="package_id" class="col-md-12 mb-4">
	<div class="card">
		<section class="card-header">
			<?php echo apply_filters( 'wpcfe_multipack_header_label', esc_html__('Packages','wpcargo-frontend-manager') ); ?>
		</section>
		<section class="card-body">
			<?php do_action('wpcfe_before_update_package_details', $shipment ); ?>
			<div id="wpcfe-multipack-table-wrapper" class="table-responsive">
				<table id="wpcfe-packages-repeater" class="table table-hover table-sm">
					<thead>
						<tr class="text-center">
							<?php foreach ( wpcargo_package_fields() as $key => $value): ?>
								<?php 
									// Ocultar descripción
									if( $key === 'description' ){
										continue;
									}
									if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
										continue;
									}
								?>
								<th><strong><?php echo $value['label']; ?></strong></th>
							<?php endforeach; ?>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody data-repeater-list="wpc-multiple-package">
					<?php
					if(!empty($packages) && is_array($packages)) {
						foreach($packages as $package) { ?>
							<tr data-repeater-item>
								<?php foreach ( wpcargo_package_fields() as $key => $field_value): 
									// Ocultar descripción
									if( $key === 'description' ){
										continue;
									}
									$value = array_key_exists( $key, $package ) ? $package[$key] : '' ;
									
									// Establecer valores predeterminados para dimensiones y peso
									if( $key === 'length' && empty($value) ) $value = '25';
									if( $key === 'width' && empty($value) ) $value = '25';
									if( $key === 'height' && empty($value) ) $value = '25';
									if( $key === 'weight' && empty($value) ) $value = '3';
									
									$class = $field_value['field'] == 'select' ? 'form-control browser-default custom-select' : 'form-control' ; ?>
									<?php 
									if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
										continue;
									}
									?>
									<td><?php echo wpcfe_field_generator( $field_value, $key, $value, $class ); ?></td>
								<?php endforeach; ?>
									<td>								
                                        <label for="del-pack" class="text-danger pck-delete" >
									        <i class="fa fa-trash"></i>
									    </label>
									    <input data-repeater-delete type="button" id="del-pack" class="wpc-delete d-none" />
								    </td>
							</tr>
							<?php
						}
					}else{
						?>
							<tr data-repeater-item>
								<?php foreach ( wpcargo_package_fields() as $key => $field_value): 
									// Ocultar descripción
									if( $key === 'description' ){
										continue;
									}
									
									// Establecer valores predeterminados
									$default_value = '';
									if( $key === 'length' ) $default_value = '25';
									if( $key === 'width' ) $default_value = '25';
									if( $key === 'height' ) $default_value = '25';
									if( $key === 'weight' ) $default_value = '3';
									
									$class = $field_value['field'] == 'select' ? 'form-control browser-default custom-select' : 'form-control' ; ?>
									<?php 
									if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
										continue;
									}
									?>
									<td><?php echo wpcfe_field_generator( $field_value, $key, $default_value, $class ); ?></td>
								<?php endforeach; ?>
								<td>								
                                    <label for="del-pack" class="text-danger pck-delete" >
										<i class="fa fa-trash"></i>
									</label>
									<input data-repeater-delete type="button" id="del-pack" class="wpc-delete d-none" />
								</td>
							</tr>
						<?php
					}
					?>
					
					</tbody>
					<tfoot>
					<tr class="wpc-computation">
						<td colspan="<?php echo wpcfe_mpack_dim_enable() ? 11 : 7 ; ?>" class="text-left">
							<label for="add-pack" class="text-info pck-add" >
								<i class="fa fa-plus"></i> <?php echo apply_filters( 'wpcinvoice_add_package_btn_label', __( 'Add', 'wpcargo-invoice' ) ); ?>
							</label>
							<input data-repeater-create type="button" id="add-pack" class="wpc-add d-none" />
						</td>
					</tr>
					<?php do_action( 'wpcargo_after_package_table_row', $shipment ); ?>
					
					</tfoot>
				</table>
				<?php do_action('wpcfe_after_update_package_details', $shipment ); ?>
				<?php do_action('wpcargo_after_package_totals', $shipment ); ?>
			</div>
		</section>
	</div>
</div>

<!-- Añadir SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script de validación de dimensiones y peso -->
<script type="text/javascript">
    function validatePackageDimensions(length, width, height) {
        const MAX_LENGTH = 25;
        const MAX_WIDTH = 25;
        const MAX_HEIGHT = 25;
        
        return length <= MAX_LENGTH && width <= MAX_WIDTH && height <= MAX_HEIGHT;
    }
    
    function validatePackageWeight(weight) {
        const MAX_WEIGHT = 3; // Máximo 3 kg
        return weight <= MAX_WEIGHT;
    }

    function showLimitExceededAlert(isWeight) {
        let message = isWeight 
            ? 'No es posible realizar envíos mayores a 3 kg.<br><br>' 
            : 'No es posible realizar envíos mayores a 25 x 25 x 25 cm.<br><br>';
            
        message += 'Para enviar paquetes con medidas mayores, por favor comuníquese por <a href="https://wa.me/51931430389" target="_blank">WhatsApp</a>.';
        
        Swal.fire({
            title: isWeight ? '¡Peso excedido!' : '¡Dimensiones excedidas!',
            html: message,
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3085d6'
        });
    }

    jQuery(document).ready(function($) {
        // Función para establecer valores predeterminados en nuevas filas
        function setDefaultValues(row) {
            $(row).find('input[name*="length"]').val('25');
            $(row).find('input[name*="width"]').val('25');
            $(row).find('input[name*="height"]').val('25');
            $(row).find('input[name*="weight"]').val('3');
        }

        // Función para validar una fila específica
        function validateRow(row) {
            const lengthField = $(row).find('input[name*="length"]');
            const widthField = $(row).find('input[name*="width"]');
            const heightField = $(row).find('input[name*="height"]');
            const weightField = $(row).find('input[name*="weight"]');
            
            // Validar dimensiones
            if (lengthField.length && widthField.length && heightField.length) {
                const length = parseFloat(lengthField.val()) || 0;
                const width = parseFloat(widthField.val()) || 0;
                const height = parseFloat(heightField.val()) || 0;
                
                if ((length > 0 || width > 0 || height > 0) && 
                    !validatePackageDimensions(length, width, height)) {
                    showLimitExceededAlert(false);
                    
                    // Restaurar valores máximos permitidos
                    if (length > 25) lengthField.val('25');
                    if (width > 25) widthField.val('25');
                    if (height > 25) heightField.val('25');
                    
                    return false;
                }
            }
            
            // Validar peso
            if (weightField.length) {
                const weight = parseFloat(weightField.val()) || 0;
                
                if (weight > 0 && !validatePackageWeight(weight)) {
                    showLimitExceededAlert(true);
                    
                    // Restaurar peso máximo permitido
                    weightField.val('3');
                    
                    return false;
                }
            }
            
            return true;
        }

        // Validar al cambiar cualquier campo de dimensiones o peso
        $('#wpcfe-packages-repeater').on('change', 'input[name*="length"], input[name*="width"], input[name*="height"], input[name*="weight"]', function() {
            validateRow($(this).closest('tr'));
        });

        // Establecer valores predeterminados cuando se añade una nueva fila
        $(document).on('click', '.wpc-add, [data-repeater-create]', function() {
            setTimeout(function() {
                const newRow = $('#wpcfe-packages-repeater tbody tr:last');
                setDefaultValues(newRow);
            }, 100);
        });
    });
</script>