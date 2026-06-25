<?php if ( ! defined('ABSPATH') ) exit;
/**
 * Template: Configuración de Contenedores por Distrito
 * Muestra todos los distritos del select de WPCargo y permite asignar
 * cada uno a un contenedor (shipment_container) de la BD.
 */

// Preparar mapa de contenedores con nombre corto para el autocompletado JS
$cont_map = [];
foreach ($contenedores as $c) {
    $cont_map[$c['ID']] = $c['post_title'];
}

// Autocompletado: intentar matchear cada distrito a un contenedor por nombre
// Extrae palabras clave del título del contenedor para el match
function wcmas_adivinar_contenedor(string $distrito, array $contenedores): int {
    $d = mb_strtolower($distrito, 'UTF-8');
    // Palabras del distrito (split por espacios y guiones)
    $palabras = preg_split('/[\s\-–]+/', $d);
    $palabras = array_filter($palabras, fn($p) => mb_strlen($p) > 3); // ignorar "de", "la", etc.

    $mejor_id    = 0;
    $mejor_score = 0;

    foreach ($contenedores as $c) {
        $titulo = mb_strtolower($c['post_title'], 'UTF-8');
        $score  = 0;
        foreach ($palabras as $p) {
            if (mb_strpos($titulo, $p) !== false) $score++;
        }
        if ($score > $mejor_score) {
            $mejor_score = $score;
            $mejor_id    = (int)$c['ID'];
        }
    }
    return $mejor_id;
}
?>
<div class="wrap">
<h1><span class="dashicons dashicons-networking" style="font-size:26px;margin-right:6px;vertical-align:middle"></span>Contenedores por Distrito</h1>
<p class="description">
    Asigna cada distrito a su contenedor (ruta) correspondiente. Este mapeo se usa al crear envíos masivos para asignar automáticamente el <strong>contenedor de entrega</strong>.
    Los distritos vienen del campo oficial de WPCargo — el mismo select que usa el formulario de envío.
</p>

<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:5px;padding:10px 14px;margin:12px 0;font-size:13px">
    ⚠️ <strong>Revisa los distritos sin asignar</strong> (marcados en rojo) y los autocompletados (en amarillo) antes de guardar.
    El autocompletado es una sugerencia basada en el nombre — puede haber errores.
</div>

<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:20px;margin-top:8px">

<div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap">
    <input type="text" id="wcmas-cont-buscar" placeholder="Filtrar distrito..." class="regular-text" style="max-width:220px">
    <label style="font-size:13px;cursor:pointer">
        <input type="checkbox" id="wcmas-solo-vacios"> Mostrar solo sin asignar
    </label>
    <span style="margin-left:auto;font-size:12px;color:#666" id="wcmas-cont-contador"></span>
</div>

<table class="widefat fixed striped" id="wcmas-cont-tabla" style="font-size:13px">
    <thead>
        <tr>
            <th style="width:260px">Distrito (WPCargo)</th>
            <th>Contenedor asignado</th>
            <th style="width:90px;text-align:center">Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($distritos as $distrito):
        // Valor guardado
        $cont_id_guardado = $mapa[$distrito] ?? 0;

        // Si no hay valor guardado, intentar autocompletar
        $autocompletado = false;
        if (!$cont_id_guardado) {
            $cont_id_guardado = wcmas_adivinar_contenedor($distrito, $contenedores);
            $autocompletado = ($cont_id_guardado > 0);
        }
    ?>
    <tr data-distrito="<?php echo esc_attr($distrito); ?>"
        data-asignado="<?php echo $cont_id_guardado ? '1' : '0'; ?>"
        style="<?php echo !$cont_id_guardado ? 'background:#fff5f5' : ($autocompletado ? 'background:#fffde7' : ''); ?>">
        <td style="font-weight:500;vertical-align:middle">
            <?php echo esc_html($distrito); ?>
        </td>
        <td style="vertical-align:middle">
            <select name="mapa[<?php echo esc_attr($distrito); ?>]"
                    class="wcmas-cont-select"
                    data-distrito="<?php echo esc_attr($distrito); ?>"
                    style="width:100%;max-width:580px;font-size:13px">
                <option value="">— Sin asignar —</option>
                <?php foreach ($contenedores as $c): ?>
                <option value="<?php echo esc_attr($c['ID']); ?>"
                    <?php selected($cont_id_guardado, $c['ID']); ?>>
                    <?php echo esc_html($c['post_title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td style="text-align:center;vertical-align:middle;font-size:18px" class="wcmas-cont-estado">
            <?php if (!$cont_id_guardado): ?>
                <span title="Sin asignar" style="color:#dc3545">✗</span>
            <?php elseif ($autocompletado): ?>
                <span title="Autocompletado — verifica" style="color:#fd7e14">⚡</span>
            <?php else: ?>
                <span title="Asignado" style="color:#28a745">✓</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <button type="button" id="wcmas-cont-guardar" class="button button-primary button-large">
        <span class="dashicons dashicons-saved" style="vertical-align:middle;margin-right:4px"></span>
        Guardar mapa de contenedores
    </button>
    <button type="button" id="wcmas-cont-reset" class="button" style="color:#dc3545;border-color:#dc3545">
        Resetear a valores por defecto
    </button>
    <span id="wcmas-cont-msg" style="display:none;font-weight:600"></span>
</div>

<div style="margin-top:16px;padding:12px;background:#f8f9fa;border-radius:5px;font-size:12px;color:#666">
    <strong>Leyenda:</strong>
    <span style="color:#28a745;margin:0 8px">✓ Asignado manualmente</span>
    <span style="color:#fd7e14;margin:0 8px">⚡ Autocompletado (revisar)</span>
    <span style="color:#dc3545;margin:0 8px">✗ Sin asignar</span>
</div>

</div>
</div>

<script>
(function(){
var nonce   = '<?php echo esc_js(wp_create_nonce('wcmas_procesar_nonce')); ?>';
var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var defaultMapa = <?php echo wp_json_encode(wcmas_get_mapa_contenedores_default()); ?>;
var contenedoresMap = <?php echo wp_json_encode($cont_map); ?>;

// Contador de asignados
function actualizarContador() {
    var rows  = document.querySelectorAll('#wcmas-cont-tabla tbody tr:not([style*="display: none"])');
    var total = rows.length;
    var asig  = 0;
    rows.forEach(function(tr) {
        var sel = tr.querySelector('select');
        if (sel && sel.value) asig++;
    });
    document.getElementById('wcmas-cont-contador').textContent = asig + ' / ' + total + ' asignados';
}

// Filtro por nombre
document.getElementById('wcmas-cont-buscar').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#wcmas-cont-tabla tbody tr').forEach(function(tr) {
        var d = (tr.getAttribute('data-distrito') || '').toLowerCase();
        tr.style.display = d.indexOf(q) >= 0 ? '' : 'none';
    });
    actualizarContador();
});

