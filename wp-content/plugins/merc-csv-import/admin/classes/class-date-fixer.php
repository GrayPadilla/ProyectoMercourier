<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Date_Fixer
 * 
 * Corrige las fechas de pickup que vienen en formatos mixtos del CSV.
 * El plugin encriptado de import-export puede interpretar mal fechas como 9/3/2026.
 * Esta clase normaliza y GUARDA las fechas en formato DD/MM/YYYY (d/m/Y).
 * Prioridad 5 — al principio, antes de cualquier otro procesamiento
 */
class MERC_Date_Fixer {

	public function __construct() {
		// Desactivado temporalmente: comentar el hook para evitar que el Date Fixer
		// modifique/normalice fechas durante las pruebas de importación.
		// add_action( 'wpcie_after_save_csv_import', [ $this, 'fix_pickup_dates' ], 5, 2 );
	}
	/**
	 * Corrige las fechas de pickup de distintos formatos y las guarda como DD/MM/YYYY
	 * Se ejecuta inmediatamente después de que se guarda el shipment desde CSV
	 */
	public function fix_pickup_dates( int $shipment_id, array $record ): void {
		if ( get_post_type( $shipment_id ) !== 'wpcargo_shipment' ) {
			return;
		}

		// Las claves de meta donde puede estar la fecha de pickup
		$date_meta_keys = [
			'wpcargo_pickup_date_picker',
			'wpcargo_pickup_date',
			'calendarenvio',
			'wpcargo_fecha_envio',
		];

		foreach ( $date_meta_keys as $meta_key ) {
			$date_value = get_post_meta( $shipment_id, $meta_key, true );
			
			if ( empty( $date_value ) ) {
				continue;
			}

			// Parsear la fecha y guardar en formato DD/MM/YYYY (d/m/Y)
			$fixed_date = $this->parse_to_ddmmyyyy( $date_value );
			
			if ( $fixed_date && $fixed_date !== $date_value ) {
				update_post_meta( $shipment_id, $meta_key, $fixed_date );
				error_log( "🔧 MERC_Date_Fixer: Shipment #{$shipment_id} - {$meta_key}: '{$date_value}' → '{$fixed_date}'" );
			}
		}
	}

	/**
	 * Parsea una fecha en varios formatos y devuelve DD/MM/YYYY
	 *
	 * Soporta y normaliza:
	 * - DD/MM/YYYY (español)
	 * - D/M/YYYY (sin ceros)
	 * - YYYY-MM-DD
	 * - DD-MM-YYYY
	 * - YYYY/MM/DD
	 *
	 * @param string $date_str La fecha como string
	 * @return string|false Fecha en formato DD/MM/YYYY o false si es inválida
	 */
	private function parse_to_ddmmyyyy( string $date_str ) {
		if ( empty( $date_str ) ) {
			return false;
		}

		$date_str = trim( $date_str );

		// Intentar parsear como D/M/YYYY (sin ceros)
		$dt = \DateTime::createFromFormat( 'j/n/Y', $date_str );
		if ( $dt ) {
			return $dt->format( 'd/m/Y' );
		}

		// Intentar parsear como DD/MM/YYYY (con ceros)
		$dt = \DateTime::createFromFormat( 'd/m/Y', $date_str );
		if ( $dt ) {
			return $dt->format( 'd/m/Y' );
		}

		// Si viene como YYYY-MM-DD o YYYY/MM/DD, convertir a d/m/Y
		$dt = \DateTime::createFromFormat( 'Y-m-d', $date_str );
		if ( $dt ) {
			return $dt->format( 'd/m/Y' );
		}

		$dt = \DateTime::createFromFormat( 'Y/m/d', $date_str );
		if ( $dt ) {
			return $dt->format( 'd/m/Y' );
		}

		// Intentar parsear guiones (DD-MM-YYYY o D-M-YYYY)
		$dt = \DateTime::createFromFormat( 'd-m-Y', $date_str );
		if ( $dt ) {
			return $dt->format( 'd/m/Y' );
		}

		$dt = \DateTime::createFromFormat( 'j-n-Y', $date_str );
		if ( $dt ) {
			return $dt->format( 'd/m/Y' );
		}

		// No se pudo parsear
		return false;
	}

}

// Instanciación desactivada para pruebas: el Date Fixer queda comentado
// para que las validaciones de fecha del preprocesador no se vean alteradas.
// new MERC_Date_Fixer();



