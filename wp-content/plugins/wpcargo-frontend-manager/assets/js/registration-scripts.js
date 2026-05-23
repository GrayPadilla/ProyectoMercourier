jQuery(document).ready( function( $ ){
    const baseCountry   = wpcfeRegistrationAjaxhandler.baseCountry;
    const ajaxURL       = wpcfeRegistrationAjaxhandler.ajaxurl;
    const selectType    = wpcfeRegistrationAjaxhandler.selectType;
    const formElem      = $('form#wpcfeRegistrationForm');
    const populateStateField = ( state ) => {
        if( state ){
            formElem.find('.billing_state-form-group').append( 
                `<select id="billing_state" class="form-control browser-default" name="billing_state"><option value="">${selectType}</option></select>`
             );
            return;
        }
        formElem.find('.billing_state-form-group').append( `<input id="billing_state" class="form-control " type="text" name="billing_state" value="">` );
        return;
    }
    const onCountryChange = () =>{
        formElem.find('[name="billing_country"]').on('change', function(){
            const selCountry = $(this).val();
            $.ajax({
                type:"POST",
                datatype: "json",
                data:{
                    action:'wpcfe_get_option_states',    
                    selCountry:selCountry,
                },
                url : ajaxURL,
                beforeSend:function(){
                    //** Proccessing
                    formElem.find('[name="billing_state"]').remove();
                    $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                },
                success:function(data){
                    $('body .wpcfe-spinner').remove();     
                    populateStateField( Object.keys(data).length );
                    setTimeout( function(){
                        if( Object.keys(data).length ){
                            $.each( data, function( index, value ){
                                formElem.find('[name="billing_state"]').append( $('<option>', {
                                    value: value, text: value
                                }));
                            });
                        }  
                    }, 10 );
                            
                }
            });
        });
    }
    const stateOptionAutoPopulate = () => {
        if( !formElem.length ){
            return false;
        }
        if( baseCountry ){
            formElem.find('[name="billing_country"]').val(baseCountry);
        }
    }
    stateOptionAutoPopulate();
    onCountryChange();
});