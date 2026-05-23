<div class="col-md-12 text-center">
    <section class="card">
        <div class="card-body">    
            <div class="restricted-page text-center">        
                <i class="fa fa-exclamation-triangle text-danger" style="font-size: 120px;"></i>
                <p class="title h1 text-danger">
                    <?php esc_html_e("Error Found!", 'wpcargo-frontend-manager' ); ?>
                </p>
                <p>
                    <?php esc_html_e("Cannot find WPCargo Custom Field Add-ons plugin activated in your system.", 'wpcargo-frontend-manager' ); ?>
                </p>
                <p>
                    <?php 
                        printf( __( 'Please purchase', 'wpcargo-frontend-manager' ). ' <a href="%s" class="your-class">' . __( 'WPCargo Custom Field Add-ons', 'wpcargo-frontend-manager' ) . '</a>',  __( 'https://www.wpcargo.com/product/wpcargo-custom-field-add-ons/' ) );
                    ?>
                </p>
            </div>
        </div>
    </section>
</div>