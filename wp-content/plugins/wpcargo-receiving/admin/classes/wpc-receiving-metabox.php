<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
class WPC_Receiving_Metabox{
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'wpc_register_receiving_list_metabox' ));
		add_action( 'save_post', array( $this, 'wpcargo_branch_save_meta_box_data' ), 30, 10 );
	}
	public function wpc_register_receiving_list_metabox(){
		add_meta_box('wpcargo_branches', __('Branch History', 'wpcargo-receiving'), array( $this, 'wpcargo_receiving_meta_box_callback' ), 'wpcargo_shipment');
	}
	public function wpcargo_receiving_meta_box_callback($post){
		global $wpdb;
		wp_nonce_field( 'wpc_branches_meta_box_1', 'wpcargo_branches_meta_box_nonce_1' );
		$branch_author_id 		= get_post_field( 'post_author', $post->ID );
		$get_all_branch_history = get_post_meta($post->ID, 'wpcargo-branches', true);
		$current_branch			= get_post_meta($post->ID, 'wpcargo-current-branches', true);
		$from_branch			= get_post_meta($post->ID, 'wpcargo-from-branch', true);
		$get_from_branch		= !empty($from_branch) ? $from_branch : $branch_author_id;
		if(!empty($get_all_branch_history) && is_array($get_all_branch_history)) {
			$join_branches		= join(",", $get_all_branch_history);
			$get_all_branches =  $join_branches;
		}else{
			$get_all_branches = $branch_author_id;
		}
		?>
		<div class="wpc-branch-wrap">
			<div class="wpc-branch-history">
				<table id="wpc-branch-history">
				<thead>
					<tr>
						<th><?php esc_html_e('Branch Name', 'wpcargo-receiving');?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($get_all_branch_history)) { ?>
						<?php
						$counter = 1;
							foreach($get_all_branch_history as $branches){
								echo '<tr><td>('.$counter.'.) '.get_the_author_meta('display_name', $branches).'</td></tr>';
								$counter++;
							}
						?>
					<?php } else { ?>
					<tr>
						<td>
						<?php echo get_the_author_meta('display_name', $branch_author_id); ?>
						</td>
					</tr>
					<?php } ?>
					<input type="hidden" id="wpcargo-branches-history" name="wpcargo-branches-history" value="<?php echo $get_all_branches; ?>">
					<input type="hidden" id="wpcargo-from-branch" name="wpcargo-from-branch" value="<?php echo $get_from_branch; ?>">
				</tbody>
				</table>
			</div>
		</div>
		<?php
	}
	public function wpcargo_branch_save_meta_box_data($post_id) {
		$nonce_name   = isset($_POST['wpcargo_branches_meta_box_nonce_1']) ? $_POST['wpcargo_branches_meta_box_nonce_1'] : '';
        $nonce_action = 'wpc_branches_meta_box_1';
		if (!isset($nonce_name)) {
			return;
		}
		if (!wp_verify_nonce($nonce_name, $nonce_action)) {
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }
		$get_wpcargo_branches_history = $_REQUEST['wpcargo-branches-history'];
		$get_wpcargo_branches = $_REQUEST['wpcargo-branches'];
		$wpcargo_from_branch 	= $_REQUEST['wpcargo-from-branch'];
		$get_branches_history = !empty($get_wpcargo_branches_history) ? explode(",", $get_wpcargo_branches_history): array();
		$get_transferred_branches = !empty($get_wpcargo_branches) ? array($get_wpcargo_branches): array();
		update_post_meta($post_id, 'wpcargo-current-branches', $get_wpcargo_branches);
		$wpc_merge_branches = array_merge($get_branches_history, $get_transferred_branches);
		update_post_meta($post_id, 'wpcargo-branches', $wpc_merge_branches);
		update_post_meta($post_id, 'wpcargo-from-branch', $wpcargo_from_branch);
	}
}
$wpc_branches_metabox =  new WPC_Receiving_Metabox;