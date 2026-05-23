jQuery(document).ready(function($) {
	let addressKeys         = JSON.parse( AddressBookAjaxHandler.addressKeys );
	//** Popups
	$('body').on('click', '.wpcargo-modal .close', function(){
		$(this).closest('.wpcargo-modal').removeClass('wpcargo-show-modal');
	});
	$('#add-address').on('click', function(e){
		e.preventDefault();
		var bookType = $(this).attr('data-book');
		$('#add-address-book-form input#book-type').val(bookType);
		$('#wpc-add-address-book').addClass('wpcargo-show-modal');
	});
	
	$('.edit-address').on('click', function(e){
		e.preventDefault();
		var bookID = $(this).attr('data-id');
		var defaultID = $(this).attr('default-id');
		var publicID = $(this).attr('public-id');
		var bookType = $(this).attr('data-book');

		$.ajax({
			type:"POST",
			dataType:"JSON",
			data:{
				action:'wpc_get_address',	
				bookType:bookType,
				bookID:bookID,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpc-loading"></div>');
			},
			success:function(data){

				$('#wpc-edit-address-book input#address-id').val(bookID);
				if( bookID == defaultID ){
					$('#wpc-edit-address-book .default_address').prop('checked', true);
					$('#edit-address-book-form .default_address').prop('checked', true);
				}else{
					$('#wpc-edit-address-book .default_address').prop('checked', false);
				}
				if( 1 == publicID ){
					$('#wpc-edit-address-book .public_address').prop('checked', true);
				}else{
					$('#wpc-edit-address-book .public_address').prop('checked', false);
				}

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
				$('body .wpc-loading').remove();
				$('#wpc-edit-address-book').addClass('wpcargo-show-modal');
			}
		});
		return false;
	});
	
	//** Add / Edit Submit script
	$( "#add-address-book-form" ).submit(function( e ) {
		e.preventDefault();
		var formData = $(this).serializeArray();
		$.ajax({
			type:"POST",
			data:{
				action:'wpc_add_address',	
				formData:formData,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpc-loading"></div>');
			},
			success:function(data){
				if( data == 0 ){
					alert( AddressBookAjaxHandler.formSubmitError );
				}
				setTimeout( function(){ location.reload(); },0);
			}
		});
		return false;
	});
	$( "#edit-address-book-form" ).submit(function( e ) {
		e.preventDefault();
		var formData = $(this).serializeArray();
		$.ajax({
			type:"POST",
			data:{
				action:'edit_address_book',	
				formData:formData,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpc-loading"></div>');
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
	//** Delete address
	$('.wpc-delete-address').on('click', function(e){
		e.preventDefault();
		var confirmResult = confirm( AddressBookAjaxHandler.deleteMessage );
		if( confirmResult ){
			var parentID = $(this).parent().parent().attr('id');
			var bookID = $(this).attr('data-id');
			$.ajax({
				type:"POST",
				data:{
					action:'delete_address_book',	
					bookID:bookID,
				},
				url : AddressBookAjaxHandler.ajaxurl,
				beforeSend:function(){
					//** Proccessing
					$('body').append('<div class="wpc-loading"></div>');
				},
				success:function(data){
					$('#'+ parentID).remove();
					$('body .wpc-loading').remove();
				}
			});
		}
	});
	//** Add / Edit Submit script
	$( "#export-address-book").on('submit', function( e ) {
		e.preventDefault();
		var userID = $('#export-address-book select[name="user"]').val();
		var bookType = $('#export-address-book select[name="book_type"]').val();
		$.ajax({
			type:"POST",
			data:{
				action:'export_address',	
				userID:userID,
				bookType:bookType
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpc-loading"></div>');
			},
			success:function(filename){
				download_file(filename);
				$('body .wpc-loading').remove();
			}
		});
		return false;
	});
	$( '#wpcab-csv-template li a' ).on('click', function( e ){
		e.preventDefault();
		var bookType = $(this).attr('data-id');
		$("select[name='book_type']").val( bookType );
		$.ajax({
			type:"POST",
			data:{
				action:'download_template',	
				bookType:bookType
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpc-loading"></div>');
			},
			success:function(response){
				window.location=response;
				$('body .wpc-loading').remove();
			}
		});
		return false;	
	});
	$('#address-list .public-user').on('click', function(e){
		e.preventDefault();
		var element = $(this);
		var bookid = $(this).val();
		var is_public = 0;
		var book = $(this).attr('book');
		if( $(this).prop("checked") ){
			is_public = 1;
		}else{
			is_public = 0;
		}
		$.ajax({
			type:"POST",
			data:{
				action 		:'edit_public_user',	
				bookid 		:bookid,
				is_public	:is_public,
				book 		:book,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('body').append('<div class="wpc-loading"></div>');
			},
			success:function(data){
				if( data == 0 ){
					alert( AddressBookAjaxHandler.formSubmitError );
				}
				$('.wpc-loading').remove();
				if( element.prop("checked") ){
					element.prop("checked", false);
				}else{
					element.prop("checked", true);
				}
			}
		});
		return false;
	});
	$( '#public-bulk' ).click( function(e){
		e.preventDefault();
		var is_public = 0;
		var dataIDs = [];
		var book = $(this).attr('book');
		$(".public-user").each( function(){
			dataIDs.push( $(this).val() );
		});
		
		var is_all = $( '.public-user:checked' ).length == $( '.public-user' ).length;
		if( !is_all ){
			is_public = 1;
			var setAll = confirm( AddressBookAjaxHandler.setAllPublic );
			if( setAll ){
					
					$.ajax({
						type:"POST",
						data:{
							action	:'edit_public_user',	
							dataIDs	:dataIDs,
							is_public	: is_public,
							book 	:book,
						},
						url : AddressBookAjaxHandler.ajaxurl,
						beforeSend:function(){
							//** Proccessing
							$('body').append('<div class="wpc-loading"></div>');
						},
						success:function(response){
							
							if( response == 0 ){
								alert( AddressBookAjaxHandler.formSubmitError );
							}
							$('.wpc-loading').remove();
							$('#public-bulk').prop("checked", true);
							$('#address-list .public-user').prop("checked", true);
							//setTimeout( function(){ location.reload(); }, 100);
						}
					});
					return false;
			}
		}else if( is_all ){
			is_public = 0;
			var unsetAll = confirm( AddressBookAjaxHandler.unsetAllPublic );
			if( unsetAll ){
					$.ajax({
						type:"POST",
						data:{
							action		:'edit_public_user',	
							dataIDs		:dataIDs,
							is_public	: is_public,
							book 		:book,
						},
						url : AddressBookAjaxHandler.ajaxurl,
						beforeSend:function(){
							//** Proccessing
							$('body').append('<div class="wpc-loading"></div>');
						},
						success:function(response){
							if( response == 0 ){
								alert( AddressBookAjaxHandler.formSubmitError );
							}
							$('.wpc-loading').remove();
							$('#public-bulk').prop("checked", false);
							$('#address-list .public-user').prop("checked", false);
							//setTimeout( function(){ location.reload(); }, 100);
						}
					});
				return false;
			}
		}
		
		return false;
		
	});
	// // Bulk edit for Public User
	// $( '#edit-public' ).click( function(){
	// 	$( '.public-user' ).css('display', 'block');
	// 	$( '.set-public' ).css('display', 'none');
	// 	$( this ).css( 'display', 'none' );
	// 	$( '#update-public' ).css( 'display', 'block' );
	// });
	
	//** Validation for currentcy and number 
	$("input.price, input.number").keydown(function (e) {
		validateCurrency(e)
	});
	$("input.qty").keydown(function (e) {
		validateNumber(e);
	});
	function validateCurrency(e){
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
             // Allow: Ctrl+A
            (e.keyCode == 65 && e.ctrlKey === true) ||
             // Allow: Ctrl+C
            (e.keyCode == 67 && e.ctrlKey === true) ||
             // Allow: Ctrl+X
            (e.keyCode == 88 && e.ctrlKey === true) ||
             // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
                 // let it happen, don't do anything
                 return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
	}
	//** Script for number
	function validateNumber(e){
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13]) !== -1 ||
             // Allow: Ctrl+A
            (e.keyCode == 65 && e.ctrlKey === true) ||
             // Allow: Ctrl+C
            (e.keyCode == 67 && e.ctrlKey === true) ||
             // Allow: Ctrl+X
            (e.keyCode == 88 && e.ctrlKey === true) ||
             // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
                 // let it happen, don't do anything
                 return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105) ) {
            e.preventDefault();
        }
    }
});

/* Helper function */
function download_file(fileName) {
	var fileURL = fileName;
    // for non-IE
    if (!window.ActiveXObject) {
        var save = document.createElement('a');
        save.href = fileURL;
        save.target = '_blank';
        var filename = fileURL.substring(fileURL.lastIndexOf('/')+1);
        save.download = fileName || filename;
        if ( navigator.userAgent.toLowerCase().match(/(ipad|iphone|safari)/) && navigator.userAgent.search("Chrome") < 0) {
                document.location = save.href; 
            // window event not working here
            }else{
                var evt = new MouseEvent('click', {
                    'view': window,
                    'bubbles': true,
                    'cancelable': false
                });
                save.dispatchEvent(evt);
                (window.URL || window.webkitURL).revokeObjectURL(save.href);
            }	
    }
    // for IE < 11
    else if ( !! window.ActiveXObject && document.execCommand)     {
        var _window = window.open(fileURL, '_blank');
        _window.document.close();
        _window.document.execCommand('SaveAs', true, fileName || fileURL)
        _window.close();
    }
}