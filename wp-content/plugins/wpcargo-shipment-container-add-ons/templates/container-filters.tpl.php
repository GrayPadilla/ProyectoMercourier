<div id="wpcsc-filter_wrapper" class="row mb-3 border-bottom">
	<section class="col-md-9 mb-3">
		<form id="wpcfe-filters" action="<?php echo $page_url; ?>" class="form-inline" style="width: 100%">
			<?php do_action( 'wpcsc_before_container_filters' ); ?>
			<?php if( !empty( $wpcargo->status ) ): ?>
				<div class="form-group wpcfe-filter status-filter p-0 mx-1">
					<label class="sr-only" for="status"><?php esc_html_e('Status', 'wpcargo-shipment-container' ); ?></label>
					<select id="status" name="status" class="form-control md-form wpcfe-select">
						<option value=""><?php echo esc_html__('All Status', 'wpcargo-shipment-container' ); ?></option>
						<?php 
							foreach ( $wpcargo->status as $status ) {
								?><option value="<?php echo $status; ?>" <?php echo selected( $sstatus, $status ); ?>><?php echo $status; ?></option><?php
							}
						?>
					</select>
				</div>
			<?php endif; ?>
			<div id="wpcfe-created-fields" class="form-group wpcfe-filter receiver-filter p-0 mx-1">
				<div class="md-form form-group">
					<?php _e('Date Created', 'wpcargo-shipment-container' ); ?>
					<input id="date_start" type="text" name="date_start" class="form-control daterange_picker start_date px-2 py-1 mx-2" value="<?php echo $date_start; ?>" autocomplete="off" style="width: 96px;">
					<div class="input-group-addon"><?php _e('to', 'wpcargo-shipment-container' ); ?></div>
					<input id="date_end" type="text" name="date_end" class="form-control daterange_picker end_date px-2 py-1 mx-2" value="<?php echo $date_end; ?>" autocomplete="off" style="width: 96px;">
				</div>
			</div>
			<?php do_action( 'wpcsc_after_container_filters' ); ?>
			<div class="form-group submit-filter p-0 mx-1">
				<button id="wpcfe-submit-filter" type="submit" class="btn btn-primary btn-fill btn-sm"><?php esc_html_e('Filter', 'wpcargo-shipment-container' ); ?></button>
			</div>
		</form>
	</section>
	<section class="col-md-3 mb-3">
		<form id="wpcfe-search" class="float-md-none float-lg-right form-inline" action="<?php echo $page_url; ?>" method="get">
			<input type="hidden" name="wpcsc" value="s">
			<div class="form-sm">
				<label for="search-shipment" class="sr-only"><?php echo wpc_scpt_container_num_label(); ?></label>
				<input type="text" class="form-control form-control-sm" name="num" id="search-shipment" placeholder="<?php echo wpc_scpt_container_num_label(); ?>" value="<?php echo $searched; ?>">
				<button type="submit" class="btn btn-primary btn-sm mx-md-0 ml-2"><?php esc_html_e('Search', 'wpcargo-shipment-container' ); ?></button>
			</div>
		</form>
	</section>
