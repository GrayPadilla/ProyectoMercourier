<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helpers globales del plugin merc-form-enhancements.
 */

/**
 * Carga un template del plugin.
 *
 * @param string $file  Ruta relativa desde admin/templates/  (ej. 'frontend/bloqueo-banner.tpl.php')
 * @param array  $data  Variables a inyectar en el template.
 */
function mfe_include_template( string $file, array $data = [] ): void {
	$path = MERC_FORM_PATH . "admin/templates/{$file}";
	if ( ! file_exists( $path ) ) {
		wp_die( "Plantilla no encontrada: {$file}" );
	}
	if ( $data ) {
		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
	}
	include $path;
}

