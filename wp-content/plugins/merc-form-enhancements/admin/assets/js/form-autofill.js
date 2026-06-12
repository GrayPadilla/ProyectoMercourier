/**
 * merc-form-enhancements / form-autofill.js
 *
 * - Autocompleta campos del remitente desde datos del usuario.
 * - Elimina el campo "ubicación" innecesario.
 * - Si el tipo es "express", cambia el estado a RECEPCIONADO.
 *
 * Variables globales requeridas (localizadas por PHP via wp_localize_script):
 *   MercFormAutofill.userData  { nombre, telefono, distrito, direccion, email, empresa, link_maps }
 *   MercFormAutofill.tipoEnvio (string)
 */
/* global MercFormAutofill */
(function () {
    'use strict';

    if (typeof MercFormAutofill === 'undefined') return;

    var ud        = MercFormAutofill.userData;
    var tipoEnvio = MercFormAutofill.tipoEnvio;

    /* ── Trigger change ── */
    function trigger(el) {
        if (!el) return;
        el.dispatchEvent(new Event('change', { bubbles: true }));
        if (typeof jQuery !== 'undefined') jQuery(el).trigger('change');
    }

    /* ── Autocompletar ── */
    function autocompletar() {
        var map = {
            nombre:    document.querySelector('[name="wpcargo_shipper_name"]'),
            telefono:  document.querySelector('[name="wpcargo_shipper_phone"]'),
            distrito:  document.querySelector('[name="wpcargo_distrito_recojo"]'),
            direccion: document.querySelector('[name="wpcargo_shipper_address"]'),
            email:     document.querySelector('[name="wpcargo_shipper_email"]'),
            empresa:   document.querySelector('[name="wpcargo_tiendaname"]'),
            link_maps: document.querySelector('[name="link_maps_remitente"]')
        };
        var count = 0;

        ['nombre', 'telefono', 'direccion', 'email', 'empresa', 'link_maps'].forEach(function (k) {
            if (map[k] && ud[k]) { map[k].value = ud[k]; trigger(map[k]); count++; }
        });

        // Distrito es select
        if (map.distrito && ud.distrito) {
            var opts = map.distrito.options;
            for (var i = 0; i < opts.length; i++) {
                if (opts[i].value === ud.distrito || opts[i].text.trim() === ud.distrito) {
                    map.distrito.selectedIndex = i;
                    trigger(map.distrito);
                    count++;
                    break;
                }
            }
        }

        return count > 0;
    }

    /* ── Eliminar campo ubicación ── */
    function eliminarCampoUbicacion() {
        var el = document.querySelector('#location, input[name="location"]');
        if (el) {
            var parent = el.closest('.form-group, .col-md-12, .col-md-6, div[class*="col"]');
            if (parent) parent.remove();
        }
    }

    /* ── Cambiar estado a RECEPCIONADO ── */
    function cambiarEstadoRecepcionado() {
        var sel = document.querySelector(
            'select.merc-estado-select,select[name="merc-estado-select"],' +
            'select[name="status"],select[name="wpcargo_status"]'
        );
        if (!sel) {
            var all = document.querySelectorAll('select');
            for (var j = 0; j < all.length; j++) {
                for (var k = 0; k < all[j].options.length; k++) {
                    if (all[j].options[k].text.toUpperCase().includes('RECEPCIONADO')) {
                        sel = all[j]; break;
                    }
                }
                if (sel) break;
            }
        }
        if (!sel) return false;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].text.trim().toUpperCase() === 'RECEPCIONADO' ||
                sel.options[i].value.trim().toUpperCase() === 'RECEPCIONADO') {
                sel.selectedIndex = i;
                trigger(sel);
                return true;
            }
        }
        return false;
    }

    /* ── Init ── */
    setTimeout(function () {
        eliminarCampoUbicacion();
        autocompletar();
        if (tipoEnvio === 'express') {
            setTimeout(cambiarEstadoRecepcionado, 500);
            setTimeout(cambiarEstadoRecepcionado, 1000);
            setTimeout(cambiarEstadoRecepcionado, 1500);
        }
    }, 1000);

    var intentos = 0;
    var iv = setInterval(function () {
        intentos++;
        if (autocompletar() || intentos >= 10) clearInterval(iv);
    }, 800);

}());

