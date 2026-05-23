jQuery(document).ready(function($) {
    $('#add-address').on('click', function(e){
		e.preventDefault();
		var bookType = $(this).attr('data-book');
		$('#add-address-book-form input#book-type').val(bookType);
		$( "#wpc-add-address-book" ).dialog({
			title: AddressBookAjaxHandler.formAddTitle,
			height: 400,
			maxHeight: 600,
			width:'350',
			closeOnEscape: true,
			draggable: false,
			resizable: false,
		});
		
	});
	$('.edit-address').on('click', function(e){
		e.preventDefault();
		var parentID = $(this).parent().parent().parent().attr('id');
		var bookID = $(this).attr('data-id');
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
				$('#'+ parentID +' .update').append('<img class="wpc-spinner" src="'+AddressBookAjaxHandler.includeUrl+'/js/tinymce/skins/lightgray/img/loader.gif" alt="spinner" />');
			},
			success:function(data){
				$.each( data, function( key, value ) {
					$( "#wpc-edit-address-book #"+key ).val( value );
				});
				$('#wpc-edit-address-book input#address-id').val(bookID);
				$('#'+ parentID +' .update img.wpc-spinner').remove();
				setTimeout( function(){ 
					$( "#wpc-edit-address-book" ).dialog({
						title: AddressBookAjaxHandler.fromEditTitle,
						height: 400,
						maxHeight: 600,
						width:'350',
						closeOnEscape: true,
						draggable: false,
						resizable: false,
					});
				},0);
			}
		});
		return false;
	});
	//** Add / Edit Submit script
	$( "#add-address-book-form" ).submit(function( e ) {
		e.preventDefault();
		var formData = $(this).serialize();

		$.ajax({
			type:"POST",
			//dataType:"JSON",
			data:{
				action:'wpc_add_address',	
				formData:formData,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('#add-address-book-form .book-submit').append('<img class="wpc-spinner" src="'+AddressBookAjaxHandler.includeUrl+'/js/tinymce/skins/lightgray/img/loader.gif" alt="spinner" />');
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
		var formData = $(this).serialize();
		$.ajax({
			type:"POST",
			data:{
				action:'edit_address_book',	
				formData:formData,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('#wpc-edit-address-book .book-submit').append('<img class="wpc-spinner" src="'+AddressBookAjaxHandler.includeUrl+'/js/tinymce/skins/lightgray/img/loader.gif" alt="spinner" />');
			},
			success:function(data){
				if( data == 0 ){
					alert( AddressBookAjaxHandler.formSubmitError );
				}
				setTimeout( function(){ location.reload(); }, 100);
			}
		});
		return false;
	});
	//** Delete address
	$('.delete-address').on('click', function(e){
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
					$('#'+ parentID +' .delete').append('<img class="wpc-spinner" src="'+AddressBookAjaxHandler.includeUrl+'/js/tinymce/skins/lightgray/img/loader.gif" alt="spinner" />');
				},
				success:function(data){
					$('#'+ parentID +' .delete img.wpc-spinner').remove();
					$('#'+ parentID).remove();
				}
			});
		}
	});
	//** Select with search script
	$('#searchShipper').select2({
		placeholder: AddressBookAjaxHandler.searchShipperTitle,
		allowClear: true,		
	});
	$('#searchShipper').on('change', function(e){
		e.preventDefault();
		//var parentID = 'shipper-details';
		var parentID = $(this).parent().parent().parent().parent().parent().parent().attr('id');
		var bookID = $(this).val();
		var bookType = $(this).attr('data-book');
		if( bookID == '' ){
			$('#'+parentID+' input, #'+parentID+' select, #'+parentID+' textarea').val('');
			return;
		}
		$.ajax({
			type:"POST",
			dataType:"JSON",
			data:{
				action:'insert_address_book',	
				bookType:bookType,
				bookID:bookID,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('#'+ parentID +' #shipper-address').append('<img class="wpc-spinner" src="'+AddressBookAjaxHandler.includeUrl+'/js/tinymce/skins/lightgray/img/loader.gif" alt="spinner" />');
			},
			success:function(data){
				$.each( data, function( key, value ) {
					$('#'+parentID+' input[name="'+key+'"], #'+parentID+' select[name="'+key+'"], #'+parentID+' textarea[name="'+key+'"]').val(value);
				});
				$('#'+ parentID +' #shipper-address img.wpc-spinner').remove();
			}
		});
	});
	$('#searchReceiver').select2({
		placeholder: AddressBookAjaxHandler.searchReceiverTitle,
		allowClear: true
	});
	$('#searchReceiver').on('change', function(e){
		e.preventDefault();
		//var parentID = 'receiver-details';
		var parentID = $(this).parent().parent().parent().parent().parent().parent().attr('id');
		var bookID = $(this).val();
		var bookType = $(this).attr('data-book');
		if( bookID == '' ){
			$('#'+parentID+' input, #'+parentID+' select, #'+parentID+' textarea').val('');
			return;
		}
		$.ajax({
			type:"POST",
			dataType:"JSON",
			data:{
				action:'insert_address_book',	
				bookType:bookType,
				bookID:bookID,
			},
			url : AddressBookAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** Proccessing
				$('#'+ parentID +' #receiver-address').append('<img class="wpc-spinner" src="'+AddressBookAjaxHandler.includeUrl+'/js/tinymce/skins/lightgray/img/loader.gif" alt="spinner" />');
			},
			success:function(data){
				$.each( data, function( key, value ) {
					$('#'+parentID+' input[name="'+key+'"], #'+parentID+' select[name="'+key+'"], #'+parentID+' textarea[name="'+key+'"]').val(value);
				});
				$('#'+ parentID +' #receiver-address img.wpc-spinner').remove();
			}
		});
	});
});