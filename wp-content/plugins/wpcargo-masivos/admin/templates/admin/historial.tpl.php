<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap">
<h1>Historial de Importaciones Masivas</h1>
<hr class="wp-header-end">

<?php if ($detalle): ?>
<!-- ═══ DETALLE DE SESIÓN ═════════════════════════════════════════════ -->
<p><a href="<?php echo esc_url(wcmas_url('wcmas-historial')); ?>" class="button">← Volver al listado</a></p>

<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
    <?php foreach([
        ['Cargado por',  $detalle->cargado_por  ?: "ID #{$detalle->user_id}"],
        ['Asignado a',   $detalle->asignado_nombre ?: "ID #{$detalle->asignado_a}"],
        ['Fecha',        date_i18n('d/m/Y H:i', strtotime($detalle->fecha))],
        ['Total filas',  $detalle->total_filas],
        ['✅ Creados',   $detalle->total_ok],
        ['❌ Errores',   $detalle->total_errores],
    ] as [$l,$v]): ?>
    <div style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:10px 16px;min-width:120px">
        <div style="font-size:10px;color:#888;text-transform:uppercase;font-weight:700"><?php echo esc_html($l); ?></div>
        <div style="font-size:1.2rem;font-weight:700;margin-top:4px"><?php echo esc_html($v); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php
$trackings = json_decode($detalle->trackings_json ?? '[]', true) ?: [];
$errores   = json_decode($detalle->errores_json   ?? '[]', true) ?: [];
?>

<?php if ($trackings): ?>
<h3>Envíos creados</h3>
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>Fila</th><th>Tracking</th><th>Ver envío</th></tr></thead>
    <tbody>
    <?php foreach($trackings as $t): ?>
        <tr>
            <td><?php echo intval($t['fila']); ?></td>
            <td><code><?php echo esc_html($t['tracking']); ?></code></td>
            <td><?php if($t['post_id']): ?><a href="<?php echo esc_url(get_edit_post_link($t['post_id'])); ?>" target="_blank">Ver envío #<?php echo intval($t['post_id']); ?></a><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($errores): ?>
<h3 style="color:#d63638;margin-top:20px">Filas con errores</h3>
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>Fila</th><th>Errores</th></tr></thead>
    <tbody>
    <?php foreach($errores as $e): ?>
        <tr>
            <td><?php echo intval($e['fila']); ?></td>
            <td><?php echo esc_html(implode('; ', is_array($e['errores']) ? $e['errores'] : [])); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php else: ?>
<!-- ═══ LISTADO ══════════════════════════════════════════════════════ -->
<?php if (empty($lista)): ?>
<p class="description">Aún no hay importaciones registradas.</p>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Cargado por</th>
            <th>Asignado a</th>
            <th style="text-align:center">Total</th>
            <th style="text-align:center">✅ OK</th>
            <th style="text-align:center">❌ Err.</th>
            <th style="width:100px">Detalle</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($lista as $h): ?>
        <tr>
            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($h->fecha))); ?></td>
            <td><?php echo esc_html($h->cargado_por ?: "ID #{$h->user_id}"); ?></td>
            <td><?php echo esc_html($h->asignado_nombre ?: "ID #{$h->asignado_a}"); ?></td>
            <td style="text-align:center"><?php echo intval($h->total_filas); ?></td>
            <td style="text-align:center"><span style="color:#00a32a;font-weight:700"><?php echo intval($h->total_ok); ?></span></td>
            <td style="text-align:center"><?php echo $h->total_errores > 0 ? '<span style="color:#d63638;font-weight:700">'.intval($h->total_errores).'</span>' : '<span style="color:#aaa">0</span>'; ?></td>
            <td><a href="<?php echo esc_url(wcmas_url('wcmas-historial',['ver'=>$h->id])); ?>" class="button button-small">Ver</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($total > $per_page): ?>
<div style="margin-top:16px">
    <?php
    $total_pages = ceil($total / $per_page);
    for ($p = 1; $p <= $total_pages; $p++) {
        $url = wcmas_url('wcmas-historial', ['paged' => $p]);
        echo $p === $page_num
            ? "<span style='display:inline-block;padding:4px 10px;background:#2271b1;color:#fff;border-radius:3px;margin-right:4px'>{$p}</span>"
            : "<a href='".esc_url($url)."' style='display:inline-block;padding:4px 10px;border:1px solid #ddd;border-radius:3px;margin-right:4px'>{$p}</a>";
    }
    ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
</div>
