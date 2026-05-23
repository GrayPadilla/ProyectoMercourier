<?php do_action('wpcumanage_user_group_before_form'); ?>
<form id="umanageUserForm" method="post">
	<?php wp_nonce_field( 'wpcumanage_user_group_action', 'wpcumanage_user_group_field'); ?>
	<?php do_action('wpcumanage_user_group_before_fields'); ?>
	<div class="table-responsive">
		<table id="wpcumanage-user-group-list" class="table table-hover table-sm">
			<thead>
				<th class="tbl-head"><?php echo wpcumanage_group_name_label(); ?></th>
				<th class="tbl-head"><?php echo wpcumanage_description_label(); ?></th>
				<th class="tbl-head"><?php echo wpcumanage_action_label(); ?></th>
			</thead>
			<tbody>
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
							<td><i class="fa fas fa-user-times"></i> <?php echo stripcslashes($label); ?></td>
							<td><?php echo stripcslashes($description); ?></td>
							<td>
								<a href="#" class="wpcumanage-update-group" data-toggle="modal" data-target="#updateUserGroupModal" title="<?php echo wpcumanage_update_label(); ?>" data-id="<?php echo $user_group_id; ?>"><i class="fa fa-edit text-info"></i></a>
								<a href="#" class="wpcumanage-delete-group" data-id="<?php echo $user_group_id; ?>" title="<?php echo wpcumanage_delete_label(); ?>"><i class="fa fa-trash text-danger"></i></a>
								
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php do_action( 'wpcumanage_user_group_after_fields' ); ?>
</form>
<?php do_action( 'wpcumanage_user_group_after_form' ); ?>