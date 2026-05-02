<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- PWA Meta Tags -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#096CC8">
        <link rel="apple-touch-icon" href="/logo.png">
        <link rel="manifest" href="/manifest.json">

        <link rel="manifest" href="/manifest.webmanifest">

        <meta name="theme-color" content="#0f172a">

        <meta name="mobile-web-app-capable" content="yes">

        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="Portal RH">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <link rel="apple-touch-icon" href="/assets/media/logos/logo.png">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

        {{-- Aquí se incluyen los estilos de Metronic --}}
        <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
        {{-- <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet" type="text/css" /> --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Libertinus+Serif:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    </head>
    <body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center">
        <script>
            var defaultThemeMode = "light";
            var themeMode;
            if (document.documentElement) {
                if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                    themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
                } else {
                    if (localStorage.getItem("data-bs-theme") !== null) {
                        themeMode = localStorage.getItem("data-bs-theme");
                    } else {
                        themeMode = defaultThemeMode;
                    }
                }
                if (themeMode === "system") {
                    themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
                }
                document.documentElement.setAttribute("data-bs-theme", themeMode);
            }

            if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
        </script>
        @inertia


        <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>

        <script>
            // 1. Configuración de Tema Metronic
            var defaultThemeMode = "light";
            var themeMode;
            if (document.documentElement) {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
                document.documentElement.setAttribute("data-bs-theme", themeMode);
            }

            // 2. Control de iFrames
            if (window.top != window.self) {
                window.top.location.replace(window.self.location.href);
            }

            // 3. Registro de Service Worker (PWA)
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(reg => console.log('SW registrado correctamente', reg))
                        .catch(err => console.log('Error al registrar SW', err));
                });
            }

            // 4. Tema oscuro extra (opcional si usas PrimeVue o custom)
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "dark") {
                document.documentElement.classList.add("app-dark", "mode-dark");
            }
        </script>
    </body>
</html>
{{-- <script>
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") {
        document.documentElement.classList.add("app-dark", "mode-dark");
    } else {
        document.documentElement.classList.remove("app-dark", "mode-dark");
    }
</script> --}}
