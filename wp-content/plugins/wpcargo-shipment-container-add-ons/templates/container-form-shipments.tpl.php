<div id="assigned-shipment" class="col-md-12 mb-4">
    <h1 class="mb-2 h3"><?php echo wpc_scpt_assinged_container_label(); ?> <div style="float: right; display: flex; gap: 10px;"><button class="btn btn-secondary btn-sm" onclick="window.history.back();" title="Volver a contenedores"><i class="fa fa-arrow-left"></i> Volver</button> <a id="showShipmentList" class="btn btn-info btn-sm" data-id="<?php echo $container_id; ?>" data-toggle="modal" data-target="#shipmentListModalPreview"><?php echo apply_filters('wpc_scpt_add_shipment_label', __( 'Add Shipment', 'wpcargo-shipment-container' ) ); ?></a></div></h1>
    <div class="container py-4 px-0">
        <section id="shipment-info-wrapper" class="w-100 m-0">
            <?php 
            // Contar por tipo: verificar cuál contenedor tiene el envío
            $shipments_recojo = array();
            $shipments_entrega = array();
            if (!empty($shipments)) {
                foreach ($shipments as $shipment_id) {
                    $container_recojo_value = get_post_meta($shipment_id, 'shipment_container_recojo', true);
                    $container_entrega_value = get_post_meta($shipment_id, 'shipment_container_entrega', true);
                    
                    // Verificar si THIS container (el actual) tiene este envío
                    // En recojo
                    if (!empty($container_recojo_value) && $container_recojo_value == $container_id) {
                        $shipments_recojo[] = $shipment_id;
                    } 
                    // En entrega
                    elseif (!empty($container_entrega_value) && $container_entrega_value == $container_id) {
                        $shipments_entrega[] = $shipment_id;
                    }
                }
            }
            // Total es la suma de ambos tipos (validado)
            $shipment_count = count($shipments_recojo) + count($shipments_entrega);
            ?>
            <i class="fa fa-list"></i> 
            <span class="shipment-count"><?php echo $shipment_count; ?></span> <?php echo wpc_scpt_shipments_label(); ?>
            <span style="margin-left: 15px; color: #666; font-size: 0.9em;">
                (<i class="fa fa-box" style="color: #e8363c;"></i> <span id="count-recojo"><?php echo count($shipments_recojo); ?></span> | 
                <i class="fa fa-truck" style="color: #28a745;"></i> <span id="count-entrega"><?php echo count($shipments_entrega); ?></span>)
            </span>
            <a href="#" id="wpcsc-toggle" class="text-info" data-stat="hide" ><?php _e( 'Hide', 'wpcargo-shipment-container' ); ?></a>
        </section>
        <section id="shipment-list-wrapper" class="row w-100 m-0">
            <?php do_action('wpc_admin_before_assigned_shipments'); ?>
            
            <!-- Botones de Control -->
            <div style="margin-bottom:15px; display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if (!empty($shipments_recojo)): ?>
                    <button type="button" id="select-all-users-btn" class="btn btn-sm" style="background:#e8363c; border:none; font-weight:600; color: white; padding: 8px 16px; border-radius: 4px; transition: all 0.3s;">
                        <i class="fa fa-check-square-o"></i> Seleccionar todos USUARIOS (Recojo)
                    </button>
                    
                    <button type="button" id="assign-motorizado-btn" class="btn btn-sm" style="background:#007bff; border:none; font-weight:600; color: white; padding: 8px 16px; border-radius: 4px; display: none;" data-toggle="modal" data-target="#modalAsignMotorizado">
                        <i class="fa fa-truck"></i> Asignar Motorizado
                    </button>
                <?php endif; ?>
                
                <?php if (!empty($shipments_entrega)): ?>
                    <button type="button" id="select-all-shipments-btn" class="btn btn-sm" style="background:#28a745; border:none; font-weight:600; color: white; padding: 8px 16px; border-radius: 4px; transition: all 0.3s;">
                        <i class="fa fa-check-square-o"></i> Seleccionar todos ENVÍOS (Entrega)
                    </button>
                    
                    <button type="button" id="assign-motorizado-entrega-btn" class="btn btn-sm" style="background:#28a745; border:none; font-weight:600; color: white; padding: 8px 16px; border-radius: 4px; display: none;" data-toggle="modal" data-target="#modalAsignMotorizadoEntrega">
                        <i class="fa fa-truck"></i> Asignar Motorizado Entrega
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- MODAL: Asignar Motorizado -->
            <div id="modalAsignMotorizado" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #e8363c 0%, #b91d23 100%); color: white;">
                            <h5 class="modal-title">
                                <i class="fa fa-truck"></i> Asignar Motorizado a Envíos
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" style="color: white;">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div style="margin-bottom: 15px;">
                                <strong>Envíos seleccionados: <span id="modal-selected-count">0</span></strong>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="modal-motorizado-select" style="font-weight: 600; margin-bottom: 10px; display: block;">
                                    <i class="fa fa-user" style="margin-right: 5px; color: #e8363c;"></i>
                                    Seleccionar Motorizado:
                                </label>
                                <select id="modal-motorizado-select" class="form-control">
                                    <option value="">-- Seleccione un motorizado --</option>
                                    <?php
                                    $drivers = get_users(array('role' => 'wpcargo_driver'));
                                    foreach ($drivers as $driver) {
                                        echo '<option value="' . esc_attr($driver->ID) . '">' . esc_html($driver->display_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="modal-confirm-btn" style="background: #e8363c; border-color: #e8363c;">
                                <i class="fa fa-check"></i> Asignar Motorizado
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MODAL: Asignar Motorizado ENTREGA -->
            <div id="modalAsignMotorizadoEntrega" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white;">
                            <h5 class="modal-title">
                                <i class="fa fa-truck"></i> Asignar Motorizado Entrega
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" style="color: white;">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div style="margin-bottom: 15px;">
                                <strong>Envíos seleccionados: <span id="modal-selected-count-entrega">0</span></strong>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label for="modal-motorizado-select-entrega" style="font-weight: 600; margin-bottom: 10px; display: block;">
                                    <i class="fa fa-user" style="margin-right: 5px; color: #28a745;"></i>
                                    Seleccionar Motorizado:
                                </label>
                                <select id="modal-motorizado-select-entrega" class="form-control">
                                    <option value="">-- Seleccione un motorizado --</option>
                                    <?php
                                    $drivers = get_users(array('role' => 'wpcargo_driver'));
                                    foreach ($drivers as $driver) {
                                        echo '<option value="' . esc_attr($driver->ID) . '">' . esc_html($driver->display_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="modal-confirm-btn-entrega" style="background: #28a745; border-color: #28a745;">
                                <i class="fa fa-check"></i> Asignar Motorizado
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Separar envíos en recojo y entrega -->
            <?php
            // Ya separamos en el header, reutilizamos las variables
            ?>
            
            <!-- Layout para dos tablas lado a lado -->
            <div style="display: flex; gap: 20px; flex-wrap: wrap; width: 100%;">
                
                <!-- TABLA 1: ENVÍOS PARA RECOGER -->
                <div style="flex: 1; min-width: 450px;" class="shipment-table-container">
                    <div style="background: #f8f9fa; padding: 12px 15px; border-radius: 4px; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; border: 1px solid #dee2e6;" class="shipment-table-header" data-toggle-table="recojo">
                        <h4 style="color: #e8363c; margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-box"></i> ENVÍOS PARA RECOGER (<?php echo count($shipments_recojo); ?>)
                        </h4>
                        <button type="button" class="btn btn-sm collapse-toggle" style="padding: 4px 12px;">
                            <i class="fa fa-chevron-up"></i>
                        </button>
                    </div>
                    <table id="shipment-list-recojo" class="table table-hover table-sm shipment-collapse-table" data-type="recojo" style="border-radius: 0; border-top: none; margin-bottom: 15px;">
                        <thead>
                            <tr>
                                <th style="white-space: nowrap;width: 1%;">
                                    <input type="checkbox" class="form-check-input select-all-type" data-type="recojo">
                                </th>
                                <?php do_action( 'wpcsc_before_header_shipment_content_section' ); ?>
                                <th class="text-center"><?php echo wpc_scpt_shipments_label(); ?></th>
                                <th><?php esc_html_e('Distrito', 'wpcargo-shipment-container'); ?></th>
                                <th class="text-center"><?php esc_html_e('Print', 'wpcargo-shipment-container'); ?></th>
                                <th class="text-center"><?php esc_html_e('Update Status', 'wpcargo-shipment-container'); ?></th>
                                <th class="text-center"><?php esc_html_e('Remove', 'wpcargo-shipment-container'); ?></th>
                                <?php do_action( 'wpcsc_after_header_shipment_content_section'); ?>
                            </tr>
                        </thead>
                        <tbody id="container-shipment-list-recojo">
                            <?php if( !empty( $shipments_recojo )): ?>
                                <?php
                                // Agrupar shipments por cliente (registered_shipper)
                                $grouped_recojo = array();
                                foreach( $shipments_recojo as $shipment_id ) {
                                    $client_id = get_post_meta($shipment_id, 'registered_shipper', true);
                                    error_log('DEBUG RECOJO - Shipment #' . $shipment_id . ': registered_shipper = ' . var_export($client_id, true));
                                    
                                    if ( empty( $client_id ) ) {
                                        $client_id = 'sin_cliente';
                                    }
                                    if ( !isset( $grouped_recojo[$client_id] ) ) {
                                        $grouped_recojo[$client_id] = array();
                                    }
                                    $grouped_recojo[$client_id][] = $shipment_id;
                                }
                                
                                error_log('DEBUG RECOJO - Agrupación: ' . var_export($grouped_recojo, true));
                                
                                foreach( $grouped_recojo as $client_id => $shipment_ids ):
                                    if ($client_id !== 'sin_cliente') {
                                        // Intentar obtener como usuario primero
                                        $user = get_user_by('ID', $client_id);
                                        $client_name = $user ? $user->display_name : get_the_title($client_id);
                                        error_log('DEBUG RECOJO - Client ID: ' . $client_id . ' | Es usuario: ' . ($user ? 'SÍ' : 'NO') . ' | Nombre: ' . $client_name);
                                    } else {
                                        $client_name = 'Sin Cliente';
                                        error_log('DEBUG RECOJO - Sin cliente para este grupo');
                                    }
                                    $client_name = $client_name ?: $client_id;
                                    $shipment_count_per_user = count($shipment_ids);
                                    $group_id = 'recojo_' . sanitize_html_class($client_id);
                                ?>
                                    <!-- Row: Encabezado del Usuario (expandible) -->
                                    <tr class="user-group-header" data-user-group="<?php echo esc_attr($group_id); ?>" style="background: #f0f7ff; border-top: 2px solid #2980b9; cursor: pointer;">
                                        <td class="form-check">
                                            <input
                                                type="checkbox"
                                                class="form-check-input user-checkbox"
                                                id="user-<?php echo esc_attr($group_id); ?>"
                                                data-group="<?php echo esc_attr($group_id); ?>"
                                            >
                                            <label class="form-check-label" for="user-<?php echo esc_attr($group_id); ?>">
                                            </label>
                                        </td>
                                        <td colspan="6" style="padding: 12px 16px; vertical-align: middle;">
                                            <strong style="color: #2980b9; font-size: 0.95em;">
                                                <i class="fa fa-chevron-right user-toggle-icon" style="display: inline-block; width: 16px; transition: transform 0.3s;"></i>
                                                <i class="fa fa-user-circle" style="margin-right: 8px;"></i><?php echo esc_html($client_name); ?>
                                                <span style="background: #2980b9; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.85em; margin-left: 10px;">
                                                    <?php echo $shipment_count_per_user; ?> envío(s)
                                                </span>
                                            </strong>
                                        </td>
                                    </tr>
                                    
                                    <?php foreach( $shipment_ids as $shipment_id ): ?>
                                        <?php
                                            $shipment_title = get_the_title($shipment_id);
                                            $status = get_post_meta( $shipment_id, 'wpcargo_status', true );
                                            $wpcfe_print_options = wpcfe_print_options();
                                            
                                            // Mostrar distrito de recojo
                                            $distrito = get_post_meta($shipment_id, 'wpcargo_distrito_recojo', true);
                                            if (empty($distrito)) {
                                                $distrito = get_post_meta($shipment_id, 'wpcargo_origin_field', true);
                                            }
                                            if (empty($distrito)) {
                                                $distrito = 'N/A';
                                            }
                                            
                                            // Verificar si tiene motorizado de recojo asignado
                                            $motorizado_recojo = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
                                            $tiene_motorizado = !empty($motorizado_recojo) && $motorizado_recojo !== '0';
                                            $row_class = $tiene_motorizado ? 'shipment-assigned' : 'shipment-unassigned';
                                        ?>
                                        <tr id="shipment-<?php echo $shipment_id; ?>" data-shipment="<?php echo $shipment_id; ?>" class="selected-shipment p-1 <?php echo $row_class; ?> user-shipment" data-group="<?php echo esc_attr($group_id); ?>" style="display: none; padding-left: 30px;">
                                            <td colspan="1"></td>
                                            <td class="text-center">
                                                <?php do_action( 'wpcsc_before_shipment_content_section', $shipment_id ); ?>
                                                <h3 class="shipment-title h6"><a style="text-decoration: none;" href="<?php echo get_the_permalink( wpcfe_admin_page() ).'?wpcfe=track&num='.$shipment_title; ?>" target="_blank"><?php echo $shipment_title; ?></a></h3>
                                                <?php do_action( 'wpcsc_after_shipment_content_section', $shipment_id ); ?>
                                            </td>
                                            <td><?php echo esc_html($distrito); ?></td>
                                            <td class="text-center print-shipment">
                                                <div class="dropdown">
                                                    <button class="btn btn-default btn-sm dropdown-toggle m-0 py-1 px-2" type="button" id="dropdownPrint-<?php echo $shipment_id; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-list"></i></button>
                                                    <div class="dropdown-menu dropdown-primary">
                                                        <?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
                                                            <a class="dropdown-item print-<?php echo $print_key; ?> py-1" data-id="<?php echo $shipment_id; ?>" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-success btn-sm dropdown-toggle m-0 py-1 px-2" type="button" id="update-<?php echo $shipment_id; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-edit"></i></button>
                                                    <div class="dropdown-menu dropdown-primary">
                                                        <?php foreach( $wpcargo->status as $status ): ?>
                                                            <a class="update-shipment dropdown-item py-1" data-id="<?php echo $shipment_id; ?>" data-value="<?php echo $status; ?>" href="#"><?php echo $status; ?></a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm m-0 py-1 px-2 remove-shipment" data-id="<?php echo $shipment_id; ?>" title="<?php esc_html_e('Remove', 'wpcargo-shipment-container'); ?>"><i class="fa fa-trash"></i></button>
                                            </td>
                                            <?php do_action( 'wpcsc_after_shipment_content_section', $shipment_id ); ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay envíos para recoger</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- TABLA 2: ENVÍOS PARA ENTREGAR -->
                <div style="flex: 1; min-width: 450px;" class="shipment-table-container">
                    <div style="background: #f8f9fa; padding: 12px 15px; border-radius: 4px; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; border: 1px solid #dee2e6;" class="shipment-table-header" data-toggle-table="entrega">
                        <h4 style="color: #28a745; margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-truck"></i> ENVÍOS PARA ENTREGAR (<?php echo count($shipments_entrega); ?>)
                        </h4>
                        <button type="button" class="btn btn-sm collapse-toggle" style="padding: 4px 12px;">
                            <i class="fa fa-chevron-up"></i>
                        </button>
                    </div>
                    <table id="shipment-list-entrega" class="table table-hover table-sm shipment-collapse-table" data-type="entrega" style="border-radius: 0; border-top: none; margin-bottom: 15px;">
                        <thead>
                            <tr>
                                <th style="white-space: nowrap;width: 1%;">
                                    <input type="checkbox" class="form-check-input select-all-type" data-type="entrega">
                                </th>
                                <?php do_action( 'wpcsc_before_header_shipment_content_section' ); ?>
                                <th class="text-center"><?php echo wpc_scpt_shipments_label(); ?></th>
                                <th><?php esc_html_e('Distrito', 'wpcargo-shipment-container'); ?></th>
                                <th class="text-center"><?php esc_html_e('Print', 'wpcargo-shipment-container'); ?></th>
                                <th class="text-center"><?php esc_html_e('Update Status', 'wpcargo-shipment-container'); ?></th>
                                <th class="text-center"><?php esc_html_e('Remove', 'wpcargo-shipment-container'); ?></th>
                                <?php do_action( 'wpcsc_after_header_shipment_content_section'); ?>
                            </tr>
                        </thead>
                        <tbody id="container-shipment-list-entrega">
                            <?php if( !empty( $shipments_entrega )): ?>
                                <?php foreach( $shipments_entrega as $shipment_id ): ?>
                                    <?php
                                        $shipment_title = get_the_title($shipment_id);
                                        $status = get_post_meta( $shipment_id, 'wpcargo_status', true );
                                        $wpcfe_print_options = wpcfe_print_options();
                                        
                                        // Mostrar distrito de entrega
                                        $distrito = get_post_meta($shipment_id, 'wpcargo_distrito_destino', true);
                                        if (empty($distrito)) {
                                            $distrito = get_post_meta($shipment_id, 'wpcargo_destination', true);
                                        }
                                        if (empty($distrito)) {
                                            $distrito = 'N/A';
                                        }
                                        
                                        // Verificar si tiene motorizado de entrega asignado
                                        $motorizado_entrega = get_post_meta($shipment_id, 'wpcargo_motorizo_entrega', true);
                                        $tiene_motorizado = !empty($motorizado_entrega) && $motorizado_entrega !== '0';
                                        $row_class = $tiene_motorizado ? 'shipment-assigned' : 'shipment-unassigned';
                                    ?>
                                    <tr id="shipment-<?php echo $shipment_id; ?>" data-shipment="<?php echo $shipment_id; ?>" class="selected-shipment p-1 <?php echo $row_class; ?>" data-courier="entrega">
                                        <td class="form-check">
                                            <input
                                                type="checkbox"
                                                class="form-check-input shipment-checkbox"
                                                id="shipment-<?php echo esc_attr($shipment_id); ?>"
                                                name="selected_shipments[]"
                                                value="<?php echo esc_attr($shipment_id); ?>"
                                            >
                                            <label
                                                class="form-check-label"
                                                for="shipment-<?php echo esc_attr($shipment_id); ?>">
                                            </label>
                                        </td>
                                        <td class="text-center">
                                            <?php do_action( 'wpcsc_before_shipment_content_section', $shipment_id ); ?>
                                            <h3 class="shipment-title h6"><a style="text-decoration: none;" href="<?php echo get_the_permalink( wpcfe_admin_page() ).'?wpcfe=track&num='.$shipment_title; ?>" target="_blank"><?php echo $shipment_title; ?></a></h3>
                                            <?php do_action( 'wpcsc_after_shipment_content_section', $shipment_id ); ?>
                                        </td>
                                        <td><?php echo esc_html($distrito); ?></td>
                                        <td class="text-center print-shipment">
                                            <div class="dropdown">
                                                <button class="btn btn-default btn-sm dropdown-toggle m-0 py-1 px-2" type="button" id="dropdownPrint-<?php echo $shipment_id; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-list"></i></button>
                                                <div class="dropdown-menu dropdown-primary">
                                                    <?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
                                                        <a class="dropdown-item print-<?php echo $print_key; ?> py-1" data-id="<?php echo $shipment_id; ?>" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-success btn-sm dropdown-toggle m-0 py-1 px-2" type="button" id="update-<?php echo $shipment_id; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-edit"></i></button>
                                                <div class="dropdown-menu dropdown-primary">
                                                    <?php foreach( $wpcargo->status as $status ): ?>
                                                        <a class="update-shipment dropdown-item py-1" data-id="<?php echo $shipment_id; ?>" data-value="<?php echo $status; ?>" href="#"><?php echo $status; ?></a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm m-0 py-1 px-2 remove-shipment" data-id="<?php echo $shipment_id; ?>" title="<?php esc_html_e('Remove', 'wpcargo-shipment-container'); ?>"><i class="fa fa-trash"></i></button>
                                        </td>
                                        <?php do_action( 'wpcsc_after_shipment_content_section', $shipment_id ); ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay envíos para entregar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
            </div>
            
            <?php do_action( 'wpc_admin_after_assigned_shipments', $container_id ); ?>
        </section>  
        <input type="hidden" name="wpcc_sorted_shipments" id="wpcc_sorted_shipments" value="<?php echo is_array($shipments) ? implode(",", $shipments) : ''; ?>" /> 
    </div>
    <?php do_action( 'wpc_shipment_container_after_assigned_shipments_info', $container_id ); ?>
</div>

<!-- ESTILOS PARA SCROLL: Hacer tablas scrolleables -->
<style>
    /* Hacer scrolleable el contenedor de tablas shipment-list-wrapper */
    #shipment-list-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
    }
    
    /* Asegurar que las tablas dentro tengan ancho mínimo para scroll */
    #shipment-list-wrapper table {
        min-width: 100%;
        width: auto;
    }
    
    /* Contenedor flex también scrolleable */
    #shipment-list-wrapper > div[style*="display: flex"] {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Estilo de la barra de scroll */
    #shipment-list-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    
    #shipment-list-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    #shipment-list-wrapper::-webkit-scrollbar-thumb {
        background: #999;
        border-radius: 10px;
    }
    
    #shipment-list-wrapper::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Estilos para tablas colapsables */
    .shipment-table-container {
        transition: all 0.3s ease;
    }
    
    .shipment-table-header {
        transition: all 0.3s ease;
    }
    
    .shipment-table-header:hover {
        background-color: #e9ecef !important;
    }
    
    .shipment-collapse-table {
        transition: opacity 0.3s ease;
        max-height: 2000px;
        opacity: 1;
        visibility: visible;
        display: table;
        margin-bottom: 15px;
    }
    
    .shipment-collapse-table.collapsed {
        opacity: 0;
        visibility: hidden;
        display: none;
        margin-bottom: 0;
        border: none !important;
    }
    
    .collapse-toggle {
        transition: transform 0.3s ease;
        color: #6c757d;
        margin: 0 !important;
        padding: 4px 12px !important;
    }
    
    .collapse-toggle.collapsed i {
        transform: rotate(180deg);
    }
    
    .collapse-toggle:hover {
        color: #495057;
        background-color: #e9ecef !important;
    }
    
    /* Diferenciación de envíos asignados vs sin asignar */
    tr.shipment-assigned {
        background-color: #d4edda !important; /* Verde claro */
        border-left: 4px solid #28a745 !important;
    }
    
    tr.shipment-unassigned {
        background-color: #fff !important; /* Blanco/sin fondo */
        border-left: 4px solid #dc3545 !important;
    }
    
    tr.shipment-assigned:hover {
        background-color: #c3e6cb !important;
    }
    
    tr.shipment-unassigned:hover {
        background-color: #fff5f5 !important;
    }
    
    /* Estilos para grupos de usuarios */
    tr.user-group-header {
        font-weight: 600;
        cursor: pointer !important;
    }
    
    tr.user-group-header:hover {
        background-color: #e8f4f8 !important;
    }
    
    tr.user-group-header td {
        padding: 12px 16px !important;
        vertical-align: middle;
    }
    
    /* Icono toggle del usuario */
    .user-toggle-icon {
        display: inline-block !important;
        width: 16px !important;
        transition: transform 0.3s ease !important;
        margin-right: 8px !important;
    }
    
    tr.user-group-header .user-toggle-icon.fa-chevron-down {
        transform: rotate(0deg);
    }

</style>

<!-- JAVASCRIPT -->
<script>
jQuery(document).ready(function($) {
    console.log('✅ Script cargado');
    
    // Obtener container_id de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const containerId = urlParams.get('id') || '';
    console.log('📦 Container ID extraído de URL:', containerId);
    
    // Generar nonce una sola vez
    const nonce = '<?php echo wp_create_nonce('merc_assign_motorizado'); ?>';
    console.log('🔐 Nonce generado:', nonce);
    
    // ============================================
    // BOTÓN: Seleccionar todos los USUARIOS (Recojo)
    // ============================================
    $('#select-all-users-btn').on('click', function() {
        console.log('🖱️ Click en "Seleccionar todos los usuarios"');
        const userCheckboxes = $('#shipment-list-recojo .user-checkbox');
        const allChecked = userCheckboxes.length > 0 && userCheckboxes.filter(':not(:checked)').length === 0;
        
        userCheckboxes.prop('checked', !allChecked).trigger('change');
        
        if (allChecked) {
            $(this).html('<i class="fa fa-check-square-o"></i> Seleccionar todos USUARIOS (Recojo)');
        } else {
            $(this).html('<i class="fa fa-check-square-o"></i> Deseleccionar todos USUARIOS (Recojo)');
        }
    });
    
    // ============================================
    // BOTÓN: Seleccionar todos los ENVÍOS (Entrega)
    // ============================================
    $('#select-all-shipments-btn').on('click', function() {
        console.log('🖱️ Click en "Seleccionar todos los envíos"');
        const shipmentCheckboxes = $('#shipment-list-entrega .shipment-checkbox');
        const allChecked = shipmentCheckboxes.length > 0 && shipmentCheckboxes.filter(':not(:checked)').length === 0;
        
        shipmentCheckboxes.prop('checked', !allChecked).trigger('change');
        
        if (allChecked) {
            $(this).html('<i class="fa fa-check-square-o"></i> Seleccionar todos ENVÍOS (Entrega)');
        } else {
            $(this).html('<i class="fa fa-check-square-o"></i> Deseleccionar todos ENVÍOS (Entrega)');
        }
    });
    
    // ============================================
    // COLAPSO/EXPANSIÓN DE TABLAS
    // ============================================
    $('.shipment-table-header').on('click', function() {
        const tableType = $(this).attr('data-toggle-table');
        const table = $('#shipment-list-' + tableType);
        const toggleBtn = $(this).find('.collapse-toggle');
        
        table.toggleClass('collapsed');
        toggleBtn.toggleClass('collapsed');
        
        localStorage.setItem('shipment-table-' + tableType + '-collapsed', table.hasClass('collapsed'));
    });
    
    // Restaurar estados guardados
    $('.shipment-table-header').each(function() {
        const tableType = $(this).attr('data-toggle-table');
        const isCollapsed = localStorage.getItem('shipment-table-' + tableType + '-collapsed') === 'true';
        
        if (isCollapsed) {
            const table = $('#shipment-list-' + tableType);
            const toggleBtn = $(this).find('.collapse-toggle');
            table.addClass('collapsed');
            toggleBtn.addClass('collapsed');
        }
    });
    
    // ============================================
    // EXPANSIÓN DE GRUPOS DE USUARIOS
    // ============================================
    $('.user-group-header').on('click', function(e) {
        if ($(e.target).closest('.user-checkbox').length) {
            return;
        }
        
        const groupId = $(this).attr('data-user-group');
        const shipments = $(`.user-shipment[data-group="${groupId}"]`);
        const icon = $(this).find('.user-toggle-icon');
        
        const isHidden = shipments.eq(0).is(':hidden');
        shipments.toggle();
        
        if (isHidden) {
            icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
        }
    });
    
    // ============================================
    // CHECKBOX DE USUARIO
    // ============================================
    $(document).on('change', '.user-checkbox', function() {
        const groupId = $(this).attr('data-group');
        const isChecked = this.checked;
        
        $(`.user-shipment[data-group="${groupId}"] .shipment-checkbox`).prop('checked', isChecked);
        updateAssignButton();
    });
    
    // ============================================
    // ACTUALIZAR BOTONES DE ASIGNAR MOTORIZADO
    // ============================================
    function updateAssignButton() {
        const selectedUsers = $('#shipment-list-recojo .user-checkbox:checked').length;
        if (selectedUsers > 0) {
            $('#assign-motorizado-btn').show();
        } else {
            $('#assign-motorizado-btn').hide();
        }
    }
    
    function updateAssignButtonEntrega() {
        const selectedShipments = $('#shipment-list-entrega .shipment-checkbox:checked').length;
        if (selectedShipments > 0) {
            $('#assign-motorizado-entrega-btn').show();
        } else {
            $('#assign-motorizado-entrega-btn').hide();
        }
    }
    
    // Actualizar cuando cambian los checkboxes de entrega
    $(document).on('change', '#shipment-list-entrega .shipment-checkbox', function() {
        updateAssignButtonEntrega();
    });
    
    // ============================================
    // MODAL: Actualizar contador y manejar confirmación
    // ============================================
    $('#modalAsignMotorizado').on('show.bs.modal', function() {
        const selectedUsers = $('#shipment-list-recojo .user-checkbox:checked').length;
        $('#modal-selected-count').text(selectedUsers);
        console.log('📋 Modal RECOJO abierto. Usuarios seleccionados:', selectedUsers);
    });
    
    // MODAL ENTREGA: Actualizar contador
    $('#modalAsignMotorizadoEntrega').on('show.bs.modal', function() {
        const selectedShipments = $('#shipment-list-entrega .shipment-checkbox:checked').length;
        $('#modal-selected-count-entrega').text(selectedShipments);
        console.log('📋 Modal ENTREGA abierto. Envíos seleccionados:', selectedShipments);
    });
    
    // Botón de confirmación del modal
    $('#modal-confirm-btn').on('click', function(e) {
        e.preventDefault();
        
        console.log('👉 CLICK EN BOTÓN MODAL');
        
        const btn = $(this);
        const selectedUsers = $('#shipment-list-recojo .user-checkbox:checked');
        const driverId = $('#modal-motorizado-select').val();
        
        console.log('👤 Usuarios seleccionados:', selectedUsers.length);
        console.log('🚙 Driver ID:', driverId);
        
        if (selectedUsers.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor selecciona al menos un usuario',
                confirmButtonColor: '#e8363c'
            });
            return;
        }
        
        if (!driverId) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor selecciona un motorizado',
                confirmButtonColor: '#e8363c'
            });
            return;
        }
        
        const userIds = selectedUsers.map(function() {
            return $(this).attr('data-group').replace('recojo_', '');
        }).get();
        
        const driverName = $('#modal-motorizado-select').find('option:selected').text();
        
        Swal.fire({
            icon: 'question',
            title: 'Confirmar asignación',
            text: `¿Asignar motorizado ${driverName} a ${userIds.length} usuario(s)?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, asignar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e8363c',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Asignando...');
            
            const formData = new FormData();
            formData.append('action', 'merc_assign_motorizado_bulk');
            formData.append('driver_id', driverId);
            formData.append('container_id', containerId);
            formData.append('nonce', nonce);
            
            userIds.forEach((userId, i) => {
                formData.append('user_ids[' + i + ']', userId);
            });
            
            console.log('📤 Enviando AJAX...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('✅ Respuesta:', response);
                    
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.data.message,
                            confirmButtonColor: '#e8363c'
                        }).then(() => {
                            // Cerrar modal con jQuery
                            try {
                                $('#modalAsignMotorizado').modal('hide');
                            } catch(e) {
                                console.log('Modal close method not available, using DOM manipulation');
                                const modalElement = document.getElementById('modalAsignMotorizado');
                                modalElement.style.display = 'none';
                                modalElement.classList.remove('show');
                                document.body.classList.remove('modal-open');
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) backdrop.remove();
                            }
                            setTimeout(() => window.location.reload(), 500);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data?.message || 'Error desconocido',
                            confirmButtonColor: '#dc3545'
                        });
                        btn.prop('disabled', false).html('<i class="fa fa-check"></i> Asignar Motorizado');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error AJAX:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'Error: ' + error,
                        confirmButtonColor: '#dc3545'
                    });
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Asignar Motorizado');
                }
            });
        });
    });
    
    // ============================================
    // MODAL ENTREGA: Manejar confirmación de asignación
    // ============================================
    $('#modal-confirm-btn-entrega').on('click', function(e) {
        e.preventDefault();
        
        console.log('👉 CLICK EN BOTÓN MODAL ENTREGA');
        
        const btn = $(this);
        const selectedShipments = $('#shipment-list-entrega .shipment-checkbox:checked');
        const driverId = $('#modal-motorizado-select-entrega').val();
        
        console.log('📦 Envíos seleccionados:', selectedShipments.length);
        console.log('🚙 Driver ID:', driverId);
        
        if (selectedShipments.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor selecciona al menos un envío',
                confirmButtonColor: '#28a745'
            });
            return;
        }
        
        if (!driverId) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor selecciona un motorizado',
                confirmButtonColor: '#28a745'
            });
            return;
        }
        
        const shipmentIds = selectedShipments.map(function() {
            return $(this).val();
        }).get();
        
        const driverName = $('#modal-motorizado-select-entrega').find('option:selected').text();
        
        Swal.fire({
            icon: 'question',
            title: 'Confirmar asignación',
            text: `¿Asignar motorizado ${driverName} a ${shipmentIds.length} envío(s)?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, asignar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Asignando...');
            
            const formData = new FormData();
            formData.append('action', 'merc_assign_motorizado_entrega_bulk');
            formData.append('driver_id', driverId);
            formData.append('container_id', containerId);
            formData.append('nonce', nonce);
            
            shipmentIds.forEach((shipmentId, i) => {
                formData.append('shipment_ids[' + i + ']', shipmentId);
            });
            
            console.log('📤 Enviando AJAX para ENTREGA...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('✅ Respuesta:', response);
                    
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.data.message,
                            confirmButtonColor: '#28a745'
                        }).then(() => {
                            // Cerrar modal con jQuery
                            try {
                                $('#modalAsignMotorizadoEntrega').modal('hide');
                            } catch(e) {
                                console.log('Modal close method not available, using DOM manipulation');
                                const modalElement = document.getElementById('modalAsignMotorizadoEntrega');
                                modalElement.style.display = 'none';
                                modalElement.classList.remove('show');
                                document.body.classList.remove('modal-open');
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) backdrop.remove();
                            }
                            setTimeout(() => window.location.reload(), 500);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data?.message || 'Error desconocido',
                            confirmButtonColor: '#dc3545'
                        });
                        btn.prop('disabled', false).html('<i class="fa fa-check"></i> Asignar Motorizado');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error AJAX:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'Error: ' + error,
                        confirmButtonColor: '#dc3545'
                    });
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Asignar Motorizado');
                }
            });
        });
    });
});
</script>