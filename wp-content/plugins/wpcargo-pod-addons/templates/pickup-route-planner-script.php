<script>
    // Variable global para almacenar las entregas de pickup
    let pickups = [];
    
    // Función para obtener la fecha de hoy en formato Y-m-d
    function getTodayDate() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${day}/${month}/${year}`;
    }
    
    // Función para obtener datos de un pickup específico
    async function getPickupData(postId) {
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                type: "POST",
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                data: {
                    action: 'wpcpod_get_single_pickup_data',
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
    
    // Función para generar enlaces de navegación para pickup
    function generatePickupNavigationLinks(shipmentNumber) {
        return `
            <div class="shipment-actions" style="margin-top: 8px;">
                <button onclick="togglePickupActions('actions-${shipmentNumber}')" 
                   type="button"
                   style="display: block; background-color: #6c757d; color: white; 
                          padding: 8px 12px; border: none; border-radius: 4px; 
                          font-size: 12px; cursor: pointer; width: 100%; margin-bottom: 8px;">
                    ⚡ Mostrar Acciones
                </button>
                <div id="actions-${shipmentNumber}" style="display: none; border-top: 1px solid #e0e0e0; padding-top: 8px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <button onclick="openPickupGoogleMaps('${shipmentNumber}')" 
                           type="button"
                           style="display: block; background-color: #4285f4; color: white; 
                                  padding: 8px 12px; border: none; border-radius: 4px; 
                                  font-size: 12px; text-align: center; font-weight: 500; 
                                  cursor: pointer; width: 100%;">
                            📍 Ver ubicación en Google Maps
                        </button>
                        
                        <button onclick="sendPickupWhatsAppMessage('${shipmentNumber}')" 
                           type="button"
                           style="display: block; background-color: #25D366; color: white; 
                                  padding: 8px 12px; border: none; border-radius: 4px; 
                                  font-size: 12px; text-align: center; font-weight: 500; 
                                  cursor: pointer; width: 100%;">
                            💬 Contactar Tienda por WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Función para alternar visibilidad de acciones
    function togglePickupActions(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    // Función para abrir Google Maps con el link correcto
    async function openPickupGoogleMaps(shipmentNumber) {
        try {
            const pickup = pickups.find(p => p.number === shipmentNumber);
            
            if (!pickup) {
                alert('❌ No se encontró el recojo.');
                return;
            }
            
            if (pickup.link_maps && pickup.link_maps.trim() !== '') {
                window.open(pickup.link_maps, '_blank');
            } else {
                alert('❌ No hay link de Google Maps disponible para este recojo.');
            }
            
        } catch (error) {
            alert('❌ Error al obtener el link de Google Maps');
        }
    }
    
    // Función para enviar mensaje de WhatsApp a la tienda para recojo
    async function sendPickupWhatsAppMessage(shipmentNumber) {
        jQuery('body').append('<div id="whatsapp-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div style="background: white; padding: 20px; border-radius: 8px; text-align: center;"><div style="font-size: 18px; margin-bottom: 10px;">⏳ Obteniendo datos...</div></div></div>');
        
        try {
            const pickup = pickups.find(p => p.number === shipmentNumber);
            
            if (!pickup || !pickup.id) {
                jQuery('#whatsapp-loader').remove();
                alert('❌ No se encontró el ID del recojo.');
                return;
            }
            
            const data = await getPickupData(pickup.id);
            
            jQuery('#whatsapp-loader').remove();
            
            const shipperPhone = formatPhoneNumber(data.shipper_phone);
            
            if (!shipperPhone) {
                alert('❌ No se encontró el teléfono de la tienda para este recojo.');
                return;
            }
            
            let mensaje = `¡Hola! Te saluda👋🏼 *${data.motorizado_name}* motorizado🏍️ de MERCourier, estoy a cargo de tu recojo 🛂 el día de hoy.\n\n`;
            mensaje += `📦 *Datos del recojo:*\n`;
            mensaje += `- Número de recojo: *${shipmentNumber}*\n`;
            mensaje += `- Tienda: *${data.tienda_name || data.shipper_name}*\n`;
            mensaje += `- Dirección: *${data.shipper_address}*\n\n`;
            mensaje += `Por favor tener listo tus pedidos🎁🛍️ rotulado y sellado completamente 📦\n`;
            mensaje += `Estaré llegando de *10:30 am a 12:30 pm*.\n\n`;
            mensaje += `¡Gracias! Por su preferencia, quedo atento a su respuesta. 😊🤝`;
            
            const mensajeCodificado = encodeURIComponent(mensaje);
            const whatsappUrl = `https://wa.me/${shipperPhone}?text=${mensajeCodificado}`;
            
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
    
    function initPODRouteMap() {
        $('#wpcpod-route-planner #wpcpod-route-map').hide();
        
        const today = getTodayDate();
        
        jQuery.ajax({
            type: "POST",
            data: {
                action: 'wpcpod_generate_pickup_route_address',
                filter_date: today
            },
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            success: function(response) {
                if (response.status == 'success') {
                    displayPickupList(response.origin, response.waypoints, response.shipments, response.poo, response.available_statuses);
                } else {
                    $('#wpcpod-route-planner #wpcpod-route-map').remove();
                    $('#wpcpod-route-planner #wpcpod-route-loader').remove();
                    $('#wpcpod-route-planner #route-planner-content').append('<div class="my-4 alert alert-info text-center">' + response.message + '</div>')
                }
            }
        });
    }
    
    function displayPickupList(origin, waypoints, shipments, poo, availableStatuses) {
        const summaryPanel = document.getElementById("directions-panel");
        
        console.log('=== DEBUG PICKUP SHIPMENTS ===');
        console.log('Shipments:', shipments);
        console.log('Available Statuses:', availableStatuses);
        console.log('======================');
        
        // Definir transiciones de estados según el estado actual
        const estadosMotorizadoInicial = ['RECOGIDO', 'NO RECOGIDO'];
        const estadosMotorizadoDespuesBase = ['EN RUTA', 'NO CONTESTA', 'NO RECIBIDO', 'ENTREGADO', 'REPROGRAMADO', 'ANULADO'];
        const estadosAvanzados = ['EN BASE MERCOURIER', 'RECEPCIONADO', 'LISTO PARA SALIR', 'NO CONTESTA', 'EN RUTA', 'NO RECIBIDO', 'ENTREGADO', 'REPROGRAMADO', 'ANULADO'];
        
        // Estados de fase inicial (incluir PENDIENTE)
        const estadosInicial = ['PENDIENTE', 'RECOGIDO', 'NO RECOGIDO'];
        
        // Función para obtener estados permitidos según el estado actual
        function getPermittedStatuses(currentStatus, allStatuses) {
            console.log('🔍 getPermittedStatuses - currentStatus:', currentStatus, 'allStatuses:', allStatuses);
            
            if (!allStatuses) {
                console.log('❌ allStatuses is empty');
                return [];
            }
            const currentStatusUpper = (currentStatus || '').toUpperCase();
            let permitidos = [];
            
            // Detectar si el estado actual es avanzado
            const esEstadoAvanzado = estadosAvanzados.some(function(estado) {
                return currentStatusUpper.includes(estado);
            });
            
            console.log('   Estado actual upper:', currentStatusUpper);
            console.log('   ¿Es avanzado?:', esEstadoAvanzado);
            
            if (esEstadoAvanzado) {
                // Si es avanzado, mostrar estados posteriores
                permitidos = allStatuses.filter(function(status) {
                    const statusUpper = status.toUpperCase();
                    return estadosMotorizadoDespuesBase.some(function(permitido) {
                        return statusUpper.includes(permitido) || permitido.includes(statusUpper);
                    });
                });
                console.log('   Filtrando como avanzado - permitidos:', permitidos);
            } else {
                // Si no es avanzado (es inicial), mostrar estados iniciales
                permitidos = allStatuses.filter(function(status) {
                    const statusUpper = status.toUpperCase();
                    return estadosMotorizadoInicial.some(function(permitido) {
                        return statusUpper.includes(permitido) || permitido.includes(statusUpper);
                    });
                });
                console.log('   Filtrando como inicial - permitidos:', permitidos);
            }
            
            // Filtrar "LISTO PARA SALIR" si existe
            permitidos = permitidos.filter(function(opt) {
                return opt.toUpperCase().trim() !== 'LISTO PARA SALIR';
            });
            
            console.log('   Después filtrar LISTO PARA SALIR - permitidos finales:', permitidos);
            return permitidos;
        }
        
        pickups = [];
        const today = getTodayDate();
        const groupedByUser = {};
        
        if (shipments && shipments.length > 0) {
            shipments.forEach((shipment, index) => {
                const shipmentDate = shipment['pickup_date'] || '';

                    // Normalizar fechas: aceptar 'd/m/Y' y 'Y-m-d'
                    function normalizeToDMY(dateStr) {
                        if (!dateStr) return '';
                        // si ya está en formato d/m/Y
                        if (dateStr.indexOf('/') !== -1) return dateStr;
                        // si está en formato Y-m-d o Y-m-d H:i:s
                        if (dateStr.indexOf('-') !== -1) {
                            const parts = dateStr.split(' ');
                            const datePart = parts[0];
                            const p = datePart.split('-');
                            if (p.length >= 3) {
                                return `${p[2]}/${p[1]}/${p[0]}`;
                            }
                        }
                        return dateStr;
                    }

                    const shipmentDateNorm = normalizeToDMY(shipmentDate);

                    if (shipmentDateNorm && shipmentDateNorm !== today) {
                        return;
                    }
                
                const shipperId = shipment['registered_shipper'] || 'sin_usuario';
                const shipperName = shipment['shipper_name'] || 'Cliente desconocido';
                
                console.log(`🔍 Shipment ${index}:`, {
                    number: shipment['number'],
                    registered_shipper: shipment['registered_shipper'],
                    shipper_name: shipment['shipper_name'],
                    status: shipment['status'],
                    shipperId_computed: shipperId,
                    shipperName_computed: shipperName
                });
                
                let address = shipment['address'] || 'Dirección no disponible';
                let linkMapsRemitente = shipment['link_maps_remitente'] || '';
                let lat = shipment['lat'] || null;
                let lng = shipment['lng'] || null;
                
                // Extraer coordenadas del link si es necesario
                if ((!lat || !lng) && linkMapsRemitente) {
                    const coords = extractCoordinatesFromUrl(linkMapsRemitente);
                    if (coords) {
                        lat = coords.lat;
                        lng = coords.lng;
                    }
                }
                
                let distance = 0;
                if (origin && origin.lat && origin.lng && lat && lng) {
                    distance = calculateDistance(origin.lat, origin.lng, lat, lng);
                }
                
                if (!groupedByUser[shipperId]) {
                    groupedByUser[shipperId] = {
                        name: shipperName,
                        pickups: []
                    };
                }
                
                groupedByUser[shipperId].pickups.push({
                    id: shipment['id'] || null,
                    number: shipment['number'] || 'N/A',
                    address: address,
                    link_maps: linkMapsRemitente,
                    info: shipment['info'] || {},
                    lat: lat,
                    lng: lng,
                    distance: distance,
                    pickup_date: shipmentDate,
                    status: shipment['status'] || 'N/A'
                });
                
                pickups.push({
                    id: shipment['id'] || null,
                    number: shipment['number'] || 'N/A',
                    address: address,
                    link_maps: linkMapsRemitente,
                    info: shipment['info'] || {},
                    lat: lat,
                    lng: lng,
                    distance: distance,
                    pickup_date: shipmentDate,
                    status: shipment['status'] || 'N/A',
                    registered_shipper: shipperId,
                    shipper_name: shipperName
                });
            });
        }
        
        pickups.sort((a, b) => a.distance - b.distance);
        
        let listHTML = '<div style="font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto;">';
        listHTML += `<h3 style="margin-bottom: 20px; color: #333; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">📦 Lista de Recojos - ${today}</h3>`;
        
        if (pickups.length === 0) {
            listHTML += '<div class="alert alert-warning" style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; text-align: center;">⚠️ No se encontraron recojos PENDIENTES para hoy</div>';
        } else {
            // Mostrar por grupos de usuario
            Object.keys(groupedByUser).forEach((shipperId) => {
                const userGroup = groupedByUser[shipperId];
                const shipperName = userGroup.name;
                const pickupCount = userGroup.pickups.length;
                
                listHTML += `
                    <div style="border: 3px solid #2c3e50; border-radius: 8px; padding: 0; margin-bottom: 20px; background: white; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">
                        <div style="background: #2c3e50; color: white; padding: 15px; border-radius: 8px 8px 0 0; display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                <h4 style="margin: 0; font-size: 18px; font-weight: bold; word-break: break-word;">
                                    <i class="fa fa-user-circle" style="margin-right: 8px;"></i>${shipperName.length > 30 ? shipperName.substring(0, 30) + '<br>' + shipperName.substring(30) : shipperName}
                                </h4>
                                <span style="background: #3498db; padding: 4px 12px; border-radius: 12px; font-size: 13px; white-space: nowrap; flex-shrink: 0;">
                                    ${pickupCount} recojo(s)
                                </span>
                            </div>
                            <button onclick="toggleUserPickups('user-${shipperId}')" 
                               type="button"
                               style="background-color: #34495e; color: white; 
                                      padding: 8px 12px; border: none; border-radius: 4px; 
                                      font-size: 12px; cursor: pointer; align-self: flex-end;">
                                ▶ Recojos
                            </button>
                        </div>
                        
                        <div id="user-${shipperId}" style="display: none; padding: 15px;">
                `;
                
                userGroup.pickups.forEach((pickup, idx) => {
                    // Obtener los estados permitidos según el estado actual del pickup
                    const permittedStatuses = getPermittedStatuses(pickup.status, availableStatuses);
                    
                    console.log(`📦 Pickup ${pickup.number}: status='${pickup.status}' -> permitidos:`, permittedStatuses);
                    
                    // Generar opciones del select: incluir SIEMPRE el estado actual + los permitidos
                    let statusOptions = '';
                    
                    // Agregar el estado actual como opción (puede no estar en permitidos)
                    statusOptions += `<option value="${pickup.status}" selected>${pickup.status}</option>`;
                    
                    // Agregar los estados permitidos (si no son el estado actual)
                    if (permittedStatuses && permittedStatuses.length > 0) {
                        permittedStatuses.forEach(status => {
                            // No duplicar el estado actual
                            if (status !== pickup.status) {
                                statusOptions += `<option value="${status}">${status}</option>`;
                            }
                        });
                    }
                    
                    listHTML += `
                        <div style="border: 1px solid #bdc3c7; border-radius: 6px; padding: 12px; margin-bottom: 12px; background: #f8f9fa;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e0e0e0;">
                                <span style="font-weight: bold; font-size: 16px; color: #2c3e50;">
                                    Recojo: ${pickup.number}
                                </span>
                                <select onchange="updatePickupStatus(${pickup.id}, this.value)" 
                                   style="background: #fff; color: #333; padding: 6px 10px; border: 1px solid #bdc3c7; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; min-width: 120px;">
                                    ${statusOptions}
                                </select>
                            </div>
                            <div style="font-size: 14px; color: #555; margin-bottom: 8px;">
                                <strong>📍 Dirección:</strong> ${pickup.address}
                            </div>
                    `;
                    
                    if (pickup.info && Object.keys(pickup.info).length > 0) {
                        for (const [key, value] of Object.entries(pickup.info)) {
                            if (value) {
                                listHTML += `<div style="font-size: 13px; color: #666; margin-bottom: 4px;">${value}</div>`;
                            }
                        }
                    }
                    
                    listHTML += generatePickupNavigationLinks(pickup.number);
                    listHTML += '</div>';
                });
                
                listHTML += `
                        </div>
                    </div>
                `;
            });
        }
        
        listHTML += '</div>';
        summaryPanel.innerHTML = listHTML;
        
        $('#wpcpod-route-planner #wpcpod-route-loader').remove();
    }
    
    // Función para alternar visibilidad de recojos del usuario
    function toggleUserPickups(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    // Función para actualizar estado de un pickup individual
    async function updatePickupStatus(shipmentId, newStatus) {
        // Obtener el nonce del atributo data
        const nonce = document.getElementById('wpcpod-route-planner').getAttribute('data-nonce');
        
        // Mostrar confirmación con SweetAlert
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
                try {
                    const response = await jQuery.ajax({
                        type: "POST",
                        url: "<?php echo admin_url('admin-ajax.php'); ?>",
                        data: {
                            action: 'wpcpod_update_pickup_status',
                            shipment_id: shipmentId,
                            new_status: newStatus,
                            nonce: nonce
                        }
                    });
                    
                    if (response.success) {
                        Swal.fire({
                            title: '✅ Éxito',
                            text: response.data.message,
                            icon: 'success',
                            confirmButtonColor: '#3498db',
                            timer: 2000
                        });
                        console.log('✅ Estado actualizado:', response.data.message);
                    } else {
                        Swal.fire({
                            title: '❌ Error',
                            text: response.data.message,
                            icon: 'error',
                            confirmButtonColor: '#e74c3c'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: '❌ Error de conexión',
                        text: 'No se pudo actualizar el estado',
                        icon: 'error',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            }
        });
    }
    
    jQuery(document).ready(function() {
        initPODRouteMap();
    });
</script>