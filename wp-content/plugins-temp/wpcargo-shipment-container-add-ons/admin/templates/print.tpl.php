<style type="text/css">
	/*
	*  Shipment container Print Style
	*/
	#print-container {
	    padding: 12px;
	    background-color: #fff;
	}
	#container-shipment #shipment-list,
	#print-container #container-status,
	#print-container #container-header {
	    text-align: center;
	}
	#print-container #container-content p {
	    display: block;
	    clear: both;
	    margin: 0;
	}
	#print-container #container-status{
		background-color: #eee;
	}
	#print-container #container-status .header{
		margin: 0;
		padding: 12px 0;
	}
	#print-container #container-content .label {
	    min-width: 120px;
	    float: left;
	}
	#container-shipment #shipment-list {
	    list-style: none;
	}
	#container-shipment #shipment-list li {
	    display: inline-block;
	    padding: 6px;
	    text-align: center;
	}
	#container-shipment #shipment-list li .shipment-label{
	  margin:0;
	  font-weight: bold;
	}
	#container-history {
	    border-collapse: collapse;
	}
	#container-history tr td, #container-history tr th {
	    border: 1px solid #eee;
	    border-collapse: collapse;
	    margin: 0;
	    padding: 6px;
	}
	/* 
	Max width before this PARTICULAR table gets nasty
	This query will take effect for any screen smaller than 760px
	and also iPads specifically.
	*/
	@media 
	only screen and (max-width: 760px),
	(min-device-width: 768px) and (max-device-width: 1024px)  {
	
		/* Force table to not be like tables anymore */
		table#container-history, 
		table#container-history thead, 
		table#container-history tbody, 
		table#container-history th, 
		table#container-history td, 
		table#container-history tr { 
			display: block; 
		}
		
		/* Hide table headers (but not display: none;, for accessibility) */
		table#container-history thead tr { 
			position: absolute;
			top: -9999px;
			left: -9999px;
		}
		
		table#container-history tr { border: 1px solid #ccc; }
		
		table#container-history td { 
			/* Behave  like a "row" */
			border: none;
			border-bottom: 1px solid #eee; 
			position: relative;
			padding-left: 50%; 
		}
		
		table#container-history td:before { 
			/* Now like a table header */
			position: absolute;
			/* Top/left values mimic padding */
			top: 6px;
			left: 6px;
			width: 45%; 
			padding-right: 10px; 
			white-space: nowrap;
		}
		
		/*
		Label the data
		*/
		table#container-history td:nth-of-type(1):before { content: "<?php esc_html_e('Date', 'wpcargo-shipment-container' ); ?>"; }
		table#container-history td:nth-of-type(2):before { content: "<?php esc_html_e('Location', 'wpcargo-shipment-container' ); ?>"; }
		table#container-history td:nth-of-type(3):before { content: "<?php esc_html_e('Status', 'wpcargo-shipment-container' ); ?>"; }
		table#container-history td:nth-of-type(4):before { content: "<?php esc_html_e('Remarks', 'wpcargo-shipment-container' ); ?>"; }
	}
	
	/* Smartphones (portrait and landscape) ----------- */
	@media only screen
	and (min-device-width : 320px)
	and (max-device-width : 480px) {
		body { 
			padding: 0; 
			margin: 0; 
			width: 320px; }
		}
	
	/* iPads (portrait and landscape) ----------- */
	@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
		body { 
			width: 495px; 
		}
	}

</style>
<script type="text/javascript">
	function wpcargo_print(print_section) {
		var printContents = document.getElementById(print_section).innerHTML;
		var originalContents = document.body.innerHTML;				
		document.body.innerHTML = printContents;
		window.print();
		document.body.innerHTML = originalContents;
		location.reload(true);
		
	}	
