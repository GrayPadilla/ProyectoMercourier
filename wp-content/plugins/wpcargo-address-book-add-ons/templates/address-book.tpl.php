<div id="wpc-address-book">
	<a href="#" id="add-address" class="btn btn-info btn-sm" data-book="<?php echo $book; ?>" <?php echo $modal_addAttribute; ?>><?php esc_html_e('Add New', 'wpcargo-address-book' ); ?> <?php echo ucfirst($book); ?> <?php esc_html_e('Address', 'wpcargo-address-book' ); ?></a>
	<form id="wpcfe-search" class="float-md-none float-lg-right" action="<?php echo $page_url; ?>" method="get">
		<input type="hidden" name="book" value="<?php echo $book; ?>"/>
		<div class="form-sm">
			<label for="search-shipment" class="sr-only active"><?php echo $search_data['meta_label']; ?></label>
			<input type="text" class="form-control form-control-sm" name="_saddress" id="search-shipment" placeholder="<?php echo $search_data['meta_label']; ?>" value="<?php echo $_saddress; ?>">
			<button type="submit" class="btn btn-primary btn-sm mx-md-0 ml-2"><?php esc_html_e('Search', 'wpcargo-address-book' ); ?></button>
		</div>
	</form>
	<?php if( !( get_option('wpc_disable_address_shipper_search') || get_option('wpc_disable_address_receiver_search') )  ): ?>
		<ul id="orders-pagination">
			<li class="<?php echo ( $book == 'shipper' ) ? 'active' : ''; ?>"><a href="<?php echo $page_link; ?>?book=shipper"><?php esc_html_e('Shipper Book Address', 'wpcargo-address-book' ); ?></a></li>
			<li class="<?php echo ( $book == 'receiver' ) ? 'active' : ''; ?>"><a href="<?php echo $page_link; ?>?book=receiver"><?php esc_html_e('Receiver Book Address', 'wpcargo-address-book' ); ?></a></li>
		</ul>
	<?php endif; ?>
	<div id="address-list" class="table-responsive">
		<table id="book" style="width:100%;" class="table table-hover table-sm">
			<thead>
				<tr>
					<?php do_action( 'wpcabook_before_field_header', $page_link, $book ); ?>
					<th class="form-check"><input class="form-check-input " name="wpcfe-all-address" id="wpcfe-all-address" type="checkbox"><label class="form-check-label" for="wpcfe-all-address"></label></th>
					<?php foreach( $book_fields as $field ): ?>
						<?php if( in_array( $field['id'], $book_options ) ): ?>
							<?php $style = $field['field_type'] == 'address' ? "width:280px;" : ''; ?>
							<th style="<?php echo $style; ?>"><?php echo stripslashes( $field['label'] ); ?></th>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php do_action( 'wpcabook_after_field_header' ); ?>
					<th><?php echo __('Default', 'wpcargo-address-book') ?></th>
					<th><?php echo __('Actions', 'wpcargo-address-book') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $book_address->have_posts() ) : ?>
					<?php while ( $book_address->have_posts() ) : $book_address->the_post(); ?>
						<?php 
						$is_public    = get_post_meta( get_the_ID(), 'public_'.$book, true );
						$meta_counter = 0; 
						?>
						<tr id="address-<?php echo get_the_ID(); ?>" class="view-mode">
							<?php do_action( 'wpcabook_before_field_data', get_the_ID() ); ?>
							<td class="form-check">
								<input class="wpcfe-address form-check-input " type="checkbox" name="wpcfe-address[]" value="<?php echo get_the_ID(); ?>">
								<label class="form-check-label" for="materialChecked2"></label>
							</td>
							<?php foreach( $book_fields as $field ): ?>
								<?php if( in_array( $field['id'], $book_options ) ): ?>
									<?php
										if(!in_array( $field['id'], $book_options) ){
											continue;
										}
										$value = '';
										if( $field['field_type'] == 'address' ){
											$address = wpccf_extract_address( get_the_ID(), $field['field_key'] );
											$address_final_data_separator = apply_filters('wpcab_list_address_implode_separator', ', ');
											$value = implode($address_final_data_separator, array_filter($address));
										}else{
											$meta_value = maybe_unserialize( get_post_meta( get_the_ID(), $field['field_key'], true ) );
											$value 	= is_array( $meta_value ) ? implode(", ", $meta_value) : $meta_value;
										}
									?>
									<td><?php echo $value; ?></td>
								<?php endif; ?>
							<?php endforeach; ?> 
							<?php do_action( 'wpcabook_after_field_data', get_the_ID() ); ?>
							<td>
								<?php if($default_user == get_the_ID() ) : ?>
									<input type="checkbox" class="form-check-input" checked><label class="form-check-label"></label>
								<?php endif; ?>
							</td>
							<td class="update">
								<a href="#" class="edit-address" default-id="<?php echo $default_user; ?>" data-id="<?php echo get_the_ID(); ?>" data-book="<?php echo get_post_meta( get_the_ID(), 'book', TRUE ); ?>" <?php echo $modal_editAttribute; ?>><i class="fa fa-edit text-info"></i></a>
								<a class="delete-address" href="#" data-id="<?php echo get_the_ID(); ?>"><i class="fa fa-trash text-danger"></i></a>
							</td>
						</tr>
					<?php endwhile; ?>
				<?php else: ?>
					<tr>
						<td colspan="5" style="text-align:center;"><?php esc_html_e('No Book Address Found!', 'wpcargo-address-book' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php wp_reset_postdata(); ?>               
			</tbody>
		</table>
	</div>
</div>