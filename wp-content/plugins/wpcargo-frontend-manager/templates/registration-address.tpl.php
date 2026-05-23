<h5 class="h5-responsive"><?php echo apply_filters( 'wpcfe_reg_additional_info', __( 'Address Information', 'wpcargo-frontend-manager' ) ); ?></h5>
<!-- Company -->
<?php foreach ($args as $key => $value ): 
    $require = ( $value['required'] == 1 ) ? 'required' : '' ;
    echo ( $value['type'] == 'select' || $value['type'] == 'radio' || $value['type'] == 'checkbox' ) ? '' : '<div class="md-form form-sm">' ;
    ?>
        <label class="form-check-label" for="<?php echo $key; ?>"><?php echo $value['label']; ?></label><?php
        if( $value['type'] == 'text' ){
            ?><input id="<?php echo $key; ?>" class="form-control border-input" type="text" size="20" value="" name="<?php echo $key; ?>" autocomplete="off" <?php echo $require; ?>><?php
        }elseif( $value['type'] == 'textarea' ){
            ?><textarea name="<?php echo $key; ?>" id="<?php echo $key; ?>" cols="30" rows="6"></textarea><?php
        }elseif( $value['type'] == 'select' ){
            ?>
            <select class="browser-default custom-select" name="<?php echo $key; ?>">
                <option value="" disabled selected><?php echo __('Select', 'wpcargo-frontend-manager').' '.$value['label']; ?></option>
                <?php
                foreach ( $value['options'] as $_key => $_value ) {
                    if( $key != 'country'){
                        $_key = $_value;
                    }
                    ?><option value="<?php echo $_key; ?>"><?php echo $_value; ?></option><?php
                }
                ?>
            </select>
            <?php
        }elseif( $value['type'] == 'radio' || $value['type'] == 'checkbox' ){
            $name = ( $value['type'] == 'radio' ) ? $key : $key.'[]' ;
            ?>
            <ul class="wpcfe_reg_option_list">
                <?php
                foreach ( $value['options'] as $_key => $_value ) {
                    if( $key != 'country'){
                        $_key = $_value;
                    }
                    ?><input type="<?php echo $value['type']; ?>" name="<?php echo $name; ?>" value="<?php echo $_key; ?>"/> <?php echo $_value; ?></br><?php
                }
                ?>
            </ul>
            <?php
        }
     echo  ( $value['type'] == 'select' || $value['type'] == 'radio' || $value['type'] == 'checkbox' ) ? '' : '</div>' ;
 endforeach;