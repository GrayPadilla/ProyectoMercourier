jQuery(document).ready(function($){
    var adminURL =  wpcfeAjaxhandler.adminURL;
    const downloadErrorMessage = wpcfeAjaxhandler.downloadErrorMessage
    const downloadFileErrorMessage = wpcfeAjaxhandler.downloadFileErrorMessage
    $(".wpcfe-select").select2({
        placeholder: wpcfeAjaxhandler.optionPlaceholder,
        allowClear: true
    });
    // Reset Shipment Number sequence
    $('.admin_page_wpcfe-settings').on('click', '#wpcfe-reset_nsequence', function(e){
        e.preventDefault();
        const currElem = $(this);
        $.ajax({
            type:"POST",
            datatype: 'JSON',
            data:{ action  : 'reset_nsequence' },
            url : wpcfeAjaxhandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcargo-loading').remove();
                if( response.status == 'error' ){
                    alert( response.message );
                    return false
                }
                currElem.closest( 'table' ).find('input[name="wpcfe_nsequence_start"]').val('');
                currElem.closest( 'td' ).append(
                    `<p class="wpcfe-nsequence_notification alert alert-success" style="font-style: italic; padding: 6px; margin: 0;">${response.message}</p>`
                );
                currElem.remove();  
            }
        });
    });
    // Select All Checkboxes for shipments
    // Print Shipment 
    $('.wpcfe-download-waybill').on('change', '#wpcfe-bulkprint', function( e ){
        const currEl    = $(this);
        const printType = currEl.val();
        const shipments = $('#the-list .check-column input[type="checkbox"]:checked').length;
        if( printType === '' ){
            return;
        }
        let selectedShipment = [];
        if( shipments > 0 ){
            $('#the-list .check-column input[type="checkbox"]:checked').each( function(){ //iterate all listed checkbox items
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
                    $('body').append('<div class="wpcargo-loading">Loading...</div>');
                },
                success:function( response ){
                    currEl.val('');
                    $('body .wpcargo-loading').remove();
                    $data = JSON.parse(response);
                    if($.isEmptyObject($data)) {
                        alert( downloadFileErrorMessage );
                        return;
                    } else {
                        $('#the-list .check-column input[type="checkbox"]').each( function(){ //iterate all listed checkbox items
                            this.checked = false; //change ".checkbox" checked status
                        });
                        download_file( $data.file_url, $data.file_name );
                        return;
                    }
                }
            });   	
        }else{
            currEl.val('');
            alert( downloadErrorMessage );
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
});