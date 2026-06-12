<?php include('header.php'); ?>
<?php
global $wpcargo, $WPCCF_Fields, $wpcargo_print_admin;
$user_info          = wp_get_current_user();
$class_not_logged   = 'not-logged';
 $wpcfesort_list     = array( 100, 200, 250, 350, 500 );
 $wpcfesort          = get_user_meta( get_current_user_id(), 'user_wpcfesort', true ) ? : 100 ;
 // Order options: alphabetical by shipper name or by shipment count per shipper
 $wpcfe_order_list   = array( 'alpha_asc', 'alpha_desc', 'count_desc', 'count_asc' );
 $wpcfe_order        = get_user_meta( get_current_user_id(), 'user_wpcfe_order', true ) ? : 'alpha_asc';
 if( isset( $_GET['wpcfe_order'] ) && in_array( $_GET['wpcfe_order'], $wpcfe_order_list ) ){
     update_user_meta( get_current_user_id(), 'user_wpcfe_order', $_GET['wpcfe_order'] );
     $wpcfe_order = $_GET['wpcfe_order'];
 }
$page_url           = get_the_permalink( wpcfe_admin_page() );
$date_range         = wpcfe_date_range_filter();
$p0                 = '';
if( is_user_logged_in() ){
	require_once( wpcfe_include_template( 'navigation.tpl' ) );
    $class_not_logged  = '';
}
if( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'update' ){
	$p0 = 'p-0';
}
?>
<!--Main layout-->
<main class="pt-5 mx-lg-5 <?php echo is_rtl() ? 'rtl' : ''; ?> <?php echo $class_not_logged; ?> ">
    <div id="content-container" class="container-fluid my-5 <?php echo $p0; ?>">
        <?php do_action( 'wpcfe_dashboard_before_content', get_the_id() ); ?>
        <?php
        if( !class_exists( 'WPCCF_Fields' ) ){
			$template = wpcfe_include_template( 'nocf-error.tpl' );
            require_once( $template );
            return false;
        }
        if( !is_user_logged_in() ){
            $redirect_to = get_the_permalink( get_the_id() );		
            $template = wpcfe_include_template( 'login' );
            require_once( $template );
        }elseif( !can_wpcfe_access_dashboard() ){
			?>
			<div class="col-md-12 text-center">
				<section class="card">
					<div class="card-body">    
						<?php
							$template = wpcfe_include_template( 'restricted.tpl' );
							require_once( $template );
						?>
					</div>
				</section>
			</div>
			<?php
        }else{
            if( $post->ID == wpcfe_admin_page() ){
                do_action( 'wpcfe_before_admin_page_load' );
                if( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'track' && isset( $_GET['num'] ) ){
                    $shipment_id = wpcfe_shipment_id( $_GET['num'] );
                    if( $shipment_id && is_user_shipment( $shipment_id ) ){
                        $shipment_detail                = new stdClass;
                        $shipment_detail->ID            = $shipment_id;
                        $shipment_detail->post_title    = get_the_title( $shipment_id );
						$template = wpcfe_include_template( 'track-shipment' );
                    }else{
						$template = wpcfe_include_template( 'no-shipment' );
                    }          
                    require_once( $template );
                }elseif( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'add' && !wpcfe_add_shipment_deactivated() && can_wpcfe_add_shipment() ){
					$template = wpcfe_include_template( 'add-shipment' );
                    require_once( $template );
                }elseif( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'dashboard'  ){
					$template = wpcfe_include_template( 'graph' );
                    require_once( $template );
                }elseif( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'update' && isset( $_GET['id'] ) && is_wpcfe_shipment( $_GET['id'] ) && can_wpcfe_update_shipment() && is_user_shipment( (int)$_GET['id'] ) ){
                    $shipment_id = (int)$_GET['id'];
					$template = wpcfe_include_template( 'update-shipment' );
                    require_once( $template );
                }else{
                    $shipper_data   = wpcfe_table_header('shipper');
                    $receiver_data  = wpcfe_table_header('receiver');
                    $paged          = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
                    $s_shipment     = isset( $_GET['wpcfes'] ) ? $_GET['wpcfes'] : '' ;

                    // Date filter
                    $date_start     = $date_range ? date('Y-m-d', strtotime('today - '.$date_range.' days')) : '';
                    $date_end       = $date_range ? date('Y-m-d') : '';
                    $date_start     = isset( $_GET['date_start'] ) ? $_GET['date_start'] : $date_start;
                    $date_end       = isset( $_GET['date_end'] ) ? $_GET['date_end'] : $date_end;

                    // Count distinct users who created shipments today
                    global $wpdb;
                    $today_date = current_time('Y-m-d');
                    $today_start = $today_date . ' 00:00:00';
                    $today_end = $today_date . ' 23:59:59';
                    $sql_count_users = $wpdb->prepare(
                        "SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} WHERE post_type=%s AND post_status='publish' AND post_date >= %s AND post_date <= %s",
                        'wpcargo_shipment', $today_start, $today_end
                    );
                    $today_user_count = (int) $wpdb->get_var( $sql_count_users );

                    // Count shipments with tipo_envio = 'normal' and combined (normal, agencia, fullfitment)
                    $sql_count_normal = $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                        WHERE p.post_type = %s AND p.post_status = 'publish' AND p.post_date >= %s AND p.post_date <= %s
                        AND pm.meta_key = %s AND pm.meta_value = %s",
                        'wpcargo_shipment', $today_start, $today_end, 'tipo_envio', 'normal'
                    );
                    $today_count_normal = (int) $wpdb->get_var( $sql_count_normal );

                    $types = array( 'normal', 'agencia', 'fullfitment' );
                    $placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
                    $sql = "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                        WHERE p.post_type = %s AND p.post_status = 'publish' AND p.post_date >= %s AND p.post_date <= %s
                        AND pm.meta_key = %s AND pm.meta_value IN ( $placeholders )";
                    $params = array_merge( array( 'wpcargo_shipment', $today_start, $today_end, 'tipo_envio' ), $types );
                    $sql_count_combo = $wpdb->prepare( $sql, $params );
                    $today_count_combo = (int) $wpdb->get_var( $sql_count_combo );

                    // Custom meta query
                    $meta_query   = array();
                    if( isset($_GET['status']) && !empty( $_GET['status'] ) ){
                        $meta_query['wpcargo_status'] = array(
                            'key' => 'wpcargo_status',
                            'value' => urldecode( $_GET['status'] ),
                            'compare' => '='
                        );
                    }
                    if( isset($_GET['shipper']) && !empty( $_GET['shipper'] ) ){
                        $meta_query[] = array(
                            'key' => $shipper_data['field_key'],
                            'value' => urldecode( $_GET['shipper'] ),
                            'compare' => '='
                        );
                    }
                    if( isset($_GET['receiver']) && !empty( $_GET['receiver'] ) ){
                        $meta_query[] = array(
                            'key' => $receiver_data['field_key'],
                            'value' => urldecode( $_GET['receiver'] ),
                            'compare' => '='
                        );
                    }
                    $meta_query = apply_filters( 'wpcfe_dashboard_meta_query', $meta_query );
                    $args           = array(
                        'post_type'         => 'wpcargo_shipment',
                        'post_status'       => 'publish',
                        'posts_per_page'    => $wpcfesort,
                        'paged'             => $paged,
                        's'                 => $s_shipment,
                        'meta_query' => array(
                            'relation' => 'AND',
                            $meta_query
                        )
                    );
                    $args = apply_filters( 'wpcfe_dashboard_arguments', $args );

                    // Apply ordering
                    if( in_array( $wpcfe_order, array( 'alpha_asc', 'alpha_desc' ) ) ){
                        $args['meta_key'] = $shipper_data['field_key'];
                        $args['orderby']  = 'meta_value';
                        $args['order']    = $wpcfe_order == 'alpha_asc' ? 'ASC' : 'DESC';
                        $wpc_shipments  = new WP_Query( $args );
                        $number_records = $wpc_shipments->found_posts;
                        $basis          = $paged * $wpcfesort;
                        $record_end     = $number_records < $basis ? $number_records : $basis ;
                        $record_start   = $basis - ( $wpcfesort - 1 );
                    }elseif( in_array( $wpcfe_order, array( 'count_desc', 'count_asc' ) ) ){
                        // Count ordering: build full id list, compute shipments count per shipper, sort IDs by that count, then slice for pagination
                        $count_args = $args;
                        $count_args['posts_per_page'] = -1;
                        $count_args['fields'] = 'ids';
                        $all_ids = get_posts( $count_args );

                        $counts = array();
                        foreach( $all_ids as $pid ){
                            $name = get_post_meta( $pid, $shipper_data['field_key'], true );
                            $key = $name ? $name : '__empty__';
                            if( ! isset( $counts[ $key ] ) ) $counts[ $key ] = 0;
                            $counts[ $key ]++;
                        }

                        $scores = array();
                        foreach( $all_ids as $pid ){
                            $name = get_post_meta( $pid, $shipper_data['field_key'], true );
                            $key = $name ? $name : '__empty__';
                            $scores[ $pid ] = isset( $counts[ $key ] ) ? $counts[ $key ] : 0;
                        }

                        if( $wpcfe_order == 'count_desc' ){
                            arsort( $scores );
                        }else{
                            asort( $scores );
                        }

                        $sorted_ids = array_keys( $scores );
                        $number_records = count( $sorted_ids );
                        $basis = $paged * $wpcfesort;
                        $record_end = $number_records < $basis ? $number_records : $basis ;
                        $record_start = $basis - ( $wpcfesort - 1 );

                        $offset = ( $paged - 1 ) * $wpcfesort;
                        $paged_slice = array_slice( $sorted_ids, $offset, $wpcfesort );

                        if( ! empty( $paged_slice ) ){
                            $args2 = $args;
                            $args2['post__in'] = $paged_slice;
                            $args2['orderby'] = 'post__in';
                            $args2['posts_per_page'] = $wpcfesort;
                            $wpc_shipments = new WP_Query( $args2 );
                        }else{
                            $wpc_shipments = new WP_Query( array( 'post__in' => array(0), 'posts_per_page' => 0 ) );
                        }
                    }else{
                        // fallback
                        $wpc_shipments  = new WP_Query( $args );
                        $number_records = $wpc_shipments->found_posts;
                        $basis          = $paged * $wpcfesort;
                        $record_end     = $number_records < $basis ? $number_records : $basis ;
                        $record_start   = $basis - ( $wpcfesort - 1 );
                    }
					$template       = wpcfe_include_template( 'shipments' );
					require_once( $template );
                    wp_reset_postdata();
                }
                do_action( 'wpcfe_after_admin_page_load' );
            }else{
				do_action( 'wpcfe_before_dashboard_page' );
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <section class="card mb-4">
                            <div class="card-body">
                            <?php
                            while ( have_posts() ) : the_post();
                                the_content();
                            endwhile;
                            ?>
                            </div>
                        </section>
                    </div>
                </div>
                <?php
				do_action( 'wpcfe_after_dashboard_page' );
            }
        }
        ?>
    </div>
    <?php do_action( 'wpcfe_dashboard_after_content', get_the_id() ); ?>
</main>
<!--Main layout-->
<!--Footer-->
<footer class="page-footer font-small primary-color-dark darken-2 mt-4 wow fadeIn fixed-bottom <?php echo is_rtl() ? 'rtl' : ''; ?> <?php echo $class_not_logged; ?>">
	<?php do_action( 'wpcfe_dashboard_before_footer', get_the_id() ); ?>
	<!--Copyright-->
	<div class="footer-copyright py-3 text-center">
		<?php echo apply_filters( 'wpcfe_footer_credits', '&copy; '.date('Y-m-d').' '.__('Copyright','wpcargo-frontend-manager').': <a href="'.home_url().'">'.get_bloginfo('name').'</a>' ); ?>
	</div>
	<!--/.Copyright-->
</footer>
<?php include('footer.php'); ?>