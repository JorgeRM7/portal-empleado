<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Acceso no autorizado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa',
                            300: '#fdba74', 400: '#fb923c', 500: '#f97316',
                            600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'shake': 'shake 0.5s ease-in-out infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        shake: {
                            '0%, 100%': { transform: 'translateX(0)' },
                            '25%': { transform: 'translateX(-5px)' },
                            '75%': { transform: 'translateX(5px)' }
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
            background: radial-gradient(ellipse at bottom, #fff7ed 0%, #ffffff 100%);
        }
        .lock-shine {
            animation: shine 2s ease-in-out infinite;
        }
        @keyframes shine {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    
    <div class="max-w-4xl w-full text-center">
        
        <!-- Ilustración SVG animada: Candado y seguridad -->
        <div class="animate-float mb-8">
            <svg viewBox="0 0 600 400" class="w-full max-w-lg mx-auto" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="orangeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#f97316"/>
                        <stop offset="100%" style="stop-color:#fb923c"/>
                    </linearGradient>
                    <linearGradient id="metalGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#64748b"/>
                        <stop offset="100%" style="stop-color:#475569"/>
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <clipPath id="doorClip">
                        <rect x="220" y="180" width="160" height="220" rx="8"/>
                    </clipPath>
                </defs>
                
                <!-- Fondo decorativo: escudos de seguridad -->
                <g opacity="0.1">
                    <path d="M50 100 Q50 50 100 50 L100 120 Q100 170 50 170 Q0 170 0 120 Q0 50 50 100Z" fill="#f97316"/>
                    <path d="M550 150 Q550 100 600 100 L600 170 Q600 220 550 220 Q500 220 500 170 Q500 100 550 150Z" fill="#fb923c"/>
                </g>
                
                <!-- Puerta/Entrada -->
                <g transform="translate(300, 280)">
                    <!-- Marco de puerta -->
                    <rect x="-90" y="-120" width="180" height="240" rx="12" fill="#c2410c" stroke="#9a3412" stroke-width="3"/>
                    <!-- Puerta -->
                    <rect x="-75" y="-105" width="150" height="210" rx="6" fill="#ea580c" class="lock-shine"/>
                    <!-- Paneles de puerta -->
                    <rect x="-60" y="-90" width="40" height="60" rx="4" fill="#c2410c" opacity="0.5"/>
                    <rect x="20" y="-90" width="40" height="60" rx="4" fill="#c2410c" opacity="0.5"/>
                    <rect x="-60" y="10" width="40" height="60" rx="4" fill="#c2410c" opacity="0.5"/>
                    <rect x="20" y="10" width="40" height="60" rx="4" fill="#c2410c" opacity="0.5"/>
                    
                    <!-- Candado grande -->
                    <g transform="translate(0, -20)">
                        <!-- Cuerpo del candado -->
                        <rect x="-30" y="-10" width="60" height="50" rx="8" fill="url(#metalGrad)" stroke="#1e293b" stroke-width="2"/>
                        <!-- Arco del candado -->
                        <path d="M -20 -10 Q -20 -50, 0 -60 Q 20 -50, 20 -10" fill="none" stroke="url(#metalGrad)" stroke-width="8" stroke-linecap="round"/>
                        <!-- Ojo de la cerradura -->
                        <circle cx="0" cy="25" r="8" fill="#1e293b"/>
                        <rect x="-3" y="28" width="6" height="12" rx="2" fill="#1e293b"/>
                        <!-- Brillo en candado -->
                        <ellipse cx="-10" cy="5" rx="8" ry="12" fill="rgba(255,255,255,0.2)"/>
                    </g>
                    
                    <!-- Señal de prohibido -->
                    <g transform="translate(0, -90)">
                        <circle cx="0" cy="0" r="25" fill="#ef4444" stroke="#fff" stroke-width="3"/>
                        <line x1="-15" y1="-15" x2="15" y2="15" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
                        <animate attributeName="opacity" values="1;0.7;1" dur="1.5s" repeatCount="indefinite"/>
                    </g>
                </g>
                
                <!-- Texto 401 flotante -->
                <g transform="translate(300, 90)">
                    <text x="0" y="0" font-size="68" font-weight="800" fill="url(#orangeGrad)" text-anchor="middle" font-family="Inter, sans-serif">
                        401
                    </text>
                    <text x="0" y="0" font-size="68" font-weight="800" fill="none" stroke="#fff7ed" stroke-width="2" text-anchor="middle" font-family="Inter, sans-serif" opacity="0.3">
                        401
                    </text>
                </g>
                
                <!-- Elementos flotantes: llaves y estrellas -->
                <g transform="translate(120, 200)">
                    <path d="M10 0 Q10 -15 25 -15 Q35 -15 35 -5 L45 -5 Q50 -5 50 0 Q50 5 45 5 L35 5 Q35 15 25 15 Q10 15 10 0Z" fill="#fb923c" opacity="0.8">
                        <animate attributeName="opacity" values="0.8;0.4;0.8" dur="3s" repeatCount="indefinite"/>
                    </path>
                    <circle cx="45" cy="0" r="8" fill="#f97316"/>
                </g>
                
                <g transform="translate(480, 240)">
                    <path d="M0 -12 L3 -4 L12 -4 L5 2 L8 10 L0 5 L-8 10 L-5 2 L-12 -4 L-3 -4 Z" fill="#fed7aa" opacity="0.7">
                        <animateTransform attributeName="transform" type="rotate" from="0 0 0" to="360 0 0" dur="8s" repeatCount="indefinite"/>
                    </path>
                </g>
            </svg>
        </div>

        <!-- Contenido de texto -->
        <div class="space-y-6">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
                🔐 Acceso Restringido
            </h1>
            
            <p class="text-lg text-gray-600 max-w-md mx-auto">
                Necesitas iniciar sesión para acceder a esta sección. 
                Por favor, autentícate con tus credenciales válidas.
            </p>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <a href="{{ route('login') ?? url('/login') }}" 
                   class="group relative px-8 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-orange-500/30 transition-all duration-300 overflow-hidden">
                    <span class="relative z-10 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                        </svg>
                        Iniciar sesión
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-orange-600 to-orange-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>
                
                <a href="{{ url('/') }}" 
                   class="px-8 py-3 border-2 border-orange-300 text-orange-700 hover:bg-orange-50 font-semibold rounded-xl transition-all duration-300">
                    🏠 Ir al inicio
                </a>
            </div>

            <!-- Enlaces útiles -->
            {{-- <div class="pt-6 border-t border-orange-100">
                <p class="text-sm text-gray-500 mb-3">¿Problemas para acceder?</p>
                <div class="flex flex-wrap justify-center gap-3 text-sm">
                    <a href="{{ route('password.request') ?? url('/forgot-password') }}" class="text-orange-600 hover:text-orange-800 font-medium transition-colors">
                        ¿Olvidaste tu contraseña?
                    </a>
                    <span class="text-gray-300">•</span>
                    <a href="{{ url('/registro') }}" class="text-orange-600 hover:text-orange-800 font-medium transition-colors">
                        Crear una cuenta
                    </a>
                    <span class="text-gray-300">•</span>
                    <a href="{{ url('/ayuda/acceso') }}" class="text-orange-600 hover:text-orange-800 font-medium transition-colors">
                        Ayuda con el acceso
                    </a>
                </div>
            </div> --}}
        </div>

        <!-- Footer decorativo -->
        <div class="mt-12 text-xs text-gray-400">
            Error {{ $code ?? '401' }} • {{ $message ?? 'No autorizado' }}
        </div>
    </div>

    <!-- Script para efectos interactivos -->
    <script>
        // Efecto parallax suave en el SVG al mover el mouse
        // document.addEventListener('mousemove', (e) => {
        //     const svg = document.querySelector('svg');
        //     if (!svg) return;
            
        //     const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
        //     const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
        //     svg.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        // });

        // Resetear transformación al salir
        document.addEventListener('mouseleave', () => {
            const svg = document.querySelector('svg');
            if (svg) svg.style.transform = 'rotateY(0deg) rotateX(0deg)';
        });

        // Efecto de "denegado" al hacer clic en el candado
        document.querySelector('svg')?.addEventListener('click', function(e) {
            if (e.target.closest('g[transform*="280"]')) {
                this.classList.add('animate-shake');
                setTimeout(() => this.classList.remove('animate-shake'), 500);
            }
        });
    </script>
</body>
</html>