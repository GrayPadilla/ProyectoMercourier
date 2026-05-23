<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap">
<h1><span class="dashicons dashicons-grid-view" style="font-size:24px;vertical-align:middle;margin-right:6px"></span>Carga Masiva — Vista Administrador</h1>
<hr class="wp-header-end">

<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:10px 14px;margin-bottom:16px;font-size:13px">
    <span class="dashicons dashicons-info" style="color:#856404;vertical-align:middle"></span>
    Como administrador puedes asignar los envíos a cualquier cliente. El tracking se genera automáticamente según la configuración de WPCargo.
</div>

<!-- ═══ Selector de cliente destino con Select2 ════════════════════ -->
<div class="postbox" style="max-width:620px;margin-bottom:16px">
    <div class="postbox-header"><h2 class="hndle" style="font-size:13px">👤 Asignar envíos a cliente</h2></div>
    <div class="inside" style="padding-bottom:14px">

        <div style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap">
            <div style="flex:1;min-width:280px">
                <label for="wcmas-admin-user" class="screen-reader-text">Seleccionar cliente</label>
                <!-- Select2 se inicializa via JS con búsqueda AJAX -->
                <select id="wcmas-admin-user" name="asignar_a" style="width:100%">
                    <option value="">— Buscar cliente por nombre o email —</option>
                </select>
                <p class="description" style="margin-top:6px">
                    <span class="dashicons dashicons-search" style="font-size:13px;vertical-align:middle"></span>
                    Escribe al menos 2 caracteres para buscar entre los clientes registrados.
                </p>
            </div>
            <!-- Panel de datos del remitente autocompletado -->
            <div id="wcmas-remitente-panel" style="display:none;flex:1;min-width:240px;background:#f6f7f7;border:1px solid #ddd;border-radius:4px;padding:10px 12px;font-size:12px">
                <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#666">Datos del remitente detectados</strong>
                <table style="margin-top:6px;width:100%;border-collapse:collapse" id="wcmas-remitente-tabla">
                    <tr><td style="color:#888;padding:2px 4px 2px 0;width:90px">Marca/Nombre</td><td id="wcmas-rem-nombre" style="font-weight:600"></td></tr>
                    <tr><td style="color:#888;padding:2px 4px 2px 0">Celular</td><td id="wcmas-rem-telefono"></td></tr>
                    <tr><td style="color:#888;padding:2px 4px 2px 0">Dirección</td><td id="wcmas-rem-direccion"></td></tr>
                    <tr><td style="color:#888;padding:2px 4px 2px 0">Dist. Recojo</td><td id="wcmas-rem-distrito"></td></tr>
                    <tr><td style="color:#888;padding:2px 4px 2px 0">Email</td><td id="wcmas-rem-email"></td></tr>
                    <tr id="wcmas-rem-maps-row" style="display:none"><td style="color:#888;padding:2px 4px 2px 0">Maps</td><td id="wcmas-rem-maps"></td></tr>
                </table>
                <p style="margin:6px 0 0;color:#888;font-size:11px">
                    <span class="dashicons dashicons-info" style="font-size:11px;vertical-align:middle"></span>
                    Estos datos se guardarán como remitente en cada envío creado.
                </p>
            </div>
        </div>

    </div>
</div>

<script>
// Datos para el JS de la grilla
var WCMAS_ADMIN_NONCE = '<?php echo esc_js(wp_create_nonce('wcmas_procesar_nonce')); ?>';
var WCMAS_AJAX_URL    = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
</script>

<!-- ═══ Grilla reutilizada ═════════════════════════════════════════ -->
<?php
$es_admin  = true;
$page_url  = '';
$historial = WCMAS_Historial::obtener(5, 0, 0);
wcmas_tpl('frontend/grilla.tpl.php', compact('columnas','todas','usuarios','nonce','filas_init','es_admin','page_url','historial'));
?>

<!-- ═══ Script Select2 + autocompletado remitente ══════════════════ -->
<script>
(function($){
    if (typeof $.fn.select2 === 'undefined') {
        console.warn('WPCargo Masivos: Select2 no cargó. Verifica la conexión a CDN.');
        return;
    }

    var $sel   = $('#wcmas-admin-user');
    var $panel = $('#wcmas-remitente-panel');
    var nonce  = WCMAS_ADMIN_NONCE || (typeof WCMAS !== 'undefined' ? WCMAS.nonce : '');

    // ── Inicializar Select2 con búsqueda AJAX ──────────────────────
    $sel.select2({
        placeholder      : '— Buscar cliente por nombre o email —',
        allowClear       : true,
        minimumInputLength: 2,
        language         : {
            inputTooShort : function(){ return 'Escribe al menos 2 caracteres para buscar...'; },
            noResults     : function(){ return 'No se encontraron clientes.'; },
            searching     : function(){ return 'Buscando...'; },
            loadingMore   : function(){ return 'Cargando más resultados...'; },
        },
        ajax: {
            url      : WCMAS_AJAX_URL,
            dataType : 'json',
            delay    : 300,
            data: function(params){
                return {
                    action : 'wcmas_buscar_clientes',
                    nonce  : nonce,
                    q      : params.term,
                    page   : params.page || 1,
                };
            },
            processResults: function(data, params){
                return {
                    results   : data.results || [],
                    pagination: data.pagination || { more: false },
                };
            },
            cache: true,
        },
        width: '100%',
    });

    // ── Al seleccionar un cliente: cargar datos del remitente ──────
    $sel.on('select2:select', function(e){
        var userId = e.params.data.id;
        if (!userId) { $panel.hide(); return; }

        $panel.show();
        $('#wcmas-rem-nombre, #wcmas-rem-telefono, #wcmas-rem-direccion, #wcmas-rem-email, #wcmas-rem-ciudad')
            .text('Cargando...');

        $.post(WCMAS_AJAX_URL, {
            action  : 'wcmas_datos_remitente',
            nonce   : nonce,
            user_id : userId,
        }, function(resp){
            if (resp.success && resp.data) {
                var d = resp.data;
                $('#wcmas-rem-nombre')   .text(d.nombre    || '—');
                $('#wcmas-rem-telefono') .text(d.telefono  || '—');
                $('#wcmas-rem-direccion').text(d.direccion || '—');
                $('#wcmas-rem-distrito') .text(d.distrito  || '—');
                $('#wcmas-rem-email')    .text(d.email     || '—');
                if (d.link_maps) {
                    $('#wcmas-rem-maps').html('<a href="'+d.link_maps+'" target="_blank" rel="noopener">Ver mapa ↗</a>');
                    $('#wcmas-rem-maps-row').show();
                } else {
                    $('#wcmas-rem-maps-row').hide();
                }
            } else {
                $panel.hide();
            }
        }).fail(function(){ $panel.hide(); });
    });

    // ── Al limpiar la selección ────────────────────────────────────
    $sel.on('select2:clear select2:unselect', function(){
        $panel.hide();
    });

    // ── Sincronizar el valor del Select2 con el campo asignar_a ───
    // que lee el JS de la grilla (WCMAS.es_admin)
    $sel.on('change', function(){
        // El valor ya lo toma el form de envío directamente del select#wcmas-admin-user
        // No se necesita nada más aquí.
    });

})(jQuery);
</script>
</div>
