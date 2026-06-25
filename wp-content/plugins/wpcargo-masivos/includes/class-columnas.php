<?php
if ( ! defined('ABSPATH') ) exit;

class WCMAS_Columnas {

    public const OPTION_KEY = 'wcmas_columnas_v2';

    private static function defaults(): array {
        return [
            'tipo_envio' => [
                'id'=>'tipo_envio','label'=>'Tipo de Envío','meta_key'=>'wpcargo_type_of_shipment',
                'tipo'=>'select_db','activa'=>true,'obligatorio'=>true,
                'default_val'=>'','opciones'=>[],'placeholder'=>'','ancho'=>'md','orden'=>1,
            ],
            'tipo_programado' => [
                'id'=>'tipo_programado','label'=>'Tipo Programado','meta_key'=>'tipo_programado',
                'tipo'=>'select_db','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'','ancho'=>'md','orden'=>2,
            ],
            'registered_shipper' => [
                'id'=>'registered_shipper','label'=>'Remitente','meta_key'=>'registered_shipper',
                'tipo'=>'shipper','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'','ancho'=>'md','orden'=>3,
            ],
            'dest_nombre' => [
                'id'=>'dest_nombre','label'=>'Destinatario','meta_key'=>'wpcargo_receiver_name',
                'tipo'=>'text','activa'=>true,'obligatorio'=>true,
                'default_val'=>'','opciones'=>[],'placeholder'=>'Nombre completo','ancho'=>'lg','orden'=>5,
            ],
            'dest_direccion' => [
                'id'=>'dest_direccion','label'=>'Dirección','meta_key'=>'wpcargo_receiver_address',
                'tipo'=>'text','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'Av. / Jr. / Calle...','ancho'=>'lg','orden'=>6,
            ],
            'distrito_envio' => [
                'id'=>'distrito_envio','label'=>'Distrito','meta_key'=>'distrito_envio',
                'tipo'=>'select','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>self::get_distritos(),'placeholder'=>'','ancho'=>'md','orden'=>7,
            ],
            'dest_telefono' => [
                'id'=>'dest_telefono','label'=>'Teléfono','meta_key'=>'wpcargo_receiver_phone',
                'tipo'=>'phone','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'9XXXXXXXX','ancho'=>'md','orden'=>8,
            ],
            'modo_de_pago' => [
                'id'=>'modo_de_pago','label'=>'Modo de Pago','meta_key'=>'modo_de_pago',
                'tipo'=>'select_db','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'','ancho'=>'md','orden'=>9,
            ],
            'monto_envio' => [
                'id'=>'monto_envio','label'=>'Monto Envío S/','meta_key'=>'monto_envio',
                'tipo'=>'number','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'0.00','ancho'=>'sm','orden'=>10,
            ],
            'listo_cobrar_producto' => [
                'id'=>'listo_cobrar_producto','label'=>'Cobrar Producto','meta_key'=>'listo_cobrar_producto',
                'tipo'=>'select','activa'=>true,'obligatorio'=>false,
                'default_val'=>'no','opciones'=>['no','si'],'placeholder'=>'','ancho'=>'sm','orden'=>11,
            ],
            'listo_monto_producto' => [
                'id'=>'listo_monto_producto','label'=>'Monto Producto S/','meta_key'=>'listo_monto_producto',
                'tipo'=>'number','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'0.00','ancho'=>'sm','orden'=>12,
            ],
            'listo_monto_total' => [
                'id'=>'listo_monto_total','label'=>'Monto Total S/','meta_key'=>'listo_monto_total',
                'tipo'=>'number_readonly','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'0.00','ancho'=>'sm','orden'=>13,
            ],
            'comentario_envio' => [
                'id'=>'comentario_envio','label'=>'Comentario','meta_key'=>'comentario_envio',
                'tipo'=>'text','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'Indicaciones de entrega','ancho'=>'lg','orden'=>14,
            ],
        ];
    }

