jQuery(document).ready(function($){
	// Download Shipment Documents (Woocommerce)
    $('.print-shipment').on('click', '.shipment-checkout', function(e){
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
            url : nonwpcfeAjaxhandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcargo-loading').remove();
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
});
