<canvas id="canvas"></canvas>
<script>
    var color = Chart.helpers.color;
    var barChartData = {
        labels: ["<?php echo implode('","', $dates); ?>"],
        datasets: <?php echo wp_json_encode( $dataset ); ?>
    };

    window.onload = function() {
        var ctx = document.getElementById('canvas').getContext('2d');
        window.myBar = new Chart(ctx, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: ''
                }
            }
        });
    };
</script>