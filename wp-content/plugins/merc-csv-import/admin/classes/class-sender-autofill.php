<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Sender_Autofill
 * Corresponde a STEP 2 + STEP 4 + STEP 5 + sync_monto del functions.php original.
 *
 * - Auto-rellena datos del remitente desde el perfil del usuario (STEP 2).
 * - Auto-asigna contenedor por distrito (STEP 4).
 * - Auto-asigna motorizado (STEP 5).
 * - Sincroniza monto wpcargo_total_cobrar desde el CSV.
 */
class MERC_Sender_Autofill {

	public function __construct() {
		add_action( 'wpcie_after_save_csv_import', [ $this, 'auto_fill_sender' ],    8,  2 );
		// Asegurar que el distrito de destino venga guardado antes de la auto-asignación
		add_action( 'wpcie_after_save_csv_import', [ $this, 'ensure_destination_district' ], 10, 2 );
		add_action( 'wpcie_after_save_csv_import', [ $this, 'sync_monto' ],          20, 2 );
		add_action( 'wpcie_after_save_csv_import', [ $this, 'auto_assign_container' ], 18, 2 );
		add_action( 'wpcie_after_save_csv_import', [ $this, 'auto_assign_motorizado' ], 30, 2 );

		// Legacy stub — mantenido para compatibilidad con importadores que usaban el hook antiguo
		add_action( 'wpcie_after_save_csv_import', '__return_null', 10, 2 );
	}

    

	/* ── STEP 2: Auto-fill remitente ─────────────────────────────────── */

	public function auto_fill_sender( int $shipment_id, array $record ): void {
		$current_user = wp_get_current_user();
		if ( ! $current_user || ! $current_user->ID ) return;

		// Usar registered_shipper si ya fue asignado, si no el usuario que importa
		$shipper_meta = get_post_meta( $shipment_id, 'registered_shipper', true );
		$client_id    = ! empty( $shipper_meta ) ? (int) $shipper_meta : (int) $current_user->ID;

		$client = get_user_by( 'ID', $client_id );
		if ( ! $client ) return;

		$shipper_name        = get_user_meta( $client_id, 'billing_company',       true );
		$shipper_phone       = get_user_meta( $client_id, 'phone',                 true );
		$shipper_address     = get_user_meta( $client_id, 'billing_address_1',     true );
		$shipper_email       = get_user_meta( $client_id, 'billing_email',         true );
		$distrito_recojo     = get_user_meta( $client_id, 'distrito',              true );
		$link_maps_remitente = get_user_meta( $client_id, 'link_maps_remitente',   true );
		$tiendaname          = get_user_meta( $client_id, 'wpcargo_tiendaname',    true );
		$brand_name          = get_user_meta( $client_id, 'wpcargo_brand_name',    true );
		$comments_default    = get_user_meta( $client_id, 'wpcargo_comments_default', true );

		// Fallbacks
		if ( empty( $shipper_name ) )  $shipper_name  = $client->display_name;
		if ( empty( $shipper_email ) ) $shipper_email = $client->user_email;
		if ( empty( $tiendaname ) )    $tiendaname    = $shipper_name;
		if ( empty( $brand_name ) )    $brand_name    = $shipper_name;

		$metas = [
			'wpcargo_shipper_name'    => $shipper_name,
			'wpcargo_shipper_phone'   => $shipper_phone,
			'wpcargo_shipper_address' => $shipper_address,
			'wpcargo_shipper_email'   => $shipper_email,
			'wpcargo_distrito_recojo' => $distrito_recojo,
			'link_maps_remitente'     => $link_maps_remitente,
			'wpcargo_tiendaname'      => $tiendaname,
			'registered_shipper'      => $client_id,
			'wpcargo_comments'        => $comments_default,
		];

		foreach ( $metas as $key => $value ) {
			update_post_meta( $shipment_id, $key, $value );
		}
	}

	/* ── Sync monto ──────────────────────────────────────────────────── */

	public function sync_monto( int $shipment_id, array $record ): void {
		$monto = 0.0;

		// Buscar en metas ya guardadas
		foreach ( [ 'wpcargo_monto', 'monto', 'amount', 'price', 'total', 'cobrar', 'pagar', 'wpcargo_price', 'wpcargo_amount' ] as $k ) {
			$v = floatval( get_post_meta( $shipment_id, $k, true ) );
			if ( $v > 0 ) { $monto = $v; break; }
		}

		// Buscar en record CSV si no se encontró
		if ( $monto <= 0 && ! empty( $record ) ) {
			foreach ( [ 'monto','moonto','amount','price','total','cobrar','pagar','wpcargo_monto','wpcargo_price','wpcargo_amount','product_price','shipping_price','total_price','valor' ] as $k ) {
				if ( isset( $record[ $k ] ) ) {
					$v = floatval( $record[ $k ] );
					if ( $v > 0 ) {
						$monto = $v;
						if ( ! get_post_meta( $shipment_id, 'wpcargo_monto', true ) ) {
							update_post_meta( $shipment_id, 'wpcargo_monto', $monto );
						}
						break;
					}
				}
			}
		}

		// Guardar en wpcargo_total_cobrar si aún no tiene valor
		if ( $monto > 0 && floatval( get_post_meta( $shipment_id, 'wpcargo_total_cobrar', true ) ) <= 0 ) {
			update_post_meta( $shipment_id, 'wpcargo_total_cobrar', $monto );
		}
	}

