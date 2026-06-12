<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Form_Autofill
 * Encola form-autofill.js con los datos del usuario via wp_localize_script.
 */
class MERC_Form_Autofill {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts(): void {
		if ( ! isset( $_GET['wpcfe'] ) || $_GET['wpcfe'] !== 'add' ) return;

		$user = wp_get_current_user();
		if ( ! $user->ID ) return;

		$uid  = $user->ID;
		$tipo = sanitize_text_field( wp_unslash( $_GET['type'] ?? '' ) );

		wp_enqueue_script(
			'merc-form-autofill',
			MERC_FORM_URL . 'admin/assets/js/form-autofill.js',
			[],
			MERC_FORM_VERSION,
			true
		);

		wp_localize_script( 'merc-form-autofill', 'MercFormAutofill', [
			'tipoEnvio' => $tipo,
			'userData'  => [
				'nombre'    => trim(
					get_user_meta( $uid, 'first_name', true ) . ' ' .
					get_user_meta( $uid, 'last_name',  true )
				),
				'telefono'  => get_user_meta( $uid, 'phone',               true ),
				'distrito'  => get_user_meta( $uid, 'distrito',            true ),
				'direccion' => get_user_meta( $uid, 'billing_address_1',   true ),
				'email'     => get_user_meta( $uid, 'billing_email',       true ) ?: $user->user_email,
				'empresa'   => get_user_meta( $uid, 'billing_company',     true ),
				'link_maps' => get_user_meta( $uid, 'link_maps_remitente', true ),
			],
		] );
	}
}

new MERC_Form_Autofill();

