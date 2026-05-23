<?php
// Save Users Group
function wpcumanage_save_user_group_callback(){
	global $wpdb;
	$save_type 				= sanitize_text_field( $_POST['save_type'] );
	$group_id 				= isset( $_POST['group_id'] ) ?sanitize_text_field( $_POST['group_id'] ) : 0;
	$label 					= sanitize_text_field( $_POST['label'] );
	$description 			= sanitize_text_field( $_POST['description'] );
	$wpcumanage_ug_users 	=  isset( $_POST['wpcumanage_ug_users'] ) ? maybe_serialize( $_POST['wpcumanage_ug_users'] ) : array();
	if( $save_type == 'update' ){
		$result = $wpdb->update(
			$wpdb->prefix.WPCU_MANAGEMENT_DB_USER_GROUP,
			array(
				'label' 	=> $label,
				'description' 	=> $description,
				'users' => $wpcumanage_ug_users,
			),
			array( 'user_group_id' => $group_id ),
			array(
				'%s',
				'%s',
				'%s',
			),
			array( '%d' )
		);
	}else{
		$result = $wpdb->insert(
			$wpdb->prefix.WPCU_MANAGEMENT_DB_USER_GROUP,
			array(
				'label' => $label,
				'description' => $description,
				'users' => $wpcumanage_ug_users,
			),
			array( '%s', '%s', '%s' )
		);
	}	
	
	if( $result ){
		$result = $wpdb->insert_id;
	}
	echo $result;
	wp_die();
}
add_action( 'wp_ajax_nopriv_wpcumanage_save_user_group', 'wpcumanage_save_user_group_callback' );
add_action( 'wp_ajax_wpcumanage_save_user_group', 'wpcumanage_save_user_group_callback' );

/***** WPCSR USER GROUP MODAL *****/
function wpcumanage_get_user_group_data_callback(){
    global $wpcargo;
	$id 		= $_REQUEST['id'];
	$user_group = wpcumanage_get_user_group_by_id( $id ) ?? '';
	$users = $user_group->users ? unserialize( $user_group->users ) : array();
	ob_start();
	?>
	<table class="form-table wpcumanage_add_table">
		<tr>
			<td><label><?php echo wpcumanage_group_name_label(); ?></label></td>
			<td><input type="text" name="wpcumanage_ug_label" id="wpcumanage_ug_label" class="wpcumanage_detail wpcumanage_ug_label" value="<?php echo $user_group->label; ?>" /></td>
		</tr>
		<tr>
			<td><label><?php echo wpcumanage_description_label(); ?></label></td>
			<td><textarea rows="6" name="wpcumanage_ug_desc" id="wpcumanage_ug_desc" class="wpcumanage_detail wpcumanage_ug_desc"><?php echo $user_group->description; ?></textarea></td>
		</tr>
		<tr>
			<td><label><?php echo wpcumanage_users_label(); ?></label></td>
			<td>
				<select name="wpcumanage_ug_users" id="wpcumanage_ug_users" class="wpcumanage_detail wpcumanage_ug_users" multiple>
					<?php 
					$args = array(
                                        'role__in'  => array('wpcargo_client'),
                                        'orderby'   => 'display_name',
                                        'order'     => 'ASC'
                                     );
 
                                    $users  = array();
                                     if( !empty( get_users( $args ) ) ){
                                         foreach ( get_users( $args ) as $user ) {
                                            $users[$user->ID] = $wpcargo->user_fullname( $user->ID );
                                         }
                                     }
    
									
									foreach( $users  as $user_id => $user_name ):?>
						<option value="<?php echo $user_id;?>" <?php echo ( in_array( $user_id, $users ) ) ? 'selected' : ''; ?>><?php echo $user_name; ?></option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<input type="hidden" id="wpcumanage_ug_id" value="<?php echo $id; ?>">
			</td>
			<td><input type="submit" id="wpcumanage_submit" class="wpcumanage_ug_btn button button-primary button-large wpcumanage_submit" value="<?php echo wpcumanage_update_group_label(); ?>" /></td>
		</tr>
	</table>
	<?php
	$output = ob_get_clean();
	echo $output;
	wp_die();
}
add_action( 'wp_ajax_wpcumanage_get_user_group_data', 'wpcumanage_get_user_group_data_callback' );
add_action( 'wp_ajax_nopriv_wpcumanage_get_user_group_data', 'wpcumanage_get_user_group_data_callback' );

