<?php
if ( is_plugin_active( 'wpcargo-custom-field-addons/wpcargo-custom-field.php' ) ) {
    require_once(WPCARGO_POD_PATH.'templates/wpc-pod-header-cf.tpl.php');
}else{
    require_once(WPCARGO_POD_PATH.'templates/wpc-pod-sign-header.tpl.php');
}