<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCROL_Permisos {

    const META_KEY = 'wcrol_permisos';

    /** NULL = sin restricción (acceso total) */
    public static function obtener( int $user_id ): ?array {
        $raw = get_user_meta($user_id, self::META_KEY, true);
        if ( $raw === '' || $raw === false ) return null;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function es_sin_restriccion( int $user_id ): bool {
        return self::obtener($user_id) === null;
    }

    public static function guardar( int $user_id, array $slugs ): void {
        update_user_meta($user_id, self::META_KEY, wp_json_encode(array_values($slugs)));
    }

    public static function quitar_restricciones( int $user_id ): void {
        delete_user_meta($user_id, self::META_KEY);
    }

    public static function obtener_usuarios(): array {
        $users = get_users([
            'role__in' => ['administrator', WCROL_Rol_WPCargo::SLUG],
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'number'   => 300,
        ]);
        $resultado = [];
        foreach ( $users as $user ) {
            $permisos    = self::obtener($user->ID);
            $tipo        = WCROL_Rol_WPCargo::tipo_acceso($user->ID);
            $resultado[] = [
                'user'            => $user,
                'permisos'        => $permisos,
                'sin_restriccion' => ($permisos === null),
                'num_modulos'     => $permisos === null ? 'Todos' : count($permisos),
                'tipo_acceso'     => $tipo,
            ];
        }
        return $resultado;
    }

    /**
     * Filtra el array del sidebar de WPCargo.
     *
     * LÓGICA CORREGIDA:
     * Cada ítem del sidebar tiene un 'page-id'.
     * Cada módulo del catálogo tiene un page_id y/o sidebar_key.
     * Un ítem está PERMITIDO si:
     *   a) Su page-id coincide con el page_id de un módulo permitido, O
     *   b) Su sidebar_key (la clave del array) coincide con el sidebar_key de un módulo permitido
     */
    public static function filtrar_sidebar( array $menu, int $user_id ): array {
        $slugs_permitidos = self::obtener($user_id);
        if ( $slugs_permitidos === null ) return $menu; // acceso total

        $catalogo = WCROL_Modulos::obtener_todos();

        // Construir índices rápidos de lookup
        $page_ids_permitidos   = []; // page_id  → true
        $sidebar_keys_permitidos = []; // sidebar_key → true

        foreach ( $slugs_permitidos as $slug ) {
            if ( ! isset($catalogo[$slug]) ) continue;
            $mod = $catalogo[$slug];
            if ( ! empty($mod['page_id']) ) {
                $page_ids_permitidos[(int)$mod['page_id']] = true;
            }
            if ( ! empty($mod['sidebar_key']) ) {
                $sidebar_keys_permitidos[$mod['sidebar_key']] = true;
            }
        }

        $filtrado = [];
        foreach ( $menu as $key => $item ) {
            $page_id = (int)($item['page-id'] ?? 0);
            $permitido = false;

            // Verificar por page_id
            if ( $page_id && isset($page_ids_permitidos[$page_id]) ) {
                $permitido = true;
            }
            // Verificar por sidebar_key
            elseif ( isset($sidebar_keys_permitidos[$key]) ) {
                $permitido = true;
            }

            if ( $permitido ) $filtrado[$key] = $item;
        }

        return $filtrado;
    }
}
