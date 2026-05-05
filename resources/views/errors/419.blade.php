<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Página expirada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe',
                            300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6',
                            600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'fade': 'fade 3s ease-in-out infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-15px)' },
                        },
                        fade: {
                            '0%, 100%': { opacity: 0.4 },
                            '50%': { opacity: 1 }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: radial-gradient(ellipse at bottom, #eff6ff 0%, #ffffff 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-4xl w-full text-center">
        
        <!-- Ilustración SVG: Reloj de arena + Token/CSRF expirado -->
        <div class="animate-float mb-8">
            <svg viewBox="0 0 600 400" class="w-full max-w-lg mx-auto" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3b82f6"/>
                        <stop offset="100%" style="stop-color:#60a5fa"/>
                    </linearGradient>
                    <linearGradient id="waterGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#bfdbfe; stop-opacity:0.8"/>
                        <stop offset="100%" style="stop-color:#93c5fd; stop-opacity:0.4"/>
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <clipPath id="glassClip">
                        <path d="M-20,-50 L20,-50 L8,-10 L20,50 L-20,50 L-8,-10 Z"/>
                    </clipPath>
                    <pattern id="grid" width="30" height="30" patternUnits="userSpaceOnUse">
                        <path d="M 30 0 L 0 0 0 30" fill="none" stroke="#bfdbfe" stroke-width="0.5" opacity="0.4"/>
                    </pattern>
                </defs>

                <!-- Fondo técnico -->
                <rect width="600" height="400" fill="url(#grid)" opacity="0.6"/>
                
                <!-- Líneas de conexión decorativas -->
                <g stroke="#93c5fd" stroke-width="2" fill="none" opacity="0.5">
                    <path d="M60 280 L120 280 L140 260 L200 260"/>
                    <path d="M540 120 L480 120 L460 140 L400 140"/>
                    <circle cx="200" cy="260" r="4" fill="#3b82f6"/>
                    <circle cx="400" cy="140" r="4" fill="#3b82f6"/>
                </g>

                <!-- ✅ ILUSTRACIÓN PRINCIPAL: Escudo + Reloj de Arena + Token -->
                <g transform="translate(300, 210)">
                    <!-- Escudo de fondo -->
                    <path d="M0,-80 L65,-50 L65,30 Q65,80 0,100 Q-65,80 -65,30 L-65,-50 Z" fill="url(#blueGrad)" opacity="0.15"/>
                    
                    <!-- Marco del reloj de arena -->
                    <path d="M-20,-50 L20,-50 L8,-10 L20,50 L-20,50 L-8,-10 Z" fill="none" stroke="#1d4ed8" stroke-width="4" stroke-linejoin="round"/>
                    <line x1="-20" y1="-50" x2="20" y2="-50" stroke="#1d4ed8" stroke-width="4" stroke-linecap="round"/>
                    <line x1="-20" y1="50" x2="20" y2="50" stroke="#1d4ed8" stroke-width="4" stroke-linecap="round"/>
                    
                    <!-- Arena superior (se vacía) -->
                    <rect x="-14" y="-46" width="28" height="26" fill="#3b82f6" rx="3" clip-path="url(#glassClip)">
                        <animate attributeName="height" values="26;8" dur="4s" repeatCount="indefinite" />
                        <animate attributeName="y" values="-46;-28" dur="4s" repeatCount="indefinite" />
                    </rect>
                    
                    <!-- Arena inferior (se llena) -->
                    <rect x="-18" y="18" width="36" height="12" fill="#3b82f6" rx="4" clip-path="url(#glassClip)">
                        <animate attributeName="height" values="12;32" dur="4s" repeatCount="indefinite" />
                        <animate attributeName="y" values="18;-2" dur="4s" repeatCount="indefinite" />
                    </rect>
                    
                    <!-- Flujo de arena -->
                    <line x1="0" y1="-10" x2="0" y2="15" stroke="#60a5fa" stroke-width="2.5">
                        <animate attributeName="opacity" values="1;0.4;1" dur="1.2s" repeatCount="indefinite" />
                    </line>
                    
                    <!-- Token/Documento expirado flotando -->
                    <g transform="translate(55, -35)" class="animate-pulse-slow">
                        <rect x="-18" y="-22" width="36" height="44" rx="4" fill="#ffffff" stroke="#1d4ed8" stroke-width="2" filter="url(#glow)"/>
                        <line x1="-10" y1="-10" x2="10" y2="-10" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        <line x1="-10" y1="0" x2="10" y2="0" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        <line x1="-10" y1="10" x2="5" y2="10" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        <!-- X de expirado -->
                        <path d="M-12,-24 L12,0 M12,-24 L-12,0" stroke="#ef4444" stroke-width="3" stroke-linecap="round"/>
                    </g>
                </g>

                <!-- Texto 419 flotante -->
                <g transform="translate(300, 90)">
                    <text x="0" y="0" font-size="76" font-weight="800" fill="url(#blueGrad)" text-anchor="middle" font-family="Inter, sans-serif">
                        419
                    </text>
                    <text x="0" y="0" font-size="76" font-weight="800" fill="none" stroke="#eff6ff" stroke-width="2" text-anchor="middle" font-family="Inter, sans-serif" opacity="0.4">
                        419
                    </text>
                </g>

                <!-- Chispas decorativas -->
                <g class="animate-fade">
                    <circle cx="160" cy="140" r="3" fill="#3b82f6"/>
                    <circle cx="440" cy="180" r="4" fill="#60a5fa"/>
                    <circle cx="230" cy="310" r="2.5" fill="#2563eb"/>
                    <circle cx="370" cy="110" r="3" fill="#93c5fd"/>
                </g>
            </svg>
        </div>

        <!-- Contenido de texto -->
        <div class="space-y-6">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
                ⏳ Tu sesión ha expirado
            </h1>
            
            <p class="text-lg text-gray-600 max-w-md mx-auto">
                Por seguridad, el token de verificación ha caducado. 
                Refresca la página o vuelve a enviar el formulario para continuar.
            </p>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <button onclick="window.location.reload()" 
                        class="group relative px-8 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-blue-500/30 transition-all duration-300 overflow-hidden">
                    <span class="relative z-10 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Recargar página
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </button>
                
                <a href="{{ url('/') }}" 
                   class="px-8 py-3 border-2 border-blue-300 text-blue-700 hover:bg-blue-50 font-semibold rounded-xl transition-all duration-300 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Volver al inicio
                </a>
            </div>

            
        </div>

        <!-- Footer decorativo -->
        <div class="mt-12 text-xs text-gray-400">
            Error {{ $code ?? '419' }} • {{ $message ?? 'Página expirada' }}
        </div>
    </div>
</body>
</html>