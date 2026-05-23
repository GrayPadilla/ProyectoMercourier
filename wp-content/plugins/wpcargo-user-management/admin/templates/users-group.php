<div class="wrap wpcumanage-content">
	<h1 class="wp-heading-inline"><?php esc_html_e('User Group', 'wpcargo-umanagement' ); ?></h1>
	<div class="column-wrap">
		<div class="column one-third">
			<div class="wpcumanage-form-section">
			<h4><?php esc_html_e('Add a new user group', 'wpcargo-umanagement' ); ?></h4>
				<form id="wpcumanage-add-user-form" class="wpcumanage-form" data-type="save">
					<table class="form-table wpcumanage_add_table">
						<tr>
							<th><label><?php echo wpcumanage_group_name_label(); ?></label></th>
							<td><input type="text" name="wpcumanage_ug_label" id="wpcumanage_ug_label" class="wpcumanage_detail wpcumanage_ug_label" value="" /></td>
						</tr>
						<tr>
							<th><label><?php echo wpcumanage_description_label(); ?></label></th>
							<td><textarea rows="6" name="wpcumanage_ug_desc" id="wpcumanage_ug_desc" class="wpcumanage_detail wpcumanage_ug_desc" value=""></textarea></td>
						</tr>
						<tr>
							<th><label><?php echo wpcumanage_users_label(); ?></label></th>
							<td>
								<select name="wpcumanage_ug_users" id="wpcumanage_ug_users" class="wpcumanage_detail wpcumanage_ug_users" multiple>
									<?php
									global $wpcargo;
									$args = array(
                                        'role__in'  => array('wpcargo_client'),
                                        'orderby'   => 'display_name',
                                        'order'     => 'ASC'
                                     );
 
                                    $users  = array();
                                     if( !empty( get_users( $args ) ) ){
                                         foreach ( get_users( $args ) as $user ) {
                                            $users[$user->ID] = $wpcargo->user_fullname( $user->ID );
                                         }
                                     }
    
									
									foreach( $users as $user_id => $user_name ):?>
										<option value="<?php echo $user_id;?>" ><?php echo $user_name; ?></option>
									<?php endforeach;?>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<input type="hidden" id="wpcumanage_type" value="add">
							</td>
							<td><input type="submit" id="wpcumanage_submit" class="wpcumanage_ug_btn button button-primary button-large wpcumanage_submit" value="<?php echo wpcumanage_add_group_label(); ?>" /></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
		<div class="column two-thirds">
			<div class="wpcumanage-table-section wpcumanage-location-table" style="overflow-x: scroll;">
				<div class="wpcumanage-search-section">
					<input type="text" class="wpcumanage-search-table">
					<input type="button" class="wpcumanage-search-button" value="Search">
				</div>
				<?php wpcumanage_pagination( $pagelink, $page_count, $page ); ?>
				<a href="#" id="wpcumanage_bulk_button" data-section="user-group" class="wpcumanage-bulk-delete delete-btn button button-medium"><?php echo wpcumanage_delete_label(); ?></a>
				<table class="wpcumanage-table list-table">
					<th class="tbl-head bulk-action"><input id="checkall" type="checkbox"></th>
					<th class="tbl-head"><?php echo wpcumanage_group_name_label(); ?></th>
					<th class="tbl-head"><?php echo wpcumanage_description_label(); ?></th>
					<th class="tbl-head"><?php echo wpcumanage_users_label(); ?></th>
					<th class="tbl-head"><?php echo wpcumanage_action_label(); ?></th>
					<?php if( $users_group ): ?>
						<?php foreach( $users_group as $data ): ?>
							<?php
								$user_group_id = $data->user_group_id;
								$label = $data->label;
								$description = $data->description;
								$users = $data->users ? unserialize( $data->users ) : array();
								$user_name = array();
								if( !empty($users) ){
									foreach( $users as $user ){
										$user_name[] = $wpcargo->user_fullname( $user );
									}
								}							
							?>
							<tr class="tbl-data">
								<input type="hidden" >
								<td><input type="checkbox" class="bulk_id" name="rate_bulk_action[]" value="<?php echo $user_group_id;?>"></td>
								<td><?php echo stripcslashes($label); ?></td>
								<td><?php echo stripcslashes($description); ?></td>
								<td><?php echo join( ', ', $user_name ); ?></td>
								<td>
									<span class="dashicons dashicons-edit wpcumanage-edit-group" data-section="user-group" data-id="<?php echo $user_group_id;?>"></span>
									<span class="dashicons dashicons-trash wpcumanage-delete" data-section="user-group" data-id="<?php echo $user_group_id;?>" ></span>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</table>
				<a href="#" id="wpcumanage_bulk_button" data-section="user-group" class="wpcumanage-bulk-delete delete-btn button button-medium"><?php echo wpcumanage_delete_label(); ?></a>
				<?php wpcumanage_pagination( $pagelink, $page_count, $page ); ?>
			</div>
		</div>
	</div>
</div>
<div id="wpcumanage-user-group-modal" class="modal">
	<div class="modal-content">
		<div class="modal-title">
			<p><?php echo wpcumanage_update_group_label(); ?>
			<span class="close-btn">&times;</span></p>
		</div>
		<div class="modal-body">
			<form id="update-user-group" class="wpcumanage-form" data-type="update">
			</form>
		</div>
	</div>
</div>