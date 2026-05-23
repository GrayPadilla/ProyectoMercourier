<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Catálogo de módulos.
 *
 * ESTRATEGIA DE MATCHING (corregida):
 * - Cada módulo tiene un page_id (ID de la página WP con template dashboard.php)
 * - El filtro del sidebar compara item['page-id'] contra los page_ids permitidos
 * - Para ítems de plugins sin página (ej: wpcv-menu), se guarda el 'sidebar_key'
 *   y se compara contra ese key directamente
 *
 * CAPTURA DE MÓDULOS REALES:
 * - Al cargar cualquier página del dashboard, se captura el array completo
 *   del sidebar via wpcfe_after_sidebar_menus con prioridad 0
 * - Se almacena en un transient que el admin lee para sincronizar
 */
class WCROL_Modulos {

    const OPTION_KEY   = 'wcrol_modulos_catalogo';
    const CAPTURE_KEY  = 'wcrol_sidebar_capturado';

    public static function registrar_defaults(): void {
        // Solo insertar si no existe nada aún
        if ( get_option(self::OPTION_KEY) ) return;
        update_option(self::OPTION_KEY, [], false);
    }

    /** Retorna catálogo completo guardado */
    public static function obtener_todos(): array {
        $guardados = get_option(self::OPTION_KEY, []);
        return is_array($guardados) ? $guardados : [];
    }

    /** Retorna un módulo por slug */
    public static function obtener( string $slug ): ?array {
        return self::obtener_todos()[$slug] ?? null;
    }

    /**
     * Captura los ítems reales del sidebar de WPCargo.
     * Se llama con prioridad 0 (antes de todo) para ver el array sin filtrar.
     * Guarda en transient para que el admin pueda sincronizar.
     */
    public static function capturar_sidebar_real( array $menu ): array {
        if ( ! empty($menu) ) {
            set_transient(self::CAPTURE_KEY, $menu, DAY_IN_SECONDS);
        }
        return $menu; // no modificar, solo observar
    }

    /**
     * Sincroniza el catálogo con los ítems capturados del sidebar real.
     * Combina con páginas que tengan template dashboard.php.
     * Solo añade módulos nuevos, nunca sobreescribe los ya configurados.
     */
    public static function sincronizar(): int {
        $catalogo = self::obtener_todos();
        $nuevos   = 0;

        // 1. Páginas WordPress con template dashboard.php
        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [[
                'key'   => '_wp_page_template',
                'value' => 'dashboard.php',
            ]],
        ]);

        foreach ( $pages as $page ) {
            // Buscar si ya existe en el catálogo por page_id
            $ya_existe = false;
            foreach ( $catalogo as $mod ) {
                if ( isset($mod['page_id']) && (int)$mod['page_id'] === $page->ID ) {
                    $ya_existe = true; break;
                }
            }
            if ( $ya_existe ) continue;

            $slug_mod = 'page_' . $page->ID; // slug único garantizado
            // Limpiar ícono del meta (viene como "fa fa-money mr-3")
            $icon_raw = get_post_meta($page->ID, 'wpcfe_menu_icon', true) ?: '';
            preg_match('/fa-[\w-]+/', $icon_raw, $m);
            $icon = $m[0] ?? 'fa-circle-o';

            $catalogo[$slug_mod] = [
                'slug'        => $slug_mod,
                'label'       => $page->post_title,
                'icon'        => $icon,
                'fuente'      => 'pagina',
                'page_id'     => $page->ID,
                'sidebar_key' => '', // no tiene key de plugin
            ];
            $nuevos++;
        }

        // 2. Ítems del sidebar capturados (añadidos por plugins via wpcfe_after_sidebar_menus)
        $capturado = get_transient(self::CAPTURE_KEY);
        if ( is_array($capturado) ) {
            foreach ( $capturado as $sidebar_key => $item ) {
                // Buscar si ya existe por sidebar_key o page_id
                $ya_existe = false;
                $page_id   = (int)($item['page-id'] ?? 0);
                foreach ( $catalogo as $mod ) {
                    $match_key = isset($mod['sidebar_key']) && $mod['sidebar_key'] === $sidebar_key;
                    $match_pid = $page_id && isset($mod['page_id']) && (int)$mod['page_id'] === $page_id;
                    if ( $match_key || $match_pid ) { $ya_existe = true; break; }
                }
                if ( $ya_existe ) continue;

                // Evitar duplicar páginas ya añadidas en el paso anterior
                if ( $page_id ) {
                    $slug_mod = 'page_' . $page_id;
                    if ( isset($catalogo[$slug_mod]) ) {
                        // Actualizar el sidebar_key del existente
                        $catalogo[$slug_mod]['sidebar_key'] = $sidebar_key;
                        continue;
                    }
                }

                $slug_mod = sanitize_key($sidebar_key) ?: 'item_' . md5($sidebar_key);
                $icon_raw = $item['icon'] ?? '';
                preg_match('/fa-[\w-]+/', $icon_raw, $m);

                $catalogo[$slug_mod] = [
                    'slug'        => $slug_mod,
                    'label'       => $item['label'] ?? $sidebar_key,
                    'icon'        => $m[0] ?? ($icon_raw ?: 'fa-circle-o'),
                    'fuente'      => 'plugin',
                    'page_id'     => $page_id,
                    'sidebar_key' => $sidebar_key,
                ];
                $nuevos++;
            }
        }

        update_option(self::OPTION_KEY, $catalogo, false);
        return $nuevos;
    }

    /** Guardar o editar un módulo del catálogo */
    public static function guardar( array $datos, string $slug_original = '' ): true|\WP_Error {
        $slug = $datos['slug'] ?? '';
        if ( ! $slug || ! ($datos['label'] ?? '') ) return new \WP_Error('req','Campos obligatorios.');

        $catalogo = self::obtener_todos();
        if ( $slug_original && $slug_original !== $slug ) unset($catalogo[$slug_original]);

        $catalogo[$slug] = array_merge($catalogo[$slug] ?? [], [
            'slug'        => $slug,
            'label'       => sanitize_text_field($datos['label']),
            'icon'        => sanitize_text_field($datos['icon'] ?? 'fa-circle-o'),
            'page_id'     => intval($datos['page_id'] ?? 0),
            'sidebar_key' => sanitize_text_field($datos['sidebar_key'] ?? ''),
            'fuente'      => $catalogo[$slug]['fuente'] ?? 'manual',
        ]);
        update_option(self::OPTION_KEY, $catalogo, false);
        return true;
    }

    public static function eliminar( string $slug ): void {
        $catalogo = self::obtener_todos();
        unset($catalogo[$slug]);
        update_option(self::OPTION_KEY, $catalogo, false);
    }
}
