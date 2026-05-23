jQuery(document).ready(function($){
    var downloadLabel = wpcfeAjaxhandler.downloadLabel; 
    var downloadErrorMessage = wpcfeAjaxhandler.downloadErrorMessage; 
    var pageURL      = wpcfeAjaxhandler.pageURL; 
    var notification = wpcfeAjaxhandler.notification ? JSON.parse( wpcfeAjaxhandler.notification ) : {};
    var downloadFileErrorMessage = wpcfeAjaxhandler.downloadFileErrorMessage;
    var confirmRepeaterDelete    = wpcfeAjaxhandler.confirmRepeaterDelete;

     // Repeater Scripts
    $('#wpcfe-packages-repeater').repeater({
        show: function () {
            $(this).slideDown();
            setTimeout(function(){
                $("input.price, input.number").keydown(function (e) {
                    validateCurrency(e)
                });
                $("input.qty").keydown(function (e) {
                    validateNumber(e);
                });
            }, 0);
        },
        hide: function (deleteElement) {
            Swal.fire({
                title: '¿Eliminar item?',
                text: confirmRepeaterDelete,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(this).slideUp(deleteElement);
                }
            });
        },
        ready: function (setIndexes) {
        }
    });
	$('#shipment-history').repeater({
		show: function () {
			$(this).slideDown();
		},
		hide: function (deleteElement) {
			Swal.fire({
				title: '¿Eliminar item?',
				text: confirmRepeaterDelete,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d32f2f',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Sí, eliminar',
				cancelButtonText: 'Cancelar'
			}).then((result) => {
				if (result.isConfirmed) {
					$(this).slideUp(deleteElement);
				}
			});
		}
	});
    $('select[name="wpcfesort"]').change(function() {
        this.form.submit();
    }); 
    $('.mdb-select').materialSelect();
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
	if( notification.length != 0 ){
		showNotification( notification.status , notification.message, notification.icon );
	}
    $('.wpcfe-delete-shipment').on('click', function(e){
        e.preventDefault();
        var shipmentID = $(this).attr('data-id');
        
        Swal.fire({
            title: '¿Eliminar envío?',
            text: wpcfeAjaxhandler.shipmentConfirmation,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d32f2f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type:"POST",
                    data:{
                        action:'wpcfe_delete_shipment',    
                        shipmentID:shipmentID,
                    },
                    url : wpcfeAjaxhandler.ajaxurl,
                    beforeSend:function(){
                        $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                    },
                    success:function(data){
                        var response = JSON.parse( data );
                        if( response.status == 'success'){
                            $('table#shipment-list tbody tr#shipment-'+shipmentID).remove();
                        }
                        $('body .wpcfe-spinner').remove();
                        if(response.status == 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: response.message,
                                confirmButtonColor: '#28a745'
                            });
                        } else {
                            showNotification( response.status, response.message, response.icon );
                        }
                    },
                    error:function(){
                        $('body .wpcfe-spinner').remove();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al eliminar el envío.',
                            confirmButtonColor: '#e74c3c'
                        });
                    }
                });
            }
        });
    });
    $('body').on('click', '.wpcfe-close-notification', function(){
        $(this).parent().remove();
    }); 
    /*
    *   UPLOAD AVATAR SCRIPT
    */
    $('#wpcfe-avatar-wrapper').on('click', '#wpcfe-change-avatar, #close-upload-avatar', function(e){
        e.preventDefault();
        $('#user-avatar, #upload-avatar-wrapper').toggle();
    });
    $('.main-photo-container').on('click', '.photo-container', function(e){
        e.preventDefault();
        $('#updateAvatarModal').css({'display':'block'});
    });
    $uploadCrop = $('#upload-avatar').croppie({
        enableExif: true,
        viewport: {
            width: 128,
            height: 128,
            type: 'circle'
        },
        boundary: {
            width: 160,
            height: 160
        },
        url:wpcfeAjaxhandler.avatar_placeholder
    });
    function readFile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#upload-avatar').croppie('bind', {
                    url: e.target.result
                });
                $('.actionUpload').toggle();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $('#upload-avatar-wrapper').on('change', '.actionUpload', function () { readFile(this); });
    $('a.actionSave').on('click', function(e){
        e.preventDefault();
        var uploadValue = $('#upload-avatar-wrapper #upload.actionUpload').val();
        if( !uploadValue ){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No hay archivo para cargar',
                confirmButtonColor: '#e74c3c'
            });
            return false;
        }
        $uploadCrop.croppie( 'result', {
            type: 'base64',
            size: {
                width: 128,
                height: 128
            }
        }).then(function (resp) {
            $.ajax({
                type:"POST",
                data:{
                    action:'wpcfe_upload_avatar',
                    imageData: resp,
                },
                url : wpcfeAjaxhandler.ajaxurl,
                beforeSend:function(){
                    //** Proccessing
                    $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                },
                success:function( response ){
                    $('#user-avatar .photo-container').html(response);
                    $('#user-avatar, #upload-avatar-wrapper').toggle();
                    $('body .wpcfe-spinner').remove();
					location.reload();
                }
            });
        });
    });
    /*
    *   END UPLOAD AVATAR SCRIPT *****************************************************************************************************************
    */
    // Prevenir que el context menu de confirmar contraseña cause conflictos
    $(".wpcfe-select").select2({});
    $(".wpcfe-select-ajax").select2({
        ajax: {
                url: wpcfeAjaxhandler.ajaxurl, // AJAX URL is predefined in WordPress admin
                dataType: 'json',
                delay: 250, // delay in ms while typing when to perform a AJAX search
                data: function (params) {
                    return {
                        q: params.term, // search query
                        filter: $(this).attr('data-filter'),
                        action: 'wpcfe_get_option' // AJAX action for admin-ajax.php
                    };
                },
                processResults: function( data ) {
                var options = [];
                if ( data ) {
 
                    // data is the array of arrays, and each of them contains ID and the Label of the option
                    $.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
                        options.push( { id: text, text: text  } );
                    });
 
                }
                return {
                    results: options
                };
            },
            cache: true
        },
        minimumInputLength: 3,
        language: {
            inputTooShort: function(args) {
              // args.minimum is the minimum required length
              // args.input is the user-typed text
              return wpcfeAjaxhandler.inputTooShort;
            },
            inputTooLong: function(args) {
              // args.maximum is the maximum allowed length
              // args.input is the user-typed text
              return wpcfeAjaxhandler.inputTooLong;
            },
            errorLoading: function() {
              return wpcfeAjaxhandler.errorLoading;
            },
            loadingMore: function() {
              return wpcfeAjaxhandler.loadingMore;
            },
            noResults: function() {
              return wpcfeAjaxhandler.noResults;
            },
            searching: function() {
              return wpcfeAjaxhandler.searching;
            },
            maximumSelected: function(args) {
              // args.maximum is the maximum number of items the user may select
              return wpcfeAjaxhandler.maximumSelected;
            }
          }
    });
    // Registration Scripts
    // Client Registration
    $('#wpcfeRegistrationForm input[name="billing_email"]').blur( function( ){
        var currentField = $(this);
        var email = currentField.val();
        currentField.parent().removeClass('error');
        currentField.parent().find('label .error-message').remove();
        if( IsEmail(email) ){
            $.ajax({
                type:"POST",
                data:{
                    action  : 'wpcfe_check_email',    
                    email   : email
                },
                url : wpcfeAjaxhandler.ajaxurl,
                beforeSend:function(){
                    $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                    $('#wpcfeRegistrationForm input[type="submit"]').prop('disabled', true);
                },
                success:function(data){
                    $('body .wpcfe-spinner').remove();
                    if( data >= 1 ){
                        currentField.parent().addClass('error');
                        currentField.parent().find('label').append(' <span class="error-message">'+wpcfeAjaxhandler.errorEmailExist+'</span>');
                        $('#wpcfeRegistrationForm #email').val('');
                    }else{
                        $('#wpcfeRegistrationForm input[type="submit"]').prop('disabled', false);
                    }  
                    
                }
            });
        }else if( email != '' ) {
            currentField.focus();
            currentField.parent().addClass('error');
            currentField.parent().find('label .error-message').remove();
            currentField.parent().find('label').append(' <span class="error-message">'+wpcfeAjaxhandler.errorInCorrectEmail+'</span>');
        }
   
    });
    $('#wpcfeRegistrationForm #reg_pass').blur( function( ){
        var currentField = $(this);   
        var reg_pass = currentField.val();
        currentField.parent().find('label .error-message').remove();
        currentField.parent().removeClass('error');
        currentField.parent().parent().find('button[type="submit"]').prop('disabled', false );
        if( reg_pass.length < 6 && reg_pass.length > 0 ){
            currentField.focus();
            currentField.parent().addClass('error');
            currentField.parent().find('label').append(' <span class="error-message">'+wpcfeAjaxhandler.errorPasswordlength+'</span>');
            currentField.parent().parent().find('button[type="submit"]').prop('disabled', true );
        }
    });
    $('#wpcfeRegistrationForm #confirm_pass').blur( function( ){
        var currentField = $(this);
        var confirm_pass = currentField.val();
        var reg_pass = $('#wpcfeRegistrationForm #reg_pass').val();
        currentField.parent().find('label .error-message').remove();
        currentField.parent().removeClass('error');
        currentField.parent().parent().find('button[type="submit"]').prop('disabled', false );
        if( confirm_pass !== reg_pass && confirm_pass.length > 0 ){
            currentField.focus();
            currentField.parent().addClass('error');
            currentField.parent().find('label').append(' <span class="error-message">'+wpcfeAjaxhandler.errorPasswordNotMatch+'</span>');
            currentField.parents().eq(3).find('button[type="submit"]').prop('disabled', true );
        }else{
            currentField.parents().eq(3).find('button[type="submit"]').prop('disabled', false );
        }

    });
    // Password Toogler
    $( '#wpcfeRegistrationForm, .wpcfe-password-form' ).on('click', '.toggle-password', function(){
        var targetField = $(this).parent().find('input');
        var  attrValue = targetField.attr('type');
        if( attrValue == 'password' ){
            targetField.attr('type', 'text');
        }else{
            targetField.attr('type', 'password');
        }

    });
    function IsEmail(email) {
        var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if(!regex.test(email)) {
            return false;
        }else{
            return true;
        }
    }
    //Select All Checkboxes for shipments
	$("#wpcfe-select-all").change( function(){  //"select all" change
        var status = this.checked; // "select all" checked status      
        $('.wpcfe-shipments').each( function(){ //iterate all listed checkbox items
            this.checked = status; //change ".checkbox" checked status
        });
    });
    $("#shipment-list").on('change', '.wpcfe-shipments', function(){
        //uncheck "select all", if one of the listed checkbox item is unchecked
        if(this.checked == false){ //if this item is unchecked
            $("#wpcfe-select-all")[0].checked = false; //change "select all" checked status to false
        }
        //check "select all" if all checkbox items are checked
        if ($('#shipment-list .wpcfe-shipments:checked').length == $('.wpcfe-shipments').length ){
            $("#wpcfe-select-all")[0].checked = true; //change "select all" checked status to true
        }
    });
    // Bulk Print Script
    $('.wpcfe-bulkprint-wrapper').on('click', '.wpcfe-bulk-print', function(e){
        e.preventDefault();
        let printType = $(this).data('type');
        let shipments = $('#shipment-list .wpcfe-shipments:checked').length;
        let selectedShipment = [];
        if( shipments > 0 ){
            $('.wpcfe-shipments:checked').each( function(){ //iterate all listed checkbox items
                selectedShipment.push( $(this).val() );
            });
            $.ajax({
                type:"POST",
                data:{
                    action  : 'wpcfe_bulkprint',    
                    selectedShipment   : selectedShipment,
                    printType : printType
                },
                url : wpcfeAjaxhandler.ajaxurl,
                beforeSend:function(){
                    $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                },
                success:function( response ){
                    $('body .wpcfe-spinner').remove();
                    $data = JSON.parse(response);
                    if($.isEmptyObject($data)) {
                        alert( downloadFileErrorMessage );
                        return;
                    } else {
                        $('.wpcfe-shipments').each( function(){ //iterate all listed checkbox items
                            this.checked = false; //change ".checkbox" checked status
                        });
                        $('#shipment-list #wpcfe-select-all').prop('checked', false );
                        download_file( $data.file_url, $data.file_name );
                        return;
                    }
                }
            });   	
        }else{
            alert( downloadErrorMessage );
            return;
        }

    });
    
    // Download Shipment Documents
    $('.print-shipment').on('click', '.dropdown-item, .shipment-checkout', function(e){
        e.preventDefault();
        var shipmentID  = $(this).data('id');
        var printType   = $(this).data('type');
        $.ajax({
            type:"POST",
            data:{
                action      : 'wpcfe_print_shipment',    
                shipmentID  : shipmentID,
                printType   : printType
            },
            url : wpcfeAjaxhandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcfe-spinner">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcfe-spinner').remove();
                $data = JSON.parse(response);
                if($.isEmptyObject($data)) {
                    alert( downloadFileErrorMessage );
                    return;
                } else {
                    download_file( $data.file_url, $data.file_name );
                    return;
                }
            }
        });
    });
    //Bulk Assign Shipments
    function reset_shipment( id, number ){
        $('#shipmentBulkUpdateModal .shipment-list-wrapper .shipment-list li').remove();
        $('#shipmentBulkUpdateModal #registered_employee').val('');
        $('#shipmentBulkUpdateModal #registered_client').val('');
        $('#shipmentBulkUpdateModal #registered_agent').val('');
    }
    function reset_selected_shipment( ){
        $("#wpcfe-select-all")[0].checked = false;
        $('.wpcfe-shipments').each( function(){ //iterate all listed checkbox items
            this.checked = false; //change ".checkbox" checked status
        });
    }
    function generate_shipment( id, number ){
        $('#shipmentBulkUpdateModal .shipment-list-wrapper .shipment-list').append( 
            '<li class="list-group-item w-50 list-group-item-action" data-id="'+id+'">'+number+' <span class="fa fa-trash float-right text-danger"></span></li>'
        );
    }
	$('#shipments-table-list').on('click', '#shipmentBulkUpdate', function(e){
        e.preventDefault();
        var shipments = $('#shipment-list .wpcfe-shipments:checked').length;
        $( '#shipmentBulkUpdateModal #shipmentBulkUpdate-form .modal-body' ).find('input[type="text"], select, textarea').val('');
        reset_shipment();
        if( shipments > 0 ){
            $('.wpcfe-shipments:checked').each( function(){ //iterate all listed checkbox items
                var shipmentID      = $(this).val();
                var shipmentNumber  = $(this).data('number');
                generate_shipment(shipmentID, shipmentNumber);
            });
        }else{
            alert( wpcfeAjaxhandler.downloadErrorMessage );
            return false;
        }
    });
    $('#shipmentBulkUpdateModal').on('hidden.bs.modal', function () {
        reset_selected_shipment();
        reset_shipment();
    });
    $('#shipmentBulkUpdateModal .shipment-list').on('click', '.list-group-item .fa-trash', function () {
        $(this).parent().remove();
    });
    $('#shipmentBulkUpdateModal').on('submit', '#shipmentBulkUpdate-form', function( e ){
        e.preventDefault();
        var updateShipmentID = [];
        var updateFields     = $( this ).serializeArray();
       
        $('#shipmentBulkUpdateModal .shipment-list-wrapper .shipment-list .list-group-item').each(function( index){
            var shipmentID = $(this).data('id');
            updateShipmentID.push( shipmentID );
        });
        // Check if shipment is selected
        if( updateShipmentID.length < 1 ){
            alert( wpcfeAjaxhandler.downloadErrorMessage );
            return false;
        }
        // Process shipment to update
        $.ajax({
            type:"POST",
            datatype: 'json',
            data:{
                action              : 'bulk_assign_shipment',    
                updateShipmentID    : updateShipmentID,
                updateFields        : updateFields
            },
            url : wpcfeAjaxhandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcfe-spinner">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcfe-spinner').remove();
                var obj = JSON.parse( response );
                if( obj.length == 0 ){
                    alert( wpcfeAjaxhandler.bulkUpdateError);
                }else{
                    showNotification( 'success', wpcfeAjaxhandler.bulkUpdateSuccess, 'check' );
                    $('#shipmentBulkUpdateModal').modal('hide')
                }              
            }
        });
    });
    
	//Bulk Delete Shipments
	$(document).on('click', '.remove-shipments', function(e){
        e.preventDefault();
        var shipments = $('#shipment-list .wpcfe-shipments:checked').length;
        var selectedShipment = [];
        
        if( shipments > 0 ){
            Swal.fire({
                title: '¿Eliminar envíos?',
                text: '¿Estás seguro de que deseas eliminar ' + shipments + ' envío(s)? Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if( result.isConfirmed ){
                    $('.wpcfe-shipments:checked').each( function(){ //iterate all listed checkbox items
                        selectedShipment.push( $(this).val() );
                    });
                    $.ajax({
                        type:"POST",
                        datatype:'json',
                        data:{
                            action  : 'wpcfe_bulk_delete',    
                            selectedShipment   : selectedShipment
                        },
                        url : wpcfeAjaxhandler.ajaxurl,
                        beforeSend:function(){
                            $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                        },
                        success:function( data ){
                            const {status, message} = data
                            if( status === 'error'){
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: message,
                                    confirmButtonColor: '#e74c3c'
                                });
                            }else{
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Éxito',
                                    text: message,
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error:function(){
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al procesar la solicitud. Por favor intenta de nuevo.',
                                confirmButtonColor: '#e74c3c'
                            });
                        }
                    });
                }
            });
        }else{
            Swal.fire({
                icon: 'warning',
                title: 'Sin seleccionar',
                text: wpcfeAjaxhandler.downloadErrorMessage,
                confirmButtonColor: '#f39c12'
            });
            return;
        }
    });
    /* Helper function */
    function download_file(fileURL, fileName) {
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
    // validations
    $(".number").keydown(function (e) {
        validateCurrency(e);
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
	
	$('.wpcargo-dashboard .card-header').on('click', function(){
		var parentSection = $(this).parent().parent();
		$(parentSection).find('.card-body').toggle();
	});
	
	$('#shipment-number #wpcfe_shipment_title').on('change, keyup', function(){
		var shipment_title = $( this ).val();
		$.ajax({
			type:"POST",
			data:{
				action  : 'wpcfe_shipment_title_checker',    
				shipment_title   : shipment_title
			},
			url : wpcfeAjaxhandler.ajaxurl,
			success:function( response ){
				if( response != '' ){
					if( $( '.title-checker' ).length == 0 ){
						$( '#shipment-number .input-group' ).after(response);
						$('.add-shipment button[type=submit]').attr('disabled', true);
					}
				}else{
					if( $( '.title-checker' ).length != 0 ){
						$( '.title-checker' ).remove();
						$('.add-shipment button[type=submit]').attr('disabled', false);
					}
				}
			}
		});  
	});
	$('#wpcfe-confirm-password').blur(function(){
		var pwd = $('#wpcfe-account-password').val();
		var cpwd = $('#wpcfe-confirm-password').val();
		
		if( pwd != ''){
			if( pwd != cpwd ){
				$('.wpcfe-password-form .alert').remove();
				$('<p class="alert alert-danger" role="alert"><i>Password does not match.</i></p>').insertAfter($('#wpcfe-confirm-password'));
				$('.wpcfe-password-form input[type="submit"]').attr('disabled', true);
			}else{
				$('.wpcfe-password-form .alert').remove();
				$('<p class="alert alert-success" role="alert"><i>Password match.</i></p>').insertAfter($('#wpcfe-confirm-password'));
				$('.wpcfe-password-form input[type="submit"]').attr('disabled', false);
			}
		}
	});
});