// Filtro solo vacíos
document.getElementById('wcmas-solo-vacios').addEventListener('change', function() {
    var soloVacios = this.checked;
    document.querySelectorAll('#wcmas-cont-tabla tbody tr').forEach(function(tr) {
        var sel = tr.querySelector('select');
        if (soloVacios) {
            tr.style.display = (sel && !sel.value) ? '' : 'none';
        } else {
            tr.style.display = '';
        }
    });
    actualizarContador();
});

// Al cambiar un select: actualizar estado visual de la fila
document.querySelectorAll('.wcmas-cont-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var tr     = sel.closest('tr');
        var estado = tr.querySelector('.wcmas-cont-estado');
        if (sel.value) {
            tr.style.background = '';
            estado.innerHTML = '<span title="Asignado" style="color:#28a745">✓</span>';
            tr.setAttribute('data-asignado', '1');
        } else {
            tr.style.background = '#fff5f5';
            estado.innerHTML = '<span title="Sin asignar" style="color:#dc3545">✗</span>';
            tr.setAttribute('data-asignado', '0');
        }
        actualizarContador();
    });
});

// Guardar
document.getElementById('wcmas-cont-guardar').addEventListener('click', function() {
    var btn = this;
    var msg = document.getElementById('wcmas-cont-msg');
    var mapa = {};

    document.querySelectorAll('.wcmas-cont-select').forEach(function(sel) {
        var distrito = sel.getAttribute('data-distrito');
        var contId   = parseInt(sel.value) || 0;
        if (distrito && contId) mapa[distrito] = contId;
    });

    btn.disabled = true;
    msg.style.display = 'none';

    var fd = new FormData();
    fd.append('action', 'wcmas_guardar_contenedores');
    fd.append('nonce',  nonce);
    // Enviar cada entrada del mapa
    Object.keys(mapa).forEach(function(d) {
        fd.append('mapa[' + d + ']', mapa[d]);
    });

    fetch(ajaxUrl, {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(resp) {
            btn.disabled = false;
            if (resp.success) {
                msg.style.color   = '#28a745';
                msg.textContent   = '✓ ' + resp.data.msg + ' (' + resp.data.total + ' distritos)';
            } else {
                msg.style.color = '#dc3545';
                msg.textContent = '✗ Error al guardar.';
            }
            msg.style.display = 'inline';
            setTimeout(function(){ msg.style.display = 'none'; }, 4000);
        })
        .catch(function() {
            btn.disabled = false;
            msg.style.color   = '#dc3545';
            msg.textContent   = '✗ Error de conexión.';
            msg.style.display = 'inline';
        });
});

// Reset a valores por defecto
document.getElementById('wcmas-cont-reset').addEventListener('click', function() {
    if (!confirm('¿Resetear el mapa a los valores por defecto? Perderás cualquier cambio manual.')) return;
    document.querySelectorAll('.wcmas-cont-select').forEach(function(sel) {
        var distrito = sel.getAttribute('data-distrito');
        var defVal   = defaultMapa[distrito] || 0;
        sel.value = defVal ? String(defVal) : '';
        sel.dispatchEvent(new Event('change'));
    });
    actualizarContador();
});

// Inicializar contador
actualizarContador();
})();
</script>
