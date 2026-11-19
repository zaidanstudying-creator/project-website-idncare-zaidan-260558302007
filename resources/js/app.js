import Chart from 'chart.js/auto';

document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

const themePreferenceKey = 'idn-care-theme-settings';
const allowedThemeModes = ['device', 'light', 'dark', 'scheduled'];
const isDarkTheme = () => document.documentElement.dataset.theme === 'dark';
const normalizeThemeTime = (time, fallback) => /^([01]\d|2[0-3]):[0-5]\d$/.test(time || '') ? time : fallback;
const readThemePreference = () => {
    try {
        const saved = JSON.parse(localStorage.getItem(themePreferenceKey) || 'null');
        if (saved && allowedThemeModes.includes(saved.mode)) {
            return {
                mode: saved.mode,
                darkStart: normalizeThemeTime(saved.darkStart, '18:00'),
                lightStart: normalizeThemeTime(saved.lightStart, '06:00'),
            };
        }
        const legacy = localStorage.getItem('idn-care-theme');
        if (legacy === 'dark' || legacy === 'light') {
            return { mode: legacy, darkStart: '18:00', lightStart: '06:00' };
        }
    } catch {}

    return { mode: 'device', darkStart: '18:00', lightStart: '06:00' };
};
let themePreference = readThemePreference();
const currentMinutes = () => {
    const now = new Date();
    return now.getHours() * 60 + now.getMinutes();
};
const timeToMinutes = (time) => Number(time.slice(0, 2)) * 60 + Number(time.slice(3, 5));
const resolveDarkTheme = (preference) => {
    if (preference.mode === 'dark') return true;
    if (preference.mode === 'light') return false;
    if (preference.mode === 'device') return window.matchMedia('(prefers-color-scheme: dark)').matches;

    const start = timeToMinutes(preference.darkStart);
    const end = timeToMinutes(preference.lightStart);
    const current = currentMinutes();
    return start === end || (start < end ? current >= start && current < end : current >= start || current < end);
};
const syncThemeControls = () => {
    const dark = isDarkTheme();
    document.querySelectorAll('.theme-switch').forEach((toggle) => toggle.setAttribute('data-theme', dark ? 'dark' : 'light'));
    document.querySelectorAll('[data-theme-choice]').forEach((choice) => {
        choice.setAttribute('aria-pressed', String(choice.dataset.themeChoice === (dark ? 'dark' : 'light')));
    });
    const schedulePanel = document.querySelector('[data-theme-schedule]');
    if (schedulePanel) schedulePanel.hidden = themePreference.mode !== 'scheduled';
    document.querySelectorAll('[data-theme-mode]').forEach((control) => {
        control.checked = control.value === themePreference.mode;
    });
    document.querySelectorAll('[data-theme-dark-start]').forEach((control) => {
        control.value = themePreference.darkStart;
        control.disabled = themePreference.mode !== 'scheduled';
    });
    document.querySelectorAll('[data-theme-light-start]').forEach((control) => {
        control.value = themePreference.lightStart;
        control.disabled = themePreference.mode !== 'scheduled';
    });
};
const applyThemePreference = ({ persist = false } = {}) => {
    const dark = resolveDarkTheme(themePreference);
    const previous = isDarkTheme();
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#101a2a' : '#f4f6f9');
    if (persist) {
        try {
            localStorage.setItem(themePreferenceKey, JSON.stringify(themePreference));
            localStorage.setItem('idn-care-theme', dark ? 'dark' : 'light');
        } catch {}
    }
    syncThemeControls();
    if (previous !== dark) {
        window.dispatchEvent(new CustomEvent('app-theme-changed', { detail: { theme: dark ? 'dark' : 'light' } }));
    }
};
applyThemePreference();

document.querySelectorAll('[data-theme-choice]').forEach((choice) => choice.addEventListener('click', () => {
    themePreference = { ...themePreference, mode: choice.dataset.themeChoice };
    applyThemePreference({ persist: true });
}));
document.querySelectorAll('[data-theme-mode]').forEach((control) => control.addEventListener('change', () => {
    if (!control.checked) return;
    themePreference = { ...themePreference, mode: control.value };
    applyThemePreference({ persist: true });
    const status = document.querySelector('[data-theme-save-status]');
    if (status) status.textContent = 'Preferensi tema tersimpan di perangkat ini.';
}));
const saveThemeSchedule = () => {
    const darkStart = document.querySelector('[data-theme-dark-start]');
    const lightStart = document.querySelector('[data-theme-light-start]');
    themePreference = {
        ...themePreference,
        mode: 'scheduled',
        darkStart: normalizeThemeTime(darkStart?.value, '18:00'),
        lightStart: normalizeThemeTime(lightStart?.value, '06:00'),
    };
    applyThemePreference({ persist: true });
    const status = document.querySelector('[data-theme-save-status]');
    if (status) status.textContent = `Tema gelap aktif pukul ${themePreference.darkStart}–${themePreference.lightStart} sesuai jam perangkat.`;
};
document.querySelectorAll('[data-theme-dark-start], [data-theme-light-start]').forEach((control) => control.addEventListener('change', saveThemeSchedule));

