<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
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
        
        <!-- Ilustración SVG animada -->
        <div class="animate-float mb-8">
            <svg viewBox="0 0 600 400" class="w-full max-w-lg mx-auto" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3b82f6"/>
                        <stop offset="100%" style="stop-color:#60a5fa"/>
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                
                <!-- Nubes decorativas -->
                <ellipse cx="100" cy="80" rx="40" ry="25" fill="#bfdbfe" opacity="0.6">
                    <animate attributeName="opacity" values="0.6;0.3;0.6" dur="4s" repeatCount="indefinite"/>
                </ellipse>
                <ellipse cx="500" cy="100" rx="35" ry="20" fill="#bfdbfe" opacity="0.4">
                    <animate attributeName="opacity" values="0.4;0.2;0.4" dur="5s" repeatCount="indefinite"/>
                </ellipse>
                
                <!-- Personaje perdido (astronauta simplificado) -->
                <g transform="translate(300, 220)">
                    <!-- Cuerpo -->
                    <circle cx="0" cy="0" r="45" fill="url(#blueGrad)" filter="url(#glow)"/>
                    <!-- Visor -->
                    <circle cx="0" cy="-5" r="28" fill="#1e293b"/>
                    <circle cx="0" cy="-5" r="24" fill="#0f172a"/>
                    <!-- Reflejo en visor -->
                    <ellipse cx="-8" cy="-12" rx="6" ry="8" fill="rgba(255,255,255,0.3)"/>
                    <!-- Antena -->
                    <line x1="0" y1="-45" x2="0" y2="-65" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="0" cy="-70" r="5" fill="#60a5fa"/>
                    <!-- Mochila -->
                    <rect x="-35" y="-15" width="12" height="30" rx="4" fill="#1d4ed8"/>
                    <rect x="23" y="-15" width="12" height="30" rx="4" fill="#1d4ed8"/>
                </g>
                
                <!-- Texto 404 flotante -->
                <g transform="translate(300, 100)">
                    <text x="0" y="0" font-size="72" font-weight="800" fill="url(#blueGrad)" text-anchor="middle" font-family="Inter, sans-serif">
                        404
                    </text>
                    <text x="0" y="0" font-size="72" font-weight="800" fill="none" stroke="#eff6ff" stroke-width="2" text-anchor="middle" font-family="Inter, sans-serif" opacity="0.3">
                        404
                    </text>
                </g>
                
                <!-- Elementos flotantes decorativos -->
                <circle cx="150" cy="250" r="8" fill="#60a5fa" opacity="0.7">
                    <animate attributeName="cy" values="250;230;250" dur="3s" repeatCount="indefinite"/>
                </circle>
                <circle cx="450" cy="280" r="6" fill="#3b82f6" opacity="0.5">
                    <animate attributeName="cy" values="280;260;280" dur="4s" repeatCount="indefinite"/>
                </circle>
                <rect x="80" y="320" width="20" height="20" rx="4" fill="#bfdbfe" opacity="0.6" transform="rotate(15 90 330)">
                    <animate attributeName="opacity" values="0.6;0.3;0.6" dur="3.5s" repeatCount="indefinite"/>
                </rect>
            </svg>
        </div>

        <!-- Contenido de texto -->
        <div class="space-y-6">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
                ¡Ups! Parece que te has perdido 🚀
            </h1>
            
            <p class="text-lg text-gray-600 max-w-md mx-auto">
                La página que buscas no existe, ha sido movida o nunca existió. 
                No te preocupes, volvamos a tierra firme.
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
                
                <button onclick="window.history.back()" 
                        class="px-8 py-3 border-2 border-blue-300 text-blue-700 hover:bg-blue-50 font-semibold rounded-xl transition-all duration-300">
                    ← Página anterior
                </button>
            </div>
        </div>

        <!-- Footer decorativo -->
        <div class="mt-12 text-xs text-gray-400">
            Error {{ $code ?? '404' }} • {{ $message ?? 'Página no encontrada' }}
        </div>
    </div>

    <script>
        document.addEventListener('mouseleave', () => {
            const svg = document.querySelector('svg');
            if (svg) svg.style.transform = 'rotateY(0deg) rotateX(0deg)';
        });
    </script>
</body>
</html>