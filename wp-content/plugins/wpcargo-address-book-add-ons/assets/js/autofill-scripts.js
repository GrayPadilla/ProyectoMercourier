jQuery(document).ready(function($) {
    const shipperSearchMeta   = addressBookAutofillAjaxHandler.shipperSearchData.meta_key;
    const receiverSearchMeta  = addressBookAutofillAjaxHandler.receiverSearchData.meta_key;
    const defaultShipper      = addressBookAutofillAjaxHandler.addressDefaultShipper;
    const defaultReceiver     = addressBookAutofillAjaxHandler.addressDefaultReceiver;
    const addressMeta         = addressBookAutofillAjaxHandler.addressKeys;
    const isAdd               = addressBookAutofillAjaxHandler.isAdd;
    const minimumInputLength  = addressBookAutofillAjaxHandler.minimumInputLength;
    const confirmation        = addressBookAutofillAjaxHandler.confirmation;
    const downloadErrorMessage = addressBookAutofillAjaxHandler.downloadErrorMessage;
    let searchMeta            = null;

    function fillFields( data ){
        $.each( data, function( index, value ){
            const addressType = addressMeta.find(element => element == index );
            const isObjValue  = typeof value === 'object';
            // Check if the field type is Address
            if( addressType && isObjValue && Object.keys(value).length ){
                for (const [key, fvalue] of Object.entries(value)) {
                    if( $('[name="'+index+'['+key+']"]').length ){
                        $('[name="'+index+'['+key+']"]').val( fvalue );
                    }
                }
            }
            // Normal Fields
            if( $('[name="'+index+'"]').length ){
                $('[name="'+index+'"]').val( value );
            }
        });
    }
    // Check if has data
    function autofillDefaultAddress(){

        if( !isAdd ){
            return false;
        }
        // Shipper Autofill default address
        const shipperMetakeys = Object.keys(defaultShipper)
        if( shipperMetakeys.length !== 0 && defaultShipper.constructor === Object ){
            const isNewForm = shipperMetakeys.find( element => {
                return $( `[name="${element}"]` ).length > 0 && !$( `[name="${element}"]` ).val();
            } );
            if( isNewForm ){
                fillFields( defaultShipper );
            }
        }
        // Receiver Autofill default address
        const receiverMetakeys = Object.keys(defaultReceiver)
        if( receiverMetakeys.length !== 0 && defaultReceiver.constructor === Object ){
            const isNewForm = receiverMetakeys.find( element => {
                return $( `[name="${element}"]` ).length > 0 && !$( `[name="${element}"]` ).val();
            } );
            if( isNewForm ){
                fillFields( defaultReceiver );
            }
        }
    }
    autofillDefaultAddress();
    $('.wpc_addressbook_autofill').each(function() {
        var placeholder = $(this).data('placeholder') || 'Select Option';
        $(this).select2({
            ajax: {
                url: addressBookAutofillAjaxHandler.ajaxurl, // AJAX URL is predefined in WordPress admin
                dataType: 'json',
                delay: 250, // delay in ms while typing when to perform a AJAX search
                data: function (params) {
                    searchMeta = $(this).attr('data-type');
                    return {
                        q: params.term, // search query
                        filter: $(this).attr('data-type'),
                        action: 'get_address_list' // AJAX action for admin-ajax.php
                    };
                },
                processResults: function( data ) {
                    const metakey = searchMeta == 'shipper' ? shipperSearchMeta : receiverSearchMeta;
                    var options = [];
                    if ( data ) {
                        // data is the array of arrays, and each of them contains ID and the Label of the option
                        $.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
                            options.push( { id: text[metakey], text: text[metakey], data:text  } );
                        });

                    }
                    return {
                        results: options
                    };
                },
                cache: true
            },
            width: '360',
            placeholder:placeholder,
            minimumInputLength: minimumInputLength,
            allowClear: true,
            language: {
                inputTooShort: function(args) {
                    return addressBookAutofillAjaxHandler.inputTooShort;
                },
                inputTooLong: function(args) {
                    return addressBookAutofillAjaxHandler.inputTooLong;
                },
                errorLoading: function() {
                    return addressBookAutofillAjaxHandler.errorLoading;
                },
                loadingMore: function() {
                    return addressBookAutofillAjaxHandler.loadingMore;
                },
                noResults: function() {
                    return addressBookAutofillAjaxHandler.noResults;
                },
                searching: function() {
                    return addressBookAutofillAjaxHandler.searching;
                },
                maximumSelected: function(args) {
                    return addressBookAutofillAjaxHandler.maximumSelected;
                }
            }
        });   
    });
    $('.wpc_addressbook_autofill').on('select2:select', function (e) {
        const parentElem = $(this).closest( '.wpcabook-autofill-wrapper').parent();
        var data = e.params.data.data;
        $.each( data, function( index, value ){
            const addressType = addressMeta.find(element => element == index );
            const isObjValue  = typeof value === 'object';
            // Check if the field type is Address
            if( addressType && isObjValue && Object.keys(value).length ){
                for (const [key, fvalue] of Object.entries(value)) {
                    if( parentElem.find('[name="'+index+'['+key+']"]').length ){
                        parentElem.find('[name="'+index+'['+key+']"]').val( fvalue );
                    }
                }
            }
            // Normal Fields
            if( parentElem.find('[name="'+index+'"]').length ){
                parentElem.find('[name="'+index+'"]').val( value );
            }
        });
    });
    $('.wpc_addressbook_autofill').on('select2:clear', function (e) {
        const parentElem = $(this).closest( '.wpcabook-autofill-wrapper').parent();
        parentElem.find('input[type="text"], input[type="email"], input[type="number"], input[type="password"], select, textarea').val('');
    });

    /************************************************
     * AJAX Scrips for the Assigned Address Book
    ********************************************* */
   $('._assigned_to').select2({
    ajax: {
        url: addressBookAutofillAjaxHandler.ajaxurl,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term, // search term
            // page: params.page
            action:'get_all_users',
          };
        },
        processResults: function (data) {
          // parse the results into the format expected by Select2
          // since we are using custom formatting functions we do not need to
          // alter the remote JSON data, except to indicate that infinite
          // scrolling can be used
        //   params.page = params.page || 1;
    
            var options = [];
            if ( data ) {
                // data is the array of arrays, and each of them contains ID and the Label of the option
                $.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
                    options.push( { id: text.ID, text: text.display_name, data:text.ID  } );
                });

            }
            return {
                results: options
            };
            
        },
        cache: true
      },
        placeholder: 'Select User',
        minimumInputLength: minimumInputLength,
        width:'360',
        allowClear: true,
        language: {
            inputTooShort: function(args) {
                return addressBookAutofillAjaxHandler.inputTooShort;
            },
            inputTooLong: function(args) {
                return addressBookAutofillAjaxHandler.inputTooLong;
            },
            errorLoading: function() {
                return addressBookAutofillAjaxHandler.errorLoading;
            },
            loadingMore: function() {
                return addressBookAutofillAjaxHandler.loadingMore;
            },
            noResults: function() {
                return addressBookAutofillAjaxHandler.noResults;
            },
            searching: function() {
                return addressBookAutofillAjaxHandler.searching;
            },
            maximumSelected: function(args) {
                return addressBookAutofillAjaxHandler.maximumSelected;
            }
        }
   });

   //SELECT ALL ADDRESS
   $("#wpcfe-all-address").change( function(){  //"select all" change
        var status = this.checked; // "select all" checked status      
        $('.wpcfe-address').each( function(){ //iterate all listed checkbox items
            this.checked = status; //change ".checkbox" checked status
        });
    });

    $("#address-list").on('change', '.wpcfe-address', function(){
        //uncheck "select all", if one of the listed checkbox item is unchecked
        if(this.checked == false){ //if this item is unchecked
            $("#wpcfe-all-address")[0].checked = false; //change "select all" checked status to false
        }
        //check "select all" if all checkbox items are checked
        if ($('#address-list .wpcfe-address:checked').length == $('.wpcfe-address').length ){
            $("#wpcfe-all-address")[0].checked = true; //change "select all" checked status to true
        }
    });


    //Bulk Delete Address
    $('#address-list').on( 'click', '.remove-address', function(e){
        e.preventDefault();
        let addresses       = $('#address-list .wpcfe-address:checked').length;
        let selectedAddress = [];

        if( addresses > 0){
            let confirmed = confirm( confirmation );
            if( confirmed ){
                $('.wpcfe-address:checked').each( function(){ //iterate all listed checkbox items
                    selectedAddress.push( $(this).val() );
                });

                //#pass the selected address for deletion
                $.ajax({
                    type:"POST",
                    datatype:'json',
                    data:{
                        action  : 'bulk_delete_address',    
                        selectedAddress   : selectedAddress
                    },
                    url : addressBookAutofillAjaxHandler.ajaxurl,
                    beforeSend:function(){
                        $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                    },
                    success:function( data ){
                        const {status, message} = data
                        if( status === 'error'){
                            alert(message);
                            location.reload();
                        }else{
                            alert(message);
                            location.reload();
                        }
                    }
                });
            }
        }else{
            alert(downloadErrorMessage);
            location.reload();
            return;
        }
    });


   //#AJAX Script for select address import/export
   $('.user_address').select2({
    ajax: {
            url: addressBookAutofillAjaxHandler.ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
            return {
                q: params.term, // search term
                // page: params.page
                action:'get_all_users',
            };
            },
            processResults: function (data) {        
                var options = [];
                if ( data ) {
                    // data is the array of arrays, and each of them contains ID and the Label of the option
                    $.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
                        options.push( { id: text.ID, text: text.display_name, data:text.ID  } );
                    });

                }
                return {
                    results: options
                };
                
            },
            cache: true
        },
            placeholder: 'Select User',
            minimumInputLength: minimumInputLength,
            width:'450',
            allowClear: true,
            language: {
                inputTooShort: function(args) {
                    return addressBookAutofillAjaxHandler.inputTooShort;
                },
                inputTooLong: function(args) {
                    return addressBookAutofillAjaxHandler.inputTooLong;
                },
                errorLoading: function() {
                    return addressBookAutofillAjaxHandler.errorLoading;
                },
                loadingMore: function() {
                    return addressBookAutofillAjaxHandler.loadingMore;
                },
                noResults: function() {
                    return addressBookAutofillAjaxHandler.noResults;
                },
                searching: function() {
                    return addressBookAutofillAjaxHandler.searching;
                },
                maximumSelected: function(args) {
                    return addressBookAutofillAjaxHandler.maximumSelected;
                }
        }
   });

   //Import/Export Address Book Datepicker
   let wpcfeAddressDatepicker = $('.wpcfe-adrress-datepicker');
   if(wpcfeAddressDatepicker.length > 0){
    wpcfeAddressDatepicker.pickadate({
        format: 'yyyy-mm-dd',
    });
   }

    //#AJAX SCRIPT FOR EXPORT USER ADDRESSBOOK
    $('#wpcfe-export-address-book').on('submit', function(e){
        e.preventDefault();
        let formData = $(this).serializeArray();

        $.ajax({
            type:"POST",
            datatype:'json',
            data:{
                action  : 'export_address_book',    
                formData   : formData
            },
            url : addressBookAutofillAjaxHandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcfe-spinner">Loading...</div>');
            },
            success:function( data ){
            }
        });
    });

});