<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helpers globales del plugin merc-csv-import.
 */

/**
 * Carga un template del plugin.
 *
 * @param string $file  Ruta relativa desde admin/templates/  (ej. 'admin/import-log.tpl.php')
 * @param array  $data  Variables a inyectar en el template.
 */
function mci_include_template( string $file, array $data = [] ): void {
	$path = MERC_CSV_PATH . "admin/templates/{$file}";
	if ( ! file_exists( $path ) ) {
		wp_die( "Plantilla no encontrada: {$file}" );
	}
	if ( $data ) {
		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
	}
	include $path;
}

/**
 * Wrappers de compatibilidad para código legado que llame
 * a merc_get_district_prices() / merc_find_district_price()
 * antes de que la clase MERC_Financial_Import esté disponible.
 * (Las versiones canónicas se definen en class-financial-import.php)
 */
if ( ! function_exists( 'merc_get_district_prices' ) ) {
	function merc_get_district_prices( string $tipo = 'normal' ): array {
		return class_exists( 'MERC_Financial_Import' )
			? MERC_Financial_Import::get_prices( $tipo )
			: [];
	}
}

if ( ! function_exists( 'merc_find_district_price' ) ) {
	function merc_find_district_price( string $destination, string $tipo = 'normal' ): float {
		return class_exists( 'MERC_Financial_Import' )
			? MERC_Financial_Import::find_district_price( $destination, $tipo )
			: 0.00;
	}
}

/**
 * ═══════════════════════════════════════════════════════════════════════════════════
 * INICIALIZAR HISTORIAL DE ESTADOS CUANDO SE CREA UN ENVÍO
 * ═══════════════════════════════════════════════════════════════════════════════════
 * Se ejecuta con prioridad 3 (muy temprano) para capturar TODOS los métodos de creación:
 * - Creación individual por formulario
 * - Creación masiva por import/export
 * 
 * Solución para: "cuando creo envios masivos, no se guarda el estado inicial en el historial"
 */
if ( ! function_exists( 'mci_initialize_shipment_history_on_create' ) ) {
	add_action( 'save_post_wpcargo_shipment', 'mci_initialize_shipment_history_on_create', 3, 3 );

	/**
	 * Inicializa el historial de estados cuando se crea un envío por primera vez.
	 * Se ejecuta muy temprano (prioridad 3) para capturar TODOS los métodos de creación.
	 *
	 * Si el meta 'wpcargo_shipments_update' está vacío y el envío es nuevo,
	 * crea una entrada inicial con el estado actual del envío (PENDIENTE por defecto).
	 *
	 * @param int    $post_id El ID del post siendo guardado.
	 * @param object $post    El objeto post.
	 * @param bool   $update  Si false, es un nuevo post; si true, es una actualización.
	 */
	function mci_initialize_shipment_history_on_create( $post_id, $post, $update ) {
		// No procesar en autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Solo procesar si NO es una actualización (es decir, es un nuevo post)
		if ( $update ) {
			error_log( '✅ [MCI_HISTORY] POST#' . $post_id . ' - Saltado: es una actualización' );
			return;
		}

		error_log( '🔍 [MCI_HISTORY] POST#' . $post_id . ' - Verificando si necesita inicialización de historial...' );

		// Obtener historial actual
		$history = get_post_meta( $post_id, 'wpcargo_shipments_update', true );
		
		// Si ya existe historial, no hacer nada
		if ( is_array( $history ) && ! empty( $history ) ) {
			error_log( '✅ [MCI_HISTORY] POST#' . $post_id . ' - Ya tiene historial (' . count( $history ) . ' entrada/s)' );
			return;
		}

		error_log( '❌ [MCI_HISTORY] POST#' . $post_id . ' - Historial vacío, inicializando...' );

		// Obtener el estado actual del envío
		$status = get_post_meta( $post_id, 'wpcargo_status', true );
		if ( empty( $status ) ) {
			// Si no tiene estado, usar PENDIENTE como predeterminado
			$status = 'PENDIENTE';
			update_post_meta( $post_id, 'wpcargo_status', $status );
			error_log( '⚠️ [MCI_HISTORY] POST#' . $post_id . ' - Estado no encontrado, usando PENDIENTE' );
		}

		// Obtener usuario actual (quien está creando el envío)
		$current_user = wp_get_current_user();
		$user_name = $current_user->display_name ?: 'Sistema';

		// Crear entrada inicial del historial
		// Formato compatible con wpcargo_history_fields()
		$initial_entry = array(
			'date'          => wp_date( 'd/m/Y' ),
			'time'          => wp_date( 'H:i a' ),
			'location'      => '',
			'status'        => $status,
			'updated-name'  => $user_name,
			'remarks'       => ''
		);

		error_log( '📝 [MCI_HISTORY] POST#' . $post_id . ' - Creando entrada inicial:' );
		error_log( '   - Fecha: ' . $initial_entry['date'] );
		error_log( '   - Hora: ' . $initial_entry['time'] );
		error_log( '   - Estado: ' . $initial_entry['status'] );
		error_log( '   - Usuario: ' . $initial_entry['updated-name'] );

		// Guardar el historial con la entrada inicial
		$history = array( $initial_entry );
		update_post_meta( $post_id, 'wpcargo_shipments_update', $history );

		error_log( '✅ [MCI_HISTORY] POST#' . $post_id . ' - Historial inicializado correctamente' );
	}
}


