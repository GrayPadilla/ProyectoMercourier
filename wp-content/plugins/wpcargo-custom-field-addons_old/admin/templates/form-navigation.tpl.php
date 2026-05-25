<?php
	if( isset($_GET['action']) ){
		$view = $_GET['action'];
	}else{
		$view = '';
	}
?>
<h2 class="nav-tab-wrapper">
	<a class="nav-tab <?php echo ( $view == 'settings') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpc-cf-manage-form-field&action=settings'; ?>" ><?php esc_html_e('Field Settings', 'wpcargo-custom-field'); ?></a>
    <a class="nav-tab <?php echo ( ( $view != 'add' && $view != 'edit' && $view != 'settings' ) || !isset( $view ) ) ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpc-cf-manage-form-field'; ?>" ><?php esc_html_e('Form Field List', 'wpcargo-custom-field'); ?></a>
    <a class="nav-tab <?php echo ( $view == 'add') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpc-cf-manage-form-field&action=add'; ?>" ><?php esc_html_e('Add Form Field', 'wpcargo-custom-field'); ?></a>
    <?php if( $view == 'edit' ){ ?>
    	<a class="nav-tab <?php echo ( $view == 'edit') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpc-cf-manage-form-field'; ?>" ><?php esc_html_e('Edit Form Field', 'wpcargo-custom-field'); ?></a>
    <?php } ?>
</h2>