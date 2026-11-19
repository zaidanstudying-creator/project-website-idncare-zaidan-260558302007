<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f4f6f9">
    @include('layouts.components.theme-bootstrap')
    <title>{{ $code }} — IDN CARE</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Plus Jakarta Sans", "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0a1f44;
            background: #fff;
        }

        * { box-sizing: border-box; }
        body { margin: 0; min-width: 320px; }
        a { color: inherit; }
        .topbar { height: 80px; padding: 0 clamp(20px, 6vw, 80px); display: flex; align-items: center; justify-content: space-between; background: #fff; }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: #0a1f44; font-size: 20px; font-weight: 800; text-decoration: none; }
        .error-brand-logo { width: 38px; height: 38px; object-fit: contain; }
        .top-actions { display: flex; align-items: center; gap: 12px; }
        .error-theme-switch { display: inline-flex; gap: 3px; padding: 4px; border: 1px solid #dce4f0; border-radius: 999px; background: #f1f4f8; }
        .error-theme-switch button { display: grid; width: 36px; height: 36px; place-items: center; border: 0; border-radius: 50%; color: #64748b; background: transparent; cursor: pointer; }
        .error-theme-switch button[aria-pressed="true"] { color: #0a1f44; background: #dce3ee; }
        .error-theme-switch svg { width: 19px; height: 19px; }
        .top-link { padding: 12px 28px; border-radius: 999px; color: #fff; background: linear-gradient(135deg, #0b5fff, #1aa7e8); font-size: 15px; font-weight: 700; text-decoration: none; }
        .error-page { position: relative; display: grid; min-height: calc(100svh - 80px); overflow: hidden; place-items: center; padding: 64px 20px 84px; isolation: isolate; background: linear-gradient(135deg, #e8f0ff 0%, #f7faff 64%, #fff 100%); }
        .error-content { z-index: 1; width: min(100%, 680px); text-align: center; }
        .error-code { margin: 0; color: #0b5fff; font-size: clamp(96px, 18vw, 152px); font-weight: 800; letter-spacing: -0.075em; line-height: .95; }
        h1 { margin: 28px 0 20px; color: #0a1f44; font-size: clamp(26px, 4vw, 36px); line-height: 1.25; }
        .message { margin: 0 auto; max-width: 620px; color: #31517e; font-size: 18px; line-height: 1.7; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; margin-top: 28px; }
        .button { display: inline-flex; min-height: 56px; align-items: center; justify-content: center; padding: 14px 30px; border: 2px solid #0b5fff; border-radius: 999px; color: #0b4fd6; background: transparent; font-size: 15px; font-weight: 800; text-decoration: none; }
        .button-primary { border-color: transparent; color: #fff; background: linear-gradient(135deg, #0b5fff, #1aa7e8); }
        .button:focus-visible, .top-link:focus-visible, .brand:focus-visible { outline: 3px solid #0a1f44; outline-offset: 4px; }
        .note { margin: 18px 0 0; color: #31517e; font-size: 15px; line-height: 1.6; }
        .shape { position: absolute; z-index: -1; border-radius: 50%; pointer-events: none; }
        .shape-soft { top: -115px; left: -110px; width: 350px; height: 350px; background: #dce8ff; }
        .shape-navy { right: -65px; bottom: -105px; width: 260px; height: 260px; background: #0a1f44; }
        .shape-cyan { right: 145px; bottom: 90px; width: 90px; height: 90px; background: #22d3ee; }

        html[data-theme="dark"] { color-scheme: dark; color: #edf3ff; background: #101a2a; }
        html[data-theme="dark"] .topbar { background: #141f31; }
        html[data-theme="dark"] .brand { color: #edf3ff; }
        html[data-theme="dark"] .error-theme-switch { border-color: #344965; background: #0b1322; }
        html[data-theme="dark"] .error-theme-switch button { color: #a9b8cc; }
        html[data-theme="dark"] .error-theme-switch button[aria-pressed="true"] { color: #f4f7ff; background: #2b3e59; }
        html[data-theme="dark"] .error-page { background: linear-gradient(135deg, #14233a 0%, #101a2a 64%, #111c2d 100%); }
        html[data-theme="dark"] h1 { color: #f4f7ff; }
        html[data-theme="dark"] .message,
        html[data-theme="dark"] .note { color: #c3d0e2; }
        html[data-theme="dark"] .shape-soft { background: #1d3556; }
        html[data-theme="dark"] .shape-navy { background: #080f1c; }
        html[data-theme="dark"] .button { color: #a9c8ff; border-color: #6b9bff; }
        html[data-theme="dark"] .button-primary { color: #fff; border-color: transparent; }
        html[data-theme="dark"] .button:focus-visible,
        html[data-theme="dark"] .top-link:focus-visible,
        html[data-theme="dark"] .brand:focus-visible { outline-color: #91baff; }

        @media (max-width: 560px) {
            .topbar { height: 68px; padding-inline: 18px; }
            .brand { font-size: 18px; }
            .top-actions { gap: 8px; }
            .top-link { padding: 10px 20px; }
            .error-page { min-height: calc(100svh - 68px); padding: 48px 20px 70px; }
            .message { font-size: 16px; }
            .actions { flex-direction: column; align-items: stretch; }
            .button { width: 100%; }
            .shape-soft { top: -170px; left: -170px; }
            .shape-navy { right: -145px; bottom: -155px; }
            .shape-cyan { right: 100px; bottom: 62px; width: 64px; height: 64px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ url('/') }}" aria-label="IDN CARE, beranda">
            <img class="error-brand-logo" src="{{ asset('report-mark.svg') }}" alt="">
            <span>IDN CARE</span>
        </a>
        <div class="top-actions">
            <div class="error-theme-switch" role="group" aria-label="Pilih tema">
                <button type="button" data-theme-choice="light" aria-label="Mode terang" aria-pressed="false"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42 1.42m11.3-11.3 1.42-1.42"/></svg></button>
                <button type="button" data-theme-choice="dark" aria-label="Mode gelap" aria-pressed="false"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.4 15.4A8.5 8.5 0 0 1 8.6 3.6 8.5 8.5 0 1 0 20.4 15.4Z"/></svg></button>
            </div>
            <a class="top-link" href="{{ url('/login') }}">Masuk</a>
        </div>
    </header>

    <main class="error-page">
        <span class="shape shape-soft" aria-hidden="true"></span>
        <span class="shape shape-navy" aria-hidden="true"></span>
        <span class="shape shape-cyan" aria-hidden="true"></span>
        <section class="error-content" aria-labelledby="error-title">
            <p class="error-code" aria-hidden="true">{{ $code }}</p>
            <h1 id="error-title">{{ $title }}</h1>
            <p class="message">{{ $message }}</p>
            <nav class="actions" aria-label="Navigasi halaman error">
                <a class="button button-primary" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
                <a class="button" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>
            </nav>
            <p class="note">{{ $note }}</p>
        </section>
    </main>
    <script>
        const errorThemeChoices = [...document.querySelectorAll('[data-theme-choice]')];
        const syncErrorThemeChoices = () => {
            const activeTheme = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
            errorThemeChoices.forEach((choice) => {
                choice.setAttribute('aria-pressed', String(choice.dataset.themeChoice === activeTheme));
            });
        };
        syncErrorThemeChoices();
        errorThemeChoices.forEach((choice) => choice.addEventListener('click', () => {
            const mode = choice.dataset.themeChoice === 'dark' ? 'dark' : 'light';
            document.documentElement.dataset.theme = mode;
            document.documentElement.style.colorScheme = mode;
            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', mode === 'dark' ? '#101a2a' : '#f4f6f9');
            try {
                const current = JSON.parse(localStorage.getItem('idn-care-theme-settings') || 'null');
                localStorage.setItem('idn-care-theme-settings', JSON.stringify({
                    mode,
                    darkStart: current?.darkStart || '18:00',
                    lightStart: current?.lightStart || '06:00',
                }));
                localStorage.setItem('idn-care-theme', mode);
            } catch {}
            syncErrorThemeChoices();
        }));
    </script>
</body>
</html>
