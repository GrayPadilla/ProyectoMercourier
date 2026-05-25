<?php
function dropzonejs_init() {
}
function wpccf_dropzone_assets_init() {
    add_action( 'wp_enqueue_scripts', 'wpccf_dropzonejs_enqueue_scripts' );
    add_action( 'admin_enqueue_scripts', 'wpccf_dropzonejs_enqueue_scripts' );
    add_filter( 'wpcfe_registered_styles', 'wpccf_dashbaord_styles' );
    add_filter( 'wpcfe_registered_scripts', 'wpccf_dashbaord_scripts' );
}
add_action( 'plugins_loaded', 'wpccf_dropzone_assets_init' );
function wpccf_dropzonejs_enqueue_scripts() {
    if( is_admin(  ) ){
        $current_screen = get_current_screen();
        if( $current_screen && $current_screen->post_type != 'wpcargo_shipment'){
			return false;
		}
    }
    if( ( isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'dashboard' )){
    
        	return false;
    }
    // Styles
    wp_register_style( 'wpccf-dzone-style', WPCARGO_CUSTOM_FIELD_URL.'assets/css/dropzone.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );
    wp_enqueue_style( 'wpccf-dzone-style');
    // Scripts
    wp_register_script( 'wpccf-dzone-script', WPCARGO_CUSTOM_FIELD_URL . 'assets/js/dropzone.stln.js', array(  ), WPCARGO_CUSTOM_FIELD_VERSION,  false );
    wp_register_script( 'wpccf-dzone-custom-script', WPCARGO_CUSTOM_FIELD_URL . 'assets/js/dropzone.custom.js', array( 'wpccf-dzone-script' ), WPCARGO_CUSTOM_FIELD_VERSION,  false );
    wp_localize_script( 'wpccf-dzone-custom-script', 'dzoneAjaxHandler', array( 
        'ajaxurl'               => admin_url( 'admin-ajax.php' ),
        'media_icon'            => includes_url().'images/media/document.png',
        'acceptedFiles'         => implode(', ', wpccf_upload_accepted_filetype() ),
        'maxFileUpload'         => apply_filters( 'wpccf_upload_maxfiles', 1 ),
        'maxFilesize'           => wpccf_upload_maxfilesize_mb(),
        'removeLabel'           => esc_html__( '(remove)', 'wpcargo-custom-field' ),
    ) );
   
    wp_enqueue_script( 'wpccf-dzone-script');
	wp_enqueue_script( 'wpccf-dzone-custom-script');
  
}
// Add template to frontend page
function wpccf_dzone_upload_template(){
    if( is_admin(  ) ){
        $current_screen = get_current_screen();
        if( $current_screen && $current_screen->post_type != 'wpcargo_shipment'){
			return false;
		}
    }
    ob_start();
    ?>
    <!-- Modal -->
    <div id="upload-attachment-modal" class="wpcargo-modal">
        <!-- Modal content -->
        <div class="modal-content">
            <div class="modal-header">
                <span class="close">&times;</span>
            </div>
            <div class="modal-body container">
                <section class="row">
                    <div class="col-md-6 offset-md-3">
                        <form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="wpcneedsclick dz-clickable" id="wpccf-dropzone-form">
                            <?php wp_nonce_field( 'wpccf_attachment_data', 'wpccf_dzone_attachment_nonce_field' ); ?>
                            <div class="dz-message needsclick">
                                <?php esc_html_e( 'Drop files here or click to upload.', 'wpcargo-custom-field' ); ?><br/>
                                <span style="font-size:12px;"><?php esc_html_e( 'Accepted file type : ', 'wpcargo-custom-field' ); ?><?php echo implode(', ', wpccf_upload_accepted_filetype() ); ?><br/>
                                <?php esc_html_e( 'Max Filesize: ', 'wpcargo-custom-field' ); ?><?php echo wpccf_upload_maxfilesize_mb(); ?> <?php esc_html_e( 'MB.', 'wpcargo-custom-field' ); ?></span>
                            </div>
                            <input type='hidden' name='action' value='submit_dropzonejs'>
                        </form>
                    </div>
                </section>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
    <!-- Modal -->
    <?php
    echo ob_get_clean();
}
add_action( 'wp_footer', 'wpccf_dzone_upload_template' );
add_action( 'admin_footer', 'wpccf_dzone_upload_template' );
// AJAX processor
function wpccf_dropzonejs_upload() {
    $uploaded_url   = '';
    $attach_id      = 0;
    $basename       = '';
	if ( !empty( $_FILES ) && wp_verify_nonce( $_REQUEST['wpccf_dzone_attachment_nonce_field'], 'wpccf_attachment_data' ) ) {
        $_filter = true; // For the anonymous filter callback below.
        add_filter( 'upload_dir', function( $arr ) use( &$_filter ){
            if ( $_filter ) {
                $folder = '/wpcargo'; // No trailing slash at the end.
                $arr['path'] .= $folder;
                $arr['url'] .= $folder;
                $arr['subdir'] .= $folder;
            }
            return $arr;
        } );
		$uploaded_bits = wp_upload_bits(
			$_FILES['file']['name'],
			null, //deprecated
            file_get_contents( $_FILES['file']['tmp_name'] ),
            null
        );
		if ( false !== $uploaded_bits['error'] ) {
            return $uploaded_bits['error'];			
        }
		$uploaded_file     = $uploaded_bits['file'];
		$uploaded_url      = $uploaded_bits['url'];
        $uploaded_filetype = wp_check_filetype( basename( $uploaded_bits['file'] ), null );
        $_filter = false;
        // Insert Attachment
        // Get the path to the upload directory.
        $wp_upload_dir = wp_upload_dir();
        $attachment = array(
            'guid'           => $uploaded_url, 
            'post_mime_type' => $uploaded_filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $uploaded_file ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        // Insert the attachment.
        $attach_id = wp_insert_attachment( $attachment, $uploaded_file );
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        if( $attach_id ){
            update_post_meta( $attach_id, 'wpcargo_attachment', 1 );
        }
        $basename =  preg_replace( '/\.[^.]+$/', '', basename( $uploaded_file ) );
        echo json_encode( array( 
            'attactment_id' => $attach_id,
            'basename'      => $basename,
            'image_url'     => $uploaded_url
        ) );
    }
	die();
}
add_action( 'wp_ajax_nopriv_submit_dropzonejs', 'wpccf_dropzonejs_upload' );
add_action( 'wp_ajax_submit_dropzonejs', 'wpccf_dropzonejs_upload' );
// Exclude attachment in the main loop
function wpccf_exclude_wpcargo_attachment( $wp_query_obj ) {
    global $current_user, $pagenow;
    $is_attachment_request = ($wp_query_obj->get('post_type')=='attachment');
    if( !$is_attachment_request ){
        return;
    }
    if( !is_a( $current_user, 'WP_User') ){
        return;
    }
        
    if( !in_array( $pagenow, array( 'upload.php', 'admin-ajax.php' ) ) ){
        return;
    }
    $wp_query_obj->set( 'meta_query', array(
        array(
            'key' => 'wpcargo_attachment', 
            'compare' => 'NOT EXISTS'
        )
    ) );
    return;
}
add_action(	'pre_get_posts', 'wpccf_exclude_wpcargo_attachment' );
// Add Script and Styles in  Frontend amanger dashboard
function wpccf_dashbaord_styles( $styles ){
    $styles[] = 'wpccf-dzone-style';
    return $styles;
}   
function wpccf_dashbaord_scripts( $scripts ){
    $scripts[] = 'wpccf-dzone-script';
    $scripts[] = 'wpccf-dzone-custom-script';
    return $scripts;
}