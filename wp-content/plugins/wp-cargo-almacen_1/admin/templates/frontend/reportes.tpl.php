<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h5 class="mb-3"><i class="fa fa-chart-bar mr-2"></i> Reportes</h5>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">Variedad por marca</div>
            <div class="card-body">
                <canvas id="wpca-chart-variedad" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">Unidades en stock por marca</div>
            <div class="card-body">
                <canvas id="wpca-chart-unidades" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Entradas vs Salidas (últimos 12 meses)</div>
    <div class="card-body">
        <canvas id="wpca-chart-mes" height="120"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var varLabels = <?php echo json_encode( array_column($variedad, 'marca') ); ?>;
    var varData   = <?php echo json_encode( array_map('intval', array_column($variedad, 'total')) ); ?>;
    var uniLabels = <?php echo json_encode( array_column($unidades, 'marca') ); ?>;
    var uniData   = <?php echo json_encode( array_map('intval', array_column($unidades, 'total')) ); ?>;

    // Entradas vs Salidas por mes
    var meses = {}, entData = {}, salData = {};
    <?php foreach ($por_mes as $r) : ?>
        meses['<?php echo esc_js($r->mes); ?>'] = 1;
        <?php if ($r->tipo === 'entrada') : ?>
        entData['<?php echo esc_js($r->mes); ?>'] = <?php echo (int)$r->total; ?>;
        <?php else : ?>
        salData['<?php echo esc_js($r->mes); ?>'] = <?php echo (int)$r->total; ?>;
        <?php endif; ?>
    <?php endforeach; ?>
    var mesLabels = Object.keys(meses).sort();
    var mesEnt = mesLabels.map(m => entData[m] || 0);
    var mesSal = mesLabels.map(m => salData[m] || 0);

    new Chart('wpca-chart-variedad', {
        type: 'bar',
        data: { labels: varLabels, datasets: [{ label: 'Productos', data: varData, backgroundColor: '#4a90d9' }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { precision: 0 } } } }
    });
    new Chart('wpca-chart-unidades', {
        type: 'bar',
        data: { labels: uniLabels, datasets: [{ label: 'Unidades', data: uniData, backgroundColor: '#2ecc71' }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { precision: 0 } } } }
    });
    new Chart('wpca-chart-mes', {
        type: 'line',
        data: {
            labels: mesLabels,
            datasets: [
                { label: 'Entradas', data: mesEnt, borderColor: '#2ecc71', backgroundColor: 'rgba(46,204,113,.15)', tension: .3 },
                { label: 'Salidas',  data: mesSal, borderColor: '#e74c3c', backgroundColor: 'rgba(231,76,60,.15)',  tension: .3 }
            ]
        },
        options: { scales: { y: { ticks: { precision: 0 } } } }
    });
})();
</script>
