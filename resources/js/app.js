import Chart from 'chart.js/auto';

document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const animateIfAllowed = (element, keyframes, options) => {
    if (reduceMotion.matches || typeof element?.animate !== 'function') return;
    element.animate(keyframes, { ...options, easing: options.easing || 'cubic-bezier(.2,.75,.25,1)' });
};

// Small press feedback makes controls feel responsive without changing layout.
const pressedAnimations = new WeakMap();
const pressedControls = new Set();
const interactiveSelector = 'a[href], button:not(:disabled), [role="button"], [role="tab"], [role="menuitem"], input:not([type="hidden"]), select, textarea, summary, .dashboard-stat, .status-flow-card';
document.addEventListener('pointerdown', (event) => {
    if (event.button !== 0 || reduceMotion.matches) return;
    const control = event.target.closest(interactiveSelector);
    if (!control || control.hasAttribute('aria-disabled')) return;
    pressedAnimations.get(control)?.cancel();
    pressedAnimations.set(control, animateIfAllowed(control, [
        { transform: 'scale(1)' },
        { transform: 'scale(.985)' },
    ], { duration: 90, fill: 'forwards' }));
    pressedControls.add(control);
});
const releaseControls = () => {
    pressedControls.forEach((control) => {
        if (!pressedAnimations.has(control)) return;
        pressedAnimations.get(control)?.cancel();
        pressedAnimations.delete(control);
        animateIfAllowed(control, [
            { transform: 'scale(.985)' },
            { transform: 'scale(1)' },
        ], { duration: 130 });
    });
    pressedControls.clear();
};
document.addEventListener('pointerup', releaseControls);
document.addEventListener('pointercancel', releaseControls);
window.addEventListener('blur', releaseControls);
document.addEventListener('click', (event) => {
    if (event.detail !== 0 || reduceMotion.matches) return;
    const control = event.target.closest(interactiveSelector);
    if (control && !control.hasAttribute('aria-disabled')) {
        animateIfAllowed(control, [
            { transform: 'scale(1)' },
            { transform: 'scale(.985)', offset: .45 },
            { transform: 'scale(1)' },
        ], { duration: 170 });
    }
});
document.addEventListener('focusin', (event) => {
    const control = event.target.closest?.('input:not([type="hidden"]), select, textarea');
    if (!control) return;
    animateIfAllowed(control, [
        { boxShadow: '0 0 0 0 rgba(11,95,255,0)' },
        { boxShadow: '0 0 0 3px rgba(11,95,255,.16)' },
    ], { duration: 180, fill: 'forwards' });
});
document.addEventListener('focusout', (event) => {
    const control = event.target.closest?.('input:not([type="hidden"]), select, textarea');
    if (!control) return;
    control.getAnimations().forEach((animation) => animation.cancel());
});

const sidebar = document.getElementById('app-sidebar');
const menuToggle = document.querySelector('[data-menu-toggle]');
const backdrop = document.querySelector('.portal-backdrop');
const workspace = document.getElementById('app-main')?.parentElement;

if (sidebar && menuToggle && workspace) {
    const mobile = window.matchMedia('(max-width: 1023px)');
    let returnFocus = null;
    const close = (restore = true) => {
        sidebar.dataset.open = 'false';
        if (backdrop) backdrop.dataset.open = 'false';
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
        menuToggle.setAttribute('aria-expanded', 'false');
        workspace.inert = false;
        if (restore && mobile.matches) (returnFocus || menuToggle).focus();
    };
    menuToggle.addEventListener('click', () => {
        if (!mobile.matches) return;
        returnFocus = document.activeElement;
        sidebar.dataset.open = 'true';
        if (backdrop) backdrop.dataset.open = 'true';
        sidebar.setAttribute('role', 'dialog');
        sidebar.setAttribute('aria-modal', 'true');
        menuToggle.setAttribute('aria-expanded', 'true');
        workspace.inert = true;
        sidebar.querySelector('[data-menu-close]')?.focus();
    });
    document.querySelectorAll('[data-menu-close]').forEach((button) => button.addEventListener('click', () => close()));
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => close(false)));
    document.addEventListener('keydown', (event) => {
        if (sidebar.dataset.open !== 'true') return;
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (event.key === 'Tab') {
            const controls = [...sidebar.querySelectorAll('a[href],button:not([disabled])')].filter((item) => item.offsetParent !== null);
            if (event.shiftKey && document.activeElement === controls[0]) { event.preventDefault(); controls.at(-1)?.focus(); }
            else if (!event.shiftKey && document.activeElement === controls.at(-1)) { event.preventDefault(); controls[0]?.focus(); }
        }
    });
    mobile.addEventListener('change', (event) => { if (!event.matches) close(false); });
}

