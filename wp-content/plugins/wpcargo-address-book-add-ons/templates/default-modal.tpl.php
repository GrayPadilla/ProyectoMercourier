<div id="wpc-add-address-book" class="wpc-address-book-dialog" style="display:none;">
  	<div class="box-group"> 
        <div id="add-address-wrapper">
            <form id="add-address-book-form">
                <?php do_action( 'wpc_address_book_before_fields', $book, 'add' ); ?>
				<?php wpc_address_book_convert_to_form_field( $book_fields ); ?>
                <input class="default_address" type="checkbox" value="yes" name="<?php echo 'default_'.$book; ?>"  ><span><?php _e('Default', 'wpcargo-address-book') ?></span> 
                <input class="book" type="hidden" name="book" value="<?php echo $book; ?>" />
                <p class="book-submit"><input class="button-wpcargo button-submit" type="submit" name="add-address" value="<?php _e('Add Address', 'wpcargo-address-book' ); ?>" /></p>
                <input id="book-type" type="hidden" name="book" value="" />
            </form>
        </div>
    </div>
</div>
<div id="wpc-edit-address-book" class="wpc-address-book-dialog" style="display:none;">
  	<div class="box-group"> 
        <div id="edit-address-wrapper">
            <form id="edit-address-book-form">
                <?php do_action( 'wpc_address_book_before_fields', $book, 'edit' ); ?>  
				<?php wpc_address_book_convert_to_form_field( $book_fields ); ?>
                <input class="default_address" type="checkbox" value="yes" name="<?php echo 'default_'.$book; ?>"  ><span><?php _e('Default', 'wpcargo-address-book') ?></span> 
                <input class="book" type="hidden" name="book" value="<?php echo $book; ?>" />
                <input id="address-id" type="hidden" name="address-id" value="" />
                <p class="book-submit"><input class="button-wpcargo button-submit" type="submit" name="submit" value="<?php _e('Update Address', 'wpcargo-address-book' ); ?>" /></p>
            </form>
        </div>
    </div>
</div>