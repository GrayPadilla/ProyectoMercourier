<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class WPCFE_Registration_Address{
    function  init(){
        if( !empty( wpcfe_registration_address_fields() ) ){
            add_action( 'wpcfe_registration_address', array( $this, 'display_template' ) );
        }      
    }
    function display_template(){
        wpcfe_get_template('registration-address', wpcfe_registration_address_fields() );
    }
}