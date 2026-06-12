<?php
// Centralizar logs diarios: redirigir error_log a wp-content/merc_logs/merc-debug-YYYY-MM-DD.log
if (!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
$merc_log_dir = WP_CONTENT_DIR . '/merc_logs';
if (!file_exists($merc_log_dir)) {
    @mkdir($merc_log_dir, 0755, true);
}
$today_log = $merc_log_dir . '/merc-debug-' . date('Y-m-d') . '.log';
@ini_set('error_log', $today_log);
// Marcar inicio en el log diario
if ( function_exists('error_log') ) {
    error_log('🔔 [MERC_LOG] Inicializado logger diario: ' . $today_log);
}
require_once get_stylesheet_directory() . '/merc-motorizado-historial.php';
// Disparar acción pública cuando se crea una penalidad (para que otros módulos la integren)
do_action('merc_penalty_module_loaded');
// ── Ocultar barra de admin para todos excepto administradores ──
add_filter('show_admin_bar', 'merc_ocultar_admin_bar');
function merc_ocultar_admin_bar($show) {
    if (current_user_can('manage_options')) return true;
    return false;
}

// ── Redirigir a wpcargo_driver fuera del wp-admin ──
add_action('admin_init', 'merc_redirigir_drivers_fuera_de_admin');
function merc_redirigir_drivers_fuera_de_admin() {
    if (current_user_can('manage_options')) return; // Admins pasan libre

    // Permitir solicitudes AJAX (necesarias para WPCargo)
    if (defined('DOING_AJAX') && DOING_AJAX) return;

    // Redirigir al dashboard de Mercourier
    wp_redirect(home_url('/dashboard/'));
    exit;
}

// Remove native date filter
remove_action( 'wpcfe_after_shipment_filters', 'wpcfe_shipment_created_date_filter_callback', 100 );
remove_filter( 'wpcfe_dashboard_arguments', 'wpcfe_shipment_created_date_quuery_args_callback' );

// ═══════════════════════════════════════════════════════════════════════════════════
// CSS GLOBAL RESPONSIVE PARA MODALES (Bottom Sheet en Móvil)
// ═══════════════════════════════════════════════════════════════════════════════════
add_action('wp_head', function() {
    echo '<style>
    /* Bottom Sheet Modal para Móviles (< 768px) */
    @media (max-width: 767.98px) {
        /* Todos los modales Bootstrap se convierten en bottom sheet */
        .modal-dialog {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            margin: 0;
            max-width: 100%;
            height: auto;
            max-height: 85vh;
            width: 100%;
            border-radius: 20px 20px 0 0;
            pointer-events: auto;
        }
        
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: translateY(100%);
        }
        
        .modal.show .modal-dialog {
            transform: translateY(0);
        }
        
        .modal-content {
            border-radius: 20px 20px 0 0;
            border: none;
            box-shadow: 0 -5px 40px rgba(0, 0, 0, 0.16);
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Agregar barra deslizable visual al top del modal */
        .modal-content::before {
            content: "";
            display: block;
            width: 50px;
            height: 4px;
            background: #d0d0d0;
            border-radius: 2px;
            margin: 12px auto 0;
            flex-shrink: 0;
        }
        
        /* Header del modal */
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            flex-shrink: 0;
        }
        
        .modal-header .close {
            position: absolute;
            right: 16px;
            top: 12px;
            opacity: 0.6;
            font-size: 28px;
            font-weight: 300;
            line-height: 1;
        }
        
        /* Body del modal (scrolleable) */
        .modal-body {
            overflow-y: auto;
            padding: 20px;
            flex-grow: 1;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Footer del modal */
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #f0f0f0;
            flex-shrink: 0;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Botones en footer */
        .modal-footer .btn {
            flex: 1;
            min-width: 100px;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        /* Efecto al cerrar */
        .modal.fade.show .modal-dialog {
            transition: transform 0.3s ease-out;
        }
        
        /* Backdrop (fondo oscuro) */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.4);
        }
    }
    
    /* En escritorio, mantener modales normales */
    @media (min-width: 768px) {
        .modal-dialog {
            max-width: 500px;
        }
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════════════ */
    /* SWAL2 (SweetAlert2) - Asegurar que siempre esté por encima de spinners */
    /* ═══════════════════════════════════════════════════════════════════════════════════ */
    .swal2-container {
        z-index: 9999999 !important;
    }
    
    .swal2-backdrop {
        z-index: 9999998 !important;
    }
    
    .swal2-shown {
        z-index: 9999999 !important;
    }
    
    /* Bajar z-index de spinners/loaders para que no bloqueen SweetAlert */
    .wpcfe-spinner,
    .spinner,
    .loading-spinner {
        z-index: 100 !important;
    }
    
    /* Bootstrap spinners */
    .spinner-border,
    .spinner-grow {
        z-index: 100 !important;
    }
    
    </style>';
});

// WPCargo date format (visual only)
add_filter( 'wpcargo_date_format', function($format){
    return 'd/m/Y';
});

// WPCargo time format
add_filter( 'wpcargo_time_format', function($format){
    return 'H:i';
});

// WPCFE custom field date format (datepicker)
add_filter( 'wpcfe_date_format', function($format){
    return 'dd/mm/yyyy';
});

// Enqueue scripts en ADMIN
add_action('admin_enqueue_scripts', function($hook){
    // Solo en páginas de WPCargo
    if (strpos($hook, 'wpcargo') === false && strpos($hook, 'wpcfe') === false) {
        return;
    }
    // Flatpickr (alternativa moderna a jQuery UI Datepicker)
    wp_enqueue_script(
        'flatpickr-js',
        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
        [],
        '4.6.13',
        true
    );
    wp_enqueue_style(
        'flatpickr-css',
        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
        [],
        '4.6.13'
    );
});

// Enqueue scripts en FRONTEND (para dashboard de WPCargo)
add_action('wp_enqueue_scripts', function(){
    // Cargar Flatpickr para usuarios logueados
    if (is_user_logged_in()) {
        // Flatpickr (alternativa moderna a jQuery UI Datepicker)
        wp_enqueue_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );
        wp_enqueue_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );
    }
});

// Migración para añadir fecha_caja a merc_liquidations
add_action('admin_init', function() {
    if (get_option('merc_migration_fecha_caja_done')) {
        return;
    }
    
    error_log("Iniciando migración automática de merc_liquidations para fecha_caja...");
    $users = get_users(['fields' => 'ID', 'number' => -1]);
    $updated_users = 0;
    
    foreach ($users as $uid) {
        $history = get_user_meta($uid, 'merc_liquidations', true);
        if (!is_array($history) || empty($history)) {
            continue;
        }
        
        $modified = false;
        foreach ($history as &$entry) {
            if (!isset($entry['fecha_caja']) && isset($entry['date'])) {
                $entry['fecha_caja'] = date('Y-m-d', strtotime($entry['date']));
                $modified = true;
            }
        }
        unset($entry);
        
        if ($modified) {
            update_user_meta($uid, 'merc_liquidations', $history);
            $updated_users++;
        }
    }
    
    update_option('merc_migration_fecha_caja_done', true);
    error_log("Migración completada. Usuarios actualizados: {$updated_users}");
});

add_action('wpcfe_after_shipment_filters', function(){
    // Obtener valores de los parámetros GET si existen
    $start_value = isset($_GET['shipping_date_start']) ? sanitize_text_field($_GET['shipping_date_start']) : '';
    $end_value = isset($_GET['shipping_date_end']) ? sanitize_text_field($_GET['shipping_date_end']) : '';
    
    // Convertir de ISO a d/m/Y para mostrar
    function iso_to_dmy($iso) {
        if (empty($iso)) return '';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) {
            return $m[3] . '/' . $m[2] . '/' . $m[1];
        }
        return $iso;
    }
    
    $start_display = iso_to_dmy($start_value);
    $end_display = iso_to_dmy($end_value);
    
    // NOTA: JavaScript se encargará de establecer la fecha de hoy por defecto
    // cuando los campos estén vacíos en la carga de página
    ?>
    <div class="form-group wpcfe-filter p-0 mx-1">
        <label><strong><?php _e('Fecha de Envío', 'wpccustom-tu'); ?></strong></label><br>
        
        <!-- Campos visibles (formato dd/mm/yyyy) -->
        <input type="text"
               id="shipping_date_start_display"
               class="form-control px-2 py-1 wpcfe-datepicker"
               style="width:110px; display:inline-block; font-weight: 500;"
               placeholder="DD/MM/YYYY"
               value="<?php echo esc_attr($start_display); ?>" autocomplete="off">
               
        <input type="text"
               id="shipping_date_end_display"
               class="form-control px-2 py-1 mx-1 wpcfe-datepicker"
               style="width:110px; display:inline-block; font-weight: 500;"
               placeholder="DD/MM/YYYY"
               value="<?php echo esc_attr($end_display); ?>"
               autocomplete="off">
        
        <!-- Campos ocultos (formato ISO para el backend) -->
        <input type="hidden" 
               name="shipping_date_start" 
               id="shipping_date_start" 
               value="<?php echo esc_attr($start_value); ?>">
               
        <input type="hidden" 
               name="shipping_date_end" 
               id="shipping_date_end" 
               value="<?php echo esc_attr($end_value); ?>">
    </div>
    
    <!-- Cargar Flatpickr directamente -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    
    <style>
        .flatpickr-month {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            text-align: center;
        }
        .flatpickr-months {
            padding: 15px 0;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($){
        // Función para convertir dd/mm/yyyy a yyyy-mm-dd
        function dmyToIso(dmy){
            if(!dmy) return '';
            var parts = dmy.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if(!parts) return '';
            return parts[3] + '-' + parts[2] + '-' + parts[1];
        }
        
        // Función para obtener hoy en formato dd/mm/yyyy
        function getTodayDmy() {
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var yyyy = today.getFullYear();
            return dd + '/' + mm + '/' + yyyy;
        }
        
        // Función para obtener hoy en formato yyyy-mm-dd
        function getTodayIso() {
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            return yyyy + '-' + mm + '-' + dd;
        }
        
        // Esperar a que Flatpickr se cargue completamente
        function initDatePickers() {
            if (typeof flatpickr === 'undefined') {
                console.warn('⏳ Esperando Flatpickr...');
                setTimeout(initDatePickers, 200);
                return;
            }
            
            console.log('✅ Flatpickr disponible, inicializando campos...');
            
            var $startDisplay = $('#shipping_date_start_display');
            var $startHidden = $('#shipping_date_start');
            var $endDisplay = $('#shipping_date_end_display');
            var $endHidden = $('#shipping_date_end');
            
            // Destruir instancias previas si existen
            var startEl = document.getElementById('shipping_date_start_display');
            var endEl = document.getElementById('shipping_date_end_display');
            
            if (startEl && startEl._flatpickr) startEl._flatpickr.destroy();
            if (endEl && endEl._flatpickr) endEl._flatpickr.destroy();
            
            // Si los campos están vacíos, llenarlos con hoy
            var todayDmy = getTodayDmy();
            var todayIso = getTodayIso();
            
            if ($startDisplay.val() === '') {
                $startDisplay.val(todayDmy);
                $startHidden.val(todayIso);
                console.log('📅 Campo INICIO establecido a hoy: ' + todayDmy + ' (hidden: ' + todayIso + ')');
            }
            
            if ($endDisplay.val() === '') {
                $endDisplay.val(todayDmy);
                $endHidden.val(todayIso);
                console.log('📅 Campo FIN establecido a hoy: ' + todayDmy + ' (hidden: ' + todayIso + ')');
            }
            
            // Configuración en español
            var config = {
                dateFormat: 'd/m/Y',
                allowInput: true,
                monthSelectorType: 'static',
                locale: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                        longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
                    },
                    months: {
                        shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                        longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
                    }
                }
            };
            
            // Inicializar campo de inicio
            if (startEl) {
                flatpickr(startEl, Object.assign({}, config, {
                    onChange: function(selectedDates, dateStr) {
                        $startHidden.val(dmyToIso(dateStr));
                        console.log('🔄 Inicio cambiado a: ' + dateStr + ' → ' + dmyToIso(dateStr));
                    }
                }));
                console.log('✅ Calendario INICIO inicializado');
            }
            
            // Inicializar campo de fin
            if (endEl) {
                flatpickr(endEl, Object.assign({}, config, {
                    onChange: function(selectedDates, dateStr) {
                        $endHidden.val(dmyToIso(dateStr));
                        console.log('🔄 Fin cambiado a: ' + dateStr + ' → ' + dmyToIso(dateStr));
                    }
                }));
                console.log('✅ Calendario FIN inicializado');
            }
        }
        
        // Iniciar
        initDatePickers();
        
        // También actualizar cuando se cambie manualmente
        $('#shipping_date_start_display').on('change', function(){
            var isoDate = dmyToIso($(this).val());
            $('#shipping_date_start').val(isoDate);
            console.log('✏️ INICIO manual: ' + $(this).val() + ' → ' + isoDate);
        });
        
        $('#shipping_date_end_display').on('change', function(){
            var isoDate = dmyToIso($(this).val());
            $('#shipping_date_end').val(isoDate);
            console.log('✏️ FIN manual: ' + $(this).val() + ' → ' + isoDate);
        });
    });
    </script>
    <?php
});

// Cargar el mismo script en el footer del admin para que esté disponible dentro del modal del backend
add_action('admin_footer', function() {
    if ( ! is_user_logged_in() ) return;
    ?>
    <script>
    jQuery(function($){

        // Helper JS: number_format similar a PHP's number_format(number, decimals)
        function number_format(number, decimals) {
            var n = Number(number);
            if (!isFinite(n)) n = 0;
            var prec = isNaN(decimals) ? 0 : Math.abs(decimals);
            var parts = n.toFixed(prec).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }
        // Función para obtener hoy en formato yyyy-mm-dd
        function getTodayIso() {
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            return yyyy + '-' + mm + '-' + dd;
        }
        
        // Al cargar la página del dashboard de envíos, auto-aplicar filtro de hoy si no hay filtros establecidos
        if ($('.wpcfe-dashboard').length > 0) {
            var urlParams = new URLSearchParams(window.location.search);
            var hasDateFilter = urlParams.has('shipping_date_start') || urlParams.has('shipping_date_end');
            
            if (!hasDateFilter) {
                console.log('📅 Sin filtro de fecha, aplicando hoy automáticamente...');
                var todayIso = getTodayIso();
                
                // Actualizar la URL con los parámetros de hoy
                var currentUrl = window.location.href;
                var separator = currentUrl.indexOf('?') > -1 ? '&' : '?';
                var newUrl = currentUrl + separator + 'shipping_date_start=' + todayIso + '&shipping_date_end=' + todayIso;
                
                // Redirigir a la página con los filtros aplicados
                window.location.href = newUrl;
            }
        }
        
        function checkAndBlockShipmentTypes() {
            var modal = $(".modal:contains('Selecciona el tipo de envío'), .modal:contains('Selecciona el tipo de envio')");
            if (modal.length === 0) return;
            $.post(ajaxurl, { action: 'merc_get_user_shipment_types_status' }, function(resp){
                if(!resp.success || !resp.data) return;
                var now = resp.data.now;
                var types = resp.data.types;
                if((types.normal.count === 0 && now >= '10:00') || (types.normal.count > 0 && types.normal.all_collected)) {
                    modal.find(".modal-body .card:contains('MERC EMPRENDEDOR')").css({opacity:0.5, pointerEvents:'none'});
                }
                if((types.express.count === 0 && now >= '12:30') || (types.express.count > 0 && now >= '13:00')) {
                    modal.find(".modal-body .card:contains('MERC AGENCIA')").css({opacity:0.5, pointerEvents:'none'});
                }
                if((types.full_fitment.count === 0 && now >= '11:30') || (types.full_fitment.count > 0 && now >= '12:15')) {
                    modal.find(".modal-body .card:contains('MERC FULL FITMENT')").css({opacity:0.5, pointerEvents:'none'});
                }
            });
        }
        var observer = new MutationObserver(function(mutations){
            mutations.forEach(function(m){
                if($(m.addedNodes).find(".modal:contains('Selecciona el tipo de envío'), .modal:contains('Selecciona el tipo de envio')").length > 0){
                    setTimeout(checkAndBlockShipmentTypes, 300);
                }
            });
        });
        observer.observe(document.body, {childList:true, subtree:true});
    });
    </script>
    <?php
});

// Definir helper `number_format` en frontend si no existe (evita ReferenceError en panel-cliente)
add_action('wp_footer', function() {
    if ( ! is_user_logged_in() ) return; // solo usuarios autenticados necesitan este helper en paneles
    ?>
    <script>
    (function(){
        if (typeof window.number_format === 'undefined') {
            window.number_format = function(number, decimals) {
                var n = Number(number);
                if (!isFinite(n)) n = 0;
                var prec = isNaN(decimals) ? 0 : Math.abs(decimals);
                var parts = n.toFixed(prec).split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return parts.join('.');
            };
        }
    })();
    </script>
    <?php
});

// FILTRO ANTIGUO DE FECHA - DESHABILITADO (ahora está consolidado más abajo)
// add_filter('wpcfe_dashboard_arguments', function( $args ) {
//     $from = isset($_GET['shipping_date_start']) ? sanitize_text_field($_GET['shipping_date_start']) : '';
//     $to = isset($_GET['shipping_date_end']) ? sanitize_text_field($_GET['shipping_date_end']) : '';
//     
//     // Validar que las fechas estén en formato correcto (YYYY-MM-DD)
//     if ($from && $to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
//         global $wpdb;
//         
//         // Buscar IDs de envíos que tengan fecha en cualquiera de los meta keys posibles
//         $shipment_ids = $wpdb->get_col($wpdb->prepare("
//             SELECT DISTINCT p.ID 
//             FROM {$wpdb->posts} p
//             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
//             WHERE p.post_type = 'wpcargo_shipment'
//             AND pm.meta_key IN ('wpcargo_pickup_date_picker', 'wpcargo_pickup_date', 'calendarenvio', 'wpcargo_fecha_envio')
//             AND pm.meta_value BETWEEN %s AND %s
//         ", $from, $to));
//         
//         if (!empty($shipment_ids)) {
//             $args['post__in'] = $shipment_ids;
//         } else {
//             // Si no hay resultados, forzar búsqueda vacía
//             $args['post__in'] = array(0);
//         }
//     }
//     
//     return $args;
// }, 20);

// Mantener el filtro antiguo por compatibilidad (pero con menor prioridad)
add_filter('wpcfe_dashboard_meta_query', function( $meta_query ) {
    // Este filtro ya no es necesario pero lo dejamos por si acaso
    return $meta_query;
});













add_action('init', function(){

    add_action('wpcie_after_import_shipment', function($shipment_id){

        // si ya tiene tipo, no tocar
        if (get_post_meta($shipment_id, 'wpcargo_type_of_shipment', true)) {
            return;
        }

        $contenedor = get_post_meta($shipment_id, 'wpcargo_container', true);
        if (!$contenedor) return;

        $c = strtoupper($contenedor);

        if (strpos($c, 'EXPRESS') !== false) {
            update_post_meta($shipment_id, 'wpcargo_type_of_shipment', 'Express');
        } elseif (strpos($c, 'NORMAL') !== false) {
            update_post_meta($shipment_id, 'wpcargo_type_of_shipment', 'Normal');
        } elseif (strpos($c, 'FULL') !== false) {
            update_post_meta($shipment_id, 'wpcargo_type_of_shipment', 'Full Fitment');
        }
    });

});
add_action('save_post_wpcargo_shipment', function($post_id){

    if (get_post_meta($post_id, 'wpcargo_type_of_shipment', true)) return;

    $contenedor = get_post_meta($post_id, 'wpcargo_container', true);
    if (!$contenedor) return;

    $c = strtoupper($contenedor);

    if (strpos($c, 'EXPRESS') !== false) {
        update_post_meta($post_id, 'wpcargo_type_of_shipment', 'Express');
    } elseif (strpos($c, 'NORMAL') !== false) {
        update_post_meta($post_id, 'wpcargo_type_of_shipment', 'Normal');
    } elseif (strpos($c, 'FULL') !== false) {
        update_post_meta($post_id, 'wpcargo_type_of_shipment', 'Full Fitment');
    }

}, 20);

/**
 * Sincronizar monto durante importación masiva
 * Si el monto fue importado pero no está en wpcargo_total_cobrar,
 * copiar el valor para que POD template lo encuentre
 */
add_action('wpcie_after_import_shipment', function($shipment_id){
    // Posibles nombres del meta key para monto
    $monto_meta_keys = array(
        'wpcargo_monto',
        'monto',
        'amount',
        'price',
        'total',
        'cobrar',
        'pagar',
        'wpcargo_price',
        'wpcargo_amount'
    );
    
    $monto_importado = 0;
    
    // Buscar el monto en todos los posibles meta keys
    foreach ($monto_meta_keys as $key) {
        $valor = floatval(get_post_meta($shipment_id, $key, true));
        if ($valor > 0) {
            $monto_importado = $valor;
            break;
        }
    }
    
    // Si hay monto importado pero NO hay wpcargo_total_cobrar, copiar
    if ($monto_importado > 0) {
        $total_cobrar = floatval(get_post_meta($shipment_id, 'wpcargo_total_cobrar', true));
        if ($total_cobrar <= 0) {
            update_post_meta($shipment_id, 'wpcargo_total_cobrar', $monto_importado);
            error_log('💰 [MONTO-SYNC] Shipment #' . $shipment_id . ' - CSV Import: Sincronizado monto: ' . $monto_importado . ' a wpcargo_total_cobrar');
        }
    }
    
}, 5); // Prioridad baja para ejecutar temprano

/**
 * AUTO-ASIGNAR MOTORIZADO al FINAL del guardado
 * Se ejecuta en save_post_wpcargo_shipment con priority 9999 (MUY al final)
 */


/**
 * Auto-asignar motorizado a envíos importados masivamente
 * Compatible con importación vía CSV/Excel
 * Se ejecuta después de que TODOS los datos se hayan guardado completamente
 */
/**
 * Auto-asignar motorizado a envíos importados masivamente
 * Se ejecuta como STEP 5 de la importación CSV
 * Garantizado: tipo_envio ya está normalizado y guardado
 */
function merc_auto_asignar_motorizado_importacion($shipment_id) {
    // Verificar que sea wpcargo_shipment
    $post_type = get_post_type($shipment_id);
    if ($post_type !== 'wpcargo_shipment') {
        error_log("   ⏭️  Post type incorrecto: $post_type (esperado: wpcargo_shipment)");
        return;
    }
    error_log("   ✅ Post type correcto: wpcargo_shipment");
    
    // Obtener tipo de envío (garantizado que existe en este punto)
    $tipo_envio = get_post_meta($shipment_id, 'tipo_envio', true);
    error_log("   📦 Tipo de envío: " . ($tipo_envio ?: 'VACÍO'));
    
    // Solo auto-asignar a envíos tipo 'normal' (emprendedor normalizado)
    if ($tipo_envio !== 'normal') {
        error_log("   ⏭️  Tipo de envío no es 'normal' ($tipo_envio), no asignar motorizado");
        return;
    }
    error_log("   ✅ Tipo apropiado para auto-asignación");
    
    // Obtener el usuario (remitente) del envío
    $user_id = get_post_meta($shipment_id, 'registered_shipper', true);
    error_log("   👤 Usuario (registered_shipper): " . ($user_id ?: 'VACÍO'));
    
    if (!$user_id) {
        error_log("   ❌ Sin usuario (registered_shipper no guardado), abortando");
        return;
    }
    
    // Obtener el motorizado default del usuario
    $motorizado_default = get_user_meta($user_id, 'merc_motorizo_recojo_default', true);
    error_log("   🔍 Motorizado default para usuario #$user_id: " . ($motorizado_default ?: 'VACÍO'));
    
    if (empty($motorizado_default)) {
        error_log("   ℹ️ Usuario #$user_id NO tiene motorizado default configurado");
        return;
    }
    
    // Verificar si ya tiene motorizado asignado
    $motorizado_actual = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
    if (!empty($motorizado_actual)) {
        error_log("   ⚠️ Ya tiene motorizado asignado (#$motorizado_actual), no sobrescribir");
        return;
    }
    
    // Verificar fecha no sea futura
    if (merc_pickup_date_is_future($shipment_id)) {
        error_log("   ⏭️  Fecha FUTURA, no asignar motorizado");
        return;
    }
    
    // ✅ ASIGNAR MOTORIZADO
    update_post_meta($shipment_id, 'wpcargo_motorizo_recojo', $motorizado_default);
    delete_post_meta($shipment_id, 'wpcargo_driver');
    add_post_meta($shipment_id, 'wpcargo_driver', $motorizado_default);
    
    $motorizado_default_data = get_userdata($motorizado_default);
    $nombre_motorizado = $motorizado_default_data ? $motorizado_default_data->display_name : 'Motorizado #' . $motorizado_default;
    
    error_log("   ✅ AUTO-ASIGNADO - Envío #$shipment_id");
    error_log("   ✅ Motorizado: $nombre_motorizado (ID: $motorizado_default)");
    error_log("   ✅ Metas asignadas: wpcargo_motorizo_recojo=$motorizado_default | wpcargo_driver=$motorizado_default");
}
















// Helper fallback: determina si un estado corresponde a 'No recogido'.
if ( ! function_exists('merc_status_is_no_recogido') ) {
    function merc_status_is_no_recogido( $status ) {
        if ( empty($status) && $status !== '0' ) return false;
        if ( is_array($status) || is_object($status) ) {
            $status = json_encode($status);
        }
        $s = strtolower( trim( (string) $status ) );
        // Normalizar: quitar guiones/underscores/espacios y acentos básicos
        $s_norm = preg_replace('/[\s_\-]+/', '', $s);
        $s_norm = strtr($s_norm, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'));

        if ( strpos($s_norm, 'norecogido') !== false ) return true;
        if ( strpos($s, 'no recogido') !== false ) return true;
        if ( strpos($s, 'no_recogido') !== false ) return true;
        return false;
    }
}

    // Helper fallback: obtener customer id desde un shipment
    if ( ! function_exists('merc_get_shipment_customer_id') ) {
        function merc_get_shipment_customer_id( $shipment_id ) {
            if ( empty($shipment_id) ) return false;
            $cid = get_post_meta($shipment_id, 'wpcargo_customer_id', true);
            if ( empty($cid) ) {
                // intentar otras metas conocidas
                $cid = get_post_meta($shipment_id, 'registered_shipper', true);
            }
            if ( empty($cid) ) {
                // fallback al autor del post
                $author = get_post_field('post_author', $shipment_id);
                if ( $author ) $cid = $author;
            }
            error_log(sprintf('MERC_DEBUG_CUSTOMER_HELPER - shipment=%s resolved_customer=%s', $shipment_id, $cid));
            return $cid ? $cid : false;
        }
        error_log('MERC_DEBUG - fallback merc_get_shipment_customer_id registered');
    }

// Relaja la validación nativa y permite decimales en campos de monto
add_action('wp_footer', function() {
    ?>
    <script>
    (function(){
        try{
            document.addEventListener('DOMContentLoaded', function(){
                // Desactivar validación nativa en formularios de alta de envíos
                document.querySelectorAll('form.add-shipment, form#form-producto-envio, form#form-producto').forEach(function(f){
                    f.setAttribute('novalidate','novalidate');
                });

                // Relajar inputs de monto: permitir decimal, step any, inputmode
                document.querySelectorAll('#wpcargo_monto, input[name*="monto"], input[id*="monto"]').forEach(function(inp){
                    try{
                        inp.setAttribute('inputmode','decimal');
                        if (inp.getAttribute('type') === 'number') inp.setAttribute('step','any');
                        if (inp.hasAttribute('pattern')) inp.removeAttribute('pattern');
                    }catch(e){}
                });

                // También relajar campos de pago en el modal de firma
                document.querySelectorAll('input.pay-amount, input[class*="pay-amount"]').forEach(function(inp){
                    try{
                        inp.setAttribute('inputmode','decimal');
                        if (inp.hasAttribute('pattern')) inp.removeAttribute('pattern');
                        if (inp.getAttribute('type') === 'number') inp.setAttribute('step','any');
                    }catch(e){}
                });

                // Normalizar coma -> punto al perder foco y formatear a 2 decimales
                document.querySelectorAll('#wpcargo_monto, input[name*="monto"], input[id*="monto"], input.pay-amount').forEach(function(inp){
                    inp.addEventListener('blur', function(){
                        if (!this.value) return;
                        var v = this.value.toString().trim().replace(/,/g, '.').replace(/\s+/g,'');
                        var n = parseFloat(v);
                        if (!isNaN(n)) this.value = n.toFixed(2);
                    });
                });
            });
        }catch(e){console && console.log && console.log('merc: relax decimal script error', e);}
    })();
    </script>
    <?php
}, 20);

// Escuchar actualizaciones de meta para detectar cambios de estado rápidos
// Y TAMBIÉN para capturar estado anterior en actualizaciones masivas
add_action('update_post_meta', 'merc_before_post_meta_update', 10, 4);
function merc_before_post_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
    // Interceptar ANTES de que se actualice, para capturar el estado anterior
    if ($meta_key !== 'wpcargo_status') {
        return;
    }
    
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    
    // Si el nuevo estado es "LISTO PARA SALIR" y hay un estado anterior diferente
    if (!empty($meta_value) && stripos($meta_value, 'LISTO PARA SALIR') !== false && !empty($estado_actual) && $estado_actual !== $meta_value) {
        error_log("🔵 [BEFORE_META_UPDATE] Interceptando cambio de estado en Envío #" . $post_id);
        error_log("   Estado Actual: '" . $estado_actual . "' -> Nuevo: '" . $meta_value . "'");
        
        // Guardar en meta específico ANTES de que se actualice
        update_post_meta($post_id, 'wpcargo_status_anterior', $estado_actual);
        error_log("   ✅ Meta 'wpcargo_status_anterior' establecido a: '" . $estado_actual . "'");
        
        // También agregar al historial - PERO SOLO si no existe un registro reciente igual
        $historial = maybe_unserialize(get_post_meta($post_id, 'wpcargo_shipments_update', true));
        if (!is_array($historial)) {
            $historial = array();
        }
        
        // Verificar si el primer registro ya es identical (evitar duplicados)
        $crear_registro = true;
        if (!empty($historial) && is_array($historial[0])) {
            $first = $historial[0];
            if ($first['status'] === $estado_actual && 
                strpos($first['remarks'], 'Estado anterior') !== false) {
                error_log("   ℹ️  Registro anterior ya existe, evitando duplicado");
                $crear_registro = false;
            }
        }
        
        if ($crear_registro) {
            // Crear registro del estado anterior
            $previous_state_record = array(
                'status' => $estado_actual,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'updated-name' => wp_get_current_user()->display_name,
                'location' => get_post_meta($post_id, 'location', true),
                'remarks' => 'Estado anterior (cambio a LISTO PARA SALIR)'
            );
            
            array_unshift($historial, $previous_state_record);
            update_post_meta($post_id, 'wpcargo_shipments_update', $historial);
            error_log("   ✅ Historial actualizado (total: " . count($historial) . " registros)");
        }
    }
}

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}

// add_action( 'wpcfe_after_shipment_filters', 'add_driver_filter_dropdown', 101 );
function add_driver_filter_dropdown() {
    
    $current_user = wp_get_current_user();
    if ( in_array( 'wpcargo_client', (array) $current_user->roles ) ) {
        return;
    }
    
    $value = isset($_GET['wpcargo_driver']) ? esc_attr($_GET['wpcargo_driver']) : '';
    
    $drivers = get_users([
        'role'    => 'wpcargo_driver',
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ]);
    ?>
    <div id="tiendaname-filter-field" class="form-group wpcfe-filter tiendaname-filter p-0 mx-1" style="display: inline-block !important; visibility: visible !important;">
        <div class="md-form form-group" style="margin: 0;">
            <select 
                id="wpcargo-driver-search"
                name="wpcargo_driver"
                class="form-control form-control-sm tiendaname-select2"
            >
                <option value="">Buscar motorizado...</option>
                <?php if (!empty($drivers)) : ?>
                    <?php foreach ($drivers as $driver) : ?>
                        <option value="<?php echo esc_attr($driver->ID); ?>" <?php selected($value, $driver->ID); ?>>
                            <?php echo esc_html($driver->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
    <?php
}
// JS: handler para botón de pago del cliente a MERC (restaurado)
add_action('wp_footer', function() {
    if ( ! is_user_logged_in() ) return;
    ?>
    <script>
    jQuery(function($){
        $(document).on('click', '.merc-btn-pagar-merc', function(e){
            e.preventDefault();
            var $btn = $(this);
            var user_id = $btn.data('user-id');
            var amount = $btn.data('amount');
            var nonce = $btn.data('nonce');

            // Reutilizar modal de liquidación (igual que en administración)
            if ($('#merc-liquidacion-modal').length === 0) {
                // Agregar estilos del modal
                if (!document.getElementById('merc-liquidacion-styles')) {
                    var style = document.createElement('style');
                    style.id = 'merc-liquidacion-styles';
                    style.innerHTML = '@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } } ' +
                        '@keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } } ' +
                        '#merc-liquidacion-modal h3 { color: #2c3e50; margin: 0 0 12px 0; font-size: 20px; font-weight: 600; } ' +
                        '#merc-liquidacion-modal p { color: #555; margin: 0 0 20px 0; font-size: 14px; line-height: 1.5; } ' +
                        '#merc-liquidacion-voucher { width: 100%; padding: 12px; border: 2px dashed #3498db; border-radius: 6px; margin-bottom: 24px; cursor: pointer; font-size: 14px; box-sizing: border-box; } ' +
                        '#merc-liquidacion-voucher:hover { border-color: #2980b9; background: #ecf0f1; } ' +
                        '#merc-liquidacion-modal .button { padding: 10px 20px; border-radius: 6px; font-weight: 500; border: none; cursor: pointer; transition: all 0.3s ease; background: #ecf0f1; color: #2c3e50; } ' +
                        '#merc-liquidacion-modal .button:hover { background: #bdc3c7; } ' +
                        '#merc-liquidacion-modal .button-primary { background: linear-gradient(135deg, #3498db, #2980b9); color: #fff; } ' +
                        '#merc-liquidacion-modal .button-primary:hover { background: linear-gradient(135deg, #2980b9, #2471a3); box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4); }';
                    document.head.appendChild(style);
                }
                
                var modal = '<div id="merc-liquidacion-modal" style="position:fixed;left:0;top:0;width:100%;height:100%;z-index:100000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);animation:fadeIn 0.3s ease;">'
                    + '<div style="background:#fff;padding:32px;border-radius:12px;max-width:520px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideUp 0.3s ease;overflow:hidden;">'
                    + '<div style="background:#f8f9fa;padding:16px;border-radius:6px;margin-bottom:24px;border-left:4px solid #3498db;">'
                    + '<small style="color:#7f8c8d;font-weight:600;">Monto a liquidar</small>'
                    + '<p style="margin:6px 0 0 0;font-size:18px;color:#2c3e50;font-weight:700;">S/. ' + number_format(amount, 2) + '</p>'
                    + '</div>'
                    + '<h3>Subir comprobante de pago</h3>'
                    + '<p>Adjunta la imagen del comprobante de pago para que se registre en el historial de liquidaciones.</p>'
                    + '<input type="file" id="merc-liquidacion-voucher" accept="image/*" />'
                    + '<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:32px;">'
                    + '<button id="merc-liquidacion-cancel" class="button">Cancelar</button>'
                    + '<button id="merc-liquidacion-submit" class="button button-primary">Confirmar y Subir (S/. ' + amount + ')</button>'
                    + '</div></div></div>';
                $('body').append(modal);

                $('#merc-liquidacion-cancel').on('click', function(){ $('#merc-liquidacion-modal').remove(); });
                $('#merc-liquidacion-submit').on('click', function(){
                    var fileInput = $('#merc-liquidacion-voucher')[0];
                    if (!fileInput.files || fileInput.files.length === 0) {
                        Swal.fire({icon:'warning',title:'Archivo requerido',text:'Debes adjuntar una imagen de comprobante.',confirmButtonColor:'#3498db'});
                        return;
                    }

                    $btn.prop('disabled', true).text('Procesando...');

                    var fd = new FormData();
                    fd.append('action', 'merc_cliente_pagar_voucher');
                    fd.append('user_id', user_id);
                    fd.append('amount', amount);
                    fd.append('nonce', nonce);
                    fd.append('voucher', fileInput.files[0]);

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({icon:'success',title:'¡Éxito!',text:response.data.message,confirmButtonColor:'#27ae60'}).then(function(){location.reload();});
                            } else {
                                Swal.fire({icon:'error',title:'Error',text:(response.data && response.data.message ? response.data.message : response.data),confirmButtonColor:'#e74c3c'});
                                $btn.prop('disabled', false).text('Cobrar del cliente S/. ' + amount);
                                $('#merc-liquidacion-modal').remove();
                            }
                        },
                        error: function() {
                            Swal.fire({icon:'error',title:'Error de conexión',text:'No se pudo conectar con el servidor',confirmButtonColor:'#e74c3c'});
                            $btn.prop('disabled', false).text('Cobrar del cliente S/. ' + amount);
                            $('#merc-liquidacion-modal').remove();
                        }
                    });
                });
            }
            return;
        });
    });
    </script>
    <?php
});

// AJAX: cliente marca pago a MERC (registra liquidación y marca envíos como cobrados) (restaurado)
add_action('wp_ajax_merc_cliente_pagar_a_merc', 'merc_cliente_pagar_a_merc_ajax');
function merc_cliente_pagar_a_merc_ajax() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'merc_cliente_pagar' ) ) {
        wp_send_json_error(array('message'=>'Nonce inválido'));
    }
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if ( $user_id <= 0 ) wp_send_json_error(array('message'=>'Usuario inválido'));
    $current = wp_get_current_user();
    if ( $current->ID !== $user_id && ! current_user_can('administrator') ) {
        wp_send_json_error(array('message'=>'Sin permisos'));
    }

    $amount = isset($_POST['amount']) ? floatval( str_replace(',', '', $_POST['amount']) ) : 0;
    if ( $amount <= 0 ) wp_send_json_error(array('message'=>'Monto inválido'));

    global $wpdb;
    
    // PASO 1: Obtener envíos pendientes del remitente (no incluidos previamente)
    $shipments = $wpdb->get_col( $wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_sender ON p.ID = pm_sender.post_id AND pm_sender.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_included ON p.ID = pm_included.post_id AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
        WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish' AND pm_sender.meta_value = %s AND (pm_included.meta_value IS NULL)", $user_id ) );

    // Filtrar solo envíos cuya fecha de pickup sea HOY (evitar incluir reprogramados de otros días)
    if ( is_array( $shipments ) && function_exists('merc_pickup_date_is_today') ) {
        $shipments = array_filter( $shipments, function( $sid ) {
            return merc_pickup_date_is_today( $sid );
        });
        // reindex
        $shipments = array_values( $shipments );
    }

    if ( empty( $shipments ) ) {
        wp_send_json_error(array('message'=>'No hay envíos pendientes para pagar'));
    }

    $remaining = $amount;
    $liquidation = array(
        'id' => uniqid('liq_cli_'),
        'date' => current_time('mysql'),
        'action' => 'cliente_pago',
        'amount' => round($amount,2)
    );

    // PASO 2: Asignar pago a envíos regular y NO RECIBIDO
    foreach ( $shipments as $shipment_id ) {
        if ( $remaining <= 0 ) break;
        
        $costo_envio = function_exists( 'merc_get_adjusted_service_cost' ) ? merc_get_adjusted_service_cost( $shipment_id ) : floatval( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) );
        if ( $costo_envio <= 0 ) continue;
        
        // Marcar como cobrado y vincular a esta liquidación
        update_post_meta( $shipment_id, 'wpcargo_servicio_cobrado', 'si' );
        update_post_meta( $shipment_id, 'wpcargo_included_in_liquidation', $liquidation['id'] );
        update_post_meta( $shipment_id, 'wpcargo_fecha_liquidacion_remitente', current_time('mysql') );
        $remaining = round( $remaining - $costo_envio, 2 );
    }

    // PASO 3: Marcar como 'paid' los cargos NO RECIBIDO que se pagaron
    $history = get_user_meta( $user_id, 'merc_liquidations', true );
    if ( ! is_array( $history ) ) $history = array();
    
    $monto_procesado = 0;
    $cargos_pagados = 0;
    
    for ( $i = 0; $i < count($history); $i++ ) {
        if ( $monto_procesado >= $amount ) break;
        
        // Procesar cargos NO RECIBIDO pendientes primero
        if ( ! empty($history[$i]['tipo_liquidacion']) && $history[$i]['tipo_liquidacion'] === 'no_recibido_charge' && 
             $history[$i]['status'] === 'unpaid' ) {
            $monto_cargo = floatval($history[$i]['amount']);
            if ( $monto_procesado + $monto_cargo <= $amount ) {
                $history[$i]['status'] = 'paid';
                $history[$i]['payment_date'] = current_time('mysql');
                $history[$i]['payment_ref'] = $liquidation['id'];
                $monto_procesado += $monto_cargo;
                $cargos_pagados++;
                
                // Marcar en el shipment como liquidado
                if ( ! empty($history[$i]['shipment_id']) ) {
                    update_post_meta( $history[$i]['shipment_id'], 'wpcargo_included_in_liquidation', $liquidation['id'] );
                    error_log(sprintf('✅ PAGO_NO_RECIBIDO - shipment=%s marcado como liquidado (pago_ref=%s)', 
                        $history[$i]['shipment_id'], $liquidation['id']));
                }
            }
        }
    }
    
    // PASO 4: Guardar registro actualizado en user meta
    $history[] = $liquidation;
    update_user_meta( $user_id, 'merc_liquidations', $history );

    $msg = 'Pago registrado por S/. ' . number_format($amount,2);
    if ( $cargos_pagados > 0 ) {
        $msg .= ' (' . $cargos_pagados . ' cargo(s) NO RECIBIDO liquidado(s))';
    }
    
    wp_send_json_success(array('message'=>$msg));
}

// AJAX: cliente sube voucher para registrar pago y guardar en historial de liquidaciones
add_action('wp_ajax_merc_cliente_pagar_voucher', 'merc_cliente_pagar_voucher_ajax');
function merc_cliente_pagar_voucher_ajax() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'merc_cliente_pagar' ) ) {
        wp_send_json_error(array('message'=>'Nonce inválido'));
    }
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if ( $user_id <= 0 ) wp_send_json_error(array('message'=>'Usuario inválido'));
    $current = wp_get_current_user();
    if ( $current->ID !== $user_id && ! current_user_can('administrator') ) {
        wp_send_json_error(array('message'=>'Sin permisos'));
    }

    $amount = isset($_POST['amount']) ? floatval( str_replace(',', '', $_POST['amount']) ) : 0;
    if ( $amount <= 0 ) wp_send_json_error(array('message'=>'Monto inválido'));

    if ( empty($_FILES) || empty($_FILES['voucher']) ) {
        wp_send_json_error(array('message'=>'Debes adjuntar un comprobante (imagen).'));
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $file = $_FILES['voucher'];
    $overrides = array( 'test_form' => false );
    $movefile = wp_handle_upload( $file, $overrides );
    if ( isset( $movefile['error'] ) ) {
        wp_send_json_error( array( 'message' => 'Error al subir comprobante: ' . $movefile['error'] ) );
    }

    $filename = $movefile['file'];
    $filetype = wp_check_filetype( basename( $filename ), null );
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name( basename( $filename ) ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    $attachment_id = wp_insert_attachment( $attachment, $filename );
    if ( ! is_wp_error( $attachment_id ) ) {
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
        wp_update_attachment_metadata( $attachment_id, $attach_data );
    }

    // Registrar liquidación en user meta
    $liquidation = array(
        'id' => uniqid('liq_cli_'),
        'date' => current_time('mysql'),
        'action' => 'cliente_pago_voucher',
        'amount' => round($amount,2),
        'attachment_id' => $attachment_id
    );

    // Obtener envíos pendientes del remitente
    global $wpdb;
    $shipments = $wpdb->get_col( $wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_sender ON p.ID = pm_sender.post_id AND pm_sender.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_included ON p.ID = pm_included.post_id AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
        WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish' AND pm_sender.meta_value = %s AND (pm_included.meta_value IS NULL)", $user_id ) );

    // Filtrar sólo envíos cuya fecha de pickup sea HOY (evita incluir reprogramados de otras fechas)
    $filtered = array();
    if ( is_array( $shipments ) && ! empty( $shipments ) ) {
        foreach ( $shipments as $sid ) {
            // usar helper existente que normaliza múltiples meta keys
            if ( function_exists('merc_pickup_date_is_today') ) {
                if ( merc_pickup_date_is_today( $sid ) ) {
                    $filtered[] = $sid;
                }
            } else {
                // Fallback: incluir si post_date es hoy
                $post_date = get_post( $sid )->post_date;
                if ( date('Y-m-d', strtotime( $post_date )) === current_time('Y-m-d') ) {
                    $filtered[] = $sid;
                }
            }
        }
    }

    // No marcamos los envíos todavía: la verificación la hace un administrador.
    $liquidation['shipments'] = $filtered;

    $history = get_user_meta( $user_id, 'merc_liquidations', true );
    if ( ! is_array( $history ) ) $history = array();
    $liquidation['verified'] = false;
    error_log(sprintf("🔔 [CLIENTE_PAGO_VOUCHER] Preparando registro: user=%d liq_id=%s attachment=%s shipments=%d amount=%01.2f",
        $user_id, $liquidation['id'], intval($attachment_id), count($filtered), $liquidation['amount']));

    // Log detallado: por cada envío, mostrar id y monto de costo_envio
    if ( is_array($filtered) && ! empty($filtered) ) {
        foreach ( $filtered as $sid ) {
            $costo = floatval( get_post_meta( $sid, 'wpcargo_costo_envio', true ) );
            error_log(sprintf("🔎 [CLIENTE_PAGO_VOUCHER] shipment_id=%d costo_envio=%01.2f", $sid, $costo));
        }
    } else {
        error_log(sprintf("🔎 [CLIENTE_PAGO_VOUCHER] No hay envíos filtrados para user=%d", $user_id));
    }
    $history[] = $liquidation;
    update_user_meta( $user_id, 'merc_liquidations', $history );
    error_log(sprintf("🔔 [CLIENTE_PAGO_VOUCHER] Registro guardado: user=%d liq_entries=%d",
        $user_id, count($history)));

    wp_send_json_success(array('message'=>'Comprobante subido. Esperando verificación administrativa.'));
}

// Filtrar shipments por tiendaname

// Filtro consolidado para fecha de envío y tiendaname
add_filter( 'wpcfe_dashboard_arguments', 'filter_shipment_by_date_and_tiendaname', 20 );
function filter_shipment_by_date_and_tiendaname( $args ) {
    global $wpdb;
    
    error_log("🔵 [FILTER] filter_shipment_by_date_and_tiendaname llamado");
    error_log("🔵 [FILTER] Usuario actual: " . get_current_user_id());
    error_log("🔵 [FILTER] Args iniciales: " . json_encode($args, JSON_PARTIAL_OUTPUT_ON_ERROR));
    error_log("🔵 [FILTER] GET params: " . print_r($_GET, true));
    
    // 1. FILTRO POR FECHA DE ENVÍO
    $from = isset($_GET['shipping_date_start']) ? sanitize_text_field($_GET['shipping_date_start']) : '';
    $to = isset($_GET['shipping_date_end']) ? sanitize_text_field($_GET['shipping_date_end']) : '';
    
    // Si no hay fechas en GET, aplicar por defecto el día de hoy
    if (empty($from) && empty($to)) {
        $from = current_time('Y-m-d');
        $to = current_time('Y-m-d');
        error_log("🔵 [FILTER] Sin parámetros de fecha, aplicando HOY por defecto: {$from}");
    }
    
    error_log("🔵 [FILTER] Fecha desde: '{$from}' | Fecha hasta: '{$to}'");
    
    $date_filtered_ids = array();
    
    // Validar formato de fechas y construir condición SQL
    $has_from = $from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
    $has_to = $to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);
    
    error_log("🔵 [FILTER] has_from: " . ($has_from ? 'true' : 'false') . " | has_to: " . ($has_to ? 'true' : 'false'));
    
    if ($has_from || $has_to) {
        // REMOVER date_query si existe para evitar conflicto
        if (isset($args['date_query'])) {
            error_log("🔵 [FILTER] REMOVIENDO date_query para evitar conflicto");
            unset($args['date_query']);
        }
        
        // Construir la condición de fecha según lo que esté disponible
        // Usamos STR_TO_DATE para manejar fechas en ambos formatos (d/m/Y y Y-m-d)
        // NOTA: NO escapar % aquí porque get_col() ejecuta directamente sin prepare()
        $date_conditions = array();
        
        if ($has_from) {
            // Buscar desde esa fecha en adelante
            // Solo STR_TO_DATE sin OR, así solo incluimos fechas válidas en el rango correcto
            // Si STR_TO_DATE falla (retorna NULL), el resultado será NULL >= fecha que es FALSE
            $date_conditions[] = "STR_TO_DATE(pm.meta_value, '%d/%m/%Y') >= STR_TO_DATE('" . sanitize_text_field($from) . "', '%Y-%m-%d')";
        }
        
        if ($has_to) {
            // Buscar hasta esa fecha
            // Solo STR_TO_DATE sin OR
            $date_conditions[] = "STR_TO_DATE(pm.meta_value, '%d/%m/%Y') <= STR_TO_DATE('" . sanitize_text_field($to) . "', '%Y-%m-%d')";
        }
        
        $final_date_condition = implode(' AND ', $date_conditions);
        
        // Buscar IDs de envíos que tengan fecha en cualquiera de los meta keys posibles
        $query = "
            SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'wpcargo_shipment'
            AND pm.meta_key IN ('wpcargo_pickup_date_picker', 'wpcargo_pickup_date', 'calendarenvio', 'wpcargo_fecha_envio')
            AND ({$final_date_condition})
        ";
        
        error_log("🔵 [FILTER] SQL Query: " . $query);
        
        $date_filtered_ids = $wpdb->get_col($query);
        
        error_log("🔵 [FILTER] IDs encontrados por fecha: " . count($date_filtered_ids));
        if (!empty($date_filtered_ids)) {
            error_log("🔵 [FILTER] Primeros 10 IDs: " . implode(', ', array_slice($date_filtered_ids, 0, 10)));
        }
        
        // Si hay filtro de fecha y se encontraron IDs, limitar la búsqueda a esos IDs
        if (!empty($date_filtered_ids)) {
            // Si ya existe post__in, intersectar
            if (isset($args['post__in']) && !empty($args['post__in'])) {
                error_log("🔵 [FILTER] post__in ya existe con " . count($args['post__in']) . " IDs");
                $args['post__in'] = array_intersect($args['post__in'], $date_filtered_ids);
                error_log("🔵 [FILTER] Después de intersectar: " . count($args['post__in']) . " IDs");
            } else {
                error_log("🔵 [FILTER] post__in no existe, creando nuevo");
                $args['post__in'] = $date_filtered_ids;
            }
            
            // ⭐ IMPORTANTE: Cambiar post_status a array PERO EXCLUYENDO 'trash'
            // Los envíos borrados (trash) NO deben aparecer en el historial
            $args['post_status'] = array('publish', 'draft', 'pending', 'private');
            error_log("🔵 [FILTER] post_status cambiado a array para incluir estados normales (EXCLUYENDO trash)");
        } else {
            // Si no hay resultados con el filtro de fecha, forzar búsqueda vacía
            error_log("🔵 [FILTER] Sin resultados de fecha, forzando búsqueda vacía");
            $args['post__in'] = array(0);
        }
    }
    
    // 2. FILTRO POR TIENDANAME
    if ( !empty( $_GET['wpcargo_tiendaname'] ) ) {
        error_log("🔵 [FILTER] Filtro tiendaname: " . $_GET['wpcargo_tiendaname']);
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = array();
        }
        $args['meta_query']['wpcargo_tiendaname'] = array(
            'key'     => 'wpcargo_tiendaname',
            'value'   => sanitize_text_field( $_GET['wpcargo_tiendaname'] ),
            'compare' => 'LIKE'
        );
    }
    
    error_log("🔵 [FILTER] Args finales: " . json_encode($args, JSON_PARTIAL_OUTPUT_ON_ERROR));
    
    // Agregar hook para capturar la SQL final que WordPress ejecuta
    add_filter('posts_request', 'debug_final_sql_query', 10, 2);
    
    return $args;
}

// Hook para capturar la SQL FINAL que WordPress ejecuta
function debug_final_sql_query($query, $wp_query) {
    if (empty($_GET['shipping_date_start']) && empty($_GET['shipping_date_end'])) {
        return $query;
    }
    error_log("🔴 [FINAL_SQL] SQL ejecutada por WordPress: " . $query);
    error_log("🔴 [FINAL_SQL] post__in en args: " . (isset($wp_query->query_vars['post__in']) ? implode(',', array_slice($wp_query->query_vars['post__in'], 0, 10)) . '...' : 'NO EXISTE'));
    error_log("🔴 [FINAL_SQL] Parámetros post__include: " . (isset($wp_query->query_vars['post__include']) ? print_r($wp_query->query_vars['post__include'], true) : 'NO EXISTE'));
    return $query;
}

// Hook para capturar los POSTS FINALES que se muestran
add_filter('the_posts', 'debug_final_posts', 10, 2);
function debug_final_posts($posts, $wp_query) {
    if (empty($_GET['shipping_date_start']) && empty($_GET['shipping_date_end'])) {
        return $posts;
    }
    
    error_log("🟠 [POSTS_RETORNADOS] Total posts retornados: " . count($posts));
    error_log("🟠 [POSTS_RETORNADOS] post__in utilizado: " . (isset($wp_query->query_vars['post__in']) ? 'SÍ' : 'NO'));
    
    $post_status_info = isset($wp_query->query_vars['post_status']) 
        ? (is_array($wp_query->query_vars['post_status']) ? json_encode($wp_query->query_vars['post_status']) : $wp_query->query_vars['post_status'])
        : 'NO ESPECIFICADO';
    error_log("🟠 [POSTS_RETORNADOS] post_status en args: " . $post_status_info);
    
    // Verificar qué status tienen los posts del post__in
    if (isset($wp_query->query_vars['post__in']) && !empty($wp_query->query_vars['post__in'])) {
        global $wpdb;
        $post_ids = $wp_query->query_vars['post__in'];
        $ids_chunk = array_slice($post_ids, 0, 10);
        $ids_str = implode(',', $ids_chunk);
        
        $status_check = $wpdb->get_results("SELECT ID, post_status, post_date FROM {$wpdb->posts} WHERE ID IN ({$ids_str})");
        error_log("🟠 [POSTS_RETORNADOS] Estado de primeros 10 posts del post__in: " . print_r($status_check, true));
    }
    
    if (!empty($posts)) {
        $post_dates = array();
        foreach (array_slice($posts, 0, 10) as $post) {
            $pickup_date = get_post_meta($post->ID, 'wpcargo_pickup_date_picker', true);
            if (!$pickup_date) {
                $pickup_date = get_post_meta($post->ID, 'wpcargo_pickup_date', true);
            }
            if (!$pickup_date) {
                $pickup_date = get_post_meta($post->ID, 'calendarenvio', true);
            }
            if (!$pickup_date) {
                $pickup_date = get_post_meta($post->ID, 'wpcargo_fecha_envio', true);
            }
            $post_dates[] = "ID:{$post->ID} | Fecha:{$pickup_date} | Creado:{$post->post_date}";
        }
        error_log("🟠 [POSTS_RETORNADOS] Primeros 10 posts: " . implode(" | ", $post_dates));
    }
    
    return $posts;
}

// Mantener el filtro antiguo por compatibilidad (pero deshabilitado)
// add_filter( 'wpcfe_dashboard_arguments', 'filter_shipment_by_tiendaname' );
// function filter_shipment_by_tiendaname( $args ) {
//     if ( !empty( $_GET['wpcargo_tiendaname'] ) ) {
//         $args['meta_query']['wpcargo_tiendaname'] = array(
//             'key'     => 'wpcargo_tiendaname',
//             'value'   => sanitize_text_field( $_GET['wpcargo_tiendaname'] ),
//             'compare' => 'LIKE'
//         );
//     }
//     return $args;
// }

// Inicializar Select2 en el campo de tiendas
add_action( 'wp_footer', 'init_tiendaname_select2', 999 );
function init_tiendaname_select2() {
    // Verificar que el usuario NO sea wpcargo_client
    $current_user = wp_get_current_user();
    if ( in_array( 'wpcargo_client', (array) $current_user->roles ) ) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($){
        function initTiendaSelect2() {
            var selectElement = $('#wpcargo-tiendaname-search');
            
            if (selectElement.length) {
                // Destruir Select2 si ya existe
                if (selectElement.hasClass('select2-hidden-accessible')) {
                    selectElement.select2('destroy');
                }
                
                // Inicializar Select2
                selectElement.select2({
                    placeholder: 'Buscar tienda...',
                    allowClear: true,
                    minimumResultsForSearch: 0, // Siempre mostrar búsqueda
                    dropdownAutoWidth: false,
                    language: {
                        noResults: function() {
                            return "No se encontraron tiendas";
                        },
                        searching: function() {
                            return "Buscando...";
                        }
                    }
                });
                
                // Forzar visibilidad del contenedor Select2
                selectElement.next('.select2-container').css({
                    'display': 'inline-block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
        }
        
        // Intentar inicializar varias veces
        setTimeout(initTiendaSelect2, 100);
        setTimeout(initTiendaSelect2, 500);
        setTimeout(initTiendaSelect2, 1000);
    });
    </script>
    <?php
}

// CSS para que se vea igual que los otros filtros
add_action( 'wp_head', 'tiendaname_filter_custom_css' );
function tiendaname_filter_custom_css() {
    // Verificar que el usuario NO sea wpcargo_client
    $current_user = wp_get_current_user();
    if ( in_array( 'wpcargo_client', (array) $current_user->roles ) ) {
        return;
    }
    ?>
    <style>
        /* Contenedor del filtro */
        #tiendaname-filter-field {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Select2 container - mismo estilo que otros filtros */
        #tiendaname-filter-field .select2-container {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 250px !important;
        }
        
        /* Select2 selection - fondo blanco y borde */
        #tiendaname-filter-field .select2-container--default .select2-selection--single {
            background-color: #ffffff !important;
            border: 1px solid #ced4da !important;
            border-radius: 4px !important;
            height: 38px !important;
            padding: 6px 12px !important;
        }
        
        /* Texto del placeholder y selección */
        #tiendaname-filter-field .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px !important;
            padding-left: 0 !important;
            color: #495057 !important;
        }
        
        /* Flecha/triángulo DENTRO del recuadro */
        #tiendaname-filter-field .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }
        
        /* Placeholder color gris */
        #tiendaname-filter-field .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d !important;
        }
        
        /* Botón de limpiar (X) */
        #tiendaname-filter-field .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 20px !important;
        }
        
        /* Eliminar margen del md-form */
        #tiendaname-filter-field .md-form {
            margin-bottom: 0 !important;
        }
        
        /* Dropdown con scroll */
        .select2-container--default .select2-results__option {
            font-size: 14px !important;
        }
    </style>
    <?php
}

// -------------------------------------------------------------------
// Función AJAX para obtener información del conductor asignado
add_action('wp_ajax_get_order_driver_info', 'wpc_get_order_driver_info');
add_action('wp_ajax_nopriv_get_order_driver_info', 'wpc_get_order_driver_info');

function wpc_get_order_driver_info() {

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce($_POST['nonce'], 'wpc_get_order_info')
    ) {
        wp_send_json_error(['message' => 'Nonce inválido']);
        wp_die();
    }

    $tracking = sanitize_text_field($_POST['tracking_number']);

    error_log('=== BUSCANDO MOTORIZADO ===');
    error_log('Tracking recibido: ' . $tracking);

    /**
     * 1️⃣ Buscar pedido CORRECTO (tracking real)
     */
    $query = new WP_Query([
        'post_type'      => 'wpcargo_shipment',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'title'          => $tracking,
    ]);

    if (!$query->have_posts()) {
        $query = new WP_Query([
            'post_type'      => 'wpcargo_shipment',
            'posts_per_page' => 1,
            'meta_query'     => [
                'relation' => 'OR',
                ['key' => 'wpcargo_shipment_number', 'value' => $tracking],
                ['key' => 'wpcargo_order_number',    'value' => $tracking],
            ]
        ]);
    }

    if (!$query->have_posts()) {
        error_log('❌ Pedido no encontrado');
        wp_send_json_error(['message' => 'Pedido no encontrado']);
        wp_die();
    }

    $query->the_post();
    $order_id = get_the_ID();

    error_log('Order ID correcto: ' . $order_id);

    /**
     * 2️⃣ Buscar motorizado REAL (todas las variantes)
     */
    $possible_keys = [
        'wpcargo_driver',
        'assigned_driver',
        'conductor',
        'wpcargo_assigned_driver'
    ];

    $driver_id = null;

    foreach ($possible_keys as $key) {
        $value = get_post_meta($order_id, $key, true);
        if (!empty($value)) {
            $driver_id = $value;
            error_log("Motorizado encontrado en meta: {$key} = {$value}");
            break;
        }
    }

    if (!$driver_id || !is_numeric($driver_id)) {
        error_log('❌ Pedido SIN motorizado asignado');
        wp_send_json_success([
            'order_id'   => $order_id,
            'has_driver' => false
        ]);
        wp_die();
    }

    $driver = get_userdata((int) $driver_id);

    if (!$driver) {
        error_log('❌ Usuario motorizado no existe');
        wp_send_json_success([
            'order_id'   => $order_id,
            'has_driver' => false
        ]);
        wp_die();
    }

    error_log('✅ Motorizado final: ' . $driver->display_name);

    wp_send_json_success([
        'order_id'    => $order_id,
        'driver_id'   => $driver->ID,
        'driver_name' => $driver->display_name,
        'has_driver'  => true
    ]);

    wp_die();
}


// AJAX: Migrar metas antiguas 'wpcargo_estado_pago_remitente' -> 'wpcargo_included_in_liquidation'
add_action('wp_ajax_merc_migrate_liquidation_meta', 'merc_migrate_liquidation_meta_ajax');
function merc_migrate_liquidation_meta_ajax() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'merc_migrate' ) ) {
        wp_send_json_error( array( 'message' => 'Nonce inválido' ) );
    }
    if ( ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( array( 'message' => 'No tienes permisos para ejecutar la migración' ) );
    }

    global $wpdb;
    // Buscar envíos que tengan la meta antigua marcada como 'liquidado'
    $shipment_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wpcargo_estado_pago_remitente' AND LOWER(meta_value) = 'liquidado'" );

    if ( empty( $shipment_ids ) ) {
        wp_send_json_success( array( 'message' => 'No se encontraron metas antiguas para migrar.' ) );
    }

    $migrated = 0;
    $ts = time();
    foreach ( $shipment_ids as $sid ) {
        // Si ya tiene included, saltar
        $existing = get_post_meta( $sid, 'wpcargo_included_in_liquidation', true );
        if ( ! empty( $existing ) ) continue;
        update_post_meta( $sid, 'wpcargo_included_in_liquidation', 'migrated_' . $ts );
        $migrated++;
    }

    wp_send_json_success( array( 'message' => "Migración completada. Envíos marcados: {$migrated}" ) );
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
});

/*
 * Ocultar el badge financiero del sidebar y hacer responsivas las tablas
 * Añadimos estilos globales al encabezado para esconder cualquier
 * .merc-sidebar-badge que pueda inyectar el plugin y permitir que
 * las tablas de entregas se desplacen horizontalmente en pantallas
 * pequeñas. Estos estilos se aplican a todo el sitio.
 */
add_action( 'wp_head', function() {
    echo '<style>
    .merc-sidebar-badge{display:none !important;}
    .merc-entregas-table{display:block;overflow-x:auto;white-space:nowrap;width:100%;}
    
    /* Ocultar campo Ubicación del formulario de creación de envíos */  
    label[for="location"],
    #location,
    .status_location,
    input[name="location"],
    .form-group:has(#location),
    .form-group:has(.status_location),
    .form-group:has(label[for="location"]),
    div:has(> label[for="location"]),
    div:has(> #location) {
        display: none !important;
    }
    </style>';
});

// Fix crítico: Forzar cierre de dropdowns de Bootstrap
add_action('wp_footer', function() {
    ?>
    <script>
    (function() {
        // Asegurar overflow:auto en el body
        document.body.style.overflow = 'auto';
        
        // Fix para dropdowns de Bootstrap - forzar cierre manual
        document.addEventListener('click', function(e) {
            // IGNORAR si el click es dentro del área de métodos de pago del POD
            if (e.target.closest('#payment-methods-list') || e.target.closest('.method-selector')) {
                return; // No hacer nada, dejar que el código del POD lo maneje
            }
            
            // Buscar si el click fue en un botón de dropdown
            var dropdownBtn = e.target.closest('[data-toggle="dropdown"]');
            
            if (dropdownBtn) {
                // Obtener el dropdown menu asociado
                var dropdownMenu = dropdownBtn.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    // Si ya tiene la clase 'show', removerla manualmente
                    if (dropdownMenu.classList.contains('show')) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropdownMenu.classList.remove('show');
                        dropdownBtn.setAttribute('aria-expanded', 'false');
                        return false;
                    }
                }
            }
            
            // Cerrar todos los dropdowns si se hace click fuera (EXCEPTO los de métodos de pago)
            var clickedInsideDropdown = e.target.closest('.dropdown-menu');
            var clickedInsidePaymentMethod = e.target.closest('.method-selector');
            
            if (!clickedInsideDropdown && !dropdownBtn && !clickedInsidePaymentMethod) {
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    // No cerrar si es parte del sistema de métodos de pago
                    if (menu.closest('#payment-methods-list')) {
                        return;
                    }
                    
                    menu.classList.remove('show');
                    // Buscar el botón asociado y actualizar aria-expanded
                    var btn = menu.previousElementSibling;
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        }, true);
        
        // Verificar que el body nunca tenga overflow:hidden
        setInterval(function() {
            if (document.body.style.overflow === 'hidden') {
                document.body.style.overflow = 'auto';
            }
        }, 500);
    })();
    </script>
    <?php
}, 1);

// RENOMBRANDO EL SIDEBAR
// RENOMBRANDO PICK UP Y DELIVERY FOR DRIVERS
// -----------------------------------------------------------------------------------------------------------------------
function custom_rename_driver_menu_callback( $menu_items ){
    // Verificar que es un driver
    $current_user = wp_get_current_user();
    if( !in_array( 'wpcargo_driver', $current_user->roles ) ){
        return $menu_items;
    }
    
    // Renombrar los menús del POD
    if( isset($menu_items['wpcpod-pickup-route']) ){
        $menu_items['wpcpod-pickup-route']['label'] = 'Recojo de mercadería';
    }
    
    if( isset($menu_items['wpcpod-route']) ){
        $menu_items['wpcpod-route']['label'] = 'Entrega de mercadería';
    }
    
    return $menu_items; 
}
add_filter( 'wpcfe_after_sidebar_menus', 'custom_rename_driver_menu_callback', 10 );

add_action('admin_init', function(){

    register_setting( 'wpcargo_podapp_settings_group', 'wpcargo_payment_modes', [
        'type'              => 'array',
        'sanitize_callback' => function($value){

            // SI VIENE COMO STRING → textarea
            if (is_string($value)) {
                $lineas = explode("\n", $value);
            }

            // SI VIENE COMO ARRAY ANIDADO → aplastar
            else if (is_array($value)) {
                $tmp = [];
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $tmp[] = trim(reset($item));
                    } else {
                        $tmp[] = trim($item);
                    }
                }
                $lineas = $tmp;
            }

            // Limpiar y devolver array simple
            return array_filter(array_map('trim', $lineas));
        }
    ]);

});

add_action('template_redirect', function () {
    if (is_page('import-export')) { // Cambiado de 'importar-exportar' a 'import-export'
        // Obtener el usuario actual
        $current_user = wp_get_current_user();
        
        // Verificar si NO es admin ni empleado (es decir, es cliente)
        $is_admin_or_employee = current_user_can('administrator') || 
                                in_array('wpcargo_employee', $current_user->roles);
        
        if (!$is_admin_or_employee) {
            // Si es cliente y no está en la vista de import, redirigir
            if (!isset($_GET['type']) || $_GET['type'] !== 'import') {
                wp_redirect(home_url('/import-export/?type=import')); // Cambiada la URL
                exit;
            }
        }
    }
});

add_action('wp_footer', function () {
    // Obtener el usuario actual
    $current_user = wp_get_current_user();
    
    // Verificar si NO es admin ni empleado
    $is_admin_or_employee = current_user_can('administrator') || 
                            in_array('wpcargo_employee', $current_user->roles);
    
    if (!$is_admin_or_employee) : ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const buttonContainer = document.querySelector('.mb-4.border-bottom');
                if (buttonContainer) {
                    buttonContainer.style.display = 'none';
                }
            });
        </script>
    <?php endif;
});

add_filter( 'wpcfe_footer_credits', 'custom_footer_text' );
function custom_footer_text(){
    echo 'Copyright © ' . date('Y') . ' - Diseñado por <a href="https://diffcode.net" target="_blank">DIFFCODE</a>';
}

// ========== PERSONALIZAR TABLA DE SHIPMENTS (SEGÚN DOCUMENTACIÓN OFICIAL) ==========

//===============================================================================================================
// STEP 0A: Filtro de Pre-insersión - Asegurar unicidad del post_title (números de tracking)
add_filter('wp_insert_post_data', 'merc_ensure_unique_tracking_on_insert', 10, 2);
function merc_ensure_unique_tracking_on_insert($data, $postarr) {
    // Solo aplicar a shipments
    if ($data['post_type'] !== 'wpcargo_shipment') {
        return $data;
    }
    
    // Si es una actualización (ID existe), no modificar
    if (!empty($postarr['ID'])) {
        return $data;
    }
    
    $post_title = $data['post_title'];
    if (empty($post_title)) {
        return $data;
    }
    
    error_log('🔒 [PRE-INSERT] Verificando unicidad de tracking: ' . $post_title);
    
    global $wpdb;
    
    // Función para verificar si un tracking ya existe
    $tracking_exists = function($title) use ($wpdb) {
        return $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'wpcargo_shipment' 
            AND post_status = 'publish' 
            AND post_title = %s
            LIMIT 1",
            $title
        ));
    };
    
    // Verificar si ya existe
    $existing_id = $tracking_exists($post_title);
    
    if (!$existing_id) {
        error_log('✅ [PRE-INSERT] Tracking único: ' . $post_title);
        return $data;
    }
    
    // Si existe, extraer número y incrementar
    error_log('❌ [PRE-INSERT] TRACKING DUPLICADO DETECTADO: ' . $post_title . ' (existe ID: ' . $existing_id . ')');
    
    // Extraer el número al final (ej: MERC-000348 → MERC- + 000348 → MERC- + 000349)
    if (preg_match('/^(MERC-)?(\d+)$/', $post_title, $matches)) {
        $prefix = !empty($matches[1]) ? $matches[1] : 'MERC-';  // MERC-
        $number = (int)$matches[2];                             // 348
        $new_number = $number + 1;
        $num_length = strlen($matches[2]);                      // Mantener mismo número de dígitos
        $new_title = $prefix . str_pad($new_number, $num_length, '0', STR_PAD_LEFT);
        
        // Verificar recursivamente si el nuevo número también existe
        $attempts = 0;
        $max_attempts = 10;
        while ($tracking_exists($new_title) && $attempts < $max_attempts) {
            $new_number++;
            $new_title = $prefix . str_pad($new_number, strlen($matches[2]), '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        if ($attempts >= $max_attempts) {
            error_log('⚠️  [PRE-INSERT] Se alcanzó máximo de intentos, usando fallback con timestamp');
            $new_title = $post_title . '-' . time();
        }
        
        $data['post_title'] = $new_title;
        error_log('✅ [PRE-INSERT] TRACKING INCREMENTADO: ' . $post_title . ' → ' . $new_title);
    } else {
        // Si no puede parsear, usar timestamp como fallback
        $new_title = $post_title . '-' . time();
        $data['post_title'] = $new_title;
        error_log('⚠️  [PRE-INSERT] No se pudo extraer número, usando fallback: ' . $new_title);
    }
    
    return $data;
}


//===============================================================================================================
// STEP 0: Validar y prevenir duplicados de tracking
add_action('wpcie_after_save_csv_import', 'merc_validate_tracking_duplicate', 3, 2);
function merc_validate_tracking_duplicate($shipment_id, $record) {
    error_log('╔════════════════════════════════════════════════════════════╗');
    error_log('║ [MERC_IMPORT] STEP 0: VALIDACIÓN DE DUPLICADOS           ║');
    error_log('╚════════════════════════════════════════════════════════════╝');
    error_log('📦 Shipment ID: ' . $shipment_id);
    
    $tracking_number = get_post_meta($shipment_id, 'wpcargo_tracking_number', true);
    $shipment_number = get_post($shipment_id);
    $post_title = isset($shipment_number->post_title) ? $shipment_number->post_title : 'SIN TÍTULO';
    
    error_log('🔍 Tracking Number en meta: ' . ($tracking_number ?: 'VACÍO'));
    error_log('🔍 Post Title (número envío): ' . $post_title);
    
    global $wpdb;
    $tracking_to_check = $tracking_number ?: $post_title;
    
    if (!empty($tracking_to_check) && $tracking_to_check !== 'SIN TÍTULO') {
        $duplicates = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'wpcargo_shipment' 
            AND post_status = 'publish' 
            AND post_title = %s 
            AND ID != %d
            AND post_date > DATE_SUB(NOW(), INTERVAL 60 SECOND)",
            $tracking_to_check,
            $shipment_id
        ));
        
        if ((int)$duplicates > 0) {
            error_log('❌ [DUPLICADO DETECTADO] Se encontraron ' . $duplicates . ' duplicado(s) del número: ' . $tracking_to_check);
            error_log('❌ [DUPLICADO] Esto indica filas duplicadas en CSV o procesamiento paralelo');
            
            $unique_suffix = '-' . time();
            $new_post_title = $post_title . $unique_suffix;
            wp_update_post([
                'ID' => $shipment_id,
                'post_title' => $new_post_title
            ]);
            error_log('🔧 [REMEDICIÓN] Nuevo número asignado: ' . $new_post_title);
            update_post_meta($shipment_id, 'wpcargo_original_tracking', $tracking_to_check);
            update_post_meta($shipment_id, 'wpcargo_duplicate_attempt', 'yes');
        } else {
            error_log('✅ [VALIDACIÓN] No hay duplicados conocidos para: ' . $tracking_to_check);
        }
    } else {
        error_log('⚠️  [VALIDACIÓN] No se pudo determinar tracking/número para validación');
    }
    
    error_log('✅ [MERC_IMPORT] STEP 0: VALIDACIÓN - COMPLETADO ✓');
    error_log('');
}

//===============================================================================================================
// Hook into the import process to normalize shipment type: wpcargo_type_of_shipment -> tipo_envio
add_action('wpcie_after_save_csv_import', 'merc_normalize_tipo_envio_on_import', 5, 2);
function merc_normalize_tipo_envio_on_import($shipment_id, $record) {
    error_log('╔════════════════════════════════════════════════════════════╗');
    error_log('║ [MERC_IMPORT] STEP 1: NORMALIZAR TIPO DE ENVÍO - INICIO  ║');
    error_log('╚════════════════════════════════════════════════════════════╝');
    error_log('📦 Shipment ID: ' . $shipment_id);
    
    // Obtener el tracking number
    $tracking_number = get_post_meta($shipment_id, 'wpcargo_tracking_number', true);
    error_log('🔍 Tracking Number: ' . ($tracking_number ?: 'NO ASIGNADO AÚN'));
    error_log('📋 Record: ' . json_encode($record));
    
    // Buscar tipo de envío en múltiples lugares
    // Primero: intentar desde wpcargo_type_of_shipment en la DB
    $wpcargo_type = get_post_meta($shipment_id, 'wpcargo_type_of_shipment', true);
    error_log('🔍 wpcargo_type_of_shipment en DB: ' . ($wpcargo_type ? $wpcargo_type : 'VACÍO'));
    
    // Segundo: buscar en el record (datos del CSV)
    if (empty($wpcargo_type)) {
        // Intentar desde wpcargo_type_of_shipment en el record
        if (isset($record['wpcargo_type_of_shipment']) && !empty($record['wpcargo_type_of_shipment'])) {
            $wpcargo_type = $record['wpcargo_type_of_shipment'];
            error_log('✅ wpcargo_type_of_shipment encontrado en Record CSV: ' . $wpcargo_type);
        }
        // Intentar desde wpcargo_service_id en el record (nombre alternativo del CSV)
        elseif (isset($record['wpcargo_service_id']) && !empty($record['wpcargo_service_id'])) {
            $wpcargo_type = $record['wpcargo_service_id'];
            error_log('✅ wpcargo_service_id encontrado en Record CSV: ' . $wpcargo_type);
        }
    }
    
    // Tercero: fallback desde el perfil del usuario
    if (empty($wpcargo_type)) {
        error_log('⚠️  Tipo de envío vacío en CSV, buscando fallback del perfil...');
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID) {
            error_log('👤 Usuario actual: ' . $current_user->display_name . ' (ID: ' . $current_user->ID . ')');
            $wpcargo_type = get_user_meta($current_user->ID, 'tipo_envio_preferido', true);
            if (!empty($wpcargo_type)) {
                error_log('✅ Tipo de envío preferido del cliente: ' . $wpcargo_type);
            }
        }
    }
    
    if (!empty($wpcargo_type)) {
        // Normalizar el valor a minúsculas y sin espacios
        $tipo_normalizado = strtolower(trim($wpcargo_type));
        error_log('🔤 Normalizado a minúsculas: ' . $tipo_normalizado);
        
        // Mapear valores comunes a nuestros tipos
        $tipo_map = array(
            'normal'         => 'normal',
            'emprendedor' => 'normal',
            'express'        => 'express',
            'merc agencia'    => 'express',
            'agencia'        => 'express',
            'full fitment'   => 'full_fitment',
            'full'           => 'full_fitment',
            'merc full fitment' => 'full_fitment'
        );
        
        // Buscar el tipo en el mapa
        $tipo_final = isset($tipo_map[$tipo_normalizado]) ? $tipo_map[$tipo_normalizado] : $tipo_normalizado;
        error_log('🗺️  Mapa de tipo: ' . $tipo_normalizado . ' → ' . $tipo_final);
        
        // Guardar en ambas metas para compatibilidad
        update_post_meta($shipment_id, 'tipo_envio', $tipo_final);
        update_post_meta($shipment_id, 'wpcargo_type_of_shipment', $tipo_final);
        error_log('✅ Meta guardada - tipo_envio: ' . $tipo_final);
        error_log('✅ Meta guardada - wpcargo_type_of_shipment: ' . $tipo_final);
        
        // Si es tipo "express" (agencia MERC), establecer estado a RECEPCIONADO automáticamente
        if ($tipo_final === 'express') {
            update_post_meta($shipment_id, 'wpcargo_status', 'RECEPCIONADO');
            error_log('✅ Estado establecido a RECEPCIONADO para tipo AGENCIA/EXPRESS');
        }
        
        error_log('✅ [MERC_IMPORT] STEP 1: NORMALIZAR - COMPLETADO ✓');
    } else {
        error_log('❌ [MERC_IMPORT] WARNING: Tipo de envío NO encontrado para shipment ' . $shipment_id);
    }
    error_log('');
}

// Hook into the import process to auto-fill sender data from current user (client)
add_action('wpcie_after_save_csv_import', 'merc_auto_fill_sender_from_current_user', 8, 2);
function merc_auto_fill_sender_from_current_user($shipment_id, $record) {
    error_log('╔════════════════════════════════════════════════════════════╗');
    error_log('║ [MERC_IMPORT] STEP 2: AUTO-LLENAR DATOS DEL REMITENTE   ║');
    error_log('╚════════════════════════════════════════════════════════════╝');
    error_log('📦 Shipment ID: ' . $shipment_id);
    
    // Obtener el usuario actual (el que está importando)
    $current_user = wp_get_current_user();
    if (!$current_user || !$current_user->ID) {
        error_log('❌ ERROR: No current user for shipment ' . $shipment_id);
        error_log('');
        return;
    }
    
    error_log('👤 Usuario actual: ' . $current_user->display_name . ' (ID: ' . $current_user->ID . ')');
    
    // Obtener el ID de cliente desde registered_shipper o usar el usuario actual
    $registered_shipper = get_post_meta($shipment_id, 'registered_shipper', true);
    $client_id = !empty($registered_shipper) ? $registered_shipper : $current_user->ID;
    error_log('👥 Client ID: ' . $client_id);
    
    // Obtener datos del cliente
    $client = get_user_by('ID', $client_id);
    if (!$client) {
        error_log('❌ ERROR: Client not found - ID: ' . $client_id);
        error_log('');
        return;
    }
    
    error_log('✅ Cliente encontrado: ' . $client->display_name);
    
    // DEBUG: Mostrar TODOS los metas del cliente
    $all_metas = get_user_meta($client_id);
    error_log('🔍 DEBUG: TODOS los metas del cliente #' . $client_id . ':');
    foreach ($all_metas as $meta_key => $meta_values) {
        $value = is_array($meta_values) ? (isset($meta_values[0]) ? $meta_values[0] : '') : $meta_values;
        if (strpos($meta_key, 'wpcargo_') === 0 || strpos($meta_key, 'billing_') === 0 || strpos($meta_key, 'shipping_') === 0 || $meta_key === 'phone' || $meta_key === 'link_maps_remitente' || $meta_key === 'distrito') {
            error_log('   📌 ' . $meta_key . ' = ' . (is_array($value) ? json_encode($value) : $value));
        }
    }
    
    // Obtener meta datos del cliente - USAR LOS NOMBRES CORRECTOS QUE EXISTEN EN EL PERFIL
    $shipper_name = get_user_meta($client_id, 'billing_company', true);
    $shipper_phone = get_user_meta($client_id, 'phone', true);
    $shipper_address = get_user_meta($client_id, 'billing_address_1', true);
    $shipper_email = get_user_meta($client_id, 'billing_email', true);
    $distrito_recojo = get_user_meta($client_id, 'distrito', true);
    $link_maps_remitente = get_user_meta($client_id, 'link_maps_remitente', true);
    $tiendaname = get_user_meta($client_id, 'wpcargo_tiendaname', true);
    $brand_name = get_user_meta($client_id, 'wpcargo_brand_name', true);
    $comments_default = get_user_meta($client_id, 'wpcargo_comments_default', true);
    
    error_log('📋 Datos del cliente obtenidos (usando nombres correctos del perfil):');
    error_log('  - Nombre (billing_company): ' . ($shipper_name ?: 'VACÍO'));
    error_log('  - Teléfono (phone): ' . ($shipper_phone ?: 'VACÍO'));
    error_log('  - Dirección (billing_address_1): ' . ($shipper_address ?: 'VACÍO'));
    error_log('  - Email (billing_email): ' . ($shipper_email ?: 'VACÍO'));
    error_log('  - Distrito (distrito): ' . ($distrito_recojo ?: 'VACÍO'));
    error_log('  - Link Maps (link_maps_remitente): ' . ($link_maps_remitente ?: 'VACÍO'));
    error_log('  - Marca (wpcargo_brand_name): ' . ($brand_name ?: 'VACÍO'));
    error_log('  - Tienda (wpcargo_tiendaname): ' . ($tiendaname ?: 'VACÍO'));
    error_log('  - Comentarios (wpcargo_comments_default): ' . ($comments_default ?: 'VACÍO'));
    
    // Fallback: si no tiene nombre, usar el nombre del usuario
    if (empty($shipper_name)) {
        error_log('⚠️  Nombre vacío, usando fallback: ' . $client->display_name);
        $shipper_name = $client->display_name;
    }
    
    // Fallback: si no tiene email, usar el email del usuario
    if (empty($shipper_email)) {
        error_log('⚠️  Email vacío, usando fallback: ' . $client->user_email);
        $shipper_email = $client->user_email;
    }
    
    // Fallback: si no tiene tiendaname, usar billing_company (nombre de la empresa)
    if (empty($tiendaname) && !empty($shipper_name)) {
        error_log('⚠️  Tienda vacía, usando fallback: ' . $shipper_name);
        $tiendaname = $shipper_name;
    }
    
    // Fallback: si no tiene brand_name, usar el nombre de la empresa también
    if (empty($brand_name) && !empty($shipper_name)) {
        error_log('⚠️  Marca vacía, usando fallback: ' . $shipper_name);
        $brand_name = $shipper_name;
    }
    
    // Auto-rellenar datos del remitente en el shipment
    $sender_meta = array(
        'wpcargo_shipper_name'    => $shipper_name,
        'wpcargo_shipper_phone'   => $shipper_phone,
        'wpcargo_shipper_address' => $shipper_address,
        'wpcargo_shipper_email'   => $shipper_email,
        'wpcargo_distrito_recojo' => $distrito_recojo,
        'link_maps_remitente'     => $link_maps_remitente,
        'wpcargo_tiendaname'      => $tiendaname,
        'registered_shipper'      => $client_id,
        'wpcargo_comments'        => $comments_default
    );
    
    error_log('💾 Guardando metas del remitente:');
    
    // Guardar cada meta dato - SIEMPRE guardar, incluso si está vacío
    foreach ($sender_meta as $meta_key => $value) {
        update_post_meta($shipment_id, $meta_key, $value);
        if (!empty($value)) {
            error_log('  ✅ ' . $meta_key . ' = ' . $value);
        } else {
            error_log('  ⊘ ' . $meta_key . ' = VACÍO (guardado igualmente)');
        }
    }
    
    // Leer el tipo de envío que fue procesado
    $tipo_envio = get_post_meta($shipment_id, 'tipo_envio', true);
    error_log('📦 Tipo de envío: ' . ($tipo_envio ?: 'NO PROCESADO'));
    
    error_log('✅ [MERC_IMPORT] STEP 2: AUTO-LLENAR - COMPLETADO ✓');
    error_log('');
}

//===============================================================================================================
// STEP 3: Validación Final de Duplicados (ejecuta DESPUÉS de todo lo demás)
add_action('wpcie_after_save_csv_import', 'merc_validate_tracking_duplicate_final', 15, 2);
function merc_validate_tracking_duplicate_final($shipment_id, $record) {
    error_log('╔════════════════════════════════════════════════════════════╗');
    error_log('║ [MERC_IMPORT] STEP 3: VALIDACIÓN FINAL DE DUPLICADOS     ║');
    error_log('╚════════════════════════════════════════════════════════════╝');
    error_log('📦 Shipment ID: ' . $shipment_id);
    
    $tracking_number = get_post_meta($shipment_id, 'wpcargo_tracking_number', true);
    $shipment_number = get_post($shipment_id);
    $post_title = isset($shipment_number->post_title) ? $shipment_number->post_title : 'SIN TÍTULO';
    
    error_log('🔍 Tracking Number en meta: ' . ($tracking_number ?: 'VACÍO'));
    error_log('🔍 Post Title (número envío): ' . $post_title);
    
    global $wpdb;
    $tracking_to_check = $tracking_number ?: $post_title;
    
    if (!empty($tracking_to_check) && $tracking_to_check !== 'SIN TÍTULO') {
        $duplicates = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'wpcargo_shipment' 
            AND post_status = 'publish' 
            AND post_title = %s 
            AND ID != %d",
            $tracking_to_check,
            $shipment_id
        ));
        
        if ((int)$duplicates > 0) {
            error_log('❌ [DUPLICADO ENCONTRADO EN VALIDACIÓN FINAL] Se encontraron ' . $duplicates . ' duplicado(s): ' . $tracking_to_check);
            
            $unique_suffix = '-' . time();
            $new_post_title = $post_title . $unique_suffix;
            wp_update_post([
                'ID' => $shipment_id,
                'post_title' => $new_post_title
            ]);
            error_log('🔧 [REMEDICIÓN FINAL] Número actualizado: ' . $post_title . ' → ' . $new_post_title);
            update_post_meta($shipment_id, 'wpcargo_original_tracking', $tracking_to_check);
            update_post_meta($shipment_id, 'wpcargo_duplicate_attempt', 'yes');
        } else {
            error_log('✅ [VALIDACIÓN FINAL] No hay duplicados: ' . $tracking_to_check);
        }
    } else {
        error_log('⚠️  [VALIDACIÓN FINAL] No se pudo determinar tracking/número para validación');
    }
    
    error_log('✅ [MERC_IMPORT] STEP 3: VALIDACIÓN FINAL - COMPLETADO ✓');
    error_log('');
}

//===============================================================================================================
// STEP 4: Auto-asignación de Contenedores por Distrito
add_action('wpcie_after_save_csv_import', 'merc_auto_assign_on_import', 18, 2);
function merc_auto_assign_on_import($shipment_id, $record) {
    error_log('╔════════════════════════════════════════════════════════════╗');
    error_log('║ [MERC_IMPORT] STEP 4: AUTO-ASIGNACIÓN POR DISTRITO       ║');
    error_log('╚════════════════════════════════════════════════════════════╝');
    error_log('📦 Shipment ID: ' . $shipment_id);
    
    // Llamar a la función de auto-asignación existente
    merc_auto_assign_shipment_to_container($shipment_id);
    
    error_log('✅ [MERC_IMPORT] STEP 4: AUTO-ASIGNACIÓN - COMPLETADO ✓');
    error_log('');
}

// Hook into the import process to auto-fill sender data (legacy - kept for compatibility)
add_action('wpcie_after_save_csv_import', 'auto_fill_sender_import_data', 10, 2);
function auto_fill_sender_import_data($shipment_id, $record) {
    // Mantener por compatibilidad - ya fue manejado por merc_auto_fill_sender_from_current_user
}
// Optional: Add filter to ensure empty sender fields in CSV don't overwrite existing data
add_filter('wpcie_after_save_meta_csv_import', 'prevent_empty_sender_overwrite', 10, 3);
function prevent_empty_sender_overwrite($shipment_id, $meta_key, $value) {
    // Lista de campos del remitente que queremos proteger (no dejar que el CSV vacío los sobre-escriba)
    $sender_fields = array(
        'tipo_envio',
        'wpcargo_shipper_name',
        'wpcargo_shipper_phone',
        'wpcargo_shipper_address',
        'wpcargo_shipper_email',
        'wpcargo_distrito_recojo',
        'wpcargo_brand_name',
        'wpcargo_tiendaname',
        'link_maps_remitente',
        'registered_shipper',
        'wpcargo_comments'
    );
    
    // Si el campo está vacío y es un campo del remitente, mantener el valor existente
    if (in_array($meta_key, $sender_fields) && empty($value)) {
        $existing_value = get_post_meta($shipment_id, $meta_key, true);
        if (!empty($existing_value)) {
            error_log('🛡️ [MERC_IMPORT] PROTECCIÓN: Campo vacío NO sobre-escribe - Shipment: ' . $shipment_id . ' | Campo: ' . $meta_key . ' | Valor existente protegido: ' . $existing_value);
            // No sobre-escribir con valor vacío
            return null; // Devolver null para no sobre-escribir
        }
    }
    
    return null; // Permitir que el filtro continúe
}

/**
 * Sincronizar monto después de importación CSV
 * Hook general que se dispara para cualquier importación
 * Asegura que wpcargo_total_cobrar tenga el valor correcto
 * Busca en meta keys importados Y en el record del CSV
 */
add_action('wpcie_after_save_csv_import', 'sync_monto_on_csv_import', 20, 2);
function sync_monto_on_csv_import($shipment_id, $record) {
    $monto_importado = 0;
    
    // Primero, buscar en meta keys importados
    $monto_meta_keys = array(
        'wpcargo_monto',
        'monto',
        'amount',
        'price',
        'total',
        'cobrar',
        'pagar',
        'wpcargo_price',
        'wpcargo_amount',
        'wpcargo_total_cobrar' // verificar si ya fue asignado
    );
    
    foreach ($monto_meta_keys as $key) {
        $valor = floatval(get_post_meta($shipment_id, $key, true));
        if ($valor > 0) {
            $monto_importado = $valor;
            if ($key !== 'wpcargo_total_cobrar') break; // No contar si ya está en wpcargo_total_cobrar
        }
    }
    
    // Si no encontró en meta, buscar directamente en el record del CSV
    if ($monto_importado <= 0 && !empty($record) && is_array($record)) {
        $record_keys = array(
            'monto', 'moonto', 'amount', 'price', 'total', 'cobrar', 
            'pagar', 'wpcargo_monto', 'wpcargo_price', 'wpcargo_amount',
            'product_price', 'shipping_price', 'total_price', 'valor'
        );
        
        foreach ($record_keys as $key) {
            if (isset($record[$key])) {
                $valor = floatval($record[$key]);
                if ($valor > 0) {
                    $monto_importado = $valor;
                    // Auto-guardar en el meta key apropiado si no estaba
                    if (!get_post_meta($shipment_id, 'wpcargo_monto', true)) {
                        update_post_meta($shipment_id, 'wpcargo_monto', $monto_importado);
                    }
                    break;
                }
            }
        }
    }
    
    // Si hay monto encontrado pero NO hay wpcargo_total_cobrar, copiar allí
    if ($monto_importado > 0) {
        $total_cobrar = floatval(get_post_meta($shipment_id, 'wpcargo_total_cobrar', true));
        if ($total_cobrar <= 0) {
            update_post_meta($shipment_id, 'wpcargo_total_cobrar', $monto_importado);
            error_log('💰 [MONTO-SYNC] Shipment #' . $shipment_id . ' - CSV Import: Sincronizado monto: ' . $monto_importado . ' a wpcargo_total_cobrar');
        }
    }
}

/**
 * Helper: Obtener tarifas de envío según tipo (EXPRESS o NORMAL)
 */
function merc_get_district_prices($tipo_envio = 'normal') {
    // Tabla de precios para EXPRESS
    $prices_express = array(
        "El Agustino" => 8.00,
        "San Juan de Lurigancho" => 8.00,
        "Santa Anita" => 8.00,
        "Ate - Salamanca - Vitarte" => 10.00,
        "La Molina" => 8.00,
        "Santa Clara" => 10.00,
        "Huaycan (Gloria Grande - Pariachi)" => 12.00,
        "Molina Alta (Musa - Portada del Sol - Planicie)" => 10.00,
        "Huachipa (Zoológico de Huachipa)" => 10.00,
        "Callao" => 8.00,
        "Bellavista" => 8.00,
        "La Punta - Callao" => 10.00,
        "La Perla" => 8.00,
        "Pueblo Libre" => 8.00,
        "Lima Cercado" => 8.00,
        "Breña" => 8.00,
        "San Miguel" => 8.00,
        "Magdalena" => 8.00,
        "Sarita Colonia (Comisaría Sarita Colonia)" => 8.00,
        "Carmen de la Legua" => 8.00,
        "Rímac" => 8.00,
        "Independencia" => 8.00,
        "Comas" => 8.00,
        "Carabayllo" => 10.00,
        "Puente Piedra" => 10.00,
        "Ventanilla" => 10.00,
        "Los Olivos" => 8.00,
        "San Martin de Porres" => 8.00,
        "Santiago de Surco" => 8.00,
        "San Juan de Miraflores" => 8.00,
        "Villa María del Triunfo" => 10.00,
        "Villa El Salvador" => 10.00,
        "Chorrillos" => 8.00,
        "Barranco" => 8.00,
        "Jesús María" => 8.00,
        "Lince" => 8.00,
        "La Victoria" => 8.00,
        "San Isidro" => 8.00,
        "Surquillo" => 8.00,
        "San Borja" => 8.00,
        "San Luis" => 8.00,
        "Centro de Lima" => 8.00
    );
    
    // Tabla de precios para NORMAL
    $prices_normal = array(
        "El Agustino" => 10.00,
        "San Juan de Lurigancho" => 10.00,
        "Santa Anita" => 10.00,
        "Ate - Salamanca - Vitarte" => 10.00,
        "La Molina" => 10.00,
        "Santa Clara" => 12.00,
        "Huaycan (Gloria Grande - Pariachi)" => 14.00,
        "Molina Alta (Musa - Portada del Sol - Planicie)" => 12.00,
        "Huachipa (Zoológico de Huachipa)" => 12.00,
        "Callao" => 10.00,
        "Bellavista" => 10.00,
        "La Punta - Callao" => 12.00,
        "La Perla" => 10.00,
        "Pueblo Libre" => 10.00,
        "Lima Cercado" => 10.00,
        "Breña" => 10.00,
        "San Miguel" => 10.00,
        "Magdalena" => 10.00,
        "Sarita Colonia (Comisaría Sarita Colonia)" => 10.00,
        "Carmen de la Legua" => 10.00,
        "Rímac" => 10.00,
        "Independencia" => 10.00,
        "Comas" => 10.00,
        "Carabayllo" => 13.00,
        "Puente Piedra" => 13.00,
        "Ventanilla" => 13.00,
        "Los Olivos" => 10.00,
        "San Martin de Porres" => 10.00,
        "Santiago de Surco" => 10.00,
        "San Juan de Miraflores" => 10.00,
        "Villa María del Triunfo" => 12.00,
        "Villa El Salvador" => 12.00,
        "Chorrillos" => 10.00,
        "Barranco" => 10.00,
        "Jesús María" => 10.00,
        "Lince" => 10.00,
        "La Victoria" => 10.00,
        "San Isidro" => 10.00,
        "Surquillo" => 10.00,
        "San Borja" => 10.00,
        "San Luis" => 10.00,
        "Centro de Lima" => 10.00
    );
    
    return ($tipo_envio === 'express') ? $prices_express : $prices_normal;
}

/**
 * Helper: Encontrar coincidencia de tarifa por distrito
 */
function merc_find_district_price($destination, $tipo_envio = 'normal') {
    $prices = merc_get_district_prices($tipo_envio);
    $destination = trim($destination);
    
    // Búsqueda exacta
    if (isset($prices[$destination])) {
        return $prices[$destination];
    }
    
    // Búsqueda por nombre principal (antes del paréntesis)
    foreach ($prices as $district => $price) {
        $main_name = trim(explode('(', $district)[0]);
        if (strtolower($main_name) === strtolower($destination)) {
            return $price;
        }
    }
    
    // Búsqueda parcial
    $destination_lower = strtolower($destination);
    foreach ($prices as $district => $price) {
        if (strpos(strtolower($district), $destination_lower) !== false || 
            strpos($destination_lower, strtolower($district)) !== false) {
            return $price;
        }
    }
    
    return 0.00; // No encontrado
}

/**
 * Guardar datos financieros durante IMPORTACIÓN MASIVA (CSV)
 * Lee desde $record en lugar de $_POST
 * Prioridad: 25 (después de sync_monto_on_csv_import que es 20)
 */
add_action('wpcie_after_save_csv_import', 'merc_save_financial_data_on_import', 25, 2);
function merc_save_financial_data_on_import($shipment_id, $record) {
    if ( get_post_type( $shipment_id ) !== 'wpcargo_shipment' ) {
        return;
    }

    error_log("💰 [SAVE_FINANCIAL_IMPORT] === GUARDANDO DATOS FINANCIEROS (CSV) - Envío #{$shipment_id} ===");
    
    // PASO 1: Verificar modo de pago (si es "no cobrar")
    $es_no_cobrar = false;
    if ( !empty($record) && is_array($record) ) {
        if (isset($record['payment_wpcargo_mode_field'])) {
            $modo_pago = strtolower(trim($record['payment_wpcargo_mode_field']));
            error_log("💰 [SAVE_FINANCIAL_IMPORT] Modo de pago encontrado: {$modo_pago}");
            
            if (strpos($modo_pago, 'no cobrar') !== false || $modo_pago === 'no charge' || $modo_pago === '0') {
                $es_no_cobrar = true;
                error_log("💰 [SAVE_FINANCIAL_IMPORT] ✅ Detectado modo NO COBRAR");
            }
        }
    }
    
    // PASO 2: Si es "no cobrar", monto = 0
    if ($es_no_cobrar) {
        update_post_meta($shipment_id, 'wpcargo_monto', 0);
        error_log("💰 [SAVE_FINANCIAL_IMPORT] Monto establecido a 0 (NO COBRAR)");
    }
    
    // PASO 2.5: CALCULAR COSTO DE ENVÍO POR DISTRITO (para todos los envíos)
    // Primero intentar leer del CSV, si no viene → calcular por distrito
    $costo_envio_en_db = get_post_meta($shipment_id, 'wpcargo_costo_envio', true);
    $costo_envio_encontrado = false;
    
    // Intentar buscar costo en CSV
    $costo_envio_keys = array('wpcargo_costo_envio', 'costo_envio', 'costo_shipping', 'shipping_cost', 'cost', 'envio');
    if ( !empty($record) && is_array($record) ) {
        foreach ($costo_envio_keys as $key) {
            if (isset($record[$key]) && !empty($record[$key])) {
                $costo_envio = sanitize_text_field( $record[$key] );
                error_log("💰 [SAVE_FINANCIAL_IMPORT] Costo envío encontrado en CSV: {$costo_envio} (clave: {$key})");
                update_post_meta( $shipment_id, 'wpcargo_costo_envio', $costo_envio );
                $costo_envio_encontrado = true;
                break;
            }
        }
    }
    
    // Si NO viene en el CSV → calcular por distrito automáticamente
    if (!$costo_envio_encontrado && empty($costo_envio_en_db)) {
        // Obtener distrito destino
        $distrito_destino = get_post_meta($shipment_id, 'wpcargo_distrito_destino', true);
        if (empty($distrito_destino) && !empty($record) && is_array($record)) {
            if (isset($record['wpcargo_distrito_destino'])) {
                $distrito_destino = $record['wpcargo_distrito_destino'];
            }
        }
        
        if (!empty($distrito_destino)) {
            // Obtener tipo de envío
            $tipo_envio = get_post_meta($shipment_id, 'tipo_envio', true);
            if (empty($tipo_envio)) {
                $tipo_envio = 'normal'; // default
            }
            
            // Calcular costo de envío por distrito
            $costo_envio = merc_find_district_price($distrito_destino, $tipo_envio);
            if ($costo_envio > 0) {
                update_post_meta($shipment_id, 'wpcargo_costo_envio', $costo_envio);
                error_log("💰 [SAVE_FINANCIAL_IMPORT] ✅ Costo envío AUTO-CALCULADO por distrito ({$distrito_destino}, tipo: {$tipo_envio}): S/. {$costo_envio}");
                $costo_envio_encontrado = true;
            } else {
                error_log("⚠️ [SAVE_FINANCIAL_IMPORT] No se encontró tarifa para distrito: {$distrito_destino} (tipo: {$tipo_envio})");
            }
        } else {
            error_log("⚠️ [SAVE_FINANCIAL_IMPORT] Distrito destino no encontrado, no se puede calcular costo");
        }
    }
    
    // PASO 3: Mapeo de posibles columnas del CSV para otros costos
    $costo_producto_keys = array('wpcargo_costo_producto', 'costo_producto', 'product_cost', 'cost_product', 'precio_producto');
    $cargo_remitente_keys = array('wpcargo_cargo_remitente', 'cargo_remitente', 'sender_charge', 'cargo');
    
    // Guardar costo producto
    if ( !empty($record) && is_array($record) ) {
        foreach ($costo_producto_keys as $key) {
            if (isset($record[$key]) && !empty($record[$key])) {
                $costo_producto = sanitize_text_field( $record[$key] );
                error_log("💰 [SAVE_FINANCIAL_IMPORT] Costo producto encontrado: {$costo_producto} (clave: {$key})");
                update_post_meta( $shipment_id, 'wpcargo_costo_producto', $costo_producto );
                break;
            }
        }
    }
    
    // Guardar cargo remitente
    if ( !empty($record) && is_array($record) ) {
        foreach ($cargo_remitente_keys as $key) {
            if (isset($record[$key]) && !empty($record[$key])) {
                $cargo_remitente = sanitize_text_field( $record[$key] );
                error_log("💰 [SAVE_FINANCIAL_IMPORT] Cargo remitente encontrado: {$cargo_remitente} (clave: {$key})");
                update_post_meta( $shipment_id, 'wpcargo_cargo_remitente', $cargo_remitente );
                break;
            }
        }
    }
    
    // PASO 4: Guardar comentarios (wpcargo_comments)
    if ( !empty($record) && is_array($record) ) {
        if ( isset($record['wpcargo_comments']) && !empty($record['wpcargo_comments']) ) {
            $comentarios = sanitize_textarea_field( $record['wpcargo_comments'] );
            error_log("💰 [SAVE_FINANCIAL_IMPORT] Comentarios encontrados en CSV: {$comentarios}");
            update_post_meta( $shipment_id, 'wpcargo_comments', $comentarios );
        }
    }
    
    // PASO 4.5: Guardar cambio de producto desde CSV
    if ( !empty($record) && is_array($record) ) {
        $cambio_producto_keys = array('cambio_producto', 'cambio de producto', 'product_change', 'productchange', 'wpcargo_cambio_producto');
        foreach ($cambio_producto_keys as $key) {
            if (isset($record[$key]) && !empty($record[$key])) {
                $cambio_producto = sanitize_text_field( $record[$key] );
                // Normalizar valores: Sí/Yes/Y/1 = "Sí", No/No/N/0 = "No"
                $cambio_normalizado = 'No';
                if (in_array(strtolower($cambio_producto), array('sí', 'yes', 'y', '1', 'true', 'si'))) {
                    $cambio_normalizado = 'Sí';
                }
                error_log("💰 [SAVE_FINANCIAL_IMPORT] Cambio de producto encontrado en CSV: {$cambio_producto} → normalizado a: {$cambio_normalizado} (clave: {$key})");
                update_post_meta( $shipment_id, 'cambio_producto', $cambio_normalizado );
                break;
            }
        }
    }
    
    error_log("💰 [SAVE_FINANCIAL_IMPORT] === GUARDANDO ESTADOS DE PAGO ===");
    
    // Obtener monto para determinar quién paga
    // En importación, el monto está en wpcargo_total_cobrar (guardado por sync_monto_on_csv_import)
    $monto = get_post_meta( $shipment_id, 'wpcargo_total_cobrar', true );
    if (empty($monto)) {
        // Fallback a wpcargo_monto si no está en wpcargo_total_cobrar
        $monto = get_post_meta( $shipment_id, 'wpcargo_monto', true );
    }
    $monto = floatval( $monto );
    error_log("💰 [SAVE_FINANCIAL_IMPORT] Monto obtenido desde wpcargo_total_cobrar: {$monto}");

    if ( $monto == 0 ) {
        update_post_meta( $shipment_id, 'wpcargo_quien_paga', 'remitente' );
        update_post_meta( $shipment_id, 'wpcargo_cobrado_por_motorizado', '0' );
        error_log("💰 [SAVE_FINANCIAL_IMPORT] Monto 0: El remitente paga");
    } else {
        // En este negocio, la marca/cliente (registered_shipper) es la responsable
        // cuando falta cobrar a MERC — no el cliente final. Marcar como 'remitente'.
        update_post_meta( $shipment_id, 'wpcargo_quien_paga', 'remitente' );
        update_post_meta( $shipment_id, 'wpcargo_cobrado_por_motorizado', $monto );
        error_log("💰 [SAVE_FINANCIAL_IMPORT] Monto > 0: Se marca remitente (marca) como responsable - Monto a cobrar: {$monto}");
    }

    if ( ! get_post_meta( $shipment_id, 'wpcargo_estado_pago_motorizado', true ) ) {
        update_post_meta( $shipment_id, 'wpcargo_estado_pago_motorizado', 'pendiente' );
        error_log("💰 [SAVE_FINANCIAL_IMPORT] Estado pago motorizado: pendiente");
    }
    
    if ( ! get_post_meta( $shipment_id, 'wpcargo_cliente_pago_a', true ) ) {
        update_post_meta( $shipment_id, 'wpcargo_cliente_pago_a', 'pendiente' );
        error_log("💰 [SAVE_FINANCIAL_IMPORT] Estado pago cliente: pendiente");
    }
    
    error_log("💰 [SAVE_FINANCIAL_IMPORT] === FIN GUARDADO DATOS FINANCIEROS ===");
}

/**
 * STEP 5: Auto-asignar Motorizado durante importación CSV
 * Se ejecuta DESPUÉS de todos los anteriores (STEP 0-4)
 * Prioridad: 30 (la más alta, ejecuta al final)
 */
add_action('wpcie_after_save_csv_import', 'merc_auto_motorizado_on_import', 30, 2);
function merc_auto_motorizado_on_import($shipment_id, $record) {
    error_log('');
    error_log('╔════════════════════════════════════════════════════════════╗');
    error_log('║ [MERC_IMPORT] STEP 5: AUTO-ASIGNACIÓN DE MOTORIZADO        ║');
    error_log('╚════════════════════════════════════════════════════════════╝');
    
    // Ejecutar la función de auto-asignación (ya tiene toda la lógica + logs)
    merc_auto_asignar_motorizado_importacion($shipment_id);
    
    error_log('✅ [MERC_IMPORT] STEP 5: AUTO-MOTORIZADO - COMPLETADO ✓');
    error_log('');
}

// ========== GUARDAR TIPO DE ENVÍO AL CREAR SHIPMENT ==========

function guardar_tipo_envio_shipment($post_id) {
    error_log('🔄 [guardar_tipo_envio_shipment] INICIADO - post_id: ' . $post_id);
    error_log('   post_type: ' . get_post_type($post_id));
    
    if(get_post_type($post_id) !== 'wpcargo_shipment') {
        error_log('   ❌ No es wpcargo_shipment, abandonando');
        return;
    }
    
    error_log('   ✅ Es wpcargo_shipment, continuando...');
    
    $tipo_envio = '';
    
    // Buscar en múltiples lugares
    if(isset($_GET['type'])) {
        $tipo_envio = sanitize_text_field($_GET['type']);
        error_log('📌 guardar_tipo_envio - GET[type]: ' . $tipo_envio);
    }
    
    if(empty($tipo_envio) && isset($_POST['tipo_envio'])) {
        $tipo_envio = sanitize_text_field($_POST['tipo_envio']);
        error_log('📌 guardar_tipo_envio - POST[tipo_envio]: ' . $tipo_envio);
    }
    
    if(empty($tipo_envio) && isset($_POST['type'])) {
        $tipo_envio = sanitize_text_field($_POST['type']);
        error_log('📌 guardar_tipo_envio - POST[type]: ' . $tipo_envio);
    }
    
    if(empty($tipo_envio) && isset($_REQUEST['type'])) {
        $tipo_envio = sanitize_text_field($_REQUEST['type']);
        error_log('📌 guardar_tipo_envio - REQUEST[type]: ' . $tipo_envio);
    }
    
    if(!empty($tipo_envio)) {
        update_post_meta($post_id, 'tipo_envio', $tipo_envio);
        error_log('✅ guardar_tipo_envio ÉXITO - ID: ' . $post_id . ' | Tipo: ' . $tipo_envio);
        
        // Validación: Si es MERC AGENCIA (express), verificar que tenga Link Google Maps
        if (strtolower($tipo_envio) === 'express') {
            $link_maps = get_post_meta($post_id, 'link_maps', true);
            
            // Si no existe, intentar obtener desde $_POST
            if (empty($link_maps) && isset($_POST['link_maps'])) {
                $link_maps = sanitize_text_field($_POST['link_maps']);
            }
            
            if (empty($link_maps)) {
                error_log('⚠️ Envío MERC AGENCIA sin Link Google Maps - ID: ' . $post_id);
            }
        }
    } else {
        // No reportar como error en importaciones masivas (STEP 1 lo hará)
        error_log('⏭️  guardar_tipo_envio - No hay tipo_envio aún (normalizará en STEP 1 si es importación) - ID: ' . $post_id);
    }
}

// DIAGNÓSTICO: Logs para verificar que se cargan los hooks
add_action('wpcargo_after_save_shipment', 'guardar_tipo_envio_shipment', 1, 1);

add_action('save_post_wpcargo_shipment', 'guardar_tipo_envio_shipment', 1, 1);

add_action('wp_insert_post', 'guardar_tipo_envio_shipment', 1, 1);

// Ocultar secciones para clientes en formulario de crear envío
add_action('wp_footer', 'merc_ocultar_campos_clientes_crear_envio', 4);
function merc_ocultar_campos_clientes_crear_envio() {
    if ( ! is_user_logged_in() ) return;
    
    $current_user = wp_get_current_user();
    $es_cliente = in_array('wpcargo_client', $current_user->roles);
    
    // Solo ocultar si es cliente Y estamos en el formulario de crear envío
    if ( $es_cliente && isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'add' ) {
        ?>
        <style>
            /* Ocultar campos administrativos para clientes en crear envío */
            #history_info,
            .history_info,
            [data-section="history_info"],
            #assigned-driver-wrapper,
            .assigned-driver-wrapper,
            [data-section="assigned-driver-wrapper"] {
                display: none !important;
            }
        </style>
        <script>
        jQuery(document).ready(function($){
            // Fallback con JS en caso que no funcione CSS
            $('#history_info, .history_info, #assigned-driver-wrapper, .assigned-driver-wrapper').hide();
            
            // Ocultar parent divs si es necesario
            $('[data-section="history_info"], [data-section="assigned-driver-wrapper"]').hide();
        });
        </script>
        <?php
    }
}

// Agregar campo oculto al formulario y validación de bloqueo
add_action('wp_footer', 'agregar_campo_tipo_envio_formulario', 5);
function agregar_campo_tipo_envio_formulario() {
    if(isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'add' && isset($_GET['type'])) {
        $tipo = sanitize_text_field($_GET['type']);
        
        // Validar bloqueo por tipo si es cliente
        $current_user = wp_get_current_user();
        $esta_bloqueado = false;
        $mensaje_bloqueo = '';
        $debug_info = '';
        
        if (in_array('wpcargo_client', $current_user->roles)) {
            // Log de debugging
            $hora_actual = current_time('H:i');
            $debug_info = "DEBUG: Usuario={$current_user->ID}, Tipo={$tipo}, Hora={$hora_actual}";
            error_log("🔍 FRONTEND VALIDATION: {$debug_info}");
            
            $esta_bloqueado = merc_check_tipo_envio_blocked($current_user->ID, $tipo);
            error_log("🎯 RESULTADO BLOQUEO: " . ($esta_bloqueado ? "BLOQUEADO" : "PERMITIDO"));
            
            if ($esta_bloqueado) {
                $tipo_nombre = '';
                $estado_fin = merc_get_estado_financiero($current_user->ID);
                $cuenta_envios = merc_count_envios_del_tipo_hoy($current_user->ID, $tipo);
                
                if (stripos($tipo, 'emprendedor') !== false || $tipo === 'normal') {
                    $tipo_nombre = 'MERC EMPRENDEDOR';
                    $limite = '10:00 AM';
                    if ($cuenta_envios == 0) {
                        $mensaje_bloqueo = "Este tipo de envío está bloqueado.\n\nYa pasaron las {$limite} sin envíos registrados de hoy.\n\nPuedes intentar mañana a partir de las 00:00.";
                    } else {
                        if ($estado_fin['estado'] === 'cliente_debe') {
                            $mensaje_bloqueo = "Este tipo de envío está BLOQUEADO.\n\n🚫 Tienes una deuda pendiente con Mercourier.\n\nDebes liquidar tu deuda antes de crear envíos.";
                        } elseif ($estado_fin['estado'] === 'merc_debe') {
                            $now = merc_get_current_time();
                            $mensaje_bloqueo = "Este tipo de envío está temporalmente BLOQUEADO.\n\n⏰ Se desbloqueará a las 19:30 (7:30 PM).\n\nEn ese momento podrás crear envíos para el día siguiente.";
                        } else {
                            $mensaje_bloqueo = "Este tipo de envío está bloqueado.\n\nYa tienes envíos de hoy y pasaron las {$limite}.\n\nPuedes crear más envíos mañana.";
                        }
                    }
                } elseif (stripos($tipo, 'agencia') !== false || $tipo === 'express') {
                    $tipo_nombre = 'MERC AGENCIA';
                    $limite_sin = '12:30 PM';
                    $limite_con = '13:00 (1:00 PM)';
                    if ($cuenta_envios == 0) {
                        $mensaje_bloqueo = "Este tipo de envío está bloqueado.\n\nYa pasaron las {$limite_sin} sin envíos registrados de hoy.\n\nPuedes intentar mañana a partir de las 00:00.";
                    } else {
                        if ($estado_fin['estado'] === 'cliente_debe') {
                            $mensaje_bloqueo = "Este tipo de envío está BLOQUEADO.\n\n🚫 Tienes una deuda pendiente con Mercourier.\n\nDebes liquidar tu deuda antes de crear envíos.";
                        } elseif ($estado_fin['estado'] === 'merc_debe') {
                            $mensaje_bloqueo = "Este tipo de envío está temporalmente BLOQUEADO.\n\n⏰ Se desbloqueará a las 19:30 (7:30 PM).\n\nEn ese momento podrás crear envíos para el día siguiente.";
                        } else {
                            $mensaje_bloqueo = "Este tipo de envío está bloqueado.\n\nYa tienes envíos de hoy y pasaron las {$limite_con}.\n\nPuedes crear más envíos mañana.";
                        }
                    }
                } elseif (stripos($tipo, 'full') !== false || $tipo === 'full_fitment') {
                    $tipo_nombre = 'MERC FULL FITMENT';
                    $limite_sin = '11:30 AM';
                    $limite_con = '12:15 PM';
                    if ($cuenta_envios == 0) {
                        $mensaje_bloqueo = "Este tipo de envío está bloqueado.\n\nYa pasaron las {$limite_sin} sin envíos registrados de hoy.\n\nPuedes intentar mañana a partir de las 00:00.";
                    } else {
                        if ($estado_fin['estado'] === 'cliente_debe') {
                            $mensaje_bloqueo = "Este tipo de envío está BLOQUEADO.\n\n🚫 Tienes una deuda pendiente con Mercourier.\n\nDebes liquidar tu deuda antes de crear envíos.";
                        } elseif ($estado_fin['estado'] === 'merc_debe') {
                            $mensaje_bloqueo = "Este tipo de envío está temporalmente BLOQUEADO.\n\n⏰ Se desbloqueará a las 19:30 (7:30 PM).\n\nEn ese momento podrás crear envíos para el día siguiente.";
                        } else {
                            $mensaje_bloqueo = "Este tipo de envío está bloqueado.\n\nYa tienes envíos de hoy y pasaron las {$limite_con}.\n\nPuedes crear más envíos mañana.";
                        }
                    }
                }
            }
        }
        ?>
        <script>
        jQuery(document).ready(function($){
            var tipoBloqueado = <?php echo $esta_bloqueado ? 'true' : 'false'; ?>;
            var mensajeBloqueo = <?php echo json_encode($mensaje_bloqueo); ?>;
            var debugInfo = <?php echo json_encode($debug_info); ?>;
            
            console.log('🔍 VALIDACIÓN BLOQUEO:', debugInfo);
            console.log('🎯 ¿Bloqueado?:', tipoBloqueado);
            
            function agregarCampoTipo() {
                var form = $('form');
                
                if(form.length > 0 && $('#tipo_envio_hidden').length === 0) {
                    form.append('<input type="hidden" name="tipo_envio" id="tipo_envio_hidden" value="<?php echo esc_js($tipo); ?>">');
                    console.log('✅ Tipo de envío agregado:', '<?php echo esc_js($tipo); ?>');
                    return true;
                }
                return false;
            }
            
            setTimeout(agregarCampoTipo, 1500);
            
            var intentos = 0;
            var intervalo = setInterval(function(){
                intentos++;
                if(agregarCampoTipo() || intentos >= 20) {
                    clearInterval(intervalo);
                }
            }, 500);
            
            // Validar bloqueo al enviar el formulario
            if (tipoBloqueado) {
                console.log('🔴 APLICANDO BLOQUEO AL FORMULARIO');
                
                $('form').on('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('🔒 TIPO DE ENVÍO BLOQUEADO\n\n' + mensajeBloqueo);
                    return false;
                });
                
                // Deshabilitar botón de enviar
                setTimeout(function() {
                    var $submitButtons = $('button[type="submit"], input[type="submit"]');
                    $submitButtons.prop('disabled', true).css({
                        'opacity': '0.5',
                        'cursor': 'not-allowed',
                        'pointer-events': 'none'
                    }).attr('title', 'Este tipo de envío está bloqueado');
                    
                    console.log('🔒 Botones deshabilitados:', $submitButtons.length);
                }, 2000);
                
                // Mostrar banner de advertencia
                setTimeout(function() {
                    $('body').prepend('<div style="position: fixed; top: 0; left: 0; right: 0; background: #f44336; color: white; padding: 15px; text-align: center; z-index: 9999; font-weight: bold;">🔒 ESTE TIPO DE ENVÍO ESTÁ BLOQUEADO - NO PUEDES CREAR ENVÍOS</div>');
                }, 1000);
            } else {
                console.log('✅ ENVÍO PERMITIDO - No se aplica bloqueo');
            }
        });
        </script>
        <?php
    }
}

// AUTOCOMPLETAR REMITENTE
function autocompletar_campos_formulario() {
    if (isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'add') {
        $user = wp_get_current_user();
        
        // Obtener datos del usuario
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        $nombre_completo = trim($first_name . ' ' . $last_name);
        $telefono = get_user_meta($user->ID, 'phone', true);
        $distrito = get_user_meta($user->ID, 'distrito', true);
        $direccion = get_user_meta($user->ID, 'billing_address_1', true);
        $email = get_user_meta($user->ID, 'billing_email', true);
        if(empty($email)) $email = $user->user_email;
        $empresa = get_user_meta($user->ID, 'billing_company', true);
        $link_maps_remitente = get_user_meta($user->ID, 'link_maps_remitente', true);
        
        ?>
        <script>
            console.log('=== AUTOCOMPLETAR REMITENTE ===');
            
            // Datos del usuario
            var userData = {
                nombre: '<?php echo esc_js($nombre_completo); ?>',
                telefono: '<?php echo esc_js($telefono); ?>',
                distrito: '<?php echo esc_js($distrito); ?>',
                direccion: '<?php echo esc_js($direccion); ?>',
                email: '<?php echo esc_js($email); ?>',
                empresa: '<?php echo esc_js($empresa); ?>',
                link_maps: '<?php echo esc_js($link_maps_remitente); ?>'
            };
            
            console.log('Datos del usuario:', userData);

            // Función para autocompletar
            function autocompletarRemitente() {
                console.log('🔄 Autocompletando campos...');
                
                // Mapeo exacto de campos
                var campos = {
                    nombre: document.querySelector('[name="wpcargo_shipper_name"]'),
                    telefono: document.querySelector('[name="wpcargo_shipper_phone"]'),
                    distrito: document.querySelector('[name="wpcargo_distrito_recojo"]'),
                    direccion: document.querySelector('[name="wpcargo_shipper_address"]'),
                    email: document.querySelector('[name="wpcargo_shipper_email"]'),
                    empresa: document.querySelector('[name="wpcargo_tiendaname"]'),
                    link_maps: document.querySelector('[name="link_maps_remitente"]')
                };
                
                var autocompletados = 0;
                
                // Autocompletar nombre
                if (campos.nombre && userData.nombre) {
                    campos.nombre.value = userData.nombre;
                    triggerChange(campos.nombre);
                    autocompletados++;
                    console.log('✅ Nombre');
                }
                
                // Autocompletar teléfono
                if (campos.telefono && userData.telefono) {
                    campos.telefono.value = userData.telefono;
                    triggerChange(campos.telefono);
                    autocompletados++;
                    console.log('✅ Teléfono');
                }
                
                // Autocompletar distrito
                if (campos.distrito && userData.distrito) {
                    var options = campos.distrito.options;
                    for (var i = 0; i < options.length; i++) {
                        if (options[i].value === userData.distrito || 
                            options[i].text.trim() === userData.distrito) {
                            campos.distrito.selectedIndex = i;
                            triggerChange(campos.distrito);
                            autocompletados++;
                            console.log('✅ Distrito:', userData.distrito);
                            break;
                        }
                    }
                }
                
                // Autocompletar dirección
                if (campos.direccion && userData.direccion) {
                    campos.direccion.value = userData.direccion;
                    triggerChange(campos.direccion);
                    autocompletados++;
                    console.log('✅ Dirección');
                }
                
                // Autocompletar email
                if (campos.email && userData.email) {
                    campos.email.value = userData.email;
                    triggerChange(campos.email);
                    autocompletados++;
                    console.log('✅ Email');
                }
                
                // Autocompletar empresa/tienda
                if (campos.empresa && userData.empresa) {
                    campos.empresa.value = userData.empresa;
                    triggerChange(campos.empresa);
                    autocompletados++;
                    console.log('✅ Empresa/Tienda');
                }
                
                // Autocompletar link maps remitente
                if (campos.link_maps && userData.link_maps) {
                    campos.link_maps.value = userData.link_maps;
                    triggerChange(campos.link_maps);
                    autocompletados++;
                    console.log('✅ Link Google Maps');
                }
                
                if (autocompletados > 0) {
                    console.log('🎉 Autocompletados:', autocompletados, 'campos');
                    return true;
                }
                
                return false;
            }
            
            function triggerChange(element) {
                if (!element) return;
                
                var event = new Event('change', { bubbles: true });
                element.dispatchEvent(event);
                
                if (typeof jQuery !== 'undefined') {
                    jQuery(element).trigger('change');
                }
            }
            
            // Eliminar campo ubicación si existe
            function eliminarCampoUbicacion() {
                var ubicacion = document.querySelector('#location, input[name="location"]');
                if (ubicacion) {
                    ubicacion.closest('.form-group, .col-md-12, .col-md-6, div[class*="col"]').remove();
                    console.log('✂️ Campo ubicación eliminado');
                }
            }
            
            // Cambiar estado a RECEPCIONADO si es MERC AGENCIA
            var urlParams = new URLSearchParams(window.location.search);
            var type = urlParams.get('type');
            
            function cambiarEstadoRecepcionado() {
                // Buscar el select de estado con múltiples estrategias
                var estadoSelect = document.querySelector('select.merc-estado-select, select[name="merc-estado-select"], select#merc-estado-select, select[name="status"], select[name="wpcargo_status"], select[name*="estado"], select[name*="status"]');
                
                if (!estadoSelect) {
                    // Si no lo encontró, buscar todos los selects y filtrar por opciones
                    var allSelects = document.querySelectorAll('select');
                    for (var j = 0; j < allSelects.length; j++) {
                        var options = allSelects[j].options;
                        for (var k = 0; k < options.length; k++) {
                            if (options[k].text.toUpperCase().includes('RECEPCIONADO')) {
                                estadoSelect = allSelects[j];
                                break;
                            }
                        }
                        if (estadoSelect) break;
                    }
                }
                
                if (!estadoSelect) {
                    console.log('⚠️ Campo estado no encontrado. Selectores buscados: merc-estado-select, status, wpcargo_status');
                    return false;
                }
                
                var options = estadoSelect.options;
                for (var i = 0; i < options.length; i++) {
                    if (options[i].text.trim().toUpperCase() === 'RECEPCIONADO' || 
                        options[i].value.trim().toUpperCase() === 'RECEPCIONADO') {
                        estadoSelect.selectedIndex = i;
                        triggerChange(estadoSelect);
                        console.log('✅ Estado cambiado a RECEPCIONADO');
                        return true;
                    }
                }
                
                return false;
            }
            
            // Ejecutar autocompletado
            setTimeout(function() {
                eliminarCampoUbicacion();
                autocompletarRemitente();
                
                // Si es express, cambiar estado
                if (type === 'express') {
                    console.log('🔹 Tipo: MERC AGENCIA - Cambiando estado...');
                    setTimeout(cambiarEstadoRecepcionado, 500);
                    setTimeout(cambiarEstadoRecepcionado, 1000);
                    setTimeout(cambiarEstadoRecepcionado, 1500);
                }
            }, 1000);
            
            // Reintentar si es necesario
            var intentos = 0;
            var intervalo = setInterval(function() {
                intentos++;
                
                if (autocompletarRemitente()) {
                    clearInterval(intervalo);
                    console.log('✅ Autocompletado exitoso');
                } else if (intentos >= 10) {
                    clearInterval(intervalo);
                    console.log('⏹️ Máximo de intentos alcanzado');
                }
            }, 800);
        </script>
        <?php
    }
}
add_action('wp_footer', 'autocompletar_campos_formulario');

// Filtrar visibilidad de opciones de estado según el tipo de envío (no afecta a motorizados)
function merc_filter_statuses_by_tipo_envio() {
    // Se inyecta en footer; el script decide si aplica según exista el select
    $current_user = wp_get_current_user();
    $is_driver = in_array('wpcargo_driver', (array) $current_user->roles);

    ?>
    <script>
    (function(){
        if (typeof document === 'undefined') return;

        var IS_DRIVER = <?php echo $is_driver ? 'true' : 'false'; ?>;

        var STATUS_MAP = {
            agencia: ['RECEPCIONADO','LISTO PARA SALIR','NO CONTESTA','EN RUTA','ENTREGADO','NO RECIBIDO','REPROGRAMADO','ANULADO'],
            emprendedor: ['PENDIENTE','RECOGIDO','NO RECOGIDO','EN BASE MERCOURIER','LISTO PARA SALIR','NO CONTESTA','EN RUTA','ENTREGADO','REPROGRAMADO','NO RECIBIDO','ANULADO'],
            fullfitment: ['RECEPCIONADO','LISTO PARA SALIR','NO CONTESTA','EN RUTA','ENTREGADO','NO RECIBIDO','REPROGRAMADO','ANULADO']
        };

        function normalize(s){
            if(!s) return '';
            s = String(s).toLowerCase();
            s = s.normalize('NFD').replace(/\p{Diacritic}/gu, '');
            return s.replace(/[\s_\-]+/g,'');
        }

        function detectGroup(tipo){
            if(!tipo) return null;

            // Priorizar el valor del parámetro `type` exacto cuando venga (normal/express/full_fitment)
            var raw = String(tipo).trim().toLowerCase();
            if(raw === 'express') return 'agencia';
            if(raw === 'normal') return 'emprendedor';
            if(raw === 'full_fitment' || raw === 'full-fitment' || raw === 'fullfitment') return 'fullfitment';

            // Fallback: buscar palabras dentro del valor (compatibilidad con 'tipo_envio' u otros formatos)
            var t = normalize(tipo);
            if(t.indexOf('express') !== -1 || t.indexOf('agencia') !== -1 || t.indexOf('mercagencia')!==-1) return 'agencia';
            if(t.indexOf('emprendedor') !== -1 || t.indexOf('mercemprendedor')!==-1) return 'emprendedor';
            if(t.indexOf('full') !== -1 || t.indexOf('fitment') !== -1 || t.indexOf('fullfitment')!==-1) return 'fullfitment';
            return null;
        }

        function buildLookup(arr){
            var map = {};
            arr.forEach(function(v){ map[normalize(v)] = true; });
            return map;
        }

        function preserveOriginal(select){
            if(!select) return;
            if(!select.dataset.originalOptions){
                var opts = [];
                for(var i=0;i<select.options.length;i++){
                    opts.push({v: select.options[i].value, t: select.options[i].text});
                }
                select.dataset.originalOptions = JSON.stringify(opts);
            }
        }

        function restoreAll(select){
            if(!select || !select.dataset.originalOptions) return;
            try{
                var opts = JSON.parse(select.dataset.originalOptions);
                select.innerHTML = '';
                opts.forEach(function(o){
                    var el = document.createElement('option'); el.value = o.v; el.text = o.t; select.appendChild(el);
                });
            }catch(e){ console.error(e); }
        }

        function filterForGroup(select, group){
            if(!select) return;
            if(!group){ restoreAll(select); return; }

            var allowed = STATUS_MAP[group] || [];
            var lookup = buildLookup(allowed);
            // Si es motorizado, conservar también las opciones actuales (pueden venir de otra lógica)
            if (IS_DRIVER) {
                for (var i = 0; i < select.options.length; i++) {
                    var o = select.options[i];
                    lookup[normalize(o.text || o.value)] = true;
                }
            }

            // Preservar valor/texto seleccionado actual
            var currentVal = select.value;
            var currentText = (select.options[select.selectedIndex] && select.options[select.selectedIndex].text) ? select.options[select.selectedIndex].text : '';

            if(!select.dataset.originalOptions) preserveOriginal(select);
            var original = JSON.parse(select.dataset.originalOptions || '[]');
            var newOpts = [];

            original.forEach(function(o){
                var key = normalize(o.t || o.v);
                // Incluir si está permitido o si es la opción actualmente seleccionada (para no forzar cambio)
                if( lookup[key] || (currentVal && String(o.v) === String(currentVal)) || (currentText && normalize(o.t) === normalize(currentText)) ) {
                    newOpts.push(o);
                }
            });

            // Si no encontramos coincidencias, restaurar todo (evita dejar vacío)
            if(newOpts.length === 0){ restoreAll(select); return; }

            // Reconstruir opciones conservando la opción seleccionada si existía
            select.innerHTML = '';
            var selectedIndex = -1;
            newOpts.forEach(function(o, idx){
                var el = document.createElement('option');
                el.value = o.v;
                el.text = o.t;
                select.appendChild(el);
                if(currentVal && String(o.v) === String(currentVal)) selectedIndex = idx;
                if(selectedIndex === -1 && currentText && normalize(o.t) === normalize(currentText)) selectedIndex = idx;
            });

            // Restaurar selección si la opción se preservó; NO disparar evento change (evitar modal)
            if(selectedIndex >= 0){
                select.selectedIndex = selectedIndex;
            }
        }

        function tryApply(){
            var selects = Array.prototype.slice.call(document.querySelectorAll('select.merc-estado-select, select[name="merc-estado-select"], select#merc-estado-select, select[name="status"], select[name="wpcargo_status"]'));
            if(!selects || selects.length === 0) return false;

            selects.forEach(function(statusSelect){
                // Determinar tipo para este select: buscar td[data-tipo-envio] en la misma fila
                var tipo = null;
                try{
                    var tr = statusSelect.closest('tr');
                    if(tr){
                        var tipoTd = tr.querySelector('[data-tipo-envio]');
                        if(tipoTd) tipo = tipoTd.getAttribute('data-tipo-envio');
                    }
                }catch(e){ /* ignore */ }

                // Si no se encontró en la fila, buscar campo global oculto o querystring
                if(!tipo){
                    var tipoField = document.querySelector('[name="tipo_envio"]') || document.getElementById('tipo_envio_hidden');
                    if(tipoField) tipo = tipoField.value || tipoField.textContent;
                }
                if(!tipo){
                    var urlParams = new URLSearchParams(window.location.search);
                    tipo = urlParams.get('type');
                }

                var group = detectGroup(tipo);
                console.log('[merc_filter] row tipo=', tipo, ' -> group=', group);
                filterForGroup(statusSelect, group);
            });

            return true;
        }

        // Ejecutar varias veces para asegurar que el DOM y select2 (si existe) cargen
        var attempts = 0; var maxAttempts = 12;
        var interval = setInterval(function(){ attempts++; if(tryApply() || attempts>=maxAttempts) clearInterval(interval); }, 500);

        // Si existe un campo tipo editable, escuchar cambios y reaplicar filtros en todas las filas
        document.addEventListener('change', function(e){
            var t = e.target;
            if(!t) return;
            if(t.name === 'tipo_envio' || t.id === 'tipo_envio_hidden' || t.name === 'type'){
                tryApply();
            }
        }, true);

    })();
    </script>
    <?php
}
add_action('wp_footer', 'merc_filter_statuses_by_tipo_envio', 6);

// RENOMBRANDO CREATE SHIPMENT A "CREAR SERVICIO"
function custom_rename_create_shipment_callback( $text ) {
    return 'Crear servicio';
}
add_filter( 'wpcfe_create_shipment', 'custom_rename_create_shipment_callback' );

// AGREGAR ENVIOS MASIVOS debajo de CREAR SERVICIO
function custom_render_envios_masivos_menu() {
    ?>
    <a href="<?php echo home_url('/import-export/?type=import'); ?>" class="list-group-item waves-effect dashboard-page-menu"> 
        <i class="fa fa-upload mr-3"></i>Envios Masivos
    </a>
    <?php
}
// Usar el hook que se ejecuta justo después de "Crear servicio"
add_action( 'wpcfe_after_create_shipment', 'custom_render_envios_masivos_menu' );

// CSS/JS para ocultar SOLO importar/exportar del wpcie-menu
add_action('wp_footer', 'merc_ocultar_solo_import_export', 1);
function merc_ocultar_solo_import_export() {
    ?>
    <style>
        /* Ocultar SOLO los elementos de Importar/Exportar que NO sean Envíos Masivos */
        .wpcie-menu a[href*="import-export"][href*="type=export"],
        .wpcie-menu a[href*="type=export"],
        .list-group-item[href*="import-export"][href*="type=export"],
        li:has(a[href*="type=export"]) {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            pointer-events: none !important;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ocultar SOLO elementos con "export" o que sea import-export pero NO sea Envios Masivos
        const menuItems = document.querySelectorAll('.list-group-item, .wpcie-menu a');
        menuItems.forEach(item => {
            const text = item.innerText.toLowerCase();
            const href = item.getAttribute('href') || '';
            
            // Ocultar SOLO si contiene "export" O si es import-export pero NO contiene "Envios"
            if (
                text.includes('export') ||
                (href.includes('import-export') && href.includes('type=export')) ||
                (text.includes('import') && !text.includes('envios') && !text.includes('masivo'))
            ) {
                item.style.display = 'none !important';
                item.style.visibility = 'hidden !important';
                item.style.height = '0';
                item.style.overflow = 'hidden';
            }
        });
    });
    </script>
    <?php
}



// RENOMBRANDO RECEIVING (Correcto - usa wpcfe_after_sidebar_menu_items)
function custom_rename_receiving_menu_callback( $menu_items ){
   if( isset($menu_items['receiving-menu']) ){
       $menu_items['receiving-menu']['label'] = 'Escáner';
   }
   return $menu_items; 
}
add_filter( 'wpcfe_after_sidebar_menu_items', 'custom_rename_receiving_menu_callback' );

//RENOMBRANDO SHIPMENTS
function custom_rename_shipments_text( $translated_text, $text, $domain ) {
    // Solo para el dominio de WP Cargo Frontend Manager
    if ( $domain === 'wpcargo-frontend-manager' ) {
        // Reemplazar "Shipments" por "Historial de Envíos"
        if ( $text === 'Shipments' ) {
            $translated_text = 'Historial de Envíos';
        }
        // También manejar el caso con el badge de notificaciones
        if ( strpos($text, 'Shipments <span') !== false ) {
            $translated_text = str_replace('Shipments', 'Historial de Envíos', $text);
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'custom_rename_shipments_text', 20, 3 );

/**
 * Obtener marcas reales desde post_meta (wpcargo_tiendaname)
 */
function diffcode_get_marcas_wpcargo() {
    global $wpdb;

    $results = $wpdb->get_col("
        SELECT DISTINCT meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'wpcargo_tiendaname'
          AND meta_value IS NOT NULL
          AND meta_value != ''
        ORDER BY meta_value ASC
    ");

    return array_unique(array_map('trim', $results));
}

add_action('wp_footer', function () {

    if (!isset($_GET['wpcfe']) || $_GET['wpcfe'] !== 'shipments') {
        return;
    }

    $marcas = diffcode_get_marcas_wpcargo();
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const filtersRow = document.querySelector('#wpcfe-filters .row');
        if (!filtersRow) return;

        if (document.getElementById('filtro_marca')) return;

        const div = document.createElement('div');
        div.className = 'form-group wpcfe-filter p-0 mx-1';

        div.innerHTML = `
            <label class="sr-only">Marca</label>
            <select id="filtro_marca" class="form-control md-form">
                <option value="">Todas las Marcas</option>
            </select>
        `;

        filtersRow.appendChild(div);

        // ✅ MARCAS REALES DESDE PHP
        const marcas = <?php echo json_encode(array_values($marcas)); ?>;

        const select = document.getElementById('filtro_marca');

        marcas.forEach(marca => {
            const opt = document.createElement('option');
            opt.value = marca;
            opt.textContent = marca;
            select.appendChild(opt);
        });

        // Restaurar selección desde URL
        const urlParams = new URLSearchParams(window.location.search);
        const marcaActual = urlParams.get('marca');
        if (marcaActual) {
            select.value = marcaActual;
        }

        // Al cambiar → recargar con querystring
        select.addEventListener('change', function () {
            const url = new URL(window.location.href);

            if (this.value) {
                url.searchParams.set('marca', this.value);
            } else {
                url.searchParams.delete('marca');
            }

            window.location.href = url.toString();
        });

    });
    </script>
    <?php
});

add_filter('wpcfe_shipment_query_args', function ($args) {

    if (!empty($_GET['marca'])) {
        $args['meta_query'][] = [
            'key'     => 'wpcargo_tiendaname',
            'value'   => sanitize_text_field($_GET['marca']),
            'compare' => 'LIKE',
        ];
    }

    return $args;
});

// RENOMBRANDO ADDRESS BOOK - Prueba diferentes variaciones de la clave
function custom_rename_address_book_menu_callback( $menu_items ){
   // Pueden ser varias claves posibles
   if( isset($menu_items['address-book-menu']) ){
       $menu_items['address-book-menu']['label'] = 'Direcciones Guardadas';
   }
   return $menu_items; 
}
add_filter( 'wpcfe_after_sidebar_menu_items', 'custom_rename_address_book_menu_callback' );

//ELIMINAR SECCIONES DE REGISTRO
function remove_wpcfe_billing_address_fields_callback( $fields ){
    unset($fields['billing_postcode']);
	unset($fields['billing_country']);
	unset($fields['billing_city']);
	unset($fields['billing_state']);
    return $fields;
}

//ID DE CLIENTE
add_filter( 'wpcfe_billing_address_fields', 'remove_wpcfe_billing_address_fields_callback' );

function wpcargo_get_user_id_label( $user ) {

    if ( empty( $user->roles ) ) {
        return 'Tu ID de usuario es:';
    }

    // Administrador
    if ( in_array( 'administrator', $user->roles ) ) {
        return 'Tu ID de administrador asignado es:';
    }

    // Motorizado (ajusta el slug si tu rol tiene otro nombre)
    if ( in_array( 'wpcargo_driver', $user->roles ) ) {
        return 'Tu ID de motorizado asignado es:';
    }

    // Cliente (ajusta el slug si es necesario)
    if ( in_array( 'wpcargo_client', $user->roles ) || in_array( 'cliente', $user->roles ) ) {
        return 'Tu ID de cliente asignado es:';
    }

    // Cualquier otro rol
    return 'Tu ID de usuario es:';
}

/**
 * Mostrar ID debajo del perfil (WPCargo)
 */
add_action( 'wpcfe_after_profile_header', 'wpcfe_after_profile_header_callback' );
function wpcfe_after_profile_header_callback() {

    if ( ! is_user_logged_in() ) {
        echo '<p>Debes iniciar sesión para ver tu ID asignado.</p>';
        return;
    }

    $current_user = wp_get_current_user();
    $label        = wpcargo_get_user_id_label( $current_user );
    ?>

    <p>
        <?php echo esc_html( $label ); ?>
        <span style="font-weight: bold; color: #005077; background-color: #e5f3ff; padding: 5px 10px; border-radius: 5px; display: inline-block;">
            <?php echo esc_html( $current_user->ID ); ?>
        </span>
    </p>

    <?php
}

add_filter( 'wpcfe_billing_address_fields', 'wpcfe_billing_address_fields_callback' );
function wpcfe_billing_address_fields_callback( $billing_fields ) {
    $billing_fields['distrito'] = array(
        'id'            => 'distrito',
        'label'         => 'Distrito',
        'field'         => 'select',
        'field_type'    => 'select',
        'required'      => true,
        'options'       => array(
            'Ate - Salamanca - Vitarte',
            'Barranco',
            'Bellavista',
            'Breña',
            'Callao',
            'Carabayllo',
            'Carmen de la Legua',
            'Centro de Lima',
            'Chorrillos',
            'Comas',
            'El Agustino',
            'Huachipa (Zoológico de Huachipa)',
            'Huaycan (Gloria Grande - Pariachi)',
            'Independencia',
            'Jesús María',
            'La Molina',
            'La Perla',
            'La Punta - Callao',
            'La Victoria',
            'Lima Cercado',
            'Lince',
            'Los Olivos',
            'Magdalena',
            'Molina Alta (Musa - Portada del Sol - Planicie)',
            'Miraflores',
            'Pueblo Libre',
            'Puente Piedra',
            'Rímac',
            'San Borja',
            'San Isidro',
            'San Juan de Lurigancho',
            'San Juan de Miraflores',
            'San Luis',
            'San Martin de Porres',
            'San Miguel',
            'Santa Anita',
            'Santa Clara',
            'Santiago de Surco',
            'Sarita Colonia (Comisaría Sarita Colonia)',
            'Surquillo',
            'Ventanilla',
            'Villa El Salvador',
            'Villa María del Triunfo',
        ),
        'field_data'    => array(),
        'field_key'     => 'distrito'
    );
    
    // Agregar campo para link de Google Maps
    $billing_fields['link_maps_remitente'] = array(
        'id'            => 'link_maps_remitente',
        'label'         => 'Link de Google Maps',
        'field'         => 'text',
        'field_type'    => 'text',
        'required'      => true,
        'placeholder'   => 'https://maps.google.com/...',
        'field_data'    => array(),
        'field_key'     => 'link_maps_remitente'
    );
    
    return $billing_fields;
}

//BLOQUEAR CALENDARIO
function custom_block_calendar_script() {
    if (isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'add') { 
        // Verificar si el usuario actual es administrador
        $is_admin = current_user_can('administrator');
        
        // Verificar si el usuario es cliente
        $current_user = wp_get_current_user();
        $is_client = in_array('wpcargo_client', $current_user->roles);
        
        // Verificar si el cliente tiene deudas pendientes
        $tiene_deudas = false;
        if ($is_client) {
            // Aquí puedes verificar si tiene deudas
            // Por ejemplo: $tiene_deudas = get_user_meta($current_user->ID, 'tiene_deudas', true);
            // O verificar el saldo pendiente: $saldo = get_user_meta($current_user->ID, 'saldo_pendiente', true);
            // $tiene_deudas = ($saldo > 0);
        }
        
        // Verificar si el cliente tiene desbloqueo manual activo
        $tiene_desbloqueo_manual = false;
        if ($is_client) {
            $hoy = current_time('Y-m-d');
            $desbloqueo_manual_fecha = get_user_meta($current_user->ID, 'merc_desbloqueado_manualmente_fecha', true);
            $envios_permitidos = intval(get_user_meta($current_user->ID, 'merc_desbloqueo_manual_envios_permitidos', true));
            $tiene_desbloqueo_manual = ($desbloqueo_manual_fecha === $hoy && $envios_permitidos > 0);
        }
        ?>
        <script>
          document.addEventListener("DOMContentLoaded", function () {
            const dateInput = document.querySelector("#wpcargo_pickup_date_picker");
            const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
            const isClient = <?php echo $is_client ? 'true' : 'false'; ?>;
            const tieneDeudas = <?php echo $tiene_deudas ? 'true' : 'false'; ?>;
            const tieneDesbloqueoManual = <?php echo $tiene_desbloqueo_manual ? 'true' : 'false'; ?>;
            
            if (dateInput) {
              const currentDate = new Date();
              let targetDate = new Date(currentDate);
              
              // ⭐ NUEVA LÓGICA SIMPLIFICADA: Solo bloquear días anteriores y domingos
              function adjustTargetDate() {
                console.log('📅 Configurando fecha mínima del calendario');
                
                // LÓGICA PARA CLIENTES
                if (isClient) {
                  // Si tiene deudas, bloquear completamente
                  if (tieneDeudas) {
                    console.log('⚠️ Cliente con deudas pendientes - Calendario bloqueado');
                    return;
                  }
                  
                  // Si tiene desbloqueo manual activo, permitir fecha de HOY
                  if (tieneDesbloqueoManual) {
                    console.log('🔓 DESBLOQUEO MANUAL ACTIVO - Puede usar fecha de hoy');
                    // Solo verificar que hoy no sea domingo
                    if (targetDate.getDay() === 0) {
                      targetDate.setDate(targetDate.getDate() + 1);
                      console.log('⚠️ Hoy es domingo - Avanzando al lunes');
                    }
                    return;
                  }
                  
                  // ⭐ SIN BLOQUEO POR HORA: Permitir fecha de hoy siempre
                  // Solo verificar que hoy no sea domingo
                  if (targetDate.getDay() === 0) {
                    targetDate.setDate(targetDate.getDate() + 1);
                    console.log('⚠️ Hoy es domingo - Avanzando al lunes');
                  } else {
                    console.log('✅ Fecha mínima: HOY (' + targetDate.toLocaleDateString('es-PE') + ')');
                  }
                } 
                // LÓGICA PARA ADMINISTRADORES
                else if (isAdmin) {
                  console.log('👑 Administrador - Sin restricciones de fecha');
                }
                // LÓGICA PARA OTROS ROLES
                else {
                  // Solo verificar que hoy no sea domingo
                  if (targetDate.getDay() === 0) {
                    targetDate.setDate(targetDate.getDate() + 1);
                    console.log('⚠️ Hoy es domingo - Avanzando al lunes');
                  }
                }
              }
              
              // Solo ajustar la fecha si NO es administrador
              if (!isAdmin) {
                adjustTargetDate();
              }
              
              // Generar array de fechas bloqueadas (todos los domingos para los próximos 10 años)
              function generateDisabledDates() {
                const disabledDays = [];
                const currentYear = currentDate.getFullYear();
                for (let year = currentYear; year <= currentYear + 10; year++) {
                  for (let month = 0; month < 12; month++) {
                    for (let day = 1; day <= 31; day++) {
                      const tempDate = new Date(year, month, day);
                      if (tempDate.getFullYear() === year && tempDate.getDay() === 0) {
                        disabledDays.push([year, month, day]);
                      }
                    }
                  }
                }
                return disabledDays;
              }
              
              // ⭐ Configuración del calendario en español con formato DD/MM/YYYY
              jQuery.extend(jQuery.fn.pickadate.defaults, {
                monthsFull: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                weekdaysFull: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                weekdaysShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                today: 'Hoy',
                clear: 'Limpiar',
                close: 'Cerrar',
                firstDay: 1,
                format: 'dd/mm/yyyy',      // ⭐ Formato de visualización
                formatSubmit: 'dd/mm/yyyy' // ⭐ Formato de envío al servidor
              });
              
              // Configurar el calendario según el rol del usuario
              const calendarConfig = {
                format: "dd/mm/yyyy",      // ⭐ Asegurar formato DD/MM/YYYY
                formatSubmit: "dd/mm/yyyy" // ⭐ Asegurar formato de envío DD/MM/YYYY
              };
              
              // Solo aplicar restricciones si NO es administrador
              if (!isAdmin) {
                calendarConfig.min = targetDate;           // ⭐ Fecha mínima: hoy (o lunes si hoy es domingo)
                calendarConfig.disable = generateDisabledDates(); // ⭐ Bloquear todos los domingos
              }
              
              jQuery(dateInput).pickadate(calendarConfig);
              
              // ⭐ Función para formatear fecha como DD/MM/YYYY
              function formatDateDDMMYYYY(date) {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return day + '/' + month + '/' + year;
              }
              
              // ⭐ Establecer fecha inicial con formato DD/MM/YYYY
              if (!isAdmin) {
                jQuery(dateInput).val(formatDateDDMMYYYY(targetDate));
              } else {
                jQuery(dateInput).val(formatDateDDMMYYYY(currentDate));
              }
            }
          });
        </script>
        <?php
    }
}
add_action('wp_footer', 'custom_block_calendar_script');

add_filter('wpcfe_locate_template_add_shipment', function($template_path) {
    if (isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'add' && empty($_GET['type'])) {
        $new_template = get_stylesheet_directory() . '/merc-service-selector.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template_path;
});

// Establecer valores predeterminados en campos de paquetes al cargar el formulario
add_action('wp_footer', function() {
    if ( isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'add' && isset($_GET['type']) ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Esperar a que el DOM esté completamente cargado
            setTimeout(function() {
                // Establecer valores predeterminados en todos los campos de dimensiones y peso
                $('#wpcfe-packages-repeater tbody tr').each(function() {
                    var $row = $(this);
                    
                    // Solo establecer si están vacíos
                    var lengthField = $row.find('input[name*="length"]');
                    var widthField = $row.find('input[name*="width"]');
                    var heightField = $row.find('input[name*="height"]');
                    var weightField = $row.find('input[name*="weight"]');
                    
                    if (lengthField.length && !lengthField.val()) {
                        lengthField.val('25');
                    }
                    if (widthField.length && !widthField.val()) {
                        widthField.val('25');
                    }
                    if (heightField.length && !heightField.val()) {
                        heightField.val('25');
                    }
                    if (weightField.length && !weightField.val()) {
                        weightField.val('3');
                    }
                });
            }, 500); // Esperar 500ms para asegurar que el formulario esté cargado
        });
        </script>
        <?php
    }
}, 1000);

// Ocultar SOLO la columna de descripción en paquetes
add_action('wp_head', function() {
    if ( isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'add' ) {
        ?>
        <style>
        /* Ocultar campos de descripción */
        textarea.wpc-pm-description,
        textarea[name*="[wpc-pm-description]"] {
            display: none !important;
        }
        
        /* Ocultar la celda TD que contiene el textarea de descripción */
        #wpcfe-packages-repeater td:has(textarea.wpc-pm-description),
        #wpcfe-packages-repeater td:has(textarea[name*="[wpc-pm-description]"]) {
            display: none !important;
        }
        
        /* Ocultar el TH (encabezado) correspondiente a descripción */
        /* Asumiendo que descripción es la columna 7, ajusta el número si es diferente */
        #wpcfe-packages-repeater thead tr th:nth-child(3) {
            display: none !important;
        }
        
        <?php if (isset($_GET['type']) && $_GET['type'] === 'full_fitment'): ?>
        /* Ocultar la sección de paquetes SOLO para FULL FITMENT */
        #package_id {
            display: none !important;
        }
        <?php endif; ?>
        </style>
        <?php
    }
});

add_filter( 'wp_head', 'remove_history_track_result' );
function remove_history_track_result(){
    if(isset( $_REQUEST['wpcargo_tracking_number'] )){
        remove_action('wpcargo_after_track_details', 'wpcargo_track_shipment_history_details', 10, 1);
    }
}
// ========== SOLUCIÓN GLOBAL: WPCARGO SIN CACHÉ ==========
add_action('init', function() {
    // Verificar si LiteSpeed Cache está activo
    if (!defined('LSCWP_V')) {
        return;
    }

    // Si el usuario está logueado, NO cachear NADA
    if (is_user_logged_in()) {
        do_action('litespeed_control_set_nocache', 'logged in user - wpcargo needs real-time data');
        
        // Forzar headers de no-cache
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
});

// Desactivar caché para TODOS los usuarios con roles de WPCargo
add_filter('litespeed_vary_curr_cookies', function($cookies) {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        
        // Roles relacionados con WPCargo
        $wpcargo_roles = array(
            'administrator',
            'wpcargo_manager', 
            'wpcargo_driver',
            'wpcargo_client',
            'customer' // Por si usas WooCommerce integrado
        );
        
        foreach ($wpcargo_roles as $role) {
            if (in_array($role, $current_user->roles)) {
                // Forzar variación única por usuario
                $cookies[] = 'wpcargo_user_' . $current_user->ID;
                break;
            }
        }
    }
    
    return $cookies;
});

// Purgar caché automáticamente en CUALQUIER cambio de WPCargo
add_action('save_post', function($post_id, $post) {
    // Solo para posts de WPCargo
    if (strpos($post->post_type, 'wpcargo') === false) {
        return;
    }
    
    // Purgar TODO el caché cuando hay cambios en WPCargo
    if (function_exists('LiteSpeed_Cache_API::purge_all')) {
        LiteSpeed_Cache_API::purge_all('wpcargo data changed');
    }
}, 10, 2);

// Purgar caché cuando se actualiza cualquier meta de WPCargo
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    // Si el meta_key contiene 'wpcargo', purgar
    if (strpos($meta_key, 'wpcargo') !== false) {
        if (function_exists('LiteSpeed_Cache_API::purge_all')) {
            LiteSpeed_Cache_API::purge_all('wpcargo meta updated');
        }
    }
}, 10, 4);

// Purgar en cambios de estado de pedidos
add_action('transition_post_status', function($new_status, $old_status, $post) {
    if (strpos($post->post_type, 'wpcargo') !== false && $new_status !== $old_status) {
        if (function_exists('LiteSpeed_Cache_API::purge_all')) {
            LiteSpeed_Cache_API::purge_all('wpcargo status changed');
        }
    }
}, 10, 3);

// Desactivar COMPLETAMENTE el purge durante login para evitar el problema de doble click
// El purge se hará automáticamente en otras acciones
add_action('wp_logout', function() {
    if (function_exists('LiteSpeed_Cache_API::purge_all')) {
        LiteSpeed_Cache_API::purge_all('user logged out');
    }
});

// Calcular Envío con Desglose Automático
function custom_shipment_multipackage_template() {
    // Obtener el ID del envío en modo edición
    $shipment_id = 0;
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $shipment_id = intval($_GET['id']);
    } elseif (isset($_POST['shipment_id']) && !empty($_POST['shipment_id'])) {
        $shipment_id = intval($_POST['shipment_id']);
    } else {
        global $post;
        if (isset($post->ID)) {
            $shipment_id = $post->ID;
        }
    }
    
    // Inicializar variables
    $tipo_envio_actual = '';
    $costo_envio_guardado = 0;
    $costo_producto_guardado = 0;
    $total_cobrar_guardado = 0;
    $cargo_remitente_guardado = 0;
    
    // Si tenemos un ID válido, cargar datos desde la base de datos
    if ($shipment_id > 0) {
        $tipo_envio_actual = get_post_meta($shipment_id, 'tipo_envio', true);
        $costo_envio_guardado = get_post_meta($shipment_id, 'wpcargo_costo_envio', true) ?: 0;
        $costo_producto_guardado = get_post_meta($shipment_id, 'wpcargo_costo_producto', true) ?: 0;
        $total_cobrar_guardado = get_post_meta($shipment_id, 'wpcargo_total_cobrar', true) ?: 0;
        $cargo_remitente_guardado = get_post_meta($shipment_id, 'wpcargo_cargo_remitente', true) ?: 0;
        error_log("📝 [EDIT_FORM_LOAD] Envío #{$shipment_id} | Tipo: {$tipo_envio_actual} | Costo envío: {$costo_envio_guardado}");
    }
    
    // Si no hay tipo guardado, intentar desde URL (modo creación)
    if (empty($tipo_envio_actual) && isset($_GET['type'])) {
        $tipo_envio_actual = sanitize_text_field($_GET['type']);
    }
    ?>
    <!-- Campos ocultos para guardar datos financieros -->
    <input type="hidden" id="tipo-envio-actual" name="tipo_envio" value="<?php echo esc_attr($tipo_envio_actual); ?>">
    <input type="hidden" id="hidden-product-cost" name="wpcargo_costo_producto" value="<?php echo esc_attr($costo_producto_guardado); ?>">
    <input type="hidden" id="hidden-shipping-cost" name="wpcargo_costo_envio" value="<?php echo esc_attr($costo_envio_guardado); ?>">
    <input type="hidden" id="hidden-customer-payment" name="wpcargo_total_cobrar" value="<?php echo esc_attr($total_cobrar_guardado); ?>">
    <input type="hidden" id="hidden-sender-charge" name="wpcargo_cargo_remitente" value="<?php echo esc_attr($cargo_remitente_guardado); ?>">

    <!-- Sección de costo de envío -->
    <div class="col-md-12 mb-5" id="shipping-cost-section" data-tipo-envio="<?php echo esc_attr($tipo_envio_actual); ?>" data-costo-envio="<?php echo esc_attr($costo_envio_guardado); ?>">
        <div class="card">
            <div class="card-body">
                <h5><b>💰 Desglose del envío:</b></h5>
                
                <!-- Desglose detallado -->
                <div id="shipping-breakdown" style="font-size: 16px; margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                        <span>Costo del producto:</span>
                        <span style="font-weight: bold;">S/. <span id="product-cost">0.00</span></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                        <span>Costo del envío:</span>
                        <span style="font-weight: bold;">S/. <span id="shipping-cost">0.00</span></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 12px 0; margin-top: 5px; background-color: #f8f9fa; padding: 10px; border-radius: 5px;">
                        <span style="font-weight: bold; font-size: 18px;">Total a cobrar:</span>
                        <span style="font-weight: bold; font-size: 18px; color: #1976D2;">S/. <span id="total-cost">0.00</span></span>
                    </div>
                </div>
                
                <!-- Mensaje de validación -->
                <div id="validation-message" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none;"></div>
                
                <!-- Debug info (oculto en producción) -->
                <div id="debug-info" style="font-size: 12px; margin-top: 10px; color: #666; display: none;"></div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Tabla de precios por distrito para MERC AGENCIA (express)
        const districtPricesExpress = {
            "-- Seleccione uno --": 0.00,
            "El Agustino": 8.00,
            "San Juan de Lurigancho": 8.00,
            "Santa Anita": 8.00,
            "Ate - Salamanca - Vitarte": 10.00,
            "La Molina": 8.00,
            "Santa Clara": 10.00,
            "Huaycan (Gloria Grande - Pariachi)": 12.00,
            "Molina Alta (Musa - Portada del Sol - Planicie)": 10.00,
            "Huachipa (Zoológico de Huachipa)": 10.00,
            "Callao": 8.00,
            "Bellavista": 8.00,
            "La Punta - Callao": 10.00,
            "La Perla": 8.00,
            "Pueblo Libre": 8.00,
            "Lima Cercado": 8.00,
            "Breña": 8.00,
            "San Miguel": 8.00,
            "Magdalena": 8.00,
            "Sarita Colonia (Comisaría Sarita Colonia)": 8.00,
            "Carmen de la Legua": 8.00,
            "Rímac": 8.00,
            "Independencia": 8.00,
            "Comas": 8.00,
            "Carabayllo": 10.00,
            "Puente Piedra": 10.00,
            "Ventanilla": 10.00,
            "Los Olivos": 8.00,
            "San Martin de Porres": 8.00,
            "Santiago de Surco": 8.00,
            "San Juan de Miraflores": 8.00,
            "Villa María del Triunfo": 10.00,
            "Villa El Salvador": 10.00,
            "Chorrillos": 8.00,
            "Barranco": 8.00,
            "Jesús María": 8.00,
            "Lince": 8.00,
            "La Victoria": 8.00,
            "Miraflores": 8.00,
            "San Isidro": 8.00,
            "Surquillo": 8.00,
            "San Borja": 8.00,
            "San Luis": 8.00,
            "Centro de Lima": 8.00
        };

        // Tabla de precios por distrito para MERC EMPRENDEDOR (normal)
        const districtPricesNormal = {
            "-- Seleccione uno --": 0.00,
            "El Agustino": 10.00,
            "San Juan de Lurigancho": 10.00,
            "Santa Anita": 10.00,
            "Ate - Salamanca - Vitarte": 10.00,
            "La Molina": 10.00,
            "Santa Clara": 12.00,
            "Huaycan (Gloria Grande - Pariachi)": 14.00,
            "Molina Alta (Musa - Portada del Sol - Planicie)": 12.00,
            "Huachipa (Zoológico de Huachipa)": 12.00,
            "Callao": 10.00,
            "Bellavista": 10.00,
            "La Punta - Callao": 12.00,
            "La Perla": 10.00,
            "Pueblo Libre": 10.00,
            "Lima Cercado": 10.00,
            "Breña": 10.00,
            "San Miguel": 10.00,
            "Magdalena": 10.00,
            "Sarita Colonia (Comisaría Sarita Colonia)": 10.00,
            "Carmen de la Legua": 10.00,
            "Rímac": 10.00,
            "Independencia": 10.00,
            "Comas": 10.00,
            "Carabayllo": 13.00,
            "Puente Piedra": 13.00,
            "Ventanilla": 13.00,
            "Los Olivos": 10.00,
            "San Martin de Porres": 10.00,
            "Santiago de Surco": 10.00,
            "San Juan de Miraflores": 10.00,
            "Villa María del Triunfo": 12.00,
            "Villa El Salvador": 12.00,
            "Chorrillos": 10.00,
            "Barranco": 10.00,
            "Jesús María": 10.00,
            "Lince": 10.00,
            "La Victoria": 10.00,
            "San Isidro": 10.00,
            "Surquillo": 10.00,
            "San Borja": 10.00,
            "San Luis": 10.00,
            "Centro de Lima": 10.00
        };

        // Variable global para cachear el tipo de envío
        let cachedServiceType = null;
        
        // Función para obtener el tipo de servicio desde la URL o DB
        function getServiceType() {
            // Si ya obtuvimos el tipo, retornar del caché
            if (cachedServiceType !== null) {
                console.log('🔍 getServiceType - Usando tipo cacheado:', cachedServiceType);
                return cachedServiceType;
            }
            
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            const shipmentId = urlParams.get('id');
            
            console.log('🔍 getServiceType - URL params:', { type, id: shipmentId });
            
            // Si hay type en la URL (modo creación), usarlo
            if (type === 'express') {
                console.log('✅ Tipo detectado: EXPRESS (MERC AGENCIA)');
                cachedServiceType = 'express';
                return 'express';
            }
            if (type === 'full_fitment' || (type && type.toLowerCase().includes('full'))) {
                console.log('✅ Tipo detectado: FULL FITMENT');
                cachedServiceType = 'full_fitment';
                return 'full_fitment';
            }
            
            // Si hay shipment_id en la URL (modo edición), obtener tipo desde el servidor
            if (shipmentId) {
                console.log('🔍 getServiceType - Modo EDICIÓN detectado, consultando tipo desde data attributes...');
                
                // Intentar obtener desde data attribute
                const shippingSection = $('#shipping-cost-section');
                console.log('🔍 Elemento #shipping-cost-section encontrado:', shippingSection.length > 0);
                
                if (shippingSection.length > 0) {
                    const tipoFromAttr = shippingSection.data('tipo-envio');
                    console.log('🔍 Data attribute tipo-envio:', tipoFromAttr, '(tipo:', typeof tipoFromAttr, ')');
                    
                    if (tipoFromAttr && tipoFromAttr !== '') {
                        const tipoLower = String(tipoFromAttr).toLowerCase().trim();
                        console.log('✅ Tipo obtenido desde data attribute:', tipoFromAttr);
                        
                        if (tipoLower === 'express' || tipoLower.includes('agencia')) {
                            cachedServiceType = 'express';
                            return 'express';
                        }
                        if (tipoLower === 'full_fitment' || tipoLower.includes('full')) {
                            cachedServiceType = 'full_fitment';
                            return 'full_fitment';
                        }
                        cachedServiceType = 'normal';
                        return 'normal';
                    }
                }
            }
            
            // Verificar campo hidden como fallback
            const tipoEnvioField = $('#tipo-envio-actual').val() || $('input[name="tipo_envio"]').val();
            console.log('🔍 getServiceType - Campo hidden #tipo-envio-actual:', tipoEnvioField, '(existe:', $('#tipo-envio-actual').length > 0, ')');
            
            if (tipoEnvioField && tipoEnvioField.trim() !== '') {
                const tipoLower = tipoEnvioField.toLowerCase().trim();
                
                if (tipoLower === 'express' || tipoLower.includes('agencia')) {
                    console.log('✅ Tipo detectado desde campo hidden: EXPRESS');
                    cachedServiceType = 'express';
                    return 'express';
                }
                if (tipoLower === 'full_fitment' || tipoLower.includes('full')) {
                    console.log('✅ Tipo detectado desde campo hidden: FULL FITMENT');
                    cachedServiceType = 'full_fitment';
                    return 'full_fitment';
                }
            }
            
            console.log('⚠️ Tipo no detectado - usando NORMAL por defecto');
            cachedServiceType = 'normal';
            return 'normal';
        }

        // Función para obtener la tabla de precios correcta
        function getDistrictPrices() {
            const serviceType = getServiceType();
            const prices = serviceType === 'express' ? districtPricesExpress : districtPricesNormal;
            console.log('💰 Tabla de precios seleccionada:', serviceType === 'express' ? 'EXPRESS' : 'NORMAL');
            return prices;
        }

        // Función para encontrar coincidencias parciales
        function findBestMatch(destination) {
            const serviceType = getServiceType();
            // Si es full_fitment, todas las tarifas por distrito son S/.10
            if (serviceType === 'full_fitment') {
                return 10.00;
            }

            const districtPrices = getDistrictPrices();
            destination = destination.trim();
            
            // Comprobar coincidencia exacta primero
            if (districtPrices[destination] !== undefined) {
                return districtPrices[destination];
            }
            
            // Comprobar si es un distrito principal
            for (const district in districtPrices) {
                const mainName = district.split('(')[0].split(',')[0].trim();
                
                if (mainName.toLowerCase() === destination.toLowerCase()) {
                    return districtPrices[district];
                }
            }
            
            // Buscar coincidencias parciales
            for (const district in districtPrices) {
                if (district.toLowerCase().includes(destination.toLowerCase()) || 
                    destination.toLowerCase().includes(district.toLowerCase())) {
                    return districtPrices[district];
                }
            }
            
            return 0.00;
        }

        // Función para mostrar mensajes de validación
        function showValidationMessage(message, type = 'warning') {
            const messageDiv = $('#validation-message');
            const colors = {
                'warning': '#fff3cd',
                'error': '#f8d7da',
                'success': '#d4edda',
                'info': '#d1ecf1'
            };
            const textColors = {
                'warning': '#856404',
                'error': '#721c24',
                'success': '#155724',
                'info': '#0c5460'
            };
            
            messageDiv.css({
                'background-color': colors[type],
                'color': textColors[type],
                'border': '1px solid ' + textColors[type]
            }).html(message).show();
        }

        // Función para ocultar mensajes de validación
        function hideValidationMessage() {
            $('#validation-message').hide();
        }
        
        // Función para actualizar el desglose del costo
        function updateShippingBreakdown() {
            // CORRECCIÓN: Usar wpcargo_distrito_destino
            const destinationField = $('#wpcargo_distrito_destino');
            let destination = '';
            
            if (destinationField.length > 0) {
                // Si es un select
                if (destinationField.is('select')) {
                    destination = destinationField.find('option:selected').text() || destinationField.val() || '';
                } else {
                    destination = destinationField.val() || '';
                }
            }
            
            const montoInput = $('#wpcargo_monto');
            let totalAmount = 0;
            
            // Buscar el campo de monto
            if (montoInput.length > 0) {
                totalAmount = parseFloat(montoInput.val()) || 0;
            } else {
                // Intentar con otros posibles nombres de campo
                const altMontoInput = $('input[name*="monto"], input[id*="monto"]');
                if (altMontoInput.length > 0) {
                    totalAmount = parseFloat(altMontoInput.val()) || 0;
                }
            }
            
            console.log('Distrito de destino:', destination);
            console.log('Monto total ingresado:', totalAmount);
            console.log('Tipo de servicio:', getServiceType());
            
            // Verificar si estamos en modo edición y ya hay un costo de envío guardado
            const hiddenShippingCostField = $('#hidden-shipping-cost');
            const existingShippingCost = parseFloat(hiddenShippingCostField.val()) || 0;
            const isEditMode = $('input[name="post_ID"]').length > 0 || $('input[name="shipment_id"]').length > 0;
            
            // Buscar el precio de envío para el distrito seleccionado
            const shippingCost = findBestMatch(destination);
            
            console.log('🔍 Modo edición:', isEditMode, '| Costo guardado:', existingShippingCost, '| Costo calculado:', shippingCost);
            
            // Si estamos en modo edición y ya hay un costo guardado, usar ese en lugar de recalcular
            // SOLO recalcular si el usuario cambia el distrito
            let finalShippingCost = shippingCost;
            if (isEditMode && existingShippingCost > 0 && !window.districtChanged) {
                finalShippingCost = existingShippingCost;
                console.log('✅ Usando costo guardado:', finalShippingCost);
            } else {
                console.log('🔄 Calculando nuevo costo:', finalShippingCost);
            }
            
            // Calcular el costo del producto
            let productCost = totalAmount - finalShippingCost;
            
            // Validaciones
            if (totalAmount === 0) {
                hideValidationMessage();
                $('#product-cost').text('0.00');
                $('#shipping-cost').text(finalShippingCost.toFixed(2));
                $('#total-cost').text(finalShippingCost.toFixed(2));
                return;
            }
            
            if (totalAmount < finalShippingCost) {
                showValidationMessage(
                    '⚠️ Advertencia: El monto total (S/. ' + totalAmount.toFixed(2) + 
                    ') es menor que el costo de envío (S/. ' + finalShippingCost.toFixed(2) + 
                    '). El costo del producto será negativo.',
                    'warning'
                );
            } else if (totalAmount === finalShippingCost) {
                showValidationMessage(
                    'ℹ️ El monto total coincide exactamente con el costo de envío. ' +
                    'El costo del producto es S/. 0.00',
                    'info'
                );
                productCost = 0;
            } else {
                hideValidationMessage();
            }
            
            // Actualizar los valores mostrados
            $('#product-cost').text(productCost.toFixed(2));
            $('#shipping-cost').text(finalShippingCost.toFixed(2));
            $('#total-cost').text(totalAmount.toFixed(2));

            // ✅ ACTUALIZAR CAMPOS OCULTOS PARA SISTEMA FINANCIERO
            $('#hidden-product-cost').val(productCost.toFixed(2));
            $('#hidden-shipping-cost').val(finalShippingCost.toFixed(2));
            $('#hidden-customer-payment').val(totalAmount.toFixed(2));

            // Mostrar información de depuración (solo en desarrollo)
            $('#debug-info').html(
                'Servicio: ' + (getServiceType() === 'express' ? 'MERC AGENCIA' : 'MERC EMPRENDEDOR') +
                ' | Distrito: "' + destination +
                '" | Envío: S/. ' + finalShippingCost.toFixed(2) +
                ' | Producto: S/. ' + productCost.toFixed(2) +
                ' | Total: S/. ' + totalAmount.toFixed(2)
            );
        }

        // CORRECCIÓN: Escuchar cambios en wpcargo_distrito_destino
        $(document).on('change', '#wpcargo_distrito_destino', function() {
            console.log('Cambio detectado en distrito destino');
            window.districtChanged = true; // Marcar que el usuario cambió el distrito
            updateShippingBreakdown();
        });
        
        // Escuchar cambios en el campo de monto
        $(document).on('input change', '#wpcargo_monto, input[name*="monto"], input[id*="monto"]', function() {
            console.log('Cambio detectado en monto');
            updateShippingBreakdown();
        });
        
        // Escuchar cambios en el modo de pago (para actualizar cuando el campo monto aparece/desaparece)
        $(document).on('change', 'select[name="payment_wpcargo_mode_field"]', function() {
            console.log('Cambio detectado en modo de pago:', $(this).val());
            // Esperar un momento a que el campo de monto se actualice o muestre/oculte
            setTimeout(function() {
                updateShippingBreakdown();
            }, 300);
        });
        
        // También escuchar con selectWoo/Select2 si está en uso
        $(document).on('select2:select', '#wpcargo_distrito_destino', function() {
            console.log('Select2 detectado en distrito destino');
            updateShippingBreakdown();
        });
        
        // Ejecutar inmediatamente para el valor inicial
        setTimeout(function() {
            console.log('Inicializando cálculo de envío...');
            updateShippingBreakdown();
        }, 500);
        
        // Verificar periódicamente si los campos aparecen (carga dinámica)
        let checkInterval = setInterval(function() {
            const distritoField = $('#wpcargo_distrito_destino');
            const montoField = $('#wpcargo_monto, input[name*="monto"], input[id*="monto"]');
            
            if (distritoField.length > 0 && montoField.length > 0) {
                console.log('Campos encontrados, ejecutando cálculo inicial');
                updateShippingBreakdown();
                clearInterval(checkInterval);
            }
        }, 500);
        
        // Detener el intervalo después de 10 segundos
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 10000);
    });
    </script>
    
    <style>
        #shipping-breakdown {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        #validation-message {
            animation: slideIn 0.3s ease-in-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            #shipping-breakdown {
                font-size: 14px;
            }
            
            #shipping-breakdown div:last-child {
                font-size: 16px !important;
            }
        }
    </style>
    <?php
}
add_action('after_wpcfe_shipment_form_fields', 'custom_shipment_multipackage_template', 1, 1);

// Selector de productos para envíos - VERSION OPTIMIZADA CON MÚLTIPLES HOOKS
add_action('after_wpcfe_shipment_form_fields', 'merc_producto_selector_envio', 5, 1);
add_action('wpcfe_after_shipment_form_fields', 'merc_producto_selector_envio', 5, 1);
add_action('wpcfe_shipment_form_fields', 'merc_producto_selector_envio', 999, 1);
function merc_producto_selector_envio($shipment_id) {
    // SOLO mostrar si el tipo de envío es MERC FULL FITMENT
    if (!isset($_GET['type']) || $_GET['type'] !== 'full_fitment') {
        error_log("⚠️ Tipo de envío no es full_fitment, saltando selector de productos");
        return;
    }

    // Evitar renderizado múltiple
    static $ya_renderizado = false;
    if ($ya_renderizado) {
        error_log("⚠️ Selector ya renderizado, saltando");
        return;
    }
    $ya_renderizado = true;
    
    error_log("📦 === INICIO SELECTOR PRODUCTOS ===");
    error_log("Shipment ID: " . $shipment_id);
    error_log("Hook actual: " . current_action());
    
    // Obtener usuario actual
    $current_user_id = get_current_user_id();
    $es_admin = current_user_can('manage_options') || current_user_can('edit_others_posts');
    
    // Construir meta_query para filtrar por cliente
    $meta_query = array();
    if (!$es_admin) {
        // Clientes solo ven productos asignados específicamente a ellos
        $meta_query = array(
            array(
                'key' => '_merc_producto_cliente_asignado',
                'value' => $current_user_id,
                'compare' => '='
            )
        );
    }
    
    // Obtener productos disponibles filtrados por cliente
    $productos = get_posts(array(
        'post_type' => 'merc_producto',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => $meta_query
    ));
    
    error_log("Total productos encontrados para usuario #{$current_user_id}: " . count($productos));
    
    // Filtrar manualmente productos sin_asignar o sin estado (nuevos)
    // IMPORTANTE: Incluir también productos "asignados" si tienen stock disponible sin asignar
    $productos_disponibles = array();
    foreach ($productos as $prod) {
        $estado = get_post_meta($prod->ID, '_merc_producto_estado', true);
        $cantidad = merc_get_product_stock($prod->ID);
        
        error_log("Producto ID {$prod->ID} - Título: {$prod->post_title} - Estado: '{$estado}' - Cantidad disponible: '{$cantidad}'");
        
        // Incluir si:
        // 1. No tiene estado (nuevo) O está sin_asignar, O
        // 2. Está asignado pero TIENE stock disponible sin asignar
        if (empty($estado) || $estado === 'sin_asignar' || ($estado === 'asignado' && intval($cantidad) > 0)) {
            $productos_disponibles[] = $prod;
            error_log("  ✅ Producto ID {$prod->ID} INCLUIDO (estado: '{$estado}' | stock disponible: {$cantidad})");
        } else {
            error_log("  ❌ Producto ID {$prod->ID} EXCLUIDO (estado: '{$estado}' | stock disponible: {$cantidad})");
        }
    }
    
    error_log("Total productos disponibles después del filtro: " . count($productos_disponibles));
    
    // Obtener producto ya seleccionado (si existe)
    $producto_seleccionado = get_post_meta($shipment_id, '_merc_producto_id', true);
    $cantidad_seleccionada = get_post_meta($shipment_id, '_merc_producto_cantidad', true);
    
    error_log("Producto seleccionado: " . ($producto_seleccionado ? $producto_seleccionado : 'ninguno'));
    
    // Si hay producto seleccionado y no está en la lista, agregarlo
    if ($producto_seleccionado) {
        $producto_actual = get_post($producto_seleccionado);
        if ($producto_actual) {
            $ya_incluido = false;
            foreach ($productos_disponibles as $p) {
                if ($p->ID == $producto_seleccionado) {
                    $ya_incluido = true;
                    break;
                }
            }
            if (!$ya_incluido) {
                $productos_disponibles[] = $producto_actual;
                error_log("Producto seleccionado agregado a la lista");
            }
        }
    }
    
    if (empty($cantidad_seleccionada)) {
        $cantidad_seleccionada = 1;
    }
    
    error_log("⏳ Iniciando renderizado HTML...");
    
    // FORZAR salida inmediata
    ob_start();
    ?>
    
    <!-- INICIO SELECTOR PRODUCTOS MERC -->
    <?php wp_nonce_field('merc_envio_producto_guardar', 'merc_envio_producto_nonce'); ?>
    <div class="col-md-12 mb-4" id="merc_producto_wrapper" style="display: block !important; visibility: visible !important; opacity: 1 !important;">
        <div class="card">
            <section class="card-header">
                <strong>📦 Producto a Enviar</strong>
            </section>
            <section class="card-body">
                <?php if (empty($productos_disponibles)): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ No hay productos disponibles</strong><br>
                        Por favor, agrega productos al almacén desde el panel de administración.
                    </div>
                <?php else: ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="merc_producto_id"><strong>Producto *</strong></label>
                            <select id="merc_producto_id" name="merc_producto_id" class="form-control" required style="display: block !important; width: 100% !important;">
                                <option value="">-- Selecciona un producto --</option>
                                <?php foreach ($productos_disponibles as $prod): 
                                    $stock = merc_get_product_stock($prod->ID);
                                    $stock = !empty($stock) ? intval($stock) : 0;
                                    $codigo = get_post_meta($prod->ID, '_merc_producto_codigo_barras', true);
                                    $selected = ($prod->ID == $producto_seleccionado) ? 'selected' : '';
                                    
                                    error_log("Renderizando option para producto ID {$prod->ID}");
                                ?>
                                    <option value="<?php echo $prod->ID; ?>" 
                                            data-stock="<?php echo $stock; ?>"
                                            <?php echo $selected; ?>>
                                        <?php echo esc_html($prod->post_title); ?> - Stock: <?php echo $stock; ?>
                                        <?php if ($codigo): ?> [<?php echo esc_html($codigo); ?>]<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Solo se muestran productos disponibles (<?php echo count($productos_disponibles); ?> total)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="merc_producto_cantidad"><strong>Cantidad *</strong></label>
                            <input type="number" 
                                   id="merc_producto_cantidad" 
                                   name="merc_producto_cantidad" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($cantidad_seleccionada); ?>" 
                                   min="1" 
                                   max="999" 
                                   required
                                   style="display: block !important; width: 100% !important;">
                            <small id="merc_stock_display" class="text-muted"></small>
                        </div>
                    </div>
                </div>
                <div id="merc_stock_warning" class="alert alert-warning" style="display: none;">
                    <strong>⚠️</strong> <span id="merc_warning_text"></span>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <!-- FIN SELECTOR PRODUCTOS MERC -->
    
    <script>
    console.log('🚀 SCRIPT SELECTOR PRODUCTOS CARGADO - TIMESTAMP:', new Date().toISOString());
    
    jQuery(document).ready(function($) {
        console.log('📦 jQuery ready - Inicializando selector productos');
        
        const $wrapper = $('#merc_producto_wrapper');
        console.log('Wrapper encontrado:', $wrapper.length > 0);
        console.log('Wrapper visible:', $wrapper.is(':visible'));
        
        const $productoSelect = $('#merc_producto_id');
        const $cantidadInput = $('#merc_producto_cantidad');
        const $stockDisplay = $('#merc_stock_display');
        const $warning = $('#merc_stock_warning');
        const $warningText = $('#merc_warning_text');
        
        console.log('Select encontrado:', $productoSelect.length > 0);
        console.log('Opciones en select:', $productoSelect.find('option').length);
        
        function actualizarStock() {
            const $option = $productoSelect.find('option:selected');
            const stock = parseInt($option.data('stock')) || 0;
            const cantidad = parseInt($cantidadInput.val()) || 0;
            
            if (!$option.val()) {
                $stockDisplay.text('');
                $warning.hide();
                return;
            }
            
            $stockDisplay.html('📦 Disponible: <strong>' + stock + '</strong>');
            $cantidadInput.attr('max', stock);
            
            if (cantidad > stock) {
                $warning.show();
                $warningText.text('Stock insuficiente. Solo hay ' + stock + ' unidades disponibles.');
                $cantidadInput.val(stock);
            } else {
                $warning.hide();
            }
        }
        
        $productoSelect.on('change', actualizarStock);
        $cantidadInput.on('input change', actualizarStock);
        
        // Validar antes de enviar
        $('form.wpcfe-new-shipment-form, form[name="wpcfe-shipment-form"]').on('submit', function(e) {
            const productoId = $productoSelect.val();
            
            if (!productoId) {
                e.preventDefault();
                alert('⚠️ Debes seleccionar un producto');
                $productoSelect.focus();
                return false;
            }
            
            const stock = parseInt($productoSelect.find('option:selected').data('stock')) || 0;
            const cantidad = parseInt($cantidadInput.val()) || 0;
            
            if (cantidad > stock) {
                e.preventDefault();
                alert('⚠️ Stock insuficiente. Solo hay ' + stock + ' unidades.');
                $cantidadInput.focus();
                return false;
            }
        });
        
        // Inicializar
        actualizarStock();
        
        console.log('✅ Selector de productos inicializado correctamente');
    });
    </script>
    
    <style>
    #merc_producto_wrapper {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 1 !important;
    }
    </style>
    <?php
    $html = ob_get_clean();
    echo $html;
    
    error_log("✅ HTML renderizado - Longitud: " . strlen($html) . " caracteres");
    error_log("=== FIN SELECTOR PRODUCTOS ===");
}

// CÓDIGO ANTIGUO DESACTIVADO
/*
add_action('wp_enqueue_scripts', 'merc_enqueue_select2_scripts');
function merc_enqueue_select2_scripts() {
    // Solo cargar en la página de crear envío
    if (isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'add') {
        // Select2 CSS
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        
        // Select2 JS
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
    }
}

// CÓDIGO ANTIGUO - COMPLETAMENTE COMENTADO
/*
add_action('after_wpcfe_shipment_form_fields', 'merc_custom_producto_selector_template', 5, 1);
function merc_custom_producto_selector_template($shipment_id) {
    // Validación de seguridad
    if (!function_exists('get_posts')) {
        error_log("⚠️ ERROR: get_posts no está disponible");
        return;
    }
    
    try {
        error_log("🔍 DEBUG Selector Productos: Ejecutándose - Shipment ID: " . $shipment_id);
        
        // Obtener usuario actual
        $current_user_id = get_current_user_id();
        $es_admin = current_user_can('manage_options') || current_user_can('edit_others_posts');
        
        // Construir meta_query para filtrar por cliente
        $meta_query = array();
        if (!$es_admin) {
            // Clientes solo ven productos asignados específicamente a ellos
            $meta_query = array(
                array(
                    'key' => '_merc_producto_cliente_asignado',
                    'value' => $current_user_id,
                    'compare' => '='
                )
            );
        }
        
        // Obtener productos disponibles filtrados por cliente
        $productos = get_posts(array(
            'post_type' => 'merc_producto',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => $meta_query
        ));
        
        if (empty($productos)) {
            error_log("⚠️ No se encontraron productos para usuario #{$current_user_id}");
        }
    } catch (Exception $e) {
        error_log("⚠️ ERROR en merc_custom_producto_selector_template: " . $e->getMessage());
        return;
    }
    
    error_log("🔍 DEBUG: Total productos encontrados: " . count($productos));
    
    // Obtener producto seleccionado si existe
    $producto_seleccionado = get_post_meta($shipment_id, '_merc_producto_id', true);
    $cantidad_seleccionada = get_post_meta($shipment_id, '_merc_producto_cantidad', true);
    if (empty($cantidad_seleccionada)) {
        $cantidad_seleccionada = 1;
    }
    
    echo "<!-- DEBUG: Iniciando selector de productos -->";
    ?>
    <div id="merc_producto_selector_section" class="col-md-12 mb-4" style="background: #f0f8ff; padding: 20px; border: 2px solid #007bff; border-radius: 8px;">
        <div class="card">
            <section class="card-header" style="background: #007bff; color: white;">
                📦 Producto a Enviar (NUEVO SISTEMA)
            </section>
            <section class="card-body">
                <div class="alert alert-info">
                    <strong>ℹ️ Información:</strong> Esta es la nueva sección de selección de productos.
                    Total de productos disponibles: <strong><?php echo count($productos); ?></strong>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="merc_producto_envio_select"><strong>Seleccionar Producto *</strong></label>
                            <select id="merc_producto_envio_select" name="merc_producto_id" class="form-control merc-producto-select2" required style="width: 100%;">
                                <option value="">-- Selecciona un producto --</option>
                                <?php 
                                $productos_mostrados = 0;
                                foreach ($productos as $prod): 
                                    $stock = merc_get_product_stock($prod->ID);
                                    $stock = !empty($stock) ? intval($stock) : 0;
                                    $codigo_barras = get_post_meta($prod->ID, '_merc_producto_codigo_barras', true);
                                    $estado = get_post_meta($prod->ID, '_merc_producto_estado', true);
                                    if (empty($estado)) {
                                        $estado = 'sin_asignar';
                                    }
                                    
                                    // Solo mostrar productos sin asignar (disponibles) O productos asignados con stock disponible, o el ya seleccionado
                                    if ($estado !== 'sin_asignar' && $estado === 'asignado' && intval($stock) == 0 && $prod->ID != $producto_seleccionado) {
                                        continue;
                                    }
                                    
                                    $productos_mostrados++;
                                    $selected = ($prod->ID == $producto_seleccionado) ? 'selected' : '';
                                    $codigo_text = !empty($codigo_barras) ? " [Código: {$codigo_barras}]" : '';
                                ?>
                                    <option value="<?php echo $prod->ID; ?>" 
                                            data-stock="<?php echo $stock; ?>" 
                                            data-codigo="<?php echo esc_attr($codigo_barras); ?>"
                                            <?php echo $selected; ?>>
                                        <?php echo esc_html($prod->post_title); ?> - Stock: <?php echo $stock; ?><?php echo $codigo_text; ?>
                                    </option>
                                <?php endforeach; 
                                error_log("🔍 DEBUG: Productos disponibles mostrados en select: " . $productos_mostrados);
                                ?>
                            </select>
                            <small class="form-text text-muted">
                                💡 Puedes buscar por nombre o código de barras. Solo se muestran productos disponibles.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="merc_producto_envio_cantidad"><strong>Cantidad *</strong></label>
                            <input type="number" 
                                   id="merc_producto_envio_cantidad" 
                                   name="merc_producto_cantidad" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($cantidad_seleccionada); ?>" 
                                   min="1" 
                                   max="999" 
                                   required>
                            <small id="merc_stock_info" class="form-text text-muted"></small>
                        </div>
                    </div>
                </div>
                
                <div id="merc_producto_info_alert" class="alert alert-warning" style="display: none; margin-top: 15px;">
                    <strong>⚠️ Stock Insuficiente:</strong> <span id="merc_stock_msg"></span>
                </div>
            </section>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('🔍 DEBUG: Script del selector de productos cargado');
        
        // Esperar un poco para asegurarnos que el DOM está completamente cargado
        setTimeout(function() {
            const $productoSelect = $('#merc_producto_envio_select');
            const $cantidadInput = $('#merc_producto_envio_cantidad');
            const $stockInfo = $('#merc_stock_info');
            const $alert = $('#merc_producto_info_alert');
            const $stockMsg = $('#merc_stock_msg');
            
            if ($productoSelect.length === 0) {
                console.log('⚠️ Selector de productos no encontrado');
                return;
            }
            
            console.log('✅ Selector de productos encontrado');
            
            // Inicializar Select2 para búsqueda
            if (typeof $.fn.select2 !== 'undefined') {
                console.log('🔍 DEBUG: Inicializando Select2');
                try {
                    $productoSelect.select2({
                        placeholder: '🔍 Buscar producto por nombre o código...',
                        allowClear: true,
                        width: '100%'
                    });
                } catch(e) {
                    console.log('⚠️ Error al inicializar Select2:', e);
                }
            } else {
                console.log('⚠️ Select2 no está disponible, usando select normal');
            }
            
            function actualizarStockInfo() {
                const $selected = $productoSelect.find('option:selected');
                const stock = parseInt($selected.data('stock')) || 0;
                const cantidad = parseInt($cantidadInput.val()) || 1;
                
                if (!$selected.val()) {
                    $stockInfo.text('');
                    $alert.hide();
                    $cantidadInput.attr('max', 999);
                    return;
                }
                
                $cantidadInput.attr('max', stock);
                $stockInfo.html(`📦 Stock disponible: <strong>${stock}</strong> unidades`);
                
                if (cantidad > stock) {
                    $alert.show();
                    $stockMsg.text(`Solo hay ${stock} unidades disponibles. Ajusta la cantidad.`);
                    $cantidadInput.val(stock);
                } else {
                    $alert.hide();
                }
            }
            
            $productoSelect.on('change', actualizarStockInfo);
            $cantidadInput.on('input', actualizarStockInfo);
            
            // Validación antes del submit - usar evento más específico
            $(document).on('submit', '.wpcfe-new-shipment-form', function(e) {
                const productoId = $productoSelect.val();
                
                if (!productoId || productoId === '') {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('⚠️ Debes seleccionar un producto para continuar.');
                    
                    // Scroll al selector
                    $('html, body').animate({
                        scrollTop: $('#merc_producto_selector_section').offset().top - 100
                    }, 500);
                    
                    if (typeof $.fn.select2 !== 'undefined' && $productoSelect.hasClass('select2-hidden-accessible')) {
                        $productoSelect.select2('open');
                    } else {
                        $productoSelect.focus();
                    }
                    return false;
                }
                
                const $selected = $productoSelect.find('option:selected');
                const stock = parseInt($selected.data('stock')) || 0;
                const cantidad = parseInt($cantidadInput.val()) || 1;
                
                if (cantidad > stock) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert(`⚠️ Stock insuficiente. Solo hay ${stock} unidades disponibles.`);
                    $cantidadInput.focus();
                    return false;
                }
                
                console.log('✅ Validación de producto OK - Permitiendo submit');
            });
            
            // Inicializar
            actualizarStockInfo();
            
        }, 500); // Esperar 500ms
    });
    </script>
    
    <style>
    #merc_producto_selector_section {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .merc-producto-select2 {
        font-size: 14px;
    }
    #merc_stock_info {
        color: #17a2b8;
        font-weight: 600;
        margin-top: 5px;
    }
    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 6px 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    </style>
    <?php
    echo "<!-- DEBUG: Fin del selector de productos -->";
    error_log("✅ DEBUG: Template de selector de productos completado");
}
*/

//ROUTE PLANNER

// Función AJAX para obtener datos del shipment para WhatsApp
add_action('wp_ajax_get_shipment_whatsapp_data', 'get_shipment_whatsapp_data');
function get_shipment_whatsapp_data() {
    if (!isset($_POST['shipment_number'])) {
        wp_send_json_error('No shipment number provided');
    }
    
    $shipment_number = sanitize_text_field($_POST['shipment_number']);
    
    // Buscar el shipment por número
    $args = array(
        'post_type' => 'wpcargo_shipment',
        'meta_query' => array(
            array(
                'key' => 'wpcargo_shipment_number',
                'value' => $shipment_number,
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    );
    
    $shipments = get_posts($args);
    
    if (empty($shipments)) {
        wp_send_json_error('Shipment not found');
    }
    
    $shipment_id = $shipments[0]->ID;
    
    // Obtener datos necesarios
    $receiver_phone = get_post_meta($shipment_id, 'wpcargo_receiver_phone', true);
    $receiver_address = get_post_meta($shipment_id, 'wpcargo_receiver_address', true);
    $receiver_name = get_post_meta($shipment_id, 'wpcargo_receiver_name', true);
    $tienda_name = get_post_meta($shipment_id, 'wpcargo_tiendaname', true);
    $tienda_phone = get_post_meta($shipment_id, 'wpcargo_tiendaphone', true);
    $monto = get_post_meta($shipment_id, 'wpcargo_monto', true);
    
    // Obtener datos del REMITENTE (el cliente que creó el envío)
    $shipment_post = $shipments[0];
    $author_id = $shipment_post->post_author;
    $author_data = get_userdata($author_id);
    
    // Obtener el teléfono del remitente desde el campo correcto
    $sender_phone = get_post_meta($shipment_id, 'wpcargo_shipper_phone', true);
    
    // Si no tiene teléfono en el envío, intentar obtener del perfil del usuario
    if (empty($sender_phone)) {
        $sender_phone = get_user_meta($author_id, 'billing_phone', true);
    }
    
    error_log("📞 WhatsApp Data - Envío #{$shipment_id}: sender_phone = '{$sender_phone}', author_id = {$author_id}");
    
    // Obtener nombre del motorizado actual
    $current_user = wp_get_current_user();
    $motorizado_name = $current_user->display_name;
    
    // Limpiar el teléfono del destinatario (solo números)
    $receiver_phone = preg_replace('/[^0-9]/', '', $receiver_phone);
    
    // Asegurarse que tenga el código de país (Perú: 51)
    if (strlen($receiver_phone) == 9 && substr($receiver_phone, 0, 1) == '9') {
        $receiver_phone = '51' . $receiver_phone;
    }
    
    // Limpiar el teléfono del remitente (cliente que hizo el envío)
    $sender_phone = preg_replace('/[^0-9]/', '', $sender_phone);
    
    // Asegurarse que el teléfono del remitente tenga el código de país
    if (strlen($sender_phone) == 9 && substr($sender_phone, 0, 1) == '9') {
        $sender_phone = '51' . $sender_phone;
    }
    
    // Limpiar el teléfono de la tienda/empresa (solo números)
    $tienda_phone = preg_replace('/[^0-9]/', '', $tienda_phone);
    
    // Asegurarse que el teléfono de la tienda tenga el código de país
    if (strlen($tienda_phone) == 9 && substr($tienda_phone, 0, 1) == '9') {
        $tienda_phone = '51' . $tienda_phone;
    }
    
    wp_send_json_success(array(
        'phone' => $receiver_phone,
        'company_phone' => $sender_phone ?: '51999999999', // Ahora usa el teléfono del REMITENTE (cliente)
        'motorizado_name' => $motorizado_name,
        'receiver_name' => $receiver_name ?: 'N/A',
        'tienda_name' => $tienda_name ?: 'N/A',
        'sender_name' => $author_data ? $author_data->display_name : 'N/A',
        'receiver_address' => $receiver_address ?: 'N/A',
        'monto' => $monto ? floatval($monto) : 0
    ));
}

// ============================================================================
// ELIMINAR UBICACION
// ============================================================================


add_filter( 'wpcpod_signature_field_list', function( $fields ) {
    // Eliminar campos específicos del historial
    $labels_a_eliminar = array(
        'Ubicación',
        'Total a recibir',
        'Location' // En caso de que esté en inglés
    );

    foreach ( $fields as $key => $fieldinfo ) {
        // Eliminar por label
        if ( isset($fieldinfo['label']) ) {
            $label_limpio = trim($fieldinfo['label']);
            if ( in_array( $label_limpio, $labels_a_eliminar ) ) {
                unset($fields[$key]);
                continue;
            }
        }
        
        // También eliminar directamente el campo 'location' por su key
        if ( $key === 'location' ) {
            unset($fields[$key]);
            continue;
        }
    }

    // Filtrar opciones del campo Status según el rol del usuario
    $current_user = wp_get_current_user();
    $user_roles   = $current_user->roles;
    
    // Si es motorizado, ocultar PENDIENTE y LISTO PARA SALIR
    if ( in_array( 'wpcargo_driver', $user_roles ) ) {
        foreach ( $fields as $key => $fieldinfo ) {
            // Buscar el campo de estado (puede ser "status" o similar)
            if ( isset( $fieldinfo['field'] ) && $fieldinfo['field'] === 'select' && isset( $fieldinfo['options'] ) && is_array( $fieldinfo['options'] ) ) {
                // Remover estados no permitidos para motorizado (comparación insensible a mayúsculas)
                $estados_ocultar = array( 'PENDIENTE', 'LISTO PARA SALIR' );
                foreach ( $estados_ocultar as $estado_a_remover ) {
                    // Recorrer las opciones y eliminar coincidencias por key o por valor
                    if ( isset( $fields[$key]['options'] ) && is_array( $fields[$key]['options'] ) ) {
                        foreach ( $fields[$key]['options'] as $opt_key => $opt_value ) {
                            $val = is_array( $opt_value ) ? ( isset( $opt_value['label'] ) ? $opt_value['label'] : '' ) : $opt_value;
                            if ( strcasecmp( trim( $val ), $estado_a_remover ) === 0 || strcasecmp( trim( $opt_key ), $estado_a_remover ) === 0 ) {
                                unset( $fields[$key]['options'][$opt_key] );
                            }
                        }
                    }
                }
            }
        }
    }

    return $fields;
}, 50 );

// CSS y JS para ocultar completamente el campo Ubicación del historial
add_action('wp_head', function() {
    ?>
    <style>
        /* Ocultar el campo de ubicación en el historial por varios selectores */
        .pod-details .location,
        .pod-details label:contains('Ubicación'),
        .pod-details label:contains('Location'),
        .pod-details div:has(label:contains('Ubicación')),
        .pod-details div:has(label:contains('Location')),
        .pod-details .col-md-6:has(input#location),
        .pod-details .col-md-6:has(input.location),
        .pod-details .col-md-6:has(input[name*="location"]) {
            display: none !important;
        }
    </style>
    <?php
});

// JavaScript adicional para eliminar el campo ubicación del DOM
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Función para eliminar el campo de ubicación
        function eliminarCampoUbicacion() {
            // Buscar por el texto del label
            $('.pod-details label').each(function() {
                var labelText = $(this).text().trim().toLowerCase();
                if (labelText === 'ubicación' || labelText === 'location') {
                    $(this).closest('.col-md-6, .form-group, .mb-4').remove();
                }
            });
            
            // Buscar por name del input
            $('.pod-details input[name="location"], .pod-details input.location, .pod-details #location').each(function() {
                $(this).closest('.col-md-6, .form-group, .mb-4').remove();
            });
        }
        
        // Ejecutar al cargar
        eliminarCampoUbicacion();
        
        // Ejecutar cuando se abre el popup del POD
        $(document).on('click', '[data-target="#wpcargo-modal"]', function() {
            setTimeout(eliminarCampoUbicacion, 300);
            setTimeout(eliminarCampoUbicacion, 600);
            setTimeout(eliminarCampoUbicacion, 1000);
        });
        
        // Observar cambios en el DOM
        var observer = new MutationObserver(function(mutations) {
            eliminarCampoUbicacion();
        });
        
        var podContainer = document.querySelector('.pod-details');
        if (podContainer) {
            observer.observe(podContainer, {
                childList: true,
                subtree: true
            });
        }
    });
    </script>
    <?php
}, 999);

// Agregar script para deshabilitar botón Actualizar hasta que se complete el pago
add_action('wpcpod_after_sign_popup_form', 'merc_add_update_button_validation');
function merc_add_update_button_validation() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        var updateBtn = $('.delivered-btn[name="submit"]');
        
        // Deshabilitar botón inicialmente
        updateBtn.prop('disabled', true).css('opacity', '0.5');
        
        // Función para validar y habilitar/deshabilitar botón
        function validarBotonActualizar() {
            var errores = [];
            
            // 1. VALIDAR FIRMA (obligatorio)
           // var tieneFirma = $('#__pod_signature').val() !== '' && $('#__pod_signature').val() !== '0';
            //if (!tieneFirma) {
              //  errores.push('Debe firmar el documento');
            //}
            
            // 2. VALIDAR IMÁGENES (al menos una)
            var tieneImagenes = $('#wpcargo-pod-images .gallery-thumb').length > 0;
            if (!tieneImagenes) {
                errores.push('Debe adjuntar al menos una imagen');
            }
            
            // 3. VALIDAR ESTADO (obligatorio y no puede estar vacío)
            var estadoSeleccionado = $('select[name="status"]').val() || $('input[name="status"]').val() || $('.status').val() || '';
            
            // Debug log
            console.log('Estado seleccionado:', estadoSeleccionado);
            
            if (!estadoSeleccionado || estadoSeleccionado === '' || estadoSeleccionado === 'Seleccionar' || estadoSeleccionado === 'Pendiente') {
                errores.push('Debe seleccionar un estado válido (no puede ser Pendiente)');
            }
            
            // 4. VALIDAR MÉTODOS DE PAGO (solo si NO es "NO COBRAR")
            var esNoCobrar = $('#payment-area').find('p[style*="color:red"]').length > 0;
            
            if (!esNoCobrar) {
                // Obtener total ingresado y total esperado
                var totalIngresado = parseFloat($('#total-ingresado').text()) || 0;
                var montoTotal = window.podMontoTotal || 0;
                
                // Verificar si hay métodos de pago agregados
                var hayMetodos = $('.fila-metodo').length > 0;
                
                if (!hayMetodos && montoTotal > 0) {
                    errores.push('Debe agregar al menos un método de pago');
                }
                
                // Verificar si el total coincide
                if (Math.abs(totalIngresado - montoTotal) > 0.01 && montoTotal > 0) {
                    if (totalIngresado < montoTotal) {
                        errores.push('Falta completar S/. ' + (montoTotal - totalIngresado).toFixed(2));
                    } else {
                        errores.push('El total excede en S/. ' + (totalIngresado - montoTotal).toFixed(2));
                    }
                }
            }
            
            // Mostrar u ocultar errores
            if (errores.length > 0) {
                updateBtn.prop('disabled', true).css('opacity', '0.5');
                
                // Mostrar tooltip o mensaje con los errores
                var mensajeError = 'Faltan campos obligatorios:\n• ' + errores.join('\n• ');
                updateBtn.attr('title', mensajeError);
                
                // Opcional: mostrar mensaje visual debajo del botón
                if ($('#validation-errors').length === 0) {
                    updateBtn.after('<div id="validation-errors" style="color:red;font-size:12px;margin-top:10px;"></div>');
                }
                $('#validation-errors').html('<strong>⚠️ Campos obligatorios faltantes:</strong><br>' + errores.join('<br>• '));
            } else {
                updateBtn.prop('disabled', false).css('opacity', '1');
                updateBtn.attr('title', 'Actualizar envío');
                $('#validation-errors').remove();
            }
        }
        
        // Ejecutar validación cuando cambie cualquier campo
        $(document).on('input change', '.pay-amount, select[name="status"]', function() {
            setTimeout(validarBotonActualizar, 100);
        });
        
        // Ejecutar validación cuando se añada o elimine un método
        $(document).on('click', '#add-method, .remove-metodo', function() {
            setTimeout(validarBotonActualizar, 200);
        });
        
        // Ejecutar validación cuando se seleccione un método
        $(document).on('click', '.method-option', function() {
            setTimeout(validarBotonActualizar, 100);
        });
        
        // Ejecutar validación después de guardar firma
        $(document).on('click', '#pod-save', function() {
            setTimeout(validarBotonActualizar, 1000);
        });
        
        // Ejecutar validación después de agregar/eliminar imágenes
        var imageObserver = new MutationObserver(function() {
            setTimeout(validarBotonActualizar, 200);
        });
        
        var imageContainer = document.getElementById('wpcargo-pod-images');
        if (imageContainer) {
            imageObserver.observe(imageContainer, { childList: true, subtree: true });
        }
        
        // Validación inicial y periódica
        setTimeout(validarBotonActualizar, 500);
        setInterval(validarBotonActualizar, 2000);
    });
    </script>
    <?php
}

// Agregar funcionalidad de imágenes obligatorias por método de pago
// NOTA: Esta función ya no es necesaria porque se modificó directamente el template del POD
// add_action('wpcpod_after_sign_popup_form', 'merc_add_payment_method_images', 20);
/*
function merc_add_payment_method_images() {
    // Código comentado - modificación hecha directamente en el template
}
*/

// Guardar métodos de pago cuando se actualiza el POD
add_action('wpcargo_extra_pod_saving', 'merc_save_pod_payment_methods', 10, 2);
function merc_save_pod_payment_methods($shipment_id, $form_data) {
    if (empty($shipment_id)) return;
    
    // ======== LOCK: Prevenir ejecuciones simultáneas usando post_meta como semáforo ========
    $lock_key = '_merc_pod_processing_lock';
    $lock_timeout = 5; // segundos
    $max_attempts = 3;
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        $attempt++;
        $current_lock = get_post_meta($shipment_id, $lock_key, true);
        $current_time = time();
        
        if ($current_lock) {
            $lock_time = (int)$current_lock;
            $elapsed = $current_time - $lock_time;
            
            if ($elapsed < $lock_timeout) {
                // Lock aún vigente - otra petición está procesando
                if ($attempt < $max_attempts) {
                    error_log('🔵 merc_save_pod_payment_methods - ESPERA: otra petición está procesando (shipment=' . $shipment_id . ', elapsed=' . $elapsed . 's)');
                    usleep(500000); // esperar 0.5 segundos
                    continue;
                } else {
                    // Ya esperamos suficiente - retornar para evitar bloqueo indefinido
                    error_log('🔵 merc_save_pod_payment_methods - SKIP: timeout esperando lock (shipment=' . $shipment_id . ')');
                    return;
                }
            }
        }
        
        // Lock expiró o no existe - establecer nuevo lock
        update_post_meta($shipment_id, $lock_key, $current_time);
        break; // salir del while, tenemos el lock
    }
    
    // ======== Verificar si ya se procesó completamente (double-check) ========
    $processed_time = get_post_meta($shipment_id, '_merc_pod_payment_processed_time', true);
    if ($processed_time) {
        $time_elapsed = time() - (int)$processed_time;
        if ($time_elapsed < $lock_timeout) { // dentro del timeout, probablemente es el mismo request
            error_log('🔵 merc_save_pod_payment_methods - SKIP: ya procesado recientemente (shipment=' . $shipment_id . ', hace ' . $time_elapsed . 's)');
            delete_post_meta($shipment_id, $lock_key); // liberar lock
            return;
        }
    }
    
    // Log para debugging (resumido para evitar volcar imágenes base64)
    error_log('🔵 merc_save_pod_payment_methods ejecutado - Shipment ID: ' . $shipment_id);
    if (is_array($form_data)) {
        $form_names = array();
        foreach ($form_data as $d) {
            if (is_array($d) && isset($d['name'])) $form_names[] = $d['name'];
        }
        error_log('🔵 form_data keys: ' . implode(',', $form_names) . ' (count=' . count($form_names) . ')');
    } else {
        error_log('🔵 form_data no es array (tipo=' . gettype($form_data) . ')');
    }
    // Resumen de $_POST: listar claves y longitud del campo pod_payment_methods si existe
    if (!empty($_POST) && is_array($_POST)) {
        error_log('🔵 $_POST keys: ' . implode(',', array_keys($_POST)));
        if (isset($_POST['pod_payment_methods']) && is_string($_POST['pod_payment_methods'])) {
            error_log('🔵 pod_payment_methods_len=' . strlen($_POST['pod_payment_methods']));
        }
    } else {
        error_log('🔵 $_POST vacío o no es array');
    }
    
    // Buscar el campo de métodos de pago en los datos del formulario
    $payment_methods_json = '';
    
            foreach ($form_data as $data) {
                if (isset($data['name']) && $data['name'] === 'pod_payment_methods') {
                    $payment_methods_json = isset($data['value']) && is_string($data['value']) ? $data['value'] : '';
                    // No volcar contenido para evitar registrar imágenes base64; sólo longitud y prefijo
                    $len = is_string($payment_methods_json) ? strlen($payment_methods_json) : 0;
                    $preview = is_string($payment_methods_json) ? substr($payment_methods_json, 0, 200) : '';
                    if ($len > 200) $preview .= '...';
                    error_log('🔵 pod_payment_methods encontrado en form_data (len=' . $len . ') preview=' . $preview);
                    break;
                }
            }
    
    // También intentar obtenerlo directamente de $_POST como fallback
    if (empty($payment_methods_json) && isset($_POST['pod_payment_methods']) && is_string($_POST['pod_payment_methods'])) {
        $payment_methods_json = $_POST['pod_payment_methods'];
        $len = strlen($payment_methods_json);
        $preview = substr($payment_methods_json, 0, 200);
        if ($len > 200) $preview .= '...';
        error_log('🔵 pod_payment_methods obtenido de $_POST (len=' . $len . ') preview=' . $preview);
    }
    
    // Si se encontró el campo, guardarlo y procesar las imágenes
    if (!empty($payment_methods_json)) {
        error_log('✅ Procesando métodos de pago...');
        error_log('✅ Tamaño del JSON: ' . strlen($payment_methods_json) . ' bytes');
        error_log('✅ Primeros 500 caracteres del JSON: ' . substr($payment_methods_json, 0, 500));
        error_log('✅ Últimos 100 caracteres del JSON: ' . substr($payment_methods_json, -100));
        
        // Intentar decodificar sin stripslashes primero
        $methods = json_decode($payment_methods_json, true);
        
        // Si falla, intentar con stripslashes (WordPress puede agregar slashes)
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('⚠️ Primer intento falló: ' . json_last_error_msg() . ' - Intentando con stripslashes...');
            $payment_methods_json = stripslashes($payment_methods_json);
            $methods = json_decode($payment_methods_json, true);
        }
        
        error_log('✅ json_last_error después de stripslashes: ' . json_last_error_msg());
        error_log('✅ Número de métodos decodificados: ' . (is_array($methods) ? count($methods) : 0));
        
        if (is_array($methods) && count($methods) > 0) {
            error_log('✅ ' . count($methods) . ' métodos decodificados correctamente');
            
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $imagenes_guardadas = [];
            
            foreach ($methods as $index => &$method) {
                error_log('🔍 Procesando método ' . $index . ' - Método: ' . (isset($method['metodo']) ? $method['metodo'] : 'N/A'));
                
                // Si el método tiene una imagen en base64, guardarla
                if (isset($method['imagen']) && !empty($method['imagen'])) {
                    error_log('📸 Método ' . $index . ' contiene imagen');
                    
                    $imagen_base64 = $method['imagen'];
                    $imagen_nombre = isset($method['imagen_nombre']) ? $method['imagen_nombre'] : 'comprobante-' . $index . '.jpg';
                    
                    // Extraer el tipo de imagen y los datos
                    if (preg_match('/^data:image\/(\w+);base64,/', $imagen_base64, $matches)) {
                        $tipo_imagen = $matches[1]; // jpg, png, etc
                        
                        $imagen_data = substr($imagen_base64, strpos($imagen_base64, ',') + 1);
                        $imagen_data = base64_decode($imagen_data);
                        
                        // Crear un nombre único para el archivo
                        $upload_dir = wp_upload_dir();
                        $nombre_archivo = 'metodo-pago-' . $shipment_id . '-' . $index . '-' . time() . '.' . $tipo_imagen;
                        $ruta_archivo = $upload_dir['path'] . '/' . $nombre_archivo;
                        
                        // Guardar el archivo
                        if (file_put_contents($ruta_archivo, $imagen_data)) {
                            error_log('✅ Archivo guardado: ' . $nombre_archivo);
                            
                            // Crear el attachment
                            $attachment = array(
                                'post_mime_type' => 'image/' . $tipo_imagen,
                                'post_title' => 'Comprobante de pago - ' . $method['metodo'],
                                'post_content' => '',
                                'post_status' => 'inherit',
                                'post_parent' => $shipment_id
                            );
                            
                            $attach_id = wp_insert_attachment($attachment, $ruta_archivo, $shipment_id);
                            
                            if (!is_wp_error($attach_id)) {
                                // Generar metadata del attachment
                                $attach_data = wp_generate_attachment_metadata($attach_id, $ruta_archivo);
                                wp_update_attachment_metadata($attach_id, $attach_data);
                                
                                // Guardar el ID del attachment en el método
                                $method['imagen_id'] = $attach_id;
                                $method['imagen_url'] = wp_get_attachment_url($attach_id);
                                $imagenes_guardadas[] = $attach_id;
                                
                                error_log('✅ Imagen guardada - ID: ' . $attach_id);
                                
                                // Remover la imagen base64 del JSON para ahorrar espacio
                                unset($method['imagen']);
                                unset($method['imagen_nombre']);
                            } else {
                                error_log('❌ Error al crear attachment para método ' . $index);
                            }
                        } else {
                            error_log('❌ Error al guardar archivo para método ' . $index);
                        }
                    }
                }
            }
            
            // Nota: no guardamos aún el JSON final; primero ajustaremos los montos
            // POS (descontar 5%) para que lo que quede almacenado sea el neto.
            
            // Guardar la lista de IDs de imágenes de comprobantes
            if (!empty($imagenes_guardadas)) {
                update_post_meta($shipment_id, 'pod_payment_images', $imagenes_guardadas);
                error_log('✅ IDs de imágenes guardadas: ' . implode(', ', $imagenes_guardadas));
            }
            
            // Acumular totales usando exactamente el monto que llega (sin distinguir bruto/neto)
            $total_efectivo = 0.0;
            $total_pago_marca = 0.0;
            $total_pago_merc = 0.0;
            $total_pos = 0.0; // suma del monto POS tal como llega
            $hay_pos = false;
            // Primero: calcular totales brutos y totales POS brutos para distribuir la comisión
            $sum_bruto_total = 0.0;
            $sum_pos_bruto = 0.0;
            $sum_pos_bruto_original = 0.0; // Guardar el monto original ingresado antes de ajustes
            foreach ( $methods as $m ) {
                if ( isset( $m['monto'] ) ) {
                    $sum_bruto_total += floatval( $m['monto'] );
                    if ( isset( $m['metodo'] ) && $m['metodo'] === 'pos' ) {
                        $sum_pos_bruto += floatval( $m['monto'] );
                        $sum_pos_bruto_original += floatval( $m['monto'] );
                    }
                }
            }

            // Comisión total = 5% del MONTO TOTAL del envío.
            // Prioridad para la fuente del total:
            // 1) campo enviado por el formulario `wpcargo_total_cobrar` (form_data o $_POST)
            // 2) meta del post `wpcargo_total_cobrar`
            // 3) meta antigua `wpcargo_monto`
            // 4) reconstruir desde producto + envío y persistir en `wpcargo_total_cobrar`.
            $used_total_for_commission = 0.0;
            $commission_source = 'none';


            // 1) Preferir la meta `wpcargo_total_cobrar` en el servidor (evita usar valores intermedios enviados por AJAX)
            $meta_total = floatval( get_post_meta( $shipment_id, 'wpcargo_total_cobrar', true ) );
            if ( $meta_total > 0 ) {
                $used_total_for_commission = $meta_total;
                $commission_source = 'meta:wpcargo_total_cobrar';
            }

            // 2) si no existe meta, buscar en form_data
            if ( $used_total_for_commission <= 0 && isset( $form_data ) && is_array( $form_data ) ) {
                foreach ( $form_data as $d ) {
                    if ( isset( $d['name'] ) && $d['name'] === 'wpcargo_total_cobrar' ) {
                        $val = is_string( $d['value'] ) ? $d['value'] : '';
                        $val = str_replace(',', '', $val);
                        $used_total_for_commission = floatval( $val );
                        $commission_source = 'form_data';
                        break;
                    }
                }
            }

            // 3) si no vino en form_data, revisar $_POST
            if ( $used_total_for_commission <= 0 && isset( $_POST['wpcargo_total_cobrar'] ) ) {
                $val = is_string( $_POST['wpcargo_total_cobrar'] ) ? $_POST['wpcargo_total_cobrar'] : '';
                $val = str_replace(',', '', $val);
                $used_total_for_commission = floatval( $val );
                $commission_source = 'post';
            }

            // 4) fallback a meta histórica `wpcargo_monto`
            if ( $used_total_for_commission <= 0 ) {
                $old_meta = floatval( get_post_meta( $shipment_id, 'wpcargo_monto', true ) );
                if ( $old_meta > 0 ) {
                    $used_total_for_commission = $old_meta;
                    $commission_source = 'meta:wpcargo_monto';
                }
            }

            // 5) si aún no hay total, reconstruir desde producto + envío y persistir
            if ( $used_total_for_commission <= 0 ) {
                $costo_producto = floatval( get_post_meta( $shipment_id, 'wpcargo_costo_producto', true ) );
                $costo_envio = function_exists( 'merc_get_adjusted_service_cost' ) ? merc_get_adjusted_service_cost( $shipment_id ) : floatval( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) );
                $reconstructed = $costo_producto + $costo_envio;
                if ( $reconstructed > 0 ) {
                    $used_total_for_commission = $reconstructed;
                    $commission_source = 'reconstructed';
                    // Persistir para futuras llamadas
                    update_post_meta( $shipment_id, 'wpcargo_total_cobrar', number_format( $reconstructed, 2, '.', '' ) );
                    error_log('MERC_POD_DEBUG - reconstructed wpcargo_total_cobrar saved=' . number_format($reconstructed,2));
                }
            }

            // Calcular la comisión SOLO sobre el faltante (total - suma de métodos no-POS)
            // Esto significa: 5% aplica SOLO a lo que falta cobrar con POS
            $sum_no_pos = 0.0;
            foreach ($methods as $m) {
                if (isset($m['metodo']) && $m['metodo'] !== 'pos' && isset($m['monto'])) {
                    $sum_no_pos += floatval($m['monto']);
                }
            }
            $faltante = $used_total_for_commission - $sum_no_pos;
            $faltante = max(0, $faltante); // Asegurar que no sea negativo
            $commission_total = ($faltante > 0) ? round($faltante * 0.05, 2) : 0.0;

            // Debug: registrar valores clave de la comisión y su fuente
            error_log('MERC_POD_DEBUG - commission calc: used_total_for_commission=' . number_format($used_total_for_commission,2) .
                ' source=' . $commission_source .
                ' sum_no_pos=' . number_format($sum_no_pos,2) .
                ' faltante=' . number_format($faltante,2) .
                ' commission_total=' . number_format($commission_total,2)
            );

            // Repartir la comisión entre los POS proporcionalmente (controlando redondeos)
            $commission_allocated = 0.0;
            $pos_count = 0;
            foreach ( $methods as $m ) {
                if ( isset( $m['metodo'] ) && $m['metodo'] === 'pos' && floatval( $m['monto'] ) > 0 ) {
                    $pos_count++;
                }
            }

            // Debug: cuántos POS se detectaron
            error_log('MERC_POD_DEBUG - pos_count=' . intval($pos_count));

            $pos_seen = 0;
            foreach ($methods as $idx => &$method) {
                $metodo = isset($method['metodo']) ? $method['metodo'] : '';
                $monto  = isset($method['monto']) ? floatval($method['monto']) : 0.0;

                if ($metodo === 'pos') {
                    $pos_seen++;
                    // Restar comisión del monto POS bruto
                    $monto_bruto = $monto;
                    $monto_neto = $monto_bruto - $commission_total;
                    $monto_neto = max(0, $monto_neto); // Asegurar que no sea negativo
                    
                    $method['monto'] = $monto_neto; // Guardar neto (restando comisión)
                    $method['monto_original'] = $monto_bruto;

                    // Debug: registrar el POS con cálculo de comisión
                    error_log('MERC_POD_DEBUG - pos_recibido index=' . $idx . ' monto_bruto=' . number_format($monto_bruto,2) .
                        ' commission=' . number_format($commission_total,2) . ' monto_neto=' . number_format($monto_neto,2)
                    );

                    $total_pos += $monto_neto;
                    $hay_pos = ($monto_neto > 0);
                } else {
                    // Otros métodos se guardan tal cual
                    if ($metodo === 'efectivo') {
                        $total_efectivo += $monto;
                    } elseif ($metodo === 'pago_marca') {
                        $total_pago_marca += $monto;
                    } elseif ($metodo === 'pago_merc') {
                        $total_pago_merc += $monto;
                    }
                }
            }
            unset($method);

            // Guardar el JSON final (con montos POS ya neteados)
            $payment_methods_json = json_encode($methods);
            update_post_meta($shipment_id, 'pod_payment_methods', $payment_methods_json);
            error_log('✅ Métodos de pago guardados en post meta (POS neteado en JSON)');

            // Guardar la lista de IDs de imágenes de comprobantes
            if (!empty($imagenes_guardadas)) {
                update_post_meta($shipment_id, 'pod_payment_images', $imagenes_guardadas);
                error_log('✅ IDs de imágenes guardadas: ' . implode(', ', $imagenes_guardadas));
            }

            // Guardar cada total en su meta correspondiente
            update_post_meta($shipment_id, 'wpcargo_efectivo', $total_efectivo);
            update_post_meta($shipment_id, 'wpcargo_pago_marca', $total_pago_marca);
            update_post_meta($shipment_id, 'wpcargo_pago_merc', $total_pago_merc);
            // Guardar POS: el monto que llega (sin modificaciones)
            update_post_meta($shipment_id, 'wpcargo_pos', $total_pos);

            error_log('✅ Totales guardados - Efectivo: ' . $total_efectivo . ', Marca: ' . $total_pago_marca . ', MERC: ' . $total_pago_merc . ', POS: ' . $total_pos);

            // LÓGICA DE ESTADOS DE PAGO:
            // Si se registró pago con POS (monto > 0), marcar como liquidado.
            if ($hay_pos && $total_pos > 0) {
                update_post_meta($shipment_id, 'wpcargo_estado_pago_motorizado', 'liquidado');
                update_post_meta($shipment_id, 'wpcargo_fecha_liquidacion_motorizado', current_time('Y-m-d H:i:s'));
            } else {
                $estado_actual = get_post_meta($shipment_id, 'wpcargo_estado_pago_motorizado', true);
                if (empty($estado_actual) || $estado_actual === 'pendiente') {
                    update_post_meta($shipment_id, 'wpcargo_estado_pago_motorizado', 'pendiente');
                }
            }
            // Si el formulario o la firma indica ENTREGADO, actualizar el estado del envío
            try {
                $estado_form = '';
                if ( isset( $form_data ) && is_array( $form_data ) ) {
                    foreach ( $form_data as $d ) {
                        if ( isset( $d['name'] ) && $d['name'] === 'status' ) {
                            $estado_form = $d['value'];
                            break;
                        }
                    }
                }
                if ( empty( $estado_form ) && isset( $_POST['status'] ) ) {
                    $estado_form = sanitize_text_field( $_POST['status'] );
                }

                // Detectar firma en form_data o en $_POST
                $has_signature = false;
                if ( isset( $form_data ) && is_array( $form_data ) ) {
                    foreach ( $form_data as $d ) {
                        if ( isset( $d['name'] ) && stripos( $d['name'], 'signature' ) !== false && ! empty( $d['value'] ) ) {
                            $has_signature = true;
                            break;
                        }
                    }
                }
                if ( ! $has_signature && ! empty( $_POST['__pod_signature'] ) ) {
                    $has_signature = true;
                }
                if ( ! $has_signature && ! empty( $_POST['pod_signature'] ) ) {
                    $has_signature = true;
                }

                if ( $has_signature || ( ! empty( $estado_form ) && strtoupper( trim( $estado_form ) ) === 'ENTREGADO' ) ) {
                    update_post_meta( $shipment_id, 'wpcargo_status', 'ENTREGADO' );
                    merc_sync_service_cost_by_status( $shipment_id );
                    // Añadir entrada simple al historial para reflejar el cambio
                    $hist = get_post_meta( $shipment_id, 'wpcargo_shipments_update', true );
                    if ( ! is_array( $hist ) ) $hist = array();
                    $hist[] = array( 'date' => current_time( 'mysql' ), 'status' => 'ENTREGADO', 'note' => 'Guardado por merc_save_pod_payment_methods' );
                    update_post_meta( $shipment_id, 'wpcargo_shipments_update', $hist );
                    error_log('✅ Estado actualizado a ENTREGADO por merc_save_pod_payment_methods para shipment ' . $shipment_id);
                }
            } catch ( Exception $e ) {
                error_log('❌ Error al intentar actualizar estado ENTREGADO: ' . $e->getMessage());
            }
            
            // Marcar este formulario como procesado (protección contra reenvío)
            update_post_meta($shipment_id, '_merc_pod_payment_processed_time', time());
            error_log('✅ Formulario marcado como procesado para shipment ' . $shipment_id);
        } else {
            error_log('❌ No se pudieron decodificar los métodos o el array está vacío');
        }
    } else {
        error_log('❌ No se encontró pod_payment_methods en el formulario');
    }
    
    // ======== LIBERAR LOCK ========
    delete_post_meta($shipment_id, '_merc_pod_processing_lock');
}

// AJAX: recibir debug del cliente POD y registrar en error_log (trazabilidad)
add_action('wp_ajax_merc_pod_client_debug', 'merc_pod_client_debug_ajax');
add_action('wp_ajax_nopriv_merc_pod_client_debug', 'merc_pod_client_debug_ajax');
function merc_pod_client_debug_ajax() {
    $payload = array();
    // Recoger algunos campos esperados
    $payload['shipmentID'] = isset($_POST['shipmentID']) ? sanitize_text_field($_POST['shipmentID']) : '';
    $payload['context'] = isset($_POST['context']) ? sanitize_text_field($_POST['context']) : '';
    $payload['pod_payment_methods'] = isset($_POST['pod_payment_methods']) ? wp_unslash($_POST['pod_payment_methods']) : '';
    $payload['methods_count'] = isset($_POST['methods_count']) ? intval($_POST['methods_count']) : 0;
    $payload['pod_signature'] = isset($_POST['pod_signature']) ? sanitize_text_field($_POST['pod_signature']) : '';

    // Dump minimal info to error_log for mapping
    error_log('MERC_POD_DEBUG - shipmentID=' . $payload['shipmentID'] . ' context=' . $payload['context'] . ' methods_count=' . $payload['methods_count']);
    if (!empty($payload['pod_payment_methods'])) {
        // Truncate to avoid huge logs
        $str = is_string($payload['pod_payment_methods']) ? $payload['pod_payment_methods'] : json_encode($payload['pod_payment_methods']);
        if (strlen($str) > 2000) $str = substr($str,0,2000) . '...';
        error_log('MERC_POD_DEBUG - pod_payment_methods: ' . $str);
    }
    if (!empty($payload['pod_signature'])) {
        error_log('MERC_POD_DEBUG - pod_signature present (length=' . strlen($payload['pod_signature']) . ')');
    }

    wp_send_json_success(array('received' => true));
}

// Mostrar métodos de pago en el frontend (tracking)
// add_action('wpcargo_track_shipment_details', 'merc_display_payment_methods_frontend', 20);
// add_action('wpcargo_after_track_details', 'merc_display_payment_methods_frontend', 20);
add_action('wpcargo_after_package_details', 'merc_display_payment_methods_frontend', 20);
function merc_display_payment_methods_frontend($shipment_detail) {
    
    // BLOQUEO TOTAL PARA USUARIOS NO LOGUEADOS
    if ( ! is_user_logged_in() ) {
        return;
    }
    // Evitar renderizado duplicado
    //static $ya_mostrado = false;
    //if ($ya_mostrado) {
    //    return;
    //}
    //$ya_mostrado = true;
    
    // Debug detallado
    error_log('🔍 === INICIO merc_display_payment_methods_frontend ===');
    error_log('🔍 Tipo de $shipment_detail: ' . gettype($shipment_detail));
    
    if (is_array($shipment_detail)) {
        error_log('🔍 Es array, keys: ' . implode(', ', array_keys($shipment_detail)));
        error_log('🔍 Contenido completo: ' . json_encode($shipment_detail));
    } elseif (is_object($shipment_detail)) {
        error_log('🔍 Es objeto: ' . get_class($shipment_detail));
        if (isset($shipment_detail->ID)) {
            error_log('🔍 Tiene propiedad ID: ' . $shipment_detail->ID);
        }
    } else {
        error_log('🔍 Valor directo: ' . var_export($shipment_detail, true));
    }
    
    // Obtener el ID del shipment (puede venir como array, objeto o ID directo)
    $shipment_id = null;
    
    if (is_array($shipment_detail) && isset($shipment_detail['ID'])) {
        $shipment_id = $shipment_detail['ID'];
        error_log('✅ ID desde array[ID]: ' . $shipment_id);
    } elseif (is_array($shipment_detail) && isset($shipment_detail['id'])) {
        $shipment_id = $shipment_detail['id'];
        error_log('✅ ID desde array[id]: ' . $shipment_id);
    } elseif (is_object($shipment_detail) && isset($shipment_detail->ID)) {
        $shipment_id = $shipment_detail->ID;
        error_log('✅ ID desde objeto->ID: ' . $shipment_id);
    } elseif (is_numeric($shipment_detail)) {
        $shipment_id = $shipment_detail;
        error_log('✅ ID directo: ' . $shipment_id);
    } else {
        error_log('❌ No se pudo obtener shipment_id');
        error_log('❌ Estructura completa: ' . print_r($shipment_detail, true));
        return;
    }
    
    error_log('✅ Shipment ID final obtenido: ' . $shipment_id);
    
    // Obtener los métodos de pago guardados
    $payment_methods_json = get_post_meta($shipment_id, 'pod_payment_methods', true);
    
    if (empty($payment_methods_json)) {
        return; // No hay métodos de pago
    }
    
    $methods = json_decode($payment_methods_json, true);
    
    if (!is_array($methods) || count($methods) === 0) {
        return; // No hay métodos válidos
    }
    
    // Nombres amigables de los métodos
    $method_names = array(
        'efectivo' => 'Pago a motorizado',
        'pago_marca' => 'Pago a Marca',
        'pago_merc' => 'Pago a MERC',
        'pos' => 'POS'
    );
    
    ?>
    <div id="merc-payment-methods-section" style="margin-top: 30px; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 20px 0; color: #333; font-size: 20px; border-bottom: 3px solid #0073aa; padding-bottom: 10px;">
            💳 Métodos de Pago Registrados
        </h3>
        
        <div style="display: grid; gap: 15px;">
            <?php 
            $total_general = 0;
            foreach ($methods as $index => $method): 
                $metodo = isset($method['metodo']) ? $method['metodo'] : '';
                $monto = isset($method['monto']) ? floatval($method['monto']) : 0;
                $imagen_url = isset($method['imagen_url']) ? $method['imagen_url'] : '';
                
                $total_general += $monto;
                
                $nombre_metodo = isset($method_names[$metodo]) ? $method_names[$metodo] : ucfirst($metodo);
            ?>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 5px solid #0073aa; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div style="flex: 1; min-width: 200px;">
                        <div style="font-size: 16px; font-weight: bold; color: #0073aa; margin-bottom: 8px;">
                            <?php echo esc_html($nombre_metodo); ?>
                        </div>
                        <div style="font-size: 24px; font-weight: bold; color: #333;">
                            S/. <?php echo number_format($monto, 2); ?>
                        </div>
                    </div>
                    
                    <?php if ($imagen_url): ?>
                        <div style="text-align: center;">
                            <a href="<?php echo esc_url($imagen_url); ?>" target="_blank" style="display: inline-block; text-decoration: none;">
                                <img src="<?php echo esc_url($imagen_url); ?>" 
                                     alt="Comprobante de pago" 
                                     style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 3px solid #ddd; cursor: pointer; transition: all 0.3s ease;">
                                <div style="margin-top: 8px; font-size: 12px; color: #666; font-weight: bold;">
                                    📸 Clic para ver comprobante
                                </div>
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="color: #999; font-style: italic; padding: 10px;">
                            Sin comprobante adjunto
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div style="background: linear-gradient(135deg, #0073aa 0%, #005177 100%); color: white; padding: 20px; border-radius: 8px; text-align: right; box-shadow: 0 3px 6px rgba(0,0,0,0.15);">
                <div style="font-size: 14px; margin-bottom: 5px; opacity: 0.9;">TOTAL RECAUDADO</div>
                <div style="font-size: 28px; font-weight: bold;">
                    S/. <?php echo number_format($total_general, 2); ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @media (max-width: 768px) {
            #merc-payment-methods-section div[style*="display: flex"] {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
        }
        #merc-payment-methods-section img[alt="Comprobante de pago"]:hover {
            border-color: #0073aa !important;
            transform: scale(1.05);
        }
    </style>
    <?php
}

function merc_enqueue_tracking_styles() {
    wp_enqueue_style(
        'merc-tracking-styles',
        get_stylesheet_directory_uri() . '/style.css',
        array(),
        filemtime( get_stylesheet_directory() . '/style.css' ) // Invalida caché
    );
}
add_action( 'wp_enqueue_scripts', 'merc_enqueue_tracking_styles', 999 );

// Mostrar métodos de pago en el admin de WordPress
add_action('add_meta_boxes', 'merc_add_payment_methods_metabox');
function merc_add_payment_methods_metabox() {
    add_meta_box(
        'merc_payment_methods_metabox',
        '💳 Métodos de Pago del Motorizado',
        'merc_render_payment_methods_metabox',
        'wpcargo_shipment',
        'normal',
        'high'
    );
}

function merc_render_payment_methods_metabox($post) {
    $shipment_id = $post->ID;
    
    // Obtener los métodos de pago guardados
    $payment_methods_json = get_post_meta($shipment_id, 'pod_payment_methods', true);
    
    if (empty($payment_methods_json)) {
        echo '<p style="color: #999; font-style: italic;">No se han registrado métodos de pago para este envío.</p>';
        return;
    }
    
    $methods = json_decode($payment_methods_json, true);
    
    if (!is_array($methods) || count($methods) === 0) {
        echo '<p style="color: #999; font-style: italic;">No se han registrado métodos de pago válidos.</p>';
        return;
    }
    
    // Nombres amigables de los métodos
    $method_names = array(
        'efectivo' => 'Pago a motorizado',
        'pago_marca' => 'Pago a Marca',
        'pago_merc' => 'Pago a MERC',
        'pos' => 'POS'
    );
    
    ?>
    <style>
        .payment-method-item {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #0073aa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .payment-method-info {
            flex: 1;
        }
        .payment-method-name {
            font-size: 16px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }
        .payment-method-amount {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .payment-method-image {
            margin-left: 15px;
        }
        .payment-method-image img {
            max-width: 120px;
            max-height: 120px;
            border-radius: 5px;
            border: 2px solid #ddd;
            cursor: pointer;
        }
        .payment-method-image img:hover {
            border-color: #0073aa;
        }
        .payment-total {
            background: #0073aa;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: right;
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
    
    <div class="payment-methods-container">
        <?php 
        $total_general = 0;
        foreach ($methods as $index => $method): 
            $metodo = isset($method['metodo']) ? $method['metodo'] : '';
            $monto = isset($method['monto']) ? floatval($method['monto']) : 0;
            $imagen_id = isset($method['imagen_id']) ? intval($method['imagen_id']) : 0;
            $imagen_url = isset($method['imagen_url']) ? $method['imagen_url'] : '';
            
            $total_general += $monto;
            
            $nombre_metodo = isset($method_names[$metodo]) ? $method_names[$metodo] : ucfirst($metodo);
        ?>
            <div class="payment-method-item">
                <div class="payment-method-info">
                    <div class="payment-method-name">
                        <?php echo esc_html($nombre_metodo); ?>
                    </div>
                    <div class="payment-method-amount">
                        S/. <?php echo number_format($monto, 2); ?>
                    </div>
                </div>
                
                <?php if ($imagen_url): ?>
                    <div class="payment-method-image">
                        <a href="<?php echo esc_url($imagen_url); ?>" target="_blank">
                            <img src="<?php echo esc_url($imagen_url); ?>" alt="Comprobante">
                        </a>
                        <div style="text-align: center; margin-top: 5px; font-size: 11px; color: #666;">
                            📸 Clic para ampliar
                        </div>
                    </div>
                <?php else: ?>
                    <div class="payment-method-image" style="color: #999; font-style: italic;">
                        Sin comprobante
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="payment-total">
            TOTAL RECAUDADO: S/. <?php echo number_format($total_general, 2); ?>
        </div>
    </div>
    <?php
}

/**
 * Sistema de cuentas financieras MERCourier – reemplazo completo.
 *
 * Este archivo contiene una implementación completa del módulo financiero
 * utilizado por WPCargo/WPCFE en tu plataforma courier. Ha sido modificado
 * para desglosar correctamente los pagos según el método (efectivo, pago a
 * MERC, pago a MARCA y POS), calculando los totales pertinentes para
 * motorizados, administradores y clientes. También se mantienen todas las
 * funciones auxiliares necesarias para la creación de paneles, páginas,
 * filtros, badges en el sidebar y redirecciones según rol. Copia este
 * archivo en tu tema o plugin y reemplaza el bloque financiero original
 * para que surta efecto.
 *
 * Importante: este archivo asume que el resto de funcionalidades del
 * tema permanecen intactas. Asegúrate de no incluir el antiguo bloque
 * financiero simultáneamente, ya que ocasionaría redefiniciones de
 * funciones.
 */

// ---------------------------------------------------------------------------
// SISTEMA FINANCIERO - GUARDADO DE DATOS
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// HELPER: obtener totales por método de pago (MEJORADO)
// ---------------------------------------------------------------------------

if ( ! function_exists( 'get_payment_totals_by_method' ) ) {
    /**
     * Devuelve totales por método de pago, distinguiendo efectivo liquidado
     * 
     * @param int $shipment_id ID del envío
     * @param bool $solo_liquidado Si true, solo cuenta efectivo si está liquidado
     * @return array Totales por método
     */
    function get_payment_totals_by_method( $shipment_id, $solo_liquidado = false ) {
        $json   = get_post_meta( $shipment_id, 'pod_payment_methods', true );
        $totals = [
            'efectivo'   => 0.0,
            'pago_merc'  => 0.0,
            'pago_marca' => 0.0,
            'pos'        => 0.0,
            'total'      => 0.0,
        ];
        
        if ( ! empty( $json ) ) {
            $data = json_decode( $json, true );
            if ( is_array( $data ) ) {
                foreach ( $data as $item ) {
                    if ( isset( $item['metodo'], $item['monto'] ) ) {
                        $method = $item['metodo'];
                        $amount = (float) $item['monto'];
                        if ( array_key_exists( $method, $totals ) ) {
                            $totals[ $method ] += $amount;
                            $totals['total']   += $amount;
                        }
                    }
                }
            }
        }
        
        // Si solo queremos efectivo liquidado, verificamos el estado
        if ( $solo_liquidado && $totals['efectivo'] > 0 ) {
            $estado_motorizado = get_post_meta( $shipment_id, 'wpcargo_estado_pago_motorizado', true );
            if ( $estado_motorizado !== 'liquidado' ) {
                $totals['efectivo'] = 0.0;
            }
        }
        
        return $totals;
    }
}

if ( ! function_exists( 'get_recaudado_merc' ) ) {
    /**
     * Calcula el recaudado por MERC (solo pago_merc, sin pos)
     */
    function get_recaudado_merc( $shipment_id ) {
        $totales = get_payment_totals_by_method( $shipment_id );
        return $totales['pago_merc'];
    }
}

/**
 * Devuelve la URL del comprobante asociado a un envío (si existe)
 */
function merc_get_shipment_voucher_url( $shipment_id ) {
    // 1) Buscar en pod_payment_methods (imagen_url o imagen_id)
    $ppm = get_post_meta( $shipment_id, 'pod_payment_methods', true );
    if ( ! empty( $ppm ) ) {
        $methods = json_decode( $ppm, true );
        if ( is_array( $methods ) ) {
            foreach ( $methods as $m ) {
                if ( isset( $m['imagen_url'] ) && ! empty( $m['imagen_url'] ) ) {
                    return esc_url_raw( $m['imagen_url'] );
                }
                if ( isset( $m['imagen_id'] ) && intval( $m['imagen_id'] ) > 0 ) {
                    $u = wp_get_attachment_url( intval( $m['imagen_id'] ) );
                    if ( $u ) return $u;
                }
            }
        }
    }

    // 2) Buscar attachments con post_parent = shipment_id
    $attachments = get_posts( array(
        'post_type' => 'attachment',
        'post_parent' => $shipment_id,
        'posts_per_page' => 1,
        'post_mime_type' => 'image',
        'orderby' => 'date',
        'order' => 'DESC'
    ) );
    if ( ! empty( $attachments ) && ! empty( $attachments[0] ) ) {
        $url = wp_get_attachment_url( $attachments[0]->ID );
        if ( $url ) return $url;
    }

    // 3) Si el envío está vinculado a una liquidación, buscar attachment en esa liquidación
    $liq_id = get_post_meta( $shipment_id, 'wpcargo_included_in_liquidation', true );
    if ( ! empty( $liq_id ) ) {
        // Buscar entre todos los usuarios con merc_liquidations
        $users = get_users( array( 'meta_key' => 'merc_liquidations', 'number' => 0 ) );
        if ( ! empty( $users ) ) {
            foreach ( $users as $user ) {
                $history = get_user_meta( $user->ID, 'merc_liquidations', true );
                if ( is_array( $history ) ) {
                    foreach ( $history as $entry ) {
                        if ( isset( $entry['id'] ) && $entry['id'] == $liq_id ) {
                            if ( isset( $entry['attachment_id'] ) && intval( $entry['attachment_id'] ) > 0 ) {
                                $u = wp_get_attachment_url( intval( $entry['attachment_id'] ) );
                                if ( $u ) return $u;
                            }
                        }
                    }
                }
            }
        }
    }

    return false;
}

function merc_get_shipment_voucher_thumb_html( $shipment_id, $size = 60 ) {
    $url = merc_get_shipment_voucher_url( $shipment_id );
    if ( ! $url ) return '';
    $escaped = esc_url( $url );
    $html = '<a href="' . $escaped . '" target="_blank" style="display:inline-block">';
    $html .= '<img src="' . $escaped . '" alt="Comprobante" style="width:' . intval( $size ) . 'px;height:auto;max-height:' . intval( $size ) . 'px;border-radius:6px;border:2px solid #eee;">';
    $html .= '</a>';
    return $html;
}

/**
 * Buscar en los historiales de liquidaciones la entrada cuya action
 * coincida con $action y que incluya el envío $shipment_id.
 * Si existe y tiene attachment_id, devuelve la miniatura HTML; sino, cadena vacía.
 */
function merc_get_shipment_liquidation_action_image_html( $shipment_id, $action = 'cliente_pago_voucher', $size = 60 ) {
    if ( empty( $shipment_id ) ) return '';
    $action = strtolower( trim( $action ) );

    $users = get_users( array( 'meta_key' => 'merc_liquidations', 'number' => 0 ) );
    if ( empty( $users ) ) return '';

    foreach ( $users as $user ) {
        $history = get_user_meta( $user->ID, 'merc_liquidations', true );
        if ( ! is_array( $history ) ) continue;
        foreach ( $history as $entry ) {
            if ( empty( $entry['action'] ) ) continue;
            if ( strtolower( trim( $entry['action'] ) ) !== $action ) continue;
            $entry_shipments = isset( $entry['shipments'] ) && is_array( $entry['shipments'] ) ? $entry['shipments'] : array();
            if ( empty( $entry_shipments ) ) continue;
            if ( ! in_array( $shipment_id, $entry_shipments ) ) continue;
            if ( isset( $entry['attachment_id'] ) && intval( $entry['attachment_id'] ) > 0 ) {
                return wp_get_attachment_image( intval( $entry['attachment_id'] ), array( $size, $size ) );
            }
        }
    }

    return '';
}

/**
 * Comprueba si para un envío existe una imagen subida asociada a un método concreto
 * (busca en meta `pod_payment_methods` por el campo 'metodo' y 'imagen_url'|'imagen_id').
 */
function merc_shipment_method_has_image( $shipment_id, $metodo ) {
    if ( empty( $shipment_id ) || empty( $metodo ) ) return false;
    $ppm = get_post_meta( $shipment_id, 'pod_payment_methods', true );
    if ( empty( $ppm ) ) return false;
    $methods = json_decode( $ppm, true );
    if ( ! is_array( $methods ) ) return false;
    foreach ( $methods as $m ) {
        if ( ! isset( $m['metodo'] ) ) continue;
        if ( $m['metodo'] !== $metodo ) continue;
        if ( isset( $m['imagen_url'] ) && ! empty( $m['imagen_url'] ) ) return true;
        if ( isset( $m['imagen_id'] ) && intval( $m['imagen_id'] ) > 0 ) return true;
    }
    return false;
}

/**
 * Devuelve miniatura/enlace del comprobante específicamente asociado a un pago a MARCA.
 * Busca primero en `pod_payment_methods` por método 'pago_marca' con imagen,
 * si no existe, verifica si el envío está incluido en una liquidación cuyo action
 * contenga 'marca' y que tenga attachment_id.
 */
function merc_get_pago_marca_voucher_thumb_html( $shipment_id, $size = 24 ) {
    // 1) Buscar en pod_payment_methods
    $ppm = get_post_meta( $shipment_id, 'pod_payment_methods', true );
    if ( ! empty( $ppm ) ) {
        $methods = json_decode( $ppm, true );
        if ( is_array( $methods ) ) {
            foreach ( $methods as $m ) {
                if ( isset( $m['metodo'] ) && $m['metodo'] === 'pago_marca' ) {
                    if ( isset( $m['imagen_url'] ) && ! empty( $m['imagen_url'] ) ) {
                        $url = esc_url_raw( $m['imagen_url'] );
                        $escaped = esc_url( $url );
                        return '<a href="' . $escaped . '" target="_blank" style="margin-left:6px;display:inline-block;vertical-align:middle;"><img src="' . $escaped . '" alt="Comprobante marca" style="width:' . intval( $size ) . 'px;height:auto;max-height:' . intval( $size ) . 'px;border-radius:4px;border:1px solid #ddd;"></a>';
                    }
                    if ( isset( $m['imagen_id'] ) && intval( $m['imagen_id'] ) > 0 ) {
                        $u = wp_get_attachment_url( intval( $m['imagen_id'] ) );
                        if ( $u ) {
                            $escaped = esc_url( $u );
                            return '<a href="' . $escaped . '" target="_blank" style="margin-left:6px;display:inline-block;vertical-align:middle;"><img src="' . $escaped . '" alt="Comprobante marca" style="width:' . intval( $size ) . 'px;height:auto;max-height:' . intval( $size ) . 'px;border-radius:4px;border:1px solid #ddd;"></a>';
                        }
                    }
                }
            }
        }
    }

    // 2) Buscar en historiales de liquidaciones vinculadas (por si la marca fue pagada durante una liquidación)
    $liq_id = get_post_meta( $shipment_id, 'wpcargo_included_in_liquidation', true );
    if ( ! empty( $liq_id ) ) {
        $users = get_users( array( 'meta_key' => 'merc_liquidations', 'number' => 0 ) );
        if ( ! empty( $users ) ) {
            foreach ( $users as $user ) {
                $history = get_user_meta( $user->ID, 'merc_liquidations', true );
                if ( ! is_array( $history ) ) continue;
                foreach ( $history as $entry ) {
                    if ( isset( $entry['id'] ) && $entry['id'] == $liq_id ) {
                        $action = isset( $entry['action'] ) ? strtolower( $entry['action'] ) : '';
                        if ( strpos( $action, 'marca' ) !== false || strpos( $action, 'pagar_a_marca' ) !== false || strpos( $action, 'pago_marca' ) !== false ) {
                            if ( isset( $entry['attachment_id'] ) && intval( $entry['attachment_id'] ) > 0 ) {
                                $u = wp_get_attachment_url( intval( $entry['attachment_id'] ) );
                                if ( $u ) {
                                    $escaped = esc_url( $u );
                                    return '<a href="' . $escaped . '" target="_blank" style="margin-left:6px;display:inline-block;vertical-align:middle;"><img src="' . $escaped . '" alt="Comprobante marca" style="width:' . intval( $size ) . 'px;height:auto;max-height:' . intval( $size ) . 'px;border-radius:4px;border:1px solid #ddd;"></a>';
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return '';
}

/**
 * Comprueba si un envío está vinculado a una liquidación y esa liquidación fue verificada.
 */
function merc_is_shipment_liquidation_verified( $shipment_id ) {
    $liq_id = get_post_meta( $shipment_id, 'wpcargo_included_in_liquidation', true );
    if ( empty( $liq_id ) ) return false;

    $users = get_users( array( 'meta_key' => 'merc_liquidations', 'number' => 0 ) );
    if ( empty( $users ) ) return false;

    foreach ( $users as $user ) {
        $history = get_user_meta( $user->ID, 'merc_liquidations', true );
        if ( ! is_array( $history ) ) continue;
        foreach ( $history as $entry ) {
            if ( isset( $entry['id'] ) && $entry['id'] == $liq_id ) {
                return isset( $entry['verified'] ) && $entry['verified'];
            }
        }
    }

    return false;
}

// ---------------------------------------------------------------------------
// SISTEMA FINANCIERO - GUARDADO DE DATOS (sin cambios)
// ---------------------------------------------------------------------------

add_action( 'wpcargo_after_save_shipment', 'merc_save_financial_data', 20, 1 );
add_action( 'save_post_wpcargo_shipment', 'merc_save_financial_data', 20, 1 );
function merc_save_financial_data( $post_id ) {
    if ( get_post_type( $post_id ) !== 'wpcargo_shipment' ) {
        return;
    }

    error_log("💰 [SAVE_FINANCIAL] === GUARDANDO DATOS FINANCIEROS - Envío #{$post_id} ===");
    
    if ( isset( $_POST['wpcargo_costo_producto'] ) ) {
        $costo_producto = sanitize_text_field( $_POST['wpcargo_costo_producto'] );
        error_log("💰 [SAVE_FINANCIAL] Costo producto desde POST: {$costo_producto}");
        update_post_meta( $post_id, 'wpcargo_costo_producto', $costo_producto );
    }
    
    if ( isset( $_POST['wpcargo_costo_envio'] ) ) {
        $costo_envio = sanitize_text_field( $_POST['wpcargo_costo_envio'] );
        error_log("💰 [SAVE_FINANCIAL] Costo envío desde POST: {$costo_envio}");
        
        // Verificar el distrito para debugging
        $distrito = isset($_POST['wpcargo_distrito_destino']) ? sanitize_text_field($_POST['wpcargo_distrito_destino']) : 'N/A';
        error_log("💰 [SAVE_FINANCIAL] Distrito destino: {$distrito}");
        
        update_post_meta( $post_id, 'wpcargo_costo_envio', $costo_envio );
        error_log("💰 [SAVE_FINANCIAL] Costo envío GUARDADO en meta: {$costo_envio}");
    } else {
        error_log("⚠️ [SAVE_FINANCIAL] wpcargo_costo_envio NO está en POST");
    }
    
    if ( isset( $_POST['wpcargo_cargo_remitente'] ) ) {
        $cargo_remitente = sanitize_text_field( $_POST['wpcargo_cargo_remitente'] );
        error_log("💰 [SAVE_FINANCIAL] Cargo remitente desde POST: {$cargo_remitente}");
        update_post_meta( $post_id, 'wpcargo_cargo_remitente', $cargo_remitente );
    }
    
    error_log("💰 [SAVE_FINANCIAL] === FIN GUARDADO DATOS FINANCIEROS ===");
    
    // Verificar el valor inmediatamente después de guardar
    $costo_envio_verificado = get_post_meta($post_id, 'wpcargo_costo_envio', true);
    error_log("🔍 [SAVE_FINANCIAL] Verificación inmediata - Costo envío en DB: {$costo_envio_verificado}");

    $monto = get_post_meta( $post_id, 'wpcargo_monto', true );
    $monto = floatval( $monto );

    if ( $monto == 0 ) {
        update_post_meta( $post_id, 'wpcargo_quien_paga', 'remitente' );
        update_post_meta( $post_id, 'wpcargo_cobrado_por_motorizado', '0' );
    } else {
        // Preferimos que la marca (registered_shipper) asuma la responsabilidad
        // en lugar de marcar al cliente final como pagador.
        update_post_meta( $post_id, 'wpcargo_quien_paga', 'remitente' );
        update_post_meta( $post_id, 'wpcargo_cobrado_por_motorizado', $monto );
    }

    if ( ! get_post_meta( $post_id, 'wpcargo_estado_pago_motorizado', true ) ) {
        update_post_meta( $post_id, 'wpcargo_estado_pago_motorizado', 'pendiente' );
    }
    // El estado de liquidación del remitente ahora se maneja a nivel de usuario,
    // no se almacena por envío. No setear meta 'wpcargo_estado_pago_remitente' aquí.
    if ( ! get_post_meta( $post_id, 'wpcargo_cliente_pago_a', true ) ) {
        update_post_meta( $post_id, 'wpcargo_cliente_pago_a', 'pendiente' );
    }
}

// Hook adicional para verificar el valor FINAL después de todos los hooks
add_action('save_post_wpcargo_shipment', 'merc_verify_final_shipping_cost', 999999, 1);
function merc_verify_final_shipping_cost($post_id) {
    if (get_post_type($post_id) !== 'wpcargo_shipment') {
        return;
    }
    
    // Esperar un momento para que todos los hooks se ejecuten
    $costo_envio_final = get_post_meta($post_id, 'wpcargo_costo_envio', true);
    $distrito = get_post_meta($post_id, 'wpcargo_distrito_destino', true);
    $tipo_envio = get_post_meta($post_id, 'tipo_envio', true);
    
    error_log("🔚 [FINAL_VERIFICATION] Envío #{$post_id} | Tipo: {$tipo_envio} | Distrito: {$distrito} | Costo FINAL: {$costo_envio_final}");
}

// Hook para detectar cuando se EDITA un envío (no cuando se crea)
add_action('edit_post', 'merc_log_edit_shipping_cost', 10, 2);
function merc_log_edit_shipping_cost($post_id, $post) {
    if (get_post_type($post_id) !== 'wpcargo_shipment') {
        return;
    }
    
    // Verificar si es una edición (no una creación)
    if ($post->post_status === 'auto-draft') {
        return; // Es un nuevo post, no una edición
    }
    
    $costo_envio_antes = get_post_meta($post_id, 'wpcargo_costo_envio', true);
    $distrito = get_post_meta($post_id, 'wpcargo_distrito_destino', true);
    $tipo_envio = get_post_meta($post_id, 'tipo_envio', true);
    
    error_log("✏️ [EDIT_DETECTED] Envío #{$post_id} siendo editado | Tipo: {$tipo_envio} | Distrito: {$distrito} | Costo ANTES: {$costo_envio_antes}");
}

// ---------------------------------------------------------------------------
// PANEL MOTORIZADO - MEJORADO
// ---------------------------------------------------------------------------

add_shortcode( 'merc_panel_motorizado', 'merc_panel_motorizado_shortcode' );
function merc_panel_motorizado_shortcode() {
    $current_user = wp_get_current_user();
    if ( ! in_array( 'wpcargo_driver', $current_user->roles, true ) ) {
        return '<div class="alert alert-danger">⛔ Acceso denegado. Solo para motorizados.</div>';
    }
    ob_start();
    ?>
    <div class="merc-panel-motorizado">
        <div class="card mb-4">
            <div class="card-header" style="background: #3498db; color: white;">
                <h4 class="mb-0">🚗 Panel del Motorizado: <?php echo esc_html( $current_user->display_name ); ?></h4>
            </div>
            <div class="card-body">
                <?php merc_motorizado_resumen( $current_user->ID ); ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📦 Mis Entregas</h5>
            </div>
            <div class="card-body">
                <?php merc_motorizado_entregas( $current_user->ID ); ?>
            </div>
        </div>
    </div>
    <style>
        .merc-panel-motorizado .badge { font-size: 0.9em; padding: 0.5em 0.8em; }
        .merc-summary-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .merc-summary-box h3 { color: #1976D2; margin-bottom: 15px; }
        .merc-summary-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .merc-summary-item:last-child { border-bottom: none; }
        .merc-summary-value { font-weight: bold; color: #2c3e50; }
        .merc-entregas-table { width: 100%; border-collapse: collapse; }
        .merc-entregas-table th { background: #e9ecef; padding: 12px; text-align: left; border: 1px solid #dee2e6; }
        .merc-entregas-table td { padding: 12px; border: 1px solid #dee2e6; }
        .merc-entregas-table tfoot td { background: #d4edda; font-weight: bold; }
    </style>
    <?php
    return ob_get_clean();
}

// Helper: escribir logs diarios en `wp-content/merc_logs/merc-debug-YYYY-MM-DD.log`
if ( ! function_exists( 'merc_daily_log' ) ) {
    function merc_daily_log( $message ) {
        $dir = WP_CONTENT_DIR . '/merc_logs';
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        $file = $dir . '/merc-debug-' . date('Y-m-d') . '.log';
        $line = date('c') . ' ' . $message . PHP_EOL;
        // Usar file_put_contents con bloqueo para evitar concurrencia
        @file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
    }
}

/**
 * Obtener el monto POS neto para un envío: si existe el meta `wpcargo_pos`
 * (monto sin la comisión del 5%) se usa ese valor; si no, caerá al valor
 * calculado en `$totales['pos']` (si está disponible).
 */
function get_pos_net_for_shipment( $shipment_id, $totales = array() ) {
    // Devolver el monto POS "que llega" tal cual.
    // PRIORIDAD:
    // 1) Si existe meta wpcargo_pos (guardado por el sistema como total definitivo), usarla
    // 2) Si existe pod_payment_methods, sumar los montos de método 'pos'
    // 3) Fallback a totales['pos']
    
    // Intentar meta wpcargo_pos primero (es el total definitivo guardado)
    $meta_pos = floatval( get_post_meta( $shipment_id, 'wpcargo_pos', true ) );
    if ( $meta_pos > 0 ) {
        return round( $meta_pos, 2 );
    }
    
    // Si existe pod_payment_methods, sumar los montos de método 'pos'
    $ppm = get_post_meta( $shipment_id, 'pod_payment_methods', true );
    if ( ! empty( $ppm ) ) {
        $methods = json_decode( $ppm, true );
        if ( is_array( $methods ) ) {
            $sum = 0.0;
            foreach ( $methods as $m ) {
                if ( isset( $m['metodo'] ) && strtolower( $m['metodo'] ) === 'pos' ) {
                    $sum += floatval( isset( $m['monto'] ) ? $m['monto'] : 0 );
                }
            }
            if ( $sum > 0 ) {
                return round( $sum, 2 );
            }
        }
    }

    // Fallback a totales['pos'] si está presente
    if ( is_array( $totales ) && isset( $totales['pos'] ) ) {
        $pos = floatval( $totales['pos'] );
        if ( $pos > 0 ) return round( $pos, 2 );
    }

    return 0.0;
}

function merc_motorizado_resumen( $driver_id ) {
    global $wpdb;
    
    // Obtener fechas del filtro GET (por defecto hoy)
    $fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : date('Y-m-d');
    $fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : date('Y-m-d');
    
    error_log('=== MERC_MOTORIZADO_RESUMEN DEBUG ===');
    error_log('Conductor ID: ' . $driver_id);
    error_log('Fecha inicio: ' . $fecha_inicio);
    error_log('Fecha fin: ' . $fecha_fin);
    
    // Mostrar formulario de filtro
    ?>
    <div style="background:#f9f9f9;padding:15px;margin-bottom:20px;border-radius:6px;border:1px solid #ddd;">
        <h4 style="margin-top:0;margin-bottom:12px;">📅 Filtrar por fecha de envío</h4>
        <form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <div>
                <label for="fecha_inicio" style="display:block;margin-bottom:5px;font-weight:500;font-size:13px;">Fecha inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>" style="padding:8px;border:1px solid #ccc;border-radius:4px;">
            </div>
            <div>
                <label for="fecha_fin" style="display:block;margin-bottom:5px;font-weight:500;font-size:13px;">Fecha fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>" style="padding:8px;border:1px solid #ccc;border-radius:4px;">
            </div>
            <button type="submit" style="padding:8px 16px;background:#3498db;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:500;">
                🔍 Filtrar
            </button>
            <a href="<?php echo remove_query_arg(array('fecha_inicio', 'fecha_fin')); ?>" style="padding:8px 16px;background:#bdc3c7;color:#fff;border:none;border-radius:4px;cursor:pointer;text-decoration:none;font-weight:500;display:inline-block;">
                ✕ Hoy
            </a>
        </form>
    </div>
    <?php

    // Las fechas en meta_value están en DD/MM/YYYY, convertimos con STR_TO_DATE
    // IMPORTANTE: Escapar % con %% para que prepare() no las interprete como placeholders
    $query = "
        SELECT p.ID, pm_estado_motorizado.meta_value as estado
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_driver ON p.ID = pm_driver.post_id AND pm_driver.meta_key = 'wpcargo_driver'
        LEFT JOIN {$wpdb->postmeta} pm_estado_motorizado ON p.ID = pm_estado_motorizado.post_id AND pm_estado_motorizado.meta_key = 'wpcargo_estado_pago_motorizado'
        LEFT JOIN {$wpdb->postmeta} pm_pickup_date ON p.ID = pm_pickup_date.post_id AND pm_pickup_date.meta_key IN ('wpcargo_pickup_date_picker', 'wpcargo_pickup_date', 'wpcargo_fecha_envio')
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_driver.meta_value = %s
        AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') >= STR_TO_DATE('" . sanitize_text_field($fecha_inicio) . "', '%%Y-%%m-%%d')
        AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') <= STR_TO_DATE('" . sanitize_text_field($fecha_fin) . "', '%%Y-%%m-%%d')
    ";
    
    $query = $wpdb->prepare($query, $driver_id);
    error_log('📋 Query motorizado: ' . $query);
    
    $shipments = $wpdb->get_results($query);
    error_log('📦 Envíos para motorizado: ' . count($shipments ?: array()));

    $total_recaudado     = 0.0;
    $total_efectivo      = 0.0;
    $total_merc          = 0.0;
    $total_marca         = 0.0;
    $entregas_pendientes = 0;

    foreach ( $shipments as $shipment ) {
        $estado  = $shipment->estado ? $shipment->estado : 'pendiente';
        $totales = get_payment_totals_by_method( $shipment->ID );
        
        if ( $estado === 'pendiente' ) {
            $entregas_pendientes++;
        }
        
        $total_recaudado += $totales['total'];
        $total_efectivo  += $totales['efectivo'];
        $total_merc      += get_recaudado_merc( $shipment->ID );
        $total_marca     += $totales['pago_marca'];
    }
    ?>
    <div class="merc-summary-box">
        <h3>💰 Resumen Financiero</h3>
        <div class="merc-summary-item">
            <span>Total recaudado:</span>
            <span class="merc-summary-value" style="color: #2c3e50;">S/. <?php echo number_format( $total_recaudado, 2 ); ?></span>
        </div>
        <div class="merc-summary-item">
            <span style="color: #e74c3c;">Total por entregar a la administración (Efectivo pendiente):</span>
            <span class="merc-summary-value" style="color: #e74c3c;">S/. <?php echo number_format( $total_efectivo, 2 ); ?></span>
        </div>
        <div class="merc-summary-item">
            <span>Entregas pendientes de liquidación:</span>
            <span class="merc-summary-value"><?php echo $entregas_pendientes; ?></span>
        </div>
    </div>
    <?php
}

function merc_motorizado_entregas( $driver_id ) {
    global $wpdb;
    // Restringir a la fecha de envio (meta) — usar fecha de envío en vez de post_date
    $now = current_time('timestamp');
    $start_date = date('Y-m-d', $now);
    $end_date   = date('Y-m-d', $now);

    // Mostrar solo envíos donde este usuario está asignado COMO motorizado de ENTREGA
    $query = "
        SELECT p.ID, p.post_title,
               pm_estado_motorizado.meta_value as estado_motorizado,
             pm_status.meta_value as estado_envio,
             pm_tienda.meta_value as tienda_name,
             pm_shipper_name.meta_value as shipper_name,
               pm_destino.meta_value as destino,
               pm_pickup_date.meta_value as envio_fecha
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_me ON p.ID = pm_me.post_id AND pm_me.meta_key = 'wpcargo_motorizo_entrega'
        LEFT JOIN {$wpdb->postmeta} pm_estado_motorizado ON p.ID = pm_estado_motorizado.post_id AND pm_estado_motorizado.meta_key = 'wpcargo_estado_pago_motorizado'
         LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
         LEFT JOIN {$wpdb->postmeta} pm_tienda ON p.ID = pm_tienda.post_id AND pm_tienda.meta_key = 'wpcargo_tiendaname'
         LEFT JOIN {$wpdb->postmeta} pm_shipper_name ON p.ID = pm_shipper_name.post_id AND pm_shipper_name.meta_key = 'wpcargo_shipper_name'
        LEFT JOIN {$wpdb->postmeta} pm_destino ON p.ID = pm_destino.post_id AND pm_destino.meta_key = 'wpcargo_distrito_destino'
        LEFT JOIN {$wpdb->postmeta} pm_pickup_date ON p.ID = pm_pickup_date.post_id AND pm_pickup_date.meta_key IN ('wpcargo_pickup_date_picker','wpcargo_pickup_date','wpcargo_fecha_envio')
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_me.meta_value = %s
        AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') >= STR_TO_DATE('%s', '%%Y-%%m-%%d')
        AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') <= STR_TO_DATE('%s', '%%Y-%%m-%%d')
        ORDER BY p.post_date DESC
        LIMIT 50
    ";

    $shipments = $wpdb->get_results( $wpdb->prepare( $query, $driver_id, $start_date, $end_date ) );

    if ( empty( $shipments ) ) {
        echo '<div class="alert alert-warning">No tienes entregas asignadas.</div>';
        return;
    }

    // Inicializar totales
    $total_efectivo_sum = 0.0;
    $total_pago_merc_sum = 0.0;
    $total_pago_marca_sum = 0.0;
    $total_pos_sum = 0.0;
    $total_general = 0.0;

    ?>
    <table class="merc-entregas-table">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Destino</th>
                <th>Estado</th>
                <th>Marca</th>
                <th>Pago a Motorizado</th>
                <th>Pago a MERC</th>
                <th>Pago a MARCA</th>
                <th>POS</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $shipments as $shipment ) :
                $estado_motorizado = $shipment->estado_motorizado ? $shipment->estado_motorizado : 'pendiente';
                $totales           = get_payment_totals_by_method( $shipment->ID );
                // Mostrar POS: usar el monto que llega (sin distinción bruto/neto)
                $pos_display = get_pos_net_for_shipment( $shipment->ID, $totales );

                // Acumular totales
                $total_efectivo_sum += $totales['efectivo'];
                $total_pago_merc_sum += $totales['pago_merc'];
                $total_pago_marca_sum += $totales['pago_marca'];
                $total_pos_sum += $pos_display;
                $total_general += $totales['total'];
                ?>
                <tr>
                    <td><strong>#<?php echo esc_html( $shipment->post_title ); ?></strong></td>
                    <td><?php echo esc_html( $shipment->destino ); ?></td>
                    <td>S/. <?php echo number_format( $totales['efectivo'], 2 ); ?></td>
                    <td>S/. <?php echo number_format( $totales['pago_merc'], 2 ); ?></td>
                    <td>S/. <?php echo number_format( $totales['pago_marca'], 2 ); ?></td>
                    <td>S/. <?php echo number_format( $pos_display, 2 ); ?></td>
                    <td><strong>S/. <?php echo number_format( $totales['total'], 2 ); ?></strong></td>
                    
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;"><strong>TOTAL DEL DÍA:</strong></td>
                <td><strong>S/. <?php echo number_format( $total_efectivo_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_pago_merc_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_pago_marca_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_pos_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_general, 2 ); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <?php
}

// ---------------------------------------------------------------------------
// PANEL ADMINISTRADOR - MEJORADO
// ---------------------------------------------------------------------------

add_shortcode( 'merc_panel_admin', 'merc_panel_admin_shortcode' );
function merc_panel_admin_shortcode() {
    if ( ! current_user_can( 'administrator' ) ) {
        return '<div class="alert alert-danger">⛔ Acceso denegado. Solo para administradores.</div>';
    }
    
    // Usar fechas personalizadas (por defecto: hoy)
    $today = current_time('Y-m-d');
    $fecha_inicio      = isset( $_GET['fecha_inicio'] ) ? sanitize_text_field( $_GET['fecha_inicio'] ) : $today;
    $fecha_fin         = isset( $_GET['fecha_fin'] ) ? sanitize_text_field( $_GET['fecha_fin'] ) : $today;
    $filtro_estado     = isset( $_GET['filtro_estado'] ) ? sanitize_text_field( $_GET['filtro_estado'] ) : '';
    $filtro_motorizado = isset( $_GET['filtro_motorizado'] ) ? intval( $_GET['filtro_motorizado'] ) : 0;
    $filtro_cliente    = isset( $_GET['filtro_cliente'] ) ? intval( $_GET['filtro_cliente'] ) : 0;
    $vista_detalle     = isset( $_GET['vista_detalle'] ) ? sanitize_text_field( $_GET['vista_detalle'] ) : '';
    
    ob_start();
    ?>
    <div class="merc-panel-admin">
        <!-- Botón volver si estamos en vista detalle -->
        <?php if ($vista_detalle): ?>
        <div class="mb-3">
            <button class="btn btn-secondary merc-volver-dashboard">
                ← Volver al Dashboard
            </button>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header" style="background: #e74c3c; color: white;">
                <h4 class="mb-0">👑 Panel del Administrador</h4>
            </div>
            <div class="card-body">
                <?php 
                if ($vista_detalle) {
                    merc_admin_vista_detalle($vista_detalle, $fecha_inicio, $fecha_fin, $filtro_estado);
                } else {
                    merc_admin_resumen_general( $fecha_inicio, $fecha_fin, $filtro_estado ); 
                }
                ?>
            </div>
        </div>
        
        <?php if (!$vista_detalle): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">🔍 Filtros</h5>
            </div>
            <div class="card-body">
                <?php merc_admin_filtros( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_motorizado, $filtro_cliente ); ?>
            </div>
        </div>
        
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="motorizados-tab" data-toggle="tab" href="#motorizados" role="tab">
                    🚗 Motorizados
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="marcas-deudoras-tab" data-toggle="tab" href="#marcas-deudoras" role="tab">
                    🔴 Marcas deudoras
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="merc-debe-tab" data-toggle="tab" href="#merc-debe" role="tab">
                    🟢 Merc debe
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="liquidaciones-tab" data-toggle="tab" href="#liquidaciones" role="tab">
                    📜 Historial Liquidaciones
                </a>
            </li>
        </ul>
        <div class="tab-content" id="adminTabsContent">
            <div class="tab-pane fade show active" id="motorizados" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <?php merc_admin_motorizados( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_motorizado ); ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="marcas-deudoras" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <?php merc_admin_clientes( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_cliente, 'deudoras' ); ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="merc-debe" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <?php merc_admin_clientes( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_cliente, 'merc_debe' ); ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="liquidaciones" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <?php if ( current_user_can('administrator') ) { merc_admin_liquidaciones( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_cliente ); } else { echo do_shortcode('[merc_liquidations_history]'); } ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
        .merc-panel-admin .nav-tabs { margin-top: 20px; }
        .merc-panel-admin .tab-content { margin-top: -1px; }
        
        /* COLORES SÓLIDOS */
        .merc-stat-box { 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center; 
        }
        .merc-stat-box h2 { font-size: 2em; margin: 10px 0; }
        .merc-stat-box p { margin: 0; opacity: 0.9; }
        
        /* Filtros mejorados */
        .merc-filter-form { 
            display: flex !important; 
            gap: 15px !important; 
            flex-wrap: wrap !important; 
            align-items: end !important; 
        }
        .merc-filter-form .form-group { 
            flex: 1 !important; 
            min-width: 200px !important; 
        }
        .merc-filter-form label {
            display: block !important;
            margin-bottom: 5px !important;
            font-weight: bold !important;
        }
        .merc-filter-form select,
        .merc-filter-form button {
            display: block !important;
            width: 100% !important;
        }
        
        /* Botones de liquidación */
        .merc-btn-liquidar { 
            background: #27ae60; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-weight: bold;
        }
        .merc-btn-liquidar:hover { background: #229954; }
        .merc-btn-liquidar:disabled { background: #95a5a6; cursor: not-allowed; }
        
        .merc-btn-liquidar-todo {
            background: #e67e22;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        .merc-btn-liquidar-todo:hover { background: #d35400; }

        .merc-btn-cierre-caja {
            background: #34495e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        .merc-btn-cierre-caja:hover {
            background: #2c3e50;
        }
        .merc-estado-cierre {
            display: inline-block;
            width: 100%;
            padding: 5px 10px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            font-weight: 500;
        }
        
        /* Tarjetas de usuario */
        .merc-user-card { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border-left: 4px solid #3498db; 
        }
        .merc-user-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px; 
        }
        .merc-user-stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
        }
        .merc-stat-item { 
            background: white; 
            padding: 15px; 
            border-radius: 5px; 
            border: 1px solid #dee2e6;
        }
        .merc-stat-label { 
            font-size: 0.85em; 
            color: #7f8c8d; 
            margin-bottom: 5px; 
        }
        .merc-stat-value { 
            font-size: 1.3em; 
            font-weight: bold; 
            color: #2c3e50; 
        }
        
        /* Tablas */
        table.merc-entregas-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        table.merc-entregas-table th { 
            background: #e9ecef; 
            padding: 12px; 
            text-align: left; 
            border: 1px solid #dee2e6; 
        }
        table.merc-entregas-table td { 
            padding: 12px; 
            border: 1px solid #dee2e6; 
        }
        table.merc-entregas-table tfoot td { 
            background: #d4edda; 
            font-weight: bold; 
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('#adminTabs a').on('click', function (e) {
            e.preventDefault();
            var target = $(this).attr('href');
            $('#adminTabs .nav-link').removeClass('active');
            $(this).addClass('active');
            $('#adminTabsContent .tab-pane').removeClass('show active');
            $(target).addClass('show active');
        });
        
        // Liquidar pago individual
        $(document).on('click', '.merc-btn-liquidar', function(e) {
            e.preventDefault();
            var btn = $(this);
            var shipmentId = btn.data('shipment-id');
            var tipo = btn.data('tipo');
            
            Swal.fire({
                title: 'Confirmar liquidacion',
                text: 'Confirmar liquidacion de pago',
                icon: 'question',
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                showCancelButton: true,
                confirmButtonColor: '#3498db',
                cancelButtonColor: '#bdc3c7'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                btn.prop('disabled', true).text('Procesando...');
                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: {
                        action: 'merc_liquidar_pago',
                        shipment_id: shipmentId,
                        tipo: tipo,
                        nonce: '<?php echo wp_create_nonce( 'merc_liquidar_pago' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({icon:'success',title:'Exito',text:response.data.message,confirmButtonColor:'#27ae60'}).then(function(){location.reload();});
                        } else {
                            Swal.fire({icon:'error',title:'Error',text:response.data.message,confirmButtonColor:'#e74c3c'});
                            var textoOriginal = tipo === 'motorizado' ? 'Registrar Entrega' : 'Liquidar';
                            btn.prop('disabled', false).text(textoOriginal);
                        }
                    },
                    error: function() {
                        Swal.fire({icon:'error',title:'Error de conexion',text:'No se pudo conectar',confirmButtonColor:'#e74c3c'});
                        var textoOriginal = tipo === 'motorizado' ? 'Registrar Entrega' : 'Liquidar';
                        btn.prop('disabled', false).text(textoOriginal);
                    }
                });
            });
        });
        
        // Liquidar todo (masivo)
        $(document).on('click', '.merc-btn-liquidar-todo', function(e) {
            e.preventDefault();
            var btn = $(this);
            var userId = btn.data('user-id');
            var tipo = btn.data('tipo');
            var monto = btn.data('monto');

            if (tipo === 'remitente') {
                if ($('#merc-liquidacion-modal').length === 0) {
                    if (!document.getElementById('merc-liquidacion-styles')) {
                        var style = document.createElement('style');
                        style.id = 'merc-liquidacion-styles';
                        style.innerHTML = '@keyframes fadeIn {from {opacity:0;}to {opacity:1;}} @keyframes slideUp {from {transform:translateY(30px);opacity:0;}to {transform:translateY(0);opacity:1;}} #merc-liquidacion-modal h3 {color:#2c3e50;margin:0 0 12px 0;font-size:20px;font-weight:600;} #merc-liquidacion-modal p {color:#555;margin:0 0 20px 0;font-size:14px;line-height:1.5;} #merc-liquidacion-voucher {width:100%;padding:12px;border:2px dashed #3498db;border-radius:6px;margin-bottom:24px;cursor:pointer;font-size:14px;box-sizing:border-box;} #merc-liquidacion-modal .button {padding:10px 20px;border-radius:6px;font-weight:500;border:none;cursor:pointer;transition:all 0.3s ease;background:#ecf0f1;color:#2c3e50;} #merc-liquidacion-modal .button:hover {background:#bdc3c7;} #merc-liquidacion-modal .button-primary {background:linear-gradient(135deg,#3498db,#2980b9);color:#fff;}';
                        document.head.appendChild(style);
                    }
                    var modal = '<div id="merc-liquidacion-modal" style="position:fixed;left:0;top:0;width:100%;height:100%;z-index:100000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);animation:fadeIn 0.3s ease;"><div style="background:#fff;padding:32px;border-radius:12px;max-width:520px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideUp 0.3s ease;overflow:hidden;"><div style="background:#f8f9fa;padding:16px;border-radius:6px;margin-bottom:24px;border-left:4px solid #3498db;"><small style="color:#7f8c8d;font-weight:600;">Monto a liquidar</small><p style="margin:6px 0 0 0;font-size:18px;color:#2c3e50;font-weight:700;">S/. ' + monto + '</p></div><h3>Liquidacion diaria - Subir comprobante</h3><p>Adjunta imagen del comprobante.</p><input type="file" id="merc-liquidacion-voucher" accept="image/*" /><div style="display:flex;gap:8px;justify-content:flex-end;margin-top:32px;"><button id="merc-liquidacion-cancel" class="button">Cancelar</button><button id="merc-liquidacion-submit" class="button button-primary">Confirmar y Liquidar (S/. ' + monto + ')</button></div></div></div>';
                    $('body').append(modal);
                    $('#merc-liquidacion-cancel').on('click', function(){ $('#merc-liquidacion-modal').remove(); });
                    $('#merc-liquidacion-submit').on('click', function(){
                        var fileInput = $('#merc-liquidacion-voucher')[0];
                        if (!fileInput.files || fileInput.files.length === 0) {
                            Swal.fire({icon:'warning',title:'Archivo requerido',text:'Debes adjuntar imagen',confirmButtonColor:'#3498db'});
                            return;
                        }
                        btn.prop('disabled', true).text('Procesando...');
                        var fd = new FormData();
                        fd.append('action', 'merc_liquidar_todo');
                        fd.append('user_id', userId);
                        fd.append('tipo', tipo);

                        var fechaCaja = $('#merc-fecha-caja-input-' + userId).val();
                        if (!fechaCaja && typeof getTodayIso === 'function') {
                            fechaCaja = getTodayIso();
                        }
                        if (fechaCaja) {
                            fd.append('fecha_caja', fechaCaja);
                        }

                        fd.append('nonce', '<?php echo wp_create_nonce( 'merc_liquidar_todo' ); ?>');
                        fd.append('voucher', fileInput.files[0]);
                        $.ajax({
                            url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                            type: 'POST',
                            data: fd,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({icon:'success',title:'Exito',text:response.data.message,confirmButtonColor:'#27ae60'}).then(function(){location.reload();});
                                } else {
                                    Swal.fire({icon:'error',title:'Error',text:(response.data && response.data.message ? response.data.message : 'Error'),confirmButtonColor:'#e74c3c'});
                                    btn.prop('disabled', false).html((tipo === 'motorizado' ? '💵 ' : '💰 ') + 'Liquidar Todo (S/. ' + monto + ')');
                                    $('#merc-liquidacion-modal').remove();
                                }
                            },
                            error: function() {
                                Swal.fire({icon:'error',title:'Error de conexion',text:'No se pudo conectar',confirmButtonColor:'#e74c3c'});
                                btn.prop('disabled', false).html((tipo === 'motorizado' ? '💵 ' : '💰 ') + 'Liquidar Todo (S/. ' + monto + ')');
                                $('#merc-liquidacion-modal').remove();
                            }
                        });
                    });
                }
                return;
            }
            Swal.fire({
                title: 'Confirmar liquidacion',
                text: 'Confirmar liquidacion masiva de S/. ' + monto,
                icon: 'question',
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                showCancelButton: true,
                confirmButtonColor: '#3498db',
                cancelButtonColor: '#bdc3c7'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                btn.prop('disabled', true).text('Procesando...');
                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: {
                        action: 'merc_liquidar_todo',
                        user_id: userId,
                        tipo: tipo,
                        nonce: '<?php echo wp_create_nonce( 'merc_liquidar_todo' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({icon:'success',title:'Exito',text:response.data.message,confirmButtonColor:'#27ae60'}).then(function(){location.reload();});
                        } else {
                            Swal.fire({icon:'error',title:'Error',text:response.data.message,confirmButtonColor:'#e74c3c'});
                            var textoTodo = tipo === 'motorizado' ? 'Registrar Entrega de Efectivo' : 'Liquidar Todo';
                            btn.prop('disabled', false).html((tipo === 'motorizado' ? '💵 ' : '💰 ') + textoTodo + ' (S/. ' + monto + ')');
                        }
                    },
                    error: function() {
                        Swal.fire({icon:'error',title:'Error de conexion',text:'No se pudo conectar',confirmButtonColor:'#e74c3c'});
                        var textoTodo = tipo === 'motorizado' ? 'Registrar Entrega de Efectivo' : 'Liquidar Todo';
                        btn.prop('disabled', false).html((tipo === 'motorizado' ? '💵 ' : '💰 ') + textoTodo + ' (S/. ' + monto + ')');
                    }
                });
            });
        });

        const mercAjaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
        const mercCerrarCajaNonce = '<?php echo esc_js( wp_create_nonce( 'merc_cerrar_caja' ) ); ?>';
        const mercSessionUserId = <?php echo json_encode( get_current_user_id() ); ?>;

        function mercCajaKey(driverId) {
            return 'merc_caja_cerrada_' + String(mercSessionUserId || 0) + '_' + String(driverId || '').trim();
        }

        $(document).on('click', '.merc-btn-cierre-caja', function(e) {
            e.preventDefault();

            const btn = $(this);
            const $card = btn.closest('.merc-user-card');
            const driverId = String(
                btn.data('driver-id') ||
                $card.data('driver-id') ||
                ''
            ).trim();

            const cardId = String($card.attr('id') || '').trim();

            if (!driverId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo identificar el motorizado.'
                });
                return;
            }

            Swal.fire({
                title: 'Confirmar cierre de caja',
                text: 'Esto convertirá los combobox de estado en texto para este motorizado hasta el cierre del día.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Cerrar caja',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#34495e',
                cancelButtonColor: '#bdc3c7'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.post(mercAjaxUrl, {
                    action: 'merc_cerrar_caja',
                    nonce: mercCerrarCajaNonce,
                    driver_id: driverId
                }, function(response) {
                    if (!response || !response.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: (response && response.data && response.data.message) ? response.data.message : 'No se pudo cerrar la caja.'
                        });
                        return;
                    }

                    localStorage.setItem(mercCajaKey(driverId), '1');
                    window.dispatchEvent(new CustomEvent('merc-caja-cerrada', {
                        detail: {
                            driverId: driverId,
                            shipmentIds: [],
                            cardId: cardId,
                            sessionUserId: mercSessionUserId
                        }
                    }));

                    btn.prop('disabled', true).text('Caja cerrada');

                    Swal.fire({
                        icon: 'success',
                        title: 'Caja cerrada',
                        text: 'Caja cerrada para el motorizado seleccionado.'
                    });
                }, 'json').fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Falló la petición AJAX.'
                    });
                });
            });
        });
    });
    </script>
    
    <style>
        /* Botón ver voucher */
        .merc-btn-ver-voucher {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 18px;
            margin-left: 8px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .merc-btn-ver-voucher:hover {
            background: #e3f2fd;
            transform: scale(1.1);
        }
        
        /* Modal voucher */
        .merc-voucher-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            align-items: center;
            justify-content: center;
        }
        .merc-voucher-modal.active {
            display: flex;
        }
        .merc-voucher-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .merc-voucher-close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
            padding: 0;
            width: 32px;
            height: 32px;
            line-height: 32px;
            text-align: center;
        }
        .merc-voucher-close:hover {
            color: #333;
        }
        .merc-vouchers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .merc-voucher-item {
            position: relative;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .merc-voucher-item:hover {
            transform: scale(1.02);
            border-color: #27ae60;
        }
        .merc-voucher-image {
            width: 100%;
            display: block;
        }
        .merc-voucher-monto {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(39, 174, 96, 0.9);
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
        .merc-no-voucher {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
    
    <!-- Modal para ver voucher -->
    <div id="mercVoucherModal" class="merc-voucher-modal">
        <div class="merc-voucher-content">
            <button class="merc-voucher-close" onclick="cerrarVoucherModal()">&times;</button>
            <h3 id="mercVoucherTitulo">Ver Voucher</h3>
            <div id="mercVoucherContenido">
                <div class="merc-no-voucher">
                    <p>⏳ Cargando...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function cerrarVoucherModal() {
        document.getElementById('mercVoucherModal').classList.remove('active');
    }
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('mercVoucherModal').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarVoucherModal();
        }
    });
    
    jQuery(document).ready(function($) {
        $(document).on('click', '.merc-btn-ver-voucher', function() {
            var shipmentId = $(this).data('shipment-id');
            var tipo = $(this).data('tipo');
            
            $('#mercVoucherModal').addClass('active');
            $('#mercVoucherTitulo').text('Ver Voucher - ' + tipo.toUpperCase().replace('_', ' '));
            $('#mercVoucherContenido').html('<div class="merc-no-voucher"><p>⏳ Cargando...</p></div>');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'merc_get_voucher',
                    shipment_id: shipmentId,
                    tipo: tipo
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.vouchers && response.data.vouchers.length > 0) {
                            var html = '<div class="merc-vouchers-grid">';
                            response.data.vouchers.forEach(function(voucher, index) {
                                html += '<div class="merc-voucher-item">';
                                html += '<img src="' + voucher.url + '" class="merc-voucher-image" alt="Voucher ' + (index + 1) + '">';
                                if (voucher.monto > 0) {
                                    html += '<div class="merc-voucher-monto">S/. ' + parseFloat(voucher.monto).toFixed(2) + '</div>';
                                }
                                html += '</div>';
                            });
                            html += '</div>';
                            $('#mercVoucherContenido').html(html);
                        } else {
                            $('#mercVoucherContenido').html(
                                '<div class="merc-no-voucher">' +
                                '<p style="font-size: 48px;">📄</p>' +
                                '<p>No hay vouchers cargados para este pago</p>' +
                                '</div>'
                            );
                        }
                    } else {
                        $('#mercVoucherContenido').html(
                            '<div class="merc-no-voucher">' +
                            '<p style="font-size: 48px; color: #e74c3c;">❌</p>' +
                            '<p>' + response.data + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    $('#mercVoucherContenido').html(
                        '<div class="merc-no-voucher">' +
                        '<p style="font-size: 48px; color: #e74c3c;">❌</p>' +
                        '<p>Error al cargar el voucher</p>' +
                        '</div>'
                    );
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function merc_admin_resumen_general( $fecha_inicio, $fecha_fin, $filtro_estado ) {
    global $wpdb;

    // DEBUG: Ver qué fechas llegan a la función
    
    // Construir filtro de fecha usando SOLO wpcargo_pickup_date_picker (sin OR con post_date)
    $date_query = '';
    if ( ! empty( $fecha_inicio ) && ! empty( $fecha_fin ) ) {
        $fecha_inicio_ymd = date('Y-m-d', strtotime($fecha_inicio));
        $fecha_fin_ymd = date('Y-m-d', strtotime($fecha_fin));
        $date_query = "AND EXISTS(
            SELECT 1 FROM {$wpdb->postmeta} pm_pickup 
            WHERE pm_pickup.post_id = p.ID 
            AND pm_pickup.meta_key = 'wpcargo_pickup_date_picker'
            AND (
                STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') >= STR_TO_DATE('{$fecha_inicio_ymd}', '%Y-%m-%d')
                AND STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') <= STR_TO_DATE('{$fecha_fin_ymd}', '%Y-%m-%d')
            )
        )";
    }
    
    $extra_join = '';
    $extra_where = '';
    // Para la vista ingresos_envios, usar la misma lógica que las tarjetas de clientes:
    // remitente + no incluido en liquidación.
    if ( $tipo_vista === 'ingresos_envios' ) {
        $extra_where = "\n        AND pm_quien_paga.meta_value = 'remitente'\n        AND (pm_included.meta_value IS NULL OR pm_included.meta_value = '')";
    }
    
    $shipments = $wpdb->get_results( "
        SELECT
            p.ID,
            pm_envio.meta_value as costo_envio,
            pm_producto.meta_value as costo_producto,
            pm_estado_motorizado.meta_value as estado_motorizado,
            pm_included.meta_value as estado_remitente,
            pm_quien_paga.meta_value as quien_paga,
            pm_cliente_pago_a.meta_value as cliente_pago_a,
            pm_shipper.meta_value as shipper_id,
            pm_status.meta_value as wpcargo_status
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_envio ON p.ID = pm_envio.post_id AND pm_envio.meta_key = 'wpcargo_costo_envio'
        LEFT JOIN {$wpdb->postmeta} pm_producto ON p.ID = pm_producto.post_id AND pm_producto.meta_key = 'wpcargo_costo_producto'
        LEFT JOIN {$wpdb->postmeta} pm_estado_motorizado ON p.ID = pm_estado_motorizado.post_id AND pm_estado_motorizado.meta_key = 'wpcargo_estado_pago_motorizado'
        LEFT JOIN {$wpdb->postmeta} pm_included ON p.ID = pm_included.post_id AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
        LEFT JOIN {$wpdb->postmeta} pm_quien_paga ON p.ID = pm_quien_paga.post_id AND pm_quien_paga.meta_key = 'wpcargo_quien_paga'
        LEFT JOIN {$wpdb->postmeta} pm_cliente_pago_a ON p.ID = pm_cliente_pago_a.post_id AND pm_cliente_pago_a.meta_key = 'wpcargo_cliente_pago_a'
        LEFT JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        $date_query
    " );

    // Debug: registrar fecha usada y número de envíos obtenidos
    error_log(sprintf('MERC_ADMIN_RESUMEN - date_query="%s"', $date_query));
    if ( ! is_array( $shipments ) || empty( $shipments ) ) {
        error_log('MERC_ADMIN_RESUMEN - NO SHIPMENTS returned for date_query, attempting fallback by pickup meta');
        // Fallback: buscar envíos cuya meta de pickup (varias claves y formatos) coincida con hoy
        $today_ymd = current_time('Y-m-d');
        $meta_keys = array('wpcargo_pickup_date_picker','wpcargo_pickup_date','calendarenvio','wpcargo_fecha_envio');
        $meta_keys_list = "'" . implode("','", $meta_keys) . "'";
        $like_ymd = $wpdb->esc_like( $today_ymd );

        // Construir la consulta para buscar fechas en formato YYYY-MM-DD (que es lo que MERC_Date_Fixer guarda)
        $sql = "SELECT p.ID, pm_envio.meta_value as costo_envio, pm_producto.meta_value as costo_producto, pm_estado_motorizado.meta_value as estado_motorizado, pm_included.meta_value as estado_remitente, pm_quien_paga.meta_value as quien_paga, pm_cliente_pago_a.meta_value as cliente_pago_a, pm_shipper.meta_value as shipper_id, pm_status.meta_value as wpcargo_status
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_pickup ON p.ID = pm_pickup.post_id AND pm_pickup.meta_key IN ($meta_keys_list)
            LEFT JOIN {$wpdb->postmeta} pm_envio ON p.ID = pm_envio.post_id AND pm_envio.meta_key = 'wpcargo_costo_envio'
            LEFT JOIN {$wpdb->postmeta} pm_producto ON p.ID = pm_producto.post_id AND pm_producto.meta_key = 'wpcargo_costo_producto'
            LEFT JOIN {$wpdb->postmeta} pm_estado_motorizado ON p.ID = pm_estado_motorizado.post_id AND pm_estado_motorizado.meta_key = 'wpcargo_estado_pago_motorizado'
            LEFT JOIN {$wpdb->postmeta} pm_included ON p.ID = pm_included.post_id AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
            LEFT JOIN {$wpdb->postmeta} pm_quien_paga ON p.ID = pm_quien_paga.post_id AND pm_quien_paga.meta_key = 'wpcargo_quien_paga'
            LEFT JOIN {$wpdb->postmeta} pm_cliente_pago_a ON p.ID = pm_cliente_pago_a.post_id AND pm_cliente_pago_a.meta_key = 'wpcargo_cliente_pago_a'
            LEFT JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
            WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish' AND (pm_pickup.meta_value = '" . $like_ymd . "' OR pm_pickup.meta_value LIKE '" . $like_ymd . "%')";

        $shipments = $wpdb->get_results( $sql );
        if ( ! is_array( $shipments ) || empty( $shipments ) ) {
            error_log('MERC_ADMIN_RESUMEN - fallback also returned NO shipments; attempting fallback by post_date range');
            // Listar últimas 20 entradas de envíos y sus metas relacionadas a pickup para identificar formatos
            $recent = $wpdb->get_results("SELECT p.ID, p.post_date FROM {$wpdb->posts} p WHERE p.post_type = 'wpcargo_shipment' ORDER BY p.post_date DESC LIMIT 20");
            if ( is_array($recent) && ! empty($recent) ) {
                foreach ( $recent as $r ) {
                    $meta_vals = array();
                    foreach ( $meta_keys as $mk ) {
                        $meta_vals[ $mk ] = get_post_meta( $r->ID, $mk, true );
                    }
                    // También incluir posibles metas alternativas
                    $meta_vals['post_date'] = $r->post_date;
                    error_log(sprintf('MERC_ADMIN_RESUMEN_SAMPLE - id=%d post_date=%s metas=%s', $r->ID, $r->post_date, json_encode($meta_vals)));
                }
            } else {
                error_log('MERC_ADMIN_RESUMEN_SAMPLE - no recent shipments found to sample');
            }

            // Intentar buscar por post_date entre las fechas (siempre que $fecha_inicio/$fecha_fin existan)
            $start_date = ! empty( $fecha_inicio ) ? $fecha_inicio : current_time('Y-m-d');
            $end_date   = ! empty( $fecha_fin ) ? $fecha_fin : current_time('Y-m-d');
            $start_dt = $start_date . ' 00:00:00';
            $end_dt   = $end_date . ' 23:59:59';

            $sql_post_date = $wpdb->prepare(
                "SELECT p.ID, pm_envio.meta_value as costo_envio, pm_producto.meta_value as costo_producto, pm_estado_motorizado.meta_value as estado_motorizado, pm_included.meta_value as estado_remitente, pm_quien_paga.meta_value as quien_paga, pm_cliente_pago_a.meta_value as cliente_pago_a, pm_shipper.meta_value as shipper_id, pm_status.meta_value as wpcargo_status
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm_envio ON p.ID = pm_envio.post_id AND pm_envio.meta_key = 'wpcargo_costo_envio'
                LEFT JOIN {$wpdb->postmeta} pm_producto ON p.ID = pm_producto.post_id AND pm_producto.meta_key = 'wpcargo_costo_producto'
                LEFT JOIN {$wpdb->postmeta} pm_estado_motorizado ON p.ID = pm_estado_motorizado.post_id AND pm_estado_motorizado.meta_key = 'wpcargo_estado_pago_motorizado'
                LEFT JOIN {$wpdb->postmeta} pm_included ON p.ID = pm_included.post_id AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
                LEFT JOIN {$wpdb->postmeta} pm_quien_paga ON p.ID = pm_quien_paga.post_id AND pm_quien_paga.meta_key = 'wpcargo_quien_paga'
                LEFT JOIN {$wpdb->postmeta} pm_cliente_pago_a ON p.ID = pm_cliente_pago_a.post_id AND pm_cliente_pago_a.meta_key = 'wpcargo_cliente_pago_a'
                LEFT JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
                LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
                WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish' AND p.post_date BETWEEN %s AND %s",
                $start_dt, $end_dt
            );
            error_log('MERC_ADMIN_RESUMEN - trying post_date fallback with start=' . $start_dt . ' end=' . $end_dt);
            $shipments = $wpdb->get_results( $sql_post_date );
            if ( ! is_array( $shipments ) || empty( $shipments ) ) {
                echo '<div class="alert alert-info">No hay envíos para el rango de fechas seleccionado.</div>';
                return;
            }
        }
        error_log(sprintf('MERC_ADMIN_RESUMEN - fallback shipments_count=%d', count($shipments)));
    }
    error_log(sprintf('MERC_ADMIN_RESUMEN - shipments_count=%d', count($shipments)));

    $ingresos_envios  = 0.0;
    $efectivo_total   = 0.0;
    $pago_merc_total  = 0.0; // nuevo: acumular PAGO_MERC de todos los shipments
    $recaudado_merc   = 0.0;
    $recaudado_marca  = 0.0;
    $por_cobrar_mot   = 0.0;
    $por_pagar_rem    = 0.0;
    $pos_recaudado    = 0.0; // nuevo: recaudado por POS (para mostrar en panel)
    $total_entregados = 0.0; // total de envíos ENTREGADOS (para Total General)
    $wpcargo_monto_total = 0.0; // total historic of shipment amount
    $has_remitente_liquidated = false; // detect if any shipment was liquidated for remitente

    error_log('═══════════════════════════════════════════════════════════');
    error_log('🔍 MERC_ADMIN_DEBUG - INICIANDO CÁLCULOS DEL PANEL');
    error_log('═══════════════════════════════════════════════════════════');

    foreach ( $shipments as $shipment ) {
        $envio = floatval( $shipment->costo_envio );
        $producto = floatval( $shipment->costo_producto );
        $estado_motorizado = $shipment->estado_motorizado;
        $estado_remitente  = $shipment->estado_remitente;
        $quien_paga        = $shipment->quien_paga;

        // DEBUG: mostrar datos del shipment
        error_log('');
        error_log('────────────────────────────────────────────────────────');
        error_log(sprintf('📦 SHIPMENT #%d', $shipment->ID));
        error_log(sprintf('   Costo Envío: S/. %01.2f', $envio));
        error_log(sprintf('   Costo Producto: S/. %01.2f', $producto));
        error_log(sprintf('   Estado Motorizado: %s', $estado_motorizado ?: 'VACÍO'));
        error_log(sprintf('   Estado Remitente (liquidado): %s', $estado_remitente ?: 'VACÍO'));
        error_log(sprintf('   Quién Paga: %s', $quien_paga ?: 'VACÍO'));

        // Estado de entrega
        $wpcargo_status = isset($shipment->wpcargo_status) ? $shipment->wpcargo_status : '';
        error_log(sprintf('   Estado Entrega: %s', $wpcargo_status ?: 'VACÍO'));

        // Total General: sumar SOLO los costos de producto de envíos ENTREGADOS (sin importar liquidación)
        if ( $wpcargo_status === 'ENTREGADO' ) {
            $total_entregados += $producto;
            error_log(sprintf('   ✅ SUMADO A TOTAL ENTREGADOS: S/. %01.2f (producto=%.2f, Total: S/. %01.2f)', $producto, $producto, $total_entregados));
        } else {
            error_log(sprintf('   ❌ NO ENTREGADO aún (estado: %s)', $wpcargo_status ?: 'sin estado'));
        }

        // Calcular monto histórico del envío según wpcargo_monto o sus alternativas.
        $monto_meta = floatval( get_post_meta( $shipment->ID, 'wpcargo_monto', true ) );
        if ( $monto_meta <= 0 ) {
            $monto_meta = floatval( get_post_meta( $shipment->ID, 'wpcargo_total_cobrar', true ) );
        }
        if ( $monto_meta <= 0 ) {
            $monto_meta = $envio + $producto;
        }
        $wpcargo_monto_total += $monto_meta;
        if ( ! $has_remitente_liquidated ) {
            $has_remitente_liquidated = ! empty( get_post_meta( $shipment->ID, 'merc_remitente_liquidated', true ) );
        }

        if ( $filtro_estado === 'pendiente' && $estado_motorizado !== 'pendiente' ) {
            error_log('   ⏭️ SALTADO - Filtro pendiente activo y estado ≠ pendiente');
            continue;
        }
        if ( $filtro_estado === 'liquidado' && $estado_motorizado !== 'liquidado' ) {
            error_log('   ⏭️ SALTADO - Filtro liquidado activo y estado ≠ liquidado');
            continue;
        }

        // Ingresos por envío: sumar el SERVICIO de los envíos del remitente
        // sin depender del botón de liquidación.
        // Replica la lógica de "Por Pagar (Envíos)" de las tarjetas de clientes.
        if ( $quien_paga === 'remitente' && $envio > 0 ) {
            $ingresos_envios += $envio;
            error_log(sprintf(
                '   ✅ SUMADO A INGRESOS ENVÍOS: S/. %01.2f (Total: S/. %01.2f)',
                $envio,
                $ingresos_envios
            ));
        } else {
            error_log(sprintf(
                '   ❌ NO SUMADO a ingresos envíos (quien_paga=%s, estado_remitente=%s)',
                $quien_paga ?: 'VACÍO',
                $estado_remitente ?: 'VACÍO'
            ));
        }

        // Mantener cálculo histórico: por cobrar de motorizados (para balance neto)
        if ( $estado_motorizado === 'pendiente' || empty( $estado_motorizado ) ) {
            $totales = get_payment_totals_by_method( $shipment->ID );
            $por_cobrar_mot += $totales['total'];
        }

        // Acumular por cliente para calcular balances netos
        $shipper_id = isset($shipment->shipper_id) ? $shipment->shipper_id : '';
        if ( empty( $shipper_id ) ) {
            error_log('   ⚠️ Sin remitente (shipper_id vacío)');
        } else {
            if ( ! isset( $client_balances ) ) $client_balances = array();
            if ( ! isset( $client_balances[ $shipper_id ] ) ) {
                $client_balances[ $shipper_id ] = array('pago_merc'=>0.0, 'efectivo'=>0.0, 'pos'=>0.0, 'pago_marca'=>0.0, 'servicio'=>0.0);
            }
            $is_verified = merc_is_shipment_liquidation_verified( $shipment->ID );
            $totales = get_payment_totals_by_method( $shipment->ID );
            error_log(sprintf('   Cliente %s - ¿Liquidación verificada? %s', $shipper_id, $is_verified ? 'SÍ' : 'NO'));
            if ( ! $is_verified ) {
                // pago a MERC (solo el campo 'pago_merc' del shipment, sin incluir POS)
                $client_balances[ $shipper_id ]['pago_merc'] += floatval( $totales['pago_merc'] );
                // pago a motorizado (MOTO - efectivo)
                $client_balances[ $shipper_id ]['efectivo'] += floatval( $totales['efectivo'] );
                // POS neto
                $pos_display = get_pos_net_for_shipment( $shipment->ID, $totales );
                $client_balances[ $shipper_id ]['pos'] += $pos_display;
                // pago a marca
                $client_balances[ $shipper_id ]['pago_marca'] += floatval( $totales['pago_marca'] );
                // servicio (costo envío)
                $client_balances[ $shipper_id ]['servicio'] += $envio;
                error_log(sprintf('   TOTALES ACUMULADOS: pago_merc=S/. %01.2f, pos=S/. %01.2f, pago_marca=S/. %01.2f, servicio=S/. %01.2f',
                    $client_balances[ $shipper_id ]['pago_merc'],
                    $client_balances[ $shipper_id ]['pos'],
                    $client_balances[ $shipper_id ]['pago_marca'],
                    $client_balances[ $shipper_id ]['servicio']
                ));
            }
        }

        // Totales por métodos
        $totales = get_payment_totals_by_method( $shipment->ID );
        error_log(sprintf('   GET_PAYMENT_TOTALS: efectivo=S/. %01.2f, pago_merc=S/. %01.2f, pos=S/. %01.2f, pago_marca=S/. %01.2f, total=S/. %01.2f',
            $totales['efectivo'],
            $totales['pago_merc'],
            isset($totales['pos']) ? $totales['pos'] : 0.0,
            $totales['pago_marca'],
            $totales['total']
        ));
        
        $efectivo_total  += $totales['efectivo'];
        $pago_merc_total += floatval( $totales['pago_merc'] );
        error_log(sprintf('   Efectivo Total acumulado: S/. %01.2f', $efectivo_total));
        
        // Recaudado por MERC y por MARCA: sólo contar envíos NO liquidados aún
        $recaudado_merc_shipment = get_recaudado_merc( $shipment->ID );
        $recaudado_merc += $recaudado_merc_shipment;
        $recaudado_marca += $totales['pago_marca'];
        error_log(sprintf('   ✅ SUMADO A RECAUDADO_MERC: S/. %01.2f (Total: S/. %01.2f)', $recaudado_merc_shipment, $recaudado_merc));
        error_log(sprintf('   ✅ SUMADO A RECAUDADO_MARCA: S/. %01.2f (Total: S/. %01.2f)', $totales['pago_marca'], $recaudado_marca));

        // Recaudado por POS (nuevo): sumar POS de envíos no liquidados
        $pos_display = get_pos_net_for_shipment( $shipment->ID, $totales );
        $pos_recaudado += $pos_display;
        error_log(sprintf('   ✅ SUMADO A POS_RECAUDADO: S/. %01.2f (Total: S/. %01.2f)', $pos_display, $pos_recaudado));
    }

    // Calcular "Por pagar remitentes" usando la fórmula TOTAL GENERAL:
    // RECAUDADO_MER + EFECTIVO_TOTAL + POS_RECAUDADO - RECAUDADO_MARCA - INGRESOS_ENVIOS
    $por_pagar_rem = floatval($recaudado_merc) + floatval($efectivo_total) + floatval($pos_recaudado) - floatval($recaudado_marca) - floatval($ingresos_envios);
    
    error_log('');
    error_log('═══════════════════════════════════════════════════════════');
    error_log('🔍 CÁLCULO DE "POR PAGAR REMITENTES" - FÓRMULA TOTAL GENERAL');
    error_log('═══════════════════════════════════════════════════════════');
    error_log(sprintf('RECAUDADO_MER (MER): S/. %01.2f', floatval($recaudado_merc)));
    error_log(sprintf('EFECTIVO_TOTAL (MOTO): S/. %01.2f', floatval($efectivo_total)));
    error_log(sprintf('POS_RECAUDADO (POS): S/. %01.2f', floatval($pos_recaudado)));
    error_log(sprintf('RECAUDADO_MARCA (MARCA): S/. %01.2f', floatval($recaudado_marca)));
    error_log(sprintf('INGRESOS_ENVIOS (ENVIOS): S/. %01.2f', floatval($ingresos_envios)));
    error_log(sprintf('─────────────────────────────────────────────────────'));
    error_log(sprintf('por_pagar_rem = MER + MOTO + POS - MARCA - ENVIOS'));
    error_log(sprintf('por_pagar_rem = (%.2f + %.2f + %.2f) - %.2f - %.2f',
        floatval($recaudado_merc),
        floatval($efectivo_total),
        floatval($pos_recaudado),
        floatval($recaudado_marca),
        floatval($ingresos_envios)
    ));
    error_log(sprintf('por_pagar_rem = S/. %01.2f', $por_pagar_rem));

    // Nuevo: Total General = suma de todos los envíos del día + suma de liquidaciones por penalidades del día
    $penalties_sum = 0.0;
    $today_meta = current_time('Y-m-d');
    $pen_q = new WP_Query(array(
        'post_type' => 'merc_penalty',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => 'date', 'value' => $today_meta),
        ),
        'fields' => 'ids',
    ));
    if ( $pen_q->have_posts() ) {
        foreach ( $pen_q->posts as $pid ) {
            $amt = floatval( get_post_meta($pid, 'amount', true) );
            $penalties_sum += $amt;
        }
    }

    if ( $has_remitente_liquidated ) {
        // Después de la liquidación: restar todos los montos ya recaudados.
        $balance_neto = floatval($wpcargo_monto_total) - floatval($recaudado_marca) - floatval($pos_recaudado) - floatval($recaudado_merc) - floatval($efectivo_total);
    } else {
        // Antes de la liquidación: usar el monto de envío menos lo recaudado por marca.
        $balance_neto = floatval($wpcargo_monto_total) - floatval($recaudado_marca);
    }
    
    // DEBUG FINAL: resumen de todos los cálculos
    error_log('');
    error_log('═══════════════════════════════════════════════════════════');
    error_log('📊 RESUMEN FINAL DE CÁLCULOS DEL PANEL ADMIN');
    error_log('═══════════════════════════════════════════════════════════');
    error_log(sprintf('Total Entregados: S/. %01.2f (TODOS los envíos entregados)', $total_entregados));
    error_log(sprintf('Ingresos por Envíos: S/. %01.2f (solo liquidados)', $ingresos_envios));
    error_log(sprintf('Efectivo Total: S/. %01.2f', $efectivo_total));
    error_log(sprintf('Recaudado por MERC: S/. %01.2f', $recaudado_merc));
    error_log(sprintf('Recaudado por MARCA: S/. %01.2f', $recaudado_marca));
    error_log(sprintf('Recaudado por POS: S/. %01.2f', $pos_recaudado));
    error_log(sprintf('Por Pagar Remitentes: S/. %01.2f', $por_pagar_rem));
    error_log(sprintf('Penalidades del día: S/. %01.2f', $penalties_sum));
    error_log(sprintf('TOTAL GENERAL (Balance Neto): S/. %01.2f', $balance_neto));
    error_log('═══════════════════════════════════════════════════════════');
    error_log('');

    ?>
    <script>console.log('🔍 merc_admin_resumen_general renderizado - balance_neto: <?php echo $balance_neto; ?>');</script>
    <div class="row">
		<!-- Total General -->
		<div class="col-md-3">
			<div class="merc-stat-box" style="background: #2ecc71;">
				<p>Total General</p>
				<h2>S/. <?php echo number_format( $balance_neto, 2 ); ?></h2>
				<small style="opacity: 0.8;">Balance General</small>
			</div>
		</div>

		<!-- Motorizado -->
		<div class="col-md-3">
			<div class="merc-stat-box merc-stat-clickable" data-vista="efectivo_recaudado" style="background: #e67e22; cursor: pointer;">
				<p>Recaudado por Motorizado</p>
				<h2>S/. <?php echo number_format( $efectivo_total, 2 ); ?></h2>
				<small style="opacity: 0.8;">Click para ver detalles →</small>
			</div>
		</div>

		<!-- MERC -->
		<div class="col-md-3">
			<div class="merc-stat-box merc-stat-clickable" data-vista="recaudado_merc" style="background: #3498db; cursor: pointer;">
				<p>Recaudado por MERC</p>
				<h2>S/. <?php echo number_format( $recaudado_merc, 2 ); ?></h2>
				<small style="opacity: 0.8;">Click para ver detalles →</small>
			</div>
		</div>

		<!-- MARCA -->
		<div class="col-md-3">
			<div class="merc-stat-box merc-stat-clickable" data-vista="recaudado_marca" style="background: #27ae60; cursor: pointer;">
				<p>Recaudado por MARCA</p>
				<h2>S/. <?php echo number_format( $recaudado_marca, 2 ); ?></h2>
				<small style="opacity: 0.8;">Click para ver detalles →</small>
			</div>
		</div>
	</div>

	<div class="row" style="margin-top: 20px;">
		<!-- POS -->
		<div class="col-md-4">
			<div class="merc-stat-box merc-stat-clickable" data-vista="pos_recaudado" style="background: #e74c3c; cursor: pointer;">
				<p>Recaudado por POS</p>
				<h2>S/. <?php echo number_format( $pos_recaudado, 2 ); ?></h2>
				<small style="opacity: 0.8;">Click para ver detalles →</small>
			</div>
		</div>

		<!-- Ingresos Envíos -->
		<div class="col-md-4">
			<div class="merc-stat-box merc-stat-clickable" data-vista="ingresos_envios" style="background: #9b59b6; cursor: pointer;">
				<p>Ingresos por Envíos</p>
				<h2>S/. <?php echo number_format( $ingresos_envios, 2 ); ?></h2>
				<small style="opacity: 0.8;">Click para ver detalles →</small>
			</div>
		</div>

		<!-- Pagar Remitentes -->
        <div class="col-md-4">
            <div class="merc-stat-box merc-stat-clickable" data-vista="por_pagar_remitentes" style="background: #16a085; cursor: pointer;">
				<p>Por pagar remitentes</p>
                <h2>
                    S/. <?php 
                    // Mostrar la suma de los montos positivos por cliente
                    // (PAGO_A_MERC + POS - PAGO_A_MARCA - SERVICIO) agregados solo si > 0
                    echo number_format( isset($por_pagar_rem) ? $por_pagar_rem : 0.0, 2 ); 
                    ?>
                </h2>
				<small style="opacity: 0.8;">Click para ver detalles →</small>
			</div>
		</div>
	</div>
    <?php
}

// Nueva función: Vista detallada de cada métrica
function merc_admin_vista_detalle($tipo_vista, $fecha_inicio, $fecha_fin, $filtro_estado) {
    global $wpdb;
    
    // Construir filtro de fecha usando SOLO wpcargo_pickup_date_picker (sin OR con post_date)
    $date_query = '';
    if ( ! empty( $fecha_inicio ) && ! empty( $fecha_fin ) ) {
        $fecha_inicio_ymd = date('Y-m-d', strtotime($fecha_inicio));
        $fecha_fin_ymd = date('Y-m-d', strtotime($fecha_fin));
        $date_query = "AND EXISTS(
            SELECT 1 FROM {$wpdb->postmeta} pm_pickup 
            WHERE pm_pickup.post_id = p.ID 
            AND pm_pickup.meta_key = 'wpcargo_pickup_date_picker'
            AND (
                STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') >= STR_TO_DATE('{$fecha_inicio_ymd}', '%Y-%m-%d')
                AND STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') <= STR_TO_DATE('{$fecha_fin_ymd}', '%Y-%m-%d')
            )
        )";
    }
    
    $titulo = '';
    $columnas = array();
    $shipments_data = array();
    
    // Definir título y columnas según la vista
    switch($tipo_vista) {
        case 'ingresos_envios':
            $titulo = '💰 Detalle de Ingresos por Envíos';
            $columnas = array('Pedido', 'Cliente', 'Motorizado', 'Costo Envío', 'Fecha');
            break;
        case 'efectivo_recaudado':
            $titulo = '💵 Detalle de Efectivo Recaudado';
            $columnas = array('Pedido', 'Cliente', 'Motorizado', 'Efectivo', 'Estado', 'Fecha');
            break;
        case 'recaudado_merc':
            $titulo = '🏦 Detalle de Recaudado por MERC';
            $columnas = array('Pedido', 'Cliente', 'Motorizado', 'Pago a MERC', 'Total', 'Fecha');
            break;
        case 'recaudado_pos':
        case 'pos_recaudado':
            $titulo = '💳 Detalle de Recaudado por POS';
            $columnas = array('Pedido', 'Cliente', 'Motorizado', 'POS', 'Fecha');
            break;
            break;
        case 'recaudado_marca':
            $titulo = '🏢 Detalle de Recaudado por MARCA';
            $columnas = array('Pedido', 'Cliente', 'Motorizado', 'Pago a MARCA', 'Estado', 'Fecha');
            break;
        case 'por_cobrar_motorizados':
            $titulo = '⏳ Detalle Por Cobrar a Motorizados';
            $columnas = array('Pedido', 'Motorizado', 'Monto Total', 'Efectivo', 'Digital', 'Estado', 'Fecha');
            break;
        case 'por_pagar_remitentes':
            $titulo = '📤 Detalle Por Pagar a Remitentes';
            $columnas = array('Pedido', 'Cliente', 'Costo Producto', 'Costo Envío', 'Estado', 'Fecha');
            break;
        default:
            return '<div class="alert alert-warning">Vista no disponible</div>';
    }

    // Añadir columna de comprobante en todas las vistas detalladas
    $columnas[] = 'Comprobante';
    
    // Obtener datos según el tipo de vista
    $query = "
        SELECT
            p.ID,
            p.post_title,
            p.post_date,
            pm_shipper.meta_value as shipper_id,
            pm_driver.meta_value as driver_id,
            pm_envio.meta_value as costo_envio,
            pm_producto.meta_value as costo_producto,
            pm_estado_motorizado.meta_value as estado_motorizado,
            pm_included.meta_value as estado_remitente,
            pm_quien_paga.meta_value as quien_paga
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_driver ON p.ID = pm_driver.post_id AND pm_driver.meta_key = 'wpcargo_driver'
        LEFT JOIN {$wpdb->postmeta} pm_envio ON p.ID = pm_envio.post_id AND pm_envio.meta_key = 'wpcargo_costo_envio'
        LEFT JOIN {$wpdb->postmeta} pm_producto ON p.ID = pm_producto.post_id AND pm_producto.meta_key = 'wpcargo_costo_producto'
        LEFT JOIN {$wpdb->postmeta} pm_estado_motorizado ON p.ID = pm_estado_motorizado.post_id AND pm_estado_motorizado.meta_key = 'wpcargo_estado_pago_motorizado'
        LEFT JOIN {$wpdb->postmeta} pm_included ON p.ID = pm_included.post_id AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
        LEFT JOIN {$wpdb->postmeta} pm_quien_paga ON p.ID = pm_quien_paga.post_id AND pm_quien_paga.meta_key = 'wpcargo_quien_paga'
        {$extra_join}
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        $date_query
        {$extra_where}
        ORDER BY p.post_date DESC
    ";
    
    $shipments = $wpdb->get_results($query);
    
    // Vista detallada específica: Por pagar remitentes (mostrar por CLIENTE)
    if ( $tipo_vista === 'por_pagar_remitentes' ) {
        // Construir balances por cliente similar a merc_admin_resumen_general
        $client_balances = array();
        foreach ( $shipments as $shipment ) {
            $shipper_id = isset($shipment->shipper_id) ? $shipment->shipper_id : '';
            if ( empty( $shipper_id ) ) continue;
            $is_verified = merc_is_shipment_liquidation_verified( $shipment->ID );
            $totales = get_payment_totals_by_method( $shipment->ID );
            if ( ! $is_verified ) {
                if ( ! isset( $client_balances[ $shipper_id ] ) ) {
                    $client_balances[ $shipper_id ] = array('pago_merc'=>0.0, 'pos'=>0.0, 'pago_marca'=>0.0, 'servicio'=>0.0, 'shipments'=>array());
                }
                $client_balances[ $shipper_id ]['pago_merc'] += floatval( $totales['pago_merc'] );
                $pos_display = get_pos_net_for_shipment( $shipment->ID, $totales );
                $client_balances[ $shipper_id ]['pos'] += $pos_display;
                $client_balances[ $shipper_id ]['pago_marca'] += floatval( $totales['pago_marca'] );
                $client_balances[ $shipper_id ]['servicio'] += floatval( $shipment->costo_envio );
                $client_balances[ $shipper_id ]['shipments'][] = $shipment->ID;
            }
        }

        // Renderizar tabla por cliente
        ?>
        <h3><?php echo $titulo; ?></h3>
        <table class="merc-detalle-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Monto a Pagar</th>
                    <th>Pago a MERC</th>
                    <th>POS</th>
                    <th>Servicio</th>
                    <th>Estado</th>
                    <th>Comprobantes</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $total_general = 0.0;
            if ( isset($client_balances) && is_array($client_balances) ) {
                foreach ( $client_balances as $cid => $vals ) {
                    $monto = (floatval($vals['pago_merc']) + floatval($vals['pos'])) - floatval($vals['servicio']);
                    if ( $monto <= 0 ) continue;
                    $total_general += $monto;
                    
                    // Cliente: Nombre de empresa + (nombre y apellido)
                    if ($cid) {
                        $client_user = get_userdata($cid);
                        $client_company = get_user_meta($cid, 'billing_company', true);
                        $client_first_name = $client_user->first_name;
                        $client_last_name = $client_user->last_name;
                        if ($client_company && ($client_first_name || $client_last_name)) {
                            $cliente_nombre = $client_company . ' (' . trim($client_first_name . ' ' . $client_last_name) . ')';
                        } elseif ($client_company) {
                            $cliente_nombre = $client_company;
                        } else {
                            $cliente_nombre = trim($client_first_name . ' ' . $client_last_name) ?: $client_user->display_name;
                        }
                    } else {
                        $cliente_nombre = 'N/A';
                    }
                    
                    // Determinar si existe una liquidación verificada que cubra estos envíos
                    $estado_cliente = 'Pendiente';
                    $liq_voucher_html = '';
                    $history = get_user_meta( $cid, 'merc_liquidations', true );
                    if ( is_array( $history ) && ! empty( $history ) ) {
                        foreach ( $history as $entry ) {
                            if ( empty( $entry['verified'] ) ) continue;
                            $entry_shipments = isset($entry['shipments']) && is_array($entry['shipments']) ? $entry['shipments'] : array();
                            // Intersección: si la liquidación incluye al menos uno de los envíos del cliente
                            if ( ! empty( array_intersect( $entry_shipments, $vals['shipments'] ) ) ) {
                                $estado_cliente = 'Liquidado';
                                if ( ! empty( $entry['attachment_id'] ) ) {
                                    $liq_voucher_html = wp_get_attachment_image( intval($entry['attachment_id']), array(48,48) );
                                }
                                break;
                            }
                        }
                    }
                    // Comprobantes: si existe voucher de liquidación verificada mostrarlo,
                    // Mostrar solo el adjunto de la liquidación verificada (sin fallback)
                    $voucher_html = '';
                    if ( $liq_voucher_html ) {
                        $voucher_html = $liq_voucher_html;
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html( $cliente_nombre ); ?></td>
                        <td><strong>S/. <?php echo number_format( $monto, 2 ); ?></strong></td>
                        <td>S/. <?php echo number_format( floatval($vals['pago_merc']), 2 ); ?></td>
                        <td>S/. <?php echo number_format( floatval($vals['pos']), 2 ); ?></td>
                        <td>S/. <?php echo number_format( floatval($vals['servicio']), 2 ); ?></td>
                        <td><?php echo esc_html( $estado_cliente ); ?></td>
                        <td><?php echo $voucher_html; ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align:right;"><strong>TOTAL:</strong></td>
                    <td><strong>S/. <?php echo number_format($total_general, 2); ?></strong></td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
        <?php
        // estilo reutilizado
        ?>
        <style>
            .merc-detalle-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            .merc-detalle-table th { background: #34495e; color: white; padding: 12px; text-align: left; border: 1px solid #2c3e50; }
            .merc-detalle-table td { padding: 10px 12px; border: 1px solid #ddd; }
            .merc-detalle-table tbody tr:hover { background: #f8f9fa; }
            .merc-detalle-table tfoot td { background: #ecf0f1; font-weight: bold; padding: 15px 12px; }
        </style>
        <?php
        return;
    }

    ?>
    <h3><?php echo $titulo; ?></h3>
    <table class="merc-detalle-table">
        <thead>
            <tr>
                <?php foreach($columnas as $col): ?>
                    <th><?php echo $col; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_general = 0.0;
            foreach($shipments as $shipment): 
                $totales = get_payment_totals_by_method($shipment->ID);
                // Mostrar POS neto guardado
                $pos_display = get_pos_net_for_shipment( $shipment->ID, $totales );
                
                // Cliente: Nombre de empresa + (nombre y apellido)
                if ($shipment->shipper_id) {
                    $shipper_user = get_userdata($shipment->shipper_id);
                    $shipper_company = get_user_meta($shipment->shipper_id, 'billing_company', true);
                    $shipper_first_name = $shipper_user->first_name;
                    $shipper_last_name = $shipper_user->last_name;
                    if ($shipper_company && ($shipper_first_name || $shipper_last_name)) {
                        $cliente_nombre = $shipper_company . ' (' . trim($shipper_first_name . ' ' . $shipper_last_name) . ')';
                    } elseif ($shipper_company) {
                        $cliente_nombre = $shipper_company;
                    } else {
                        $cliente_nombre = trim($shipper_first_name . ' ' . $shipper_last_name) ?: $shipper_user->display_name;
                    }
                } else {
                    $cliente_nombre = 'N/A';
                }
                
                // Motorizado: Nombre y apellido (o display_name si no están disponibles)
                if ($shipment->driver_id) {
                    $driver_user = get_userdata($shipment->driver_id);
                    $driver_first_name = $driver_user->first_name;
                    $driver_last_name = $driver_user->last_name;
                    $motorizado_nombre = trim($driver_first_name . ' ' . $driver_last_name) ?: $driver_user->display_name;
                } else {
                    $motorizado_nombre = 'Sin asignar';
                }
                
                $fecha = date('d/m/Y', strtotime($shipment->post_date));
                
                // Filtrar según el tipo de vista
                $mostrar = false;
                $valor_principal = 0;
                
                switch($tipo_vista) {
                    case 'ingresos_envios':
                        $valor_principal = floatval($shipment->costo_envio);
                        $mostrar = $valor_principal > 0;
                        break;
                    case 'efectivo_recaudado':
                        $valor_principal = $totales['efectivo'];
                        $mostrar = $valor_principal > 0;
                        break;
                    case 'recaudado_merc':
                        $recaudado_merc = get_recaudado_merc($shipment->ID);
                        $mostrar = $recaudado_merc > 0;
                        break;
                    case 'recaudado_pos':
                    case 'pos_recaudado':
                        // Mostrar cuando haya POS neto positivo.
                        $mostrar = $pos_display > 0;
                        // Fallback: si por alguna razón pos_display es 0 pero el envío aporta a recaudado_merc,
                        // mostrarlo para facilitar la inspección (se registrará en logs para depuración).
                        if ( ! $mostrar ) {
                            $recaudado_merc_tmp = get_recaudado_merc($shipment->ID);
                            if ( $recaudado_merc_tmp > 0 ) {
                                $mostrar = true;
                                if ( function_exists('current_user_can') && current_user_can('administrator') ) {
                                    error_log(sprintf('MERC_DEBUG_POS_FALLBACK - shipment=%d pos_display=%01.2f recaudado_merc=%01.2f pod_meta=%s',
                                        $shipment->ID,
                                        $pos_display,
                                        $recaudado_merc_tmp,
                                        substr( (string) get_post_meta($shipment->ID, 'pod_payment_methods', true ), 0, 200 )
                                    ));
                                }
                            }
                        }
                        break;
                    case 'recaudado_marca':
                        $valor_principal = $totales['pago_marca'];
                        $mostrar = $valor_principal > 0;
                        break;
                    case 'por_cobrar_motorizados':
                        $estado = $shipment->estado_motorizado ?: 'pendiente';
                        $mostrar = $estado === 'pendiente' && $totales['total'] > 0;
                        break;
                    case 'por_pagar_remitentes':
                        $estado = $shipment->estado_remitente ?: 'pendiente';
                        $mostrar = $estado === 'pendiente' && $shipment->quien_paga === 'cliente_final' && floatval($shipment->costo_producto) > 0;
                        break;
                }
                
                if (!$mostrar) continue;
                
                // Renderizar fila según el tipo
                ?>
                <tr>
                    <?php if ($tipo_vista === 'ingresos_envios'): ?>
                        <td><strong>#<?php echo $shipment->post_title; ?></strong></td>
                        <td><?php echo $cliente_nombre; ?></td>
                        <td><?php echo $motorizado_nombre; ?></td>
                        <td>S/. <?php echo number_format(floatval($shipment->costo_envio), 2); $total_general += floatval($shipment->costo_envio); ?></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php echo merc_get_shipment_liquidation_action_image_html( $shipment->ID, 'cliente_pago_voucher', 48 ); ?></td>
                        
                    <?php elseif ($tipo_vista === 'efectivo_recaudado'): ?>
                        <td><strong>#<?php echo $shipment->post_title; ?></strong></td>
                        <td><?php echo $cliente_nombre; ?></td>
                        <td><?php echo $motorizado_nombre; ?></td>
                        <td>S/. <?php echo number_format($totales['efectivo'], 2); $total_general += $totales['efectivo']; ?></td>
                        <td><?php echo ($shipment->estado_motorizado === 'liquidado') ? '<span class="badge badge-success">Liquidado</span>' : '<span class="badge badge-warning">Pendiente</span>'; ?></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php if ( merc_shipment_method_has_image( $shipment->ID, 'efectivo' ) ) echo '<button class="merc-btn-ver-voucher" data-shipment-id="' . esc_attr($shipment->ID) . '" data-tipo="efectivo" title="Ver voucher">👁️</button>'; ?></td>
                        
                    <?php elseif ($tipo_vista === 'recaudado_merc'): 
                        $recaudado_merc = get_recaudado_merc($shipment->ID);
                        $total_general += $recaudado_merc;
                        ?>
                        <td><strong>#<?php echo $shipment->post_title; ?></strong></td>
                        <td><?php echo $cliente_nombre; ?></td>
                        <td><?php echo $motorizado_nombre; ?></td>
                        <td>S/. <?php echo number_format($totales['pago_merc'], 2); ?></td>
                        <td><strong>S/. <?php echo number_format($recaudado_merc, 2); ?></strong></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php if ( merc_shipment_method_has_image( $shipment->ID, 'pago_merc' ) ) echo '<button class="merc-btn-ver-voucher" data-shipment-id="' . esc_attr($shipment->ID) . '" data-tipo="pago_merc" title="Ver voucher">👁️</button>'; ?></td>
                        
                    <?php elseif ($tipo_vista === 'pos_recaudado'): 
                        // Mostrar detalle centrado en POS
                        $total_general += floatval($pos_display);
                        ?>
                        <td><strong>#<?php echo $shipment->post_title; ?></strong></td>
                        <td><?php echo $cliente_nombre; ?></td>
                        <td><?php echo $motorizado_nombre; ?></td>
                        <td><strong>S/. <?php echo number_format($pos_display, 2); ?></strong></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php if ( merc_shipment_method_has_image( $shipment->ID, 'pos' ) ) echo '<button class="merc-btn-ver-voucher" data-shipment-id="' . esc_attr($shipment->ID) . '" data-tipo="pos" title="Ver voucher">👁️</button>'; ?></td>
                        
                    <?php elseif ($tipo_vista === 'recaudado_marca'): ?>
                        <td><strong>#<?php echo $shipment->post_title; ?></strong></td>
                        <td><?php echo $cliente_nombre; ?></td>
                        <td><?php echo $motorizado_nombre; ?></td>
                        <td>S/. <?php echo number_format($totales['pago_marca'], 2); $total_general += $totales['pago_marca']; ?></td>
                        <td><?php echo ($shipment->estado_remitente === 'liquidado') ? '<span class="badge badge-success">Liquidado</span>' : '<span class="badge badge-warning">Pendiente</span>'; ?></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php if ( merc_shipment_method_has_image( $shipment->ID, 'pago_marca' ) || merc_get_pago_marca_voucher_thumb_html( $shipment->ID ) ) echo '<button class="merc-btn-ver-voucher" data-shipment-id="' . esc_attr($shipment->ID) . '" data-tipo="pago_marca" title="Ver voucher">👁️</button>'; ?></td>
                        
                    <?php elseif ($tipo_vista === 'por_cobrar_motorizados'): ?>
                        <td><strong>#<?php echo $shipment->post_title; ?></strong></td>
                        <td><?php echo $motorizado_nombre; ?></td>
                        <td>S/. <?php echo number_format($totales['total'], 2); $total_general += $totales['total']; ?></td>
                        <td>S/. <?php echo number_format($totales['efectivo'], 2); ?></td>
                        <td>S/. <?php echo number_format($totales['pago_merc'] + $pos_display, 2); ?></td>
                        <td><span class="badge badge-warning">Pendiente</span></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php if ( merc_shipment_method_has_image( $shipment->ID, 'efectivo' ) ) echo '<button class="merc-btn-ver-voucher" data-shipment-id="' . esc_attr($shipment->ID) . '" data-tipo="efectivo" title="Ver voucher">👁️</button>'; ?></td>
                        
                    <?php elseif ($tipo_vista === 'por_pagar_remitentes'): ?>
                        <td><strong>#<?php echo $shipment->post_title; ?></strong></td>
                        <td><?php echo $cliente_nombre; ?></td>
                        <td>S/. <?php echo number_format(floatval($shipment->costo_producto), 2); $total_general += floatval($shipment->costo_producto); ?></td>
                        <td>S/. <?php echo number_format(floatval($shipment->costo_envio), 2); ?></td>
                        <td><span class="badge badge-warning">Pendiente</span></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php if ( merc_shipment_method_has_image( $shipment->ID, 'pago_marca' ) || merc_shipment_method_has_image( $shipment->ID, 'pago_merc' ) || merc_shipment_method_has_image( $shipment->ID, 'pos' ) ) echo '<button class="merc-btn-ver-voucher" data-shipment-id="' . esc_attr($shipment->ID) . '" data-tipo="all" title="Ver voucher">👁️</button>'; ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="<?php echo count($columnas) - 1; ?>" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td><strong>S/. <?php echo number_format($total_general, 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
    
    <style>
        .merc-detalle-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .merc-detalle-table th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            border: 1px solid #2c3e50;
        }
        .merc-detalle-table td {
            padding: 10px 12px;
            border: 1px solid #ddd;
        }
        .merc-detalle-table tbody tr:hover {
            background: #f8f9fa;
        }
        .merc-detalle-table tfoot td {
            background: #ecf0f1;
            font-weight: bold;
            padding: 15px 12px;
        }
    </style>
    <?php
}

function merc_admin_filtros( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_motorizado, $filtro_cliente ) {
    $motorizados = get_users( array( 'role' => 'wpcargo_driver' ) );
    $clientes    = get_users( array( 'role' => 'wpcargo_client' ) );
    
    // Obtener la URL actual sin parámetros
    $current_url = strtok($_SERVER["REQUEST_URI"], '?');
    ?>
    <form method="GET" action="<?php echo esc_url($current_url); ?>" class="merc-filter-form">
        <div class="form-group">
            <label>Fecha Inicio</label>
            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo esc_attr($fecha_inicio); ?>" />
        </div>
        <div class="form-group">
            <label>Fecha Fin</label>
            <input type="date" name="fecha_fin" class="form-control" value="<?php echo esc_attr($fecha_fin); ?>" />
        </div>
        <div class="form-group">
            <label>Estado</label>
            <select name="filtro_estado" class="form-control">
                <option value="" <?php selected( $filtro_estado, '' ); ?>>Todos</option>
                <option value="pendiente" <?php selected( $filtro_estado, 'pendiente' ); ?>>Pendientes</option>
                <option value="liquidado" <?php selected( $filtro_estado, 'liquidado' ); ?>>Liquidados</option>
            </select>
        </div>
        <div class="form-group">
            <label>Motorizado</label>
            <select name="filtro_motorizado" class="form-control">
                <option value="0">Todos</option>
                <?php foreach ( $motorizados as $motorizado ) : ?>
                    <option value="<?php echo esc_attr( $motorizado->ID ); ?>" <?php selected( $filtro_motorizado, $motorizado->ID ); ?>>
                        <?php echo esc_html( $motorizado->display_name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Cliente</label>
            <select name="filtro_cliente" class="form-control">
                <option value="0">Todos</option>
                <?php foreach ( $clientes as $cliente ) : ?>
                    <option value="<?php echo esc_attr( $cliente->ID ); ?>" <?php selected( $filtro_cliente, $cliente->ID ); ?>>
                        <?php echo esc_html( $cliente->display_name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
            <a href="<?php echo get_permalink(); ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
    <script>
    jQuery(document).ready(function($) {
        $('.merc-filter-form').on('submit', function(e) {
            // Eliminar parámetros con valores por defecto para limpiar la URL
            $(this).find('select, input').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var val = $field.val();
                
                // Si es estado vacío o motorizado/cliente = 0, remover del form
                if ((name === 'filtro_estado' && val === '') || 
                    (name === 'filtro_motorizado' && val === '0') || 
                    (name === 'filtro_cliente' && val === '0')) {
                    $field.prop('disabled', true);
                }
            });
        });
    });
    </script>
    <?php
}


// ---------------------------------------------------------------------------
// CREAR PÁGINAS AUTOMÁTICAMENTE
// ---------------------------------------------------------------------------

add_action( 'after_switch_theme', 'merc_create_dashboard_pages' );
function merc_create_dashboard_pages() {
    $pages = array(
        'panel-motorizado' => array(
            'title'   => 'Panel del Motorizado',
            'content' => '[merc_panel_motorizado]',
        ),
        'panel-cliente' => array(
            'title'   => 'Panel del Cliente',
            'content' => '[merc_panel_cliente]',
        ),
        'panel-admin' => array(
            'title'   => 'Panel del Administrador',
            'content' => '[merc_panel_admin]',
        ),
    );
    
    foreach ( $pages as $slug => $page_data ) {
        $existing_page = get_page_by_path( $slug );
        if ( ! $existing_page ) {
            wp_insert_post( array(
                'post_title'   => $page_data['title'],
                'post_name'    => $slug,
                'post_content' => $page_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ) );
        }
    }
}

// ---------------------------------------------------------------------------
// REDIRECCIÓN AUTOMÁTICA SEGÚN ROL
// ---------------------------------------------------------------------------

add_action( 'template_redirect', 'merc_role_based_redirect', 5 );
function merc_role_based_redirect() {
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // NOTA: La verificación de bloqueo por tipo de envío se maneja en las funciones
    // merc_check_tipo_*_blocked() que deshabilitan el formulario, no redirigen
    // Por lo tanto, no verificamos bloqueo manual aquí
    
    // Redirección automática al home
    if ( ! is_front_page() || ! empty( $_GET ) ) {
        return;
    }
    
    $role_pages   = array(
        'wpcargo_driver' => 'panel-motorizado',
        'wpcargo_client' => 'panel-cliente',
        'administrator'  => 'panel-admin',
    );
    
    foreach ( $role_pages as $role => $slug ) {
        if ( in_array( $role, $current_user->roles, true ) ) {
            $page = get_page_by_path( $slug );
            if ( $page ) {
                wp_redirect( get_permalink( $page->ID ) );
                exit;
            }
            break;
        }
    }
}

// ---------------------------------------------------------------------------
// AGREGAR ENLACES EN EL MENÚ DEL SIDEBAR
// ---------------------------------------------------------------------------

// Evitar que administradores entren al WP-ADMIN: redirigir al dashboard frontend
/*add_action('admin_init', 'merc_redirect_admin_to_frontend_dashboard', 1);
function merc_redirect_admin_to_frontend_dashboard() {
    if ( ! is_user_logged_in() ) return;

    $user = wp_get_current_user();
    if ( empty($user) || ! in_array('administrator', (array) $user->roles, true) ) return;

    // Permitir AJAX y procesos internos
    if ( defined('DOING_AJAX') && DOING_AJAX ) return;

    global $pagenow;
    // Permitir páginas administrativas necesarias (perfil, AJAX, admin-post, opciones de plugins que requieren admin)
    $allowed = array(
        'admin-ajax.php',
        'admin-post.php',
        'profile.php',
        'options.php',
        'async-upload.php',
        'media-upload.php',
    );
    if ( in_array( basename( $pagenow ), $allowed, true ) ) return;

    // Obtener URL de redirección configurada
    $custom = get_option('merc_admin_dashboard_url');
    if ( ! empty($custom) ) {
        $redirect = esc_url_raw( $custom );
    } else {
        // Intentar encontrar una página que contenga el shortcode [merc_panel_admin]
        global $wpdb;
        $like = '%[merc_panel_admin%';
        $post_id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status = 'publish' LIMIT 1", $like) );
        if ( $post_id ) {
            $redirect = get_permalink( $post_id );
        } else {
            // Fallback al home
            $redirect = home_url('/');
        }
    }

    if ( ! empty( $redirect ) ) {
        wp_safe_redirect( $redirect );
        exit;
    }
}
*/
/**
 * NUEVO SISTEMA DE BLOQUEO POR TIPO DE ENVÍO
 * Los bloqueos son independientes por cada tipo de servicio
 */

// Función para verificar bloqueo de MERC EMPRENDEDOR (normal)
function merc_check_tipo_normal_blocked($client_id, $tipo_envio = 'normal') {
    global $wpdb;
    $hoy = current_time('Y-m-d');
    $hora_actual = current_time('H:i');
    
    error_log("🔍 [NORMAL] Verificando bloqueo para cliente #{$client_id} - Hora: {$hora_actual}");
    
    // VERIFICAR DESBLOQUEO MANUAL PRIMERO (si fue desbloqueado manualmente hoy, permitir)
    $desbloqueo_manual_fecha = get_user_meta($client_id, 'merc_desbloqueado_manualmente_fecha', true);
    $envios_permitidos = intval(get_user_meta($client_id, 'merc_desbloqueo_manual_envios_permitidos', true));
    
    if ($desbloqueo_manual_fecha === $hoy && $envios_permitidos > 0) {
        error_log("🔓 [NORMAL] PERMITIDO: Desbloqueo manual activo - Cliente puede crear envíos");
        return false;
    }
    
    // VERIFICAR BLOQUEO MANUAL PRIMERO
    $bloqueado_manual = get_user_meta($client_id, 'merc_tipo_normal_bloqueado', true);
    if ($bloqueado_manual === '1') {
        error_log("🔴 [NORMAL] BLOQUEADO: Bloqueo manual por administrador");
        return true;
    }
    
    // 1. Si NO tiene envíos y es >= 10:00 AM → BLOQUEADO
    $envios_tipo = merc_get_envios_hoy_por_tipo($client_id, 'normal');
    
    if (empty($envios_tipo) && $hora_actual >= '10:00') {
        error_log("🔴 [NORMAL] BLOQUEADO: Sin envíos y pasadas las 10:00 AM");
        return true;
    }
    
    // 2. Si TIENE envíos, verificar si todos están en "recogido" o "no recogido"
    if (!empty($envios_tipo)) {
        $todos_recogidos_o_no = true;
        
        foreach ($envios_tipo as $envio) {
            $estado = strtolower(trim($envio->estado));
            // Si hay algún estado que NO sea "recogido" ni "no recogido", permitir crear más
            if ($estado !== 'recogido' && $estado !== 'no recogido') {
                $todos_recogidos_o_no = false;
                break;
            }
        }
        
        // Bloquear si TODOS son RECOGIDO O NO RECOGIDO
        if ($todos_recogidos_o_no) {
            error_log("🔴 [NORMAL] BLOQUEADO: Todos los envíos están en recogido/no recogido");
            return true;
        }
    }
    
    error_log("✅ [NORMAL] PERMITIDO");
    return false;
}

// Función para verificar bloqueo de MERC AGENCIA (express)
function merc_check_tipo_express_blocked($client_id, $tipo_envio = 'express') {
    global $wpdb;
    $hoy = current_time('Y-m-d');
    $hora_actual = current_time('H:i');
    
    error_log("🔍 [EXPRESS] Verificando bloqueo para cliente #{$client_id} - Hora: {$hora_actual}");
    
    // VERIFICAR DESBLOQUEO MANUAL PRIMERO (si fue desbloqueado manualmente hoy, permitir)
    $desbloqueo_manual_fecha = get_user_meta($client_id, 'merc_desbloqueado_manualmente_fecha', true);
    $envios_permitidos = intval(get_user_meta($client_id, 'merc_desbloqueo_manual_envios_permitidos', true));
    
    if ($desbloqueo_manual_fecha === $hoy && $envios_permitidos > 0) {
        error_log("🔓 [EXPRESS] PERMITIDO: Desbloqueo manual activo - Cliente puede crear envíos");
        return false;
    }
    
    // VERIFICAR BLOQUEO MANUAL PRIMERO
    $bloqueado_manual = get_user_meta($client_id, 'merc_tipo_express_bloqueado', true);
    if ($bloqueado_manual === '1') {
        error_log("🔴 [EXPRESS] BLOQUEADO: Bloqueo manual por administrador");
        return true;
    }
    
    $envios_tipo = merc_get_envios_hoy_por_tipo($client_id, 'express');
    
    // 1. Si NO tiene envíos y son >= 12:30 PM → BLOQUEADO
    if (empty($envios_tipo) && $hora_actual >= '12:30') {
        error_log("🔴 [EXPRESS] BLOQUEADO: Sin envíos y pasadas las 12:30 PM");
        return true;
    }
    
    // 2. Si TIENE envíos y es después de la 1:00 PM → BLOQUEADO
    if (!empty($envios_tipo) && $hora_actual >= '13:00') {
        error_log("🔴 [EXPRESS] BLOQUEADO: Con envíos y pasadas las 1:00 PM");
        return true;
    }
    
    error_log("✅ [EXPRESS] PERMITIDO");
    return false;
}

// Función para verificar bloqueo de MERC FULL FITMENT (full_fitment)
function merc_check_tipo_full_fitment_blocked($client_id, $tipo_envio = 'full_fitment') {
    global $wpdb;
    $hoy = current_time('Y-m-d');
    $hora_actual = current_time('H:i');
    
    error_log("🔍 [FULL_FITMENT] Verificando bloqueo para cliente #{$client_id} - Hora: {$hora_actual}");
    
    // VERIFICAR DESBLOQUEO MANUAL PRIMERO (si fue desbloqueado manualmente hoy, permitir)
    $desbloqueo_manual_fecha = get_user_meta($client_id, 'merc_desbloqueado_manualmente_fecha', true);
    $envios_permitidos = intval(get_user_meta($client_id, 'merc_desbloqueo_manual_envios_permitidos', true));
    
    if ($desbloqueo_manual_fecha === $hoy && $envios_permitidos > 0) {
        error_log("🔓 [FULL_FITMENT] PERMITIDO: Desbloqueo manual activo - Cliente puede crear envíos");
        return false;
    }
    
    // VERIFICAR BLOQUEO MANUAL PRIMERO
    $bloqueado_manual = get_user_meta($client_id, 'merc_tipo_full_fitment_bloqueado', true);
    if ($bloqueado_manual === '1') {
        error_log("🔴 [FULL_FITMENT] BLOQUEADO: Bloqueo manual por administrador");
        return true;
    }
    
    $envios_tipo = merc_get_envios_hoy_por_tipo($client_id, 'full_fitment');
    
    // 1. Si NO tiene envíos y son >= 11:30 AM → BLOQUEADO
    if (empty($envios_tipo) && $hora_actual >= '11:30') {
        error_log("🔴 [FULL_FITMENT] BLOQUEADO: Sin envíos y pasadas las 11:30 AM");
        return true;
    }
    
    // 2. Si TIENE envíos y es después de las 12:15 PM → BLOQUEADO
    if (!empty($envios_tipo) && $hora_actual >= '12:15') {
        error_log("🔴 [FULL_FITMENT] BLOQUEADO: Con envíos y pasadas las 12:15 PM");
        return true;
    }
    
    error_log("✅ [FULL_FITMENT] PERMITIDO");
    return false;
}

// Función auxiliar: Obtener envíos de hoy por tipo
function merc_get_envios_hoy_por_tipo($client_id, $tipo) {
    global $wpdb;
    $hoy = current_time('Y-m-d');
    
    // Buscar por varias fuentes de tipo: 'tipo_envio' (custom), 'wpcargo_type_of_shipment' (plugin),
    // o inferir desde 'wpcargo_container' cuando corresponda.
    $tipo_lookup = strtolower($tipo);
    $query = $wpdb->prepare("
        SELECT 
            p.ID, 
            p.post_date,
            pm_status.meta_value as estado,
            COALESCE(pm_tipo.meta_value, pm_type_of_shipment.meta_value, pm_container.meta_value) as tipo_envio,
            pm_fecha_pickup.meta_value as fecha_pickup
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_shipper 
            ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_status 
            ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
        LEFT JOIN {$wpdb->postmeta} pm_tipo 
            ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = 'tipo_envio'
        LEFT JOIN {$wpdb->postmeta} pm_type_of_shipment 
            ON p.ID = pm_type_of_shipment.post_id AND pm_type_of_shipment.meta_key = 'wpcargo_type_of_shipment'
        LEFT JOIN {$wpdb->postmeta} pm_container 
            ON p.ID = pm_container.post_id AND pm_container.meta_key = 'wpcargo_container'
        LEFT JOIN {$wpdb->postmeta} pm_fecha_pickup 
            ON p.ID = pm_fecha_pickup.post_id AND pm_fecha_pickup.meta_key = 'wpcargo_pickup_date_picker'
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_shipper.meta_value = %s
        AND (
            LOWER(COALESCE(pm_tipo.meta_value, '')) = %s
            OR LOWER(COALESCE(pm_type_of_shipment.meta_value, '')) = %s
            OR LOWER(COALESCE(pm_container.meta_value, '')) LIKE %s
        )
    ", $client_id, $tipo_lookup, $tipo_lookup, '%' . $wpdb->esc_like($tipo_lookup) . '%');
    
    $envios = $wpdb->get_results($query);
    
    // Filtrar por fecha de pickup = hoy
    $envios_hoy = array();
    foreach ($envios as $envio) {
        $fecha_pickup = !empty($envio->fecha_pickup) ? date('Y-m-d', strtotime($envio->fecha_pickup)) : date('Y-m-d', strtotime($envio->post_date));
        
        if ($fecha_pickup === $hoy) {
            $envios_hoy[] = $envio;
        }
    }
    
    // Si no hay envíos para el tipo (posible falta de meta 'tipo_envio'), realizar fallback:
    // traer todos los envíos del cliente para hoy (sin filtrar por tipo)
    if (empty($envios_hoy)) {
        $fallback_sql = $wpdb->prepare("SELECT p.ID, p.post_date, pm_status.meta_value as estado, pm_tipo.meta_value as tipo_envio, pm_fecha_pickup.meta_value as fecha_pickup
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
            LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = 'tipo_envio'
            LEFT JOIN {$wpdb->postmeta} pm_fecha_pickup ON p.ID = pm_fecha_pickup.post_id AND pm_fecha_pickup.meta_key = 'wpcargo_pickup_date_picker'
            WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish' AND pm_shipper.meta_value = %s", $client_id);

        $all_ships = $wpdb->get_results($fallback_sql);
        foreach ($all_ships as $envio) {
            $fecha_pickup = !empty($envio->fecha_pickup) ? date('Y-m-d', strtotime($envio->fecha_pickup)) : date('Y-m-d', strtotime($envio->post_date));
            if ($fecha_pickup === $hoy) {
                $envio->fecha_pickup = $fecha_pickup;
                $envios_hoy[] = $envio;
            }
        }
    }

    return $envios_hoy;
}

// Función para verificar bloqueo específico por tipo de envío
/**
 * ============= NUEVA LÓGICA DE BLOQUEOS (Febrero 2026) =============
 * 
 * DOCUMENTO DE CONFIGURACIÓN Y USO
 * 
 * ESCENARIOS IMPLEMENTADOS:
 * 
 * 1️⃣  SIN ENVÍOS CREADOS (count = 0):
 *     - Si no ha pasado hora límite → PERMITIR creación normal
 *     - Si SÍ pasó hora límite → PERMITIR pero forza fecha = MAÑANA
 *     Límites por tipo:
 *       • NORMAL (Emprendedor): 10:00 AM
 *       • EXPRESS (Agencia): 12:30 PM
 *       • FULL FITMENT: 11:30 AM
 * 
 * 2️⃣  CON ENVÍOS CREADOS (count > 0):
 *     A) CLIENTE LE DEBE A MERC → BLOQUEADO completamente en los 3 tipos
 *        - No se puede crear envíos hasta que liquide la deuda
 *     
 *     B) MERC LE DEBE AL CLIENTE + AÚN NO SON LAS 19:30 → BLOQUEADO
 *        - A las 19:30 (7:30 PM) se desbloquea automáticamente
 *     
 *     C) MERC LE DEBE + YA SON 19:30+ → PERMITIR con fecha = MAÑANA
 *        - El cliente puede crear envíos para el día siguiente
 *     
 *     D) BALANCE = 0 (equilibrado) → PERMITIR con fecha = MAÑANA
 *        - El cliente puede crear envíos para el día siguiente
 * 
 * Límites por tipo (con envíos):
 *   • NORMAL: comparar con 10:00 AM
 *   • EXPRESS: comparar con 13:00 (1:00 PM)
 *   • FULL FITMENT: comparar con 12:15 PM
 * 
 * RESETEO DIARIO:
 * - Todos los desbloqueos parciales se limpian automáticamente al cambiar de día
 * - Se ejecuta mediante wp_option 'merc_last_unlock_reset_date'
 * 
 * CONFIGURACIÓN REQUERIDA:
 * 
 * ⚙️  META KEY: merc_estado_financiero (user_meta)
 *     Valores posibles:
 *     - 'merc_debe' → Mercourier le debe dinero al cliente
 *     - 'cliente_debe' → Cliente le debe dinero a Mercourier
 *     - 'balance_cero' → Ambas partes están al corriente
 * 
 *     Cómo implementar:
 *     Si NO tienes este sistema, necesitarás:
 *     1. Crear una tabla en la BD o usar user_meta para guardar esto
 *     2. Actualizar 'merc_estado_financiero' cuando haya liquidaciones
 *     3. O modificar merc_get_estado_financiero() para leer de tu fuente actual
 * 
 * 📱 ENDPOINTS AJAX DISPONIBLES:
 *    - merc_get_partial_unlock_info → Obtiene info de desbloqueo parcial (para frontend)
 *    - merc_daily_reset_unlock → Ejecuta reseteo diario
 * 
 * 📝 LOGS:
 *    Todas las operaciones se loguean en error_log con prefijo emoji
 *    📊 = Conteo de envíos
 *    🔍 = Búsquedas/consultas
 *    ✅ = Acciones permitidas
 *    🔒 = Bloqueos
 *    💰 = Estado financiero
 *    ⏰ = Comparaciones de hora
 *    🌅 = Reseteos
 * 
 * TESTING:
 * 1. Prueba sin envíos antes de la hora límite → Debe permitir
 * 2. Prueba sin envíos después de la hora límite → Debe permitir pero con fecha mañana
 * 3. Crea un envío y testea después de hora límite → Debe aplicar reglas financieras
 * 4. Simula que cliente debe dinero → Debe bloquearse completamente
 * 5. Simula que merc debe dinero antes de 19:30 → Debe bloquearse
 * 6. Simula que merc debe dinero después de 19:30 → Debe permitir con fecha mañana
 */

// Obtener la hora actual en formato HH:MM
function merc_get_current_time() {
    return current_time('H:i');
}

// Obtener hoy en formato Y-m-d
function merc_get_today() {
    return current_time('Y-m-d');
}

// Obtener mañana en formato d/m/Y (mismo formato que wpcargo_pickup_date_picker)
function merc_get_tomorrow_formatted() {
    $tomorrow = strtotime('+1 day', current_time('timestamp'));
    return date('d/m/Y', $tomorrow);
}

// Obtener horas límite por tipo de envío
function merc_get_time_limits($tipo) {
    $tipo_lower = strtolower(trim($tipo));
    
    if ($tipo_lower === 'express' || stripos($tipo, 'agencia') !== false) {
        return [
            'sin_envios' => '12:30',     // 12:30 PM
            'con_envios' => '13:00',     // 1:00 PM (13:00)
            'nombre' => 'EXPRESS'
        ];
    } elseif ($tipo_lower === 'normal' || stripos($tipo, 'emprendedor') !== false) {
        return [
            'sin_envios' => '10:00',     // 10:00 AM
            'con_envios' => '10:00',     // 10:00 AM
            'nombre' => 'NORMAL'
        ];
    } elseif ($tipo_lower === 'full_fitment' || stripos($tipo, 'full') !== false) {
        return [
            'sin_envios' => '11:30',     // 11:30 AM
            'con_envios' => '12:15',     // 12:15 PM
            'nombre' => 'FULL FITMENT'
        ];
    }
    
    return ['sin_envios' => '23:59', 'con_envios' => '23:59', 'nombre' => 'UNKNOWN'];
}

// Contar envíos del tipo creados hoy
function merc_count_envios_del_tipo_hoy($client_id, $tipo) {
    global $wpdb;
    
    $hoy = merc_get_today();
    $tipo_normalized = sanitize_text_field($tipo);
    
    // Normalizar el tipo
    if (stripos($tipo, 'agencia') !== false || $tipo_normalized === 'express') {
        $tipo_search = 'express';
    } elseif (stripos($tipo, 'emprendedor') !== false || $tipo_normalized === 'normal') {
        $tipo_search = 'normal';
    } elseif (stripos($tipo, 'full') !== false) {
        $tipo_search = 'full_fitment';
    } else {
        $tipo_search = $tipo_normalized;
    }
    
    // Buscar envíos donde el cliente puede estar en 'registered_shipper', 'wpcargo_customer_id' o como autor del post
    $tipo_cond = '';
    $params = array($client_id, $client_id, $client_id, $hoy);
    if (!empty($tipo_search)) {
        $tipo_cond = " AND LOWER(pm_type.meta_value) = %s ";
        $params[] = strtolower($tipo_search);
    }

    $sql = "SELECT COUNT(*) as total
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = 'wpcargo_customer_id'
        LEFT JOIN {$wpdb->postmeta} pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = 'wpcargo_type_of_shipment'
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND (pm_shipper.meta_value = %d OR pm_customer.meta_value = %d OR p.post_author = %d)
        AND DATE(p.post_date) = %s " . $tipo_cond;

    $query = $wpdb->prepare($sql, ...$params);
    
    $result = $wpdb->get_row($query);
    $total = isset($result->total) ? intval($result->total) : 0;
    
    error_log("📊 [CONTAR ENVÍOS] Cliente {$client_id} | Tipo: {$tipo_search} | Hoy: {$hoy} | Total: {$total}");
    
    return $total;
}

// Determinar estado financiero: 'merc_debe' | 'cliente_debe' | 'balance_cero' | 'desconocido'
function merc_get_estado_financiero($client_id) {
    // Aquí tú tienes la fuente de verdad del estado financiero
    // Por ahora, asumo que está en user_meta. Ajusta según tu estructura.
    
    // OPCIÓN 1: Si está guardado en un meta
    $estado_financiero = get_user_meta($client_id, 'merc_estado_financiero', true);
    
    if ($estado_financiero === 'merc_debe') {
        return ['estado' => 'merc_debe', 'descripcion' => 'Merc le debe al cliente'];
    } elseif ($estado_financiero === 'cliente_debe') {
        return ['estado' => 'cliente_debe', 'descripcion' => 'Cliente le debe a Merc'];
    } elseif ($estado_financiero === 'balance_cero' || empty($estado_financiero)) {
        return ['estado' => 'balance_cero', 'descripcion' => 'Balance equilibrado'];
    }
    
    // OPCIÓN 2: Si quieres calcular desde totales de shipments (descomentar si aplica)
    // global $wpdb;
    // $saldo = $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(balance), 0) FROM {$wpdb->prefix}client_financials WHERE client_id = %d", $client_id));
    // if ($saldo > 0) return ['estado' => 'merc_debe', 'descripcion' => 'Merc le debe'];
    // if ($saldo < 0) return ['estado' => 'cliente_debe', 'descripcion' => 'Cliente le debe'];
    
    return ['estado' => 'balance_cero', 'descripcion' => 'Sin estado financiero definido'];
}

// Verificar si está en hora de desbloqueo parcial (después de 19:30)
function merc_is_after_unlock_time() {
    $current_time = merc_get_current_time();
    return $current_time >= '19:30';
}

// NUEVA FUNCIÓN PRINCIPAL DE BLOQUEO
function merc_check_tipo_envio_blocked($client_id, $tipo_envio) {
    $hoy = merc_get_today();
    $now = merc_get_current_time();
    $limits = merc_get_time_limits($tipo_envio);
    
    error_log("═══════════════════════════════════════════════════════════════");
    error_log("🔍 NUEVA LÓGICA DE BLOQUEOS - Cliente: {$client_id} | Tipo: {$tipo_envio} | Ahora: {$now}");
    error_log("Límites: SIN={$limits['sin_envios']} | CON={$limits['con_envios']}");
    
    // Contar envíos de este tipo creados hoy
    $cuenta_envios = merc_count_envios_del_tipo_hoy($client_id, $tipo_envio);
    error_log("📊 Envíos de tipo {$limits['nombre']} creados hoy: {$cuenta_envios}");
    
    // ===== ESCENARIO 1: SIN ENVÍOS CREADOS =====
    if ($cuenta_envios == 0) {
        error_log("📌 ESCENARIO 1: SIN ENVÍOS");
        
        $hora_limite_sin_envios = $limits['sin_envios'];
        error_log("   Comparando: {$now} vs {$hora_limite_sin_envios}");
        
        if ($now < $hora_limite_sin_envios) {
            // Aún no llegó la hora límite → PERMITIR
            error_log("   ✅ AÚN NO PASÓ LA HORA LÍMITE → PERMITIR CREACIÓN");
            return false;
        } else {
            // Ya pasó la hora límite → PERMITIR pero guardar que necesita fecha mañana
            error_log("   ✅ PASÓ LA HORA LÍMITE → PERMITIR CON FECHA MAÑANA");
            
            // Guardar que este cliente debe usar fecha mañana para este tipo
            $meta_key = "merc_desbloqueo_parcial_{$limits['nombre']}";
            update_user_meta($client_id, $meta_key, [
                'tipo' => $tipo_envio,
                'fecha' => $hoy,
                'razon' => 'sin_envios_pasada_hora',
                'fecha_asignada' => merc_get_tomorrow_formatted()
            ]);
            
            update_user_meta($client_id, 'merc_force_pickup_date', merc_get_tomorrow_formatted());
            
            return false; // NO BLOQUEAR, pero hay que manipular el campo de fecha
        }
    }
    
    // ===== ESCENARIO 2: CON ENVÍOS CREADOS =====
    error_log("📌 ESCENARIO 2: CON ENVÍOS ({$cuenta_envios})");
    
    $hora_limite_con_envios = $limits['con_envios'];
    error_log("   Comparando: {$now} vs {$hora_limite_con_envios}");
    
    if ($now < $hora_limite_con_envios) {
        // Aún no llegó la hora límite → PERMITIR normalmente
        error_log("   ✅ AÚN NO PASÓ LA HORA LÍMITE → PERMITIR");
        return false;
    }
    
    // YA PASÓ LA HORA LÍMITE → Evaluar estado financiero
    error_log("   ⏰ YA PASÓ LA HORA LÍMITE ({$hora_limite_con_envios})");
    
    $financiero = merc_get_estado_financiero($client_id);
    error_log("   💰 Estado financiero: {$financiero['estado']} - {$financiero['descripcion']}");
    
    // --- CASO 1: CLIENTE LE DEBE A MERC → BLOQUEADO completamente ---
    if ($financiero['estado'] === 'cliente_debe') {
        error_log("   🔒 CLIENTE DEBE A MERC → BLOQUEADO EN LOS 3 TIPOS");
        error_log("   ❌ BLOQUEO TOTAL HASTA QUE LIQUIDE");
        return true; // BLOQUEADO
    }
    
    // --- CASO 2: MERC LE DEBE AL CLIENTE → BLOQUEAR hasta 19:30 ---
    if ($financiero['estado'] === 'merc_debe') {
        if (merc_is_after_unlock_time()) {
            // Ya pasaron las 19:30 → PERMITIR pero con fecha mañana
            error_log("   🔓 MERC DEBE + PASARON LAS 19:30 → PERMITIR CON FECHA MAÑANA");
            
            $meta_key = "merc_desbloqueo_parcial_{$limits['nombre']}";
            update_user_meta($client_id, $meta_key, [
                'tipo' => $tipo_envio,
                'fecha' => $hoy,
                'razon' => 'merc_debe_post_1930',
                'fecha_asignada' => merc_get_tomorrow_formatted()
            ]);
            
            update_user_meta($client_id, 'merc_force_pickup_date', merc_get_tomorrow_formatted());
            
            return false; // PERMITIR
        } else {
            // Aún no son las 19:30 → BLOQUEADO
            error_log("   🔒 MERC DEBE + AÚN NO SON LAS 19:30 → BLOQUEADO");
            error_log("   En {$now}, se desbloquea a las 19:30");
            return true; // BLOQUEADO
        }
    }
    
    // --- CASO 3: BALANCE = 0 → PERMITIR con fecha mañana ---
    if ($financiero['estado'] === 'balance_cero') {
        error_log("   ✅ BALANCE CERO → PERMITIR CON FECHA MAÑANA");
        
        $meta_key = "merc_desbloqueo_parcial_{$limits['nombre']}";
        update_user_meta($client_id, $meta_key, [
            'tipo' => $tipo_envio,
            'fecha' => $hoy,
            'razon' => 'balance_cero',
            'fecha_asignada' => merc_get_tomorrow_formatted()
        ]);
        
        update_user_meta($client_id, 'merc_force_pickup_date', merc_get_tomorrow_formatted());
        
        return false; // PERMITIR
    }
    
    error_log("═══════════════════════════════════════════════════════════════");
    error_log("   ⚠️ Caso no manejado → Por defecto PERMITIR");
    return false;
}

/**
 * AJAX: Obtener info de desbloqueo parcial (para manipular fecha en frontend)
 */
add_action('wp_ajax_nopriv_merc_get_partial_unlock_info', 'merc_ajax_get_partial_unlock_info');
add_action('wp_ajax_merc_get_partial_unlock_info', 'merc_ajax_get_partial_unlock_info');

function merc_ajax_get_partial_unlock_info() {
    $user = wp_get_current_user();
    
    if (!$user->ID) {
        wp_send_json_error(['message' => 'User not logged in']);
        wp_die();
    }
    
    $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
    
    if (empty($tipo)) {
        wp_send_json_error(['message' => 'Tipo de envío no especificado']);
        wp_die();
    }
    
    $limits = merc_get_time_limits($tipo);
    $meta_key = "merc_desbloqueo_parcial_{$limits['nombre']}";
    
    $unlock_info = get_user_meta($user->ID, $meta_key, true);
    $force_date = get_user_meta($user->ID, 'merc_force_pickup_date', true);
    
    error_log("🔍 [AJAX] Consultando desbloqueo parcial para {$limits['nombre']}");
    error_log("   Meta key: {$meta_key}");
    error_log("   Unlock info: " . json_encode($unlock_info));
    error_log("   Force date: {$force_date}");
    
    wp_send_json_success([
        'tipo' => $tipo,
        'has_partial_unlock' => !empty($unlock_info),
        'unlock_info' => $unlock_info,
        'forced_date' => $force_date,
        'now' => merc_get_current_time(),
        'date_format' => 'd/m/Y'
    ]);
    wp_die();
}

/**
 * RESETEO DIARIO: Limpiar meta de desbloqueos parciales
 */
add_action('wp_ajax_nopriv_merc_daily_reset_unlock', 'merc_daily_reset_unlock');
add_action('wp_ajax_merc_daily_reset_unlock', 'merc_daily_reset_unlock');

function merc_daily_reset_unlock() {
    $user = wp_get_current_user();
    
    if (!$user->ID) {
        wp_send_json_error(['message' => 'User not logged in']);
        wp_die();
    }
    
    $hoy = merc_get_today();
    // Limpiar desbloqueos parciales del día anterior
    delete_user_meta($user->ID, 'merc_force_pickup_date');
    delete_user_meta($user->ID, 'merc_desbloqueo_parcial_EXPRESS');
    delete_user_meta($user->ID, 'merc_desbloqueo_parcial_NORMAL');
    delete_user_meta($user->ID, 'merc_desbloqueo_parcial_FULL FITMENT');
    
    error_log("🔄 [RESETEO DIARIO] Usuario {$user->ID} | Fecha: {$hoy}");
    
    wp_send_json_success(['message' => 'Desbloqueos parciales reseteados']);
    wp_die();
}

/**
 * Hooks para reseteo automático diario (alternativa)
 * Ejecutar a las 00:01 cada día
 */
add_action('init', function() {
    $last_reset = get_option('merc_last_unlock_reset_date');
    $hoy = merc_get_today();
    
    if ($last_reset !== $hoy) {
        // Limpiar todos los usuarios
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'merc_desbloqueo_parcial_%'");
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'merc_force_pickup_date'");
        
        update_option('merc_last_unlock_reset_date', $hoy);
        
        error_log("🌅 [RESETEO DIARIO AUTOMÁTICO] Ejecutado para {$hoy}");
    }
}, 999);

/**
 * SCRIPT FRONTEND: Manipular date picker cuando hay desbloqueo parcial
 */
add_action('wp_footer', function() {
    if (!is_user_logged_in()) return;
    
    // Solo cargar en la página de creación de envíos
    if (!isset($_GET['wpcfe']) || $_GET['wpcfe'] !== 'add') return;
    
    $tipo = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    if (empty($tipo)) return;
    
    ?>
    <script>
    jQuery(function($) {
        console.log('📅 Script de manipulación de fecha cargado para tipo: <?php echo esc_js($tipo); ?>');
        
        // Llamar AJAX para obtener info de desbloqueo parcial
        $.ajax({
            type: 'POST',
            data: {
                action: 'merc_get_partial_unlock_info',
                tipo: '<?php echo esc_js($tipo); ?>'
            },
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            success: function(resp) {
                console.log('📋 Respuesta de desbloqueo:', resp);
                
                if (resp.success && resp.data.has_partial_unlock) {
                    const info = resp.data.unlock_info;
                    const forcedDate = resp.data.forced_date;
                    
                    console.log('⚠️ DESBLOQUEO PARCIAL ACTIVADO');
                    console.log('   Tipo: ' + info.tipo);
                    console.log('   Razón: ' + info.razon);
                    console.log('   Fecha asignada: ' + info.fecha_asignada);
                    
                    // Buscar el campo del date picker
                    const $datePicker = $('input[name="wpcargo_pickup_date_picker"], input[name="calendarenvio"]');
                    
                    if ($datePicker.length > 0) {
                        // Forzar el valor
                        $datePicker.val(forcedDate);
                        
                        // Si es un Flatpickr o similar
                        if ($datePicker[0].flatpickr) {
                            $datePicker[0].flatpickr.setDate(forcedDate, true);
                        }
                        
                        console.log('✅ Fecha forzada a: ' + forcedDate);
                        
                        // Mostrar notificación
                        Swal.fire({
                            title: '📅 Fecha de envío ajustada',
                            text: 'Por haber pasado la hora límite, la fecha de envío se ha establecido en: ' + forcedDate,
                            icon: 'info',
                            confirmButtonColor: '#3498db'
                        });
                    } else {
                        console.warn('⚠️ Campo date picker no encontrado');
                    }
                } else {
                    console.log('✅ Sin desbloqueos parciales, operación normal');
                }
            },
            error: function(err) {
                console.error('❌ Error al consultar desbloqueo:', err);
            }
        });
        
        // Llamar reseteo diario al cargar
        $.ajax({
            type: 'POST',
            data: { action: 'merc_daily_reset_unlock' },
            url: '<?php echo admin_url("admin-ajax.php"); ?>'
        });
    });
    </script>
    <?php
}, 999);

// MANTENER FUNCIÓN ANTIGUA POR RETROCOMPATIBILIDAD (pero ya no se usa)
// Función auxiliar: Verificar si un cliente puede crear envíos hoy según estados
function merc_cliente_tiene_envios_pendientes_hoy($client_id) {
    global $wpdb;
    
    // Obtener la fecha de HOY en formato Y-m-d (usando timezone de WordPress)
    $hoy = current_time('Y-m-d');
    // Verificar si el usuario fue BLOQUEADO manualmente por un administrador
    $bloqueado_manual = get_user_meta($client_id, 'merc_bloqueado_manual', true);
    if (!empty($bloqueado_manual) && $bloqueado_manual == '1') {
        error_log("🔒 BLOQUEO MANUAL ACTIVO: Cliente #{$client_id} bloqueado por admin");
        return true; // Está bloqueado
    }
    
    // Verificar si el usuario fue desbloqueado manualmente hoy y aún tiene envíos permitidos
    $desbloqueo_manual_fecha = get_user_meta($client_id, 'merc_desbloqueado_manualmente_fecha', true);
    $envios_permitidos = intval(get_user_meta($client_id, 'merc_desbloqueo_manual_envios_permitidos', true));
    
    if ($desbloqueo_manual_fecha === $hoy && $envios_permitidos > 0) {
        error_log("🔓 DESBLOQUEO MANUAL ACTIVO: Cliente #{$client_id} puede crear {$envios_permitidos} envío(s) más hoy");
        return false; // No está bloqueado - tiene desbloqueo manual activo
    } elseif ($desbloqueo_manual_fecha === $hoy && $envios_permitidos <= 0) {
        error_log("🔒 DESBLOQUEO MANUAL AGOTADO: Cliente #{$client_id} ya usó su envío permitido");
        // Continuar con la verificación normal de bloqueo
    }
    
    // Log para debugging
    error_log("=====================================");
    error_log("🔍 VERIFICANDO BLOQUEO - Cliente: #{$client_id} | Fecha: {$hoy}");
    error_log("URL actual: " . $_SERVER['REQUEST_URI']);
    error_log("=====================================");
    
    // Obtener TODOS los envíos del cliente con su fecha reprogramada (si existe)
    $query = $wpdb->prepare("
        SELECT 
            p.ID, 
            p.post_date,
            pm_status.meta_value as estado,
            pm_pago.meta_value as estado_pago,
            pm_fecha_reprog.meta_value as fecha_reprogramada
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_shipper 
            ON p.ID = pm_shipper.post_id 
            AND pm_shipper.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_status 
            ON p.ID = pm_status.post_id 
            AND pm_status.meta_key = 'wpcargo_status'
        LEFT JOIN {$wpdb->postmeta} pm_pago 
            ON p.ID = pm_pago.post_id 
            AND pm_pago.meta_key = 'wpcargo_estado_pago_remitente'
        LEFT JOIN {$wpdb->postmeta} pm_fecha_reprog 
            ON p.ID = pm_fecha_reprog.post_id 
            AND pm_fecha_reprog.meta_key = 'wpcargo_pickup_date_picker'
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_shipper.meta_value = %s
    ", $client_id);
    
    error_log("🔎 SQL Query: " . $query);
    $envios_hoy = $wpdb->get_results($query);
    error_log("📊 Total envíos del cliente (sin filtrar): " . count($envios_hoy));
    
    // Filtrar solo los envíos que son de HOY (considerando fecha reprogramada)
    $envios_filtrados = array();
    foreach ($envios_hoy as $envio) {
        $fecha_creacion_raw = $envio->post_date;
        $fecha_reprog_raw = $envio->fecha_reprogramada;
        
        // Usar fecha reprogramada si existe, sino usar post_date
        if (!empty($envio->fecha_reprogramada)) {
            // La fecha reprogramada puede venir en diferentes formatos
            // Intentar parsear con diferentes formatos comunes
            $fecha_reprog_str = trim($envio->fecha_reprogramada);
            
            // Detectar si viene en formato d/m/Y o Y-m-d
            if (strpos($fecha_reprog_str, '/') !== false) {
                // Formato con slash: puede ser d/m/Y o m/d/Y
                $partes = explode('/', $fecha_reprog_str);
                if (count($partes) === 3) {
                    // Si el tercer elemento tiene 4 dígitos, es el año
                    if (strlen($partes[2]) === 4) {
                        // Asumir formato d/m/Y (día/mes/año) para fechas con año de 4 dígitos
                        $fecha_obj = DateTime::createFromFormat('d/m/Y', $fecha_reprog_str);
                        if ($fecha_obj !== false) {
                            $fecha_efectiva = $fecha_obj->format('Y-m-d');
                        } else {
                            // Fallback a strtotime
                            $fecha_efectiva = date('Y-m-d', strtotime($fecha_reprog_str));
                        }
                    } else {
                        $fecha_efectiva = date('Y-m-d', strtotime($fecha_reprog_str));
                    }
                } else {
                    $fecha_efectiva = date('Y-m-d', strtotime($fecha_reprog_str));
                }
            } else {
                // Formato sin slash (probablemente Y-m-d o timestamp)
                $fecha_efectiva = date('Y-m-d', strtotime($fecha_reprog_str));
            }
        } else {
            $fecha_efectiva = date('Y-m-d', strtotime($envio->post_date));
        }
        
        error_log("   📅 Envío #{$envio->ID}: post_date_raw='{$fecha_creacion_raw}' | fecha_reprog_raw='{$fecha_reprog_raw}' | fecha_efectiva='{$fecha_efectiva}' | hoy='{$hoy}'");
        
        // Solo incluir si la fecha efectiva es HOY
        if ($fecha_efectiva === $hoy) {
            $envio->fecha_efectiva = $fecha_efectiva;
            $envios_filtrados[] = $envio;
            error_log("      ✅ INCLUIDO en filtrados");
        } else {
            error_log("      ❌ NO incluido (fecha no coincide)");
        }
    }
    
    error_log("📦 Total envíos encontrados para HOY: " . count($envios_filtrados));
    
    // Si no hay envíos hoy, puede crear
    if (empty($envios_filtrados)) {
        error_log("✅ PERMITIDO: Cliente #{$client_id} sin envíos hoy - Puede crear");
        error_log("=====================================");
        return false;
    }
    
    $total_envios = count($envios_filtrados);
    $count_recogidos = 0;
    $count_no_recogidos = 0;
    $count_entregados_sin_pagar = 0;
    $count_finales = 0;
    $count_otros = 0;
    
    // Contar estados
    foreach ($envios_filtrados as $envio) {
        $estado_lower = strtolower(trim($envio->estado));
        $estado_pago_lower = strtolower(trim($envio->estado_pago));
            // Si el envío está vinculado a una liquidación verificada, considerarlo final
            $liq_verified = false;
            try {
                if ( function_exists('merc_is_shipment_liquidation_verified') ) {
                    $liq_verified = merc_is_shipment_liquidation_verified( $envio->ID );
                }
            } catch ( Exception $e ) { $liq_verified = false; }
        $fecha_reprog = !empty($envio->fecha_reprogramada) ? date('Y-m-d', strtotime($envio->fecha_reprogramada)) : 'N/A';
        $fecha_creacion = date('Y-m-d', strtotime($envio->post_date));
        
        error_log("   📅 Envío #{$envio->ID}: Creado={$fecha_creacion} | Reprog={$fecha_reprog} | Efectiva={$envio->fecha_efectiva} | Estado={$estado_lower} | Pago={$estado_pago_lower}");
        
        // Un envío está FINALIZADO (desbloquea) si:
        // - Estado es ENTREGADO y además está PAGADO (sin deudas)
        // - Estado es REPROGRAMADO
        // - Estado es ANULADO
        $es_final = false;

        // Si la liquidación fue verificada, forzar que sea final
        if ( $liq_verified ) {
            $es_final = true;
            error_log("   Envío #{$envio->ID}: marcado como FINAL por liquidación verificada (liq)");
            $count_finales++;
            continue; // siguiente envío
        }
        
        if ($estado_lower === 'entregado') {
            // ENTREGADO solo cuenta como final si está pagado
            if ($estado_pago_lower === 'pagado' || $estado_pago_lower === 'paid') {
                $es_final = true;
                error_log("   Envío #{$envio->ID}: ENTREGADO y PAGADO → Final");
            } else {
                // ENTREGADO sin pagar también bloquea
                $count_entregados_sin_pagar++;
                error_log("   Envío #{$envio->ID}: ENTREGADO pero NO pagado (estado_pago: {$estado_pago_lower}) → Bloquea");
                continue; // Saltar a la siguiente iteración
            }
        } elseif ($estado_lower === 'reprogramado' || $estado_lower === 'anulado') {
            $es_final = true;
            error_log("   Envío #{$envio->ID}: {$estado_lower} → Final");
        } elseif ($estado_lower === 'no recogido') {
            // NO RECOGIDO bloquea (no es final, genera penalización)
            $count_no_recogidos++;
            error_log("   Envío #{$envio->ID}: NO RECOGIDO → Bloquea (penalización de S/. 5.00)");
            continue; // Saltar a la siguiente iteración
        }
        
        if ($es_final) {
            $count_finales++;
        } elseif ($estado_lower === 'recogido') {
            $count_recogidos++;
            error_log("   Envío #{$envio->ID}: RECOGIDO → En tránsito");
        } else {
            $count_otros++;
            error_log("   Envío #{$envio->ID}: {$estado_lower} → Pendiente de recoger");
        }
    }
    
    error_log("📊 RESUMEN - Total: {$total_envios} | Finales: {$count_finales} | Recogidos: {$count_recogidos} | Entregados sin pagar: {$count_entregados_sin_pagar} | No Recogidos: {$count_no_recogidos} | Otros: {$count_otros}");
    
    // LÓGICA DE BLOQUEO CORREGIDA:
    // Una vez que CUALQUIER envío del día pase a RECOGIDO o posterior (sin estar finalizado),
    // se bloquea hasta que TODOS los envíos del día estén finalizados
    
    // 1. Si NO hay ningún envío en RECOGIDO, NO RECOGIDO o ENTREGADO sin pagar → PUEDE CREAR
    //    (todos están en estados previos o ya finalizados)
    if ($count_recogidos === 0 && $count_no_recogidos === 0 && $count_entregados_sin_pagar === 0) {
        // Todos están en estados previos a RECOGIDO o ya finalizados
        if ($count_finales === $total_envios || $count_otros > 0) {
            error_log("✅ PERMITIDO: No hay envíos recogidos aún, o todos están finalizados - Puede crear más");
            error_log("=====================================");
            return false;
        }
    }
    
    // 2. Si hay envíos en RECOGIDO, NO RECOGIDO o ENTREGADO sin pagar → BLOQUEADO
    //    hasta que TODOS los envíos del día estén finalizados
    if ($count_recogidos > 0 || $count_no_recogidos > 0 || $count_entregados_sin_pagar > 0) {
        // Si TODOS están finalizados, puede crear para mañana
        if ($count_finales === $total_envios) {
            error_log("✅ PERMITIDO: Todos los envíos finalizados ({$count_finales}/{$total_envios}) - Puede crear para mañana");
            error_log("=====================================");
            return false;
        }
        
        // Si NO todos están finalizados, está bloqueado
        error_log("🔴 BLOQUEADO: Hay envíos en proceso - Recogidos: {$count_recogidos}, Entregados sin pagar: {$count_entregados_sin_pagar}, No Recogidos: {$count_no_recogidos}");
        error_log("   Se necesita que TODOS los {$total_envios} envíos del día estén finalizados (actualmente: {$count_finales} finalizados)");
        error_log("=====================================");
        return true;
    }
    
    // Caso por defecto: permitir
    error_log("✅ PERMITIDO: Caso por defecto");
    error_log("=====================================");
    return false;
}

// Decrementar contador de desbloqueo manual al crear un envío
add_action('wpcfe_after_save_add_shipment', 'merc_decrementar_desbloqueo_manual', 10);
add_action('save_post_wpcargo_shipment', 'merc_decrementar_desbloqueo_manual', 10, 3);
add_action('wpcargo_after_save_shipment', 'merc_decrementar_desbloqueo_manual', 10);
function merc_decrementar_desbloqueo_manual($shipment_id, $post = null, $update = null) {
    // Si viene de save_post, asegurarse que es un post publicado y no es autosave
    if ($post !== null) {
        if (wp_is_post_autosave($shipment_id) || wp_is_post_revision($shipment_id)) {
            return;
        }
        if (get_post_status($shipment_id) !== 'publish') {
            return;
        }
    }
    
    // Evitar ejecución múltiple con un flag de transient
    $transient_key = 'merc_decrement_' . $shipment_id;
    if (get_transient($transient_key)) {
        error_log("⚠️ DESBLOQUEO: Ya procesado para envío #{$shipment_id}, evitando duplicación");
        return;
    }
    set_transient($transient_key, true, 60); // Bloquear por 60 segundos
    
    // Obtener el ID del cliente (shipper) del envío
    $client_id = get_post_meta($shipment_id, 'registered_shipper', true);
    
    // Si no hay cliente registrado, intentar con el usuario actual
    if (empty($client_id)) {
        $client_id = get_current_user_id();
    }
    
    // Si aún no hay cliente, salir
    if (empty($client_id)) {
        error_log("❌ DESBLOQUEO: No se pudo obtener el ID del cliente para envío #{$shipment_id}");
        return;
    }
    
    $hoy = current_time('Y-m-d');
    
    // Verificar si tiene desbloqueo manual activo
    $desbloqueo_manual_fecha = get_user_meta($client_id, 'merc_desbloqueado_manualmente_fecha', true);
    $envios_permitidos = intval(get_user_meta($client_id, 'merc_desbloqueo_manual_envios_permitidos', true));
    
    error_log("🔍 VERIFICANDO DECREMENTO: Cliente #{$client_id} | Envío #{$shipment_id} | Fecha desbloqueo: {$desbloqueo_manual_fecha} | Hoy: {$hoy} | Envíos permitidos: {$envios_permitidos}");
    
    if ($desbloqueo_manual_fecha === $hoy && $envios_permitidos > 0) {
        // Decrementar el contador
        $nuevos_envios_permitidos = $envios_permitidos - 1;
        update_user_meta($client_id, 'merc_desbloqueo_manual_envios_permitidos', $nuevos_envios_permitidos);
        
        error_log("🔽 DESBLOQUEO MANUAL: Cliente #{$client_id} creó envío #{$shipment_id}. Envíos restantes: {$nuevos_envios_permitidos}");
        
        if ($nuevos_envios_permitidos <= 0) {
            error_log("🔒 DESBLOQUEO MANUAL AGOTADO: Cliente #{$client_id} vuelve a estar bloqueado");
        }
    } else {
        error_log("❌ NO SE DECREMENTA: Desbloqueo no activo o sin envíos permitidos");
    }
}

// Asignar automáticamente el estado según el tipo de servicio
add_action('wpcfe_after_save_add_shipment', 'merc_asignar_estado_segun_tipo_servicio', 5);
add_action('save_post_wpcargo_shipment', 'merc_asignar_estado_segun_tipo_servicio_post', 11, 3);
// Asegurar que el total a cobrar se guarde como decimal (2 decimales) tras crear/guardar envío
add_action('wpcfe_after_save_add_shipment', 'merc_force_decimal_total_on_save', 1);
add_action('save_post_wpcargo_shipment', 'merc_force_decimal_total_on_save_post', 20, 3);
// Hook para asignar unidades de full fitment
add_action('save_post_wpcargo_shipment', 'merc_asignar_unidades_full_fitment', 12, 2);
// Hook adicional para forzar el estado después de todo
add_action('save_post_wpcargo_shipment', 'merc_forzar_estado_por_tipo', 13, 3);
add_action('wp_footer', 'merc_cambiar_estado_en_frontend');

/**
 * Asignar unidades de producto a envío full fitment
 * Se ejecuta despúes de merc_guardar_envio_producto() pero antes de forzar estado
 */
function merc_asignar_unidades_full_fitment($post_id, $post) {
    // No procesar en autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        error_log("🔄 Full Fitment #{$post_id} - SKIPPED (autosave)");
        return;
    }
    
    // Obtener tipo de envío: primero desde REQUEST, luego desde meta
    $tipo_envio = '';
    if (isset($_REQUEST['type'])) {
        $tipo_envio = strtolower(sanitize_text_field($_REQUEST['type']));
    } else {
        $tipo_envio_raw = get_post_meta($post_id, 'wpcargo_type_of_shipment', true);
        $tipo_envio = strtolower($tipo_envio_raw);
    }
    
    $es_full_fitment = (strpos($tipo_envio, 'full') !== false || strpos($tipo_envio, 'fitment') !== false || $tipo_envio === 'full_fitment');
    
    error_log("🔍 Full Fitment verificación #{$post_id} - Tipo: '{$tipo_envio}' -> Es full fitment: " . ($es_full_fitment ? 'SI' : 'NO'));
    
    if (!$es_full_fitment) {
        error_log("⏭️ Full Fitment #{$post_id} - NO es full fitment, saltando");
        return;
    }
    
    // Verificar si ya hay unidades asignadas
    $unidades_asignadas = get_post_meta($post_id, '_merc_producto_unidades', true);
    if (!empty($unidades_asignadas) && is_array($unidades_asignadas)) {
        error_log("✅ Full Fitment #{$post_id} - Unidades ya asignadas: " . count($unidades_asignadas) . " [" . implode(',', $unidades_asignadas) . "]");
        return;
    }
    
    // Obtener producto y cantidad
    $producto_id = intval(get_post_meta($post_id, '_merc_producto_id', true));
    $cantidad = intval(get_post_meta($post_id, '_merc_producto_cantidad', true));
    
    // Si no hay cantidad pero hay producto seleccionado, intentar obtenerla desde POST
    if ($cantidad === 0 && $producto_id > 0) {
        $cantidad = isset($_POST['merc_producto_cantidad']) ? intval($_POST['merc_producto_cantidad']) : 1;
        error_log("📝 Full Fitment #{$post_id} - Cantidad obtenida desde POST: {$cantidad}");
    }
    
    error_log("📦 Full Fitment #{$post_id} - Datos: Producto={$producto_id}, Cantidad={$cantidad}");
    
    if ($producto_id <= 0 || $cantidad <= 0) {
        error_log("⚠️ Full Fitment #{$post_id} - NO SE PROCESÓ: Producto o cantidad inválidos");
        return;
    }
    
    // Verificar stock disponible
    $stock_disponible = merc_get_product_stock($producto_id);
    $stock_disponible = intval($stock_disponible);
    
    error_log("📊 Full Fitment #{$post_id} - Stock disponible: {$stock_disponible} unidades");
    
    if ($cantidad > $stock_disponible) {
        error_log("❌ Full Fitment #{$post_id} - STOCK INSUFICIENTE: Solicitado {$cantidad}, Disponible {$stock_disponible}");
        return;
    }
    
    // Asignar unidades
    error_log("🚀 Full Fitment #{$post_id} - ASIGNANDO {$cantidad} unidades del producto #{$producto_id} al envío");
    $assigned_units = merc_assign_stock_units($producto_id, $cantidad, $post_id);
    
    if ($assigned_units === false) {
        error_log("❌ Full Fitment #{$post_id} - merc_assign_stock_units() retornó FALSE");
        return;
    }
    
    if (empty($assigned_units)) {
        error_log("❌ Full Fitment #{$post_id} - merc_assign_stock_units() retornó array vacío");
        return;
    }
    
    // Guardar unidades asignadas
    update_post_meta($post_id, '_merc_producto_unidades', $assigned_units);
    error_log("✅ Full Fitment #{$post_id} - Unidades ASIGNADAS EXITOSAMENTE: " . implode(',', $assigned_units) . " (Total: " . count($assigned_units) . ")");
}

/**
 * Forzar que `wpcargo_total_cobrar` se guarde como decimal (2 decimales)
 * Hook para el flujo frontend (wpcfe_after_save_add_shipment)
 */
function merc_force_decimal_total_on_save( $shipment_id ) {
    if ( empty( $shipment_id ) ) return;

    // Preferir valor enviado por POST si existe
    $val = null;
    if ( isset( $_POST['wpcargo_total_cobrar'] ) ) {
        $val = is_string( $_POST['wpcargo_total_cobrar'] ) ? $_POST['wpcargo_total_cobrar'] : '';
    }

    // Si no vino en POST, intentar leer el campo oculto que pudo haberse poblado por JS
    if ( ( $val === null || $val === '' ) && isset( $_REQUEST['wpcargo_total_cobrar'] ) ) {
        $val = is_string( $_REQUEST['wpcargo_total_cobrar'] ) ? $_REQUEST['wpcargo_total_cobrar'] : '';
    }

    // Si aún no hay valor, leer meta actual y reescribir en formato decimal
    if ( $val === null || $val === '' ) {
        $meta = get_post_meta( $shipment_id, 'wpcargo_total_cobrar', true );
        if ( $meta !== null && $meta !== '' ) {
            $num = floatval( str_replace(',', '.', $meta) );
        } else {
            return;
        }
    } else {
        // Normalizar: quitar separadores de miles y usar punto decimal
        $clean = str_replace( array(',',' '), array('',''), $val );
        $clean = str_replace( ',', '.', $clean );
        $num = floatval( $clean );
    }

    // Formatear con 2 decimales y guardar
    $formatted = number_format( $num, 2, '.', '' );
    update_post_meta( $shipment_id, 'wpcargo_total_cobrar', $formatted );
    error_log("✅ [MERC_FORCE_DECIMAL] Shipment #{$shipment_id} wpcargo_total_cobrar forced to {$formatted}");
}

/**
 * Versión para el hook save_post (recibe $post_id, $post, $update)
 */
function merc_force_decimal_total_on_save_post( $post_id, $post = null, $update = false ) {
    // Solo actuar sobre post_type correcto
    if ( ! $post || $post->post_type !== 'wpcargo_shipment' ) return;
    // Evitar autosave/revision
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    merc_force_decimal_total_on_save( $post_id );
}

function merc_asignar_estado_segun_tipo_servicio($shipment_id) {
    error_log("📌 [wpcfe_after_save_add_shipment] Ejecutando para envío #{$shipment_id}");
    merc_asignar_estado_segun_tipo_servicio_post($shipment_id, null, false);
}

function merc_asignar_estado_segun_tipo_servicio_post($post_id, $post = null, $update = false) {
    error_log("🔍 ASIGNACIÓN ESTADO - Iniciando para envío #{$post_id}");
    
    // Si estamos en el flujo de edición frontend (wpcfe=update), no sobrescribimos el estado
    if ( isset($_REQUEST['wpcfe']) && sanitize_text_field($_REQUEST['wpcfe']) === 'update' ) {
        error_log("🔧 SKIP ASIGNACIÓN - modo frontend update para envío #{$post_id}");
        return;
    }
    
    // PRIMERO: Asegurar que el tipo está guardado en meta
    $tipo_envio = get_post_meta($post_id, 'tipo_envio', true);
    
    // Si no está en meta, intentar obtenerlo de REQUEST/POST AHORA
    if (empty($tipo_envio)) {
        if (isset($_REQUEST['type'])) {
            $tipo_envio = sanitize_text_field($_REQUEST['type']);
            error_log("   ⚠️ Tipo NO estaba en meta, obtenido desde REQUEST[type]: {$tipo_envio}");
            // Guardarlo en meta ahora para futuras referencias
            update_post_meta($post_id, 'tipo_envio', $tipo_envio);
            error_log("   📌 Guardado en meta para próximas referencias");
        } elseif (isset($_POST['tipo_envio'])) {
            $tipo_envio = sanitize_text_field($_POST['tipo_envio']);
            error_log("   ⚠️ Tipo NO estaba en meta, obtenido desde POST[tipo_envio]: {$tipo_envio}");
            update_post_meta($post_id, 'tipo_envio', $tipo_envio);
            error_log("   📌 Guardado en meta para próximas referencias");
        }
    } else {
        error_log("   ✅ Tipo ya estaba en meta: {$tipo_envio}");
    }
    
    if (empty($tipo_envio)) {
        error_log("❌ ASIGNACIÓN ESTADO - Tipo de envío vacío, abortando");
        return;
    }
    
    // Normalizar el tipo
    $tipo_lower = strtolower(trim($tipo_envio));
    error_log("   Tipo normalizado: '{$tipo_lower}'");
    
    // PROTECCIÓN: Si el envío ya tiene RECEPCIONADO (estado para express/agencia/full), no cambiar
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    if ($estado_actual === 'RECEPCIONADO') {
        error_log("⏭️ ESTADO PROTEGIDO - Envío ya tiene RECEPCIONADO, omitiendo sobrescritura");
        return;
    }
    
    // MERC AGENCIA (express) o FULL FITMENT → RECEPCIONADO
    if ($tipo_lower === 'express' || 
        stripos($tipo_envio, 'agencia') !== false || 
        $tipo_lower === 'full_fitment' || 
        stripos($tipo_envio, 'full fitment') !== false) {
		
        $estado_antes = get_post_meta($post_id, 'wpcargo_status', true);
        update_post_meta($post_id, 'wpcargo_status', 'RECEPCIONADO');
        $estado_despues = get_post_meta($post_id, 'wpcargo_status', true);
        error_log("✅ ASIGNACIÓN ESTADO - AGENCIA/FULL detectado: '{$estado_antes}' → 'RECEPCIONADO' (verificado: '{$estado_despues}')");
    }
    // MERC EMPRENDEDOR (normal) → usar primer estado de recojo
    elseif ($tipo_lower === 'normal' || stripos($tipo_envio, 'emprendedor') !== false) {
        error_log("📦 MERC EMPRENDEDOR detectado");
        $estados_recojo = get_option('wpcpod_pickup_route_status', array());
        error_log("   Estados de recojo configurados: " . (is_array($estados_recojo) ? count($estados_recojo) : '0'));
        
        if (!empty($estados_recojo) && is_array($estados_recojo)) {
            $estado_inicial = reset($estados_recojo);
            error_log("   Primer estado de recojo: '{$estado_inicial}'");
            $estado_antes = get_post_meta($post_id, 'wpcargo_status', true);
            update_post_meta($post_id, 'wpcargo_status', $estado_inicial);
            $estado_despues = get_post_meta($post_id, 'wpcargo_status', true);
            error_log("✅ ASIGNACIÓN ESTADO - EMPRENDEDOR detectado: '{$estado_antes}' → '{$estado_inicial}' (verificado: '{$estado_despues}')");
        } else {
            error_log("⚠️ No hay estados de recojo configurados - usando por defecto 'PENDIENTE'");
            $estado_antes = get_post_meta($post_id, 'wpcargo_status', true);
            update_post_meta($post_id, 'wpcargo_status', 'PENDIENTE');
            $estado_despues = get_post_meta($post_id, 'wpcargo_status', true);
            error_log("   Cambio: '{$estado_antes}' → 'PENDIENTE' (verificado: '{$estado_despues}')");
        }
    }
}

// Cambiar el estado en el frontend cuando se selecciona el tipo
function merc_cambiar_estado_en_frontend() {
    // Solo en la página de crear envío
    if (!isset($_GET['wpcfe']) || $_GET['wpcfe'] !== 'add') {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Cuando se carga la página con type=express
        var urlParams = new URLSearchParams(window.location.search);
        var type = urlParams.get('type');
        
        if (type === 'express' || type === 'full_fitment') {
            // Cambiar el select de estado a RECEPCIONADO
            setTimeout(function() {
                // Buscar en múltiples posibilidades de selectores
                var $estadoSelect = $('select[name="wpcargo_status"]').length > 0
                    ? $('select[name="wpcargo_status"]')
                    : ($('select[name="status"]').length > 0
                        ? $('select[name="status"]')
                        : $('select.merc-estado-select'));
                        
                if ($estadoSelect.length) {
                    $estadoSelect.val('RECEPCIONADO');
                    $estadoSelect.trigger('change');
                    console.log('✅ Estado establecido a RECEPCIONADO para express/full_fitment');
                } else {
                    console.log('⚠️ No se encontró select de estado para express/full_fitment');
                }
            }, 500);
        }
    });
    </script>
    <?php
}

/**
 * Función adicional para forzar el estado correcto basado en tipo de envío
 * Se ejecuta con máxima prioridad después de que todo se haya guardado
 */
function merc_forzar_estado_por_tipo($post_id, $post = null, $update = false) {
    // Verificar que sea un envío
    if (get_post_type($post_id) !== 'wpcargo_shipment') {
        return;
    }
    // No forzar estado si la petición proviene del editor frontend (wpcfe=update)
    if ( isset($_REQUEST['wpcfe']) && sanitize_text_field($_REQUEST['wpcfe']) === 'update' ) {
        error_log("🔧 SKIP FORZAR - modo frontend update para envío #{$post_id}");
        return;
    }
    
    // Obtener el tipo de envío guardado
    $tipo_envio = get_post_meta($post_id, 'tipo_envio', true);
    if (empty($tipo_envio)) {
        return;
    }
    
    // Obtener el estado actual
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    
    // Determinar el estado correcto
    $estado_correcto = '';
    $tipo_lower = strtolower(trim($tipo_envio));
    
    if ($tipo_lower === 'express' || 
        stripos($tipo_envio, 'agencia') !== false ||
        $tipo_lower === 'full_fitment' || 
        stripos($tipo_envio, 'full fitment') !== false) {
		
        $estado_correcto = 'RECEPCIONADO';
    } elseif ($tipo_lower === 'normal' || stripos($tipo_envio, 'emprendedor') !== false) {
        $estados_recojo = get_option('wpcpod_pickup_route_status', array());
        if (!empty($estados_recojo) && is_array($estados_recojo)) {
            $estado_correcto = reset($estados_recojo);
        }
    }
    
    // Si el estado actual no es el correcto, forzar cambio
    if (!empty($estado_correcto) && $estado_actual !== $estado_correcto) {
        error_log("🔧 FORZANDO estado - Envío #{$post_id}: de '{$estado_actual}' a '{$estado_correcto}' (tipo: {$tipo_envio})");
        update_post_meta($post_id, 'wpcargo_status', $estado_correcto);
        
        // Verificar que se guardó
        $verificacion = get_post_meta($post_id, 'wpcargo_status', true);
        if ($verificacion === $estado_correcto) {
            error_log("✅ VERIFICADO - Estado '{$estado_correcto}' guardado correctamente");
        } else {
            error_log("❌ ERROR - Estado NO se guardó. Esperado: {$estado_correcto}, Obtenido: {$verificacion}");
        }
    }
}

/**
 * Verificar y forzar el estado correcto en el shutdown hook
 * Esto garantiza que se ejecute después de TODO el código de WordPress y plugins
 */
function merc_verificar_estado_final($post_id, $estado_esperado) {
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    
    if ($estado_actual !== $estado_esperado) {
        error_log("🔧 SHUTDOWN - Forzando estado final - Envío #{$post_id}: '{$estado_actual}' → '{$estado_esperado}'");
        update_post_meta($post_id, 'wpcargo_status', $estado_esperado);
        
        // Verificación final
        $verificacion = get_post_meta($post_id, 'wpcargo_status', true);
        if ($verificacion === $estado_esperado) {
            error_log("✅ SHUTDOWN - Estado '{$estado_esperado}' guardado correctamente");
        } else {
            error_log("❌ SHUTDOWN - ERROR crítico. Esperado: {$estado_esperado}, Obtenido: {$verificacion}");
        }
    } else {
        error_log("✓ SHUTDOWN - Estado ya correcto: '{$estado_esperado}' para envío #{$post_id}");
    }
}

add_filter('wpcfe_after_sidebar_menu_items', 'merc_add_financial_menu_items');
function merc_add_financial_menu_items($menu_items) {
    $current_user = wp_get_current_user();
    
    // ALMACÉN DE PRODUCTOS - Para Administradores y Clientes
    if (current_user_can('manage_options') || in_array('wpcargo_client', $current_user->roles)) {
        // Buscar página del almacén (sin logging para evitar loops)
        $almacen_page = get_page_by_path('almacen-de-productos');
        
        if (!$almacen_page) {
            // Fallback: buscar por ID conocido
            $almacen_page = get_post(3835);
        }
        
        if ($almacen_page && $almacen_page->post_status === 'publish') {
            $url = get_permalink($almacen_page->ID);
        } else {
            $url = home_url('/almacen-de-productos/');
        }
        
        $menu_items['merc-almacen-productos'] = array(
            'label' => '<i>📦</i> Almacén de Productos',
            'url' => $url,
            'permalink' => $url,
        );
    }
    
    if (in_array('wpcargo_driver', $current_user->roles)) {
        $page = get_page_by_path('panel-motorizado');
        $url = $page ? get_permalink($page->ID) : home_url('/panel-motorizado/');
        
        $menu_items['merc-panel-motorizado'] = array(
            'label' => '<i>💵</i> Finanzas Motorizado',
            'url' => $url,
            'permalink' => $url,
        );
    }
    
    if (in_array('wpcargo_client', $current_user->roles)) {
        $page = get_page_by_path('panel-cliente');
        $url = $page ? get_permalink($page->ID) : home_url('/panel-cliente/');
        
        $menu_items['merc-panel-cliente'] = array(
            'label' => '<i>💵</i> Finanzas Cliente',
            'url' => $url,
            'permalink' => $url,
        );
    }
    
    // OCULTAR SIEMPRE: SOLO Importar y Exportar (NO quitamos Envíos Masivos)
    foreach ($menu_items as $key => $item) {
        $label = isset($item['label']) ? strtolower($item['label']) : '';
        $url_item = isset($item['url']) ? $item['url'] : '';
        
        // NO remover Envíos Masivos - solo remover Importar/Exportar puros
        if (stripos($label, 'envios masivos') !== false || stripos($label, 'envíos masivos') !== false) {
            continue; // Saltar Envíos Masivos
        }
        
        // Verificar por URL para import-export (export)
        $es_export = strpos($url_item, 'type=export') !== false;
        
        // Verificar por palabras clave en el label PERO que NO sea Envios Masivos
        $es_importar = (stripos($label, 'importar') !== false || stripos($label, 'import') !== false) && 
                       stripos($label, 'envios masivos') === false;
        $es_exportar = stripos($label, 'exportar') !== false || stripos($label, 'export') !== false;
        
        if ($es_export || $es_importar || $es_exportar) {
            unset($menu_items[$key]);
        }
    }
    
    // BLOQUEO ADICIONAL: Si tiene envíos pendientes, ocultar creación de envíos
    if (in_array('wpcargo_client', $current_user->roles) && merc_cliente_tiene_envios_pendientes_hoy($current_user->ID)) {
        foreach ($menu_items as $key => $item) {
            $label = isset($item['label']) ? strtolower($item['label']) : '';
            $url_item = isset($item['url']) ? $item['url'] : '';
            
            // Verificar por URL
            $url_bloqueada = strpos($url_item, 'wpcfe=add') !== false;
            
            // Verificar por palabras clave en el label
            $label_bloqueado = (
                stripos($label, 'crear envio') !== false ||
                stripos($label, 'crear envío') !== false ||
                stripos($label, 'new shipment') !== false ||
                stripos($label, 'add shipment') !== false
            );
            
            if ($url_bloqueada || $label_bloqueado) {
                unset($menu_items[$key]);
            }
        }
    }
    
    
    if (current_user_can('administrator')) {
        $page = get_page_by_path('panel-admin');
        $url = $page ? get_permalink($page->ID) : home_url('/panel-admin/');
        
        $menu_items['merc-panel-admin'] = array(
            'label' => '<i>💵</i> Finanzas Admin',
            'url' => $url,
            'permalink' => $url,
        );
    }
    
    return $menu_items;
}

// Filtro final con máxima prioridad para remover definitivamente import/export
add_filter('wpcfe_after_sidebar_menu_items', 'merc_remover_import_export_final', 999);
function merc_remover_import_export_final($menu_items) {
    foreach ($menu_items as $key => $item) {
        $label = isset($item['label']) ? strtolower($item['label']) : '';
        $url_item = isset($item['url']) ? $item['url'] : '';
        
        // Saltar Envíos Masivos
        if (stripos($label, 'envios masivos') !== false || stripos($label, 'envíos masivos') !== false) {
            continue;
        }
        
        // Remover DEFINITIVAMENTE importar/exportar
        if (
            strpos($url_item, 'type=export') !== false ||
            strpos($url_item, 'type=import') !== false ||
            (strpos($url_item, 'import-export') !== false && strpos($url_item, 'type=import') !== false) ||
            stripos($label, 'export') !== false ||
            (stripos($label, 'import') !== false && stripos($label, 'envios masivos') === false)
        ) {
            unset($menu_items[$key]);
        }
    }
    return $menu_items;
}

// JavaScript adicional para remover del DOM en tiempo real por si queda algo
add_action('wp_footer', 'merc_js_remover_import_export', 999);
function merc_js_remover_import_export() {
    ?>
    <script>
    (function() {
        function removeImportExport() {
            const items = document.querySelectorAll('a, .list-group-item, .menu-item');
            items.forEach(item => {
                const text = (item.innerText || '').toLowerCase();
                const href = (item.getAttribute('href') || '').toLowerCase();
                
                // Si contiene export O import (pero no es envios masivos)
                if (
                    (
                        (text.includes('export') || href.includes('type=export')) ||
                        ((text.includes('import') || href.includes('type=import')) && !text.includes('envios') && !text.includes('masivo'))
                    ) && 
                    !text.includes('envios masivos') && 
                    !text.includes('envíos masivos')
                ) {
                    item.style.display = 'none';
                    item.style.visibility = 'hidden';
                    item.style.height = '0';
                    item.style.overflow = 'hidden';
                    item.style.margin = '0';
                    item.style.padding = '0';
                }
            });
        }
        
        // Ejecutar al cargar
        removeImportExport();
        
        // Ejecutar cada 500ms por si hay actualización dinámica
        setInterval(removeImportExport, 500);
    })();
    </script>
    <?php
}

// JavaScript para ocultar items del menú con múltiples estrategias
/* TEMPORALMENTE DESHABILITADO PARA REVISIÓN
add_action('wp_footer', 'merc_alerta_cliente_bloqueado');
function merc_alerta_cliente_bloqueado() {
    $current_user = wp_get_current_user();
    
    if (in_array('wpcargo_client', $current_user->roles)) {
        if (merc_cliente_tiene_envios_pendientes_hoy($current_user->ID)) {
            ?>
            <style>
                .sidebar-fixed li:has(a[href*="wpcfe=add"]),
                .sidebar-fixed li:has(a[href*="import-export"]),
                .sidebar-fixed li:has(a[href*="type=import"]) {
                    display: none !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                    height: 0 !important;
                    overflow: hidden !important;
                    pointer-events: none !important;
                }
            </style>
            <script>
            jQuery(document).ready(function($) {
                // Función para remover items bloqueados
                function removerItemsBloqueados() {
                    $('.sidebar-fixed a').each(function() {
                        var $link = $(this);
                        var texto = $link.text().toLowerCase().trim();
                        var href = $link.attr('href') || '';
                        
                        var bloqueado = texto.includes('crear envio') || 
                                       texto.includes('crear envio') ||
                                       texto.includes('new shipment') || 
                                       texto.includes('add shipment') ||
                                       texto.includes('envios masivos') ||
                                       texto.includes('envios masivos') ||
                                       texto.includes('bulk') ||
                                       texto.includes('importar') ||
                                       texto.includes('import') ||
                                       href.includes('wpcfe=add') ||
                                       href.includes('import-export') ||
                                       href.includes('type=import');
                        
                        if (bloqueado) {
                            $link.closest('li').remove();
                        }
                    });
                }
                
                // Ejecutar múltiples veces
                removerItemsBloqueados();
                setTimeout(removerItemsBloqueados, 100);
                setTimeout(removerItemsBloqueados, 300);
                setTimeout(removerItemsBloqueados, 500);
                setTimeout(removerItemsBloqueados, 1000);
                setTimeout(removerItemsBloqueados, 2000);
                
                // MutationObserver para detectar cambios en el sidebar
                var sidebar = document.querySelector('.sidebar-fixed');
                if (sidebar) {
                    var observer = new MutationObserver(function(mutations) {
                        removerItemsBloqueados();
                    });
                    
                    observer.observe(sidebar, {
                        childList: true,
                        subtree: true
                    });
                }
                
                // Intervalo continuo
                setInterval(removerItemsBloqueados, 2000);
                
                // Bloquear clicks como último recurso
                $(document).on('click', '.sidebar-fixed a', function(e) {
                    var href = $(this).attr('href') || '';
                    var texto = $(this).text().toLowerCase().trim();
                    
                    var bloqueado = texto.includes('crear envio') || 
                                   texto.includes('crear envio') ||
                                   texto.includes('envios masivos') ||
                                   texto.includes('envios masivos') ||
                                   texto.includes('import') ||
                                   href.includes('wpcfe=add') || 
                                   href.includes('import-export') || 
                                   href.includes('type=import');
                    
                    if (bloqueado) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        alert('No puedes crear nuevos envios porque todos tus envios de hoy ya fueron recogidos.\n\nDebes esperar a que se entreguen y se liquiden los pagos para crear envios del dia siguiente.');
                        return false;
                    }
                });
            });
            </script>
            <?php
        }
    }
}
*/

// ===============================
// MODULO DEVOLUCIONES
// ===============================



// ---------------------------------------------------------------------------
// NAVEGACIÓN CON DELEGACIÓN DE EVENTOS
// ---------------------------------------------------------------------------

add_action( 'wp_footer', 'merc_persistent_panel_navigation', 99999 );
function merc_persistent_panel_navigation() {
    if ( ! is_user_logged_in() ) {
        return;
    }
    ?>
    <script>
    (function($) {
        'use strict';
        var panelUrls = {
            'Finanzas Admin': '<?php echo home_url( '/panel-admin/' ); ?>',
            'Finanzas Motorizado': '<?php echo home_url( '/panel-motorizado/' ); ?>',
            'Finanzas Cliente': '<?php echo home_url( '/panel-cliente/' ); ?>'
        };
         
        $(document).on('click', '.sidebar-fixed a', function(e) {
            var $clicked = $(this);
            // ignorar enlaces que solo tienen imagen (icono)
            if ($clicked.find('img').length > 0) {
                return;
            }
            var text = $clicked.text().trim();
            var targetUrl = null;
            $.each(panelUrls, function(name, url) {
                if (text.includes(name)) {
                    targetUrl = url;
                    return false;
                }
            });
            if (targetUrl) {
                e.preventDefault();
                e.stopPropagation();
                window.location.href = targetUrl;
                return false;
            }
        });
        
        // Navegación en tarjetas del panel admin
        $(document).on('click', '.merc-stat-clickable', function() {
            var vista = $(this).data('vista');
            var currentUrl = window.location.href.split('?')[0];
            var params = new URLSearchParams(window.location.search);
            params.set('vista_detalle', vista);
            window.location.href = currentUrl + '?' + params.toString();
        });
        
        // Botón volver
        $(document).on('click', '.merc-volver-dashboard', function() {
            var currentUrl = window.location.href.split('?')[0];
            var params = new URLSearchParams(window.location.search);
            params.delete('vista_detalle');
            window.location.href = currentUrl + '?' + params.toString();
        });
        
        // Hover effect en tarjetas
        $('.merc-stat-clickable').hover(
            function() {
                $(this).css('transform', 'scale(1.05)');
            },
            function() {
                $(this).css('transform', 'scale(1)');
            }
        );
    })(jQuery);
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// PANEL ADMIN: LISTADO DE MOTORIZADOS
// ---------------------------------------------------------------------------

function merc_admin_motorizados( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_motorizado ) {
    global $wpdb;
    
    // Construir filtro de fecha usando SOLO wpcargo_pickup_date_picker (sin OR con post_date)
    $pickup_date_filter = '';
    if ( ! empty( $fecha_inicio ) && ! empty( $fecha_fin ) ) {
        $fecha_inicio_ymd = date('Y-m-d', strtotime($fecha_inicio));
        $fecha_fin_ymd = date('Y-m-d', strtotime($fecha_fin));
        $pickup_date_filter = "AND EXISTS(
            SELECT 1 FROM {$wpdb->postmeta} pm_pickup 
            WHERE pm_pickup.post_id = p.ID 
            AND pm_pickup.meta_key = 'wpcargo_pickup_date_picker'
            AND (
                STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') >= STR_TO_DATE('{$fecha_inicio_ymd}', '%Y-%m-%d')
                AND STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') <= STR_TO_DATE('{$fecha_fin_ymd}', '%Y-%m-%d')
            )
        )";
    }
    
    $driver_query = $filtro_motorizado > 0 ? $wpdb->prepare( 'AND pm_entrega.meta_value = %s', $filtro_motorizado ) : '';

    // Buscar motorizados que tengan envíos como 'wpcargo_motorizo_entrega' (solo entregas)
    $drivers_sql = "
        SELECT pm_entrega.meta_value AS driver_id,
               COUNT(DISTINCT p.ID) AS total_entregas
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_entrega
            ON p.ID = pm_entrega.post_id
            AND pm_entrega.meta_key = 'wpcargo_motorizo_entrega'
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_entrega.meta_value IS NOT NULL
        $pickup_date_filter
        $driver_query
        GROUP BY pm_entrega.meta_value
        ORDER BY total_entregas DESC
    ";

    error_log('🔍 [MERC_ADMIN_CARDS] drivers_sql: ' . preg_replace('/\s+/', ' ', trim($drivers_sql)) );

    // DEBUG: obtener hasta 10 envíos que coincidan con el filtro de pickup_date_picker para inspeccionar metas de entrega
    $sample_sql = "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish' $pickup_date_filter LIMIT 10";
    $sample_ids = $wpdb->get_col( $sample_sql );
    error_log('🔍 [MERC_ADMIN_CARDS] sample_ids_count: ' . count($sample_ids));
    if ( ! empty( $sample_ids ) ) {
        foreach ( $sample_ids as $sid ) {
            $m1 = get_post_meta( $sid, 'wpcargo_driver', true );
            $m2 = get_post_meta( $sid, 'wpcargo_motorizo_recojo', true );
            error_log("🔍 [MERC_ADMIN_CARDS] sample shipment_id={$sid} wpcargo_driver=" . var_export($m1, true) . " wpcargo_motorizo_recojo=" . var_export($m2, true));
        }
    } else {
        error_log('🔍 [MERC_ADMIN_CARDS] sample_ids EMPTY for date_query');
    }

    $drivers = $wpdb->get_results( $drivers_sql );
    error_log('🔍 [MERC_ADMIN_CARDS] drivers_count: ' . count( $drivers ?: array() ) );

    if ( empty( $drivers ) ) {
        echo '<div class="alert alert-info">No hay datos de motorizados para mostrar.</div>';
        return;
    }

    foreach ( $drivers as $driver ) {
        $driver_user = get_user_by( 'ID', $driver->driver_id );
        if ( ! $driver_user ) {
            continue;
        }

        // Traer envíos del motorizado (solo envíos donde fue motorizado de entrega)
        // Filtrar SOLO por wpcargo_pickup_date_picker (sin fallback a post_date)
        $driver_id_sql = esc_sql( intval( $driver->driver_id ) );
        $sql = "SELECT p.ID,
                   pm_estado_motorizado.meta_value AS estado
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_entrega
                ON p.ID = pm_entrega.post_id
                AND pm_entrega.meta_key = 'wpcargo_motorizo_entrega'
            LEFT JOIN {$wpdb->postmeta} pm_estado_motorizado 
                ON p.ID = pm_estado_motorizado.post_id 
                AND pm_estado_motorizado.meta_key = 'wpcargo_estado_pago_motorizado'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status = 'publish'
            AND pm_entrega.meta_value = '" . $driver_id_sql . "'
            $pickup_date_filter";

        $shipments = $wpdb->get_results( $sql );
        
        // Asegurar que $shipments sea siempre un array para evitar errores con count()/foreach
        if ( ! is_array( $shipments ) ) {
            $shipments = (array) $shipments;
        }

        $num_shipments = is_countable( $shipments ) ? count( $shipments ) : 0;
        error_log("🔍 [MERC_ADMIN_CARDS] Motorizado ID: " . $driver->driver_id . " | Envios encontrados: " . $num_shipments);

        $tot_recaudado   = 0.0;
        $pendiente       = 0.0;
        $liquidado       = 0.0;
        $efectivo_total  = 0.0;
        $pago_merc_total = 0.0;
        $pago_marca_total= 0.0;
        $pos_total       = 0.0;
        $entregas_pendientes = array();

        foreach ( $shipments as $shipment ) {
            $estado  = $shipment->estado ? $shipment->estado : 'pendiente';

            // Traer totales COMPLETOS sin filtrar por liquidación
            // Los cards deben mostrar TODO lo que el motorizado ha recaudado hoy
            $totales = get_payment_totals_by_method( $shipment->ID );
            $pos_display = get_pos_net_for_shipment( $shipment->ID, $totales );

            // DEBUG: mostrar metas relacionadas con pagos para diagnosticar montos faltantes
            $raw_ppm = get_post_meta( $shipment->ID, 'pod_payment_methods', true );
            $raw_total_meta = get_post_meta( $shipment->ID, 'wpcargo_total_cobrar', true );
            $candidate_keys = array('wpcargo_monto','monto','amount','price','total','cobrar','pagar','wpcargo_price','wpcargo_amount');
            $found_other = array();
            foreach ( $candidate_keys as $k ) {
                $v = get_post_meta( $shipment->ID, $k, true );
                if ( $v !== '' && $v !== null ) $found_other[ $k ] = $v;
            }

            error_log("   Shipment #" . $shipment->ID . " | pod_payment_methods(raw)=" . (is_scalar($raw_ppm) ? $raw_ppm : json_encode($raw_ppm)) . ", wpcargo_total_cobrar=" . var_export($raw_total_meta, true) . ", otros_meta=" . json_encode($found_other));

            error_log("   Shipment #" . $shipment->ID . " | Totales: efectivo=" . $totales['efectivo'] . ", pago_merc=" . $totales['pago_merc'] . ", pago_marca=" . $totales['pago_marca'] . ", pos=" . $pos_display . ", total=" . $totales['total']);

            $tot_recaudado   += $totales['total'];
            $efectivo_total  += $totales['efectivo'];
            $pago_merc_total += $totales['pago_merc'];
            $pago_marca_total+= $totales['pago_marca'];
            $pos_total       += $pos_display;

            // Agregar TODOS los envíos del motorizado (con o sin dinero recaudado)
            // para que se vea claramente cuáles entregó y cuáles no
            $entregas_pendientes[] = $shipment;
        }
        
        error_log("   Totales del motorizado: Total=" . $tot_recaudado . ", Efectivo=" . $efectivo_total . ", MERC=" . $pago_merc_total . ", MARCA=" . $pago_marca_total . ", POS=" . $pos_total);
        ?>
		<?php
		$driver_display = get_user_meta( $driver_user->ID, 'first_name', true ) . ' ' . get_user_meta( $driver_user->ID, 'last_name', true );
		$driver_display = trim( $driver_display ) ?: $driver_user->display_name;
		?>
        <?php $card_id = 'merc_card_driver_' . $driver->driver_id . '_' . sanitize_title( $driver_user->display_name ); ?>
        <div class="merc-user-card" style="width:100%;box-sizing:border-box;margin-bottom:12px;border-left-color:#2980b9;">
            <div class="merc-user-header" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;" data-target="#<?php echo esc_attr( $card_id ); ?>">
                <div><h4 style="margin:0;">🚗 <?php echo esc_html( $driver_display ); ?></h4><span class="badge badge-info" style="margin-left:8px;"><?php echo esc_html( count( $entregas_pendientes ) ); ?> envíos</span></div>
                <div class="merc-card-toggle" style="font-size:18px;padding-left:8px;">▾</div>
            </div>
            <div id="<?php echo esc_attr( $card_id ); ?>" class="merc-user-body" style="display:none;padding:10px;">
            <div class="merc-user-stats">
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Total Recaudado</div>
                    <div class="merc-stat-value">S/. <?php echo number_format( $tot_recaudado, 2 ); ?></div>
                </div>
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Efectivo recaudado</div>
                    <div class="merc-stat-value" style="color: #d35400;">
                        S/. <?php echo number_format( $efectivo_total, 2 ); ?>
                    </div>
                </div>
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Recaudado por MERC</div>
                    <div class="merc-stat-value" style="color: #2980b9;">
                        S/. <?php echo number_format( $pago_merc_total + $pos_total, 2 ); ?>
                    </div>
                </div>
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Recaudado por MARCA</div>
                    <div class="merc-stat-value" style="color: #8e44ad;">
                        S/. <?php echo number_format( $pago_marca_total, 2 ); ?>
                    </div>
                </div>
            </div>

            <?php if ( ! empty( $entregas_pendientes ) ) : ?>
                <div class="mt-3">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h6 style="margin: 0;">📋 Todos los Envíos del Motorizado (Entregados y No Entregados):</h6>
                        <?php
                        $caja_cerrada = false;
                        $estados_finales_cierre = array( 'NO RECIBIDO', 'REPROGRAMADO', 'NO CONTESTA', 'ANULADO', 'ENTREGADO' );
                        $total_estados_no_finales = 0;

                        foreach ( $entregas_pendientes as $entrega_estado ) {
                            $estado_envio_normalizado = strtoupper(
                                remove_accents(
                                    trim( (string) get_post_meta( $entrega_estado->ID, 'wpcargo_status', true ) )
                                )
                            );

                            if ( ! in_array( $estado_envio_normalizado, $estados_finales_cierre, true ) ) {
                                $total_estados_no_finales++;
                            }
                        }
                        $caja_cerrada_raw = (string) get_user_meta( $driver->driver_id, 'merc_caja_cerrada', true );
                        $caja_cerrada_fecha = (string) get_user_meta( $driver->driver_id, 'merc_caja_cerrada_fecha', true );
                        $fecha_actual = wp_date( 'Y-m-d' );

                        if ( '1' === $caja_cerrada_raw && $caja_cerrada_fecha === $fecha_actual ) {
                            $caja_cerrada = true;
                        } elseif ( '1' === $caja_cerrada_raw ) {
                            update_user_meta( $driver->driver_id, 'merc_caja_cerrada', '0' );
                        }
                        ?>

                        <?php if ( $total_estados_no_finales === 0 ) : ?>
                            <?php if ( $caja_cerrada ) : ?>
                                <button class="merc-btn-cierre-caja" disabled style="background:#27ae60;cursor:default;opacity:0.95;">
                                    ✅ Caja cerrada
                                </button>
                            <?php else : ?>
                                <button class="merc-btn-cierre-caja" 
                                        data-driver-id="<?php echo esc_attr( $driver->driver_id ); ?>" 
                                        data-tipo="motorizado">
                                    🔒 Cierre de caja
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php
                    $current_user = wp_get_current_user();
                    $is_admin = current_user_can('administrator');
                    $is_client = in_array('wpcargo_client', (array) $current_user->roles);
                    $show_comprobante = ! $is_admin;
                    ?>
                    <table class="table table-sm merc-entregas-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
								<th>Estado</th>
                                <th>Marca</th>
                                <th>Pago a Motorizado</th>
                                <th>Pago a MERC</th>
                                <th>Pago a MARCA</th>
                                <th>POS</th>
                                <th>Total</th>
                                <?php if ( $show_comprobante ) : ?>
                                    <th>Comprobante</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal_efectivo = 0;
                            $subtotal_merc     = 0;
                            $subtotal_marca    = 0;
                            $subtotal_pos      = 0;
                            $subtotal_total    = 0;

                            foreach ( $entregas_pendientes as $entrega ) :
                                $totales_entrega = get_payment_totals_by_method( $entrega->ID );
                                $subtotal_efectivo += $totales_entrega['efectivo'];
                                $subtotal_merc     += $totales_entrega['pago_merc'];
                                $subtotal_marca    += $totales_entrega['pago_marca'];
                                $subtotal_pos      += $totales_entrega['pos'];
                                $subtotal_total    += $totales_entrega['total'];
                                ?>
                                <?php
								$tracking_num = get_the_title( $entrega->ID );
								$tracking_url = home_url( '/dashboard/?wpcfe=track&num=' . $tracking_num );
								$estado_envio = get_post_meta( $entrega->ID, 'wpcargo_status', true ) ?: '—';
								
								// Obtener nombre de la marca (remitente) - billing_company
								$marca_id = get_post_meta( $entrega->ID, 'registered_shipper', true );
								$marca_nombre = '—';
								if ( ! empty( $marca_id ) ) {
									$billing_company = get_user_meta( $marca_id, 'billing_company', true );
									if ( ! empty( $billing_company ) ) {
										$marca_nombre = $billing_company;
									}
								}
								
								// Estilo condicional para estado REPROGRAMADO
								$estado_lower = strtolower( $estado_envio );
								$estado_bg = 'background:#eef6fc;color:#075985;';
								if ( $estado_lower === 'reprogramado' ) {
									$estado_bg = 'background:#ffcccc;color:#c0392b;';
								}
								?>
								<tr id="shipment-<?php echo esc_attr( $entrega->ID ); ?>" data-shipment-id="<?php echo esc_attr( $entrega->ID ); ?>" data-driver-id="<?php echo esc_attr( $driver->driver_id ); ?>">
									<td>
										<a href="<?php echo esc_url( $tracking_url ); ?>" target="_blank"
										   style="font-weight:bold;color:#2980b9;text-decoration:underline;">
											#<?php echo esc_html( $tracking_num ); ?>
										</a>
									</td>
									<td>
										<span style="display:inline-block;padding:2px 8px;border-radius:10px;
													 font-size:11px;font-weight:600;<?php echo $estado_bg; ?>">
											<?php echo esc_html( $estado_envio ); ?>
										</span>
									</td>
									<td><?php echo esc_html( $marca_nombre ); ?></td>
                                    <td>
                                        <strong>S/. <?php echo number_format( $totales_entrega['efectivo'], 2 ); ?></strong>
                                            <?php if ( $totales_entrega['efectivo'] > 0 && $is_admin && merc_shipment_method_has_image( $entrega->ID, 'efectivo' ) ) : ?>
                                                <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $entrega->ID; ?>" data-tipo="efectivo" title="Ver voucher">👁️</button>
                                            <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>S/. <?php echo number_format( $totales_entrega['pago_merc'], 2 ); ?></strong>
                                        <?php if ( $totales_entrega['pago_merc'] > 0 && $is_admin && merc_shipment_method_has_image( $entrega->ID, 'pago_merc' ) ) : ?>
                                            <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $entrega->ID; ?>" data-tipo="pago_merc" title="Ver voucher">👁️</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>S/. <?php echo number_format( $totales_entrega['pago_marca'], 2 ); ?></strong>
                                        <?php if ( $totales_entrega['pago_marca'] > 0 && $is_admin && merc_shipment_method_has_image( $entrega->ID, 'pago_marca' ) ) : ?>
                                            <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $entrega->ID; ?>" data-tipo="pago_marca" title="Ver voucher">👁️</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>S/. <?php echo number_format( $totales_entrega['pos'], 2 ); ?></strong>
                                        <?php if ( $totales_entrega['pos'] > 0 && $is_admin && merc_shipment_method_has_image( $entrega->ID, 'pos' ) ) : ?>
                                            <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $entrega->ID; ?>" data-tipo="pos" title="Ver voucher">👁️</button>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>S/. <?php echo number_format( $totales_entrega['total'], 2 ); ?></strong></td>
                                    <?php if ( $show_comprobante ) : ?>
                                        <td><?php echo merc_get_shipment_voucher_thumb_html( $entrega->ID ); ?></td>
                                    <?php endif; ?>
                                    <!-- Acción por envío removida: se utiliza la liquidación masiva por motorizado -->
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                                <tr>
                                    <td><strong>TOTAL:</strong></td>
									<td></td>
									<td></td>
                                    <td><strong>S/. <?php echo number_format( $subtotal_efectivo, 2 ); ?></strong></td>
                                    <td><strong>S/. <?php echo number_format( $subtotal_merc, 2 ); ?></strong></td>
                                    <td><strong>S/. <?php echo number_format( $subtotal_marca, 2 ); ?></strong></td>
                                    <td><strong>S/. <?php echo number_format( $subtotal_pos, 2 ); ?></strong></td>
                                    <td><strong>S/. <?php echo number_format( $subtotal_total, 2 ); ?></strong></td>
                                    <?php if ( $show_comprobante ) : ?><td></td><?php endif; ?>
                                </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
            </div> <!-- merc-user-body -->
        </div>
        <?php
    }
}

// ---------------------------------------------------------------------------
// PANEL ADMIN: LISTADO DE CLIENTES
// ---------------------------------------------------------------------------

function merc_admin_clientes( $fecha_inicio, $fecha_fin, $filtro_estado, $filtro_cliente, $modo = 'todos' ) {
    global $wpdb;
    
    // Construir filtro de fecha usando SOLO wpcargo_pickup_date_picker (sin OR con post_date)
    $date_query = '';
    if ( ! empty( $fecha_inicio ) && ! empty( $fecha_fin ) ) {
        $fecha_inicio_ymd = date('Y-m-d', strtotime($fecha_inicio));
        $fecha_fin_ymd = date('Y-m-d', strtotime($fecha_fin));
        $date_query = "AND EXISTS(
            SELECT 1 FROM {$wpdb->postmeta} pm_pickup 
            WHERE pm_pickup.post_id = p.ID 
            AND pm_pickup.meta_key = 'wpcargo_pickup_date_picker'
            AND (
                STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') >= STR_TO_DATE('{$fecha_inicio_ymd}', '%Y-%m-%d')
                AND STR_TO_DATE(pm_pickup.meta_value, '%d/%m/%Y') <= STR_TO_DATE('{$fecha_fin_ymd}', '%Y-%m-%d')
            )
        )";
    }
    
    $client_query = $filtro_cliente > 0 ? $wpdb->prepare( 'AND pm_sender.meta_value = %s', $filtro_cliente ) : '';

    $clients_sql = "
        SELECT pm_sender.meta_value AS client_id,
               COUNT(DISTINCT p.ID) AS total_envios
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_sender 
            ON p.ID = pm_sender.post_id 
            AND pm_sender.meta_key = 'registered_shipper'
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_sender.meta_value IS NOT NULL
        AND pm_sender.meta_value != ''
        $date_query
        $client_query
        GROUP BY pm_sender.meta_value
        ORDER BY total_envios DESC
    ";

    $clients = $wpdb->get_results( $clients_sql );

    if ( empty( $clients ) ) {
        if ( $modo === 'deudoras' ) {
            echo '<div class="alert alert-success">✅ No hay marcas con saldo negativo.</div>';
        } elseif ( $modo === 'merc_debe' ) {
            echo '<div class="alert alert-info">ℹ️ No hay marcas a las que Merc deba pagar.</div>';
        } else {
            echo '<div class="alert alert-info">No hay datos de clientes para mostrar.</div>';
        }
        return;
    }
    
    // Enriquecer y ordenar alfabéticamente
    foreach ( $clients as $client ) {
        $client_user = get_user_by( 'ID', $client->client_id );
        if ( $client_user ) {
            $billing_company = get_user_meta( $client_user->ID, 'billing_company', true );
            $billing_first_name = $client_user->first_name;
            $billing_last_name = $client_user->last_name;
            
            if (!empty($billing_company) && (!empty($billing_first_name) || !empty($billing_last_name))) {
                $client->sort_name = $billing_company . ' (' . trim($billing_first_name . ' ' . $billing_last_name) . ')';
            } elseif (!empty($billing_company)) {
                $client->sort_name = $billing_company;
            } else {
                $client->sort_name = trim($billing_first_name . ' ' . $billing_last_name) ?: $client_user->display_name;
            }
        } else {
            $client->sort_name = 'Zzz';
        }
    }
    usort($clients, function($a, $b) {
        return strcasecmp($a->sort_name, $b->sort_name);
    });

    $shown_clients = 0;

    foreach ( $clients as $client ) {
        $client_user = get_user_by( 'ID', $client->client_id );
        if ( ! $client_user ) {
            continue;
        }

        $shipments_sql = "
            SELECT p.ID,
                   pm_producto.meta_value         AS costo_producto,
                   pm_envio.meta_value            AS costo_envio,
                   pm_quien_paga.meta_value       AS quien_paga,
                   pm_included.meta_value         AS estado_remitente,
                   pm_cliente_pago_a.meta_value   AS cliente_pago_a,
                   pm_total.meta_value            AS wpcargo_total_cobrar
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_sender 
                ON p.ID = pm_sender.post_id AND pm_sender.meta_key = 'registered_shipper'
            LEFT JOIN {$wpdb->postmeta} pm_producto 
                ON p.ID = pm_producto.post_id AND pm_producto.meta_key = 'wpcargo_costo_producto'
            LEFT JOIN {$wpdb->postmeta} pm_envio 
                ON p.ID = pm_envio.post_id AND pm_envio.meta_key = 'wpcargo_costo_envio'
            LEFT JOIN {$wpdb->postmeta} pm_quien_paga 
                ON p.ID = pm_quien_paga.post_id AND pm_quien_paga.meta_key = 'wpcargo_quien_paga'
            LEFT JOIN {$wpdb->postmeta} pm_included 
                ON p.ID = pm_included.post_id AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
            LEFT JOIN {$wpdb->postmeta} pm_cliente_pago_a 
                ON p.ID = pm_cliente_pago_a.post_id AND pm_cliente_pago_a.meta_key = 'wpcargo_cliente_pago_a'
            LEFT JOIN {$wpdb->postmeta} pm_total 
                ON p.ID = pm_total.post_id AND pm_total.meta_key = 'wpcargo_total_cobrar'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status = 'publish'
            AND pm_sender.meta_value = '" . esc_sql($client->client_id) . "'
            $date_query
        ";
        
        $shipments = $wpdb->get_results( $shipments_sql );

        $por_cobrar       = 0.0;
        $por_pagar        = 0.0;
        $efectivo_total   = 0.0;
        $pago_merc_total  = 0.0;
        $pago_marca_total = 0.0;
        $pos_total        = 0.0;
        
        $por_cobrar_tot       = 0.0;
        $por_pagar_tot        = 0.0;
        $efectivo_total_tot   = 0.0;
        $pago_merc_total_tot  = 0.0;
        $pago_marca_total_tot = 0.0;
        $pos_total_tot        = 0.0;
        
        $pendientes       = array();
        $todos_envios     = array();
        $all_liquidated   = true;

        foreach ( $shipments as $shipment ) {
            $producto   = floatval( $shipment->costo_producto );
            $envio = function_exists( 'merc_get_adjusted_service_cost' ) ? merc_get_adjusted_service_cost( $shipment->ID ) : floatval( $shipment->costo_envio );
            $quien_paga = $shipment->quien_paga;
            $estado     = $shipment->estado_remitente ? $shipment->estado_remitente : 'pendiente';

            // Comprobar si está liquidado
            $included = get_post_meta( $shipment->ID, 'wpcargo_included_in_liquidation', true );
            $is_included = ! empty( $included );
            if ( ! $is_included ) {
                $all_liquidated = false;
            }

            $totales_shipment = get_payment_totals_by_method( $shipment->ID );
            $monto_total = $totales_shipment['total'];
            $pos_display = get_pos_net_for_shipment( $shipment->ID, $totales_shipment );
            
            // Acumular totales (históricos)
            $efectivo_total_tot  += $totales_shipment['efectivo'];
            $pago_merc_total_tot += $totales_shipment['pago_merc'];
            $pago_marca_total_tot+= $totales_shipment['pago_marca'];
            $pos_total_tot       += $pos_display;
            
            if ( $estado === 'pendiente' ) {
                if ( $quien_paga === 'cliente_final' && $producto > 0 ) {
                    $por_cobrar_tot += $producto;
                } elseif ( $quien_paga === 'remitente' && $envio > 0 ) {
                    $por_pagar_tot += $envio;
                }
            }
            
            // Acumular pendientes (no liquidados)
            if ( ! $is_included ) {
                $efectivo_total  += $totales_shipment['efectivo'];
                $pago_merc_total += $totales_shipment['pago_merc'];
                $pago_marca_total+= $totales_shipment['pago_marca'];
                $pos_total       += $pos_display;

                if ( $estado === 'pendiente' ) {
                    if ( $quien_paga === 'cliente_final' && $producto > 0 ) {
                        $por_cobrar += $producto;
                    } elseif ( $quien_paga === 'remitente' && $envio > 0 ) {
                        $por_pagar += $envio;
                    }
                }
            }

            // Agregar a tabla de TODOS los envios
            $todos_envios[] = array(
                'id'              => $shipment->ID,
                'titulo'          => get_the_title( $shipment->ID ),
                'monto'           => $envio,
                'monto_concepto'  => $monto_total,
                'tipo'            => ( $quien_paga === 'cliente_final' ? 'cobrar' : 'pagar' ),
                'liquidado'       => $is_included
            );
        }
        
        if ( empty($todos_envios) ) $all_liquidated = false;

        $recaudado_merc_tot  = $efectivo_total_tot + $pago_merc_total_tot + $pos_total_tot;
        $balance_total = $recaudado_merc_tot - $por_pagar_tot;

        $recaudado_merc  = $efectivo_total + $pago_merc_total + $pos_total;
        $recaudado_marca = $pago_marca_total;

        // Balance Neto (Pendiente) = Recaudado por MARCA (no usado aquí) - Total servicios por pagar
        $balance_neto = $recaudado_merc - $por_pagar;

        // Filtrar según la pestaña activa basado en el BALANCE TOTAL HISTÓRICO de este filtro de fechas
        if ( $modo === 'deudoras' && $balance_total >= 0 ) continue;
        if ( $modo === 'merc_debe' && $balance_total <= 0 ) continue;
        
        $shown_clients++;

        $total_por_liquidar = abs( $balance_neto );
        $btn_text = $balance_neto >= 0
            ? 'Pagar al cliente S/. ' . number_format( $balance_neto, 2 )
            : 'Cobrar del cliente S/. ' . number_format( abs( $balance_neto ), 2 );
        ?>
		<?php
		$billing_company = get_user_meta( $client_user->ID, 'billing_company', true );
		$billing_first_name = $client_user->first_name;
		$billing_last_name = $client_user->last_name;
		
		// Mostrar empresa (nombre y apellido) o solo nombre y apellido
		if (!empty($billing_company) && (!empty($billing_first_name) || !empty($billing_last_name))) {
			$display_name = $billing_company . ' (' . trim($billing_first_name . ' ' . $billing_last_name) . ')';
		} elseif (!empty($billing_company)) {
			$display_name = $billing_company;
		} else {
			$display_name = trim($billing_first_name . ' ' . $billing_last_name) ?: $client_user->display_name;
		}
		?>
        <?php 
        $card_id = 'merc_card_client_' . $client->client_id . '_' . sanitize_title( $client_user->display_name ); 
        
        $badge_html = '';
        if ( $all_liquidated ) {
            if ( $modo === 'deudoras' ) {
                $badge_html = '<span class="badge badge-success" style="margin-left:auto; font-size:14px; padding:6px 10px;">✅ (Pagó)</span>';
            } else {
                $badge_html = '<span class="badge badge-success" style="margin-left:auto; font-size:14px; padding:6px 10px;">✅ (Cliente Pagado)</span>';
            }
        }
        ?>
        <div class="merc-user-card" style="border-left-color: #27ae60;width:100%;box-sizing:border-box;margin-bottom:12px;">
            <div class="merc-user-header" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;" data-target="#<?php echo esc_attr( $card_id ); ?>">
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;">
                    <h4 style="margin:0;">🏢 <?php echo esc_html( $display_name ); ?></h4>
                    <span class="badge badge-secondary"><?php echo esc_html( count($todos_envios) ); ?> envíos</span>
                </div>
                <?php echo $badge_html; ?>
                <div class="merc-card-toggle" style="font-size:18px;padding-left:8px;<?php echo $all_liquidated ? 'margin-left:10px;' : 'margin-left:auto;'; ?>">▾</div>
            </div>
            <div id="<?php echo esc_attr( $card_id ); ?>" class="merc-user-body" style="display:none;padding:10px;">
            <div class="merc-user-stats">
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Por Cobrar (Productos)</div>
                    <div class="merc-stat-value" style="color: #27ae60;">
                        S/. <?php echo number_format( $por_cobrar, 2 ); ?>
                    </div>
                </div>
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Por Pagar (Envíos)</div>
                    <div class="merc-stat-value" style="color: #e74c3c;">
                        S/. <?php echo number_format( $por_pagar, 2 ); ?>
                    </div>
                </div>
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Recaudado por MERC</div>
                    <div class="merc-stat-value" style="color: #2980b9;">
                        S/. <?php echo number_format( $recaudado_merc, 2 ); ?>
                    </div>
                </div>
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Recaudado por MARCA</div>
                    <div class="merc-stat-value" style="color: #8e44ad;">
                        S/. <?php echo number_format( $recaudado_marca, 2 ); ?>
                    </div>
                </div>
                <div class="merc-stat-item">
                    <div class="merc-stat-label">Balance Neto</div>
                    <div class="merc-stat-value" style="color: <?php echo $balance_neto >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
                        S/. <?php echo number_format( $balance_neto, 2 ); ?>
                    </div>
                </div>
            </div>

            <?php if ( ! empty( $todos_envios ) ) : ?>
                <div class="mt-3">
                    <h6 style="margin: 0; margin-bottom: 15px;">📦 Todos los Envíos:</h6>
                    <?php if ( $balance_neto > 0 && !$all_liquidated ) : // Mostrar solo cuando MERC debe PAGAR al cliente y no está liquidado ?>
                        <?php if ( false ) : // El badge ya se muestra en el header ?>
                            <div style="margin-bottom: 15px;">
                                <span class="badge badge-success" style="font-size: 14px; padding: 10px;">✅ Cliente Pagado</span>
                            </div>
                        <?php else : ?>
                            <div style="margin-bottom:12px;">
                                <label style="font-size:0.85em;color:#7f8c8d;">📅 Fecha de caja a liquidar:</label>
                                <input type="date" 
                                       class="merc-fecha-caja-input form-control form-control-sm" 
                                       id="merc-fecha-caja-input-<?php echo esc_attr( $client->client_id ); ?>"
                                       value="<?php echo current_time('Y-m-d'); ?>"
                                       max="<?php echo current_time('Y-m-d'); ?>"
                                       style="width:180px;display:inline-block;margin-left:8px;">
                            </div>
                            <button class="merc-btn-liquidar-todo" 
                                    data-user-id="<?php echo esc_attr( $client->client_id ); ?>" 
                                    data-tipo="remitente"
                                    data-monto="<?php echo number_format( $total_por_liquidar, 2 ); ?>"
                                    style="background: #27ae60; margin-bottom: 15px;">
                                💰
                                <?php echo esc_html( $btn_text ); ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php
                    $current_user = wp_get_current_user();
                    $is_admin = current_user_can('administrator');
                    $is_client = in_array('wpcargo_client', (array) $current_user->roles);
                    $show_comprobante = ! $is_admin;
                    ?>
                    <table class="table table-sm merc-entregas-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Estado Pago</th>
								<th>Estado Envío</th>
                                <th>Concepto</th>
                                <th>Efectivo</th>
                                <th>Pago a MERC</th>
                                <th>Pago a MARCA</th>
                                <th>POS</th>
                                <th>Servicio</th>
                                <th>Cargos Ad.</th>
                                <?php if ( $show_comprobante ) : ?>
                                    <th>Comprobante</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal_efectivo  = 0;
                            $subtotal_merc      = 0;
                            $subtotal_marca     = 0;
                            $subtotal_pos       = 0;
                            $subtotal_monto     = 0;
                            $subtotal_concepto  = 0;
                            $subtotal_cargos_adicionales = 0;

                            foreach ( $todos_envios as $envio_item ) : 
                                $totales_pendiente = get_payment_totals_by_method( $envio_item['id'] );
                                $estado_texto = ( $envio_item['tipo'] === 'cobrar' ) ? 'Por cobrar' : 'Por pagar';
                                $color_estado = ( $envio_item['tipo'] === 'cobrar' ) ? '#27ae60' : '#e74c3c';
                                
                                // Verificar si el servicio ya fue cobrado
                                $servicio_cobrado = get_post_meta( $envio_item['id'], 'wpcargo_servicio_cobrado', true );
                                $color_servicio = $servicio_cobrado ? '#27ae60' : '#e74c3c';

                                $subtotal_efectivo  += $totales_pendiente['efectivo'];
                                $subtotal_merc      += $totales_pendiente['pago_merc'];
                                $subtotal_marca     += $totales_pendiente['pago_marca'];
                                $subtotal_pos       += $totales_pendiente['pos'];
                                $subtotal_monto     += $envio_item['monto'];
                                $subtotal_concepto  += $envio_item['monto_concepto'];
                                ?>
                                <tr>
                                    <?php
									$tracking_num_cli = $envio_item['titulo'];
									$tracking_url_cli = home_url( '/dashboard/?wpcfe=track&num=' . $tracking_num_cli );
									$estado_envio_cli = get_post_meta( $envio_item['id'], 'wpcargo_status', true ) ?: '—';
									?>
									<td>
										<a href="<?php echo esc_url( $tracking_url_cli ); ?>" target="_blank"
										   style="font-weight:bold;color:#2980b9;text-decoration:underline;">
											#<?php echo esc_html( $tracking_num_cli ); ?>
										</a>
                                        <?php if ( $envio_item['liquidado'] ) : ?>
                                            <span class="badge badge-success" style="font-size:10px;margin-left:5px;">Liquidado</span>
                                        <?php endif; ?>
									</td>
									<td><strong style="color: <?php echo $color_estado; ?>;"><?php echo $estado_texto; ?></strong></td>
									<td>
										<?php
										$estado_cli_lower = strtolower( $estado_envio_cli );
										$estado_cli_bg = 'background:#eef6fc;color:#075985;';
										if ( $estado_cli_lower === 'reprogramado' ) {
											$estado_cli_bg = 'background:#ffcccc;color:#c0392b;';
										}
										?>
										<span style="display:inline-block;padding:2px 8px;border-radius:10px;
													 font-size:11px;font-weight:600;<?php echo $estado_cli_bg; ?>">
											<?php echo esc_html( $estado_envio_cli ); ?>
										</span>
									</td>
                                    <td><strong>S/. <?php echo number_format( $envio_item['monto_concepto'], 2 ); ?></strong></td>
                                    <td>
                                        S/. <?php echo number_format( $totales_pendiente['efectivo'], 2 ); ?>
                                        <?php if ( $totales_pendiente['efectivo'] > 0 && $is_admin && merc_shipment_method_has_image( $envio_item['id'], 'efectivo' ) ) : ?>
                                            <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $envio_item['id']; ?>" data-tipo="efectivo" title="Ver voucher">👁️</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        S/. <?php echo number_format( $totales_pendiente['pago_merc'], 2 ); ?>
                                        <?php if ( $totales_pendiente['pago_merc'] > 0 && $is_admin && merc_shipment_method_has_image( $envio_item['id'], 'pago_merc' ) ) : ?>
                                            <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $envio_item['id']; ?>" data-tipo="pago_merc" title="Ver voucher">👁️</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        S/. <?php echo number_format( $totales_pendiente['pago_marca'], 2 ); ?>
                                            <?php if ( $totales_pendiente['pago_marca'] > 0 && $is_admin && merc_shipment_method_has_image( $envio_item['id'], 'pago_marca' ) ) : ?>
                                            <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $envio_item['id']; ?>" data-tipo="pago_marca" title="Ver voucher">👁️</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        S/. <?php echo number_format( $totales_pendiente['pos'], 2 ); ?>
                                            <?php if ( $totales_pendiente['pos'] > 0 && $is_admin && merc_shipment_method_has_image( $envio_item['id'], 'pos' ) ) : ?>
                                            <button class="merc-btn-ver-voucher" data-shipment-id="<?php echo $envio_item['id']; ?>" data-tipo="pos" title="Ver voucher">👁️</button>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong style="color: <?php echo $color_servicio; ?>;">S/. <?php echo number_format( $envio_item['monto'], 2 ); ?></strong></td>
                                    <td>
                                        <?php 
                                        $cargos = get_post_meta( $envio_item['id'], 'merc_cargos_adicionales', true );
                                        $total_cargos_envio = 0;
                                        $desc_cargos = '';
                                        if ( is_array( $cargos ) ) {
                                            foreach ( $cargos as $c ) {
                                                $total_cargos_envio += floatval( $c['monto'] );
                                                $desc_cargos .= esc_html($c['descripcion']) . ' (S/. ' . number_format($c['monto'], 2) . ')' . "\n";
                                            }
                                        }
                                        $subtotal_cargos_adicionales += $total_cargos_envio;
                                        
                                        $is_liq = get_post_meta( $envio_item['id'], 'merc_remitente_liquidated', true );
                                        $cargos_json = is_array($cargos) ? esc_attr(json_encode($cargos)) : '[]';
                                        
                                        // Desbloquear si está reprogramado, aunque haya sido liquidado antes
                                        $is_blocked = ( $is_liq === '1' && stripos($estado_envio_cli, 'REPROGRAMADO') === false );

                                        if ( $total_cargos_envio > 0 ) : 
                                            if ( $is_blocked ) : ?>
                                                <span class="badge badge-warning" title="<?php echo esc_attr($desc_cargos); ?>">⚡ +S/. <?php echo number_format($total_cargos_envio, 2); ?></span>
                                            <?php else : ?>
                                                <span class="badge badge-warning" style="cursor:pointer;" onclick="mercPromptCargo(<?php echo $envio_item['id']; ?>, '<?php echo $cargos_json; ?>')" title="<?php echo esc_attr($desc_cargos); ?> Añadir más">⚡ +S/. <?php echo number_format($total_cargos_envio, 2); ?></span>
                                            <?php endif; 
                                        else : 
                                            if ( ! $is_blocked ) : ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="mercPromptCargo(<?php echo $envio_item['id']; ?>, '[]')" title="Añadir cargo adicional" style="font-size:10px;margin:0;">➕ Añadir</button>
                                            <?php endif; 
                                        endif; ?>
                                    </td>
                                    <?php if ( $show_comprobante ) : ?>
                                        <td><?php echo merc_get_shipment_voucher_thumb_html( $envio_item['id'] ); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong>TOTAL:</strong></td>
                                <td><strong>S/. <?php echo number_format( $subtotal_concepto, 2 ); ?></strong></td>
                                <td><strong>S/. <?php echo number_format( $subtotal_efectivo, 2 ); ?></strong></td>
                                <td><strong>S/. <?php echo number_format( $subtotal_merc, 2 ); ?></strong></td>
                                <td><strong>S/. <?php echo number_format( $subtotal_marca, 2 ); ?></strong></td>
                                <td><strong>S/. <?php echo number_format( $subtotal_pos, 2 ); ?></strong></td>
                                <td><strong>S/. <?php echo number_format( $subtotal_monto, 2 ); ?></strong></td>
                                <td><strong>S/. <?php echo number_format( $subtotal_cargos_adicionales, 2 ); ?></strong></td>
                                <?php if ( $show_comprobante ) : ?><td></td><?php endif; ?>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
            </div> <!-- merc-user-body -->
        </div>
        <?php
    }

    if ( $shown_clients === 0 ) {
        if ( $modo === 'deudoras' ) {
            echo '<div class="alert alert-success">✅ No hay marcas con saldo negativo para este periodo.</div>';
        } elseif ( $modo === 'merc_debe' ) {
            echo '<div class="alert alert-info">ℹ️ No hay marcas a las que Merc deba pagar para este periodo.</div>';
        }
    }

    if ( ! defined( 'MERC_CARGOS_JS_LOADED' ) ) {
        define( 'MERC_CARGOS_JS_LOADED', true );
        ?>
        <script>
        function mercPromptCargo(shipmentId, historyJsonString) {
            let historyHtml = '';
            try {
                let history = JSON.parse(historyJsonString);
                if (history && history.length > 0) {
                    historyHtml = '<div style="text-align:left; font-size:12px; margin-bottom:15px; background:#f8f9fa; padding:10px; border-radius:5px;">' +
                                  '<strong>Historial de Cargos:</strong><ul style="margin-top:5px; padding-left:20px;">';
                    history.forEach(function(item, index) {
                        historyHtml += '<li style="margin-bottom:5px; display:flex; justify-content:space-between; align-items:center;">' + 
                                       '<span>' + item.descripcion + ' <b>(S/. ' + parseFloat(item.monto).toFixed(2) + ')</b></span>' +
                                       '<button type="button" class="btn btn-sm btn-danger py-0 px-1" style="font-size:10px;" onclick="mercDeleteCargo(' + shipmentId + ', ' + index + ')">🗑️</button>' +
                                       '</li>';
                    });
                    historyHtml += '</ul></div>';
                }
            } catch(e) {}

            Swal.fire({
                title: 'Añadir Cargo Adicional',
                html:
                    historyHtml +
                    '<input id="merc-cargo-desc" class="swal2-input" placeholder="Descripción (ej: Empaque)" style="margin-top:0;">' +
                    '<input id="merc-cargo-monto" type="number" step="0.01" class="swal2-input" placeholder="Monto (ej: 5.00)">',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const desc = document.getElementById('merc-cargo-desc').value;
                    const monto = parseFloat(document.getElementById('merc-cargo-monto').value);
                    if (!desc || isNaN(monto) || monto <= 0) {
                        Swal.showValidationMessage('Por favor ingresa una descripción y un monto válido mayor a 0');
                        return false;
                    }
                    return { desc: desc, monto: monto };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    var fd = new FormData();
                    fd.append('action', 'merc_add_cargo_adicional');
                    fd.append('nonce', '<?php echo wp_create_nonce("merc_cargo_adicional"); ?>');
                    fd.append('shipment_id', shipmentId);
                    fd.append('descripcion', result.value.desc);
                    fd.append('monto', result.value.monto);

                    Swal.fire({title: 'Guardando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                    jQuery.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if(response.success) {
                                Swal.fire({icon: 'success', title: 'Éxito', text: response.data.message}).then(() => { location.reload(); });
                            } else {
                                Swal.fire({icon: 'error', title: 'Error', text: response.data.message || 'Error desconocido'});
                            }
                        },
                        error: function() {
                            Swal.fire({icon: 'error', title: 'Error', text: 'Error de red.'});
                        }
                    });
                }
            });
        }

        function mercDeleteCargo(shipmentId, cargoIndex) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Se eliminará este cargo adicional.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    var fd = new FormData();
                    fd.append('action', 'merc_delete_cargo_adicional');
                    fd.append('nonce', '<?php echo wp_create_nonce("merc_cargo_adicional"); ?>');
                    fd.append('shipment_id', shipmentId);
                    fd.append('cargo_index', cargoIndex);

                    Swal.fire({title: 'Eliminando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                    jQuery.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if(response.success) {
                                Swal.fire({icon: 'success', title: 'Eliminado', text: response.data.message}).then(() => { location.reload(); });
                            } else {
                                Swal.fire({icon: 'error', title: 'Error', text: response.data.message || 'Error desconocido'});
                            }
                        },
                        error: function() {
                            Swal.fire({icon: 'error', title: 'Error', text: 'Error de red.'});
                        }
                    });
                }
            });
        }
        </script>
        <?php
    }
}

// ---------------------------------------------------------------------------
// FUNCIONES AUXILIARES DE FECHA
// ---------------------------------------------------------------------------

function merc_get_date_range_query( $fecha_inicio, $fecha_fin ) {
    global $wpdb;
    if ( empty($fecha_inicio) || empty($fecha_fin) ) {
        return '';
    }
    
    // Convertir fechas a formato YYYY-MM-DD
    $start_ts = strtotime($fecha_inicio);
    $end_ts = strtotime($fecha_fin);
    $fecha_inicio_ymd = date('Y-m-d', $start_ts);
    $fecha_fin_ymd = date('Y-m-d', $end_ts);
    
    // Construir fragmento SQL que intente varios parseos y además permita fallback a post_date.
    // Dejamos %% escapados para que STR_TO_DATE funcione correctamente cuando esta cadena
    // se use en contextos que podrían pasar por wpdb->prepare().
    $start_dt = $fecha_inicio_ymd . ' 00:00:00';
    $end_dt   = $fecha_fin_ymd . ' 23:59:59';

    return "AND ( 
        EXISTS( 
            SELECT 1 FROM {$wpdb->postmeta} pm_pickup 
            WHERE pm_pickup.post_id = p.ID 
              AND pm_pickup.meta_key IN ('wpcargo_pickup_date_picker','wpcargo_pickup_date','calendarenvio','wpcargo_fecha_envio')
              AND (
                    (STR_TO_DATE(pm_pickup.meta_value, '%%d/%%m/%%Y') >= STR_TO_DATE('{$fecha_inicio_ymd}', '%%Y-%%m-%%d') AND STR_TO_DATE(pm_pickup.meta_value, '%%d/%%m/%%Y') <= STR_TO_DATE('{$fecha_fin_ymd}', '%%Y-%%m-%%d'))
                 OR (STR_TO_DATE(pm_pickup.meta_value, '%%Y-%%m-%%d') >= STR_TO_DATE('{$fecha_inicio_ymd}', '%%Y-%%m-%%d') AND STR_TO_DATE(pm_pickup.meta_value, '%%Y-%%m-%%d') <= STR_TO_DATE('{$fecha_fin_ymd}', '%%Y-%%m-%%d'))
                 OR (pm_pickup.meta_value BETWEEN '{$fecha_inicio_ymd}' AND '{$fecha_fin_ymd}')
                 OR (pm_pickup.meta_value LIKE '{$fecha_inicio_ymd}%')
              )
        )
        OR (p.post_date BETWEEN '{$start_dt}' AND '{$end_dt}')
    )";
}

function merc_get_date_query( $filtro_fecha ) {
    // Usar la fecha/hora del sitio (current_time) para evitar desajustes de zona horaria
    $now = current_time('timestamp');
    switch ( $filtro_fecha ) {
        case 'hoy':
            $start = date('Y-m-d 00:00:00', $now);
            $end   = date('Y-m-d 23:59:59', $now);
            return "AND p.post_date BETWEEN '{$start}' AND '{$end}'";
        case 'semana':
            // Semana ISO: lunes - domingo
            $monday = strtotime('monday this week', $now);
            $sunday = strtotime('sunday this week', $now);
            $start = date('Y-m-d 00:00:00', $monday);
            $end   = date('Y-m-d 23:59:59', $sunday);
            return "AND p.post_date BETWEEN '{$start}' AND '{$end}'";
        case 'mes':
            $start = date('Y-m-01 00:00:00', $now);
            $end   = date('Y-m-t 23:59:59', $now);
            return "AND p.post_date BETWEEN '{$start}' AND '{$end}'";
        default:
            return '';
    }
}

// ---------------------------------------------------------------------------
// TRANSFERIR PAGO DE MERC A MARCA (LIQUIDACIÓN DE CLIENTES)
// ---------------------------------------------------------------------------

function merc_liquidar_cliente_transferir_a_marca( $shipment_id ) {
    $json = get_post_meta( $shipment_id, 'pod_payment_methods', true );
    $costo_envio = function_exists( 'merc_get_adjusted_service_cost' ) ? merc_get_adjusted_service_cost( $shipment_id ) : floatval( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) );
    
    if ( empty( $json ) || $costo_envio <= 0 ) {
        return;
    }
    
    $methods = json_decode( $json, true );
    if ( ! is_array( $methods ) ) {
        return;
    }
    
    $total_pago_merc = 0;
    $nuevo_array = array();
    
    // Primero calcular cuánto hay en pago_merc
    foreach ( $methods as $method ) {
        if ( isset( $method['metodo'] ) && $method['metodo'] === 'pago_merc' ) {
            $total_pago_merc += floatval( $method['monto'] );
        }
    }
    
    // Si no hay suficiente para cubrir el servicio, no hacer nada
    if ( $total_pago_merc < $costo_envio ) {
        error_log("⚠️ No hay suficiente en pago_merc para cubrir el servicio - Envío: {$shipment_id}");
        return;
    }
    
    // Calcular cuánto va a MARCA (total MERC - servicio)
    $monto_a_marca = $total_pago_merc - $costo_envio;
    
    // Procesar el array: convertir todos los pago_merc a pago_marca con el nuevo monto
    $pago_merc_procesados = 0;
    foreach ( $methods as $method ) {
        if ( isset( $method['metodo'] ) && $method['metodo'] === 'pago_merc' ) {
            if ( $pago_merc_procesados === 0 && $monto_a_marca > 0 ) {
                // Solo crear UN pago_marca con el monto total menos el servicio
                $nuevo_pago = array(
                    'metodo' => 'pago_marca',
                    'monto' => $monto_a_marca
                );
                
                // Transferir imagen del primer pago_merc si existe
                if ( isset( $method['imagen_id'] ) ) {
                    $nuevo_pago['imagen_id'] = $method['imagen_id'];
                }
                if ( isset( $method['imagen_url'] ) ) {
                    $nuevo_pago['imagen_url'] = $method['imagen_url'];
                }
                
                $nuevo_array[] = $nuevo_pago;
                $pago_merc_procesados++;
            }
            // No agregamos los pago_merc originales (se eliminan)
        } else {
            // Mantener los demás métodos de pago
            $nuevo_array[] = $method;
        }
    }
    
    // Guardar el JSON actualizado
    $payment_methods_json = json_encode( $nuevo_array );
    update_post_meta( $shipment_id, 'pod_payment_methods', $payment_methods_json );
    
    // Marcar el servicio como cobrado
    update_post_meta( $shipment_id, 'wpcargo_servicio_cobrado', 'si' );
    
    error_log("✅ Liquidación cliente - Envío: {$shipment_id}, MERC: {$total_pago_merc}, Servicio: {$costo_envio}, a MARCA: {$monto_a_marca}");
}

// PANEL ADMIN: HISTORIAL GLOBAL DE LIQUIDACIONES (ADMIN)
function merc_admin_liquidaciones( $fecha_inicio = '', $fecha_fin = '', $filtro_estado = '', $filtro_cliente = 0 ) {
    // Filtrar por cliente específico si se seleccionó
    $user_args = array(
        'meta_key' => 'merc_liquidations',
        'meta_compare' => 'EXISTS',
        'number' => 0,
    );
    
    if ( $filtro_cliente > 0 ) {
        $user_args['include'] = array( $filtro_cliente );
    }
    
    // Obtener todos los usuarios que tengan historiales de liquidación
    $users = get_users( $user_args );
    
    // Preparar rango de fechas para filtrado
    $fecha_inicio_ts = !empty($fecha_inicio) ? strtotime($fecha_inicio . ' 00:00:00') : 0;
    $fecha_fin_ts = !empty($fecha_fin) ? strtotime($fecha_fin . ' 23:59:59') : PHP_INT_MAX;

    if ( empty( $users ) ) {
        echo '<div class="alert alert-info">No hay registros de liquidación.</div>';
        return;
    }

    // Botones de migración y generación de penalidades removidos: no se usan y entorpecen la UI.
    // Si en el futuro se requieren, regenerar nonces y reimplementar las acciones con seguridad.

        // Mostrar una tarjeta por remitente con su historial (una por fila, ancho completo)
        echo '<div class="merc-liquidations-cards" style="display:block">';
        // Estilos mejorados para las tarjetas y tablas de liquidaciones
        echo '<style>' .
            '.merc-liquidations-cards .merc-user-card{border:1px solid #e6eef7;border-radius:6px;margin-bottom:12px;background:#ffffff;box-shadow:0 1px 2px rgba(16,24,40,0.03);overflow:hidden;border-left:4px solid #2980b9}' .
            '.merc-liquidations-cards .merc-user-header{padding:12px 16px;background:#f7fbfe;display:flex;justify-content:space-between;align-items:center}' .
            '.merc-liquidations-cards .merc-user-body{padding:14px 16px;color:#374151}' .
            '.merc-liquidations-table{width:100%;border-collapse:collapse;font-size:13px}' .
            '.merc-liquidations-table th,.merc-liquidations-table td{padding:10px 12px;border-bottom:1px solid #f0f3f5;text-align:left;vertical-align:middle}' .
            '.merc-liquidations-table thead th{background:#fbfdff;color:#1f2937;font-weight:600}' .
            '.merc-liquidations-table tbody tr:hover{background:#fbfdff}' .
             '.merc-liquidations-btn{display:inline-block;padding:6px 8px;border-radius:4px;border:1px solid #cbd5e0;background:#ffffff;color:#1f2937;text-decoration:none;margin-left:6px;font-size:13px}' .
             '.merc-liquidations-verify{background:#16a34a;border-color:#10b981;color:#fff;padding:6px 10px;border-radius:6px;font-weight:600;border:0;cursor:pointer;margin-left:8px}' .
             '.merc-liquidations-verify:hover{opacity:0.95}' .
            '.merc-liquidations-amount{font-weight:700;color:#111827}' .
            '.badge.badge-secondary{background:#eef6fc;color:#075985;padding:3px 6px;border-radius:6px;font-size:12px;margin-left:8px}' .
            '</style>';

    foreach ( $users as $user ) {
        $history = get_user_meta( $user->ID, 'merc_liquidations', true );
        if ( ! is_array( $history ) || empty( $history ) ) continue;
        
        // Filtrar entradas por rango de fechas y estado
        $history_filtered = array();
        foreach ( $history as $entry ) {
            // Filtro de fecha
            if ( !empty($entry['date']) ) {
                $entry_ts = strtotime($entry['date']);
                if ( $entry_ts < $fecha_inicio_ts || $entry_ts > $fecha_fin_ts ) {
                    continue;
                }
            }
            
            // Filtro de estado (liquidado=verificado, pendiente=no verificado)
            if ( !empty($filtro_estado) ) {
                $is_verified = isset($entry['verified']) && $entry['verified'];
                if ( $filtro_estado === 'liquidado' && !$is_verified ) {
                    continue;
                }
                if ( $filtro_estado === 'pendiente' && $is_verified ) {
                    continue;
                }
            }
            
            $history_filtered[] = $entry;
        }
        
        // Si después del filtro no hay entradas, saltar este usuario
        if ( empty( $history_filtered ) ) continue;
        $history = $history_filtered;

        // Etiqueta del remitente
        $empresa = get_user_meta( $user->ID, 'billing_company', true );
        if ( empty( $empresa ) ) {
            $first = get_user_meta( $user->ID, 'first_name', true );
            $last  = get_user_meta( $user->ID, 'last_name', true );
            $label = trim( $first . ' ' . $last );
            if ( empty( $label ) ) $label = $user->display_name;
        } else {
            $label = $empresa;
        }

        // Contadores y totales básicos
        $count_entries = count( $history );
        $total_amount = 0.0;
        foreach ( $history as $entry ) {
            $total_amount += isset( $entry['amount'] ) ? floatval( $entry['amount'] ) : 0;
        }

        // ID único del card para toggle
        $card_id = 'merc_card_' . $user->ID . '_' . sanitize_title( $label );
        echo '<div class="merc-user-card" data-driver-id="<?php echo esc_attr( $driver->driver_id ); ?>" style="width:100%;box-sizing:border-box;margin-bottom:12px;border-left-color:#2980b9;">';
        echo '<div class="merc-user-header" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;" data-target="#' . esc_attr( $card_id ) . '">';
        echo '<div><h4 style="margin:0;">🏢 ' . esc_html( $label ) . '</h4><span class="badge badge-secondary" style="margin-left:8px;">' . intval( $count_entries ) . ' entradas</span></div>';
        echo '<div class="merc-card-toggle" style="font-size:18px;padding-left:8px;">▾</div>';
        echo '</div>';
        echo '<div id="' . esc_attr( $card_id ) . '" class="merc-user-body" style="display:none;padding:10px;">';
        echo '<div style="margin-bottom:8px;color:#333;">Total histórico: <strong>S/. ' . number_format( $total_amount, 2 ) . '</strong></div>';

        echo '<table class="table table-sm merc-liquidations-table"><thead><tr><th>ID</th><th>Fecha</th><th>Resultado</th><th>Monto</th><th>Acción</th><th>Comprobante</th></tr></thead><tbody>';

        foreach ( $history as $entry ) {
            $id = isset( $entry['id'] ) ? esc_html( $entry['id'] ) : '';
            $date = isset( $entry['date'] ) ? esc_html( $entry['date'] ) : '';
            $result = isset( $entry['result'] ) ? floatval( $entry['result'] ) : 0.0;
            $action = isset( $entry['action'] ) ? esc_html( $entry['action'] ) : '';
            $amount = isset( $entry['amount'] ) ? floatval( $entry['amount'] ) : 0.0;
            $attachment_id = isset( $entry['attachment_id'] ) ? intval( $entry['attachment_id'] ) : 0;
            $attachment_url = $attachment_id ? wp_get_attachment_url( $attachment_id ) : ''; 

            // Envíos vinculados
            $ship_list_html = '';
            $shipments_list = isset( $entry['shipments'] ) && is_array( $entry['shipments'] ) ? $entry['shipments'] : array();
            if ( ! empty( $shipments_list ) ) {
                $parts = array();
                foreach ( $shipments_list as $sid ) {
                    $post = get_post( $sid );
                    if ( $post && $post->post_type === 'wpcargo_shipment' ) {
                        $edit_url = admin_url( 'post.php?post=' . intval( $sid ) . '&action=edit' );
                        $parts[] = '<a href="' . esc_url( $edit_url ) . '" target="_blank">#' . esc_html( get_the_title( $sid ) ) . '</a>';
                    } else {
                        $parts[] = '<span style="color:#999;">(enviado ' . intval( $sid ) . ' eliminado)</span>';
                    }
                }
                $ship_list_html = implode( ', ', $parts );
            } else {
                $ship_list_html = '<span style="color:#999;">(sin envíos)</span>';
            }

            echo '<tr>';
            echo '<td>' . $id . '</td>';
            echo '<td>' . $date . '</td>';
            // columna 'Envíos' removida intencionalmente
            echo '<td>' . number_format( $result, 2 ) . '</td>';
            echo '<td>' . number_format( $amount, 2 ) . '</td>';
            echo '<td>' . esc_html( $action ) . '</td>';

            if ( $attachment_url ) {
                $verify_button = '';
                $verified = isset( $entry['verified'] ) && $entry['verified'];
                if ( ! $verified ) {
                    $user_roles = is_array( $user->roles ) ? $user->roles : array();
                    $is_client = in_array( 'wpcargo_client', $user_roles ) || in_array( 'client', $user_roles ) || in_array( 'remitente', $user_roles );
                    if ( $is_client ) {
                        $verify_nonce = wp_create_nonce('merc_verify');
                        // botón verde de verificar
                        $verify_button = ' <button class="merc-liquidations-verify" data-user-id="' . esc_attr( $user->ID ) . '" data-liq-id="' . esc_attr( $id ) . '" data-nonce="' . esc_attr( $verify_nonce ) . '" onclick="(function(btn){ if(!confirm(\'Verificar y aceptar este pago?\')) return; btn.disabled=true; var fd=new FormData(); fd.append(\'action\',\'merc_verify_liquidation\' ); fd.append(\'user_id\', btn.getAttribute(\'data-user-id\')); fd.append(\'liq_id\', btn.getAttribute(\'data-liq-id\')); fd.append(\'nonce\', btn.getAttribute(\'data-nonce\')); fetch(\'' . admin_url('admin-ajax.php') . '\', { method: \'' . 'POST' . '\', body: fd }).then(r=>r.json()).then(function(res){ if(res && res.success){ alert(res.data.message||\'Verificado\'); location.reload(); } else { alert((res && res.data && res.data.message) ? res.data.message : JSON.stringify(res)); btn.disabled=false; } }).catch(function(){ alert(\'Error\'); btn.disabled=false; }); })(this);">Verificar</button>';
                    }
                } else {
                    $verify_button = ' <span class="merc-liquidations-verify" style="display:inline-block;padding:6px 8px;font-size:13px;">Verificado</span>';
                }
                // enlace con icono de ojo (SVG)
                $eye_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/><circle cx="12" cy="12" r="2.5"/></svg>';
                $view_link = '<a href="' . esc_url( $attachment_url ) . '" target="_blank" class="merc-liquidations-btn" title="Ver comprobante" style="color:#2563eb;">' . $eye_svg . '</a>';
                echo '<td>' . $view_link . $verify_button . '</td>';
            } else {
                echo '<td>-</td>';
            }

            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>'; // card padding
        echo '</div>'; // card
    }

    echo '</div>'; // cards container
    // Script para toggles de tarjetas (colapsar/expandir)
    echo '<script>document.addEventListener("DOMContentLoaded", function(){ var headers = document.querySelectorAll(".merc-user-header[data-target]"); headers.forEach(function(h){ h.addEventListener("click", function(){ var target = document.querySelector(h.getAttribute("data-target")); if(!target) return; var toggle = h.querySelector(".merc-card-toggle"); if(target.style.display === "none"){ target.style.display = "block"; if(toggle) toggle.textContent = "▾"; } else { target.style.display = "none"; if(toggle) toggle.textContent = "▸"; } }); }); });</script>';
}

// Hooks: cuando un envío se borra o va a la papelera, limpiar referencias en merc_liquidations
add_action( 'before_delete_post', 'merc_handle_shipment_deleted' );
add_action( 'trashed_post', 'merc_handle_shipment_deleted' );
add_action( 'delete_post', 'merc_handle_shipment_deleted' );
add_action( 'wp_trash_post', 'merc_handle_shipment_deleted' );
function merc_handle_shipment_deleted( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'wpcargo_shipment' ) return;

    error_log("[merc_handle_shipment_deleted] limpiando referencias para envio {$post_id}");

    // Buscar todos los usuarios que tengan merc_liquidations
    $users = get_users( array( 'meta_key' => 'merc_liquidations', 'number' => 0 ) );
    if ( empty( $users ) ) return;

    foreach ( $users as $user ) {
        $history = get_user_meta( $user->ID, 'merc_liquidations', true );
        if ( ! is_array( $history ) ) continue;
        $changed = false;
        foreach ( $history as $i => $entry ) {
            $removed_from_entry = false;

            // Normal case: lista 'shipments'
            if ( isset( $entry['shipments'] ) && is_array( $entry['shipments'] ) ) {
                $new_shipments = array_values( array_filter( $entry['shipments'], function( $s ) use ( $post_id ) { return intval( $s ) !== intval( $post_id ); } ) );
                if ( count( $new_shipments ) !== count( $entry['shipments'] ) ) {
                    $removed_from_entry = true;
                    if ( empty( $new_shipments ) ) {
                        unset( $history[ $i ] );
                    } else {
                        $history[ $i ]['shipments'] = $new_shipments;
                    }
                }
            }

            // Older/alternative keys: 'shipment' or 'shipment_id'
            if ( ! $removed_from_entry && isset( $entry['shipment'] ) && intval( $entry['shipment'] ) === intval( $post_id ) ) {
                unset( $history[ $i ] );
                $removed_from_entry = true;
            }
            if ( ! $removed_from_entry && isset( $entry['shipment_id'] ) && intval( $entry['shipment_id'] ) === intval( $post_id ) ) {
                unset( $history[ $i ] );
                $removed_from_entry = true;
            }

            // Fallback: buscar la ocurrencia del ID dentro de la entrada (serialized/json)
            if ( ! $removed_from_entry ) {
                try {
                    $serialized = @json_encode( $entry );
                } catch ( Exception $e ) {
                    $serialized = '';
                }
                if ( $serialized && strpos( $serialized, '"' . intval( $post_id ) . '"' ) !== false ) {
                    // eliminar la entrada completa por seguridad
                    unset( $history[ $i ] );
                    $removed_from_entry = true;
                }
            }

            if ( $removed_from_entry ) {
                $changed = true;
                error_log("[merc_handle_shipment_deleted] removido envio {$post_id} del historial de user {$user->ID}");
            }
        }
        if ( $changed ) {
            // reindex array
            $history = array_values( $history );
            update_user_meta( $user->ID, 'merc_liquidations', $history );
            error_log("[merc_handle_shipment_deleted] historial actualizado para user {$user->ID}");
        }
    }
}

// ---------------------------------------------------------------------------
// TRANSFERIR PAGO DE MOTORIZADO A MERC
// ---------------------------------------------------------------------------

function merc_transferir_pago_motorizado_a_merc( $shipment_id ) {
    $json = get_post_meta( $shipment_id, 'pod_payment_methods', true );
    
    if ( empty( $json ) ) {
        return;
    }
    
    $methods = json_decode( $json, true );
    if ( ! is_array( $methods ) ) {
        return;
    }
    
    // Buscar todos los pagos de tipo "efectivo" (Pago a Motorizado) y moverlos a pago_merc
    $pagos_transferidos = 0;
    $nuevo_array = array();
    
    foreach ( $methods as $method ) {
        if ( isset( $method['metodo'] ) && $method['metodo'] === 'efectivo' ) {
            // Crear el nuevo pago como "pago_merc" con los mismos datos
            $nuevo_pago = array(
                'metodo' => 'pago_merc',
                'monto' => $method['monto']
            );
            
            // Transferir también la imagen si existe
            if ( isset( $method['imagen_id'] ) ) {
                $nuevo_pago['imagen_id'] = $method['imagen_id'];
            }
            if ( isset( $method['imagen_url'] ) ) {
                $nuevo_pago['imagen_url'] = $method['imagen_url'];
            }
            
            // Agregar el pago como "pago_merc" (NO agregamos el efectivo original)
            $nuevo_array[] = $nuevo_pago;
            $pagos_transferidos++;
            
            error_log("✅ Movido pago efectivo → pago_merc - Envío: {$shipment_id}, Monto: {$method['monto']}");
        } else {
            // Mantener los demás métodos de pago tal como están
            $nuevo_array[] = $method;
        }
    }
    
    if ( $pagos_transferidos === 0 ) {
        error_log("No hay pagos en efectivo para transferir en envío {$shipment_id}");
        return;
    }
    
    // Guardar el JSON actualizado (sin los pagos en efectivo, ahora son pago_merc)
    $payment_methods_json = json_encode( $nuevo_array );
    update_post_meta( $shipment_id, 'pod_payment_methods', $payment_methods_json );
    
    error_log("✅ {$pagos_transferidos} pago(s) transferido(s) de efectivo a pago_merc en envío {$shipment_id}");
}

// ---------------------------------------------------------------------------
// AJAX: LIQUIDAR PAGO INDIVIDUAL
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_merc_liquidar_pago', 'merc_liquidar_pago_ajax' );
function merc_liquidar_pago_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'merc_liquidar_pago' ) ) {
        wp_send_json_error( array( 'message' => 'Seguridad: nonce inválido' ) );
    }
    if ( ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( array( 'message' => 'No tienes permisos para esta acción' ) );
    }

    $shipment_id = isset( $_POST['shipment_id'] ) ? intval( $_POST['shipment_id'] ) : 0;
    $tipo        = isset( $_POST['tipo'] ) ? sanitize_text_field( $_POST['tipo'] ) : '';

    if ( $shipment_id <= 0 || ! in_array( $tipo, array( 'motorizado', 'remitente' ), true ) ) {
        wp_send_json_error( array( 'message' => 'Datos inválidos' ) );
    }

    $shipment = get_post( $shipment_id );
    if ( ! $shipment || $shipment->post_type !== 'wpcargo_shipment' ) {
        wp_send_json_error( array( 'message' => 'Envío no encontrado' ) );
    }

    if ( 'motorizado' === $tipo ) {
        // Solo transferir pago de motorizado a MERC (sin cambiar estado)
        merc_transferir_pago_motorizado_a_merc( $shipment_id );
        update_post_meta( $shipment_id, 'wpcargo_fecha_entrega_efectivo', current_time( 'mysql' ) );
        $message = 'Efectivo entregado a MERC correctamente';
    } else {
        // La liquidación por remitente NO se realiza de forma individual.
        // Usar la acción de liquidación general ("Liquidar todo") para procesar remitentes.
        wp_send_json_error( array( 'message' => 'Liquidación por remitente sólo está permitida de forma general. Usa "Liquidar todo" para este remitente.' ) );
    }

    wp_send_json_success( array( 'message' => $message ) );
}

// ---------------------------------------------------------------------------
// AJAX: LIQUIDAR TODO POR USUARIO
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_merc_liquidar_todo', 'merc_liquidar_todo_ajax' );
function merc_liquidar_todo_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'merc_liquidar_todo' ) ) {
        wp_send_json_error( array( 'message' => 'Seguridad: nonce inválido' ) );
    }
    if ( ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( array( 'message' => 'No tienes permisos para esta acción' ) );
    }

    $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
    $tipo    = isset( $_POST['tipo'] ) ? sanitize_text_field( $_POST['tipo'] ) : '';

    if ( $user_id <= 0 || ! in_array( $tipo, array( 'motorizado', 'remitente' ), true ) ) {
        wp_send_json_error( array( 'message' => 'Datos inválidos' ) );
    }

    global $wpdb;

    if ( 'motorizado' === $tipo ) {
        $shipments = $wpdb->get_col( $wpdb->prepare( "
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_driver 
                ON p.ID = pm_driver.post_id 
                AND pm_driver.meta_key = 'wpcargo_driver'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status = 'publish'
            AND pm_driver.meta_value = %s
        ", $user_id ) );

        // Filtrar solo envíos del día actual (evitar procesar entregas de otras fechas)
        if ( is_array( $shipments ) && function_exists('merc_pickup_date_is_today') ) {
            $shipments = array_filter( $shipments, function( $sid ) {
                return merc_pickup_date_is_today( $sid );
            });
            $shipments = array_values( $shipments );
        }

        $count = 0;
        foreach ( $shipments as $shipment_id ) {
            // Verificar que tenga efectivo para transferir
            $json = get_post_meta( $shipment_id, 'pod_payment_methods', true );
            if ( !empty( $json ) ) {
                $methods = json_decode( $json, true );
                if ( is_array( $methods ) ) {
                    foreach ( $methods as $method ) {
                        if ( isset( $method['metodo'] ) && $method['metodo'] === 'efectivo' ) {
                            // Transferir pago de motorizado a MERC
                            merc_transferir_pago_motorizado_a_merc( $shipment_id );
                            update_post_meta( $shipment_id, 'wpcargo_fecha_entrega_efectivo', current_time( 'mysql' ) );
                            $count++;
                            break;
                        }
                    }
                }
            }
        }

        wp_send_json_success( array( 'message' => "Se procesaron {$count} entregas de efectivo del motorizado" ) );

    } else {
        // Liquidación masiva (remitente): cálculo agregado y requerir comprobante si viene en FILES
        // Para flujo híbrido: no filtrar por 'wpcargo_estado_pago_remitente' (la liquidación general
        // no depende del estado por envío). En su lugar excluimos envíos que ya fueron incluidos
        // en una liquidación previa mediante la meta 'wpcargo_included_in_liquidation'.
        $shipments = $wpdb->get_col( $wpdb->prepare( "
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_sender 
                ON p.ID = pm_sender.post_id 
                AND pm_sender.meta_key = 'registered_shipper'
            LEFT JOIN {$wpdb->postmeta} pm_included 
                ON p.ID = pm_included.post_id 
                AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status = 'publish'
            AND pm_sender.meta_value = %s
            AND (pm_included.meta_value IS NULL)
        ", $user_id ) );
        // Filtrar sólo envíos del día actual (evitar incluir reprogramados)
        if ( is_array( $shipments ) && function_exists('merc_pickup_date_is_today') ) {
            $shipments = array_filter( $shipments, function( $sid ) {
                return merc_pickup_date_is_today( $sid );
            });
            $shipments = array_values( $shipments );
        }
        if ( empty( $shipments ) ) {
            wp_send_json_error( array( 'message' => 'No hay envíos pendientes para este remitente' ) );
        }

        // Sumar totales: efectivo + pago_merc + pos  - pago_marca - servicios
        $efectivo_total = 0.0;
        $pago_merc_total = 0.0;
        $pos_total = 0.0;
        $pago_marca_total = 0.0;
        $servicios_total = 0.0;

        foreach ( $shipments as $shipment_id ) {
            $totales = get_payment_totals_by_method( $shipment_id );
            $efectivo_total += floatval( $totales['efectivo'] );
            $pago_merc_total += floatval( $totales['pago_merc'] );
            $pos_total += floatval( $totales['pos'] );
            $pago_marca_total += floatval( $totales['pago_marca'] );

            // Servicio entendido como costo de envío pendiente
            $costo_envio = floatval( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) );
            // Si el servicio ya fue marcado como cobrado, no contarlo
            $servicio_cobrado = get_post_meta( $shipment_id, 'wpcargo_servicio_cobrado', true );
            if ( ! $servicio_cobrado ) {
                $servicios_total += $costo_envio;
            }
        }

        // NUEVA LÓGICA: Solo transferir a MERC el monto correspondiente al SERVICIO.
        // Requerir comprobante (archivo) para la liquidación masiva de remitente
        $attachment_id = 0;
        if ( empty( $_FILES ) || empty( $_FILES['voucher'] ) ) {
            wp_send_json_error( array( 'message' => 'La liquidación requiere un comprobante (imagen).' ) );
        }

        // Manejar subida del archivo
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = $_FILES['voucher'];
        $overrides = array( 'test_form' => false );
        $movefile = wp_handle_upload( $file, $overrides );
        if ( isset( $movefile['error'] ) ) {
            wp_send_json_error( array( 'message' => 'Error al subir comprobante: ' . $movefile['error'] ) );
        }

        $filename = $movefile['file'];
        $filetype = wp_check_filetype( basename( $filename ), null );
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( basename( $filename ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        $attachment_id = wp_insert_attachment( $attachment, $filename );
        if ( ! is_wp_error( $attachment_id ) ) {
            $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
            wp_update_attachment_metadata( $attachment_id, $attach_data );
        }

        // LOG: depuración inicial
        $recaudado_merc = $efectivo_total + $pago_merc_total + $pos_total;
        error_log("[merc_liquidar_todo] remitente={$user_id} shipments_count=" . count($shipments) . " recaudado_merc={$recaudado_merc} pago_marca={$pago_marca_total} servicios={$servicios_total}");

        // Queremos que MERC reciba exactamente el total de servicios.
        // 1) Usar hasta lo disponible en lo recaudado por MERC (efectivo, pago_merc, pos)
        // 2) Si lo recaudado no alcanza, registrar que la MARCA debe la diferencia (sin mover fondos de pago_marca)

        $service_needed = round( floatval( $servicios_total ), 2 );
        $available = round( floatval( $recaudado_merc ), 2 );

        $to_cover = min( $available, $service_needed );
        $remaining_service = round( $service_needed - $to_cover, 2 ); // si >0, marca debe esto

        $liquidation = array(
            'id' => uniqid('liq_'),
            'date' => current_time( 'mysql' ),
            'recaudado_merc' => $recaudado_merc,
            'pago_marca' => $pago_marca_total,
            'servicios' => $servicios_total,
            'attachment_id' => $attachment_id
        );

        // Si hay fondos recaudados, moverlos desde las fuentes hacia 'pago_merc' (representa lo que MERC ya retuvo)
        if ( $to_cover > 0 ) {
            $remaining = $to_cover;
            error_log("[merc_liquidar_todo] cubrir servicio con recaudado={$to_cover}");
            // Fuentes: efectivo, pos, pago_merc (preferir efectivo/pos antes que tocar pago_merc)
            $sources = array( 'efectivo', 'pos', 'pago_merc' );

            foreach ( $shipments as $shipment_id ) {
                if ( $remaining <= 0 ) break;
                $json = get_post_meta( $shipment_id, 'pod_payment_methods', true );
                if ( empty( $json ) ) continue;
                $methods = json_decode( $json, true );
                if ( ! is_array( $methods ) ) continue;

                $changed = false;
                // Asegurarse que existe un método pago_merc para acumular
                $found_index_merc = null;
                foreach ( $methods as $i => $m ) {
                    if ( isset( $m['metodo'] ) && $m['metodo'] === 'pago_merc' ) { $found_index_merc = $i; break; }
                }

                foreach ( $sources as $src ) {
                    if ( $remaining <= 0 ) break;
                    foreach ( $methods as $idx => &$method ) {
                        if ( ! isset( $method['metodo'] ) ) continue;
                        if ( $method['metodo'] !== $src ) continue;
                        $available_amt = floatval( $method['monto'] );
                        if ( $available_amt <= 0 ) continue;
                        $move = min( $available_amt, $remaining );
                        $method['monto'] = round( $available_amt - $move, 2 );
                        // agregar/mover a pago_merc
                        if ( $found_index_merc !== null ) {
                            $methods[$found_index_merc]['monto'] = round( floatval( $methods[$found_index_merc]['monto'] ) + $move, 2 );
                        } else {
                            $methods[] = array( 'metodo' => 'pago_merc', 'monto' => round( $move, 2 ) );
                            $found_index_merc = count( $methods ) - 1;
                        }
                        $remaining = round( $remaining - $move, 2 );
                        $changed = true;
                        if ( $remaining <= 0 ) break 2;
                    }
                    unset( $method );
                }

                if ( $changed ) {
                    // Guardar cambios en methods y metas totales del envío
                    update_post_meta( $shipment_id, 'pod_payment_methods', json_encode( $methods ) );
                    // Recalcular totales por método
                    $tot_ef = $tot_pm = $tot_mm = $tot_pos = 0.0;
                    foreach ( $methods as $m ) {
                        $met = isset( $m['metodo'] ) ? $m['metodo'] : '';
                        $mon = isset( $m['monto'] ) ? floatval( $m['monto'] ) : 0.0;
                        if ( $met === 'efectivo' ) $tot_ef += $mon;
                        elseif ( $met === 'pago_merc' ) $tot_pm += $mon;
                        elseif ( $met === 'pago_marca' ) $tot_mm += $mon;
                        elseif ( $met === 'pos' ) $tot_pos += $mon;
                    }
                    update_post_meta( $shipment_id, 'wpcargo_efectivo', $tot_ef );
                    update_post_meta( $shipment_id, 'wpcargo_pago_merc', $tot_pm );
                    update_post_meta( $shipment_id, 'wpcargo_pago_marca', $tot_mm );
                    update_post_meta( $shipment_id, 'wpcargo_pos', $tot_pos );
                }
            }

            // Registrar que parte/total del servicio fue cubierto con recaudado
            $liquidation['covered_by_recaudado'] = $to_cover;
        }

        // Marcar servicios como cobrados y vincular envíos a esta liquidación
        $liquidation_id = $liquidation['id'];
        foreach ( $shipments as $shipment_id ) {
            update_post_meta( $shipment_id, 'wpcargo_servicio_cobrado', 'si' );
            update_post_meta( $shipment_id, 'wpcargo_included_in_liquidation', $liquidation_id );
            update_post_meta( $shipment_id, 'wpcargo_fecha_liquidacion_remitente', current_time( 'mysql' ) );
        }

        // Determinar actor e historial donde guardar la liquidación.
        $actor = get_current_user_id();
        $history_owner = ( $actor !== $user_id && current_user_can( 'administrator' ) ) ? $actor : $user_id;

        // Si queda diferencia, es deuda de la MARCA (no tocar pago_marca)
        if ( $remaining_service > 0 ) {
            $liquidation['action'] = 'marca_debe';
            $liquidation['amount'] = $remaining_service;
            // Si el admin está ejecutando la operación para el remitente, registrar la liquidación
            // bajo el admin y marcarla como verificada por el admin.
            if ( $history_owner !== $user_id ) {
                $liquidation['created_by'] = $actor;
                $liquidation['verified'] = true;
                $liquidation['verified_by'] = $actor;
                $liquidation['performed_for'] = $user_id;
            }
            $history = get_user_meta( $history_owner, 'merc_liquidations', true );
            if ( ! is_array( $history ) ) $history = array();
            $history[] = $liquidation;
            update_user_meta( $history_owner, 'merc_liquidations', $history );

            wp_send_json_success( array( 'message' => 'Liquidación completada: la MARCA debe S/. ' . number_format( $remaining_service, 2 ) . ' (registrado)' ) );
        }

        // Si no queda diferencia, todo el servicio quedó cubierto por lo recaudado
        $liquidation['action'] = 'servicio_cubierto';
        $liquidation['amount'] = $service_needed;

        // Calcular monto final según la fórmula solicitada por el flujo admin:
        // monto_final = (efectivo + pago_merc + pos) - servicios
        // Nota: NO restar 'pago_marca' aquí — fue erroneamente restado antes.
        $monto_final = round( ( $efectivo_total + $pago_merc_total + $pos_total ) - $servicios_total, 2 );
        if ( function_exists('current_user_can') && current_user_can('administrator') ) {
            error_log(sprintf('MERC_DEBUG_PAGAR_A_CLIENTE - shipment_calc efectivo=%01.2f pago_merc=%01.2f pos=%01.2f pago_marca=%01.2f servicios=%01.2f monto_final=%01.2f',
                $efectivo_total,
                $pago_merc_total,
                $pos_total,
                $pago_marca_total,
                $servicios_total,
                $monto_final
            ));
        }
        // Si el actor es un admin distinto al remitente, registrar como "Pagar a cliente"
        if ( $history_owner !== $user_id ) {
            $liquidation['created_by'] = $actor;
            $liquidation['verified'] = true;
            $liquidation['verified_by'] = $actor;
            $liquidation['performed_for'] = $user_id;
            $liquidation['action'] = 'pagar_a_cliente';
            $liquidation['amount'] = $monto_final;
            $liquidation['result'] = $monto_final;
        }

        $history = get_user_meta( $history_owner, 'merc_liquidations', true );
        if ( ! is_array( $history ) ) $history = array();
        $history[] = $liquidation;
        update_user_meta( $history_owner, 'merc_liquidations', $history );

        // NUEVO: Si es "pagar_a_cliente", también guardar en la meta del cliente para que lo vea
        if ( $history_owner !== $user_id && isset( $liquidation['action'] ) && $liquidation['action'] === 'pagar_a_cliente' ) {
            $client_history = get_user_meta( $user_id, 'merc_liquidations', true );
            if ( ! is_array( $client_history ) ) $client_history = array();
            // Agregar el mismo registro a la historia del cliente (sin changiar performed_for)
            $client_history[] = $liquidation;
            update_user_meta( $user_id, 'merc_liquidations', $client_history );
        }

        wp_send_json_success( array( 'message' => 'Liquidación completada: el servicio fue cubierto. S/. ' . number_format( $service_needed, 2 ) ) );

        // fin remitente
    }
}

// ---------------------------------------------------------------------------
// AJAX: OBTENER VOUCHER DE PAGO
// ---------------------------------------------------------------------------

add_action('wp_ajax_merc_get_voucher', 'merc_get_voucher_ajax');
function merc_get_voucher_ajax() {
    if (!isset($_POST['shipment_id']) || !isset($_POST['tipo'])) {
        wp_send_json_error('Datos incompletos');
    }
    
    $shipment_id = intval($_POST['shipment_id']);
    $tipo = sanitize_text_field($_POST['tipo']);
    
    error_log("=== BUSCANDO VOUCHER ===");
    error_log("Shipment ID: {$shipment_id}");
    error_log("Tipo solicitado: {$tipo}");
    
    // Verificar que el usuario tenga permiso
    $current_user = wp_get_current_user();
    if (!current_user_can('administrator') && !in_array('wpcargo_driver', $current_user->roles)) {
        wp_send_json_error('Sin permisos para ver vouchers');
    }
    
    // Obtener los métodos de pago guardados
    $payment_methods_json = get_post_meta($shipment_id, 'pod_payment_methods', true);
    
    error_log("JSON encontrado: " . ($payment_methods_json ? 'SI' : 'NO'));
    
    if (empty($payment_methods_json)) {
        wp_send_json_success(array(
            'voucher_url' => null,
            'message' => 'No hay métodos de pago registrados'
        ));
        return;
    }
    
    $methods = json_decode($payment_methods_json, true);
    
    error_log("Métodos decodificados: " . print_r($methods, true));
    
    if (!is_array($methods)) {
        wp_send_json_success(array(
            'vouchers' => array(),
            'message' => 'Error al leer los métodos de pago'
        ));
        return;
    }
    
    // El tipo puede ser un método específico (efectivo, pago_merc, pago_marca, pos)
    // o 'all' para devolver cualquier comprobante disponible.
    $search_method = $tipo;
    error_log("Buscando método: {$search_method}");

    // Buscar todos los métodos que coincidan (puede haber múltiples)
    $vouchers = array();
    foreach ($methods as $method) {
        $method_name = isset($method['metodo']) ? strtolower(trim($method['metodo'])) : '';

        // Si se solicita 'all', tomar cualquier imagen disponible
        if ($search_method === 'all') {
            if (isset($method['imagen_url']) && !empty($method['imagen_url'])) {
                $vouchers[] = array(
                    'url' => $method['imagen_url'],
                    'monto' => isset($method['monto']) ? $method['monto'] : 0
                );
                error_log("ALL MATCH encontrado! URL: " . $method['imagen_url']);
            } elseif (isset($method['imagen_id']) && intval($method['imagen_id']) > 0) {
                $u = wp_get_attachment_url(intval($method['imagen_id']));
                if ($u) {
                    $vouchers[] = array('url' => $u, 'monto' => isset($method['monto']) ? $method['monto'] : 0);
                    error_log("ALL MATCH encontrado por ID! URL: " . $u);
                }
            }
            continue;
        }

        if ($method_name === $search_method) {
            if (isset($method['imagen_url']) && !empty($method['imagen_url'])) {
                $vouchers[] = array(
                    'url' => $method['imagen_url'],
                    'monto' => isset($method['monto']) ? $method['monto'] : 0
                );
                error_log("MATCH encontrado! URL: " . $method['imagen_url']);
            } elseif (isset($method['imagen_id']) && intval($method['imagen_id']) > 0) {
                $u = wp_get_attachment_url(intval($method['imagen_id']));
                if ($u) {
                    $vouchers[] = array('url' => $u, 'monto' => isset($method['monto']) ? $method['monto'] : 0);
                    error_log("MATCH encontrado por ID! URL: " . $u);
                }
            }
        }
    }
    
    if (!empty($vouchers)) {
        wp_send_json_success(array(
            'vouchers' => $vouchers,
            'tipo' => $tipo
        ));
    } else {
        error_log("No se encontraron vouchers para el tipo: {$tipo}");
        wp_send_json_success(array(
            'vouchers' => array(),
            'message' => 'No hay vouchers cargados para este pago'
        ));
    }
}


// Ocultar todos los selects de "Assign shipment to" excepto Conductor
add_action( 'wp_footer', function () {
    // Solo ejecutar en la pantalla de contenedores del frontend
    if ( ! isset( $_GET['wpcsc'] ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Seleccionar y eliminar los campos: Cliente, Agente y Empleado
        var selectors = [
            'select[name="registered_shipper"]', // Cliente
            'select[name="agent_fields"]',       // Agente
            'select[name="wpcargo_employee"]'    // Empleado
        ];
        selectors.forEach(function (sel) {
            var el = document.querySelector(sel);
            if (el) {
                var group = el.closest('.form-group');
                if (group) {
                    group.parentNode.removeChild(group);
                }
            }
        });
    });
    </script>
    <?php
});

// Ocultar la tarjeta completa de "Publicar" en el formulario de contenedores (frontend)
add_action( 'wp_footer', function () {
    if ( ! isset($_GET['wpcsc']) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var publishCard = document.querySelector('#container-history');
        if (publishCard) {
            publishCard.remove();
        }
    });
    </script>
    <?php
});

// Cambiar el título "Assign shipment to" por "Selección de Conductor"
add_action( 'wp_footer', function () {
    if ( ! isset($_GET['wpcsc']) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var header = document.querySelector('#wpcfe-misc-assign-user .card-header');
        if (header && header.textContent.trim() === 'Assign shipment to') {
            header.textContent = 'Selección de Conductor';
        }
    });
    </script>
    <?php
});

// Personalizar botones en la vista de listado de contenedores
add_action('wp_footer', function() {
    ?>
    <style>
        /* Ocultar botones Ver en contenedores - SUPER AGRESIVO */
        a[href*="wpcsc=view"],
        a[href*="&wpcsc=view"],
        a[href*="?wpcsc=view"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
            left: -9999px !important;
        }
    </style>
    <script>
    console.log('=== SCRIPT DE CONTENEDORES CARGADO ===');
    
    (function() {
        function personalizarBotones() {
            // Cambiar "Editar" a "Asignar"
            document.querySelectorAll('a[href*="wpcsc=edit"]').forEach(function(link) {
                var textoLink = link.textContent.trim();
                if (textoLink === 'Editar' || textoLink === 'Edit') {
                    link.textContent = 'Asignar';
                }
            });
            
            // Ocultar "Ver" - solo CSS
            document.querySelectorAll('a[href*="wpcsc=view"]').forEach(function(link) {
                link.style.display = 'none';
            });
            
            // También ocultar por texto
            document.querySelectorAll('a').forEach(function(link) {
                var textoLink = link.textContent.trim();
                if ((textoLink === 'Ver' || textoLink === 'View') && link.getAttribute('href') && link.getAttribute('href').indexOf('wpcsc') > -1) {
                    link.style.display = 'none';
                }
            });
        }
        
        // Ejecutar una sola vez cuando cargue
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', personalizarBotones);
        } else {
            personalizarBotones();
        }
        
    })();
    </script>
    <?php
});

// Asignación automática de contenedor según tipo de envío
add_action('wp_footer', function() {
    // Ejecutar tanto en add como en update
    if (!isset($_GET['wpcfe']) || !in_array($_GET['wpcfe'], ['add', 'update'])) {
        return;
    }
    ?>
    <script>
    // VERSIÓN 2.0 - Asignación automática con detección mejorada
    jQuery(document).ready(function($) {
        console.log('🚀 Script de asignación automática V2.0 cargado - Modo: <?php echo $_GET['wpcfe']; ?>');
        console.log('⚙️ Limpiando listeners anteriores...');
        
        // Definir ajaxurl para frontend
        var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
        
        // Flag para evitar múltiples ejecuciones simultáneas
        var procesandoContenedor = false;
        
        // Variable global para almacenar el tipo de envío
        var tipoEnvioGlobal = '';
        var estadoActualGlobal = '';
        
        // Obtener el tipo de envío y estado actual si estamos en modo edición
        var urlParams = new URLSearchParams(window.location.search);
        var shipmentId = urlParams.get('id');
        var modoEdicion = urlParams.get('wpcfe') === 'update';
        
        if (modoEdicion && shipmentId) {
            console.log('📦 Modo edición detectado - ID:', shipmentId);
            console.log('⏳ Obteniendo tipo de envío y estado desde la base de datos...');
            
            // Obtener el tipo de envío y estado desde el backend
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                async: false, // Sincrónico para tener el valor antes de continuar
                data: {
                    action: 'merc_get_shipment_data',
                    shipment_id: shipmentId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        tipoEnvioGlobal = response.data.tipo_envio || '';
                        estadoActualGlobal = response.data.estado_actual || '';
                        console.log('✅ Tipo de envío obtenido:', tipoEnvioGlobal);
                        console.log('✅ Estado actual obtenido:', estadoActualGlobal);
                    } else {
                        console.log('⚠️ No se pudo obtener los datos del envío');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error al obtener datos del envío:', error);
                }
            });
        } else {
            // En modo creación, obtener desde el campo hidden o URL
            tipoEnvioGlobal = $('input[name="tipo_envio"]').val() || urlParams.get('type') || '';
            console.log('📝 Tipo de envío (modo creación):', tipoEnvioGlobal);
        }
        
        console.log('✅ Configuración inicial completa');
        
        // Función para obtener el tipo de envío desde múltiples fuentes
        function obtenerTipoEnvio() {
            // Primero intentar usar el valor global que ya obtuvimos
            if (tipoEnvioGlobal) {
                console.log('📌 Usando tipo de envío global:', tipoEnvioGlobal);
                return tipoEnvioGlobal;
            }
            
            // Fallback: intentar obtener desde el campo hidden
            var tipo = $('input[name="tipo_envio"]').val();
            if (tipo) {
                console.log('📝 Tipo de envío desde campo hidden:', tipo);
                return tipo;
            }
            
            // Fallback: desde la URL
            var urlParams = new URLSearchParams(window.location.search);
            tipo = urlParams.get('type');
            if (tipo) {
                console.log('🔗 Tipo de envío desde URL:', tipo);
                return tipo;
            }
            
            console.log('⚠️ No se pudo determinar el tipo de envío');
            return '';
        }
        
        // Función para buscar contenedor por distrito según tipo de envío
        function buscarContenedorPorDistrito(forzar, selectTarget) {
            selectTarget = selectTarget || null; // null = ambos (legacy), 'recojo', 'entrega'
            console.log('🔄 Iniciando búsqueda de contenedor automático... Target:', selectTarget);
            // Solo evitar múltiples ejecuciones si no es forzado
            if (!forzar && procesandoContenedor) {
                console.log('⏳ Ya se está procesando una búsqueda, ignorando...');
                return;
            }
            
            // Obtener tipo de envío
            var tipoEnvio = obtenerTipoEnvio();
            var distrito = '';
            var tipoDistrito = '';
            
            console.log('🔍 Buscando contenedor - Tipo de envío:', tipoEnvio);
            
            // Lógica para determinar qué distrito usar
            if (selectTarget === 'recojo') {
                // Siempre usar recojo para target recojo
                distrito = $('select[name="wpcargo_distrito_recojo"]').val();
                tipoDistrito = 'recojo';
                console.log('📍 Target RECOJO - Distrito:', distrito);
            }
            else if (selectTarget === 'entrega') {
                // Siempre usar destino para target entrega
                distrito = $('select[name="wpcargo_distrito_destino"]').val();
                tipoDistrito = 'destino';
                console.log('📍 Target ENTREGA - Distrito destino:', distrito);
            }
            else {
                // Sin selectTarget: usar lógica por tipo de envío
                // MERC AGENCIA (express): usar distrito de DESTINO
                if (tipoEnvio.toLowerCase() === 'express') {
                    distrito = $('select[name="wpcargo_distrito_destino"]').val();
                    tipoDistrito = 'destino';
                    console.log('📍 MERC AGENCIA - Distrito de destino:', distrito);
                }
                // MERC EMPRENDEDOR (normal): usar distrito de RECOJO
                else if (tipoEnvio.toLowerCase() === 'normal') {
                    distrito = $('select[name="wpcargo_distrito_recojo"]').val();
                    tipoDistrito = 'recojo';
                    console.log('📍 MERC EMPRENDEDOR - Distrito de recojo:', distrito);
                }

                // MERC FULL FITMENT (full_fitment): usar distrito de DESTINO
                else if (tipoEnvio.toLowerCase() === 'full_fitment') {
                    distrito = $('select[name="wpcargo_distrito_destino"]').val();
                    tipoDistrito = 'destino';
                    console.log('📍 MERC FULL FITMENT - Distrito de destino:', distrito);
                }
                else {
                    console.log('⚠️ Tipo de envío no reconocido para asignación automática:', tipoEnvio);
                    return;
                }
            }
            
            if (!distrito || distrito === '' || distrito === '-- Seleccione uno --') {
                console.log('⚠️ No hay distrito seleccionado');
                return;
            }
            
            // Marcar como procesando
            procesandoContenedor = true;
            console.log('⏳ Búsqueda AJAX para distrito:', distrito);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'merc_buscar_contenedor_por_distrito',
                    distrito: distrito,
                    tipo_envio: tipoEnvio
                },
                timeout: 10000, // 10 segundos timeout
                success: function(response) {
                    // Liberar el flag
                    procesandoContenedor = false;
                    console.log('✅ Respuesta AJAX recibida:', response);
                    
                    if (response.success && response.data.container_id) {
                        // Limpiar mensajes anteriores
                        $('.merc-container-asignado').remove();
                        
                        console.log('📦 Asignando contenedor ID:', response.data.container_id, '- Nombre:', response.data.container_name);
                        
                        var tipoEnvio = response.data.tipo_envio || obtenerTipoEnvio();
                        var es_merc = tipoEnvio.toLowerCase() === 'normal';
                        
                        // Determinar qué selects actualizar
                        if (selectTarget === 'recojo') {
                            // Solo actualizar recojo
                            var $recojoCont = $('select[name="shipment_container_recojo"]');
                            if ($recojoCont.length) {
                                $recojoCont.val(response.data.container_id).trigger('change');
                                console.log('✅ shipment_container_recojo asignado:', response.data.container_id);
                            }
                            var mensaje = '<div class="merc-container-asignado" style="background: #4CAF50; color: white; padding: 10px; margin: 10px 0; border-radius: 4px; font-weight: bold; position: fixed; top: 20px; right: 20px; z-index: 9999; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">✅ Contenedor RECOJO actualizado por distrito ' + tipoDistrito + ': ' + response.data.container_name + '</div>';
                        }
                        else if (selectTarget === 'entrega') {
                            // Solo actualizar entrega
                            var $entregaCont = $('select[name="shipment_container_entrega"]');
                            if ($entregaCont.length) {
                                $entregaCont.val(response.data.container_id).trigger('change');
                                console.log('✅ shipment_container_entrega asignado:', response.data.container_id);
                            }
                            var mensaje = '<div class="merc-container-asignado" style="background: #4CAF50; color: white; padding: 10px; margin: 10px 0; border-radius: 4px; font-weight: bold; position: fixed; top: 20px; right: 20px; z-index: 9999; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">✅ Contenedor ENTREGA actualizado por distrito ' + tipoDistrito + ': ' + response.data.container_name + '</div>';
                        }
                        else {
                            // Legacy: actualizar ambos selectores (para compatibilidad con express/full_fitment)
                            var $containerSelect = $('select[name="shipment_container"]');
                            var valorAnterior = $containerSelect.val();
                            $containerSelect.val(response.data.container_id);
                            
                            // Trigger change con jQuery y nativo con validación
                            $containerSelect.trigger('change');
                            if ($containerSelect[0]) {
                                try {
                                    $containerSelect[0].dispatchEvent(new Event('change', { bubbles: true }));
                                } catch(e) {
                                    console.log('⚠️ Error al disparar evento change del contenedor:', e);
                                }
                            }
                            
                            console.log('🔄 Contenedor actualizado de', valorAnterior, 'a', response.data.container_id);
                            
                            // PARA MERC EMPRENDEDOR: Asignar también a los dos nuevos selects
                            if (es_merc) {
                                console.log('✅ MERC EMPRENDEDOR detectado - Asignando a selects duales...');
                                
                                // Asignar al select de recojo
                                var $recojoCont = $('select[name="shipment_container_recojo"]');
                                if ($recojoCont.length) {
                                    $recojoCont.val(response.data.container_id).trigger('change');
                                    console.log('✅ shipment_container_recojo asignado:', response.data.container_id);
                                }
                                
                                // Asignar al select de entrega
                                var $entregaCont = $('select[name="shipment_container_entrega"]');
                                if ($entregaCont.length) {
                                    $entregaCont.val(response.data.container_id).trigger('change');
                                    console.log('✅ shipment_container_entrega asignado:', response.data.container_id);
                                }
                            }
                            
                            // Mostrar mensaje temporal indicando qué distrito se usó
                            var selectsInfo = es_merc ? ' (Recojo + Entrega)' : '';
                            var mensaje = '<div class="merc-container-asignado" style="background: #4CAF50; color: white; padding: 10px; margin: 10px 0; border-radius: 4px; font-weight: bold; position: fixed; top: 20px; right: 20px; z-index: 9999; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">✅ Contenedor actualizado' + selectsInfo + ' por distrito de ' + tipoDistrito + ': ' + response.data.container_name + '</div>';
                        }
                        
                        // Agregar el mensaje al body para que aparezca como notificación flotante
                        $('body').append(mensaje);
                        
                        setTimeout(function() {
                            $('.merc-container-asignado').fadeOut(function() {
                                $(this).remove();
                            });
                        }, 4000);
                    } else {
                        console.log('⚠️ No se encontró contenedor para:', distrito);
                        // No limpiar el contenedor en modo edición, solo mostrar mensaje
                        var urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.get('wpcfe') === 'add') {
                            $('select[name="shipment_container"]').val('').trigger('change');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    procesandoContenedor = false;
                    console.error('❌ Error al buscar contenedor:', error);
                    if (status === 'timeout') {
                        console.error('⏱️ Timeout - búsqueda tardó demasiado');
                    }
                }
            });
        }
        
        // Throttle timer para evitar llamadas múltiples
        var throttleTimer = null;
        
        // Detectar cambio en el select de distrito de DESTINO (para MERC AGENCIA, FULL FITMENT y MERC ENTREGA)
        // IMPORTANTE: consolidamos en un único handler para evitar que múltiples .off() se sobreescriban
        $(document).off('change.containerAssignment', 'select[name="wpcargo_distrito_destino"]')
                   .on('change.containerAssignment', 'select[name="wpcargo_distrito_destino"]', function() {
            var tipoEnvio = (obtenerTipoEnvio() || '').toLowerCase().trim();
            console.log('🔄 Distrito destino cambiado - tipoEnvio:', tipoEnvio, 'valor:', $(this).val());
            if (tipoEnvio === 'express' || tipoEnvio === 'full_fitment') {
                // Para express y full_fitment: buscar sin selectTarget (actualiza legacy)
                clearTimeout(throttleTimer);
                throttleTimer = setTimeout(function() {
                    buscarContenedorPorDistrito(true);
                }, 500);
            }
            else if (tipoEnvio === 'normal') {
                // Para MERC EMPRENDEDOR: actualizar solo shipment_container_entrega
                clearTimeout(throttleTimer);
                throttleTimer = setTimeout(function() {
                    buscarContenedorPorDistrito(true, 'entrega');
                }, 500);
            }
        });
        
        // Detectar cambio en el select de distrito de RECOJO (para MERC EMPRENDEDOR)
        $(document).off('change.containerAssignment', 'select[name="wpcargo_distrito_recojo"]')
                   .on('change.containerAssignment', 'select[name="wpcargo_distrito_recojo"]', function() {
            var tipoEnvio = obtenerTipoEnvio();
            if (tipoEnvio.toLowerCase() === 'normal') {
                console.log('🔄 Distrito recojo:', $(this).val());
                // Throttle: esperar 500ms antes de buscar
                // Para MERC EMPRENDEDOR recojo: actualizar solo shipment_container_recojo
                clearTimeout(throttleTimer);
                throttleTimer = setTimeout(function() {
                    buscarContenedorPorDistrito(true, 'recojo');
                }, 500);
            }
        });

        /* El handler para full_fitment ya está cubierto por el handler consolidado arriba. */
        
        // Establecer estado por defecto según tipo de envío y hacer observaciones opcionales
        setTimeout(function() {
            // Solo establecer estado en modo creación (add), NO en edición (update)
            var modoCreacion = urlParams.get('wpcfe') === 'add';
            // Buscar el select de estado en múltiples posibilidades
            var estadoSelect = $('select[name="status"]').length > 0 
                ? $('select[name="status"]') 
                : ($('select[name="wpcargo_status"]').length > 0 
                    ? $('select[name="wpcargo_status"]') 
                    : $('select.merc-estado-select'));
            
            if (modoCreacion) {
                var tipoEnvio = obtenerTipoEnvio();
                
                if (estadoSelect.length && estadoSelect.val() === '') {
                    if (tipoEnvio.toLowerCase() === 'normal') {
                        // MERC EMPRENDEDOR: estado por defecto PENDIENTE
                        var pendienteOption = estadoSelect.find('option').filter(function() {
                            return $(this).text().toLowerCase().includes('pendiente') || 
                                   $(this).text().toLowerCase().includes('pending');
                        });
                        
                        if (pendienteOption.length) {
                            estadoSelect.val(pendienteOption.val()).trigger('change');
                            console.log('✅ Estado establecido en PENDIENTE (MERC EMPRENDEDOR)');
                        }
                    } else if (tipoEnvio.toLowerCase() === 'express' || tipoEnvio.toLowerCase() === 'full_fitment') {
                        // MERC AGENCIA: estado por defecto RECEPCIONADO
                        var recepcionadoOption = estadoSelect.find('option').filter(function() {
                            return $(this).text().toLowerCase().includes('RECEPCIONADO');
                        });
                        
                        if (recepcionadoOption.length) {
                            estadoSelect.val(recepcionadoOption.val()).trigger('change');
                            console.log('✅ Estado establecido en RECEPCIONADO (MERC AGENCIA/FULLFITMENT)');
                        }
                    }
                }
            } else {
                // Modo edición: usar el estado obtenido desde la base de datos
                console.log('ℹ️ Modo edición - usando estado desde BD:', estadoActualGlobal);
                
                if (estadoActualGlobal && estadoSelect.length) {
                    console.log('🔄 Pre-seleccionando estado:', estadoActualGlobal);
                    
                    var encontrado = false;
                    estadoSelect.find('option').each(function() {
                        var optionText = $(this).text().trim();
                        var optionValue = $(this).val();
                        
                        if (optionText.toUpperCase() === estadoActualGlobal.toUpperCase() ||
                            optionValue.toUpperCase() === estadoActualGlobal.toUpperCase()) {
                            
                            estadoSelect.val(optionValue);
                            encontrado = true;
                            console.log('✅ Estado pre-seleccionado en el select:', estadoActualGlobal);
                            return false; // break
                        }
                    });
                    
                    if (!encontrado) {
                        console.log('⚠️ No se encontró opción para:', estadoActualGlobal);
                        console.log('Opciones disponibles:', estadoSelect.find('option').map(function() {
                            return $(this).text() + ' (value: ' + $(this).val() + ')';
                        }).get());
                    }
                    
                    // Ocultar el estado del label si aparece
                    $('label, h4, h5, h3, div').each(function() {
                        var textoCompleto = $(this).clone().children().remove().end().text().trim();
                        if (textoCompleto.match(/^Historial\s+[A-Z]/i)) {
                            $(this).contents().each(function() {
                                if (this.nodeType === 3 && this.textContent.includes(estadoActualGlobal)) {
                                    this.textContent = this.textContent.replace(estadoActualGlobal, '').trim();
                                    console.log('🗑️ Estado removido del label');
                                }
                            });
                        }
                    });
                } else {
                    console.log('⚠️ No se pudo obtener el estado actual desde la BD');
                }
            }
            
            // Hacer observaciones opcionales (quitar asterisco rojo si existe)
            var remarksLabel = $('label[for*="remarks"], label:contains("Observaciones"), label:contains("Remarks")');
            remarksLabel.find('.required, .text-danger, span:contains("*")').remove();
            remarksLabel.css('font-weight', 'normal');
            
            // Quitar el atributo required del campo
            $('textarea[name="remarks"], input[name="remarks"]').removeAttr('required');
            
            console.log('Observaciones configuradas como opcionales');
        }, 1500);
    });
    </script>
    <?php
});

// AJAX handler para obtener los datos del shipment (tipo de envío y estado)
add_action('wp_ajax_merc_get_shipment_data', 'merc_get_shipment_data_ajax');
function merc_get_shipment_data_ajax() {
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    
    error_log("📥 [GET_SHIPMENT_DATA_AJAX] Solicitud recibida para Envío #{$shipment_id}");
    
    if (empty($shipment_id)) {
        error_log("❌ [GET_SHIPMENT_DATA_AJAX] ID de envío vacío");
        wp_send_json_error(['message' => 'ID de envío vacío']);
    }
    
    // Obtener el tipo de envío y el estado actual
    $tipo_envio = get_post_meta($shipment_id, 'tipo_envio', true);
    
    // Obtener el estado actual
    $estado_actual = get_post_meta($shipment_id, 'wpcargo_status', true);
    error_log("   🔹 Estado Actual Meta: '{$estado_actual}'");
    
    // Obtener estado anterior: primero desde meta directo, luego del historial
    $estado_prev = get_post_meta($shipment_id, 'wpcargo_status_anterior', true);
    error_log("   🔹 Estado Anterior Meta: '{$estado_prev}'");
    
    // Si no hay meta de estado anterior, intentar obtener del historial
    if (empty($estado_prev)) {
        error_log("   ℹ️  No hay estado anterior en meta, buscando en historial...");
        
        $historial = get_post_meta($shipment_id, 'wpcargo_shipments_update', true);
        error_log("   🔹 Historial recuperado: " . (is_array($historial) ? count($historial) . " registros" : "NO ES ARRAY"));
        
        $historial_ordenado = array();
        
        // Procesizar historial si existe
        if (!empty($historial) && is_array($historial)) {
            // Intentar ordenar usando la función de WPCargo si existe
            if (function_exists('wpcargo_history_order')) {
                $historial_ordenado = wpcargo_history_order($historial);
                error_log("   ✅ Historial ordenado con wpcargo_history_order");
            } else {
                // Si no existe, asumir que ya está en orden o usar como está
                $historial_ordenado = $historial;
                error_log("   ℹ️  wpcargo_history_order no existe, usando historial tal cual");
            }
            
            // Validar que sea un array
            if (!is_array($historial_ordenado)) {
                error_log("   ❌ Historial ordenado no es array");
                $historial_ordenado = array();
            } else {
                error_log("   ✅ Historial ordenado: " . count($historial_ordenado) . " registros");
            }
        }
        
        // Obtener estado previo - con lógica mejorada
        if (!empty($historial_ordenado)) {
            // Primero verificar si el registro [0] es reciente (nuestro registro de "actualización masiva")
            $first_record = $historial_ordenado[0];
            $use_first = false;
            
            if (is_array($first_record) && isset($first_record['remarks'])) {
                // Si contiene "actualización masiva", es nuestro registro
                if (stripos($first_record['remarks'], 'actualización masiva') !== false) {
                    $use_first = true;
                    error_log("   🎯 Registro [0] identificado como nuestro registro de actualización masiva");
                }
            }
            
            if ($use_first && is_array($first_record)) {
                $estado_prev = $first_record['status'] ?? '';
                error_log("   ✅ Estado Anterior obtenido de [0] (actualización masiva): '{$estado_prev}'");
            } elseif (count($historial_ordenado) > 1) {
                // Si no es nuestro registro, usar el segundo
                error_log("   🔎 Buscando estado previo en historial (index 1)...");
                
                if (isset($historial_ordenado[1]) && is_array($historial_ordenado[1])) {
                    $estado_prev = $historial_ordenado[1]['status'] ?? '';
                    error_log("   ✅ Estado Anterior encontrado (array): '{$estado_prev}'");
                } elseif (isset($historial_ordenado[1]) && is_string($historial_ordenado[1])) {
                    // Si es un string directo
                    $estado_prev = $historial_ordenado[1];
                    error_log("   ✅ Estado Anterior encontrado (string): '{$estado_prev}'");
                } else {
                    error_log("   ⚠️  Formato inesperado en historial[1]");
                }
            } elseif (is_array($first_record)) {
                // Si solo hay un registro y no es nuestro, usarlo como estado anterior
                $estado_prev = $first_record['status'] ?? '';
                error_log("   ✅ Solo hay 1 registro, usando como estado anterior: '{$estado_prev}'");
            } else {
                error_log("   ⚠️  Historial vacío o solo 1 registro");
            }
        } else {
            error_log("   ⚠️  Historial vacío o sin registros");
        }
    } else {
        error_log("   ✅ Estado Anterior encontrado en meta: '{$estado_prev}'");
    }
    
    // Normalizar espacios y mayúsculas para consister comparación
    $estado_actual = trim($estado_actual);
    $estado_prev = trim($estado_prev);
    
    error_log("🔍 [GET_SHIPMENT_DATA_RESULT] Envío #{$shipment_id} - Actual: '{$estado_actual}' | Anterior: '{$estado_prev}'");
    
    // intentar resolver cliente asociado
    $customer_id = get_post_meta($shipment_id, 'wpcargo_customer_id', true);
    if ( empty($customer_id) ) {
        $customer_id = get_post_meta($shipment_id, 'registered_shipper', true);
    }
    if ( empty($customer_id) ) {
        $author = get_post_field('post_author', $shipment_id);
        if ( $author ) $customer_id = $author;
    }
    $customer_name = '';
    if ( ! empty($customer_id) ) {
        $u = get_userdata( intval($customer_id) );
        if ( $u ) $customer_name = trim($u->first_name . ' ' . $u->last_name) ?: $u->display_name;
    }

    wp_send_json_success([
        'tipo_envio' => $tipo_envio,
        'estado_actual' => $estado_actual,
        'estado_prev' => $estado_prev,
        'shipment_id' => $shipment_id,
        'customer_id' => $customer_id,
        'customer_name' => $customer_name,
    ]);
}

// ===============================================
// PLANIFICADOR DE RUTAS: USAR LINK GOOGLE MAPS (CON FILTRO DE FECHA)
// ===============================================

/**
 * Función auxiliar: Extraer coordenadas de un Link de Google Maps
 * Soporta formatos:
 * - https://www.google.com/maps/@-11.884586,-77.070465,17z
 * - https://www.google.com/maps/place/@-11.884586,-77.070465
 * - https://maps.app.goo.gl/xyz (requiere seguir redirect)
 */
function merc_extraer_coordenadas_google_maps($url) {
    if (empty($url)) {
        return null;
    }
    
    // Patrón 1: URL con @ seguida de lat,lng
    // Ejemplo: @-11.884586,-77.070465
    if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
        $lat = floatval($matches[1]);
        $lng = floatval($matches[2]);
        
        // Validar que las coordenadas sean válidas para Perú (aproximadamente)
        // Perú: Lat entre -18.5 y 0, Lng entre -81.5 y -68.5
        if ($lat >= -18.5 && $lat <= 0 && $lng >= -81.5 && $lng <= -68.5) {
            return [
                'lat' => $lat,
                'lng' => $lng
            ];
        }
    }
    
    // Patrón 2: URL acortada de Google Maps (maps.app.goo.gl)
    // Nota: Esto requeriría hacer una petición HTTP para seguir el redirect
    // Por ahora lo dejamos como fallback sin implementar
    
    return null;
}

/**
 * Filtro: Modificar direcciones antes de enviarlas al planificador
 * Si existe link_maps, extraer coordenadas y usarlas directamente
 */
add_filter('wpcpod_route_shipment_address', 'merc_usar_link_maps_en_planificador', 10, 1);
function merc_usar_link_maps_en_planificador($address) {
    // El plugin no pasa el shipment_id al filtro, así que necesitamos
    // usar una aproximación: extraer el ID desde la pila de llamadas
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    $shipment_id = null;
    
    // Buscar en la pila de llamadas la función wpcpod_route_addresses
    // que tiene el objeto $shipment con la propiedad id
    foreach ($backtrace as $i => $trace) {
        if (isset($trace['function']) && $trace['function'] === 'wpcpod_route_addresses') {
            // El siguiente frame debería tener los args con el objeto shipment
            // Pero como usamos DEBUG_BACKTRACE_IGNORE_ARGS, necesitamos otro enfoque
            break;
        }
    }
    
    // Enfoque alternativo: Usar una expresión regular para extraer el ID
    // desde cualquier identificador en la dirección (si existe)
    // O mejor aún, almacenar el ID en una variable global temporal
    
    // Por ahora, usaremos un hook diferente que modifique el array completo
    // En lugar de modificar cada dirección individualmente
    
    return $address;
}

/**
 * Reemplazar la función del planificador para usar Link de Google Maps
 * Se ejecuta antes que la función original del plugin
 * 🔥 ACTUALIZADO: Ahora filtra por fecha de envío del día actual
 */
add_action('wp_ajax_wpcpod_generate_route_address', 'merc_planificador_con_link_maps', 1);
function merc_planificador_con_link_maps() {
    // 🔥 NUEVO: Obtener fecha del filtro (viene del JavaScript)
    // Esperamos recibir 'd/m/Y' desde el cliente; por seguridad normalizamos a d/m/Y
    $filter_date_raw = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : date('d/m/Y');
    // Normalizar separadores: aceptar tanto '-' como '/'
    $filter_date = str_replace('-', '/', $filter_date_raw);

    error_log("📅 Planificador de entregas - Filtrando por fecha (normalizada): {$filter_date} (raw: {$filter_date_raw})");
    
    // Obtener las direcciones usando la función original del plugin
    $shipments = wpcpod_route_shipments();
    $route_fields = wpcpod_route_fields();
    $route_origin = wpcpod_route_origin();

    // ---- DEBUG: registrar petición y los shipments recibidos (resumen) ----
    try {
        error_log('MERC_ROUTE_REQUEST_POST: ' . json_encode($_POST));

        $count_shipments = is_array($shipments) ? count($shipments) : 0;
        error_log('MERC_ROUTE_RAW_SHIPMENTS_COUNT: ' . $count_shipments);

        $meta_keys = array('wpcargo_pickup_date_picker','wpcargo_pickup_date','calendarenvio','wpcargo_fecha_envio');
        $sample = array();

        if (!empty($shipments) && is_array($shipments)) {
            foreach ($shipments as $s) {
                $sid = isset($s->id) ? $s->id : (isset($s->ID) ? $s->ID : null);
                $num = isset($s->number) ? $s->number : (isset($s->post_title) ? $s->post_title : '');
                $meta_vals = array();

                if ($sid) {
                    foreach ($meta_keys as $mk) {
                        $meta_vals[$mk] = get_post_meta($sid, $mk, true);
                    }
                    $meta_vals['wpcargo_driver'] = get_post_meta($sid, 'wpcargo_driver', true);
                    $meta_vals['wpcargo_motorizo_recojo'] = get_post_meta($sid, 'wpcargo_motorizo_recojo', true);
                    $meta_vals['wpcargo_status'] = get_post_meta($sid, 'wpcargo_status', true);
                    $meta_vals['tipo_envio'] = get_post_meta($sid, 'tipo_envio', true);
                }

                $sample[] = array('id' => $sid, 'number' => $num, 'meta' => $meta_vals);
            }
        }

        error_log('MERC_ROUTE_RAW_SHIPMENTS: ' . json_encode($sample));
        error_log('MERC_ROUTE_FIELDS: ' . json_encode($route_fields));
        error_log('MERC_ROUTE_ORIGIN: ' . json_encode($route_origin));
    } catch (Exception $e) {
        error_log('MERC_ROUTE_DEBUG_ERROR: ' . $e->getMessage());
    }
    // ---- end debug ----
    
    if (empty(get_option('shmap_api'))) {
        wp_send_json([
            'status' => 'error',
            'message' => __('Google API key required to run the Driver Route Planner.', 'wpcargo-pod')
        ]);
        wp_die();
    }
    
    if (empty($shipments)) {
        wp_send_json([
            'status' => 'error',
            'message' => __('No Delivery for route found.', 'wpcargo-pod')
        ]);
        wp_die();
    }
    
    // 🔥 NUEVO: Filtrar shipments por fecha
    $shipments_filtrados = array();
    $total_original = count($shipments);
    
    // Aceptar varias claves de meta que puedan contener la fecha de envío
    $meta_pickup_keys = array('wpcargo_pickup_date_picker','wpcargo_pickup_date','calendarenvio','wpcargo_fecha_envio');

    foreach ($shipments as $shipment) {
        $shipment_id = $shipment->id;
        $matched = false;
        $found_values = array();

        foreach ($meta_pickup_keys as $mk) {
            $val = get_post_meta($shipment_id, $mk, true);
            if (!empty($val)) {
                $found_values[] = "{$mk}='{$val}'";
                // Normalizar valor: cambiar '-' a '/' y tomar solo la parte de fecha
                $val_norm = trim(explode(' ', str_replace('-', '/', $val))[0]);

                // Intentar parsear la fecha en varios formatos (soporta '22/2/2026' y '22/02/2026' y '2026-02-22')
                $date_obj = false;
                // Formatos comunes: 'Y-m-d', 'd/m/Y' (con ceros), 'j/n/Y' (sin ceros)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val_norm)) {
                    $date_obj = DateTime::createFromFormat('Y-m-d', $val_norm);
                } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $val_norm)) {
                    // Primero intentar 'j/n/Y' (sin ceros)
                    $date_obj = DateTime::createFromFormat('j/n/Y', $val_norm);
                    if (! $date_obj) {
                        $date_obj = DateTime::createFromFormat('d/m/Y', $val_norm);
                    }
                } else {
                    // Último recurso: intentar strtotime para formatos extraños
                    $ts = strtotime($val_norm);
                    if ($ts !== false) {
                        $date_obj = (new DateTime())->setTimestamp($ts);
                    }
                }

                if ($date_obj instanceof DateTime) {
                    $d = $date_obj->format('d/m/Y');
                    if ($d === $filter_date) { $matched = true; break; }
                } else {
                    // Si no se pudo parsear, comparar de forma más tolerante (ignorar ceros a la izquierda)
                    $cmp1 = preg_replace('/\b0(\d)\b/', '$1', $val_norm);
                    $cmp2 = preg_replace('/\b0(\d)\b/', '$1', $filter_date);
                    if ($cmp1 === $cmp2) { $matched = true; break; }
                }
            }
        }

        if ($matched) {
            $shipments_filtrados[] = $shipment;
        } else {
            error_log("⏭️ Shipment #{$shipment_id} omitido - pickup metas: " . implode(', ', $found_values) . " (esperada: {$filter_date})");
        }
    }
    
    // Reemplazar $shipments con los filtrados
    $shipments = $shipments_filtrados;
    $total_filtrado = count($shipments);
    
    error_log("✅ Entregas filtradas: {$total_filtrado} de {$total_original} para la fecha {$filter_date}");
    
    // Verificar si hay shipments después del filtro
    if (empty($shipments)) {
        wp_send_json([
            'status' => 'error',
            'message' => sprintf(__('No hay entregas programadas para el %s', 'wpcargo-pod'), $filter_date)
        ]);
        wp_die();
    }
    
    $addresses = [];
    
    // Procesar cada shipment
    foreach ($shipments as $shipment) {
        $shipment_id = $shipment->id;
        
        // Primero intentar obtener coordenadas del Link de Google Maps
        $link_maps = get_post_meta($shipment_id, 'link_maps', true);
        $coords = null;
        
        if (!empty($link_maps)) {
            $coords = merc_extraer_coordenadas_google_maps($link_maps);
        }
        
        if ($coords !== null) {
            // Usar coordenadas directamente
            $direccion_final = $coords['lat'] . ',' . $coords['lng'];
            error_log("✅ Planificador: Shipment #{$shipment_id} - Usando coordenadas del Link Google Maps: {$direccion_final}");
        } else {
            // Fallback: construir dirección de texto desde los campos configurados
            $_address = '';
            foreach ($route_fields as $key) {
                $value = maybe_unserialize(get_post_meta($shipment_id, $key, true));
                if (is_array($value)) {
                    $value = implode(" ", $value);
                }
                if (!empty(trim($value))) {
                    $_address .= $value . ' ';
                }
            }
            $_address = apply_filters('wpcpod_route_shipment_address', $_address);
            $direccion_final = trim($_address);
            
            if (empty($direccion_final)) {
                error_log("⚠️ Planificador: Shipment #{$shipment_id} - Sin dirección válida, omitiendo");
                continue;
            }
            
            error_log("ℹ️ Planificador: Shipment #{$shipment_id} - Usando dirección de texto: {$direccion_final}");
        }
        
        $addresses[$shipment_id] = [
            'number' => $shipment->number,
            'address' => $direccion_final
        ];
    }
    
    // Configurar origen
    $poo = true;
    if (!empty($route_origin['address'])) {
        $origin = [
            'id' => null,
            'number' => __('Point of Origin', 'wpcargo-pod'),
            'address' => $route_origin['address']
        ];
    } else {
        $poo = false;
        $key = key($addresses);
        $origin = [
            'id' => $key,
            'number' => $addresses[$key]['number'],
            'address' => $addresses[$key]['address']
        ];
    }
    
    // Calcular distancias y ordenar
    $waypoints = [];
    $shipments_data = [];
    $counter = 1;
    
    foreach ($addresses as $shipmentID => $shipment) {
        $shipmentNumber = $shipment['number'];
        $destination = urlencode($shipment['address']);
        
        $distance_data = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?&origins=' . urlencode($origin['address']) . '&destinations=' . $destination . '&key=' . get_option('shmap_api'));
        $distance_arr = json_decode($distance_data);
        
        if ($distance_arr->status == 'OK' && $distance_arr->rows[0]->elements[0]->status == 'OK') {
            $distance = $distance_arr->rows[0]->elements[0]->distance->value;
            $data = [
                'id' => $shipmentID,
                'number' => $shipmentNumber,
                'address' => urldecode($destination),
                'link_maps' => get_post_meta($shipmentID, 'link_maps', true), // 🔥 NUEVO: Incluir link_maps
                'pickup_date' => get_post_meta($shipmentID, 'wpcargo_pickup_date_picker', true), // 🔥 NUEVO: Incluir fecha
                'status' => get_post_meta($shipmentID, 'wpcargo_status', true) ?: 'PENDIENTE', // 🔥 NUEVO: Incluir status
            ];
            
            $waypoints[$distance] = $data;
            $shipments_data[$distance] = $data;
        } else {
            $data = [
                'id' => $shipmentID,
                'number' => $shipmentNumber,
                'address' => urldecode($destination),
                'link_maps' => get_post_meta($shipmentID, 'link_maps', true), // 🔥 NUEVO: Incluir link_maps
                'pickup_date' => get_post_meta($shipmentID, 'wpcargo_pickup_date_picker', true), // 🔥 NUEVO: Incluir fecha
                'status' => get_post_meta($shipmentID, 'wpcargo_status', true) ?: 'PENDIENTE', // 🔥 NUEVO: Incluir status
            ];
            $waypoints[$counter] = $data;
            $shipments_data[$counter] = $data;
        }
        $counter++;
    }
    
    ksort($waypoints);
    ksort($shipments_data);
    $shipments_data = array_values($shipments_data);
    $waypoints = array_values($waypoints);
    
    if (count($waypoints) == 0) {
        $destination = $origin;
    } else {
        $destination = array_pop($waypoints);
    }
    
    $result = [
        'status' => 'success',
        'waypoints' => $waypoints,
        'origin' => $origin,
        'destination' => $destination,
        'shipments' => $shipments_data,
        'poo' => $poo
    ];
    
    error_log("🎯 Resultado final: " . count($shipments_data) . " entregas para {$filter_date}");
    
    wp_send_json($result);
    wp_die();
}

// AJAX handler para obtener el tipo de envío de un shipment (legacy - mantener por compatibilidad)
add_action('wp_ajax_merc_get_shipment_tipo_envio', 'merc_get_shipment_tipo_envio_ajax');
function merc_get_shipment_tipo_envio_ajax() {
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    
    if (empty($shipment_id)) {
        wp_send_json_error(['message' => 'ID de envío vacío']);
    }
    
    // Obtener el tipo de envío desde la meta
    $tipo_envio = get_post_meta($shipment_id, 'tipo_envio', true);
    
    if (empty($tipo_envio)) {
        wp_send_json_error(['message' => 'Tipo de envío no encontrado']);
    }
    
    wp_send_json_success([
        'tipo_envio' => $tipo_envio,
        'shipment_id' => $shipment_id
    ]);
}

// AJAX handler para buscar contenedor por distrito - OPTIMIZADO CON CACHÉ
add_action('wp_ajax_merc_buscar_contenedor_por_distrito', 'merc_buscar_contenedor_ajax');
add_action('wp_ajax_nopriv_merc_buscar_contenedor_por_distrito', 'merc_buscar_contenedor_ajax');
function merc_buscar_contenedor_ajax() {
    $distrito = isset($_POST['distrito']) ? sanitize_text_field($_POST['distrito']) : '';
    $tipo_envio = isset($_POST['tipo_envio']) ? sanitize_text_field($_POST['tipo_envio']) : '';
    
    if (empty($distrito)) {
        wp_send_json_error(['message' => 'Distrito vacío']);
        wp_die();
    }
    
    // Normalizar distrito
    $distrito_normalizado = strtolower(trim($distrito));
    $distrito_normalizado = remove_accents($distrito_normalizado);
    
    // Usar caché transient para los contenedores (5 minutos)
    $cache_key = 'merc_containers_list';
    $containers = get_transient($cache_key);
    
    if (false === $containers) {
        global $wpdb;
        
        // Consulta optimizada - solo ID y título
        $query = $wpdb->prepare(
            "SELECT ID, post_title FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_status = %s 
            ORDER BY post_title ASC 
            LIMIT 50",
            'shipment_container',
            'publish'
        );
        
        $containers = $wpdb->get_results($query);
        
        // Guardar en caché por 5 minutos
        set_transient($cache_key, $containers, 5 * MINUTE_IN_SECONDS);
    }
    
    if (empty($containers)) {
        wp_send_json_error(['message' => 'No hay contenedores']);
        wp_die();
    }
    
    // Buscar coincidencia
    $container_encontrado = null;
    foreach ($containers as $container) {
        $container_normalizado = strtolower(remove_accents($container->post_title));
        
        if (strpos($container_normalizado, $distrito_normalizado) !== false) {
            $container_encontrado = $container;
            break;
        }
    }

    // Si no hubo coincidencia exacta, intentar coincidencia parcial por palabras
    if (!$container_encontrado) {
        $palabras = preg_split('/\s+/', $distrito_normalizado);
        $palabras = array_filter($palabras, function($w) { return strlen($w) > 2; });

        if (!empty($palabras)) {
            foreach ($containers as $container) {
                $container_normalizado = strtolower(remove_accents($container->post_title));
                $coinc = 0;
                foreach ($palabras as $pal) {
                    if (strpos($container_normalizado, $pal) !== false) {
                        $coinc++;
                    }
                }
                if ($coinc > 0 && $coinc >= (count($palabras) / 2)) {
                    $container_encontrado = $container;
                    break;
                }
            }
        }
    }
    
    if ($container_encontrado) {
        $conductor_id = get_post_meta($container_encontrado->ID, 'wpcargo_driver', true);
        
        wp_send_json_success([
            'container_id' => $container_encontrado->ID,
            'container_name' => $container_encontrado->post_title,
            'driver_id' => !empty($conductor_id) ? $conductor_id : null,
            'tipo_envio' => $tipo_envio
        ]);
    } else {
        wp_send_json_error(['message' => 'No se encontró contenedor']);
    }
    
    wp_die();
}

// Planificador de recojos con filtro de fecha
add_action('wp_ajax_wpcpod_generate_pickup_route_address', 'merc_planificador_pickup_con_filtro', 1);
function merc_planificador_pickup_con_filtro() {
    $filter_date = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : date('d/m/Y');
    
    error_log("📅 Planificador de recojos - Filtrando por fecha: {$filter_date}");
    
    global $wpdb;
    
    $current_user = wp_get_current_user();
    $driver_id = $current_user->ID;
    
    error_log("👤 Motorizado actual: ID {$driver_id}");
    
    // Buscar shipments asignados al motorizado: admitir varias claves de driver y pickup, y formatos de fecha
    // Usar sólo 'wpcargo_driver' como clave oficial del conductor asignado
    $meta_pickup_keys = "'wpcargo_pickup_date_picker','wpcargo_pickup_date','calendarenvio','wpcargo_fecha_envio'";

    // Normalizar fecha: recibir d/m/Y desde cliente pero comparar con STR_TO_DATE
    $fecha_ymd = date('Y-m-d', strtotime(str_replace('/', '-', $filter_date)));
    $like_dmy = $wpdb->esc_like($filter_date) . '%';
    $like_ymd = $wpdb->esc_like($fecha_ymd) . '%';

    // Añadir join para tipo_envio y forzar que sea 'normal'
    $sql = "SELECT DISTINCT p.ID, p.post_title
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_driver ON p.ID = pm_driver.post_id AND pm_driver.meta_key = 'wpcargo_driver'
        LEFT JOIN {$wpdb->postmeta} pm_pickup ON p.ID = pm_pickup.post_id AND pm_pickup.meta_key IN ({$meta_pickup_keys})
        LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
        LEFT JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = 'tipo_envio'
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_driver.meta_value = %d
        AND (
            (STR_TO_DATE(pm_pickup.meta_value, '%%d/%%m/%%Y') = STR_TO_DATE(%s, '%%Y-%%m-%%d'))
            OR (STR_TO_DATE(pm_pickup.meta_value, '%%Y-%%m-%%d') = STR_TO_DATE(%s, '%%Y-%%m-%%d'))
            OR pm_pickup.meta_value LIKE %s
        )
        AND LOWER(IFNULL(pm_tipo.meta_value, '')) = 'normal'
        AND UPPER(IFNULL(pm_status.meta_value, '')) = 'PENDIENTE'
        ORDER BY p.ID ASC";

    $query = $wpdb->prepare($sql, $driver_id, $fecha_ymd, $fecha_ymd, $like_dmy);
    error_log('MERC_PLANIFICADOR_SQL - ' . $query);
    $results = $wpdb->get_results($query);
    
    error_log("✅ Recojos asignados al motorizado {$driver_id}: " . count($results));
    
    if (empty($results)) {
        // Diagnóstico adicional: listar envíos cuya meta pickup parece ser hoy
        $meta_keys = array('wpcargo_pickup_date_picker','wpcargo_pickup_date','calendarenvio','wpcargo_fecha_envio');
        $meta_keys_list = "'" . implode("','", $meta_keys) . "'";
        $fecha_ymd = date('Y-m-d', strtotime(str_replace('/', '-', $filter_date)));

        $diag_sql = "SELECT DISTINCT p.ID, p.post_date FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ({$meta_keys_list})
            WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish'
            AND (pm.meta_value = %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)
            ORDER BY p.ID DESC
            LIMIT 200";

        $diag_query = $wpdb->prepare($diag_sql, $filter_date, $filter_date . '%', $fecha_ymd . '%');
        $diag_rows = $wpdb->get_results($diag_query);
        error_log('MERC_DIAG - Found candidate shipments by pickup meta: ' . count($diag_rows));

        foreach ($diag_rows as $row) {
            $sid = $row->ID;
            $pickup1 = get_post_meta($sid, 'wpcargo_pickup_date_picker', true);
            $pickup2 = get_post_meta($sid, 'wpcargo_pickup_date', true);
            $pickup3 = get_post_meta($sid, 'calendarenvio', true);
            $pickup4 = get_post_meta($sid, 'wpcargo_fecha_envio', true);
            $driver_main = get_post_meta($sid, 'wpcargo_driver', true);
            $driver_alt = get_post_meta($sid, 'wpcargo_motorizo_recojo', true);
            $tipo_envio = get_post_meta($sid, 'tipo_envio', true);
            $status_meta = get_post_meta($sid, 'wpcargo_status', true);
            error_log(sprintf("MERC_DIAG_ROW - ID=%d | post_date=%s | tipo_envio='%s' | status='%s' | wpcargo_driver='%s' | wpcargo_motorizo_recojo='%s' | pickups=['%s','%s','%s','%s']",
                $sid,
                $row->post_date,
                $tipo_envio,
                $status_meta,
                $driver_main,
                $driver_alt,
                $pickup1,
                $pickup2,
                $pickup3,
                $pickup4
            ));
        }

        wp_send_json([
            'status' => 'error',
            'message' => sprintf(__('No tienes recojos PENDIENTES asignados para el %s', 'wpcargo-pod'), $filter_date)
        ]);
        wp_die();
    }
    
    $shipments_data = [];
    
    foreach ($results as $shipment) {
        $shipment_id = $shipment->ID;
        
        // Obtener datos del remitente
        $shipper_address = get_post_meta($shipment_id, 'wpcargo_shipper_address', true);
        $link_maps_remitente = get_post_meta($shipment_id, 'link_maps_remitente', true);
        $status = get_post_meta($shipment_id, 'wpcargo_status', true);
        $assigned_driver = get_post_meta($shipment_id, 'wpcargo_driver', true);
        
        // 🔥 NUEVO: Obtener registered_shipper y shipper_name
        $registered_shipper = get_post_meta($shipment_id, 'registered_shipper', true);
        if (!$registered_shipper) {
            $other_post_id = get_post_meta($shipment_id, 'other_posts_id', true);
            $registered_shipper = $other_post_id;
        }
        if (!$registered_shipper) {
            $shipment_post = get_post($shipment_id);
            $registered_shipper = $shipment_post->post_author;
        }
        
        $shipper_name = 'Cliente desconocido';
        if ($registered_shipper) {
            $user = get_user_by('ID', $registered_shipper);
            if ($user) {
                $shipper_name = $user->display_name;
            } else {
                $shipper_name = get_the_title($registered_shipper) ?: "Cliente #{$registered_shipper}";
            }
        }
        
        error_log("🏪 Shipment #{$shipment_id} ({$status}) - Driver asignado: {$assigned_driver} - Dirección: '{$shipper_address}' - Link: '{$link_maps_remitente}' - Shipper: {$shipper_name} (ID: {$registered_shipper})");
        
        $shipments_data[] = [
            'id' => $shipment_id,
            'number' => $shipment->post_title,
            'address' => $shipper_address,
            'link_maps_remitente' => $link_maps_remitente,
            'pickup_date' => $filter_date,
            'status' => $status,
            'registered_shipper' => $registered_shipper,
            'shipper_name' => $shipper_name,
            'info' => []
        ];
    }
    
    error_log("✅ Recojos procesados: " . count($shipments_data) . " PENDIENTES asignados al motorizado {$driver_id} para {$filter_date}");
    
    // 🔥 NUEVO: Obtener estados disponibles del plugin (TODOS los estados)
    $available_statuses = function_exists('wpcpod_get_all_possible_statuses') ? wpcpod_get_all_possible_statuses() : [];
    error_log("🟢 Estados disponibles: " . json_encode($available_statuses));
    
    wp_send_json([
        'status' => 'success',
        'shipments' => $shipments_data,
        'origin' => ['lat' => null, 'lng' => null],
        'waypoints' => [],
        'poo' => false,
        'available_statuses' => $available_statuses
    ]);
    
    wp_die();
}
// ---------------------------------------------------------------------------
// BADGES EN SIDEBAR
// ---------------------------------------------------------------------------

add_action( 'wp_footer', 'merc_sidebar_badges' );
function merc_sidebar_badges() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $current_user = wp_get_current_user();
    $badge_html   = '';

    // Badge para motorizado
    if ( in_array( 'wpcargo_driver', $current_user->roles, true ) ) {
        global $wpdb;
        $pending_shipments = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_driver 
                ON p.ID = pm_driver.post_id 
                AND pm_driver.meta_key = 'wpcargo_driver'
            LEFT JOIN {$wpdb->postmeta} pm_estado 
                ON p.ID = pm_estado.post_id 
                AND pm_estado.meta_key = 'wpcargo_estado_pago_motorizado'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status = 'publish'
            AND pm_driver.meta_value = %s
            AND (pm_estado.meta_value = 'pendiente' OR pm_estado.meta_value IS NULL)
        ", $current_user->ID ) );

        $total_pendiente = 0.0;
        foreach ( $pending_shipments as $row ) {
            $totales         = get_payment_totals_by_method( $row->ID );
            $total_pendiente += $totales['total'];
        }

        if ( $total_pendiente > 0 ) {
            $badge_html = '<div class="merc-sidebar-badge merc-badge-driver"><strong>💰 Debes entregar:</strong><br><span class="merc-badge-amount">S/. ' . number_format( $total_pendiente, 2 ) . '</span></div>';
        }
    }

    // Badge para cliente
    if ( in_array( 'wpcargo_client', $current_user->roles, true ) ) {
        global $wpdb;
        $shipments = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID,
                   pm_producto.meta_value         AS costo_producto,
                   pm_envio.meta_value            AS costo_envio,
                   pm_quien_paga.meta_value       AS quien_paga,
                   pm_cliente_pago_a.meta_value   AS cliente_pago_a
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_sender 
                ON p.ID = pm_sender.post_id 
                AND pm_sender.meta_key = 'wpcargo_sender_id'
            LEFT JOIN {$wpdb->postmeta} pm_producto 
                ON p.ID = pm_producto.post_id 
                AND pm_producto.meta_key = 'wpcargo_costo_producto'
            LEFT JOIN {$wpdb->postmeta} pm_envio 
                ON p.ID = pm_envio.post_id 
                AND pm_envio.meta_key = 'wpcargo_costo_envio'
            LEFT JOIN {$wpdb->postmeta} pm_quien_paga 
                ON p.ID = pm_quien_paga.post_id 
                AND pm_quien_paga.meta_key = 'wpcargo_quien_paga'
            LEFT JOIN {$wpdb->postmeta} pm_cliente_pago_a 
                ON p.ID = pm_cliente_pago_a.post_id 
                AND pm_cliente_pago_a.meta_key = 'wpcargo_cliente_pago_a'
            LEFT JOIN {$wpdb->postmeta} pm_included 
                ON p.ID = pm_included.post_id 
                AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status = 'publish'
            AND pm_sender.meta_value = %s
            AND (pm_included.meta_value IS NULL)
        ", $current_user->ID ) );

        $por_cobrar = 0.0;
        $por_pagar  = 0.0;

        foreach ( $shipments as $shipment ) {
            $producto   = floatval( $shipment->costo_producto );
            $envio      = floatval( $shipment->costo_envio );
            $quien_paga = $shipment->quien_paga;

            if ( $quien_paga === 'cliente_final' ) {
                $por_cobrar += $producto;
            } elseif ( $quien_paga === 'remitente' ) {
                $por_pagar += $envio;
            }
        }

        $balance_neto = $por_cobrar - $por_pagar;

        if ( abs( $balance_neto ) > 0.01 ) {
            $badge_class = $balance_neto > 0 ? 'merc-badge-client-positive' : 'merc-badge-client-negative';
            $badge_text  = $balance_neto > 0 ? 'Por cobrar' : 'Debes';
            $badge_html  = '<div class="merc-sidebar-badge ' . $badge_class . '"><strong>' . $badge_text . ':</strong><br><span class="merc-badge-amount">S/. ' . number_format( abs( $balance_neto ), 2 ) . '</span></div>';
        }
    }

    if ( ! empty( $badge_html ) ) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var sidebar = $('.sidebar-fixed');
            if (sidebar.length) {
                var badge = $('<div class="merc-sidebar-badge-wrapper"></div>').html('<?php echo addslashes( $badge_html ); ?>');
                sidebar.prepend(badge);
            }
        });
        </script>
        <?php
    }
}

// ---------------------------------------------------------------------------
// PANEL CLIENTE - MEJORADO
// ---------------------------------------------------------------------------

add_shortcode( 'merc_panel_cliente', 'merc_panel_cliente_shortcode' );
function merc_panel_cliente_shortcode() {
    $current_user = wp_get_current_user();
    if ( ! in_array( 'wpcargo_client', $current_user->roles, true ) && ! current_user_can( 'administrator' ) ) {
        return '<div class="alert alert-danger">⛔ Acceso denegado. Solo para clientes.</div>';
    }

    ob_start();
    
    // Obtener filtros de fecha
    $fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : '';
    
    // Verificar si hay envíos pendientes de hoy (bloqueo activo)
    $tiene_bloqueo = merc_cliente_tiene_envios_pendientes_hoy($current_user->ID);
    $mostrar_alerta_bloqueo = isset($_GET['blocked']) && $_GET['blocked'] === '1';
    ?>
    <div class="merc-panel-cliente">
        <?php if ($tiene_bloqueo): ?>
        <div class="alert alert-warning" style="margin-bottom: 20px; border-left: 4px solid #ff9800;">
            <h5 style="margin-top: 0; color: #f57c00;">⚠️ Cuenta con Restricción Temporal</h5>
            <p style="margin-bottom: 0;">
                <strong>Todos tus envíos de hoy ya fueron recogidos por el motorizado.</strong><br>
                No puedes crear nuevos envíos hasta que se completen las entregas.
                Una vez que todos los envíos estén "Entregados y pagados", "Reprogramados" o "Anulados",
                podrás crear nuevos envíos para el día siguiente.
                <br><small><em>Nota: Los envíos marcados como "No recogidos" también mantienen el bloqueo y generan una penalización.</em></small>
            </p>
        </div>
        <?php endif; ?>
        
        <?php if ($mostrar_alerta_bloqueo): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <h5 style="margin-top: 0;">🚫 Acceso Bloqueado</h5>
            <p style="margin-bottom: 0;">
                No puedes crear nuevos envíos porque todos tus envíos de hoy ya están en estado "Recogido".
                Debes esperar a que el motorizado los entregue y se liquiden los pagos para poder crear envíos del día siguiente.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header" style="background: #27ae60; color: white;">
                <h4 class="mb-0">🏢 Panel del Cliente: <?php echo esc_html( $current_user->display_name ); ?></h4>
            </div>
            <div class="card-body">
                <?php merc_cliente_balance( $current_user->ID ); ?>
            </div>
        </div>
        <!-- Historial de liquidaciones (cliente) -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">💳 Historial de liquidaciones</h5></div>
            <div class="card-body">
                <?php echo do_shortcode('[merc_finanzas_cliente fecha_inicio="' . esc_attr($fecha_inicio) . '" fecha_fin="' . esc_attr($fecha_fin) . '"]'); ?>
            </div>
        </div>
        <!-- Penalidades pendientes (cliente) -->
        <?php do_action('merc_cliente_dashboard_after_envios'); ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📊 Mis Envíos</h5>
            </div>
            <div class="card-body">
                <?php merc_cliente_envios( $current_user->ID, $fecha_inicio, $fecha_fin ); ?>
            </div>
        </div>
    </div>
    <style>
        .merc-panel-cliente .badge { font-size: 0.9em; padding: 0.5em 0.8em; }
        .merc-balance-positive { color: #27ae60; font-weight: bold; font-size: 1.2em; }
        .merc-balance-negative { color: #e74c3c; font-weight: bold; font-size: 1.2em; }
        .merc-summary-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .merc-summary-box h3 { color: #1976D2; margin-bottom: 15px; }
        .merc-summary-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .merc-summary-item:last-child { border-bottom: none; }
        .merc-summary-value { font-weight: bold; color: #2c3e50; }
        .merc-entregas-table { width: 100%; border-collapse: collapse; }
        .merc-entregas-table th { background: #e9ecef; padding: 12px; text-align: left; border: 1px solid #dee2e6; }
        .merc-entregas-table td { padding: 12px; border: 1px solid #dee2e6; }
        .merc-entregas-table tfoot td { background: #d4edda; font-weight: bold; }
    </style>
    <?php
    return ob_get_clean();
}

function merc_cliente_balance( $client_id ) {
    global $wpdb;

    // Obtener fechas del filtro GET
    $fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : '';
    
    // DEBUG: Log fechas recibidas
    error_log('=== MERC_CLIENTE_BALANCE DEBUG ===');
    error_log('Cliente ID: ' . $client_id);
    error_log('Fecha inicio (GET): ' . ($fecha_inicio ?: 'VACÍA'));
    error_log('Fecha fin (GET): ' . ($fecha_fin ?: 'VACÍA'));
    
    // Mostrar formulario de filtro
    ?>
    <div style="background:#f9f9f9;padding:15px;margin-bottom:20px;border-radius:6px;border:1px solid #ddd;">
        <h4 style="margin-top:0;margin-bottom:12px;">📅 Filtrar por fecha de envío</h4>
        <form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <div>
                <label for="fecha_inicio" style="display:block;margin-bottom:5px;font-weight:500;font-size:13px;">Fecha inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>" style="padding:8px;border:1px solid #ccc;border-radius:4px;">
            </div>
            <div>
                <label for="fecha_fin" style="display:block;margin-bottom:5px;font-weight:500;font-size:13px;">Fecha fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>" style="padding:8px;border:1px solid #ccc;border-radius:4px;">
            </div>
            <button type="submit" style="padding:8px 16px;background:#3498db;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:500;">
                🔍 Filtrar
            </button>
            <?php if ($fecha_inicio || $fecha_fin): ?>
                <a href="<?php echo remove_query_arg(array('fecha_inicio', 'fecha_fin')); ?>" style="padding:8px 16px;background:#bdc3c7;color:#fff;border:none;border-radius:4px;cursor:pointer;text-decoration:none;font-weight:500;display:inline-block;">
                    ✕ Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>
    <?php

    // Construir query base
    // NOTA: Las fechas en meta_value están en formato DD/MM/YYYY, necesitamos convertir para comparar
    $query = "
        SELECT p.ID,
               pm_producto.meta_value         AS costo_producto,
               pm_envio.meta_value            AS costo_envio,
               pm_quien_paga.meta_value       AS quien_paga,
               pm_included.meta_value         AS estado_pago_remitente,
               pm_cliente_pago_a.meta_value   AS cliente_pago_a,
               pm_pickup_date.meta_value      AS pickup_date
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_shipper 
            ON p.ID = pm_shipper.post_id 
            AND pm_shipper.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_producto 
            ON p.ID = pm_producto.post_id 
            AND pm_producto.meta_key = 'wpcargo_costo_producto'
        LEFT JOIN {$wpdb->postmeta} pm_envio 
            ON p.ID = pm_envio.post_id 
            AND pm_envio.meta_key = 'wpcargo_costo_envio'
        LEFT JOIN {$wpdb->postmeta} pm_quien_paga 
            ON p.ID = pm_quien_paga.post_id 
            AND pm_quien_paga.meta_key = 'wpcargo_quien_paga'
        LEFT JOIN {$wpdb->postmeta} pm_included 
            ON p.ID = pm_included.post_id 
            AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
        LEFT JOIN {$wpdb->postmeta} pm_cliente_pago_a 
            ON p.ID = pm_cliente_pago_a.post_id 
            AND pm_cliente_pago_a.meta_key = 'wpcargo_cliente_pago_a'
        LEFT JOIN {$wpdb->postmeta} pm_pickup_date
            ON p.ID = pm_pickup_date.post_id
            AND pm_pickup_date.meta_key IN ('wpcargo_pickup_date_picker', 'wpcargo_pickup_date', 'wpcargo_fecha_envio')
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_shipper.meta_value = %s
    ";
    
    // Agregar filtro de fecha INICIO (desde esa fecha hasta hoy)
    // Las fechas están en formato DD/MM/YYYY, así que convertimos con STR_TO_DATE
    // Las fechas ya están validadas con regex, así que las insertamos directamente
    // IMPORTANTE: Escapar % con %% para que prepare() no las interprete como placeholders
    if ($fecha_inicio && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
        $query .= " AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') >= STR_TO_DATE('" . sanitize_text_field($fecha_inicio) . "', '%%Y-%%m-%%d')";
        error_log('✓ Filtro INICIO aplicado: >= ' . $fecha_inicio . ' (convertido de DD/MM/YYYY)');
    }
    
    // Agregar filtro de fecha FIN (desde el inicio hasta esa fecha)
    if ($fecha_fin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
        $query .= " AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') <= STR_TO_DATE('" . sanitize_text_field($fecha_fin) . "', '%%Y-%%m-%%d')";
        error_log('✓ Filtro FIN aplicado: <= ' . $fecha_fin . ' (convertido de DD/MM/YYYY)');
    }
    
    // Ejecutar query con argumentos seguros
    $query = $wpdb->prepare($query, $client_id);
    
    // DEBUG: Log query completa
    error_log('📋 Query SQL generada:');
    error_log($query);
    
    $shipments = $wpdb->get_results($query);
    
    // DEBUG: Log resultados
    error_log('📦 Envíos encontrados: ' . count($shipments ?: array()));
    if (!empty($shipments)) {
        foreach ($shipments as $s) {
            error_log('   → ID: ' . $s->ID . ', Fecha meta: ' . ($s->pickup_date ?: 'N/A'));
        }
    } else {
        error_log('   ⚠️  SIN RESULTADOS - Verifica el formato de fechas en BD');
    }

    $por_cobrar       = 0.0;
    $por_pagar        = 0.0;
    $efectivo         = 0.0;
    $recaudado_merc   = 0.0;
    $recaudado_marca  = 0.0;

    error_log('--- Procesando envíos para cálculo de totales ---');
    
    if ( ! empty( $shipments ) && is_array( $shipments ) ) {
        foreach ( $shipments as $shipment ) {
            $producto   = floatval( $shipment->costo_producto );
            $envio      = floatval( $shipment->costo_envio );
            $quien_paga = $shipment->quien_paga;
            $estado     = $shipment->estado_pago_remitente ? $shipment->estado_pago_remitente : '';
            
            error_log('  Envío ID: ' . $shipment->ID . 
                      ' | Producto: ' . $producto . 
                      ' | Envío: ' . $envio . 
                      ' | Quién paga: ' . $quien_paga . 
                      ' | Estado: ' . $estado);

        // Si el envío está ya vinculado a una liquidación verificada, ignorarlo en cálculos (ya fue procesado)
        $is_verified = merc_is_shipment_liquidation_verified( $shipment->ID );
        if ( ! $is_verified ) {
            $is_pending = ( $estado === 'pendiente' || $estado === '' );
            if ( $is_pending ) {
                if ( $quien_paga === 'cliente_final' && $producto > 0 ) {
                    $por_cobrar += $producto;
                }
                if ( $quien_paga === 'remitente' && $envio > 0 ) {
                    $por_pagar += $envio;
                }
            }
        }

        // Si está verificado, no sumar sus montos al balance del cliente (ya fue liquidado)
        $totales = get_payment_totals_by_method( $shipment->ID );
        if ( ! $is_verified ) {
            $efectivo       += $totales['efectivo'];
            $recaudado_merc += get_recaudado_merc( $shipment->ID );
            $recaudado_marca+= $totales['pago_marca'];
        }
        }
    }
    
    error_log('--- Totales calculados ---');
    error_log('Por cobrar: ' . $por_cobrar);
    error_log('Por pagar: ' . $por_pagar);
    error_log('Efectivo: ' . $efectivo);
    error_log('Recaudado MERC: ' . $recaudado_merc);
    error_log('Recaudado MARCA: ' . $recaudado_marca);
    error_log('=== FIN DEBUG ===');

    $balance_neto = $recaudado_merc + $efectivo - $por_pagar;

    // Si todos los envíos del cliente están liquidados/verificados, forzar balance 0
    $all_verified = true;
    if ( ! empty( $shipments ) && is_array( $shipments ) ) {
        foreach ( $shipments as $s ) {
            if ( ! merc_is_shipment_liquidation_verified( $s->ID ) ) { $all_verified = false; break; }
        }
    }
    if ( $all_verified ) $balance_neto = 0.0;
    ?>
    <div class="merc-summary-box">
		<h3>💵 Balance Financiero</h3>

		<!-- TOTAL RECAUDADO -->
		<div class="merc-summary-item">
			<span>Total recaudado:</span>
			<span class="merc-summary-value">
				S/. <?php echo number_format( $recaudado_merc + $efectivo + $recaudado_marca + $pos_total, 2 ); ?>
			</span>
		</div>

		<!-- PAGO A MARCA -->
		<div class="merc-summary-item">
			<span>Pago a MARCA:</span>
			<span class="merc-summary-value" style="color:#8e44ad;">
				S/. <?php echo number_format( $recaudado_marca, 2 ); ?>
			</span>
		</div>

		<!-- EFECTIVO RECAUDADO -->
		<div class="merc-summary-item">
			<span>Efectivo recaudado:</span>
			<span class="merc-summary-value" style="color:#3498db;">
				S/. <?php echo number_format( $efectivo + $recaudado_merc, 2 ); ?>
			</span>
		</div>

		<!-- POS -->
		<div class="merc-summary-item">
			<span>POS:</span>
			<span class="merc-summary-value" style="color:#16a085;">
				S/. <?php
					$pos_total = 0;
					foreach ( $shipments as $s ) {
						$tot = get_payment_totals_by_method( $s->ID );
						$pos_total += isset($tot['pos']) ? $tot['pos'] : 0;
					}
					echo number_format( $pos_total, 2 );
				?>
			</span>
		</div>

		<!-- TOTAL SERVICIOS (incluyendo cargos NO RECIBIDO) -->
		<?php 
			// Obtener cargos NO RECIBIDO pendientes del cliente
			$historia = get_user_meta( $client_id, 'merc_liquidations', true );
			$cargos_no_recibido = array();
			$total_no_recibido = 0.0;
			
			if ( is_array($historia) ) {
				foreach ( $historia as $entry ) {
					if ( isset($entry['tipo_liquidacion']) && $entry['tipo_liquidacion'] === 'no_recibido_charge' && 
						 isset($entry['status']) && $entry['status'] === 'unpaid' ) {
						$cargos_no_recibido[] = $entry;
						$total_no_recibido += floatval($entry['amount']);
					}
				}
			}
			
			$total_servicios_completo = $por_pagar + $total_no_recibido;
		?>
		<div class="merc-summary-item">
			<span>Total de servicios:</span>
			<span class="merc-summary-value" style="color:#e74c3c;">
				S/. <?php echo number_format( $total_servicios_completo, 2 ); ?>
			</span>
		</div>
		
		<?php if ( ! empty($cargos_no_recibido) ) : ?>
		<!-- Desglose: Cargos por NO RECIBIDO -->
		<div class="merc-summary-item" style="background:#fff3cd;padding:12px;margin-top:8px;border-radius:6px;border-left:4px solid #ff9800;">
			<div style="width:100%;">
				<strong style="color:#ff6b00;">📦 Envíos No Recibidos (<?php echo count($cargos_no_recibido); ?>)</strong>
				<div style="margin-top:8px;font-size:13px;">
					<?php foreach ( $cargos_no_recibido as $cargo ) : 
						$shipment_id = isset($cargo['shipment_id']) ? $cargo['shipment_id'] : 'N/A';
						$monto = number_format(floatval($cargo['amount']), 2);
					?>
					<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid rgba(255,107,0,0.2);">
						<span>Envío #<?php echo $shipment_id; ?></span>
						<span style="font-weight:bold;color:#ff6b00;">S/. <?php echo $monto; ?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<div style="margin-top:10px;padding-top:8px;border-top:2px solid #ff9800;display:flex;justify-content:space-between;font-weight:bold;color:#ff6b00;">
					<span>Subtotal NO RECIBIDO:</span>
					<span>S/. <?php echo number_format($total_no_recibido, 2); ?></span>
				</div>
				<?php if ( ! empty($por_pagar) ) : ?>
				<div style="margin-top:4px;padding:4px 0;display:flex;justify-content:space-between;color:#666;">
					<span>+ Otros servicios:</span>
					<span>S/. <?php echo number_format($por_pagar, 2); ?></span>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- BALANCE NETO -->
		<?php 
			// Recalcular balance neto incluyendo cargos NO RECIBIDO
			$balance_neto = $recaudado_merc + $efectivo - $total_servicios_completo;
		?>
		<div class="merc-summary-item" style="background:#f0f0f0;padding:15px;margin-top:10px;border-radius:6px;">
			<span><strong>Balance Neto:</strong></span>
			<span class="<?php echo $balance_neto >= 0 ? 'merc-balance-positive' : 'merc-balance-negative'; ?>">
				S/. <?php echo number_format( $balance_neto, 2 ); ?>
			</span>

			<?php if ( $balance_neto < 0 ) : ?>
				<?php $nonce_pagar = wp_create_nonce('merc_cliente_pagar'); ?>
				<div style="margin-top:8px;">
					<?php if ( ! empty($cargos_no_recibido) ) : ?>
					<!-- Botón con desglose de NO RECIBIDO -->
					<button class="merc-btn-pagar-merc-detalles"
							data-user-id="<?php echo esc_attr( get_current_user_id() ); ?>"
							data-amount="<?php echo esc_attr( number_format( abs( $balance_neto ), 2 ) ); ?>"
							data-nonce="<?php echo esc_attr( $nonce_pagar ); ?>"
							data-cargos-no-recibido='<?php echo esc_attr(json_encode($cargos_no_recibido)); ?>'
							style="background:#ff6b00;color:#fff;border:none;padding:10px 16px;border-radius:4px;font-weight:600;cursor:pointer;transition:all 0.3s ease;">
						🔍 Ver detalle y pagar S/. <?php echo number_format( abs( $balance_neto ), 2 ); ?>
					</button>
					<?php else : ?>
					<!-- Botón simple si no hay NO RECIBIDO -->
					<button class="merc-btn-pagar-merc"
							data-user-id="<?php echo esc_attr( get_current_user_id() ); ?>"
							data-amount="<?php echo esc_attr( number_format( abs( $balance_neto ), 2 ) ); ?>"
							data-nonce="<?php echo esc_attr( $nonce_pagar ); ?>"
							style="background:#e74c3c;color:#fff;border:none;padding:10px 16px;border-radius:4px;font-weight:600;cursor:pointer;transition:all 0.3s ease;">
						Pagar S/. <?php echo number_format( abs( $balance_neto ), 2 ); ?>
					</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="alert alert-info mt-3 mb-0">
			<strong>ℹ️ Nota:</strong>
			Un balance positivo indica que MERC debe depositarte.
			Un balance negativo indica que debes cubrir servicios pendientes.
		</div>
	</div>
	
	<!-- Modal: Detalles de Pago con desglose NO RECIBIDO -->
	<script>
	jQuery(function($){
		var ajaxurl = '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
		
		// Crear modal reutilizable
		if ($('#merc-detalles-pago-modal').length === 0) {
			var modalHTML = `
				<div id="merc-detalles-pago-modal" style="display:none;position:fixed;z-index:100001;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.7);overflow-y:auto;">
					<div style="background:#fff;margin:40px auto;max-width:600px;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);padding:0;width:90%;">
						<!-- Header -->
						<div style="background:linear-gradient(135deg,#ff6b00,#ff8533);color:#fff;padding:24px;border-radius:12px 12px 0 0;display:flex;justify-content:space-between;align-items:center;">
							<h2 style="margin:0;font-size:20px;">📦 Detalle de Pago</h2>
							<button type="button" id="merc-modal-close" style="border:none;background:transparent;color:#fff;font-size:24px;cursor:pointer;">&times;</button>
						</div>
						
						<!-- Body -->
						<div style="padding:24px;">
							<!-- Desglose -->
							<div id="merc-desglose-container" style="background:#f9f9f9;padding:16px;border-radius:8px;margin-bottom:20px;border-left:4px solid #ff9800;">
								<!-- Se llena dinámicamente -->
							</div>
							
							<!-- Total -->
							<div style="background:#e8f5e9;padding:16px;border-radius:8px;margin-bottom:20px;border-left:4px solid #4caf50;">
								<div style="display:flex;justify-content:space-between;align-items:center;">
									<span style="font-size:16px;font-weight:600;color:#2e7d32;">Total a pagar:</span>
									<span style="font-size:28px;font-weight:700;color:#2e7d32;" id="merc-modal-total">S/. 0.00</span>
								</div>
							</div>
							
							<!-- Nota -->
							<div style="background:#fff3e0;padding:12px;border-radius:6px;border-left:3px solid #ff9800;margin-bottom:20px;font-size:13px;color:#e65100;line-height:1.6;">
								<strong>⚠️ Importante:</strong> Este pago cubrirá todos los servicios pendientes que se muestran arriba.
							</div>
						</div>
						
						<!-- Footer -->
						<div style="padding:16px 24px;background:#f5f5f5;border-radius:0 0 12px 12px;display:flex;gap:12px;justify-content:flex-end;border-top:1px solid #e0e0e0;">
							<button id="merc-modal-cancel" type="button" class="btn btn-secondary" style="padding:10px 20px;border-radius:6px;border:1px solid #ccc;background:#fff;color:#333;cursor:pointer;font-weight:500;transition:all 0.3s ease;">
								Cancelar
							</button>
							<button id="merc-modal-confirm" type="button" class="btn btn-primary" style="padding:10px 20px;border-radius:6px;border:none;background:#ff6b00;color:#fff;cursor:pointer;font-weight:600;transition:all 0.3s ease;">
								Continuar con el pago
							</button>
						</div>
					</div>
				</div>
			`;
			$('body').append(modalHTML);
		}
		
		// Manejo del botón con detalles
		$(document).on('click', '.merc-btn-pagar-merc-detalles', function(e){
			e.preventDefault();
			var $btn = $(this);
			var amount = $btn.data('amount');
			var cargosStr = $btn.attr('data-cargos-no-recibido');
			var cargos = [];
			
			try {
				cargos = JSON.parse(cargosStr);
			} catch(err) {
				console.error('Error parsing cargos:', err);
				cargos = [];
			}
			
			// Construir desglose
			var desglose = '<div style="font-weight:600;color:#ff6b00;margin-bottom:12px;font-size:14px;">Cargos por entregas NO RECIBIDAS:</div>';
			var totalRec = 0;
			
			if (Array.isArray(cargos) && cargos.length > 0) {
				cargos.forEach(function(cargo, idx){
					var shipId = cargo.shipment_id || 'N/A';
					var montos = parseFloat(cargo.amount) || 0;
					totalRec += montos;
					desglose += `
						<div style="display:flex;justify-content:space-between;padding:8px;border-bottom:1px solid rgba(255,107,0,0.15);">
							<span style="color:#333;">Envío #${shipId}</span>
							<span style="font-weight:600;color:#ff6b00;">S/. ${montos.toFixed(2)}</span>
						</div>
					`;
				});
			} else {
				desglose += '<div style="color:#999;font-size:13px;">No hay detalles disponibles</div>';
			}
			
			if (amount && amount > totalRec) {
				var otros = (parseFloat(amount) - totalRec).toFixed(2);
				if (otros > 0) {
					desglose += `
						<div style="display:flex;justify-content:space-between;padding:8px;margin-top:4px;font-weight:600;border-top:2px solid #ff9800;padding-top:12px;color:#666;">
							<span>+ Otros servicios:</span>
							<span>S/. ${otros}</span>
						</div>
					`;
				}
			}
			
			$('#merc-desglose-container').html(desglose);
			$('#merc-modal-total').text('S/. ' + parseFloat(amount).toFixed(2));
			
			// Guardar datos para confirmación
			$('#merc-modal-confirm').data({
				'user-id': $btn.data('user-id'),
				'amount': amount,
				'nonce': $btn.data('nonce')
			});
			
			$('#merc-detalles-pago-modal').fadeIn(200);
		});
		
		// Cerrar modal
		$(document).on('click', '#merc-modal-close, #merc-modal-cancel', function(){
			$('#merc-detalles-pago-modal').fadeOut(150);
		});
		
		// Confirmar pago
		$(document).on('click', '#merc-modal-confirm', function(){
			var data = $(this).data();
			var $btn = $(this);
			
			if (!data['user-id'] || !data.amount || !data.nonce) {
				alert('Datos incompletos. Por favor recarga la página.');
				return;
			}
			
			// Mostrar más opciones de pago (similar al flujo existente)
			// Esto dispara el mismo flujo del botón merc-btn-pagar-merc
			var $triggerBtn = $('<button class="merc-btn-pagar-merc" />').data({
				'user-id': data['user-id'],
				'amount': data.amount,
				'nonce': data.nonce
			});
			
			// Simular click en el botón normal
			$triggerBtn.trigger('click');
			
			// Cerrar modal
			$('#merc-detalles-pago-modal').fadeOut(150);
		});
	});
	</script>
    <?php
}

function merc_cliente_envios( $client_id, $fecha_inicio = '', $fecha_fin = '' ) {
    global $wpdb;

    $shipments_query = "
        SELECT p.ID, p.post_title,
               pm_destino.meta_value         AS destino,
               pm_included.meta_value        AS estado_pago_remitente,
               pm_pickup_date.meta_value     AS pickup_date
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_shipper 
            ON p.ID = pm_shipper.post_id 
            AND pm_shipper.meta_key = 'registered_shipper'
        LEFT JOIN {$wpdb->postmeta} pm_destino 
            ON p.ID = pm_destino.post_id 
            AND pm_destino.meta_key = 'wpcargo_distrito_destino'
        LEFT JOIN {$wpdb->postmeta} pm_included 
            ON p.ID = pm_included.post_id 
            AND pm_included.meta_key = 'wpcargo_included_in_liquidation'
        LEFT JOIN {$wpdb->postmeta} pm_pickup_date
            ON p.ID = pm_pickup_date.post_id
            AND pm_pickup_date.meta_key IN ('wpcargo_pickup_date_picker', 'wpcargo_pickup_date', 'wpcargo_fecha_envio')
        WHERE p.post_type = 'wpcargo_shipment'
        AND p.post_status = 'publish'
        AND pm_shipper.meta_value = %s
    ";
    
    // Aplicar filtro de fecha INICIO
    if ( $fecha_inicio && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) ) {
        $shipments_query .= " AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') >= STR_TO_DATE('" . sanitize_text_field($fecha_inicio) . "', '%%Y-%%m-%%d')";
    }
    
    // Aplicar filtro de fecha FIN
    if ( $fecha_fin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin) ) {
        $shipments_query .= " AND STR_TO_DATE(pm_pickup_date.meta_value, '%%d/%%m/%%Y') <= STR_TO_DATE('" . sanitize_text_field($fecha_fin) . "', '%%Y-%%m-%%d')";
    }
    
    $shipments_query .= " ORDER BY p.post_date DESC LIMIT 50";
    
    $shipments = $wpdb->get_results( $wpdb->prepare( $shipments_query, $client_id ) );

    if ( empty( $shipments ) ) {
        echo '<div class="alert alert-warning">No tienes envíos registrados.</div>';
        return;
    }

    $total_efectivo_sum   = 0.0;
    $total_pago_merc_sum  = 0.0;
    $total_pago_marca_sum = 0.0;
    $total_pos_sum        = 0.0;
    $total_general        = 0.0;
    ?>
    <table class="merc-entregas-table">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Destino</th>
                <th>Efectivo</th>
                <th>Pago a MERC</th>
                <th>Pago a MARCA</th>
                <th>POS</th>
                <th>Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $shipments as $shipment ) :
            $estado_remitente = $shipment->estado_pago_remitente ? $shipment->estado_pago_remitente : '';
            $is_verified = merc_is_shipment_liquidation_verified( $shipment->ID );
            $totales          = get_payment_totals_by_method( $shipment->ID );
            // Mostrar POS neto guardado
            $pos_display      = get_pos_net_for_shipment( $shipment->ID, $totales );

            $total_efectivo_sum   += $totales['efectivo'];
            $total_pago_merc_sum  += $totales['pago_merc'];
            $total_pago_marca_sum += $totales['pago_marca'];
            $total_pos_sum        += $pos_display;
            $total_general        += $totales['total'];
            ?>
            <tr>
                <td><strong>#<?php echo esc_html( $shipment->post_title ); ?></strong></td>
                <td><?php echo esc_html( $shipment->destino ); ?></td>
                <td>S/. <?php echo number_format( $totales['efectivo'], 2 ); ?></td>
                <td>S/. <?php echo number_format( $totales['pago_merc'], 2 ); ?></td>
                <td>S/. <?php echo number_format( $totales['pago_marca'], 2 ); ?><?php echo merc_get_pago_marca_voucher_thumb_html( $shipment->ID, 20 ); ?></td>
                <td>S/. <?php echo number_format( $pos_display, 2 ); ?></td>
                <td><strong>S/. <?php echo number_format( $totales['total'], 2 ); ?></strong></td>
                <td>
                    <?php if ( $is_verified || $estado_remitente === 'liquidado' ) : ?>
                        <span class="badge badge-success">✅ Liquidado</span>
                    <?php else : ?>
                        <span class="badge badge-warning">⏳ Pendiente</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;"><strong>TOTAL:</strong></td>
                <td><strong>S/. <?php echo number_format( $total_efectivo_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_pago_merc_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_pago_marca_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_pos_sum, 2 ); ?></strong></td>
                <td><strong>S/. <?php echo number_format( $total_general, 2 ); ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php
}

// ========== ASIGNACIÓN AUTOMÁTICA DE ENVÍOS A CONTENEDORES ==========
/**
 * Función auxiliar para normalizar texto (eliminar tildes y caracteres especiales)
 */
function merc_normalizar_texto($texto) {
    $texto = trim($texto);
    $texto = strtoupper($texto);
    
    // Reemplazar caracteres con tildes
    $tildes = array(
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
        'Ñ' => 'N', 'Ñ' => 'N',
        'Ü' => 'U', 'Ü' => 'U'
    );
    
    return strtr($texto, $tildes);
}

/**
 * Obtener el motorizado "activo" basado en el estado del envío
 * 
 * LÓGICA MERC EMPRENDEDOR:
 * - Antes de EN BASE MERCOURIER: usa motorizado_recojo
 * - En EN BASE MERCOURIER o después: usa motorizado_entrega (si existe), sino motorizado_recojo
 * 
 * @param int $shipment_id ID del envío
 * @return int|false ID del motorizado activo o false si no hay
 */
function merc_get_motorizado_activo($shipment_id) {
    // Estados que indican que estamos en la fase de entrega
    $estados_entrega = ['EN BASE MERCOURIER', 'RECEPCIONADO', 'LISTO PARA SALIR', 'EN RUTA', 'ENTREGADO', 'NO RECIBIDO', 'REPROGRAMADO'];
    
    // Obtener estado actual del envío
    $estado_actual = get_post_meta($shipment_id, 'wpcargo_status', true);
    
    // Verificar si estamos en fase de entrega
    $es_fase_entrega = false;
    if (!empty($estado_actual)) {
        foreach ($estados_entrega as $estado) {
            if (stripos($estado_actual, $estado) !== false) {
                $es_fase_entrega = true;
                break;
            }
        }
    }
    
    // Si estamos en fase de entrega, intentar obtener motorizado_entrega
    if ($es_fase_entrega) {
        $motorizado_entrega = get_post_meta($shipment_id, 'wpcargo_motorizo_entrega', true);
        if (!empty($motorizado_entrega)) {
            return intval($motorizado_entrega);
        }
        // Si no hay motorizado de entrega, retornar el de recojo como fallback
        $motorizado_recojo = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
        if (!empty($motorizado_recojo)) {
            return intval($motorizado_recojo);
        }
    } else {
        // Si estamos en fase de recojo, usar motorizado_recojo
        $motorizado_recojo = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
        if (!empty($motorizado_recojo)) {
            return intval($motorizado_recojo);
        }
    }
    
    return false;
}

/**
 * Asignar solo los pedidos seleccionados al motorizado
 */
 
add_action('admin_post_wpcsc_assign_partial_shipments', 'assign_partial_shipments_to_driver');
add_action('admin_post_nopriv_wpcsc_assign_partial_shipments', 'assign_partial_shipments_to_driver');

function assign_partial_shipments_to_driver() {

    if (
        !isset($_POST['wpcsc_nonce_field_value']) ||
        !wp_verify_nonce($_POST['wpcsc_nonce_field_value'], 'wpcsc_form_action')
    ) {
        wp_die('Nonce inválido');
    }

    if (
        empty($_POST['selected_shipments']) ||
        empty($_POST['assigned_driver'])
    ) {
        wp_redirect(wp_get_referer());
        exit;
    }

    $driver_id = intval($_POST['assigned_driver']);
    $shipments = array_map('intval', $_POST['selected_shipments']);

    foreach ($shipments as $shipment_id) {
        update_post_meta($shipment_id, 'wpcargo_driver', $driver_id);

        // Opcional: estado del pedido
        update_post_meta($shipment_id, 'wpcargo_status', 'assigned_to_driver');
    }

    wp_redirect(wp_get_referer());
    exit;
}

/**
 * Asigna automáticamente un envío al contenedor correspondiente
 * basándose en el distrito de destino
 */
add_action('wpcargo_after_save_shipment', 'merc_auto_assign_shipment_to_container', 100, 1);
add_action('save_post_wpcargo_shipment', 'merc_auto_assign_shipment_to_container', 100, 1);
// Hook adicional con máxima prioridad para asegurar guardado del motorizado
//add_action('wpcargo_after_save_shipment', 'merc_forzar_asignacion_motorizado', 999, 1);
//add_action('save_post_wpcargo_shipment', 'merc_forzar_asignacion_motorizado', 999, 1);

function merc_auto_assign_shipment_to_container($post_id) {
    error_log("🔍 AUTO-ASIGNACIÓN INICIADA - Envío #{$post_id}");
    
    // Verificar que sea un envío
    if (get_post_type($post_id) !== 'wpcargo_shipment') {
        error_log("❌ AUTO-ASIGNACIÓN - No es un envío wpcargo_shipment");
        return;
    }
    
    // Verificar que no sea un autoguardado
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        error_log("❌ AUTO-ASIGNACIÓN - Es un autoguardado, omitiendo");
        return;
    }
    
    // Obtener tipo de envío
    $tipo_envio = get_post_meta($post_id, 'tipo_envio', true);
    if (empty($tipo_envio) && isset($_POST['tipo_envio'])) {
        $tipo_envio = sanitize_text_field($_POST['tipo_envio']);
    }
    
    error_log("📦 AUTO-ASIGNACIÓN - Tipo de envío: " . ($tipo_envio ?: 'NO DEFINIDO'));
    
    // Determinar qué tipo de contenedor y distrito usar según el tipo de envío
    $distrito = '';
    $distrito_destino = ''; // Para MERC EMPRENDEDOR
    $meta_key_contenedor = '';
    
    $tipo_lower = strtolower($tipo_envio);
    if ($tipo_lower === 'express' || $tipo_lower === 'full_fitment' || strpos($tipo_lower, 'full') !== false) {
        // MERC AGENCIA/FULL FITMENT: usar distrito de DESTINO → contenedor de ENTREGA
        $distrito = get_post_meta($post_id, 'wpcargo_distrito_destino', true);
        if (empty($distrito) && isset($_POST['wpcargo_distrito_destino'])) {
            $distrito = sanitize_text_field($_POST['wpcargo_distrito_destino']);
        }
        $meta_key_contenedor = 'shipment_container_entrega';
        error_log("🎯 AUTO-ASIGNACIÓN - AGENCIA/FULL detectado, distrito destino: " . ($distrito ?: 'NO DEFINIDO') . " → contenedor de ENTREGA");
    } elseif (strtolower($tipo_envio) === 'normal') {
        // MERC EMPRENDEDOR: usar distrito de RECOJO → contenedor de RECOJO
        $distrito = get_post_meta($post_id, 'wpcargo_distrito_recojo', true);
        if (empty($distrito) && isset($_POST['wpcargo_distrito_recojo'])) {
            $distrito = sanitize_text_field($_POST['wpcargo_distrito_recojo']);
        }
        $meta_key_contenedor = 'shipment_container_recojo';
        error_log("🎯 AUTO-ASIGNACIÓN - EMPRENDEDOR detectado, distrito recojo: " . ($distrito ?: 'NO DEFINIDO') . " → contenedor de RECOJO");
        
        // También necesitaremos el distrito de destino para asignar el contenedor de entrega
        $distrito_destino = get_post_meta($post_id, 'wpcargo_distrito_destino', true);
        if (empty($distrito_destino) && isset($_POST['wpcargo_distrito_destino'])) {
            $distrito_destino = sanitize_text_field($_POST['wpcargo_distrito_destino']);
        }
    }
    
    if (empty($distrito) || empty($meta_key_contenedor)) {
        error_log("❌ AUTO-ASIGNACIÓN - No se pudo determinar el distrito o tipo de contenedor (tipo_envio={$tipo_envio}), abortando");
        return;
    }
    
    // Verificar si ya tiene un contenedor asignado de este tipo
    $container_actual = get_post_meta($post_id, $meta_key_contenedor, true);
    if (!empty($container_actual)) {
        error_log("⏭️ AUTO-ASIGNACIÓN - Envío ya tiene {$meta_key_contenedor} #{$container_actual}, omitiendo");
        return;
    }
    
    // Buscar el contenedor que coincida con el distrito
    $args = array(
        'post_type'      => 'shipment_container',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    );
    
    $containers = get_posts($args);
    $container_encontrado = null;
    
    foreach ($containers as $container) {
        $container_title = $container->post_title;
        
        // Limpiar y normalizar las cadenas para comparación (sin tildes)
        $distrito_limpio = merc_normalizar_texto($distrito);
        $container_limpio = merc_normalizar_texto($container_title);
        
        // Verificar coincidencia exacta
        if (strpos($container_limpio, $distrito_limpio) !== false) {
            $container_encontrado = $container->ID;
            break;
        }
        
        // Verificar coincidencia parcial (distrito dentro del título del contenedor)
        $palabras_distrito = explode(' ', $distrito_limpio);
        $coincidencias = 0;
        foreach ($palabras_distrito as $palabra) {
            if (strlen($palabra) > 3 && strpos($container_limpio, $palabra) !== false) {
                $coincidencias++;
            }
        }
        
        // Si más del 50% de las palabras coinciden, considerar como match
        if ($coincidencias > 0 && $coincidencias >= (count($palabras_distrito) / 2)) {
            $container_encontrado = $container->ID;
            break;
        }
    }
    
    // Si se encontró un contenedor, asignar el envío
    if ($container_encontrado) {
        update_post_meta($post_id, $meta_key_contenedor, $container_encontrado);
        
        error_log("✅ Auto-asignación - Envío #{$post_id}: {$meta_key_contenedor} #{$container_encontrado} asignado");
        
        do_action('merc_after_auto_assign_container', $post_id, $container_encontrado, $distrito, $meta_key_contenedor);
    }
    
    // Si es MERC EMPRENDEDOR, SIEMPRE intentar asignar AMBOS contenedores
    if (strtolower($tipo_envio) === 'normal' && !empty($distrito_destino)) {
        error_log("📍 Auto-asignación EMPRENDEDOR - Validando contenedores para RECOJO y ENTREGA...");
        
        // Asegurarse de que tiene contenedor de RECOJO asignado
        $container_recojo_actual = get_post_meta($post_id, 'shipment_container_recojo', true);
        if (empty($container_recojo_actual) && !empty($distrito)) {
            error_log("📍 Auto-asignación EMPRENDEDOR - Asignando contenedor de RECOJO para: {$distrito}");
            $container_recojo_encontrado = null;
            
            foreach ($containers as $container) {
                $container_title = $container->post_title;
                $distrito_limpio = merc_normalizar_texto($distrito);
                $container_limpio = merc_normalizar_texto($container_title);
                
                if (strpos($container_limpio, $distrito_limpio) !== false) {
                    $container_recojo_encontrado = $container->ID;
                    break;
                }
                
                $palabras_distrito = explode(' ', $distrito_limpio);
                $coincidencias = 0;
                foreach ($palabras_distrito as $palabra) {
                    if (strlen($palabra) > 3 && strpos($container_limpio, $palabra) !== false) {
                        $coincidencias++;
                    }
                }
                
                if ($coincidencias > 0 && $coincidencias >= (count($palabras_distrito) / 2)) {
                    $container_recojo_encontrado = $container->ID;
                    break;
                }
            }
            
            if ($container_recojo_encontrado) {
                $result = update_post_meta($post_id, 'shipment_container_recojo', $container_recojo_encontrado);
                error_log("✅ Auto-asignación EMPRENDEDOR - Envío #{$post_id}: shipment_container_recojo #{$container_recojo_encontrado} asignado para recojo en {$distrito}");
                
                // VERIFICACIÓN: Confirmar que se guardó realmente en BD
                $verificacion = get_post_meta($post_id, 'shipment_container_recojo', true);
                error_log("🔍 VERIFICACIÓN - shipment_container_recojo para envío #{$post_id}: " . ($verificacion ? "✅ Encontrado en BD: {$verificacion}" : "❌ NO encontrado en BD"));
                
                if (empty($verificacion)) {
                    error_log("⚠️ ALERTA - El meta shipment_container_recojo NO se guardó correctamente!");
                }
            }
        }
        
        // Asegurarse de que tiene contenedor de ENTREGA asignado
        $container_entrega_actual = get_post_meta($post_id, 'shipment_container_entrega', true);
        if (empty($container_entrega_actual)) {
            error_log("📍 Auto-asignación EMPRENDEDOR - Asignando contenedor de ENTREGA para: {$distrito_destino}");
            $container_entrega_encontrado = null;
            
            foreach ($containers as $container) {
                $container_title = $container->post_title;
                $distrito_destino_limpio = merc_normalizar_texto($distrito_destino);
                $container_limpio = merc_normalizar_texto($container_title);
                
                if (strpos($container_limpio, $distrito_destino_limpio) !== false) {
                    $container_entrega_encontrado = $container->ID;
                    break;
                }
                
                $palabras_destino = explode(' ', $distrito_destino_limpio);
                $coincidencias = 0;
                foreach ($palabras_destino as $palabra) {
                    if (strlen($palabra) > 3 && strpos($container_limpio, $palabra) !== false) {
                        $coincidencias++;
                    }
                }
                
                if ($coincidencias > 0 && $coincidencias >= (count($palabras_destino) / 2)) {
                    $container_entrega_encontrado = $container->ID;
                    break;
                }
            }
            
            if ($container_entrega_encontrado) {
                $result = update_post_meta($post_id, 'shipment_container_entrega', $container_entrega_encontrado);
                error_log("✅ Auto-asignación EMPRENDEDOR - Envío #{$post_id}: shipment_container_entrega #{$container_entrega_encontrado} asignado para entrega en {$distrito_destino}");
                
                // VERIFICACIÓN: Confirmar que se guardó realmente en BD
                $verificacion = get_post_meta($post_id, 'shipment_container_entrega', true);
                error_log("🔍 VERIFICACIÓN - shipment_container_entrega para envío #{$post_id}: " . ($verificacion ? "✅ Encontrado en BD: {$verificacion}" : "❌ NO encontrado en BD"));
                
                if (empty($verificacion)) {
                    error_log("⚠️ ALERTA - El meta shipment_container_entrega NO se guardó correctamente!");
                }
            } else {
                error_log("⚠️ Auto-asignación EMPRENDEDOR - No se encontró contenedor de entrega para: {$distrito_destino}");
            }
        } else {
            error_log("⏭️ Auto-asignación EMPRENDEDOR - Envío ya tiene contenedor de entrega #{$container_entrega_actual}, omitiendo");
        }
    }
}

/**
 * Obtener envíos asignados al contenedor de RECOJO
 */
function merc_get_shipments_by_container_recojo($container_id) {
    global $wpdb;
    
    // NOTA: Se excluyen envíos MERC EMPRENDEDOR (tipo='normal') que ya estén en "EN BASE MERCOURIER" o posteriores
    // Esto evita que aparezcan en la lista de recojo una vez que ya fueron recogidos
    
    $sql = "SELECT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
    $sql .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
    $sql .= "LEFT JOIN {$wpdb->prefix}postmeta as tbl3 ON tbl1.ID = tbl3.post_id AND tbl3.meta_key = 'wpcargo_status' ";
    $sql .= "LEFT JOIN {$wpdb->prefix}postmeta as tbl4 ON tbl1.ID = tbl4.post_id AND tbl4.meta_key = 'tipo_envio' ";
    $sql .= "WHERE tbl1.post_status = 'publish' AND tbl1.post_type = 'wpcargo_shipment' ";
    $sql .= "AND tbl2.meta_key = 'shipment_container_recojo' ";
    $sql .= "AND tbl2.meta_value = %s ";
    // Lógica: Los envíos tipo 'normal' SOLO aparecen en recojo cuando están en PENDIENTE, RECOGIDO o NO RECOGIDO
    // Otros tipos siempre aparecen
    $sql .= "AND (tbl4.meta_value != 'normal' OR tbl3.meta_value IN ('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO')) ";
    $sql .= "ORDER BY tbl1.ID ASC";
    
    return $wpdb->get_col($wpdb->prepare($sql, $container_id));
}

/**
 * Obtener envíos asignados al contenedor de ENTREGA
 */
function merc_get_shipments_by_container_entrega($container_id) {
    global $wpdb;
    
    $sql = "SELECT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
    $sql .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
    $sql .= "WHERE tbl1.post_status = 'publish' AND tbl1.post_type = 'wpcargo_shipment' ";
    $sql .= "AND tbl2.meta_key = 'shipment_container_entrega' ";
    $sql .= "AND tbl2.meta_value = %s ";
    $sql .= "ORDER BY tbl1.ID ASC";
    
    return $wpdb->get_col($wpdb->prepare($sql, $container_id));
}

/**
 * Obtener TODOS los envíos asignados a un contenedor (recojo + entrega)
 * Mantiene compatibilidad con código existente
 */
function merc_get_all_container_shipments($container_id) {
    $shipments_recojo = merc_get_shipments_by_container_recojo($container_id);
    $shipments_entrega = merc_get_shipments_by_container_entrega($container_id);
    
    // Combinar y remover duplicados (por si acaso)
    return array_unique(array_merge($shipments_recojo, $shipments_entrega));
}

/**
 * ========================================
 * LIMPIEZA DIARIA DE MOTORIZADO RECOJO
 * ========================================
 * 
 * Cada día a las 00:01 se elimina el merc_motorizo_recojo_default de todos los usuarios
 * porque la asignación es manual y diaria
 */

// Registrar el evento programado si no existe
add_action('init', function() {
    if (!wp_next_scheduled('merc_daily_cleanup_motorizo_default')) {
        error_log("🔧 Registrando evento programado: merc_daily_cleanup_motorizo_default");
        wp_schedule_event(time(), 'daily', 'merc_daily_cleanup_motorizo_default');
    }
});

function merc_add_motorizado_history($shipment_id, $tipo, $motorizado_anterior, $motorizado_nuevo, $razon = 'manual') {
    $historia = get_post_meta($shipment_id, 'merc_motorizado_historia', true);
    if (!is_array($historia)) {
        $historia = [];
    }
    
    $entrada = [
        'timestamp' => current_time('Y-m-d H:i:s'),
        'tipo' => $tipo,
        'de' => intval($motorizado_anterior) ?: 0,
        'a' => intval($motorizado_nuevo) ?: 0,
        'razon' => $razon,
        'usuario' => get_current_user_id()
    ];
    
    $historia[] = $entrada;
    update_post_meta($shipment_id, 'merc_motorizado_historia', $historia);
}

// Hook que se ejecuta diariamente
add_action('merc_daily_cleanup_motorizo_default', 'merc_cleanup_motorizo_recojo_default');
function merc_cleanup_motorizo_recojo_default() {
    global $wpdb;
    
    error_log("\n════════════════════════════════════════════════════════════");
    error_log("🧹 [LIMPIEZA DIARIA] Eliminando merc_motorizo_recojo_default de todos los usuarios");
    error_log("════════════════════════════════════════════════════════════");
    
    // Obtener todos los usuarios que tienen merc_motorizo_recojo_default
    $users_with_driver = $wpdb->get_col(
        "SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
        WHERE meta_key = 'merc_motorizo_recojo_default'"
    );
    
    error_log("📊 Usuarios con motorizado_recojo_default: " . count($users_with_driver));
    
    $deleted_count = 0;
    foreach ($users_with_driver as $user_id) {
        $motorizado = get_user_meta($user_id, 'merc_motorizo_recojo_default', true);
        $deleted = delete_user_meta($user_id, 'merc_motorizo_recojo_default');
        
        if ($deleted) {
            error_log("   ✅ Usuario #$user_id: Eliminado motorizado #$motorizado");
            $deleted_count++;
        } else {
            error_log("   ⚠️  Usuario #$user_id: No se pudo eliminar (quizás ya estaba vacío)");
        }
    }
    
    error_log("🔄 Total usuarios limpios: $deleted_count");
    error_log("✅ [LIMPIEZA DIARIA COMPLETADA] Próxima ejecución en 24 horas");
    error_log("════════════════════════════════════════════════════════════\n");
}

/**
 * AJAX handler: Asignar motorizado masivamente a envíos
 */
add_action('wp_ajax_merc_assign_motorizado_bulk', 'merc_assign_motorizado_bulk_ajax');
add_action('wp_ajax_nopriv_merc_assign_motorizado_bulk', 'merc_assign_motorizado_bulk_ajax');
function merc_assign_motorizado_bulk_ajax() {
    error_log("\n🔍 MERC_ASSIGN_MOTORIZADO_BULK - Solicitud AJAX RECIBIDA");
    error_log("📬 POST data: " . json_encode($_POST));
    
    try {
        // Verificar nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        error_log("🔐 Nonce: " . (empty($nonce) ? 'VACÍO' : $nonce));
        
        if (empty($nonce) || !wp_verify_nonce($nonce, 'merc_assign_motorizado')) {
            error_log("❌ NONCE verificado como INVÁLIDO");
            wp_send_json_error(['message' => 'Nonce inválido'], 403);
            exit;
        }
        
        error_log("✅ Nonce válido");
        
        $user_ids = isset($_POST['user_ids']) ? array_map('intval', (array) $_POST['user_ids']) : [];
        $driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
        $container_id = isset($_POST['container_id']) ? intval($_POST['container_id']) : 0;
        
        error_log("👤 User IDs: " . json_encode($user_ids));
        error_log("🚙 Driver ID: " . $driver_id);
        error_log("📦 Container ID: " . $container_id);
        
        if (empty($user_ids) || !$driver_id || !$container_id) {
            wp_send_json_error(['message' => 'Datos inválidos']);
            exit;
        }
        
        error_log("════════════════════════════════════════════");
        error_log("🔄 INICIANDO GUARDADO DE USER_META");
        error_log("   Driver ID: " . $driver_id);
        error_log("   User IDs que recibirán motorizado: " . json_encode($user_ids));
        error_log("════════════════════════════════════════════");
        
        $total = 0;
        foreach ($user_ids as $uid) {
            error_log("→ Procesando usuario #$uid");
            update_user_meta($uid, 'merc_motorizo_recojo_default', $driver_id);
            
            // Verificar que se guardó correctamente
            $verificacion_default = get_user_meta($uid, 'merc_motorizo_recojo_default', true);
            error_log("   📝 Guardado: merc_motorizo_recojo_default = " . ($verificacion_default ?: 'VACÍO'));
            
            if (empty($verificacion_default)) {
                error_log("   ⚠️ ERROR: No se guardó correctamente el user_meta!");
                error_log("   🔍 Debuging - Listando todos los metas del usuario #$uid:");
                $all_metas = get_user_meta($uid);
                foreach ($all_metas as $key => $val) {
                    error_log("      - $key = " . (is_array($val) ? json_encode($val) : $val));
                }
            } else {
                error_log("   ✅ Verificación OK: merc_motorizo_recojo_default = $verificacion_default");
            }
            
            // Buscar envíos de este usuario EN ESTE CONTENEDOR con fecha de recojo HOY
            // FILTROS: solo publicados, solo tipo 'normal'
            global $wpdb;
            $shipments = $wpdb->get_col($wpdb->prepare(
                "SELECT pm.post_id FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                LEFT JOIN {$wpdb->postmeta} pm_tipo ON pm.post_id = pm_tipo.post_id AND pm_tipo.meta_key = 'tipo_envio'
                WHERE p.post_status = 'publish'
                AND p.post_type = 'wpcargo_shipment'
                AND pm.meta_key = 'shipment_container_recojo'
                AND pm.meta_value = %d
                AND pm_tipo.meta_value = 'normal'",
                $container_id
            ));
            
            error_log("📍 Total envíos en container $container_id: " . count($shipments));
            
            // Filtrar: solo los que tienen fecha de recojo HOY
            $shipments_today = array();
            foreach ($shipments as $sid) {
                if (merc_pickup_date_is_today($sid)) {
                    $shipments_today[] = $sid;
                    error_log("   ✓ Envío $sid tiene recojo HOY");
                } else {
                    error_log("   ✗ Envío $sid NO tiene recojo HOY");
                }
            }
            
            error_log("📍 Envíos con recojo HOY: " . json_encode($shipments_today));
            
            foreach ($shipments_today as $ship_id) {
                $shipper = get_post_meta($ship_id, 'registered_shipper', true);
                error_log("   Verificando envío $ship_id - registered_shipper: $shipper, buscando: $uid");
                
                if ($shipper == $uid) {
                    update_post_meta($ship_id, 'wpcargo_motorizo_recojo', $driver_id);
                    
                    // También actualizar wpcargo_driver para que se vea en el listado del motorizado
                    delete_post_meta($ship_id, 'wpcargo_driver');
                    add_post_meta($ship_id, 'wpcargo_driver', $driver_id);
                    error_log("   ✅ Actualizado envío $ship_id para usuario $uid");
                    
                    $total++;
                }
            }
            error_log("✅ Usuario $uid: $total envíos actualizados HOY");
            
            // BONUS: Buscar TODOS los envíos tipo 'normal' del usuario SIN motorizado asignado y asignarles también
            error_log("🔍 Buscando ALL envíos tipo 'normal' del usuario $uid sin motorizado...");
            $all_shipments_no_driver = $wpdb->get_col($wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper' AND pm_shipper.meta_value = %d
                INNER JOIN {$wpdb->postmeta} pm_tipo ON p.ID = pm_tipo.post_id AND pm_tipo.meta_key = 'tipo_envio' AND pm_tipo.meta_value = 'normal'
                LEFT JOIN {$wpdb->postmeta} pm_driver ON p.ID = pm_driver.post_id AND pm_driver.meta_key = 'wpcargo_driver'
                WHERE p.post_status = 'publish'
                AND p.post_type = 'wpcargo_shipment'
                AND pm_driver.post_id IS NULL",
                $uid
            ));
            
            error_log("   📦 Envíos sin motorizado encontrados: " . count($all_shipments_no_driver));
            foreach ($all_shipments_no_driver as $ship_id) {
                // Verificar que NO esté en la lista ya procesada (del contenedor de hoy)
                if (!in_array($ship_id, $shipments_today)) {
                    
                    // VERIFICAR: Si tiene fecha FUTURA, NO asignar motorizado
                    if (merc_pickup_date_is_future($ship_id)) {
                        error_log("   ⏭️  Envío #$ship_id tiene fecha FUTURA, NO asignando motorizado");
                        continue;
                    }
                    
                    error_log("   ➕ Asignando motorizado a envío #$ship_id (anterior, sin motorizado)");
                    update_post_meta($ship_id, 'wpcargo_motorizo_recojo', $driver_id);
                    delete_post_meta($ship_id, 'wpcargo_driver');
                    add_post_meta($ship_id, 'wpcargo_driver', $driver_id);
                    error_log("   ✅ Envío #$ship_id también actualizado");
                    $total++;
                }
            }
        }
        
        error_log("✅ Total envíos asignados: $total");
        wp_send_json_success(['message' => "Motorizado asignado a " . count($user_ids) . " usuario(s). Se actualizaron $total envío(s)", 'count' => $total]);
        exit;
        
    } catch (Exception $e) {
        error_log("❌ ERROR: " . $e->getMessage());
        wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Hook: Aplicar motorizado default al crear nuevos envíos
 * Se ejecuta después de que el envío se haya guardado completamente
 * Si el usuario tiene motorizado_recojo_default, lo asigna automáticamente
 * 
 * Usa el hook 'after_wpcfe_save_shipment' del plugin frontend-manager
 * Este hook se dispara DESPUÉS de que se guardaron todos los metas del envío
 * 
 * PRIORIDAD 999: Garantiza que sea LO ÚLTIMO que se ejecute
 * (evita que otros procesos sobrescriban el estado después)
 */
add_action('after_wpcfe_save_shipment', 'merc_asignar_motorizado_default_al_crear', 999, 2);

function merc_asignar_motorizado_default_al_crear($post_id, $data = array()) {
    error_log("\n🎯 [HOOK: after_wpcfe_save_shipment] DISPARADO para envío #$post_id");
    
    // Verificar que sea realmente un wpcargo_shipment
    if (get_post_type($post_id) !== 'wpcargo_shipment') {
        error_log("   ⏭️  No es wpcargo_shipment (es " . get_post_type($post_id) . "), saltando");
        return;
    }
    
    // ✅ PRIMERO: ASIGNAR ESTADO CORRECTO SEGÚN TIPO (para clientes y admin)
    merc_asignar_estado_final_segun_tipo($post_id);
    
    error_log("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🆕 AUTO-ASIGNAR MOTORIZADO (Nuevo Envío)\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    error_log("📝 Hook ejecutado para envío: #" . $post_id);
    
    // Obtener el usuario (remitente) del envío
    $user_id = get_post_meta($post_id, 'registered_shipper', true);
    error_log("👤 Usuario (registered_shipper): " . ($user_id ?: 'NO ENCONTRADO'));
    
    if (!$user_id) {
        error_log("❌ No hay usuario para este envío");
        error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
        return;
    }
    
    // Obtener el motorizado default del usuario
    $motorizado_default = get_user_meta($user_id, 'merc_motorizo_recojo_default', true);
    error_log("🔍 Buscando 'merc_motorizo_recojo_default' para usuario #" . $user_id . "...");
    error_log("   Valor encontrado: " . ($motorizado_default ? $motorizado_default : 'VACÍO/NO EXISTE'));
    
    if (empty($motorizado_default)) {
        error_log("ℹ️ Usuario #" . $user_id . " NO tiene motorizado default configurado");
        error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
        return;
    }
    
    // Verificar si el envío ya tiene motorizado asignado
    $motorizado_actual = get_post_meta($post_id, 'wpcargo_motorizo_recojo', true);
    error_log("🔍 Verificando 'wpcargo_motorizo_recojo' del envío...");
    error_log("   Valor actual: " . ($motorizado_actual ? $motorizado_actual : 'VACÍO'));
    
    if (!empty($motorizado_actual)) {
        error_log("⚠️ Envío ya tiene motorizado asignado (#" . $motorizado_actual . "), no se sobrescribe");
        error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
        return;
    }
    
    // VERIFICAR: Si el envío tiene fecha FUTURA, NO asignar motorizado
    if (merc_pickup_date_is_future($post_id)) {
        error_log("⏭️  Envío tiene fecha de recojo FUTURA, NO asignando motorizado automáticamente");
        error_log("   (Los envíos futuros deben asignarse manualmente cuando llegue su fecha)");
        error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
        return;
    }
    
    // Asignar el motorizado default
    $motorizado_default_data = get_userdata($motorizado_default);
    $nombre_motorizado = $motorizado_default_data ? $motorizado_default_data->display_name : 'Motorizado #' . $motorizado_default;
    
    update_post_meta($post_id, 'wpcargo_motorizo_recojo', $motorizado_default);
    
    // También asignar wpcargo_driver para que sea visible en la cuenta del motorizado
    delete_post_meta($post_id, 'wpcargo_driver');
    add_post_meta($post_id, 'wpcargo_driver', $motorizado_default);
    
    $verificacion = get_post_meta($post_id, 'wpcargo_motorizo_recojo', true);
    $verificacion_driver = get_post_meta($post_id, 'wpcargo_driver', true);
    
    error_log("✅ AUTO-ASIGNACIÓN COMPLETADA:");
    error_log("   - Envío #" . $post_id . " asignado automáticamente");
    error_log("   - Motorizado: " . $nombre_motorizado . " (ID: " . $motorizado_default . ")");
    error_log("   - Verificación wpcargo_motorizo_recojo: " . $verificacion);
    error_log("   - Verificación wpcargo_driver: " . $verificacion_driver);
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
}

add_action('admin_footer', 'merc_dual_motorizado_js');
add_action('wp_footer', 'merc_dual_motorizado_js');
function merc_dual_motorizado_js() {
    // Solo ejecutarse cuando exista un select wpcargo_driver en la página
    ?>
    <script>
    (function($){
        $(function(){
            var $orig = $('select[name="wpcargo_driver"]');
            if (!$orig.length) return;

            // Sólo insertar en formularios de creación/edición de WPCFE
            var urlParams = new URLSearchParams(window.location.search);
            var wpcfe = (urlParams.get('wpcfe') || '').toLowerCase();
            if (wpcfe !== 'add' && wpcfe !== 'update') return;

            // Evitar duplicar si ya añadimos
            if ($('select[name="wpcargo_motorizo_recojo"]').length) return;

            // Determinar tipo de envío: primero desde el DOM
            var tipo = ($('input[name="tipo_envio"]').val() || $('select[name="tipo_envio"]').val() || '').toString().toLowerCase();
            var motorizo_recojo_val = '';
            var motorizo_entrega_val = '';

            // Si no hay tipo en DOM y estamos en update, solicitarlo al servidor
            if ((!tipo || wpcfe === 'update') && wpcfe === 'update') {
                var shipmentId = ($('input[name="post_id"]').val() || $('input[name="post_ID"]').val() || $('input[name="shipment_id"]').val() || urlParams.get('post_id') || urlParams.get('id') || urlParams.get('shipment_id'));
                if (shipmentId) {
                    var AJAX = (typeof AJAX_URL !== 'undefined') ? AJAX_URL : (typeof ajaxurl !== 'undefined' ? ajaxurl : (window.location.origin + '/wp-admin/admin-ajax.php'));
                    $.ajax({
                        url: AJAX,
                        type: 'POST',
                        data: { action: 'merc_get_shipment_data', shipment_id: shipmentId },
                        async: false,
                        success: function(resp) {
                            if (resp && resp.success && resp.data) {
                                if (resp.data.tipo_envio) {
                                    tipo = (resp.data.tipo_envio || '').toString().toLowerCase();
                                }
                                // Obtener también los motorizados de recojo y entrega
                                if (resp.data.motorizo_recojo) {
                                    motorizo_recojo_val = resp.data.motorizo_recojo.toString();
                                }
                                if (resp.data.motorizo_entrega) {
                                    motorizo_entrega_val = resp.data.motorizo_entrega.toString();
                                }
                            }
                        }
                    });
                }
            }

            // Continuar si el tipo es 'normal', 'express' o 'full_fitment'
            if (tipo !== 'normal' && tipo !== 'express' && tipo !== 'full_fitment') return;

            // Ocultar el select original de forma forzada (inline !important) y marcar hidden
            try {
                $orig.attr('style', 'display:none !important;visibility:hidden !important;');
                $orig.attr('hidden', 'hidden');
                $orig.attr('aria-hidden', 'true');
            } catch(e) {
                // Silenciar errores en navegadores antiguos
            }

            // Para EXPRESS y FULL_FITMENT: solo crear entrega
            // Para NORMAL: crear ambos recojo y entrega
            var isNormal = (tipo === 'normal');
            var $recojo = null;
            var $entrega = $('<select/>', { name: 'wpcargo_motorizo_entrega', class: $orig.attr('class') });

            if (isNormal) {
                $recojo = $('<select/>', { name: 'wpcargo_motorizo_recojo', class: $orig.attr('class') });
            }

            // Copiar opciones a los selects creados
            $orig.find('option').each(function(){
                var $opt = $(this).clone();
                // Remover atributos selected de las copias
                $opt.removeAttr('selected').removeAttr('defaultSelected');
                if (isNormal && $recojo) {
                    $recojo.append($opt.clone());
                }
                $entrega.append($opt.clone());
            });

            // Insertar después del original: apilar verticalmente (uno debajo de otro)
            var $wrap = $('<div style="display:flex;flex-direction:column;gap:8px;margin-top:6px;max-width:420px;"></div>');
            
            if (isNormal && $recojo) {
                var $labelR = $('<label style="font-size:12px;color:#333;margin:0 0 4px 0;">Motorizado (Recojo)</label>');
                $recojo.css('width','100%');
                $wrap.append($labelR).append($recojo);
            }
            
            var $labelE = $('<label style="font-size:12px;color:#333;margin:' + (isNormal ? '8px' : '0') + ' 0 4px 0;">Motorizado' + (isNormal ? ' (Entrega)' : '') + '</label>');
            $entrega.css('width','100%');
            $wrap.append($labelE).append($entrega);
            $orig.after($wrap);

            // Limpiar entrega, y si es normal, también recojo
            $entrega.val('');
            if (isNormal && $recojo) {
                $recojo.val('');
            }
            
            // Buscar valores específicos para recojo y entrega desde las variables obtenidas del AJAX
            // En modo ADD: si no hay valores del AJAX, asignar el valor original solo a recojo (si normal) o entrega (si express)
            // En modo UPDATE: usar los valores obtenidos del AJAX
            
            if (!motorizo_recojo_val && !motorizo_entrega_val) {
                // Modo ADD: Solo propagar al campo correspondiente si hay valor original
                var origVal = $orig.val();
                if (origVal) {
                    if (isNormal) {
                        motorizo_recojo_val = origVal;
                        motorizo_entrega_val = ''; // Entrega queda vacío
                    } else {
                        // Para EXPRESS/FULL_FITMENT: asignar al entrega
                        motorizo_entrega_val = origVal;
                    }
                }
            }
            
            // Asignar valores a cada select explícitamente
            $entrega.val(motorizo_entrega_val);
            if (isNormal && $recojo) {
                $recojo.val(motorizo_recojo_val);
            }

            // Al enviar formularios (incluye AJAX forms), asegurarse de que los selects
            // estén presentes en los datos enviados
            $(document).on('submit', 'form', function(e){
                // Asegurarse de que los valores de motorizado se envíen en el formulario
                var $form = $(this);
                
                // Para EXPRESS/FULL_FITMENT, que no tienen recojo, asignar entrega al recojo si necesario
                // La lógica del backend decidirá a qué meta guardar basándose en el estado
                var sourceVal = isNormal && $recojo ? $recojo.val() : $entrega.val();
                
                if (isNormal) {
                    var $recojoHidden = $form.find('input[name="wpcargo_motorizo_recojo"]');
                    if (!$recojoHidden.length && $recojo && $recojo.length) {
                        $form.append($('<input/>').attr({type:'hidden', name:'wpcargo_motorizo_recojo', value: $recojo.val()}));
                    } else if ($recojoHidden.length && $recojo) {
                        $recojoHidden.val($recojo.val());
                    }
                }
                
                var $entregaHidden = $form.find('input[name="wpcargo_motorizo_entrega"]');
                if (!$entregaHidden.length && $entrega.length) {
                    $form.append($('<input/>').attr({type:'hidden', name:'wpcargo_motorizo_entrega', value: $entrega.val()}));
                } else if ($entregaHidden.length) {
                    $entregaHidden.val($entrega.val());
                }
                
                // Si wpcargo_driver está vacío, copiar desde recojo (si normal) o entrega (si express) para compatibilidad
                if ($orig.length && !$orig.val()) {
                    $orig.val(sourceVal).trigger('change');
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * Guardar metas duales de motorizado al guardar el post type wpcargo_shipment
 * Hook con máxima prioridad para asegurar que se guarda
 */
add_action('save_post_wpcargo_shipment', 'merc_save_dual_motorizado_meta', 999, 1);
function merc_save_dual_motorizado_meta($post_id) {
    // Evitar autosaves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Revisar capacidades: quien puede editar envíos
    if (!current_user_can('edit_post', $post_id)) return;

    error_log("💾 [DUAL_MOTORIZADO_SAVE] Guardando motorizado dual para envío #{$post_id}");
    error_log("   📬 POST keys disponibles: " . implode(', ', array_keys($_POST)));

    // Guardar Motorizado de RECOJO (solo si está en POST - presente en NORMAL tipos)
    if (isset($_POST['wpcargo_motorizo_recojo'])) {
        $val = sanitize_text_field($_POST['wpcargo_motorizo_recojo']);
        $val = !empty($val) ? intval($val) : 0;
        $val_anterior = get_post_meta($post_id, 'wpcargo_motorizo_recojo', true);
        
        if ($val > 0) {
            update_post_meta($post_id, 'wpcargo_motorizo_recojo', $val);
            error_log("      ✔️  Guardado: wpcargo_motorizo_recojo = $val");
            
            // Registrar en historial si cambió
            if ($val !== intval($val_anterior)) {
                merc_add_motorizado_history($post_id, 'recojo', $val_anterior, $val, 'cambio_manual');
            }
        } else {
            delete_post_meta($post_id, 'wpcargo_motorizo_recojo');
            error_log("      🗑️ Eliminado: wpcargo_motorizo_recojo");
            
            // Registrar en historial si había algo antes
            if (!empty($val_anterior)) {
                merc_add_motorizado_history($post_id, 'recojo', $val_anterior, 0, 'cambio_manual');
            }
        }
    }

    // Guardar Motorizado de ENTREGA
    if (isset($_POST['wpcargo_motorizo_entrega'])) {
        $val = sanitize_text_field($_POST['wpcargo_motorizo_entrega']);
        $val = !empty($val) ? intval($val) : 0;
        $val_anterior = get_post_meta($post_id, 'wpcargo_motorizo_entrega', true);
        
        error_log("   ✅ wpcargo_motorizo_entrega = " . ($val ?: 'VACÍO'));
        
        if ($val > 0) {
            update_post_meta($post_id, 'wpcargo_motorizo_entrega', $val);
            error_log("      ✔️  Guardado: wpcargo_motorizo_entrega = $val");
            
            // Registrar en historial si cambió
            if ($val !== intval($val_anterior)) {
                merc_add_motorizado_history($post_id, 'entrega', $val_anterior, $val, 'cambio_manual');
            }
        } else {
            delete_post_meta($post_id, 'wpcargo_motorizo_entrega');
            error_log("      🗑️ Eliminado: wpcargo_motorizo_entrega");
            
            // Registrar en historial si había algo antes
            if (!empty($val_anterior)) {
                merc_add_motorizado_history($post_id, 'entrega', $val_anterior, 0, 'cambio_manual');
            }
        }
    }

    // Sincronizar wpcargo_driver según el tipo y estado del envío
    $tipo = strtolower( (string) get_post_meta($post_id, 'tipo_envio', true) );
    $isExpress = ($tipo === 'express' || $tipo === 'full_fitment');
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    $estados_recojo = array('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO');
    
    error_log("   🔍 Tipo: '$tipo' | Estado actual del envío: '$estado_actual'");
    
    if ( $isExpress ) {
        // EXPRESS/FULL_FITMENT: siempre usar motorizo_entrega
        $motorizado = get_post_meta($post_id, 'wpcargo_motorizo_entrega', true);
        if (!empty($motorizado)) {
            update_post_meta($post_id, 'wpcargo_driver', intval($motorizado));
            error_log("   🔄 Sincronizado: wpcargo_driver = $motorizado (desde entrega de EXPRESS/FULL_FITMENT)");
        } else {
            error_log("   ⚠️  No hay motorizo_entrega para sincronizar en EXPRESS/FULL_FITMENT");
        }
    } else {
        // NORMAL: sincronizar según estado
        if (in_array($estado_actual, $estados_recojo)) {
            // Estados de recojo: sincronizar desde wpcargo_motorizo_recojo
            $motorizado = get_post_meta($post_id, 'wpcargo_motorizo_recojo', true);
            if (!empty($motorizado)) {
                update_post_meta($post_id, 'wpcargo_driver', intval($motorizado));
                error_log("   🔄 Sincronizado: wpcargo_driver = $motorizado (desde recojo - estado: $estado_actual)");
            } else {
                error_log("   ⚠️  No hay wpcargo_motorizo_recojo para sincronizar");
            }
        } else {
            // Estados de entrega: sincronizar desde wpcargo_motorizo_entrega
            $motorizado = get_post_meta($post_id, 'wpcargo_motorizo_entrega', true);
            if (!empty($motorizado)) {
                update_post_meta($post_id, 'wpcargo_driver', intval($motorizado));
                error_log("   🔄 Sincronizado: wpcargo_driver = $motorizado (desde entrega - estado: $estado_actual)");
            } else {
                error_log("   ⚠️  No hay wpcargo_motorizo_entrega para sincronizar");
            }
        }
    }

    error_log("   ✅ [DUAL_MOTORIZADO_SAVE] COMPLETADO");
}

/**
 * BLOQUEAR POST wpcargo_driver - dejar que solo la sincronización lo maneje
 * En edición, no permitir que POST sobrescriba el valor sincronizado
 */
add_action('wp_insert_post_data', 'merc_remove_driver_from_post_data', 10, 2);
function merc_remove_driver_from_post_data($data, $postarr) {
    // Solo en edición (actualización), no en creación
    if ($data['post_type'] !== 'wpcargo_shipment' || empty($postarr['ID'])) {
        return $data;
    }
    
    // Si tiene ID significa que es edición
    if ($postarr['ID'] > 0 && isset($_POST['wpcargo_driver'])) {
        error_log("🚫 [REMOVE_DRIVER_POST] Removiendo wpcargo_driver de POST en edición - envío #" . $postarr['ID']);
        unset($_POST['wpcargo_driver']);
    }
    
    return $data;
}

/**
 * SINCRONIZAR wpcargo_driver SIEMPRE con máxima prioridad
 * Se ejecuta PRIMERO antes que cualquier otro hook
 * Garantiza que cuando CAMBIE cualquier motorizado, se sincronice el driver según el estado
 */
add_action('updated_post_meta', 'merc_sync_driver_priority_first', 0, 4);
function merc_sync_driver_priority_first($meta_id, $post_id, $meta_key, $meta_value) {
    // Solo procesar cambios de motorizo_recojo o motorizo_entrega
    if ($meta_key !== 'wpcargo_motorizo_recojo' && $meta_key !== 'wpcargo_motorizo_entrega') {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'wpcargo_shipment') {
        return;
    }
    
    // Obtener estado actual
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    $estados_recojo = array('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO');
    
    error_log("🔄 [SYNC_PRIORITY_FIRST] Meta cambiada: $meta_key | Valor: " . intval($meta_value) . " | Estado: $estado_actual");
    
    // LÓGICA: según el estado actual, elegir cuál motorizado usar para sincronizar
    if (in_array($estado_actual, $estados_recojo)) {
        // Estado de RECOJO: sincronizar desde motorizo_recojo
        $motorizado_final = get_post_meta($post_id, 'wpcargo_motorizo_recojo', true);
        if (!empty($motorizado_final)) {
            update_post_meta($post_id, 'wpcargo_driver', intval($motorizado_final));
            error_log("   ✅ SINCRONIZADO: wpcargo_driver = " . intval($motorizado_final) . " (desde RECOJO - estado: $estado_actual)");
        }
    } else {
        // Estado de ENTREGA: sincronizar desde motorizo_entrega
        $motorizado_final = get_post_meta($post_id, 'wpcargo_motorizo_entrega', true);
        if (!empty($motorizado_final)) {
            update_post_meta($post_id, 'wpcargo_driver', intval($motorizado_final));
            error_log("   ✅ SINCRONIZADO: wpcargo_driver = " . intval($motorizado_final) . " (desde ENTREGA - estado: $estado_actual)");
        }
    }
}

/**
 * SINCRONIZAR wpcargo_driver cuando cambia wpcargo_motorizo_recojo
 * Detecta el estado actual y sincroniza el driver según corresponda
 */
add_action('updated_post_meta', 'merc_sync_on_motorizo_recojo_update', 10, 4);
function merc_sync_on_motorizo_recojo_update($meta_id, $post_id, $meta_key, $meta_value) {
    if ($meta_key !== 'wpcargo_motorizo_recojo') {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'wpcargo_shipment') {
        return;
    }
    
    // Obtener estado actual
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    $estados_recojo = array('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO');
    
    error_log("🔄 [MOTORIZO_RECOJO_UPDATE] Envío #$post_id | Nueva valor: " . intval($meta_value) . " | Estado: $estado_actual");
    
    // Si está en estado de recojo → sincronizar desde recojo
    if (in_array($estado_actual, $estados_recojo)) {
        if (!empty($meta_value)) {
            update_post_meta($post_id, 'wpcargo_driver', intval($meta_value));
            error_log("   ✅ SINCRONIZADO: wpcargo_driver = " . intval($meta_value) . " (desde RECOJO)");
        }
    }
}

/**
 * SINCRONIZAR wpcargo_driver cuando cambia wpcargo_motorizo_entrega
 * Detecta el estado actual y sincroniza el driver según corresponda
 */
add_action('updated_post_meta', 'merc_sync_on_motorizo_entrega_update', 10, 4);
function merc_sync_on_motorizo_entrega_update($meta_id, $post_id, $meta_key, $meta_value) {
    if ($meta_key !== 'wpcargo_motorizo_entrega') {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'wpcargo_shipment') {
        return;
    }
    
    // Obtener estado actual
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    $estados_recojo = array('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO');
    
    error_log("🔄 [MOTORIZO_ENTREGA_UPDATE] Envío #$post_id | Nuevo valor: " . intval($meta_value) . " | Estado: $estado_actual");
    
    // Si NO está en estado de recojo → sincronizar desde entrega
    if (!in_array($estado_actual, $estados_recojo)) {
        if (!empty($meta_value)) {
            update_post_meta($post_id, 'wpcargo_driver', intval($meta_value));
            error_log("   ✅ SINCRONIZADO: wpcargo_driver = " . intval($meta_value) . " (desde ENTREGA)");
        }
    }
}

/**
 * SINCRONIZAR wpcargo_driver cuando se CARGA el formulario de edición
 * Según el estado actual y motorizado asignado (recojo o entrega)
 * Hook: wpcfe_before_load_shipment_form o similar
 */
add_action('wpcfe_before_load_shipment_form', 'merc_sync_driver_on_form_load', 99);
function merc_sync_driver_on_form_load($shipment_id) {
    if (!$shipment_id) {
        return;
    }
    
    $post = get_post($shipment_id);
    if (!$post || $post->post_type !== 'wpcargo_shipment') {
        return;
    }
    
    // Obtener estado actual
    $estado_actual = get_post_meta($shipment_id, 'wpcargo_status', true);
    $estados_recojo = array('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO');
    
    error_log("🔄 [SYNC_DRIVER_ON_LOAD] Envío #$shipment_id - Estado: '$estado_actual'");
    
    if (in_array($estado_actual, $estados_recojo)) {
        // Estados de recojo: sincronizar desde wpcargo_motorizo_recojo
        $motorizado = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
        if (!empty($motorizado)) {
            update_post_meta($shipment_id, 'wpcargo_driver', intval($motorizado));
            error_log("   ✅ Sincronizado: wpcargo_driver = $motorizado (desde RECOJO)");
        }
    } else {
        // Estados de entrega: sincronizar desde wpcargo_motorizo_entrega
        $motorizado = get_post_meta($shipment_id, 'wpcargo_motorizo_entrega', true);
        if (!empty($motorizado)) {
            update_post_meta($shipment_id, 'wpcargo_driver', intval($motorizado));
            error_log("   ✅ Sincronizado: wpcargo_driver = $motorizado (desde ENTREGA)");
        }
    }
}

/**
 * Asignar estado FINAL correcto según tipo de envío
 * Se ejecuta como ÚLTIMA acción después de guardar, para garantizar que
 * admin Y cliente tienen el estado correcto (sin depender del frontend)
 */
function merc_asignar_estado_final_segun_tipo($post_id) {
    error_log("\n🔧 [ASIGNAR ESTADO FINAL] Ejecutando para envío #$post_id");
    
    $tipo_envio = get_post_meta($post_id, 'tipo_envio', true);
    error_log("   📦 Tipo de envío: " . ($tipo_envio ?: 'NO ENCONTRADO'));
    
    if (empty($tipo_envio)) {
        error_log("   ⚠️ No hay tipo_envio, saltando asignación de estado");
        return;
    }
    
    $tipo_lower = strtolower(trim($tipo_envio));
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    
    error_log("   📌 Estado actual en BD: " . ($estado_actual ?: 'VACÍO'));
    
    // Express, Agencia o Full Fitment → RECEPCIONADO (para admin y cliente)
    if ($tipo_lower === 'express' || 
        stripos($tipo_envio, 'agencia') !== false || 
        $tipo_lower === 'full_fitment' || 
        stripos($tipo_envio, 'full fitment') !== false) {
        
        // Solo cambiar si NO es RECEPCIONADO
        if ($estado_actual !== 'RECEPCIONADO') {
            error_log("   ✅ TIPO ESPECIAL detectado (" . $tipo_lower . ") - Asignando RECEPCIONADO");
            update_post_meta($post_id, 'wpcargo_status', 'RECEPCIONADO');
            merc_sync_service_cost_by_status( $post_id );
            error_log("   ✔️ Estado guardado: RECEPCIONADO");
        } else {
            error_log("   ℹ️ Ya tiene RECEPCIONADO, no modificar");
        }
    }
    // Normal o Emprendedor → Mantener estado o asignar primer estado de recojo
    elseif ($tipo_lower === 'normal' || stripos($tipo_envio, 'emprendedor') !== false) {
        error_log("   📦 Tipo NORMAL/EMPRENDEDOR - Verificando estado");
        
        // Si no tiene estado, asignar el primero configurado
        if (empty($estado_actual)) {
            $estados_recojo = get_option('wpcpod_pickup_route_status', array());
            if (!empty($estados_recojo) && is_array($estados_recojo)) {
                $estado_inicial = reset($estados_recojo);
                update_post_meta($post_id, 'wpcargo_status', $estado_inicial);
                merc_sync_service_cost_by_status( $post_id );
                error_log("   ✔️ Estado asignado: " . $estado_inicial);
            }
        }
    }
}

/**
 * AJAX handler: Asignar / Desasignar motorizado ENTREGA masivamente a envíos
 * Reemplaza el handler anterior merc_assign_motorizado_entrega_bulk_ajax
 */
add_action('wp_ajax_merc_assign_motorizado_entrega_bulk', 'merc_assign_motorizado_entrega_bulk_ajax');
add_action('wp_ajax_nopriv_merc_assign_motorizado_entrega_bulk', 'merc_assign_motorizado_entrega_bulk_ajax');
function merc_assign_motorizado_entrega_bulk_ajax() {
    error_log("\n🔍 MERC_ASSIGN_MOTORIZADO_ENTREGA_BULK - Solicitud AJAX RECIBIDA");
    error_log("📬 POST data: " . json_encode($_POST));
    
    try {
        // Verificar nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        error_log("🔐 Nonce: " . (empty($nonce) ? 'VACÍO' : $nonce));
        
        if (empty($nonce) || !wp_verify_nonce($nonce, 'merc_assign_motorizado')) {
            error_log("❌ NONCE verificado como INVÁLIDO");
            wp_send_json_error(['message' => 'Nonce inválido'], 403);
            exit;
        }
        
        error_log("✅ Nonce válido");
        
        $shipment_ids = isset($_POST['shipment_ids']) ? array_map('intval', (array) $_POST['shipment_ids']) : [];
        $driver_id    = isset($_POST['driver_id']) ? sanitize_text_field($_POST['driver_id']) : '';
 
        // Detectar si es desasignación
        $is_desasignar = ($driver_id === 'desasignar' || $driver_id === '0' || $driver_id === '');
        $driver_id_int = $is_desasignar ? 0 : intval($driver_id);
 
        error_log("📦 Shipment IDs: " . json_encode($shipment_ids));
        error_log("🚙 Driver ID raw: '$driver_id' | int: $driver_id_int | Desasignar: " . ($is_desasignar ? 'SÍ' : 'NO'));
 
        // Validar: necesitamos envíos; driver solo si es asignación
        if (empty($shipment_ids)) {
            wp_send_json_error(['message' => 'Datos inválidos: no se recibieron envíos']);
            exit;
        }
        if (!$is_desasignar && !$driver_id_int) {
            wp_send_json_error(['message' => 'Datos inválidos: driver_id incorrecto']);
            exit;
        }
 
        $total = 0;
        $estados_recojo = array('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO');
 
        foreach ($shipment_ids as $sid) {
            $estado_actual = get_post_meta($sid, 'wpcargo_status', true);
            $upper_status  = strtoupper((string) $estado_actual);
 
            error_log("→ Envío #$sid | Estado: '$estado_actual' | Acción: " . ($is_desasignar ? 'DESASIGNAR' : "ASIGNAR driver $driver_id_int"));
 
            if ($is_desasignar) {
                // ── DESASIGNACIÓN ──────────────────────────────────────────────
                delete_post_meta($sid, 'wpcargo_motorizo_entrega');
                error_log("   🗑️ Eliminado: wpcargo_motorizo_entrega del envío #$sid");
 
                // Limpiar wpcargo_driver solo si el envío NO está en estado de recojo
                // (si está en recojo, el driver viene del motorizo_recojo, no tocarlo)
                if (!in_array($upper_status, $estados_recojo, true)) {
                    delete_post_meta($sid, 'wpcargo_driver');
                    error_log("   🗑️ Limpiado wpcargo_driver del envío #$sid (no está en estado recojo)");
                } else {
                    error_log("   ℹ️  wpcargo_driver preservado (envío en estado recojo: $estado_actual)");
                }
            } else {
                // ── ASIGNACIÓN ─────────────────────────────────────────────────
                update_post_meta($sid, 'wpcargo_motorizo_entrega', $driver_id_int);
 
                if (in_array($upper_status, $estados_recojo, true)) {
                    // En estado recojo: NO sobrescribir wpcargo_driver
                    error_log("   - Estado '$estado_actual': actualizado motorizo_entrega pero preservado wpcargo_driver");
                } else {
                    // En estado entrega: actualizar wpcargo_driver
                    delete_post_meta($sid, 'wpcargo_driver');
                    add_post_meta($sid, 'wpcargo_driver', $driver_id_int);
                    error_log("   - wpcargo_driver actualizado a $driver_id_int para envío #$sid (estado: $estado_actual)");
                }
            }
 
            $total++;
        }
 
        error_log("✅ Total envíos procesados: $total");
 
        if ($is_desasignar) {
            $mensaje = "Motorizado desasignado de $total envío(s) de entrega.";
        } else {
            $mensaje = "Motorizado asignado a $total envío(s) de entrega.";
        }
 
        wp_send_json_success(['message' => $mensaje, 'count' => $total]);
        exit;
 
    } catch (Exception $e) {
        error_log("❌ ERROR: " . $e->getMessage());
        wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * AJAX handler: Actualización masiva de estado (wpcr_bulk_update)
 * Intercepta y mejora la actualización masiva para preservar estado anterior
 */
add_action('wp_ajax_wpcr_bulk_update', 'merc_wpcr_bulk_update_ajax', 5);
add_action('wp_ajax_nopriv_wpcr_bulk_update', 'merc_wpcr_bulk_update_ajax', 5);
function merc_wpcr_bulk_update_ajax() {
    $selected_shipments = isset($_POST['selectedShipment']) ? sanitize_text_field($_POST['selectedShipment']) : '';
    $shipments_array = array_filter(array_map('intval', explode(',', $selected_shipments)));
    
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    error_log("🔄 [BULK_UPDATE_INICIADO] Procesando " . count($shipments_array) . " envío(s)");
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    
    if (empty($shipments_array)) {
        error_log("❌ [BULK_UPDATE] No hay envíos seleccionados");
        wp_send_json_error(['message' => 'No shipments selected']);
    }
    
    $receiver_fields = isset($_POST['receiverFields']) ? $_POST['receiverFields'] : [];
    $new_status = '';
    
    // Extraer nuevo estado de los campos enviados
    foreach ($receiver_fields as $field) {
        if ($field['index'] === 'status') {
            $new_status = sanitize_text_field($field['val']);
            break;
        }
    }
    
    error_log("📝 [BULK_UPDATE] Nuevo Estado: '" . $new_status . "'");
    error_log("📊 [BULK_UPDATE] Envíos a procesar: " . implode(', ', $shipments_array));
    error_log("");
    
    $updated_count = 0;
    
    foreach ($shipments_array as $shipment_id) {
        error_log("┌─ Procesando Envío #" . $shipment_id);
        
        $old_status = get_post_meta($shipment_id, 'wpcargo_status', true);
        error_log("│  Estado Actual: '" . $old_status . "'");
        
        // Si el nuevo estado es "LISTO PARA SALIR", guardar el estado anterior EN EL HISTORIAL
        if (!empty($new_status) && stripos($new_status, 'LISTO PARA SALIR') !== false && !empty($old_status)) {
            error_log("│  ℹ️  Nuevo estado es 'LISTO PARA SALIR' y hay estado anterior");
            error_log("│  💾 Guardando estado anterior en historial...");
            
            // Obtener historial actual
            $shipment_history = maybe_unserialize(get_post_meta($shipment_id, 'wpcargo_shipments_update', true));
            if (!is_array($shipment_history)) {
                $shipment_history = array();
                error_log("│     📋 Historial creado (estaba vacío)");
            } else {
                error_log("│     📋 Historial existente con " . count($shipment_history) . " registros");
            }
            
            // Crear registro del estado anterior
            $previous_state_record = array(
                'status' => $old_status,
                'date' => current_time('Y-m-d'),
                'time' => current_time('H:i:s'),
                'updated-name' => wp_get_current_user()->display_name,
                'location' => get_post_meta($shipment_id, 'location', true),
                'remarks' => 'Estado anterior (actualización masiva a LISTO PARA SALIR)'
            );
            
            error_log("│     ➕ Registro creado: status='" . $old_status . "' | usuario='" . $previous_state_record['updated-name'] . "'");
            
            // Agregarreglo al inicio del historial
            array_unshift($shipment_history, $previous_state_record);
            update_post_meta($shipment_id, 'wpcargo_shipments_update', $shipment_history);
            
            error_log("│     ✅ Historial actualizado en meta (total: " . count($shipment_history) . " registros)");
            
            // También guardar en meta específico
            update_post_meta($shipment_id, 'wpcargo_status_anterior', $old_status);
            
            error_log("│     ✅ Meta 'wpcargo_status_anterior' establecido a: '" . $old_status . "'");
            
            // Verificar que se guardó
            $verificacion = get_post_meta($shipment_id, 'wpcargo_status_anterior', true);
            if ($verificacion === $old_status) {
                error_log("│     ✅ ✅ Verificación: Estado anterior confirmado en meta");
            } else {
                error_log("│     ❌ ❌ ERROR: Verificación fallida. Meta tiene: '" . $verificacion . "'");
            }
        }
        
        // Actualizar el estado
        if (!empty($new_status)) {
            update_post_meta($shipment_id, 'wpcargo_status', $new_status);
            merc_sync_service_cost_by_status( $shipment_id );
            error_log("│  📌 Nuevo Estado Guardado: '" . $new_status . "'");
            $updated_count++;
        }
        
        error_log("└─ Envío #" . $shipment_id . " completado\n");
    }
    
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    error_log("✅ [BULK_UPDATE_FINALIZADO] " . $updated_count . " de " . count($shipments_array) . " envío(s) actualizado(s)");
    error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
    
    // Retornar mensaje de éxito (que es lo que espera el JS)
    wp_send_json_success([
        'message' => 'Se actualizaron ' . $updated_count . ' envío(s) correctamente',
        'count' => $updated_count
    ]);
}

/**
 * Agregar las dos secciones personalizadas de envíos usando JavaScript
 * DEPRECATED - Ahora se hace directamente en el template container-form-shipments.tpl.php
 */
// REMOVIDO - La lógica fue migrada al template del plugin para mejor mantenibilidad

/**
 * Desactivar la funcionalidad de sortable (arrastrar) en los envíos del contenedor
 * DEPRECATED - Lógica migrada al template
 */
// REMOVIDO - Ya no necesaria con la nueva arquitectura

/**
 * AJAX para remover envío del contenedor (compatible con ambos tipos)
 */
add_action('wp_ajax_merc_remove_shipment_from_container', 'merc_remove_shipment_from_container_ajax');
function merc_remove_shipment_from_container_ajax() {
    check_ajax_referer('merc_remove_shipment', 'nonce');
    
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    $meta_key = isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : '';
    
    if (!$shipment_id || !in_array($meta_key, ['shipment_container_recojo', 'shipment_container_entrega'])) {
        wp_send_json_error(['message' => 'Parámetros no válidos']);
        return;
    }
    
    // Remover el meta
    $deleted = delete_post_meta($shipment_id, $meta_key);
    
    if ($deleted) {
        wp_send_json_success(['message' => 'Envío removido correctamente']);
    } else {
        wp_send_json_error(['message' => 'No se pudo remover el envío']);
    }
}

/**
 * Función adicional para forzar la asignación del motorizado
 * Se ejecuta con máxima prioridad para asegurar que el motorizado se guarde
 */


/**
 * Reasignar contenedor y motorizado cuando el estado cambia a EN BASE MERCOURIER
 * Esto desasigna el contenedor de RECOJO y asigna un contenedor de ENTREGA basándose en el distrito de destino
 * 
 * FLUJO MERC EMPRENDEDOR:
 * 1. Creación: Se asigna contenedor_recojo (distrito origen)
 * 2. Al llegar a base (EN BASE MERCOURIER): Se asigna contenedor_entrega (distrito destino)
 */
add_action('updated_post_meta', 'merc_reasignar_en_base_mercourier', 10, 4);
// Hook adicional de mayor prioridad para capturar cambios de estado
add_action('wpc_add_sms_shipment_history', 'merc_procesar_cambio_estado_base_mercourier', 9, 1);
function merc_procesar_cambio_estado_base_mercourier($post_id) {
    // Obtener el estado actual
    $status = get_post_meta($post_id, 'wpcargo_status', true);
    
    // Si es EN BASE MERCOURIER, procesarlo
    if (stripos($status, 'EN BASE MERCOURIER') !== false) {
        error_log("🔔 HOOK ALTERNATIVO: Detectado estado EN BASE MERCOURIER para envío #{$post_id}");
        // Dispara la función directamente
        merc_reasignar_en_base_mercourier(null, $post_id, 'wpcargo_status', $status);
    }
}

function merc_reasignar_en_base_mercourier($meta_id, $post_id, $meta_key, $meta_value) {
    // Solo procesar si es un shipment y el meta key es el estado
    if (get_post_type($post_id) !== 'wpcargo_shipment') {
        return;
    }
    
    if ($meta_key !== 'wpcargo_status') {
        return;
    }
    
    // Verificar si el nuevo estado es "EN BASE MERCOURIER"
    if (stripos($meta_value, 'EN BASE MERCOURIER') === false) {
        return;
    }
    
    // Obtener tipo de envío
    $tipo_envio = get_post_meta($post_id, 'tipo_envio', true);
    
    // Solo procesar si es MERC EMPRENDEDOR (normal)
    if (strtolower($tipo_envio) !== 'normal') {
        error_log("⏭️ Reasignación EN BASE - Envío #{$post_id}: No es MERC EMPRENDEDOR (tipo: {$tipo_envio}), omitiendo");
        return;
    }
    
    // Verificar si ya tiene contenedor de entrega asignado
    $contenedor_entrega_actual = get_post_meta($post_id, 'shipment_container_entrega', true);
    $ya_tiene_entrega = !empty($contenedor_entrega_actual);
    
    error_log("🔄 Reasignación EN BASE MERCOURIER INICIADA - Envío #{$post_id}");
    if ($ya_tiene_entrega) {
        error_log("ℹ️ Reasignación EN BASE - Envío #{$post_id}: Ya tiene contenedor de entrega #{$contenedor_entrega_actual}, solo actualizando motorizado...");
    }
    
    // Mantener el contenedor de recojo (NO lo eliminamos, el envío estuvo ahí)
    $container_recojo = get_post_meta($post_id, 'shipment_container_recojo', true);
    error_log("📦 Reasignación EN BASE - Envío #{$post_id}: Contenedor de recojo: #{$container_recojo}");
    
    // TRANSFERIR MOTORIZADO: Determinar qué motorizado usar para entrega
    $motorizado_recojo = get_post_meta($post_id, 'wpcargo_motorizo_recojo', true);
    $motorizado_entrega = get_post_meta($post_id, 'wpcargo_motorizo_entrega', true);
    
    // El motorizado a usar para entrega es el que esté asignado en motorizado_entrega (o el de recojo si no hay entrega)
    $motorizado_para_entrega = !empty($motorizado_entrega) ? $motorizado_entrega : $motorizado_recojo;
    
    if (!empty($motorizado_para_entrega)) {
        // Obtener el nombre del motorizado para log
        $motorizado_info = get_user_by('id', $motorizado_para_entrega);
        $motorizado_nombre = $motorizado_info ? $motorizado_info->display_name : "ID#{$motorizado_para_entrega}";
        
        // Asignar el motorizado a wpcargo_driver (ahora el envío está EN BASE y pasará a fase de ENTREGA)
        delete_post_meta($post_id, 'wpcargo_driver');
        add_post_meta($post_id, 'wpcargo_driver', $motorizado_para_entrega);
        
        // Guardar en motorizado_entrega si no estaba ya
        if (empty($motorizado_entrega) && !empty($motorizado_recojo)) {
            // Si no hay motorizado_entrega pero sí hay de recojo, guardar la transferencia
            delete_post_meta($post_id, 'wpcargo_motorizo_entrega');
            add_post_meta($post_id, 'wpcargo_motorizo_entrega', $motorizado_recojo);
            error_log("🚚 Reasignación EN BASE - Envío #{$post_id}: TRANSFERENCIA DE MOTORIZADO: {$motorizado_nombre} (ID:{$motorizado_recojo}) transferido de recojo a entrega");
        } else if (!empty($motorizado_entrega)) {
            error_log("🚚 Reasignación EN BASE - Envío #{$post_id}: MOTORIZADO ENTREGA YA ASIGNADO: {$motorizado_nombre} (ID:{$motorizado_entrega})");
        }
        
        error_log("📍 Reasignación EN BASE - Envío #{$post_id}: wpcargo_driver actualizado a #{$motorizado_para_entrega}");
    } else {
        error_log("ℹ️ Reasignación EN BASE - Envío #{$post_id}: No hay motorizado asignado para recojo ni entrega");
    }
    
    // PASO: Obtener distrito de DESTINO para asignar contenedor de ENTREGA (solo si no lo tiene)
    if (!$ya_tiene_entrega) {
        $distrito = get_post_meta($post_id, 'wpcargo_distrito_destino', true);
        
        if (empty($distrito)) {
            error_log("⚠️ Reasignación EN BASE - Envío #{$post_id}: No se encontró distrito de destino");
            return;
        }
        
        error_log("📍 Reasignación EN BASE - Envío #{$post_id}: Buscando contenedor de ENTREGA para distrito: {$distrito}");
        
        // Buscar contenedor que coincida con el distrito de DESTINO
        $args = array(
            'post_type'      => 'shipment_container',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        );
        
        $containers = get_posts($args);
        $container_encontrado = null;
        
        foreach ($containers as $container) {
            $container_title = $container->post_title;
            
            // Limpiar y normalizar las cadenas para comparación
            $distrito_limpio = merc_normalizar_texto($distrito);
            $container_limpio = merc_normalizar_texto($container_title);
            
            // Verificar coincidencia exacta
            if (strpos($container_limpio, $distrito_limpio) !== false) {
                $container_encontrado = $container->ID;
                error_log("✅ Reasignación EN BASE - Envío #{$post_id}: Contenedor de ENTREGA encontrado #{$container_encontrado} ({$container_title})");
                break;
            }
            
            // Verificar coincidencia parcial
            $palabras_distrito = explode(' ', $distrito_limpio);
            $coincidencias = 0;
            foreach ($palabras_distrito as $palabra) {
                if (strlen($palabra) > 3 && strpos($container_limpio, $palabra) !== false) {
                    $coincidencias++;
                }
            }
            
            if ($coincidencias > 0 && $coincidencias >= (count($palabras_distrito) / 2)) {
                $container_encontrado = $container->ID;
                error_log("✅ Reasignación EN BASE - Envío #{$post_id}: Contenedor de ENTREGA encontrado (parcial) #{$container_encontrado} ({$container_title})");
                break;
            }
        }
        
        // Si se encontró un contenedor, asignarlo como contenedor de ENTREGA
        if ($container_encontrado) {
            update_post_meta($post_id, 'shipment_container_entrega', $container_encontrado);
            
            error_log("✅ Reasignación EN BASE completa - Envío #{$post_id}: RECOJO=#{$container_recojo}, ENTREGA=#{$container_encontrado}");
            
            do_action('merc_after_assign_entrega_container', $post_id, $container_encontrado, $distrito);
        } else {
            error_log("❌ Reasignación EN BASE - Envío #{$post_id}: No se encontró contenedor de ENTREGA para distrito {$distrito}");
        }
    } else {
        error_log("✅ Reasignación EN BASE - Envío #{$post_id}: Ya tiene contenedor de entrega #{$contenedor_entrega_actual}");
    }
}

/**
 * BLOQUEADO: Hacer que envíos con estado ENTREGADO no sean editables
 * Esto mantiene el historial del contenedor y motorizado
 */
add_filter('user_has_cap', 'merc_bloquear_edicion_entregado', 10, 4);
function merc_bloquear_edicion_entregado($allcaps, $caps, $args, $user) {
    // Solo aplicar en el admin
    if (!is_admin()) {
        return $allcaps;
    }
    
    // Verificar si se está intentando editar un post
    if (isset($args[0]) && $args[0] === 'edit_post' && !empty($args[2])) {
        $post_id = $args[2];
        
        // Verificar si es un shipment
        if (get_post_type($post_id) === 'wpcargo_shipment') {
            $estado = strtoupper(trim(get_post_meta($post_id, 'wpcargo_status', true)));
            
            // Estados finales que no pueden ser editados
            $estados_bloqueados = array('ENTREGADO', 'RECOGIDO', 'NO RECOGIDO', 'ANULADO', 'REPROGRAMADO', 'NO RECIBIDO');
            
            // Si está en un estado bloqueado, remover la capacidad de editar
            foreach ($estados_bloqueados as $estado_bloqueado) {
                if (stripos($estado, $estado_bloqueado) !== false) {
                    $allcaps['edit_post'] = false;
                    $allcaps['edit_posts'] = false;
                    break;
                }
            }
        }
    }
    
    return $allcaps;
}

// Agregar aviso visual en el admin cuando un envío está ENTREGADO
add_action('edit_form_after_title', 'merc_aviso_entregado_no_editable');
function merc_aviso_entregado_no_editable($post) {
    if ($post->post_type !== 'wpcargo_shipment') {
        return;
    }
    
    $estado = strtoupper(trim(get_post_meta($post->ID, 'wpcargo_status', true)));
    
    // Estados finales que no pueden ser editados
    $estados_bloqueados = array('ENTREGADO', 'RECOGIDO', 'NO RECOGIDO', 'ANULADO', 'REPROGRAMADO', 'NO RECIBIDO');
    $is_bloqueado = false;
    
    foreach ($estados_bloqueados as $estado_bloqueado) {
        if (stripos($estado, $estado_bloqueado) !== false) {
            $is_bloqueado = true;
            break;
        }
    }
    
    if ($is_bloqueado) {
        ?>
        <div class="notice notice-warning" style="margin: 15px 0; padding: 12px; border-left: 4px solid #ffb900;">
            <p style="margin: 0; font-weight: bold;">
                🔒 Este envío está en estado <strong><?php echo esc_html($estado); ?></strong> y no puede ser editado. Se requiere validación de administrador.
            </p>
        </div>
        <style>
            /* Deshabilitar todos los campos del formulario */
            #post-body input:not([type="hidden"]),
            #post-body select,
            #post-body textarea {
                pointer-events: none;
                opacity: 0.6;
                background-color: #f5f5f5 !important;
            }
            /* Ocultar botones de acción */
            #publish, #save-post, .submitdelete {
                display: none !important;
            }
        </style>
        <?php
    }
}

/**
 * ⛔ BLOQUEO DEFINITIVO DE ESTADOS FINALES (WPCARGO)
 * Aplica a tabla, AJAX y cualquier intento de cambio
 */
add_action('wp_ajax_merc_actualizar_estado', 'merc_bloquear_estados_finales', 0);
add_action('wp_ajax_nopriv_merc_actualizar_estado', 'merc_bloquear_estados_finales', 0);

function merc_bloquear_estados_finales() {

    if (!is_user_logged_in()) {
        wp_send_json_error('No autorizado');
    }

    $shipment_id = intval($_POST['shipment_id'] ?? 0);

    if (!$shipment_id) {
        wp_send_json_error('Shipment inválido');
    }

    $estado_actual = strtoupper(trim(
        get_post_meta($shipment_id, 'wpcargo_status', true)
    ));

    $estados_finales = array(
        'ENTREGADO',
        'RECOGIDO',
        'NO RECOGIDO',
        'ANULADO',
        'REPROGRAMADO',
        'NO RECIBIDO'
    );

    if (in_array($estado_actual, $estados_finales, true)) {
        wp_send_json_error(array(
            'message' => '⛔ Este envío está en estado ' . $estado_actual . ' y no puede ser modificado. Se requiere validación de administrador.'
        ));
    }

    // si no es final, deja continuar
}

/**
 * Desasignar contenedor cuando el estado cambia a "EN BASE MERCOURIER"
 */
add_action('updated_post_meta', 'merc_desasignar_contenedor_en_base', 10, 4);
function merc_desasignar_contenedor_en_base($meta_id, $post_id, $meta_key, $meta_value) {
    // Solo procesar si es un shipment y el meta key es el historial de estado
    if (get_post_type($post_id) !== 'wpcargo_shipment') {
        return;
    }
    
    if ($meta_key !== 'wpcargo_shipment_history') {
        return;
    }
    
    // Obtener el historial completo
    $history = get_post_meta($post_id, 'wpcargo_shipment_history', true);
    
    if (!is_array($history) || empty($history)) {
        return;
    }
    
    // Obtener el último estado
    $ultimo_estado = end($history);
    
    if (isset($ultimo_estado['status'])) {
        $estado_normalizado = merc_normalizar_texto($ultimo_estado['status']);
        
        // Si el estado contiene "EN BASE MERCOURIER", desasignar contenedor
        if (stripos($estado_normalizado, 'EN BASE MERCOURIER') !== false || 
            stripos($estado_normalizado, 'BASE MERCOURIER') !== false) {
            delete_post_meta($post_id, 'shipment_container');
        }
    }
}

/**
 * Actualizar conductor en todos los envíos cuando se cambia el conductor del contenedor
 */


// ===============================================
// FIRMAR: BLOQUEAR ESTADO EN ENTREGADO
// ===============================================

/**
 * Bloquear estado en ENTREGADO cuando se accede desde el botón FIRMAR
 */
add_action('wp_footer', 'merc_bloquear_estado_en_firmar');
function merc_bloquear_estado_en_firmar() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Detectar si se abrió el formulario POD
        function verificarYBloquearEstado() {
            const $form = $('#wpc_pod_signature-form');
            const $estadoSelect = $('select.status');
            
            if (!$form.length || !$estadoSelect.length) {
                return false;
            }
            
            // El formulario POD está abierto - bloquear en ENTREGADO
            console.log('🔒 Formulario POD detectado - Bloqueando estado en ENTREGADO');
            
            // Buscar la opción ENTREGADO
            let opcionEntregado = null;
            $estadoSelect.find('option').each(function() {
                const texto = $(this).text().toUpperCase().trim();
                if (texto === 'ENTREGADO' || texto.includes('ENTREGADO')) {
                    opcionEntregado = $(this).val();
                    return false;
                }
            });
            
            if (opcionEntregado) {
                // Seleccionar ENTREGADO
                $estadoSelect.val(opcionEntregado);
                
                // Deshabilitar el select para que no se pueda cambiar
                $estadoSelect.prop('disabled', true);
                
                // Agregar un campo hidden con el valor para que se envíe en el form
                if (!$('#hidden_status').length) {
                    $form.append('<input type="hidden" id="hidden_status" name="status" value="' + opcionEntregado + '">');
                }
                
                // Agregar indicador visual
                $estadoSelect.css({
                    'background-color': '#e8f5e9',
                    'border': '2px solid #4caf50',
                    'cursor': 'not-allowed'
                });
                
                // Agregar mensaje explicativo
                if (!$('.merc-estado-bloqueado-msg').length) {
                    $estadoSelect.parent().append(
                        '<p class="merc-estado-bloqueado-msg" style="color: #4caf50; font-size: 12px; margin-top: 5px;">' +
                        '✓ Este formulario es exclusivo para marcar pedidos como ENTREGADOS' +
                        '</p>'
                    );
                }
                
                console.log('✅ Estado bloqueado en ENTREGADO');
            }
            
            // Agregar botón de regresar
            agregarBotonRegresar();
            
            return true;
        }
        
        // Función para agregar botón de regresar
        function agregarBotonRegresar() {
            // Buscar si ya existe el botón
            if ($('.merc-btn-regresar').length > 0) {
                return;
            }
            
            // Buscar el contenedor del formulario POD
            const $formContainer = $('#pod-pop-up, #wpc_pod_signature-form').first();
            
            if (!$formContainer.length) {
                return;
            }
            
            // Crear botón de regresar
            const $btnRegresar = $('<div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">' +
                '<button type="button" class="merc-btn-regresar btn btn-secondary" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">' +
                    '← Regresar a la tabla' +
                '</button>' +
            '</div>');
            
            // Insertar al inicio del formulario
            $formContainer.prepend($btnRegresar);
            
            // Evento click para regresar
            $btnRegresar.find('.merc-btn-regresar').on('click', function() {
                console.log('🔙 Regresando a la tabla');
                // Recargar la página sin el parámetro sid
                const url = window.location.href.split('?')[0];
                window.location.href = url;
            });
            
            console.log('✅ Botón "Regresar" agregado');
        }
        
        // Intentar bloquear inmediatamente
        setTimeout(verificarYBloquearEstado, 500);
        
        // Observar cambios en el DOM para detectar cuando se abre el modal
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    $(mutation.addedNodes).each(function() {
                        if ($(this).find('#wpc_pod_signature-form').length > 0 || $(this).attr('id') === 'wpc_pod_signature-form') {
                            setTimeout(verificarYBloquearEstado, 300);
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    </script>
    <?php
}

// ===============================================
// TABLA: COLUMNA ESTADO EDITABLE CON SELECT
// ===============================================

/**
 * Convertir columna Estado en SELECT editable con confirmación
 */
add_action('wp_footer', 'merc_estado_editable_en_tabla');
function merc_estado_editable_en_tabla() {
    global $wpcargo;
    $estados = $wpcargo->status;
    
    if (empty($estados)) {
        return;
    }
    
    // Pre-generar todas las variables PHP necesarias
    $current_user = wp_get_current_user();
    $is_client = in_array('wpcargo_client', $current_user->roles) && 
                !in_array('wpcargo_driver', $current_user->roles) && 
                !current_user_can('manage_options');
    $is_driver = in_array('wpcargo_driver', $current_user->roles) && 
                !current_user_can('manage_options');
    
    // Pre-generar opciones de clientes para modal de producto
    $clientes_options_html = '<option value="">-- Selecciona un cliente --</option>';
    $clientes_form = get_users(array('role' => 'wpcargo_client'));
    foreach ($clientes_form as $cliente) {
        $nombre_completo = trim($cliente->first_name . ' ' . $cliente->last_name);
        $nombre_mostrar = !empty($nombre_completo) ? $nombre_completo : $cliente->display_name;
        $clientes_options_html .= '<option value="' . $cliente->ID . '">' . esc_html($nombre_mostrar) . '</option>';
    }
    
    // Pre-generar nonce y admin URL
    $merc_almacen_nonce = wp_create_nonce("merc_almacen");
    $admin_ajax_url = admin_url("admin-ajax.php");
    ?>
    <style>
    .merc-estado-select {
        padding: 5px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
        min-width: 150px;
        background: white;
        color: #333;
        font-weight: normal;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .merc-estado-select:hover {
        border-color: #2196F3;
        box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
    }
    .merc-estado-select:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    td.shipment-status {
        padding: 8px !important;
    }
    
    /* Resaltar filas por estado: REPROGRAMADO (naranja), NO CONTESTA (amarillo), ANULADO (rojo) */
    tr.merc-estado-reprogramado {
        background-color: #fff3e0 !important; /* naranja claro */
        border-left: 5px solid #ff9800 !important; /* naranja */
    }

    tr.merc-estado-reprogramado:hover {
        background-color: #ffe0b2 !important;
    }

    tr.merc-estado-reprogramado td {
        color: #bf360c !important;
        font-weight: 600 !important;
    }

    tr.merc-estado-reprogramado td:first-child::before {
        content: '📅 ';
        font-size: 16px;
        margin-right: 5px;
    }

    tr.merc-estado-no-contesta {
        background-color: #fff9c4 !important; /* amarillo claro */
        border-left: 5px solid #fdd835 !important; /* amarillo */
    }

    tr.merc-estado-no-contesta:hover {
        background-color: #fff59d !important;
    }

    tr.merc-estado-no-contesta td {
        color: #f57f17 !important;
        font-weight: 600 !important;
    }

    tr.merc-estado-no-contesta td:first-child::before {
        content: '⚠️ ';
        font-size: 16px;
        margin-right: 5px;
    }

    tr.merc-estado-anulado {
        background-color: #ffcdd2 !important; /* rojo claro */
        border-left: 5px solid #d32f2f !important; /* rojo */
    }

    tr.merc-estado-anulado:hover {
        background-color: #ef9a9a !important;
    }

    tr.merc-estado-anulado td {
        color: #b71c1c !important;
        font-weight: 600 !important;
    }

    tr.merc-estado-anulado td:first-child::before {
        content: '🗑️ ';
        font-size: 16px;
        margin-right: 5px;
    }
    
    /* Botón de Reprogramar para clientes */
    .merc-btn-reprogramar {
        background: #ff5722;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 5px rgba(255, 87, 34, 0.3);
    }
    
    .merc-btn-reprogramar:hover {
        background: #e64a19;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(255, 87, 34, 0.4);
    }
    
    .merc-btn-reprogramar:active {
        transform: translateY(0);
    }
    
    /* Modal de Reprogramación */
    .merc-modal-reprogram {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .merc-modal-reprogram-content {
        background: white;
        padding: 35px;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        max-width: 450px;
        width: 90%;
        text-align: center;
    }
    
    .merc-modal-reprogram-title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #ff5722;
    }
    
    .merc-modal-reprogram-info {
        background: #fff3e0;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        text-align: left;
    }
    
    .merc-modal-reprogram-info p {
        margin: 5px 0;
        font-size: 14px;
        color: #333;
    }
    
    .merc-modal-reprogram-label {
        display: block;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #555;
        text-align: left;
    }
    
    .merc-modal-reprogram-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 15px;
        margin-bottom: 25px;
        box-sizing: border-box;
        transition: border 0.3s;
    }
    
    .merc-modal-reprogram-input:focus {
        outline: none;
        border-color: #ff5722;
    }
    
    .merc-modal-reprogram-buttons {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    
    .merc-modal-reprogram-btn {
        padding: 12px 35px;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .merc-modal-reprogram-btn-confirmar {
        background: #4caf50;
        color: white;
    }
    
    .merc-modal-reprogram-btn-confirmar:hover {
        background: #45a049;
    }
    
    .merc-modal-reprogram-btn-cancelar {
        background: #f44336;
        color: white;
    }
    
    .merc-modal-reprogram-btn-cancelar:hover {
        background: #da190b;
    }
    
    /* Modal de confirmación personalizado */
    .merc-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .merc-modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
        text-align: center;
    }
    .merc-modal-title {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }
    .merc-modal-message {
        font-size: 16px;
        margin-bottom: 25px;
        color: #666;
        line-height: 1.5;
    }
    .merc-modal-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .merc-modal-btn {
        padding: 10px 30px;
        border: none;
        border-radius: 4px;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .merc-modal-btn-confirmar {
        background: #4caf50;
        color: white;
    }
    .merc-modal-btn-confirmar:hover {
        background: #45a049;
    }
    .merc-modal-btn-cancelar {
        background: #f44336;
        color: white;
    }
    .merc-modal-btn-cancelar:hover {
        background: #da190b;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        console.log('🔧 Inicializando columna Estado editable');
        
        // Variables PHP pre-generadas
        const estados = <?php echo json_encode($estados); ?>;
        const AJAX_URL = <?php echo json_encode($admin_ajax_url); ?>;
        const NONCE_ALMACEN = <?php echo json_encode($merc_almacen_nonce); ?>;
        const esCliente = <?php echo $is_client ? 'true' : 'false'; ?>;
        const esMotorizado = <?php echo $is_driver ? 'true' : 'false'; ?>;
        const clientesOptionsHtml = <?php echo json_encode($clientes_options_html); ?>;
        
        // Estados permitidos para motorizado según el estado actual
        const estadosMotorizadoInicial = ['RECOGIDO', 'NO RECOGIDO'];
        const estadosMotorizadoDespuesBase = ['EN RUTA', 'NO CONTESTA', 'NO RECIBIDO', 'ENTREGADO', 'REPROGRAMADO', 'ANULADO'];
        
        // Función para crear el modal de confirmación CON CAMPO DE OBSERVACIONES
        function mostrarModalConfirmacion(mensaje, onConfirmar, onCancelar) {
            const $modal = $('<div class="merc-modal-overlay">' +
                '<div class="merc-modal-content">' +
                    '<div class="merc-modal-title">⚠️ Confirmar cambio de estado</div>' +
                    '<div class="merc-modal-message">' + mensaje + '</div>' +
                    '<div class="merc-modal-observaciones" style="margin: 20px 0;">' +
                        '<label for="merc-observaciones-input" style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">📝 Observaciones (opcional):</label>' +
                        '<textarea id="merc-observaciones-input" class="merc-observaciones-textarea" placeholder="Ingrese observaciones adicionales sobre este cambio de estado..." style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; font-size: 14px; resize: vertical;"></textarea>' +
                    '</div>' +
                    '<div class="merc-modal-buttons">' +
                        '<button class="merc-modal-btn merc-modal-btn-confirmar">✓ Sí, cambiar estado</button>' +
                        '<button class="merc-modal-btn merc-modal-btn-cancelar">✗ Cancelar</button>' +
                    '</div>' +
                '</div>' +
            '</div>');
            
            $('body').append($modal);
            
            $modal.find('.merc-modal-btn-confirmar').on('click', function() {
                const observaciones = $modal.find('#merc-observaciones-input').val().trim();
                $modal.remove();
                if (onConfirmar) onConfirmar(observaciones); // Pasar las observaciones
            });
            
            $modal.find('.merc-modal-btn-cancelar').on('click', function() {
                $modal.remove();
                if (onCancelar) onCancelar();
            });
            
            // Cerrar con ESC
            $(document).on('keyup.mercModal', function(e) {
                if (e.key === 'Escape') {
                    $modal.remove();
                    if (onCancelar) onCancelar();
                    $(document).off('keyup.mercModal');
                }
            });
            
            // Enfocar el textarea para facilitar la escritura
            setTimeout(function() {
                $modal.find('#merc-observaciones-input').focus();
            }, 100);
        }
        
        // Helper: aplicar clase de fila según estado
        function setRowStateClass($row, estado) {
            if (!$row || !$row.length) return;
            const text = (estado || '').toUpperCase();
            $row.removeClass('merc-estado-reprogramado merc-estado-no-contesta merc-estado-anulado');
            if (text.includes('ANULADO') || text.includes('CANCEL')) {
                $row.addClass('merc-estado-anulado');
                return;
            }
            if (text.includes('NO CONTESTA')) {
                $row.addClass('merc-estado-no-contesta');
                return;
            }
            if (text.includes('REPROGRAMADO') || text.includes('RESCHEDULE')) {
                $row.addClass('merc-estado-reprogramado');
                return;
            }
        }

        // Función para resaltar filas según estado (aplicar a todos los usuarios)
        function resaltarFilasReprogramadas() {
            // Buscar en la tabla específica de envíos
            $('#shipment-list td.shipment-status, table.shipment-list td.shipment-status').each(function() {
                const $estadoCell = $(this);
                let estadoActual = $estadoCell.text().trim();
                
                // Si ya es un select, obtener el valor seleccionado
                const $select = $estadoCell.find('.merc-estado-select');
                if ($select.length > 0) {
                    estadoActual = $select.val() || $select.find('option:selected').text().trim();
                }
                
                const $row = $estadoCell.closest('tr');
                setRowStateClass($row, estadoActual);
            });
            
            console.log('🎨 Filas resaltadas por estado');
        }
        
        // Ejecutar resaltado inmediatamente (antes de cualquier otra cosa)
        setTimeout(resaltarFilasReprogramadas, 100);

        // Añadir columna "LISTO PARA SALIR" visual en tablas existentes para motorizados,
        // pero EXCLUIR la tabla específica `.merc-entregas-table` (panel motorizado).
        function addListoParaSalirColumn() {
            // Solo procesar para motorizados
            if (!esMotorizado) {
                return;
            }
            
            // SOLO procesar la tabla #shipment-list
            var $table = $('#shipment-list');
            if ($table.length === 0) {
                return;
            }

            // Agregar columna "LISTO PARA SALIR" al header si no existe
            var $thead = $table.find('thead');
            if ($thead.length > 0 && $thead.find('th:contains("LISTO PARA SALIR")').length === 0) {
                // Buscar el <th> de "ESTADO" para insertar DESPUÉS de él
                var $estadoTh = $thead.find('tr').first().find('th').filter(function() {
                    return $(this).text().toUpperCase().indexOf('ESTADO') !== -1;
                }).first();
                
                var $newTh = $('<th style="text-align:center;">LISTO PARA SALIR</th>');
                
                if ($estadoTh.length > 0) {
                    // Insertar después de la columna ESTADO
                    $newTh.insertAfter($estadoTh);
                } else {
                    // Si no encuentra ESTADO, agregarlo al final
                    $thead.find('tr').first().append($newTh);
                }
            }

            // Procesar cada fila de la tabla
            // Primero, encontrar el índice de la columna ESTADO en el header
            var estadoIndex = -1;
            $thead.find('tr').first().find('th').each(function(idx) {
                if ($(this).text().toUpperCase().indexOf('ESTADO') !== -1) {
                    estadoIndex = idx;
                    return false;
                }
            });
            
            $table.find('tbody tr').each(function(index) {
                var $row = $(this);
                
                // No agregar si ya está agregada
                if ($row.find('td.listo-para-salir-cell').length > 0) {
                    return;
                }
                
                // Obtener el shipment ID de la fila
                var shipmentId = null;
                var rowId = $row.attr('id');
                if (rowId && rowId.match(/shipment-(\d+)/)) {
                    shipmentId = rowId.match(/shipment-(\d+)/)[1];
                }
                if (!shipmentId) {
                    var $ds = $row.find('[data-shipment-id]').first();
                    if ($ds.length) shipmentId = $ds.data('shipment-id');
                }
                if (!shipmentId) {
                    var $a = $row.find('a[href]').first();
                    if ($a.length) {
                        var href = $a.attr('href');
                        var m = href.match(/post=(\d+)/) || href.match(/shipment-(\d+)/) || href.match(/(\d{4,})/);
                        if (m) shipmentId = m[1];
                    }
                }

                // Encontrar la celda de ESTADO usando el índice encontrado en el header
                var $estadoCell = estadoIndex >= 0 ? $row.find('td').eq(estadoIndex) : null;

                // Agregar celda para "LISTO PARA SALIR" después de ESTADO
                var $newCell = $('<td class="listo-para-salir-cell" style="text-align:center;">❓</td>');
                if ($estadoCell && $estadoCell.length > 0) {
                    $newCell.insertAfter($estadoCell);
                } else {
                    $row.append($newCell);
                }

                // Si tenemos un shipmentId, obtener el estado actual y anterior
                if (shipmentId) {
                    $.post(AJAX_URL, { action: 'merc_get_shipment_data', shipment_id: shipmentId }, function(resp) {
                        if (!resp || !resp.success) {
                            $newCell.text('❌').css('color', '#e74c3c');
                            return;
                        }
                        
                        var data = resp.data || {};
                        var estado_actual = (data.estado_actual || '').toString().toUpperCase().trim();
                        var estado_prev = (data.estado_prev || '').toString().trim();
                        
                        // Si es "LISTO PARA SALIR", mostrar checkmark en la columna y reemplazar estado con anterior
                        var esListoParaSalir = estado_actual.indexOf('LISTO') !== -1 && estado_actual.indexOf('SALIR') !== -1;
                        
                        if (esListoParaSalir) {
                            // Mostrar ✅ en la columna LISTO PARA SALIR
                            $newCell.text('✅').css({'color': '#27ae60', 'font-weight': 'bold'});
                            
                            // Si hay estado anterior, reemplazar en la columna "ESTADO"
                            if (estado_prev && estado_prev.length > 0) {
                                var $estadoCells = $row.find('td').filter(function() {
                                    var text = $(this).text().toUpperCase().trim();
                                    return text.indexOf('LISTO') !== -1 && text.indexOf('SALIR') !== -1;
                                }).not('.listo-para-salir-cell');
                                
                                $estadoCells.each(function() {
                                    var $cell = $(this);
                                    var $select = $cell.find('select');
                                    if ($select.length > 0) {
                                        // Primero intentar encontrar la opción en el SELECT
                                        var encontrado = false;
                                        $select.find('option').each(function() {
                                            var optionText = $(this).text().toUpperCase().trim();
                                            var searchText = estado_prev.toUpperCase().trim();
                                            
                                            if (optionText === searchText) {
                                                $select.val($(this).val());
                                                encontrado = true;
                                                return false;
                                            }
                                        });
                                        
                                        // Si NO encontró la opción, no reemplazar el SELECT: agregar la opción previa
                                        if (!encontrado) {
                                            try {
                                                // Agregar como opción al inicio y seleccionarla para mantener la fila editable
                                                var $opPrev = $('<option>').val(estado_prev).text(estado_prev);
                                                $select.prepend($opPrev);
                                                $select.val(estado_prev);
                                                console.log('ℹ️ Estado anterior agregado al SELECT en vez de reemplazar la celda:', estado_prev);
                                            } catch (e) {
                                                // Si algo falla, como fallback, reemplazar con texto (comportamiento antiguo)
                                                console.warn('⚠️ No se pudo agregar opción previa al SELECT, usando fallback texto:', e);
                                                $cell.html(estado_prev);
                                            }
                                        }
                                    } else {
                                        $cell.text(estado_prev);
                                    }
                                });
                            }
                        } else {
                            $newCell.text('❌').css('color', '#e74c3c');
                        }
                    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                        $newCell.text('💥').css('color', '#e67e22');
                    });
                } else {
                    $newCell.text('❌').css('color', '#e74c3c');
                }
            });
        }

        // Ejecutar al cargar
        addListoParaSalirColumn();

        // Ejecutar al cargar

        // Reemplazar etiquetas temporales en inglés por español (p. ej. "ES SHIPMENT STATUS")
        function reemplazarEtiquetaShipmentStatus() {
            // Buscar nodos de texto en la página que contengan la cadena problemática
            $('*:not(script):not(style)').contents().filter(function() {
                return this.nodeType === 3 && /ES\s*SHIPMENT\s*STATUS/gi.test(this.nodeValue);
            }).each(function() {
                this.nodeValue = this.nodeValue.replace(/ES\s*SHIPMENT\s*STATUS/gi, 'ESTADO DEL ENVÍO');
            });

            // También reemplazar en elementos que contengan la cadena como HTML
            // Establecer variable CSS global --wpcargo
            try { document.documentElement.style.setProperty('--wpcargo', '#8e0205'); } catch(e) {}

            $('[id], [class], div, span, p, th, td').each(function() {
                var $el = $(this);
                if ($el.children().length === 0) {
                    var txt = $el.text();
                    if (/ES\s*SHIPMENT\s*STATUS/gi.test(txt)) {
                        $el.text(txt.replace(/ES\s*SHIPMENT\s*STATUS/gi, 'ESTADO DEL ENVÍO'));
                        $el.css({ 'background-color': '#8e0205', 'color': '#ffffff', 'padding': '10px', 'border-radius': '3px' });
                        var $wrap = $el.closest('.card, .pod-details, .container, .row, .text-center');
                        if ($wrap.length) {
                            $wrap.css({ 'background-color': '#8e0205', 'color': '#ffffff' });
                        }
                    }
                }
            });
        }
        setTimeout(reemplazarEtiquetaShipmentStatus, 300);
        
        // Función para convertir la celda de estado en SELECT
        function convertirEstadoASelect() {
            if (esCliente) {
                console.log('👤 Usuario es cliente - estados solo en modo lectura');
                return;
            }
            
            // Verificar que la tabla existe
            if ($('#shipment-list, table.shipment-list').length === 0) {
                console.log('⏭️ Tabla shipment-list no encontrada');
                return;
            }
            
            let contadorConvertidos = 0;
            
            $('#shipment-list td.shipment-status, table.shipment-list td.shipment-status').each(function() {
                const $estadoCell = $(this);
                
                if ($estadoCell.find('.merc-estado-select').length > 0) {
                    return;
                }
                
                const estadoActual = $estadoCell.text().trim();
                const $row = $estadoCell.closest('tr');
                
                if (estadoActual.length < 2) {
                    return;
                }
                
                if (estadoActual.toUpperCase().includes('ENTREGADO') || estadoActual.toUpperCase().includes('DELIVERED')) {
                    console.log('⏭️ Saltando estado ENTREGADO:', estadoActual);
                    return;
                }
                
                const rowId = $row.attr('id');
                let shipmentId = null;
                if (rowId) {
                    const match = rowId.match(/shipment-(\d+)/);
                    if (match) {
                        shipmentId = match[1];
                    }
                }
                
                let shipmentNumber = $row.find('td').first().text().trim();
                if (!shipmentNumber) {
                    const $link = $row.find('a').first();
                    if ($link.length > 0) {
                        shipmentNumber = $link.text().trim();
                    }
                }
                
                if (!shipmentId) {
                    console.warn('⚠️ No se pudo obtener ID para:', shipmentNumber);
                    return;
                }
                
                console.log('📝 Convirtiendo:', shipmentNumber, 'ID:', shipmentId, 'Estado:', estadoActual);
                
                let estadosFiltrados = estados;
                
                if (esMotorizado) {
                    const estadoActualUpper = estadoActual.toUpperCase();
                    
                    const estadosAvanzados = ['EN BASE MERCOURIER', 'RECEPCIONADO', 'LISTO PARA SALIR', 'NO CONTESTA', 'EN RUTA', 'NO RECIBIDO', 'ENTREGADO', 'REPROGRAMADO', 'ANULADO'];
                    const esEstadoAvanzado = estadosAvanzados.some(function(estado) {
                        return estadoActualUpper.includes(estado);
                    });
                    
                    if (esEstadoAvanzado) {
                        estadosFiltrados = estados.filter(function(estado) {
                            const estadoUpper = estado.toUpperCase();
                            return estadosMotorizadoDespuesBase.some(function(permitido) {
                                return estadoUpper.includes(permitido) || permitido.includes(estadoUpper);
                            });
                        });
                        console.log('🚗 Motorizado - Estado avanzado detectado (' + estadoActual + ') - Mostrando estados posteriores');
                    } else {
                        estadosFiltrados = estados.filter(function(estado) {
                            const estadoUpper = estado.toUpperCase();
                            return estadosMotorizadoInicial.some(function(permitido) {
                                return estadoUpper.includes(permitido) || permitido.includes(estadoUpper);
                            });
                        });
                        console.log('🚗 Motorizado - Estado inicial - Solo RECOGIDO y NO CONTESTA');
                    }

                    estadosFiltrados = estadosFiltrados.filter(function(opt) {
                        return opt.toUpperCase().trim() !== 'LISTO PARA SALIR';
                    });
                }
                
                let $select = $('<select class="merc-estado-select" data-shipment-id="' + shipmentId + '" data-shipment-number="' + shipmentNumber + '" style="display:block!important;width:100%;padding:5px 10px;border:1px solid #ddd;border-radius:4px;"></select>');
                
                let tieneSeleccionado = false;
                let estadoActualAgregado = false;
                
                estadosFiltrados.forEach(function(estado) {
                    const selected = estado.toUpperCase() === estadoActual.toUpperCase() ? 'selected' : '';
                    if (selected) {
                        tieneSeleccionado = true;
                        estadoActualAgregado = true;
                    }
                    $select.append('<option value="' + estado + '" ' + selected + '>' + estado + '</option>');
                });
                
                if (!estadoActualAgregado && estadoActual.length > 0) {
                    console.log('ℹ️ Estado actual no está en filtro - Agregándolo como opción solo lectura:', estadoActual);
                    $select.prepend('<option value="' + estadoActual + '" selected disabled>' + estadoActual + '</option>');
                    tieneSeleccionado = true;
                }
                
                if (!tieneSeleccionado) {
                    console.log('⚠️ No se encontró coincidencia exacta para:', estadoActual, '- Buscando parcial');
                    $select.find('option').each(function() {
                        const opcionTexto = $(this).text().toUpperCase();
                        if (estadoActual.toUpperCase().includes(opcionTexto) || opcionTexto.includes(estadoActual.toUpperCase())) {
                            $(this).prop('selected', true);
                            console.log('✓ Seleccionado por coincidencia parcial:', $(this).text());
                            return false;
                        }
                    });
                }
                
                $estadoCell.removeClass();
                $estadoCell.addClass('shipment-status');
                
                $estadoCell.html($select);
                contadorConvertidos++;
                
                // IMPORTANTE: Re-aplicar clase de estado a la fila después de convertir
                setRowStateClass($row, estadoActual);
                
                // Agregar evento change CON OBSERVACIONES
                $select.on('change', function() {
                    const $this = $(this);
                    const nuevoEstado = $this.val();
                    const estadoAnterior = estadoActual;
                    const id = $this.data('shipment-id');
                    const numero = $this.data('shipment-number');
                    
                    if (nuevoEstado.toUpperCase().includes('ENTREGADO') || nuevoEstado.toUpperCase() === 'DELIVERED') {
                        console.log('🔀 Estado ENTREGADO seleccionado - Buscando botón FIRMAR');
                        
                        const $row = $this.closest('tr');
                        console.log('Fila encontrada:', $row.attr('id'));
                        
                        let $btnFirmar = $row.find('button.wpcod_pod_signature, button[data-target="#wpc_pod_signature-modal"]').first();
                        
                        if ($btnFirmar.length === 0) {
                            console.log('Buscando por texto...');
                            $row.find('button').each(function() {
                                const texto = $(this).text().trim().toUpperCase();
                                if (texto.includes('FIRMAR') || texto.includes('SIGN')) {
                                    $btnFirmar = $(this);
                                    return false;
                                }
                            });
                        }
                        
                        if ($btnFirmar.length === 0) {
                            console.error('❌ No se encontró el botón FIRMAR en la fila');
                            alert('❌ No se encontró el botón FIRMAR para este pedido');
                            $this.val(estadoAnterior);
                            return;
                        }
                        
                        console.log('✓ Botón FIRMAR encontrado:', $btnFirmar.attr('class'));
                        
                        const $modalInfo = $('<div class="merc-modal-overlay" style="z-index: 999998;">' +
                            '<div class="merc-modal-content">' +
                                '<div class="merc-modal-message" style="font-size: 16px; padding: 20px;">' +
                                    '📝 Abriendo formulario de firma...' +
                                '</div>' +
                            '</div>' +
                        '</div>');
                        
                        $('body').append($modalInfo);
                        
                        setTimeout(function() {
                            $modalInfo.remove();
                            
                            console.log('👆 Ejecutando click...');
                            $btnFirmar[0].click();
                            
                            console.log('✓ Click ejecutado');
                        }, 1000);
                        
                        return;
                    }
                    
                    const esReprogramado = nuevoEstado.toUpperCase().includes('REPROGRAMADO') || nuevoEstado.toUpperCase().includes('RESCHEDULE');
                    
                    // Mostrar modal de confirmación CON CAMPO DE OBSERVACIONES
                    mostrarModalConfirmacion(
                        '<strong>Pedido:</strong> ' + numero + '<br><br>' +
                        '<strong>Estado actual:</strong> ' + estadoAnterior + '<br>' +
                        '<strong>Nuevo estado:</strong> ' + nuevoEstado + '<br><br>' +
                        '¿Está seguro de realizar este cambio?',
                        function(observaciones) {
                            // CONFIRMAR - Actualizar estado CON OBSERVACIONES
                            console.log('✅ Confirmado - Actualizando estado del pedido #' + numero);
                            console.log('📝 Observaciones:', observaciones || 'Sin observaciones');
                            
                            $this.prop('disabled', true);
                            
                            // AJAX para actualizar el estado
                            $.ajax({
                                type: 'POST',
                                url: AJAX_URL,
                                data: {
                                    action: 'merc_actualizar_estado_rapido',
                                    shipment_id: id,
                                    nuevo_estado: nuevoEstado,
                                    observaciones: observaciones, // ENVIAR OBSERVACIONES
                                    nonce: '<?php echo wp_create_nonce('merc_actualizar_estado'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        console.log('✅ Estado actualizado correctamente');
                                        
                                        // Aplicar clase de estado a la fila
                                        const $row = $this.closest('tr');
                                        setRowStateClass($row, nuevoEstado);
                                        
                                        if (esReprogramado) {
                                            console.log('🔴 Fila marcada como REPROGRAMADO');
                                            
                                            $.ajax({
                                                type: 'POST',
                                                url: AJAX_URL,
                                                data: {
                                                    action: 'merc_notificar_reprogramacion',
                                                    shipment_id: id,
                                                    shipment_number: numero,
                                                    nonce: '<?php echo wp_create_nonce('merc_notificar_reprog'); ?>'
                                                },
                                                success: function(notifResp) {
                                                    if (notifResp.success) {
                                                        console.log('📧 Notificación enviada al cliente:', notifResp.data);
                                                    } else {
                                                        console.warn('⚠️ Error al enviar notificación:', notifResp.data);
                                                    }
                                                }
                                            });
                                        }
                                        
                                        const colorNotif = esReprogramado ? '#f44336' : '#4caf50';
                                        const textoNotif = esReprogramado ? '✓ Estado actualizado a: <strong>' + nuevoEstado + '</strong><br><small>Se ha notificado al cliente</small>' : '✓ Estado actualizado a: <strong>' + nuevoEstado + '</strong>';
                                        
                                        const $notif = $('<div style="position: fixed; top: 20px; right: 20px; background: ' + colorNotif + '; color: white; padding: 15px 25px; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 999999; font-size: 14px;">' +
                                            textoNotif +
                                        '</div>');
                                        $('body').append($notif);
                                        setTimeout(function() {
                                            $notif.fadeOut(300, function() { $(this).remove(); });
                                        }, 3000);
                                        
                                        setTimeout(function() {
                                            location.reload();
                                        }, 2000);
                                    } else {
                                        alert('❌ Error: ' + response.data);
                                        $this.val(estadoAnterior);
                                    }
                                    $this.prop('disabled', false);
                                },
                                error: function() {
                                    alert('❌ Error de conexión. Por favor intente de nuevo.');
                                    $this.val(estadoAnterior);
                                    $this.prop('disabled', false);
                                }
                            });
                        },
                        function() {
                            // CANCELAR - Revertir el select
                            console.log('❌ Cancelado - Revirtiendo estado');
                            $this.val(estadoAnterior);
                        }
                    );
                });
            });
            
            if (contadorConvertidos > 0) {
                console.log('✅ Columna Estado convertida a SELECT -', contadorConvertidos, 'pedidos');
            } else {
                console.log('ℹ️ No se encontraron estados para convertir a SELECT');
            }
        }
        
        setTimeout(convertirEstadoASelect, 1000);
        setTimeout(agregarBotonesReprogramar, 1500);
        
        // Ejecutar periódicamente en orden correcto:
        // 1. Resaltar filas por estado
        // 2. Convertir estados a SELECT
        // 3. Agregar botones de acciones
        setInterval(function() {
            const $tabla = $('#shipment-list, table.shipment-list');
            if ($tabla.length === 0) return;
            
            const $filas = $tabla.find('tbody tr');
            if ($filas.length === 0) return;
            
            resaltarFilasReprogramadas(); // Primero aplicar clases
            
            // Convertir a SELECT solo si aún no se han convertido
            const $selects = $tabla.find('.merc-estado-select');
            const $estadoCells = $tabla.find('td.shipment-status');
            if ($estadoCells.length > 0 && $selects.length === 0) {
                console.log('🔄 Convirtiendo estados a SELECT...');
                convertirEstadoASelect(); // Luego convertir a SELECT
            }
            
            agregarBotonesReprogramar(); // Finalmente agregar botones
        }, 2000);
        // Función para agregar botones de reprogramar en filas REPROGRAMADO
        function agregarBotonesReprogramar() {
            // Mostrar botones según rol: clientes ven Reprogramar (+ Anular manejado por admin),
            // administradores verán botón "Crear Producto" junto a Reprogramar cuando el envío
            // tenga estado ANULADO y tipo normal/express (no mostrar en full fitment).
            const esClienteLocal = <?php 
                $current_user = wp_get_current_user();
                $is_client = in_array('wpcargo_client', $current_user->roles) && 
                            !in_array('wpcargo_driver', $current_user->roles) && 
                            !current_user_can('manage_options');
                echo $is_client ? 'true' : 'false'; 
            ?>;
            const esAdmin = <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>;

            // Añadir botón de reprogramar a filas reprogramadas (clientes)
            if (esClienteLocal) {
                $('tr.merc-estado-reprogramado').each(function() {
                    const $row = $(this);
                    if ($row.find('.merc-btn-reprogramar').length > 0) return;
                    const rowId = $row.attr('id'); if (!rowId) return;
                    const match = rowId.match(/shipment-(\d+)/); if (!match) return;
                    const shipmentId = match[1];
                    const shipmentNumber = $row.find('td').first().text().trim();
                    const $celdaAcciones = $row.find('td.merc-acciones-cell');
                    
                    // Crear contenedor de botones
                    const $contenedorBotones = $('<div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;"></div>');
                    
                    // Botón Reprogramar
                    const $btnReprogramar = $('<button class="merc-btn-reprogramar" data-shipment-id="' + shipmentId + '" data-shipment-number="' + shipmentNumber + '" style="background: #ff5722; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; white-space: nowrap;">📅 Reprogramar</button>');
                    $btnReprogramar.on('click', function(e){ e.preventDefault(); mostrarModalReprogramacion(shipmentId, shipmentNumber); });
                    
                    // Botón Anular
                    const $btnAnular = $('<button class="merc-btn-anular" data-shipment-id="' + shipmentId + '" data-shipment-number="' + shipmentNumber + '" style="background: #f44336; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; white-space: nowrap;">❌ Anular</button>');
                    $btnAnular.on('click', function(e){ e.preventDefault(); mostrarModalAnulacion(shipmentId, shipmentNumber); });
                    
                    $contenedorBotones.append($btnReprogramar).append($btnAnular);
                    $celdaAcciones.append($contenedorBotones);
                });
            }

            // Para administradores: añadir botón "Crear Producto" en filas ANULADO
            if (esAdmin) {
                // Esperar un momento para que las clases de estado se apliquen
                setTimeout(function() {
                    $('tr.merc-estado-anulado').each(function() {
                        const $row = $(this);
                        // evitar agregar múltiples botones
                        if ($row.find('.merc-btn-crear-producto').length > 0) return;
                        
                        const rowId = $row.attr('id'); 
                        if (!rowId) return;
                        
                        const match = rowId.match(/shipment-(\d+)/); 
                        if (!match) return;
                        
                        const shipmentId = match[1];
                        const shipmentNumber = $row.find('td').first().text().trim();
                        const $celdaAcciones = $row.find('td.merc-acciones-cell');
                        
                        if ($celdaAcciones.length === 0) return;

                        // Obtener tipo de envío desde el data attribute de la columna
                        const $tipoCell = $row.find('td[data-tipo-envio]');
                        const tipoEnvio = $tipoCell.length > 0 ? $tipoCell.attr('data-tipo-envio').toLowerCase() : '';
                        
                        // Mostrar solo para tipo normal o express (no full fitment)
                        if (tipoEnvio.indexOf('full') !== -1 || tipoEnvio.indexOf('fit') !== -1) return;

                        // Consultar datos del cliente para pre-cargar en el modal
                        $.post(AJAX_URL, { 
                            action: 'merc_get_shipment_data', 
                            shipment_id: shipmentId 
                        }, function(resp) {
                            if (!resp || !resp.success) {
                                // Si falla el AJAX, crear el botón de todas formas
                                crearBotonProducto($row, $celdaAcciones, shipmentId, shipmentNumber, '', '');
                                return;
                            }
                            
                            const data = resp.data || {};
                            const clienteId = (data && data.customer_id) ? data.customer_id : '';
                            
                            crearBotonProducto($row, $celdaAcciones, shipmentId, shipmentNumber, clienteId, data);
                        }, 'json').fail(function() {
                            // Si falla completamente, crear el botón de todas formas
                            crearBotonProducto($row, $celdaAcciones, shipmentId, shipmentNumber, '', '');
                        });
                    });
                }, 500);
                
                // Función auxiliar para crear el botón
                function crearBotonProducto($row, $celdaAcciones, shipmentId, shipmentNumber, clienteId, data) {
                    // Evitar duplicados
                    if ($row.find('.merc-btn-crear-producto').length > 0) return;
                    
                    const $btnCrear = $('<button class="merc-btn-crear-producto" data-shipment-id="' + shipmentId + '" data-shipment-number="' + shipmentNumber + '" style="margin-left:8px;background:#1976d2;color:#fff;padding:6px 10px;border-radius:6px;border:none;cursor:pointer;white-space:nowrap;">' +
                        '🛒 Crear Producto' +
                    '</button>');
                    
                    $btnCrear.on('click', function(e) {
                        e.preventDefault();
                        const id = $(this).data('shipment-id');
                        mostrarModalProductoDesdeEnvio(id, clienteId, $row.find('td').first().text().trim());
                    });
                    
                    $celdaAcciones.append($btnCrear);
                }
            }
        }
        
        // ========== FUNCIÓN: MOSTRAR MODAL DE PRODUCTO DESDE ENVÍO ==========
        function mostrarModalProductoDesdeEnvio(shipmentId, clienteId, shipmentTitle) {
            // Opciones de clientes pre-generadas
            const clientesOptions = clientesOptionsHtml;
            
            // Crear modal usando el mismo diseño del shortcode [merc_almacen_productos]
            const modalHTML = `
<div id="modal-producto-envio" class="modal" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999999; align-items: center; justify-content: center;">
    <div class="modal-backdrop" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6);"></div>
    <div class="modal-box" style="position: relative; background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #ecf0f1; flex-shrink: 0;">
            <h3 style="margin: 0; font-size: 20px; color: #2c3e50;">📦 Crear Producto en Almacén</h3>
            <button class="modal-close-envio" style="background: none; border: none; font-size: 24px; color: #7f8c8d; cursor: pointer; padding: 0; line-height: 1; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>
        <form id="form-producto-envio" style="overflow-y: auto; flex: 1;">
            <input type="hidden" id="prod-shipment-id" value="${shipmentId}">
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Nombre *</label>
                <input type="text" id="prod-nombre-envio" required placeholder="Nombre del producto" value="${shipmentTitle}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
            </div>
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Código de Barras <small>(opcional)</small></label>
                <input type="text" id="prod-codigo-barras-envio" placeholder="Código o SKU" value="SHIP-${shipmentId}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
                <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">📦 Puedes dejar este campo vacío si el producto no tiene código de barras</small>
            </div>
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Cliente Asignado *</label>
                <select id="prod-cliente-asignado-envio" required style="width: 100%; padding: 10px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; display: block;">
                    ${clientesOptions}
                </select>
                <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">👤 Este campo será pre-seleccionado automáticamente</small>
            </div>
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Cantidad *</label>
                <input type="number" id="prod-cantidad-envio" min="1" required placeholder="0" value="1" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
            </div>
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Peso (kg) <small>(opcional)</small></label>
                <input type="number" id="prod-peso-envio" min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
                <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">⚖️ Peso del producto en kilogramos</small>
            </div>
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Tipo de Medida <small>(opcional)</small></label>
                <select id="prod-tipo-medida-envio" style="width: 100%; padding: 10px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
                    <option value="">-- Seleccionar --</option>
                    <option value="talla">Talla</option>
                    <option value="volumen">Volumen</option>
                    <option value="dimensiones">Dimensiones</option>
                    <option value="unidades">Unidades</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Valor de Medida <small>(opcional)</small></label>
                <input type="text" id="prod-valor-medida-envio" placeholder="Ej: S, M, L, XL o 100ml" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
            </div>
            <div class="form-group" style="padding: 12px 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Dimensiones (cm) <small>(opcional)</small></label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="number" id="prod-largo-envio" min="0" step="0.1" placeholder="Largo" style="flex: 1; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
                    <span style="color: #7f8c8d;">×</span>
                    <input type="number" id="prod-ancho-envio" min="0" step="0.1" placeholder="Ancho" style="flex: 1; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
                    <span style="color: #7f8c8d;">×</span>
                    <input type="number" id="prod-alto-envio" min="0" step="0.1" placeholder="Alto" style="flex: 1; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px;">
                </div>
                <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">📦 Dimensiones: Largo × Ancho × Alto en centímetros</small>
            </div>
            <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; padding: 15px 20px; border-top: 1px solid #ecf0f1; background: #f8f9fa; flex-shrink: 0;">
                <button type="button" class="btn-secondary modal-close-envio" style="background: #6c757d; color: white; box-shadow: 0 2px 6px rgba(108, 117, 125, 0.3); padding: 10px 24px; font-size: 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="submit" class="btn-primary" style="background: #3498db; color: white; box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3); padding: 10px 24px; font-size: 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Guardar</button>
            </div>
        </form>
    </div>
</div>`;

            $('body').append(modalHTML);
            const $modal = $('#modal-producto-envio');
            
            // Pre-seleccionar cliente si existe
            if (clienteId) {
                $modal.find('#prod-cliente-asignado-envio').val(clienteId);
            }
            
            // Cerrar modal
            $modal.find('.modal-close-envio, .modal-backdrop').on('click', function() {
                $modal.remove();
            });
            
            // Enviar formulario
            $modal.find('#form-producto-envio').on('submit', function(e) {
                e.preventDefault();
                
                const datos = {
                    action: 'merc_guardar_producto',
                    nonce: NONCE_ALMACEN,
                    id: 0, // nuevo producto
                    nombre: $('#prod-nombre-envio').val(),
                    codigo_barras: $('#prod-codigo-barras-envio').val(),
                    cliente_asignado: $('#prod-cliente-asignado-envio').val(),
                    cantidad: parseInt($('#prod-cantidad-envio').val()) || 0,
                    peso: parseFloat($('#prod-peso-envio').val()) || 0,
                    tipo_medida: $('#prod-tipo-medida-envio').val(),
                    valor_medida: $('#prod-valor-medida-envio').val(),
                    largo: parseFloat($('#prod-largo-envio').val()) || 0,
                    ancho: parseFloat($('#prod-ancho-envio').val()) || 0,
                    alto: parseFloat($('#prod-alto-envio').val()) || 0,
                    shipment_id: shipmentId // vincular con el envío
                };
                
                $.post(AJAX_URL, datos, function(r) {
                    if (r.success) {
                        alert('✅ Producto creado exitosamente en el almacén');
                        $modal.remove();
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (r.data || 'Error desconocido'));
                    }
                }).fail(function() {
                    alert('❌ Error de red al crear el producto');
                });
            });
        }
        
        // Función para mostrar modal de reprogramación
		function mostrarModalReprogramacion(shipmentId, shipmentNumber) {
			// ========== FUNCIONES AUXILIARES PARA FORMATEO DE FECHAS ==========

			// Convertir fecha de YYYY-MM-DD a DD/MM/YYYY
			function formatearFechaAMostrar(fechaISO) {
				if (!fechaISO) return '';
				const partes = fechaISO.split('-');
				if (partes.length !== 3) return fechaISO;
				return partes[2] + '/' + partes[1] + '/' + partes[0];
			}

			// Convertir fecha de DD/MM/YYYY a YYYY-MM-DD
			function formatearFechaAISO(fechaDDMMYYYY) {
				if (!fechaDDMMYYYY) return '';
				const partes = fechaDDMMYYYY.split('/');
				if (partes.length !== 3) return fechaDDMMYYYY;
				return partes[2] + '-' + partes[1] + '-' + partes[0];
			}

			// ========== FIN FUNCIONES AUXILIARES ==========

			// Obtener fecha actual del envío (ya viene en DD/MM/YYYY desde la BD)
			const $row = $('#shipment-' + shipmentId);
			const fechaActualMostrar = $row.find('td').eq(4).text().trim(); // Columna "Fecha de Envío"

			// Fecha mínima: mañana
			const tomorrow = new Date();
			tomorrow.setDate(tomorrow.getDate() + 1);
			const minDate = tomorrow.toISOString().split('T')[0]; // YYYY-MM-DD para el input
			const minDateMostrar = formatearFechaAMostrar(minDate); // DD/MM/YYYY para mostrar

			const $modal = $('<div class="merc-modal-reprogram">' +
				'<div class="merc-modal-reprogram-content">' +
					'<div class="merc-modal-reprogram-title">📅 Reprogramar Envío</div>' +
					'<div class="merc-modal-reprogram-info">' +
						'<p><strong>📦 Número de envío:</strong> ' + shipmentNumber + '</p>' +
						'<p><strong>📆 Fecha actual:</strong> ' + fechaActualMostrar + '</p>' +
						'<p style="font-size: 12px; color: #666; margin-top: 8px;">📌 Fecha mínima disponible: ' + minDateMostrar + '</p>' +
					'</div>' +
					'<label class="merc-modal-reprogram-label">Seleccione la nueva fecha de envío:</label>' +
					'<input type="date" class="merc-modal-reprogram-input" id="merc-nueva-fecha" min="' + minDate + '" required>' +
					'<div class="merc-modal-reprogram-buttons">' +
						'<button class="merc-modal-reprogram-btn merc-modal-reprogram-btn-confirmar">✓ Confirmar</button>' +
						'<button class="merc-modal-reprogram-btn merc-modal-reprogram-btn-cancelar">✗ Cancelar</button>' +
					'</div>' +
				'</div>' +
			'</div>');

			$('body').append($modal);

			// Evento confirmar
			$modal.find('.merc-modal-reprogram-btn-confirmar').on('click', function() {
				const nuevaFechaISO = $('#merc-nueva-fecha').val(); // El input devuelve YYYY-MM-DD

				if (!nuevaFechaISO) {
					alert('Por favor seleccione una fecha');
					return;
				}

				// Convertir a formato DD/MM/YYYY para mostrar Y para enviar a la BD
				const nuevaFechaDDMMYYYY = formatearFechaAMostrar(nuevaFechaISO);

				// Confirmar cambio con fecha en formato DD/MM/YYYY
				if (!confirm('¿Confirmar reprogramación para el ' + nuevaFechaDDMMYYYY + '?')) {
					return;
				}

				$(this).prop('disabled', true).text('Guardando...');

				// Enviar solicitud AJAX (AHORA enviamos en formato DD/MM/YYYY)
				$.ajax({
					type: 'POST',
					url: AJAX_URL,
					data: {
						action: 'merc_reprogramar_envio',
						shipment_id: shipmentId,
						nueva_fecha: nuevaFechaDDMMYYYY, // ⭐ ENVIAMOS EN FORMATO DD/MM/YYYY
						nonce: '<?php echo wp_create_nonce('merc_reprogramar'); ?>'
					},
					success: function(response) {
						if (response.success) {
							$modal.remove();

							// Disparar evento personalizado para actualizar la tabla
							$(document).trigger('merc-fecha-reprogramada', [shipmentId]);

							// Notificación de éxito con fecha en formato DD/MM/YYYY
							const $notif = $('<div style="position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 15px 25px; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 999999; font-size: 14px;">' +
								'✓ Fecha reprogramada exitosamente<br><small>Nueva fecha: ' + nuevaFechaDDMMYYYY + '</small>' +
							'</div>');
							$('body').append($notif);

							setTimeout(function() {
								$notif.fadeOut(300, function() { $(this).remove(); });
							}, 3000);

							// Recargar después de 2 segundos forzando actualización del caché
							setTimeout(function() {
								window.location.href = window.location.href.split('?')[0] + '?t=' + new Date().getTime();
							}, 2000);
						} else {
							alert('❌ Error: ' + (response.data || 'No se pudo reprogramar'));
							$modal.find('.merc-modal-reprogram-btn-confirmar').prop('disabled', false).text('✓ Confirmar');
						}
					},
					error: function() {
						alert('❌ Error de conexión');
						$modal.find('.merc-modal-reprogram-btn-confirmar').prop('disabled', false).text('✓ Confirmar');
					}
				});
			});

			// Evento cancelar
			$modal.find('.merc-modal-reprogram-btn-cancelar').on('click', function() {
				$modal.remove();
			});

			// Cerrar con ESC
			$(document).on('keyup.mercModalReprogram', function(e) {
				if (e.key === 'Escape') {
					$modal.remove();
					$(document).off('keyup.mercModalReprogram');
				}
			});
		}
		
		// ========== MODAL DE ANULACIÓN ==========
		function mostrarModalAnulacion(shipmentId, shipmentNumber) {
			const $modal = $('<div class="merc-modal-anular">' +
				'<div class="merc-modal-anular-content">' +
					'<div class="merc-modal-anular-title">❌ Anular Envío</div>' +
					'<div class="merc-modal-anular-info">' +
						'<p><strong>📦 Número de envío:</strong> ' + shipmentNumber + '</p>' +
						'<p style="color: #d32f2f; font-weight: bold;">⚠️ ¿Está seguro que desea anular este envío?</p>' +
						'<p style="font-size: 13px; color: #666;">Esta acción no se puede deshacer.</p>' +
					'</div>' +
					'<label class="merc-modal-anular-label">Motivo de anulación (opcional):</label>' +
					'<textarea class="merc-modal-anular-textarea" id="merc-motivo-anulacion" rows="3" placeholder="Describe el motivo..."></textarea>' +
					'<div class="merc-modal-anular-buttons">' +
						'<button class="merc-modal-anular-btn merc-modal-anular-btn-confirmar">✓ Confirmar Anulación</button>' +
						'<button class="merc-modal-anular-btn merc-modal-anular-btn-cancelar">✗ Cancelar</button>' +
					'</div>' +
				'</div>' +
			'</div>');
			
			// Estilos del modal de anulación (similar al de reprogramación)
			$modal.find('.merc-modal-anular').css({
				position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
				background: 'rgba(0,0,0,0.6)', display: 'flex', alignItems: 'center',
				justifyContent: 'center', zIndex: 999999
			});
			$modal.find('.merc-modal-anular-content').css({
				background: '#fff', borderRadius: '12px', padding: '25px',
				maxWidth: '500px', width: '90%', boxShadow: '0 10px 40px rgba(0,0,0,0.3)'
			});
			$modal.find('.merc-modal-anular-title').css({
				fontSize: '20px', fontWeight: 'bold', marginBottom: '20px',
				color: '#d32f2f', textAlign: 'center'
			});
			$modal.find('.merc-modal-anular-label').css({
				display: 'block', marginBottom: '8px', fontWeight: '600', color: '#555'
			});
			$modal.find('.merc-modal-anular-textarea').css({
				width: '100%', padding: '10px', border: '1px solid #ddd',
				borderRadius: '6px', fontSize: '14px', marginBottom: '15px', boxSizing: 'border-box'
			});
			$modal.find('.merc-modal-anular-buttons').css({
				display: 'flex', gap: '10px', justifyContent: 'center'
			});
			$modal.find('.merc-modal-anular-btn').css({
				padding: '10px 20px', border: 'none', borderRadius: '6px',
				cursor: 'pointer', fontSize: '14px', fontWeight: 'bold', transition: 'all 0.3s'
			});
			$modal.find('.merc-modal-anular-btn-confirmar').css({
				background: '#d32f2f', color: '#fff'
			});
			$modal.find('.merc-modal-anular-btn-cancelar').css({
				background: '#757575', color: '#fff'
			});
			
			$('body').append($modal);
			
			// Evento confirmar anulación
			$modal.find('.merc-modal-anular-btn-confirmar').on('click', function() {
				const motivo = $('#merc-motivo-anulacion').val().trim();
				
				if (!confirm('⚠️ ¿Confirmar la anulación del envío ' + shipmentNumber + '?')) {
					return;
				}
				
				$(this).prop('disabled', true).text('Anulando...');
				
				$.ajax({
					type: 'POST',
					url: AJAX_URL,
					data: {
						action: 'merc_anular_envio_cliente',
						shipment_id: shipmentId,
						motivo: motivo,
						nonce: '<?php echo wp_create_nonce('merc_anular_envio'); ?>'
					},
					success: function(response) {
						if (response.success) {
							$modal.remove();
							
							const $notif = $('<div style="position: fixed; top: 20px; right: 20px; background: #f44336; color: white; padding: 15px 25px; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 999999; font-size: 14px;">' +
								'✓ Envío anulado exitosamente' +
							'</div>');
							$('body').append($notif);
							
							setTimeout(function() {
								$notif.fadeOut(300, function() { $(this).remove(); });
							}, 3000);
							
							// Recargar tabla
							setTimeout(function() {
								window.location.reload();
							}, 2000);
						} else {
							alert('❌ Error: ' + (response.data || 'No se pudo anular'));
							$modal.find('.merc-modal-anular-btn-confirmar').prop('disabled', false).text('✓ Confirmar Anulación');
						}
					},
					error: function() {
						alert('❌ Error de conexión');
						$modal.find('.merc-modal-anular-btn-confirmar').prop('disabled', false).text('✓ Confirmar Anulación');
					}
				});
			});
			
			// Evento cancelar
			$modal.find('.merc-modal-anular-btn-cancelar').on('click', function() {
				$modal.remove();
			});
			
			// Cerrar con ESC
			$(document).on('keyup.mercModalAnular', function(e) {
				if (e.key === 'Escape') {
					$modal.remove();
					$(document).off('keyup.mercModalAnular');
				}
			});
		}
    });
    </script>
    <?php
}

// ===============================================
// AJAX: ACTUALIZAR ESTADO RÁPIDO (CON OBSERVACIONES)
// ===============================================

/**
 * Endpoint AJAX para actualizar el estado de un envío
 */
add_action('wp_ajax_merc_actualizar_estado_rapido', 'merc_actualizar_estado_rapido_ajax');
function merc_actualizar_estado_rapido_ajax() {
    // Verificar nonce
    check_ajax_referer('merc_actualizar_estado', 'nonce');
    
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    $nuevo_estado = isset($_POST['nuevo_estado']) ? sanitize_text_field($_POST['nuevo_estado']) : '';
    $observaciones = isset($_POST['observaciones']) ? sanitize_textarea_field($_POST['observaciones']) : '';
    
    if (empty($shipment_id) || empty($nuevo_estado)) {
        wp_send_json_error('Datos incompletos');
    }
    
    // Verificar que el post existe
    $shipment = get_post($shipment_id);
    if (!$shipment || $shipment->post_type !== 'wpcargo_shipment') {
        wp_send_json_error('Envío no encontrado');
    }
    
    // Obtener estado anterior
    $estado_anterior = get_post_meta($shipment_id, 'wpcargo_status', true);
    
    // Si el nuevo estado es "LISTO PARA SALIR", guardar el estado anterior
    if (stripos($nuevo_estado, 'LISTO PARA SALIR') !== false && !empty($estado_anterior)) {
        update_post_meta($shipment_id, 'wpcargo_status_anterior', $estado_anterior);
    }
    
    // Actualizar el meta del estado
    update_post_meta($shipment_id, 'wpcargo_status', $nuevo_estado);
    
    // Agregar al historial
    $historial = get_post_meta($shipment_id, 'wpcargo_shipments_update', true);
    if (!is_array($historial)) {
        $historial = array();
    }
    
    // Determinar las observaciones finales
    $remarks_final = '';
    if (!empty($observaciones)) {
        // Si el usuario escribió observaciones, usarlas
        $remarks_final = $observaciones;
    } else {
        // Si no hay observaciones, usar el texto por defecto
        $remarks_final = 'Estado actualizado desde la tabla de pedidos';
    }
    
    $nuevo_registro = array(
        'status' => $nuevo_estado,
        'date' => date('Y-m-d'),
        'time' => date('H:i:s'),
        'updated-name' => wp_get_current_user()->display_name,
        'remarks' => $remarks_final
    );
    
    array_unshift($historial, $nuevo_registro);
    update_post_meta($shipment_id, 'wpcargo_shipments_update', $historial);
    
    // Log
    $log_observaciones = !empty($observaciones) ? " - Observaciones: {$observaciones}" : '';
    error_log("✅ Estado actualizado - Pedido #{$shipment_id}: {$nuevo_estado}{$log_observaciones}");
    
    wp_send_json_success([
        'message' => 'Estado actualizado correctamente',
        'nuevo_estado' => $nuevo_estado,
        'observaciones' => $remarks_final
    ]);
}

// ===============================================
// AJAX: NOTIFICAR REPROGRAMACIÓN AL CLIENTE
// ===============================================

/**
 * Endpoint AJAX para notificar al cliente cuando un envío es reprogramado
 */
add_action('wp_ajax_merc_notificar_reprogramacion', 'merc_notificar_reprogramacion_ajax');
function merc_notificar_reprogramacion_ajax() {
    // Verificar nonce
    check_ajax_referer('merc_notificar_reprog', 'nonce');
    
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    $shipment_number = isset($_POST['shipment_number']) ? sanitize_text_field($_POST['shipment_number']) : '';
    
    if (empty($shipment_id)) {
        wp_send_json_error('ID de envío no proporcionado');
    }
    
    // Obtener el post del envío
    $shipment = get_post($shipment_id);
    if (!$shipment || $shipment->post_type !== 'wpcargo_shipment') {
        wp_send_json_error('Envío no encontrado');
    }
    
    // Obtener el cliente (autor del post)
    $cliente_id = $shipment->post_author;
    $cliente = get_userdata($cliente_id);
    
    if (!$cliente) {
        wp_send_json_error('Cliente no encontrado');
    }
    
    // Datos del cliente
    $cliente_email = $cliente->user_email;
    $cliente_nombre = $cliente->display_name;
    
    // Preparar email de notificación
    $asunto = 'Envío Reprogramado - ' . $shipment_number;
    
    $mensaje = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
            .header { background: #f44336; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: white; padding: 30px; border-radius: 0 0 5px 5px; }
            .alert { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .btn { display: inline-block; padding: 12px 30px; background: #f44336; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>⏰ Envío Reprogramado</h2>
            </div>
            <div class='content'>
                <p>Hola <strong>{$cliente_nombre}</strong>,</p>
                
                <p>Te informamos que tu envío ha sido <strong style='color: #f44336;'>REPROGRAMADO</strong>.</p>
                
                <div class='alert'>
                    <strong>📦 Número de envío:</strong> {$shipment_number}<br>
                    <strong>📅 Fecha y Hora de la modificación:</strong> " . wp_date('d/m/Y H:i') . "
                </div>
                
                <p>Para coordinar una nueva fecha de entrega, por favor:</p>
                
                <ul>
                    <li>Ingresa a tu cuenta en nuestra plataforma</li>
                    <li>Ve a la sección de \"Mis Envíos\"</li>
                    <li>Selecciona el envío <strong>{$shipment_number}</strong></li>
                    <li>Coordina la nueva fecha de entrega</li>
                </ul>
                
                <p style='text-align: center;'>
                    <a href='" . site_url() . "' class='btn'>Ir a Mi Cuenta</a>
                </p>
                
                <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                
                <p>Saludos,<br>Equipo de MerCourier</p>
            </div>
            <div class='footer'>
                <p>Este es un correo automático, por favor no responder.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers para email HTML
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: MerCourier <noreply@gold-swan-671549.hostingersite.com>'
    );
    
    // Enviar email
    $enviado = wp_mail($cliente_email, $asunto, $mensaje, $headers);
    
    if ($enviado) {
        // Agregar nota en el historial del envío
        $historial = get_post_meta($shipment_id, 'wpcargo_shipments_update', true);
        if (!is_array($historial)) {
            $historial = array();
        }
        
        $nuevo_registro = array(
            'status' => 'REPROGRAMADO',
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'updated-name' => 'Sistema',
            'remarks' => "Notificación enviada a {$cliente_nombre} ({$cliente_email})"
        );
        
        array_unshift($historial, $nuevo_registro);
        update_post_meta($shipment_id, 'wpcargo_shipments_update', $historial);
        
        error_log("📧 Notificación de reprogramación enviada - Pedido #{$shipment_id} a {$cliente_email}");
        
        wp_send_json_success([
            'message' => 'Notificación enviada correctamente',
            'cliente' => $cliente_nombre,
            'email' => $cliente_email
        ]);
    } else {
        error_log("❌ Error al enviar notificación - Pedido #{$shipment_id}");
        wp_send_json_error('Error al enviar el correo electrónico');
    }
}

// ===============================================
// FILTRO: MOSTRAR FECHA CORRECTA EN TABLA
// ===============================================

/**
 * Modificar la fecha mostrada en la tabla para que primero intente usar
 * wpcargo_pickup_date_picker (meta) y si está vacío use post_date como fallback
 */
add_filter('wpcargo_customizer_formatted_date', 'merc_usar_fecha_meta_primero', 10, 2);
function merc_usar_fecha_meta_primero($formatted_date, $original_date) {
    // Obtener el ID del shipment actual desde el contexto global
    global $shipment_id;
    
    if (empty($shipment_id)) {
        return $formatted_date;
    }
    
    // Intentar obtener la fecha desde el meta field primero (wpcargo_pickup_date_picker)
    $fecha_meta = get_post_meta($shipment_id, 'wpcargo_pickup_date_picker', true);
    
    // Si existe el meta y es diferente a la fecha original, usarlo
    if (!empty($fecha_meta) && $fecha_meta !== $original_date) {
        // Formatear la fecha meta con el mismo formato
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_meta);
        if ($fecha_obj) {
            return $fecha_obj->format('d/m/Y');
        }
    }
    
    // Si no hay meta o falló el formato, devolver la fecha original formateada
    return $formatted_date;
}

/**
 * Inyectar script JavaScript para reemplazar fechas en la tabla del dashboard
 * con las fechas del meta field wpcargo_pickup_date_picker cuando existan
 */
add_action('wp_footer', 'merc_corregir_fechas_tabla_dashboard');
function merc_corregir_fechas_tabla_dashboard() {
    // Solo ejecutar en la página de dashboard con la tabla de envíos
    if (!is_page('dashboard')) {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        console.log('🗓️ Sistema de actualización de fechas iniciado');
        
        // Mapeo de IDs de envío a fechas actualizadas (caché)
        let fechasActualizadas = {};
        
        // Función para corregir las fechas en la tabla
        function corregirFechasEnTabla() {
            // Buscar TODAS las filas con IDs de envío
            const $rows = $('tr[id^="shipment-"]');
            
            console.log('📋 Encontradas ' + $rows.length + ' filas de envíos en la tabla');
            
            if ($rows.length === 0) {
                console.warn('⚠️ No se encontraron filas de envíos. Selectores disponibles:', $('table tbody tr').length, 'filas totales');
                return;
            }
            
            $rows.each(function(index) {
                const $row = $(this);
                const rowId = $row.attr('id');
                
                if (!rowId) {
                    console.log('⚠️ Fila sin ID encontrada');
                    return;
                }
                
                // Extraer el ID del envío
                const match = rowId.match(/shipment-(\d+)/);
                if (!match) {
                    console.log('⚠️ No se pudo extraer ID de:', rowId);
                    return;
                }
                
                const shipmentId = match[1];
                
                // Si ya procesamos este envío, saltar
                if (fechasActualizadas[shipmentId]) {
                    return;
                }
                
                // Buscar TODAS las celdas de la fila y encontrar la que parece ser la fecha
                const $allCells = $row.find('td');
                console.log('🔍 Envío #' + shipmentId + ' tiene ' + $allCells.length + ' celdas');
                
                // La fecha de envío típicamente está en la columna 3, 4 o 5 (después de checkbox, número, estado)
                // Buscar por contenido que parezca una fecha (formato dd/mm/yyyy)
                let $fechaCelda = null;
                $allCells.each(function(i) {
                    const texto = $(this).text().trim();
                    // Buscar formato de fecha dd/mm/yyyy o yyyy-mm-dd
                    if (/\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}-\d{2}/.test(texto)) {
                        if (!$fechaCelda) { // Tomar la primera celda con formato de fecha
                            $fechaCelda = $(this);
                            console.log('📅 Celda de fecha encontrada en índice ' + i + ' con valor: ' + texto);
                        }
                    }
                });
                
                if (!$fechaCelda || $fechaCelda.length === 0) {
                    console.warn('⚠️ No se encontró celda de fecha para envío #' + shipmentId);
                    return;
                }
                
                // Marcar como procesada temporalmente
                fechasActualizadas[shipmentId] = 'procesando';
                
                // Hacer consulta AJAX para obtener la fecha correcta
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        action: 'merc_obtener_fecha_envio',
                        shipment_id: shipmentId,
                        nonce: '<?php echo wp_create_nonce('merc_obtener_fecha'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.fecha_formateada) {
                            const fechaActual = $fechaCelda.text().trim();
                            const fechaNueva = response.data.fecha_formateada;
                            
                            console.log('✅ Envío #' + shipmentId + ':');
                            console.log('   - Fecha en tabla: ' + fechaActual);
                            console.log('   - Fecha correcta: ' + fechaNueva);
                            console.log('   - Fue reprogramada: ' + response.data.fue_reprogramada);
                            
                            // Actualizar si es diferente
                            if (fechaActual !== fechaNueva) {
                                if (response.data.fue_reprogramada) {
                                    $fechaCelda.html('<strong style="color: #2196F3;" title="Fecha reprogramada por el cliente">' + fechaNueva + '</strong>');
                                    console.log('   🔄 Fecha REPROGRAMADA actualizada visualmente');
                                } else {
                                    $fechaCelda.text(fechaNueva);
                                    console.log('   ✓ Fecha actualizada');
                                }
                                fechasActualizadas[shipmentId] = fechaNueva;
                            } else {
                                console.log('   ⏭️ Fecha ya correcta, no se actualiza');
                                fechasActualizadas[shipmentId] = fechaActual;
                            }
                        } else {
                            console.error('❌ Error en respuesta para envío #' + shipmentId, response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Error AJAX para envío #' + shipmentId + ':', error);
                        delete fechasActualizadas[shipmentId]; // Permitir reintentar
                    }
                });
            });
        }
        
        // Ejecutar inmediatamente al cargar
        setTimeout(function() {
            console.log('⏰ Ejecutando actualización inicial de fechas...');
            corregirFechasEnTabla();
        }, 1000);
        
        // Ejecutar después de que todo cargue
        setTimeout(function() {
            console.log('⏰ Ejecutando actualización secundaria de fechas...');
            fechasActualizadas = {}; // Limpiar caché
            corregirFechasEnTabla();
        }, 3000);
        
        // Escuchar evento de reprogramación exitosa
        $(document).on('merc-fecha-reprogramada', function(e, shipmentId) {
            console.log('🔔 Evento de reprogramación detectado para envío #' + shipmentId);
            delete fechasActualizadas[shipmentId]; // Limpiar caché para este envío
            setTimeout(corregirFechasEnTabla, 500);
        });
    });
    </script>
    <?php
}

/**
 * AJAX endpoint para obtener la fecha de envío correcta
 */
add_action('wp_ajax_merc_obtener_fecha_envio', 'merc_obtener_fecha_envio_ajax');
function merc_obtener_fecha_envio_ajax() {
    check_ajax_referer('merc_obtener_fecha', 'nonce');
    
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    
    if (empty($shipment_id)) {
        wp_send_json_error('ID de envío no proporcionado');
    }
    
    // Verificar que el envío existe
    $shipment = get_post($shipment_id);
    if (!$shipment || $shipment->post_type !== 'wpcargo_shipment') {
        wp_send_json_error('Envío no encontrado');
    }
    
    // LÓGICA: Primero intentar wpcargo_pickup_date_picker, si está vacío usar post_date
    $fecha_meta = get_post_meta($shipment_id, 'wpcargo_pickup_date_picker', true);
    
    if (!empty($fecha_meta)) {
        // Formatear la fecha desde el meta (formato Y-m-d)
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_meta);
        if ($fecha_obj) {
            $fecha_formateada = $fecha_obj->format('d/m/Y');
        } else {
            $fecha_formateada = $fecha_meta;
        }
    } else {
        // Usar post_date como fallback
        $post_date = $shipment->post_date;
        $fecha_obj = new DateTime($post_date);
        $fecha_formateada = $fecha_obj->format('d/m/Y');
    }
    
    // Formatear post_date para comparación
    $post_date_obj = new DateTime($shipment->post_date);
    $post_date_formatted = $post_date_obj->format('d/m/Y');
    
    // Determinar si fue reprogramada (fecha meta diferente a post_date)
    $fue_reprogramada = !empty($fecha_meta) && $fecha_formateada !== $post_date_formatted;
    
    wp_send_json_success([
        'fecha_formateada' => $fecha_formateada,
        'fecha_meta' => $fecha_meta,
        'post_date' => $shipment->post_date,
        'post_date_formatted' => $post_date_formatted,
        'fue_reprogramada' => $fue_reprogramada
    ]);
}

// ===============================================
// AJAX: REPROGRAMAR FECHA DE ENVÍO (CLIENTE)
// ===============================================

/**
 * Endpoint AJAX para que el cliente reprograme la fecha de envío
 */
add_action('wp_ajax_merc_reprogramar_envio', 'merc_reprogramar_envio_ajax');
function merc_reprogramar_envio_ajax() {
    // Verificar nonce
    check_ajax_referer('merc_reprogramar', 'nonce');
    
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    $nueva_fecha = isset($_POST['nueva_fecha']) ? sanitize_text_field($_POST['nueva_fecha']) : '';
    
    if (empty($shipment_id) || empty($nueva_fecha)) {
        wp_send_json_error('Datos incompletos');
    }
    
    // ⭐ VALIDAR FORMATO DD/MM/YYYY (en lugar de Y-m-d)
    $fecha_obj = DateTime::createFromFormat('d/m/Y', $nueva_fecha);
    if (!$fecha_obj) {
        wp_send_json_error('Formato de fecha inválido. Use DD/MM/YYYY');
    }
    
    // Verificar que la fecha sea futura
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0); // Resetear hora para comparar solo fecha
    $fecha_obj->setTime(0, 0, 0);
    
    if ($fecha_obj <= $hoy) {
        wp_send_json_error('La fecha debe ser posterior a hoy');
    }
    
    // Obtener el post del envío
    $shipment = get_post($shipment_id);
    if (!$shipment || $shipment->post_type !== 'wpcargo_shipment') {
        wp_send_json_error('Envío no encontrado');
    }
    
    // Verificar que el usuario esté logueado
    if (!is_user_logged_in()) {
        wp_send_json_error('Debe iniciar sesión');
    }
    
    // Los administradores pueden reprogramar cualquier envío
    // Los clientes solo pueden reprogramar envíos que estén en estado REPROGRAMADO
    $current_user_id = get_current_user_id();
    $estado_actual = get_post_meta($shipment_id, 'wpcargo_status', true);
    
    if (!current_user_can('manage_options')) {
        // Si no es admin, verificar que el envío esté en estado REPROGRAMADO
        if (stripos($estado_actual, 'REPROGRAMADO') === false && stripos($estado_actual, 'RESCHEDULE') === false) {
            wp_send_json_error('Solo puede reprogramar envíos marcados como REPROGRAMADO');
        }
    }
    
    // Obtener fecha anterior (del campo pickup date de WPCargo)
    $fecha_anterior = get_post_meta($shipment_id, 'wpcargo_pickup_date_picker', true);
    
    // ⭐ GUARDAR EN FORMATO DD/MM/YYYY (tal como viene del frontend)
    update_post_meta($shipment_id, 'wpcargo_pickup_date_picker', $nueva_fecha);
    
    // Limpiar caché de LiteSpeed si está activo
    if (class_exists('LiteSpeed_Cache_API')) {
        LiteSpeed_Cache_API::purge_all('shipment date updated');
    }
    
    // Obtener el tipo de envío para determinar el nuevo estado
    $tipo_envio = get_post_meta($shipment_id, 'wpcargo_type_of_shipment', true);
    if (empty($tipo_envio)) {
        $tipo_envio = get_post_meta($shipment_id, 'tipo_envio', true);
    }
    
    $nuevo_estado = '';
    
    // Determinar nuevo estado según tipo de envío
    if (stripos($tipo_envio, 'AGENCIA') !== false || strtolower($tipo_envio) === 'express') {
        $nuevo_estado = 'RECEPCIONADO';
    } elseif (stripos($tipo_envio, 'EMPRENDEDOR') !== false || strtolower($tipo_envio) === 'normal') {
        $nuevo_estado = 'EN BASE MERCOURIER';
    } else {
        // Si no se puede determinar, mantener EN BASE MERCOURIER por defecto
        $nuevo_estado = 'EN BASE MERCOURIER';
    }
    
    // Actualizar el estado
    update_post_meta($shipment_id, 'wpcargo_status', $nuevo_estado);
    
    // Agregar al historial
    $historial = get_post_meta($shipment_id, 'wpcargo_shipments_update', true);
    if (!is_array($historial)) {
        $historial = array();
    }
    
    $usuario = wp_get_current_user();
    
    // ⭐ FORMATO DE FECHA PARA HISTORIAL: DD/MM/YYYY
    $fecha_actual_historial = date('d/m/Y'); // Cambiar de Y-m-d a d/m/Y
    $hora_actual = date('H:i:s');
    
    $nuevo_registro = array(
        'status' => $nuevo_estado,
        'date' => $fecha_actual_historial, // ⭐ Ahora en formato DD/MM/YYYY
        'time' => $hora_actual,
        'updated-name' => $usuario->display_name . ' (Cliente)',
        'remarks' => "Envío reprogramado. Fecha anterior: {$fecha_anterior} → Nueva fecha: {$nueva_fecha}. Estado cambiado a {$nuevo_estado}."
    );
    
    array_unshift($historial, $nuevo_registro);
    update_post_meta($shipment_id, 'wpcargo_shipments_update', $historial);
    
    // Enviar notificación al administrador
    $admin_email = get_option('admin_email');
    $shipment_number = get_post_meta($shipment_id, 'wpcargo_shipment_number', true);
    
    $asunto = 'Cliente Reprogramó Envío - ' . $shipment_number;
    $mensaje = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
            .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
            .content { background: white; padding: 30px; }
            .info { background: #e3f2fd; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>📅 Envío Reprogramado por Cliente</h2>
            </div>
            <div class='content'>
                <p>El cliente <strong>{$usuario->display_name}</strong> ha reprogramado un envío.</p>
                
                <div class='info'>
                    <strong>📦 Número de envío:</strong> {$shipment_number}<br>
                    <strong>👤 Cliente:</strong> {$usuario->display_name} ({$usuario->user_email})<br>
                    <strong>📆 Fecha anterior:</strong> {$fecha_anterior}<br>
                    <strong>📅 Nueva fecha:</strong> <span style='color: #2196F3; font-weight: bold;'>{$nueva_fecha}</span><br>
                    <strong>🕐 Fecha de cambio:</strong> " . date('d/m/Y H:i') . "
                </div>
                
                <p>El estado del envío ha sido cambiado a <strong>{$nuevo_estado}</strong>.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($admin_email, $asunto, $mensaje, $headers);
    
    error_log("📅 Envío reprogramado por cliente - Pedido #{$shipment_id}: {$fecha_anterior} → {$nueva_fecha}");
    
    wp_send_json_success([
        'message' => 'Fecha reprogramada exitosamente',
        'nueva_fecha' => $nueva_fecha,
        'fecha_anterior' => $fecha_anterior
    ]);
}

// ===============================================
// AJAX: ANULAR ENVÍO (CLIENTE)
// ===============================================

add_action('wp_ajax_merc_anular_envio_cliente', 'merc_anular_envio_cliente_ajax');
function merc_anular_envio_cliente_ajax() {
    // Verificar nonce
    check_ajax_referer('merc_anular_envio', 'nonce');
    
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : 'Cliente solicitó anulación';
    
    if (empty($shipment_id)) {
        wp_send_json_error('ID de envío no proporcionado');
    }
    
    // Verificar que el envío existe
    $shipment = get_post($shipment_id);
    if (!$shipment || $shipment->post_type !== 'wpcargo_shipment') {
        wp_send_json_error('Envío no encontrado');
    }
    
    // Verificar que el usuario esté logueado
    if (!is_user_logged_in()) {
        wp_send_json_error('Debe iniciar sesión');
    }
    
    $current_user_id = get_current_user_id();
    $current_user = wp_get_user_by('id', $current_user_id);
    
    // Verificar que el envío esté en estado REPROGRAMADO
    $estado_actual = get_post_meta($shipment_id, 'wpcargo_status', true);
    
    if (!current_user_can('manage_options')) {
        // Si no es admin, verificar que el envío esté en estado REPROGRAMADO
        if (stripos($estado_actual, 'REPROGRAMADO') === false && stripos($estado_actual, 'RESCHEDULE') === false) {
            wp_send_json_error('Solo puede anular envíos marcados como REPROGRAMADO');
        }
        
        // Verificar que el envío pertenezca al cliente
        $shipper_id = get_post_meta($shipment_id, 'registered_shipper', true);
        if ($shipper_id != $current_user_id) {
            wp_send_json_error('No tiene permisos para anular este envío');
        }
    }
    
    // Cambiar estado a ANULADO
    update_post_meta($shipment_id, 'wpcargo_status', 'ANULADO');
    
    // Registrar en el historial
    $historial = get_post_meta($shipment_id, 'wpcargo_shipments_update', true);
    if (!is_array($historial)) {
        $historial = array();
    }
    
    $fecha_actual = date('d/m/Y');
    $hora_actual = date('H:i:s');
    
    $nuevo_registro = array(
        'status' => 'ANULADO',
        'date' => $fecha_actual,
        'time' => $hora_actual,
        'updated-name' => $current_user->display_name . ' (Cliente)',
        'remarks' => "Envío anulado por el cliente. Estado anterior: {$estado_actual}. Motivo: {$motivo}"
    );
    
    array_unshift($historial, $nuevo_registro);
    update_post_meta($shipment_id, 'wpcargo_shipments_update', $historial);
    
    // Enviar notificación al administrador
    $admin_email = get_option('admin_email');
    $shipment_number = get_post_meta($shipment_id, 'wpcargo_shipment_number', true);
    
    $asunto = 'Cliente Anuló Envío - ' . $shipment_number;
    $mensaje = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
            .header { background: #f44336; color: white; padding: 20px; text-align: center; }
            .content { background: white; padding: 30px; }
            .info { background: #ffebee; padding: 15px; margin: 15px 0; border-left: 4px solid #f44336; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>❌ Envío Anulado por Cliente</h2>
            </div>
            <div class='content'>
                <div class='info'>
                    <strong>📦 Número de envío:</strong> {$shipment_number}<br>
                    <strong>👤 Cliente:</strong> {$current_user->display_name} ({$current_user->user_email})<br>
                    <strong>📝 Motivo:</strong> {$motivo}<br>
                    <strong>🕐 Fecha de anulación:</strong> " . date('d/m/Y H:i') . "
                </div>
                
                <p>El estado del envío ha sido cambiado a <strong>ANULADO</strong>.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($admin_email, $asunto, $mensaje, $headers);
    
    error_log("❌ Envío anulado por cliente - Pedido #{$shipment_id}: {$current_user->display_name}. Motivo: {$motivo}");
    
    wp_send_json_success([
        'message' => 'Envío anulado exitosamente',
        'shipment_id' => $shipment_id
    ]);
}

// ===============================================
// SISTEMA DE ALMACÉN DE PRODUCTOS
// ===============================================

/**
 * Registrar Custom Post Type para Productos de Almacén
 */
add_action('init', 'merc_registrar_productos_almacen');
function merc_registrar_productos_almacen() {
    $labels = array(
        'name'                  => 'Productos',
        'singular_name'         => 'Producto',
        'menu_name'             => 'Almacén de Productos',
        'add_new'               => 'Agregar Producto',
        'add_new_item'          => 'Agregar Nuevo Producto',
        'edit_item'             => 'Editar Producto',
        'new_item'              => 'Nuevo Producto',
        'view_item'             => 'Ver Producto',
        'search_items'          => 'Buscar Productos',
        'not_found'             => 'No se encontraron productos',
        'not_found_in_trash'    => 'No hay productos en la papelera',
        'all_items'             => 'Todos los Productos'
    );
    
    $args = array(
        'labels'                => $labels,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_icon'             => 'dashicons-clipboard',
        'menu_position'         => 26,
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
        'hierarchical'          => false,
        'supports'              => array('title', 'author'),
        'has_archive'           => false,
        'rewrite'               => false,
        'query_var'             => false,
        'show_in_rest'          => false
    );
    
    register_post_type('merc_producto', $args);
    
    // Inicializar valores por defecto para productos existentes (solo la primera vez)
    if (!get_option('merc_productos_inicializados')) {
        merc_inicializar_productos_existentes();
        update_option('merc_productos_inicializados', true);
    }
}

/**
 * Inicializar valores por defecto para productos que no tienen estado
 */
function merc_inicializar_productos_existentes() {
    $productos = get_posts(array(
        'post_type' => 'merc_producto',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    foreach ($productos as $producto) {
        $estado = get_post_meta($producto->ID, '_merc_producto_estado', true);
        $motorizado = get_post_meta($producto->ID, '_merc_producto_motorizado', true);
        
        // Si no tiene estado, establecer 'sin_asignar'
        if (empty($estado)) {
            update_post_meta($producto->ID, '_merc_producto_estado', 'sin_asignar');
        }
        
        // Si no tiene motorizado, establecer '-'
        if (empty($motorizado)) {
            update_post_meta($producto->ID, '_merc_producto_motorizado', '-');
        }
    }
}

/**
 * SISTEMA: Tabla de unidades de stock y helpers por unidad
 * - Crea tabla `{prefix}merc_stock_units` si no existe
 * - Cada fila representa una unidad física: product_id, sku, status (available, assigned, delivered)
 */
function merc_get_stock_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'merc_stock_units';
}

function merc_ensure_stock_table() {
    global $wpdb;
    $table = merc_get_stock_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $sql = "CREATE TABLE {$table} (\n"
         . "  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n"
         . "  product_id BIGINT UNSIGNED NOT NULL,\n"
         . "  sku VARCHAR(255) DEFAULT '',\n"
         . "  status VARCHAR(32) NOT NULL DEFAULT 'available',\n"
         . "  shipment_id BIGINT UNSIGNED NULL DEFAULT NULL,\n"
         . "  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n"
         . "  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n"
         . "  PRIMARY KEY (id),\n"
         . "  KEY product_id (product_id),\n"
         . "  KEY status (status)\n"
         . ") {$charset_collate};";

    dbDelta($sql);
}
add_action('init', 'merc_ensure_stock_table');

/**
 * Obtener cantidad disponible (status = 'available')
 */
function merc_get_product_stock($product_id) {
    global $wpdb;
    $table = merc_get_stock_table_name();
    $qty = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status = 'available'", $product_id));
    if ($qty !== null) return intval($qty);
    return 0;
}

/**
 * Asegurar que existan exactamente $quantity unidades disponibles para el producto.
 * - Si hay menos, inserta unidades nuevas (status 'available').
 * - Si hay más, elimina unidades 'available' extra (no elimina unidades asignadas).
 */
function merc_set_product_stock($product_id, $quantity, $sku = '') {
    global $wpdb;
    $table = merc_get_stock_table_name();
    $current_available = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status = 'available'", $product_id)));
    // Contar unidades no-disponibles (assigned/delivered)
    $non_available = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status != 'available'", $product_id)));

    // Si se intenta reducir la cantidad total por debajo del número de unidades ya asignadas/delivered, prohibir
    if ($quantity < $non_available) {
        return new WP_Error('stock_locked', 'No puedes establecer una cantidad menor que las unidades ya asignadas o entregadas.');
    }

    if ($quantity > $current_available) {
        $to_add = $quantity - $current_available;
        for ($i = 0; $i < $to_add; $i++) {
            $wpdb->insert($table, array(
                'product_id' => intval($product_id),
                'sku' => $sku,
                'status' => 'available',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ), array('%d','%s','%s','%s','%s'));
        }
    } elseif ($quantity < $current_available) {
        $to_remove = $current_available - $quantity;
        // Eliminar unidades disponibles más recientes (por id DESC)
        $rows = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE product_id = %d AND status = 'available' ORDER BY id DESC LIMIT %d", $product_id, $to_remove));
        if (!empty($rows)) {
            $ids = implode(',', array_map('intval', $rows));
            $wpdb->query("DELETE FROM {$table} WHERE id IN ({$ids})");
        }
    }

    // Mantener meta para compatibilidad (suma de todas unidades, incluidas asignadas)
    $total = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d", $product_id)));
    update_post_meta($product_id, '_merc_producto_cantidad', $total);
    if (!empty($sku)) update_post_meta($product_id, '_merc_producto_codigo_barras', sanitize_text_field($sku));

    return true;
}

/**
 * Asignar N unidades disponibles y ligarlas a un envío. Devuelve array de unit ids o false si no hay stock suficiente.
 */
function merc_assign_stock_units($product_id, $quantity, $shipment_id = null) {
    global $wpdb;
    $table = merc_get_stock_table_name();

    $available = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status = 'available'", $product_id)));
    error_log("   🔍 merc_assign_stock_units() - Producto #{$product_id}: {$available} unidades disponibles");
    if ($available < $quantity) {
        error_log("   ❌ merc_assign_stock_units() - Stock insuficiente: necesitas {$quantity}, disponibles {$available}");
        return false;
    }

    $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE product_id = %d AND status = 'available' ORDER BY id ASC LIMIT %d", $product_id, $quantity));
    if (empty($ids)) {
        error_log("   ❌ merc_assign_stock_units() - get_col retornó vacío");
        return false;
    }

    error_log("   📍 merc_assign_stock_units() - Unidades a asignar: " . implode(',', $ids));

    $in = implode(',', array_map('intval', $ids));
    $shipment_val = $shipment_id ? intval($shipment_id) : 'NULL';
    
    $query = "UPDATE {$table} SET status = 'assigned', shipment_id = {$shipment_val}, updated_at = '" . current_time('mysql') . "' WHERE id IN ({$in})";
    error_log("   🔨 merc_assign_stock_units() - Ejecutando: UPDATE ...WHERE id IN ({$in})");
    
    $result = $wpdb->query($query);
    error_log("   ✅ merc_assign_stock_units() - Resultado: {$result} filas afectadas, shipment_id={$shipment_val}");
    
    return $ids;
}

/**
 * Desasignar unidades por ids (volver a status 'available')
 */
function merc_unassign_stock_units(array $unit_ids) {
    global $wpdb;
    $table = merc_get_stock_table_name();
    $ids = implode(',', array_map('intval', $unit_ids));
    if (empty($ids)) return false;
    $wpdb->query("UPDATE {$table} SET status = 'available', shipment_id = NULL, updated_at = '" . current_time('mysql') . "' WHERE id IN ({$ids})");
    return true;
}

/**
 * Marcar unidades como entregadas por ids
 */
function merc_mark_units_delivered(array $unit_ids) {
    global $wpdb;
    $table = merc_get_stock_table_name();
    $ids = implode(',', array_map('intval', $unit_ids));
    if (empty($ids)) return false;
    $wpdb->query("UPDATE {$table} SET status = 'delivered', updated_at = '" . current_time('mysql') . "' WHERE id IN ({$ids})");
    return true;
}

/**
 * Agregar Meta Box para campos de producto
 */
add_action('add_meta_boxes', 'merc_producto_meta_boxes');
function merc_producto_meta_boxes() {
    error_log("🔧 Registrando metabox de productos");
    add_meta_box(
        'merc_producto_datos',
        'Datos del Producto',
        'merc_producto_datos_callback',
        'merc_producto',
        'normal',
        'high'
    );
}

function merc_producto_datos_callback($post) {
    error_log("✅ Callback del metabox ejecutándose - Post ID: " . $post->ID);
    wp_nonce_field('merc_producto_guardar', 'merc_producto_nonce');
    
    $cantidad = merc_get_product_stock($post->ID);
    $cantidad = !empty($cantidad) ? intval($cantidad) : 0;
    
    $codigo_barras = get_post_meta($post->ID, '_merc_producto_codigo_barras', true);
    
    // Campos de peso y medidas
    $peso = get_post_meta($post->ID, '_merc_producto_peso', true);
    $tipo_medida = get_post_meta($post->ID, '_merc_producto_tipo_medida', true);
    $valor_medida = get_post_meta($post->ID, '_merc_producto_valor_medida', true);
    $largo = get_post_meta($post->ID, '_merc_producto_largo', true);
    $ancho = get_post_meta($post->ID, '_merc_producto_ancho', true);
    $alto = get_post_meta($post->ID, '_merc_producto_alto', true);
    
    $fecha_creacion = get_the_date('Y-m-d H:i:s', $post->ID);
    $fecha_modificacion = get_the_modified_date('Y-m-d H:i:s', $post->ID);
    ?>
    <style>
        .merc-producto-field {
            margin-bottom: 20px;
        }
        .merc-producto-field label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .merc-producto-field input[type="text"],
        .merc-producto-field input[type="number"] {
            width: 100%;
            max-width: 400px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .merc-producto-field input[type="number"] {
            max-width: 150px;
        }
        .merc-producto-field .description {
            color: #666;
            font-size: 13px;
            font-style: italic;
            margin-top: 5px;
        }
        .merc-producto-readonly {
            background-color: #f5f5f5;
            padding: 10px;
            border-left: 4px solid #2196F3;
            margin: 10px 0;
        }
    </style>
    
    <div class="merc-producto-field">
        <label for="merc_producto_nombre">Nombre del Producto *</label>
        <input type="text" id="merc_producto_nombre" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" required>
        <p class="description">Nombre descriptivo del producto</p>
    </div>
    
    <div class="merc-producto-field">
        <label for="merc_producto_codigo_barras">Código de Barras</label>
        <input type="text" id="merc_producto_codigo_barras" name="merc_producto_codigo_barras" value="<?php echo esc_attr($codigo_barras); ?>" placeholder="Escanea o ingresa el código">
        <p class="description">📦 Código de barras del producto para escaneo rápido</p>
    </div>
    
    <div class="merc-producto-field">
        <label for="merc_producto_cliente_asignado">Cliente Asignado *</label>
        <?php
        $cliente_asignado = get_post_meta($post->ID, '_merc_producto_cliente_asignado', true);
        $clientes = get_users(array('role' => 'wpcargo_client'));
        
        // Debug
        error_log("🔍 DEBUG Clientes encontrados: " . count($clientes));
        foreach($clientes as $c) {
            error_log("  - Cliente ID: {$c->ID}, Nombre: {$c->display_name}, Email: {$c->user_email}");
        }
        ?>
        <select id="merc_producto_cliente_asignado" name="merc_producto_cliente_asignado" style="width: 100%; max-width: 400px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; display: block !important; visibility: visible !important;" required>
            <option value="">-- Selecciona un cliente --</option>
            <?php foreach ($clientes as $cliente): ?>
                <option value="<?php echo $cliente->ID; ?>" <?php selected($cliente_asignado, $cliente->ID); ?>>
                    <?php 
                    $nombre_completo = trim($cliente->first_name . ' ' . $cliente->last_name);
                    echo esc_html(!empty($nombre_completo) ? $nombre_completo : $cliente->display_name); 
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">👤 <strong>Campo obligatorio:</strong> Cada producto debe estar asignado a un cliente específico.
        <?php if (empty($clientes)): ?>
            <br><span style="color: red;">⚠️ No hay clientes registrados. Por favor, crea usuarios con rol "Cliente" primero.</span>
        <?php endif; ?>
        </p>
    </div>
    
    <div class="merc-producto-field">
        <label for="merc_producto_cantidad">Cantidad en Almacén *</label>
        <input type="number" id="merc_producto_cantidad" name="merc_producto_cantidad" value="<?php echo esc_attr($cantidad); ?>" min="0" step="1" required>
        <p class="description">Cantidad disponible actualmente en el almacén</p>
    </div>
    
    <div class="merc-producto-field">
        <label for="merc_producto_peso">Peso (kg)</label>
        <input type="number" id="merc_producto_peso" name="merc_producto_peso" value="<?php echo esc_attr($peso); ?>" min="0" step="0.01">
        <p class="description">Peso del producto en kilogramos</p>
    </div>
    
    <div class="merc-producto-field">
        <label for="merc_producto_tipo_medida">Tipo de Medida</label>
        <select id="merc_producto_tipo_medida" name="merc_producto_tipo_medida" style="width: 100%; max-width: 400px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">-- Seleccionar --</option>
            <option value="talla" <?php selected($tipo_medida, 'talla'); ?>>Talla</option>
            <option value="volumen" <?php selected($tipo_medida, 'volumen'); ?>>Volumen</option>
            <option value="dimensiones" <?php selected($tipo_medida, 'dimensiones'); ?>>Dimensiones</option>
            <option value="unidades" <?php selected($tipo_medida, 'unidades'); ?>>Unidades</option>
            <option value="otro" <?php selected($tipo_medida, 'otro'); ?>>Otro</option>
        </select>
        <p class="description">Tipo de medida aplicable al producto</p>
    </div>
    
    <div class="merc-producto-field">
        <label for="merc_producto_valor_medida">Valor de Medida</label>
        <input type="text" id="merc_producto_valor_medida" name="merc_producto_valor_medida" value="<?php echo esc_attr($valor_medida); ?>" placeholder="Ej: S, M, L, XL o 100ml">
        <p class="description">Valor específico (ej: talla S, M, L o cantidad)</p>
    </div>
    
    <div class="merc-producto-field">
        <label>Dimensiones (cm) - Opcional</label>
        <div style="display: flex; gap: 10px; max-width: 400px;">
            <input type="number" name="merc_producto_largo" value="<?php echo esc_attr($largo); ?>" placeholder="Largo" min="0" step="0.1" style="flex: 1; padding: 8px;">
            <span style="line-height: 34px;">×</span>
            <input type="number" name="merc_producto_ancho" value="<?php echo esc_attr($ancho); ?>" placeholder="Ancho" min="0" step="0.1" style="flex: 1; padding: 8px;">
            <span style="line-height: 34px;">×</span>
            <input type="number" name="merc_producto_alto" value="<?php echo esc_attr($alto); ?>" placeholder="Alto" min="0" step="0.1" style="flex: 1; padding: 8px;">
        </div>
        <p class="description">Dimensiones del producto (Largo × Ancho × Alto en cm)</p>
    </div>
    
    <?php if ($post->ID): ?>
    <div class="merc-producto-readonly">
        <p><strong>📅 Fecha de Creación:</strong> <?php echo date('d/m/Y H:i', strtotime($fecha_creacion)); ?></p>
        <p><strong>🔄 Última Modificación:</strong> <?php echo date('d/m/Y H:i', strtotime($fecha_modificacion)); ?></p>
    </div>
    <?php endif; ?>
    <?php
}

/**
 * Guardar datos del producto
 */
add_action('save_post_merc_producto', 'merc_guardar_producto_datos', 10, 2);
function merc_guardar_producto_datos($post_id, $post) {
    // Verificar nonce
    if (!isset($_POST['merc_producto_nonce']) || !wp_verify_nonce($_POST['merc_producto_nonce'], 'merc_producto_guardar')) {
        return;
    }
    
    // Verificar permisos
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Evitar autoguardado
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Inicializar estado y motorizado si no existen (productos nuevos)
    $estado = get_post_meta($post_id, '_merc_producto_estado', true);
    if (empty($estado)) {
        update_post_meta($post_id, '_merc_producto_estado', 'sin_asignar');
    }
    
    $motorizado = get_post_meta($post_id, '_merc_producto_motorizado', true);
    if (empty($motorizado)) {
        update_post_meta($post_id, '_merc_producto_motorizado', '-');
    }
    
    // Guardar cantidad
    if (isset($_POST['merc_producto_cantidad'])) {
        $cantidad = intval($_POST['merc_producto_cantidad']);
        $codigo_barras = isset($_POST['merc_producto_codigo_barras']) ? sanitize_text_field($_POST['merc_producto_codigo_barras']) : '';
        // Guardar en la tabla de stock y mantener meta. Proteger si hay unidades asignadas/delivered
        $result = merc_set_product_stock($post_id, $cantidad, $codigo_barras);
        if (is_wp_error($result)) {
            add_filter('redirect_post_location', function($location) use ($result) {
                return add_query_arg('merc_error', urlencode($result->get_error_message()), $location);
            });
        }

        // Log de cambios
        error_log("📦 Producto actualizado - ID: {$post_id}, Cantidad: {$cantidad}");
    }
    
    // Guardar peso
    if (isset($_POST['merc_producto_peso'])) {
        $peso = floatval($_POST['merc_producto_peso']);
        update_post_meta($post_id, '_merc_producto_peso', $peso);
    }
    
    // Guardar tipo de medida
    if (isset($_POST['merc_producto_tipo_medida'])) {
        $tipo_medida = sanitize_text_field($_POST['merc_producto_tipo_medida']);
        update_post_meta($post_id, '_merc_producto_tipo_medida', $tipo_medida);
    }
    
    // Guardar valor de medida
    if (isset($_POST['merc_producto_valor_medida'])) {
        $valor_medida = sanitize_text_field($_POST['merc_producto_valor_medida']);
        update_post_meta($post_id, '_merc_producto_valor_medida', $valor_medida);
    }
    
    // Guardar dimensiones
    if (isset($_POST['merc_producto_largo'])) {
        $largo = floatval($_POST['merc_producto_largo']);
        update_post_meta($post_id, '_merc_producto_largo', $largo);
    }
    if (isset($_POST['merc_producto_ancho'])) {
        $ancho = floatval($_POST['merc_producto_ancho']);
        update_post_meta($post_id, '_merc_producto_ancho', $ancho);
    }
    if (isset($_POST['merc_producto_alto'])) {
        $alto = floatval($_POST['merc_producto_alto']);
        update_post_meta($post_id, '_merc_producto_alto', $alto);
    }
    
    // Guardar código de barras
    if (isset($_POST['merc_producto_codigo_barras'])) {
        $codigo_barras = sanitize_text_field($_POST['merc_producto_codigo_barras']);
        update_post_meta($post_id, '_merc_producto_codigo_barras', $codigo_barras);
    }
    
    // Guardar cliente asignado (solo admin) - OBLIGATORIO
    if (current_user_can('manage_options')) {
        if (isset($_POST['merc_producto_cliente_asignado'])) {
            $cliente_id = intval($_POST['merc_producto_cliente_asignado']);
            if ($cliente_id > 0) {
                update_post_meta($post_id, '_merc_producto_cliente_asignado', $cliente_id);
            } else {
                // Si no hay cliente, mostrar error
                add_filter('redirect_post_location', function($location) {
                    return add_query_arg('merc_error', 'cliente_requerido', $location);
                });
            }
        }
    }
}

/**
 * Filtrar productos según el cliente logueado
 */
add_action('pre_get_posts', 'merc_filtrar_productos_por_cliente');
function merc_filtrar_productos_por_cliente($query) {
    // Solo en admin, para el post type merc_producto
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'merc_producto') {
        return;
    }
    
    // Si es admin, mostrar todos
    if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
        return;
    }
    
    // Para clientes, filtrar solo sus productos asignados
    $current_user_id = get_current_user_id();
    
    $meta_query = array(
        array(
            'key' => '_merc_producto_cliente_asignado',
            'value' => $current_user_id,
            'compare' => '='
        )
    );
    
    $query->set('meta_query', $meta_query);
}

/**
 * Dar capacidades de lectura de productos a los clientes
 */
add_action('init', 'merc_agregar_capacidades_productos_clientes');
function merc_agregar_capacidades_productos_clientes() {
    $cliente_role = get_role('wpcargo_client');
    if ($cliente_role) {
        $cliente_role->add_cap('read_post');
        $cliente_role->add_cap('read_private_posts');
    }
}

/**
 * Personalizar columnas en el listado de productos
 */
add_filter('manage_merc_producto_posts_columns', 'merc_producto_columnas');
function merc_producto_columnas($columns) {
    $new_columns = array(
        'cb'                    => $columns['cb'],
        'title'                 => 'Nombre del Producto',
        'codigo_barras'         => 'Código de Barras',
        'cantidad'              => 'Cantidad',
        'peso'                  => 'Peso',
        'tipo_medida'           => 'Tipo/Medida',
        'estado'                => 'Estado',
        'motorizado'            => 'Motorizado',
        'fecha_creacion'        => 'Fecha de Creación',
        'fecha_modificacion'    => 'Última Modificación'
    );
    return $new_columns;
}

add_action('manage_merc_producto_posts_custom_column', 'merc_producto_columna_contenido', 10, 2);
function merc_producto_columna_contenido($column, $post_id) {
    switch ($column) {
        case 'codigo_barras':
            $codigo = get_post_meta($post_id, '_merc_producto_codigo_barras', true);
            if (!empty($codigo)) {
                echo '<code style="background: #f5f5f5; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 13px;">' . esc_html($codigo) . '</code>';
            } else {
                echo '<span style="color: #999;">-</span>';
            }
            break;
            
        case 'cantidad':
            global $wpdb;
            $table = merc_get_stock_table_name();
            $available = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status = 'available'", $post_id)));
            $total = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d", $post_id)));

            // Color según disponibilidad (en base a disponibles)
            $color = '#4caf50'; // Verde
            if ($available == 0) {
                $color = '#f44336'; // Rojo
            } elseif ($available < 10) {
                $color = '#ff9800'; // Naranja
            }

            // Mostrar disponibles / total para mayor claridad
            echo '<strong style="color: ' . $color . '; font-size: 16px;">' . $available . ' / ' . $total . ' disponibles</strong>';
            break;
        
        case 'peso':
            $peso = get_post_meta($post_id, '_merc_producto_peso', true);
            if (!empty($peso) && $peso > 0) {
                echo '<strong>' . number_format(floatval($peso), 2) . ' kg</strong>';
            } else {
                echo '<span style="color: #999;">-</span>';
            }
            break;
            
        case 'tipo_medida':
            $tipo_medida = get_post_meta($post_id, '_merc_producto_tipo_medida', true);
            $valor_medida = get_post_meta($post_id, '_merc_producto_valor_medida', true);
            
            if (!empty($tipo_medida)) {
                $tipo_label = ucfirst($tipo_medida);
                $badges = array(
                    'talla' => '#2196F3',
                    'volumen' => '#9C27B0',
                    'dimensiones' => '#FF9800',
                    'unidades' => '#4CAF50',
                    'otro' => '#757575'
                );
                $color = isset($badges[$tipo_medida]) ? $badges[$tipo_medida] : '#757575';
                
                echo '<span style="background: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">' . esc_html($tipo_label) . '</span>';
                
                if (!empty($valor_medida)) {
                    echo '<br><small style="color: #666;">' . esc_html($valor_medida) . '</small>';
                }
            } else {
                echo '<span style="color: #999;">-</span>';
            }
            break;
        
        case 'estado':
            $estado = get_post_meta($post_id, '_merc_producto_estado', true);
            if (empty($estado)) {
                $estado = 'sin_asignar';
            }
            
            $estados = array(
                'sin_asignar' => array('label' => '📦 Sin Asignar', 'color' => '#95a5a6'),
                'asignado' => array('label' => '🚚 Asignado', 'color' => '#3498db'),
                'entregado' => array('label' => '✅ Entregado', 'color' => '#2ecc71')
            );
            
            $info = $estados[$estado];
            echo '<span style="display: inline-block; padding: 5px 12px; border-radius: 15px; background: ' . $info['color'] . '; color: white; font-size: 12px; font-weight: 600;">' . $info['label'] . '</span>';
            break;
        
        case 'motorizado':
            $motorizado = get_post_meta($post_id, '_merc_producto_motorizado', true);
            if (empty($motorizado) || $motorizado == '-') {
                echo '<span style="color: #999;">-</span>';
            } else {
                echo '<strong>' . esc_html($motorizado) . '</strong>';
            }
            break;
            
        case 'fecha_creacion':
            echo get_the_date('d/m/Y H:i', $post_id);
            break;
            
        case 'fecha_modificacion':
            echo get_the_modified_date('d/m/Y H:i', $post_id);
            break;
    }
}

/**
 * Hacer columnas ordenables
 */
add_filter('manage_edit-merc_producto_sortable_columns', 'merc_producto_columnas_ordenables');
function merc_producto_columnas_ordenables($columns) {
    $columns['cantidad'] = 'cantidad';
    $columns['fecha_creacion'] = 'date';
    $columns['fecha_modificacion'] = 'modified';
    return $columns;
}

add_action('pre_get_posts', 'merc_producto_ordenar_columnas');
function merc_producto_ordenar_columnas($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('post_type') != 'merc_producto') {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ($orderby == 'cantidad') {
        $query->set('meta_key', '_merc_producto_cantidad');
        $query->set('orderby', 'meta_value_num');
    }
}

/**
 * Agregar Meta Box en envíos para seleccionar producto
 */
add_action('add_meta_boxes', 'merc_envio_producto_meta_box');
function merc_envio_producto_meta_box() {
    global $post;
    
    // Solo mostrar el meta box si el tipo de envío es MERC FULL FITMENT
    $tipo_envio = get_post_meta($post->ID, 'wpcargo_type_of_shipment', true);
    
    // Si no hay tipo aún (nuevo envío), verificar parámetro GET
    if (empty($tipo_envio) && isset($_GET['type']) && $_GET['type'] === 'full_fitment') {
        $tipo_envio = 'MERC FULL FITMENT';
    }
    
    // Solo agregar el meta box si es FULL FITMENT
    if (stripos($tipo_envio, 'FULL FITMENT') !== false || stripos($tipo_envio, 'full_fitment') !== false) {
        add_meta_box(
            'merc_envio_producto',
            '📦 Producto del Almacén',
            'merc_envio_producto_callback',
            'wpcargo_shipment',
            'side',
            'high'
        );
    }
}

function merc_envio_producto_callback($post) {
    wp_nonce_field('merc_envio_producto_guardar', 'merc_envio_producto_nonce');
    
    $producto_id = get_post_meta($post->ID, '_merc_producto_id', true);
    $cantidad_enviada = get_post_meta($post->ID, '_merc_producto_cantidad', true);
    $cantidad_enviada = !empty($cantidad_enviada) ? intval($cantidad_enviada) : 1;
    
    // Obtener todos los productos
    $productos = get_posts(array(
        'post_type' => 'merc_producto',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    ?>
    <style>
        .merc-envio-producto-field {
            margin-bottom: 15px;
        }
        .merc-envio-producto-field label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .merc-envio-producto-field select,
        .merc-envio-producto-field input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .merc-producto-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 13px;
        }
        .merc-producto-info strong {
            color: #1976d2;
        }
        .merc-alerta-stock {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-top: 10px;
            font-size: 12px;
        }
    </style>
    
    <div class="merc-envio-producto-field">
        <label for="merc_producto_select">Producto a Entregar</label>
        <select id="merc_producto_select" name="merc_producto_id">
            <option value="">-- Seleccionar Producto --</option>
            <?php foreach ($productos as $producto): 
                $cantidad_disponible = merc_get_product_stock($producto->ID);
                $cantidad_disponible = !empty($cantidad_disponible) ? intval($cantidad_disponible) : 0;
                $selected = ($producto_id == $producto->ID) ? 'selected' : '';
            ?>
            <option value="<?php echo $producto->ID; ?>" data-cantidad="<?php echo $cantidad_disponible; ?>" <?php echo $selected; ?>>
                <?php echo esc_html($producto->post_title); ?> (<?php echo $cantidad_disponible; ?> disponibles)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="merc-envio-producto-field">
        <label for="merc_producto_cantidad_envio">Cantidad a Enviar</label>
        <input type="number" id="merc_producto_cantidad_envio" name="merc_producto_cantidad" value="<?php echo esc_attr($cantidad_enviada); ?>" min="1" step="1">
    </div>
    
    <div id="merc_producto_info_box" class="merc-producto-info" style="display: none;">
        <p><strong>Disponible:</strong> <span id="merc_stock_disponible">0</span> unidades</p>
    </div>
    
    <div id="merc_alerta_stock" class="merc-alerta-stock" style="display: none;">
        ⚠️ <strong>Advertencia:</strong> No hay suficiente stock disponible
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        const $productoSelect = $('#merc_producto_select');
        const $cantidadInput = $('#merc_producto_cantidad_envio');
        const $infoBox = $('#merc_producto_info_box');
        const $stockSpan = $('#merc_stock_disponible');
        const $alertaStock = $('#merc_alerta_stock');
        
        function actualizarInfo() {
            const $selectedOption = $productoSelect.find('option:selected');
            const cantidadDisponible = parseInt($selectedOption.data('cantidad')) || 0;
            const cantidadSolicitada = parseInt($cantidadInput.val()) || 0;
            
            if ($selectedOption.val()) {
                $infoBox.show();
                $stockSpan.text(cantidadDisponible);
                
                if (cantidadSolicitada > cantidadDisponible) {
                    $alertaStock.show();
                } else {
                    $alertaStock.hide();
                }
            } else {
                $infoBox.hide();
                $alertaStock.hide();
            }
        }
        
        $productoSelect.on('change', actualizarInfo);
        $cantidadInput.on('input', actualizarInfo);
        
        // Inicializar
        actualizarInfo();
    });
    </script>
    <?php
}

/**
 * Guardar producto asociado al envío
 */
add_action('save_post_wpcargo_shipment', 'merc_guardar_envio_producto', 10, 2);
function merc_guardar_envio_producto($post_id, $post) {
    // Evitar autoguardado primero
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Verificar que el usuario esté logueado
    if (!is_user_logged_in()) {
        error_log("⚠️ Usuario no logueado en merc_guardar_envio_producto");
        return;
    }
    
    // Verificar permisos: permitir si es admin, editor, o el autor del envío
    $current_user_id = get_current_user_id();
    $post_author_id = get_post_field('post_author', $post_id);
    
    if (!current_user_can('edit_others_posts') && $current_user_id != $post_author_id) {
        error_log("⚠️ Usuario sin permisos en merc_guardar_envio_producto - User: {$current_user_id}, Author: {$post_author_id}");
        return;
    }
    
    // Verificar nonce SI viene del formulario frontend - pero permitir que se procese sin nonce si viene del admin
    $tiene_nonce = isset($_POST['merc_envio_producto_nonce']);
    if ($tiene_nonce && !wp_verify_nonce($_POST['merc_envio_producto_nonce'], 'merc_envio_producto_guardar')) {
        error_log("⚠️ Nonce inválido en merc_guardar_envio_producto");
        return;
    }
    
    error_log("✅ Iniciando guardado de producto para envío #{$post_id}");
    
    $producto_id = isset($_POST['merc_producto_id']) ? intval($_POST['merc_producto_id']) : 0;
    $cantidad = isset($_POST['merc_producto_cantidad']) ? intval($_POST['merc_producto_cantidad']) : 1;
    
    // Obtener tipo de envío: primero desde REQUEST (durante la creación), luego desde meta (durante edición)
    $tipo_envio = '';
    if (isset($_REQUEST['type'])) {
        $tipo_envio = strtolower(sanitize_text_field($_REQUEST['type']));
        error_log("   📝 Tipo obtenido desde REQUEST[type]: {$tipo_envio}");
    } else {
        $tipo_envio = strtolower(get_post_meta($post_id, 'wpcargo_type_of_shipment', true));
        error_log("   📝 Tipo obtenido desde meta: {$tipo_envio}");
    }
    
    $es_full_fitment = (strpos($tipo_envio, 'full') !== false || strpos($tipo_envio, 'fitment') !== false || $tipo_envio === 'full_fitment');
    
    error_log("📦 Datos recibidos - Producto ID: {$producto_id}, Cantidad: {$cantidad}, Tipo: '{$tipo_envio}', Es Full Fitment: " . ($es_full_fitment ? 'SI' : 'NO'));
    
    if ($producto_id > 0) {
        // Obtener cantidad anterior si existe
        $producto_anterior = get_post_meta($post_id, '_merc_producto_id', true);
        $cantidad_anterior = get_post_meta($post_id, '_merc_producto_cantidad', true);

        // Si había unidades previas asignadas, desasignarlas siempre que existan
        $prev_units = get_post_meta($post_id, '_merc_producto_unidades', true);
        if (!empty($prev_units) && is_array($prev_units)) {
            merc_unassign_stock_units($prev_units);
            delete_post_meta($post_id, '_merc_producto_unidades');
        }

        // Si el envío es tipo FULL/FULL_FITMENT entonces asignar unidades al envío
        if ($es_full_fitment) {
            // Verificar stock disponible (unidades disponibles)
            $stock_disponible = merc_get_product_stock($producto_id);
            $stock_disponible = intval($stock_disponible);

            if ($cantidad > $stock_disponible) {
                // No hay suficiente stock
                error_log("⚠️ Stock insuficiente - Producto #{$producto_id}: Solicitado {$cantidad}, Disponible {$stock_disponible}");
                return;
            }

            // Intentar asignar unidades y guardarlas en meta del envío
            $assigned_units = merc_assign_stock_units($producto_id, $cantidad, $post_id);
            if ($assigned_units === false || count($assigned_units) < $cantidad) {
                error_log("⚠️ Error asignando unidades - Producto #{$producto_id}");
                return;
            }

            // Guardar producto y cantidad (meta del envío) y las unidades asignadas
            update_post_meta($post_id, '_merc_producto_id', $producto_id);
            update_post_meta($post_id, '_merc_producto_cantidad', $cantidad);
            update_post_meta($post_id, '_merc_producto_unidades', $assigned_units);

            // Actualizar estado del producto a 'asignado' (si corresponde)
            update_post_meta($producto_id, '_merc_producto_estado', 'asignado');

            // Guardar motorizado en el producto (si está disponible)
            $motorizado = get_post_meta($post_id, 'wpcargo_driver', true);
            if (!empty($motorizado)) {
                update_post_meta($producto_id, '_merc_producto_motorizado', $motorizado);
            }

            error_log("📦 Unidades asignadas al envío #{$post_id}: " . implode(',', $assigned_units));
        } else {
            // Para envíos NO full_fitment, no asignar unidades: solo registrar el producto seleccionado y cantidad en el envío
            update_post_meta($post_id, '_merc_producto_id', $producto_id);
            update_post_meta($post_id, '_merc_producto_cantidad', $cantidad);
            // No guardar _merc_producto_unidades ni alterar estados de stock
            update_post_meta($producto_id, '_merc_producto_estado', 'sin_asignar');
            error_log("ℹ️ Envío #{$post_id} NO es full_fitment - producto registrado sin asignar unidades");
        }
    } else {
        // Si no hay producto, devolver stock del anterior si existía
        $producto_anterior = get_post_meta($post_id, '_merc_producto_id', true);
        $prev_units = get_post_meta($post_id, '_merc_producto_unidades', true);
        if (!empty($prev_units) && is_array($prev_units)) {
            merc_unassign_stock_units($prev_units);
            delete_post_meta($post_id, '_merc_producto_unidades');
        }

        if ($producto_anterior) {
            // Restaurar estado a 'sin_asignar' y limpiar motorizado
            update_post_meta($producto_anterior, '_merc_producto_estado', 'sin_asignar');
            update_post_meta($producto_anterior, '_merc_producto_motorizado', '-');
        }

        delete_post_meta($post_id, '_merc_producto_id');
        delete_post_meta($post_id, '_merc_producto_cantidad');
    }
}

/**
 * Actualizar estado del producto cuando el envío cambia a "Delivered"
 */
add_action('transition_post_status', 'merc_actualizar_estado_producto_entregado', 10, 3);
function merc_actualizar_estado_producto_entregado($new_status, $old_status, $post) {
    if ($post->post_type != 'wpcargo_shipment') {
        return;
    }
    
    // Verificar si el estado cambió a algún estado de entrega
    $estado_envio = get_post_meta($post->ID, 'wpcargo_status', true);
    
    if ($estado_envio == 'Delivered' || $estado_envio == 'delivered') {
        $producto_id = get_post_meta($post->ID, '_merc_producto_id', true);
        $units = get_post_meta($post->ID, '_merc_producto_unidades', true);
        if (!empty($units) && is_array($units)) {
            merc_mark_units_delivered($units);
            error_log("✅ Unidades marcadas como entregadas para Envío #{$post->ID}: " . implode(',', $units));
        }

        // Si todas las unidades del producto están entregadas, marcar producto como entregado
        if ($producto_id) {
            global $wpdb;
            $table = merc_get_stock_table_name();
            $total = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d", $producto_id)));
            $delivered = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status = 'delivered'", $producto_id)));
            if ($total > 0 && $delivered === $total) {
                update_post_meta($producto_id, '_merc_producto_estado', 'entregado');
            }
        }
    }
}

// Detectar envíos ANULADOS para crear producto en almacén
add_action('transition_post_status', 'merc_handle_shipment_cancelled_modal', 20, 3);
function merc_handle_shipment_cancelled_modal($new_status, $old_status, $post) {
    if ($post->post_type !== 'wpcargo_shipment') return;

    $estado_envio = get_post_meta($post->ID, 'wpcargo_status', true);
    if (empty($estado_envio)) return;

    $estado_lower = strtolower($estado_envio);
    if ($estado_lower === 'anulado' || $estado_lower === 'cancelled' || $estado_lower === 'cancel') {
        // Comprobar tipo de envío
        $tipo = strtolower(get_post_meta($post->ID, 'wpcargo_type_of_shipment', true));
        // Si es normal/express, mostrar modal para crear producto en almacén (no ligado)
        if ($tipo === 'normal' || $tipo === 'express' || strpos($tipo,'normal') !== false || strpos($tipo,'express') !== false) {
            update_post_meta($post->ID, '_merc_needs_store', 1);
            error_log("🔔 Envío #{$post->ID} anulado (tipo: {$tipo}) — marcar para crear producto en almacén");
        }

        // Si es full_fitment, restaurar unidades asignadas y limpiar metas (se liberan al estar anulado)
        if (strpos($tipo, 'full') !== false || strpos($tipo, 'fitment') !== false || $tipo === 'full_fitment') {
            $units = get_post_meta($post->ID, '_merc_producto_unidades', true);
            $producto_id = get_post_meta($post->ID, '_merc_producto_id', true);
            if (!empty($units) && is_array($units)) {
                merc_unassign_stock_units($units);
                delete_post_meta($post->ID, '_merc_producto_unidades');
                delete_post_meta($post->ID, '_merc_producto_id');
                delete_post_meta($post->ID, '_merc_producto_cantidad');
                error_log("🔁 Envío full_fitment anulado (#{$post->ID}) - Unidades restauradas: " . implode(',', $units));

                // Si el producto ya no tiene unidades asignadas, marcar como sin_asignar
                if ($producto_id) {
                    global $wpdb;
                    $table = merc_get_stock_table_name();
                    $assigned_remaining = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status = 'assigned'", $producto_id)));
                    if ($assigned_remaining === 0) {
                        update_post_meta($producto_id, '_merc_producto_estado', 'sin_asignar');
                        update_post_meta($producto_id, '_merc_producto_motorizado', '-');
                        error_log("ℹ️ Producto #{$producto_id} ahora sin asignaciones restantes - estado actualizado a sin_asignar");
                    }
                }
            }
        }
    }
}

// Mostrar modal en admin edit screen si el envío tiene flag _merc_needs_store
add_action('admin_footer-post.php', 'merc_admin_modal_for_cancelled_shipment');
function merc_admin_modal_for_cancelled_shipment() {
    global $post;
    if (!isset($post) || $post->post_type !== 'wpcargo_shipment') return;
    if (!current_user_can('manage_options') && !current_user_can('edit_posts')) return;

    $needs = get_post_meta($post->ID, '_merc_needs_store', true);
    if (empty($needs)) return;

    // Sólo mostrar una vez
    delete_post_meta($post->ID, '_merc_needs_store');

    $nonce = wp_create_nonce('merc_almacen');
    ?>
    <div id="merc-cancelled-modal" style="display:none;">
        <div class="merc-modal-inner" style="max-width:720px;background:#fff;padding:18px;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <h2>Crear producto en almacén desde envío anulado</h2>
            <p>Este envío fue anulado. Puedes crear un producto en el almacén para mapear el paquete.</p>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;">
                <input id="merc_cancel_product_name" placeholder="Nombre del producto" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:6px;" value="<?php echo esc_attr(get_the_title($post->ID)); ?>">
                <input id="merc_cancel_product_sku" placeholder="Código (SKU)" style="width:200px;padding:8px;border:1px solid #ddd;border-radius:6px;" value="SHIP-<?php echo intval($post->ID); ?>">
                <input id="merc_cancel_product_qty" type="number" min="1" value="1" style="width:120px;padding:8px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <div style="margin-top:14px;text-align:right;">
                <button id="merc_cancel_product_skip" class="button">Omitir</button>
                <button id="merc_cancel_product_create" class="button button-primary">Crear en almacén</button>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($){
        var modal = $('#merc-cancelled-modal');
        modal.css({position:'fixed',left:'50%',top:'20%',transform:'translateX(-50%)',zIndex:999999,display:'block'});

        $('#merc_cancel_product_skip').on('click', function(){ modal.remove(); });
        $('#merc_cancel_product_create').on('click', function(){
            var name = $('#merc_cancel_product_name').val();
            var sku = $('#merc_cancel_product_sku').val();
            var qty = parseInt($('#merc_cancel_product_qty').val()) || 1;
            $.post(ajaxurl, { action: 'merc_create_product_from_shipment', shipment_id: <?php echo intval($post->ID); ?>, name: name, sku: sku, qty: qty, nonce: '<?php echo $nonce; ?>' }, function(r){
                    if (r.success) {
                    alert('Producto creado en almacén (no ligado al envío).');
                    modal.remove();
                    location.reload();
                } else {
                    alert('Error: ' + r.data);
                }
            });
        });
    });
    </script>
    <?php
}

// AJAX: crear producto desde envío anulado
add_action('wp_ajax_merc_create_product_from_shipment', 'merc_create_product_from_shipment_ajax');
function merc_create_product_from_shipment_ajax() {
    check_ajax_referer('merc_almacen', 'nonce');
    $current_user = wp_get_current_user();
    // Permitir administradores y también al rol cliente de WPCargo
    if ( ! ( current_user_can('manage_options') || in_array('wpcargo_client', (array) $current_user->roles, true) ) ) {
        wp_send_json_error('Sin permisos');
    }
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $sku = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '';
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    $cliente_asignado = isset($_POST['cliente_asignado']) ? intval($_POST['cliente_asignado']) : 0;
    if (!$shipment_id || empty($name)) wp_send_json_error('Parámetros inválidos');

    // Crear post tipo merc_producto
    $post_id = wp_insert_post(array('post_title' => $name, 'post_type' => 'merc_producto', 'post_status' => 'publish'));
    if (is_wp_error($post_id) || !$post_id) wp_send_json_error('Error creando producto');

    // Asignar cliente: usar autor del envío si existe
    $author = get_post_field('post_author', $shipment_id);
    if ($cliente_asignado && $cliente_asignado > 0) {
        update_post_meta($post_id, '_merc_producto_cliente_asignado', $cliente_asignado);
    } elseif ($author) {
        update_post_meta($post_id, '_merc_producto_cliente_asignado', intval($author));
    }

    // Crear unidades (quedan en status 'available' — NO se asignan al envío)
    $res = merc_set_product_stock($post_id, $qty, $sku);
    if (is_wp_error($res)) wp_send_json_error($res->get_error_message());

    // No ligar unidades ni producto al envío: se registran en almacén y quedan disponibles
    wp_send_json_success(array('product_id' => $post_id));
}

/**
 * Restaurar stock cuando un envío es eliminado
 */
add_action('before_delete_post', 'merc_restaurar_stock_envio_eliminado');
add_action('wp_trash_post', 'merc_restaurar_stock_envio_eliminado');
function merc_restaurar_stock_envio_eliminado($post_id) {
    if (get_post_type($post_id) != 'wpcargo_shipment') {
        return;
    }
    
    $producto_id = get_post_meta($post_id, '_merc_producto_id', true);
    $cantidad = get_post_meta($post_id, '_merc_producto_cantidad', true);
    
    if ($producto_id && $cantidad) {
        // Si existen unidades asignadas al envío, desasignarlas
        $units = get_post_meta($post_id, '_merc_producto_unidades', true);
        if (!empty($units) && is_array($units)) {
            merc_unassign_stock_units($units);
            delete_post_meta($post_id, '_merc_producto_unidades');
        }

        // Restaurar estado a 'sin_asignar' y limpiar motorizado
        update_post_meta($producto_id, '_merc_producto_estado', 'sin_asignar');
        update_post_meta($producto_id, '_merc_producto_motorizado', '-');
        
        error_log("♻️ Unidades desasignadas y estado restaurado para Producto #{$producto_id} (Envío #{$post_id} eliminado/papelera)");
    }
}

/**
 * Mostrar columna de producto en listado de envíos
 */
add_filter('manage_wpcargo_shipment_posts_columns', 'merc_envio_agregar_columna_producto');
function merc_envio_agregar_columna_producto($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        // Insertar después de la columna de título
        if ($key == 'title') {
            $new_columns['producto'] = 'Producto';
        }
    }
    return $new_columns;
}

add_action('manage_wpcargo_shipment_posts_custom_column', 'merc_envio_columna_producto_contenido', 10, 2);
function merc_envio_columna_producto_contenido($column, $post_id) {
    if ($column == 'producto') {
        $producto_id = get_post_meta($post_id, '_merc_producto_id', true);
        $cantidad = get_post_meta($post_id, '_merc_producto_cantidad', true);
        
        if ($producto_id) {
            $producto = get_post($producto_id);
            if ($producto) {
                echo '<strong>' . esc_html($producto->post_title) . '</strong><br>';
                echo '<small>Cantidad: ' . intval($cantidad) . '</small>';
            } else {
                echo '<span style="color: #999;">Producto no encontrado</span>';
            }
        } else {
            echo '<span style="color: #999;">Sin producto</span>';
        }
    }
}

// ===============================================
// SHORTCODE: ALMACÉN DE PRODUCTOS (FRONTEND)
// ===============================================

add_shortcode('merc_almacen_productos', 'merc_almacen_frontend_shortcode');
function merc_almacen_frontend_shortcode() {
    $current_user = wp_get_current_user();
    $is_admin = current_user_can('manage_options');
    $is_client = in_array('wpcargo_client', $current_user->roles);
    
    // Permitir acceso a administradores y clientes
    if (!is_user_logged_in() || (!$is_admin && !$is_client)) {
        return '<div style="padding:40px;text-align:center;background:#fff3cd;border:2px solid #ffc107;border-radius:8px;"><h3 style="color:#856404;">⚠️ Acceso Restringido</h3><p>No tienes permisos para ver esta sección.</p></div>';
    }
    
    ob_start();
    ?>
    <div class="almacen-container">
        <div class="almacen-header">
            <h2>📦 Almacén de Productos</h2>
            <?php if ($is_admin): ?>
            <button id="btn-nuevo" class="btn-primary">➕ Nuevo Producto</button>
            <?php endif; ?>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card card-total">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <span class="stat-label">Total de Productos</span>
                    <span class="stat-value" id="stat-total">0</span>
                </div>
            </div>
            <div class="stat-card card-asignados">
                <div class="stat-icon">🚚</div>
                <div class="stat-content">
                    <span class="stat-label">Productos Asignados</span>
                    <span class="stat-value" id="stat-asignados">0</span>
                </div>
            </div>
            <div class="stat-card card-sin-asignar">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <span class="stat-label">Productos Sin Asignar</span>
                    <span class="stat-value" id="stat-sin-asignar">0</span>
                </div>
            </div>
            <div class="stat-card card-entregados">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <span class="stat-label">Productos Entregados</span>
                    <span class="stat-value" id="stat-entregados">0</span>
                </div>
            </div>
        </div>
        
        <div class="filtros-bar">
            <input type="text" id="input-buscar" placeholder="🔍 Buscar producto..." class="input-buscar">
            <select id="select-filtro" class="select-filtro">
                <option value="">Todos</option>
                <option value="sin_asignar">Sin Asignar</option>
                <option value="asignado">Asignados</option>
                <option value="entregado">Entregados</option>
            </select>
        </div>
        
        <div id="lista-productos" class="lista-productos">
            <div class="tabla-header">
                <div>Producto</div>
                <div>Stock</div>
                <div>Creado</div>
                <div>Modificado</div>
                <div>Estado</div>
                <div>Motorizado</div>
                <div>Cliente</div>
                <div>Acciones</div>
            </div>
            <div class="loading">Cargando...</div>
        </div>
    </div>
    
    <div id="modal" class="modal" style="display:none;">
        <div class="modal-backdrop"></div>
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modal-title">Nuevo Producto</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="form-producto">
                <input type="hidden" id="prod-id">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" id="prod-nombre" required placeholder="Nombre del producto">
                </div>
                <div class="form-group">
                    <label>Código de Barras <small>(opcional)</small></label>
                    <input type="text" id="prod-codigo-barras" placeholder="Escanea o ingresa el código">
                    <small style="color: #7f8c8d; font-size: 12px;">📦 Puedes dejar este campo vacío si el producto no tiene código de barras</small>
                </div>
                <div class="form-group">
                    <label>Cliente Asignado *</label>
                    <select id="prod-cliente-asignado" required style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 4px !important; font-size: 14px !important; display: block !important; visibility: visible !important; opacity: 1 !important;">
                        <option value="">-- Selecciona un cliente --</option>
                        <?php
                        $clientes_frontend = get_users(array('role' => 'wpcargo_client'));
                        foreach ($clientes_frontend as $cliente):
                        $nombre_completo = trim($cliente->first_name . ' ' . $cliente->last_name);
                        $nombre_mostrar = !empty($nombre_completo) ? $nombre_completo : $cliente->display_name;
                        ?>
                            <option value="<?php echo $cliente->ID; ?>">
                                <?php echo esc_html($nombre_mostrar); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #7f8c8d; font-size: 12px;">👤 Selecciona el cliente al que pertenece este producto</small>
                </div>
                <div class="form-group">
                    <label>Cantidad *</label>
                    <input type="number" id="prod-cantidad" min="0" required placeholder="0">
                </div>
                <div class="form-group">
                    <label>Peso (kg) <small>(opcional)</small></label>
                    <input type="number" id="prod-peso" min="0" step="0.01" placeholder="0.00">
                    <small style="color: #7f8c8d; font-size: 12px;">⚖️ Peso del producto en kilogramos</small>
                </div>
                <div class="form-group">
                    <label>Tipo de Medida <small>(opcional)</small></label>
                    <select id="prod-tipo-medida" style="width: 100% !important; padding: 10px !important; border: 2px solid #dfe6e9 !important; border-radius: 6px !important; font-size: 15px !important; display: block !important; visibility: visible !important; opacity: 1 !important;">
                        <option value="">-- Seleccionar --</option>
                        <option value="talla">Talla</option>
                        <option value="volumen">Volumen</option>
                        <option value="dimensiones">Dimensiones</option>
                        <option value="unidades">Unidades</option>
                        <option value="otro">Otro</option>
                    </select>
                    <small style="color: #7f8c8d; font-size: 12px;">📏 Tipo de medida del producto</small>
                </div>
                <div class="form-group">
                    <label>Valor de Medida <small>(opcional)</small></label>
                    <input type="text" id="prod-valor-medida" placeholder="Ej: S, M, L, XL o 100ml">
                    <small style="color: #7f8c8d; font-size: 12px;">📐 Valor específico de la medida (talla, volumen, etc.)</small>
                </div>
                <div class="form-group">
                    <label>Dimensiones (cm) <small>(opcional)</small></label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="number" id="prod-largo" min="0" step="0.1" placeholder="Largo" style="flex: 1;">
                        <span style="color: #7f8c8d;">×</span>
                        <input type="number" id="prod-ancho" min="0" step="0.1" placeholder="Ancho" style="flex: 1;">
                        <span style="color: #7f8c8d;">×</span>
                        <input type="number" id="prod-alto" min="0" step="0.1" placeholder="Alto" style="flex: 1;">
                    </div>
                    <small style="color: #7f8c8d; font-size: 12px;">📦 Dimensiones: Largo × Ancho × Alto en centímetros</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary modal-close">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .almacen-container { max-width: 1400px; margin: 0 auto; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .almacen-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
    .almacen-header h2 { margin: 0; font-size: 32px; color: #2c3e50; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; padding: 25px; border-radius: 12px; display: flex; align-items: center; gap: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
    .card-total { border-left: 5px solid #3498db; }
    .card-asignados { border-left: 5px solid #f39c12; }
    .card-sin-asignar { border-left: 5px solid #95a5a6; }
    .card-entregados { border-left: 5px solid #2ecc71; }
    .stat-icon { font-size: 42px; }
    .stat-content { display: flex; flex-direction: column; }
    .stat-label { font-size: 14px; color: #7f8c8d; }
    .stat-value { font-size: 32px; font-weight: bold; color: #2c3e50; }
    .filtros-bar { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
    .input-buscar { flex: 1; min-width: 300px; padding: 12px 20px; border: 2px solid #dfe6e9; border-radius: 8px; font-size: 15px; }
    .input-buscar:focus { outline: none; border-color: #3498db; }
    .select-filtro { padding: 12px 20px; border: 2px solid #dfe6e9; border-radius: 8px; font-size: 15px; background: white; }
    .lista-productos { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
    .tabla-header { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 150px; gap: 15px; padding: 15px 25px; background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 700; font-size: 13px; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; }
    .producto-item { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 150px; gap: 15px; padding: 20px 25px; border-bottom: 1px solid #ecf0f1; align-items: center; }
    <?php if (!$is_admin): ?>
    /* Vista de solo lectura para clientes */
    .tabla-header { grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr; }
    .producto-item { grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr; }
    <?php endif; ?>
    .producto-item:hover { background: #f8f9fa; }
    .prod-nombre { font-size: 16px; font-weight: 600; color: #2c3e50; }
    .prod-cantidad { text-align: center; }
    span.badge.badge-bajo { display: inline-block; padding: 8px 20px; border-radius: 20px; font-weight: bold; font-size: 16px; }
    .badge-disponible { background: #d4edda; color: #155724; }
    .badge-bajo { background: #fff3cd; color: #856404 !important; }
    .badge-sin { background: #f8d7da; color: #721c24; }
    .badge-estado { display: inline-block; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    .badge-sin-asignar { background: #e9ecef; color: #495057; }
    .badge-asignado { background: #d1ecf1; color: #0c5460; }
    .badge-entregado { background: #d4edda; color: #155724; }
    .prod-fecha { font-size: 13px; color: #7f8c8d; }
    .prod-acciones { display: flex; gap: 10px; }
    .btn-primary, .btn-secondary, .btn-edit, .btn-delete { border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
    .btn-primary { background: #3498db; color: white; box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3); padding: 10px 24px; font-size: 14px; }
    .btn-primary:hover { background: #2980b9; box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4); }
    .btn-secondary { background: #6c757d; color: white; box-shadow: 0 2px 6px rgba(108, 117, 125, 0.3); padding: 10px 24px; font-size: 14px; }
    .btn-secondary:hover { background: #5a6268; box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4); }
    .btn-edit { background: #f39c12; color: white; padding: 8px 16px; font-size: 13px; }
    .btn-edit:hover { background: #e67e22; }
    .btn-delete { background: #e74c3c; color: white; padding: 8px 16px; font-size: 13px; }
    .btn-delete:hover { background: #c0392b; }
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999999; display: flex; align-items: center; justify-content: center; }
    .modal-backdrop { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
    .modal-box { position: relative; background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #ecf0f1; flex-shrink: 0; }
    .modal-header h3 { margin: 0; font-size: 20px; color: #2c3e50; }
    .modal-header .modal-close { background: none; border: none; font-size: 24px; color: #7f8c8d; cursor: pointer; padding: 0; line-height: 1; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; }
    .modal-header .modal-close:hover { color: #2c3e50; }
    #form-producto { overflow-y: auto; flex: 1; }
    .form-group { padding: 12px 20px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px; }
    .form-group input { width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; }
    .form-group input:focus { outline: none; border-color: #3498db; }
    .form-group small { display: block; margin-top: 3px; }
    .modal-footer { display: flex; gap: 10px; justify-content: flex-end; padding: 15px 20px; border-top: 1px solid #ecf0f1; background: #f8f9fa; flex-shrink: 0; }
    .loading { text-align: center; padding: 60px 20px; color: #7f8c8d; }
    .empty { text-align: center; padding: 60px 20px; }
    .empty-icon { font-size: 64px; margin-bottom: 20px; }
    @media (max-width: 768px) { .producto-item { grid-template-columns: 1fr; gap: 10px; } }
    </style>
    
    <script>
    let usuariosCache = {};
    jQuery(document).ready(function($) {
        let productos = [];
        const esAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
        
        cargar();
        if (esAdmin) {
            $('#btn-nuevo').on('click', () => abrir());
            $('.modal-close, .modal-backdrop').on('click', () => cerrar());
            $('#form-producto').on('submit', guardar);
        }
        $('#input-buscar').on('input', filtrar);
        $('#select-filtro').on('change', filtrar);
        
        function cargar() {
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                action: 'merc_listar_productos',
                nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>'
            }, function(r) {
                if (r.success) {
                    productos = r.data;
                    // Precargar usuarios únicos de los productos para mapear IDs a nombres
                    const userIds = []; 
                    productos.forEach(p => { if (p.cliente_asignado && !usuariosCache[p.cliente_asignado]) { userIds.push(p.cliente_asignado); } });
                    if (userIds.length > 0) {
                        $.ajax({
                            url: '<?php echo admin_url("admin-ajax.php"); ?>',
                            type: 'POST',
                            data: { action: 'merc_get_usuarios_info', user_ids: userIds, nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>' },
                            success: function(resp) { if (resp.success) { usuariosCache = $.extend({}, usuariosCache, resp.data); } renderizar(); stats(); },
                            error: function() { renderizar(); stats(); }
                        });
                    } else {
                        renderizar();
                        stats();
                    }
                }
            });
        }
        
        function renderizar(lista = null) {
            const items = lista || productos;
            const $l = $('#lista-productos');
            
            if (items.length === 0) {
                $l.html('<div class="empty"><div class="empty-icon">📦</div><h3>No hay productos</h3></div>');
                return;
            }
            
            let html = '<div class="tabla-header">';
            html += '<div>Producto</div>';
            html += '<div style="text-align:center;">Cantidad</div>';
            html += '<div>Fecha Creación</div>';
            html += '<div>Última Modificación</div>';
            html += '<div style="text-align:center;">Estado</div>';
            html += '<div style="text-align:center;">Motorizado</div>';
            html += '<div style="text-align:center;">Cliente</div>';
            if (esAdmin) {
                html += '<div style="text-align:center;">Acciones</div>';
            }
            html += '</div>';
            
            items.forEach(p => {
                const c = parseInt(p.cantidad);
                const b = c === 0 ? 'sin' : c < 10 ? 'bajo' : 'disponible';
                
                // Estado badge
                let estadoBadge = '';
                if (p.estado === 'asignado') {
                    estadoBadge = '<span class="badge-estado badge-asignado">🚚 Asignado</span>';
                } else if (p.estado === 'entregado') {
                    estadoBadge = '<span class="badge-estado badge-entregado">✅ Entregado</span>';
                } else {
                    estadoBadge = '<span class="badge-estado badge-sin-asignar">📦 Sin Asignar</span>';
                }
                
                // Motorizado
                const motorizado = p.motorizado && p.motorizado !== '-' ? p.motorizado : '-';
                
                // Obtener nombre del cliente asignado
                let clienteNombre = '-';
                if (p.cliente_asignado && p.cliente_asignado > 0) {
                    const usuario = usuariosCache && usuariosCache[p.cliente_asignado];
                    clienteNombre = usuario ? usuario.display_name : 'Usuario #' + p.cliente_asignado;
                }
                
                html += `<div class="producto-item">
                    <div class="prod-nombre">📦 ${p.nombre}</div>
                    <div class="prod-cantidad"><button class="btn-stock" data-product-id="${p.id}" title="Ver unidades"><span class="badge badge-${b}">${c}</span></button></div>
                    <div class="prod-fecha">${p.fecha_creacion}</div>
                    <div class="prod-fecha">${p.fecha_modificacion}</div>
                    <div style="text-align:center;">${estadoBadge}</div>
                    <div style="text-align:center; color: #2c3e50; font-weight: 600;">${motorizado}</div>
                    <div style="text-align:center; color: #2c3e50; font-weight: 600;">${clienteNombre}</div>`;
                
                if (esAdmin) {
                    html += `<div class="prod-acciones">
                        <button class="btn-edit" onclick="editar(${p.id})">✏️</button>
                        <button class="btn-delete" onclick="eliminar(${p.id},'${p.nombre}')">🗑️</button>
                    </div>`;
                }
                
                html += `</div>`;
            });
            $l.html(html);
        }
        
        function stats() {
            const t = productos.length;
            const sin_asignar = productos.filter(p => !p.estado || p.estado === 'sin_asignar').length;
            const asignados = productos.filter(p => p.estado === 'asignado').length;
            const entregados = productos.filter(p => p.estado === 'entregado').length;
            $('#stat-total').text(t);
            $('#stat-sin-asignar').text(sin_asignar);
            $('#stat-asignados').text(asignados);
            $('#stat-entregados').text(entregados);
        }
        
        function filtrar() {
            const q = $('#input-buscar').val().toLowerCase();
            const f = $('#select-filtro').val();
            const r = productos.filter(p => {
                const n = p.nombre.toLowerCase().includes(q);
                let ok = true;
                if (f === 'sin_asignar') ok = !p.estado || p.estado === 'sin_asignar';
                else if (f === 'asignado') ok = p.estado === 'asignado';
                else if (f === 'entregado') ok = p.estado === 'entregado';
                return n && ok;
            });
            renderizar(r);
        }
        
        window.editar = function(id) {
            const p = productos.find(x => x.id == id);
            if (!p) return;
            // Precargar usuario si no está en cache
            if (p.cliente_asignado && !usuariosCache[p.cliente_asignado]) {
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: { action: 'merc_get_usuarios_info', user_ids: [p.cliente_asignado], nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>' },
                    success: function(resp) { if (resp.success) { usuariosCache = $.extend({}, usuariosCache, resp.data); } completeFormulario(p); },
                    error: function() { completeFormulario(p); }
                });
            } else {
                completeFormulario(p);
            }
        };
        
        function completeFormulario(p) {
            $('#prod-id').val(p.id);
            $('#prod-nombre').val(p.nombre);
            $('#prod-codigo-barras').val(p.codigo_barras || '');
            $('#prod-cliente-asignado').val(p.cliente_asignado || '');
            $('#prod-cantidad').val(p.cantidad);
            $('#prod-peso').val(p.peso || '');
            $('#prod-tipo-medida').val(p.tipo_medida || '');
            $('#prod-valor-medida').val(p.valor_medida || '');
            $('#prod-largo').val(p.largo || '');
            $('#prod-ancho').val(p.ancho || '');
            $('#prod-alto').val(p.alto || '');
            $('#modal-title').text('Editar Producto');
            $('#modal').fadeIn(200);
        }
        
        window.eliminar = function(id, n) {
            if (!confirm(`¿Eliminar "${n}"?`)) return;
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                action: 'merc_eliminar_producto',
                id: id,
                nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>'
            }, function(r) {
                notif(r.success ? '✓ Eliminado' : '✗ ' + r.data, r.success ? 's' : 'e');
                if (r.success) cargar();
            });
        };
        
        function abrir() {
            $('#form-producto')[0].reset();
            $('#prod-id').val('');
            $('#modal-title').text('Nuevo Producto');
            $('#modal').fadeIn(200);
        }
        
        function cerrar() {
            $('#modal').fadeOut(200);
        }
        
        function guardar(e) {
            e.preventDefault();
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                action: 'merc_guardar_producto',
                id: $('#prod-id').val(),
                nombre: $('#prod-nombre').val(),
                codigo_barras: $('#prod-codigo-barras').val(),
                cliente_asignado: $('#prod-cliente-asignado').val(),
                cantidad: $('#prod-cantidad').val(),
                peso: $('#prod-peso').val(),
                tipo_medida: $('#prod-tipo-medida').val(),
                valor_medida: $('#prod-valor-medida').val(),
                largo: $('#prod-largo').val(),
                ancho: $('#prod-ancho').val(),
                alto: $('#prod-alto').val(),
                nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>'
            }, function(r) {
                notif(r.success ? '✓ Guardado' : '✗ ' + r.data, r.success ? 's' : 'e');
                if (r.success) { cerrar(); cargar(); }
            });
        }
        
        function notif(m, t) {
            const c = t === 's' ? '#2ecc71' : '#e74c3c';
            const $n = $(`<div style="position:fixed;top:20px;right:20px;background:${c};color:white;padding:15px 25px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.3);z-index:9999999;">${m}</div>`);
            $('body').append($n);
            setTimeout(() => $n.fadeOut(300, function() { $(this).remove(); }), 3000);
        }
    });
    </script>
    <!-- Modal: Unidades de Stock -->
    <div id="merc-units-modal" style="display:none; position:fixed; top:20px; left:50%; transform:translateX(-50%); width:90%; max-width:900px; z-index:999999;">
        <div style="background:#fff; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.2); overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="merc-units-modal-title" style="margin:0;font-size:18px;">Unidades</h3>
                <button id="merc-units-modal-close" style="background:transparent;border:0;font-size:18px;">✕</button>
            </div>
            <div style="padding:12px 16px; max-height:60vh; overflow:auto;">
                <table id="merc-units-table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid #eee;"><th>ID</th><th>SKU</th><th>Estado</th><th>Tracking</th><th>Motorizado</th><th>Creado</th><th>Actualizado</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div style="padding:12px 16px; border-top:1px solid #eee; text-align:right; background:#fafafa;">
                <button id="merc-units-modal-ok" style="padding:8px 14px;border-radius:6px;border:0;background:#1976D2;color:#fff;">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        function openUnitsModal(productId, productName) {
            $('#merc-units-modal-title').text('Unidades — ' + (productName||productId));
            $('#merc-units-table tbody').html('<tr><td colspan="7" style="padding:20px;text-align:center;">Cargando...</td></tr>');
            $('#merc-units-modal').fadeIn(150);

            $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                action: 'merc_get_product_units',
                product_id: productId,
                nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>'
            }, function(r){
                if (!r.success) {
                    $('#merc-units-table tbody').html('<tr><td colspan="7" style="padding:20px;text-align:center;color:#e74c3c;">'+(r.data||'Error')+'</td></tr>');
                    return;
                }
                const rows = r.data;
                if (!rows || rows.length === 0) {
                    $('#merc-units-table tbody').html('<tr><td colspan="7" style="padding:20px;text-align:center;">No hay unidades</td></tr>');
                    return;
                }

                let html = '';
                rows.forEach(u => {
                    const estado = u.status;
                    const badge = estado === 'available' ? '📦 Disponible' : (estado === 'assigned' ? '🚚 Asignado' : (estado === 'delivered' ? '✅ Entregado' : estado));
                    let accionesHtml = '';
                    if (<?php echo $is_admin ? 'true' : 'false'; ?>) {
                        if (u.status === 'assigned') {
                            accionesHtml = `<div style="display:flex;gap:6px;">` +
                                `<button class="btn-unit-unassign" data-unit-id="${u.id}" style="padding:6px 8px;border-radius:6px;border:0;background:#f39c12;color:#fff;">Desasignar</button>` +
                                `<button class="btn-unit-delivered" data-unit-id="${u.id}" style="padding:6px 8px;border-radius:6px;border:0;background:#27ae60;color:#fff;">Marcar Entregado</button>` +
                                `</div>`;
                        }
                    }
                    html += `<tr style="border-bottom:1px solid #f1f1f1;"><td style="padding:8px;">${u.id}</td><td style="padding:8px;">${u.sku||'-'}</td><td style="padding:8px;">${badge}</td><td style="padding:8px;"><strong>${u.tracking||'-'}</strong></td><td style="padding:8px;">${u.motorizado||'-'}</td><td style="padding:8px;">${u.created_at||'-'}</td><td style="padding:8px;">${u.updated_at||'-'}</td></tr>`;
                });
                $('#merc-units-table tbody').html(html);
            });
        }

        $(document).on('click', '.btn-stock', function(e){
            const id = $(this).data('product-id');
            const name = $(this).closest('.producto-item').find('.prod-nombre').text().trim();
            openUnitsModal(id, name);
        });

        // Handlers for admin unit actions
        $(document).on('click', '.btn-unit-unassign', function(e){
            e.preventDefault();
            const unitId = $(this).data('unit-id');
            if (!confirm('Desasignar unidad #' + unitId + '?')) return;
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', { action: 'merc_unit_unassign', unit_id: unitId, nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>' }, function(r){
                if (r.success) { notif('✓ Unidad desasignada','s'); $('#merc-units-modal').find('button[data-unit-id="'+unitId+'"]').closest('tr').fadeOut(200, function(){ $(this).remove(); }); }
                else notif('✗ '+(r.data||'Error'),'e');
            });
        });

        $(document).on('click', '.btn-unit-delivered', function(e){
            e.preventDefault();
            const unitId = $(this).data('unit-id');
            if (!confirm('Marcar unidad #' + unitId + ' como entregada?')) return;
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', { action: 'merc_unit_mark_delivered', unit_id: unitId, nonce: '<?php echo wp_create_nonce("merc_almacen"); ?>' }, function(r){
                if (r.success) { notif('✓ Unidad marcada como entregada','s'); $('#merc-units-modal').fadeOut(150); }
                else notif('✗ '+(r.data||'Error'),'e');
            });
        });

        $('#merc-units-modal-close, #merc-units-modal-ok').on('click', function(){ $('#merc-units-modal').fadeOut(150); });
        $(document).on('keyup', function(e){ if (e.key === 'Escape') $('#merc-units-modal').fadeOut(150); });
    });
    </script>
    <?php
    return ob_get_clean();
}

// AJAX: Obtener información de usuarios por IDs
add_action('wp_ajax_merc_get_usuarios_info', 'merc_get_usuarios_info_ajax');
function merc_get_usuarios_info_ajax() {
    check_ajax_referer('merc_almacen', 'nonce');
    
    $user_ids = isset($_POST['user_ids']) ? array_map('intval', (array) $_POST['user_ids']) : array();
    if (empty($user_ids)) wp_send_json_error('Sin IDs de usuario');
    
    $usuarios = array();
    foreach ($user_ids as $uid) {
        $user = get_userdata($uid);
        if ($user) {
            $usuarios[$uid] = array(
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email
            );
        }
    }
    wp_send_json_success($usuarios);
}

// AJAX: Listar productos
add_action('wp_ajax_merc_listar_productos', 'merc_listar_productos_ajax');
function merc_listar_productos_ajax() {
    error_log('🔍 DEBUG: merc_listar_productos_ajax iniciada');
    
    check_ajax_referer('merc_almacen', 'nonce');
    
    // Permitir acceso a administradores y clientes
    $current_user = wp_get_current_user();
    $is_admin = current_user_can('manage_options');
    $is_client = in_array('wpcargo_client', $current_user->roles);
    
    error_log('🔍 DEBUG: Usuario ID: ' . $current_user->ID . ' | Login: ' . $current_user->user_login);
    error_log('🔍 DEBUG: Es Admin: ' . ($is_admin ? 'SÍ' : 'NO') . ' | Es Cliente: ' . ($is_client ? 'SÍ' : 'NO'));
    error_log('🔍 DEBUG: Roles del usuario: ' . json_encode($current_user->roles));
    
    if (!$is_admin && !$is_client) {
        error_log('🔍 DEBUG: Usuario sin permisos');
        wp_send_json_error('Sin permisos');
    }
    
    // Preparar query de productos
    $args = array(
        'post_type' => 'merc_producto',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    // Si es cliente (no admin), filtrar solo productos asignados a él
    if ($is_client && !$is_admin) {
        $args['meta_query'] = array(
            array(
                'key' => '_merc_producto_cliente_asignado',
                'value' => (string) $current_user->ID,
                'compare' => '=',
                'type' => 'CHAR'
            )
        );
        error_log('🔍 DEBUG: Cliente - Filtrando por meta _merc_producto_cliente_asignado: ' . $current_user->ID . ' (como string)');
    } else {
        error_log('🔍 DEBUG: Admin - Mostrando todos los productos');
    }
    
    error_log('🔍 DEBUG: Args de query: ' . json_encode($args));
    
    $ps = get_posts($args);
    error_log('🔍 DEBUG: Productos encontrados por query: ' . count($ps));
    
    $lista = array();
    foreach ($ps as $p) {
        $cliente_meta = get_post_meta($p->ID, '_merc_producto_cliente_asignado', true);
        error_log('🔍 DEBUG: Producto ID: ' . $p->ID . ' | Meta cliente_asignado: "' . $cliente_meta . '" (tipo: ' . gettype($cliente_meta) . ') | Buscando: "' . (string)$current_user->ID . '"');
        
        // Filtro manual para clientes (por si el meta_query no funciona)
        if ($is_client && !$is_admin) {
            if ((string)$cliente_meta !== (string)$current_user->ID) {
                error_log('🔍 DEBUG: ⚠️ SALTANDO producto ' . $p->ID . ' - NO coincide el cliente');
                continue;
            }
        }
        
        error_log('🔍 DEBUG: ✓ Procesando producto - ID: ' . $p->ID . ' | Título: ' . $p->post_title . ' | Author: ' . $p->post_author);
        
        $c = merc_get_product_stock($p->ID);
        $estado = get_post_meta($p->ID, '_merc_producto_estado', true);
        $motorizado = get_post_meta($p->ID, '_merc_producto_motorizado', true);
        
        // Asegurarse de que tengan valores por defecto
        if (empty($estado)) {
            $estado = 'sin_asignar';
            update_post_meta($p->ID, '_merc_producto_estado', 'sin_asignar');
        }
        if (empty($motorizado)) {
            $motorizado = '-';
            update_post_meta($p->ID, '_merc_producto_motorizado', '-');
        }
        
        $lista[] = array(
            'id' => $p->ID,
            'nombre' => $p->post_title,
            'codigo_barras' => get_post_meta($p->ID, '_merc_producto_codigo_barras', true),
            'cliente_asignado' => get_post_meta($p->ID, '_merc_producto_cliente_asignado', true),
            'cantidad' => !empty($c) ? intval($c) : 0,
            'fecha_creacion' => get_the_date('d/m/Y H:i', $p->ID),
            'fecha_modificacion' => get_the_modified_date('d/m/Y H:i', $p->ID),
            'estado' => $estado,
            'motorizado' => $motorizado,
            'peso' => get_post_meta($p->ID, '_merc_producto_peso', true),
            'tipo_medida' => get_post_meta($p->ID, '_merc_producto_tipo_medida', true),
            'valor_medida' => get_post_meta($p->ID, '_merc_producto_valor_medida', true),
            'largo' => get_post_meta($p->ID, '_merc_producto_largo', true),
            'ancho' => get_post_meta($p->ID, '_merc_producto_ancho', true),
            'alto' => get_post_meta($p->ID, '_merc_producto_alto', true)
        );
    }
    error_log('🔍 DEBUG: Total de productos en lista: ' . count($lista));
    error_log('🔍 DEBUG: Enviando al cliente: ' . json_encode($lista));
    wp_send_json_success($lista);
}

// AJAX: Unassign unit
add_action('wp_ajax_merc_unit_unassign', 'merc_unit_unassign_ajax');
function merc_unit_unassign_ajax() {
    check_ajax_referer('merc_almacen', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');
    $unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;
    if (!$unit_id) wp_send_json_error('Unidad inválida');
    $res = merc_unassign_stock_units(array($unit_id));
    if ($res) wp_send_json_success();
    wp_send_json_error('Error al desasignar');
}

// AJAX: Mark unit delivered
add_action('wp_ajax_merc_unit_mark_delivered', 'merc_unit_mark_delivered_ajax');
function merc_unit_mark_delivered_ajax() {
    check_ajax_referer('merc_almacen', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');
    $unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;
    if (!$unit_id) wp_send_json_error('Unidad inválida');
    $res = merc_mark_units_delivered(array($unit_id));
    if ($res) wp_send_json_success();
    wp_send_json_error('Error al marcar entregada');
}

// AJAX: Guardar producto
add_action('wp_ajax_merc_guardar_producto', 'merc_guardar_producto_ajax');
function merc_guardar_producto_ajax() {
    check_ajax_referer('merc_almacen', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $n = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
    $c = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $codigo_barras = isset($_POST['codigo_barras']) ? sanitize_text_field($_POST['codigo_barras']) : '';
    $cliente_asignado = isset($_POST['cliente_asignado']) ? intval($_POST['cliente_asignado']) : 0;
    
    if (empty($n)) wp_send_json_error('Nombre requerido');
    if (empty($cliente_asignado)) wp_send_json_error('Cliente asignado es obligatorio');
    
    $d = array('post_title' => $n, 'post_type' => 'merc_producto', 'post_status' => 'publish');
    if ($id > 0) { $d['ID'] = $id; $r = wp_update_post($d); } else { $r = wp_insert_post($d); }
    
    if (is_wp_error($r)) wp_send_json_error('Error al guardar');
    
    $pid = $id > 0 ? $id : $r;
    // Guardar en la tabla de stock y mantener meta por compatibilidad
    merc_set_product_stock($pid, $c, $codigo_barras);
    update_post_meta($pid, '_merc_producto_cliente_asignado', $cliente_asignado);
    
    // Guardar campos de peso y medidas
    if (isset($_POST['peso'])) {
        update_post_meta($pid, '_merc_producto_peso', floatval($_POST['peso']));
    }
    if (isset($_POST['tipo_medida'])) {
        update_post_meta($pid, '_merc_producto_tipo_medida', sanitize_text_field($_POST['tipo_medida']));
    }
    if (isset($_POST['valor_medida'])) {
        update_post_meta($pid, '_merc_producto_valor_medida', sanitize_text_field($_POST['valor_medida']));
    }
    if (isset($_POST['largo'])) {
        update_post_meta($pid, '_merc_producto_largo', floatval($_POST['largo']));
    }
    if (isset($_POST['ancho'])) {
        update_post_meta($pid, '_merc_producto_ancho', floatval($_POST['ancho']));
    }
    if (isset($_POST['alto'])) {
        update_post_meta($pid, '_merc_producto_alto', floatval($_POST['alto']));
    }
    
    // Si es un producto nuevo, inicializar estado y motorizado
    if ($id == 0) {
        update_post_meta($pid, '_merc_producto_estado', 'sin_asignar');
        update_post_meta($pid, '_merc_producto_motorizado', '-');
    }
    
    // Si viene un shipment_id, vincularlo
    if (isset($_POST['shipment_id']) && intval($_POST['shipment_id']) > 0) {
        $shipment_id = intval($_POST['shipment_id']);
        update_post_meta($pid, '_merc_producto_shipment_origen', $shipment_id);
        error_log("📦 Producto #{$pid} creado desde envío #{$shipment_id}");
    }
    
    wp_send_json_success(array('id' => $pid));
}

// AJAX: Eliminar producto
add_action('wp_ajax_merc_eliminar_producto', 'merc_eliminar_producto_ajax');
function merc_eliminar_producto_ajax() {
    check_ajax_referer('merc_almacen', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Sin permisos');
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (empty($id)) wp_send_json_error('ID inválido');
    
    global $wpdb;
    $table = merc_get_stock_table_name();
    $inuse = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND status != 'available'", $id)));
    if ($inuse > 0) wp_send_json_error('Producto en uso');

    // Borrar filas de unidades (si las hay) antes de eliminar el post
    $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE product_id = %d", $id));

    $r = wp_delete_post($id, true);
    if (!$r) wp_send_json_error('Error al eliminar');
    wp_send_json_success();
}

// Shortcode: Mostrar historial de liquidaciones de un remitente
// Uso: [merc_liquidations_history user_id="123"]
function merc_liquidations_history_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'user_id' => 0,
        'fecha_inicio' => '',
        'fecha_fin' => '',
    ), $atts, 'merc_liquidations_history' );

    $user_id = intval( $atts['user_id'] );
    $fecha_inicio = sanitize_text_field( $atts['fecha_inicio'] );
    $fecha_fin = sanitize_text_field( $atts['fecha_fin'] );
    
    if ( $user_id <= 0 ) {
        $current = wp_get_current_user();
        if ( $current && $current->ID ) {
            $user_id = $current->ID;
        }
    }

    if ( $user_id <= 0 ) {
        return '<p>No se especificó remitente válido.</p>';
    }

    // Permisos: solo admin o el propio remitente pueden ver su historial
    $current = wp_get_current_user();
    if ( ! current_user_can( 'administrator' ) && intval( $current->ID ) !== $user_id ) {
        return '<p>No tienes permisos para ver este historial.</p>';
    }

    $history = get_user_meta( $user_id, 'merc_liquidations', true );
    
    // Filtrar por fecha si se proporcionan
    if ( is_array($history) && ( ! empty($fecha_inicio) || ! empty($fecha_fin) ) ) {
        $history_filtrada = array();
        foreach ( $history as $entry ) {
            $entry_date = isset($entry['date']) ? $entry['date'] : (isset($entry['created']) ? $entry['created'] : '');
            if ( empty($entry_date) ) continue;
            
            // Normalizar a YYYY-MM-DD para comparar
            $entry_date_normalized = $entry_date;
            if ( strpos($entry_date, '/') !== false ) {
                // Formato DD/MM/YYYY
                $parts = explode('/', $entry_date);
                if ( count($parts) === 3 ) {
                    $entry_date_normalized = sprintf('%04d-%02d-%02d', intval($parts[2]), intval($parts[1]), intval($parts[0]));
                }
            } elseif ( strlen($entry_date) === 19 ) {
                // Formato YYYY-MM-DD HH:MM:SS
                $entry_date_normalized = substr($entry_date, 0, 10);
            }
            
            // Aplicar filtro inicio
            if ( ! empty($fecha_inicio) && $entry_date_normalized < $fecha_inicio ) {
                continue;
            }
            
            // Aplicar filtro fin
            if ( ! empty($fecha_fin) && $entry_date_normalized > $fecha_fin ) {
                continue;
            }
            
            $history_filtrada[] = $entry;
        }
        $history = $history_filtrada;
    }

    // Buscar sanción del DÍA (si existe) para este cliente — la penalidad es única por día
    $unpaid_penalties = array();
    $today = date('Y-m-d');
    $pen_query = new WP_Query(array(
        'post_type' => 'merc_penalty',
        'posts_per_page' => 1,
        'meta_query' => array(
            array('key' => 'user_id', 'value' => $user_id),
            array('key' => 'date', 'value' => $today),
        ),
    ));
    if ( $pen_query->have_posts() ) {
        $p = $pen_query->posts[0];
        $status = get_post_meta($p->ID,'status',true);
        $unpaid_penalties[] = array(
            'id' => $p->ID,
            'date' => get_post_meta($p->ID,'date',true),
            'amount' => floatval( get_post_meta($p->ID,'amount',true) ),
            'status' => $status ? $status : 'unpaid',
        );
    }

    // Diagnóstico: contar envíos del día y cuántos están 'no recogido' para explicar por qué no existe sanción
    $today = date('Y-m-d');
    $total_shipments_today = 0;
    $no_recogido_count = 0;
    global $wpdb;
    $ship_ids = $wpdb->get_col( $wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = 'wpcargo_customer_id'
        LEFT JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = 'wpcargo_pickup_date_picker'
        LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
        WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish' AND pm_customer.meta_value = %s AND pm_date.meta_value = %s", $user_id, $today ) );
    if ( is_array($ship_ids) ) {
        $total_shipments_today = count($ship_ids);
        foreach ( $ship_ids as $sid ) {
            $st = get_post_meta($sid, 'wpcargo_status', true);
            if ( function_exists('merc_status_is_no_recogido') ) {
                if ( merc_status_is_no_recogido( $st ) ) $no_recogido_count++;
            } else {
                if ( stripos($st, 'no recogido') !== false || stripos($st,'no_recogido')!==false ) $no_recogido_count++;
            }
        }
    }
    if ( ! is_array( $history ) || empty( $history ) ) {
        return '<p>No hay registros de liquidación para este remitente en el rango de fechas seleccionado.</p>';
    }

    ob_start();
    ?>
    <style>
    .merc-liquidation-table{width:100%;border-collapse:collapse;margin:8px 0}
    .merc-liquidation-table th,.merc-liquidation-table td{padding:6px;border:1px solid #ddd;text-align:left}
    .merc-liquidation-table th{background:#f7f7f7}
    
    /* Responsive: tabla scrollable en móvil */
    @media (max-width: 768px) {
        .merc-liquidation-table {
            font-size: 12px;
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        .merc-liquidation-table th,
        .merc-liquidation-table td {
            padding: 4px 8px;
            font-size: 11px;
        }
    }
    
    @media (max-width: 480px) {
        .merc-liquidation-table th,
        .merc-liquidation-table td {
            padding: 3px 5px;
            font-size: 10px;
        }
    }
    </style>

    <!-- Penalidades: ahora manejadas por el plugin merc-finance -->
    <table class="merc-liquidation-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Recaudado (S/.)</th>
                <th>Pago Marca (S/.)</th>
                <th>Servicios (S/.)</th>
                <th>Resultado (S/.)</th>
                <th>Acción</th>
                <th>Monto (S/.)</th>
                <th>Comprobante</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $history as $entry ) :
            $id = isset( $entry['id'] ) ? esc_html( $entry['id'] ) : '';
            $date = isset( $entry['date'] ) ? esc_html( $entry['date'] ) : '';
            $recaudado = isset( $entry['recaudado_merc'] ) ? floatval( $entry['recaudado_merc'] ) : 0.0;
            $pago_marca = isset( $entry['pago_marca'] ) ? floatval( $entry['pago_marca'] ) : 0.0;
            $servicios = isset( $entry['servicios'] ) ? floatval( $entry['servicios'] ) : 0.0;
            $result = isset( $entry['result'] ) ? floatval( $entry['result'] ) : 0.0;
            $action = isset( $entry['action'] ) ? esc_html( $entry['action'] ) : '';
            $amount = isset( $entry['amount'] ) ? floatval( $entry['amount'] ) : 0.0;
            $attachment_id = isset( $entry['attachment_id'] ) ? intval( $entry['attachment_id'] ) : 0;
            $attachment_url = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
            ?>
            <tr>
                <td><?php echo $id; ?></td>
                <td><?php echo $date; ?></td>
                <td><?php echo number_format( $recaudado, 2 ); ?></td>
                <td><?php echo number_format( $pago_marca, 2 ); ?></td>
                <td><?php echo number_format( $servicios, 2 ); ?></td>
                <td><?php echo number_format( $result, 2 ); ?></td>
                <td><?php echo $action; ?></td>
                <td><?php echo number_format( $amount, 2 ); ?></td>
                <td>
                    <?php if ( $attachment_url ) : ?>
                        <a href="<?php echo esc_url( $attachment_url ); ?>" target="_blank">Ver</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
add_shortcode( 'merc_liquidations_history', 'merc_liquidations_history_shortcode' );
    
    // Shortcode: sección de finanzas para el cliente que muestra su historial de liquidaciones
    add_shortcode('merc_finanzas_cliente', 'merc_finanzas_cliente_shortcode');
    function merc_finanzas_cliente_shortcode( $atts = array() ) {
        if ( ! is_user_logged_in() ) {
            return '<p>Debe iniciar sesión para ver sus finanzas.</p>';
        }
    
        $user = wp_get_current_user();
        
        // Procesar atributos de fecha
        $atts = shortcode_atts( array(
            'fecha_inicio' => '',
            'fecha_fin' => ''
        ), $atts );
    
        // Reusar el shortcode existente si está registrado
        if ( shortcode_exists('merc_liquidations_history') ) {
            $shortcode_params = 'user_id="' . esc_attr($user->ID) . '"';
            if ( ! empty($atts['fecha_inicio']) ) {
                $shortcode_params .= ' fecha_inicio="' . esc_attr($atts['fecha_inicio']) . '"';
            }
            if ( ! empty($atts['fecha_fin']) ) {
                $shortcode_params .= ' fecha_fin="' . esc_attr($atts['fecha_fin']) . '"';
            }
            return '<div class="merc-finanzas-cliente">' . do_shortcode('[merc_liquidations_history ' . $shortcode_params . ']') . '</div>';
        }
    
        // Fallback: render básico desde user meta 'merc_liquidations'
        $history = get_user_meta( $user->ID, 'merc_liquidations', true );
        if ( empty($history) || ! is_array($history) ) {
            return '<p>No tienes liquidaciones registradas.</p>';
        }
    
        ob_start();
        echo '<div class="merc-finanzas-cliente"><h3>Historial de liquidaciones</h3>';
        echo '<table class="widefat"><thead><tr><th>Fecha</th><th>Monto</th><th>Verificado</th><th>Envíos</th></tr></thead><tbody>';
        foreach ( $history as $h ) {
            $date = isset($h['date']) ? esc_html($h['date']) : (isset($h['created']) ? esc_html($h['created']) : '');
            $amount = isset($h['amount']) ? number_format(floatval($h['amount']), 2) : '0.00';
            $verified = ! empty($h['verified']) ? 'Sí' : 'No';
            $shipments = isset($h['shipments']) && is_array($h['shipments']) ? implode(', ', array_map('intval', $h['shipments'])) : '';
            echo '<tr><td>' . $date . '</td><td>S/. ' . $amount . '</td><td>' . $verified . '</td><td>' . esc_html($shipments) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    
        return ob_get_clean();
    }

// Detector ligero para identificar cuando el formulario POD llega por POST
// Registra un log compacto para confirmar que el envío llegó al servidor.
add_action('init','merc_pod_detect_form_submit');
function merc_pod_detect_form_submit(){
    if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        if ( isset($_POST['__pod_id']) || isset($_POST['pod_payment_methods']) || isset($_POST['__pod_signature']) ) {
            $sid = isset($_POST['__pod_id']) ? sanitize_text_field($_POST['__pod_id']) : '';
            $len = isset($_POST['pod_payment_methods']) ? strlen($_POST['pod_payment_methods']) : 0;
            $sig = ( isset($_POST['__pod_signature']) && ! empty($_POST['__pod_signature']) ) ? 'yes' : 'no';
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            error_log("MERC_POD_DETECT - shipmentID={$sid} pod_payment_methods_len={$len} signature_present={$sig} URI={$uri}");
        }
    }
}

// Intento de fallback: si el formulario POD hace POST y el plugin no ejecuta
// su hook, invocamos directamente la función de guardado una sola vez por envío.
add_action('init','merc_pod_catch_and_save', 21);
function merc_pod_catch_and_save(){
    if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        // Soporte para AJAX que envía un campo `formData` (serializeArray)
        $incoming_formdata = null;
        if ( isset($_POST['formData']) ) {
            $incoming_formdata = $_POST['formData']; // puede ser array o string
        }

        // Caso 1: campos enviados en top-level (no AJAX)
        if ( isset($_POST['__pod_id']) && isset($_POST['pod_payment_methods']) ) {
            $shipment_id = intval($_POST['__pod_id']);
            if ( $shipment_id <= 0 ) return;

            // Evitar doble procesamiento
            $flag = get_post_meta($shipment_id, 'pod_payment_methods_processed', true);
            if ( $flag == '1' ) {
                // Si ya fue procesado previamente, no forzamos re-procesos salvo que esta petición sea la final
                // y contenga firma o estado ENTREGADO (ver detección más abajo).
                // Continuar para permitir re-procesos si es una SUMA final con firma.
            }

            // Construir form_data similar al plugin (array de name/value)
            $form_data = array();
            foreach ($_POST as $k => $v) {
                // no incluir archivos en $_FILES aquí; las imágenes vienen en base64 en el JSON
                $form_data[] = array('name' => $k, 'value' => is_array($v) ? json_encode($v) : $v);
            }
            // Detectar si esta petición incluye firma o indica ENTREGADO
            $has_signature = false;
            if ( (isset($_POST['__pod_signature']) && !empty($_POST['__pod_signature'])) || (isset($_POST['pod_signature']) && !empty($_POST['pod_signature'])) ) {
                $has_signature = true;
            }
            $estado_form = '';
            if ( isset($_POST['status']) ) {
                $estado_form = sanitize_text_field($_POST['status']);
            }

            // Solo ejecutar el guardado automático si detectamos firma o estado ENTREGADO
            if ( $has_signature || strtoupper(trim($estado_form)) === 'ENTREGADO' || (isset($_POST['action']) && $_POST['action'] === 'pod_signed') ) {
                try {
                    error_log('MERC_POD_AUTO_SAVE - invocando merc_save_pod_payment_methods (final) para shipment ' . $shipment_id . ' has_signature=' . ($has_signature?1:0) . ' status=' . $estado_form);
                    merc_save_pod_payment_methods($shipment_id, $form_data);
                    update_post_meta($shipment_id, 'pod_payment_methods_processed', '1');
                    error_log('MERC_POD_AUTO_SAVE - completado para shipment ' . $shipment_id);
                } catch (Exception $e) {
                    error_log('MERC_POD_AUTO_SAVE - error al invocar guardado: ' . $e->getMessage());
                }
            } else {
                error_log('MERC_POD_AUTO_SAVE - petición intermedia detectada, no se procesa (shipment ' . $shipment_id . ') has_signature=' . ($has_signature?1:0) . ' status=' . $estado_form);
            }
        }
        // Caso 2: AJAX envía `formData` como arreglo (DESHABILITADO - usar solo POST normal del Caso 1)
        // El POST normal del Caso 1 ya maneja correctamente el guardado
        // Mantener esta sección comentada para evitar múltiples POSTs
        /*
        elseif ( $incoming_formdata ) {
            // ... código deshabilitado ...
        }
        */
    }
}

// Registrar CPT para penalidades (para evitar notices y exponer UI admin)
add_action( 'init', 'merc_register_penalty_post_type', 11 );
function merc_register_penalty_post_type() {
    $labels = array(
        'name'               => 'Penalidades',
        'singular_name'      => 'Penalidad',
        'menu_name'          => 'Penalidades',
        'name_admin_bar'     => 'Penalidad',
        'add_new'            => 'Añadir penalidad',
        'add_new_item'       => 'Añadir nueva penalidad',
        'new_item'           => 'Nueva penalidad',
        'edit_item'          => 'Editar penalidad',
        'view_item'          => 'Ver penalidad',
        'all_items'          => 'Todas las penalidades',
        'search_items'       => 'Buscar penalidades',
        'not_found'          => 'No se encontraron penalidades',
        'not_found_in_trash' => 'No hay penalidades en la papelera'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 58,
        'menu_icon'          => 'dashicons-warning',
        'supports'           => array( 'title', 'editor', 'custom-fields' ),
        'has_archive'        => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'rewrite'            => false,
    );

    register_post_type( 'merc_penalty', $args );
}


// ====================================================================================
 
// Ocultar la sección de asignación de conductor del sidebar
add_action('wpcsc_before_sidebar_form_section', 'merc_ocultar_seccion_conductor', 5);
function merc_ocultar_seccion_conductor() {
    ?>
    <style>
        /* Ocultar la sección de asignación de conductor */
        #wpcfe-misc-assign-user,
        .wpcfe-misc-assign-user {
            display: none !important;
        }
    </style>
    <?php
}

// Handler AJAX para obtener datos de un shipment individual
add_action('wp_ajax_wpcpod_get_single_shipment_data', 'wpcpod_get_single_shipment_data_handler');
add_action('wp_ajax_nopriv_wpcpod_get_single_shipment_data', 'wpcpod_get_single_shipment_data_handler');
function wpcpod_get_single_shipment_data_handler() {
    error_log('=== HANDLER AJAX LLAMADO ===');
    
    // Ahora recibimos el post_id en lugar del shipment_number
    $post_id = intval($_POST['post_id']);
    
    error_log('Post ID recibido: ' . $post_id);
    
    if (empty($post_id)) {
        error_log('Error: Post ID vacío');
        wp_send_json_error('ID de pedido no proporcionado');
        return;
    }
    
    // Verificar que el post existe y es del tipo correcto
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'wpcargo_shipment') {
        error_log('Error: Post no encontrado o tipo incorrecto');
        wp_send_json_error('Pedido no encontrado: ' . $post_id);
        return;
    }
    
    error_log('Post encontrado: ' . $post_id);
    
    // Obtener todos los meta
    $shipment_number = get_post_meta($post_id, 'wpcargo_shipment_number', true);
    if (empty($shipment_number)) {
        $shipment_number = get_post_meta($post_id, 'wpcargo_order_number', true);
    }
    
    $receiver_name = get_post_meta($post_id, 'wpcargo_receiver_name', true);
    $receiver_address = get_post_meta($post_id, 'wpcargo_receiver_address', true);
    $receiver_phone = get_post_meta($post_id, 'wpcargo_receiver_phone', true);
    $shipper_phone = get_post_meta($post_id, 'wpcargo_shipper_phone', true);
    $shipper_name = get_post_meta($post_id, 'wpcargo_shipper_name', true);
    $tienda_name = get_post_meta($post_id, 'wpcargo_tiendaname', true);
    $monto = floatval(get_post_meta($post_id, 'wpcargo_shipment_amount', true));
    $link_maps = get_post_meta($post_id, 'link_maps', true);
    
    $current_user = wp_get_current_user();
    $motorizado_name = $current_user->display_name;
    
    error_log('Receiver phone: ' . $receiver_phone);
    error_log('Shipper phone: ' . $shipper_phone);
    error_log('Link Maps: ' . $link_maps);
    error_log('Tienda name: ' . $tienda_name);
    
    wp_send_json_success(array(
        'post_id' => $post_id,
        'shipment_number' => $shipment_number,
        'motorizado_name' => $motorizado_name,
        'receiver_name' => $receiver_name,
        'receiver_address' => $receiver_address,
        'receiver_phone' => $receiver_phone,
        'shipper_phone' => $shipper_phone,
        'shipper_name' => $shipper_name,
        'tienda_name' => $tienda_name,
        'monto' => $monto,
        'link_maps' => $link_maps
    ));
    
    error_log('=== FIN HANDLER ===');
}

// Handler AJAX para obtener datos de pickup individual
add_action('wp_ajax_wpcpod_get_single_pickup_data', 'wpcpod_get_single_pickup_data_handler');
add_action('wp_ajax_nopriv_wpcpod_get_single_pickup_data', 'wpcpod_get_single_pickup_data_handler');
function wpcpod_get_single_pickup_data_handler() {
    error_log('=== HANDLER PICKUP AJAX LLAMADO ===');
    
    $post_id = intval($_POST['post_id']);
    
    error_log('Post ID recibido: ' . $post_id);
    
    if (empty($post_id)) {
        error_log('Error: Post ID vacío');
        wp_send_json_error('ID de recojo no proporcionado');
        return;
    }
    
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'wpcargo_shipment') {
        error_log('Error: Post no encontrado o tipo incorrecto');
        wp_send_json_error('Recojo no encontrado: ' . $post_id);
        return;
    }
    
    error_log('Post encontrado: ' . $post_id);
    
    // Obtener todos los meta
    $shipment_number = get_post_meta($post_id, 'wpcargo_shipment_number', true);
    if (empty($shipment_number)) {
        $shipment_number = get_post_meta($post_id, 'wpcargo_order_number', true);
    }
    
    $shipper_phone = get_post_meta($post_id, 'wpcargo_shipper_phone', true);
    $shipper_name = get_post_meta($post_id, 'wpcargo_shipper_name', true);
    $tienda_name = get_post_meta($post_id, 'wpcargo_tiendaname', true);
    $shipper_address = get_post_meta($post_id, 'wpcargo_shipper_address', true);
    $link_maps_remitente = get_post_meta($post_id, 'link_maps_remitente', true);
    
    $current_user = wp_get_current_user();
    $motorizado_name = $current_user->display_name;
    
    error_log('Shipper phone: ' . $shipper_phone);
    error_log('Link Maps Remitente: ' . $link_maps_remitente);
    error_log('Tienda name: ' . $tienda_name);
    
    wp_send_json_success(array(
        'post_id' => $post_id,
        'shipment_number' => $shipment_number,
        'motorizado_name' => $motorizado_name,
        'shipper_phone' => $shipper_phone,
        'shipper_name' => $shipper_name,
        'tienda_name' => $tienda_name,
        'shipper_address' => $shipper_address,
        'link_maps_remitente' => $link_maps_remitente
    ));
    
    error_log('=== FIN HANDLER PICKUP ===');
}

// Inyectar script: cuando el modo es NO COBRAR, sincronizar `#monto` con el span `#shipping-cost`
add_action('wp_footer', 'merc_pod_sync_shipping_cost_to_monto', 9999);
function merc_pod_sync_shipping_cost_to_monto() {
    ?>
    <!-- SweetAlert2 para alertas modales -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    (function(){
        function parseNumber(str){
            if (str === undefined || str === null) return 0;
            var s = String(str).trim();
            s = s.replace(/[^0-9,\.\-]/g, '');
            if (!s) return 0;
            if (s.indexOf('.') !== -1 && s.indexOf(',') !== -1) {
                s = s.replace(/\./g,'').replace(/,/g,'.');
            } else if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) {
                s = s.replace(/,/g,'.');
            }
            var n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        }

        function isNoCobrar(){
            var m = document.querySelector('[name="payment_wpcargo_mode_field"], #payment_wpcargo_mode_field');
            if (!m) return false;
            var v = (m.value || m.textContent || '').toString().toLowerCase();
            v = v.replace(/[\s_\-]+/g,'');
            return v.indexOf('nocobrar') !== -1 || v === 'nocobrar';
        }

        function dispatchEvents(el){
            if (!el) return;
            try {
                var evInput = new Event('input', { bubbles: true });
                var evChange = new Event('change', { bubbles: true });
                el.dispatchEvent(evInput);
                el.dispatchEvent(evChange);
                // keyup for legacy handlers
                try { el.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true })); } catch(e){}
            } catch(e){}
        }

        function syncMonto(){
            try {
                var ship = document.getElementById('shipping-cost');
                if (!ship) return;
                var txt = ship.textContent || ship.innerText || '';
                var shippingCost = parseNumber(txt);

                // Obtener el campo de monto actual
                var monto = document.getElementById('monto') || document.querySelector('input[name="monto"]');
                if (!monto) return;
                
                var currentMonto = parseFloat(monto.value) || 0;
                var totalCostSpan = document.getElementById('total-cost');
                var totalCost = totalCostSpan ? parseNumber(totalCostSpan.textContent || totalCostSpan.innerText || '') : shippingCost;

                // Si es modo "No Cobrar", sincronizar con el costo de envío
                if (isNoCobrar()) {
                    // set visible span for product-cost and hidden inputs
                    var productSpan = document.getElementById('product-cost');
                    if (productSpan) productSpan.textContent = Number(shippingCost).toFixed(2);

                    monto.value = shippingCost.toFixed(2);
                    dispatchEvents(monto);

                    var total = document.querySelector('input[name="wpcargo_total_cobrar"], #wpcargo_total_cobrar');
                    if (total) { total.value = shippingCost.toFixed(2); dispatchEvents(total); }

                    // update hidden shipping/product cost inputs
                    var hiddenShip = document.getElementById('hidden-shipping-cost') || document.querySelector('input[name="wpcargo_costo_envio"]');
                    if (hiddenShip) { hiddenShip.value = shippingCost.toFixed(2); dispatchEvents(hiddenShip); }

                    var hiddenProd = document.getElementById('hidden-product-cost') || document.querySelector('input[name="wpcargo_costo_producto"]');
                    if (hiddenProd) { hiddenProd.value = shippingCost.toFixed(2); dispatchEvents(hiddenProd); }

                    if (typeof window.podMontoBase !== 'undefined') window.podMontoBase = shippingCost;
                    if (typeof window.wpcargo_total_cobrar !== 'undefined') window.wpcargo_total_cobrar = shippingCost;
                } else {
                    // Si NO es "No Cobrar", validar si el monto es menor al costo de envío
                    if (currentMonto > 0 && currentMonto < shippingCost) {
                        // Actualizar automáticamente
                        monto.value = shippingCost.toFixed(2);
                        dispatchEvents(monto);
                        // Recalcular el desglose
                        if (typeof updateShippingBreakdown === 'function') {
                            setTimeout(updateShippingBreakdown, 100);
                        }
                        // Mostrar aviso
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'info',
                                title: 'ℹ️ Monto Actualizado',
                                html: '<p>El monto a cobrar se ha <strong>actualizado automáticamente</strong> al costo mínimo de envío.</p>' +
                                      '<p><strong>Nuevo monto: S/. ' + shippingCost.toFixed(2) + '</strong></p>' +
                                      '<p>No coloque un monto menor al costo de envío.</p>',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#3085d6'
                            });
                        } else {
                            // Fallback si no hay SweetAlert
                            alert('ℹ️ ACTUALIZACIÓN\n\nEl monto a cobrar se ha actualizado al costo mínimo de envío.\n\nNuevo monto: S/. ' + shippingCost.toFixed(2));
                        }
                    }
                    // Si el monto está vacío o es 0, actualizar al total-cost
                    else if (currentMonto === 0) {
                        monto.value = totalCost.toFixed(2);
                        dispatchEvents(monto);
                        if (typeof updateShippingBreakdown === 'function') {
                            setTimeout(updateShippingBreakdown, 100);
                        }
                    }
                }

                // small debug ping (non-blocking)
                if (typeof ajaxurl !== 'undefined') {
                    try{ var fd = new FormData(); fd.append('action','merc_pod_client_debug'); fd.append('context','syncMonto'); fd.append('shipmentID',(document.querySelector('[name="shipment_id"]')||{value:''}).value); fetch(ajaxurl,{method:'POST',body:fd}).catch(()=>{}); }catch(e){}
                }
            } catch(e){ console && console.log && console.log('syncMonto error', e); }
        }

        // Mantener/restaurar `#monto` cuando el modo NO COBRAR no esté seleccionado.
        function ensureMontoForNonNoCobrar(){
            try {
                if (isNoCobrar()) return;
                var monto = document.getElementById('monto') || document.querySelector('input[name="monto"]');
                if (!monto) return;
                var current = (monto.value || '').toString().trim();
                if (current !== '') return; // ya tiene valor

                var val = 0;
                if (typeof window.podMontoBase !== 'undefined' && window.podMontoBase) val = window.podMontoBase;
                else {
                    var ship = document.getElementById('shipping-cost');
                    if (ship) val = parseNumber(ship.textContent || ship.innerText || '');
                }

                monto.value = Number(val).toFixed(2);
                dispatchEvents(monto);
            } catch(e){}
        }

        function runTemporaryRestoreWatcher(){
            try {
                var tries = 0;
                var iv = setInterval(function(){
                    if (isNoCobrar() || tries > 6) { clearInterval(iv); return; }
                    ensureMontoForNonNoCobrar();
                    tries++;
                }, 200);
            } catch(e){}
        }

        document.addEventListener('DOMContentLoaded', function(){ syncMonto(); });

        // Validar input de monto en tiempo real
        var montoInput = document.getElementById('monto') || document.querySelector('input[name="monto"]');
        if (montoInput) {
            montoInput.addEventListener('input', function() {
                var shippingCost = 0;
                var ship = document.getElementById('shipping-cost');
                if (ship) {
                    var txt = ship.textContent || ship.innerText || '';
                    shippingCost = parseNumber(txt);
                }
                
                var currentMonto = parseFloat(this.value) || 0;
                
                // Si NO es "No Cobrar" y el monto es menor al costo de envío, mostrar advertencia
                if (!isNoCobrar() && currentMonto > 0 && currentMonto < shippingCost) {
                    this.style.borderColor = '#dc3545';
                    this.style.backgroundColor = '#ffe6e6';
                    this.title = '⚠️ Monto menor al costo mínimo (S/. ' + shippingCost.toFixed(2) + ')';
                } else {
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';
                    this.title = '';
                }
            });
            
            // Validar también en change
            montoInput.addEventListener('change', function() {
                var shippingCost = 0;
                var ship = document.getElementById('shipping-cost');
                if (ship) {
                    var txt = ship.textContent || ship.innerText || '';
                    shippingCost = parseNumber(txt);
                }
                
                var currentMonto = parseFloat(this.value) || 0;
                
                // Si NO es "No Cobrar" y el monto es menor al costo de envío, actualizar automáticamente
                if (!isNoCobrar() && currentMonto > 0 && currentMonto < shippingCost) {
                    // Actualizar automáticamente
                    this.value = shippingCost.toFixed(2);
                    dispatchEvents(this);
                    if (typeof updateShippingBreakdown === 'function') {
                        setTimeout(updateShippingBreakdown, 100);
                    }
                    // Mostrar aviso
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'ℹ️ Monto Actualizado',
                            html: '<p>El monto a cobrar se ha <strong>actualizado automáticamente</strong> al costo mínimo de envío.</p>' +
                                  '<p><strong>Nuevo monto: S/. ' + shippingCost.toFixed(2) + '</strong></p>' +
                                  '<p>No coloque un monto menor al costo de envío.</p>',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        alert('ℹ️ ACTUALIZACIÓN\n\nEl monto a cobrar se ha actualizado al costo mínimo de envío.\n\nNuevo monto: S/. ' + shippingCost.toFixed(2));
                    }
                } else {
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';
                    this.title = '';
                }
            });
        }

        // Escuchar cambios en el campo de modo de pago y restaurar monto si corresponde
        var payField = document.querySelector('[name="payment_wpcargo_mode_field"], #payment_wpcargo_mode_field');
        if (payField){
            payField.addEventListener('change', function(){ setTimeout(function(){ if (!isNoCobrar()) { ensureMontoForNonNoCobrar(); runTemporaryRestoreWatcher(); } else { syncMonto(); } }, 50); });
            payField.addEventListener('input', function(){ setTimeout(function(){ if (!isNoCobrar()) { ensureMontoForNonNoCobrar(); runTemporaryRestoreWatcher(); } else { syncMonto(); } }, 50); });
        }

        // Delegado para campo de pago dinámico
        document.addEventListener('change', function(e){ var t=e.target; if(!t) return; if(t.matches && (t.matches('[name="payment_wpcargo_mode_field"]')||t.matches('#payment_wpcargo_mode_field'))){ setTimeout(function(){ if (!isNoCobrar()) { ensureMontoForNonNoCobrar(); runTemporaryRestoreWatcher(); } else { syncMonto(); } }, 50); } }, true);

        // Listen changes on selects that affect shipping cost
        var selIds = ['#wpcargo_distrito_destino','#wpcargo_distrito_recojo','select[name="wpcargo_distrito_destino"]','select[name="wpcargo_distrito_recojo"]','input[name="wpcargo_shipper_address"]','#wpcargo_shipper_address'];
        selIds.forEach(function(s){ var el = document.querySelector(s); if (el) el.addEventListener('change', function(){ setTimeout(syncMonto, 100); }); });

        // Delegate change in case selects/inputs are loaded dynamically
        document.addEventListener('change', function(e){ var t=e.target; if(!t) return; if(t.matches && (t.matches('#wpcargo_distrito_destino')||t.matches('#wpcargo_distrito_recojo')||t.matches('select[name="wpcargo_distrito_destino"]')||t.matches('select[name="wpcargo_distrito_recojo"]')||t.matches('input[name="wpcargo_shipper_address"]')||t.matches('#wpcargo_shipper_address'))){ setTimeout(syncMonto,100); } }, true);

        // Robust observation: track last text and observe both the span and the document
        var lastShipText = null;
        function updateLastAndSync(){
            var ship = document.getElementById('shipping-cost');
            if (!ship) return;
            var txt = ship.textContent || ship.innerText || '';
            if (txt !== lastShipText) {
                lastShipText = txt;
                syncMonto();
            }
        }

        // Observe the specific span for direct text changes
        try{
            var ship = document.getElementById('shipping-cost');
            if (ship) {
                var moSpan = new MutationObserver(function(){ updateLastAndSync(); });
                moSpan.observe(ship, {childList:true, characterData:true, subtree:true});
            }
        } catch(e){}

        // Observe document body for nodes being replaced/added (covers templates that re-render the span)
        try{
            var moBody = new MutationObserver(function(mutations){
                var triggered = false;
                for (var i=0;i<mutations.length;i++){
                    var mut = mutations[i];
                    if (mut.type === 'childList' && (mut.addedNodes.length || mut.removedNodes.length)) { triggered = true; break; }
                    if (mut.type === 'attributes') { triggered = true; break; }
                    if (mut.type === 'characterData') { triggered = true; break; }
                }
                if (triggered) updateLastAndSync();
            });
            moBody.observe(document.body, {childList:true, subtree:true, characterData:true, attributes:true});
        } catch(e){}

        // Also run a few initial checks in case loading is staggered
        setTimeout(updateLastAndSync, 200);
        setTimeout(updateLastAndSync, 800);

    })();
    </script>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════════
// ASIGNAR MOTORIZADO A CLIENTE
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * AJAX: Obtener motorizado actual del cliente
 */
add_action('wp_ajax_merc_get_client_driver', function() {
    // Verificar nonce
    if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], 'merc_driver_assign')) {
        error_log('❌ [AJAX] merc_get_client_driver - Nonce inválido');
        wp_send_json_error(['message' => 'Nonce inválido'], 403);
    }
    
    if (!current_user_can('manage_options')) {
        error_log('❌ [AJAX] merc_get_client_driver - Sin permisos');
        wp_send_json_error(['message' => 'Acceso denegado'], 403);
    }
    
    $client_id = intval($_POST['client_id']);
    error_log("\n" . str_repeat("─", 80) . "\n🔍 DASHBOARD AJAX: OBTENER MOTORIZADO DE CLIENTE\n" . str_repeat("─", 80));
    $client_data = get_userdata($client_id);
    $cliente_nombre = $client_data ? $client_data->display_name : 'Cliente #' . $client_id;
    error_log("👤 Cliente: " . $cliente_nombre . " (ID: " . $client_id . ")");
    
    $driver_id = get_user_meta($client_id, 'merc_motorizo_recojo_default', true);
    error_log("🔎 Buscando meta_key 'merc_motorizo_recojo_default'...");
    error_log("   Valor encontrado: " . ($driver_id ? "Motorizado #" . $driver_id : "VACÍO"));
    
    if ($driver_id) {
        $motorizado_data = get_userdata($driver_id);
        $motorizado_nombre = $motorizado_data ? $motorizado_data->display_name : 'Motorizado #' . $driver_id;
        error_log("   Nombre: " . $motorizado_nombre);
    }
    error_log(str_repeat("─", 80) . "\n");
    
    wp_send_json_success(['driver_id' => $driver_id ?: '']);
});

/**
 * AJAX: Asignar motorizado a cliente Y ACTUALIZAR SUS ENVÍOS
 */
add_action('wp_ajax_merc_assign_driver_to_client', function() {
    // Verificar nonce
    if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], 'merc_driver_assign')) {
        error_log('❌ [AJAX] merc_assign_driver_to_client - Nonce inválido');
        wp_send_json_error(['message' => 'Nonce inválido'], 403);
    }
    
    if (!current_user_can('manage_options')) {
        error_log('❌ [AJAX] merc_assign_driver_to_client - Sin permisos');
        wp_send_json_error(['message' => 'Acceso denegado'], 403);
    }
    
    $client_id = intval($_POST['client_id']);
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : '';
    $shipment_count = 0;
    
    error_log("\n501501501501501501501501501501501501501501501501501501501501501501501501\n📋 DASHBOARD AJAX: ASIGNAR MOTORIZADO A CLIENTE\n501501501501501501501501501501501501501501501501501501501501501501501501");
    $client_data = get_userdata($client_id);
    $cliente_nombre = $client_data ? $client_data->display_name : 'Cliente #' . $client_id;
    error_log("👤 Cliente: " . $cliente_nombre . " (ID: " . $client_id . ")");
    
    if ($driver_id) {
        // Validar que el usuario sea un motorizado
        $driver = get_userdata($driver_id);
        if (!in_array('wpcargo_driver', (array)$driver->roles)) {
            error_log("❌ Usuario #{$driver_id} no tiene rol 'wpcargo_driver'");
            wp_send_json_error(['message' => 'Este usuario no es un motorizado']);
        }
        
        $nombre_motorizado = $driver ? $driver->display_name : 'Motorizado #' . $driver_id;
        error_log("🚚 Motorizado a asignar: " . $nombre_motorizado . " (ID: " . $driver_id . ")");
        
        // Guardar motorizado default en user_meta
        update_user_meta($client_id, 'merc_motorizo_recojo_default', $driver_id);
        $verificacion_meta = get_user_meta($client_id, 'merc_motorizo_recojo_default', true);
        error_log("📌 Guardado en user_meta 'merc_motorizo_recojo_default': " . $verificacion_meta);
        
        // ACTUALIZAR TODOS LOS ENVÍOS DE RECOJO DEL CLIENTE DEL DÍA ACTUAL
        error_log("🔎 Buscando envíos de RECOJO de HOY...");
        
        $hoy = current_time('Y-m-d');
        error_log("   📅 Fecha: " . $hoy);
        
        $args = [
            'post_type' => 'wpcargo_shipment',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'registered_shipper',
                    'value' => $client_id,
                    'compare' => '='
                ]
            ],
            'date_query' => [
                [
                    'after' => $hoy . ' 00:00:00',
                    'before' => $hoy . ' 23:59:59',
                    'inclusive' => true
                ]
            ]
        ];
        
        $shipments = get_posts($args);
        error_log("   📦 Total encontrado: " . count($shipments) . " envío(s)");
        
        if (empty($shipments)) {
            error_log("   ⚠️ No hay envíos de RECOJO de hoy para este cliente");
        } else {
            foreach ($shipments as $shipment) {
                error_log("   ├─ Procesando envío #" . $shipment->ID);
                // Solo actualizar si es envío de RECOJO
                $container_recojo = get_post_meta($shipment->ID, 'shipment_container_recojo', true);
                if (!empty($container_recojo)) {
                    update_post_meta($shipment->ID, 'wpcargo_motorizo_recojo', $driver_id);
                    $verify = get_post_meta($shipment->ID, 'wpcargo_motorizo_recojo', true);
                    error_log("   │  ✅ Asignado - wpcargo_motorizo_recojo: " . $verify);
                    $shipment_count++;
                } else {
                    error_log("   │  ⚠️ No es envío de RECOJO (sin container_recojo)");
                }
            }
        }
        error_log("✅ Total actualizado: " . $shipment_count . " envío(s)");
        
    } else {
        error_log("🔴 Removiendo asignación de motorizado de RECOJO...");
        
        // Remover asignación
        delete_user_meta($client_id, 'merc_motorizo_recojo_default');
        error_log("📌 Eliminado del user_meta 'merc_motorizo_recojo_default'");
        
        // REMOVER DE ENVÍOS DE RECOJO DEL CLIENTE DEL DÍA ACTUAL
        error_log("🔎 Buscando envíos de RECOJO de HOY...");
        
        $hoy = current_time('Y-m-d');
        error_log("   📅 Fecha: " . $hoy);
        
        $args = [
            'post_type' => 'wpcargo_shipment',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'registered_shipper',
                    'value' => $client_id,
                    'compare' => '='
                ]
            ],
            'date_query' => [
                [
                    'after' => $hoy . ' 00:00:00',
                    'before' => $hoy . ' 23:59:59',
                    'inclusive' => true
                ]
            ]
        ];
        
        $shipments = get_posts($args);
        error_log("   📦 Total encontrado: " . count($shipments) . " envío(s)");
        
        if (empty($shipments)) {
            error_log("   ⚠️ No hay envíos de RECOJO de hoy para este cliente");
        } else {
            foreach ($shipments as $shipment) {
                error_log("   ├─ Procesando envío #" . $shipment->ID);
                // Solo remover si es envío de RECOJO
                $container_recojo = get_post_meta($shipment->ID, 'shipment_container_recojo', true);
                if (!empty($container_recojo)) {
                    delete_post_meta($shipment->ID, 'wpcargo_motorizo_recojo');
                    $verify = get_post_meta($shipment->ID, 'wpcargo_motorizo_recojo', true);
                    error_log("   │  ✅ Removido - wpcargo_motorizo_recojo es ahora: " . ($verify ?: 'vacío'));
                    $shipment_count++;
                } else {
                    error_log("   │  ⚠️ No es envío de RECOJO (sin container_recojo)");
                }
            }
        }
        error_log("✅ Total modificado: " . $shipment_count . " envío(s)");
    }
    
    // Verificar que se guardó correctamente
    $verification = get_user_meta($client_id, 'merc_motorizo_recojo_default', true);
    error_log("🔎 VERIFICACIÓN FINAL:");
    error_log("   user_meta 'merc_motorizo_recojo_default': " . ($verification ?: 'VACÍO'));
    error_log(str_repeat("─", 80) . "\n");
    
    // Construir mensaje con información de envíos actualizados
    $message = $driver_id 
        ? "✅ Motorizado de RECOJO asignado. Se actualizaron {$shipment_count} envío(s) de hoy."
        : "✅ Asignación de motorizado de RECOJO removida. Se modificaron {$shipment_count} envío(s) de hoy.";
    
    wp_send_json_success([
        'message' => $message,
        'updated_count' => $shipment_count
    ]);
});

/**
 * Usar motorizado por defecto al crear envío (si existe)
 * NOTA: Ahora se maneja en merc_asignar_motorizado_default_al_crear()
 */
add_filter('wpcargo_shipment_meta_defaults', function($defaults) {
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        
        // SOLO aplicar si el usuario actual tiene un motorizado asignado
        $default_driver = get_user_meta($current_user_id, 'merc_motorizo_recojo_default', true);
        
        // Validar que el motorizado exista y pertenezca a este cliente específico
        if ($default_driver) {
            error_log("✅ [SHIPMENT_DEFAULTS] Usuario #{$current_user_id} - Motorizado preseleccionado: #{$default_driver}");
            $defaults['wpcargo_motorizo_recojo'] = $default_driver;
        }
    }
    
    return $defaults;
});

/**
 * FILTRO: Filtrar tabla de usuarios por motorizado asignado
 * Se ejecuta cuando existe el parámetro GET 'merc_driver_filter' en la URL
 */
add_action('pre_get_users', function($query) {
    // Solo aplicar en páginas públicas y si está presente el parámetro de filtro
    if (!is_admin() && isset($_GET['merc_driver_filter']) && !empty($_GET['merc_driver_filter'])) {
        $driver_id = intval($_GET['merc_driver_filter']);
        
        error_log("🔍 [FILTER] pre_get_users - Filtrando usuarios por motorizado: #{$driver_id}");
        
        // Validar que el motorizado exista
        $driver = get_userdata($driver_id);
        if ($driver && in_array('wpcargo_driver', (array)$driver->roles)) {
            // Agregar meta_query para filtrar por merc_default_driver
            $meta_query = $query->get('meta_query');
            if (!is_array($meta_query)) {
                $meta_query = [];
            }
            
            $meta_query[] = [
                'key'   => 'merc_motorizo_recojo_default',
                'value' => $driver_id,
                'compare' => '='
            ];
            
            $query->set('meta_query', $meta_query);
            
            error_log("✅ [FILTER] pre_get_users - meta_query agregado correctamente");
        } else {
            error_log("⚠️  [FILTER] pre_get_users - Motorizado #{$driver_id} no existe o no es válido");
        }
    }
}, 10, 1);

// 🔥 AJAX handler para obtener todos los estados posibles
add_action('wp_ajax_wpcpod_get_all_possible_statuses', 'merc_get_all_possible_statuses_ajax');
function merc_get_all_possible_statuses_ajax() {
    $statuses = function_exists('wpcpod_get_all_possible_statuses') ? wpcpod_get_all_possible_statuses() : [];
    
    wp_send_json_success($statuses);
    wp_die();
}

// 🔥 AJAX handler para actualizar estado de entrega
add_action('wp_ajax_wpcpod_update_delivery_status', 'merc_update_delivery_status_ajax');
add_action('wp_ajax_nopriv_wpcpod_update_delivery_status', 'merc_update_delivery_status_ajax');
function merc_update_delivery_status_ajax() {
    // ✅ Verificar nonce de forma segura
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    
    if (!$nonce || !wp_verify_nonce($nonce, 'wpcpod_nonce')) {
        error_log('❌ [UPDATE_STATUS] Nonce inválido');
        wp_send_json_error(['message' => 'Token de seguridad inválido'], 403);
        wp_die();
    }
    
    $shipment_id = isset($_POST['shipment_id']) ? intval($_POST['shipment_id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    
    if (!$shipment_id || !$new_status) {
        error_log('❌ [UPDATE_STATUS] Datos incompletos - ID=' . $shipment_id . ' Status=' . $new_status);
        wp_send_json_error(['message' => 'Datos incompletos'], 400);
        wp_die();
    }
    
    // Validar que si es ENTREGADO, se requiere firma
    $signature_data = '';
    if (isset($_POST['signature'])) {
        $signature_data = sanitize_text_field($_POST['signature']);
    }

    // Si no viene directo, intentar extraer desde formData / form_data (serializado) enviado por el cliente
    if (empty($signature_data)) {
        $candidates = array('formData','form_data','formdata');
        foreach ($candidates as $c) {
            if (isset($_POST[$c])) {
                $fd = $_POST[$c];
                // puede ser array (serialized by jQuery) o JSON string
                if (is_string($fd)) {
                    $maybe = json_decode(wp_unslash($fd), true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($maybe)) {
                        $fd = $maybe;
                    }
                }
                if (is_array($fd)) {
                    foreach ($fd as $entry) {
                        if (is_array($entry) && isset($entry['name']) && stripos($entry['name'],'signature') !== false && !empty($entry['value'])) {
                            $signature_data = sanitize_text_field($entry['value']);
                            error_log('✅ [UPDATE_STATUS] Firma encontrada dentro de ' . $c . ' para shipment ' . $shipment_id);
                            break 2;
                        }
                    }
                }
            }
        }
        // También revisar claves conocidas
        if (empty($signature_data) && !empty($_POST['__pod_signature'])) {
            $signature_data = sanitize_text_field($_POST['__pod_signature']);
        }
        if (empty($signature_data) && !empty($_POST['pod_signature'])) {
            $signature_data = sanitize_text_field($_POST['pod_signature']);
        }
    }

    if ('ENTREGADO' === strtoupper(trim($new_status)) && empty($signature_data)) {
        error_log('⚠️ [UPDATE_STATUS] No se encontró firma pero la firma no es requerida. Procediendo a actualizar estado a ENTREGADO para Shipment #' . $shipment_id);
        // Nota: no se bloquea la actualización; merc_save_pod_payment_methods también guarda sin firma.
    }
    
    error_log('✅ [UPDATE_STATUS] Actualizando shipment #' . $shipment_id . ' a estado: ' . $new_status . (!!$signature_data ? ' (con firma)' : ''));
    
    $old_status = get_post_meta($shipment_id, 'wpcargo_status', true) ?: 'PENDIENTE';
    
    // Actualizar el status principal
    update_post_meta($shipment_id, 'wpcargo_status_anterior', $old_status);
    update_post_meta($shipment_id, 'wpcargo_status', $new_status);
    
    // Guardar firma si está presente
    if (!empty($signature_data)) {
        error_log('📝 Guardando firma para shipment #' . $shipment_id);
        update_post_meta($shipment_id, 'wpcargo_signature_data', $signature_data);
        update_post_meta($shipment_id, 'wpcargo_signature_date', current_time('mysql'));
        update_post_meta($shipment_id, 'wpcargo_signature_user', wp_get_current_user()->user_login ?: 'Sistema');
    }
    
    // Agregar al historial
    $updates = get_post_meta($shipment_id, 'wpcargo_shipments_update', true);
    if (!is_array($updates)) {
        $updates = [];
    }
    
    $current_user = wp_get_current_user();
    $user_name = $current_user->user_login ?: 'Sistema';
    
    $new_update = [
        'status' => $new_status,
        'date' => date('Y-m-d'),
        'time' => current_time('H:i:s'),
        'updated-name' => $user_name,
        'remarks' => 'Estado actualizado desde el planificador de rutas' . (!empty($signature_data) ? ' (con firma)' : '')
    ];
    
    // Si hay firma, agregar referencia a ella en el historial
    if (!empty($signature_data)) {
        $new_update['has_signature'] = true;
    }
    
    array_unshift($updates, $new_update);
    update_post_meta($shipment_id, 'wpcargo_shipments_update', $updates);
    
    error_log('✅ [UPDATE_STATUS] Estado actualizado: ' . $old_status . ' → ' . $new_status);
    
    wp_send_json_success([
        'message' => 'Estado actualizado correctamente' . (!empty($signature_data) ? ' y firma guardada' : ''),
        'old_status' => $old_status,
        'new_status' => $new_status,
        'has_signature' => !empty($signature_data)
    ]);
    wp_die();
}

/**
 * DETECCIÓN DE CAMBIOS EN MOTORIZADOS Y ACTUALIZACIÓN DINÁMICA DEL WPCARGO_DRIVER
 * Se ejecuta en la página de edición de envíos
 * Detecta cambios en wpcargo_motorizo_recojo o wpcargo_motorizo_entrega
 * Y actualiza automáticamente el wpcargo_driver según el estado actual
 */
add_action('wp_footer', function() {
    global $post;
    
    // Determinar post_id según contexto
    $post_id = 0;
    $is_valid_page = false;
    
    // Contexto 1: Admin de WordPress
    if (is_admin() && !empty($post) && $post->post_type === 'wpcargo_shipment') {
        $post_id = $post->ID;
        $is_valid_page = true;
    }
    
    // Contexto 2: Dashboard del frontend manager (?wpcfe=update&id=...)
    if (!is_admin() && isset($_GET['wpcfe']) && $_GET['wpcfe'] === 'update' && isset($_GET['id'])) {
        $post_id = intval($_GET['id']);
        $check_post = get_post($post_id);
        if ($check_post && $check_post->post_type === 'wpcargo_shipment') {
            $is_valid_page = true;
        }
    }
    
    // Si no se cumple ningún contexto válido, salir
    if (!$is_valid_page || !$post_id) {
        return;
    }
    ?>
    <script>
    (function($) {
        // Estados que se consideran de RECOJO
        var PICKUP_STATES = ['PENDIENTE', 'RECOGIDO', 'NO RECOGIDO'];
        
        // URL para AJAX (compatible con admin y frontend)
        var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo admin_url("admin-ajax.php"); ?>';
        var postId = <?php echo intval($post_id); ?>;
        
        // Función para sincronizar wpcargo_driver basado en motorizado actual
        function syncDriverSelect() {
            
            // Obtener campos SELECT
            var $selectRecojo = $('select[name="wpcargo_motorizo_recojo"]');
            var $selectEntrega = $('select[name="wpcargo_motorizo_entrega"]');
            var $selectDriver = $('select[name="wpcargo_driver"]');
            var $inputDriver = $('input[name="wpcargo_driver"]');
            
            // Si no existen los campos, no hacer nada
            if ($selectRecojo.length === 0 && $selectEntrega.length === 0) {
                return false;
            }
            
            // El campo driver puede ser SELECT o INPUT
            var $driverField = $selectDriver.length > 0 ? $selectDriver : $inputDriver;
            
            if ($driverField.length === 0) {
                return false;
            }
            
            var valorRecojo = $selectRecojo.val();
            var valorEntrega = $selectEntrega.val();
            
            // Obtener estado actual vía AJAX
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'merc_get_shipment_status',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('merc_shipment_status'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var status = response.data.status;
                        var isPickup = PICKUP_STATES.indexOf(status) >= 0;
                        
                        // Determinar cuál motorizado usar
                        var motorizadoToUse = isPickup ? 
                            $selectRecojo.val() : 
                            $selectEntrega.val();
                        
                        var fuente = isPickup ? 'RECOJO' : 'ENTREGA';
                        
                        if (motorizadoToUse && motorizadoToUse !== '') {
                            // Actualizar el valor del driver
                            if ($driverField.is('select')) {
                                $driverField.val(motorizadoToUse);
                                $driverField.trigger('change');
                            } else {
                                $driverField.val(motorizadoToUse);
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                }
            });
            
            return true;
        }
        
        // Esperar a que el DOM esté listo
        $(document).ready(function() {
            // Detectar cambios en wpcargo_motorizo_recojo
            $(document).on('change', 'select[name="wpcargo_motorizo_recojo"]', function() {
                syncDriverSelect();
            });
            
            // Detectar cambios en wpcargo_motorizo_entrega
            $(document).on('change', 'select[name="wpcargo_motorizo_entrega"]', function() {
                syncDriverSelect();
            });
            
            // Sincronizar cuando se carga la página
            setTimeout(function() {
                syncDriverSelect();
            }, 500);
        });
    })(jQuery);
    </script>
    <?php
}, 999); // Priority al final para asegurar que jQuery esté disponible

/**
 * AJAX handler: Obtener estado del envío
 */
add_action('wp_ajax_merc_get_shipment_status', 'merc_get_shipment_status_ajax');
add_action('wp_ajax_nopriv_merc_get_shipment_status', 'merc_get_shipment_status_ajax');
function merc_get_shipment_status_ajax() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'merc_shipment_status')) {
        wp_send_json_error(['message' => 'Nonce inválido']);
        wp_die();
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'Post ID inválido']);
        wp_die();
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'wpcargo_shipment') {
        wp_send_json_error(['message' => 'Envío no encontrado']);
        wp_die();
    }
    
    $status = get_post_meta($post_id, 'wpcargo_status', true);
    
    wp_send_json_success([
        'status' => $status,
        'post_id' => $post_id
    ]);
}