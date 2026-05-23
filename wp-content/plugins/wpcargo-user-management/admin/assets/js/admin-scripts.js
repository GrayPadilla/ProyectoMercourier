jQuery( document ).ready( function($){
	$('#wpcumanage_ug_users').select2({
		placeholder: '',
		allowClear: true
	});
	$('.wpcumanage-search-section .wpcumanage-search-button').click(function(){
		var search_text = jQuery('.wpcumanage-search-section input').val();
		if($.urlParam('search-label') === null){
			window.location.href = window.location.href + '&search-label='+ search_text;
		}else{
			var url = window.location.href;
			var page_param = getQueryVariable(url, 'page');
			var params = { 'page':page_param, 'search-label':search_text };
			var new_url = url + '?' + jQuery.param(params);
			window.location.href = new_url;
		}
	});
	// Add user group
	$( "#wpcumanage-add-user-form, #update-user-group" ).on('submit', function( e ) {
		e.preventDefault();
		var group_id 			= $(this).find('#wpcumanage_ug_id').val();
		var label 				= $(this).find('#wpcumanage_ug_label').val();
		var description 		= $(this).find('#wpcumanage_ug_desc').val();
		var wpcumanage_ug_users = $(this).find('#wpcumanage_ug_users').val();
		var save_type 			= $(this).data('type');

		$.ajax({
			url : wpcumanageAjaxHandler.ajaxurl,
			type : 'post',
			data : {
				action : 'wpcumanage_save_user_group',
				group_id : group_id,
				label : label,
				description : description,
				wpcumanage_ug_users : wpcumanage_ug_users,
				save_type : save_type,
			},
			beforeSend:function(){
				$('body').append('<div class="wpc-loading">Loading...</div>');
			},
			success : function( response ) {
				window.location.reload();
			}
		});
	});

	// DISPLAY UPDATE USER GROUP MODAL
	$('body').on('click', '.wpcumanage-edit-group', function(e){
		e.preventDefault();
		var form = $('#wpcumanage-user-group-modal');
		form.css("display","block");
		var id = $(this).attr('data-id');
		$.ajax({
			url : wpcumanageAjaxHandler.ajaxurl,
			type : 'post',
			data : {
				action : 'wpcumanage_get_user_group_data',
				id:id
			},
			beforeSend:function(){
				$('body').append('<div class="wpc-loading">Loading...</div>');
			},
			success : function( response ) {
				form.find('.modal-body form').html( response );
				form.find('#wpcumanage_ug_users').select2({
					placeholder: '',
					allowClear: true
				});
				$('body .wpc-loading').remove();
			}
		});
	});
	//DELETE DATA
	$("body").on('click','.wpcumanage-delete',function(e){
		e.preventDefault();
		var id = $(this).attr('data-id');
		var confirmDelete = confirm('Are you sure you want to delete this data?');
		if( confirmDelete ){
			$.ajax({
				url : wpcumanageAjaxHandler.ajaxurl,
				type : 'post',
				data : {
					action : 'wpcumanage_delete_user_group',
					id : id,
				},
				beforeSend:function(){
					$('body').append('<div class="wpc-loading">Loading...</div>');
				},
				success : function( response ) {
					window.location.reload();
				}
			});
		}
	});
	// BULK ACTION 
	$("body").on('click','.wpcumanage-bulk-delete',function(e){
		e.preventDefault();
		var id = [];
		var confirmDelete = confirm('Are you sure you want to delete selected data?');
		$.each( $( '.bulk_id:checked' ), function(){
			id.push( $(this).val() );
		} );
		if( confirmDelete ){
			$.ajax({
				url 	: wpcumanageAjaxHandler.ajaxurl,
				type	: 'post',
				data 	: {
						action 	: 'wpcumanage_ug_bulk_delete',
						id 		: id,
				},
				beforeSend:function(){
					$('body').append('<div class="wpc-loading">Loading...</div>');
				},
				success:function(data){
					window.location.reload();
				}
			});
		}
	});
	//  Check and Uncheck ALL		  
 	$('#checkall:checkbox').click( function(){
 		if(  $(this).prop("checked") == true){	         		
         	$(".tbl-data td :checkbox").attr("checked", true);
     	}else {
     		$(".tbl-data td :checkbox").attr("checked", false);         		
     	}
 	});
});