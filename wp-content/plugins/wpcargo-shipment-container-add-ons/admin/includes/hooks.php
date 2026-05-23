<?php
// Frontend Manager Hooks
function wpcsc_registered_frontend_scripts( $scripts ){
    $scripts[] = 'shipment-container-sortable-scripts';
    $scripts[] = 'shipment-container-datatable-scripts';
    $scripts[] = 'shipment-container-scripts';
    $scripts[] = 'wpcsc-importexport-scripts';
    return $scripts;
}
function wpcsc_registered_frontend_style( $styles ){
    $styles[] ='shipment-container-datatable-styles';
    $styles[] ='shipment-container-styles';
    return $styles;
}
function wpcsc_after_container_modal_callback(){
    require_once( wpcsc_include_template('container-view-modal.tpl') );
}
function wpcsc_frontend_register_assets_callback(){
    add_filter( 'wpcfe_registered_styles', 'wpcsc_registered_frontend_style', 10 );
    add_filter( 'wpcfe_registered_scripts', 'wpcsc_registered_frontend_scripts', 10 );
    if( can_access_containers() ){
        add_action( 'before_wpcfe_shipment_form_submit', 'wpcsc_container_details', 40 );
    }
    add_action('after_wpcfe_save_shipment', 'save_shipment_container_frontend_callback', 10, 2);
    add_action( 'after_container_publish', 'wpcsc_after_container_publish_assigment_callback', 10, 2 );
    add_filter( 'wpcsc_shipment_action_rows', 'wpcsc_shipment_view_action_row', 10, 2 );
    add_filter( 'wpcsc_shipment_action_rows', 'wpcsc_shipment_update_action_row', 10, 2 );
    add_filter( 'wpcsc_shipment_action_rows', 'wpcsc_shipment_delete_action_row', 10, 2 );
    add_action( 'wpcsc_after_container_dashboard', 'wpcsc_after_container_modal_callback' );
}
add_action( 'plugins_loaded', 'wpcsc_frontend_register_assets_callback' );
// Container dashboard filters
function wpcsc_dashboard_filters_callback(){
    global $post, $wpcargo;
    if( $post->ID != wpc_container_frontend_page() ){
        return false;
    }
    if( isset( $_GET['wpcsc'] ) 
    && ( 
        $_GET['wpcsc'] == 'edit' || 
        $_GET['wpcsc'] == 'add' || 
        $_GET['wpcsc'] == 'import' || 
        $_GET['wpcsc'] == 'track' ||
        $_GET['wpcsc'] == 'export' ) ){
        return false;
    }

    $page_url       = get_the_permalink( wpc_container_frontend_page() );
    $user_wpcfesort = get_user_meta( get_current_user_id(), 'user_wpcfesort', true );
    $date_start     = date('Y-m-d', strtotime('today - '.wpcsc_date_filter_range().' days'));
    $date_end       = date('Y-m-d');
    $date_start     = isset( $_GET['date_start'] ) ? $_GET['date_start'] : $date_start;
    $date_end       = isset( $_GET['date_end'] ) ? $_GET['date_end'] : $date_end;
    $sstatus        = isset( $_GET['status'] ) ? trim($_GET['status']) : '';
    $searched       = isset( $_GET['num'] ) ? trim($_GET['num']) : '';
    $wpcsc_list     = array( 10, 25, 50, 100 );
    $wpcsc_page     = $user_wpcfesort ? $user_wpcfesort : 25 ;
    require_once( wpcsc_include_template('container-filters.tpl') );
}
add_action('wpcfe_before_dashboard_page', 'wpcsc_dashboard_filters_callback' );
function wpcsc_before_container_filters_callback(){
    $filter_key     = apply_filters( 'wpcsc_before_container_filter_metakey', 'container_no' );
    $container_info = wpc_container_info_fields();
    $value          = isset( $_GET[$filter_key] ) ? $_GET[$filter_key] : '' ;
    ?>
    <div class="form-group wpcsc_filter-<?php echo $filter_key; ?>-wrap p-0 mx-1">
        <label class="sr-only" for="wpcsc_filter-<?php echo $filter_key; ?>"><?php echo $container_info[$filter_key]['label']; ?></label>
        <input type="text" class="form-control form-control-sm" name="<?php echo $filter_key; ?>" id="wpcsc_filter-<?php echo $filter_key; ?>" placeholder="<?php echo $container_info[$filter_key]['label']; ?>" value="<?php echo $value; ?>">
    </div>
    <?php
}
add_action( 'wpcsc_before_container_filters', 'wpcsc_before_container_filters_callback' );
function wpcsc_dashboard_meta_query_callback( $meta_queries ){
    $filter_key     = apply_filters( 'wpcsc_before_container_filter_metakey', 'container_no' );
    if( !isset( $_GET[$filter_key] ) || empty( $_GET[$filter_key] ) ){
        return $meta_queries;
    }
    $meta_queries[$filter_key] = array(
        'key' => $filter_key,
        'value' => urldecode( $_GET[$filter_key] ),
        'compare' => 'LIKE'
    );
    return $meta_queries;
}
add_filter( 'wpcsc_dashboard_meta_query', 'wpcsc_dashboard_meta_query_callback' );
function wpcsc_dashboard_date_main_arguments_callback( $args ){
    if( array_key_exists( 's', $args ) && !empty( $args['s'] ) ){
        return $args;
    }
    $date_start     = date('Y-m-d', strtotime('today - '.wpcsc_date_filter_range().' days'));
    $date_end       = date('Y-m-d');
    $date_start     = isset( $_GET['date_start'] ) ? $_GET['date_start'] : $date_start;
    $date_end       = isset( $_GET['date_end'] ) ? $_GET['date_end'] : $date_end;
    $args['date_query'] = array(
        array(
            'after'     => $date_start,
            'before'    => $date_end,
            'inclusive' => true,
        )
    );
    return $args;
}
add_filter( 'wpcsc_dashboard_main_arguments', 'wpcsc_dashboard_date_main_arguments_callback' );
// FM Sidebar Menu
add_filter('wpcfe_after_sidebar_menus', 'wpc_container_sidebar_menu', 5, 1 );
function wpc_container_sidebar_menu( $menu_array ){
    if( function_exists('wpcfe_admin_page') && can_access_containers() ){
        $menu_array['wpcsc-menu'] = array(
            'page-id' => wpc_container_frontend_page(),
            'label' => wpc_container_label_plural(),
            'permalink' => get_the_permalink( wpc_container_frontend_page() ),
            'icon' => 'fa-truck'
        ) ;
    }
    return $menu_array;
}
add_action( 'wpcsc_table_header_value', 'wpcsc_container_list_header' );
function wpcsc_container_list_header(){
    $key_label = wpc_shipment_container_key_label_header_callback();
    if( !empty( $key_label ) ){
        foreach ( $key_label as $key => $value) {
            $_class = '';
            
            // ❌ Ocultar columnas innecesarias - solo mostrar "shipments" y "scmanifest"
            if( $key !== 'shipments' && $key !== 'scmanifest' ){
                continue;
            }
            
            if(  $key == 'scprint' ){
                continue;
            }
            if( $key == 'scmanifest' ){
                $_class = 'text-center';
            }
            ?><th class="<?php echo $_class; ?>"><?php echo $value; ?></th><?php
        }
    }
}
// Import / Export Hooks &&  Bulk Delete
function wpcsc_import_export_dashboard_callback(){
    $page_url       	= get_the_permalink( wpc_container_frontend_page() );
    ?>
    <?php if( can_import_containers() ): ?>
    <a href="<?php echo $page_url; ?>?wpcsc=import" class="btn btn-secondary btn-sm"><i class="fa fa-recycle text-white"></i> <?php echo wpc_scpt_import_export_container_label(); ?></a>
    <?php endif; ?>
    <?php if( delete_containers_roles() ): ?>
    <button id="wpcsc_container_bulk-delete" class="btn btn-danger btn-sm"><i class="fa fa-trash text-white"></i> <?php _e( 'Bulk Delete', 'wpcargo-shipment-container' ) ?></button>
    <?php endif; ?>
    <?php
}
add_action( 'wpcsc_after_add_container_dashboard', 'wpcsc_import_export_dashboard_callback',10 );



