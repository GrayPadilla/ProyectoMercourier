<?php
/**
 * Template personalizado para agregar envíos
 * Ubicación: blocksy-child/wpcargo-frontend-manager/templates/add-shipment.php
 */

// Obtener el tipo de envío seleccionado
$shipment_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

// Mostrar banner informativo del tipo seleccionado
if (!empty($shipment_type)) {
    $type_label = ($shipment_type == 'normal') ? 'MERC EMPRENDEDOR' : 'MERC AGENCIA';
    echo '<div class="alert alert-info mb-3" style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin-bottom: 20px;">';
    echo '<strong>📦 Tipo de envío seleccionado:</strong> ' . esc_html($type_label);
    echo '</div>';
}
?>

<form method="post" action="" enctype="multipart/form-data" class="add-shipment">
	<?php wp_nonce_field( 'wpcfe_add_action', 'wpcfe_add_form_fields' ); ?>
	
	<!-- Campo oculto para guardar el tipo de envío -->
	<input type="hidden" name="shipment_type" value="<?php echo esc_attr($shipment_type); ?>">
	
	<div class="row">
		<div class="col-md-9 mb-3">
			<section class="row"> 
				<?php if( has_action( 'before_wpcfe_shipment_form_fields' ) ): ?>
					<?php do_action( 'before_wpcfe_shipment_form_fields', 0 ); ?>
				<?php
				endif;
				$counter = 1;
				$row_class = '';
				foreach ( wpcfe_get_shipment_sections() as $section => $section_header ) {		
					if( empty( $section ) ){
						continue;
					}
					$column = 12;
					if( ( $section == 'shipper_info' || $section == 'receiver_info' ) && $counter <= 2 ){
						$column = 6;
					}
					$column = apply_filters( 'wpcfe_shipment_form_column', $column, $section ); 

					?>
					<div id="<?php echo $section; ?>" class="col-md-<?php echo $column; ?> mb-4">
						<div class="card">
							<section class="card-header">
								<?php echo $section_header; ?>
							</section>				
							<section class="card-body">
								<div class="row">
									<?php if( has_action( 'before_wpcfe_'.$section.'_form_fields' ) ): ?>
										<?php do_action( 'before_wpcfe_'.$section.'_form_fields', 0 ); ?>
									<?php endif; ?>
									<?php $section_fields = $WPCCF_Fields->get_custom_fields( $section ); ?>
									<?php $WPCCF_Fields->convert_to_form_fields( $section_fields ); ?>
									<?php if( has_action( 'after_wpcfe_'.$section.'_form_fields' ) ): ?>
										<?php do_action( 'after_wpcfe_'.$section.'_form_fields', 0 ); ?>
									<?php endif; ?>
								</div>
							</section>
						</div>
					</div>
					<?php
					$counter++;
				}
				if( has_action( 'after_wpcfe_shipment_form_fields' ) ): ?>
					<?php do_action( 'after_wpcfe_shipment_form_fields', 0 ); ?>
				<?php endif; ?>
			</section>
		</div>
		<div class="col-md-3 mb-3">
			<section class="row"> 
				<?php if( has_action( 'before_wpcfe_shipment_form_submit' ) ): ?>
					<div class="after-shipments-info col-md-12 mb-4">
						<?php do_action( 'before_wpcfe_shipment_form_submit' ); ?>
					</div>
				<?php endif; ?>
				<div class="col-md-12 mb-5 text-right">
					<button type="submit" class="btn btn-info btn-fill btn-wd btn-block">
						<?php esc_html_e('Add Shipment', 'wpcargo-frontend-manager'); ?>
					</button>
				</div>
			</section>
		</div>
	</div>
	<div class="clearfix"></div>
</form>

<?php do_action( 'before_wpcargo_shipment_history', 0); ?>