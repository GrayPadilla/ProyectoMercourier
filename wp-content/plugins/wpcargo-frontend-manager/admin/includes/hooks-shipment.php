<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcfe_shipment_action_rows( $shipment_id ){
    return apply_filters( 'wpcfe_shipment_action_rows', array(), $shipment_id );
}
function wpcfe_shipment_view_action_row( $rows, $shipment_id ){
    $page_url = get_the_permalink( wpcfe_admin_page() ).'?wpcfe=track&num='.urlencode( get_the_title($shipment_id) );
    $rows[] = '<a class="wpcfe-update-shipment text-primary" href="'. esc_url( $page_url ) .'" title="'. esc_html__('View', 'wpcargo-frontend-manager') .'">'. esc_html__('View', 'wpcargo-frontend-manager') .'</a>';
    return $rows;
}
function wpcfe_shipment_update_action_row( $rows, $shipment_id ){
    if( !can_wpcfe_update_shipment() ) return $rows;
    $page_url = get_the_permalink( wpcfe_admin_page() ).'?wpcfe=update&id='. (int)$shipment_id;
    $rows[] = '<a class="wpcfe-update-shipment text-primary" href="'. esc_url( $page_url ) .'" title="'. esc_html__('Edit', 'wpcargo-frontend-manager') .'">'. esc_html__('Edit', 'wpcargo-frontend-manager') .'</a>';
    return $rows;
}
function wpcfe_shipment_delete_action_row( $rows, $shipment_id ){
    if( !can_wpcfe_delete_shipment() ) return $rows;
    $rows[] = '<a href="#" class="wpcfe-delete-shipment text-danger" data-id="'. (int)$shipment_id .'" title="'. esc_html__('Trash', 'wpcargo-frontend-manager') .'">'. esc_html__('Delete', 'wpcargo-frontend-manager') .'</a>';
    return $rows;
}
// Shipment table Callback
function wpcfe_shipper_receiver_shipment_header_callback(){
    $shipper_data   = wpcfe_table_header('shipper');
    $receiver_data  = wpcfe_table_header('receiver');
    ?>
    <th class="no-space"><?php echo apply_filters( 'wpcfe_shipper_table_header_label', $shipper_data['label'] ); ?></th>
	<th class="no-space"><?php echo apply_filters( 'wpcfe_receiver_table_header_label', $receiver_data['label'] ); ?></th>
    <?php
}
function wpcfe_shipper_receiver_shipment_data_callback( $shipment_id ){
    $shipper_data   = wpcfe_table_header('shipper');
    $receiver_data  = wpcfe_table_header('receiver');
    $shipper_meta 	= apply_filters( 'wpcfe_shipper_table_cell_data', get_post_meta( $shipment_id, $shipper_data['field_key'], true ), $shipment_id );
	$receiver_meta 	= apply_filters( 'wpcfe_receiver_table_cell_data', get_post_meta( $shipment_id, $receiver_data['field_key'], true ), $shipment_id );
    ?>
    <td class="no-space"><?php echo $shipper_meta; ?></td>
	<td class="no-space"><?php echo $receiver_meta; ?></td>
    <?php
}
function wpcfe_shipment_number_header_callback(){
    echo '<th>'.apply_filters( 'wpcfe_shipment_number_label', __('Tracking Number', 'wpcargo-frontend-manager' ) ).'</th>';
}
function wpcfe_shipment_number_data_callback( $shipment_id ){
    $current_user   = wp_get_current_user();
    $seen_metakey   = '_wpcfe_seen_'.$current_user->ID;
    $page_url           = get_the_permalink( wpcfe_admin_page() );
    $shipment_title     = get_the_title($shipment_id);
    
    if( wpcfe_disable_unseen() == false ){
        $is_seen            = get_post_meta( $shipment_id, $seen_metakey, true );
        $badge              = !$is_seen ? sprintf( '<span class="badge badge-pill bg-danger align-top">%s</span>', __('New', 'wpcargo-frontend-manager' ) )  : '';
    }
    $action_rows        = wpcfe_shipment_action_rows( $shipment_id );
    $page_url           = !can_wpcfe_update_shipment() ? $page_url.'?wpcfe=track&num='.$shipment_title : $page_url.'?wpcfe=update&id='. (int)$shipment_id ;
    ob_start();
    ?>
        <td>
            <a href="<?php  echo esc_url( $page_url ); ?>" class="text-primary font-weight-bold"><?php echo esc_html($shipment_title) . $badge; ?></a>
            <?php if( $action_rows ): ?>
                <div class="wpcfe-action-row">
                    <?php echo implode(" | ",$action_rows); ?>
                </div>
            <?php endif; ?>
        </td>
    <?php
    echo ob_get_clean();
}
function wpcfe_shipment_table_header_status(){
    ?><th><?php _e('Status', 'wpcargo-frontend-manager' ); ?></th><?php
}
function wpcfe_shipment_table_data_status( $shipment_id  ){
    $status = get_post_meta( $shipment_id, 'wpcargo_status', true );
    ?><td class="shipment-status <?php echo wpcfe_to_slug( $status ); ?>"><?php echo $status; ?></td><?php
}
function wpcfe_shipment_table_header_type(){
    ?><th><?php _e('Shipment Type', 'wpcargo-frontend-manager' ); ?></th><?php
}
function wpcfe_shipment_table_data_type( $shipment_id ){
    ?><td class="shipment-type <?php echo wpcfe_to_slug( wpcfe_get_shipment_type( $shipment_id ) ); ?>"><?php echo wpcfe_get_shipment_type( $shipment_id ); ?></td><?php
}
function wpcfe_shipment_table_header_action_print(){
    if( empty( wpcfe_print_options() ) ) return false;
    ?>
    <th class="text-center"><?php _e('Print', 'wpcargo-frontend-manager' ); ?></th>
    <?php
}   
function wpcfe_shipment_table_action_print( $shipment_id ){
    $print_options = wpcfe_print_options();
    if( empty( $print_options ) ) return false;
    ?>
    <td class="text-center print-shipment">
        <div class="dropdown" style="display:inline-block !important;">
            <!--Trigger-->
            <button class="btn btn-default btn-sm dropdown-toggle m-0 py-1 px-2" type="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i></button>
            <!--Menu-->
            <div class="dropdown-menu dropdown-primary">
                <?php foreach( $print_options as $print_key => $print_label ): ?>
                    <a class="dropdown-item print-<?php echo $print_key; ?> py-1" data-id="<?php echo $shipment_id; ?>" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </td>
    <?php
} 
function wpcfe_seen_shipment_callback(){
    global $post;
    if( !function_exists( 'wpcfe_admin_page' ) || !$post ){
        return false;
    }
    if( $post->ID != wpcfe_admin_page() ){
        return false;
    }
    $shipment_id = null;
    if( isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'track' && isset( $_GET['num'] ) && !empty( $_GET['num'] ) ){
        $shipment_id = wpcfe_get_shipment_id( $_GET['num'] );
    }
    if(  isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'update' && isset( $_GET['id'] ) && (int)$_GET['id'] ){
        $shipment_id = (int)$_GET['id'];
    }
    if( $shipment_id && is_user_logged_in() ){
        $seen_metakey   = '_wpcfe_seen_'.get_current_user_id();
        update_post_meta( $shipment_id, $seen_metakey, current_time( 'mysql' ) );
    }
}

