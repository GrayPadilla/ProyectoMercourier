<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Tipo_Envio_Normalizer
 * Corresponde a STEP 1 + merc_assign_registered_shipper + merc_apply_blocking del functions.php original.
 *
 * - Asigna registered_shipper desde columna CSV (prioridad 2).
 * - Normaliza tipo_envio (prioridad 5).
 * - Protege campos del remitente de sobreescritura con valores vacíos.
 * - Aplica bloqueo por política de tipo_envio (prioridad 35).
 */
class MERC_Tipo_Envio_Normalizer {

	const TIPO_MAP = [
		'normal'            => 'normal',
		'emprendedor'       => 'normal',
		'express'           => 'express',
		'merc agencia'      => 'express',
		'agencia'           => 'express',
		'full fitment'      => 'full_fitment',
		'full'              => 'full_fitment',
		'merc full fitment' => 'full_fitment',
	];

	const SENDER_FIELDS = [
		'tipo_envio', 'wpcargo_shipper_name', 'wpcargo_shipper_phone',
		'wpcargo_shipper_address', 'wpcargo_shipper_email',
		'wpcargo_distrito_recojo', 'wpcargo_brand_name',
		'wpcargo_tiendaname', 'link_maps_remitente',
		'registered_shipper', 'wpcargo_comments',
	];

	public function __construct() {
		// Registrar en guardar metas (antes de save_post)
		add_action( 'added_post_meta', [ $this, 'normalize_on_meta_added' ], 1, 4 );
		add_action( 'updated_post_meta', [ $this, 'normalize_on_meta_updated' ], 1, 4 );
		
		// Registrar en wpcie_after_save_csv_import con prioridades originales
		add_action( 'wpcie_after_save_csv_import', [ $this, 'assign_registered_shipper' ], 2,  2 );
		add_action( 'wpcie_after_save_csv_import', [ $this, 'normalize_tipo_envio' ],      5,  2 );
		add_filter( 'wpcie_after_save_meta_csv_import', [ $this, 'prevent_empty_overwrite' ], 10, 3 );
		add_action( 'wpcie_after_save_csv_import', [ $this, 'apply_blocking' ],            35, 2 );
	}

	/* ── Normalize cuando se agrega/actualiza meta ───────────────────────────────────── */

	public function normalize_on_meta_added( int $meta_id, int $post_id, string $meta_key, $meta_value ): void {
		// Solo durante importación CSV, cuando se agrega wpcargo_service_id o wpcargo_type_of_shipment
		if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) return;
		if ( ! in_array( $meta_key, [ 'wpcargo_service_id', 'wpcargo_type_of_shipment' ], true ) ) return;

		// Si ya tiene tipo_envio normalizado, no hacer nada
		if ( get_post_meta( $post_id, 'tipo_envio', true ) ) return;