	/**
	 * Asegura que el meta `wpcargo_distrito_destino` se guarde desde el CSV
	 * antes de que se ejecute la auto-asignación de contenedores.
	 */
	public function ensure_destination_district( int $shipment_id, array $record ): void {
		// Si ya existe, no hacemos nada
		$current = get_post_meta( $shipment_id, 'wpcargo_distrito_destino', true );
		if ( ! empty( $current ) ) return;

		// Posibles claves en el CSV que contienen el distrito destino
		$keys = [ 'wpcargo_distrito_destino', 'distrito_destino', 'destination_district', 'district', 'distrito', 'wpcargo_distrito' ];
		$found = '';
		foreach ( $keys as $k ) {
			if ( isset( $record[ $k ] ) && ! empty( $record[ $k ] ) ) {
				$found = sanitize_text_field( $record[ $k ] );
				break;
			}
		}

		// Intento adicional: buscar cualquier columna cuyo nombre sugiera 'distrito' o 'destino'
		if ( empty( $found ) && ! empty( $record ) ) {
			foreach ( $record as $k => $v ) {
				$lk = strtolower( trim( (string) $k ) );
				if ( (strpos( $lk, 'distrito' ) !== false || strpos( $lk, 'district' ) !== false || strpos( $lk, 'destino' ) !== false) && ! empty( $v ) ) {
					$found = sanitize_text_field( $v );
					break;
				}
			}
		}

		if ( ! empty( $found ) ) {
			update_post_meta( $shipment_id, 'wpcargo_distrito_destino', $found );
		}
	}

	/* ── STEP 4: Auto-asignar contenedor por distrito ────────────────── */

	public function auto_assign_container( int $shipment_id, array $record ): void {
		if ( function_exists( 'merc_auto_assign_shipment_to_container' ) ) {
			merc_auto_assign_shipment_to_container( $shipment_id );
		}
	}

	/* ── STEP 5: Auto-asignar motorizado ─────────────────────────────── */

	public function auto_assign_motorizado( int $shipment_id, array $record ): void {
		// Verificar que sea wpcargo_shipment
		$post_type = get_post_type( $shipment_id );
		if ( $post_type !== 'wpcargo_shipment' ) {
			error_log( "   ⏭️  Post type incorrecto: $post_type (esperado: wpcargo_shipment)" );
			return;
		}
		error_log( "   ✅ Post type correcto: wpcargo_shipment" );

		// Obtener tipo de envío (garantizado que existe en este punto)
		$tipo_envio = get_post_meta( $shipment_id, 'tipo_envio', true );
		error_log( "   📦 Tipo de envío: " . ( $tipo_envio ?: 'VACÍO' ) );

		// Solo auto-asignar a envíos tipo 'normal' (emprendedor normalizado)
		if ( $tipo_envio !== 'normal' ) {
			error_log( "   ⏭️  Tipo de envío no es 'normal' ($tipo_envio), no asignar motorizado" );
			return;
		}
		error_log( "   ✅ Tipo apropiado para auto-asignación" );

		// Obtener el usuario (remitente) del envío
		$user_id = get_post_meta( $shipment_id, 'registered_shipper', true );
		error_log( "   👤 Usuario (registered_shipper): " . ( $user_id ?: 'VACÍO' ) );

		if ( ! $user_id ) {
			error_log( "   ❌ Sin usuario (registered_shipper no guardado), abortando" );
			return;
		}

		// Obtener el motorizado default del usuario
		$motorizado_default = get_user_meta( $user_id, 'merc_motorizo_recojo_default', true );
		error_log( "   🔍 Motorizado default para usuario #$user_id: " . ( $motorizado_default ?: 'VACÍO' ) );

		if ( empty( $motorizado_default ) ) {
			error_log( "   ℹ️ Usuario #$user_id NO tiene motorizado default configurado" );
			return;
		}

		// Verificar si ya tiene motorizado asignado
		$motorizado_actual = get_post_meta( $shipment_id, 'wpcargo_motorizo_recojo', true );
		if ( ! empty( $motorizado_actual ) ) {
			error_log( "   ⚠️ Ya tiene motorizado asignado (#$motorizado_actual), no sobrescribir" );
			return;
		}

		// Verificar fecha no sea futura
		if ( function_exists( 'merc_pickup_date_is_future' ) && merc_pickup_date_is_future( $shipment_id ) ) {
			error_log( "   ⏭️  Fecha FUTURA, no asignar motorizado" );
			return;
		}

		// ✅ ASIGNAR MOTORIZADO
		update_post_meta( $shipment_id, 'wpcargo_motorizo_recojo', $motorizado_default );
		delete_post_meta( $shipment_id, 'wpcargo_driver' );
		add_post_meta( $shipment_id, 'wpcargo_driver', $motorizado_default );

		$motorizado_default_data = get_userdata( $motorizado_default );
		$nombre_motorizado       = $motorizado_default_data ? $motorizado_default_data->display_name : 'Motorizado #' . $motorizado_default;

		error_log( "   ✅ AUTO-ASIGNADO - Envío #$shipment_id" );
		error_log( "   ✅ Motorizado: $nombre_motorizado (ID: $motorizado_default)" );
		error_log( "   ✅ Metas asignadas: wpcargo_motorizo_recojo=$motorizado_default | wpcargo_driver=$motorizado_default" );
	}
}

new MERC_Sender_Autofill();


