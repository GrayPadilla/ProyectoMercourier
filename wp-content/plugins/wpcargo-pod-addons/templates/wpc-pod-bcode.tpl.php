<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
?>
<section id="wpc-barcode-scanner" class="wpc-barcode-scanner">
	<div class="controls">
		<fieldset class="input-group wpc-stop-btn">
			<button class="stop wpcargo-pod-btn"><?php esc_html_e('Stop', 'wpcargo-pod' ); ?></button>
		</fieldset>
		<fieldset class="reader-config-group wpc-reader-config">
			<label>
				<span><?php esc_html_e('Device Selection', 'wpcargo-pod' ); ?></span>				
				<select name="input-stream_constraints" id="deviceSelection">
				</select>
				<!--<input type="checkbox" checked="checked" name="locator_half-sample" />-->
			</label>
		</fieldset>
	</div>
  <div id="result_strip">  	
	<ul class="thumbnails"></ul>
	<ul class="collector"></ul>
	<p><?php esc_html_e('Images will display here after the scan...', 'wpcargo-pod' ); ?></p>
  </div>
  <div id="interactive" class="viewport"></div>
</section>
<script src="//webrtc.github.io/adapter/adapter-latest.js" type="text/javascript"></script>
