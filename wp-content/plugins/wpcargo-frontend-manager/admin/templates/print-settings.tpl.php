<h2><?php _e('Print Setup', 'wpcargo-frontend-manager' ); ?></h2>
<table class="form-table">
    <tr>
        <th><?php esc_html_e('Enable Chinese Fonts', 'wpcargo-frontend-manager' ); ?></th>
        <td>
            <input type="checkbox" name="wpcfe_customfont_enable" value="1" <?php echo checked( wpcfe_customfont_enable(), 1 ) ?>>
            <p class="description"><?php esc_html_e('Note: This will add additional font family for simplified chinese fonts. Enable this option only if you use simplified chinese fonts in you site.', 'wpcargo-frontend-manager' ); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Enable Print BOL', 'wpcargo-frontend-manager' ); ?></th>
        <td>
            <input type="checkbox" name="wpcfe_bol_enable" value="1" <?php echo checked( wpcfe_bol_enable(), 1 ) ?>>
            <p class="description"><?php esc_html_e('Note: This will add additional print functionality for BOL.', 'wpcargo-frontend-manager' ); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Waybill Paper Size', 'wpcargo-frontend-manager'); ?></th>
        <td>
            <select name="wpcfe_waybill_paper_size" style="width:360px;">
                <option value=""><?php esc_html_e('Select Size', 'wpcargo-frontend-manager'); ?></option>
                <option value="a1" <?php selected( $wpcfe_waybill_paper_size, 'a1'); ?>><?php esc_html_e('A1', 'wpcargo-frontend-manager'); ?></option>
                <option value="a2" <?php selected( $wpcfe_waybill_paper_size, 'a2'); ?>><?php esc_html_e('A2', 'wpcargo-frontend-manager'); ?></option>
                <option value="a3" <?php selected( $wpcfe_waybill_paper_size, 'a3'); ?>><?php esc_html_e('A3', 'wpcargo-frontend-manager'); ?></option>
                <option value="a4" <?php selected( $wpcfe_waybill_paper_size, 'a4'); ?>><?php esc_html_e('A4', 'wpcargo-frontend-manager'); ?></option>
                <option value="a5" <?php selected( $wpcfe_waybill_paper_size, 'a5'); ?>><?php esc_html_e('A5', 'wpcargo-frontend-manager'); ?></option>
                <option value="a6" <?php selected( $wpcfe_waybill_paper_size, 'a6'); ?>><?php esc_html_e('A6', 'wpcargo-frontend-manager'); ?></option>
                <option value="letter" <?php selected( $wpcfe_waybill_paper_size, 'letter'); ?>><?php esc_html_e('Letter', 'wpcargo-frontend-manager'); ?></option>
                <option value="legal" <?php selected( $wpcfe_waybill_paper_size, 'legal'); ?>><?php esc_html_e('Legal', 'wpcargo-frontend-manager'); ?></option>
                <option value="tabloid" <?php selected( $wpcfe_waybill_paper_size, 'tabloid'); ?>><?php esc_html_e('Tabloid', 'wpcargo-frontend-manager'); ?></option>
                <option value="executive" <?php selected( $wpcfe_waybill_paper_size, 'executive'); ?>><?php esc_html_e('Executive', 'wpcargo-frontend-manager'); ?></option>
                <option value="folio" <?php selected( $wpcfe_waybill_paper_size, 'folio'); ?>><?php esc_html_e('Folio', 'wpcargo-frontend-manager'); ?></option>
                <option value="tabloid" <?php selected( $wpcfe_waybill_paper_size, 'tabloid'); ?>><?php esc_html_e('Tabloid', 'wpcargo-frontend-manager'); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Waybill Paper Orientation', 'wpcargo-frontend-manager'); ?></th>
        <td>
            <select name="wpcfe_waybill_paper_orient" style="width:360px;">
                <option value=""><?php esc_html_e('Select Orientation', 'wpcargo-frontend-manager'); ?></option>
                <option value="landscape" <?php selected( $wpcfe_waybill_paper_orient, 'landscape'); ?> ><?php esc_html_e('Landscape', 'wpcargo-frontend-manager'); ?></option>
                <option value="portrait" <?php selected( $wpcfe_waybill_paper_orient, 'portrait'); ?>><?php esc_html_e('Portrait', 'wpcargo-frontend-manager'); ?></option>
            </select>
        </td>
    </tr>
    <?php if ( in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ) ) ) : ?>
        <tr>
            <th><?php esc_html_e('Print documents on checkout', 'wpcargo-frontend-manager'); ?></th>
            <td>
                <?php if( !empty( $wpcfe_print_options ) ): ?>
                    <select class="wpcfe-select" name="wpcfe_checkout_print[]" multiple="multiple" style="width:360px;">
                        <?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
                            <option value="<?php echo $print_key?>" <?php echo in_array( $print_key, $wpcfe_print_checkout ) ? 'selected' : '' ; ?>>
                                <?php echo $print_label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
    <tr>
        <th colspan="2"><h2><?php esc_html_e('Label print per package setup', 'wpcargo-frontend-manager'); ?></h2></th>
    </tr>
    <tr>
        <th><?php esc_html_e('Enable label multi print', 'wpcargo-frontend-manager' ); ?></th>
        <td>
            <input id="wpcfe_enable_label_multiple_print" type="checkbox" name="wpcfe_enable_label_multiple_print" value="1" <?php echo checked( wpcfe_enable_label_multiple_print(), 1 ) ?>>
            <label for="wpcfe_enable_label_multiple_print" class="description"><?php _e('Note: This will print the label per shipment package.', 'wpcargo-frontend-manager'); ?></label>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Label pagination template', 'wpcargo-frontend-manager' ); ?></th>
        <td>
            <input style="min-width: 280px;" type="text" name="wpcfe_label_pagination_template" value="<?php echo wpcfe_label_pagination_template(); ?>" placeholder="{current_page} of {total_page}">
            <p class="description"><?php _e('Default template {current_page} of {total_page}.', 'wpcargo-frontend-manager' ); ?></p>   
            <?php $shortcodes = wpcfe_package_shortcode(); ?>
            <?php if( !empty( $shortcodes ) ): ?>
            <h3 class="description"><?php _e('Available Shortcodes', 'wpcargo-frontend-manager' ); ?></h3>
            <ul style="list-style: circle; padding-left: 18px; color: #23282d;">
            <?php foreach ($shortcodes as $skey => $svalue): ?>
                <li><?php echo $skey; ?> - <?php echo $svalue; ?></li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </td>
    </tr>
</table>