// Update shipment hooks

function wpcfe_initialize_table_hooks(){  
    // Shipment table Hook
    add_action( 'wpcfe_shipment_before_tracking_number_header', 'wpcfe_shipment_number_header_callback', 25 );
    add_action( 'wpcfe_shipment_before_tracking_number_data', 'wpcfe_shipment_number_data_callback', 25 );
    // Shipment Shipper / Receiver Column
    add_action( 'wpcfe_shipment_after_tracking_number_header', 'wpcfe_shipper_receiver_shipment_header_callback', 25 );
    add_action( 'wpcfe_shipment_after_tracking_number_data', 'wpcfe_shipper_receiver_shipment_data_callback', 25 );
    // Shipment Type Column
    add_action( 'wpcfe_shipment_table_header', 'wpcfe_shipment_table_header_type', 25 ); 
    add_action( 'wpcfe_shipment_table_data', 'wpcfe_shipment_table_data_type', 25 );
    // Shipment Status Column
    add_action( 'wpcfe_shipment_table_header', 'wpcfe_shipment_table_header_status', 25 ); 
    add_action( 'wpcfe_shipment_table_data', 'wpcfe_shipment_table_data_status', 25 );
    // Shipment Print Column
    add_action( 'wpcfe_shipment_table_header_action', 'wpcfe_shipment_table_header_action_print', 25 ); 
    add_action( 'wpcfe_shipment_table_data_action', 'wpcfe_shipment_table_action_print', 25 );
    add_filter( 'wpcfe_shipment_action_rows', 'wpcfe_shipment_view_action_row', 10, 2 );
    add_filter( 'wpcfe_shipment_action_rows', 'wpcfe_shipment_update_action_row', 10, 2 );
    add_filter( 'wpcfe_shipment_action_rows', 'wpcfe_shipment_delete_action_row', 10, 2 );
    add_action( 'wp_head', 'wpcfe_seen_shipment_callback' );
}
add_action( 'plugins_loaded', 'wpcfe_initialize_table_hooks' );