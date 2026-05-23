<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Allow shortcode in the Widgets
add_filter( 'widget_text', 'do_shortcode' );
// Register Widgets
function wpcfe_widgets_init_callback() {
    $dashboard_top = array(
        'name'          => __( 'FM Dashboard- Top', 'wpcargo-frontend-manager' ),
        'id'            => 'wpcfe-dashboard-top',
        'description'   => __( 'This widget display in the Frontend Manager dashboard report top area page.', 'wpcargo-frontend-manager' ),
        'before_widget' => '<section id="%1$s" class="col-lg-12 mb-4 wpcfe-widget widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="wpcfe-widgettitle widgettitle">',
        'after_title'   => '</h2>',
    );
    $dashboard_middle = array(
        'name'          => __( 'FM Dashboard- Middle', 'wpcargo-frontend-manager' ),
        'id'            => 'wpcfe-dashboard-middle',
        'description'   => __( 'This widget display in the Frontend Manager dashboard report middle area page.', 'wpcargo-frontend-manager' ),
        'before_widget' => '<section id="%1$s" class="col-lg-12 mb-4 wpcfe-widget widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="wpcfe-widgettitle widgettitle">',
        'after_title'   => '</h2>',
    );
    $dashboard_bottom = array(
        'name'          => __( 'FM Dashboard- Bottom', 'wpcargo-frontend-manager' ),
        'id'            => 'wpcfe-dashboard-bottom',
        'description'   => __( 'This widget display in the Frontend Manager dashboard report bottom area page.', 'wpcargo-frontend-manager' ),
        'before_widget' => '<section id="%1$s" class="col-lg-12 mb-4 wpcfe-widget widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="wpcfe-widgettitle widgettitle">',
        'after_title'   => '</h2>',
    );
    $shipment_top = array(
        'name'          => __( 'FM Shipment - Top', 'wpcargo-frontend-manager' ),
        'id'            => 'wpcfe-shipment-top',
        'description'   => __( 'This widget display in the Frontend Manager dashboard shipment top area page.', 'wpcargo-frontend-manager' ),
        'before_widget' => '<section id="%1$s" class="col-lg-12 mb-4 wpcfe-widget widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="wpcfe-widgettitle widgettitle">',
        'after_title'   => '</h2>',
    );
    $shipment_bottom = array(
        'name'          => __( 'FM Shipment - Bottom', 'wpcargo-frontend-manager' ),
        'id'            => 'wpcfe-shipment-bottom',
        'description'   => __( 'This widget display in the Frontend Manager dashboard shipment bottom area page.', 'wpcargo-frontend-manager' ),
        'before_widget' => '<section id="%1$s" class="col-lg-12 mb-4 wpcfe-widget widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="wpcfe-widgettitle widgettitle">',
        'after_title'   => '</h2>',
    );
    register_sidebar($dashboard_top);
    register_sidebar($dashboard_middle);
    register_sidebar($dashboard_bottom);
    register_sidebar($shipment_top);
    register_sidebar($shipment_bottom);
}
add_action( 'widgets_init', 'wpcfe_widgets_init_callback' );
// Widgets Callbacks - Dashboard
function wpcfe_before_dashboard_widget_callback(){
    if ( is_active_sidebar( 'wpcfe-dashboard-top' ) ):
        ?><div id="wpcfe-dashboard-top" class="wpcfe-widget-wrapper bg-white row my-4 py-4"><?php
            dynamic_sidebar('wpcfe-dashboard-top');
        ?></div><?php
    endif;
}
function wpcfe_after_dashboard_widget_callback(){
    if ( is_active_sidebar( 'wpcfe-dashboard-middle' ) ):
        ?><div id="wpcfe-dashboard-middle" class="wpcfe-widget-wrapper bg-white row my-4 py-4"><?php
            dynamic_sidebar('wpcfe-dashboard-middle');
        ?></div><?php
    endif;
}
function wpcfe_bottom_dashboard_widget_callback(){
    if ( is_active_sidebar( 'wpcfe-dashboard-bottom' ) ):
        ?><div id="wpcfe-dashboard-bottom" class="wpcfe-widget-wrapper bg-white row my-4 py-4" style="margin-bottom:102px !important;"><?php
            dynamic_sidebar('wpcfe-dashboard-bottom');
        ?></div><?php
    endif;
}
// Widgets Callbacks - Shipments
function wpcfe_top_shipment_widget_callback(){
    if ( is_active_sidebar( 'wpcfe-shipment-top' ) ):
        ?><div id="wpcfe-shipment-top" class="wpcfe-widget-wrapper bg-white row my-4 py-4"><?php
            dynamic_sidebar('wpcfe-shipment-top');
        ?></div><?php
    endif;
}
function wpcfe_bottom_shipment_widget_callback(){
    if ( is_active_sidebar( 'wpcfe-shipment-bottom' ) ):
        ?><div id="wpcfe-shipment-bottom" class="wpcfe-widget-wrapper bg-white row my-4 py-4" style="margin-bottom:102px !important;"><?php
            dynamic_sidebar('wpcfe-shipment-bottom');
        ?></div><?php
    endif;
}
add_action( 'wpcfe_before_dashboard_status_report', 'wpcfe_before_dashboard_widget_callback', 1 );
add_action( 'wpcfe_after_dashboard_status_report', 'wpcfe_after_dashboard_widget_callback', 1 );
add_action( 'wpcfe_after_dashboard_graph_report', 'wpcfe_bottom_dashboard_widget_callback', 100 );
add_action( 'wpcfe_before_shipment_table', 'wpcfe_top_shipment_widget_callback', 1 );
add_action( 'wpcfe_after_shipment_data', 'wpcfe_bottom_shipment_widget_callback', 100 );