<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wpca_get_frontend_page_id(): int {
	$saved = (int) get_option( 'wpca_frontend_page_id' );
	if ( $saved && get_post_status( $saved ) === 'publish' ) return $saved;

	global $wpdb;
	$id = (int) $wpdb->get_var(
		"SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[wpca-almacen]%' AND post_status = 'publish' LIMIT 1"
	);
	if ( ! $id ) {
		$id = (int) wp_insert_post( [
			'post_title'   => 'Almacén',
			'post_content' => '[wpca-almacen]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		] );
	}
	if ( $id ) {
		update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
		update_post_meta( $id, 'wpcfe_menu_icon', 'fa fa-cubes mr-3' );
		update_option( 'wpca_frontend_page_id', $id, false );
	}
	return $id;
}

function wpca_frontend_url( array $extra = [] ): string {
	$url = get_permalink( wpca_get_frontend_page_id() ) ?: home_url( '/almacen/' );
	return $extra ? add_query_arg( $extra, $url ) : $url;
}

function wpca_include_template( string $file, array $data = [] ): void {
	$path = WPCA_PATH . "admin/templates/{$file}";
	if ( ! file_exists( $path ) ) wp_die( "Plantilla no encontrada: {$file}" );
	if ( $data ) extract( $data, EXTR_SKIP ); // phpcs:ignore
	include $path;
}

function wpca_current_action(): string {
	return sanitize_key( $_GET['wpca'] ?? '' );
}

function wpca_es_admin(): bool {
	return current_user_can( 'manage_options' );
}

function wpca_es_cliente(): bool {
	$user = wp_get_current_user();
	return in_array( 'wpcargo_client', (array) $user->roles, true );
}

function wpca_num( $n ): string {
	return number_format( (float) $n, 0, '.', ',' );
}

function wpca_fecha( $d ): string {
	return $d ? date( 'd/m/Y', strtotime( $d ) ) : '—';
}

function wpca_stock_badge( object $p ): string {
	if ( (int) $p->stock_actual <= 0 ) {
		return '<span class="badge badge-danger">Sin stock</span>';
	}
	if ( (int) $p->stock_minimo > 0 && (int) $p->stock_actual <= (int) $p->stock_minimo ) {
		return '<span class="badge badge-warning">Stock bajo</span>';
	}
	return '<span class="badge badge-success">OK</span>';
}

/* ── Clientes WPCargo ────────────────────────────────── */

/**
 * Devuelve el label (billing_company o display_name) del cliente logueado actualmente,
 * que coincide con el campo `marca` de sus productos asignados.
 */
function wpca_cliente_marca(): string {
	$user    = wp_get_current_user();
	$empresa = get_user_meta( $user->ID, 'billing_company', true );
	return ( $empresa !== '' && $empresa !== false ) ? $empresa : $user->display_name;
}

function wpca_obtener_clientes_wpcargo(): array {
	$users    = get_users( [ 'role' => 'wpcargo_client', 'orderby' => 'display_name', 'order' => 'ASC' ] );
	$clientes = [];
	foreach ( $users as $user ) {
		// Usar billing_company si existe (guardado por WPCargo frontend), si no display_name
		$empresa = get_user_meta( $user->ID, 'billing_company', true );
		$clientes[] = (object) [
			'ID'    => $user->ID,
			'label' => ( $empresa !== '' && $empresa !== false ) ? $empresa : $user->display_name,
		];
	}
	usort( $clientes, fn( $a, $b ) => strcmp( $a->label, $b->label ) );
	return $clientes;
}
