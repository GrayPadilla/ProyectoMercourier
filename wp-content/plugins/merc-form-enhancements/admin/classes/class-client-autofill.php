<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Client_Autofill
 *
 * 1. Filtra el select de clientes de WPCargo para mostrar el nombre de la empresa
 *    (billing_company) en lugar del nombre de usuario.
 *
 * 2. Provee un endpoint AJAX para devolver los datos de un cliente por ID,
 *    de modo que el JS pueda autocompletar los campos del remitente al seleccionar
 *    un cliente en el formulario de creación de envíos.
 */
class MERC_Client_Autofill {

	public function __construct() {
		// Reemplazar display_name con billing_company en el select de clientes
		add_filter( 'wpcfe_get_users_wpcargo_client_list', [ $this, 'usar_billing_company' ] );

		// Cargar JS via wp_footer (inline data + <script src>) para garantizar carga
		add_action( 'wp_footer', [ $this, 'output_inline_script' ], 18 );

		// AJAX: devolver datos de usuario (cliente) por ID
		add_action( 'wp_ajax_merc_get_client_data', [ $this, 'get_client_data_ajax' ] );
	}

	/* ── Filtrar lista de clientes para mostrar billing_company ──────── */

	public function usar_billing_company( array $users ): array {
		foreach ( $users as $id => &$label ) {
			$company = get_user_meta( (int) $id, 'billing_company', true );
			if ( ! empty( $company ) ) {
				$label = esc_html( trim( $company ) );
			}
		}
		return $users;
	}

	/* ── Cargar JS via wp_footer (inline data + script src) ─────────────── */

	public function output_inline_script(): void {
		if ( ! is_user_logged_in() ) return;

		$es_formulario = (
			( isset( $_GET['wpcfe'] ) && in_array( $_GET['wpcfe'], [ 'add', 'update' ], true ) ) ||
			( is_page() && ( has_shortcode( get_post_field( 'post_content', get_the_ID() ), 'wpcfe_shipment_form' ) ||
							 has_shortcode( get_post_field( 'post_content', get_the_ID() ), 'wpcargo_add_shipment' ) ) )
		);

		if ( ! $es_formulario ) return;

		$ajaxurl = admin_url( 'admin-ajax.php' );
		$nonce   = wp_create_nonce( 'merc_form_nonce' );
		$debug   = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'true' : 'false';
		$js_url  = MERC_FORM_URL . 'admin/assets/js/client-autofill.js?ver=' . MERC_FORM_VERSION;
		?>
		<script>var MercClientAutofill = { ajaxurl: '<?php echo esc_js( $ajaxurl ); ?>', nonce: '<?php echo esc_js( $nonce ); ?>', debug: <?php echo $debug; ?> };</script>
		<script src="<?php echo esc_url( $js_url ); ?>"></script>
		<?php
	}

	/* ── AJAX: retornar datos del cliente por ID ─────────────────────── */

	public function get_client_data_ajax(): void {
		// Validar nonce manualmente para devolver error JSON si falla
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'merc_form_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Nonce inválido o expirado' ] );
		}

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( [ 'message' => 'Sin permisos' ] );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => 'ID de usuario inválido' ] );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( [ 'message' => 'Usuario no encontrado' ] );
		}

		$nombre_completo = trim(
			get_user_meta( $user_id, 'first_name', true ) . ' ' .
			get_user_meta( $user_id, 'last_name',  true )
		);

		// 🔄 GENERAR ARRAY DE PRODUCTOS DEL CLIENTE
		$productos_opciones = $this->generar_opciones_productos_cliente( $user_id );

		wp_send_json_success( [
			'nombre'    => $nombre_completo ?: $user->display_name,
			'telefono'  => get_user_meta( $user_id, 'phone',               true ),
			'distrito'  => get_user_meta( $user_id, 'distrito',            true ),
			'direccion' => get_user_meta( $user_id, 'billing_address_1',   true ),
			'email'     => get_user_meta( $user_id, 'billing_email',       true ) ?: $user->user_email,
			'empresa'   => get_user_meta( $user_id, 'billing_company',     true ),
			'link_maps' => get_user_meta( $user_id, 'link_maps_remitente', true ),
			'motorizado_recojo_default' => get_user_meta( $user_id, 'merc_motorizo_recojo_default', true ),
			'productos' => $productos_opciones, // Array de opciones del selector
		] );
	}

	/**
	 * Genera el array de opciones de productos para un cliente
	 * Retorna array de [ 'id' => product_id, 'titulo' => product_title, 'stock' => stock_qty ]
	 */
	private function generar_opciones_productos_cliente( int $user_id ): array {
		// Asegurar que user_id es válido
		$user_id = intval( $user_id );
		if ( $user_id <= 0 ) {
			error_log( "MERC_DEBUG_PRODUCTOS: ❌ user_id inválido: $user_id" );
			return [];
		}

		// Verificar que el usuario existe
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			error_log( "MERC_DEBUG_PRODUCTOS: ❌ Usuario no existe: $user_id" );
			return [];
		}

		error_log( "MERC_DEBUG_PRODUCTOS: 🔍 [INICIO] Buscando productos para usuario: " . $user->user_login . " (ID: $user_id)" );

		// Buscar productos asignados EXPLÍCITAMENTE a este cliente por meta
		$productos = get_posts( [
			'post_type'      => 'merc_producto',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_merc_producto_cliente_asignado',
					'value'   => $user_id, // Valor exacto, no conversión
					'compare' => '=',
					'type'    => 'NUMERIC', // Comparación numérica
				],
			],
		] );

		error_log( "MERC_DEBUG_PRODUCTOS: ✅ Query devolvió " . count($productos) . " productos para user_id=$user_id" );

		// Filtrar productos con stock disponible
		$productos_opciones = [];
		foreach ( $productos as $prod ) {
			$estado   = get_post_meta( $prod->ID, '_merc_producto_estado', true );
			$cantidad = function_exists( 'merc_get_product_stock' ) ? merc_get_product_stock( $prod->ID ) : 0;
			$cliente_meta = get_post_meta( $prod->ID, '_merc_producto_cliente_asignado', true );
			error_log( "MERC_DEBUG_PRODUCTOS: Producto ID=" . $prod->ID . " titulo=" . $prod->post_title . " meta_cliente=" . $cliente_meta . " estado=" . $estado . " stock=" . $cantidad );

			// Debug: verificar que la meta realmente coincide
			if ( intval( $cliente_meta ) !== $user_id ) {
				error_log( "MERC_DEBUG_PRODUCTOS: ⚠️ SALTANDO Producto ID=" . $prod->ID . " - meta_cliente=" . $cliente_meta . " !== user_id=" . $user_id );
				continue; // Saltar este producto
			}

			if ( empty( $estado ) || $estado === 'sin_asignar' || ( $estado === 'asignado' && intval( $cantidad ) > 0 ) ) {
				$productos_opciones[] = [
					'id'     => intval( $prod->ID ),
					'titulo' => sanitize_text_field( $prod->post_title ),
					'stock'  => intval( $cantidad ),
				];
				error_log( "MERC_DEBUG_PRODUCTOS: ✓ INCLUIDO Producto ID=" . $prod->ID . " en opciones" );
			}
		}

		error_log( "MERC_DEBUG_PRODUCTOS: 🎯 [FIN] Retornando " . count($productos_opciones) . " opciones filtradas para user_id=$user_id (desde " . count($productos) . " totales)" );

		return $productos_opciones;
	}
}

new MERC_Client_Autofill();



