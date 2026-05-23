 (function($) {


		$.ajax({
			url: ajaxloadaddons.ajaxurl,
			type: 'post',
			data: {
				action: 'load_addons',
			},
			beforeSend:function(){
					//** Proccessing
					$('#load_addons').append('<div class="wptf-loading">Loading...</div>');
				},
			success: function( html ) {
	
				$('#load_addons').append( html );
				$('#load_addons .wptf-loading').remove();
				//console.log(html + 'hello')
			}
		})

})(jQuery);
