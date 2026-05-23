jQuery(document).ready(function($) {
    let recordCounter = 0;
    const downloadFile =  function(fileURL, fileName) {
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

    if ($.isFunction( $.fn.pickadate )) {
		// Get the elements
		var from_input = $('#wpcsc-import-export-content #startingDate').pickadate({
			format: 'yyyy-mm-dd',
		}),
		from_picker = from_input.pickadate('picker');
		var to_input = $('#wpcsc-import-export-content #endingDate').pickadate({
			format: 'yyyy-mm-dd',
		}),
		to_picker = to_input.pickadate('picker');
		
		if( from_picker && to_picker ){

			// Check if there’s a “from” or “to” date to start with and if so, set their appropriate properties.
			if (from_picker.get('value')) {
				to_picker.set('min', from_picker.get('select'))
			}
			if (to_picker.get('value')) {
				from_picker.set('max', to_picker.get('select'))
			}
			
			// Apply event listeners in case of setting new “from” / “to” limits to have them update on the other end. If ‘clear’ button is pressed, reset the value.
			from_picker.on('set', function (event) {
				if (event.select) {
					to_picker.set('min', from_picker.get('select'))
				} else if ('clear' in event) {
					to_picker.set('min', false)
				}
			});
			to_picker.on('set', function (event) {
				if (event.select) {
					from_picker.set('max', to_picker.get('select'))
				} else if ('clear' in event) {
					from_picker.set('max', false)
				}
			});
		}
	}
    // Export Shipment Container
    $('#wpcsc-import-export-content').on('submit', '#wpcsc-export-form', function( e ){
        e.preventDefault();
        $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
        const formData = $(this).serializeArray();
        $.ajax({
            type:"POST",
            data:{
                action:'wpcsc_export',  
                nonce: shipmentContainerAjaxHandler.nonce,  
                formData:formData,
            },
            url : shipmentContainerAjaxHandler.ajaxurl,
            beforeSend:function(){
                //** Proccessing
                $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html(`<section class="alert alert-info text-center">${shipmentContainerAjaxHandler.processExport}</section>`);
            },
            success:function(response){
                if( response.status == 'error'){
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html(`<section class="alert alert-danger text-center">${response.message}</section>`)
                }else{
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html(`<section class="alert alert-success text-center">${response.message}</section>`)
                    downloadFile( response.file.file_url, response.file.file_name );
                } 
                setTimeout( function(){
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
                }, 3000 );
            }
        });
    });
    // Import Shipment Container
    $('#wpcsc-import-export-content').on('click', '#wpcsc_download-csv-template', function(e){
        e.preventDefault();
        $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
        $.ajax({
            type:"POST",
            data:{
                action:'wpcsc_download_template',  
            },
            url : shipmentContainerAjaxHandler.ajaxurl,
            beforeSend:function(){
                //** Proccessing
                $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html(`<section class="alert alert-info text-center">${shipmentContainerAjaxHandler.downloadTemplate}</section>`)
            },
            success:function(response){
                if( response.status == 'error'){
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html(`<section class="alert alert-danger text-center">${response.message}</section>`)
                }else{
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html(`<section class="alert alert-success text-center">${response.message}</section>`)
                    downloadFile( response.file.file_url, response.file.file_name );
                } 
                setTimeout( function(){
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
                }, 3000 );
            }
        });
    });

    $('#wpcsc-import-export-content').on('submit', '#wpcsc-import-form', function( e ){
        e.preventDefault();
        const currForm = $(this);
        $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
        $.ajax({
            url : shipmentContainerAjaxHandler.ajaxurl,
            type: "POST",
            data:  new FormData(this),
            contentType: false,
            cache: false,
            processData:false,
            beforeSend: function() {   
                recordCounter = 0; 
                $('#wpcsc-import-export-content').find('#wpcscie-form_notification').append( `<div id="tc-import-result" class="container mt-4 p-2" style="max-height: 260px; overflow-y: scroll;color: #383d41; background-color: #e2e3e5; border-color: #d6d8db;"><p >${shipmentContainerAjaxHandler.uploadingFile}...</p></div>`);
            },
            success: function (response) {
                if( response.status == 'success' ){
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification #tc-import-result').append( `<p>${response.message}</p><ol class="outbound_po-list"></ol><p class="processing finish">${shipmentContainerAjaxHandler.processingData}... </p>`);
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').append( '<div id="import_loading_wapper"><div id="loading_percentage"><p style="color: #721c24;padding: 0 12px;"></p></div></div>' );
                    var records     = response.data;
                    var recordCount = records.length;
                    for( let i = 0; i < records.length; i++ ){
                        save_records( records[i], recordCount );
                    }  
                }else{
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').html('');
                    $('#wpcsc-import-export-content').find('#wpcscie-form_notification').prepend(`<div class="alert alert-danger">${response.message}</div>`);
                }
                currForm.find('[name="uploadedfile"]').val('');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log( textStatus, errorThrown );
            }
        });
    });

    async function save_records( record, recordCount ){     
        const result = await $.ajax({
          url : shipmentContainerAjaxHandler.ajaxurl,
          type: "post",
          data: {
            action : 'wpcsc_save_records',
            record : record,
          },
          beforeSend: function() {
            console.log( record );
          },
          success: function (response) {
            recordCounter ++;
            const loadPercent = Math.ceil( ( recordCounter / recordCount ) * 100 );
            $('#wpcsc-import-export-content').find( '#tc-import-result .outbound_po-list' ).append('<li>'+response.message+'</li>');  
            $('#wpcsc-import-export-content').find( '#loading_percentage' ).css("width", loadPercent + "%");  
            $('#wpcsc-import-export-content').find( '#loading_percentage p' ).text( loadPercent + "%");  
            if( recordCount == recordCounter ){
              $('#wpcsc-import-export-content').find( '#tc-import-result .processing' ).remove();
              $('#wpcsc-import-export-content').find( '#tc-import-result' ).append(`<p class="finish">+++++++++++++++++++++++++++++ ${shipmentContainerAjaxHandler.processingCompleted} +++++++++++++++++++++++++++++ </p>`);
            }
          },
          error: function(jqXHR, textStatus, errorThrown) {
            recordCounter ++;
            const loadPercent = Math.ceil( ( recordCounter / recordCount ) * 100 );
            $('#wpcsc-import-export-content').find( '#loading_percentage' ).css("width", loadPercent + "%");  
            $('#wpcsc-import-export-content').find( '#loading_percentage p' ).text( loadPercent + "%");  
            $('#wpcsc-import-export-content').find( '#tc-import-result .outbound_po-list' ).append('<li style="color: #ff0000;"> PO No. '+record._container_number+' failed. Server error 502</li>');  
            console.log( textStatus, errorThrown, errorThrown.status );
          }
        });
        return result;
    }

});