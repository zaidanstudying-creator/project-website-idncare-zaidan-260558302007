<div class="status-composition-layout">
    <div class="chart-wrap chart-wrap-doughnut">
        <canvas id="status-doughnut-chart" role="img" aria-label="Grafik donat distribusi status laporan"></canvas>
    </div>
    <ul class="status-composition-legend" aria-label="Legenda status laporan">
        @foreach($analytics['status']['labels'] as $index => $label)
            <li><span class="status-composition-swatch status-composition-swatch-{{ $index }}" aria-hidden="true"></span>{{ $label }}</li>
        @endforeach
    </ul>
</div>
