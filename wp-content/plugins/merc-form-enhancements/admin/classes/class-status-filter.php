<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Status_Filter
 * Encola status-filter.js con el mapa de estados via wp_localize_script.
 */
class MERC_Status_Filter {

	const STATUS_MAP = [
		'agencia'     => [ 'RECEPCIONADO','LISTO PARA SALIR','NO CONTESTA','EN RUTA','ENTREGADO','NO RECIBIDO','REPROGRAMADO','ANULADO' ],
		'emprendedor' => [ 'PENDIENTE','RECOGIDO','NO RECOGIDO','EN BASE MERCOURIER','LISTO PARA SALIR','NO CONTESTA','EN RUTA','ENTREGADO','REPROGRAMADO','NO RECIBIDO','ANULADO' ],
		'fullfitment' => [ 'RECEPCIONADO','LISTO PARA SALIR','NO CONTESTA','EN RUTA','ENTREGADO','NO RECIBIDO','REPROGRAMADO','ANULADO' ],
	];

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts(): void {
		$user      = wp_get_current_user();
		$is_driver = in_array( 'wpcargo_driver', (array) $user->roles, true );

		wp_enqueue_script(
			'merc-status-filter',
			MERC_FORM_URL . 'admin/assets/js/status-filter.js',
			[],
			MERC_FORM_VERSION,
			true
		);

		wp_localize_script( 'merc-status-filter', 'MercStatusFilter', [
			'isDriver'  => $is_driver,
			'statusMap' => self::STATUS_MAP,
		] );
	}
}

new MERC_Status_Filter();

