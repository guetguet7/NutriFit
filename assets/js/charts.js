// NutriFit/assets/charts.js

let chartLibPromise = null;

const loadChartLibrary = async () => {
    if (!chartLibPromise) {
        chartLibPromise = import('chart.js/auto')
            .then((module) => module.default)
            .catch(() => null);
    }

    return chartLibPromise;
};

async function initCharts() {
    const chartCanvases = document.querySelectorAll('[data-chart]');

    if (chartCanvases.length === 0) {
        return;
    }

    const Chart = await loadChartLibrary();

    //Si la librairie Chart.js n'est pas chargée, on quitte sans bloquer les autres scripts.
    if (!Chart) {
        return;
    }

    //Initialisation des graphiques pour chaque canvas
    chartCanvases.forEach((canvas) => {
        if (canvas.dataset.chartInitialized === 'true') {
            return;
        }

        //Récupération des données depuis les attributs data-*
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');

        //Si les données sont vides, on ne crée pas le graphique
        if (labels.length === 0) {
            return;
        }

        //Configuration et création du graphique
        const datasetLabel = canvas.dataset.datasetLabel || '';
        const chartType = canvas.dataset.chartType || 'line';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         

        //Marquer le graphique comme initialisé pour éviter les doublons
        canvas.dataset.chartInitialized = 'true';
        new Chart(canvas.getContext('2d'), {
            type: chartType,
            data: {
                labels,
                datasets: [
                    {
                        label: datasetLabel,
                        data: values,
                        borderColor: '#1f7d5b',
                        backgroundColor: 'rgba(31, 125, 91, 0.2)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: chartType === 'line',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    });
}

// Initialisation sur chargement classique + navigation Turbo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts);
} else {
    initCharts();
}
document.addEventListener('turbo:load', initCharts);
window.addEventListener('load', initCharts);
