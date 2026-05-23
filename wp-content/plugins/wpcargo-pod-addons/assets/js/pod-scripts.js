jQuery(document).ready(function($) {
	var podCustomMeta = wpcargoPODAJAXHandler.custom_meta;
	if( $('#shipment-list .wpcpod-sign_data').length > 0 ){
		$('#shipment-list').find('.wpcpod-sign_data').removeClass('hide-me');
	}
	$('#shipment-list').on('click', '.show-signaturepad', function(){
		var shipmentID = $(this).attr('data-id');
		$.ajax({
            type:"POST",
            data:{
                action  : 'show_signaturepad',    
                sid   : shipmentID
            },
            url : wpcargoPODAJAXHandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcargo-loading').remove();
	            $('#wpc_pod_signature-modal .modal-body').html(response);
            }
        });
    })
    // POD Sign Submit
	$('#wpc_pod_signature-modal').on('submit', '#wpc_pod_signature-form', function( e ){
		e.preventDefault();
		const formData = $(this).serializeArray();
		$.ajax({
			type: "POST",
			datatype: 'JSON',
			url: wpcargoPODAJAXHandler.ajaxurl,
			data:{
				action: 'pod_signed',
				nonce:  wpcargoPODAJAXHandler.sign_nonce,
				formData: formData
			},
			beforeSend:function(){
				$('body').append('<div class="wpcargo-loading">Loading...</div>');
			},
			success:function(response){
				$('body .wpcargo-loading').remove();
				if( response.status == 'error' ){
					alert(response.message);
					return;
				}
				window.location.reload();
			}
		});
	});

    //prevent disabling scroll in modal popup
    if( $('#wpc_pod_signature-modal').length ){
        $('body').click( function(){
            $(this).find('.media-modal').click( function(){
                setTimeout( function(){ $('.wpcargo-dashboard').addClass('modal-open');}, 300);
            });
        });
    }    
	// Export report Script
	$('#wpcpod-export').on('submit', function( e ){
		e.preventDefault();
		var driverID 	= $('#wpcpod-export #assign_driver').val();
		var status 		= $('#wpcpod-export #shipment_status').val();
		var dateFrom 	= $('#wpcpod-export #date_from').val();
		var dateTo 		= $('#wpcpod-export #date_to').val();
		$.ajax({
			type: "POST",
			url: wpcargoPODAJAXHandler.ajaxurl,
			data:{
				action: 'wpcpod_generate_report',
				driverID: driverID,
				status : status,
				dateFrom: dateFrom,
				dateTo : dateTo
			},
			beforeSend:function(){
				$('body').append('<div class="wpcargo-loading">Loading...</div>');
				$('#wpcpod-export-progress').html('');
			},
			success:function(response){
				if( response.rows == 0 ){
					$('#wpcpod-export-progress').prepend('<div class="alert alert-danger text-center">'+response.message+'</div>');
				}else{
					$('#wpcpod-export-progress').prepend('<div class="alert alert-success text-center">'+response.message+'</div>');
					download_file( response.file_url, response.file_name);
					setTimeout(function(){
						$('#wpcpod-export-progress').html('');
					}, 3000 );
				}
				$('body .wpcargo-loading').remove();
			}
		});
	});
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
});