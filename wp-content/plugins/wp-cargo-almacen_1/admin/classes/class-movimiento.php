<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCA_Movimiento {

    public static function crear( array $datos ): int|WP_Error {
        global $wpdb;
        $tabla    = $wpdb->prefix . 'wpca_movimientos';
        $tipo     = sanitize_key( $datos['tipo']     ?? '' );
        $prod_id  = (int) ( $datos['producto_id']    ?? 0 );
        $cantidad = (int) ( $datos['cantidad']        ?? 0 );
        $fecha    = sanitize_text_field( $datos['fecha'] ?? date('Y-m-d') );

        if ( ! in_array( $tipo, [ 'entrada', 'salida' ], true ) ) return new WP_Error( 'validacion', 'Tipo inválido.' );
        if ( $prod_id <= 0 )  return new WP_Error( 'validacion', 'Selecciona un producto.' );
        if ( $cantidad <= 0 ) return new WP_Error( 'validacion', 'La cantidad debe ser mayor a 0.' );

        if ( $tipo === 'salida' ) {
            $prod = WPCA_Producto::obtener_por_id( $prod_id );
            if ( ! $prod ) return new WP_Error( 'not_found', 'Producto no encontrado.' );
            if ( (int) $prod->stock_actual < $cantidad ) {
                return new WP_Error( 'stock', "Stock insuficiente. Disponible: {$prod->stock_actual}" );
            }
        }

        $wpdb->insert( $tabla, [
            'tipo'          => $tipo,
            'producto_id'   => $prod_id,
            'cantidad'      => $cantidad,
            'lote'          => sanitize_text_field( $datos['lote']          ?? '0' ),
            'nro_documento' => sanitize_text_field( $datos['nro_documento'] ?? '' ),
            'fecha'         => $fecha,
            'notas'         => sanitize_textarea_field( $datos['notas']     ?? '' ),
            'creado_por'    => get_current_user_id(),
        ] );

        if ( ! $wpdb->insert_id ) return new WP_Error( 'db', 'Error al guardar el movimiento.' );
        WPCA_Producto::recalcular_stock( $prod_id );
        return $wpdb->insert_id;
    }

    public static function eliminar( int $id ): void {
        global $wpdb;
        $tabla = $wpdb->prefix . 'wpca_movimientos';
        $mov   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE id = %d", $id ) );
        $wpdb->delete( $tabla, [ 'id' => $id ] );
        if ( $mov ) WPCA_Producto::recalcular_stock( (int) $mov->producto_id );
    }

    public static function obtener_todos( array $args = [] ): array {
        global $wpdb;
        $t_mov  = $wpdb->prefix . 'wpca_movimientos';
        $t_prod = $wpdb->prefix . 'wpca_productos';
        $where  = [];
        $params = [];

        if ( ! empty( $args['tipo'] ) ) {
            $where[]  = 'm.tipo = %s';
            $params[] = $args['tipo'];
        }
        if ( ! empty( $args['buscar'] ) ) {
            $like     = '%' . $wpdb->esc_like( $args['buscar'] ) . '%';
            $where[]  = '( p.codigo LIKE %s OR p.descripcion LIKE %s OR m.nro_documento LIKE %s )';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ( ! empty( $args['desde'] ) ) { $where[] = 'm.fecha >= %s'; $params[] = $args['desde']; }
        if ( ! empty( $args['hasta'] ) ) { $where[] = 'm.fecha <= %s'; $params[] = $args['hasta']; }
        if ( ! empty( $args['marca_cliente'] ) ) {
            $where[]  = 'p.marca = %s';
            $params[] = $args['marca_cliente'];
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $limit     = (int) ( $args['limite'] ?? 300 );
        $query = "SELECT m.*, p.codigo, p.descripcion, p.marca, p.unidad
                  FROM {$t_mov} m LEFT JOIN {$t_prod} p ON p.id = m.producto_id
                  {$where_sql} ORDER BY m.fecha DESC, m.id DESC LIMIT %d";
        $params[] = $limit;
        return $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );
    }

    public static function obtener_por_id( int $id ): ?object {
        global $wpdb;
        $t_mov  = $wpdb->prefix . 'wpca_movimientos';
        $t_prod = $wpdb->prefix . 'wpca_productos';
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT m.*, p.codigo, p.descripcion, p.marca, p.unidad
             FROM {$t_mov} m LEFT JOIN {$t_prod} p ON p.id = m.producto_id WHERE m.id = %d", $id
        ) ) ?: null;
    }

    public static function movimientos_por_mes( int $meses = 6 ): array {
        global $wpdb;
        $t = $wpdb->prefix . 'wpca_movimientos';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE_FORMAT(fecha,'%%Y-%%m') AS mes, tipo, SUM(cantidad) AS total
             FROM {$t} WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL %d MONTH)
             GROUP BY mes, tipo ORDER BY mes ASC", $meses
        ) );
    }
}
