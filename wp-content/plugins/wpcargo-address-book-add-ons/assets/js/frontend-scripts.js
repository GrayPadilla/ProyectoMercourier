jQuery(document).ready(function($) {
	let addressKeys         = JSON.parse( addressBookFrontendAjaxHandler.addressKeys );
    // Submit form for add address book
    $( '#add-address-book-form' ).on( 'submit', function( e ) {
        e.preventDefault();
		var formData = $(this).serializeArray();
		$.ajax({
			type:"POST",
			dataType:"JSON",
			data:{
				action:'wpc_add_address',	
				formData:formData,
			},
			url : addressBookFrontendAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
			},
			success:function(data){
				$('body .wpcargo-loading').remove();
				if( data.status == 'error' ){
					return;
				}
				alert( data.message );
				setTimeout( function(){ location.reload(); },0);
			}
		});
		return false;
    });
    // Click edit button
    $('#address-list').on('click', '.edit-address', function(e){
		e.preventDefault();
		var bookID = $(this).attr('data-id');
        var bookType = $(this).attr('data-book');
        var defaultID = $(this).attr('default-id');
		$.ajax({
			type:"POST",
			dataType:"JSON",
			data:{
				action:'wpc_get_address',	
				bookType:bookType,
				bookID:bookID,
				defaultID:defaultID,
			},
			url : addressBookFrontendAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpcargo-loading">Loading...</div>');
			},
			success:function(data){
				setTimeout(function(){
					$.each( data, function( key, value ) {
						const addressType = addressKeys.find(element => element == key );
						const isObjValue  = typeof value === 'object';
						// Check if the field type is Address
						if( addressType && isObjValue && Object.keys(value).length ){
							for (const [fkey, fvalue] of Object.entries(value)) {
								if( $('#edit-address-book-form [name="'+key+'['+fkey+']"]').length ){
									$('#edit-address-book-form [name="'+key+'['+fkey+']"]').val( fvalue );
								}
							}
						}
						// Normal Fields
						if( $('#edit-address-book-form [name="'+key+'"]').length ){
							$('#edit-address-book-form [name="'+key+'"]').val( value );

							if( key == 'default_'+bookType && value == bookID ){
								$( "#edit-address-book-form input[name='"+key+"']" ).prop( 'checked', true );
							}else{
								$( "#edit-address-book-form input[name='"+key+"']" ).prop( 'checked', false );
							}
						}

					});
					$('#edit-address-book-form input#address-id').val(bookID);
				}, 10);
				$('body .wpcargo-loading').remove();
			}
		});
    });
    // Update Address
    $(this).on("submit","#edit-address-book-form" , function( e ) {
		e.preventDefault();
		var formData = $(this).serializeArray();
		$.ajax({
			type:"POST",
			dataType:"JSON",
			data:{
				action:'edit_address_book',	
				formData:formData,
			},
			url : addressBookFrontendAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpcargo-loading">Loading...</div>');
			},
			success:function(data){
				if( data.status == 'error' ){
					alert( data.message );
					return;
				}
				setTimeout( function(){ location.reload(); }, 100);
			}
		});
		return false;
    });
	
	// Bulk delete address
	const getChildCheckBoxes = () => {
		return $('input.wpcfe-address');
	}
	$('body').on('change', 'input#wpcfe-all-address', function(){
		let isChecked = $(this).is(':checked');
		getChildCheckBoxes().each(function(){
			$(this).prop('checked', isChecked);
		});
	});
	$('body').on('change', 'input.wpcfe-address', function(){
		let numChecked = 0;
		let parentCheck = true;
		getChildCheckBoxes().each(function(){
			if($(this).is(':checked')) {
				numChecked++;
			}
		});
		if(numChecked != getChildCheckBoxes().length) {
			parentCheck = false;
		}
		$('input#wpcfe-all-address').prop('checked', parentCheck);
	});
	$('body').on('click', 'button.remove-address', function(){
		let addressBookIds = [];
		getChildCheckBoxes().each(function(){
			if($(this).is(':checked')) {
				addressBookIds.push($(this).val());
			}
		});
		if(addressBookIds.length === 0) {
			alert('Please check an item(s) from the list.');
			return;
		}
		if(confirm('Delete selected item(s)?')) {
			$.ajax({
				type:"POST",
				data:{
					action:'bulk_delete_address_book',	
					addressBookIds:addressBookIds,
				},
				url : addressBookFrontendAjaxHandler.ajaxurl,
				beforeSend:function(){
					$('body').append('<div class="wpcargo-loading">Loading...</div>');
				},
				success:function(data){
					$('body .wpcargo-loading').remove();
					if(data) {
						let {status, message} = data;
						alert(message);
						window.location.reload();
					}
				},
				error:function(err){
					$('body .wpcargo-loading').remove();
					alert(err.statusText);
				}
			});
		}
	});
	//** Delete address
	$('#address-list').on('click', '.delete-address', function(e){
		e.preventDefault();
		var confirmResult = confirm( addressBookFrontendAjaxHandler.deleteMessage );
		if( confirmResult ){
			var parentID = $(this).parent().parent().attr('id');
			var bookID = $(this).attr('data-id');
			$.ajax({
				type:"POST",
				data:{
					action:'delete_address_book',	
					bookID:bookID,
				},
				url : addressBookFrontendAjaxHandler.ajaxurl,
				beforeSend:function(){
					//** Proccessing
					$('body').append('<div class="wpcargo-loading">Loading...</div>');
				},
				success:function(data){
					$('body .wpcargo-loading').remove();
					$('#'+ parentID).remove();
				}
			});
		}
	});
});
