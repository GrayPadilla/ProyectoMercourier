jQuery(document).ready(function($) {	
	var file_frame;
	$('.choose-file').on('click', function( event ){
		event.preventDefault();
		var dataId = $(this).attr("data-id");
		if ( file_frame ) {
			file_frame.open();
			return;
		}
		
		file_frame = wp.media.frames.file_frame = wp.media({
			title: $( this ).data( 'uploader_title' ),
			button: {
				text: $( this ).data( 'uploader_button_text' ),
			},
			multiple: false 
		});
		
		file_frame.on( 'select', function() {
			attachment = file_frame.state().get('selection').first().toJSON();
			$('input#'+dataId).val( attachment.url );
		});
		file_frame.open();
	});
	
});