jQuery(document).ready(function($) {
	const hideLabel = shipmentContainerAjaxHandler.hideLabel;
	const showLabel = shipmentContainerAjaxHandler.showLabel;
	if( $( '#container-history-wrapper' ).length ){
		$('#container-history-wrapper').repeater({
			show: function () {
				$(this).slideDown();
			},
			hide: function (deleteElement) {
				if(confirm('Are you sure you want to delete this element?')) {
					$(this).slideUp(deleteElement);
				}
			},
			ready: function (setIndexes) {
	
			}
		});
	}
	var wpcsc_number = '';
	if( $('#wpcsc_number').length ){
		wpcsc_number = $('#wpcsc_number').val();
	}
	$('select[name="wpcsc_page"]').change(function() {
        this.form.submit();
    }); 
	$('#shipment-info-wrapper').on('click', '#wpcsc-toggle', function( e ){
		e.preventDefault();
		const status = $(this).data('stat');
		if( status === 'show'){
			$('#assigned-shipment #shipment-list-wrapper').removeClass('display-none');
			$(this).data('stat', 'hide' );
			$(this).text(hideLabel);
		}else{
			$('#assigned-shipment #shipment-list-wrapper').addClass('display-none');
			$(this).data('stat', 'show' );
			$(this).text(showLabel);
		}
		
	})
	$('#container-form').on('focusout', '#wpcsc_number', function(){
		$('#container-form button[type=submit]').removeClass('disabled');
		var currentField = $(this);
		var containerNumber = currentField.val();
		if( wpcsc_number == containerNumber ){
			currentField.parent().parent().find('#container-number-message').remove();
			return;
		}
		$.ajax({
			type:"POST",
			data:{
				action	: 'check_container',
				containerNumber	: containerNumber,	
			},
			url : shipmentContainerAjaxHandler.ajaxurl,
			beforeSend:function(){
				currentField.parent().find('.fa').removeClass('fa-barcode').addClass( 'fa-spinner fa-spin text-primary' );
				currentField.parent().parent().find('#container-number-message').remove();
				currentField.addClass( 'border border-primary' );
				$('#container-form button[type=submit]').addClass('disabled');
			},
			success:function(data){
				if( data > 1 ){
					currentField.parent().parent().append('<div id="container-number-message" class="alert alert-danger p-1 mt-1 rounded-0" role="alert">'+
					shipmentContainerAjaxHandler.messageContainersExist+
				  '</div>');
					$('#container-form button[type=submit]').addClass('disabled');
				}else{
					$('#container-form button[type=submit]').removeClass('disabled');
				}
				currentField.parent().find('.fa').removeClass('fa-spinner fa-spin text-primary').addClass( 'fa-barcode' );
				currentField.removeClass( 'border border-primary' );
				return false;
			}
		});
	});
	function showNotification(type, message, icon = "check"){
    	$('body').append(
            '<div class="wpcfe-alert alert alert-'+type+'" role="alert">'+
            '<span class="fa fa-'+icon+'"></span> '+
              message+
            '  <span class="fa fa-window-close wpcfe-close-notification"></span>'+
            '</div>'
        );
        setTimeout(function(){
            $('body .wpcfe-alert').remove();
        }, 6000);
	}
	 $('.wpcsc_container-delete').on('click', function(e){
        e.preventDefault();
        var confirmation = confirm( wpcfeAjaxhandler.shipmentConfirmation );
        var containerID   = $(this).attr('data-id');
        if( confirmation ){
            $.ajax({
                type:"POST",
                data:{
                    action:'delete_container',    
                    containerID:containerID,
                },
                url : wpcfeAjaxhandler.ajaxurl,
                beforeSend:function(){
                    //** Proccessing
                    $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                },
                success:function(data){
                    var response = JSON.parse( data );
                    if( response.status == 'success'){
                        $('table#container-list tbody tr#container-'+containerID).remove();
                    }
                    $('body .wpcfe-spinner').remove();                   
                    showNotification( response.status, response.message, response.icon );
                }
            });
        }
    });
	$( "#container-shipment-list-wrapper" ).sortable({
		cursorAt: { left: 0 },
		update: function( event, ui ) {
			shipment_sorter();
		}
	});
	function shipment_sorter(){
		var selectedShipment = [];
		$('#container-shipment-list-wrapper .selected-shipment').each(function( index ){
			var shipmentID = $(this).data('shipment');
			selectedShipment.push( shipmentID );
		});
		$('#wpcc_sorted_shipments').val( selectedShipment.join(',') );
	}
	var dataColumn = []
	$.each(shipmentContainerAjaxHandler.dataTableInfo, function( index, value){
		dataColumn.push({ 'data': index });
	});
	dataColumn.push( { data: 'actions', "class": "text-center" } );
	let shipmentOptions = {};
	var shipmentTable = $('#shipment-options-table').DataTable( {
		data: shipmentOptions,
		columns: dataColumn,
		"paging":   shipmentContainerAjaxHandler.dataTablePaging,
		"pageLength": shipmentContainerAjaxHandler.dataTablePageLength
	} );
	shipmentTable.on('draw', function(){
		$('.dataTables_paginate > .pagination').addClass('pagination-circle');
		$('.dataTables_length select').addClass('mdb-select md-form');
	});
    $('#showShipmentList').on('click', function(e){
		e.preventDefault();
		var postID = $(this).attr('data-id');
		$('#assigned-shipment #shipment-list-wrapper').removeClass('display-none');
		$('#wpcsc-toggle').data('stat', 'hide' );
		$('#wpcsc-toggle').text(hideLabel);
		$.ajax({
			type:"POST",
			datatype:'json',
			data:{
				action	: 'get_shipments',
				postID	: postID,	
			},
			url : shipmentContainerAjaxHandler.ajaxurl,
			beforeSend:function(){
				$('body').append('<div class="wpcfe-spinner">Loading...</div>');
				shipmentTable.clear().draw();
			},
			success:function(data){		
				$('body .wpcfe-spinner').remove();
				shipmentOptions = JSON.parse( data );
				shipmentTable.rows.add( shipmentOptions ).search( '' ).draw();
				return false;
			}
		});
	});
	// Assign shipment to container
	$('#shipment-options-table').on('click', '.shipment-assign-icon', function(e){
		e.preventDefault();
		var shipmentID = $(this).data('id');
		var containerID = $(this).data('ctn');
		$.ajax({
			type:"POST",
			datatype:'json',
			data:{
				action	: 'assign_shipment',
				shipmentID	: shipmentID,	
				containerID	: containerID
			},
			url : shipmentContainerAjaxHandler.ajaxurl,
			beforeSend:function(){
				$('body').append('<div class="wpcfe-spinner">Loading...</div>');
				shipmentTable.clear().draw();
			},
			success:function(data){
				$('body .wpcfe-spinner').remove(); 
				var data = JSON.parse( data );
				shipmentOptions = data.data;
				shipmentTable.rows.add( shipmentOptions ).search( '' ).draw();
				if( data.status == 'success' ){
					$( '#shipment-list tbody' ).append( data.message );
					$('#shipment-options-table #opt_'+shipmentID).remove();
				}else{
					alert( data.message )
				}
				$('#shipment-info-wrapper .shipment-count').text( $('#container-shipment-list-wrapper .selected-shipment').length );
				return false;
			}
		});
	});
	// Unassign shipment to container
	$("#container-shipment-list-wrapper").on('click', '.remove-shipment', function(e){
		var postID = $(this).attr('data-id');
		var parentID = $(this).parent().parent().attr('id');
		$.ajax({
			type:"POST",
			data:{
				action	: 'remove_shipment',
				postID	: postID,
			},
			url : shipmentContainerAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** 
				$('body').append('<div class="wpcfe-spinner">Loading...</div>');
			},
			success:function(data){
				$('body .wpcfe-spinner').remove(); 
				if( data == true  ){
					$('#'+parentID).remove();
				}else{
					alert( shipmentContainerAjaxHandler.processError );
				}
				shipment_sorter();
				$('#shipment-info-wrapper .shipment-count').text( $('#container-shipment-list-wrapper .selected-shipment').length );
				return;
			}
		});
	});
	$("#container-shipment-list-wrapper").on('click', '.update-shipment', function(e){
		var postID = $(this).attr('data-id');
		var status = $(this).attr('data-value');
		$.ajax({
			type:"POST",
			data:{
				action	: 'update_shipment',
				postID	: postID,
				status	: status,
			},
			url : shipmentContainerAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** 
				$('body').append('<div class="wpcfe-spinner">Loading...</div>');
			},
			success:function(data){
				$('body .wpcfe-spinner').remove(); 
				location.reload();
			}
		});
	});
	$("#shipment-list").on('click', '.unassigned-shipment', function(e){
		var currentField 	= $(this);
		var postID 			= currentField.attr('data-id');
		$.ajax({
			type:"POST",
			data:{
				action	: 'remove_shipment',
				postID	: postID,
			},
			url : shipmentContainerAjaxHandler.ajaxurl,
			beforeSend:function(){
				//** 
				$('body').append('<div class="wpcfe-spinner">Loading...</div>');
			},
			success:function(data){
				$('body .wpcfe-spinner').remove(); 
				currentField.parent().remove();
				return;
			}
		});
	});
	// Bulk Assign Shipments to Container
    $('#shipmentBulkContainerModal').on('hidden.bs.modal', function () {
        reset_selected_shipment();
        reset_shipment();
    });
	$('#shipmentBulkContainerModal .shipment-list').on('click', '.list-group-item .fa-trash', function () {
        $(this).parent().remove();
    });
    function reset_selected_shipment( ){
        $("#wpcfe-select-all")[0].checked = false;
        $('.wpcfe-shipments').each( function(){ //iterate all listed checkbox items
            this.checked = false; //change ".checkbox" checked status
        });
    }
	function reset_shipment( id, number ){
        $('#shipmentBulkContainerModal .shipment-list-wrapper .shipment-list li').remove();
    }
	function generate_shipment( id, number ){
        $('#shipmentBulkContainerModal .shipment-list-wrapper .shipment-list').append( 
            '<li class="list-group-item w-50 list-group-item-action" data-id="'+id+'">'+number+' <span class="fa fa-trash float-right text-danger"></span></li>'
        );
    }
	$('#shipments-table-list').on('click', '#bulkContainerAssign', function(e){
        e.preventDefault();
        var shipments = $('#shipment-list .wpcfe-shipments:checked').length;
        reset_shipment();
        if( shipments > 0 ){
            $('.wpcfe-shipments:checked').each( function(){ //iterate all listed checkbox items
                var shipmentID      = $(this).val();
                var shipmentNumber  = $(this).data('number');
                generate_shipment(shipmentID, shipmentNumber);
            });
        }else{
            alert( shipmentContainerAjaxHandler.selectedErrorMessage );
            return false;
        }
    });
	$('#shipmentBulkContainerModal').on('submit', '#shipmentBulkAssignContainer-form', function( e ){
        e.preventDefault();
        var shipmentIDs = [];
       	var containerID = $('#assign_container').val();
        $('#shipmentBulkContainerModal .shipment-list-wrapper .shipment-list .list-group-item').each(function( index){
            var shipmentID = $(this).data('id');
            shipmentIDs.push( shipmentID );
        });
        // Check if shipment is selected
        if( shipmentIDs.length < 1 ){
            alert( shipmentContainerAjaxHandler.selectedErrorMessage );
            return false;
        }
        //Process shipment to update
        $.ajax({
            type:"POST",
            //dataType: 'json',
            data:{
                action         	: 'bulk_assign_container',    
                shipmentIDs    	: shipmentIDs,
                containerID		: containerID,
            },
            url : shipmentContainerAjaxHandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcfe-spinner">Loading...</div>');
            },
            error:function( xml, error ){ 

            },
            success:function( response ){
                $('body .wpcfe-spinner').remove();
                var obj = JSON.parse( response );
                if( !obj.length ){
                    alert( shipmentContainerAjaxHandler.bulkUpdateError);
                }else{
                    showNotification( 'success', shipmentContainerAjaxHandler.bulkUpdateSuccess, 'check' );
                    $('#shipmentBulkContainerModal').modal('hide');
                    window.location.reload();
                }              
            }
        });
    });
	// Bulk Delete functionality
	//Select All Checkboxes for shipments
	$("#wpcsc-select-all").change( function(){  //"select all" change
        var status = this.checked; // "select all" checked status      
        $('.wpcsc-container').each( function(){ //iterate all listed checkbox items
            this.checked = status; //change ".checkbox" checked status
        });
    });
    $("#container-list").on('change', '.wpcsc-container', function(){
        //uncheck "select all", if one of the listed checkbox item is unchecked
        if(this.checked == false){ //if this item is unchecked
            $("#wpcsc-select-all")[0].checked = false; //change "select all" checked status to false
        }
        //check "select all" if all checkbox items are checked
        if ($('#container-list .wpcsc-container:checked').length == $('.wpcsc-container').length ){
            $("#wpcsc-select-all")[0].checked = true; //change "select all" checked status to true
        }
    });
    // Bulk Print Script
    $('#wpcsc-action_wrapper').on('click', '#wpcsc_container_bulk-delete', function(e){
        e.preventDefault();
        let selectedContainer = [];
		$('#container-list').find('.wpcsc-container:checked').each( function(){ //iterate all listed checkbox items
			selectedContainer.push( $(this).val() );
		});
		const confimration = confirm( shipmentContainerAjaxHandler.bulkDeleteConfirm );
		if( !confimration ){
			return;
		}
        if( selectedContainer.length > 0 ){        
            $.ajax({
                type:"POST",
                data:{
                    action : 'wpcsc_bulk_delete',    
                    selectedContainer : selectedContainer,
                },
                url : shipmentContainerAjaxHandler.ajaxurl,
                beforeSend:function(){
					$("#wpcsc-select-all")[0].checked = false;
                    $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                },
                success:function( response ){
                    $('body .wpcfe-spinner').remove();
				   if( response.status == 'error'){
						alert( response.message );
						return;
				   }
				   $.each( response.results, function( key, value ) {
						const dataRow = $('#container-list').find(`tr#container-${key}`);
						const countTD = dataRow.find('td').length
						if( value.status == 'error' ){
							dataRow.addClass('wpcsc-proccessed wpcsc-proccessed-failed alert alert-danger')
						}else{
							dataRow.addClass('wpcsc-proccessed wpcsc-proccessed-success alert alert-success')
						}
						dataRow.html(`<td colspan="${countTD}">${value.message }</td>`);
				  });
				  setTimeout(() => {
					$('#container-list').find(`tr.wpcsc-proccessed.wpcsc-proccessed-success`).remove();
				  }, 3000 );
                }
            });   	
        }else{
            alert( selectedErrorMessage );
            return;
        }
    });
});