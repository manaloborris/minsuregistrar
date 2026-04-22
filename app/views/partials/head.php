<?php
$title = $title ?? 'MinSU e-Registrar';
$minsuLogoPath = APP_ROOT . '/public/assets/minsulogo.png';
$minsuLogoVersion = file_exists($minsuLogoPath) ? filemtime($minsuLogoPath) : time();
$minsuLogoUrl = url('public/assets/minsulogo.png') . '?v=' . $minsuLogoVersion;
$skipInitialLoading = !empty($_SESSION['ui']['skip_initial_loading']);
unset($_SESSION['ui']['skip_initial_loading']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <script>
        (function () {
            var key = 'minsureg.appPreferences.v1';

            var defaults = {
                text_scale: 'normal',
                contrast: 'normal',
                motion: 'normal',
                font: 'default',
                focus: 'normal',
                theme: 'system'
            };

            function sanitize(input) {
                var source = (input && typeof input === 'object') ? input : {};
                return {
                    text_scale: ['normal', 'large', 'xlarge'].indexOf(source.text_scale) >= 0 ? source.text_scale : defaults.text_scale,
                    contrast: ['normal', 'high'].indexOf(source.contrast) >= 0 ? source.contrast : defaults.contrast,
                    motion: ['normal', 'reduced'].indexOf(source.motion) >= 0 ? source.motion : defaults.motion,
                    font: ['default', 'readable'].indexOf(source.font) >= 0 ? source.font : defaults.font,
                    focus: ['normal', 'strong'].indexOf(source.focus) >= 0 ? source.focus : defaults.focus,
                    theme: ['light', 'dark', 'system'].indexOf(source.theme) >= 0 ? source.theme : defaults.theme
                };
            }

            function read() {
                try {
                    var raw = window.localStorage.getItem(key);
                    if (!raw) return sanitize(defaults);
                    return sanitize(JSON.parse(raw));
                } catch (e) {
                    return sanitize(defaults);
                }
            }

            function apply(prefs) {
                var normalized = sanitize(prefs);
                var root = document.documentElement;
                root.classList.remove('ui-text-large', 'ui-text-xlarge', 'ui-contrast-high', 'ui-motion-reduced', 'ui-font-readable', 'ui-focus-strong', 'ui-theme-dark');

                if (normalized.text_scale === 'large') root.classList.add('ui-text-large');
                if (normalized.text_scale === 'xlarge') root.classList.add('ui-text-xlarge');
                if (normalized.contrast === 'high') root.classList.add('ui-contrast-high');
                if (normalized.motion === 'reduced') root.classList.add('ui-motion-reduced');
                if (normalized.font === 'readable') root.classList.add('ui-font-readable');
                if (normalized.focus === 'strong') root.classList.add('ui-focus-strong');

                // Dark theme logic
                var theme = normalized.theme;
                if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    root.classList.add('ui-theme-dark');
                }
            }
    <style>
        html.ui-theme-dark body {
            background: #18181b;
            color: #f1f5f9;
        }
        html.ui-theme-dark .bubble-card,
        html.ui-theme-dark .soft-panel {
            background: rgba(30, 41, 59, 0.92) !important;
            color: #f1f5f9 !important;
            border-color: #334155 !important;
        }
        html.ui-theme-dark .text-slate-800,
        html.ui-theme-dark .text-slate-700,
        html.ui-theme-dark .text-slate-600,
        html.ui-theme-dark .text-slate-500 {
            color: #f1f5f9 !important;
        }
        html.ui-theme-dark .bg-white {
            background: #27272a !important;
        }
        html.ui-theme-dark .bg-emerald-100 {
            background: #134e4a !important;
        }
        html.ui-theme-dark .ring-slate-200 {
            border-color: #334155 !important;
        }
        html.ui-theme-dark select,
        html.ui-theme-dark input,
        html.ui-theme-dark textarea {
            background: #27272a !important;
            color: #f1f5f9 !important;
            border-color: #334155 !important;
        }
        html.ui-theme-dark .bg-emerald-500 {
            background: #047857 !important;
        }
        html.ui-theme-dark .hover\:bg-emerald-600:hover {
            background: #065f46 !important;
        }
        html.ui-theme-dark .bg-slate-100 {
            background: #334155 !important;
        }
        html.ui-theme-dark .bg-slate-50 {
            background: #1e293b !important;
        }
        html.ui-theme-dark .text-emerald-700 {
            color: #6ee7b7 !important;
        }
        html.ui-theme-dark .text-amber-500 {
            color: #fde68a !important;
        }
        html.ui-theme-dark .text-blue-500 {
            color: #93c5fd !important;
        }
        html.ui-theme-dark .text-emerald-600 {
            color: #6ee7b7 !important;
        }
    </style>

            var initial = read();
            apply(initial);

            window.MinSUAppPrefs = {
                storageKey: key,
                defaults: defaults,
                read: read,
                apply: apply,
                save: function (prefs) {
                    var normalized = sanitize(prefs);
                    window.localStorage.setItem(key, JSON.stringify(normalized));
                    apply(normalized);
                    return normalized;
                },
                reset: function () {
                    window.localStorage.setItem(key, JSON.stringify(defaults));
                    apply(defaults);
                    return defaults;
                }
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --mint: #d1fae5;
            --leaf: #16a34a;
            --forest: #14532d;
            --cream: #f8fafc;
            --bubble: rgba(255, 255, 255, 0.72);
            --bubble-strong: rgba(255, 255, 255, 0.88);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: radial-gradient(circle at top right, #dcfce7 0%, #f0fdf4 30%, #eef2ff 70%, #f8fafc 100%);
            min-height: 100vh;
        }

        h1, h2, h3, h4 {
            font-family: 'Poppins', sans-serif;
        }

        html.ui-text-large {
            font-size: 17px;
        }

        html.ui-text-xlarge {
            font-size: 18px;
        }

        html.ui-contrast-high body {
            background: linear-gradient(135deg, #f8fafc 0%, #ecfeff 100%);
            color: #0f172a;
        }

        html.ui-contrast-high .bubble-card {
            background: rgba(255, 255, 255, 0.97);
            border-color: rgba(15, 23, 42, 0.25);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16);
        }

        html.ui-contrast-high .soft-panel {
            background: #ffffff;
            border-color: rgba(15, 23, 42, 0.24);
            box-shadow: none;
        }

        html.ui-motion-reduced *,
        html.ui-motion-reduced *::before,
        html.ui-motion-reduced *::after {
            animation: none !important;
            transition: none !important;
            scroll-behavior: auto !important;
        }

        html.ui-font-readable body,
        html.ui-font-readable h1,
        html.ui-font-readable h2,
        html.ui-font-readable h3,
        html.ui-font-readable h4,
        html.ui-font-readable p,
        html.ui-font-readable button,
        html.ui-font-readable a,
        html.ui-font-readable input,
        html.ui-font-readable select,
        html.ui-font-readable textarea {
            font-family: Verdana, Tahoma, Geneva, sans-serif !important;
        }

        html.ui-focus-strong :focus-visible {
            outline: 3px solid #0ea5e9 !important;
            outline-offset: 2px;
        }

        .bubble-card {
            background: var(--bubble);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 10px 35px rgba(20, 83, 45, 0.09);
            border-radius: 1.25rem;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .bubble-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 45px rgba(20, 83, 45, 0.14);
        }

        .soft-panel {
            background: var(--bubble-strong);
            border-radius: 1rem;
            border: 1px solid rgba(34, 197, 94, 0.15);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .status-pill {
            border-radius: 9999px;
            padding: 0.28rem 0.8rem;
            font-weight: 700;
            font-size: 0.75rem;
            display: inline-block;
        }

        .page-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.75rem;
            background: rgb(241 245 249);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: rgb(51 65 85);
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .page-back-link:hover {
            background: rgb(226 232 240);
            transform: translateY(-1px);
        }

        .page-back-link svg {
            width: 1rem;
            height: 1rem;
            flex: none;
        }

        .fade-in {
            animation: fadeIn 0.45s ease both;
        }

        #studentSidebar,
        #adminSidebar {
            transition: transform 0.2s ease, width 0.2s ease;
        }

        #studentSidebar nav a,
        #adminSidebar nav a {
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        #studentSidebar nav a:hover,
        #adminSidebar nav a:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 20px rgba(20, 83, 45, 0.08);
        }

        #studentSidebar nav a:focus-visible,
        #adminSidebar nav a:focus-visible {
            outline: 2px solid rgba(16, 185, 129, 0.55);
            outline-offset: 2px;
        }

        .dashboard-stat {
            position: relative;
            overflow: hidden;
        }

        .dashboard-stat::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0) 20%, rgba(255, 255, 255, 0.35) 45%, rgba(255, 255, 255, 0) 70%);
            transform: translateX(-130%);
            transition: transform 0.45s ease;
            pointer-events: none;
        }

        .dashboard-stat:hover::after {
            transform: translateX(130%);
        }

        @media (min-width: 1280px) {
            #studentSidebar.is-collapsed,
            #adminSidebar.is-collapsed {
                width: 5.2rem;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            #studentSidebar.is-collapsed .sidebar-meta,
            #adminSidebar.is-collapsed .sidebar-meta,
            #studentSidebar.is-collapsed .menu-label,
            #adminSidebar.is-collapsed .menu-label,
            #studentSidebar.is-collapsed .menu-badge,
            #adminSidebar.is-collapsed .menu-badge {
                display: none;
            }

            #studentSidebar.is-collapsed nav a,
            #adminSidebar.is-collapsed nav a {
                justify-content: center;
                transform: none;
                box-shadow: none;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Loading Screen Styles */
        #loadingScreen {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(1100px 580px at 12% 16%, rgba(34, 197, 94, 0.24), transparent 56%),
                radial-gradient(900px 500px at 86% 82%, rgba(16, 185, 129, 0.18), transparent 52%),
                linear-gradient(145deg, #f8fffb 0%, #ecfdf5 46%, #f0fdf4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.45s ease;
        }

        #loadingScreen.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loading-container {
            width: min(92vw, 360px);
            padding: 1.5rem 1.4rem;
            border-radius: 24px;
            border: 1px solid rgba(16, 185, 129, 0.22);
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(8px);
            box-shadow: 0 20px 44px rgba(6, 95, 70, 0.16);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.95rem;
            animation: loaderCardIn 0.5s ease;
        }

        .loading-logo-mask {
            width: 94px;
            height: 94px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 24%, #ffffff 0%, #ecfdf5 100%);
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 2px solid rgba(16, 185, 129, 0.26);
            box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.65), 0 12px 26px rgba(5, 150, 105, 0.18);
            animation: logoFloat 2.2s ease-in-out infinite;
        }

        .loading-logo {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .loading-text {
            margin: 0.25rem 0 0;
            color: #047857;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .loading-subtext {
            margin: 0;
            color: #065f46;
            font-size: 0.84rem;
            opacity: 0.86;
        }

        .loading-progress {
            position: relative;
            width: 100%;
            height: 8px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(16, 185, 129, 0.18);
        }

        .loading-progress::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, #10b981 0%, #34d399 45%, #059669 100%);
            transform-origin: left center;
            animation: loadingBar 1.2s linear forwards;
        }

        @keyframes loadingBar {
            from { transform: scaleX(0.08); }
            to { transform: scaleX(1); }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes loaderCardIn {
            from { opacity: 0; transform: translateY(10px) scale(0.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Login Portal Access Section Animations */
        .portal-feature-item {
            animation: slideInLeft 0.5s ease-out forwards;
            opacity: 0;
        }

        .portal-feature-item:nth-child(1) { animation-delay: 0.1s; }
        .portal-feature-item:nth-child(2) { animation-delay: 0.2s; }
        .portal-feature-item:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .portal-feature-item {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .portal-feature-item::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .portal-feature-item:hover::before {
            transform: translateX(100%);
        }

        .portal-feature-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.15);
        }

        .portal-feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(52, 211, 153, 0.1));
            color: #10b981;
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }

        @media (max-width: 575.98px) {
            .loading-container {
                padding: 1.25rem 1.1rem;
                border-radius: 20px;
            }

            .loading-logo-mask {
                width: 82px;
                height: 82px;
            }

            .loading-logo {
                width: 68px;
                height: 68px;
            }
        }
    </style>
</head>
<body>
<?php if ($skipInitialLoading): ?>
<script>window.__skipInitialLoading = true;</script>
<?php endif; ?>
<!-- Loading Screen -->
<div id="loadingScreen" class="loading-screen-wrap">
    <div class="loading-container">
        <div class="loading-logo-mask">
            <img src="<?= esc($minsuLogoUrl) ?>" alt="MinSU Logo" class="loading-logo">
        </div>
        <p class="loading-text">Loading Portal</p>
        <p class="loading-subtext">Preparing your dashboard...</p>
        <div class="loading-progress" aria-hidden="true"></div>
    </div>
</div>

<!-- Aggressive loading screen failsafe -->
<script>
(function() {
	window.setTimeout(function() {
		const loadingScreen = document.getElementById('loadingScreen');
		if (loadingScreen) {
			loadingScreen.style.display = 'none !important';
			loadingScreen.style.visibility = 'hidden !important';
			loadingScreen.style.pointerEvents = 'none !important';
			loadingScreen.classList.add('hidden');
		}
	}, 2000);
})();
</script>
