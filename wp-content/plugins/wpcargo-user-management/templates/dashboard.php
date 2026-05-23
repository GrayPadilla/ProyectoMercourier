<!-- SweetAlert2 CSS y JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<div id="wpcumanage-table-wrapper" class="table-responsive">
    <h1 class="h4"><?php _e('Users', 'wpcargo-umanagement'); ?></h1>
    <?php do_action('wpcumanage_before_user_table', $wpcumanage_query); ?>
    <table id="wpcumanage-user-list" class="table table-hover table-sm">
        <thead>
            <tr>
                <?php do_action('wpcumanage_user_table_header'); ?>
                <td class="text-center wpcumanage-header-action"><?php _e('Action', 'wpcargo-umanagement'); ?></td>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($wpcumanage_query->get_results())): ?>
                <?php foreach ($wpcumanage_query->get_results() as $user): ?>
                    <?php
                    $access         = wpcumanage_user_access($user->ID);
                    $str_access     = is_array($access) ? implode(',', $access)  : '';
                    ?>
                    <tr id="user-<?php echo $user->ID; ?>" class="user-row">
                        <?php do_action('wpcumanage_user_table_data', $user); ?>
                        <td class="wpcumanage-action text-center">
                            <a href="<?php echo $page_url; ?>?umpage=edit&uid=<?php echo $user->ID; ?>" title="<?php _e('Update', 'wpcargo-umanagement'); ?>" class="mr-2"><i class="fa fa-edit text-info"></i></a>
                            <a href="#" title="<?php _e('Add Access', 'wpcargo-umanagement'); ?>" data-id="<?php echo $user->ID; ?>" data-access="<?php echo $str_access; ?>" class="wpcumange-update-access mr-2" data-toggle="modal" data-target="#wpcumanageAccessModal"><i class="fa fa-key text-success"></i></a>
                            <a href="#" title="Asignar Motorizado" data-id="<?php echo $user->ID; ?>" class="merc-assign-driver mr-2"><i class="fa fa-motorcycle text-warning"></i></a>
                            <a href="#" class="wpcumange-deactivate-account" data-id="<?php echo $user->ID; ?>" title="<?php _e('Deactivate', 'wpcargo-umanagement'); ?>"><i class="fa fa-user-times text-danger"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php do_action('wpcumanage_after_user_table', $wpcumanage_query); ?>
    <div class="row">
        <section id="wpcumanage-user-pagination" class="col-md-5 my-4">
            <?php

            echo paginate_links(array(
                'base' => get_pagenum_link(1) . '%_%',
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => 'Previous',
                'next_text' => 'Next',
                'type'     => 'list',
            ));
            ?>
        </section>
    </div>
    <?php do_action('wpcumanage_after_user_table_pagination', $wpcumanage_query); ?>
</div>


<script>
jQuery(document).ready(function($) {
    var currentUserId = null; // Variable global para almacenar el ID del usuario
    
    // Capturar el click en el botón de asignar motorizado
    $(document).on('click', '.merc-assign-driver', function(e) {
        e.preventDefault();
        currentUserId = $(this).data('id');
        
        console.log('🔓 [MODAL SWAl] ABIERTO - Asignando a cliente #' + currentUserId);
        console.log('📝 [DEBUG] Data ID capturado:', $(this).data('id'));
        
        // Cargar motorizado actual y mostrar SweetAlert
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'merc_get_client_driver',
                client_id: currentUserId,
                _nonce: '<?php echo wp_create_nonce('merc_driver_assign'); ?>'
            },
            success: function(response) {
                var currentDriver = '';
                if (response.success && response.data.driver_id) {
                    currentDriver = response.data.driver_id;
                    console.log('📋 [CARGAR] Motor actual para cliente #' + currentUserId + ': ' + currentDriver);
                }
                
                // Mostrar SweetAlert con el select
                showDriverAssignModal(currentDriver);
            },
            error: function(err) {
                console.error('❌ [CARGAR] Error cargando motorizado:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el motorizado actual'
                });
            }
        });
    });
    
    // Mostrar modalSweetAlert con selector
    function showDriverAssignModal(currentDriver) {
        var driversOptions = `<?php 
            $drivers = get_users(['role' => 'wpcargo_driver']);
            echo '<option value="">-- Sin asignar --</option>';
            foreach ($drivers as $driver) {
                echo '<option value="' . esc_attr($driver->ID) . '">' . esc_html($driver->display_name) . '</option>';
            }
        ?>`;
        
        var htmlContent = `
            <div style="text-align: left;">
                <div class="form-group">
                    <label for="merc_driver_select_swal" style="font-weight: bold; margin-bottom: 10px; display: block;">Selecciona el Motorizado de RECOJO (Opcional):</label>
                    <select id="merc_driver_select_swal" class="form-control" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        ${driversOptions}
                    </select>
                    <small style="display: block; margin-top: 8px; color: #666;">El motorizado seleccionado se asignará solo para RECOJO en nuevos envíos.</small>
                </div>
                
                <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 12px; margin-top: 15px; border-radius: 4px;">
                    <strong style="color: #1976D2; display: block; margin-bottom: 8px;">⚙️ Nota importante:</strong>
                    <ul style="margin: 0; padding-left: 20px; color: #555; font-size: 13px;">
                        <li>Solo afecta el motorizado de <strong>RECOJO</strong> (wpcargo_motorizo_recojo)</li>
                        <li>Se actualizarán automáticamente los envíos de este cliente <strong>creados HOY</strong></li>
                    </ul>
                </div>
            </div>
        `;
        
        Swal.fire({
            title: '🚗 Asignar Motorizado al Cliente',
            html: htmlContent,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '✅ Guardar',
            cancelButtonText: '❌ Cancelar',
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            didOpen: function() {
                // Establecer el valor actual en el select
                $('#merc_driver_select_swal').val(currentDriver);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var driverId = $('#merc_driver_select_swal').val();
                saveDriverAssignment(driverId);
            }
        });
    }
    
    // Guardar asignación de motorizado
    function saveDriverAssignment(driverId) {
        console.log('💾 [GUARDAR] Iniciando - Cliente #' + currentUserId + ' → Motorizado #' + (driverId || 'vacío'));
        
        if (!currentUserId || currentUserId === '' || currentUserId === '0') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Usuario ID no válido (' + currentUserId + ')'
            });
            console.error('❌ [GUARDAR] userId no válido: currentUserId=' + currentUserId);
            return;
        }
        
        // Mostrar cargando
        Swal.fire({
            title: 'Guardando...',
            html: 'Por favor espera mientras se actualiza la asignación',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'merc_assign_driver_to_client',
                client_id: currentUserId,
                driver_id: driverId,
                _nonce: '<?php echo wp_create_nonce('merc_driver_assign'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var message = response.data.message || 'Asignación realizada';
                    console.log('✅ [GUARDAR] Éxito - ' + message);
                    
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Éxito',
                        text: message,
                        confirmButtonColor: '#007bff'
                    });
                } else {
                    console.error('❌ [GUARDAR] Error backend:', response.data.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message || 'Error al guardar la asignación'
                    });
                }
            },
            error: function(err) {
                console.error('❌ [GUARDAR] Error AJAX:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Por favor intenta de nuevo.'
                });
            }
        });
    }
});
</script>
    <?php do_action('wpcumanage_after_user_table_pagination', $wpcumanage_query); ?>