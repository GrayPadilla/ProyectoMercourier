/**
 * container-assign.js  v4
 *
 * Asigna automáticamente el contenedor al cambiar el distrito.
 * Usa polling (detección activa cada 300 ms) + event listeners
 * para garantizar funcionamiento independientemente de MDB/Select2.
 * Versión mejorada con búsqueda dinámica de campos.
 *
 * - Distrito Destino → Contenedor ENTREGA (normal) o único (express/full_fitment)
 * - Distrito Recojo  → Contenedor RECOJO  (solo tipo 'normal')
 */
/* global MercContainerAssign, jQuery */
jQuery(document).ready(function ($) {
    'use strict';

    if (typeof MercContainerAssign === 'undefined') {
        console.warn('[ContainerAssign] MercContainerAssign no está definido.');
        return;
    }

    var ajaxurl     = MercContainerAssign.ajaxurl;
    var shipmentId  = MercContainerAssign.shipmentId || 0;

    console.log('[ContainerAssign] ✓ Iniciado - Modo:', MercContainerAssign.mode);

    /* ══════════════════════════════════════════════════════════════
     * DIAGNÓSTICO: Encontrar selects de distrito
     * ══════════════════════════════════════════════════════════════ */

    function diagnosticoFormulario() {
        console.log('[ContainerAssign] === DIAGNÓSTICO DE FORMULARIO ===');
        
        var selectsEncontrados = [];
        $('select').each(function() {
            var nombre = $(this).attr('name') || $(this).attr('id') || 'sin-nombre';
            var opciones = $(this).find('option').length;
            selectsEncontrados.push({
                nombre: nombre,
                opciones: opciones,
                valor: $(this).val()
            });
        });
        
        console.log('[ContainerAssign] Selects encontrados:');
        selectsEncontrados.forEach(function(s) {
            console.log('   ├─', s.nombre, '(' + s.opciones + ' opciones)', '→', s.valor);
        });
        
        // Buscar específicamente los distritos
        var $destino = buscarSelectDistrito('destino');
        var $recojo = buscarSelectDistrito('recojo');
        
        console.log('[ContainerAssign] Resultados búsqueda:');
        console.log('   ├─ Distrito Destino:', $destino.length > 0 ? 'ENCONTRADO (' + $destino.attr('name') + ')' : 'NO ENCONTRADO');
        console.log('   └─ Distrito Recojo:', $recojo.length > 0 ? 'ENCONTRADO (' + $recojo.attr('name') + ')' : 'NO ENCONTRADO');
        
        // Mostrar estado del autocompletado según tipo de envío
        var tipo = tipoEnvioGlobal || getTipoEnvio();
        console.log('[ContainerAssign] ✅ AUTOCOMPLETADO ACTIVO para tipo:', tipo || '(pendiente)');
    }

    setTimeout(diagnosticoFormulario, 1000);

    /**
     * Busca un select de distrito (destino o recojo) por varios nombres posibles
     */
    function buscarSelectDistrito(tipo) {
        var nombres = tipo === 'destino' 
            ? ['wpcargo_distrito_destino', 'distrito_destino', 'destination_district', 'distrito']
            : ['wpcargo_distrito_recojo', 'distrito_recojo', 'pickup_district', 'distrito_origen'];
        
        for (var i = 0; i < nombres.length; i++) {
            var $sel = $('select[name="' + nombres[i] + '"], #' + nombres[i] + ', select[id*="' + nombres[i] + '"]');
            if ($sel.length > 0) {
                return $sel.first();
            }
        }
        return $();
    }

    /* ══════════════════════════════════════════════════════════════
     * TIPO DE ENVÍO
     * ══════════════════════════════════════════════════════════════ */

    var tipoEnvioGlobal = '';

    /** Obtiene el tipo de envío desde la URL, campos hidden o AJAX (modo edición). */
    function getTipoEnvio() {
        // 1. Hidden input inyectado por tipo-envio-saver.js
        var el = document.querySelector('input[name="tipo_envio"]');
        if (el && el.value) return el.value;

        // 2. URL query string (?type=...)
        var urlTipo = new URLSearchParams(window.location.search).get('type');
        if (urlTipo) return urlTipo;

        return '';
    }

    // PHP inyecta el tipo directamente en MercContainerAssign.tipoEnvio (add y update)
    tipoEnvioGlobal = MercContainerAssign.tipoEnvio || getTipoEnvio();

    // Fallback: si aún no se obtuvo, reintentar con el hidden input (inyectado por tipo-envio-saver.js)
    if (!tipoEnvioGlobal) {
        var intentosTipo = 0;
        var ivTipo = setInterval(function () {
            intentosTipo++;
            tipoEnvioGlobal = getTipoEnvio();
            if (tipoEnvioGlobal || intentosTipo >= 10) {
                clearInterval(ivTipo);
                console.log('[ContainerAssign] tipoEnvio detectado (fallback):', tipoEnvioGlobal);
            }
        }, 300);
    } else {
        console.log('[ContainerAssign] tipoEnvio:', tipoEnvioGlobal);
    }

    /* ══════════════════════════════════════════════════════════════
     * NOMBRE DEL SELECT DE CONTENEDOR
     * ══════════════════════════════════════════════════════════════
     *
     *  tipo 'normal'          → destino: shipment_container_entrega
     *                           recojo:  shipment_container_recojo
     *  express / full_fitment → destino/entrega: shipment_container_entrega (único)
     *                           recojo:  no aplica (null)
     * ══════════════════════════════════════════════════════════════ */

    function getSelectName(campo) {
        var tipo = (tipoEnvioGlobal || '').toLowerCase();
        if (tipo === 'normal') {
            return campo === 'destino' ? 'shipment_container_entrega' : 'shipment_container_recojo';
        }
        // EXPRESS / FULL_FITMENT: usar shipment_container_entrega para ambos
        return campo === 'destino' ? 'shipment_container_entrega' : null;
    }

    /* ══════════════════════════════════════════════════════════════
     * ACTUALIZAR SELECT MDB
     * ══════════════════════════════════════════════════════════════ */

    /**
     * Asigna containerId en el select MDB y refresca el widget visual.
     * MDB4 oculta el <select> nativo y crea una UI personalizada.
     * Hay que llamar materialSelect() para que el widget se actualice.
     */
    function setearSelectContenedor($sel, containerId) {
        if (!$sel.length) return false;

        // Verificar que la opción exista
        if (!$sel.find('option[value="' + containerId + '"]').length) {
            console.warn('[ContainerAssign] Opción no encontrada en select para containerId:', containerId);
            return false;
        }

        $sel.val(containerId);
        $sel.trigger('change');

        // Refrescar widget MDB (método real: $.fn.materialSelect)
        if (typeof $.fn.materialSelect === 'function') {
            try { $sel.materialSelect('destroy'); } catch (e) { /* no-op */ }
            try { $sel.materialSelect(); }         catch (e) { /* no-op */ }
            console.log('[ContainerAssign] materialSelect() llamado OK');
        } else {
            console.warn('[ContainerAssign] $.fn.materialSelect no disponible, usando solo .val()');
        }

        return true;
    }

    /* ══════════════════════════════════════════════════════════════
     * AJAX: BUSCAR Y ASIGNAR CONTENEDOR
     * ══════════════════════════════════════════════════════════════ */

    var enCurso = {}; // { destino: bool, recojo: bool }

    function buscarYAsignar(distrito, campo) {
        if (!distrito || /^--/.test(distrito.trim()) || distrito === '' || distrito === '0') return;
        if (enCurso[campo]) return;

        var tipo = tipoEnvioGlobal || getTipoEnvio();

        var selectName = getSelectName(campo);
        if (!selectName) {
            console.log('[ContainerAssign] Campo "' + campo + '" no aplica para tipo:', tipo);
            return;
        }

        var $sel = $('select[name="' + selectName + '"]');
        if (!$sel.length) {
            console.warn('[ContainerAssign] Select no encontrado:', selectName, '— reintentará en próximo ciclo');
            // Resetear para que el polling pueda reintentar cuando el select esté disponible
            if (campo === 'destino') lastDestino = '';
            else lastRecojo = '';
            return;
        }

        console.log('[ContainerAssign] Buscando contenedor | distrito:', distrito, '| campo:', campo, '| tipo:', tipo);
        enCurso[campo] = true;

        $.ajax({
            url: ajaxurl, type: 'POST', timeout: 10000,
            data: {
                action:     'merc_buscar_contenedor_por_distrito',
                distrito:   distrito,
                tipo_envio: tipo
            },
            success: function (r) {
                console.log('[ContainerAssign] Respuesta AJAX:', r);
                if (!r || !r.success || !r.data || !r.data.container_id) {
                    toast('⚠️ Sin contenedor para: ' + distrito, '#f39c12');
                    return;
                }
                var ok = setearSelectContenedor($sel, r.data.container_id);
                if (ok) {
                    toast('✅ Contenedor ' + (campo === 'destino' ? 'ENTREGA' : 'RECOJO') +
                          ': ' + r.data.container_name, '#4CAF50');
                } else {
                    toast('⚠️ Contenedor no listado: ' + r.data.container_name, '#f39c12');
                }
            },
            error: function (xhr) {
                console.error('[ContainerAssign] Error AJAX:', xhr.status);
                toast('❌ Error al buscar contenedor', '#e74c3c');
            },
            complete: function () {
                enCurso[campo] = false;
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════
     * DETECCIÓN DE CAMBIO — Event listeners + Polling
     * ══════════════════════════════════════════════════════════════ */

    var lastDestino = '';
    var lastRecojo  = '';
    var tDest = null;
    var tRecojo = null;

    function onDestinoChange(val) {
        if (!val || val === lastDestino || /^--/.test(val) || val === '' || val === '0') return;
        lastDestino = val;
        console.log('[ContainerAssign] [DEST] Cambio detectado →', val);
        clearTimeout(tDest);
        tDest = setTimeout(function () { buscarYAsignar(val, 'destino'); }, 300);
    }

    function onRecojoChange(val) {
        if (!val || val === lastRecojo || /^--/.test(val) || val === '' || val === '0') return;
        lastRecojo = val;
        console.log('[ContainerAssign] [REC] Cambio detectado →', val);
        clearTimeout(tRecojo);
        tRecojo = setTimeout(function () { buscarYAsignar(val, 'recojo'); }, 300);
    }

    // Event listeners - Usar búsqueda dinámica
    $(document).on('change', 'select[name*="distrito"]', function () {
        var nombre = $(this).attr('name') || '';
        var valor = $(this).val();
        
        if (nombre.indexOf('destino') !== -1) {
            console.log('[ContainerAssign] [EVENT:change] Distrito DESTINO →', valor);
            onDestinoChange(valor);
        } else if (nombre.indexOf('recojo') !== -1) {
            console.log('[ContainerAssign] [EVENT:change] Distrito RECOJO →', valor);
            onRecojoChange(valor);
        }
    });

    // Select2 events
    $(document).on('select2:select', 'select[name*="distrito"]', function (e) {
        var nombre = $(this).attr('name') || '';
        var valor = (e.params && e.params.data) ? e.params.data.id : $(this).val();
        
        if (nombre.indexOf('destino') !== -1) {
            console.log('[ContainerAssign] [EVENT:select2] Distrito DESTINO →', valor);
            onDestinoChange(valor);
        } else if (nombre.indexOf('recojo') !== -1) {
            console.log('[ContainerAssign] [EVENT:select2] Distrito RECOJO →', valor);
            onRecojoChange(valor);
        }
    });

    // Click como fallback
    $(document).on('click', 'select[name*="distrito"]', function () {
        var $this = $(this);
        setTimeout(function() {
            var nombre = $this.attr('name') || '';
            var valor = $this.val();
            
            if (nombre.indexOf('destino') !== -1) {
                onDestinoChange(valor);
            } else if (nombre.indexOf('recojo') !== -1) {
                onRecojoChange(valor);
            }
        }, 100);
    });

    // Polling como respaldo (captura cambios aunque los eventos no lleguen)
    var pollingIntentando = 0;
    var pollingInfoMostrado = false;
    setInterval(function () {
        var $dest = buscarSelectDistrito('destino');
        if ($dest.length) {
            if (!pollingInfoMostrado) {
                console.log('[ContainerAssign] [POLLING] ✓ Select destino encontrado:', $dest.attr('name') || $dest.attr('id'));
                pollingInfoMostrado = true;
            }
            onDestinoChange($dest.val());
        } else if (pollingIntentando < 3) {
            pollingIntentando++;
            console.warn('[ContainerAssign] [POLLING] Select destino no encontrado (intento ' + pollingIntentando + ')');
        }

        var $rec = buscarSelectDistrito('recojo');
        if ($rec.length) {
            onRecojoChange($rec.val());
        }
    }, 200);

    /* ══════════════════════════════════════════════════════════════
     * ESTADO POR DEFECTO Y OBSERVACIONES OPCIONALES
     * ══════════════════════════════════════════════════════════════ */

    setTimeout(function () {
        // Quitar obligatoriedad de observaciones
        $('label[for*="remarks"], label:contains("Observaciones"), label:contains("Remarks")')
            .find('.required, .text-danger, span:contains("*")').remove()
            .end().css('font-weight', 'normal');
        $('textarea[name="remarks"], input[name="remarks"]').removeAttr('required');

        if (MercContainerAssign.mode !== 'add') return;
        var tipo = (tipoEnvioGlobal || getTipoEnvio()).toLowerCase();
        if (!tipo) return;

        var $sel = $('select[name="status"], select[name="wpcargo_status"], select.merc-estado-select').first();
        if (!$sel.length || $sel.val() !== '') return;

        var target = tipo === 'normal'      ? 'pendiente'     :
                    (tipo === 'express' || tipo === 'full_fitment') ? 'recepcionado' : '';
        if (!target) return;

        $sel.find('option').filter(function () {
            return $(this).text().toLowerCase().indexOf(target) !== -1;
        }).first().each(function () {
            $sel.val($(this).val()).trigger('change');
        });
    }, 1500);

    /* ══════════════════════════════════════════════════════════════
     * TOAST
     * ══════════════════════════════════════════════════════════════ */

    function toast(msg, color) {
        $('.merc-container-notif').remove();
        $('<div class="merc-container-notif">' + msg + '</div>').css({
            background: color, color: '#fff', padding: '10px 18px',
            borderRadius: '6px', fontWeight: 'bold', position: 'fixed',
            top: '20px', right: '20px', zIndex: 9999,
            boxShadow: '0 2px 8px rgba(0,0,0,.3)', fontSize: '14px'
        }).appendTo('body');
        setTimeout(function () {
            $('.merc-container-notif').fadeOut(400, function () { $(this).remove(); });
        }, 4000);
    }
});


