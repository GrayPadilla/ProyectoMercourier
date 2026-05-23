<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCA_Admin {

    public function __construct() {
        add_action( 'admin_menu',                       [ $this, 'registrar_menu' ] );
        add_action( 'admin_post_wpca_admin_mov',        [ $this, 'handle_mov' ] );
        add_action( 'admin_post_wpca_admin_del_mov',    [ $this, 'handle_del_mov' ] );
        add_action( 'admin_post_wpca_admin_prod',       [ $this, 'handle_prod' ] );
        add_action( 'admin_post_wpca_admin_del_prod',   [ $this, 'handle_del_prod' ] );
    }

    public function registrar_menu(): void {
        add_menu_page( 'Almacén', 'Almacén', 'manage_options', 'wpca-almacen', [ $this, 'pag_stock' ], 'dashicons-store', 30 );
        add_submenu_page( 'wpca-almacen', 'Stock',     'Stock',     'manage_options', 'wpca-almacen',   [ $this, 'pag_stock' ] );
        add_submenu_page( 'wpca-almacen', 'Entradas',  'Entradas',  'manage_options', 'wpca-entradas',  [ $this, 'pag_entradas' ] );
        add_submenu_page( 'wpca-almacen', 'Salidas',   'Salidas',   'manage_options', 'wpca-salidas',   [ $this, 'pag_salidas' ] );
        add_submenu_page( 'wpca-almacen', 'Productos', 'Productos', 'manage_options', 'wpca-productos', [ $this, 'pag_productos' ] );
        add_submenu_page( 'wpca-almacen', 'Reportes',  'Reportes',  'manage_options', 'wpca-reportes',  [ $this, 'pag_reportes' ] );
    }

    public function pag_stock(): void {
        $marca  = sanitize_text_field( $_GET['marca']  ?? '' );
        $buscar = sanitize_text_field( $_GET['buscar'] ?? '' );
        $stock_bajo = ! empty( $_GET['stock_bajo'] );
        $msg    = sanitize_key( $_GET['msg'] ?? '' );
        $productos = WPCA_Producto::obtener_todos( compact( 'marca', 'buscar', 'stock_bajo' ) );
        $marcas    = WPCA_Producto::obtener_marcas();
        wpca_include_template( 'admin/stock.tpl.php', compact( 'productos', 'marcas', 'marca', 'buscar', 'stock_bajo', 'msg' ) );
    }

    public function pag_entradas(): void {
        $action = sanitize_key( $_GET['action'] ?? '' );
        $msg    = sanitize_key( $_GET['msg']    ?? '' );
        if ( $action === 'add' ) {
            $prod_pre  = (int)( $_GET['prod'] ?? 0 );
            $productos = WPCA_Producto::obtener_todos();
            $error = get_transient( 'wpca_admin_flash_mov' );
            if ( $error ) delete_transient( 'wpca_admin_flash_mov' );
            wpca_include_template( 'admin/mov-form.tpl.php', [ 'tipo' => 'entrada', 'productos' => $productos, 'prod_pre' => $prod_pre, 'error' => $error ] );
        } else {
            $buscar = sanitize_text_field( $_GET['buscar'] ?? '' );
            $desde  = sanitize_text_field( $_GET['desde']  ?? '' );
            $hasta  = sanitize_text_field( $_GET['hasta']  ?? '' );
            $movs   = WPCA_Movimiento::obtener_todos( [ 'tipo' => 'entrada', 'buscar' => $buscar, 'desde' => $desde, 'hasta' => $hasta ] );
            wpca_include_template( 'admin/movimientos.tpl.php', compact( 'movs', 'msg', 'buscar', 'desde', 'hasta' ) + [ 'tipo' => 'entrada' ] );
        }
    }

    public function pag_salidas(): void {
        $action = sanitize_key( $_GET['action'] ?? '' );
        $msg    = sanitize_key( $_GET['msg']    ?? '' );
        if ( $action === 'add' ) {
            $prod_pre  = (int)( $_GET['prod'] ?? 0 );
            $productos = WPCA_Producto::obtener_todos();
            $error = get_transient( 'wpca_admin_flash_mov' );
            if ( $error ) delete_transient( 'wpca_admin_flash_mov' );
            wpca_include_template( 'admin/mov-form.tpl.php', [ 'tipo' => 'salida', 'productos' => $productos, 'prod_pre' => $prod_pre, 'error' => $error ] );
        } else {
            $buscar = sanitize_text_field( $_GET['buscar'] ?? '' );
            $desde  = sanitize_text_field( $_GET['desde']  ?? '' );
            $hasta  = sanitize_text_field( $_GET['hasta']  ?? '' );
            $movs   = WPCA_Movimiento::obtener_todos( [ 'tipo' => 'salida', 'buscar' => $buscar, 'desde' => $desde, 'hasta' => $hasta ] );
            wpca_include_template( 'admin/movimientos.tpl.php', compact( 'movs', 'msg', 'buscar', 'desde', 'hasta' ) + [ 'tipo' => 'salida' ] );
        }
    }

    public function pag_productos(): void {
        $action = sanitize_key( $_GET['action'] ?? '' );
        $msg    = sanitize_key( $_GET['msg']    ?? '' );
        $id     = (int) ( $_GET['id'] ?? 0 );
        if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
            $producto = $id ? WPCA_Producto::obtener_por_id( $id ) : null;
            $error = get_transient( 'wpca_admin_flash_prod' );
            $prev  = get_transient( 'wpca_admin_flash_prod_prev' );
            if ( $error ) { delete_transient('wpca_admin_flash_prod'); delete_transient('wpca_admin_flash_prod_prev'); }
            wpca_include_template( 'admin/producto-form.tpl.php', compact( 'producto', 'id', 'error', 'prev', 'action' ) );
        } else {
            $buscar = sanitize_text_field( $_GET['buscar'] ?? '' );
            $marca  = sanitize_text_field( $_GET['marca']  ?? '' );
            $productos = WPCA_Producto::obtener_todos( compact( 'buscar', 'marca' ) );
            $marcas    = WPCA_Producto::obtener_marcas();
            wpca_include_template( 'admin/productos.tpl.php', compact( 'productos', 'marcas', 'buscar', 'marca', 'msg' ) );
        }
    }

    public function pag_reportes(): void {
        $variedad = WPCA_Producto::variedad_por_marca();
        $unidades = WPCA_Producto::unidades_por_marca();
        $por_mes  = WPCA_Movimiento::movimientos_por_mes( 12 );
        wpca_include_template( 'admin/reportes.tpl.php', compact( 'variedad', 'unidades', 'por_mes' ) );
    }

    /* ── Handlers ─────────────────────────────────────── */

    public function handle_mov(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        check_admin_referer( 'wpca_admin_mov_nonce' );
        $tipo   = sanitize_key( $_POST['tipo'] ?? '' );
        $result = WPCA_Movimiento::crear( $_POST );
        $page   = $tipo === 'entrada' ? 'wpca-entradas' : 'wpca-salidas';
        if ( is_wp_error( $result ) ) {
            set_transient( 'wpca_admin_flash_mov', $result->get_error_message(), 60 );
            wp_safe_redirect( admin_url( "admin.php?page={$page}&action=add" ) );
        } else {
            wp_safe_redirect( admin_url( "admin.php?page={$page}&msg=guardado" ) );
        }
        exit;
    }

    public function handle_del_mov(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        check_admin_referer( 'wpca_admin_del_mov_nonce' );
        $mov  = WPCA_Movimiento::obtener_por_id( (int)( $_POST['id'] ?? 0 ) );
        $tipo = $mov->tipo ?? 'entrada';
        WPCA_Movimiento::eliminar( (int)( $_POST['id'] ?? 0 ) );
        $page = $tipo === 'entrada' ? 'wpca-entradas' : 'wpca-salidas';
        wp_safe_redirect( admin_url( "admin.php?page={$page}&msg=eliminado" ) );
        exit;
    }

    public function handle_prod(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        check_admin_referer( 'wpca_admin_prod_nonce' );
        $id     = (int)( $_POST['id'] ?? 0 );
        $result = $id ? WPCA_Producto::actualizar( $id, $_POST ) : WPCA_Producto::crear( $_POST );
        if ( is_wp_error( $result ) ) {
            set_transient( 'wpca_admin_flash_prod', $result->get_error_message(), 60 );
            set_transient( 'wpca_admin_flash_prod_prev', $_POST, 60 );
            $url = $id ? admin_url("admin.php?page=wpca-productos&action=edit&id={$id}") : admin_url("admin.php?page=wpca-productos&action=add");
            wp_safe_redirect( $url );
        } else {
            wp_safe_redirect( admin_url( "admin.php?page=wpca-productos&msg=" . ( $id ? 'actualizado' : 'creado' ) ) );
        }
        exit;
    }

    public function handle_del_prod(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        check_admin_referer( 'wpca_admin_del_prod_nonce' );
        WPCA_Producto::eliminar( (int)( $_POST['id'] ?? 0 ) );
        wp_safe_redirect( admin_url( "admin.php?page=wpca-productos&msg=eliminado" ) );
        exit;
    }
}

new WPCA_Admin();
