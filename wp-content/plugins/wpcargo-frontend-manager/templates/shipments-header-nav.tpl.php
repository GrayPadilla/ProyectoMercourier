<div class="container-fluid mb-0">
    <div class="row">
        <section class="col-md-9">
        <ul id="shipment-list-nav">
            <li class="">
                <a href="<?php echo get_the_permalink( WPCFE_ALL_SHIPMENT_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_ALL_SHIPMENT_PAGE ? 'active' : '' ; ?>" >All Shipments</a>
            </li>
            <?php if( !is_wpcargo_client() ): ?>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_TODAY_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_TODAY_PAGE ? 'active' : '' ; ?>"  >Today</a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_NEXTDAY_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_NEXTDAY_PAGE ? 'active' : '' ; ?>"  >Next Day</a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_LTL_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_LTL_PAGE ? 'active' : '' ; ?>"  >LTL</a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_DISPATCHER_GRID_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_DISPATCHER_GRID_PAGE ? 'active' : '' ; ?>" >Dispatcher Review</a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_ACCOUNTING_GRID_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_ACCOUNTING_GRID_PAGE ? 'active' : '' ; ?>" >Accounting</a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_SHIPMENT_DETAIL_GRID_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_SHIPMENT_DETAIL_GRID_PAGE ? 'active' : '' ; ?>" >Shipment Detail</a>
                </li>
				<li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_MARGIN_REPORT_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_MARGIN_REPORT_PAGE ? 'active' : '' ; ?>" ><?php echo get_the_title(WPCFE_MARGIN_REPORT_PAGE); ?></a>
                </li>
				<li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_EMPLOYEE_COMMISSION_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_EMPLOYEE_COMMISSION_PAGE ? 'active' : '' ; ?>" ><?php echo get_the_title(WPCFE_EMPLOYEE_COMMISSION_PAGE); ?></a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCARGO_HELPER_TIME_SHEET_ID ); ?>" class="<?php echo get_the_ID() == WPCARGO_HELPER_TIME_SHEET_ID ? 'active' : '' ; ?>" ><?php echo get_the_title(WPCARGO_HELPER_TIME_SHEET_ID); ?></a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_WEATHER_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_WEATHER_PAGE ? 'active' : '' ; ?>"  >Weather Map</a>
                </li>
                <li class="">
                    <a href="<?php echo get_the_permalink( WPCFE_TRAFFIC_PAGE ); ?>" class="<?php echo get_the_ID() == WPCFE_TRAFFIC_PAGE ? 'active' : '' ; ?>"  >Traffic Map</a>
                </li>
                <?php do_action( 'wpfce_shipment_list_nav' ); ?>
            <?php endif; ?>
        </ul>
        </section>
        <section class="col-md-3">
            <?php require_once( WPCFE_PATH.'templates/entry.tpl.php'); ?>
        </section>
    </div>
</div>