const systemThemePreference = window.matchMedia('(prefers-color-scheme: dark)');
systemThemePreference.addEventListener('change', () => {
    if (themePreference.mode === 'device') applyThemePreference();
});
window.setInterval(() => {
    if (themePreference.mode === 'scheduled') applyThemePreference();
}, 60_000);

document.querySelectorAll('.login-form').forEach((form) => form.addEventListener('submit', () => {
    const submitButton = form.querySelector('.login-submit');
    if (!(submitButton instanceof HTMLButtonElement)) return;
    submitButton.classList.add('is-authenticating');
    submitButton.setAttribute('aria-busy', 'true');
}));

const notificationToastContainer = document.getElementById('app-notification-toasts');
let notificationSequence = 0;
const showAppNotification = ({ message, type = 'success' } = {}) => {
    if (!notificationToastContainer || typeof message !== 'string' || message.trim() === '') return;

    const notificationType = type === 'error' ? 'error' : 'success';
    const toast = document.createElement('div');
    const content = document.createElement('p');
    const closeButton = document.createElement('button');
    const notificationId = `app-notification-${++notificationSequence}`;
    const returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    toast.className = `app-notification-toast app-notification-toast-${notificationType}`;
    toast.id = notificationId;
    toast.setAttribute('role', notificationType === 'error' ? 'alert' : 'status');
    content.className = 'app-notification-toast-message';
    content.textContent = message;
    closeButton.className = 'app-notification-toast-close';
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Tutup notifikasi');
    closeButton.textContent = '×';
    if (typeof toast.showPopover === 'function') {
        toast.setAttribute('popover', 'manual');
        closeButton.setAttribute('popovertarget', notificationId);
        closeButton.setAttribute('popovertargetaction', 'hide');
        toast.addEventListener('toggle', (event) => {
            if (event.newState === 'closed') {
                window.setTimeout(() => {
                    toast.remove();
                    restackNotifications();
                }, 220);
            } else {
                restackNotifications();
            }
        });
    } else {
        toast.dataset.toastOpen = 'true';
        closeButton.addEventListener('click', () => {
            toast.dataset.toastOpen = 'false';
            window.setTimeout(() => {
                toast.remove();
                restackNotifications();
            }, 220);
        });
    }
    toast.append(content, closeButton);
    notificationToastContainer.append(toast);
    if (typeof toast.showPopover === 'function') {
        toast.showPopover();
    }
    restackNotifications();
    closeButton.addEventListener('click', () => {
        window.setTimeout(() => {
            const nextToast = [...notificationToastContainer.querySelectorAll('.app-notification-toast')]
                .find((candidate) => candidate !== toast && (
                    typeof candidate.showPopover === 'function'
                        ? candidate.matches(':popover-open')
                        : candidate.dataset.toastOpen === 'true'
                ));

            if (nextToast instanceof HTMLElement) {
                nextToast.querySelector('button')?.focus();
            } else if (returnFocus?.isConnected) {
                returnFocus.focus();
            }
        }, 220);
    }, { once: true });
};

function restackNotifications() {
    if (!notificationToastContainer) return;

    let offset = 0;
    notificationToastContainer.querySelectorAll('.app-notification-toast').forEach((toast) => {
        toast.style.setProperty('--toast-offset', `${offset}px`);
        offset += toast.getBoundingClientRect().height + 12;
    });
}

window.addEventListener('app-notification', (event) => showAppNotification(event.detail));
window.addEventListener('notification-count-updated', (event) => {
    const unreadCount = Number(event.detail?.count ?? 0);
    document.querySelectorAll('.notification-count').forEach((badge) => {
        badge.textContent = String(unreadCount);
    });
    document.querySelectorAll('.topbar-notification').forEach((link) => {
        link.setAttribute('aria-label', `Notifikasi, ${unreadCount} belum dibaca`);
    });
});

document.querySelectorAll('[data-flash-notification]').forEach((flash) => {
    showAppNotification({ message: flash.textContent.trim(), type: flash.dataset.type });
});

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const animateIfAllowed = (element, keyframes, options) => {
    if (reduceMotion.matches || typeof element?.animate !== 'function') return;
    element.animate(keyframes, { ...options, easing: options.easing || 'cubic-bezier(.2,.75,.25,1)' });
};

const motionTargetSelector = [
    '.landing-hero',
    '.landing-section',
    '.landing-public-reports',
    '.landing-process',
    '.landing-faq',
    '.portal-content > .page-heading',
    '.portal-content > section',
    '.portal-content > form',
    '.portal-content > article',
    '.portal-content > .empty-state',
    '.portal-content > .table-scroll',
    '.dashboard-columns > *',
    '.dashboard-chart-grid > *',
    '.dashboard-stat',
    '.status-flow-card',
    '.report-type-card',
    '.report-card',
    '.notification-list article',
    '.table-scroll tbody > tr',
    '.panel',
    '[class*="-card"]',
    '[class*="-panel"]',
].join(',');

