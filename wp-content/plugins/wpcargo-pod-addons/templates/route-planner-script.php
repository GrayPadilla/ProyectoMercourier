<script>
    // Variable global para almacenar datos de shipments
    let shipmentsWhatsAppData = {};
    
    // Variable global para almacenar las entregas
    let deliveries = [];
    
    // Variable global para almacenar estados disponibles
    let availableStatuses = [];
    
    // Función para obtener la fecha de hoy en formato Y-m-d
    function getTodayDate() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${day}/${month}/${year}`; // ✅ Formato d-m-Y
    }

    // Normalizar varias representaciones de fecha a 'dd/mm/YYYY'
    function normalizeToDMY(dateStr) {
        if (!dateStr) return '';
        dateStr = String(dateStr).trim();
        dateStr = dateStr.replace(/-/g, '/');

        // YYYY/MM/DD -> DD/MM/YYYY
        var m = dateStr.match(/^\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\s*$/);
        if (m) {
            var y = m[1], mo = String(m[2]).padStart(2,'0'), d = String(m[3]).padStart(2,'0');
            return d + '/' + mo + '/' + y;
        }

        // D/M/YYYY or DD/MM/YYYY -> pad with zeros
        m = dateStr.match(/^\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\s*$/);
        if (m) {
            var d2 = String(m[1]).padStart(2,'0'), mo2 = String(m[2]).padStart(2,'0'), y2 = m[3];
            return d2 + '/' + mo2 + '/' + y2;
        }

        return dateStr;
    }
    
    // Función para obtener datos de un shipment específico
    async function getShipmentData(postId) {
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                type: "POST",
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                data: {
                    action: 'wpcpod_get_single_shipment_data',
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    reject('Error de conexión: ' + error);
                }
            });
        });
    }
    
    // Función para extraer coordenadas de un enlace de Google Maps
    function extractCoordinatesFromUrl(url) {
        if (!url) return null;
        
        const patterns = [
            /@(-?\d+\.?\d*),(-?\d+\.?\d*)/,
            /q=(-?\d+\.?\d*),(-?\d+\.?\d*)/,
            /ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/,
            /query=(-?\d+\.?\d*),(-?\d+\.?\d*)/
        ];
        
        for (let pattern of patterns) {
            const match = url.match(pattern);
            if (match) {
                return {
                    lat: parseFloat(match[1]),
                    lng: parseFloat(match[2])
                };
            }
        }
        return null;
    }
    
    // Función para limpiar y formatear teléfono
    function formatPhoneNumber(phone) {
        if (!phone) return '';
        
        let cleaned = phone.replace(/[^0-9]/g, '');
        
        if (cleaned && !cleaned.startsWith('51')) {
            cleaned = '51' + cleaned;
        }
        
        return cleaned;
    }
    
    // Función para generar enlaces de navegación
    function generateNavigationLinks(shipmentNumber) {
        return `
            <div class="shipment-actions" style="margin-top: 8px;">
                <button onclick="toggleActions('actions-${shipmentNumber}')" 
                   type="button"
                   style="display: block; background-color: #6c757d; color: white; 
                          padding: 8px 12px; border: none; border-radius: 4px; 
                          font-size: 12px; cursor: pointer; width: 100%; margin-bottom: 8px;">
                    ⚡ Mostrar Acciones
                </button>
                <div id="actions-${shipmentNumber}" style="display: none; border-top: 1px solid #e0e0e0; padding-top: 8px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <button onclick="openGoogleMaps('${shipmentNumber}')" 
                           type="button"
                           style="display: block; background-color: #4285f4; color: white; 
                                  padding: 8px 12px; border: none; border-radius: 4px; 
                                  font-size: 12px; text-align: center; font-weight: 500; 
                                  cursor: pointer; width: 100%;">
                            📍 Ver ubicación en Google Maps
                        </button>
                        
                        <button onclick="sendWhatsAppMessage('${shipmentNumber}')" 
                           type="button"
                           style="display: block; background-color: #25D366; color: white; 
                                  padding: 8px 12px; border: none; border-radius: 4px; 
                                  font-size: 12px; text-align: center; font-weight: 500; 
                                  cursor: pointer; width: 100%;">
                            💬 Contactar por WhatsApp
                        </button>
                        
                        <button onclick="sendSupportMessage('${shipmentNumber}')" 
                           type="button"
                           style="display: block; background-color: #dc3545; color: white; 
                                  padding: 8px 12px; border: none; border-radius: 4px; 
                                  font-size: 12px; text-align: center; font-weight: 500; 
                                  cursor: pointer; width: 100%;">
                            🆘 Solicitar soporte a Marca
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Función para abrir Google Maps con el link correcto
    async function openGoogleMaps(shipmentNumber) {
        try {
            const delivery = deliveries.find(d => d.number === shipmentNumber);
            
            if (!delivery || !delivery.id) {
                alert('❌ No se encontró el pedido.');
                return;
            }
            
            // Obtener datos del shipment para conseguir link_maps
            const data = await getShipmentData(delivery.id);
            
            if (data.link_maps && data.link_maps.trim() !== '') {
                window.open(data.link_maps, '_blank');
            } else {
                alert('❌ No hay link de Google Maps disponible para este pedido.');
            }
            
        } catch (error) {
            alert('❌ Error al obtener el link de Google Maps');
        }
    }
    
    // Función para alternar visibilidad de acciones
    function toggleActions(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    // Función para solicitar soporte a la marca
    async function sendSupportMessage(shipmentNumber) {
        jQuery('body').append('<div id="whatsapp-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div style="background: white; padding: 20px; border-radius: 8px; text-align: center;"><div style="font-size: 18px; margin-bottom: 10px;">⏳ Obteniendo datos...</div></div></div>');
        
        try {
            const delivery = deliveries.find(d => d.number === shipmentNumber);
            
            if (!delivery || !delivery.id) {
                jQuery('#whatsapp-loader').remove();
                alert('❌ No se encontró el ID del pedido.');
                return;
            }
            
            const data = await getShipmentData(delivery.id);
            
            jQuery('#whatsapp-loader').remove();
            
            // ✅ CORRECCIÓN: Usar shipper_phone en lugar de brand_phone
            const shipperPhone = formatPhoneNumber(data.shipper_phone);
            
            if (!shipperPhone) {
                alert('❌ No se encontró el teléfono de la marca para este pedido.');
                return;
            }
            
            let mensaje = `¡Hola! Te saluda👋🏼 *${data.motorizado_name}* motorizado🏍️ de MERCourier, tengo una entrega🎁 de tu clienta:\n\n`;
            mensaje += `📦 *Datos de la entrega:*\n`;
            mensaje += `- Número de pedido: *${shipmentNumber}*\n`;
            mensaje += `- Destinatario: *${data.receiver_name || 'No especificado'}*\n`;
            mensaje += `- Dirección: *${data.receiver_address}*\n`;
            mensaje += `- Teléfono: *${formatPhoneNumber(data.receiver_phone)}*\n`;
            if (data.monto > 0) {
                mensaje += `- Monto: *S/. ${data.monto.toFixed(2)}*\n`;
            }
            mensaje += `\nNo me contesta 📞 me podría apoyar con la comunicación🗣️, para que su pedido🛂 se entregado💪🏻 correctamente.\n\n`;
            mensaje += `¡Gracias! Por su apoyo, quedo atento a su respuesta. 😊🤝`;
            
            const mensajeCodificado = encodeURIComponent(mensaje);
            const whatsappUrl = `https://wa.me/${shipperPhone}?text=${mensajeCodificado}`;
            
            window.open(whatsappUrl, '_blank');
            
        } catch (error) {
            jQuery('#whatsapp-loader').remove();
            alert('❌ Error: ' + error);
        }
    }
    
    // Función para enviar mensaje de WhatsApp al cliente
    async function sendWhatsAppMessage(shipmentNumber) {
        // Mostrar loader
        jQuery('body').append('<div id="whatsapp-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div style="background: white; padding: 20px; border-radius: 8px; text-align: center;"><div style="font-size: 18px; margin-bottom: 10px;">⏳ Obteniendo datos...</div></div></div>');
        
        try {
            // Buscar el post_id del shipment en el array global
            const delivery = deliveries.find(d => d.number === shipmentNumber);
            
            if (!delivery || !delivery.id) {
                jQuery('#whatsapp-loader').remove();
                alert('❌ No se encontró el ID del pedido.');
                return;
            }
            
            // Obtener datos del shipment usando el post_id
            const data = await getShipmentData(delivery.id);
            
            jQuery('#whatsapp-loader').remove();
            
            const receiverPhone = formatPhoneNumber(data.receiver_phone);
            
            if (!receiverPhone) {
                alert('❌ No se encontró el teléfono del cliente para este pedido.');
                return;
            }
            
            let mensaje = `¡Hola! Te saluda👋🏼 *${data.motorizado_name}* motorizado🏍️ de MERCourier, tengo una entrega🎁 para ud de parte de la marca *${data.tienda_name}*, me podría confirmar la recepción de su pedido🛂 en la *${data.receiver_address}*.\n\n`;
            mensaje += `⏳ Te notificaremos de 10 a 15 min antes de llegar. El horario de reparto es de 2:30 a 7:30 pm\n\n`;
            
            if (data.monto > 0) {
                mensaje += `💰 Tipo de Pago: YAPE o efectivo (monto exacto S/. ${data.monto.toFixed(2)}).\n\n`;
            } else {
                mensaje += `💰 Tipo de Pago: YAPE o efectivo (monto exacto). Si no tiene ningún cobro omita el aviso 🤓\n\n`;
            }
            
            mensaje += `¡Gracias! Por tu atención`;
            
            const mensajeCodificado = encodeURIComponent(mensaje);
            const whatsappUrl = `https://wa.me/${receiverPhone}?text=${mensajeCodificado}`;
            
            window.open(whatsappUrl, '_blank');
            
        } catch (error) {
            jQuery('#whatsapp-loader').remove();
            alert('❌ Error: ' + error);
        }
    }
    
    // Función para calcular distancia entre dos puntos (fórmula de Haversine)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    // Función para obtener estados permitidos según el estado actual
    function getPermittedStatuses(currentStatus, allStatuses) {
        const estadosMotorizadoInicial = ['PENDIENTE', 'RECOGIDO', 'NO RECOGIDO'];
        const estadosMotorizadoDespuesBase = ['EN RUTA', 'NO CONTESTA', 'NO RECIBIDO', 'ENTREGADO', 'REPROGRAMADO', 'ANULADO'];
        const estadosAvanzados = ['EN BASE MERCOURIER', 'RECEPCIONADO', 'LISTO PARA SALIR', 'NO CONTESTA', 'EN RUTA', 'NO RECIBIDO', 'ENTREGADO', 'REPROGRAMADO', 'ANULADO'];
        
        if (!allStatuses) {
            return [];
        }
        const currentStatusUpper = (currentStatus || '').toUpperCase();
        let permitidos = [];
        
        // Detectar si el estado actual es avanzado
        const esEstadoAvanzado = estadosAvanzados.some(function(estado) {
            return currentStatusUpper.includes(estado);
        });
        
        if (esEstadoAvanzado) {
            // Si es avanzado, mostrar estados posteriores
            permitidos = allStatuses.filter(function(status) {
                const statusUpper = status.toUpperCase();
                return estadosMotorizadoDespuesBase.some(function(permitido) {
                    return statusUpper.includes(permitido) || permitido.includes(statusUpper);
                });
            });
        } else {
            // Si no es avanzado (es inicial), mostrar estados iniciales
            permitidos = allStatuses.filter(function(status) {
                const statusUpper = status.toUpperCase();
                return estadosMotorizadoInicial.some(function(permitido) {
                    return statusUpper.includes(permitido) || permitido.includes(statusUpper);
                });
            });
        }
        
        // Filtrar "LISTO PARA SALIR" si existe
        permitidos = permitidos.filter(function(opt) {
            return opt.toUpperCase().trim() !== 'LISTO PARA SALIR';
        });
        
        return permitidos;
    }
    
    // Función para actualizar estado de una entrega
    async function updateDeliveryStatus(shipmentId, newStatus) {
        // 🔥 Obtener el nonce del elemento oculto
        const nonceField = document.getElementById('wpcpod-nonce-field');
        const nonce = nonceField ? nonceField.value : null;
        
        if (!nonce) {
            alert('❌ Error: No se pudo obtener el token de seguridad. Recarga la página');
            console.error('Nonce no encontrado en el elemento HTML');
            return;
        }
        
        console.log('✅ Nonce obtenido:', nonce);
        
        // Si el estado es ENTREGADO, mostrar modal de firma existente
        if (newStatus === 'ENTREGADO') {
            showSignatureModalForStatus(shipmentId, newStatus, nonce);
        } else {
            // Para otros estados, mostrar confirmación directa
            showStatusConfirmation(shipmentId, newStatus, nonce);
        }
    }
    
    // Función para mostrar modal de firma reutilizando el modal existente
    function showSignatureModalForStatus(shipmentId, newStatus, nonce) {
        console.log('🔥 showSignatureModalForStatus llamado con:', { shipmentId, newStatus, nonce });
        
        try {
            // Verificar que el modal existe en el DOM
            const $modal = jQuery('#wpc_pod_signature-modal');
            console.log('🔍 Buscando modal #wpc_pod_signature-modal...');
            console.log('✓ Modal encontrado en DOM:', $modal.length > 0);
            
            if ($modal.length === 0) {
                console.error('❌ ERROR: Modal #wpc_pod_signature-modal no existe en el DOM');
                alert('Error: Modal de firma no encontrado en la página');
                return;
            }
            
            // Cargar el formulario de firma en el modal existente
            jQuery.ajax({
                type: "POST",
                data: {
                    action: 'show_signaturepad',
                    sid: shipmentId
                },
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                beforeSend: function() {
                    console.log('📡 Enviando AJAX para cargar formulario...');
                    jQuery('body').append('<div class="wpcargo-loading">Cargando formulario de firma...</div>');
                },
                success: function(response) {
                    console.log('✅ Respuesta AJAX recibida (longitud:', response.length, 'caracteres)');
                    
                    try {
                        jQuery('body .wpcargo-loading').remove();
                        
                        // Cargar el contenido en el modal
                        const $modalBody = jQuery('#wpc_pod_signature-modal .modal-body');
                        console.log('📝 Modal body encontrado:', $modalBody.length > 0);
                        
                        $modalBody.html(response);
                        console.log('✅ Contenido cargado en .modal-body');
                        
                        // Verificar que jQuery y Bootstrap estén disponibles
                        console.log('✓ jQuery versión:', typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'NO DISPONIBLE');
                        console.log('✓ Bootstrap modal disponible:', typeof jQuery('#wpc_pod_signature-modal').modal === 'function');
                        
                        console.log('📡 Intentando mostrar modal con .modal("show")...');
                        jQuery('#wpc_pod_signature-modal').modal('show');
                        console.log('✅ .modal("show") ejecutado sin errores');
                        
                        // Esperar a que el modal esté completamente visible
                        setTimeout(function() {
                            console.log('✅ Modal debería estar visible ahora');
                            console.log('🔍 Estado actual del modal:', jQuery('#wpc_pod_signature-modal').hasClass('show') ? 'VISIBLE' : 'OCULTO');
                        }, 500);
                        
                        // Interceptar el submit del formulario
                        jQuery('#wpc_pod_signature-modal #wpc_pod_signature-form').off('submit').on('submit', function(e) {
                            e.preventDefault();
                            console.log('📝 Submit del formulario interceptado');
                            
                            // Obtener datos del formulario
                            const formData = jQuery(this).serializeArray();
                            console.log('📝 Datos del formulario de firma:', formData);
                            
                            // Cambiar el estado
                            sendStatusUpdate(shipmentId, newStatus, nonce, formData);
                            
                            // Cerrar modal
                            jQuery('#wpc_pod_signature-modal').modal('hide');
                        });
                        console.log('✅ Event handler de submit configurado');
                        
                    } catch(e) {
                        console.error('❌ Error al procesar respuesta AJAX:', e.message);
                        console.error('📋 Stack trace:', e.stack);
                        alert('Error al mostrar modal: ' + e.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error AJAX:', { status, error, xhr });
                    console.error('📋 Respuesta del servidor:', xhr.responseText);
                    jQuery('body .wpcargo-loading').remove();
                    alert('❌ Error al cargar el formulario de firma: ' + error);
                }
            });
        } catch(e) {
            console.error('❌ Error general en showSignatureModalForStatus:', e.message);
            console.error('📋 Stack trace:', e.stack);
        }
    }
    
    // Función para mostrar confirmación de estado (sin firma)
    function showStatusConfirmation(shipmentId, newStatus, nonce) {
        Swal.fire({
            title: '⚠️ Confirmar cambio de estado',
            text: `¿Deseas cambiar el estado a "${newStatus}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3498db',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then(async (result) => {
            if (result.isConfirmed) {
                await sendStatusUpdate(shipmentId, newStatus, nonce);
            }
        });
    }
    
    // Función para enviar actualización de estado
    async function sendStatusUpdate(shipmentId, newStatus, nonce, formData = null) {
        try {
            const data = {
                action: 'wpcpod_update_delivery_status',
                shipment_id: shipmentId,
                status: newStatus,
                nonce: nonce
            };
            
            // Si hay datos del formulario de firma, convertirlos a objeto
            if (formData && Array.isArray(formData)) {
                formData.forEach(field => {
                    // Buscar el campo __pod_signature
                    if (field.name === '__pod_signature') {
                        data.signature = field.value;
                    }
                });
            }
            
            const response = await jQuery.ajax({
                type: "POST",
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                data: data
            });
            
            if (response.success) {
                Swal.fire({
                    title: '✅ Éxito',
                    text: response.data.message,
                    icon: 'success',
                    confirmButtonColor: '#3498db',
                    timer: 2000
                });
            } else {
                Swal.fire({
                    title: '❌ Error',
                    text: response.data.message || 'Error al actualizar el estado',
                    icon: 'error',
                    confirmButtonColor: '#e74c3c'
                });
            }
        } catch (error) {
            console.error('Error AJAX:', error);
            Swal.fire({
                title: '❌ Error de conexión',
                text: 'No se pudo actualizar el estado. ' + (error.statusText || error.responseText || 'Error desconocido'),
                icon: 'error',
                confirmButtonColor: '#e74c3c'
            });
        }
    }
    
    
    function initPODRouteMap() {
        $('#wpcpod-route-planner #wpcpod-route-map').hide();
        
        // Obtener fecha de hoy
        const today = getTodayDate();
        
        // Obtener estados disponibles
        jQuery.ajax({
            type: "POST",
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            data: {
                action: 'wpcpod_get_all_possible_statuses'
            },
            success: function(response) {
                if (response.success) {
                    availableStatuses = response.data;
                }
            }
        });
        
        jQuery.ajax({
            type:"POST",
            data:{
                action  : 'wpcpod_generate_route_address',
                filter_date: today  // 🔥 NUEVO: Enviar fecha al backend
            },
            url : "<?php echo admin_url( 'admin-ajax.php' ); ?>",
            beforeSend:function(){
            },
            success:function(response){
                if( response.status == 'success'){
                    displayShipmentsList(response.origin, response.waypoints, response.shipments, response.poo);
                }else{
                    $('#wpcpod-route-planner #wpcpod-route-map').remove();
                    $('#wpcpod-route-planner #wpcpod-route-loader').remove();
                    $('#wpcpod-route-planner #route-planner-content').append('<div class="my-4 alert alert-info text-center">'+response.message+'</div>')
                }              
            }
        });
    }
    
    function displayShipmentsList(origin, waypoints, shipments, poo) {
        const summaryPanel = document.getElementById("directions-panel");
        
        // Reiniciar el array global
        deliveries = [];
        
        // Obtener fecha de hoy para filtro adicional
        const today = getTodayDate();
        
        // IMPORTANTE: Procesar TODOS los shipments, no solo los waypoints
        if (shipments && shipments.length > 0) {
            shipments.forEach((shipment, index) => {
                // 🔥 NUEVO: Filtrar por fecha de envío (validación adicional en cliente)
                const shipmentDateRaw = shipment['pickup_date'] || shipment['shipping_date'] || '';
                const shipmentDate = normalizeToDMY(shipmentDateRaw);

                // Si el shipment tiene fecha y NO coincide (tras normalizar) con hoy, saltarlo
                if (shipmentDate && shipmentDate !== today) {
                    return; // Continuar con el siguiente
                }
                
                let address = shipment['address'] || 'Dirección no disponible';
                let linkMaps = shipment['link_maps'] || shipment['address'] || '';
                let lat = shipment['lat'] || null;
                let lng = shipment['lng'] || null;
                
                if ((!lat || !lng) && address) {
                    const coords = extractCoordinatesFromUrl(address);
                    if (coords) {
                        lat = coords.lat;
                        lng = coords.lng;
                    }
                }
                
                let distance = 0;
                if (origin && origin.lat && origin.lng && lat && lng) {
                    distance = calculateDistance(origin.lat, origin.lng, lat, lng);
                }
                
                deliveries.push({
                    id: shipment['id'] || null,
                    number: shipment['number'] || 'N/A',
                    address: address,
                    link_maps: linkMaps,
                    info: shipment['info'] || {},
                    lat: lat,
                    lng: lng,
                    distance: distance,
                    pickup_date: shipmentDate, // Guardar fecha para referencia
                    status: shipment['status'] || 'N/A'
                });
            });
        }
        
        // Ordenar por distancia
        deliveries.sort((a, b) => a.distance - b.distance);
        
        let listHTML = '<div style="font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto;">';
        listHTML += `<h3 style="margin-bottom: 20px; color: #333; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">📋 Lista de Entregas - ${today}</h3>`;
        
        if (deliveries.length === 0) {
            listHTML += '<div class="alert alert-warning" style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; text-align: center;">⚠️ No se encontraron pedidos para entregar hoy</div>';
        } else {
            deliveries.forEach((delivery, index) => {
                // Obtener los estados permitidos según el estado actual del delivery
                const permittedStatuses = getPermittedStatuses(delivery.status, availableStatuses);
                
                // Generar opciones del select: incluir SIEMPRE el estado actual + los permitidos
                let statusOptions = '';
                
                // Agregar el estado actual como opción (puede no estar en permitidos)
                if (delivery.status) {
                    statusOptions += `<option value="${delivery.status}" selected>${delivery.status}</option>`;
                }
                
                // Agregar los estados permitidos (si no son el estado actual)
                if (permittedStatuses && permittedStatuses.length > 0) {
                    permittedStatuses.forEach(status => {
                        // No duplicar el estado actual
                        if (status !== delivery.status) {
                            statusOptions += `<option value="${status}">${status}</option>`;
                        }
                    });
                }
                
                listHTML += `
                    <div style="border: 2px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef;">
                            <span style="font-weight: bold; font-size: 18px; color: #007bff;">
                                ${index + 1}. Pedido: ${delivery.number}
                            </span>
                            <select onchange="updateDeliveryStatus(${delivery.id}, this.value)" 
                               style="background: #fff; color: #333; padding: 6px 10px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; min-width: 120px;">
                                ${statusOptions}
                            </select>
                        </div>
                        <div style="font-size: 14px; color: #555; margin-bottom: 8px;">
                            <strong>📍 Dirección:</strong> ${delivery.address}
                        </div>
                `;
                
                if (delivery.info && Object.keys(delivery.info).length > 0) {
                    for (const [key, value] of Object.entries(delivery.info)) {
                        if (value) {
                            listHTML += `<div style="font-size: 13px; color: #666; margin-bottom: 4px;">${value}</div>`;
                        }
                    }
                }
                
                listHTML += generateNavigationLinks(delivery.number);
                listHTML += '</div>';
            });
        }
        
        listHTML += '</div>';
        summaryPanel.innerHTML = listHTML;
        
        $('#wpcpod-route-planner #wpcpod-route-loader').remove();
    }
    
    jQuery(document).ready(function(){
        initPODRouteMap();
    });
</script>
