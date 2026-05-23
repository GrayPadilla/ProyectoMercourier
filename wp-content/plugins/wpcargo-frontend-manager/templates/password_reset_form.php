<?php
$options = get_option('wpcargo_option_settings');
$shipment_logo = isset( $options['settings_shipment_ship_logo'] ) && !empty( $options['settings_shipment_ship_logo'] ) ? $options['settings_shipment_ship_logo'] : "";
$color = isset( $options['wpcargo_base_color'] ) && !empty( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : "#00a924";

?>

<div id="password-reset-form" class="widecolumn">
    <div class = "form-header">
        <h3><?php _e( 'Reset Password', 'personalize-login' ); ?></h3>
    </div>

    <div class = "form-body">
        <form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
            <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off" />
            <input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>" />
            
            <?php if ( count( $attributes['errors'] ) > 0 ) : ?>
                <?php foreach ( $attributes['errors'] as $error ) : ?>
                    <p>
                        <?php echo $error; ?>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>
            <img class = "login-form-logo" src = "<?php echo $shipment_logo; ?>">
            <p>
                <input placeholder = "New Password" type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
            </p>
            <p>
                <input placeholder = "Retype Password" type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
            </p>
            
            <p class="description"><?php echo wp_get_password_hint(); ?></p>
            
            <p class="resetpass-submit">
                <input type="submit" name="submit" id="resetpass-button"
                    class="button" value="<?php _e( 'Reset Password', 'personalize-login' ); ?>" />
            </p>
        </form>
    </div>
</div>

<style>
    div#password-reset-form {
        max-width: 600px;
        text-align: center;
        margin: 0 auto;
        border: 1px solid rgb(240, 240, 240);
    }

    .form-header {
        background-color: <?php echo $color;?>;
        padding: 10px;
    }

    input#resetpass-button {
        border: 0;
        padding: 10px;
        background-color: <?php echo $color;?>;
    }

    img.login-form-logo {
        width: 100%;
        max-width: 150px;
    }

    .input {
        width: 75%;
        padding: 10px;
    }

    p.description {
        margin: 10px auto;
        width: 75%;
        line-height: normal;
        font-size: 12px;
        color: #dc3545;
    }
</style>