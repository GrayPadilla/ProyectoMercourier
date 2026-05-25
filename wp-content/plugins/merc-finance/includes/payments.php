<?php
if (!defined('ABSPATH')) exit;

// Funciones relacionadas con pagos y liquidaciones

/**
 * Obtener total de penalidades para un usuario (usado por sistemas de liquidación)
 */
add_action('merc_get_user_penalties_total_for_liquidation', 'merc_get_user_penalties_total_for_liquidation');
function merc_get_user_penalties_total_for_liquidation($user_id) {
    $args = array(
        'post_type'      => 'merc_penalty',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array('key' => 'penalty_user', 'value' => $user_id, 'compare' => '=')
        )
    );
    
    $q = new WP_Query($args);
    $total = 0;
    
    if ($q->have_posts()) {
        foreach ($q->posts as $post) {
            $amount = get_post_meta($post->ID, 'penalty_amount', true);
            $status = get_post_meta($post->ID, 'penalty_status', true);
            
            if ($status !== 'paid') {
                $total += floatval($amount);
            }
        }
    }
    
    return $total;
}
