<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Financial_Import
 * Corresponde a merc_save_financial_data_on_import + merc_get_district_prices
 * + merc_find_district_price del functions.php original.
 *
 * Prioridad 25 — después de sync_monto (20).
 */
class MERC_Financial_Import {

	/* ── Tarifas por distrito ────────────────────────────────────────── */

	const PRICES_EXPRESS = [
		'El Agustino'                                       => 8.00,
		'San Juan de Lurigancho'                            => 8.00,
		'Santa Anita'                                       => 8.00,
		'Ate - Salamanca - Vitarte'                         => 10.00,
		'La Molina'                                         => 8.00,
		'Santa Clara'                                       => 10.00,
		'Huaycan - Gloria Grande - Pariachi'                => 12.00,
		'Molina Alta (Musa - Portada del Sol - Planicie)'   => 10.00,
		'Huachipa (Zoológico de Huachipa)'                  => 10.00,
		'Callao'                                            => 8.00,
		'Bellavista'                                        => 8.00,
		'La Punta - Callao'                                 => 10.00,
		'La Perla'                                          => 8.00,
		'Pueblo Libre'                                      => 8.00,
		'Lima Cercado'                                      => 8.00,
		'Breña'                                             => 8.00,
		'San Miguel'                                        => 8.00,
		'Magdalena'                                         => 8.00,
		'Sarita Colonia (Comisaría Sarita Colonia)'         => 8.00,
		'Carmen de la Legua'                                => 8.00,
		'Rímac'                                             => 8.00,
		'Independencia'                                     => 8.00,
		'Comas'                                             => 8.00,
		'Carabayllo'                                        => 10.00,
		'Puente Piedra'                                     => 10.00,
		'Ventanilla'                                        => 10.00,
		'Los Olivos'                                        => 8.00,
		'San Martin de Porres'                              => 8.00,
		'Santiago de Surco'                                 => 8.00,
		'San Juan de Miraflores'                            => 8.00,
		'Villa María del Triunfo'                           => 10.00,
		'Villa El Salvador'                                 => 10.00,
		'Chorrillos'                                        => 8.00,
		'Barranco'                                          => 8.00,
		'Miraflores'                                        => 10.00,
		'Jesús María'                                       => 8.00,
		'Lince'                                             => 8.00,
		'La Victoria'                                       => 8.00,
		'San Isidro'                                        => 8.00,
		'Surquillo'                                         => 8.00,
		'San Borja'                                         => 8.00,
		'San Luis'                                          => 8.00,
		'Centro de Lima'                                    => 8.00,
	];

	const PRICES_NORMAL = [
		'El Agustino'                                       => 10.00,
		'San Juan de Lurigancho'                            => 10.00,
		'Santa Anita'                                       => 10.00,
		'Ate - Salamanca - Vitarte'                         => 10.00,
		'La Molina'                                         => 10.00,
		'Santa Clara'                                       => 12.00,
		'Huaycan - Gloria Grande - Pariachi'                => 14.00,
		'Molina Alta (Musa - Portada del Sol - Planicie)'   => 12.00,
		'Huachipa (Zoológico de Huachipa)'                  => 12.00,
		'Callao'                                            => 10.00,
		'Bellavista'                                        => 10.00,
		'La Punta - Callao'                                 => 12.00,
		'La Perla'                                          => 10.00,
		'Pueblo Libre'                                      => 10.00,
		'Lima Cercado'                                      => 10.00,
		'Breña'                                             => 10.00,
		'San Miguel'                                        => 10.00,
		'Magdalena'                                         => 10.00,
		'Sarita Colonia (Comisaría Sarita Colonia)'         => 10.00,
		'Carmen de la Legua'                                => 10.00,
		'Rímac'                                             => 10.00,
		'Independencia'                                     => 10.00,
		'Comas'                                             => 10.00,
		'Carabayllo'                                        => 13.00,
		'Puente Piedra'                                     => 13.00,
		'Ventanilla'                                        => 13.00,
		'Los Olivos'                                        => 10.00,
		'San Martin de Porres'                              => 10.00,
		'Santiago de Surco'                                 => 10.00,
		'San Juan de Miraflores'                            => 10.00,
		'Villa María del Triunfo'                           => 12.00,
		'Villa El Salvador'                                 => 12.00,
		'Chorrillos'                                        => 10.00,
		'Barranco'                                          => 10.00,
		'Miraflores'                                        => 8.00,
		'Jesús María'                                       => 10.00,
		'Lince'                                             => 10.00,
		'La Victoria'                                       => 10.00,
		'San Isidro'                                        => 10.00,
		'Surquillo'                                         => 10.00,
		'San Borja'                                         => 10.00,
		'San Luis'                                          => 10.00,
		'Centro de Lima'                                    => 10.00,
	];

	public function __construct() {
		add_action( 'wpcie_after_save_csv_import', [ $this, 'save_financial_data' ], 25, 2 );
	}

	/* ── Guardar datos financieros ───────────────────────────────────── */

