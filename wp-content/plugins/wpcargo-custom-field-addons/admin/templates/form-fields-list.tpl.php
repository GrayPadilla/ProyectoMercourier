<div class="wpcargo-form-field-list">
    <h2><?php esc_html_e('Form Field List', 'wpcargo-custom-field' ); ?></h2>
    <div class='wpcargo-wrap'>
        <table id='wpcargo-table-cf'>
        	<thead>
                <tr>
                    <th style='text-align:left;' class='td-width'><?php esc_html_e('Label / Association', 'wpcargo-custom-field' ); ?></th>
                    <th style='text-align:left;' class='td-width'><?php esc_html_e('Field Key', 'wpcargo-custom-field' ); ?></th>
                    <th style='text-align:left;' class='td-width'><?php esc_html_e('Type', 'wpcargo-custom-field' ); ?></th>
                    <th style='text-align:left;' class='td-width'><?php esc_html_e('Required', 'wpcargo-custom-field' ); ?></th>
                    <th style='text-align:left;width: 260px;' class='td-width'><?php esc_html_e('Field Attributes', 'wpcargo-custom-field' ); ?></th>
                    <th style='text-align:left;' class='td-width'><?php esc_html_e('Class Attributes', 'wpcargo-custom-field' ); ?></th>
                </tr>
            </thead>
            <tbody id="sortable">
            	<?php foreach ($this->fields as $field) { ?>
            	<tr id="list-<?php echo $field->id; ?>" data-weight="<?php echo $field->weight; ?>" data-id="<?php echo $field->id; ?>">
                	<td>
                    	<?php echo stripslashes( $field->label ); ?><div id="label-<?php echo $field->id; ?>" class="row-actions" ><a href="<?php echo admin_url(); ?>admin.php?page=wpc-cf-manage-form-field&action=edit&id=<?php echo $field->id; ?>"><?php esc_html_e('Edit', 'wpcargo-custom-field' ); ?></a> | <a class="delete-field" href="#" data-id="<?php echo $field->id;?>" ><?php esc_html_e('Delete', 'wpcargo-custom-field' ); ?></a><span class="delete-spin"></span></div>
                    </td>
                    <td>
                    	<?php echo $field->field_key; ?>
                    </td>
                    <td>
                    	<?php echo $field->field_type; ?>
                    </td>
                    <td>
                    	<?php echo $field->required; ?>
                    </td>
                    <td>
                    	<?php 
						$flags = maybe_unserialize( $field->display_flags );
						if( !empty( $flags ) ){
							$flags = array_filter( $flags );
							?>
							<ul class="wpc-cf-flag" >
								<?php
									foreach($flags as $flag){
										?><li><?php echo $flag; ?></li><?php 
									}
								?>
							</ul>
							<?php
						} 
						?>
                    </td>
                    <td>
                    	<?php echo $field->classes; ?>
                    </td>
                </tr>
                <?php } ?>
        	</tbody>
        </table>
    </div>
</div>