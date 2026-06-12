/**
 * client-autofill.js  v4
 *
 * Rellena los campos del remitente al seleccionar un cliente.
 * Usa polling (detección activa cada 300 ms) + event listeners
 * para garantizar funcionamiento independientemente de Select2/MDB.
 * Versión mejorada con búsqueda dinámica de campos.
 */
/* global MercClientAutofill, jQuery */
jQuery(document).ready(function ($) {
    'use strict';

    if (typeof MercClientAutofill === 'undefined') {
        console.warn('[ClientAutofill] MercClientAutofill no está definido. ¿El script fue encolado?');
        return;
    }

    var ajaxurl = MercClientAutofill.ajaxurl;
    var nonce   = MercClientAutofill.nonce;

    console.log('[ClientAutofill] ✓ Iniciado');
    console.log('[ClientAutofill] Esperando detección de cliente...');

    var ultimoClienteId = '';   // Para detectar cambios por polling
    var cargando        = false; // Evitar llamadas simultáneas
    var clienteAutomatico = false; // Indica si el cliente ya está predefinido (caso cliente normal)

    /* ══════════════════════════════════════════════════════════════
     * DIAGNÓSTICO: Encontrar campos en el formulario
     * ══════════════════════════════════════════════════════════════ */

    function diagnosticoFormulario() {
        console.log('[ClientAutofill] === DIAGNÓSTICO DE FORMULARIO ===');
        
        // Select de cliente
        var $cliente = $('#registered_client');
        console.log('[ClientAutofill] Select cliente (#registered_client):', $cliente.length > 0 ? 'ENCONTRADO' : 'NO ENCONTRADO');
        if ($cliente.length) {
            console.log('   └─ Valor actual:', $cliente.val());
        }
        
        // Buscar todos los inputs y selects del formulario
        var camposEncontrados = {
            inputs: [],
            selects: [],
            textareas: []
        };
        
        $('input[type="text"], input[type="email"], input[type="tel"]').each(function() {
            var nombre = $(this).attr('name') || $(this).attr('id') || 'sin-nombre';
            camposEncontrados.inputs.push(nombre);
        });
        
        $('select').each(function() {
            var nombre = $(this).attr('name') || $(this).attr('id') || 'sin-nombre';
            var opciones = $(this).find('option').length;
            camposEncontrados.selects.push(nombre + ' (' + opciones + ' opciones)');
        });
        
        $('textarea').each(function() {
            var nombre = $(this).attr('name') || $(this).attr('id') || 'sin-nombre';
            camposEncontrados.textareas.push(nombre);
        });
        
        console.log('[ClientAutofill] Campos encontrados:');
        console.log('   ├─ Inputs:', camposEncontrados.inputs);
        console.log('   ├─ Selects:', camposEncontrados.selects);
        console.log('   └─ Textareas:', camposEncontrados.textareas);
    }

    // Ejecutar diagnóstico al cargar
    setTimeout(diagnosticoFormulario, 1000);

    /* ══════════════════════════════════════════════════════════════
     * LLENADO DE CAMPOS - Búsqueda inteligente
     * ══════════════════════════════════════════════════════════════ */

    /**
     * Busca un campo por múltiples criterios (name o id)
     */
    function buscarCampo(nombres) {
        if (!Array.isArray(nombres)) nombres = [nombres];
        
        for (var i = 0; i < nombres.length; i++) {
            var selector = '[name="' + nombres[i] + '"], #' + nombres[i];
            var $campo = $(selector);
            if ($campo.length > 0) {
                return $campo;
            }
        }
        return $();
    }

    /**
     * Rellena un <input> / <textarea> y activa el floating label de MDB4.
     */
    function setTexto($el, valor) {
        if (!$el.length || !valor) return false;
        $el.val(valor);
        $el.trigger('focus').trigger('input').trigger('blur').trigger('change');
        // Eventos nativos
        try {
            $el[0].dispatchEvent(new Event('input',  { bubbles: true }));
            $el[0].dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) { /* no-op */ }
        return true;
    }

    /**
     * Selecciona una opción en un <select> buscando por valor o texto (case-insensitive).
     */
    function setSelect($sel, valor) {
        if (!$sel.length || !valor) return false;
        var vl = valor.toLowerCase().trim();
        var ok = false;

        $sel.find('option').each(function () {
            var ov = String($(this).val()).toLowerCase().trim();
            var ot = $(this).text().toLowerCase().trim();
            
            if (ov === vl || ot === vl || ot.indexOf(vl) !== -1) {
                $sel.val($(this).val());
                ok = true;
                return false; /* break */
            }
        });

        if (!ok) return false;

        // Change nativo
        try { $sel[0].dispatchEvent(new Event('change', { bubbles: true })); } catch (e) { /* no-op */ }
        $sel.trigger('change');
        return true;
    }

    /** Rellena todos los campos del remitente con los datos del cliente. */
    function rellenarRemitente(ud) {
        console.log('[ClientAutofill] ─────── RELLENANDO REMITENTE ───────');
        console.log('[ClientAutofill] Datos recibidos:', ud);

        var camposMap = {
            nombre:    ['wpcargo_shipper_name', 'shipper_name', 'nombre_remitente'],
            telefono:  ['wpcargo_shipper_phone', 'shipper_phone', 'telefono_remitente', 'phone'],
            direccion: ['wpcargo_shipper_address', 'shipper_address', 'direccion_remitente', 'address'],
            email:     ['wpcargo_shipper_email', 'shipper_email', 'email_remitente'],
            empresa:   ['wpcargo_tiendaname', 'tienda', 'empresa', 'company'],
            link_maps: ['link_maps_remitente', 'link_maps', 'google_maps'],
            motorizado_recojo_default: ['merc_motorizo_recojo_default', 'wpcargo_motorizo_recojo', 'motorizado_recojo']
        };

        var rellenados = 0;
        var noEncontrados = [];

        for (var key in camposMap) {
            if (ud[key]) {
                var $campo = buscarCampo(camposMap[key]);
                if ($campo.length) {
                    // Si es un select, usar setSelect; si no, usar setTexto
                    var isSelect = $campo.prop('tagName').toLowerCase() === 'select';
                    
                    if (isSelect) {
                        if (setSelect($campo, ud[key])) {
                            console.log('[ClientAutofill]   ✓', key + ':', ud[key], '(select)');
                            rellenados++;
                        } else {
                            console.warn('[ClientAutofill]   ✗', key, '- opción no encontrada:', ud[key]);
                        }
                    } else {
                        if (setTexto($campo, ud[key])) {
                            console.log('[ClientAutofill]   ✓', key + ':', ud[key]);
                            rellenados++;
                        }
                    }
                } else {
                    if (key === 'motorizado_recojo_default') {
                        console.log('[ClientAutofill]   ℹ️', key, '- campo no encontrado (es opcional). Motorizado default:', ud[key]);
                    } else {
                        console.warn('[ClientAutofill]   ✗', key, '- campo NO encontrado. Buscado:', camposMap[key]);
                        noEncontrados.push(key);
                    }
                }
            }
        }

        // Distrito (es select)
        if (ud.distrito) {
            var $distrito = buscarCampo(['wpcargo_distrito_recojo', 'distrito_recojo']);
            if ($distrito.length) {
                if (setSelect($distrito, ud.distrito)) {
                    console.log('[ClientAutofill]   ✓ distrito:', ud.distrito);
                    rellenados++;
                } else {
                    console.warn('[ClientAutofill]   ✗ distrito - opción no encontrada en select:', ud.distrito);
                }
            } else {
                console.warn('[ClientAutofill]   ✗ distrito - select NO encontrado');
                noEncontrados.push('distrito');
            }
        }

        console.log('[ClientAutofill] ─────────────────────────────────────');
        console.log('[ClientAutofill] Resumen: ' + rellenados + ' campos rellenados');
        if (noEncontrados.length > 0) {
            console.warn('[ClientAutofill] Campos no encontrados:', noEncontrados.join(', '));
        }
        
        return rellenados;
    }

    /* ══════════════════════════════════════════════════════════════
     * AJAX
     * ══════════════════════════════════════════════════════════════ */

    function cargarDatosCliente(userId, forzar) {
        console.log('[ClientAutofill] 🔎 cargarDatosCliente() llamado con userId:', userId, '| forzar:', forzar);
        console.log('[ClientAutofill]    ultimoClienteId:', ultimoClienteId, '| cargando:', cargando);

        if (!userId) {
            console.log('[ClientAutofill] ⚠️ userId vacío, deteniendo');
            return;
        }
        if (userId === '0') {
            console.log('[ClientAutofill] ⚠️ userId es "0", deteniendo');
            return;
        }
        if (userId === '') {
            console.log('[ClientAutofill] ⚠️ userId es string vacío, deteniendo');
            return;
        }
        if (!forzar && userId === ultimoClienteId) {
            console.log('[ClientAutofill] ℹ️ userId igual a ultimoClienteId, evitando duplicados');
            return;
        }
        if (cargando) {
            console.log('[ClientAutofill] ⚠️ Ya está cargando, ignorando duplicado');
            return;
        }

        console.log('[ClientAutofill] ───> Cargando datos para userId:', userId);
        cargando = true;

        $.post(ajaxurl, {
            action:  'merc_get_client_data',
            nonce:   nonce,
            user_id: userId
        })
        .done(function (resp) {
            console.log('[ClientAutofill] <─── Respuesta AJAX:', resp);
            if (resp && resp.success && resp.data) {
                ultimoClienteId = userId;
                var numCampos = rellenarRemitente(resp.data);
                if (numCampos > 0) {
                    console.log('[ClientAutofill] ✅ Éxito: ' + numCampos + ' campos rellenados');
                    toast('✅ Datos del remitente cargados (' + numCampos + ' campos)', '#4CAF50');
                } else {
                    console.warn('[ClientAutofill] ⚠️ Respuesta recibida pero sin data para rellenar');
                    toast('⚠️ No se pudieron rellenar los campos', '#f39c12');
                }
                
                // 🔄 ACTUALIZAR SELECTOR DE PRODUCTOS (SOLO FULLFITMENT)
                if (resp.data.productos && Array.isArray(resp.data.productos)) {
                    if (esTipoFullfitmentActual()) {
                        console.log('[ClientAutofill] 🔄 Actualizando productos (' + resp.data.productos.length + ' encontrados)');
                        actualizarSelectorProductos(resp.data.productos);
                    } else {
                        console.log('[ClientAutofill] ℹ️ Tipo de envío actual no es fullfitment, se omite actualización de productos');
                    }
                } else {
                    console.warn('[ClientAutofill] ⚠️ Sin array de productos en respuesta');
                }
            } else {
                console.warn('[ClientAutofill] ⚠️ Respuesta sin datos:', resp);
                if (resp && resp.data && resp.data.message) {
                    console.error('[ClientAutofill] Mensaje del servidor:', resp.data.message);
                }
                toast('⚠️ Sin datos para este cliente', '#f39c12');
            }
        })
        .fail(function (xhr) {
            console.error('[ClientAutofill] ❌ Error AJAX status:', xhr.status);
            console.error('[ClientAutofill] Response text:', xhr.responseText);
            var msg = xhr.responseText || 'Error desconocido';
            console.error('[ClientAutofill] Intentando parsear error...');
            try {
                var parsed = JSON.parse(xhr.responseText);
                console.error('[ClientAutofill] Error JSON:', parsed);
                if (parsed.data && parsed.data.message) {
                    msg = parsed.data.message;
                }
            } catch(e) {
                console.error('[ClientAutofill] No se pudo parsear como JSON:', e.message);
            }
            toast('❌ ' + msg, '#e74c3c');
        })
        .always(function () {
            cargando = false;
        });
    }

    /* ══════════════════════════════════════════════════════════════
     * ACTUALIZAR SELECTOR DE PRODUCTOS CON ARRAY
     * ══════════════════════════════════════════════════════════════ */
    function normalizarTipoEnvio(rawTipo) {
        var raw = String(rawTipo || '').toLowerCase().trim();
        if (raw === 'full_fitment' || raw === 'full-fitment' || raw === 'fullfitment') return 'fullfitment';
        return raw;
    }

    function esTipoFullfitmentActual() {
        var tipoEnvio =
            $('#tipo-envio-actual').val() ||
            $('input[name="tipo_envio"]').first().val() ||
            $('#tipo_envio_hidden').val() ||
            '';

        var tipoNormalizado = normalizarTipoEnvio(tipoEnvio);
        console.log('[ClientAutofill] 🔎 tipo_envio detectado:', tipoEnvio, '| normalizado:', tipoNormalizado);
        return tipoNormalizado === 'fullfitment';
    }

    function actualizarSelectorProductos(productosArray) {
        // productosArray = [ { id, titulo, stock }, ... ]
        console.log('[ClientAutofill] 🔄 actualizarSelectorProductos() llamado');
        console.log('[ClientAutofill]   Productos recibidos:', productosArray);
        console.log('[ClientAutofill]   Array length:', productosArray ? productosArray.length : 'null');

        if (!esTipoFullfitmentActual()) {
            console.log('[ClientAutofill] ℹ️ Se omite actualizar productos: tipo_envio no es fullfitment');
            return;
        }

        if (!productosArray || productosArray.length === 0) {
            console.warn('[ClientAutofill] ⚠️ Array de productos vacío o null');
            alert('⚠️ Sin productos disponibles para este cliente');
            return;
        }

        // Construir HTML de opciones
        var html = '<option value="">-- Selecciona un producto --</option>';
        for (var i = 0; i < productosArray.length; i++) {
            var prod = productosArray[i];
            var label = (prod.titulo || 'Producto sin nombre') + ' (Stock: ' + (prod.stock || 0) + ')';
            html += '<option value="' + prod.id + '">' + label + '</option>';
        }

        console.log('[ClientAutofill]   HTML generado (' + productosArray.length + ' opciones), actualizando selects...');

        // Actualizar todos los selects y la plantilla de fila
        var $productSelects = jQuery('select[name="merc_producto_id[]"]');
        console.log('[ClientAutofill]   - Selects encontrados:', $productSelects.length);
        
        if ($productSelects.length === 0) {
            console.error('[ClientAutofill] ❌ NO HAY SELECTS CON NAME "merc_producto_id[]"');
            alert('❌ Error: No se encontró el selector de productos en el formulario');
            return;
        }
        
        $productSelects.each(function (idx) { 
            jQuery(this).html(html);
            console.log('[ClientAutofill]   ✓ Select #' + idx + ' actualizado');
        });

        var $template = jQuery('#merc_product_template');
        console.log('[ClientAutofill]   - Template encontrado:', $template.length > 0 ? '✓' : '✗');
        if ($template.length) {
            $template.find('select[name="merc_producto_id[]"]').html(html);
            console.log('[ClientAutofill]   ✓ Template actualizado');
        }

        // Actualizar pequeño contador si existe
        var $contador = jQuery('#merc_producto_wrapper').find('small.text-muted').first();
        console.log('[ClientAutofill]   - Contador encontrado:', $contador.length > 0 ? '✓' : '✗');
        if ($contador.length) {
            $contador.text('Solo se muestran productos disponibles (' + productosArray.length + ' total)');
            console.log('[ClientAutofill]   ✓ Contador actualizado a: ' + productosArray.length);
        }

        // Ocultar alerta de "No hay productos" si existe
        var $alerta = jQuery('#merc_producto_wrapper').find('.alert-warning').first();
        console.log('[ClientAutofill]   - Alerta encontrada:', $alerta.length > 0 ? '✓' : '✗');
        if ($alerta.length && productosArray.length > 0) {
            $alerta.hide();
            console.log('[ClientAutofill]   ✓ Alerta oculta');
        }

        console.log('[ClientAutofill] ✅ Selector de productos actualizado con éxito');
        alert('✅ Productos cargados (' + productosArray.length + ' disponibles)');
    }

    /* ══════════════════════════════════════════════════════════════
     * DETECCIÓN DE CAMBIO — Event listeners + Polling como respaldo
     * ══════════════════════════════════════════════════════════════ */

    // Función auxiliar para obtener el select de cliente con múltiples selectores
    function getClientSelect() {
        var selectors = [
            '#registered_client',
            'select[name="registered_shipper"]',
            'select[id*="client"]',
            'select[name*="client"]',
            'select[name*="shipper"]',
            'select.wpcargo-client-select'
        ];
        
        for (var i = 0; i < selectors.length; i++) {
            var $sel = $(selectors[i]).first();
            if ($sel.length) {
                if (i > 1 && pollingIntentando === 0) {
                    console.log('[ClientAutofill] ✓ Cliente select encontrado con:', selectors[i]);
                }
                return $sel;
            }
        }
        return $();
    }

    /* ══════════════════════════════════════════════════════════════
     * DETECTAR CLIENTE INICIAL (al cargar la página)
     * ══════════════════════════════════════════════════════════════ */

    function detectarClienteInicial() {
        console.log('[ClientAutofill] 🔍 Detectando cliente inicial...');
        
        // CASO 1: Input hidden (cliente predefinido para cliente normal)
        var $inputHidden = $('input[type="hidden"][name="registered_shipper"], input[type="hidden"][id="registered_shipper"]').first();
        console.log('[ClientAutofill] 📍 Buscando input hidden... encontrados:', $inputHidden.length);
        
        if ($inputHidden.length) {
            var clienteId = $inputHidden.val();
            console.log('[ClientAutofill] ✓ Input hidden encontrado: registered_shipper');
            console.log('[ClientAutofill] │  Nombre:', $inputHidden.attr('name'));
            console.log('[ClientAutofill] │  ID:', $inputHidden.attr('id'));
            console.log('[ClientAutofill] └─ Valor:', clienteId || '(vacío)');
            
            if (clienteId && clienteId !== '' && clienteId !== '0') {
                console.log('[ClientAutofill] ✓ Cliente predefinido (input hidden): ' + clienteId);
                console.log('[ClientAutofill] 📞 Llamando a cargarDatosCliente(' + clienteId + ')...');
                cargarDatosCliente(clienteId, true); // true = forzar carga (es inicial)
                clienteAutomatico = true;
                console.log('[ClientAutofill] ✓ detectarClienteInicial() terminado (input hidden)');
                return;
            } else {
                console.log('[ClientAutofill] ⚠️ Input hidden vacío o valor 0, continuando...');
            }
        }
        
        // CASO 2: Select de cliente (admin/operador selecciona cliente)
        var $sel = getClientSelect();
        
        if ($sel.length) {
            var clienteId = $sel.val();
            console.log('[ClientAutofill] ✓ Select encontrado:', $sel.attr('name') || $sel.attr('id'));
            console.log('[ClientAutofill] └─ Valor inicial:', clienteId || '(vacío)');
            
            // Si hay un valor inicial, cargar datos del cliente
            if (clienteId && clienteId !== '' && clienteId !== '0') {
                console.log('[ClientAutofill] ✓ Cliente predefinido (select): ' + clienteId);
                ultimoClienteId = clienteId;
                cargarDatosCliente(clienteId);
                clienteAutomatico = true;
            }
        } else {
            console.log('[ClientAutofill] ⚠️ No se encontró select de cliente');
            console.log('[ClientAutofill] ℹ️ Esto es normal si eres un cliente normal (sin opción de elegir)');
            clienteAutomatico = true; // Marca que el cliente es automático (predefinido por PHP)
        }
    }

    // Ejecutar detección de cliente inicial después de 500ms
    setTimeout(detectarClienteInicial, 500);

    // Listener estándar change (nativo y jQuery) - SELECTORES AMPLIOS
    $(document).on('change', '#registered_client, select[name="registered_shipper"], select[id*="client"], select[name*="client"], select[name*="shipper"]', function () {
        var v = $(this).val();
        console.log('[ClientAutofill] [EVENT:change] Cliente seleccionado:', v, '| Selector:', this.name || this.id);
        if (v && v !== '0' && v !== '') cargarDatosCliente(v);
    });

    // Listener DIRECTO para #registered_client (para asegurar que se ejecute)
    jQuery('#registered_client').on('change select2:select', function() {
        var v = jQuery(this).val();
        console.log('[ClientAutofill] [DIRECT_LISTENER] #registered_client cambió a:', v);
        if (v && v !== '0' && v !== '') {
            console.log('[ClientAutofill] 📞 Ejecutando cargarDatosCliente(' + v + ')');
            cargarDatosCliente(v);
        }
    });

    // Select2 dispara este evento en adición a change
    $(document).on('select2:select', '#registered_client, select[name="registered_shipper"], select[id*="client"], select[name*="client"]', function (e) {
        var v = (e.params && e.params.data) ? String(e.params.data.id) : $(this).val();
        console.log('[ClientAutofill] [EVENT:select2:select] Cliente seleccionado:', v, '| Selector:', this.name || this.id);
        if (v && v !== '0' && v !== '') cargarDatosCliente(v);
    });

    // También escuchar click como fallback
    $(document).on('click', 'select[id*="client"], select[name*="client"], select[name*="shipper"]', function () {
        var that = this;
        setTimeout(function() {
            var v = $(that).val();
            if (v && v !== '0' && v !== '' && v !== ultimoClienteId) {
                console.log('[ClientAutofill] [EVENT:click] Cliente detectado:', v, '| Selector:', that.name || that.id);
                cargarDatosCliente(v);
            }
        }, 100);
    });

    // Polling: comprueba cada 200 ms si el valor del select cambió (MEJORADO)
    var pollingIntentando = 0;
    setInterval(function () {
        var $sel = getClientSelect();
        if (!$sel.length) {
            if (pollingIntentando < 3) {
                pollingIntentando++;
                console.warn('[ClientAutofill] [POLLING] Select de cliente no encontrado (intento ' + pollingIntentando + ')');
            }
            return;
        }
        
        var v = $sel.val();
        if (v && v !== ultimoClienteId && v !== '' && v !== '0') {
            console.log('[ClientAutofill] [POLLING] Cambio detectado \u2192 userId:', v, '| Selector:', $sel.attr('name') || $sel.attr('id'));
            cargarDatosCliente(v);
        }
    }, 200);

    /* ══════════════════════════════════════════════════════════════
     * TOAST
     * ══════════════════════════════════════════════════════════════ */

    function toast(msg, color) {
        $('.merc-client-toast').remove();
        $('<div class="merc-client-toast">' + msg + '</div>').css({
            background: color, color: '#fff', padding: '10px 18px',
            borderRadius: '6px', fontWeight: 'bold', position: 'fixed',
            top: '70px', right: '20px', zIndex: 9999,
            boxShadow: '0 2px 8px rgba(0,0,0,.3)', fontSize: '14px'
        }).appendTo('body');
        setTimeout(function () {
            $('.merc-client-toast').fadeOut(400, function () { $(this).remove(); });
        }, 4000);
    }
});