/***** FRONTEND USER GROUP MODAL *****/
function wpcumanage_frontend_get_user_group_data_callback(){
	$group_id 	= $_REQUEST['group_id'];
	$user_group = wpcumanage_get_user_group_by_id( $group_id );
	$users = !empty( $user_group->users )? unserialize( $user_group->users ) : array();
	ob_start();
	?>
	<input type="hidden" id="wpcumanage_ug_id" value="<?php echo $group_id; ?>">
	<div class="col-md-12">
		<div class="form-group">
			<label for="wpcumanage_ug_label"><?php echo wpcumanage_group_name_label(); ?></label>
			<input id="wpcumanage_ug_label" type="text" class="form-control" name="wpcumanage_ug_label" value="<?php echo $user_group->label; ?>">
		</div>
	</div>
	<div class="col-md-12">
		<div class="form-group">
			<label for="wpcumanage_ug_label"><?php echo wpcumanage_description_label(); ?></label>
			<textarea rows="4" name="wpcumanage_ug_desc" id="wpcumanage_ug_desc" class="form-control wpcumanage_ug_desc"><?php echo $user_group->description; ?></textarea>
		</div>
	</div>
	<?php
	echo ob_get_clean();
	wp_die();
}
add_action( 'wp_ajax_frontend_get_user_group_data', 'wpcumanage_frontend_get_user_group_data_callback' );
add_action( 'wp_ajax_nopriv_frontend_get_user_group_data', 'wpcumanage_frontend_get_user_group_data_callback' );


/***** DELETE WPCSR DATA *****/ 
function wpcumanage_delete_user_group_callback(){
	global $wpdb;
	$id = $_REQUEST['id'];
	if( !empty( $id ) ){
		$wpdb->delete( $wpdb->prefix . WPCU_MANAGEMENT_DB_USER_GROUP, array( 'user_group_id' => $id ) );
	}
	die();
}
add_action( 'wp_ajax_wpcumanage_delete_user_group', 'wpcumanage_delete_user_group_callback' );
add_action( 'wp_ajax_nopriv_wpcumanage_delete_user_group', 'wpcumanage_delete_user_group_callback' );

/***** BULK WPCSR DATA *****/
function wpcumanage_ug_bulk_delete_callback(){
	global $wpdb;
	$ids 		= $_REQUEST['id'];
	$table_name = $wpdb->prefix .WPCU_MANAGEMENT_DB_USER_GROUP;
	$id_label = 'user_group_id';
	foreach ($ids as $array => $id) {
		$wpdb->delete( $table_name, array( $id_label => $id ) );
	}
    wp_die();
}
add_action( 'wp_ajax_wpcumanage_ug_bulk_delete', 'wpcumanage_ug_bulk_delete_callback' );
add_action( 'wp_ajax_nopriv_wpcumanage_ug_bulk_delete', 'wpcumanage_ug_bulk_delete_callback' );

// Bloquear usuario manualmente (AJAX)
function wpcumanage_block_user_callback() {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (!$user_id) {
        wp_send_json(array('status' => 'error', 'message' => __('ID de usuario inválido', 'wpcargo-umanagement')));
        wp_die();
    }
    // Establecer meta de bloqueo manual
    update_user_meta($user_id, 'merc_bloqueado_manual', '1');
    
    // Deshabilitar los 3 tipos de envíos
    update_user_meta($user_id, 'merc_tipo_normal_bloqueado', '1');
    update_user_meta($user_id, 'merc_tipo_express_bloqueado', '1');
    update_user_meta($user_id, 'merc_tipo_full_fitment_bloqueado', '1');
    
    // Limpiar meta de desbloqueo manual si existe
    delete_user_meta($user_id, 'merc_desbloqueado_manualmente_fecha');
    delete_user_meta($user_id, 'merc_desbloqueo_manual_envios_permitidos');
    error_log("🔒 BLOQUEO MANUAL: Usuario #{$user_id} bloqueado manualmente por admin - Todos los tipos de envío deshabilitados");
    wp_send_json(array('status' => 'success', 'message' => __('Usuario bloqueado manualmente - Todos los tipos de envío deshabilitados', 'wpcargo-umanagement')));
    wp_die();
}
add_action('wp_ajax_wpcumanage_block_user', 'wpcumanage_block_user_callback');

