<?php 
    $default_user = get_user_meta( $userID, 'default_'.$book, true );
?>
<h1><?php esc_html_e('User Address Book', 'wpcargo-address-book' ); ?> <a id="add-address" href="#" class="page-title-action" data-book="<?php echo $book; ?>"><?php esc_html_e('Add', 'wpcargo-address-book' ); ?> <?php echo ucfirst($book); ?> <?php esc_html_e('Address', 'wpcargo-address-book' ); ?></a></h1>
<div id="address-book-menu">
	<h2 class="nav-tab-wrapper">
		<?php
			if( !get_option('wpc_disable_address_receiver_search') ){
				?><a href="<?php echo admin_url('admin.php?page=address-book&user='.$userID ); ?>&book=receiver" class="nav-tab<?php echo ( $book == 'receiver' ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e('Receiver Book', 'wpcargo-address-book' ); ?></a><?php
			}
			if( !get_option('wpc_disable_address_shipper_search') ){
				?><a href="<?php echo admin_url('admin.php?page=address-book&user='.$userID); ?>&book=shipper" class="nav-tab<?php echo ( $book == 'shipper' ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e('Shipper Book', 'wpcargo-address-book' ); ?></a><?php
			}
		?>
	</h2>
</div>
<form style="float: right;  margin-bottom: 18px;" action="<?php echo admin_url('admin.php'); ?>" method="get">
	<input type="hidden" name="page" value="address-book"/>
	<input type="hidden" name="user" value="<?php echo $userID; ?>"/>
	<input type="hidden" name="book" value="<?php echo $book; ?>"/>
	<div class="form-sm">
		<label for="search-shipment" class="sr-only active"><?php echo $search_data['meta_label']; ?></label>
		<input type="text" class="form-control form-control-sm" name="_saddress" id="search-shipment" placeholder="<?php echo $search_data['meta_label']; ?>" value="<?php echo $_saddress; ?>">
		<button type="submit" class="button button-secondary"><?php esc_html_e('Search', 'wpcargo-address-book' ); ?></button>
	</div>
</form>
<div id="address-list">
	<table id="book" style="width:100%;">
		<thead>
			<tr>
				<?php
				foreach( $book_info as $field ){
					if( in_array( $field['id'], $book_options ) ){
						$style = $field['field_type'] == 'address' ? "width:280px;" : '';
						?><th style="<?php echo $style; ?>"><?php echo stripslashes( $field['label'] ); ?></th><?php
					}
				}
				?>
				<th><?php echo esc_html__('Default', 'wpcargo-address-book'); ?></th>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ( $book_address->have_posts() ) :
				while ( $book_address->have_posts() ) : $book_address->the_post();
					$meta_counter = 0;
					?>
					<tr id="address-<?php echo get_the_ID(); ?>" class="view-mode">
						<?php
							foreach( $book_info as $field ){
								if(!in_array( $field['id'], $book_options) ){
									continue;
								}
								$value = '';
								if( $field['field_type'] == 'address' ){
									$address = wpccf_extract_address( get_the_ID(), $field['field_key'] );
									$value = implode(" ", $address);
								}else{
									$meta_value = maybe_unserialize( get_post_meta( get_the_ID(), $field['field_key'], true ) );
									$value 	= is_array( $meta_value ) ? implode(", ", $meta_value) : $meta_value;
								}
								?><td><?php echo $value; ?></td><?php
							}
						?>
						<td>
							<?php if( $default_user == get_the_ID() ): ?>
								<input type="radio" checked disabled="">
							<?php endif; ?>
						</td>
						<td class="update">
							<span class="update-button"><a href="#" class="edit-address button button-quotation" default-id="<?php echo $default_user; ?>" data-id="<?php echo get_the_ID(); ?>" data-book="<?php echo get_post_meta( get_the_ID(), 'book', TRUE ); ?>"><?php esc_html_e('Update', 'wpcargo-address-book' ); ?></a></span>
						</td>
						<td class="delete"><a class="wpc-delete-address" href="#" data-id="<?php echo get_the_ID(); ?>"><?php esc_html_e('Delete', 'wpcargo-address-book' ); ?></a></td>
					</tr>
				<?php 
				endwhile;
			else: 
				?>
				<tr><td colspan="5" style="text-align:center;"><?php esc_html_e('No Book Address Found!', 'wpcargo-address-book' ); ?></td></tr>
				<?php
			endif;
			wp_reset_postdata();
			?>               
		</tbody>
	</table>
</div>

<div id="wpc-add-address-book" class="wpcabook-modal wpcargo-modal">
	<div class="modal-content">
		<div class="modal-header">
			<span class="close">&times;</span>
		</div>
		<div d="add-address-wrapper" class="modal-body">
			<form id="add-address-book-form">
				<?php wpc_address_book_convert_to_form_field( $book_info ); ?>
                <input class="default_address" type="checkbox" value="yes" name="<?php echo 'default_'.$book; ?>"  ><span><?php _e('Default', 'wpcargo-address-book') ?></span> 
                <input class="book" type="hidden" name="book" value="<?php echo $book; ?>" />
                <input id="book-type" type="hidden" name="book" value="" />
                <input id="book-user" type="hidden" name="_assigned_to" value="<?php echo $userID; ?>" />
                <p class="book-submit"><input class="button button-primary button-wpcargo button-submit" type="submit" name="add-address" value="<?php esc_html_e('Add Address', 'wpcargo-address-book' ); ?>" /></p>
            </form>
		</div>
		<div class="modal-footer"></div>
	</div>
</div>
<div id="wpc-edit-address-book" class="wpcabook-modal wpcargo-modal">
	<div class="modal-content">
		<div class="modal-header">
			<span class="close">&times;</span>
		</div>
		<div id="edit-address-wrapper" class="modal-body">
			<form id="edit-address-book-form">
				<?php wpc_address_book_convert_to_form_field( $book_info ); ?>
                <input class="default_address" type="checkbox" value="yes" name="<?php echo 'default_'.$book; ?>"  ><span><?php _e('Default', 'wpcargo-address-book') ?></span> 
                <input class="book" type="hidden" name="book" value="<?php echo $book; ?>" />
                <input id="address-id" type="hidden" name="address-id" value="" />
                <input id="book-user" type="hidden" name="_assigned_to" value="<?php echo $userID; ?>" />
                <p class="book-submit"><input class="button button-primary button-wpcargo button-submit" type="submit" name="submit" value="<?php esc_html_e('Update Address', 'wpcargo-address-book' ); ?>" /></p>
            </form>
		</div>
		<div class="modal-footer"></div>
	</div>
</div>