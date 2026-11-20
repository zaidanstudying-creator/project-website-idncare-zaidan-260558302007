<script>
document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

try {
    const themePreference = JSON.parse(localStorage.getItem('idn-care-theme-settings') || 'null');
    const legacyTheme = localStorage.getItem('idn-care-theme');
    const savedMode = themePreference?.mode;
    const mode = ['device', 'light', 'dark', 'scheduled'].includes(savedMode)
        ? savedMode
        : (legacyTheme === 'dark' || legacyTheme === 'light' ? legacyTheme : 'device');
    const toMinutes = (time, fallback) => /^([01]\d|2[0-3]):[0-5]\d$/.test(time || '')
        ? Number(time.slice(0, 2)) * 60 + Number(time.slice(3, 5))
        : fallback;
    let dark = mode === 'dark';
    if (mode === 'device') {
        dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    } else if (mode === 'scheduled') {
        const start = toMinutes(themePreference?.darkStart, 18 * 60);
        const end = toMinutes(themePreference?.lightStart, 6 * 60);
        const now = new Date();
        const current = now.getHours() * 60 + now.getMinutes();
        dark = start === end || (start < end ? current >= start && current < end : current >= start || current < end);
    }
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#101a2a' : '#f4f6f9');
} catch {}
</script>
