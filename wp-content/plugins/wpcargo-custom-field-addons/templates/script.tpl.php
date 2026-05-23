<script>
jQuery(document).ready(function($){
    // Uploading files
    var file_frame;
    var $image_gallery_ids 	= $( '#wpcpq_upload_ids_<?php echo $field['id'];?>' );
    var gallery_ids 		= $( '#wpcpq_upload_ids_<?php echo $field['id'];?>' ).val();
    var $product_images    	= $( '#wpcargo-gallery-container_<?php echo $field['id'];?>' ).find( 'ul.wpccf_uploads' );
    var image_url 			= "<?php echo WPCARGO_CUSTOM_FIELD_URL.'assets/images/'; ?>";
    $('#wpcargo_select_gallery_<?php echo $field['id']; ?>').on('click', function( event ){
        event.preventDefault();
        // If the media frame already exists, reopen it.
        if ( file_frame ) {
            file_frame.open();
            return;
        }
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: '<?php _e('Upload File', 'wpcargo-custom-field'  ); ?>',
            button: {
                text: '<?php _e('Select File', 'wpcargo-custom-field'  ); ?>',
            },
            multiple: <?php echo apply_filters('wpcpq_multiple_fileupload', 'true' ); ?>
            // Set to true to allow multiple files to be selected
        });
        // When an image is selected, run a callback.
        var attachment_ids;
        file_frame.on( 'select', function() {
            // We set multiple to false so only get one image from the uploader
            //attachment = file_frame.state().get('selection').first().toJSON();
            attachment = file_frame.state().get('selection').map(function( attachment ) {
                // Do something with attachment.id and/or attachment.url here
                attachment = attachment.toJSON();
                if ( attachment.id ) {
                    if( attachment.type == 'text' || attachment.type == 'video' || attachment.type == 'audio' ){
                        var imageURL = image_url+attachment.type+'-format.png';
                    }else if( attachment.type == 'application' && attachment.subtype == 'pdf' ){
                        var imageURL = image_url+attachment.subtype+'-format.png';
                    }else{
                        var imageURL = image_url+'file-format.png';
                    }
                    attachment_ids   = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
                    var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : imageURL;
                    $product_images.append( '<li class="image" data-attachment_id="' + attachment.id + '">'+
                        '<img src="' + attachment_image + '" />'+
                        '<span class="img-title">'+attachment.title.substr(0,18)+'</span>'+
                        '<span class="actions"><a href="#" class="delete" data-imgID="'+attachment.id+'">x</a></span>'+
                        '</li>' );
                }
            });
            $image_gallery_ids.val( gallery_ids+','+attachment_ids );
            // $image_gallery_ids.map(function( attachment_ids ) { return attachment_ids; }).get().join( "," );
        });
        // Finally, open the modal
        file_frame.open();
    });
    // Remove images
    $( '#wpcargo-gallery-container_<?php echo $field['id'];?>' ).on( 'click', 'a.delete', function() {
        $( this ).closest( 'li.image' ).remove();
        var attachment_ids = '';
        $( '#wpcargo-gallery-container_<?php echo $field['id'];?>' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
            var attachment_id = jQuery( this ).attr( 'data-attachment_id' );
            attachment_ids = attachment_ids + attachment_id + ',';
        });
        $image_gallery_ids.val( attachment_ids );
        // remove any lingering tooltips
        $( '#tiptip_holder' ).removeAttr( 'style' );
        $( '#tiptip_arrow' ).removeAttr( 'style' );
        return false;
    });
});
</script>