<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCA_Database {

    public static function crear_tablas(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $t_prod = $wpdb->prefix . 'wpca_productos';
        $sql_prod = "CREATE TABLE {$t_prod} (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo        VARCHAR(50)  NOT NULL,
            descripcion   TEXT         NOT NULL,
            marca         VARCHAR(100) NOT NULL DEFAULT '',
            unidad        VARCHAR(30)  NOT NULL DEFAULT 'UND',
            stock_actual  INT          NOT NULL DEFAULT 0,
            stock_inicial INT          NOT NULL DEFAULT 0,
            stock_minimo  INT          NOT NULL DEFAULT 0,
            imagen        VARCHAR(255) NOT NULL DEFAULT '',
            activo        TINYINT(1)   NOT NULL DEFAULT 1,
            creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_codigo (codigo),
            KEY idx_marca (marca)
        ) {$charset};";

        $t_mov = $wpdb->prefix . 'wpca_movimientos';
        $sql_mov = "CREATE TABLE {$t_mov} (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo            ENUM('entrada','salida') NOT NULL,
            producto_id     INT UNSIGNED NOT NULL,
            cantidad        INT          NOT NULL,
            lote            VARCHAR(100) NOT NULL DEFAULT '0',
            nro_documento   VARCHAR(100) NOT NULL DEFAULT '',
            fecha           DATE         NOT NULL,
            notas           TEXT,
            creado_por      INT UNSIGNED NOT NULL DEFAULT 0,
            creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_producto (producto_id),
            KEY idx_tipo (tipo),
            KEY idx_fecha (fecha)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_prod );
        dbDelta( $sql_mov );
    }
}
