<h1 class="wp-heading-inline">Shipment Logs</h1>
<div class="postbox">
    <div class="inside">
    	<table class="form-table">
    		<tbody>
    			<tr>
    				<th>Select Log File</th>
    				<td>
    					<?php if( !empty( wpcfe_get_log_files() ) ): ?>
    					<select name="log_file">
    						<option value="">--Select Log File</option>
    						<?php foreach( wpcfe_get_log_files() as $file ): ?>
    							<option value="<?php echo $file; ?>"><?php echo str_replace('.txt','',$file); ?></option>
    						<?php endforeach; ?>
    					</select>
    					<?php else: ?>
    					NO Log File Found
    					<?php endif; ?>
    				</td>
    			</tr>
    		</tbody>
    	</table>
    	<a id="download-logfile" href="#" class="button button-primary" ><span class="dashicons dashicons-download" style="vertical-align:middle;"></span> Download Log File</a>
    </div>
</div>