    public static function get_distritos(): array {
        return [
            'SAN MIGUEL','PUEBLO LIBRE','CERCADO DE LIMA','BREÑA','MAGDALENA DEL MAR',
            'JESUS MARIA','SAN ISIDRO','LINCE','LA VICTORIA','SAN LUIS','RIMAC',
            'SAN JUAN DE LURIGANCHO','EL AGUSTINO','SANTA ANITA','SALAMANCA / ATE',
            'SAN BORJA','MIRAFLORES','SURQUILLO','SANTIAGO DE SURCO','BARRANCO',
            'VILLA EL SALVADOR','SAN JUAN DE MIRAFLORES','COMAS','LOS OLIVOS',
            'INDEPENDENCIA','SAN MARTIN DE PORRES','CALLAO','CHORRILLOS','ATE',
            'LA MOLINA','SANTA CLARA - ATE','LA PUNTA / CALLAO','PUENTE PIEDRA',
            'CARABAYLLO','COLLIQUE','VILLA MARIA DEL TRIUNFO','OQUENDO / MARQUEZ',
            'VMT JOSE GALVEZ','JICAMARCA ANEXO 22','VENTANILLA','HUACHIPA','HUAYCAN',
            'LURIN','CARAPONGO','CHACLACAYO / ÑAÑA','ANCON','PACHACAMAC','MANCHAY',
            'CIENEGUILLA','JICAMARCA ANEXO 8','LURIGANCHO','CHOSICA','CAJAMARQUILLA',
        ];
    }

    public static function get_tarifa_distrito( string $distrito ): int {
        $tarifas = [
            'SAN MIGUEL'=>8,'PUEBLO LIBRE'=>8,'CERCADO DE LIMA'=>8,'BREÑA'=>8,
            'MAGDALENA DEL MAR'=>8,'JESUS MARIA'=>8,'SAN ISIDRO'=>8,'LINCE'=>8,
            'LA VICTORIA'=>8,'SAN LUIS'=>8,'RIMAC'=>8,'SAN JUAN DE LURIGANCHO'=>8,
            'EL AGUSTINO'=>8,'SANTA ANITA'=>8,'SALAMANCA / ATE'=>8,'SAN BORJA'=>8,
            'MIRAFLORES'=>8,'SURQUILLO'=>8,'SANTIAGO DE SURCO'=>8,'BARRANCO'=>8,
            'VILLA EL SALVADOR'=>10,'SAN JUAN DE MIRAFLORES'=>10,'COMAS'=>10,
            'LOS OLIVOS'=>10,'INDEPENDENCIA'=>10,'SAN MARTIN DE PORRES'=>10,
            'CALLAO'=>10,'CHORRILLOS'=>10,'ATE'=>10,'LA MOLINA'=>10,
            'SANTA CLARA - ATE'=>12,'LA PUNTA / CALLAO'=>12,'PUENTE PIEDRA'=>12,
            'CARABAYLLO'=>12,'COLLIQUE'=>12,'VILLA MARIA DEL TRIUNFO'=>12,
            'OQUENDO / MARQUEZ'=>14,'VMT JOSE GALVEZ'=>14,'JICAMARCA ANEXO 22'=>14,
            'VENTANILLA'=>14,'HUACHIPA'=>14,'HUAYCAN'=>14,'LURIN'=>15,'CARAPONGO'=>15,
            'CHACLACAYO / ÑAÑA'=>16,'ANCON'=>18,'PACHACAMAC'=>18,'MANCHAY'=>18,
            'CIENEGUILLA'=>18,'JICAMARCA ANEXO 8'=>18,'LURIGANCHO'=>18,'CHOSICA'=>18,
            'CAJAMARQUILLA'=>20,
        ];
        return $tarifas[ strtoupper(trim($distrito)) ] ?? 0;
    }