document.querySelectorAll('[data-modal-open]').forEach((link) => {
    const dialog = document.getElementById(link.dataset.modalOpen);
    if (!(dialog instanceof HTMLDialogElement)) return;
    link.addEventListener('click', (event) => {
        if (typeof dialog.showModal !== 'function') return;
        event.preventDefault();
        dialog.showModal();
        animateIfAllowed(dialog, [
            { opacity: 0, transform: 'translateY(12px) scale(.985)' },
            { opacity: 1, transform: 'translateY(0) scale(1)' },
        ], { duration: 220 });
    });
});
document.querySelectorAll('dialog[data-dialog-open-on-load]').forEach((dialog) => {
    if (dialog instanceof HTMLDialogElement && typeof dialog.showModal === 'function') {
        dialog.showModal();
    }
});
const userRoleSelect = document.getElementById('role');
const studentFields = document.querySelector('[data-student-fields]');
const studentProgramField = document.querySelector('[data-student-program-field]');
const nonstudentUnitField = document.querySelector('[data-nonstudent-unit-field]');
const studentProgramSelect = document.querySelector('[data-student-program-select]');
const nonstudentUnitInput = document.querySelector('[data-nonstudent-unit-input]');
if (userRoleSelect instanceof HTMLSelectElement && studentFields instanceof HTMLFieldSetElement
    && studentProgramField instanceof HTMLDivElement && nonstudentUnitField instanceof HTMLDivElement
    && studentProgramSelect instanceof HTMLSelectElement && nonstudentUnitInput instanceof HTMLInputElement) {
    const studentRequiredFields = [...studentFields.querySelectorAll('[data-required-for-student]')];
    const syncStudentFields = () => {
        const isStudent = userRoleSelect.value === 'mahasiswa';
        studentFields.hidden = !isStudent;
        studentRequiredFields.forEach((field) => { field.required = isStudent; });
        studentProgramField.hidden = !isStudent;
        studentProgramSelect.disabled = !isStudent;
        studentProgramSelect.required = isStudent;
        nonstudentUnitField.hidden = isStudent;
        nonstudentUnitInput.disabled = isStudent;
    };
    userRoleSelect.addEventListener('change', syncStudentFields);
    syncStudentFields();
}
document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('[data-report-tab]').forEach((tab) => {
    tab.addEventListener('click', (event) => {
        event.preventDefault();
        const dialog = tab.closest('dialog');
        const tabs = [...dialog.querySelectorAll('[data-report-tab]')];
        tabs.forEach((item) => {
            const selected = item === tab;
            item.setAttribute('aria-selected', String(selected));
            item.tabIndex = selected ? 0 : -1;
            const panel = dialog.querySelector(`#${CSS.escape(item.getAttribute('aria-controls'))}`);
            if (panel) {
                panel.hidden = !selected;
                if (selected) animateIfAllowed(panel, [
                    { opacity: 0, transform: 'translateY(6px)' },
                    { opacity: 1, transform: 'translateY(0)' },
                ], { duration: 180 });
            }
        });
    });
    tab.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const tabs = [...tab.closest('[role="tablist"]').querySelectorAll('[data-report-tab]')];
        const index = tabs.indexOf(tab);
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length;
        tabs[next].focus();
        tabs[next].click();
    });
});


