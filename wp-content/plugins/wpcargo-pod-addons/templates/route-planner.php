<div id="wpcpod-route-planner" class="row mb-4">
    <!-- 🔥 Elemento oculto que almacena el nonce -->
    <input type="hidden" id="wpcpod-nonce-field" value="<?php echo wp_create_nonce('wpcpod_nonce'); ?>" />
    
    <?php do_action( 'wpcpod_before_route_planner' ); ?>
    <section id="route-planner-content" class="col-sm-12 bg-white py-3">
        <h2 class="my-4 pb-2 h5 text-center border-bottom"><?php esc_html_e('Planificador de Entrega de Productos', 'wpcargo-pod'); ?></h2>
        <div id="wpcpod-route-loader" class="my-4 alert alert-info text-center"><div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div></div>
        <div id="wpcpod-route-map" style="width:100%;" class="d-none"></div>       
    </section>
    <section id="directions-panel" class="col-lg-12 mt-4"></section>
    <?php do_action( 'wpcpod_after_route_planner' ); ?>
</div>

<!-- 🔥 MODAL DE FIRMA PARA ENTREGADO -->
<div class="modal fade" id="wpc_pod_signature-modal" tabindex="-1" role="dialog" aria-labelledby="signatureModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: max-content !important;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signatureModalLabel">📝 Firma de Cliente - Confirmación de Entrega</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- El contenido del formulario se cargará aquí mediante AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>