    public static function instalar_defaults(): void {
        if ( ! get_option(self::OPTION_KEY) ) {
            update_option(self::OPTION_KEY, self::defaults(), false);
        }
    }

    public static function obtener_todas(): array {
        $cols = get_option(self::OPTION_KEY, []);
        if ( empty($cols) ) $cols = self::defaults();
        uasort($cols, fn($a,$b) => ($a['orden']??99) <=> ($b['orden']??99));
        return $cols;
    }

    public static function obtener_activas(): array {
        return array_filter(self::obtener_todas(), fn($c) => !empty($c['activa']));
    }

    public static function obtener_por_id( string $id ): ?array {
        return self::obtener_todas()[$id] ?? null;
    }

    public static function guardar( array $datos, string $id_original = '' ): true|\WP_Error {
        $id = sanitize_key($datos['id'] ?? '');
        if ( !$id || !($datos['label'] ?? '') || !($datos['meta_key'] ?? '') ) {
            return new \WP_Error('req', 'ID, etiqueta y meta_key son obligatorios.');
        }
        $cols  = self::obtener_todas();
        if ( $id_original && $id_original !== $id ) unset($cols[$id_original]);
        $orden = isset($cols[$id]) ? ($cols[$id]['orden'] ?? 99) : (empty($cols) ? 1 : max(array_column($cols,'orden')) + 1);
        $tipos_validos = ['text','number','phone','email','select','select_db','textarea','shipper','number_readonly'];
        $cols[$id] = [
            'id'          => $id,
            'label'       => sanitize_text_field($datos['label']),
            'meta_key'    => sanitize_text_field($datos['meta_key']),
            'tipo'        => in_array($datos['tipo']??'text', $tipos_validos) ? $datos['tipo'] : 'text',
            'activa'      => !empty($datos['activa']),
            'obligatorio' => !empty($datos['obligatorio']),
            'default_val' => sanitize_text_field($datos['default_val'] ?? ''),
            'opciones'    => self::parsear_opciones($datos['opciones'] ?? ''),
            'placeholder' => sanitize_text_field($datos['placeholder'] ?? ''),
            'ancho'       => in_array($datos['ancho']??'md',['sm','md','lg']) ? $datos['ancho'] : 'md',
            'orden'       => intval($datos['orden'] ?? $orden),
        ];
        update_option(self::OPTION_KEY, $cols, false);
        return true;
    }

    public static function eliminar( string $id ): void {
        $cols = self::obtener_todas(); unset($cols[$id]);
        update_option(self::OPTION_KEY, $cols, false);
    }

    public static function reordenar( array $orden_ids ): void {
        $cols = self::obtener_todas();
        foreach ( $orden_ids as $pos => $id ) {
            if ( isset($cols[$id]) ) $cols[$id]['orden'] = $pos + 1;
        }
        update_option(self::OPTION_KEY, $cols, false);
    }

    private static function parsear_opciones( $raw ): array {
        if ( is_array($raw) ) return array_map('sanitize_text_field', array_filter($raw));
        return array_filter(array_map('trim', explode("\n", $raw)));
    }

    /** Opciones de modo_de_pago desde la BD de WPCargo */
    public static function get_modos_de_pago(): array {
        // Primero intentar desde postmeta
        $rows = self::get_meta_values_distintos('modo_de_pago');
        if ( ! empty($rows) ) return $rows;
        
        // Fallback: intentar leer desde wp_wpcargo_custom_fields
        global $wpdb;
        $custom = $wpdb->get_col($wpdb->prepare(
            "SELECT field_data FROM {$wpdb->prefix}wpcargo_custom_fields WHERE field_key = %s LIMIT 1",
            'payment_wpcargo_mode_field'
        ));
        if ( ! empty($custom) ) {
            $data = maybe_unserialize($custom[0]);
            if ( is_array($data) && ! empty($data) ) {
                return array_values(array_filter(array_map('trim', $data)));
            }
        }
        
        return ['Efectivo','Yape','Transferencia'];
    }

