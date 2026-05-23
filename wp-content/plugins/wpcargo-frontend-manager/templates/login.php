<?php 
// Evitar duplicación de login usando nonce único por request
static $wpcfe_login_loaded = false;

if ( $wpcfe_login_loaded ) {
	return; // Ya fue renderizado, salir
}

$wpcfe_login_loaded = true;
?>
<div class="row">
	<div class="col-md-4 offset-md-4">
		<!-- Material form login -->
		<?php $user_name 	= ( !empty( $_POST ) && array_key_exists( 'billing_email', $_POST  ) ) ? $_POST['billing_email'] : '' ; ?>
		<?php if( isset( $_GET['login'] ) && $_GET['login'] == 'failed' ): ?>
			<?php $user_name 	= isset( $_GET['user'] ) ? $_GET['user'] : '' ; ?>
			<div class="alert alert-danger" role="alert">
				<span><b><?php esc_html_e( 'Error', 'wpcargo-frontend-manager' ); ?> - </b> <?php echo apply_filters( 'wpcfe_login_error', esc_html__( 'Please check your Username or Password.', 'wpcargo-frontend-manager' ) ); ?></span>
			</div>
		<?php endif; ?>
		<div class="card">
			<h5 class="card-header primary-color-dark darken-2 white-text text-center py-4">
				<strong><?php esc_html_e( 'Sign in', 'wpcargo-frontend-manager' ); ?></strong>
			</h5>
			<!--Card content-->
			<div class="card-body px-lg-5 pt-0">
				<div class="my-2 text-center">
					<?php $site_logo = $wpcargo->logo ? '<img style="width:160px;" src="'.$wpcargo->logo.'" alt="Site Logo">' : '<h1 class="h3">'.get_bloginfo( 'name' ).'</h1>' ; ?>
					<a href="<?php echo get_bloginfo( 'url' ); ?>"><?php echo $site_logo; ?></a>
				</div>
				<?php do_action( 'wpcfe_before_login_form' ); ?>
				<!-- Form -->
				<form name="loginform" id="loginform" action="<?php echo site_url( '/wp-login.php' ); ?>" method="post">
					<!-- Email -->
					<div class="md-form login-username">
						<label class="form-check-label" for="user_login"><?php esc_html_e( 'Username/E-mail', 'wpcargo-frontend-manager' ); ?></label>
						<input id="user_login" class="form-control border-input" type="text" size="20" value="<?php echo $user_name; ?>" name="log" required="required">
					</div>
					<!-- Password -->
					<div class="md-form login-password">
						<label class="form-check-label" for="user_pass"><?php esc_html_e( 'Password', 'wpcargo-frontend-manager' ); ?></label>
						<input id="user_pass" class="form-control border-input" type="password" size="20" value="" name="pwd" required="required">
					</div>
					<?php if( has_action('register_form') ): ?>
					<div class="col-lg-12 p-0">
						<?php do_action( 'register_form' ); ?>
					</div>
					<?php endif ?>
					<div class="d-flex justify-content-around">
						<div>
							<!-- Remember me -->
							<div class="form-check">
								<input name="rememberme" type="checkbox" id="rememberme" class="form-check-input" value="forever">
								<label class="form-check-label" for="rememberme"><?php esc_html_e( 'Remember me', 'wpcargo-frontend-manager' ); ?></label>
							</div>
						</div>
						<div>
							<a href="<?php echo wp_lostpassword_url( $redirect_to ); ?>"><?php esc_html_e( 'Forgot password?', 'wpcargo-frontend-manager' ); ?></a>
						</div>
					</div>
					<div class="md-form login-submit">
						<input type="hidden" value="<?php echo esc_attr( apply_filters( 'wpcfe_login_redirect', $redirect_to ) ); ?>" name="redirect_to">
						<p class="text-center small text-muted mb-3">
							Al usar el servicio acepto los <a href="https://www.mercourier.com/terminos" target="_blank" rel="noopener noreferrer">Términos y Condiciones</a>
						</p>
						<button id="wp-submit" class="btn btn-outline-primary btn-rounded btn-block my-4 waves-effect z-depth-0" type="submit" name="wp-submit"><?php esc_html_e('Login', 'wpcargo-frontend-manager' ); ?></button>
					</div>
				</form>
				<!-- Form -->
				<?php do_action( 'wpcfe_after_login_form' ); ?>				
			</div>
		</div>
		<!-- Material form login -->
	</div>
</div>
