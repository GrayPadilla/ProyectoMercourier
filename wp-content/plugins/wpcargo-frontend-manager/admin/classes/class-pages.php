<?php
class WPCFE_Admin{
	public static function add_wpcfe_custom_pages() {
		$wpcfe_admin =  get_option( 'wpcfe_admin' );
		if ( get_page_by_path('dashboard') == NULL && !$wpcfe_admin ) {
			$dashboard_args    = array(
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_author' => 1,
				'post_date' => date('Y-m-d H:i:s'),
				'post_name' => 'dashboard',
				'post_status' => 'publish',
				'post_title' => 'Dashboard',
				'post_type' => 'page',
			);
			$dashboard = wp_insert_post( $dashboard_args, false );
			update_option( 'wpcfe_admin', $dashboard );
			update_post_meta( $dashboard, '_wp_page_template', 'dashboard.php' );
		}
	}
}

class WPC_Custom_ForgotPassword{
	function __construct(){
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_shortcode( 'custom-forgot-password-form', array( $this, 'render_password_lost_form' ) );
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 );

		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
		add_shortcode( 'custom-reset-password-form', array( $this, 'render_password_reset_form' ) );
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
	}

	//# Activation Hook for change password
	public static function plugin_activated() {
		// Information needed for creating the plugin's pages 
		$page_definitions = array(
			'member-forgot-password' => array(
				'title' => __( 'Forgot Password?', WPCFE_TEXTDOMAIN ),
				'content' => '[custom-forgot-password-form]'
			),
			'member-reset-password' => array(
				'title' => __( 'Set a New Password', WPCFE_TEXTDOMAIN ),
				'content' => '[custom-reset-password-form]'
			),
		);
		foreach ( $page_definitions as $slug => $page ) {
			// Check that the page doesn't exist already 
			$query = new WP_Query( 'pagename=' . $slug );
			if ( ! $query->have_posts() ) {
				// Add the page using the data from the array above 
				wp_insert_post(
					array(
						'post_content'   => $page['content'],
						'post_name'      => $slug,
						'post_title'     => $page['title'],
						'post_status'    => 'publish',
						'post_type'      => 'page',
						'ping_status'    => 'closed',
						'comment_status' => 'closed',
					)
				);
			}
		}
	}

	public function redirect_to_custom_lostpassword() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}
			wp_redirect( home_url( 'member-forgot-password' ) );
			exit;
		}
	}

	public function render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode attributes 
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'personalize-login' );
		} else {
			// Retrieve possible errors from request parameters 
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );
				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->get_error_message( $error_code );
				}
			}
			return $this->get_template_html( 'password_lost_form', $attributes );
		}
	}

	private function get_template_html( $template_name, $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}
		ob_start();
		do_action( 'personalize_login_before_' . $template_name );
		require( WPCFE_PATH.'templates/' . $template_name . '.php');
		do_action( 'personalize_login_after_' . $template_name );
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	public function do_password_lost() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$errors = retrieve_password();
			if ( is_wp_error( $errors ) ) {
				// Errors found 
				$redirect_url = home_url( 'member-forgot-password' );
				$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
			} else {
				// Email sent 
				// Check if the user just requested a new password 
				$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';
				$redirect_url = get_the_permalink( wpcfe_admin_page() );
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
			}
			wp_redirect( $redirect_url );
			exit;
		}
	}

	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			case 'empty_username':
				return __( 'You do have an email address, right?', 'personalize-login' );
			case 'empty_password':
				return __( 'You need to enter a password to login.', 'personalize-login' );
			case 'invalid_username':
				return __(
					"We don't have any users with that email address. Maybe you used a different one when signing up?",
					'personalize-login'
				);
			case 'incorrect_password':
				$err = __(
					"The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?",
					'personalize-login'
				);
				return sprintf( $err, wp_lostpassword_url() );
			case 'empty_username':
				return __( 'You need to enter your email address to continue.', 'personalize-login' );
			case 'invalid_email':
			case 'invalidcombo':
				return __( 'There are no users registered with this email address.', 'personalize-login' );
			default:
				break;
		}
		
		return __( 'An unknown error occurred. Please try again later.', 'personalize-login' );
	}

	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		$site = get_site_url();
		// Create new message 
		$msg  = __( 'Hello!', 'personalize-login' ) . "\r\n\r\n";
		$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s ', 'personalize-login' ), $user_login ) . "\r\n\r\n";
		$msg .= sprintf( __( 'at %s.', 'personalize-login' ), $site ) . "\r\n\r\n";
		$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'personalize-login' ) . "\r\n\r\n";
		$msg .= __( 'To reset your password, visit the following address:', 'personalize-login' ) . "\r\n\r\n";
		$msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
		$msg .= __( 'Thanks!', 'personalize-login' ) . "\r\n";
		return $msg;
	}

	public function redirect_to_custom_password_reset() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// Verify key / login combo 
			$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( home_url( 'dashboard?login=expiredkey' ) );
				} else {
					wp_redirect( home_url( 'dashboard?login=invalidkey' ) );
				}
				exit;
			}
			$redirect_url = home_url( 'member-reset-password' );
			$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );
			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode attributes 
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'personalize-login' );
		} else {
			if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = $_REQUEST['login'];
				$attributes['key'] = $_REQUEST['key'];
				// Error messages 
				$errors = array();
				if ( isset( $_REQUEST['error'] ) ) {
					$error_codes = explode( ',', $_REQUEST['error'] );
					foreach ( $error_codes as $code ) {
						$errors []= $this->get_error_message( $code );
					}
				}
				$attributes['errors'] = $errors;
				return $this->get_template_html( 'password_reset_form', $attributes );
			} else {
				return __( 'Invalid password reset link.', 'personalize-login' );
			}
		}
	}

	public function do_password_reset() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$rp_key = $_REQUEST['rp_key'];
			$rp_login = $_REQUEST['rp_login'];
			$user = check_password_reset_key( $rp_key, $rp_login );
			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( home_url( 'dashboard?login=expiredkey' ) );
				} else {
					wp_redirect( home_url( 'dashboard?login=invalidkey' ) );
				}
				exit;
			}
			if ( isset( $_POST['pass1'] ) ) {
				if ( $_POST['pass1'] != $_POST['pass2'] ) {
					// Passwords don't match 
					$redirect_url = home_url( 'member-reset-password' );
					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );
					wp_redirect( $redirect_url );
					exit;
				}
				if ( empty( $_POST['pass1'] ) ) {
					// Password is empty 
					$redirect_url = home_url( 'member-reset-password' );
					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );
					wp_redirect( $redirect_url );
					exit;
				}
				// Parameter checks OK, reset password 
				reset_password( $user, $_POST['pass1'] );
				wp_redirect( home_url( 'dashboard?password=changed' ) );
			} else {
				echo "Invalid request.";
			}
			exit;
		}
	}
}

$forgotPass = new WPC_Custom_ForgotPassword();