let motionObserver = null;
const prepareMotionTargets = (root = document) => {
    if (reduceMotion.matches || !('IntersectionObserver' in window)) return;

    if (!motionObserver) {
        motionObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.dataset.motionReveal = 'visible';
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    }

    const targets = [];
    if (root instanceof Element && root.matches(motionTargetSelector)) targets.push(root);
    targets.push(...root.querySelectorAll(motionTargetSelector));

    targets.forEach((element) => {
        if (element.dataset.motionReveal) return;
        const siblingIndex = [...(element.parentElement?.children ?? [])].indexOf(element);
        const staggerIndex = Math.max(0, siblingIndex % 6);
        element.style.setProperty('--motion-delay', `${staggerIndex * 45}ms`);
        element.dataset.motionReveal = 'pending';
        motionObserver.observe(element);
    });
};

prepareMotionTargets();
reduceMotion.addEventListener('change', (event) => {
    if (event.matches) {
        document.querySelectorAll('[data-motion-reveal="pending"]').forEach((element) => {
            element.dataset.motionReveal = 'visible';
            motionObserver?.unobserve(element);
        });
        return;
    }

    prepareMotionTargets();
});

const livewireMotionObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node instanceof Element) prepareMotionTargets(node);
    }));
});
livewireMotionObserver.observe(document.body, { childList: true, subtree: true });

// Small press feedback makes controls feel responsive without changing layout.
const pressedAnimations = new WeakMap();
const pressedControls = new Set();
const interactiveSelector = 'a[href], button:not(:disabled), [role="button"], [role="tab"], [role="menuitem"], summary, .dashboard-stat, .status-flow-card';
document.addEventListener('pointerdown', (event) => {
    if (event.button !== 0 || reduceMotion.matches) return;
    const control = event.target.closest(interactiveSelector);
    if (!control || control.hasAttribute('aria-disabled')) return;
    pressedAnimations.get(control)?.cancel();
    pressedAnimations.set(control, animateIfAllowed(control, [
        { transform: 'scale(1)' },
        { transform: 'scale(.94)' },
    ], { duration: 110, fill: 'forwards' }));
    pressedControls.add(control);
});
const releaseControls = () => {
    pressedControls.forEach((control) => {
        if (!pressedAnimations.has(control)) return;
        pressedAnimations.get(control)?.cancel();
        pressedAnimations.delete(control);
        animateIfAllowed(control, [
            { transform: 'scale(.94)' },
            { transform: 'scale(1)' },
        ], { duration: 180 });
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
            { transform: 'scale(.94)', offset: .45 },
            { transform: 'scale(1)' },
        ], { duration: 220 });
    }
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

const chartDataElement = document.getElementById('dashboard-chart-data');

if (chartDataElement) {
    const data = JSON.parse(chartDataElement.dataset.payload);
    const dashboardCharts = [];
    const chartTextColor = () => isDarkTheme() ? '#e5edf9' : '#52647e';
    const chartGridColor = () => isDarkTheme() ? '#33445f' : '#edf2fb';
    const palette = ['#0B5FFF', '#1AA7E8', '#8BB7FF', '#0A1F44', '#4389F4', '#78A9FF'];
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', align: 'start', labels: { usePointStyle: true, boxWidth: 8, padding: 10, font: { size: 11 }, generateLabels: (chart) => Chart.defaults.plugins.legend.labels.generateLabels(chart).map((item) => {
            const color = chart.config.type === 'line' ? chart.data.datasets[item.datasetIndex]?.borderColor : item.fillStyle;
            return { ...item, fillStyle: color, lineWidth: 0, strokeStyle: color };
        }) } } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: chartGridColor() } }, x: { grid: { color: chartGridColor() } } },
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
        const chart = new Chart(canvas, {
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
        dashboardCharts.push(chart);
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
        plugins: { legend: { display: showLegend, position: 'bottom', align: 'start', labels: { usePointStyle: true, boxWidth: 8, padding: 10, font: { size: 11 } } } },
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
            plugins: { legend: { display: true, position: 'bottom', align: 'start', labels: { boxWidth: 8, padding: 10, font: { size: 11 } } } },
            scales: { x: { grid: { color: '#edf2fb' }, ticks: { autoSkip: false, minRotation: 0, maxRotation: 0, padding: 6 } } },
        });
    }
    if (data.monthlyLogins) render('login-chart', 'bar', data.monthlyLogins.labels, [{ label: 'Login', data: data.monthlyLogins.values, backgroundColor: '#8BB7FF' }]);

    const updateChartTheme = () => {
        dashboardCharts.forEach((chart) => {
            chart.options.plugins.legend.labels.color = chartTextColor();
            Object.values(chart.options.scales ?? {}).forEach((scale) => {
                scale.ticks = { ...scale.ticks, color: chartTextColor() };
                scale.grid = { ...scale.grid, color: chartGridColor() };
                scale.title = { ...scale.title, color: chartTextColor() };
            });
            chart.update('none');
        });
    };
    updateChartTheme();
    window.addEventListener('app-theme-changed', updateChartTheme);
}
