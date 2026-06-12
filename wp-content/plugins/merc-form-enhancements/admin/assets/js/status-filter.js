/**
 * merc-form-enhancements / status-filter.js
 *
 * Filtra las opciones del select de estado según el tipo_envio de cada fila.
 * Los motorizados (IS_DRIVER = true) ven todas las opciones del mapa.
 *
 * Variables globales requeridas (localizadas por PHP via wp_localize_script):
 *   MercStatusFilter.isDriver  (bool)
 *   MercStatusFilter.statusMap { agencia: [...], emprendedor: [...], fullfitment: [...] }
 */
/* global MercStatusFilter */
(function () {
    'use strict';

    if (typeof MercStatusFilter === 'undefined') return;

    var IS_DRIVER  = MercStatusFilter.isDriver;
    var STATUS_MAP = MercStatusFilter.statusMap;

    /* ── Helpers ── */
    function normalize(s) {
        if (!s) return '';
        s = String(s).toLowerCase();
        try { s = s.normalize('NFD').replace(/\p{Diacritic}/gu, ''); } catch (e) { /* ignore */ }
        return s.replace(/[\s_\-]+/g, '');
    }

    function detectGroup(tipo) {
        if (!tipo) return null;
        var raw = String(tipo).trim().toLowerCase();
        if (raw === 'express')                                         return 'agencia';
        if (raw === 'normal')                                          return 'emprendedor';
        if (raw === 'full_fitment' || raw === 'full-fitment' || raw === 'fullfitment') return 'fullfitment';
        var t = normalize(tipo);
        if (t.indexOf('express') !== -1 || t.indexOf('agencia') !== -1)  return 'agencia';
        if (t.indexOf('emprendedor') !== -1)                              return 'emprendedor';
        if (t.indexOf('full') !== -1 || t.indexOf('fitment') !== -1)     return 'fullfitment';
        return null;
    }

    function buildLookup(arr) {
        var m = {};
        arr.forEach(function (v) { m[normalize(v)] = true; });
        return m;
    }

    function preserveOriginal(sel) {
        if (!sel || sel.dataset.originalOptions) return;
        var opts = [];
        for (var i = 0; i < sel.options.length; i++) {
            opts.push({ v: sel.options[i].value, t: sel.options[i].text });
        }
        sel.dataset.originalOptions = JSON.stringify(opts);
    }

    function restoreAll(sel) {
        if (!sel || !sel.dataset.originalOptions) return;
        try {
            var opts = JSON.parse(sel.dataset.originalOptions);
            sel.innerHTML = '';
            opts.forEach(function (o) {
                var el = document.createElement('option');
                el.value = o.v; el.text = o.t;
                sel.appendChild(el);
            });
        } catch (e) { /* ignore */ }
    }

    function filterForGroup(sel, group) {
        if (!sel) return;
        if (!group) { restoreAll(sel); return; }

        var allowed = STATUS_MAP[group] || [];
        var lookup  = buildLookup(allowed);

        if (IS_DRIVER) {
            for (var i = 0; i < sel.options.length; i++) {
                lookup[normalize(sel.options[i].text || sel.options[i].value)] = true;
            }
        }

        var currentVal  = sel.value;
        var currentText = (sel.options[sel.selectedIndex] || {}).text || '';

        preserveOriginal(sel);
        var original = JSON.parse(sel.dataset.originalOptions || '[]');
        var newOpts  = original.filter(function (o) {
            var key = normalize(o.t || o.v);
            return lookup[key]
                || (currentVal  && String(o.v) === String(currentVal))
                || (currentText && normalize(o.t) === normalize(currentText));
        });

        if (newOpts.length === 0) { restoreAll(sel); return; }

        var selIdx = -1;
        sel.innerHTML = '';
        newOpts.forEach(function (o, idx) {
            var el = document.createElement('option');
            el.value = o.v; el.text = o.t;
            sel.appendChild(el);
            if (currentVal  && String(o.v) === String(currentVal))       selIdx = idx;
            if (selIdx === -1 && currentText && normalize(o.t) === normalize(currentText)) selIdx = idx;
        });
        if (selIdx >= 0) sel.selectedIndex = selIdx;
    }

    /* ── Aplicar filtros ── */
    function tryApply() {
        var selects = Array.prototype.slice.call(document.querySelectorAll(
            'select.merc-estado-select,select[name="merc-estado-select"],' +
            'select#merc-estado-select,select[name="status"],select[name="wpcargo_status"]'
        ));
        if (!selects || selects.length === 0) return false;

        selects.forEach(function (statusSel) {
            var tipo = null;
            try {
                var tr = statusSel.closest('tr');
                if (tr) {
                    var td = tr.querySelector('[data-tipo-envio]');
                    if (td) tipo = td.getAttribute('data-tipo-envio');
                }
            } catch (e) { /* ignore */ }

            if (!tipo) {
                var f = document.querySelector('[name="tipo_envio"]') || document.getElementById('tipo_envio_hidden');
                if (f) tipo = f.value || f.textContent;
            }
            if (!tipo) tipo = new URLSearchParams(window.location.search).get('type');

            filterForGroup(statusSel, detectGroup(tipo));
        });
        return true;
    }

    /* ── Init ── */
    var attempts = 0, maxAttempts = 12;
    var iv = setInterval(function () {
        attempts++;
        if (tryApply() || attempts >= maxAttempts) clearInterval(iv);
    }, 500);

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        if (t.name === 'tipo_envio' || t.id === 'tipo_envio_hidden' || t.name === 'type') tryApply();
    }, true);

}());

