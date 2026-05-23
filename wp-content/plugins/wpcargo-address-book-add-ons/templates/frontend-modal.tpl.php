<!-- Add Modal -->
<div class="modal fade top" id="addAddressModalPreview" class="modal hide fade" role="dialog" aria-labelledby="addAddressModalPreviewLabel" aria-hidden="true">
	<div class="modal-dialog modal-fluid modal-full-height modal-top" role="document">
		<div class="modal-content container-fluid">
			<form id="add-address-book-form" class="row">
				<section class="col-md-6 offset-md-3">
					<div class="modal-header">
						<h5 class="modal-title" id="addAddressModalPreviewLabel"><?php esc_html_e('Add New','wpcargo-address-book' ); ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e('Close','wpcargo-address-book' ); ?>">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<?php do_action( 'wpc_address_book_before_fields', $book, 'add' ); ?>
						<?php wpc_address_book_convert_to_form_field( $book_fields, 'add' ); ?>
						<?php do_action( 'wpc_address_book_after_shipper_fields', $book, 'add' ); ?>
						<input class="book" type="hidden" name="book" value="<?php echo $book; ?>" />
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e('Close','wpcargo-address-book' ); ?></button>
						<button type="submit" class="btn btn-primary"><?php esc_html_e('Save Address','wpcargo-address-book' ); ?></button>
					</div>
				</section>
			</form>
		</div>
	</div>
</div>
<!-- Modal -->
<!-- Edit Modal -->
<div class="modal fade top" id="editAddressModalPreview" class="modal hide fade" role="dialog" aria-labelledby="editAddressModalPreviewLabel" aria-hidden="true">
	<div class="modal-dialog modal-fluid modal-full-height modal-top" role="document">
		<div class="modal-content container-fluid">
			<form id="edit-address-book-form" class="row">
				<section class="col-md-6 offset-md-3">
					<div class="modal-header">
						<h5 class="modal-title" id="editAddressModalPreviewLabel"><?php esc_html_e('Edit Address','wpcargo-address-book' ); ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e('Close','wpcargo-address-book' ); ?>">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">  
						<?php do_action( 'wpc_address_book_before_fields', $book, 'edit' ); ?>          
						<?php wpc_address_book_convert_to_form_field( $book_fields, 'edit' ); ?>
						<?php do_action( 'wpc_address_book_after_shipper_fields', $book, 'edit' ); ?>
						<input id="address-id" type="hidden" name="address-id" value="" /> 
						<input class="book" type="hidden" name="book" value="<?php echo $book; ?>" />
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e('Close','wpcargo-address-book' ); ?></button>
						<button type="submit" class="btn btn-primary"><?php esc_html_e('Update Address','wpcargo-address-book' ); ?></button>
					</div>
				</section>
			</form>
		</div>
	</div>
</div>
<!-- Modal -->