<?php include('header.php'); ?>
<?php
global $wpcargo, $WPCCF_Fields, $wpcargo_print_admin;
$user_info          = wp_get_current_user();
$class_not_logged   = 'not-logged';
$wpcfesort_list     = array( 10, 25, 50, 100 );
$wpcfesort          = get_user_meta( get_current_user_id(), 'user_wpcfesort', true ) ? : 10 ;
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
                    
                    $wpc_shipments  = new WP_Query( $args );
                    $number_records = $wpc_shipments->found_posts;
					$basis          = $paged * $wpcfesort;
                    $record_end     = $number_records < $basis ? $number_records : $basis ;
                    $record_start   = $basis - ( $wpcfesort - 1 );
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