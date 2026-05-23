<!-- Full Height Modal Left Info Demo-->
<div class="modal fade right" id="wpcfe-account" tabindex="-1" role="dialog"
    aria-labelledby="<?php esc_html_e( 'My Account', 'wpcargo-frontend-manager' ); ?>" aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog modal-full-height modal-right modal-notify modal-info" role="document">
        <!--Content-->
        <div class="modal-content">
            <!--Header-->
            <div class="modal-header primary-color-dark darken-2">
                <p class="heading lead"><i class="fa fa-user-circle text-white"></i> <?php esc_html_e( 'My Account', 'wpcargo-frontend-manager' ); ?></p>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e( 'Close', 'wpcargo-frontend-manager' ); ?>">
                    <span aria-hidden="true" class="white-text">&times;</span>
                </button>
            </div>
            <!--Body-->
            <div class="modal-body">
                <div id="profile-header" class="text-center">
                    <div id="wpcfe-avatar-wrapper">
                        <div id="user-avatar">
                            <a href="#" id="wpcfe-change-avatar"><i class="fa fa-camera text-primary"></i></a>
                            <div class="photo-container">
                                <?php wpcfe_user_avatar(); ?>
                            </div>
                        </div>
                        <div id="upload-avatar-wrapper" style="display:none;">
                            <a href="#" id="close-upload-avatar"><i class="fa fa-close text-danger"></i></a>
                            <div id="upload-avatar" ></div>
                            <div id="croppie-actions">
                                <input type="file" id="upload" class="btn actionUpload btn-primary btn-sm" value="<?php esc_html_e('Upload Avatar', 'wpcargo-frontend-manager' ); ?>" accept="image/*" />
                                <a class="button actionSave btn btn-success btn-sm"><?php esc_html_e('Save Avatar', 'wpcargo-frontend-manager' ); ?></a>
                            </div>
                        </div>
                    </div>
                    <p id="user_fullname" ><strong><?php echo $wpcargo->user_fullname( $user_info->ID ); ?></strong></p>
                    <p id="user_email" ><strong><?php esc_html_e('Email:', 'wpcargo-frontend-manager' ); ?> <?php echo $user_info->user_email; ?></strong></p>
                </div>
                <div class="accordion" id="accordionAccountInformation">
                    <div class="card z-depth-0 bordered">
                        <div class="card-header" id="headingOne">
                          <h5 class="mb-0">
                            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne"
                              aria-expanded="true" aria-controls="collapseOne">
                              <?php esc_html_e('Address Information', 'wpcargo-frontend-manager' ); ?>
                            </button>
                          </h5>
                        </div>
                        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionAccountInformation">
                            <div class="card-body">
                                <div id="profile-address">
                                    <i id="updateAccount" class="fa fa-edit text-primary"></i>
                                    <div id="accountInformation">       
                                        <p><strong ><?php esc_html_e('Phone', 'wpcargo-frontend-manager' ); ?></strong>:
                                            <span id="billing_phone" class="user-data">
                                                <?php echo get_user_meta( $user_info->ID , 'billing_phone', true ); ?>
                                            </span>
                                        </p>
                                        <?php if( !empty( wpcfe_registration_address_fields() ) ): foreach( wpcfe_registration_address_fields() as $field_key => $field_data ): ?>
                                            <?php 
                                            $meta_value = maybe_unserialize( get_user_meta( $user_info->ID , 'billing_'.$field_key, true ) );
                                            if( $field_key == 'country' ){
                                                $meta_value = wpcfe_get_country_name( $meta_value );
                                            }  
                                            if( is_array( $meta_value) ){
                                                $meta_value = implode(', ', $meta_value );
                                            }  
                                            ?>
                                            <p><strong ><?php echo $field_data['label']; ?></strong>:
                                                <span id="billing_<?php echo $field_key; ?>" class="user-data"><?php echo $meta_value; ?></span>
                                            </p>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <form id="wpcfeAccountInformation" style="display: none;">
                                        <div class="md-form form-sm">
                                            <label class="form-check-label" for="first_name"><?php esc_html_e( 'First Name', 'wpcargo-frontend-manager' ); ?></label>
                                            <input id="first_name" class="form-control border-input" type="text" size="20" value="<?php echo $user_info->first_name; ?>" name="first_name" required="required" autocomplete="off">
                                        </div>
                                        <div class="md-form form-sm">
                                            <label class="form-check-label" for="last_name"><?php esc_html_e( 'Last Name', 'wpcargo-frontend-manager' ); ?></label>
                                            <input id="last_name" class="form-control border-input" type="text" size="20" value="<?php echo $user_info->last_name; ?>" name="last_name" required="required" autocomplete="off">
                                        </div>
                                        <div class="md-form form-sm">
                                            <label class="form-check-label" for="phone"><?php esc_html_e( 'Phone', 'wpcargo-frontend-manager' ); ?></label>
                                            <input id="phone" class="form-control border-input" type="text" size="20" value="<?php echo get_user_meta( $user_info->ID , 'billing_phone', true ); ?>" name="phone" required="required" autocomplete="off">
                                        </div>
                                        <?php if( !empty( wpcfe_registration_address_fields() ) ): foreach( wpcfe_registration_address_fields() as $field_key => $field_data ): ?>
                                            <?php 
                                            $require = ( $field_data['required'] == 1 ) ? 'required' : '' ;
                                            $meta_value = maybe_unserialize( get_user_meta( $user_info->ID , 'billing_'.$field_key, true ) );
                                            if( $field_key == 'country' ){
                                                $meta_value = wpcfe_get_country_name( $meta_value );
                                            } 
                                            echo  ( $field_data['type'] == 'select' || $field_data['type'] == 'radio' || $field_data['type'] == 'checkbox' ) ? '' : '<div class="md-form form-sm">' ; 
                                            ?><label class="form-check-label" for="<?php echo $field_key; ?>"><?php echo $field_data['label']; ?></label><?php
                                            if( $field_data['type'] == 'text' ){
                                                ?><input id="<?php echo $field_key; ?>" class="form-control border-input" type="text" size="20" value="<?php echo $meta_value; ?>" name="<?php echo $field_key; ?>" autocomplete="off" <?php echo $require; ?>><?php
                                            }elseif( $field_data['type'] == 'textarea' ){
                                                ?><textarea name="<?php echo $field_key; ?>" id="<?php echo $field_key; ?>" cols="30" rows="6"><?php echo $meta_value; ?></textarea><?php
                                            }elseif( $field_data['type'] == 'select' ){                                              
                                                ?>
                                                <select class="browser-default custom-select" name="<?php echo $field_key; ?>">
                                                    <option value="" disabled selected><?php echo esc_html__('Select', 'wpcargo-frontend-manager').' '.$field_data['label']; ?></option>
                                                    <?php
                                                    foreach ( $field_data['options'] as $_key => $_value ) {
                                                        if( $field_key != 'country'){
                                                            $_key = $_value;
                                                        }
                                                        ?><option value="<?php echo $_key; ?>" <?php selected( $meta_value, $_key); ?>><?php echo $_value; ?></option><?php
                                                    }
                                                    ?>
                                                </select>
                                                <?php
                                            }elseif( $field_data['type'] == 'radio' || $field_data['type'] == 'checkbox' ){
                                                $name       = ( $field_data['type'] == 'radio' ) ? $field_key : $field_key.'[]' ;
                                                ?>
                                                <ul class="wpcfe_reg_option_list">
                                                    <?php
                                                    foreach ( $field_data['options'] as $_key => $_value ) {
                                                        if( $field_key != 'country'){
                                                            $_key = $_value;
                                                        }
                                                        if( $field_data['type'] == 'radio' ){
                                                            $checked = checked( $meta_value, $_key, false );
                                                        }else{
                                                            $checked    = in_array( $_key, $meta_value) ? 'checked="checked"' : '';
                                                        }
                                                        ?><input type="<?php echo $field_data['type']; ?>" name="<?php echo $name; ?>" value="<?php echo $_key; ?>" <?php echo $checked; ?>/> <?php echo $_value; ?></br><?php
                                                    }
                                                    ?>
                                                </ul>
                                                <?php
                                            }
                                            echo  ( $field_data['type'] == 'select' || $field_data['type'] == 'radio' || $field_data['type'] == 'checkbox' ) ? '' : '</div>' ;
                                            ?>  
                                        <?php endforeach; endif; ?>
                                        <button id="update_account" class="btn btn-outline-primary btn-rounded btn-block my-4 waves-effect z-depth-0" type="submit"><?php esc_html_e('Update', 'wpcargo-frontend-manager' ); ?></button>  
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php do_action( 'wpcfe_after_account_information', $user_info ); ?>
                </div> <!-- #accordionAccountInformation -->
            </div> <!-- modal body -->
            <!--Footer-->
            <div class="modal-footer justify-content-center">
            </div>
        </div>
        <!--/.Content-->
    </div>
</div>
<!-- Full Height Modal Right Info Demo-->