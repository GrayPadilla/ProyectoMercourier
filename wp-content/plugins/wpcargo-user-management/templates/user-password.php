<!-- Password -->
<div class="row">
    <div class="col-sm-12">
        <h2 class="h6 py-2 border-bottom font-weight-bold"><?php _e( 'Password', 'wpcargo-umanagement' ); ?></h2>
    </div>
    <section id="upass-generator-wrapper" class="col-md-6">    
        <div class="row">
            <button id="upass-generate" class="btn btn-success btn-md mb-2 col-md-3"><?php echo $pwd_label; ?></button>
            <div class="upass-wrapper form-inline w-100 <?php echo $is_update ? 'd-none' : '' ; ?> col-md-6">
                <div class="form-group">
                    <label class="sr-only" for="upass"><?php echo apply_filters( 'wpcfe_upassword', __( 'Password', 'wpcargo-umanagement' ) ); ?></label>
                    <input id="upass" class="form-control" type="text" size="20" value="<?php echo !$is_update ? wp_generate_password( 10 ) : '' ; ?>" name="pwd" <?php echo $is_update ? 'disabled' : 'required' ; ?>>
                </div>
                <?php if( $is_update ): ?>
                    <button id="upass-cancel" class="btn btn-light btn-md mb-2"><?php _e( 'Cancel', 'wpcargo-umanagement' ) ?></button>
                <?php endif; ?>
            </div>
        </div>
        <span id="upass-strength"></span>
    </section>
</div>
<div class="registration-message"></div>