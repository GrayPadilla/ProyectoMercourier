jQuery(document).ready(function($) {
	const bulkErrorMessage = wpcajaxReceiving.bulkErrorMessage;
	const siteUrl 		   = wpcajaxReceiving.siteUrl;
	const ajaxUrl 		   = wpcajaxReceiving.wpc_ajax_receiving_url;
	const receivingForm    = document.getElementById('wpc-receiving');

	$('#wpc-receiving').on('submit', function( e ) {

		if (receivingForm.action.includes('cannot-add') || receivingForm.action.includes('can-add')) {
            e.preventDefault();
		    var formFields = $(this).serializeArray();
		    $.ajax({
				type:"POST",
				dataType: 'JSON',
				data:{
					action : 'wpcreceive_shipment',    
					formFields : formFields
				},
				url : ajaxUrl,
				beforeSend:function(){
					$(".wpc-receiver-notif").fadeIn();
					$(".wpc-receiver-notif").html(`<img src="${siteUrl}/wp-admin/images/spinner-2x.gif" />`);
				},
				success:function(data){
					$('#wpc-tracking-number').val('');
					$('body .wpcfe-spinner').remove();
					if( wpcajaxReceiving.enableBeepSound ){
						play_scanning_sound();
					}
					if ($('#clear-fields').is(":checked")){
						$('#wpc-receiving .form-control').each( function(){
							if( $(this).hasClass('wpc-receiving-date') || $(this).hasClass('wpc-receiving-time') ){
								return;
							}
							$(this).val('');
						});
					}

                    if (data.notifaction == '<p class="wpc-receiver-error">Shipment Not Found!</p>' && receivingForm.action.includes('can-add')) {

                        receivingForm.action = '';
						$('#wpc-receiving').submit();

					}
					
					$(".wpc-receiver-notif img").remove();
					$(".wpc-receiver-notif").html(data.notifaction);
					$(".wpc-receiver-notif").addClass( data.class );
					setTimeout(function(){
						$(".wpc-receiver-notif").fadeOut("slow");
						$(".wpc-receiver-notif").removeClass( data.class );
					}, 2000);
				}
			});

		} else {
			receivingForm.action = 'can-add';
		}

	});
	
	$('#wpc-tracking-number').bind("paste", function(){
		setTimeout( function(){
			if (receivingForm.action.includes('cannot-add')) {
                $('#wpc-receiving').submit();
			}
		}, 10 )
	});
	$('#wpc-tracking-number').keypress(function(e) {
		if(e.which == 13) {
			setTimeout( function(){
				$('#wpc-receiving').submit();
			}, 10 );	
		}
	});

	function play_scanning_sound(){
		var audioElement = document.createElement('audio');
		audioElement.setAttribute('src', wpcajaxReceiving.beepSoundSrc);
		audioElement.play();
	}
	
	$('#shipments-table-list').on('click', '.bulk-barcode-scan', function(e){
        e.preventDefault();
        var shipments 			= $('#shipment-list .wpcfe-shipments:checked').length;
        var selectedShipment 	= [];
        if( shipments > 0 ){
			$('.wpcfe-shipments:checked').each( function(){
                selectedShipment.push( $(this).val() );
            });
			$('#bulk-receiver .shipments-to-scan').val(selectedShipment.join(','));
        }else{
            alert( bulkErrorMessage );
			setTimeout( function(){
				$('body').find("#wpcr-bulk-barcode-modal").removeClass('show').css({"display":"none"});;
			}, 10);	
            return;
        }
    });
	$('#bulk-receiver').on('click', '.submit-shipment-scan', function(e){
        e.preventDefault();
		var receiverFields = [];
		var selectedShipment = $('#bulk-receiver .shipments-to-scan').val();
		$('#bulk-receiver .form-control').each( function(){
			var name = $(this).attr('name');
			var value = $(this).val();
			receiverFields.push({index:name, val:value});
		});
		$.ajax({
			type:"POST",
			data:{
				action : 'wpcr_bulk_update',    
				selectedShipment : selectedShipment,
				receiverFields : receiverFields
			},
			url : wpcajaxReceiving.wpc_ajax_receiving_url,
			beforeSend:function(){
				$('body').append('<div class="wpcfe-spinner">Loading...</div>');
			},
			success:function( response ){
				$('#bulk-receiver .message').html( response );
				setTimeout(function(){
					window.location.reload();
				}, 2000);
			}
		});
	});
});