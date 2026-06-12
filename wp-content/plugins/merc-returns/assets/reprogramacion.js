/**
 * Reprogramación - Script Frontend
 * 
 * Aplica atributos data-reprogramado a las filas de envíos
 * Para que el CSS las pinte de naranja
 */

jQuery(document).ready(function ($) {
    'use strict';

    console.log('[Reprogramación] Script cargado');

    /**
     * Marcar filas reprogramadas en la tabla
     */
    function marcarFilasReprogramadas() {
        // Selector general para filas de envíos
        var $filas = jQuery('tr[data-shipment-id], tr[data-envio-id], .merc-shipmentslist-row, .merc-shipment-row');
        
        console.log('[Reprogramación] Revisando ' + $filas.length + ' filas para marcar reprogramadas');
        
        $filas.each(function (idx) {
            var $fila = jQuery(this);
            var shipmentId = $fila.attr('data-shipment-id') || $fila.attr('data-envio-id') || $fila.find('[data-shipment-id]').attr('data-shipment-id');
            
            // Buscar el estado en la fila
            var $estadoCell = $fila.find('td[data-estado], td.status, .merc-estado, .shipment-status');
            var estadoText = $estadoCell.text() || '';
            
            // Verificar si es REPROGRAMADO
            if (estadoText.indexOf('REPROGRAMADO') !== -1 || $fila.attr('data-status') === 'REPROGRAMADO') {
                console.log('[Reprogramación]   ✓ Fila #' + (idx + 1) + ' es REPROGRAMADA - aplicando estilos');
                $fila.attr('data-reprogramado', '1');
                $fila.attr('data-estado-reprogramado', '1');
                $fila.addClass('reprogramado-row');
                
                if (shipmentId) {
                    console.log('[Reprogramación]     └─ Shipment ID:', shipmentId);
                }
            }
        });
    }

    // Ejecutar al cargar
    marcarFilasReprogramadas();

    // Re-ejecutar cuando cambia la tabla (AJAX, etc.)
    $(document).on('wpcargo_table_updated merc_table_updated ajax:success', function () {
        console.log('[Reprogramación] Tabla actualizada, re-evaluando filas...');
        setTimeout(marcarFilasReprogramadas, 500);
    });

    // También monitorear cambios cada 2 segundos (fallback)
    setInterval(marcarFilasReprogramadas, 2000);

    /**
     * Agregar evento cuando el estado cambia a REPROGRAMADO
     */
    $(document).on('merc:estado-cambiado', function (e, data) {
        if (data && data.estado === 'REPROGRAMADO') {
            console.log('[Reprogramación] Estado cambió a REPROGRAMADO para shipment:', data.shipment_id);
            
            // Encontrar la fila y marcarla
            var $fila = jQuery('tr[data-shipment-id="' + data.shipment_id + '"]');
            if ($fila.length) {
                $fila.attr('data-reprogramado', '1');
                $fila.attr('data-estado-reprogramado', '1');
                $fila.addClass('reprogramado-row pulse');
                
                // Remover animación después de 5 segundos
                setTimeout(function () {
                    $fila.removeClass('pulse');
                }, 5000);
            }
        }
    });

    console.log('[Reprogramación] Script listo para marcar filas reprogramadas');
});
