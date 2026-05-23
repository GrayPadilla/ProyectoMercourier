<form id="shipment-sort" class="form-inline form-group float-right" method="get">
	<label><?php echo __('Show', 'wpcargo-frontend-manager' ); ?> </label>
	<select name="wpcfesort" class="mdb-select form-control form-control-sm mx-2" style="width: 60px;">
		<?php foreach( $wpcfesort_list as $list ): ?>
		<option value="<?php echo $list ?>" <?php echo $list == $wpcfesort ? 'selected' : '' ;?>><?php echo $list ?></option>
		<?php endforeach; ?>
	</select>
	<label> <?php echo __('entries', 'wpcargo-frontend-manager' ); ?></label>
	<?php if( isset($_GET['status']) ): ?>
    	<input type="hidden" name="status" value="<?php echo $_GET['status']; ?>">
    <?php endif; ?>
    <?php if( isset($_GET['shipper']) ): ?>
    	<input type="hidden" name="shipper" value="<?php echo $_GET['shipper']; ?>">
    <?php endif; ?>
    <?php if( isset($_GET['assigned_to']) ): ?>
    	<input type="hidden" name="assigned_to" value="<?php echo $_GET['assigned_to']; ?>">
    <?php endif; ?>
    <?php if( isset($_GET['date_from']) ): ?>
    	<input type="hidden" name="date_from" value="<?php echo $_GET['date_from']; ?>">
    <?php endif; ?>
    <?php if( isset($_GET['date_to']) ): ?>
    	<input type="hidden" name="date_to" value="<?php echo $_GET['date_to']; ?>">
    <?php endif; ?>
    <?php if( isset($_GET['wpcfeorder']) ): ?>
        <input type="hidden" name="wpcfeorder" value="<?php echo $_GET['wpcfeorder']; ?>">
    <?php endif; ?>
</form>