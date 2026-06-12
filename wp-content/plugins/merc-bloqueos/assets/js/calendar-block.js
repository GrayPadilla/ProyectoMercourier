jQuery(document).ready(function($) {

    // Guard 1: si el objeto WCMAS existe, estamos en la página de Masivos.
    // El script de PHP ya no debería cargar aquí (guard en merc-bloqueos.php),
    // pero esta es la segunda línea de defensa.
    if (typeof window.WCMAS !== 'undefined') {
        return;
    }

    var urlParams = new URLSearchParams(window.location.search);
    var isBulk = window.location.pathname.indexOf('importacion-masiva') !== -1 || 
                 window.location.pathname.indexOf('bulk-import') !== -1 || 
                 window.location.pathname.indexOf('envios-masivos') !== -1;
    
    // Guard 2: detección por pathname (mantener como fallback)
    if (isBulk) {
        return;
    }

    var tipoEnvio = urlParams.get('type') || 'normal';

    /**
     * Calcula la próxima fecha disponible (YYYY-MM-DD) dado el estado de bloqueos.
     */
    function calcularProximaFecha(bloquear_hoy, fechas_bloqueadas, domingos_desbloqueados) {
        var date = new Date();
        date.setHours(0, 0, 0, 0);
        if (bloquear_hoy) {
            date.setDate(date.getDate() + 1);
        }

        for (var i = 0; i < 60; i++) {
            var year  = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var day   = ('0' + date.getDate()).slice(-2);
            var ds    = year + '-' + month + '-' + day;

            // Domingo (0)?
            if (date.getDay() === 0) {
                if ((domingos_desbloqueados || []).indexOf(ds) === -1) {
                    date.setDate(date.getDate() + 1);
                    continue; 
                }
            }

            // Fecha especial bloqueada?
            if ((fechas_bloqueadas || []).indexOf(ds) !== -1) {
                date.setDate(date.getDate() + 1);
                continue;
            }

            return ds; 
        }
        return ''; 
    }

    $.post(mercBloqueos.ajax_url, {
        action: 'merc_bloqueo_info',
        tipo: tipoEnvio
    }, function(res) {
        if (!res.success) return;
        
        var data = res.data;
        console.log('[MERC] Bloqueo Info:', data);

        // Función para aplicar bloqueo total
        function aplicarBloqueoTotal() {
            // Seleccionamos solo los contenedores del formulario individual de WPCargo.
            // IMPORTANTE: NO incluir #wcmas-grid-wrap ni #wcmas-toolbar — son IDs del
            // módulo de Envíos Masivos y no deben ser objetivo de este script.
            var forms = $('#wpcfe-add-shipment-form, .wpcargo-frontend-bulk-import, .wpcargo-bulk-import');
            if (forms.length > 0) {
                forms.hide();
                if ($('.merc-bloqueo-total-alert').length === 0) {
                    $('<div class="alert alert-danger merc-bloqueo-total-alert" style="margin-top:20px; font-size:16px; padding: 20px; background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; border-radius: 8px;">Tu cuenta se encuentra bloqueada para realizar envíos. Por favor comunícate con administración.</div>').insertBefore(forms.first());
                }
                return true;
            }
            return false;
        }

        if (data.bloqueo_total) {
            if (!aplicarBloqueoTotal()) {
                var totalInterval = setInterval(function() {
                    if (aplicarBloqueoTotal()) clearInterval(totalInterval);
                }, 500);
            }
            return;
        }

        var domingos_desbloqueados = data.domingos_desbloqueados || [];
        var fechas_bloqueadas = data.fechas_bloqueadas || [];

        // Configuración de deshabilitación
        var disableConfig = [
            function(date) {
                var year = date.getFullYear();
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                var day = ("0" + date.getDate()).slice(-2);
                var ds = year + "-" + month + "-" + day;

                if (date.getDay() === 0) {
                    if (domingos_desbloqueados.indexOf(ds) !== -1) return false;
                    return true; 
                }
                if (fechas_bloqueadas.indexOf(ds) !== -1) return true;
                return false;
            }
        ];

        var todayObj = new Date();
        todayObj.setHours(0,0,0,0);
        var minDate = data.bloquear_hoy ? new Date(todayObj.getTime() + 24 * 60 * 60 * 1000) : "today";
        
        if (data._debug_reason === 'is_admin') {
            console.log('[MERC] Modo Admin: Desbloqueando calendario.');
            minDate = null;
        }

        var proximaFecha = (data._debug_reason === 'is_admin') ? null : calcularProximaFecha(data.bloquear_hoy, fechas_bloqueadas, domingos_desbloqueados);

        function aplicarCamposObligatorios() {
            var $form = $('#wpcfe-add-shipment-form');
            if ($form.length === 0) return false;

            $form.find('input, select, textarea')
                .not('[type="hidden"], [type="button"], [type="submit"], [type="checkbox"], [type="radio"], [readonly]')
                .not('[name="wpcargo_comments"], [name="wpcargo_comments[]"]')
                .each(function() {
                    $(this).attr('required', 'required');
                });

            if (!$form.data('merc-submit-bound')) {
                $form.data('merc-submit-bound', true);
                $form.on('submit', function(e) {
                    var fechaInput = $form.find('input[name="wpcargo_pickup_date_picker"], input[name="calendarenvio"]').first();
                    var fechaVal = fechaInput.val();
                    if (!fechaVal || !fechaVal.trim()) {
                        e.preventDefault();
                        fechaInput.addClass('is-invalid');
                        if ($('#merc-fecha-error-msg').length === 0) {
                            fechaInput.after('<div id="merc-fecha-error-msg" class="invalid-feedback" style="display:block;color:#dc3545;font-size:13px;margin-top:4px;">⚠️ La fecha de envío es obligatoria.</div>');
                        }
                        fechaInput[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                });
            }
            return true;
        }

        function buscarYAplicarReglas() {
            var inputs = $('input[name="wpcargo_pickup_date_picker"], input[name="calendarenvio"], .wpcfe-datepicker, .wpccf-datepicker');
            if (inputs.length === 0) return false;

            inputs.each(function() {
                var input = $(this);
                var fp = input[0]._flatpickr || input[0].flatpickr;
                
                if (fp) {
                    if (data._debug_reason !== 'is_admin') {
                        fp.set('disable', disableConfig);
                    } else {
                        fp.set('disable', []);
                    }
                    fp.set('minDate', minDate);
                    
                    if (!fp.selectedDates.length && proximaFecha) {
                        fp.setDate(proximaFecha, true);
                    }
                    input.attr('required', 'required');
                } else {
                    // Pickadate fallback
                    var picker = input.pickadate ? input.pickadate('picker') : null;
                    if (picker) {
                        if (data._debug_reason !== 'is_admin') {
                            picker.set('disable', false);
                            picker.set('disable', disableConfigPickadate());
                            picker.set('min', data.bloquear_hoy ? 1 : true);
                        } else {
                            picker.set('min', false);
                            picker.set('disable', false);
                        }
                        if (!input.val() && proximaFecha) {
                            var p = proximaFecha.split('-');
                            picker.set('select', new Date(p[0], p[1]-1, p[2]));
                        }
                        input.attr('required', 'required');
                    }
                }
            });
            aplicarCamposObligatorios();
            return true;
        }

        function disableConfigPickadate() {
            var conf = [1];
            fechas_bloqueadas.forEach(function(f) { var p = f.split('-'); conf.push([parseInt(p[0]), parseInt(p[1])-1, parseInt(p[2])]); });
            domingos_desbloqueados.forEach(function(f) { var p = f.split('-'); conf.push([parseInt(p[0]), parseInt(p[1])-1, parseInt(p[2]), 'inverted']); });
            return conf;
        }

        if (!buscarYAplicarReglas()) {
            var att = 0;
            var poll = setInterval(function() {
                att++;
                if (buscarYAplicarReglas() || att > 20) clearInterval(poll);
            }, 500);
        }
    });
});

