/**
 * Sprint 7 (RF-04): gráficos interactivos del panel de indicadores.
 * Los datos llegan del servidor en el atributo data-datos del <canvas>.
 */
import {
    ArcElement, BarController, BarElement, CategoryScale, Chart, DoughnutController,
    Legend, LinearScale, LineController, LineElement, PointElement, Filler, Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement, BarController, BarElement, CategoryScale, DoughnutController,
    Legend, LinearScale, LineController, LineElement, PointElement, Filler, Tooltip,
);

const COLORES = {
    rojo: '#E53E3E',
    rojoSuave: 'rgba(229, 62, 62, 0.12)',
    azul: '#1f2633',
    verde: '#16A34A',
    ambar: '#F59E0B',
    gris: '#94A3B8',
};

Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = '#64748B';
Chart.defaults.plugins.legend.labels.usePointStyle = true;

const ejeEntero = { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F1F5F9' } };

const constructores = {
    visitasPorMes: (d) => ({
        type: 'line',
        data: {
            labels: Object.keys(d),
            datasets: [{
                label: 'Visitas', data: Object.values(d), borderColor: COLORES.rojo,
                backgroundColor: COLORES.rojoSuave, fill: true, tension: 0.35, pointRadius: 4,
            }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: ejeEntero, x: { grid: { display: false } } } },
    }),

    estados: (d) => ({
        type: 'doughnut',
        data: {
            labels: Object.keys(d),
            datasets: [{ data: Object.values(d), backgroundColor: [COLORES.gris, COLORES.ambar, COLORES.verde, COLORES.rojo], borderWidth: 0 }],
        },
        options: { cutout: '62%', plugins: { legend: { position: 'bottom' } } },
    }),

    provincias: (d) => ({
        type: 'bar',
        data: {
            labels: Object.keys(d),
            datasets: [
                { label: 'Adoptaron', data: Object.values(d).map((p) => p.adoptados), backgroundColor: COLORES.verde, borderRadius: 6 },
                { label: 'Desertaron', data: Object.values(d).map((p) => p.desertados), backgroundColor: COLORES.rojo, borderRadius: 6 },
            ],
        },
        options: { plugins: { legend: { position: 'bottom' } }, scales: { y: ejeEntero, x: { grid: { display: false } } } },
    }),

    series: (d) => ({
        type: 'bar',
        data: {
            labels: Object.keys(d),
            datasets: [{ label: 'Unidades', data: Object.values(d), backgroundColor: [COLORES.rojo, COLORES.azul, COLORES.gris], borderRadius: 6 }],
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: ejeEntero, y: { grid: { display: false } } } },
    }),

    anual: (d) => ({
        type: 'bar',
        data: {
            labels: Object.keys(d),
            datasets: [
                { label: 'Adoptaron', data: Object.values(d).map((a) => a.adoptados), backgroundColor: COLORES.verde, borderRadius: 6 },
                { label: 'Desertaron', data: Object.values(d).map((a) => a.desertados), backgroundColor: COLORES.rojo, borderRadius: 6 },
            ],
        },
        options: { plugins: { legend: { position: 'bottom' } }, scales: { y: ejeEntero, x: { grid: { display: false } } } },
    }),
};

document.querySelectorAll('canvas[data-grafico]').forEach((canvas) => {
    const construir = constructores[canvas.dataset.grafico];
    if (!construir) return;

    const config = construir(JSON.parse(canvas.dataset.datos));
    config.options = { ...config.options, responsive: true, maintainAspectRatio: false, animation: canvas.dataset.animar !== 'no' };
    new Chart(canvas, config);
});
