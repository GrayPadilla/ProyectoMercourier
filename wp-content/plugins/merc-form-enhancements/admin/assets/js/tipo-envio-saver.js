/**
 * merc-form-enhancements / tipo-envio-saver.js
 *
 * - Inyecta campo oculto tipo_envio en el formulario.
 * - Aplica bloqueo visual + submit block si tipoBloqueado = true.
 *
 * Variables globales requeridas (localizadas por PHP via wp_localize_script):
 *   MercFormSaver.tipoEnvio
 *   MercFormSaver.bloqueado   (bool)
 *   MercFormSaver.mensaje     (string)
 */
/* global MercFormSaver */
(function ($) {
    'use strict';

    if (typeof MercFormSaver === 'undefined') return;

    var tipoEnvio    = MercFormSaver.tipoEnvio;
    var tipoBloqueado = MercFormSaver.bloqueado;
    var mensaje       = MercFormSaver.mensaje;

    /* ── Inyectar campo oculto ── */
    function agregarCampoTipo() {
        var $form = $('form');
        if ($form.length > 0 && $('#tipo_envio_hidden').length === 0) {
            $form.append(
                '<input type="hidden" name="tipo_envio" id="tipo_envio_hidden" value="' +
                $('<span>').text(tipoEnvio).html() + '">'
            );
            return true;
        }
        return false;
    }

    setTimeout(agregarCampoTipo, 1500);
    var intentos = 0;
    var iv = setInterval(function () {
        intentos++;
        if (agregarCampoTipo() || intentos >= 20) clearInterval(iv);
    }, 500);

    /* ── Bloqueo ── */
    if (tipoBloqueado) {
        $('form').on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            alert('🔒 TIPO DE ENVÍO BLOQUEADO\n\n' + mensaje);
            return false;
        });

        setTimeout(function () {
            $('button[type="submit"],input[type="submit"]')
                .prop('disabled', true)
                .css({ opacity: '0.5', cursor: 'not-allowed', 'pointer-events': 'none' })
                .attr('title', 'Este tipo de envío está bloqueado');
        }, 2000);

        setTimeout(function () {
            $('body').prepend(
                '<div id="merc-bloqueo-banner" style="position:fixed;top:0;left:0;right:0;' +
                'background:#f44336;color:white;padding:15px;text-align:center;' +
                'z-index:9999;font-weight:bold;">' +
                '🔒 ESTE TIPO DE ENVÍO ESTÁ BLOQUEADO - NO PUEDES CREAR ENVÍOS</div>'
            );
        }, 1000);
    }

}(jQuery));

