<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MERC_Shipment_Form_Fields
 *
 * Renderiza campos adicionales en el formulario de creación/edición de envíos:
 *   - Desglose del costo de envío (shipping cost breakdown).
 *   - Selector de producto para envíos MERC Full Fitment.
 *
 * Antes estaba en functions.php del tema – movido al plugin para mejor separación.
 */
class MERC_Shipment_Form_Fields {

	public function __construct() {
		// Renderizar campos en el formulario
		add_action( 'after_wpcfe_shipment_form_fields', [ $this, 'render_shipping_cost' ],    1, 1 );
		add_action( 'after_wpcfe_shipment_form_fields', [ $this, 'render_producto_selector' ], 5, 1 );
		add_action( 'wpcfe_after_shipment_form_fields', [ $this, 'render_producto_selector' ], 5, 1 );
		add_action( 'wpcfe_shipment_form_fields',       [ $this, 'render_producto_selector' ], 999, 1 );

		// Scripts y estilos del formulario de paquetes
		add_action( 'wp_footer', [ $this, 'package_defaults_script' ], 15 );
		add_action( 'wp_head',   [ $this, 'package_form_styles' ] );
		add_action( 'save_post_wpcargo_shipment', [ $this, 'sync_service_cost_by_status' ], 1000000, 1 );

		// Guardar datos financieros al salvar el envío
		add_action( 'wpcargo_after_save_shipment', [ $this, 'save_financial_data' ], 20, 1 );
		add_action( 'save_post_wpcargo_shipment',  [ $this, 'save_financial_data' ], 20, 1 );
		add_action( 'save_post_wpcargo_shipment',  [ $this, 'verify_final_shipping_cost' ], 999999, 1 );
		// También ejecutar la verificación final después del flujo de importación CSV
		add_action( 'wpcie_after_save_csv_import', [ $this, 'verify_final_shipping_cost' ], 99999, 2 );
		add_action( 'edit_post',                   [ $this, 'log_edit_shipping_cost' ], 10, 2 );

		// Registrar handlers AJAX desde la misma instancia para evitar doble instanciación
		$this->__construct_ajax_handlers();
	}

	/* ── Desglose del costo de envío ─────────────────────────────────────── */

	public function render_shipping_cost( $shipment_id = 0 ) {
		// Obtener el ID del envío en modo edición
		if ( empty( $shipment_id ) ) {
			if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
				$shipment_id = intval( $_GET['id'] );
			} elseif ( isset( $_POST['shipment_id'] ) && ! empty( $_POST['shipment_id'] ) ) {
				$shipment_id = intval( $_POST['shipment_id'] );
			} else {
				global $post;
				if ( isset( $post->ID ) ) {
					$shipment_id = $post->ID;
				}
			}
		}

		// Inicializar variables
		$tipo_envio_actual        = '';
		$costo_envio_guardado     = 0;
		$costo_producto_guardado  = 0;
		$total_cobrar_guardado    = 0;
		$cargo_remitente_guardado = 0;

