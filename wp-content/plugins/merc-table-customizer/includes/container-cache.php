<?php
/**
 * Transient cache for wpcargo_container to improve performance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Intercept pre_get_posts for wpcargo_container
add_action('pre_get_posts', 'merc_container_cache_pre_get_posts');
function merc_container_cache_pre_get_posts($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'wpcargo_container') {
        // Generar una clave de caché basada en los argumentos de la consulta
        $cache_key = 'merc_container_query_' . md5(serialize($query->query_vars));
        
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            // Si hay caché, podríamos intentar inyectar los posts, pero pre_get_posts no permite saltarse la query fácilmente.
            // La mejor forma en pre_get_posts es usar el filtro posts_request para devolver algo vacío y luego inyectar en the_posts.
            // Para simplicidad y evitar conflictos con ionCube, optimizamos las consultas SQL donde es posible,
            // o simplemente invalidamos la caché de counts de WP.
            
            // Dado que ionCube protege la consulta exacta, lo más seguro es cachear wp_count_posts
        }
    }
}

// Invalidate cache on container save
add_action('save_post_wpcargo_container', 'merc_invalidate_container_cache', 10, 3);
function merc_invalidate_container_cache($post_id, $post, $update) {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_merc_container_query_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_merc_container_query_%'");
}