// Add Shipment table for the assigned container
add_action( 'wpcfe_shipment_table_header', 'wpcsc_shipment_container_table_header', 10 );
function wpcsc_shipment_container_table_header(){
    echo '<th class="no-space">'.esc_html__( apply_filters( 'wpcsc_shipment_container_table_header_label', __('Container', 'wpcargo-shipment-container' ) ) ).'</th>';
}
add_action( 'wpcfe_shipment_table_data', 'wpcsc_shipment_container_table_data', 10, 1 );
function wpcsc_shipment_container_table_data( $shipment_id ){
    $value          = '';
    $container_id   = wpcsc_get_shipment_container( $shipment_id );
    if( $container_id ){
        $container_number = wpcsc_get_container_number( $container_id  );
        if( $container_number ){
            $value = $container_number;
        }else{
            $value = '<span><i class="unassigned-shipment fa fa-unlink fa-lg mr-2 text-danger" data-id="'.$shipment_id.'"></i>'.__('Container NOT Found', 'wpcargo-shipment-container' ).'</span>';
        }
    }
    echo '<td class="no-space">'.$value.'</td>';
}


add_action( 'wpcsc_table_data_value', 'wpcsc_container_list_data', 10, 1 );
function wpcsc_container_list_data( $container_id ){
    global $wpcargo;
    $key_label = wpc_shipment_container_key_label_header_callback();
    if( !empty( $key_label ) ){
        foreach ( $key_label as $key => $value) {
            
            // ❌ Ocultar columnas innecesarias - solo mostrar "shipments" y "scmanifest"
            if( $key !== 'shipments' && $key !== 'scmanifest' ){
                continue;
            }
            
            if(  $key == 'scprint' ){
                continue;
            }
            $_value = '';
            $_class = '';
            
            if ( $key == 'flight' ){
                $_value = get_post_meta( $container_id, 'container_no', TRUE );
            }
            
            if( $key == 'shipments' ){
                $shipment_count = wpc_shipment_container_get_assigned_shipment_count($container_id);
                
                // Nuevo texto personalizado
                if ($shipment_count > 0) {
                    $shipment_count_label = $shipment_count . ' envío' . ($shipment_count > 1 ? 's' : '') . ' pendiente' . ($shipment_count > 1 ? 's' : '') . ' de asignación';
                } else {
                    $shipment_count_label = 'Sin envíos';
                }
                
                $_value = $shipment_count 
                ? '<span class="text-info openAssShipmentModal" data-id="'.$container_id.'"><i class="fa fa-list"></i> '.$shipment_count_label.'</span>' 
                : '';
            }
            
            if( $key == 'agent' ){
                $_value = get_post_meta( $container_id, 'container_agent', TRUE );
            }
            if( $key == 'delivery_agent' ){
                $_value =  get_post_meta( $container_id, 'delivery_agent', TRUE );
            }
            if( $key == 'status' ){
                $_value =  get_post_meta( $container_id, 'container_status', TRUE );
            }
            if( $key == 'scmanifest' ){
                $_value = wpcsc_shipment_table_action_print( $container_id );
                $_class = 'text-center';
            }
            ?><td class="<?php echo $_class; ?>"><?php echo $_value; ?></td><?php
        }
    }
}
// track result Hooks
add_action( 'container_track_result_after_details', 'container_track_assigned_shipment_callback', 10, 1 );
function container_track_assigned_shipment_callback( $container_id ){
    global $wpcargo;
    $shipments 		= wpc_shipment_container_get_assigned_shipment( $container_id );
    if( empty($shipments) ){
        return false;
    }
    
    // Obtener información del tipo de meta para cada shipment
    $shipments_with_type = wpc_shipment_container_get_assigned_shipment_with_type( $container_id );
    
    ?>
    <div id="container-shipments" class="col-sm-12 my-4">
        <p class="section-header h5-responsive font-weight-normal pb-2 border-bottom"><?php echo wpc_scpt_assinged_container_label(); ?></p>
        <div class="container-fluid w-100 m-0">
            <div class="row">
                <?php foreach( $shipments as $shipment_id ):  
                    // Determinar el tipo de envío y el label
                    $shipment_type = isset($shipments_with_type[$shipment_id]) ? $shipments_with_type[$shipment_id] : 'legacy';
                    
                    if ($shipment_type === 'recojo') {
                        $type_label = 'Para Recoger';
                        $type_color = 'badge-primary';
                    } elseif ($shipment_type === 'entrega') {
                        $type_label = 'Para Entregar';
                        $type_color = 'badge-success';
                    } else {
                        $type_label = '';
                        $type_color = '';
                    }
                ?>
                    <div id="shipment-<?php echo $shipment_id; ?>" class="selected-shipment text-center p-1 col-md-4 border" >
                        <?php do_action( 'wpcsc_before_shipment_content_section', $shipment_id ); ?>
                        <img src="<?php echo $wpcargo->barcode_url( $shipment_id); ?>" alt="<?php echo get_the_title( $shipment_id ); ?>" />
                        <h3 class="shipment-title h6"><?php echo get_the_title( $shipment_id  ); ?></h3>
                        <?php if (!empty($type_label)): ?>
                            <span class="badge <?php echo $type_color; ?>" style="margin-top: 5px; display: inline-block;">
                                <?php echo $type_label; ?>
                            </span>
                        <?php endif; ?>
                        <?php do_action( 'wpcsc_after_shipment_content_section', $shipment_id ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>   
    </div>
    <?php
}
add_action( 'container_track_result_after_details', 'container_track_history_callback', 10, 1 );
function container_track_history_callback( $container_id ){
    global $wpcargo;
    $history    = wpcsc_get_container_history( $container_id );  
    if( empty($history) ){
        return false;
    }  
    include_once( wpcsc_include_template( 'track-history.tpl' ) );	
}
add_action( 'wpc_shipment_additional_container_info', 'container_update_history_callback', 10, 1 );
function container_update_history_callback( $container_id ){
    global $wpcargo;
    $history    = wpcsc_get_container_history( $container_id );  
    ob_start();
    ?>
    <div id="time-info" class="col-md-12 mb-4">
        <div class="card">
            <section class="card-header">
            <?php echo apply_filters( 'wpcsc_history_label', __('History', 'wpcargo-shipment-container') ); ?>                
            </section>
            <section class="card-body">
            <?php require_once( wpcsc_admin_include_template('history.tpl') ); ?>
            </section>
        </div>
    </div>
    <?php
    echo ob_get_clean();
}
add_action( 'wp', 'wpcsc_save_container_callback' );
function wpcsc_save_container_callback(){
    // Check if nonce is isset
    global $wpcargo;
    if ( ! isset( $_POST['wpcsc_nonce_field_value'] ) 
        || ! wp_verify_nonce( $_POST['wpcsc_nonce_field_value'], 'wpcsc_form_action' ) 
    ) {
        return false;
    }
    $is_update = false;
    if( !isset( $_POST['container_id'] ) || !(int)$_POST['container_id'] ){
        $container_args = array(
            'post_title'    => sanitize_text_field( $_POST['wpcsc_number'] ),
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'post_type'		=> 'shipment_container'
        );
        $container_id = wp_insert_post( $container_args );
    }else{
        $container_id       = (int)$_POST['container_id'];	
        $container_args = array(
            'ID'            => $container_id,
            'post_title'    => sanitize_text_field( $_POST['wpcsc_number'] ),
            'post_status'   => 'publish',
        );
        wp_update_post( $container_args );
        $is_update = true;
    }
    $info_fields = wpc_container_info_fields();
    if( !empty( $info_fields  ) ){
        foreach ( $info_fields as $key => $value) {
			if( isset( $_POST[$key] ) && !empty( $_POST[$key] ) ) {
				update_post_meta( $container_id, $key, sanitize_text_field( $_POST[$key] ) );
			}
        }
    }
    $trip_fields = wpc_trip_info_fields();
    if( !empty( $trip_fields  ) ){
        foreach ( $trip_fields as $key => $value) {
			if( isset( $_POST[$key] ) && !empty( $_POST[$key] ) ){
				update_post_meta( $container_id, $key, sanitize_text_field( $_POST[$key] ) );
			}
        }
    }
    $time_fields = wpc_time_info_fields();
    if( !empty( $time_fields  ) ){
        foreach ( $time_fields as $key => $value) {
			if( isset( $_POST[$key] ) && !empty( $_POST[$key] ) ){
            	update_post_meta( $container_id, $key, sanitize_text_field( $_POST[$key] ) );
			}
        }
    }

    // Assinged Shipments
    if( !$is_update ){
        $pre_assigned_shipments = wpc_shipment_container_get_preassigned_shipment( );
        if(!empty($pre_assigned_shipments)){
            foreach ( $pre_assigned_shipments  as $pre_shipment_id ) {
                update_post_meta( (int)$pre_shipment_id, 'shipment_container', $container_id );
            }
        }
    }
    $assigned_shipments = sanitize_text_field( $_POST['wpcc_sorted_shipments'] );
    update_post_meta( $container_id, 'wpcc_sorted_shipments', $assigned_shipments );
    $assigned_shipments = $assigned_shipments ? explode(',', $assigned_shipments ) : array(); 
    foreach ( $assigned_shipments as $shipment_id ) {
        if( !(int)$shipment_id ){
            continue;
        }
        update_post_meta( (int)$shipment_id, 'shipment_container', $container_id );
    }

    wpcsc_save_history( $container_id, $_POST );
	do_action( 'after_container_publish', $container_id, $_POST );
    wp_redirect( get_the_permalink().'/?wpcsc=edit&id='.$container_id.'&update=1' );
    die;
}
function wpcsc_container_details( $shipment_id ){
	$container_id   = wpcsc_get_shipment_container( $shipment_id );
	// Obtener tipo_envio: primero del POST (creación), luego del meta (edición)
	// Nota: El meta se llama 'tipo_envio' NO 'wpcargo_tipo_envio'
	$tipo_envio     = isset( $_POST['tipo_envio'] ) ? sanitize_text_field( $_POST['tipo_envio'] ) : get_post_meta( $shipment_id, 'tipo_envio', true );
	// Fallback: también buscar en $_GET['type'] por si acaso
	if(empty($tipo_envio) && isset($_GET['type'])) {
		$tipo_envio = sanitize_text_field($_GET['type']);
	}
	
	$container_recojo = get_post_meta( $shipment_id, 'shipment_container_recojo', true );
	$container_entrega = get_post_meta( $shipment_id, 'shipment_container_entrega', true );
    $containers     = get_shipment_containers();
    $es_merc_emprendedor = strtolower( $tipo_envio ) === 'normal';
    
    // DEBUG
    error_log("🔍 [CONTAINER_DETAILS] Envío #{$shipment_id} | tipo_envio desde POST: " . (isset($_POST['tipo_envio']) ? $_POST['tipo_envio'] : 'NO') . " | tipo_envio desde meta: " . get_post_meta( $shipment_id, 'tipo_envio', true ) . " | tipo_envio final: {$tipo_envio} | es_merc: " . ($es_merc_emprendedor ? 'YES' : 'NO'));
	?>
	<div id="consolidated-details" class="card mb-4">
		<div class="card">
			<section class="card-header">
				<?php echo apply_filters( 'wpcfe_multipack_header_label', esc_html__('Container Details','wpcargo-shipment-container') ); ?>
			</section>
			<section class="card-body">					
					<?php do_action( 'before_container_details_row', $shipment_id ); ?>
					
					<?php if( $es_merc_emprendedor ): ?>
						<!-- MERC EMPRENDEDOR: Mostrar AMBOS contenedores -->
						<div class="form-group">
							<label><?php _e( 'Contenedor de RECOJO', 'wpcargo-shipment-container' ); ?></label>                   
                            <select name="shipment_container_recojo" class="mdb-select mt-0 form-control browser-default" id="shipment_container_recojo" >
                                <option value=""><?php esc_html_e('-- Seleccionar Contenedor --','wpcargo-shipment-container'); ?></option>
                                <?php if( $containers ): ?>
                                    <?php foreach( $containers as $container ): ?>
                                        <option value="<?php echo $container->ID; ?>" <?php selected($container_recojo, $container->ID); ?>><?php echo $container->post_title; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
						</div>
						
						<div class="form-group">
							<label><?php _e( 'Contenedor de ENTREGA', 'wpcargo-shipment-container' ); ?></label>                   
                            <select name="shipment_container_entrega" class="mdb-select mt-0 form-control browser-default" id="shipment_container_entrega" >
                                <option value=""><?php esc_html_e('-- Seleccionar Contenedor --','wpcargo-shipment-container'); ?></option>
                                <?php if( $containers ): ?>
                                    <?php foreach( $containers as $container ): ?>
                                        <option value="<?php echo $container->ID; ?>" <?php selected($container_entrega, $container->ID); ?>><?php echo $container->post_title; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
						</div>
					<?php else: ?>
						<!-- Otros tipos: mostrar UN solo contenedor (compatibilidad) -->
						<div class="form-group">
							<label><?php _e( 'Shipment Container', 'wpcargo-shipment-container' ); ?></label>                   
                            <select name="shipment_container" class="mdb-select mt-0 form-control browser-default" id="shipment_container" >
                                <option value=""><?php esc_html_e('-- Select Container --','wpcargo-shipment-container'); ?></option>
                                <?php if( $containers ): ?>
                                    <?php foreach( $containers as $container ): ?>
                                        <option value="<?php echo $container->ID; ?>" <?php selected($container_id, $container->ID); ?>><?php echo $container->post_title; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
						</div>
					<?php endif; ?>
					
				<?php do_action( 'after_container_details_row', $shipment_id ); ?>					
			</section>
		</div>
	</div>
	<?php
}
function wpcsc_after_container_publish_assigment_callback( $container_id, $post ){

    if( isset($post['registered_shipper']) ){
        update_post_meta( $container_id, 'registered_shipper', sanitize_text_field( $post['registered_shipper'] ) );
    }
    if( isset($post['agent_fields']) ){
        update_post_meta( $container_id, 'agent_fields', sanitize_text_field( $post['agent_fields'] ) );
    }
    if( isset($post['wpcargo_employee']) ){
        update_post_meta( $container_id, 'wpcargo_employee', sanitize_text_field( $post['wpcargo_employee'] ) );
    }
    if( isset($post['wpcargo_branch_manager']) ){
        update_post_meta( $container_id, 'wpcargo_branch_manager', sanitize_text_field( $post['wpcargo_branch_manager'] ) );
    }
    if( isset($post['shipment_branch']) ){
        update_post_meta( $container_id, 'shipment_branch', sanitize_text_field( $post['shipment_branch'] ) );
    }
    if( isset($post['wpcargo_driver']) ){
        update_post_meta( $container_id, 'wpcargo_driver', sanitize_text_field( $post['wpcargo_driver'] ) );
    }
}
function save_shipment_container_frontend_callback( $post_id, $post ){
    if( !can_access_containers() ) {
        return;
    }
    
    // Obtener tipo_envio: primero del POST (actual), luego del meta (fallback)
    // Nota: El meta se llama 'tipo_envio' NO 'wpcargo_tipo_envio'
    $tipo_envio = isset( $post['tipo_envio'] ) ? sanitize_text_field( $post['tipo_envio'] ) : get_post_meta( $post_id, 'tipo_envio', true );
    $es_merc_emprendedor = strtolower( $tipo_envio ) === 'normal';
    
    error_log("✅ [FRONTEND SAVE] Envío #{$post_id}: tipo_envio = {$tipo_envio} | es_merc = " . ($es_merc_emprendedor ? 'YES' : 'NO'));
    
    if( $es_merc_emprendedor ) {
        // MERC EMPRENDEDOR: Guardar AMBOS contenedores
        if( isset($post['shipment_container_recojo']) ) {
            $container_recojo = sanitize_text_field( $post['shipment_container_recojo'] );
            if( !empty($container_recojo) ) {
                update_post_meta( $post_id, 'shipment_container_recojo', (int)$container_recojo );
                error_log("✅ [FRONTEND SAVE] Envío #{$post_id}: shipment_container_recojo #{$container_recojo} guardado desde frontend");
            }
        }
        
        if( isset($post['shipment_container_entrega']) ) {
            $container_entrega = sanitize_text_field( $post['shipment_container_entrega'] );
            if( !empty($container_entrega) ) {
                update_post_meta( $post_id, 'shipment_container_entrega', (int)$container_entrega );
                error_log("✅ [FRONTEND SAVE] Envío #{$post_id}: shipment_container_entrega #{$container_entrega} guardado desde frontend");
            }
        }
    } else {
        // Otros tipos: Guardar un solo contenedor (compatibilidad)
        if( isset($post['shipment_container']) && !empty($post['shipment_container']) ) {
            update_post_meta( $post_id, 'shipment_container', sanitize_text_field( $post['shipment_container'] ) );
            error_log("✅ [FRONTEND SAVE] Envío #{$post_id}: shipment_container guardado desde frontend");
        }
    }
}
// Plugin Rows Hook
function wpc_shipment_container_row_action_callback( $links ){
    $action_links = array(
        'settings' => '<a href="' . admin_url( 'admin.php?page=wpc-container-settings' ) . '" aria-label="' . esc_attr__( 'Settings', 'wpcargo-shipment-container' ) . '">' . esc_html__( 'Settings', 'wpcargo-shipment-container' ) . '</a>',
        'license' => '<a href="' . admin_url( 'admin.php?page=wptaskforce-helper' ) . '" aria-label="' . esc_attr__( 'License', 'wpcargo-shipment-container' ) . '">' . esc_html__( 'License', 'wpcargo-shipment-container' ) . '</a>',
    );
    return array_merge( $action_links, $links );
}
add_filter('plugin_action_links_' . WPCARGO_SHIPMENT_CONTAINER_BASENAME, 'wpc_shipment_container_row_action_callback', 10, 2);
// API Add on Hooks
function wpcscon_api_shipment_data_callback( $data, $shipment_id ){
    $container_id = get_post_meta( $shipment_id, 'shipment_container', true );
    $data['shipment_container'] = get_the_title( $container_id );
    return $data;
}
add_filter('wpcargo_api_shipment_data', 'wpcscon_api_shipment_data_callback', 10, 2);
function wpcscon_api_after_add_shipment_callback( $shipmentID, $request ){
    $shipment_container  = $request->get_param( 'shipment_container' );
    if( empty( $shipment_container ) ){
        return false;
    }
    $container_id = wpcsc_get_container_id( $shipment_container );
    if( !$container_id ){
        return false;
    }
    update_post_meta( $shipmentID, 'shipment_container', (int)$container_id );
}
add_action( 'wpcargo_api_after_add_shipment', 'wpcscon_api_after_add_shipment_callback', 10, 2 );
function wpcscon_api_after_update_shipment_callback( $shipmentID, $request ){
    $shipment_container  = $request->get_param( 'shipment_container' );
    if( empty( $shipment_container ) ){
        return false;
    }
    $container_id = wpcsc_get_container_id( $shipment_container );
    if( !$container_id ){
        return false;
    }
    update_post_meta( $shipmentID, 'shipment_container', (int)$container_id );
}
add_action( 'wpcargo_api_after_update_shipment', 'wpcscon_api_after_update_shipment_callback', 10, 2 );
function wpcsc_before_sidebar_assignment_section_callback( $container_id ){
    include_once( WPCARGO_SHIPMENT_CONTAINER_PATH.'templates/container-form-assign-role.tpl.php' );
}
add_action( 'wpcsc_before_sidebar_form_section', 'wpcsc_before_sidebar_assignment_section_callback', 10 );
function wpcsc_before_sidebar_form_section_callback( $container_id ){
    global $wpcargo;
    include_once( WPCARGO_SHIPMENT_CONTAINER_PATH.'templates/container-form-misc.tpl.php' );
}
add_action( 'wpcsc_before_sidebar_form_section', 'wpcsc_before_sidebar_form_section_callback', 10 );
// Manifest Helpers
function wpcsc_pdf_siteinfo_manifest_callback( $container_id, $site_info = '' ){
    include_once( wpcsc_include_template( 'siteinfo', 'manifest' ) );	
}
function wpcsc_pdf_header_manifest_callback( $container_id, $site_info = '' ){
    $container_detail_headers = array_values(wpcsc_csv_container_detail_headers());
    $container_detail_values = wpcsc_csv_container_detail_values($container_id);
    $merged_container_details = array_combine($container_detail_headers, $container_detail_values);
    $wpcsc_csv_delivery_zone_detail_headers = array_values(wpcsc_csv_delivery_zone_detail_headers());
    $delivery_zone_detail_values = array_values(wpcsc_csv_delivery_zone_detail_values($container_id, true));
    $box_types_str = get_option('wpcsb_box_type') ?: '';
    $box_types_arr = array();
    $translate     = array(' ' => '-');

    # format box types from options
    if(!empty($box_types_str)){
        $box_types_arr = array_map(function($x)use($translate){
            return strtolower(strtr(sanitize_text_field($x), $translate));
        }, explode(',', $box_types_str));
    }
    include_once( wpcsc_include_template( 'header', 'manifest' ) );
}
function wpcsc_pdf_content_manifest_callback( $container_id, $site_info = '' ){
    global $wpcargo;
    $delivery_zone_detail_values = array_values(wpcsc_csv_delivery_zone_detail_values($container_id, true));
    $shipments_per_dz_detail_headers = array_merge(wpcsc_csv_shipments_per_zone_additional_headers(), array_values(wpcsc_csv_shipments_per_zone_meta_details()));
    $shipment_fields    = apply_filters( 'wpcsc_manifest_registered_fields', get_option('container_field_manifest') );
    $url_barcode	    = WPCARGO_PLUGIN_URL."/includes/barcode.php?codetype=Code128&size=60&text=";
    $shipments		    = wpc_shipment_container_get_assigned_shipment($container_id);	
	$shipment_ids 		= apply_filters( 'wpcsc_shipment_manifest_list', $shipments, $container_id );
    $delivery_zone      = array();
    
    foreach ($shipment_ids as $ship_id) {
        $shipment_type = get_post_meta( $ship_id, '__shipment_type', true );
        $zone      = ($shipment_type == 'shipment-box'  ) ?get_post_meta( $ship_id,'wpcargo_reciever_zone',true):get_post_meta( $ship_id,'wpcargo_delivery_zone',true);
        $delivery_zone[$zone][] = $ship_id;
    }
    include_once( wpcsc_include_template( 'content', 'manifest' ) );	
}
function wpcsc_pdf_before_footer_manifest_callback( $container_id, $site_info = '' ){
    $acknowledgement    = wpautop(get_option('container_manifest_acknowledge'));
    $footer_data 	    = wpautop(get_option('container_print_footer'));	
    include_once( wpcsc_include_template( 'footer', 'manifest' ) );	
}
add_action( 'wpcsc_pdf_header_manifest', 'wpcsc_pdf_siteinfo_manifest_callback', 10, 2 );
add_action( 'wpcsc_pdf_header_manifest', 'wpcsc_pdf_header_manifest_callback', 10, 2 );
add_action( 'wpcsc_pdf_content_manifest', 'wpcsc_pdf_content_manifest_callback', 10, 2 );
add_action( 'wpcsc_pdf_footer_manifest', 'wpcsc_pdf_before_footer_manifest_callback', 10, 2 );
// Import/Export Hooks
function wpcsc_ie_registered_field( $fields ){
    $fields[] = array(
        'meta_key' 	=> 'wpcsc_container',
        'label' 	=> esc_html__( 'Container Number', 'wpcargo-shipment-container' ),
        'fields' 	=> array()
    );
    return $fields;
}
function wpcsc_export_data_callback( $data, $shipment_id, $meta_key ){
    $container_id   = wpcsc_get_shipment_container( $shipment_id );
    if( $meta_key === 'wpcsc_container' && $container_id ){
        return get_the_title( $container_id );
    }
    return $data;
}
function wpcsc_import_save_data_callback( $shipment_id, $data ){
    if( array_key_exists( 'wpcsc_container', $data ) ){
        $container_id = wpcsc_get_container_id( $data['wpcsc_container'] );
        if( $container_id ){
            update_post_meta( $shipment_id, 'shipment_container', $container_id );
        }
    }
}
function wpcsc_plugins_loaded_callback(){
    add_filter( 'ie_registered_fields', 'wpcsc_ie_registered_field' );
    add_filter( 'wpc_ie_meta_data', 'wpcsc_export_data_callback', 10, 3 );
    add_action( 'wpcie_after_save_csv_import', 'wpcsc_import_save_data_callback', 10, 2 );
}

// get box type
function wpcsc_get_box_type($rate_id) {
    global $wpdb;
    $query = $wpdb->prepare("SELECT `box_type` FROM {$wpdb->prefix}wpcbox_rate WHERE id = %d", $rate_id);
    $result = $wpdb->get_row($query);
    return $result;
}

add_action( 'plugins_loaded', 'wpcsc_plugins_loaded_callback' );
//** Load Plugin text domain
add_action( 'plugins_loaded', 'wpc_shipment_container_load_textdomain' );
function wpc_shipment_container_load_textdomain() {
	load_plugin_textdomain( 'wpcargo-shipment-container', false, '/wpcargo-shipment-container-add-ons/languages' );
}
function wpc_shipment_container_user_sort(){
	$wpcfesort_list = array( 10, 25, 50, 100 );
	if( isset( $_GET['wpcsc_page'] ) && in_array( $_GET['wpcsc_page'], $wpcfesort_list ) ){
		update_user_meta( get_current_user_id(), 'user_wpcfesort', $_GET['wpcsc_page'] );
	}
}
add_action( 'wp_head', 'wpc_shipment_container_user_sort' );

//include assigned branch and branch manager
add_action( 'wpcfe_before_assign_form_content', 'wpcbm_assigned_branch_custom', 10, 2 );
function wpcbm_assigned_branch_custom( $container_id ){
    if( !function_exists( 'wpcbm_get_all_branch' ) ){
        return false;
    }
	$shipment       = new stdClass();
	$all_branch		= wpcbm_get_all_branch( -1 );
	$shipment_branch = get_post_meta( $container_id, 'shipment_branch', true ) ? get_post_meta( $container_id, 'shipment_branch', true ) : '';
	if( !can_wpcfe_assign_branch_manager() ){
		return false;
	}
	?>
	<div>
        <?php echo wpcdm_assign_branch_label(); ?>
        <div class="form-row">
            <?php if( !empty( $all_branch ) ): ?>
                <select id="wpc-user-branch" name="shipment_branch" class="mdb-select mt-0 form-control browser-default">
                    <option value=""><?php echo wpcdm_select_branch_label(); ?></option>
                    <?php foreach ( $all_branch as $branch ): ?>
                        <option value="<?php echo $branch->id; ?>" <?php selected( $shipment_branch, $branch->id ); ?>><?php echo $branch->name; ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <i><?php esc_html_e('No available branches.', 'wpcargo-branches' ).' * '; ?></i>
            <?php endif; ?>
        </div>
	</div>
	<?php
}
//#AJAX for Assigned Branch
function assigned_branch_ajax_select_branch(){
    if( !function_exists( 'wpcdm_get_branch' ) ){
        return false;
    }
    $branch = $_POST['selectedBranch'];
    $get_branch = wpcdm_get_branch( $branch );
    $branch_managers = unserialize( $get_branch['branch_manager'] );
    ?>
    <option value=""><?php esc_html_e('-- Select Branch Manager --', 'wpcargo-branches'); ?></option>
    <?php
    foreach( wpcargo_get_branch_managers() as $branch_managerID => $branch_manager_name ){
       // echo $branch_managerID;
       if(empty($branch_managerID) ||$branch_managerID=='' || $branch_managerID==0 ){
        continue;
        }
        if (empty($branch_managers)){
            continue;
        } 
        if( in_array( $branch_managerID, $branch_managers ) ){
            ?>
            <option value="<?php echo $branch_managerID; ?>"><?php echo $branch_manager_name; ?></option>
            <?php
        }
    }
    die();

}
add_action( 'wp_ajax_select_branch', 'assigned_branch_ajax_select_branch' );
add_action( 'wp_ajax_nopriv_select_branch', 'assigned_branch_ajax_select_branch' );

remove_action( 'wpcfe_after_designation_dropdown', 'assign_branch_manager_dropdown' );
add_action( 'wpcfe_after_designation_dropdown', 'wpcsc_assign_branch_manager_dropdown' );
function wpcsc_assign_branch_manager_dropdown( $container_id ){
    if( !function_exists( 'wpcdm_get_branch' ) ){
        return false;
    }
	$branch 			= get_post_meta( $container_id, 'shipment_branch', true );
	$get_branch 		= wpcdm_get_branch( $branch );
	$branch_managers 	= !empty( $get_branch ) && array_key_exists('branch_manager', $get_branch ) ? unserialize( $get_branch['branch_manager'] ) : array();
	if( !can_wpcfe_assign_branch_manager() ){
		return false;
	}
	?>
	<div class="form-group">
		<div class="select-no-margin">
			<label><?php esc_html_e('Branch Manager','wpcargo-branches'); ?></label>
			<?php if( !empty( $branch ) ): ?>
				<select name="wpcargo_branch_manager" class="mdb-select mt-0 form-control browser-default" id="wpcargo_branch_manager">
					<option value=""><?php esc_html_e('-- Select Branch Manager --', 'wpcargo-branches'); ?></option>
					<?php if( !empty( wpcargo_get_branch_managers() ) ): ?>
						<?php foreach( wpcargo_get_branch_managers() as $branch_managerID => $branch_manager_name ):
                                   if(empty($branch_managerID) ||$branch_managerID=='' || $branch_managerID==0 ){
                                    continue;
                                    }
                                    if (empty($branch_managers)){
                                        continue;
                                    } 
                            ?>
							<?php if( in_array( $branch_managerID, $branch_managers ) ): ?>
								<option value="<?php echo $branch_managerID; ?>" <?php selected( get_post_meta( $container_id, 'wpcargo_branch_manager', TRUE ), $branch_managerID ); ?>><?php echo $branch_manager_name; ?></option>
							<?php endif; ?>
						<?php endforeach; ?>	
					<?php endif; ?>	                
				</select>
			<?php else: ?>
				<select name="wpcargo_branch_manager" class="mdb-select mt-0 form-control browser-default" id="wpcargo_branch_manager" disabled>
					<option value=""><?php esc_html_e('-- Select Branch Manager --', 'wpcargo-branches'); ?></option>	                
				</select>
				<i class="text-danger empty-branch-notice"><?php esc_html_e('Please select branch before assigning branch manager.','wpcargo-branches'); ?></i>
			<?php endif; ?>
		</div>
	</div>
	<?php 

}
function wpcsc_shipment_table_action_print( $container_id ){
    $print_options = wpcsc_print_options();
    if( empty( $print_options ) ) return false;
    ?>
    <td class="text-center">
        <div class="dropdown" style="display:inline-block !important;">
            <!--Trigger-->
            <button class="btn btn-default btn-sm dropdown-toggle m-0 py-1 px-2" type="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i></button>
            <!--Menu-->
            <div class="dropdown-menu dropdown-primary">
                <?php foreach( $print_options as $print_key => $print_label ): ?>
                    <a class="dropdown-item py-1" href="<?php echo get_the_permalink(wpc_container_frontend_page()). '?wpcsc'.$print_key.'='.$container_id; ?>"><?php echo $print_label; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </td>
    <?php
} 

function wpcsc_csv_maker() {
    global $wpcargo;
    if ((isset($_GET['wpcsccsv']) || isset($_GET['wpcscxlxs'])) && is_user_logged_in()) {
        $containerID = (isset($_GET['wpcsccsv'])) ? $_GET['wpcsccsv'] : $_GET['wpcscxlxs'];
        $format = (isset($_GET['wpcsccsv'])) ? 'csv' : 'xlsx';

        if (get_post_type($containerID) == 'shipment_container' && get_post_status($containerID) == 'publish') {
            # get container title by id
            $container_title = get_the_title($containerID);
            $filename = $container_title.'-'.time().'.csv';
            $filename = apply_filters('wpcsc_csv_filename', $filename, $containerID, $container_title);
			       

            # declare main csv line holders
            $rows = array();
            
            # get wpcargo logo url
            $logo_url = $wpcargo->logo;

            # construct csv rows
            $site_info = array($logo_url, wpcsc_csv_manifest_header_label());
            $dm_label = array(wpcsc_csv_delivery_manifest_header_label());
            $container_detail_headers = array_values(wpcsc_csv_container_detail_headers());
            $container_detail_values = wpcsc_csv_container_detail_values($containerID);
            $delivery_zone_detail_headers = array_values(wpcsc_csv_delivery_zone_detail_headers());
            $delivery_zone_detail_values = array_values(wpcsc_csv_delivery_zone_detail_values($containerID, true));
            $shipments_per_dz_detail_headers = array_merge(wpcsc_csv_shipments_per_zone_additional_headers(), array_values(wpcsc_csv_shipments_per_zone_meta_details()));
			
            # append all subrows to main array
            $rows['site_info'] = $site_info;
            $rows['dm_label'] = $dm_label;
            $rows['spacer_1'] = array();
            $rows['spacer_2'] = array();
            $rows['container_detail_headers'] = $container_detail_headers;
            $rows['container_detail_values'] = $container_detail_values;
            $rows['spacer_3'] = array();
            $rows['spacer_4'] = array();
            $rows['delivery_zone_detail_headers'] = $delivery_zone_detail_headers;
            if(!empty($delivery_zone_detail_values)){
                $counter1 = 0;
                foreach($delivery_zone_detail_values as $key1 => $val1){
                    $counter1++;
                    unset($val1['shipments']);
                    if(isset($val1['total_cbm']) && $val1['total_cbm']) {
                        $val1['total_cbm'] .= ' '.wpc_shipment_container_wpc_mp_weight_unit();
                    }
					$rows['delivery_zone_detail_value'.$counter1] = $val1;
                }
            }
            $rows['spacer_5'] = array();
            $rows['spacer_6'] = array();
            if(!empty($delivery_zone_detail_values)){
                $counter2 = 0;
                $spacer_count = apply_filters('wpcsc_delivery_zone_detail_values_spacer', 6);
                $spdz_counter = 0;
                foreach($delivery_zone_detail_values as $key2 => $val2){
                    $counter2++;
                    $spacer_count++;
                    $shipments_per_dz = $val2['shipments'] ?: array();
                    $rows['shipments_per_zone'.$counter2] = array($val2['actual_zone']);
                    $rows['shipments_per_zone_headers'.$counter2] = $shipments_per_dz_detail_headers;
                    if(!empty($shipments_per_dz)){
                        $ship_count = 0;
                        foreach($shipments_per_dz as $ship_id){
                            $spdz_counter++;
                            $ship_count++;
                            $track_num = get_the_title($ship_id);
                            $meta_data = wpcsc_formatted_shipment_meta_data($ship_id);
                            $additional_meta_data = array($ship_count, $track_num, 1);
                            $merged_meta_data = array_merge($additional_meta_data, $meta_data);
                            $rows['shipments_per_zone_values'.$spdz_counter] = $merged_meta_data;
                        }
                    }
                    $rows['spacer_'.$spacer_count] = array();
                }
            }
            $_rows = apply_filters('wpcsc_csv_final_row', $rows, $delivery_zone_detail_headers, $delivery_zone_detail_values);

            # generate csv
            wpcsccsv_generator(array_values($_rows), $filename);
        }
    }
}
add_action( 'init', 'wpcsc_csv_maker', 25);
# =================================================== balikbayan integration hooks ======================================================= #

# add a new csv header called Total CBM after Total Qty if balikbayan addon exists
function wpcsb_wpcsc_csv_container_detail_headers_callback($headers){
    $headers['cbm_label'] = __('Total CBM', 'wpcargo-container');
    return $headers;
}

# add a new csv value for Total CBM if balikbayan addon exists
function wpcsb_wpcsc_csv_container_detail_values_callback($keys, $shipment_id, $assigned_shipments){
    $total_cbm = 0;
    if(!empty($assigned_shipments) && is_array($assigned_shipments)){
        foreach($assigned_shipments as $shipment_id){
            $shipment_type = get_post_meta($shipment_id, '__shipment_type', true);
            if($shipment_type != 'shipment-box'){ continue; } # skip non balikbayan shipments
            $shipment_cbm = get_post_meta($shipment_id, 'wpcsb_total_charge', true);
            if($shipment_cbm){
                $total_cbm += (float)$shipment_cbm;
            }
        }
    }
    $keys['cmb_val'] = $total_cbm;
    return $keys;
}

# add a new csv headers for box types after Total Boxes if balikbayan addon exists
function wpcsb_wpcsc_csv_delivery_zone_detail_headers_callback($headers){
    $arr_key_translate = array(' ' => '-');
    $_box_types = get_option('wpcsb_box_type') ?: '';
    $box_types = array();
    $trimmed_box_types = false;
    if(!empty($_box_types)){
        $box_types = explode(',', $_box_types);
    }
    if(!empty($box_types)){
        $trimmed_box_types = array_map(function($type){
            return sanitize_text_field($type);
        }, $box_types);
    }
    if($trimmed_box_types && !empty($trimmed_box_types)){
        foreach($trimmed_box_types as $tbt){
            $key = strtolower(strtr($tbt, $arr_key_translate).'_label');
            $val = ucwords($tbt);
            $headers[$key] = $val;
        }
    }
    return $headers;
}

# add the corresponding box type count for each balikbayan boxes per zone if balikbayan addon exists
function wpcsb_wpcsc_csv_delivery_zone_detail_values_callback($_final_res, $values, $container_id, $assigned_shipments){
    $box_types     = array();
    $result        = array();
    $box_types_str = get_option('wpcsb_box_type') ?: '';
    $translate     = array(' ' => '-');

    # format box types from options
    if(!empty($box_types_str)){
        $box_types_arr = array_map(function($x)use($translate){
            return strtolower(strtr(sanitize_text_field($x), $translate));
        }, explode(',', $box_types_str));
        foreach($box_types_arr as $_bt){
            $key = strtolower(strtr(sanitize_text_field($_bt), $translate));
            $box_types[$key] = 0;
        }
    }
	
	//#Reformat boxes
	$_box_types = get_option('wpcsb_box_type') ?: '';
	$box_types = array();
	$trimmed_box_types = false;


	if(!empty($_box_types)){
		$box_types = explode(',', $_box_types);
	}
	if(!empty($box_types)){
		$trimmed_box_types = array_map(function($type){
			return sanitize_text_field( str_replace( ' ', '-', strtolower($type) ) );
		}, $box_types);
	}

	# count box types per zone
	if(!empty($assigned_shipments) && is_array($assigned_shipments)){
		foreach($assigned_shipments as $ship_id){
			$shipment_type = get_post_meta($ship_id, '__shipment_type', true) ?: '';

			//fetch delivery zone for BB
			$wpcsb_dz = get_post_meta($ship_id, 'wpcargo_reciever_zone', true) ? get_post_meta($ship_id, 'wpcargo_reciever_zone', true): '';
			$wpcsb_dz_slug = wpcsb_delivery_zone_to_slug($wpcsb_dz);

			//Fetch Rate id
			$wpcsb_rate_id = get_post_meta($ship_id, 'wpcsb_rate_id', true) ?: '';
			// $wpcsb_dz = get_post_meta($ship_id, $dz_meta_key, true) ?: '';

			//Fetch Box Type per rate in each box
			if( $wpcsb_rate_id && $wpcsb_dz_slug ){
				$wpcsc_get_box_type = wpcsc_get_box_type($wpcsb_rate_id);
				$wpcsb_box_type = $wpcsc_get_box_type->box_type;
				foreach( $trimmed_box_types as $type ){

					if( $type == $wpcsb_box_type ){
						if( isset( $result[$wpcsb_dz_slug][$wpcsb_box_type] ) ){
							$result[$wpcsb_dz_slug][$wpcsb_box_type] += 1;
						}else{
							$result[$wpcsb_dz_slug][$wpcsb_box_type] = 1;
						}
					}else{
						if( isset( $result[$wpcsb_dz_slug][$type] ) ){
							continue;
						}else{
							$result[$wpcsb_dz_slug][$type] = 0;
						}
					}
				}
			}
		}
	}
	
    # reconstruct final array
    if(!empty($result) && is_array($result)){
        foreach($result as $_k => $_v){
            $arr1 = $_final_res[$_k] ?? array();
            $_final_res[$_k] = array_merge($arr1, $_v);
        }
    }
	
    return $_final_res;
}

# append new headers if balikbayan addon exists
function wpcsb_wpcsc_csv_shipments_per_zone_meta_details_callback($meta_details){
    $additional_meta_details = array(
        'wpcargo_home_number' => __('Shipper Home No.', 'wpcargo-container'),
        'wpcargo_receiver_home_number' => __('Receiver Home No.', 'wpcargo-container'),
        'wpcargo_reciever_zone' => __('Delivery Zone', 'wpcargo-container'),
        'wpcargo_receiver_hub' => __('Delivery Hub', 'wpcargo-container'),
        'wpcsb_box_type' => __('Product', 'wpcargo-container'),
        'wpcsb_total_charge' => __('CBM / Kilo', 'wpcargo-container'),
        'ajustment_remarks' => __('Adjustment Remarks', 'wpcargo-container')
    );
    $merged_meta_details = array_merge($meta_details, $additional_meta_details);
    return $merged_meta_details;
}

# change shipment meta data
function wpcsb_wpcsc_formatted_shipment_meta_data_callback($res, $ship_id, $translate, $meta_keys){
    $res = array_map(function($mkey)use($ship_id, $translate){
        $shipment_type = get_post_meta($ship_id, '__shipment_type', true);
        if($shipment_type === 'shipment-box'){
            switch ($mkey) {
                case 'wpcargo_shipper_phone':
                    $mkey = 'wpcargo_phone_number';
                    break;
                case 'wpcargo_receiver_phone':
                    $mkey = 'wpcargo_receiver_phone_number';
                    break;
                default:
                    break;
            }
        }
		return strtr(get_post_meta($ship_id, $mkey, true), $translate);
	}, $meta_keys);
    return $res;
}

# main hook caller
function wpcsc_init_callback(){
    if(class_exists('WPCargo_ShipmentBox')){
        add_filter('wpcsc_csv_container_detail_headers', 'wpcsb_wpcsc_csv_container_detail_headers_callback', 11, 1);
        add_filter('wpcsc_csv_container_detail_values', 'wpcsb_wpcsc_csv_container_detail_values_callback', 11, 3);
        add_filter('wpcsc_csv_delivery_zone_detail_headers', 'wpcsb_wpcsc_csv_delivery_zone_detail_headers_callback', 99, 1);
        add_filter('wpcsc_csv_delivery_zone_detail_values', 'wpcsb_wpcsc_csv_delivery_zone_detail_values_callback', 11, 4);
        add_filter('wpcsc_csv_shipments_per_zone_meta_details', 'wpcsb_wpcsc_csv_shipments_per_zone_meta_details_callback', 11, 1);
        add_filter('wpcsc_csv_shipments_per_zone_meta_details', 'modified_fied_keys', 99, 1);
        add_filter('wpcsc_formatted_shipment_meta_data', 'wpcsb_wpcsc_formatted_shipment_meta_data_callback', 11, 4);
    }
}
add_action('plugins_loaded', 'wpcsc_init_callback', 15);

function modified_fied_keys( $structured_meta_keys ){
    $structured_meta_keys = array(
       'wpcargo_shipper_name' => 'Shipper Full Name',
       'wpcargo_shipper_phone' => 'Phone Number',
       'wpcargo_home_number' => 'Shipper Home No.',
       'wpcargo_receiver_name' => 'Receiver Full Name',
       'wpcargo_receiver_phone' => 'Phone Number',
       'wpcargo_receiver_home_number' => 'Receiver Home No.',
       'wpcargo_receiver_address' => 'Full Address',
       'wpcargo_reciever_zone' => 'Delivery Zone',
       'wpcargo_receiver_hub' => 'Delivery Hub',
       'wpcsb_box_type' => 'Product',
       'wpcsb_total_charge' => 'CBM / Kilo',
       'ajustment_remarks' => 'Adjustment Remarks',
    );

    return $structured_meta_keys;
}