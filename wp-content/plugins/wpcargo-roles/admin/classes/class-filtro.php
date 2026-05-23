<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCROL_Filtro {

    public function __construct() {
        // Prioridad 0: solo observar y capturar (nunca modifica)
        add_filter('wpcfe_after_sidebar_menus', [WCROL_Modulos::class, 'capturar_sidebar_real'], 0, 1);

        // Prioridad 999: filtrar según permisos del usuario
        add_filter('wpcfe_after_sidebar_menus', [$this, 'filtrar'],  999, 1);
        add_filter('wpcfe_sidebar_menus',        [$this, 'filtrar'],  999, 1);
    }

    public function filtrar( array $menu ): array {
        if ( ! is_user_logged_in() ) return $menu;
        $user_id = get_current_user_id();

        // WP Admins sin restricciones configuradas: acceso total
        if ( wcrol_puede_gestionar() && WCROL_Permisos::es_sin_restriccion($user_id) ) {
            return $menu;
        }

        return WCROL_Permisos::filtrar_sidebar($menu, $user_id);
    }
}

new WCROL_Filtro();
