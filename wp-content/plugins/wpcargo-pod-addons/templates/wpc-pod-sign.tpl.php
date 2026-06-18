<?php
	$signature_fields 		= wpcpod_signature_field_list();
	$get_sid 				= $shipment_id;
	$get_pod_img 			= get_post_meta($get_sid, 'wpcargo-pod-image', true);
	$pod_signature 			= get_post_meta($get_sid, 'wpcargo-pod-signature', true);
	$shipment_update 		= maybe_unserialize( get_post_meta( $get_sid, 'wpcargo_shipments_update', true ) );
	$shipment_update 		= $shipment_update && is_array( $shipment_update ) ? wpcargo_history_order( $shipment_update )[0] : array();
?>
<?php do_action( 'wpcpod_before_sign_popup_form' ); ?>
<form id="wpc_pod_signature-form" method="post" action="">
	<input type="hidden" id="__pod_id" name="__pod_id" value="<?php echo $get_sid;?>">
	<input type="hidden" id="__pod_signature" name="__pod_signature" value="<?php echo $pod_signature; ?>">
	<input type="hidden" id="wpcpod_nonce" name="wpcpod_nonce" value="<?php echo wp_create_nonce('wpcpod_upload_image'); ?>">	
	<div id="pod-pop-up">
		<?php do_action( 'wpcpod_before_popup_header' ); ?>
		<?php	
		if ( is_plugin_active( 'wpcargo-custom-field-addons/wpcargo-custom-field.php' ) ) {
			require_once(WPCARGO_POD_PATH.'templates/wpc-pod-sign-header-cf.tpl.php');
		}else{
			require_once(WPCARGO_POD_PATH.'templates/wpc-pod-sign-header.tpl.php');
		}
		?>
		<?php do_action( 'wpcpod_after_popup_header', $get_sid ); ?>
		<?php do_action( 'wpcpod_before_upload_container', $get_sid ); ?>
		<div class="wpcargo-upload container">
			<div class="wpcargo-add-signature">
				<?php require_once( WPCARGO_POD_PATH.'templates/wpc-pod-signature-form.tpl.php'); ?>
			</div>	
			<div id="images-section">
                <a href="#" id="wpcargo-pod-img-btn" class="wpcargo-btn wpcargo-btn-success"><?php esc_html_e( 'ADD IMAGES', 'wpcargo-pod' ); ?></a>	
                <input type="file" id="wpcargo-pod-file-input" multiple accept="image/*" style="display:none;">
                <input type="file" id="wpcargo-pod-camera-input" accept="image/*" capture="camera" style="display:none;">
                <div id="wpcargo-pod-images">			
                    <?php
                    $cambio_producto = get_post_meta($get_sid, 'cambio_producto', true);
                    if (strtolower(trim($cambio_producto)) === 'sí' || strtolower(trim($cambio_producto)) === 'si') {
                        echo '<p class="header-pod-result" style="color: #d9534f; font-weight: bold; font-size: 16px;">⚠️ ESTE PEDIDO TIENE RECOJO, NO TE OLVIDES DE RECOGER EL PRODUCTO DEL CLIENTE</p>';
                    }
                    ?>
                    <?php
                    if(!empty($get_pod_img)) {
                        $explode_pod_img = array_filter( explode(",", $get_pod_img) );
                        if(is_array($explode_pod_img)) {
                            foreach($explode_pod_img as $pod_img) {
                                echo '<div class="gallery-thumb" data-id="'.$pod_img.'"><div class="single-img"><img width="250" src="'.wp_get_attachment_url( $pod_img ).'"/></div><span class="delete-attachment" title="Remove">x</span></div>';
                            }
                        }
                    } else {
                        ?><img src="<?php echo WPCARGO_POD_URL. 'assets/img/no-image.jpg'; ?>"><?php
                    }
                    ?>	
                </div>
            </div>
		</div>
		<?php do_action( 'wpcpod_after_upload_container', $get_sid ); ?>
		<?php do_action( 'wpcpod_before_status_container', $get_sid ); ?>
		<div class="pod-status container">	
			<div class="pod-details row">
				<?php foreach( $signature_fields as $metakey => $fieldinfo ): ?>
				<?php
                    if (isset($fieldinfo['label']) && strpos($fieldinfo['label'], 'Total a recibir') !== false) {
                        continue;
                    }
                ?>
					<?php 
						$field_value = array_key_exists( $metakey, $shipment_update ) ? $shipment_update[$metakey] : '' ; 
						$class 		 = $fieldinfo['field'] != 'select' ? 'form-control' : 'form-control browser-default' ;
					?>
					<div class="col-md-6 mb-4">
						<p>
							<label><?php echo $fieldinfo['label']; ?> </label><br/>
							<?php echo wpcargo_field_generator( $fieldinfo, $metakey, $field_value, $class .' '.$metakey ); ?>
						</p>		
					</div>
				<?php endforeach; ?>
				<?php
                    $shipment_id   = $get_sid;
                
                    $monto_total   = floatval( get_post_meta( $shipment_id, 'wpcargo_total_cobrar', true ) );
                
                    if ( $monto_total <= 0 ) {
                        $monto_total = floatval( get_post_meta( $shipment_id, 'wpcargo_monto', true ) );
                    }
                
                    if ( $monto_total <= 0 ) {
                        $costo_producto = floatval( get_post_meta( $shipment_id, 'wpcargo_costo_producto', true ) );
                        $costo_envio    = floatval( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) );
                        if ( $costo_producto > 0 || $costo_envio > 0 ) {
                            $monto_total = $costo_producto + $costo_envio;
                        }
                    }
                
                    $modo_pago_raw = get_post_meta( $shipment_id, 'payment_wpcargo_mode_field', true );
                    $modo_pago     = strtolower( trim( $modo_pago_raw ) );
                    $es_no_cobrar  = ( $modo_pago === 'no cobrar' || $modo_pago === '1' );

                    $costo_envio = floatval( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) );
                    $monto_display = $es_no_cobrar ? $costo_envio : $monto_total;
                ?>

				<?php if ( $es_no_cobrar ) : ?>
					<?php /* NO COBRAR: solo hidden inputs para que el servidor no falle, se oculta toda la sección de pago */ ?>
                    <div class="col-md-12 mb-4">
                        <div class="alert alert-info" style="background-color: #d9edf7; color: #31708f; padding: 15px; border-radius: 4px; border: 1px solid #bce8f1;">
                            <strong>ℹ️ ESTE PEDIDO TIENE CERO COSTO, CIÉRRALO DIRECTAMENTE PARA QUE PASE AL ESTADO SELECCIONADO.</strong>
                        </div>
                    </div>
					<input type="hidden" name="wpcargo_total_cobrar" id="hidden-wpcargo-total" value="0">
					<input type="hidden" id="hidden-customer-payment" value="0">
					<input type="hidden" name="pod_payment_methods" id="pod_payment_methods" value="[]">

				<?php else : ?>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label><strong>📦 Costo Producto</strong></label>
                        <input type="text" class="form-control" value="S/. <?php echo number_format( (float) get_post_meta( $shipment_id, 'wpcargo_costo_producto', true ), 2 ); ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label><strong>🚚 Costo Envío</strong></label>
                        <input type="text" class="form-control" value="S/. <?php echo number_format( (float) get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ), 2 ); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-12 mb-4">
                    <label><strong>💰 Total a recibir</strong></label>
                    <input type="text" id="monto_display_input" class="form-control" value="<?php echo number_format( $monto_display, 2 ); ?>" readonly>
                    <input type="hidden" id="hidden-customer-payment" value="<?php echo number_format( $monto_display, 2, '.', '' ); ?>">
                    <input type="hidden" name="wpcargo_total_cobrar" id="hidden-wpcargo-total" value="<?php echo number_format( $monto_display, 2, '.', '' ); ?>">
                </div>
                
                <div class="col-md-12 mb-4" id="payment-area">
                        <label><strong>Métodos de pago</strong></label>

                        <div id="payment-methods-list">
                        <?php
                        $methods_json  = get_post_meta( $shipment_id, 'pod_payment_methods', true );
                        $methods_saved = json_decode( $methods_json, true );
                        if ( ! empty( $methods_saved ) && is_array( $methods_saved ) ) {
                            $method_names = array(
                                'efectivo'   => 'Pago a motorizado',
                                'pago_marca' => 'Pago a Marca',
                                'pago_merc'  => 'Pago a MERC',
                                'pos'        => 'POS',
                            );
                            foreach ( $methods_saved as $item ) {
                                $metodo = isset( $item['metodo'] ) ? $item['metodo'] : '';
                                $monto  = isset( $item['monto'] )  ? floatval( $item['monto'] ) : 0;
                                $display = isset( $method_names[ $metodo ] ) ? $method_names[ $metodo ] : ucfirst( str_replace( '_', ' ', $metodo ) );
                                ?>
                                <div class="fila-metodo" style="border:1px solid #ccc;padding:10px;margin-bottom:10px;border-radius:5px;">
                                    <label><strong>Método</strong></label>
                                    <div class="method-selector" style="margin-bottom:8px;">
                                        <button type="button" class="btn btn-light dropdown-toggle seleccionar-metodo" style="width:100%;text-align:left;">
                                            <?php echo esc_html( $display ); ?>
                                        </button>
                                        <div class="method-options" style="display:none;border:1px solid #ddd;border-radius:4px;background:white;">
                                            <div class="method-option" data-value="efectivo">Pago a motorizado</div>
                                            <div class="method-option" data-value="pago_marca">Pago a Marca</div>
                                            <div class="method-option" data-value="pago_merc">Pago a MERC</div>
                                            <div class="method-option" data-value="pos">POS</div>
                                        </div>
                                    </div>
                                    <input type="hidden" class="pay-method" value="<?php echo esc_attr( $metodo ); ?>">
                                    <label><strong>Monto</strong></label>
                                    <input type="text" class="pay-amount form-control" inputmode="decimal" placeholder="0.00" value="<?php echo esc_attr( number_format( $monto, 2, '.', '' ) ); ?>" pattern="[0-9]+(\.[0-9]{1,2})?">

                                    <label><strong>Imagen del comprobante *</strong></label>
                                    <input type="file" class="pay-image form-control" accept="image/*" style="margin-bottom:8px;">
                                    <div class="image-preview-container"></div>

                                    <button type="button" class="btn btn-danger btn-sm remove-metodo" style="margin-top:8px;">Eliminar</button>
                                </div>
                                <?php
                            }
                        }
                        ?>
                        </div>

                        <button type="button" id="add-method" class="btn btn-primary" style="margin-top:10px;">
                            ➕ Agregar método de pago
                        </button>

                        <p style="margin-top:15px;font-size:16px;">
                            <strong>Total ingresado: S/. <span id="total-ingresado">0.00</span></strong>
                        </p>

                        <p id="payment-error" style="color:red;font-weight:bold;display:none;"></p>

                        <input type="hidden" name="pod_payment_methods" id="pod_payment_methods" value="[]">
                </div>

				<?php endif; ?>

			</div>
		</div>
		<?php do_action( 'wpcpod_after_status_container', $get_sid ); ?>
		<div class="pod-submit container">	
			<div class="status-btn pt-sm-4">
                <input type="submit" class="delivered-btn btn btn-success" name="submit" value="<?php esc_html_e('Update', 'wpcargo-pod' ); ?>" disabled>
			</div>
		</div>
    </div>
