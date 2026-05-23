<?php
class WPCFE_Print {
	public function __construct(){
		add_action( 'wpcargo_before_track_details', array($this, 'wpcfe_dashboard_print_results'), 10, 1 );
	}
	function wpcfe_dashboard_print_results($shipment) {
    if(!is_user_logged_in()){
      return;
    }
		?>
		<script>
			function wpcfe_dashboard_print(wpcargo_class) {
				var printContents = document.getElementById(wpcargo_class).innerHTML;
				var originalContents = document.body.innerHTML;
				document.body.innerHTML = printContents;
				window.print();
				document.body.innerHTML = originalContents;
				location.reload(true);
			}
		</script>
		<style>
			a:link:after, a:visited:after {
				content: "";
			}
			.noprint {
				display: none !important;
			}
			a:link:after, a:visited:after {
				display: none;
				content: "";
			}

		</style>
    <div class="wpcargo-print-btn print-shipment">
			<button type="button" data-id="<?php echo $shipment->ID; ?>" data-type="track" class="btn btn-primary btn-sm shipment-checkout"><i class="fa fa-file-text mr-md-3 mr-3 text-white" style="font-family: FontAwesome !important;"></i><?php echo apply_filters( 'wpcargo_print_label_label', esc_html__( 'Print Track Result', 'wpcargo') ); ?></button>
		
    </div>
		<?php
	}
}

$wpcfe_dashboard_print = new WPCFE_Print;