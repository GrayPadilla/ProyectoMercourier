<?php
class WPCargo_Address_Book_Scripts{
	public $text_domain = 'wpcargo-address-book';
	function __construct(){
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}
	function frontend_scripts() {
		global $post;
		$address_keys		= wpcabook_address_types();
		$address_keys 		= $address_keys ? $address_keys : array();
		$is_create_shipment = false;
		$can_load_autofill_scripts = false;
		$wpcfe = ($_GET['wpcfe'] ?? '') ?: '';
		if( function_exists( 'wpcfe_admin_page' ) && $post && $post->ID == wpcfe_admin_page() && isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'add' ){
			$is_create_shipment = true;
		}

		# load autofill scripts on FM create/update shipment page
		if(function_exists('wpcfe_admin_page') && ($wpcfe === 'add' || $wpcfe === 'update')) {
			$can_load_autofill_scripts = true;
		}

		# load autofill scripts on PQ create quotation page
		if(function_exists('wpcpq_get_frontend_page') && is_page(wpcpq_get_frontend_page())) {
			$can_load_autofill_scripts = true;
		}

		# load autofill scripts on SR create order page
		if(isset( $_POST['wpcsr_create_order_step2'] ) 
		&& wp_verify_nonce( $_POST['wpcsr_create_order_step2'], 'wpcsr_create_order_action' ) 
		&& isset( $_POST['_wpcsr'] ) 
		&& (int)$_POST['_wpcsr']) {
			$can_load_autofill_scripts = true;
		}

		$_can_load_autofill_scripts = apply_filters('wpcab_can_load_autofill_scripts', $can_load_autofill_scripts);

		$is_create_shipment = apply_filters( 'wpcaddress_autocomplete_default_status', $is_create_shipment );
		$minimumInputLength = apply_filters( 'wpcaddress_minimum_input_length', 3 );

		if( $_can_load_autofill_scripts ) {
			// Styles
			wp_enqueue_style( 'address-book-global-styles', WPCARGO_ADDRESS_BOOK_URL .'assets/css/global-styles.css', array(), WPCARGO_ADDRESS_BOOK_VERSION );

			wp_enqueue_script( 'address-book-autofill-scripts', WPCARGO_ADDRESS_BOOK_URL . 'assets/js/autofill-scripts.js', array( 'jquery', 'wpcfe-select2-scripts' ), WPCARGO_ADDRESS_BOOK_VERSION, true );
			
			wp_localize_script( 'address-book-autofill-scripts', 'addressBookAutofillAjaxHandler',
				array( 
					'ajaxurl' 				=> admin_url( 'admin-ajax.php' ),
					'shipperSearchData' 	=> wpcaddress_search_data('shipper'),
					'receiverSearchData' 	=> wpcaddress_search_data('receiver'),
					'addressKeys'			=> $address_keys,
					'addressDefaultShipper'	=> wpcabook_get_default('shipper'),
					'addressDefaultReceiver' => wpcabook_get_default('receiver'),
					'searchLabel' 			=> __( 'Search Address', 'wpcargo-address-book' ),
					'inputTooShort'         => __('Please enter more characters', 'wpcargo-address-book'),
					'inputTooLong'          => __('Please delete some character', 'wpcargo-address-book'),
					'errorLoading'          => __('Error loading results', 'wpcargo-address-book'),
					'loadingMore'           => __('Loading more results', 'wpcargo-address-book'),
					'noResults'             => __('No results found', 'wpcargo-address-book'),
					'searching'             => __('Searching...', 'wpcargo-address-book'),
					'maximumSelected'       => __('Error loading results', 'wpcargo-address-book'),
					'isAdd' 				=> $is_create_shipment,
					'minimumInputLength' 	=> $minimumInputLength,
					'confirmation'			=> __('Are you sure to delete these addresses?', 'wpcargo-address-book'),
					'downloadErrorMessage'  => __('No address selected, Please select at least one address.', 'wpcargo-address-book'),
				)
			);
		}
		// Enqueue scripts for the frontend Compatibility
		if( !empty( $post ) ){
			if( is_a( $post, 'WP_Post' ) && 
				( has_shortcode( $post->post_content, 'wpc-address-book') || has_shortcode( $post->post_content, 'wpc_address_book') )
			) {
				$template = get_page_template_slug( $post->ID );
				if( $template == 'dashboard.php' ){
					$book = isset( $_GET['book'] ) && $_GET['book'] == 'receiver' ? 'receiver' : 'shipper' ;
					// Styles
					wp_enqueue_style( 'address-book-frontend-styles', WPCARGO_ADDRESS_BOOK_URL .'assets/css/frontend-styles.css', array(), WPCARGO_ADDRESS_BOOK_VERSION );
					// Scripts
					wp_enqueue_script( 'address-book-frontend-scripts', WPCARGO_ADDRESS_BOOK_URL . 'assets/js/frontend-scripts.js', array( 'jquery' ), WPCARGO_ADDRESS_BOOK_VERSION, true );
					wp_localize_script( 'address-book-frontend-scripts', 'addressBookFrontendAjaxHandler',
						array( 
							'ajaxurl' 				=> admin_url( 'admin-ajax.php' ),
							'deleteMessage'			=> __('Are you sure you want to delete this Book Address?', 'wpcargo-address-book' ),
							'addressKeys'			=> json_encode($address_keys)
						)
					);
				}
			}
		}
	}
	function admin_scripts(){
		$screen 			= get_current_screen();
		$address_keys		= wpcabook_address_types();
		$address_keys 		= $address_keys ? $address_keys : array();

		wp_enqueue_script( 'jquery' );
		if( $screen->post_type == 'wpcargo_shipment' ){
			// Autofill 
			wp_enqueue_script( 'address-book-autofill-scripts', WPCARGO_ADDRESS_BOOK_URL . 'assets/js/autofill-scripts.js', array( 'jquery', 'wpcargo-select2-js' ), WPCARGO_ADDRESS_BOOK_VERSION, true );
			wp_localize_script( 'address-book-autofill-scripts', 'addressBookAutofillAjaxHandler',
				array( 
					'ajaxurl' 				=> admin_url( 'admin-ajax.php' ),
					'shipperSearchData' 	=> wpcaddress_search_data('shipper'),
					'receiverSearchData' 	=> wpcaddress_search_data('receiver'),
					'addressKeys'			=> $address_keys,
					'addressDefaultShipper'	=> wpcabook_get_default('shipper'),
					'addressDefaultReceiver' => wpcabook_get_default('receiver'),
					'searchLabel' 			=> __( 'Search Address', 'wpcargo-address-book' ),
					'inputTooShort'         => __('Please enter more characters', 'wpcargo-address-book'),
                    'inputTooLong'          => __('Please delete some character', 'wpcargo-address-book'),
                    'errorLoading'          => __('Error loading results', 'wpcargo-address-book'),
                    'loadingMore'           => __('Loading more results', 'wpcargo-address-book'),
                    'noResults'             => __('No results found', 'wpcargo-address-book'),
                    'searching'             => __('Searching...', 'wpcargo-address-book'),
                    'maximumSelected'       => __('Error loading results', 'wpcargo-address-book')
				)
			);
		}
		if( isset($_GET['page']) && 
			( $_GET['page'] == 'ei-address-book' || $_GET['page'] == 'address-book' ) 
		){
			$book = isset( $_GET['book'] ) && $_GET['book'] == 'receiver' ? 'receiver' : 'shipper' ;
			//** Styles
			wp_enqueue_style( 'address-book-styles', WPCARGO_ADDRESS_BOOK_URL .'admin/assets/css/address-book-styles.css' );
			//** Scripts
			wp_enqueue_script( 'address-book-scripts', WPCARGO_ADDRESS_BOOK_URL . 'admin/assets/js/book-address-scripts.js', array( 'jquery' ), WPCARGO_ADDRESS_BOOK_VERSION, true );
			wp_localize_script( 'address-book-scripts', 'AddressBookAjaxHandler',
				array( 
					'ajaxurl' 				=> admin_url( 'admin-ajax.php' ),
					'confirmRepeaterDelete'	=> __( 'Are you sure you want to Cancel Order?', 'wpcargo-address-book' ),
					'confirmMessage' 		=> __( 'Are you sure you want to Cancel Order?', 'wpcargo-address-book' ),
					'cancelQoutationError' 	=> __( 'Something went wrong cannot cancel Quotation, Please contact support.', 'wpcargo-address-book' ),
					'formSubmitError' 		=> __( 'Something went wrong during the process. Please try again later.', 'wpcargo-address-book' ),
					'fromEditTitle' 		=> __( 'Edit Book Address', 'wpcargo-address-book' ).' '.ucfirst($book).' '.__( 'Edit Book Address', 'wpcargo-address-book' ),
					'formAddTitle' 			=> __( 'Add', 'wpcargo-address-book' ).' '.ucfirst($book).' '.__( 'Address', 'wpcargo-address-book' ),
					'bookTitle' 			=> __( 'Book List', 'wpcargo-address-book' ),
					'deleteMessage'			=> __( 'Are you sure you want to delete this Book Address?', 'wpcargo-address-book' ),
					'setAllPublic' 			=> __( 'Are you sure you want to set all in public?', 'wpcargo-address-book' ),
					'unsetAllPublic' 		=> __( 'Are you sure you want to remove all from public?', 'wpcargo-address-book' ),
					'adminUrl' 				=> admin_url( ),
					'includeUrl' 			=> includes_url( ),
					'pluginUrl' 			=> WPCARGO_ADDRESS_BOOK_URL,
					'addressKeys'			=> json_encode($address_keys)
				)
			);
		}
	}
}
new WPCargo_Address_Book_Scripts;