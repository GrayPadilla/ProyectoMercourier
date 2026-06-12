<?php
if (!defined('ABSPATH')) exit;

// Hooks para warehouse

// Añadir checkbox en la lista admin de `merc_producto` para mostrar entregados
add_action('restrict_manage_posts', 'merc_warehouse_restrict_manage_posts');
function merc_warehouse_restrict_manage_posts() {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ( ! $screen || $screen->post_type !== 'merc_producto' ) return;

	$show = isset($_GET['show_archived_products']) && $_GET['show_archived_products'] === '1';
	?>
	<label style="margin-left:8px;">
		<input type="checkbox" name="show_archived_products" value="1" <?php checked( $show ); ?> /> Mostrar entregados
	</label>
	<?php
}

// Filtrar productos entregados por defecto (soft-hide). Permitir override con ?show_archived_products=1
add_action('pre_get_posts', 'merc_warehouse_pre_get_posts_filter');
function merc_warehouse_pre_get_posts_filter( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) return;
	$post_type = $query->get('post_type');
	if ( $post_type !== 'merc_producto' ) return;

	// Si el usuario marcó el checkbox, no filtrar
	if ( isset($_GET['show_archived_products']) && $_GET['show_archived_products'] === '1' ) {
		return;
	}

	$meta_query = $query->get('meta_query');
	if ( ! is_array( $meta_query ) ) $meta_query = array();

	$meta_query[] = array(
		'relation' => 'OR',
		array(
			'key' => '_merc_producto_estado',
			'value' => 'entregado',
			'compare' => '!=',
		),
		array(
			'key' => '_merc_producto_estado',
			'compare' => 'NOT EXISTS',
		),
	);

	$query->set('meta_query', $meta_query);
}

