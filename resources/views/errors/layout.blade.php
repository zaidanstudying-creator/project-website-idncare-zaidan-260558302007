<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
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

        @media (max-width: 560px) {
            .topbar { height: 68px; padding-inline: 18px; }
            .brand { font-size: 18px; }
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
        <a class="top-link" href="{{ url('/login') }}">Masuk</a>
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
</body>
</html>