document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!(input instanceof HTMLInputElement)) return;
    const showIcon = button.querySelector('[data-password-show-icon]');
    const hideIcon = button.querySelector('[data-password-hide-icon]');
    button.hidden = false;
    button.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        const label = visible ? 'Sembunyikan password' : 'Tampilkan password';
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        if (showIcon) showIcon.hidden = visible;
        if (hideIcon) hideIcon.hidden = !visible;
        button.setAttribute('aria-pressed', String(visible));
    });
});

const reportPhotoInput = document.querySelector('#photos[data-file-preview]');
if (reportPhotoInput instanceof HTMLInputElement && reportPhotoInput.form) {
    const reportForm = reportPhotoInput.form;
    const uploadStatus = reportForm.querySelector('[data-photo-upload-status]');
    const submitButtons = [...reportForm.querySelectorAll('button[type="submit"], input[type="submit"]')];
    const maxPhotoPayloadBytes = 1.3 * 1024 * 1024;
    let isOptimizingPhotos = false;

    const formatFileSize = (bytes) => `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    const encodePhoto = async (file, maxDimension, quality) => {
        if (!file.type.startsWith('image/') || file.size <= 150 * 1024) return file;

        const bitmap = await createImageBitmap(file);
        try {
            const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));
            const context = canvas.getContext('2d');
            if (!context) throw new Error('Foto tidak dapat diproses di browser ini.');

            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise((resolve, reject) => {
                canvas.toBlob((result) => result ? resolve(result) : reject(new Error('Foto tidak dapat dikompres.')), 'image/jpeg', quality);
            });
            const baseName = file.name.replace(/\.[^.]+$/, '') || 'foto';

            return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg', lastModified: file.lastModified });
        } finally {
            bitmap.close();
        }
    };

    reportPhotoInput.addEventListener('change', async () => {
        if (isOptimizingPhotos) return;

        const selectedFiles = [...(reportPhotoInput.files ?? [])];
        const originalSize = selectedFiles.reduce((total, file) => total + file.size, 0);
        if (selectedFiles.length === 0) {
            uploadStatus.textContent = 'Unggahan foto dibatasi agar laporan bisa terkirim dengan lancar.';
            return;
        }
        if (originalSize <= maxPhotoPayloadBytes) {
            uploadStatus.textContent = `${selectedFiles.length} foto dipilih · ${formatFileSize(originalSize)}.`;
            return;
        }

        isOptimizingPhotos = true;
        reportPhotoInput.setAttribute('aria-busy', 'true');
        submitButtons.forEach((button) => { button.disabled = true; });
        uploadStatus.textContent = `Mengoptimalkan ${selectedFiles.length} foto agar dapat dikirim…`;

        try {
            let optimizedFiles = selectedFiles;
            const compressionSteps = [
                { maxDimension: 1920, quality: 0.82 },
                { maxDimension: 1600, quality: 0.74 },
                { maxDimension: 1440, quality: 0.66 },
                { maxDimension: 1280, quality: 0.58 },
                { maxDimension: 1120, quality: 0.5 },
                { maxDimension: 960, quality: 0.42 },
            ];

            for (const step of compressionSteps) {
                const compressed = [];
                for (const file of selectedFiles) {
                    compressed.push(await encodePhoto(file, step.maxDimension, step.quality));
                }
                optimizedFiles = compressed;
                if (optimizedFiles.reduce((total, file) => total + file.size, 0) <= maxPhotoPayloadBytes) break;
            }

            const optimizedSize = optimizedFiles.reduce((total, file) => total + file.size, 0);
            if (optimizedSize > maxPhotoPayloadBytes) {
                throw new Error('Ukuran foto masih terlalu besar. Pilih lebih sedikit foto atau kecilkan ukuran fotonya.');
            }

            const transfer = new DataTransfer();
            optimizedFiles.forEach((file) => transfer.items.add(file));
            reportPhotoInput.files = transfer.files;
            uploadStatus.textContent = `Ukuran foto diperkecil dari ${formatFileSize(originalSize)} menjadi ${formatFileSize(optimizedSize)}.`;
            reportPhotoInput.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (error) {
            reportPhotoInput.value = '';
            uploadStatus.textContent = error instanceof Error
                ? error.message
                : 'Foto tidak dapat diproses. Pilih foto lain dengan ukuran lebih kecil.';
            reportPhotoInput.dispatchEvent(new Event('change', { bubbles: true }));
        } finally {
            isOptimizingPhotos = false;
            reportPhotoInput.removeAttribute('aria-busy');
            submitButtons.forEach((button) => { button.disabled = false; });
        }
    });

    reportForm.addEventListener('submit', (event) => {
        if (!isOptimizingPhotos) return;
        event.preventDefault();
        uploadStatus.textContent = 'Tunggu sampai foto selesai dioptimalkan sebelum mengirim laporan.';
    });
}

document.querySelectorAll('[data-file-preview]').forEach((input) => {
    const preview = document.getElementById(input.dataset.filePreview);
    if (!(input instanceof HTMLInputElement) || !preview) return;
    let urls = [];
    input.addEventListener('change', () => {
        urls.forEach((url) => URL.revokeObjectURL(url)); urls = []; preview.replaceChildren();
        [...(input.files ?? [])].filter((file) => file.type.startsWith('image/')).forEach((file) => {
            const url = URL.createObjectURL(file); urls.push(url);
            const image = document.createElement('img'); image.src = url; image.alt = `Pratinjau ${file.name}`;
            image.className = 'aspect-square w-full rounded-lg object-cover'; preview.append(image);
            animateIfAllowed(image, [
                { opacity: 0, transform: 'scale(.96)' },
                { opacity: 1, transform: 'scale(1)' },
            ], { duration: 200 });
        });
    });
});

document.querySelectorAll('[data-ai-title-button]').forEach((button) => {
    let latestRequest = 0;
    let activeRequest = null;

    button.addEventListener('click', async () => {
        const form = button.closest('form');
        const status = form.querySelector('[data-ai-title-status]');
        const options = form.querySelector('[data-ai-title-options]');
        const descriptionField = form.querySelector('#description');
        const description = descriptionField.value;
        const requestId = ++latestRequest;
        activeRequest?.abort();
        activeRequest = new AbortController();
        const originalLabel = button.dataset.originalLabel || button.textContent;
        button.dataset.originalLabel = originalLabel;

        options.replaceChildren();
        status.textContent = 'Meminta saran judul…';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Memuat saran…';

        try {
            const body = new URLSearchParams({
                _token: form.querySelector('input[name="_token"]').value,
                type: button.dataset.type,
                description,
            });

            const response = await fetch(button.dataset.endpoint, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body,
                signal: activeRequest.signal,
            });

            const result = await response.json().catch(() => null);
            if (requestId !== latestRequest) {
                return;
            }

            if (descriptionField.value !== description) {
                status.textContent = 'Uraian berubah. Minta kembali saran agar sesuai dengan uraian terbaru.';
                return;
            }

            if (!response.ok) {
                status.textContent = typeof result?.message === 'string'
                    ? result.message
                    : 'Saran AI belum tersedia. Kamu tetap bisa menulis judul dan mengirim laporan.';
                return;
            }

            if (!Array.isArray(result?.titles) || result.titles.length !== 3
                || result.titles.some((title) => typeof title !== 'string' || title.trim() === '' || title.length > 150)) {
                throw new Error('Saran belum tersedia. Kamu tetap bisa menulis judul sendiri.');
            }

            result.titles.forEach((title) => {
                const choice = document.createElement('button');
                choice.type = 'button';
                choice.className = 'rounded-full bg-blue-50 px-4 py-2 text-left text-xs font-semibold text-[#0B5FFF] hover:bg-blue-100';
                choice.textContent = title;
                choice.addEventListener('click', () => { form.querySelector('#title').value = title; });
                options.append(choice);
            });
            status.textContent = 'Pilih saran untuk mengisi judul, lalu periksa sebelum mengirim.';
        } catch (error) {
            if (requestId !== latestRequest || error.name === 'AbortError') {
                return;
            }

            status.textContent = 'Saran AI belum tersedia. Kamu tetap bisa menulis judul dan mengirim laporan.';
        } finally {
            if (requestId === latestRequest) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.textContent = originalLabel;
                activeRequest = null;
            }
        }
    });
});

const chartDataElement = document.getElementById('dashboard-chart-data');

if (chartDataElement) {
    const data = JSON.parse(chartDataElement.dataset.payload);
    const palette = ['#0B5FFF', '#1AA7E8', '#8BB7FF', '#0A1F44', '#4389F4', '#78A9FF'];
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: window.matchMedia('(max-width: 760px)').matches ? 'bottom' : 'right', labels: { usePointStyle: true, boxWidth: 8, padding: 16, generateLabels: (chart) => Chart.defaults.plugins.legend.labels.generateLabels(chart).map((item) => {
            const color = chart.config.type === 'line' ? chart.data.datasets[item.datasetIndex]?.borderColor : item.fillStyle;
            return { ...item, fillStyle: color, lineWidth: 0, strokeStyle: color };
        }) } } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#edf2fb' } }, x: { grid: { color: '#edf2fb' } } },
    };
    const hasValues = (values) => Array.isArray(values) && values.some((value) => Number(value) > 0);
    const appendDataTable = (canvas, labels, datasets) => {
        const details = document.createElement('details');
        details.className = 'chart-data-details';
        const summary = document.createElement('summary');
        summary.textContent = 'Lihat data grafik sebagai tabel';
        summary.setAttribute('aria-label', `Lihat tabel data: ${canvas.getAttribute('aria-label')}`);
        details.append(summary);

        const table = document.createElement('table');
        const caption = document.createElement('caption');
        caption.textContent = canvas.getAttribute('aria-label');
        table.append(caption);
        const head = document.createElement('thead');
        const headerRow = document.createElement('tr');
        [ 'Kategori', ...datasets.map((dataset) => dataset.label) ].forEach((label) => {
            const cell = document.createElement('th');
            cell.scope = 'col';
            cell.textContent = label;
            headerRow.append(cell);
        });
        head.append(headerRow);
        table.append(head);

        const body = document.createElement('tbody');
        labels.forEach((label, index) => {
            const row = document.createElement('tr');
            const heading = document.createElement('th');
            heading.scope = 'row';
            heading.textContent = Array.isArray(label) ? label.join(' ') : label;
            row.append(heading);
            datasets.forEach((dataset) => {
                const cell = document.createElement('td');
                cell.textContent = String(dataset.data[index] ?? 0);
                row.append(cell);
            });
            body.append(row);
        });
        table.append(body);
        details.append(table);
        const chartLayout = canvas.closest('.type-composition-layout, .unit-comparison-layout, .status-composition-layout');
        (chartLayout || canvas.parentElement).after(details);
    };
    const render = (id, type, labels, datasets, options = {}) => {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const series = datasets.map((dataset, index) => ({
            ...dataset,
            backgroundColor: dataset.backgroundColor || (type === 'line' ? `${palette[index % palette.length]}33` : palette[index % palette.length]),
            borderColor: dataset.borderColor || palette[index % palette.length],
            borderWidth: dataset.borderWidth ?? (type === 'bar' ? 0 : 2),
            borderRadius: type === 'bar' ? 8 : undefined,
            borderSkipped: false,
            ...(type === 'line' ? { fill: true, tension: 0.35, pointRadius: 3, pointHoverRadius: 5 } : {}),
        }));

        const hasChartValues = series.some((dataset) => hasValues(dataset.data));
        if (!hasChartValues && (labels.length === 0 || series.length === 0)) {
            canvas.hidden = true;
            const empty = document.createElement('p');
            empty.className = 'dashboard-chart-empty';
            empty.textContent = 'Belum ada data untuk ditampilkan.';
            canvas.parentElement.append(empty);
            return;
        }

        appendDataTable(canvas, labels, series);
        new Chart(canvas, {
            type,
            data: { labels, datasets: series },
            options: {
                ...chartOptions,
                ...options,
                plugins: {
                    ...chartOptions.plugins,
                    ...options.plugins,
                    legend: {
                        ...chartOptions.plugins.legend,
                        ...options.plugins?.legend,
                        labels: { ...chartOptions.plugins.legend.labels, ...options.plugins?.legend?.labels },
                    },
                },
                scales: type === 'doughnut' ? {} : { ...chartOptions.scales, ...options.scales },
            },
        });
        if (!hasChartValues) {
            const empty = document.createElement('p');
            empty.className = 'dashboard-chart-empty-note';
            empty.textContent = 'Belum ada data untuk ditampilkan.';
            canvas.parentElement.after(empty);
        }
    };
    const rowsToSeries = (rows) => [{ label: 'Laporan', data: rows.map((row) => row.count) }];
    const renderDoughnut = (id, chart, colors = [], showLegend = true) => render(id, 'doughnut', chart.labels, [{ label: 'Jumlah', data: chart.values, backgroundColor: colors, borderColor: colors, borderWidth: 0 }], {
        cutout: '66%',
        plugins: { legend: { display: showLegend, position: window.matchMedia('(max-width: 760px)').matches ? 'bottom' : 'right', labels: { usePointStyle: true, boxWidth: 8, padding: 12 } } },
    });

    render('monthly-type-chart', 'line', data.monthlyByType.labels, data.monthlyByType.datasets);
    const statusColors = ['#B8D2FF', '#8BB7FF', '#0B5FFF', '#1AA7E8', '#0A1F44'];
    renderDoughnut('status-doughnut-chart', data.status, statusColors, false);
    renderDoughnut('type-doughnut-chart', { labels: data.byType.map((row) => row.label), values: data.byType.map((row) => row.count) }, ['#0B5FFF', '#1AA7E8', '#0A1F44'], false);
    if (data.aspirationVisibility) renderDoughnut('visibility-chart', {
        labels: data.aspirationVisibility.map((row) => row.label),
        values: data.aspirationVisibility.map((row) => row.count),
    }, ['#0A1F44', '#0B5FFF', '#8BB7FF']);
    render('location-chart', 'bar', data.byLocation.map((row) => row.label), rowsToSeries(data.byLocation), {
        ...(data.role === 'admin_sarpras' ? {} : { indexAxis: 'y' }),
    });
    if (data.unitComparison) {
        render('unit-chart', 'bar', data.unitComparison.labels, data.unitComparison.datasets.map((dataset, index) => ({
            ...dataset,
            backgroundColor: ['#0B5FFF', '#1AA7E8'][index % 2],
            borderColor: ['#0B5FFF', '#1AA7E8'][index % 2],
        })), {
            plugins: { legend: { display: true, position: 'right' } },
            scales: { x: { grid: { color: '#edf2fb' }, ticks: { autoSkip: false, minRotation: 0, maxRotation: 0, padding: 6 } } },
        });
    }
    if (data.monthlyLogins) render('login-chart', 'bar', data.monthlyLogins.labels, [{ label: 'Login', data: data.monthlyLogins.values, backgroundColor: '#8BB7FF' }]);
}
