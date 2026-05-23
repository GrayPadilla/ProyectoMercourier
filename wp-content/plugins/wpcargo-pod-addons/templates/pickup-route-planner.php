<div id="wpcpod-route-planner" class="row mb-4" data-nonce="<?php echo wp_create_nonce('wpcpod_nonce'); ?>">
    <?php do_action( 'wpcpod_pickup_before_route_planner' ); ?>
    <section id="route-planner-content" class="col-sm-12 bg-white py-3">
        <h2 class="my-4 pb-2 h5 text-center border-bottom"><?php esc_html_e('Planificador de Recojo de Productos', 'wpcargo-pod'); ?></h2>
        <div id="wpcpod-route-loader" class="my-4 alert alert-info text-center"><div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div></div>
        <div id="wpcpod-route-map" style="width:100%;" class="d-none"></div>       
    </section>
    <section id="directions-panel" class="col-lg-12 mt-4"></section>
    <?php do_action( 'wpcpod_pickup_after_route_planner' ); ?>
</div>