</script>
<div id="print-wrapper" style="position: relative;">
	<button class="button button-primary button-large ti-printer" onclick="wpcargo_print('print-container')" style="position: absolute; top: 12px; left: 12px;"> <?php esc_html_e('Print', 'wpcargo-shipment-container' ); ?></button>
	<div id="print-container" class="section" style="background-color: #fff;">
		<style type="text/css">
			@media only print{
				body.toplevel_page_print-shipment-container{
					background: #fff;
				}
				/*
				*  Shipment container Print Style
				*/
				#container-header, #container-content, #container-status, #container-shipment, #container-history {
				    padding: 0px 12px;
				    font-size: 18px !important;
				}
				#container-shipment #shipment-list,
				#container-status,
				#container-header {
				    text-align: center;
				}
				#container-content p {
				    display: block;
				    clear: both;
				}
				#container-content .label {
				    min-width: 90px;
				    float: left;
				}
				#container-shipment #shipment-list {
				    list-style: none;
				}
				#container-shipment #shipment-list li {
				    display: inline-block;
				    padding: 6px;
				    text-align: center;
				}
				#container-shipment #shipment-list li .shipment-label{
				  margin:0;
				  font-weight: bold;
				}
				#container-history {
				    border-collapse: collapse;
				}
				#container-history tr td, #container-history tr th {
				    border: 1px solid #eee;
				    border-collapse: collapse;
				    margin: 0;
				    padding: 6px;
				}
			}
		</style>
		<section id="container-header">
			<?php if( !empty( $wpcargo->logo ) ): ?>
			<div id="site-logo">
				<img src="<?php echo $wpcargo->logo; ?>" alt="<?php echo get_bloginfo('name'); ?> Logo" />
			</div>
			<?php endif; ?>
			<div id="container-no">
				<h2><?php esc_html_e( 'Container No.:', 'wpcargo-shipment-container' ); ?> <?php echo $container_title; ?></h2>
			</div>
			<?php if( !empty( $options ) && array_key_exists( 'settings_barcode_checkbox', $options )  ): ?>
				<div id="container-barcode">
					<img src="<?php echo $wpcargo->barcode_url( $containerID  ); ?>" alt="<?php echo $container_title; ?> barcode" />
				</div>
			<?php endif; ?>
			<?php echo get_option('container_print_header'); ?>
			<?php do_action('after-shipment-container-print-header'); ?>
		</section>
		<section id="container-content">
			<div id="info" class="one-third first">
				<h3 class="header"><?php esc_html_e( 'Container Information : ', 'wpcargo-shipment-container' ); ?></h3>
				<p><span class="label"><?php esc_html_e('Flight/Container No. : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'container_no', true ); ?></span></p>
				<p><span class="label"><?php esc_html_e('Agent name : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo $container_agent; ?></span></p>
				<p><span class="label"><?php esc_html_e('Telephone : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'container_tel', true ); ?></span></p>
				<p><span class="label"><?php esc_html_e('Passport : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'passport', true ); ?></span></p>
			</div>
			<div id="trip" class="one-third">
				<h3 class="header"><?php esc_html_e('Trip Information', 'wpcargo-shipment-container' ); ?></h3>
				<p><span class="label"><?php esc_html_e('Origin port : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'origin', true ); ?></span></p>
				<p><span class="label"><?php esc_html_e('Destination port : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'destination', true ); ?></span></p>
				<p><span class="label"><?php esc_html_e('Delivery Agent : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo $delivery_agent; ?></span></p>
				<p><span class="label"><?php esc_html_e('Telephone : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'delivery_tel', true ); ?></span></p>
			</div>		
			<div id="time" class="one-third">
				<h3 class="header"><?php esc_html_e('Time Information', 'wpcargo-shipment-container' ); ?></h3>
				<p><span class="label"><?php esc_html_e('Date : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'date', true ); ?></span></p>
				<p><span class="label"><?php esc_html_e('Time : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'time', true ); ?></span></p>
				<p><span class="label"><?php esc_html_e('Expected Date : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'expected_date', true ); ?></span></p>
				<p><span class="label"><?php esc_html_e('Travel Mode : ', 'wpcargo-shipment-container' ); ?></span> <span class="data"><?php echo get_post_meta( $containerID, 'travel_mode', true ); ?></span></p>
			</div>
			<div class="clear-line"></div>
		</section>
		<section id="container-status">
			<h2 class="header status"><?php esc_html_e('Status : ', 'wpcargo-shipment-container' ); ?> <?php echo get_post_meta( $containerID, 'container_status', TRUE ); ?></h2>
		</section>
		<section id="container-shipment">
			<h3 class="header"><?php esc_html_e('Assigned Shipments', 'wpcargo-shipment-container' ); ?></h3>
			<?php 
				if( !empty( $shipments ) ){
					?><ul id="shipment-list"><?php
					foreach ($shipments as $shipment_id ) {
						?><li class="shipment"><?php
							?><img class="shipment-barcode" src="<?php echo $wpcargo->barcode_url( $shipment_id ); ?>" alt="<?php echo get_the_title( $shipment_id ); ?> barcode" /><?php
							?><p class="shipment-label"><?php echo get_the_title( $shipment_id ); ?></p><?php
						?></li><?php
					}	
					?></ul><?php		
				}else{
					?><p style="text-align: center;" class="notification"><?php esc_html_e('No assigned shipment.', 'wpcargo-shipment-container' ); ?></p><?php
				}
			?>
		</section>
		<section id="container-history">
			<h3 class="header"><?php esc_html_e( 'Container History', 'wpcargo-shipment-container' ); ?></h3>
			<?php 
				if( !empty( $history ) ){
					?>
					<table id="container-history" style="width: 100%;">
						<thead>
							<tr>
								<th><?php esc_html_e('Date', 'wpcargo-shipment-container' ); ?></th>
								<th><?php esc_html_e('Location', 'wpcargo-shipment-container' ); ?></th>
								<th><?php esc_html_e('Status', 'wpcargo-shipment-container' ); ?></th>
								<th><?php esc_html_e('Remarks', 'wpcargo-shipment-container' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach( $history as $details ) : ?>
							<tr>
								<td><?php echo $details['date']; ?></td>
								<td><?php echo $details['location']; ?></td>
								<td><?php echo $details['status']; ?></td>
								<td><?php echo $details['remarks']; ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php
				}else{
					?><p style="text-align: center;" class="notification"><?php esc_html_e('No container history.', 'wpcargo-shipment-container' ); ?></p><?php
				}
			?>
		</section>
		<?php echo get_option('container_print_footer'); ?>
		<?php do_action('shipment-container-print-footer'); ?>
	</div>
</div> <!-- print-wrapper -->