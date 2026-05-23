<?php
if ( ! defined('ABSPATH') ) exit;

class WCMAS_Historial {

    const TABLE = 'wcmas_historial';

    public static function crear_tabla(): void {
        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE;
        $c     = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE {$tabla} (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id     VARCHAR(40)  NOT NULL,
            user_id        INT UNSIGNED NOT NULL,
            asignado_a     INT UNSIGNED NOT NULL,
            total_filas    INT UNSIGNED NOT NULL DEFAULT 0,
            total_ok       INT UNSIGNED NOT NULL DEFAULT 0,
            total_errores  INT UNSIGNED NOT NULL DEFAULT 0,
            trackings_json TEXT,
            errores_json   TEXT,
            fecha          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY fecha (fecha)
        ) $c;");
    }

    public static function registrar( array $resultados, int $user_id, int $asignado_a ): int {
        global $wpdb;
        $ok       = array_filter($resultados, fn($r) => $r['ok']);
        $errores  = array_filter($resultados, fn($r) => !$r['ok']);
        $trackings= array_map(fn($r) => ['fila'=>$r['fila_num'],'tracking'=>$r['tracking'],'post_id'=>$r['post_id']], $ok);
        $errs_log = array_map(fn($r) => ['fila'=>$r['fila_num'],'errores'=>$r['errores']], $errores);

        $wpdb->insert($wpdb->prefix . self::TABLE, [
            'session_id'    => wp_generate_uuid4(),
            'user_id'       => $user_id,
            'asignado_a'    => $asignado_a,
            'total_filas'   => count($resultados),
            'total_ok'      => count($ok),
            'total_errores' => count($errores),
            'trackings_json'=> wp_json_encode(array_values($trackings)),
            'errores_json'  => wp_json_encode(array_values($errs_log)),
            'fecha'         => current_time('mysql'),
        ]);
        return $wpdb->insert_id;
    }

    public static function obtener( int $limit = 50, int $offset = 0, int $user_id = 0 ): array {
        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE;
        $where = $user_id ? $wpdb->prepare('WHERE h.user_id = %d', $user_id) : '';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, u1.display_name AS cargado_por, u2.display_name AS asignado_nombre
             FROM {$tabla} h
             LEFT JOIN {$wpdb->users} u1 ON u1.ID = h.user_id
             LEFT JOIN {$wpdb->users} u2 ON u2.ID = h.asignado_a
             {$where}
             ORDER BY h.fecha DESC LIMIT %d OFFSET %d",
            $limit, $offset
        )) ?: [];
    }

    public static function obtener_por_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT h.*, u1.display_name AS cargado_por, u2.display_name AS asignado_nombre
             FROM {$wpdb->prefix}".self::TABLE." h
             LEFT JOIN {$wpdb->users} u1 ON u1.ID = h.user_id
             LEFT JOIN {$wpdb->users} u2 ON u2.ID = h.asignado_a
             WHERE h.id = %d", $id
        )) ?: null;
    }

    public static function total( int $user_id = 0 ): int {
        global $wpdb;
        $where = $user_id ? $wpdb->prepare('WHERE user_id = %d', $user_id) : '';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}".self::TABLE." $where");
    }
}
