<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Error interno del servidor</title>
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
                        'spark': 'spark 2s ease-in-out infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-15px)' },
                        },
                        spark: {
                            '0%, 100%': { opacity: 0.6, transform: 'scale(1)' },
                            '50%': { opacity: 1, transform: 'scale(1.4)' }
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
        
        <!-- Ilustración SVG corregida: Engranaje centrado y rotación nativa -->
        <div class="animate-float mb-8">
            <svg viewBox="0 0 600 400" class="w-full max-w-lg mx-auto" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3b82f6"/>
                        <stop offset="100%" style="stop-color:#60a5fa"/>
                    </linearGradient>
                    <linearGradient id="metalGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#64748b"/>
                        <stop offset="50%" style="stop-color:#475569"/>
                        <stop offset="100%" style="stop-color:#1e293b"/>
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <pattern id="grid" width="30" height="30" patternUnits="userSpaceOnUse">
                        <path d="M 30 0 L 0 0 0 30" fill="none" stroke="#bfdbfe" stroke-width="0.5" opacity="0.4"/>
                    </pattern>
                </defs>

                <!-- Fondo técnico -->
                <rect width="600" height="400" fill="url(#grid)" opacity="0.6"/>
                
                <!-- Líneas de circuito decorativas -->
                <g stroke="#93c5fd" stroke-width="2" fill="none" opacity="0.5">
                    <path d="M40 320 L90 320 L110 300 L160 300"/>
                    <path d="M560 80 L510 80 L490 100 L440 100"/>
                    <circle cx="160" cy="300" r="4" fill="#3b82f6"/>
                    <circle cx="440" cy="100" r="4" fill="#3b82f6"/>
                </g>

                <!-- Triángulo de advertencia superpuesto (fijo, no gira) -->
                <g transform="translate(300, 210)">
                    <path d="M0,-48 L44,32 L-44,32 Z" fill="#ef4444" stroke="#ffffff" stroke-width="4" stroke-linejoin="round" class="animate-pulse-slow" filter="url(#glow)"/>
                    <text x="0" y="20" font-size="38" font-weight="bold" fill="#ffffff" text-anchor="middle" font-family="Inter, sans-serif">!</text>
                </g>

                <!-- Chispas técnicas -->
                <g class="animate-spark">
                    <circle cx="170" cy="130" r="3" fill="#3b82f6"/>
                    <circle cx="430" cy="160" r="4" fill="#60a5fa"/>
                    <circle cx="240" cy="330" r="2.5" fill="#2563eb"/>
                    <circle cx="360" cy="80" r="3" fill="#93c5fd"/>
                </g>

                <!-- Símbolos de código -->
                <g transform="translate(120, 160)" opacity="0.8">
                    <text x="0" y="0" font-size="32" font-weight="600" fill="#1d4ed8" font-family="monospace">&lt;/&gt;</text>
                </g>
                <g transform="translate(480, 200)" opacity="0.8">
                    <text x="0" y="0" font-size="32" font-weight="600" fill="#1d4ed8" font-family="monospace">{ }</text>
                </g>

                <!-- Texto 500 flotante -->
                <g transform="translate(300, 90)">
                    <text x="0" y="0" font-size="76" font-weight="800" fill="url(#blueGrad)" text-anchor="middle" font-family="Inter, sans-serif">
                        500
                    </text>
                    <text x="0" y="0" font-size="76" font-weight="800" fill="none" stroke="#eff6ff" stroke-width="2" text-anchor="middle" font-family="Inter, sans-serif" opacity="0.4">
                        500
                    </text>
                </g>
            </svg>
        </div>

        <!-- Contenido de texto -->
        <div class="space-y-6">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
                ⚙️ Algo salió mal en nuestro servidor
            </h1>
            
            <p class="text-lg text-gray-600 max-w-md mx-auto">
                Se produjo un error interno inesperado. Nuestro equipo técnico ya está trabajando en la solución. 
                Por favor, intenta nuevamente en unos minutos.
            </p>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <a href="{{ url('/') }}" 
                   class="group relative px-8 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-blue-500/30 transition-all duration-300 overflow-hidden">
                    <span class="relative z-10 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        Volver al inicio
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>
                
                <button onclick="window.location.reload()" 
                        class="px-8 py-3 border-2 border-blue-300 text-blue-700 hover:bg-blue-50 font-semibold rounded-xl transition-all duration-300 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reintentar
                </button>
            </div>

            <!-- Enlaces útiles -->
            {{-- <div class="pt-6 border-t border-blue-100">
                <p class="text-sm text-gray-500 mb-3">¿El problema persiste? Contacta con nosotros:</p>
                <div class="flex flex-wrap justify-center gap-3 text-sm">
                    <a href="{{ url('/soporte') }}" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        Soporte técnico
                    </a>
                    <span class="text-gray-300">•</span>
                    <a href="{{ url('/estado-sistema') }}" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        Estado del servicio
                    </a>
                    <span class="text-gray-300">•</span>
                    <a href="mailto:soporte@tudominio.com" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        Email de soporte
                    </a>
                </div>
            </div> --}}
        </div>

        <!-- Footer decorativo -->
        <div class="mt-12 text-xs text-gray-400">
            Error {{ $code ?? '500' }} • {{ $message ?? 'Error interno del servidor' }}
        </div>
    </div>
</body>
</html>