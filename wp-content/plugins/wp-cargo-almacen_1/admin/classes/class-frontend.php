<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCA_Frontend {

	public function __construct() {
		add_shortcode( 'wpca-almacen',              [ $this, 'render_shortcode' ] );
		add_filter( 'wpcfe_after_sidebar_menus',    [ $this, 'sidebar_item' ], 25, 1 );
		add_action( 'wp_enqueue_scripts',           [ $this, 'encolar_assets' ], 100 );
		add_action( 'admin_post_wpca_guardar_mov',  [ $this, 'handle_guardar_movimiento' ] );
		add_action( 'admin_post_wpca_eliminar_mov', [ $this, 'handle_eliminar_movimiento' ] );
		add_action( 'admin_post_wpca_guardar_prod', [ $this, 'handle_guardar_producto' ] );
		add_action( 'wp_ajax_wpca_upload_imagen',    [ $this, 'handle_upload_imagen' ] );
		add_action( 'admin_post_wpca_eliminar_prod',[ $this, 'handle_eliminar_producto' ] );
	}

	/* ── Sidebar (ítem simple, sin sub-menú) ─────────── */

	public function sidebar_item( array $menu ): array {
		if ( ! current_user_can( 'manage_options' ) && ! wpca_es_cliente() ) return $menu;
		$menu['wpca-menu'] = [
			'page-id'   => wpca_get_frontend_page_id(),
			'label'     => __( 'Almacén', 'wp-cargo-almacen' ),
			'permalink' => wpca_frontend_url(),
			'icon'      => 'fa-cubes',
		];
		return $menu;
	}

	public function encolar_assets(): void {
		$page_id = (int) get_option( 'wpca_frontend_page_id', 0 );
		if ( ! $page_id || (int) get_queried_object_id() !== $page_id ) return;
		wp_enqueue_style( 'wpca-frontend', WPCA_URL . 'admin/assets/css/frontend.css', [], WPCA_VERSION );
	}

	/* ── Shortcode principal ──────────────────────────── */

	public function render_shortcode(): string {
		$es_admin  = current_user_can( 'manage_options' );
		$es_client = wpca_es_cliente();

		if ( ! $es_admin && ! $es_client ) {
			return '<p>' . esc_html__( 'Acceso restringido.', 'wp-cargo-almacen' ) . '</p>';
		}

		ob_start();

		$action   = wpca_current_action();
		$page_url = wpca_frontend_url();

		// Acciones que NO muestran tabs (formularios internos)
		$sin_tabs = [ 'nuevo-entrada', 'nuevo-salida', 'nuevo-producto', 'editar-producto' ];
		$mostrar_tabs = ! in_array( $action, $sin_tabs, true );

		if ( $mostrar_tabs ) {
			$this->render_tabs( $action, $es_admin, $page_url );
		}

		if ( $es_admin ) {
			match ( $action ) {
				'entradas'        => $this->render_movimientos( 'entrada' ),
				'salidas'         => $this->render_movimientos( 'salida' ),
				'nuevo-entrada'   => $this->render_mov_form( 'entrada' ),
				'nuevo-salida'    => $this->render_mov_form( 'salida' ),
				'productos'       => $this->render_productos(),
				'nuevo-producto'  => $this->render_producto_form(),
				'editar-producto' => $this->render_producto_form(),
				'reportes'        => $this->render_reportes(),
				default           => $this->render_stock(),
			};
		} else {
			match ( $action ) {
				'entradas' => $this->render_movimientos_cliente( 'entrada' ),
				'salidas'  => $this->render_movimientos_cliente( 'salida' ),
				default    => $this->render_stock_cliente(),
			};
		}

		return ob_get_clean();
	}

	/* ── Tabs de navegación ───────────────────────────── */

	private function render_tabs( string $action, bool $es_admin, string $page_url ): void {
		// Normalizar action para tabs: nuevo-entrada/salida → entradas/salidas
		$tab_activo = match ( $action ) {
			'entradas', 'nuevo-entrada' => 'entradas',
			'salidas',  'nuevo-salida'  => 'salidas',
			'productos', 'nuevo-producto', 'editar-producto' => 'productos',
			'reportes' => 'reportes',
			default    => 'stock',
		};

		$tabs_admin = [
			'stock'     => [ 'label' => 'Stock',     'icon' => 'fa-boxes' ],
			'entradas'  => [ 'label' => 'Entradas',  'icon' => 'fa-arrow-down' ],
			'salidas'   => [ 'label' => 'Salidas',   'icon' => 'fa-arrow-up' ],
			'productos' => [ 'label' => 'Productos', 'icon' => 'fa-box' ],
			'reportes'  => [ 'label' => 'Reportes',  'icon' => 'fa-chart-bar' ],
		];
		$tabs_cliente = [
			'stock'    => [ 'label' => 'Stock',    'icon' => 'fa-boxes' ],
			'entradas' => [ 'label' => 'Entradas', 'icon' => 'fa-arrow-down' ],
			'salidas'  => [ 'label' => 'Salidas',  'icon' => 'fa-arrow-up' ],
		];

		$tabs = $es_admin ? $tabs_admin : $tabs_cliente;
		?>
		<ul class="nav nav-pills mb-3 flex-wrap" style="gap:.25rem;">
			<?php foreach ( $tabs as $key => $tab ) :
				$url    = $key === 'stock' ? $page_url : add_query_arg( 'wpca', $key, $page_url );
				$active = $tab_activo === $key ? 'active' : '';
			?>
			<li class="nav-item">
				<a class="nav-link <?php echo $active; ?>" href="<?php echo esc_url( $url ); ?>">
					<i class="fa <?php echo esc_attr( $tab['icon'] ); ?> mr-1"></i>
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
		<hr class="mt-0 mb-3">
		<?php
	}

	/* ── Vistas admin ─────────────────────────────────── */

	private function render_stock(): void {
		$marca      = sanitize_text_field( $_GET['marca']  ?? '' );
		$buscar     = sanitize_text_field( $_GET['buscar'] ?? '' );
		$stock_bajo = ! empty( $_GET['stock_bajo'] );
		$msg        = sanitize_key( $_GET['msg'] ?? '' );
		$productos  = WPCA_Producto::obtener_todos( compact( 'marca', 'buscar', 'stock_bajo' ) );
		$marcas     = WPCA_Producto::obtener_marcas();
		$page_url   = wpca_frontend_url();
		wpca_include_template( 'frontend/stock.tpl.php', compact( 'productos', 'marcas', 'marca', 'buscar', 'stock_bajo', 'msg', 'page_url' ) );
	}

	private function render_movimientos( string $tipo ): void {
		$buscar   = sanitize_text_field( $_GET['buscar'] ?? '' );
		$desde    = sanitize_text_field( $_GET['desde']  ?? '' );
		$hasta    = sanitize_text_field( $_GET['hasta']  ?? '' );
		$msg      = sanitize_key( $_GET['msg'] ?? '' );
		$movs     = WPCA_Movimiento::obtener_todos( compact( 'tipo', 'buscar', 'desde', 'hasta' ) );
		$page_url = wpca_frontend_url();
		wpca_include_template( 'frontend/movimientos.tpl.php', compact( 'tipo', 'movs', 'buscar', 'desde', 'hasta', 'msg', 'page_url' ) );
	}

	private function render_mov_form( string $tipo ): void {
		$prod_pre  = (int) ( $_GET['prod'] ?? 0 );
		$productos = WPCA_Producto::obtener_todos();  // Todos los productos activos
		$page_url  = wpca_frontend_url();
		$flash     = $this->get_flash( 'mov' );
		$error     = $flash['error'] ?? '';
		wpca_include_template( 'frontend/mov-form.tpl.php', compact( 'tipo', 'productos', 'prod_pre', 'page_url', 'error' ) );
	}

	private function render_productos(): void {
		$buscar    = sanitize_text_field( $_GET['buscar'] ?? '' );
		$marca     = sanitize_text_field( $_GET['marca']  ?? '' );
		$msg       = sanitize_key( $_GET['msg'] ?? '' );
		$productos = WPCA_Producto::obtener_todos( compact( 'buscar', 'marca' ) );
		$clientes  = wpca_obtener_clientes_wpcargo();
		$page_url  = wpca_frontend_url();
		wpca_include_template( 'frontend/productos.tpl.php', compact( 'productos', 'clientes', 'buscar', 'marca', 'msg', 'page_url' ) );
	}

	private function render_producto_form(): void {
		$id       = (int) ( $_GET['id'] ?? 0 );
		$producto = $id ? WPCA_Producto::obtener_por_id( $id ) : null;
		$page_url = wpca_frontend_url();
		$flash    = $this->get_flash( 'prod' );
		$error    = $flash['error'] ?? '';
		$prev     = $flash['prev']  ?? null;
		$clientes = wpca_obtener_clientes_wpcargo();
		wpca_include_template( 'frontend/producto-form.tpl.php', compact( 'id', 'producto', 'page_url', 'error', 'prev', 'clientes' ) );
	}

	private function render_reportes(): void {
		$variedad = WPCA_Producto::variedad_por_marca();
		$unidades = WPCA_Producto::unidades_por_marca();
		$por_mes  = WPCA_Movimiento::movimientos_por_mes( 12 );
		$page_url = wpca_frontend_url();
		wpca_include_template( 'frontend/reportes.tpl.php', compact( 'variedad', 'unidades', 'por_mes', 'page_url' ) );
	}

	/* ── Vistas cliente ───────────────────────────────── */

	private function render_stock_cliente(): void {
		$marca_cliente = wpca_cliente_marca();               // Forzar filtro por cliente actual
		$marca      = $marca_cliente;                        // Ignora cualquier ?marca= del query string
		$buscar     = sanitize_text_field( $_GET['buscar'] ?? '' );
		$stock_bajo = ! empty( $_GET['stock_bajo'] );
		$productos  = WPCA_Producto::obtener_todos( compact( 'marca', 'buscar', 'stock_bajo' ) );
		$marcas     = [];                                    // El cliente no necesita filtrar por otras marcas
		$page_url   = wpca_frontend_url();
		wpca_include_template( 'frontend/stock-cliente.tpl.php', compact( 'productos', 'marcas', 'marca', 'buscar', 'stock_bajo', 'page_url' ) );
	}

	private function render_movimientos_cliente( string $tipo ): void {
		$marca_cliente = wpca_cliente_marca();               // Solo movimientos del cliente actual
		$buscar   = sanitize_text_field( $_GET['buscar'] ?? '' );
		$desde    = sanitize_text_field( $_GET['desde']  ?? '' );
		$hasta    = sanitize_text_field( $_GET['hasta']  ?? '' );
		$movs     = WPCA_Movimiento::obtener_todos( compact( 'tipo', 'buscar', 'desde', 'hasta', 'marca_cliente' ) );
		$page_url = wpca_frontend_url();
		wpca_include_template( 'frontend/movimientos-cliente.tpl.php', compact( 'tipo', 'movs', 'buscar', 'desde', 'hasta', 'page_url' ) );
	}

	/* ── Handlers admin-post ──────────────────────────── */

	public function handle_guardar_movimiento(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpca_mov_nonce' );
		$tipo   = sanitize_key( $_POST['tipo'] ?? '' );
		$result = WPCA_Movimiento::crear( $_POST );
		if ( is_wp_error( $result ) ) {
			$this->set_flash( 'mov', [ 'error' => $result->get_error_message() ] );
			wp_safe_redirect( wpca_frontend_url( [ 'wpca' => 'nuevo-' . $tipo ] ) );
		} else {
			wp_safe_redirect( wpca_frontend_url( [ 'wpca' => $tipo . 's', 'msg' => 'guardado' ] ) );
		}
		exit;
	}

	public function handle_eliminar_movimiento(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpca_del_mov_nonce' );
		$mov  = WPCA_Movimiento::obtener_por_id( (int) ( $_POST['id'] ?? 0 ) );
		$tipo = $mov->tipo ?? 'entrada';
		WPCA_Movimiento::eliminar( (int) ( $_POST['id'] ?? 0 ) );
		wp_safe_redirect( wpca_frontend_url( [ 'wpca' => $tipo . 's', 'msg' => 'eliminado' ] ) );
		exit;
	}

	public function handle_upload_imagen(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die();
		check_ajax_referer( 'wpca_upload_imagen' );

		if ( empty( $_FILES['imagen'] ) ) {
			wp_send_json_error( 'No se recibió ningún archivo.' );
		}

		// Limitar tipos permitidos
		$tipo = $_FILES['imagen']['type'] ?? '';
		if ( ! in_array( $tipo, [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ], true ) ) {
			wp_send_json_error( 'Tipo de archivo no permitido.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$_POST['post_id'] = 0;
		$attachment_id = media_handle_upload( 'imagen', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		}

		$url = wp_get_attachment_url( $attachment_id );
		wp_send_json_success( [ 'url' => $url, 'id' => $attachment_id ] );
	}

	public function handle_guardar_producto(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpca_prod_nonce' );
		$id     = (int) ( $_POST['id'] ?? 0 );
		$result = $id ? WPCA_Producto::actualizar( $id, $_POST ) : WPCA_Producto::crear( $_POST );
		if ( is_wp_error( $result ) ) {
			$this->set_flash( 'prod', [ 'error' => $result->get_error_message(), 'prev' => $_POST ] );
			$accion = $id ? [ 'wpca' => 'editar-producto', 'id' => $id ] : [ 'wpca' => 'nuevo-producto' ];
			wp_safe_redirect( wpca_frontend_url( $accion ) );
		} else {
			wp_safe_redirect( wpca_frontend_url( [ 'wpca' => 'productos', 'msg' => $id ? 'actualizado' : 'creado' ] ) );
		}
		exit;
	}

	public function handle_eliminar_producto(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpca_del_prod_nonce' );
		WPCA_Producto::eliminar( (int) ( $_POST['id'] ?? 0 ) );
		wp_safe_redirect( wpca_frontend_url( [ 'wpca' => 'productos', 'msg' => 'eliminado' ] ) );
		exit;
	}

	/* ── Flash helpers ────────────────────────────────── */

	private function set_flash( string $key, array $data ): void {
		set_transient( 'wpca_flash_' . $key . '_' . wp_get_session_token(), $data, 60 );
	}

	private function get_flash( string $key ): array {
		$token = wp_get_session_token();
		$flash = get_transient( 'wpca_flash_' . $key . '_' . $token );
		if ( $flash ) delete_transient( 'wpca_flash_' . $key . '_' . $token );
		return $flash ?: [];
	}
}

new WPCA_Frontend();
