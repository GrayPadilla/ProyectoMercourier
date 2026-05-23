<?php
$shipper_data   = wpcfe_table_header('shipper');
$receiver_data  = wpcfe_table_header('receiver');

$paged          = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$s_shipment     = isset( $_GET['wpcfes'] ) ? $_GET['wpcfes'] : '' ;

// Custom meta query
$meta_query   = array();

// $meta_query['__shipment_type'] = array(
//     'key' => '__shipment_type',
//     'value' => 'ltl',
//     'compare' => 'NOT EXISTS'
// );

$meta_query['__shipment_type'] = array(
    'key' => '__shipment_type',
    'compare' => 'NOT EXISTS'
);

if( isset($_GET['status']) && !empty( $_GET['status'] ) && !is_wpcargo_client() ){
    $meta_query[] = array(
        'key' => 'wpcargo_status',
        'value' => urldecode( $_GET['status'] ),
        'compare' => '='
    );
}else{
    
    $meta_query[] = array(
        'key' => 'wpcargo_status',
        'value' => array('COMPLETE', 'DELIVERED', 'WAREHOUSE', 'DECLINED', 'DISPATCHER REVIEW', 'APPROVED FOR FIN', 'APPROVED FOR FIN', 'FINALIZED'),
        'compare' => 'NOT IN'
    );
    
}
if( isset($_GET['shipper']) && !empty( $_GET['shipper'] ) && !is_wpcargo_client() ){
    $meta_query[] = array(
        'key' => 'registered_shipper',
        'value' => urldecode( $_GET['shipper'] ),
        'compare' => '='
    );
}
if( isset($_GET['receiver']) && !empty( $_GET['receiver'] ) && !is_wpcargo_client() ){
    $meta_query[] = array(
        'key' => $receiver_data['field_key'],
        'value' => urldecode( $_GET['receiver'] ),
        'compare' => '='
    );
}
if( isset($_GET['assigned_to']) && !empty( $_GET['assigned_to'] ) && !is_wpcargo_client() ){
    $meta_query[] = array(
        'key' => 'assigned_agent_to',
        'value' => urldecode( $_GET['assigned_to'] ),
        'compare' => 'LIKE'
    );
}
if( ( ( isset($_GET['date_from']) && !empty( $_GET['date_from'] ) ) || isset($_GET['date_to']) && !empty( $_GET['date_to'] ) ) && !is_wpcargo_client() ){

    $start  = isset($_GET['date_from']) && !empty( $_GET['date_from'] ) ? $_GET['date_from'] : $_GET['date_to'] ;
    $end    = isset($_GET['date_to']) && !empty( $_GET['date_from'] ) ? $_GET['date_to'] : $_GET['date_from'] ;

    $meta_query['ship_date_query'] = array(
        'key' => 'ship_date',
        'value' => array( $start, $end ),
        'compare' => 'BETWEEN',
        'type' => 'DATE'
    );
}else{
    $meta_query['ship_date_query'] = array(
        'key' => 'ship_date'
    );
}

if( !empty($_GET['delivery_date']) && function_exists('wpcargo_customizer_format_date') ){
    $d_date =  wpcargo_customizer_format_date( $_GET['delivery_date'] );
    $meta_query['delivery_date_query'] = array(
         'key'   => 'requested_date',
         'value' => $d_date->year.'-'.$d_date->month.'-'.$d_date->day
     );
 }

$meta_query = apply_filters( 'wpcfe_dashboard_meta_query', $meta_query );

$args           = array(
    'post_type'         => 'wpcargo_shipment',
    'post_status'       => 'publish',
    'posts_per_page'    => $wpcfesort,
    'paged'             => get_query_var('paged'),
    's'                 => $s_shipment,
    'meta_query' => array(
        'relation' => 'AND',
        $meta_query
    ),
    'orderby' => array( 'ship_date_query' => $wpcfeorder ),
);

// Aplicar filtros personalizados (incluyendo filtros de fecha, tiendaname, etc.)
$args = apply_filters( 'wpcfe_dashboard_arguments', $args );

// $args['meta_query']['_parent_shipment_id'] = array(
//     array(
//         'key'       => '_parent_shipment_id',
//         'compare' => 'NOT EXISTS'
//     )
// );


// if( isset( $_GET['dbug'] ) ){

//     echo '<pre>';
//     print_r( get_the_title( $_GET['dbug'] ).' : '.get_post_meta( $_GET['dbug'], '_parent_shipment_id', true ) );
//     echo '</pre>';

//     echo '<pre>';
//     print_r( $args );
//     echo '</pre>';
// }


// $paged         = get_query_var('paged') <= 1 ? 1 : get_query_var('paged');
$record_end    = $paged * $wpcfesort;
$record_start  = $record_end - ( $wpcfesort - 1 );
$wpc_shipments = new WP_Query( $args );
$number_records = $wpc_shipments->found_posts;
require_once( WPCFE_PATH.'templates/shipments.php');
wp_reset_postdata();