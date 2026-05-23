<h2><?php esc_html_e('Add registration info to address book', 'wpcargo-frontend-manager' ); ?></h2>
<p class="description"><?php esc_html_e('Note: This data while add to the address book if plugin is installed.', 'wpcargo-frontend-manager' ); ?></p>
<table class="form-table">
    <?php foreach( $shipper_fields as $field ): ?>
    <?php if( $field['field_type'] == 'file' ){ continue; } ?>
    <tr>
        <th><label for="<?php echo $field['field_key'].'_'.$field['weight']; ?>"><?php echo stripslashes($field['label']); ?></label></th> 
        <td>
            <select name="wpcfe_regmap_<?php echo trim($field['field_key']); ?>" id="<?php echo $field['field_key'].'_'.$field['weight']; ?>">
                <option value=""><?php esc_html_e('Select Field', 'wpcargo-frontend-manager' ); ?></option>
                <?php foreach( $registration_fields as $r_key => $r_field ): ?>
                    <option value="<?php echo $r_key; ?>"
                        <?php selected( get_option( 'wpcfe_regmap_'.trim($field['field_key']) ), $r_key ); ?>
                    ><?php echo $r_field['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php endforeach; ?>
</table>