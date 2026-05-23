<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCA_Producto {

    public static function crear( array $datos ): int|WP_Error {
        global $wpdb;
        $tabla  = $wpdb->prefix . 'wpca_productos';

        // El campo 'codigo' viene ya como DHV-XXXX desde el hidden input del form
        $codigo = strtoupper( trim( $datos['codigo'] ?? '' ) );
        if ( empty( $codigo ) ) return new WP_Error( 'validacion', 'El código es obligatorio.' );
        if ( ! preg_match( '/^DHV-.+/', $codigo ) ) {
            $codigo = 'DHV-' . $codigo; // Fallback por si acaso
        }

        $existe = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tabla} WHERE codigo = %s", $codigo ) );
        if ( $existe ) return new WP_Error( 'duplicado', "Ya existe un producto con el código {$codigo}." );

        $imagen       = sanitize_text_field( $datos['imagen_url'] ?? '' );
        $stock_ini    = (int) ( $datos['stock_actual'] ?? 0 ); // "stock_actual" del form = stock inicial

        $wpdb->insert( $tabla, [
            'codigo'        => $codigo,
            'descripcion'   => sanitize_text_field( $datos['descripcion'] ?? '' ),
            'marca'         => sanitize_text_field( $datos['marca']        ?? '' ),
            'unidad'        => sanitize_text_field( $datos['unidad']       ?? 'UND' ),
            'stock_minimo'  => (int) ( $datos['stock_minimo'] ?? 0 ),
            'stock_actual'  => $stock_ini,
            'stock_inicial' => $stock_ini,
            'imagen'        => $imagen,
            'activo'        => 1,
        ] );
        return $wpdb->insert_id ?: new WP_Error( 'db', 'Error al crear el producto.' );
    }

    public static function actualizar( int $id, array $datos ): true|WP_Error {
        global $wpdb;
        $tabla  = $wpdb->prefix . 'wpca_productos';
        $codigo = strtoupper( trim( $datos['codigo'] ?? '' ) );

        $existe = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE codigo = %s AND id <> %d", $codigo, $id
        ) );
        if ( $existe ) return new WP_Error( 'duplicado', "Ya existe otro producto con el código {$codigo}." );

        $imagen = sanitize_text_field( $datos['imagen_url'] ?? $datos['imagen'] ?? '' );

        $wpdb->update( $tabla, [
            'codigo'       => $codigo,
            'descripcion'  => sanitize_text_field( $datos['descripcion'] ?? '' ),
            'marca'        => sanitize_text_field( $datos['marca']        ?? '' ),
            'unidad'       => sanitize_text_field( $datos['unidad']       ?? 'UND' ),
            'stock_minimo' => (int) ( $datos['stock_minimo'] ?? 0 ),
            'imagen'       => $imagen,
        ], [ 'id' => $id ] );
        return true;
    }

    public static function eliminar( int $id ): void {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'wpca_productos', [ 'activo' => 0 ], [ 'id' => $id ] );
    }

    public static function obtener_por_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpca_productos WHERE id = %d", $id
        ) ) ?: null;
    }

    public static function obtener_todos( array $args = [] ): array {
        global $wpdb;
        $tabla  = $wpdb->prefix . 'wpca_productos';
        $where  = [ 'activo = 1' ];
        $params = [];

        if ( ! empty( $args['marca'] ) ) {
            $where[]  = 'marca = %s';
            $params[] = $args['marca'];
        }
        if ( ! empty( $args['buscar'] ) ) {
            $like     = '%' . $wpdb->esc_like( $args['buscar'] ) . '%';
            $where[]  = '( codigo LIKE %s OR descripcion LIKE %s )';
            $params[] = $like;
            $params[] = $like;
        }
        if ( ! empty( $args['stock_bajo'] ) ) {
            $where[] = 'stock_minimo > 0 AND stock_actual <= stock_minimo';
        }

        $where_sql = implode( ' AND ', $where );
        $query = "SELECT * FROM {$tabla} WHERE {$where_sql} ORDER BY marca, codigo";
        return $params
            ? $wpdb->get_results( $wpdb->prepare( $query, ...$params ) )
            : $wpdb->get_results( $query );
    }

    public static function obtener_marcas(): array {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT DISTINCT marca FROM {$wpdb->prefix}wpca_productos WHERE activo = 1 AND marca <> '' ORDER BY marca"
        );
    }

    public static function recalcular_stock( int $id ): void {
        global $wpdb;
        $t_mov  = $wpdb->prefix . 'wpca_movimientos';
        $t_prod = $wpdb->prefix . 'wpca_productos';
        // stock_inicial es el valor base guardado al crear el producto
        $stock_ini = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(stock_inicial,0) FROM {$t_prod} WHERE id = %d", $id ) );
        $e = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(cantidad),0) FROM {$t_mov} WHERE producto_id = %d AND tipo = 'entrada'", $id ) );
        $s = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(cantidad),0) FROM {$t_mov} WHERE producto_id = %d AND tipo = 'salida'",  $id ) );
        $wpdb->update( $t_prod, [ 'stock_actual' => $stock_ini + $e - $s ], [ 'id' => $id ] );
    }

    public static function variedad_por_marca(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT marca, COUNT(*) AS total FROM {$wpdb->prefix}wpca_productos WHERE activo = 1 AND marca <> '' GROUP BY marca ORDER BY total DESC"
        );
    }

    public static function unidades_por_marca(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT marca, SUM(stock_actual) AS total FROM {$wpdb->prefix}wpca_productos WHERE activo = 1 AND marca <> '' GROUP BY marca ORDER BY total DESC"
        );
    }
}