</form>
<?php do_action( 'wpcpod_after_sign_popup_form' ); ?>
<script>
const AJAXHANDLER_GLOBAL_POD = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
window.sendDebug = function(payload) {
    try {
        payload = payload || {};
        payload._merc_pod_client = 1;
        if (!payload.shipmentID) {
            var el = document.querySelector('[name="__pod_id"]');
            payload.shipmentID = el ? el.value : '';
        }
        jQuery.post(AJAXHANDLER_GLOBAL_POD, Object.assign({ action: 'merc_pod_client_debug' }, payload));
    } catch(e) {}
};

jQuery(document).ready(function ($) {
    const shipmentID 	= $( '[name="__pod_id"]' ).val();
    const AJAXHANDLER 	= AJAXHANDLER_GLOBAL_POD;
		$('#pod-pop-up').on('click', '.delete-attachment', function(){
			const parentElem = $(this).closest('.gallery-thumb');
			const attchID 	 = parentElem.attr('data-id');
			$.ajax({
				type: "POST",
				datatype: 'JSON',
				url: AJAXHANDLER,
				data:{
					action: 'wpcpod_delete_image',
					shipmentID : shipmentID,
					attchID: attchID
				},
				beforeSend:function(){
                    parentElem.addClass('d-none');
                },
				success:function(response){
					if(!response.status){
						parentElem.removeClass('d-none');
						alert( response.message );
						return;
					}
					parentElem.remove();
				}
			});
		});
		$( '#wpcargo-pod-img-btn' ).click(function(e) {
			e.preventDefault();

			var swalAvailable = typeof window.Swal !== 'undefined' || typeof window.swal !== 'undefined';
			var SwalLib = window.Swal || window.swal;

			if (!swalAvailable || !SwalLib || typeof SwalLib.fire !== 'function') {
				// Fallback: abrir selector de archivos directamente
				$('#wpcargo-pod-file-input').click();
				return;
			}

			SwalLib.fire({
				title: 'Agregar imagen',
				text: '¿Cómo deseas agregar la imagen?',
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: '📷 Tomar foto',
				cancelButtonText: '🖼️ Subir imagen',
				cancelButtonColor: '#3085d6',
				confirmButtonColor: '#28a745',
				reverseButtons: false,
				footerHtml: '',
				customClass: {
					actions: 'merc-pod-swal-actions'
				}
			}).then(function(result) {
				if (result.isConfirmed) {
					// Tomar foto con la cámara
					$('#wpcargo-pod-camera-input').val('').click();
				} else if (result.dismiss === SwalLib.DismissReason.cancel) {
					// Subir imagen desde archivos
					$('#wpcargo-pod-file-input').val('').click();
				}
			});
		});

		function mercPodSubirImagenes(files) {
			if (!files || files.length === 0) return;

			var formData = new FormData();
			var validImages = 0;
			var validExtensions = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml'];

			for (var i = 0; i < files.length; i++) {
				if (validExtensions.indexOf(files[i].type) !== -1) {
					formData.append('files[]', files[i]);
					validImages++;
				}
			}

			if (validImages === 0) {
				alert('Por favor selecciona solo imágenes válidas (PNG, JPG, GIF, SVG)');
				return;
			}

			var originalText = $('#wpcargo-pod-img-btn').text();
			$('#wpcargo-pod-img-btn').prop('disabled', true).css('opacity', '0.6').html('⏳ Procesando...');

			formData.append('action', 'wpcpod_direct_upload_image');
			formData.append('shipmentID', shipmentID);
			formData.append('nonce', $('#wpcpod_nonce').val());

			$.ajax({
				type: 'POST',
				url: AJAXHANDLER,
				data: formData,
				processData: false,
				contentType: false,
				timeout: 120000,
				success: function(response) {
					$('#wpcargo-pod-img-btn').prop('disabled', false).css('opacity', '1').text(originalText);
					if (response.success) {
						$('#wpcargo-pod-images').html(response.html);
					} else {
						alert(response.message || 'Error al subir las imágenes');
					}
				},
				error: function(xhr, status, error) {
					$('#wpcargo-pod-img-btn').prop('disabled', false).css('opacity', '1').text(originalText);
					if (status === 'timeout') {
						alert('La solicitud tardó demasiado. Intenta con menos imágenes o imágenes más pequeñas.');
					} else {
						console.error('Error AJAX:', status, error, xhr.responseText);
						alert('Error al conectar con el servidor: ' + error);
					}
				}
			});
		}

		$('#wpcargo-pod-file-input').change(function() {
			mercPodSubirImagenes(this.files);
			$(this).val('');
		});

		$('#wpcargo-pod-camera-input').change(function() {
			mercPodSubirImagenes(this.files);
			$(this).val('');
		});
	});
	// ---------------- MÉTODOS DE PAGO DINÁMICOS ------------------
    const paymentModes = <?php echo json_encode( get_option('wpcargo_payment_modes', []) ); ?>;
    
    // Función para comprimir imagen antes de convertir a base64
    function compressImage(file, callback) {
        var reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(event) {
            var img = new Image();
            img.src = event.target.result;
            img.onload = function() {
                var canvas = document.createElement('canvas');
                var maxWidth = 800;
                var maxHeight = 800;
                var width = img.width;
                var height = img.height;

                // Calcular nuevas dimensiones manteniendo aspecto
                if (width > height) {
                    if (width > maxWidth) {
                        height *= maxWidth / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width *= maxHeight / height;
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // Convertir a base64 con compresión JPEG
                var compressedBase64 = canvas.toDataURL('image/jpeg', 0.7); // 0.7 = 70% calidad
                callback(compressedBase64);
            };
        };
    }
    
    // monto base que debe cobrar (leer primero del campo oculto por si fue modificado)
    // Guardamos el monto base original en `window.podMontoBase` para poder
    // restaurarlo cuando se quite la opción POS.
    window.podMontoBase = parseFloat($('#hidden-customer-payment').val()) || <?php echo floatval($monto_display); ?>;
    let montoTotal = window.podMontoBase;
    
    // Solo si NO ES "NO COBRAR"
    if($('#add-method').length){

        // Estado del botón Actualizar según validación
        function updateSubmitState() {
            var updateBtn = $('.delivered-btn[name="submit"]');
            if (!updateBtn.length) return;

            var totalIngresado = parseFloat($('#total-ingresado').text()) || 0;
            // El monto esperado es el que se muestra al motorizado (que puede incluir comisión de POS)
            var montoDisplayVal = parseFloat($('#monto_display_input').val());
            var montoEsperado = (!isNaN(montoDisplayVal) && montoDisplayVal > 0) ? montoDisplayVal : parseFloat(montoTotal) || 0;

            // Comparación con tolerancia
            var diff = Math.abs(totalIngresado - montoEsperado);
            if (montoEsperado > 0 && diff > 0.01) {
                updateBtn.prop('disabled', true).css('opacity', '0.5').attr('title', 'Completa el total a cobrar antes de actualizar');
            } else {
                updateBtn.prop('disabled', false).css('opacity', '1').attr('title', 'Actualizar envío');
            }
        }

        // Aplicar visual del Total a recibir según si existe POS seleccionado
        function applyPOSDisplay() {
            console.log('🔍 applyPOSDisplay() ejecutado');
            
            // Detectar POS de forma robusta
            let anyPOS = false;
            $('.fila-metodo').each(function() {
                let $fila = $(this);
                let val = $fila.find('.pay-method').val();
                let btnText = $fila.find('.seleccionar-metodo').text() || '';
                console.log('  Checking fila: metodo_val=' + val + ' btnText=' + btnText);
                if ( (val && val.toString().toLowerCase() === 'pos') || btnText.toString().toLowerCase().indexOf('pos') !== -1 ) {
                    anyPOS = true;
                    console.log('  ✓ POS detectado en esta fila');
                    return false;
                }
            });

            let montoBase = window.podMontoBase;
            console.log('🔍 anyPOS=' + anyPOS + ' montoBase=' + montoBase);
            
            if (anyPOS) {
                // Calcular lo que ya se ingresó en otros métodos
                let montoOtros = 0;
                $('.fila-metodo').each(function() {
                    let $fila = $(this);
                    let metodo = $fila.find('.pay-method').val();
                    let monto = parseFloat($fila.find('.pay-amount').val()) || 0;
                    
                    // Si no es POS, sumarlo
                    if (metodo && metodo.toLowerCase() !== 'pos') {
                        montoOtros += monto;
                        console.log('  Método ' + metodo + ': ' + monto);
                    }
                });
                
                // Calcular lo que falta por cobrar
                let faltante = montoBase - montoOtros;
                faltante = Math.max(0, faltante);
                
                // No aplicar comisión visual: el POS debe ingresar el monto tal cual (sin 5%)
                let montoPOSConComision = Number((faltante).toFixed(2));

                // El nuevo monto total a mostrar es: otros + faltante (sin comisión)
                let nuevoMontoTotal = Number((montoOtros + montoPOSConComision).toFixed(2));
                
                console.log('  montoOtros=' + montoOtros + ' faltante=' + faltante + ' montoPOSConComision=' + montoPOSConComision + ' nuevoMontoTotal=' + nuevoMontoTotal);
                
                // Mostrar el nuevo monto total en el input readonly
                $('#monto_display_input').val(nuevoMontoTotal.toFixed(2));
                window.podMontoTotal = nuevoMontoTotal;
                console.log('  ✓ Actualizado monto_display_input a ' + nuevoMontoTotal.toFixed(2));
            } else {
                // Quitar efecto POS: volver al monto base
                $('#monto_display_input').val(Number(window.podMontoBase).toFixed(2));
                window.podMontoTotal = Number(window.podMontoBase);
                console.log('  ✓ Sin POS, restaurando a ' + window.podMontoBase.toFixed(2));
            }
            // Actualizar estado del botón al cambiar la visual del total
            updateSubmitState();
        }
    
        function recalcular(){
            let total = 0;
            let arr = [];
            let promesas = [];
            let tienePOS = false;
    
            $('.fila-metodo').each(function(){
                let $fila = $(this);
                let metodo = $fila.find('.pay-method').val();
                let montoStr = $fila.find('.pay-amount').val().trim();
                // 🔧 Parsear string a float, permitiendo decimales
                let monto = parseFloat(montoStr) || 0;
                let fileInput = $fila.find('.pay-image')[0];
    
                if(metodo){
                    // Guardar el monto tal cual, sin modificaciones
                    // El servidor se encargará de aplicar comisiones si es necesario
                    let montoFinal = monto;
                    
                    if (fileInput && fileInput.files.length > 0) {
                        // Comprimir imagen antes de convertir a base64
                        let promesa = new Promise((resolve) => {
                            compressImage(fileInput.files[0], function(compressedBase64) {
                                arr.push({ 
                                    metodo: metodo, 
                                    monto: montoFinal,
                                    imagen: compressedBase64,
                                    imagen_nombre: fileInput.files[0].name
                                });
                                resolve();
                            });
                        });
                        promesas.push(promesa);
                    } else {
                        arr.push({ metodo: metodo, monto: montoFinal });
                    }
                }
    
                if(!isNaN(monto)){
                    total += monto;
                }
            });
    
            // Esperar a que todas las imágenes se compriman
            Promise.all(promesas).then(() => {
                $('#total-ingresado').text(total.toFixed(2));

                // El monto esperado es lo mostrado en "Total a recibir" 
                // (que incluye el 5% de POS si está seleccionado)
                let montoDisplayVal = parseFloat($('#monto_display_input').val());
                let montoEsperado = (!isNaN(montoDisplayVal) && montoDisplayVal > 0) ? montoDisplayVal : montoTotal;

                let diff = Math.abs(total - montoEsperado);
                if(total > montoEsperado){
                    $('#payment-error').text("El total excede lo que se debe cobrar (S/. " + montoEsperado.toFixed(2) + ").").show();
                }
                else if(total < montoEsperado - 0.01){
                    $('#payment-error').text("Falta completar el total a cobrar (faltan S/. " + (montoEsperado - total).toFixed(2) + ").").show();
                }
                else{
                    $('#payment-error').hide();
                }

                $('#pod_payment_methods').val(JSON.stringify(arr));
                // Actualizar estado del botón cuando cambie el total ingresado
                updateSubmitState();
            });
        }
    
        $('#add-method').on('click', function(){
            let fila = `
                <div class="fila-metodo" style="border:1px solid #ccc;padding:10px;margin-bottom:10px;border-radius:5px;">
                    <label><strong>Método</strong></label>
                    <div class="method-selector" style="margin-bottom:8px;">
                        <button type="button" class="btn btn-light dropdown-toggle seleccionar-metodo" 
                                style="width:100%;text-align:left;">
                            Seleccionar método
                        </button>
                        <div class="method-options" 
                             style="display:none;border:1px solid #ddd;border-radius:4px;background:white;position:absolute;z-index:1000;width:calc(100% - 20px);">
                            <div class="method-option" data-value="efectivo" style="padding:8px;cursor:pointer;">Pago a motorizado</div>
                            <div class="method-option" data-value="pago_marca" style="padding:8px;cursor:pointer;">Pago a Marca</div>
                            <div class="method-option" data-value="pago_merc" style="padding:8px;cursor:pointer;">Pago a MERC</div>
                            <div class="method-option" data-value="pos" style="padding:8px;cursor:pointer;">POS</div>
                        </div>
                    </div>
                    <input type="hidden" class="pay-method" required>
                    
                    <label><strong>Monto</strong></label>
                    <input type="text" class="pay-amount form-control" inputmode="decimal" placeholder="0.00" pattern="[0-9]+(\.[0-9]{1,2})?" style="margin-bottom:10px;">
                    
                    <label><strong>Imagen del comprobante *</strong></label>
                    <input type="file" class="pay-image form-control" accept="image/*" required style="margin-bottom:8px;">
                    <div class="image-preview-container"></div>
                    
                    <button type="button" class="btn btn-danger btn-sm remove-metodo" style="margin-top:8px;">Eliminar</button>
                </div>
            `;
            $('#payment-methods-list').append(fila);

            // Al añadir una fila, recalculamos inmediatamente para que el total
            // y el JSON se actualicen. Esto evita que solo se registre la primera fila.
            applyPOSDisplay(); // Recalcular visual del POS si se añadió
            recalcular();
        });


    
        // Delegación para eliminar
        $(document).on('click', '.remove-metodo', function(){
            $(this).closest('.fila-metodo').remove();
            applyPOSDisplay(); // Recalcular visual del POS si se elimina
            recalcular();
        });
    
    // Delegación para recalcular y reapliar POS
    $(document).on('input change', '.pay-method, .pay-amount', function() {
        // Al cambiar monto (tipo text), validar que sea un decimal válido
        if ($(this).hasClass('pay-amount')) {
            let valor = $(this).val().trim();
            // Permitir solo números y un punto decimal
            valor = valor.replace(/[^0-9.]/g, '');
            // Asegurar solo un punto
            let partes = valor.split('.');
            if (partes.length > 2) {
                valor = partes[0] + '.' + partes.slice(1).join('');
            }
            // Limitar a 2 decimales
            if (partes.length === 2 && partes[1].length > 2) {
                valor = partes[0] + '.' + partes[1].substring(0, 2);
            }
            $(this).val(valor);
        }
        
        applyPOSDisplay(); // Primero recalcular visual del POS si cambió
        recalcular();      // Luego recalcular totales
    });
        
        // Delegación para vista previa de imagen
        $(document).on('change', '.pay-image', function(e) {
            let file = e.target.files[0];
            let $container = $(this).siblings('.image-preview-container');
            
            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $container.html(`
                        <img src="${e.target.result}" style="max-width:150px;max-height:150px;margin-top:8px;border-radius:4px;border:1px solid #ddd;">
                        <br>
                        <button type="button" class="btn-remove-image btn btn-sm btn-danger" style="margin-top:5px;">Eliminar imagen</button>
                    `);
                };
                reader.readAsDataURL(file);
            }
            
            // Recalcular para incluir la imagen en el JSON
            setTimeout(() => {
                applyPOSDisplay();
                recalcular();
            }, 100);
        });
        
        // Delegación para remover imagen
        $(document).on('click', '.btn-remove-image', function() {
            let $fila = $(this).closest('.fila-metodo');
            $fila.find('.pay-image').val('');
            $(this).parent().html('');
            applyPOSDisplay();
            recalcular();
        });

        // Ejecutar una recalculación inicial para cargar los métodos
        // preexistentes al cargar la interfaz de firma.
        recalcular();
        // Ajustar visual del Total si ya existe POS guardado
        applyPOSDisplay();
        // Inicializar estado del botón
        updateSubmitState();
        
        // Asegurar que recalcular() se ejecute antes de enviar el formulario
        $('#wpc_pod_signature-form').on('submit', function(e) {
            // REMOVIDO: sendDebug para evitar múltiples AJAX
            e.preventDefault(); // Prevenir envío inmediato
            
            let formElement = this; // Guardar referencia al formulario
            
            // Recalcular y esperar a que termine (las promesas de las imágenes comprimidas)
            let arr = [];
            let total = 0;
            let promesas = [];
            
            $('.fila-metodo').each(function(){
                let $fila = $(this);
                let metodo = $fila.find('.pay-method').val();
                let monto  = parseFloat($fila.find('.pay-amount').val()) || 0;
                let fileInput = $fila.find('.pay-image')[0];
    
                if(metodo){
                    // Guardar el monto tal cual, sin modificaciones
                    let montoFinal = monto;
                    
                    if (fileInput && fileInput.files.length > 0) {
                        // Comprimir imagen antes de convertir a base64
                        let promesa = new Promise((resolve) => {
                            compressImage(fileInput.files[0], function(compressedBase64) {
                                arr.push({ 
                                    metodo: metodo, 
                                    monto: montoFinal,
                                    imagen: compressedBase64,
                                    imagen_nombre: fileInput.files[0].name
                                });
                                resolve();
                            });
                        });
                        promesas.push(promesa);
                    } else {
                        arr.push({ metodo: metodo, monto: montoFinal });
                    }
                }
    
                if(!isNaN(monto)){
                    total += monto;
                }
            });
            
            Promise.all(promesas).then(() => {
                // Guardar el JSON con los métodos de pago tal cual
                // (el servidor aplicará la comisión del POS si es necesario)
                $('#pod_payment_methods').val(JSON.stringify(arr));
                
                // Validar nuevamente antes de enviar contra el monto mostrado en "Total a recibir"
                // (que incluye el 5% de POS si está seleccionado)
                let totalIngresado = 0;
                $('.fila-metodo').each(function(){
                    let monto = parseFloat($(this).find('.pay-amount').val()) || 0;
                    totalIngresado += monto;
                });
                
                // El monto esperado es lo mostrado en "Total a recibir"
                let montoDisplayVal = parseFloat($('#monto_display_input').val());
                let montoEsperado = (!isNaN(montoDisplayVal) && montoDisplayVal > 0) ? montoDisplayVal : montoTotal;
                
                let diff = Math.abs(totalIngresado - montoEsperado);
                if (diff > 0.01) {
                    alert('El total ingresado (S/. ' + totalIngresado.toFixed(2) + ') no coincide con el total esperado (S/. ' + montoEsperado.toFixed(2) + '). Corrige los montos antes de actualizar.');
                    updateSubmitState();
                    return;
                }

                // Escribir el total autoritativo en el campo oculto para enviarlo al servidor
                try{
                    var montoFinalStr = Number(montoEsperado).toFixed(2);
                    if (typeof jQuery !== 'undefined' && jQuery('#hidden-wpcargo-total').length) {
                        jQuery('#hidden-wpcargo-total').val(montoFinalStr);
                    } else {
                        var el = document.getElementById('hidden-wpcargo-total');
                        if(el) el.value = montoFinalStr;
                    }
                }catch(e){}

                // Usar el método submit del prototipo para evitar conflicto con el botón name="submit"
                HTMLFormElement.prototype.submit.call(formElement);
            });
        });
    }
    window.podPaymentModes = <?php echo json_encode(get_option('wpcargo_payment_modes', [])); ?>;
    window.podMontoTotal   = <?php echo floatval($monto_display); ?>;
    // Abrir / cerrar dropdown
    $(document).on('click', '.seleccionar-metodo', function(){
        $(this).siblings('.method-options').toggle();
    });
    // ---------------- EXCEPCIÓN: PAGO A MOTORIZADO SIN IMAGEN ----------------
    $(document).on('change input', '.pay-method', function () {
        let $fila   = $(this).closest('.fila-metodo');
        let metodo  = ($(this).val() || '').toLowerCase();
        let $file   = $fila.find('.pay-image');
        let $label  = $fila.find('label:contains("Imagen del comprobante")');
        let $preview = $fila.find('.image-preview-container');
    
        if (metodo === 'efectivo') {
            // Pago a motorizado → NO requiere imagen
            $file.prop('required', false).hide().val('');
            $preview.empty();
    
            $label.html('<strong>Imagen del comprobante</strong>');
    
            if (!$fila.find('.nota-efectivo').length) {
                $file.after('<small class="nota-efectivo text-muted">No requiere comprobante</small>');
            }
        } else if (metodo) {
            // Otros métodos → imagen obligatoria
            $file.prop('required', true).show();
            $fila.find('.nota-efectivo').remove();
    
            if ($label.find('*').length === 0) {
                $label.html('<strong>Imagen del comprobante *</strong>');
            }
        }
    });

    // Seleccionar método
    $(document).on('click', '.method-option', function(){
        let metodo = $(this).data('value');
        let texto = $(this).text();
    
        let contenedor = $(this).closest('.fila-metodo');
    
        // Mostrar nombre en botón
        contenedor.find('.seleccionar-metodo').text(texto);
    
        // Guardar valor real
        contenedor.find('.pay-method').val(metodo).trigger('change');
        // Limpiar el input de monto al cambiar de método para evitar valores heredados
        contenedor.find('.pay-amount').val('');
        // Limpiar vista previa de imagen si existe (opcional)
        contenedor.find('.image-preview-container').html('');
        contenedor.find('.pay-image').val('');
    
        // Cerrar menú
        $(this).parent().hide();
    
        // Aplicar visual de POS (si corresponde) y luego recalcular totales
        applyPOSDisplay();
        recalcular();
    });
    
    // Cerrar dropdowns si se hace clic afuera
    $(document).click(function(e){
        if(!$(e.target).closest('.method-selector').length){
            $('.method-options').hide();
        }
    });
</script>