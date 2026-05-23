<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
    <?php foreach ( $attributes['errors'] as $error ) : ?>
        <p>
            <?php echo $error; ?>
        </p>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$options = get_option('wpcargo_option_settings');
$shipment_logo = isset( $options['settings_shipment_ship_logo'] ) && !empty( $options['settings_shipment_ship_logo'] ) ? $options['settings_shipment_ship_logo'] : "";
$color = isset( $options['wpcargo_base_color'] ) && !empty( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : "#00a924";

?>
<div id="password-lost-form" class="widecolumn">
    <div class = "form-header">
        <h3><?php _e( 'Forgot Your Password?', 'personalize-login' ); ?></h3>
    </div>
    <div class = "form-body">
        <img class = "login-form-logo" src = "<?php echo $shipment_logo; ?>">
        <p>
            <?php
                _e(
                    "Enter your email address and we'll send you a link you can use to pick a new password.",
                    'personalize_login'
                );
            ?>
        </p>

        <form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
            <p class="form-row">
                <input type="text" name="user_login" id="user_login" placeholder = "Enter your Email Address">
            </p>
            <p class="lostpassword-submit">
                <input type="submit" name="submit" class="lostpassword-button"
                    value="<?php _e( 'Reset Password', 'personalize-login' ); ?>"/>
            </p>
        </form>
    </div>
</div>

<style>
    div#password-lost-form {
        max-width: 600px;
        text-align: center;
        margin: 0 auto;
        border: 1px solid rgb(240, 240, 240);
    }

    .form-header {
        background-color: <?php echo $color;?>;
        padding: 10px;
    }

    input.lostpassword-button {
        border: 0;
        padding: 10px;
        background-color: <?php echo $color;?>;
    }

    img.login-form-logo {
        width: 100%;
        max-width: 150px;
    }

    input#user_login {
        width: 70%;
        padding: 10px;
    }
</style>