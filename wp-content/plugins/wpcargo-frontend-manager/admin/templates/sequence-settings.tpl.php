<h2><?php _e('Shipment Number Sequence', 'wpcargo-frontend-manager' ); ?></h2>
<table class="form-table">
    <tr>
        <th><?php esc_html_e('Enable Sequence Number?', 'wpcargo-frontend-manager' ); ?></th>
        <td>
            <input type="checkbox" name="wpcfe_nsequence_enable" value="1" <?php echo checked( wpcfe_nsequence_enable(), 1 ) ?>>
            <p class="description"><?php esc_html_e('Note: This will generate a shipment sequence number.', 'wpcargo-frontend-manager' ); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Shipment Number Start', 'wpcargo-frontend-manager' ); ?></th>
        <td>
            <input type="number" min="0" name="wpcfe_nsequence_start" value="<?php echo wpcfe_nsequence_start(); ?>" required>
            <p class="description"><?php esc_html_e('Note: This will be the starting shipment number.', 'wpcargo-frontend-manager' ); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Shipment Number Digit', 'wpcargo-frontend-manager' ); ?></th>
        <td>
            <input type="number" min="1" name="wpcfe_nsequence_digit" value="<?php echo wpcfe_nsequence_digit(); ?>" required >
            <p class="description"><?php esc_html_e('Note: This will be the N of shipment Number digit.', 'wpcargo-frontend-manager' ); ?></p>
        </td>
    </tr>
    <tr>
        <th>&nbsp;</th>
        <td>
            <a id="wpcfe-reset_nsequence" href="#" class="button button-medium button-secondary" ><span class="dashicons dashicons-undo" style="vertical-align: sub;"></span> <?php esc_html_e('Reset Sequence Number', 'wpcargo-frontend-manager' ); ?></a>
        </td>
    </tr>
</table>