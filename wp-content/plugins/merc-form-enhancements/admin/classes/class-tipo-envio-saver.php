<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Tipo_Envio_Saver
 * - Guarda tipo_envio en post_meta al crear/actualizar un shipment.
 * - Encola tipo-envio-saver.js con los datos necesarios via wp_localize_script.
 * - Oculta secciones administrativas para clientes (CSS + JS inline mínimo).
 */
class MERC_Tipo_Envio_Saver {

	public function __construct() {
		add_action( 'wpcargo_after_save_shipment', [ $this, 'guardar_tipo_envio' ], 1, 1 );
		add_action( 'save_post_wpcargo_shipment',  [ $this, 'guardar_tipo_envio' ], 1, 1 );
		add_action( 'wp_insert_post',              [ $this, 'guardar_tipo_envio' ], 1, 1 );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_footer',          [ $this, 'ocultar_campos_clientes' ], 4 );
	}

	/* ── Guardar tipo_envio ──────────────────────────────────────────── */

	public function guardar_tipo_envio( int $post_id ): void {
		if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) return;

		$tipo = sanitize_text_field( wp_unslash(
			$_GET['type']          ??
			$_POST['tipo_envio']   ??
			$_POST['type']         ??
			$_REQUEST['type']      ?? ''
		) );

		if ( ! empty( $tipo ) ) {
			update_post_meta( $post_id, 'tipo_envio', $tipo );
		}
	}

	/* ── Encolar JS ─────────────────────────────────────────────────── */

	public function enqueue_scripts(): void {
		if ( ! isset( $_GET['wpcfe'] ) || $_GET['wpcfe'] !== 'add' ) return;
		if ( ! isset( $_GET['type'] ) ) return;

		$tipo = sanitize_text_field( wp_unslash( $_GET['type'] ) );

		$esta_bloqueado  = false;
		$mensaje_bloqueo = '';
		$current_user    = wp_get_current_user();

		if ( in_array( 'wpcargo_client', (array) $current_user->roles, true )
		     && function_exists( 'merc_check_tipo_envio_blocked' )
		) {
			$esta_bloqueado  = merc_check_tipo_envio_blocked( $current_user->ID, $tipo );
			$mensaje_bloqueo = $esta_bloqueado
				? $this->get_mensaje_bloqueo( $tipo, $current_user->ID )
				: '';
		}

		wp_enqueue_script(
			'merc-tipo-envio-saver',
			MERC_FORM_URL . 'admin/assets/js/tipo-envio-saver.js',
			[ 'jquery' ],
			MERC_FORM_VERSION,
			true
		);

		wp_localize_script( 'merc-tipo-envio-saver', 'MercFormSaver', [
			'tipoEnvio' => $tipo,
			'bloqueado' => $esta_bloqueado,
			'mensaje'   => $mensaje_bloqueo,
		] );
	}

	/* ── Ocultar campos admin para clientes ─────────────────────────── */

	public function ocultar_campos_clientes(): void {
		if ( ! is_user_logged_in() ) return;

		$user   = wp_get_current_user();
		$es_add = isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] === 'add';

		if ( ! in_array( 'wpcargo_client', (array) $user->roles, true ) || ! $es_add ) return;
		?>
		<style>
			#history_info,.history_info,[data-section="history_info"],
			#assigned-driver-wrapper,.assigned-driver-wrapper,
			[data-section="assigned-driver-wrapper"] { display:none !important; }
		</style>
		<script>
		jQuery(document).ready(function($){
			$('#history_info,.history_info,#assigned-driver-wrapper,.assigned-driver-wrapper').hide();
			$('[data-section="history_info"],[data-section="assigned-driver-wrapper"]').hide();
		});
		</script>
		<?php
	}

	/* ── Helper: mensaje de bloqueo ─────────────────────────────────── */

	private function get_mensaje_bloqueo( string $tipo, int $user_id ): string {
		if ( ! function_exists( 'merc_get_estado_financiero' ) ) return 'Este tipo de envío está bloqueado.';

		$estado_fin = merc_get_estado_financiero( $user_id );
		$cuenta     = function_exists( 'merc_count_envios_del_tipo_hoy' )
		              ? merc_count_envios_del_tipo_hoy( $user_id, $tipo )
		              : 0;
		$raw        = strtolower( $tipo );

		$grupos = [
			[ ['normal','emprendedor'],        '11:00 AM',  '11:00 AM'        ],
			[ ['express','agencia'],            '12:30 PM',  '13:00 (1:00 PM)' ],
			[ ['full_fitment','full'],          '11:30 AM',  '12:15 PM'        ],
		];

		foreach ( $grupos as [ $tipos, $limite_sin, $limite_con ] ) {
			if ( ! in_array( $raw, $tipos, true ) ) continue;
			$limite = $cuenta == 0 ? $limite_sin : $limite_con;
			if ( $cuenta == 0 )                             return "Ya pasaron las {$limite} sin envíos registrados.\n\nPuedes intentar mañana.";
			if ( $estado_fin['estado'] === 'cliente_debe' ) return "🚫 Tienes una deuda pendiente con Mercourier.\n\nDebes liquidar tu deuda antes de crear envíos.";
			if ( $estado_fin['estado'] === 'merc_debe' )    return "⏰ Se desbloqueará a las 19:30 (7:30 PM).";
			return "Ya tienes envíos de hoy y pasaron las {$limite}.";
		}

		return 'Este tipo de envío está bloqueado.';
	}
}

new MERC_Tipo_Envio_Saver();