    /** Opciones de tipo_envio desde la BD (solo "Envío Programado") */
    public static function get_tipos_envio(): array {
        $rows = self::get_meta_values_distintos('wpcargo_type_of_shipment', "AND meta_value = 'Envío Programado'");
        if ( ! empty($rows) ) return $rows;
        
        // Fallback: intentar leer desde wp_wpcargo_custom_fields
        global $wpdb;
        $custom = $wpdb->get_col($wpdb->prepare(
            "SELECT field_data FROM {$wpdb->prefix}wpcargo_custom_fields WHERE field_key = %s LIMIT 1",
            'wpcargo_type_of_shipment'
        ));
        if ( ! empty($custom) ) {
            $data = maybe_unserialize($custom[0]);
            if ( is_array($data) && ! empty($data) ) {
                return array_values(array_filter(array_map('trim', $data)));
            }
        }
        
        return ['Envío Programado'];
    }

    /** Opciones de tipo_programado desde la BD (excluye "Agencia") */
    public static function get_tipos_programado(): array {
        $rows = self::get_meta_values_distintos('tipo_programado', "AND meta_value <> 'Agencia'");
        if ( ! empty($rows) ) return $rows;
        
        // Fallback: valores por defecto comunes
        return ['Domicilio','Mercado Flex'];
    }

    private static function get_meta_values_distintos( string $meta_key, string $extra_where = '' ): array {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_value
             FROM {$wpdb->prefix}postmeta
             WHERE meta_key = %s
             AND meta_value != ''
             {$extra_where}
             ORDER BY meta_value ASC",
            $meta_key
        );
        $rows = $wpdb->get_col($query);
        return is_array($rows) ? array_values(array_filter(array_map('strval', $rows))) : [];
    }

    public static function para_js( bool $solo_activas = true ): string {
        $cols = $solo_activas ? self::obtener_activas() : self::obtener_todas();
        $modos_pago = null;
        $tipos_envio = null;
        $tipos_programado = null;
        foreach ( $cols as &$c ) {
            $col_id = $c['id'] ?? '';
            $meta_key = $c['meta_key'] ?? '';

            // Mantener estos campos clave siempre como select_db aunque la config haya sido alterada.
            if ( in_array($col_id, ['tipo_envio','tipo_programado','modo_de_pago'], true) ) {
                $c['tipo'] = 'select_db';
            }

            if ( $c['tipo'] !== 'select_db' ) continue;

            if ( $meta_key === 'modo_de_pago' || $col_id === 'modo_de_pago' ) {
                if ( $modos_pago === null ) $modos_pago = self::get_modos_de_pago();
                $c['opciones'] = $modos_pago;
                continue;
            }

            if ( $meta_key === 'wpcargo_type_of_shipment' || $col_id === 'tipo_envio' ) {
                if ( $tipos_envio === null ) $tipos_envio = self::get_tipos_envio();
                $c['opciones'] = $tipos_envio;
                continue;
            }

            if ( $meta_key === 'tipo_programado' || $col_id === 'tipo_programado' ) {
                if ( $tipos_programado === null ) $tipos_programado = self::get_tipos_programado();
                $c['opciones'] = $tipos_programado;
            }
        }
        unset($c);
        return wp_json_encode(array_values(array_map(function($c) {
            return [
                'id'          => $c['id'],
                'label'       => $c['label'],
                'meta_key'    => $c['meta_key'],
                'tipo'        => $c['tipo'],
                'activa'      => (bool)$c['activa'],
                'obligatorio' => (bool)$c['obligatorio'],
                'default_val' => $c['default_val'] ?? '',
                'opciones'    => $c['opciones'] ?? [],
                'placeholder' => $c['placeholder'] ?? '',
                'ancho'       => $c['ancho'] ?? 'md',
            ];
        }, $cols)));
    }
}

