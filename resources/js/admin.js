import './bootstrap';
import * as bootstrap from 'bootstrap';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip } from 'chart.js';

window.bootstrap = bootstrap;
Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

// Confirm destructive actions: <form data-confirm="Delete this?">
document.addEventListener('submit', (e) => {
    const msg = e.target.dataset?.confirm;
    if (msg && !window.confirm(msg)) e.preventDefault();
});

// Revenue chart on the dashboard.
const canvas = document.getElementById('revenue-chart');
if (canvas) {
    const data = JSON.parse(canvas.dataset.chart);
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: Object.keys(data),
            datasets: [{
                data: Object.values(data),
                borderColor: '#B69A70',
                backgroundColor: 'rgba(182,154,112,.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 0,
                pointHoverRadius: 4,
                borderWidth: 2,
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { tooltip: { callbacks: { label: (c) => '৳' + c.parsed.y.toLocaleString('en-US') } } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8, color: '#777' } },
                y: { beginAtZero: true, grid: { color: '#eee' }, ticks: { color: '#777', callback: (v) => '৳' + v.toLocaleString('en-US') } },
            },
        },
    });
}

// Product form: variant repeater.
const variantTable = document.getElementById('variant-rows');
if (variantTable) {
    const template = document.getElementById('variant-template').innerHTML;
    let index = variantTable.querySelectorAll('tr').length;

    document.getElementById('add-variant').addEventListener('click', () => {
        variantTable.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index++));
    });
    variantTable.addEventListener('click', (e) => {
        if (e.target.closest('[data-remove-row]')) e.target.closest('tr').remove();
    });
    // Keep colour picker and hex text in sync.
    variantTable.addEventListener('input', (e) => {
        if (e.target.type === 'color') e.target.closest('td').querySelector('input[type="hidden"]').value = e.target.value;
    });
}

// Image preview for file inputs: <input type="file" data-preview="#target">
document.addEventListener('change', (e) => {
    const input = e.target;
    if (!input.matches('input[type="file"][data-preview]')) return;
    const target = document.querySelector(input.dataset.preview);
    target.innerHTML = '';
    Array.from(input.files).forEach((f) => {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.className = 'admin-thumb';
        target.appendChild(img);
    });
});

// Product list: select all + selected counter + bulk confirmation.
const selectAll = document.querySelector('[data-select-all]');
const rowChecks = () => Array.from(document.querySelectorAll('[data-row-check]'));
const updateCount = () => {
    const n = rowChecks().filter((c) => c.checked).length;
    document.querySelectorAll('[data-selected-count]').forEach((el) => { el.textContent = n; });
};
if (selectAll) {
    selectAll.addEventListener('change', () => { rowChecks().forEach((c) => { c.checked = selectAll.checked; }); updateCount(); });
    document.addEventListener('change', (e) => { if (e.target.matches('[data-row-check]')) updateCount(); });
}
const bulkForm = document.querySelector('[data-confirm-bulk]');
if (bulkForm) {
    bulkForm.addEventListener('submit', (e) => {
        const n = rowChecks().filter((c) => c.checked).length;
        const action = bulkForm.querySelector('[name="action"]');
        const pct = bulkForm.querySelector('[name="percent"]').value;
        if (!n) { e.preventDefault(); window.alert('Select at least one product first.'); return; }
        if (['increase', 'decrease', 'sale'].includes(action.value) && !pct) { e.preventDefault(); window.alert('Enter a percentage for this action.'); return; }
        const label = action.options[action.selectedIndex].text.replace('%', pct ? `${pct}%` : '%');
        if (!window.confirm(`${label} — apply to ${n} product(s)?`)) e.preventDefault();
    });
}

// Server flash messages.
document.querySelectorAll('.toast').forEach((el) => new bootstrap.Toast(el, { delay: 4000 }).show());
