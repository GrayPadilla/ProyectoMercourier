<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Variables disponibles en este template:
 * @var int    $shipment_id
 * @var string $tienda
 * @var string $actions_html
 * @var string $distrito_recojo
 * @var string $distrito_destino
 * @var string $fecha
 * @var string $tipo_html
 * @var string $cambio_html
 * @var string $estado
 * @var string $motorizo_recojo_html
 * @var string $motorizo_entrega_html
 *
 * NOTA: Este template NO incluye el wrapper <tr>. Los TDs se insertan dentro
 * del <tr> que WPCargo ya abrió mediante el hook wpcfe_shipment_table_data.
 */
?>
<td class="merc-tienda-cell" data-tienda="<?php echo esc_attr( $tienda ?: 'N/A' ); ?>" data-cliente-id="<?php echo esc_attr( $cliente_id ?: '' ); ?>" data-cliente-nombre="<?php 
	$cliente_nombre = '';
	if ( $cliente_id ) {
		$first_name = get_user_meta( intval( $cliente_id ), 'first_name', true );
		$last_name = get_user_meta( intval( $cliente_id ), 'last_name', true );
		if ( $first_name || $last_name ) {
			$cliente_nombre = trim( $first_name . ' ' . $last_name );
		}
	}
	echo esc_attr( $cliente_nombre );
?>" data-shipment-id="<?php echo esc_attr( $shipment_id ); ?>" data-distrito="<?php echo esc_attr( $distrito_recojo ?: '' ); ?>" data-motorizo="<?php echo esc_attr( isset( $motorizo_recojo_name ) ? $motorizo_recojo_name : '' ); ?>">
	<?php if ( $tienda ) : ?>
		<strong><?php echo esc_html( $tienda ); ?></strong><br>
	<?php endif; ?>
	<?php echo $actions_html; ?>
</td>



<td><?php echo ! empty( $distrito_destino )
	? esc_html( $distrito_destino )
	: '<span style="color:#999;">N/A</span>'; ?></td>

<td><?php echo esc_html( $fecha ); ?></td>

<td style=""><?php echo $tipo_html; ?></td>

<td style="text-align:center;"><?php echo $cambio_html; ?></td>

<?php $caja_cerrada = ( '1' === get_post_meta( $shipment_id, 'merc_caja_cerrada', true ) ) ? '1' : '0'; ?>

<td class="shipment-status <?php echo esc_attr( sanitize_title( $estado ) ); ?>"
    data-merc-caja-cerrada="<?php echo esc_attr( $caja_cerrada ); ?>"
    data-es-reprogramado="<?php echo esc_attr( $es_reprogramado ?? 0 ); ?>">
    <?php echo esc_html( $estado ); ?>
</td>


<td><?php echo $motorizo_entrega_html; ?></td>