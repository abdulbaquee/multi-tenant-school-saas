import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const palette = [
    '#0d6efd',
    '#198754',
    '#ffc107',
    '#dc3545',
    '#6c757d',
    '#0dcaf0',
];

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('analytics-charts');

    if (! root) {
        return;
    }

    const charts = JSON.parse(root.dataset.charts || '[]');

    charts.forEach((chartConfig, index) => {
        const canvas = document.getElementById(`analytics-chart-${index}`);

        if (! canvas) {
            return;
        }

        const isLine = chartConfig.type === 'line';

        new Chart(canvas, {
            type: chartConfig.type,
            data: {
                labels: chartConfig.labels,
                datasets: chartConfig.datasets.map((dataset, datasetIndex) => ({
                    label: dataset.label,
                    data: dataset.data,
                    backgroundColor: isLine
                        ? 'rgba(13, 110, 253, 0.15)'
                        : palette.slice(0, chartConfig.labels.length),
                    borderColor: palette[datasetIndex % palette.length],
                    borderWidth: isLine ? 2 : 1,
                    tension: 0.3,
                    fill: isLine,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: chartConfig.type === 'bar' || chartConfig.type === 'line' ? 'top' : 'bottom',
                    },
                },
            },
        });
    });
});