</div>
<div id="wpcsc-action_wrapper" class="mb-3">
	<div class="table-top">
		<form id="shipment-sort" class="float-right" action="<?php echo $page_url; ?>" method="get">
			<select name="wpcsc_page" class="form-control form-control-sm browser-default" style="display: inline-block; margin: 0.375rem 0;">
				<option ><?php echo __('Show entries', 'wpcargo-shipment-container' ); ?></option>
				<?php foreach( $wpcsc_list as $list ): ?>
				<option value="<?php echo $list; ?>" <?php selected($wpcsc_page, $list ); ?>><?php echo $list ?> <?php echo __('entries', 'wpcargo-shipment-container' ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>
		
		<!-- ESTADÍSTICAS DE CONTENEDORES -->
		<div style="margin-right: 20px; display: inline-block;">
			<?php
				global $wpdb;
				
				// Ajustar a zona horaria de Perú (UTC-5)
				$original_tz = date_default_timezone_get();
				date_default_timezone_set('America/Lima');
				$today_peru = date('d/m/Y');
				$today_peru_alt = date('j/n/Y');
				date_default_timezone_set($original_tz);
				
				// Puntos de Recojo: contar usuarios ÚNICOS con shipment_container_recojo (HOY - zona Perú)
				$recojo_query = "
					SELECT COUNT(DISTINCT pm.meta_value) as conteo
					FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					INNER JOIN {$wpdb->postmeta} pd ON pd.post_id = p.ID AND pd.meta_key = 'wpcargo_pickup_date_picker'
					WHERE pm.meta_key = 'shipment_container_recojo'
					AND p.post_type = 'wpcargo_shipment'				AND p.post_status = 'publish'					AND pm.meta_value != ''
					AND (pd.meta_value = '{$today_peru}' OR pd.meta_value = '{$today_peru_alt}')
				";
				$puntos_recojo = $wpdb->get_var($recojo_query);
				$puntos_recojo = $puntos_recojo ?: 0;
				
				// Puntos de Entrega: contar total de envíos express y fullfillment que tengan shipment_container_entrega (HOY)
				$entrega_query = "
					SELECT COUNT(DISTINCT p.ID) as conteo
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_tipo ON pm_tipo.post_id = p.ID AND pm_tipo.meta_key = 'tipo_envio'
					INNER JOIN {$wpdb->postmeta} pm_fecha ON pm_fecha.post_id = p.ID AND pm_fecha.meta_key = 'wpcargo_pickup_date_picker'
					INNER JOIN {$wpdb->postmeta} pm_entrega ON pm_entrega.post_id = p.ID AND pm_entrega.meta_key = 'shipment_container_entrega'
					WHERE p.post_type = 'wpcargo_shipment'				AND p.post_status = 'publish'					AND pm_tipo.meta_value IN ('normal', 'express', 'fullfillment')
					AND pm_entrega.meta_value != ''
					AND (pm_fecha.meta_value = '{$today_peru}' OR pm_fecha.meta_value = '{$today_peru_alt}')
				";
				$puntos_entrega = $wpdb->get_var($entrega_query);
				
				// Debug detallado de los envíos
				$detail_entrega = "
					SELECT DISTINCT p.ID, pm_tipo.meta_value as tipo_envio, pm_fecha.meta_value as fecha_envio, pm_entrega.meta_value as container_entrega
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_tipo ON pm_tipo.post_id = p.ID AND pm_tipo.meta_key = 'tipo_envio'
					INNER JOIN {$wpdb->postmeta} pm_fecha ON pm_fecha.post_id = p.ID AND pm_fecha.meta_key = 'wpcargo_pickup_date_picker'
					INNER JOIN {$wpdb->postmeta} pm_entrega ON pm_entrega.post_id = p.ID AND pm_entrega.meta_key = 'shipment_container_entrega'
					WHERE p.post_type = 'wpcargo_shipment'
				AND p.post_status = 'publish'
					AND pm_entrega.meta_value != ''
					AND (pm_fecha.meta_value = '{$today_peru}' OR pm_fecha.meta_value = '{$today_peru_alt}')
				";
				$details_entrega = $wpdb->get_results($detail_entrega);
				
				error_log("DEBUG ENTREGA TODAY: {$today_peru} / {$today_peru_alt}");
				error_log("DEBUG ENTREGA QUERY: " . $entrega_query);
				error_log("DEBUG ENTREGA RESULT: " . $puntos_entrega);
				error_log("DEBUG ENTREGA DETAILS: " . var_export($details_entrega, true));
				
				$puntos_entrega = $puntos_entrega ?: 0;
			?>
			<span style="font-weight: bold; margin-right: 30px; color: #333;">
				📍 Puntos de Recojo: <span style="color: #007bff; font-size: 1.1em;"><?php echo $puntos_recojo; ?></span>
			</span>
			<span style="font-weight: bold; color: #333;">
				🚚 Puntos de Entrega: <span style="color: #28a745; font-size: 1.1em;"><?php echo $puntos_entrega; ?></span>
			</span>
		</div>
		
		<?php if( can_access_containers() && update_container_role() ): ?>
		<a href="<?php echo $page_url; ?>?wpcsc=add" class="addShipmentContainer btn btn-primary btn-sm"><i class="fa fa-truck text-white"></i> <?php echo wpc_scpt_add_new_item_label(); ?></a>
		<?php endif; ?>
		<?php do_action('wpcsc_after_add_container_dashboard'); ?>
	</div>
</div>