		$this->normalize_and_save( $post_id, (string) $meta_value );
	}

	public function normalize_on_meta_updated( int $meta_id, int $post_id, string $meta_key, $meta_value ): void {
		// Solo para wpcargo_shipment
		if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) return;
		if ( ! in_array( $meta_key, [ 'wpcargo_service_id', 'wpcargo_type_of_shipment' ], true ) ) return;

		// Siempre normalizar cuando se actualiza el tipo
		$this->normalize_and_save( $post_id, (string) $meta_value );
	}

	private function normalize_and_save( int $post_id, string $raw ): void {
		if ( empty( $raw ) ) return;

		$key   = strtolower( trim( $raw ) );
		$final = self::TIPO_MAP[ $key ] ?? $key;

		update_post_meta( $post_id, 'tipo_envio',               $final );
		update_post_meta( $post_id, 'wpcargo_type_of_shipment', $final );

		if ( $final === 'express' ) {
			update_post_meta( $post_id, 'wpcargo_status', 'RECEPCIONADO' );
		}
	}

	/* ── Assign registered_shipper ───────────────────────────────────── */

	public function assign_registered_shipper( int $shipment_id, array $record ): void {
		$keys      = array_change_key_case( $record, CASE_LOWER );
		$candidate = trim( (string) ( $keys['registered_shipper'] ?? $keys['registeredshipper'] ?? '' ) );
		if ( $candidate === '' ) return;

		// Only allow registered_shipper when the importer is an admin
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
			error_log( "⚠️ [MERC_CSV] Ignorando registered_shipper para shipment #{$shipment_id} (importador no admin)" );
			return;
		}

		$user = false;
		if ( ctype_digit( $candidate ) )           $user = get_userdata( (int) $candidate );
		if ( ! $user && str_contains( $candidate, '@' ) ) $user = get_user_by( 'email', $candidate );
		if ( ! $user )                             $user = get_user_by( 'login', $candidate );

		if ( $user && $user->ID ) {
			update_post_meta( $shipment_id, 'registered_shipper', $user->ID );
			wp_update_post( [ 'ID' => $shipment_id, 'post_author' => $user->ID ] );
		}
	}

	/* ── Normalize tipo_envio ────────────────────────────────────────── */

	public function normalize_tipo_envio( int $shipment_id, array $record ): void {
		error_log( '╔════════════════════════════════════════════════════════════╗' );
		error_log( '║ [MERC_CSV] STEP 1: NORMALIZAR TIPO DE ENVÍO - INICIO    ║' );
		error_log( '╚════════════════════════════════════════════════════════════╝' );
		error_log( '📦 Shipment ID: ' . $shipment_id );

		// 1) DB first
		$raw = get_post_meta( $shipment_id, 'wpcargo_type_of_shipment', true );
		if ( ! empty( $raw ) ) {
			error_log( '✅ Tipo encontrado en DB: ' . $raw );
		} else {
			error_log( '🔍 Tipo vacío en DB, buscando en CSV...' );
			
			// 2) CSV: buscar en MÚLTIPLES posibles columnas (TODO tipo_envio)
			$csv_keys = [
				'tipo_envio', 'tipo de envio', 'tipo_de_envio',
				'wpcargo_type_of_shipment', 'type_of_shipment', 'tipo_envio_normalizado',
				'wpcargo_service_id', 'service_id', 'service', 'shipment_type',
				'tipo', 'tipo_servicio', 'tipo_paquete', 'tipo_envío',
				'type', 'delivery_type', 'shipment_service', 'service_type',
			];

			// Normalizar claves del record a minúsculas para búsqueda insensible a mayúsculas
			$record_lower = array_change_key_case( $record, CASE_LOWER );

			foreach ( $csv_keys as $key ) {
				$key_lower = strtolower( $key );
				if ( isset( $record_lower[ $key_lower ] ) && ! empty( $record_lower[ $key_lower ] ) ) {
					$raw = (string) $record_lower[ $key_lower ];
					error_log( '✅ Tipo encontrado en CSV (columna: ' . $key . '): ' . $raw );
					break;
				}
			}
		}

		// 3) Fallback: usuario importador
		if ( empty( $raw ) ) {
			error_log( '⚠️  Tipo vacío en CSV, buscando fallback del usuario...' );
			$uid = get_current_user_id();
			$raw = $uid ? (string) get_user_meta( $uid, 'tipo_envio_preferido', true ) : '';
			if ( ! empty( $raw ) ) {
				error_log( '✅ Tipo preferido del usuario #' . $uid . ': ' . $raw );
			}
		}

		if ( empty( $raw ) ) {
			error_log( '❌ Tipo de envío NO encontrado en DB, CSV ni perfil - ABORTANDO' );
			error_log( '✅ [MERC_CSV] STEP 1: NORMALIZAR - COMPLETADO ✓' );
			error_log( '' );
			return;
		}

		$key   = strtolower( trim( $raw ) );
		$final = self::TIPO_MAP[ $key ] ?? $key;

		error_log( '🔤 Normalizado: "' . $key . '" → "' . $final . '"' );

		update_post_meta( $shipment_id, 'tipo_envio',               $final );
		update_post_meta( $shipment_id, 'wpcargo_type_of_shipment', $final );
		error_log( '💾 Metas guaradas - tipo_envio & wpcargo_type_of_shipment: ' . $final );

		if ( $final === 'express' ) {
			update_post_meta( $shipment_id, 'wpcargo_status', 'RECEPCIONADO' );
			error_log( '✅ Estado establecido a RECEPCIONADO para tipo EXPRESS' );
		}

		error_log( '✅ [MERC_CSV] STEP 1: NORMALIZAR - COMPLETADO ✓' );
		error_log( '' );
	}

	/* ── Proteger campos vacíos ──────────────────────────────────────── */

	public function prevent_empty_overwrite( $shipment_id, string $meta_key, $value ) {
		if ( in_array( $meta_key, self::SENDER_FIELDS, true ) && empty( $value ) ) {
			if ( ! empty( get_post_meta( $shipment_id, $meta_key, true ) ) ) {
				return null; // no sobreescribir
			}
		}
		return null;
	}

	/* ── Apply blocking ──────────────────────────────────────────────── */

	public function apply_blocking( int $shipment_id, array $record ): void {
		if ( ! function_exists( 'merc_check_tipo_envio_blocked' ) ) return;

		$shipper    = (int) get_post_meta( $shipment_id, 'registered_shipper', true );
		$client_id  = $shipper ?: get_current_user_id();
		if ( ! $client_id ) return;

		$tipo = get_post_meta( $shipment_id, 'tipo_envio', true )
		     ?: get_post_meta( $shipment_id, 'wpcargo_type_of_shipment', true );
		if ( empty( $tipo ) ) return;

		if ( merc_check_tipo_envio_blocked( $client_id, $tipo ) ) {
			wp_update_post( [ 'ID' => $shipment_id, 'post_status' => 'draft' ] );
			update_post_meta( $shipment_id, 'merc_import_blocked',        'blocked_by_policy' );
			update_post_meta( $shipment_id, 'merc_import_blocked_client', $client_id );
			update_post_meta( $shipment_id, 'merc_import_blocked_tipo',   $tipo );
			return;
		}

		// Aplicar fecha forzada si existe (pero IGNORAR si quien importa es administrador)
		$forced_date = get_user_meta( $client_id, 'merc_force_pickup_date', true );
		if ( $forced_date ) {
			// Si el usuario que ejecuta la importación es administrador, no aplicar la fecha forzada
			if ( function_exists('current_user_can') && ( current_user_can('manage_options') || current_user_can('administrator') ) ) {
				error_log( '[MERC_CSV] IMPORTADOR ADMIN - Ignorando merc_force_pickup_date para shipment ' . $shipment_id . ' (usuario importador es admin)' );
			} else {
				// Normalizar formato de fecha antes de persistir (para evitar 6/4/2026 vs 06/04/2026)
				$normalized_forced = $forced_date;
				if ( class_exists( 'MERC_CSV_Preprocessor' ) && method_exists( 'MERC_CSV_Preprocessor', 'parse_possibly_formatted_date' ) ) {
					$dt = MERC_CSV_Preprocessor::parse_possibly_formatted_date( (string) $forced_date );
					if ( $dt instanceof \DateTime ) {
						$normalized_forced = $dt->format('d/m/Y');
					}
				} else {
					if ( is_string( $forced_date ) && preg_match('/^(\d{1,2})\s*[\/\-\s]\s*(\d{1,2})\s*[\/\-\s]\s*(\d{2,4})$/', trim( $forced_date ), $m) ) {
						$dd = str_pad( $m[1], 2, '0', STR_PAD_LEFT );
						$mm = str_pad( $m[2], 2, '0', STR_PAD_LEFT );
						$yy = $m[3]; if ( strlen( $yy ) === 2 ) $yy = '20' . $yy;
						$normalized_forced = sprintf('%s/%s/%s', $dd, $mm, $yy);
					}
				}

				update_post_meta( $shipment_id, 'wpcargo_pickup_date_picker', $normalized_forced );
				update_post_meta( $shipment_id, 'wpcargo_pickup_date',        $normalized_forced );
			}
		}
	}
}

new MERC_Tipo_Envio_Normalizer();