		// Si tenemos un ID válido, cargar datos desde la base de datos
		if ( $shipment_id > 0 ) {
			$tipo_envio_actual        = get_post_meta( $shipment_id, 'tipo_envio', true );
			$costo_envio_guardado     = get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) ?: 0;
			$costo_producto_guardado  = get_post_meta( $shipment_id, 'wpcargo_costo_producto', true ) ?: 0;
			$total_cobrar_guardado    = get_post_meta( $shipment_id, 'wpcargo_total_cobrar', true ) ?: 0;
			$cargo_remitente_guardado = get_post_meta( $shipment_id, 'wpcargo_cargo_remitente', true ) ?: 0;
		}

		// Si no hay tipo guardado, intentar desde URL (modo creación)
		if ( empty( $tipo_envio_actual ) && isset( $_GET['type'] ) ) {
			$tipo_envio_actual = sanitize_text_field( $_GET['type'] );
		}
		?>
		<!-- Campos ocultos para guardar datos financieros -->
		<input type="hidden" id="tipo-envio-actual" name="tipo_envio" value="<?php echo esc_attr( $tipo_envio_actual ); ?>">
		<input type="hidden" id="hidden-product-cost" name="wpcargo_costo_producto" value="<?php echo esc_attr( $costo_producto_guardado ); ?>">
		<input type="hidden" id="hidden-shipping-cost" name="wpcargo_costo_envio" value="<?php echo esc_attr( $costo_envio_guardado ); ?>">
		<input type="hidden" id="hidden-customer-payment" name="wpcargo_total_cobrar" value="<?php echo esc_attr( $total_cobrar_guardado ); ?>">
		<input type="hidden" id="hidden-sender-charge" name="wpcargo_cargo_remitente" value="<?php echo esc_attr( $cargo_remitente_guardado ); ?>">

		<!-- Sección de costo de envío -->
		<div class="col-md-12 mb-5" id="shipping-cost-section"
			data-tipo-envio="<?php echo esc_attr( $tipo_envio_actual ); ?>"
			data-costo-envio="<?php echo esc_attr( $costo_envio_guardado ); ?>">
			<div class="card">
				<div class="card-body">
					<h5><b>💰 Desglose del envío:</b></h5>

					<!-- Desglose detallado -->
					<div id="shipping-breakdown" style="font-size: 16px; margin-top: 15px;">
						<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
							<span>Costo del producto:</span>
							<span style="font-weight: bold;">S/. <span id="product-cost">0.00</span></span>
						</div>
						<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
							<span>Costo del envío:</span>
							<span style="font-weight: bold;">S/. <span id="shipping-cost">0.00</span></span>
						</div>
						<div style="display: flex; justify-content: space-between; padding: 12px 0; margin-top: 5px; background-color: #f8f9fa; padding: 10px; border-radius: 5px;">
							<span style="font-weight: bold; font-size: 18px;">Total a cobrar:</span>
							<span style="font-weight: bold; font-size: 18px; color: #1976D2;">S/. <span id="total-cost">0.00</span></span>
						</div>
					</div>

					<!-- Mensaje de validación -->
					<div id="validation-message" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none;"></div>

					<!-- Debug info (oculto en producción) -->
					<div id="debug-info" style="font-size: 12px; margin-top: 10px; color: #666; display: none;"></div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Tabla de precios por distrito para MERC AGENCIA (express)
			const districtPricesExpress = {
				"-- Seleccione uno --": 0.00,
				"El Agustino": 8.00,
				"San Juan de Lurigancho": 8.00,
				"Santa Anita": 8.00,
				"Ate - Salamanca - Vitarte": 10.00,
				"La Molina": 8.00,
				"Santa Clara": 10.00,
				"Huaycan - Gloria Grande - Pariachi": 12.00,
				"Molina Alta (Musa - Portada del Sol - Planicie)": 10.00,
				"Huachipa (Zoológico de Huachipa)": 10.00,
				"Callao": 8.00,
				"Bellavista": 8.00,
				"La Punta - Callao": 10.00,
				"La Perla": 8.00,
				"Pueblo Libre": 8.00,
				"Lima Cercado": 8.00,
				"Breña": 8.00,
				"San Miguel": 8.00,
				"Magdalena": 8.00,
				"Sarita Colonia (Comisaría Sarita Colonia)": 8.00,
				"Carmen de la Legua": 8.00,
				"Rímac": 8.00,
				"Independencia": 8.00,
				"Comas": 8.00,
				"Carabayllo": 10.00,
				"Puente Piedra": 10.00,
				"Ventanilla": 10.00,
				"Los Olivos": 8.00,
				"San Martin de Porres": 8.00,
				"Santiago de Surco": 8.00,
				"San Juan de Miraflores": 8.00,
				"Villa María del Triunfo": 10.00,
				"Villa El Salvador": 10.00,
				"Chorrillos": 8.00,
				"Barranco": 8.00,
				"Jesús María": 8.00,
				"Lince": 8.00,
				"La Victoria": 8.00,
				"Miraflores": 8.00,
				"San Isidro": 8.00,
				"Surquillo": 8.00,
				"San Borja": 8.00,
				"San Luis": 8.00,
				"Centro de Lima": 8.00
			};

			// Tabla de precios por distrito para MERC EMPRENDEDOR (normal)
			const districtPricesNormal = {
				"-- Seleccione uno --": 0.00,
				"El Agustino": 10.00,
				"San Juan de Lurigancho": 10.00,
				"Santa Anita": 10.00,
				"Ate - Salamanca - Vitarte": 10.00,
				"La Molina": 10.00,
				"Santa Clara": 12.00,
				"Huaycan - Gloria Grande - Pariachi": 14.00,
				"Molina Alta (Musa - Portada del Sol - Planicie)": 12.00,
				"Huachipa (Zoológico de Huachipa)": 12.00,
				"Callao": 10.00,
				"Bellavista": 10.00,
				"La Punta - Callao": 12.00,
				"La Perla": 10.00,
				"Pueblo Libre": 10.00,
				"Lima Cercado": 10.00,
				"Breña": 10.00,
				"San Miguel": 10.00,
				"Magdalena": 10.00,
				"Sarita Colonia (Comisaría Sarita Colonia)": 10.00,
				"Carmen de la Legua": 10.00,
				"Rímac": 10.00,
				"Independencia": 10.00,
				"Comas": 10.00,
				"Carabayllo": 13.00,
				"Puente Piedra": 13.00,
				"Ventanilla": 13.00,
				"Los Olivos": 10.00,
				"San Martin de Porres": 10.00,
				"Santiago de Surco": 10.00,
				"San Juan de Miraflores": 10.00,
				"Villa María del Triunfo": 12.00,
				"Villa El Salvador": 12.00,
				"Chorrillos": 10.00,
				"Barranco": 10.00,
				"Jesús María": 10.00,
				"Lince": 10.00,
				"La Victoria": 10.00,
				"San Isidro": 10.00,
				"Surquillo": 10.00,
				"San Borja": 10.00,
				"San Luis": 10.00,
				"Centro de Lima": 10.00
			};

			let cachedServiceType = null;

			function getServiceType() {
				if (cachedServiceType !== null) {
					return cachedServiceType;
				}

				const urlParams  = new URLSearchParams(window.location.search);
				const type       = urlParams.get('type');
				const shipmentId = urlParams.get('id');

				if (type === 'express') {
					cachedServiceType = 'express';
					return 'express';
				}
				if (type === 'full_fitment' || (type && type.toLowerCase().includes('full'))) {
					cachedServiceType = 'full_fitment';
					return 'full_fitment';
				}

				if (shipmentId) {
					const shippingSection = $('#shipping-cost-section');
					if (shippingSection.length > 0) {
						const tipoFromAttr = shippingSection.data('tipo-envio');
						if (tipoFromAttr && tipoFromAttr !== '') {
							const tipoLower = String(tipoFromAttr).toLowerCase().trim();
							if (tipoLower === 'express' || tipoLower.includes('agencia')) {
								cachedServiceType = 'express';
								return 'express';
							}
							if (tipoLower === 'full_fitment' || tipoLower.includes('full')) {
								cachedServiceType = 'full_fitment';
								return 'full_fitment';
							}
							cachedServiceType = 'normal';
							return 'normal';
						}
					}
				}

				const tipoEnvioField = $('#tipo-envio-actual').val() || $('input[name="tipo_envio"]').val();
				if (tipoEnvioField && tipoEnvioField.trim() !== '') {
					const tipoLower = tipoEnvioField.toLowerCase().trim();
					if (tipoLower === 'express' || tipoLower.includes('agencia')) {
						cachedServiceType = 'express';
						return 'express';
					}
					if (tipoLower === 'full_fitment' || tipoLower.includes('full')) {
						cachedServiceType = 'full_fitment';
						return 'full_fitment';
					}
				}

				cachedServiceType = 'normal';
				return 'normal';
			}

			function getDistrictPrices() {
				const serviceType = getServiceType();
				return serviceType === 'express' ? districtPricesExpress : districtPricesNormal;
			}

			function findBestMatch(destination) {
				const serviceType = getServiceType();
				if (serviceType === 'full_fitment') {
					return 10.00;
				}

				const districtPrices = getDistrictPrices();
				destination = destination.trim();

				if (districtPrices[destination] !== undefined) {
					return districtPrices[destination];
				}

				for (const district in districtPrices) {
					const mainName = district.split('(')[0].split(',')[0].trim();
					if (mainName.toLowerCase() === destination.toLowerCase()) {
						return districtPrices[district];
					}
				}

				for (const district in districtPrices) {
					if (district.toLowerCase().includes(destination.toLowerCase()) ||
						destination.toLowerCase().includes(district.toLowerCase())) {
						return districtPrices[district];
					}
				}

				return 0.00;
			}

			function showValidationMessage(message, type) {
				type = type || 'warning';
				const colors = {
					'warning': '#fff3cd',
					'error':   '#f8d7da',
					'success': '#d4edda',
					'info':    '#d1ecf1'
				};
				const textColors = {
					'warning': '#856404',
					'error':   '#721c24',
					'success': '#155724',
					'info':    '#0c5460'
				};
				$('#validation-message').css({
					'background-color': colors[type],
					'color':            textColors[type],
					'border':           '1px solid ' + textColors[type]
				}).html(message).show();
			}

			function hideValidationMessage() {
				$('#validation-message').hide();
			}

			function updateShippingBreakdown() {
				const destinationField = $('#wpcargo_distrito_destino');
				let destination = '';

				if (destinationField.length > 0) {
					if (destinationField.is('select')) {
						destination = destinationField.find('option:selected').text() || destinationField.val() || '';
					} else {
						destination = destinationField.val() || '';
					}
				}

				const montoInput = $('#wpcargo_monto');
				let totalAmount = 0;

				if (montoInput.length > 0) {
					totalAmount = parseFloat(montoInput.val()) || 0;
				} else {
					const altMontoInput = $('input[name*="monto"]:not([type="hidden"]):not([name*="costo"]):not([name*="total"]):not([name*="cargo"]), input[id*="monto"]:not([type="hidden"])');
					if (altMontoInput.length > 0) {
						totalAmount = parseFloat(altMontoInput.first().val()) || 0;
					}
				}

				const hiddenShippingCostField = $('#hidden-shipping-cost');
				const existingShippingCost    = parseFloat(hiddenShippingCostField.val()) || 0;
				const isEditMode = $('input[name="post_ID"]').length > 0 || $('input[name="shipment_id"]').length > 0;

				const shippingCost = findBestMatch(destination);

				let finalShippingCost = shippingCost;
				if (isEditMode && existingShippingCost > 0 && !window.districtChanged) {
					finalShippingCost = existingShippingCost;
				}

				let productCost = totalAmount - finalShippingCost;

				if (totalAmount === 0) {
					hideValidationMessage();
					$('#product-cost').text('0.00');
					$('#shipping-cost').text(finalShippingCost.toFixed(2));
					$('#total-cost').text(finalShippingCost.toFixed(2));
					return;
				}

				if (totalAmount === finalShippingCost) {
					showValidationMessage(
						'ℹ️ El monto total coincide exactamente con el costo de envío. ' +
						'El costo del producto es S/. 0.00',
						'info'
					);
					productCost = 0;
				} else {
					// No mostrar advertencia si el total es menor que el costo de envío.
					// Permitimos que el monto total sea inferior al costo de envío.
					hideValidationMessage();
				}

				$('#product-cost').text(productCost.toFixed(2));
				$('#shipping-cost').text(finalShippingCost.toFixed(2));
				$('#total-cost').text(totalAmount.toFixed(2));

				$('#hidden-product-cost').val(productCost.toFixed(2));
				$('#hidden-shipping-cost').val(finalShippingCost.toFixed(2));
				$('#hidden-customer-payment').val(totalAmount.toFixed(2));
			}

			$(document).on('change', '#wpcargo_distrito_destino, select[name="wpcargo_distrito_destino"]', function() {
				window.districtChanged = true;
				updateShippingBreakdown();
			});

			$(document).on('select2:select', '#wpcargo_distrito_destino', function() {
				window.districtChanged = true;
				updateShippingBreakdown();
			});

			$(document).on('input change', '#wpcargo_monto, input[name*="monto"]:not([type="hidden"]):not([name*="costo"]):not([name*="total"]):not([name*="cargo"]), input[id*="monto"]:not([type="hidden"])', function() {
				updateShippingBreakdown();
			});

			$(document).on('change', 'select[name="payment_wpcargo_mode_field"]', function() {
				setTimeout(function() { updateShippingBreakdown(); }, 300);
			});

			setTimeout(function() { updateShippingBreakdown(); }, 500);

			let checkInterval = setInterval(function() {
				const distritoField = $('#wpcargo_distrito_destino');
				const montoField    = $('#wpcargo_monto');
				if (distritoField.length > 0 && montoField.length > 0) {
					updateShippingBreakdown();
					clearInterval(checkInterval);
				}
			}, 500);

			setTimeout(function() { clearInterval(checkInterval); }, 10000);
		});
		</script>

		<style>
		#shipping-breakdown { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
		#validation-message { animation: mercSlideIn 0.3s ease-in-out; }
		@keyframes mercSlideIn {
			from { opacity: 0; transform: translateY(-10px); }
			to   { opacity: 1; transform: translateY(0); }
		}
		@media (max-width: 576px) {
			#shipping-breakdown { font-size: 14px; }
			#shipping-breakdown div:last-child { font-size: 16px !important; }
		}
		</style>
		<?php
	}

	/* ── Selector de producto para MERC Full Fitment ─────────────────────── */

	public function render_producto_selector( $shipment_id ) {
		// Determinar el tipo de envío
		$tipo_envio = '';
		
		// En creación: vería desde URL
		if ( isset( $_GET['type'] ) ) {
			$tipo_envio = sanitize_text_field( $_GET['type'] );
		}
		// En edición: tomar desde meta del envío
		elseif ( $shipment_id ) {
			$tipo_envio = get_post_meta( $shipment_id, 'tipo_envio', true );
		}
		
		// render_producto_selector() called
		
		// Solo mostrar si el tipo de envío es MERC FULL FITMENT
		if ( $tipo_envio !== 'full_fitment' ) {
			// Not a full_fitment shipment — do not render product selector
			return;
		}

		// Evitar renderizado múltiple
		static $ya_renderizado = false;
		if ( $ya_renderizado ) {
			// Already rendered; avoid duplication
			return;
		}
		$ya_renderizado = true;

		// Obtener usuario actual
		$current_user_id = get_current_user_id();
		$es_admin        = current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' );

		// Determinar ID del cliente a filtrar
		// Prioridad: 1) shipper_id del POST (cliente seleccionado en el formulario)
		//            2) Usuario actual si no es admin
		//            3) NO FILTRAR si es admin sin shipper_id específico
		$cliente_id = null;
		if ( ! empty( $_POST['shipper_id'] ) ) {
			$cliente_id = intval( $_POST['shipper_id'] );
		} elseif ( ! $es_admin ) {
			$cliente_id = $current_user_id;
		} else {
			// Admin sin shipper_id: No hay cliente definido aún
			// El selector se llenará cuando el admin seleccione un cliente vía AJAX
			// Admin without shipper_id: selector will remain empty until client selection
		}

		// Construir meta_query para filtrar por cliente
		$meta_query = [];
		if ( $cliente_id !== null ) {
			$meta_query = [
				[
					'key'     => '_merc_producto_cliente_asignado',
					'value'   => $cliente_id,
					'compare' => '=',
				],
			];
		}

		// Obtener productos disponibles
		// Si hay cliente definido, filtrar por ese cliente
		// Si no hay cliente (creación de envío por admin), obtener TODOS los productos
		$productos = [];
		if ( $cliente_id !== null ) {
			// Cliente específico: obtener solo sus productos
			$productos = get_posts( [
				'post_type'      => 'merc_producto',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_query'     => $meta_query,
			] );
			// Products found for specific client
		} else {
			// Sin cliente definido (creación): mostrar TODOS los productos disponibles
			// (El admin podrá filtrar luego al seleccionar cliente)
			$productos = get_posts( [
				'post_type'      => 'merc_producto',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );
			// All products shown in creation mode (to be filtered by client selection)
		}

		// Filtrar productos con stock disponible
		$productos_disponibles = [];
		foreach ( $productos as $prod ) {
			$estado   = get_post_meta( $prod->ID, '_merc_producto_estado', true );
			$cantidad = function_exists( 'merc_get_product_stock' ) ? merc_get_product_stock( $prod->ID ) : 0;

			if ( empty( $estado ) || $estado === 'sin_asignar' || ( $estado === 'asignado' && intval( $cantidad ) > 0 ) ) {
				$productos_disponibles[] = $prod;
			}
		}

		// Productos disponibles iniciales: count($productos_disponibles)

		// Obtener producto(s) ya seleccionado(s) (si existe)
		$producto_seleccionado  = get_post_meta( $shipment_id, '_merc_producto_id', true );
		$cantidad_seleccionada  = get_post_meta( $shipment_id, '_merc_producto_cantidad', true );
		$productos_multi        = get_post_meta( $shipment_id, '_merc_productos_multi', true );
		
		// Asegurar que la cantidad tenga un valor válido por defecto
		$cantidad_seleccionada = ! empty( $cantidad_seleccionada ) ? intval( $cantidad_seleccionada ) : 1;
		
		// Carga de producto(s) seleccionado(s) para el envío
		
		if ( empty( $productos_multi ) || ! is_array( $productos_multi ) ) {
			// Normalizar formato: si hay un producto simple, usarlo como array de un elemento
			if ( $producto_seleccionado ) {
				$productos_multi = [ [ 'id' => intval( $producto_seleccionado ), 'cantidad' => $cantidad_seleccionada ] ];
				// Normalizar producto simple a array multi
			} else {
				$productos_multi = [];
				// No products assigned to this shipment
			}
		} else {
			// Si ya existe `$productos_multi` como array, asegurar que cada item tiene cantidad válida
			foreach ( $productos_multi as &$item ) {
				if ( empty( $item['cantidad'] ) ) {
					$item['cantidad'] = 1;
				}
			}
			// Loaded productos_multi array
		}

		// En modo edición: SIEMPRE agregar los productos ya asignados a este envío
		// (incluso si no tienen stock disponible, porque ya están asignados aquí)
		if ( $shipment_id > 0 && ! empty( $productos_multi ) ) {
			// Adding already-assigned products to selector (edit mode)
			foreach ( $productos_multi as $p_item ) {
				$p_id = intval( $p_item['id'] ?? 0 );
				if ( $p_id <= 0 ) continue;
				
				// Verificar si ya está en la lista
				$ya_incluido = false;
				foreach ( $productos_disponibles as $p ) {
					if ( $p->ID == $p_id ) {
						$ya_incluido = true;
						break;
					}
				}
				
				// Si NO está en la lista, SIEMPRE agregarlo (sin importar stock o estado)
				if ( ! $ya_incluido ) {
					$producto_actual = get_post( $p_id );
					if ( $producto_actual && $producto_actual->post_type === 'merc_producto' ) {
						$productos_disponibles[] = $producto_actual;
						// Added assigned product to selector
					} else {
						// Assigned product not found or not of type merc_producto
					}
				} else {
					// Assigned product already in available list
				}
			}
		}

		ob_start();
		?>
		<!-- INICIO SELECTOR PRODUCTOS MERC -->
		<?php wp_nonce_field( 'merc_envio_producto_guardar', 'merc_envio_producto_nonce' ); ?>
		<div class="col-md-12 mb-4" id="merc_producto_wrapper"
			style="display:block !important; visibility:visible !important; opacity:1 !important;">
			<div class="card">
				<section class="card-header">
					<strong>📦 Producto a Enviar</strong>
				</section>
				<section class="card-body">
					<?php if ( empty( $productos_disponibles ) ) : ?>
						<div class="alert alert-warning">
							<?php if ( $es_admin && $cliente_id === null && $shipment_id === 0 ) : ?>
								<strong>ℹ️ Selecciona un cliente</strong><br>
								Los productos disponibles aparecerán automáticamente cuando selecciones un cliente remitente.
							<?php else : ?>
								<strong>⚠️ No hay productos disponibles</strong><br>
								Por favor, agrega productos al almacén desde el panel de administración.
							<?php endif; ?>
						</div>
					<?php else : ?>
					<div class="row">
						<div class="col-md-8">
							<div class="form-group">
								<label><strong>Productos *</strong></label>
								<div id="merc_product_rows">
									<?php if ( ! empty( $productos_multi ) ) : ?>
										<?php foreach ( $productos_multi as $p_index => $p_item ) :
											$selected_id = intval( $p_item['id'] ?? 0 );
											$selected_qty = intval( $p_item['cantidad'] ?? 1 );
											// Render selected product row
										?>
											<div class="merc-product-row" style="display:flex; gap:12px; align-items:flex-start; margin-bottom:10px;">
											<select name="merc_producto_id[]" class="form-control merc_producto_id" style="flex:1; display:block !important; width:100% !important;">
													<option value="">-- Selecciona un producto --</option>
													<?php foreach ( $productos_disponibles as $prod ) :
														$stock        = function_exists( 'merc_get_product_stock' ) ? merc_get_product_stock( $prod->ID ) : 0;
														$stock        = ! empty( $stock ) ? intval( $stock ) : 0;
														$codigo       = get_post_meta( $prod->ID, '_merc_producto_codigo_barras', true );
														$tipo_medida  = get_post_meta( $prod->ID, '_merc_producto_tipo_medida', true );
														$valor_medida = get_post_meta( $prod->ID, '_merc_producto_valor_medida', true );
														$dimensiones  = get_post_meta( $prod->ID, '_merc_producto_dimensiones', true );
														$sel = ( $prod->ID == $selected_id ) ? 'selected' : '';
														if ( $sel ) {
															// Product matched in available list
														}
													?>
														<option value="<?php echo esc_attr( $prod->ID ); ?>" data-stock="<?php echo esc_attr( $stock ); ?>" <?php echo $sel; ?>>
															<?php echo esc_html( $prod->post_title ); ?> - Stock: <?php echo esc_html( $stock ); ?><?php if ( $tipo_medida || $valor_medida ) : ?> (<?php if ( $tipo_medida && $valor_medida ) { echo esc_html( $tipo_medida . ': ' . $valor_medida ); } elseif ( $tipo_medida ) { echo esc_html( $tipo_medida ); } else { echo esc_html( $valor_medida ); } ?>)<?php endif; ?>
															<?php if ( $codigo ) : ?> [<?php echo esc_html( $codigo ); ?>]<?php endif; ?>
															<?php if ( $tipo_medida ) : ?> | Tipo: <?php echo esc_html( $tipo_medida ); ?><?php endif; ?>
															<?php if ( ! empty( $dimensiones ) && is_array( $dimensiones ) ) : ?> | Dim: <?php echo intval( $dimensiones['largo'] ?? 0 ) . 'x' . intval( $dimensiones['ancho'] ?? 0 ) . 'x' . intval( $dimensiones['alto'] ?? 0 ); ?> cm<?php endif; ?>
														</option>
													<?php endforeach; ?>
													<?php 
													// GARANTÍA FINAL: Si el producto asignado NO está en la lista, agregarlo manualmente
													// (esto puede pasar si tiene stock=0 y no fue incluido en $productos_disponibles)
													if ( $selected_id > 0 ) {
														$encontrado = false;
														foreach ( $productos_disponibles as $p ) {
															if ( $p->ID == $selected_id ) {
																$encontrado = true;
																break;
															}
														}
														
														if ( ! $encontrado ) {
															$prod_asignado = get_post( $selected_id );
															if ( $prod_asignado && $prod_asignado->post_type === 'merc_producto' ) {
																$stock_asignado = function_exists( 'merc_get_product_stock' ) ? merc_get_product_stock( $prod_asignado->ID ) : 0;
																$stock_asignado = ! empty( $stock_asignado ) ? intval( $stock_asignado ) : 0;
																// Assigned product not in available list; adding manually
																?>
																<option value="<?php echo esc_attr( $prod_asignado->ID ); ?>" data-stock="<?php echo esc_attr( $stock_asignado ); ?>" selected>
																	<?php echo esc_html( $prod_asignado->post_title ); ?> - Stock: <?php echo esc_html( $stock_asignado ); ?> [ASIGNADO A ESTE ENVÍO]
																</option>
																<?php
															}
														}
													}
													?>
												</select>

												<input type="number" name="merc_producto_cantidad[]" class="form-control merc_producto_cantidad" value="<?php echo esc_attr( $selected_qty ); ?>" min="1" max="999" style="width:120px;">

												<button type="button" class="button merc_remove_product" style="background:#e74c3c;color:#fff;border:none;padding:6px 10px;border-radius:4px;">−</button>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="merc-product-row" style="display:flex; gap:12px; align-items:flex-start; margin-bottom:10px;">
											<select name="merc_producto_id[]" class="form-control merc_producto_id" style="flex:1; display:block !important; width:100% !important;">
												<option value="">-- Selecciona un producto --</option>
												<?php foreach ( $productos_disponibles as $prod ) :
													$stock        = function_exists( 'merc_get_product_stock' ) ? merc_get_product_stock( $prod->ID ) : 0;
													$stock        = ! empty( $stock ) ? intval( $stock ) : 0;
													$codigo       = get_post_meta( $prod->ID, '_merc_producto_codigo_barras', true );
													$tipo_medida  = get_post_meta( $prod->ID, '_merc_producto_tipo_medida', true );
													$valor_medida = get_post_meta( $prod->ID, '_merc_producto_valor_medida', true );
													$dimensiones  = get_post_meta( $prod->ID, '_merc_producto_dimensiones', true );
												?>
													<option value="<?php echo esc_attr( $prod->ID ); ?>" data-stock="<?php echo esc_attr( $stock ); ?>"><?php echo esc_html( $prod->post_title ); ?> - Stock: <?php echo esc_html( $stock ); ?><?php if ( $tipo_medida || $valor_medida ) : ?> (<?php if ( $tipo_medida && $valor_medida ) { echo esc_html( $tipo_medida . ': ' . $valor_medida ); } elseif ( $tipo_medida ) { echo esc_html( $tipo_medida ); } else { echo esc_html( $valor_medida ); } ?>)<?php endif; ?></option>
												<?php endforeach; ?>
											</select>

									<input type="number" name="merc_producto_cantidad[]" class="form-control merc_producto_cantidad" value="<?php echo esc_attr( $cantidad_seleccionada ); ?>" min="1" max="999" style="width:120px;">
										</div>
									<?php endif; ?>
								</div>
								<div style="margin-top:8px;">
									<button type="button" id="merc_add_product_btn" class="button" style="background:#2ecc71;color:#fff;border:none;padding:6px 10px;border-radius:4px;">+ Agregar producto</button>
								</div>
								<small class="text-muted">Solo se muestran productos disponibles (<?php echo count( $productos_disponibles ); ?> total)</small>
							</div>
						</div>
					</div>
					<div id="merc_stock_warning" class="alert alert-warning" style="display:none;">
						<strong>⚠️</strong> <span id="merc_warning_text"></span>
					</div>
					<?php endif; ?>
				</section>
			</div>
		</div>

		<!-- Plantilla para clon - FUERA de merc_product_rows para evitar ser serializada en POST -->
		<div id="merc_product_template" style="display:none !important; visibility:hidden !important; position:absolute !important; pointer-events:none !important;">
			<div class="merc-product-row" style="display:flex; gap:12px; align-items:flex-start; margin-bottom:10px;">
				<select name="merc_producto_id[]" class="form-control merc_producto_id" style="flex:1; display:block !important; width:100% !important;">
					<option value="">-- Selecciona un producto --</option>
					<?php foreach ( $productos_disponibles as $prod ) :
						$stock = function_exists( 'merc_get_product_stock' ) ? merc_get_product_stock( $prod->ID ) : 0;
						$stock = ! empty( $stock ) ? intval( $stock ) : 0;
						$valor_medida = get_post_meta( $prod->ID, '_merc_producto_valor_medida', true );
						$tipo_medida  = get_post_meta( $prod->ID, '_merc_producto_tipo_medida', true );
					?>
						<option value="<?php echo esc_attr( $prod->ID ); ?>" data-stock="<?php echo esc_attr( $stock ); ?>"><?php echo esc_html( $prod->post_title ); ?> - Stock: <?php echo esc_html( $stock ); ?><?php if ( $tipo_medida || $valor_medida ) : ?> (<?php if ( $tipo_medida && $valor_medida ) { echo esc_html( $tipo_medida . ': ' . $valor_medida ); } elseif ( $tipo_medida ) { echo esc_html( $tipo_medida ); } else { echo esc_html( $valor_medida ); } ?>)<?php endif; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="number" name="merc_producto_cantidad[]" class="form-control merc_producto_cantidad" value="1" min="1" max="999" style="width:120px;">
				<button type="button" class="button merc_remove_product" style="background:#e74c3c;color:#fff;border:none;padding:6px 10px;border-radius:4px;">−</button>
			</div>
		</div>
		<!-- FIN SELECTOR PRODUCTOS MERC -->

		<script>
		jQuery(document).ready(function($) {
			var $productRows     = $('#merc_product_rows');
			var $stockDisplay    = $('#merc_stock_display');
			var $warning         = $('#merc_stock_warning');
			var $warningText     = $('#merc_warning_text');
			var $form            = $('form.wpcfe-new-shipment-form, form[name="wpcfe-shipment-form"], form.add-shipment');
			var isEditMode       = getUrlParameter('wpcfe') === 'update' || $('input[name="post_ID"]').length > 0;

			function actualizarStockRow($row) {
				var $select  = $row.find('.merc_producto_id');
				var $input   = $row.find('.merc_producto_cantidad');
				var $option  = $select.find('option:selected');
				var stock    = parseInt($option.data('stock')) || 0;
				var cantidad = parseInt($input.val()) || 0;

				if (!$select.val()) {
					$stockDisplay.text('');
					$warning.hide();
					return;
				}

				$stockDisplay.html('📦 Disponible: <strong>' + stock + '</strong>');
				
				// En modo edición, permitir cantidades mayores que el stock actual
				// porque el producto ya está asignado a este envío
				if (isEditMode) {
					// No limitar el máximo en modo edición
					$input.removeAttr('max');
					$warning.hide();
				} else {
					// En modo creación, limitar por stock disponible
					$input.attr('max', Math.max(stock, 999)); // Permitir hasta 999 si no hay stock

					if (cantidad > stock && stock > 0) {
						$warning.show();
						$warningText.text('Stock insuficiente. Solo hay ' + stock + ' unidades disponibles.');
						// NO cambiar el valor automáticamente, dejar que el usuario decida
					} else {
						$warning.hide();
					}
				}
			}

			function getUrlParameter(name) {
				var url = window.location.href;
				var regex = new RegExp('[?&]' + name + '=([^&#]*)');
				var match = regex.exec(url);
				return match ? match[1] : null;
			}

			function bindRowEvents($row) {
				$row.find('.merc_producto_id').on('change', function() { 
					actualizarStockRow($row);
					// Limpiar estilos de error al cambiar
					$(this).css('border-color', '').css('background-color', '');
				});
				$row.find('.merc_producto_cantidad').on('input change', function() { actualizarStockRow($row); });
				$row.find('.merc_remove_product').on('click', function() {
					if ($productRows.find('.merc-product-row').length <= 1) return; // mantener al menos una
					$(this).closest('.merc-product-row').remove();
				});
			}

			// Inicializar filas existentes
			$productRows.find('.merc-product-row').each(function() { 
				bindRowEvents($(this));
				// Asignar required a todos los selects visibles
				$(this).find('select[name="merc_producto_id[]"]').prop('required', true).attr('data-parsley-required', 'true');
			});

			function generarTemplateRow() {
				// Clonar desde la plantilla oculta (que ya tiene los names correctos)
				var $template = $('#merc_product_template').find('.merc-product-row').first();
				if (!$template.length) {
					console.error('❌ [TEMPLATE_ERROR] No se encontró #merc_product_template');
					return null;
				}
				
				var $clone = $template.clone();
				return $clone;
			}

			$('#merc_add_product_btn').on('click', function() {
				var $new = generarTemplateRow();
				if (!$new) {
					console.error('❌ No se pudo generar template');
					return;
				}
				
				$new.find('select').val('');
				$new.find('input[type="number"]').val('1');
				// Agregar atributo required solo a la nueva fila visible
				$new.find('select[name="merc_producto_id[]"]').prop('required', true).attr('data-parsley-required', 'true');
				$productRows.append($new);
				bindRowEvents($new);
			});

			// Función para actualizar atributos required basado en visibilidad
			function actualizarRequiredAttributes() {
				// Asignar required SOLO a selects visibles que tienen un valor seleccionado
				$productRows.find('.merc-product-row').each(function() {
					var $row = $(this);
					var $select = $row.find('select[name="merc_producto_id[]"]');
					
					if ($row.is(':visible')) {
						// Asignar required
						$select.prop('required', true);
					} else {
						// Remover required de filas ocultas
						$select.prop('required', false);
					}
				});
				
				// Manejar otros campos que puedan estar ocultos (location, etc.)
				// Remover required de cualquier campo oculto
				$form.find('input, select, textarea').each(function() {
					var $field = $(this);
					
					// Si el campo está oculto, remover required
					if (!$field.is(':visible') || $field.css('display') === 'none') {
						$field.prop('required', false);
					}
				});
			}

			// Ejecutar al cargar
			actualizarRequiredAttributes();
			
			// También ejecutar cuando cambien condiciones que afecten visibilidad
			$(document).on('change', 'input, select', function() {
				setTimeout(function() { actualizarRequiredAttributes(); }, 100);
			});

			$form.on('submit', function(e) {
				// Actualizar required attributes antes de validar
				actualizarRequiredAttributes();
				
				// CRÍTICO: Remover filas vacías del POST
				$productRows.find('.merc-product-row').each(function() {
					var $row = $(this);
					var productoId = $row.find('.merc_producto_id').val().trim();
					// Si la fila está vacía, remover el atributo name para que no se envíe
					if (!productoId) {
						$row.find('select, input').removeAttr('name');
					}
				});
				
				var $form = $(this);
				var tipoEnvio = $('#tipo-envio-actual').val() || $('input[name="tipo_envio"]').val() || '';
				var tipoLower = (tipoEnvio + '').trim().toLowerCase();
				var isFullFitment = (tipoLower === 'full_fitment' || tipoLower.includes('full'));
				
				// LOG: Ver formulario siendo enviado
				console.log('🚀 [SUBMIT] Formulario siendo enviado - tipo_envio:', tipoEnvio, '| isFullFitment:', isFullFitment);
				console.log('🚀 [SUBMIT] Action:', $form.attr('action'), '| Method:', $form.attr('method'));
				
				// LOG: Ver qué se va a enviar
				console.log('📋 Datos a enviar:');
				var productosArray = [];
				$productRows.find('.merc-product-row').each(function() {
					var $row = $(this);
					var productoId = $row.find('.merc_producto_id').val();
					var cantidad = $row.find('.merc_producto_cantidad').val();
					console.log('  - Producto ID:', productoId, 'Cantidad:', cantidad);
					if (productoId) {
						productosArray.push({id: productoId, cantidad: cantidad});
					}
				});
				console.log('📋 Array final productos:', productosArray);
				console.log('📋 Nonce presente:', $form.find('input[name="merc_envio_producto_nonce"]').val() ? 'SÍ' : 'NO');
				
				// VALIDACIÓN ESPECÍFICA: Solo validar campos FULL FITMENT si aplica
				if (isFullFitment) {
					var hasValidProduct = false;
					var emptySelectFound = false;
					var errorMsg = '';
					
					$productRows.find('.merc-product-row').each(function() {
						var $row = $(this);
						var productoId = $row.find('.merc_producto_id').val();
						var cantidad = parseInt($row.find('.merc_producto_cantidad').val()) || 0;
						var stock = parseInt($row.find('.merc_producto_id option:selected').data('stock')) || 0;
						
						if (!productoId) {
							emptySelectFound = true;
							// Marcar visualmente el select como error
							$row.find('.merc_producto_id').css('border-color', '#dc3545').css('background-color', '#fff5f5');
							console.log('❌ Producto vacío en fila');
							return true; // continue
						} else {
							// Limpiar estilos de error si ahora está ok
							$row.find('.merc_producto_id').css('border-color', '').css('background-color', '');
						}
						
						if (cantidad < 1) {
							errorMsg = '⚠️ Cantidad debe ser mayor a 0.';
							console.log('❌ Cantidad inválida:', cantidad);
							e.preventDefault();
							alert(errorMsg);
							return false;
						}
						
						hasValidProduct = true;
						console.log('✅ Producto válido - ID:', productoId, 'Cantidad:', cantidad, 'Stock:', stock);
					});
					
					if (emptySelectFound) {
						console.log('❌ Se encontraron selects vacíos');
						alert('⚠️ Debes seleccionar un producto en todas las filas antes de guardar.');
						e.preventDefault();
						return false;
					}
					
					if (!hasValidProduct && $productRows.find('.merc-product-row').length > 0) {
						console.log('❌ No hay productos válidos');
						alert('⚠️ FULL FITMENT requiere al menos un producto válido.');
						e.preventDefault();
						return false;
					}
				}
				
				// NO usar reportValidity() porque bloquea campos requeridos no enfocables
				// Solo permitir que el navegador envíe el formulario
				console.log('✅ Validación completada, permitiendo envío de formulario');
			});

			// Ejecutar una vez para sincronizar displays
			$productRows.find('.merc-product-row').each(function() { actualizarStockRow($(this)); });
		});
		</script>

		<style>
		#merc_producto_wrapper {
			display:    block   !important;
			visibility: visible !important;
			opacity:    1       !important;
			position:   relative !important;
			z-index:    1       !important;
		}
		</style>
		<?php
		echo ob_get_clean();
	}

	/* ── Valores predeterminados en campos de paquetes ───────────────────── */

	public function package_defaults_script(): void {
		if ( ! isset( $_GET['wpcfe'] ) || $_GET['wpcfe'] !== 'add' || ! isset( $_GET['type'] ) ) return;
		?>
		<script>
		jQuery(document).ready(function($) {
			setTimeout(function() {
				$('#wpcfe-packages-repeater tbody tr').each(function() {
					var $row = $(this);
					var lengthField = $row.find('input[name*="length"]');
					var widthField  = $row.find('input[name*="width"]');
					var heightField = $row.find('input[name*="height"]');
					var weightField = $row.find('input[name*="weight"]');
					if (lengthField.length && !lengthField.val()) lengthField.val('25');
					if (widthField.length  && !widthField.val())  widthField.val('25');
					if (heightField.length && !heightField.val()) heightField.val('25');
					if (weightField.length && !weightField.val()) weightField.val('3');
				});
			}, 500);

			// Validación adicional: verificar campos del destinatario (frontend)
			// Sólo en formulario de creación (wpcfe=add)
			try {
				var addForm = $('form.wpcfe-new-shipment-form, form[name="wpcfe-shipment-form"], form.add-shipment');
				if (addForm.length) {
					addForm.on('submit', function(e){
						var invalid = null;
						$(this).find('input,select,textarea').filter(function(){
							var name = $(this).attr('name') || '';
							if (name.indexOf('wpcargo_receiver_') !== 0) return false;
							if (/map|link/i.test(name)) return false; // excluir mapas/links
							if (!$(this).is(':visible')) return false;
							var val = $.trim($(this).val());
							if ($(this).is('select')) return (val === '' || val === null);
							return (val === '');
						}).each(function(){ if (!invalid) invalid = this; });
						if (invalid) {
							e.preventDefault(); e.stopPropagation();
							try { $(invalid).focus(); } catch(err){}
							alert('Complete los campos obligatorios del destinatario antes de guardar.');
							return false;
						}
					});
				}
			} catch(err) { console && console.error && console.error(err); }
		});
		</script>

		<script>
		// Fallback robusto: forzar validación HTML5 aun si el formulario tiene 'novalidate'
		(function(){
			try {
				var selectors = 'form.wpcfe-new-shipment-form, form[name="wpcfe-shipment-form"], form.add-shipment';
				var nodeList = document.querySelectorAll(selectors);
				Array.prototype.forEach.call(nodeList, function(f){
					if (!f) return;
					// Interceptar submit (capturing) para evitar envío si inválido
					f.addEventListener('submit', function(ev){
						try {
							if (!f.checkValidity()) {
								ev.preventDefault(); ev.stopPropagation();
								if (typeof f.reportValidity === 'function') {
									f.reportValidity();
								} else {
									alert('Complete los campos obligatorios antes de guardar.');
								}
								return false;
							}
						} catch(e) { /* ignore */ }
					}, true);

					// Proteger contra submit programático: envolver form.submit
					try {
						var origSubmit = f.submit;
						f.submit = function(){
							if (!f.checkValidity()) {
								if (typeof f.reportValidity === 'function') f.reportValidity();
								return false;
							}
							return origSubmit.apply(f, arguments);
						};
					} catch(err) { /* ignore non-writable submit */ }
				});
			} catch(e) { console && console.error && console.error(e); }
		})();
		</script>
		<?php
	}

	/* ── CSS: ocultar columna de descripción en paquetes ─────────────────── */

	public function package_form_styles(): void {
		if ( ! isset( $_GET['wpcfe'] ) || $_GET['wpcfe'] !== 'add' ) return;
		$hide_packages = ( isset( $_GET['type'] ) && $_GET['type'] === 'full_fitment' );
		?>
		<style>
		textarea.wpc-pm-description,
		textarea[name*="[wpc-pm-description]"] { display: none !important; }
		#wpcfe-packages-repeater td:has(textarea.wpc-pm-description),
		#wpcfe-packages-repeater td:has(textarea[name*="[wpc-pm-description]"]) { display: none !important; }
		#wpcfe-packages-repeater thead tr th:nth-child(3) { display: none !important; }
		<?php if ( $hide_packages ) : ?>
		#package_id { display: none !important; }
		<?php endif; ?>
		</style>
		<?php
	}

	/* ── Guardar datos financieros del formulario ─────────────────────────── */

	public function save_financial_data( int $post_id ): void {
		if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) return;

		error_log("🔔 [HOOK_TRIGGERED] save_financial_data() ejecutado para envío #{$post_id}");

		if ( isset( $_POST['wpcargo_costo_producto'] ) ) {
			update_post_meta( $post_id, 'wpcargo_costo_producto',
				sanitize_text_field( $_POST['wpcargo_costo_producto'] ) );
		}

		if ( isset( $_POST['wpcargo_costo_envio'] ) ) {
			update_post_meta( $post_id, 'wpcargo_costo_envio',
				sanitize_text_field( $_POST['wpcargo_costo_envio'] ) );
		}

		if ( isset( $_POST['wpcargo_cargo_remitente'] ) ) {
			update_post_meta( $post_id, 'wpcargo_cargo_remitente',
				sanitize_text_field( $_POST['wpcargo_cargo_remitente'] ) );
		}

		$monto = floatval( get_post_meta( $post_id, 'wpcargo_monto', true ) );

		update_post_meta( $post_id, 'wpcargo_quien_paga', 'remitente' );
		update_post_meta( $post_id, 'wpcargo_cobrado_por_motorizado', $monto > 0 ? $monto : '0' );

		if ( ! get_post_meta( $post_id, 'wpcargo_estado_pago_motorizado', true ) ) {
			update_post_meta( $post_id, 'wpcargo_estado_pago_motorizado', 'pendiente' );
		}
		if ( ! get_post_meta( $post_id, 'wpcargo_cliente_pago_a', true ) ) {
			update_post_meta( $post_id, 'wpcargo_cliente_pago_a', 'pendiente' );
		}

		/* ── Guardar productos MERC (soporta múltiples) ───────────────── */
		error_log("🔍 [MERC_SAVE_CHECK] Verificando si merc_producto_id existe en POST...");
		error_log("📮 [POST_DUMP] POST keys: " . json_encode(array_keys($_POST)));
		
		if ( isset( $_POST['merc_producto_id'] ) ) {
			error_log("✅ [MERC_FOUND] merc_producto_id encontrado en POST");
			
			// Validar nonce si viene presente
			if ( isset( $_POST['merc_envio_producto_nonce'] ) ) {
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['merc_envio_producto_nonce'] ) ), 'merc_envio_producto_guardar' ) ) {
					error_log("❌ [NONCE_FAIL] Nonce inválido para envío #{$post_id}");
					return;
				}
				error_log("✅ [NONCE_OK] Nonce válido");
			} else {
				error_log("⚠️ [NONCE_MISSING] Nonce no presente en POST");
			}

			// DIAGNÓSTICO: Ver qué recibe del formulario (ANTES de filtrar)
			$raw_ids = $_POST['merc_producto_id'];
			$raw_qtys = isset($_POST['merc_producto_cantidad']) ? $_POST['merc_producto_cantidad'] : [];
				// POST raw product ids and quantities received (for debugging)

			// CRÍTICO: Filtrar elementos vacíos del array antes de procesar
			$ids = [];
			$qtys = [];
			foreach ( (array) $_POST['merc_producto_id'] as $idx => $id ) {
				$id = intval( $id );
				// Solo agregar si el ID es válido (> 0)
				if ( $id > 0 ) {
					$ids[] = $id;
					// Agregar la cantidad correspondiente
					$qty = isset( $_POST['merc_producto_cantidad'][ $idx ] ) ? intval( $_POST['merc_producto_cantidad'][ $idx ] ) : 1;
					$qtys[] = $qty;
				}
			}

				// IDs and quantities filtered for storage

			$productos_to_store = [];
			foreach ( $ids as $i => $id ) {
				if ( $id <= 0 ) {
						// Invalid product id; skipping
					continue;
				}
				$cant = isset( $qtys[ $i ] ) && intval( $qtys[ $i ] ) > 0 ? intval( $qtys[ $i ] ) : 1;
				$productos_to_store[] = [ 'id' => $id, 'cantidad' => $cant ];
						// Product added to list for saving
			}

						// Products prepared for storage (count: " . count($productos_to_store) . ")

			// Obtener productos anteriores para saber cuáles liberar si se remueven
			$old_productos = get_post_meta( $post_id, '_merc_productos_multi', true );
			$old_data = []; // Array: product_id => cantidad
			if ( ! empty( $old_productos ) && is_array( $old_productos ) ) {
				foreach ( $old_productos as $op ) {
					if ( ! empty( $op['id'] ) ) {
						$old_data[ intval( $op['id'] ) ] = intval( $op['cantidad'] ?? 1 );
					}
				}
			}

			if ( ! empty( $productos_to_store ) ) {
				update_post_meta( $post_id, '_merc_productos_multi', $productos_to_store );
				// Mantener compatibilidad: guardar primer producto en meta antigua
				update_post_meta( $post_id, '_merc_producto_id', $productos_to_store[0]['id'] );
				update_post_meta( $post_id, '_merc_producto_cantidad', $productos_to_store[0]['cantidad'] );
				error_log("✅ [MERC_SAVE] Envío #{$post_id} - Metas guardadas exitosamente");
				
				// 🚨 CRÍTICO: Manejar asignación/desasignación en la tabla de stock por SKU
				$affected_products = [];
				
				foreach ( $productos_to_store as $p_item ) {
					$product_id = intval( $p_item['id'] ?? 0 );
					$required_qty = intval( $p_item['cantidad'] ?? 1 );
					
					if ( $product_id <= 0 ) continue;
					
					$affected_products[ $product_id ] = $required_qty;
					
					// Contar cuántos SKUs ya están asignados a este envío en la tabla
					$current_assigned = $this->merc_count_assigned_units( $product_id, $post_id );
					
								// Stock assignment details for product
					
					// Si necesitamos más unidades que las asignadas, asignar el resto
					if ( $required_qty > $current_assigned ) {
						$qty_to_assign = $required_qty - $current_assigned;
						if ( function_exists( 'merc_assign_stock_units' ) ) {
							$result = merc_assign_stock_units( $product_id, $qty_to_assign, $post_id );
							if ( $result ) {
										// Assigned units to shipment
							} else {
										// Could not assign some units (insufficient stock)
							}
						}
					}
					// Si necesitamos menos, desasignar el exceso
					elseif ( $required_qty < $current_assigned ) {
						$qty_to_unassign = $current_assigned - $required_qty;
						if ( function_exists( 'merc_unassign_stock_by_shipment' ) ) {
							merc_unassign_stock_by_shipment( $product_id, $qty_to_unassign, $post_id );
												// Units unassigned
						}
					}
				}
				
				// Desasignar productos que se removieron completamente
				foreach ( $old_data as $product_id => $old_qty ) {
					if ( ! isset( $affected_products[ $product_id ] ) ) {
						// Este producto ya no está en el envío, desasignarlo todo
						$current_assigned = $this->merc_count_assigned_units( $product_id, $post_id );
						if ( $current_assigned > 0 && function_exists( 'merc_unassign_stock_by_shipment' ) ) {
							merc_unassign_stock_by_shipment( $product_id, $current_assigned, $post_id );
													// All units unassigned (product removed)
						}
						$affected_products[ $product_id ] = 0; // Marcar para actualizar estado
					}
				}
				
				// Actualizar estado de todos los productos afectados
				foreach ( array_keys( $affected_products ) as $product_id ) {
					$this->merc_update_product_estado( $product_id );
				}
				
				// Verificación final
				$verificacion = get_post_meta( $post_id, '_merc_productos_multi', true );
				error_log("✅ [MERC_VERIFY] Envío #{$post_id} - Verificación POST-SAVE: " . json_encode($verificacion));
			} else {
				error_log("⚠️ [MERC_SAVE] Envío #{$post_id} - Sin productos válidos para guardar, limpiando metas");
				delete_post_meta( $post_id, '_merc_productos_multi' );
				delete_post_meta( $post_id, '_merc_producto_id' );
				delete_post_meta( $post_id, '_merc_producto_cantidad' );
				
				// Desasignar TODOS los productos porque se vació la selección
				foreach ( $old_data as $product_id => $old_qty ) {
					if ( function_exists( 'merc_unassign_stock_by_shipment' ) ) {
						merc_unassign_stock_by_shipment( $product_id, $old_qty, $post_id );
														// All units unassigned (shipment cleaned)
					}
					$this->merc_update_product_estado( $product_id );
				}

				error_log("⚠️ [MERC_SAVE] Envío #{$post_id} - Metas borradas");
			}
		} else {
			error_log("⚠️ [MERC_SAVE] Envío #{$post_id} - merc_producto_id NO está en POST");
		}
	}

	/**
	 * Helper: Contar cuántos SKUs están asignados a este envío
	 */
	private function merc_count_assigned_units( int $product_id, int $shipment_id ): int {
		global $wpdb;
		$table = 'merc_product_stock'; // Cambiar si la tabla tiene otro nombre
		
		// Intentar obtener el nombre de la tabla dinámicamente si existe la función
		if ( function_exists( 'merc_get_stock_table_name' ) ) {
			$table = merc_get_stock_table_name();
		}
		
		$count = intval( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND shipment_id = %d AND status = 'assigned'",
			$product_id,
			$shipment_id
		) ) );
		
		return $count;
	}

	/**
	 * Helper: Actualizar estado del producto basado en stock disponible
	 * - Si stock > 0 → 'sin_asignar'
	 * - Si stock = 0 → 'asignado'
	 */
	private function merc_update_product_estado( int $product_id ): void {
		$stock = function_exists( 'merc_get_product_stock' ) ? merc_get_product_stock( $product_id ) : 0;
		$nuevo_estado = ( intval( $stock ) > 0 ) ? 'sin_asignar' : 'asignado';
		update_post_meta( $product_id, '_merc_producto_estado', $nuevo_estado );
					// Updated product state based on stock
	}

	/* ── Verificación final del costo de envío (logging) ─────────────────── */

	public function verify_final_shipping_cost( int $post_id, $record = null ): void {
		if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) return;

		// Evitar registrar verificaciones tempranas ejecutadas por save_post cuando
		// las metas aún no han sido normalizadas por el importador CSV.
		// Sólo registrar si estamos siendo llamados desde el hook CSV o si
		// ya existen datos relevantes (tipo o distrito).
		$current = function_exists('current_filter') ? current_filter() : '';

		$costo    = get_post_meta( $post_id, 'wpcargo_costo_envio', true );
		$distrito = get_post_meta( $post_id, 'wpcargo_distrito_destino', true );
		$tipo     = get_post_meta( $post_id, 'tipo_envio', true );

		// Si no hay tipo ni distrito y no venimos del flujo CSV, posponer
		if ( empty( $tipo ) && empty( $distrito ) && $current !== 'wpcie_after_save_csv_import' ) {
			return;
		}

		error_log( "🔚 [FINAL_VERIFICATION] Envío #{$post_id} | Tipo: {$tipo} | Distrito: {$distrito} | Costo: {$costo}" );
	}

	public function sync_service_cost_by_status( int $post_id ): void {
		if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) {
			return;
		}

		if ( function_exists( 'merc_sync_service_cost_by_status' ) ) {
			merc_sync_service_cost_by_status( $post_id );
		}
	}

	/* ── Logging al editar un envío ───────────────────────────────────────── */

	public function log_edit_shipping_cost( int $post_id, \WP_Post $post ): void {
		if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) return;
		if ( $post->post_status === 'auto-draft' ) return;
		$costo    = get_post_meta( $post_id, 'wpcargo_costo_envio', true );
		$distrito = get_post_meta( $post_id, 'wpcargo_distrito_destino', true );
		$tipo     = get_post_meta( $post_id, 'tipo_envio', true );
		error_log( "✏️ [EDIT_DETECTED] Envío #{$post_id} | Tipo: {$tipo} | Distrito: {$distrito} | Costo antes: {$costo}" );
	}

	/* ── AJAX: Recargar productos cuando cambia el cliente ─────────────────── */

	public function __construct_ajax_handlers() {
		// El handler AJAX ahora se ejecuta en get_client_data_ajax()
		// (generar_opciones_productos_cliente()) para optimizar y evitar llamadas innecesarias
	}

}

// Inicializar la clase (una sola instancia)
new MERC_Shipment_Form_Fields();




