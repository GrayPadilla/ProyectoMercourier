<div class="card">
	<div class="card-body">
		<div id="wpcargo-result-wrapper" class="wpcargo-wrap-details wpcargo-container mb-5">
		    <style>
    		    #wpcargo-result{
    		        font-size:14px!important;
    		    }
    		    #wpcargo-result .header-title {
                    border-bottom: 1px solid #858b89;
                    font-size: 1.2rem;
                }

                .wpcargo-col-md-6 {
                    flex: 0 0 50%;
                    max-width: 48%;
                    float: left;
                }
                .wpcargo-col-md-4 {
    
    	            margin: 1% 0 1% 0%;
                    float: none!important;
                    max-width: 26%!important;
                    width: 26%!important;
                    flex:none!important;
                    height:30px;
                    display: inline-block!important;
    
                }
                #shipment_info, 
                #wpcargo-history-section{ margin-top:15px;}
    
                #shipment_info .wpcargo-label {
                    font-size: 13px!important;
                    display: block;
                }
                p.wpcargo-label{
                       margin-top: 0;
                    margin-bottom: 0.2em; 
                }
                p {
                    margin-block-start: 0;
                    margin-block-end: 0.5rem;
                    
                    margin-top: 0;
                    margin-bottom: 0.5em;
                     font-size: 13px!important;
                }
                table td, table th {
                    font-size: 13px!important;
                }
                p.header-title{
                    font-size:16px!important;
                }
                .wpcargo-table thead th {
                    color: #fff;
                    background-color: #00A924;
                    border-color: #00A924;
                    border: 1px solid #eeeeee;
                }
                .wpcargo-row::after {
                  content: "";
                  clear: both;
                  display: table;
                }
                
                #shmap-wrapper{ display:none;}
                #shipment-history{ font-size:13px!important;}
                .wpcargo-table td, .wpcargo-table th {
                    padding: 0.75rem;
                    vertical-align: top;
                    border-top: 1px solid #dee2e6;
                }
                .print-shipment { display:none;}
            </style>
		    <?php
		    
		    
            $shipment 				= new stdClass;
			$shipment->ID 			= (int)esc_html( $shipment_id );
			$shipment->post_title 	= esc_html( get_the_title( $shipment->ID) );
			$shipment_status = esc_html( get_post_meta( $shipment->ID, 'wpcargo_status', true ) );
			
			
		    do_action('wpcargo_before_track_details', $shipment );
		    do_action('wpcargo_track_header_details', $shipment );
		    do_action('wpcargo_track_after_header_details', $shipment );
		    do_action('wpcargo_track_shipper_details', $shipment );
		    do_action('wpcargo_before_shipment_details', $shipment );
		    do_action('wpcargo_track_shipment_details', $shipment );
		    do_action('wpcargo_after_package_details', $shipment );
		    do_action('wpcargo_after_package_totals', $shipment );
			do_action('wpcargo_after_track_details', $shipment );
		 
		   ?>
		</div>
	</div>
</div>