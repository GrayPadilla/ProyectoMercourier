<?php
if (!defined('ABSPATH')) exit;

// Registrar post types para finanzas
add_action('init', 'merc_finance_register_post_types');
function merc_finance_register_post_types() {
    // Post type: Penalidades
    $args = array(
        'label'              => 'Penalidades',
        'singular_name'      => 'Penalidad',
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'capability_type'    => 'post',
        'supports'           => array('title', 'editor', 'custom-fields'),
        'has_archive'        => false,
        'rewrite'            => false,
    );
    register_post_type('merc_penalty', $args);
}
