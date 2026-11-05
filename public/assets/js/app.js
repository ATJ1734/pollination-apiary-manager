// Vanilla JS, ES modules, no build step. Small shared behaviors used by
// several pages: the delete-confirmation modal and a debounced live search.

function wireConfirmModals() {
    const backdrop = document.getElementById('confirm-modal');
    if (!backdrop) return;
    const message = document.getElementById('confirm-modal-message');
    const form = document.getElementById('confirm-modal-form');

    document.querySelectorAll('[data-confirm-delete]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            message.textContent = trigger.dataset.confirmMessage || 'Are you sure you want to delete this?';
            form.action = trigger.dataset.action || trigger.getAttribute('href');
            backdrop.classList.add('open');
        });
    });

    backdrop.querySelectorAll('[data-modal-cancel]').forEach((btn) => {
        btn.addEventListener('click', () => backdrop.classList.remove('open'));
    });
    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) backdrop.classList.remove('open');
    });
}

/** Debounced live search: types into [data-search-input], fetches [data-search-url]?q=..., swaps [data-search-target] innerHTML. */
function wireLiveSearch() {
    document.querySelectorAll('[data-search-input]').forEach((input) => {
        let timer = null;
        const targetSelector = input.dataset.searchTarget;
        const url = input.dataset.searchUrl;
        const target = document.querySelector(targetSelector);
        if (!target || !url) return;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(async () => {
                const params = new URLSearchParams(window.location.search);
                params.set('q', input.value);
                params.delete('page');
                const response = await fetch(`${url}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'fetch' },
                });
                if (response.ok) {
                    target.innerHTML = await response.text();
                    window.history.replaceState(null, '', `?${params.toString()}`);
                }
            }, 300);
        });
    });
}

/** Number steppers: [data-step-target] buttons adjust the named numeric input. */
function wireSteppers() {
    document.querySelectorAll('[data-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.stepTarget);
            if (!input) return;
            const step = Number(button.dataset.step);
            const min = Number(input.min || 0);
            const max = input.max === '' ? Infinity : Number(input.max);
            const next = Math.min(max, Math.max(min, Number(input.value || 0) + step));
            input.value = String(next);
            input.dispatchEvent(new Event('change'));
        });
    });
}

/** On/off toggles: [data-toggle] buttons flip a hidden input between "1" and "0". */
function wireToggles() {
    document.querySelectorAll('[data-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.toggleTarget);
        if (!input) return;
        const sync = () => {
            const on = input.value === '1';
            button.classList.toggle('on', on);
            button.textContent = on ? 'Yes' : 'No';
        };
        button.addEventListener('click', () => {
            input.value = input.value === '1' ? '0' : '1';
            sync();
        });
        sync();
    });
}

/** A header checkbox with data-select-all="<class>" toggles every .<class> checkbox on the page. */
function wireSelectAll() {
    document.querySelectorAll('[data-select-all]').forEach((header) => {
        const targets = document.querySelectorAll(`.${header.dataset.selectAll}`);
        header.addEventListener('change', () => {
            targets.forEach((cb) => { cb.checked = header.checked; });
        });
    });
}

/** Renders a Chart.js line/bar chart from a canvas's data-* attributes. Chart.js itself
 *  loads as a classic <script> before this module, so `window.Chart` is already set. */
function wireCharts() {
    if (typeof window.Chart === 'undefined') return;

    document.querySelectorAll('[data-chart]').forEach((canvas) => {
        const type = canvas.dataset.chart;
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const datasets = JSON.parse(canvas.dataset.datasets || '[]');
        const options = JSON.parse(canvas.dataset.options || '{}');

        new window.Chart(canvas, {
            type,
            data: { labels, datasets },
            options: { responsive: true, maintainAspectRatio: false, ...options },
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    wireConfirmModals();
    wireLiveSearch();
    wireSteppers();
    wireToggles();
    wireSelectAll();
    wireCharts();
});
