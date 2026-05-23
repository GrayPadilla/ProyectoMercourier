<?php
$date_format    = get_option( 'date_format' );
$color_pallete  = wpcfe_report_color_pallete();
$status_report  = wpcfe_report_status();
$date_range     = wpcfe_date_range_filter();
$date_range     = $date_range ? $date_range : apply_filters( 'wpcfe_date_range_graph_filter', 7 );
$records        = array();
$date_start     = date('Y-m-d', strtotime('today - '.$date_range.' days'));
$date_end       = date('Y-m-d');
$date_start     = isset( $_GET['date_start'] ) ? $_GET['date_start'] : $date_start;
$date_end       = isset( $_GET['date_end'] ) ? $_GET['date_end'] : $date_end;
$dates          = wpcef_get_dates( $date_start, $date_end );
$total_shipments = 0;
// Create status variables
if( !empty( $status_report  ) ){
    foreach ($status_report as $s_variable) {
        ${wpcfe_to_slug($s_variable)} = array();
    }
}
foreach ( $dates as $date ) {    
    if( !empty( $status_report  ) ){
        foreach ($status_report as $s_variable) {
            $report_count = wpcfe_get_report_count( $date, $s_variable );
            ${wpcfe_to_slug($s_variable)}[] = $report_count;
        }
    }
}
// Create data set object
$dataset = array();

if( !empty( $status_report  ) ){
    $counter = 0;
    foreach ($status_report as $s_variable) {
        $pallete = array_key_exists( $counter, $color_pallete) ? $color_pallete[$counter] : '403039';
        $total_shipments += array_sum( ${wpcfe_to_slug($s_variable)} );
        $dataset[] = array(
            'label' => $s_variable,
            'backgroundColor' => '#'.$pallete,
            'borderColor'   => '#'.$pallete,
            'borderWidth'   => 1,
            'data'          => ${wpcfe_to_slug($s_variable)}
        );
        $counter++;
    }
}
?>
<style>#wpcfe-status-report .wpcfe-status_report_section a:hover > .classic-admin-card { background-color: #eee; }</style>
<?php do_action( 'wpcfe_before_dashboard_status_report_form' ); ?>
<form id="dashboard-form-filter" action="<?php echo $page_url; ?>" class="row mb-4 border-bottom">
    <input type="hidden" name="wpcfe" value="dashboard">
    <div id="wpcfe-filter-fields" class="col-lg-12 form-inline">
        <div class="md-form form-group">
            <?php _e('Date Range', 'wpcargo-frontend-manager' ); ?>
            <input id="date_start" type="text" name="date_start" class="form-control daterange_picker start_date px-2 py-1 mx-2" value="<?php echo $date_start; ?>" autocomplete="off" style="width: 96px;">
            <div class="input-group-addon"><?php _e('to', 'wpcargo-frontend-manager' ); ?></div>
            <input id="date_end" type="text" name="date_end" class="form-control daterange_picker end_date px-2 py-1 mx-2" value="<?php echo $date_end; ?>" autocomplete="off" style="width: 96px;">
        </div>
        <div class="md-form form-group submit-filter p-0 mx-1">
            <button id="wpcfe-submit-filter" type="submit" class="btn btn-primary btn-fill btn-sm m-0"><?php esc_html_e('Filter', 'wpcargo-frontend-manager' ); ?></button>
        </div>
    </div>
</form>
<?php do_action( 'wpcfe_before_dashboard_status_report_grid' ); ?>
<div id="wpcfe-status-report" class="row mb-4">
    <?php
    if( !empty( $status_report ) ){
        foreach ( $status_report as $_skey => $status ) {
            $shipment_count      = array_sum( $dataset[$_skey]['data'] );
            $shipment_percentage = 0;
            if( $total_shipments || $shipment_count ){
                $shipment_percentage = ( $shipment_count / $total_shipments ) * 100;
            }
            ?>   
            <div class="wpcfe-status_report_section col-sm-6 col-md-3 col-lg-3 mb-4">
                <a href="<?php echo get_permalink( wpcfe_admin_page() ); ?>?status=<?php echo urlencode($status); ?>&date_start=<?php echo urlencode($date_start); ?>&date_end=<?php echo urlencode($date_end); ?>">
                    <div class="card classic-admin-card">
                        <div class="card-body">
                            <div class="pull-right">
                            <i class="fa fa-line-chart" style="color:<?php echo '#'.$color_pallete[$_skey] ?> !important;"></i>
                            </div>
                            <h6 style="color:<?php echo '#'.$color_pallete[$_skey] ?> !important;"><?php echo esc_html($status); ?></h6>
                            <h4 class="text-dark h1"><?php echo $shipment_count; ?></h4>
                            <p class="text-dark">
                            <?php
                            printf( __("%s of 100%s"), number_format($shipment_percentage, 2, '.', ''),'%' );
                            ?>
                            </p>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg info darken-3" role="progressbar" style="width: <?php echo $shipment_percentage; ?>%; background-color: <?php echo '#'.$color_pallete[$_skey] ?> !important;" aria-valuenow="<?php echo $shipment_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php
        }
    }
    ?>
</div>
<?php do_action( 'wpcfe_after_dashboard_status_report' ); ?>
<div id="wpcfe-graph-report" class="row my-4 py-4 border-top bg-white">
    <div class="col-lg-12">
        <h2 class="h5 text-center"><?php printf( __('Report from %s to %s', 'wpcargo-frontend-manager'), date( $date_format, strtotime( $date_start ) ), date( $date_format, strtotime( $date_end ) ) ); ?></h2>
        <p class="h6 text-center text-muted"><?php _e('Total shipment updates', 'wpcargo-frontend-manager'); ?>: <?php echo $total_shipments; ?></p>
    </div>   
    <div class="col-lg-12 d-block d-sm-none">
        <div class="list-group list-group-flush">
            <?php foreach ($dates as $m_key => $m_date ): ?>
                <p class="h5 py-2 border-bottom" data-toggle="collapse" href="#mdata<?php echo $m_key; ?>" role="button" aria-expanded="false" aria-controls="mdata<?php echo $m_key; ?>"><?php echo $m_date; ?> <i class="fa fa-th-list float-right text-info" aria-hidden="true"></i></p>
                <section id="mdata<?php echo $m_key; ?>" class="<?php echo $m_key != 0 ? 'collapse' : '' ; ?>">
                <?php 
                    $mcounter = 0;
                    foreach ($status_report as $s_variable) {
                        ?>
                        <a class="list-group-item list-group-item-action"><?php echo $s_variable; ?>
                            <span class="badge badge-pill pull-right" style="background-color:<?php echo '#'.$color_pallete[$mcounter] ?> !important; font-size: 1em;"><?php echo ${wpcfe_to_slug($s_variable)}[$m_key]; ?>
                            </span>
                        </a>
                        <?php
                        $mcounter++;
                    }
                ?>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-12 d-none d-sm-block">
        <?php 
        $template = wpcfe_include_template( 'chart' );
        require_once( $template );
        ?>
    </div>
</div>
<?php do_action( 'wpcfe_after_dashboard_graph_report' ); ?>