// Get Branch Options
function wpcumanage_branch_options_callback(){
    global $wpcargo;
    $branch_id  = $_POST['branchID'];
    $branch     = wpcdm_get_branch( $branch_id );
    $manager_opt       = array();
    $agent_opt         = array();
    $employee_opt      = array();
    $driver_opt        = array();
    $client_opt        = array();
    if( !$branch ){
        wp_send_json( array(
            'status' => 'success',
            'results' => null,
            'message' => __('Branch not found', 'wpcargo-umanagement'),
            'data' => array(
                'manager' => $manager_opt,
                'client' => $client_opt,
                'agent' => $agent_opt,
                'employee' => $employee_opt,
                'driver' => $driver_opt
            )
        ) );
        wp_die();
    }

    $branch_manager     = maybe_unserialize( $branch['branch_manager'] );
    $branch_agent       = maybe_unserialize( $branch['branch_agent'] );
    $branch_employee    = maybe_unserialize( $branch['branch_employee'] );
    $branch_driver      = maybe_unserialize( $branch['branch_driver'] );
    $branch_client      = maybe_unserialize( $branch['branch_client'] );

    // Get assigment
    $manager            = is_array( $branch_manager) && !empty( $branch_manager ) ? $branch_manager : null;
    $agent              = is_array( $branch_agent) && !empty( $branch_agent ) ? $branch_agent : null;
    $employee           = is_array( $branch_employee) && !empty( $branch_employee ) ? $branch_employee : null;
    $driver             = is_array( $branch_driver) && !empty( $branch_driver ) ? $branch_driver : null;
    $client             = is_array( $branch_client) && !empty( $branch_client ) ? $branch_client : null;

    if( $manager ){
        foreach ($manager as $mid ) {
            $manager_opt[$mid] = $wpcargo->user_fullname( $mid );
        }
    }
    
    if( $agent ){
        foreach ($agent as $aid ) {
            $agent_opt[$aid] = $wpcargo->user_fullname( $aid );
        }
    }
    
    if( $employee ){
        foreach ($employee as $eid ) {
            $employee_opt[$eid] = $wpcargo->user_fullname( $eid );
        }
    }
    
    if( $driver ){
        foreach ($driver as $did ) {
            $driver_opt[$did] = $wpcargo->user_fullname( $did );
        }
    }
    
    if( $client ){
        foreach ($client as $cid ) {
            $client_opt[$cid] = $wpcargo->user_fullname( $cid );
        }
    }
    wp_send_json( array(
        'status' => 'success',
        'results' => 1,
        'message' => __('Branch options found', 'wpcargo-umanagement'),
        'data' => array(
            'manager' => array_filter( $manager_opt ),
            'client' => array_filter( $client_opt ),
            'agent' => array_filter( $agent_opt ),
            'employee' => array_filter( $employee_opt ),
            'driver' => array_filter( $driver_opt )
        )
    ) );

    wp_die();
}
add_action( 'wp_ajax_branch_options', 'wpcumanage_branch_options_callback' );

// Desbloquear usuario manualmente
function wpcumanage_unblock_user_callback(){
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $envios_permitidos = isset($_POST['envios_permitidos']) ? intval($_POST['envios_permitidos']) : 1;
    
    if (!$user_id) {
        wp_send_json(array(
            'status' => 'error',
            'message' => __('ID de usuario inválido', 'wpcargo-umanagement')
        ));
        wp_die();
    }
    
    // Validar que la cantidad sea al menos 1
    if ($envios_permitidos < 1) {
        $envios_permitidos = 1;
    }
    
    // Eliminar bloqueo manual
    delete_user_meta($user_id, 'merc_bloqueado_manual');
    
    // Habilitar los 3 tipos de envíos
    delete_user_meta($user_id, 'merc_tipo_normal_bloqueado');
    delete_user_meta($user_id, 'merc_tipo_express_bloqueado');
    delete_user_meta($user_id, 'merc_tipo_full_fitment_bloqueado');
    
    // Guardar un meta para indicar que el usuario fue desbloqueado manualmente
    $today = current_time('Y-m-d');
    update_user_meta($user_id, 'merc_desbloqueado_manualmente_fecha', $today);
    update_user_meta($user_id, 'merc_desbloqueo_manual_envios_permitidos', $envios_permitidos);
    
    $mensaje_log = $envios_permitidos === 1 
        ? "🔓 DESBLOQUEO MANUAL: Usuario #{$user_id} desbloqueado. Todos los tipos de envío habilitados."
        : "🔓 DESBLOQUEO MANUAL: Usuario #{$user_id} desbloqueado. Todos los tipos de envío habilitados.";
    error_log($mensaje_log);
    
    $mensaje_usuario = __('Usuario desbloqueado. Todos los tipos de envío habilitados.', 'wpcargo-umanagement');
    
    wp_send_json(array(
        'status' => 'success',
        'message' => $mensaje_usuario
    ));
    
    wp_die();
}
add_action( 'wp_ajax_wpcumanage_unblock_user', 'wpcumanage_unblock_user_callback' );