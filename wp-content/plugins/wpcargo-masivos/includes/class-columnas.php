<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Meta keys confirmados del plugin oficial import/export de WPCargo:
 * wpcargo_receiver_name, wpcargo_receiver_phone, wpcargo_receiver_address,
 * wpcargo_receiver_email, wpcargo_weight, wpcargo_comments,
 * wpcargo_destination, wpcargo_origin_field, wpcargo_qty,
 * wpcargo_total_freight, wpcargo_type_of_shipment, etc.
 */
class WCMAS_Columnas {

    const OPTION_KEY = 'wcmas_columnas_v2';

    private static function defaults(): array {
        return [
            'dest_nombre' => [
                'id'=>'dest_nombre','label'=>'Destinatario','meta_key'=>'wpcargo_receiver_name',
                'tipo'=>'text','activa'=>true,'obligatorio'=>true,
                'default_val'=>'','opciones'=>[],'placeholder'=>'Nombre completo','ancho'=>'lg','orden'=>1,
            ],
            'dest_telefono' => [
                'id'=>'dest_telefono','label'=>'Teléfono','meta_key'=>'wpcargo_receiver_phone',
                'tipo'=>'phone','activa'=>true,'obligatorio'=>true,
                'default_val'=>'','opciones'=>[],'placeholder'=>'9XXXXXXXX','ancho'=>'md','orden'=>2,
            ],
            'dest_direccion' => [
                'id'=>'dest_direccion','label'=>'Dirección','meta_key'=>'wpcargo_receiver_address',
                'tipo'=>'text','activa'=>true,'obligatorio'=>true,
                'default_val'=>'','opciones'=>[],'placeholder'=>'Av. / Jr. / Calle...','ancho'=>'lg','orden'=>3,
            ],
            'dest_distrito' => [
                'id'=>'dest_distrito','label'=>'Distrito','meta_key'=>'wpcargo_destination',
                'tipo'=>'text','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'Ej: Miraflores','ancho'=>'md','orden'=>4,
            ],
            'monto' => [
                'id'=>'monto','label'=>'Monto S/','meta_key'=>'wpcargo_total_freight',
                'tipo'=>'number','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'0.00','ancho'=>'sm','orden'=>5,
            ],
            'referencia' => [
                'id'=>'referencia','label'=>'Referencia','meta_key'=>'wpcargo_carrier_ref_number',
                'tipo'=>'text','activa'=>true,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'N° pedido, código...','ancho'=>'md','orden'=>6,
            ],
            'notas' => [
                'id'=>'notas','label'=>'Notas','meta_key'=>'wpcargo_comments',
                'tipo'=>'text','activa'=>false,'obligatorio'=>false,
                'default_val'=>'','opciones'=>[],'placeholder'=>'Indicaciones de entrega','ancho'=>'lg','orden'=>7,
            ],
        ];
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
        $cols[$id] = [
            'id'          => $id,
            'label'       => sanitize_text_field($datos['label']),
            'meta_key'    => sanitize_text_field($datos['meta_key']),
            'tipo'        => in_array($datos['tipo']??'text',['text','number','phone','email','select','textarea','date']) ? $datos['tipo'] : 'text',
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

    public static function para_js( bool $solo_activas = true ): string {
        $cols = $solo_activas ? self::obtener_activas() : self::obtener_todas();
        return wp_json_encode(array_values(array_map(fn($c) => [
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
        ], $cols)));
    }
}
