@props([
    'chartData',
    'year',
])

<div {{ $attributes->merge(['class' => '']) }} x-data="invoiceTimelineChart(@js($chartData), @js($year))">
    <canvas x-ref="canvas" class="max-h-[400px]"></canvas>
</div>

@pushOnce('scripts')
<script type="module">
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

Alpine.data('invoiceTimelineChart', (chartData, year) => ({
    chart: null,

    init() {
        this.renderChart();
    },

    renderChart() {
        const ctx = this.$refs.canvas.getContext('2d');

        // Destroy existing chart if any
        if (this.chart) {
            this.chart.destroy();
        }

        // Get computed styles for dark mode colors
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

        this.chart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: textColor,
                            font: {
                                family: 'Inter, system-ui, sans-serif',
                                size: 12,
                            },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                        },
                    },
                    title: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: isDark ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDark ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.9)',
                        bodyColor: isDark ? 'rgba(255, 255, 255, 0.8)' : 'rgba(0, 0, 0, 0.8)',
                        borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: 'CHF'
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    },
                },
                scales: {
                    x: {
                        stacked: false,
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                family: 'Inter, system-ui, sans-serif',
                                size: 11,
                            },
                        },
                    },
                    y: {
                        stacked: false,
                        grid: {
                            color: gridColor,
                            drawBorder: false,
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                family: 'Inter, system-ui, sans-serif',
                                size: 11,
                            },
                            callback: function(value) {
                                return new Intl.NumberFormat('en-US', {
                                    style: 'currency',
                                    currency: 'CHF',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0,
                                }).format(value);
                            }
                        },
                        beginAtZero: true,
                    },
                },
            },
        });

        // Listen for theme changes
        const observer = new MutationObserver(() => {
            this.renderChart();
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    },
}));
</script>
@endPushOnce