	public function save_financial_data( int $shipment_id, array $record ): void {
		if ( get_post_type( $shipment_id ) !== 'wpcargo_shipment' ) return;

		// ── Modo no-cobrar ────────────────────────────────────────────
		$es_no_cobrar = false;
		if ( isset( $record['payment_wpcargo_mode_field'] ) ) {
			$modo = strtolower( trim( $record['payment_wpcargo_mode_field'] ) );
			if ( str_contains( $modo, 'no cobrar' ) || $modo === 'no charge' || $modo === '0' ) {
				$es_no_cobrar = true;
				update_post_meta( $shipment_id, 'wpcargo_monto', 0 );
			}
		}

		// ── Costo de envío por distrito ───────────────────────────────
		$costo_keys = [ 'wpcargo_costo_envio', 'costo_envio', 'costo_shipping', 'shipping_cost', 'cost', 'envio' ];
		$costo_found = false;
		foreach ( $costo_keys as $k ) {
			if ( ! empty( $record[ $k ] ) ) {
				update_post_meta( $shipment_id, 'wpcargo_costo_envio', sanitize_text_field( $record[ $k ] ) );
				$costo_found = true;
				break;
			}
		}

		// Auto-calcular por distrito si no viene en el CSV
		if ( ! $costo_found && empty( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) ) ) {
			$distrito = get_post_meta( $shipment_id, 'wpcargo_distrito_destino', true )
			         ?: ( $record['wpcargo_distrito_destino'] ?? '' );
			if ( ! empty( $distrito ) ) {
				$tipo  = get_post_meta( $shipment_id, 'tipo_envio', true ) ?: 'normal';
				$costo = $this->find_district_price( $distrito, $tipo );
				if ( $costo > 0 ) {
					update_post_meta( $shipment_id, 'wpcargo_costo_envio', $costo );
				}
			}
		}

		// ── Costo producto ────────────────────────────────────────────
		foreach ( [ 'wpcargo_costo_producto', 'costo_producto', 'product_cost', 'cost_product', 'precio_producto' ] as $k ) {
			if ( ! empty( $record[ $k ] ) ) {
				update_post_meta( $shipment_id, 'wpcargo_costo_producto', sanitize_text_field( $record[ $k ] ) );
				break;
			}
		}

		// ── Cargo remitente ───────────────────────────────────────────
		foreach ( [ 'wpcargo_cargo_remitente', 'cargo_remitente', 'sender_charge', 'cargo' ] as $k ) {
			if ( ! empty( $record[ $k ] ) ) {
				update_post_meta( $shipment_id, 'wpcargo_cargo_remitente', sanitize_text_field( $record[ $k ] ) );
				break;
			}
		}

		// ── Comentarios ───────────────────────────────────────────────
		if ( ! empty( $record['wpcargo_comments'] ) ) {
			update_post_meta( $shipment_id, 'wpcargo_comments', sanitize_textarea_field( $record['wpcargo_comments'] ) );
		}

		// ── Cambio de producto ────────────────────────────────────────
		foreach ( [ 'cambio_producto', 'cambio de producto', 'product_change', 'productchange', 'wpcargo_cambio_producto' ] as $k ) {
			if ( ! empty( $record[ $k ] ) ) {
				$v = strtolower( $record[ $k ] );
				$norm = in_array( $v, [ 'sí', 'si', 'yes', 'y', '1', 'true' ], true ) ? 'Sí' : 'No';
				update_post_meta( $shipment_id, 'cambio_producto', $norm );
				break;
			}
		}

		// ── Estados de pago ───────────────────────────────────────────
		$monto = floatval( get_post_meta( $shipment_id, 'wpcargo_total_cobrar', true ) )
		      ?: floatval( get_post_meta( $shipment_id, 'wpcargo_monto',        true ) );

		update_post_meta( $shipment_id, 'wpcargo_quien_paga',             'remitente' );
		update_post_meta( $shipment_id, 'wpcargo_cobrado_por_motorizado', $monto > 0 ? $monto : '0' );

		if ( ! get_post_meta( $shipment_id, 'wpcargo_estado_pago_motorizado', true ) ) {
			update_post_meta( $shipment_id, 'wpcargo_estado_pago_motorizado', 'pendiente' );
		}
		if ( ! get_post_meta( $shipment_id, 'wpcargo_cliente_pago_a', true ) ) {
			update_post_meta( $shipment_id, 'wpcargo_cliente_pago_a', 'pendiente' );
		}
	}

	/* ── Helpers de tarifa ───────────────────────────────────────────── */

	public static function get_prices( string $tipo ): array {
		return $tipo === 'express' ? self::PRICES_EXPRESS : self::PRICES_NORMAL;
	}

	public static function find_district_price( string $destination, string $tipo = 'normal' ): float {
		$prices = self::get_prices( $tipo );
		$dest   = trim( $destination );

		// Exacto
		if ( isset( $prices[ $dest ] ) ) return $prices[ $dest ];

		// Por nombre principal (antes del paréntesis)
		foreach ( $prices as $district => $price ) {
			$main = trim( explode( '(', $district )[0] );
			if ( strcasecmp( $main, $dest ) === 0 ) return $price;
		}

		// Parcial
		$dest_lower = strtolower( $dest );
		foreach ( $prices as $district => $price ) {
			$d = strtolower( $district );
			if ( str_contains( $d, $dest_lower ) || str_contains( $dest_lower, $d ) ) return $price;
		}

		return 0.00;
	}
}

new MERC_Financial_Import();

// ── Funciones globales para compatibilidad con código existente ───────────
if ( ! function_exists( 'merc_get_district_prices' ) ) {
	function merc_get_district_prices( string $tipo = 'normal' ): array {
		return MERC_Financial_Import::get_prices( $tipo );
	}
}
if ( ! function_exists( 'merc_find_district_price' ) ) {
	function merc_find_district_price( string $destination, string $tipo = 'normal' ): float {
		return MERC_Financial_Import::find_district_price( $destination, $tipo );
	}
}

