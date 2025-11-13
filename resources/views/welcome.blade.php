<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Nigeria Online Voting</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-[#1F2937] font-sans antialiased">
    <header class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="#" class="flex items-center gap-2">
                <span class="text-2xl font-bold tracking-tight text-[#008751]">eBallot</span>
            </a>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                <a href="#home" class="text-[#1F2937] hover:text-[#008751]">Home</a>
                <a href="#about" class="text-[#1F2937] hover:text-[#008751]">About</a>
                <a href="#how" class="text-[#1F2937] hover:text-[#008751]">How It Works</a>
                <a href="#features" class="text-[#1F2937] hover:text-[#008751]">Features</a>
                <a href="#contact" class="text-[#1F2937] hover:text-[#008751]">Contact</a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="px-4 py-2 rounded-lg bg-[#008751] text-white hover:scale-105 transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-4 py-2 rounded-lg border border-[#008751] text-[#008751] hover:bg-[#008751] hover:text-white transition">Login</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-4 py-2 rounded-lg bg-[#008751] text-white hover:scale-105 transition">Register</a>
                        @endif
                    @endauth
                @endif
            </nav>
            <button id="hamburger" aria-controls="mobile-menu" aria-expanded="false"
                class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg border border-[#1F2937]/20 text-[#1F2937] hover:bg-[#008751]/10 transition">
                <svg id="hamburger-open-icon" class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.5" />
                </svg>
                <svg id="hamburger-close-icon" class="w-6 h-6 hidden" viewBox="0 0 24 24" fill="none">
                    <path d="M6 6l12 12M6 18L18 6" stroke="currentColor" stroke-width="1.5" />
                </svg>
            </button>
            <div id="mobile-menu-backdrop" class="md:hidden fixed inset-0 bg-black/30 hidden z-40"></div>
            <div id="mobile-menu"
                class="md:hidden absolute top-full left-0 right-0 bg-white shadow-md border-t hidden z-50">
                <div class="px-6 py-4 grid gap-3 text-sm">
                    <a href="#home" class="block px-3 py-2 rounded-lg hover:bg-[#008751]/10">Home</a>
                    <a href="#about" class="block px-3 py-2 rounded-lg hover:bg-[#008751]/10">About</a>
                    <a href="#how" class="block px-3 py-2 rounded-lg hover:bg-[#008751]/10">How It Works</a>
                    <a href="#features" class="block px-3 py-2 rounded-lg hover:bg-[#008751]/10">Features</a>
                    <a href="#contact" class="block px-3 py-2 rounded-lg hover:bg-[#008751]/10">Contact</a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                                class="block px-3 py-2 rounded-lg bg-[#008751] text-white">Dashboard</a>
                        @else
                            <div class="grid grid-cols-2 gap-3 mt-2">
                                <a href="{{ route('login') }}"
                                    class="block px-3 py-2 text-center rounded-lg border border-[#008751] text-[#008751]">Login</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                        class="block px-3 py-2 text-center rounded-lg bg-[#008751] text-white">Register</a>
                                @endif
                            </div>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main id="home" class="pt-28 scroll-mt-28">
        <section class="relative overflow-hidden pb-24 scroll-mt-28">
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="w-full h-full"
                    style="background: linear-gradient(to right, #008751 0%, #008751 33.33%, #FFFFFF 33.33%, #FFFFFF 66.66%, #008751 66.66%, #008751 100%);">
                </div>
                <svg class="absolute bottom-0 left-0 w-full h-32" viewBox="0 0 1440 320" preserveAspectRatio="none">
                    <path
                        d="M0,224L48,213.3C96,203,192,181,288,186.7C384,192,480,224,576,240C672,256,768,256,864,234.7C960,213,1056,171,1152,165.3C1248,160,1344,192,1392,208L1440,224L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"
                        fill="#FFFFFF" fill-opacity="0.9"></path>
                </svg>
            </div>
            <div class="relative z-10 max-w-7xl mx-auto px-8 py-20 grid md:grid-cols-2 gap-12 items-center">
                <div class="rounded-lg bg-white/85 backdrop-blur p-6 md:p-8 shadow-md">
                    <h1 class="text-5xl md:text-6xl font-bold leading-tight text-[#008751]">Secure, Transparent, and
                        Accessible Voting for Every Nigerian.</h1>
                    <p class="mt-6 text-lg text-[#1F2937]">Empowering democracy through digital innovation.</p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="#how"
                            class="px-6 py-3 rounded-lg bg-[#008751] text-white shadow-xl hover:scale-105 transition">Get
                            Started</a>
                        <a href="#features"
                            class="px-6 py-3 rounded-lg border border-[#1F2937] text-[#1F2937] hover:bg-[#008751] hover:text-white transition">Learn
                            More</a>
                    </div>
                </div>
                <div class="relative">
                    <div class="rounded-lg shadow-xl bg-white/90 backdrop-blur p-6 md:p-8 hover:scale-105 transition">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="rounded-lg border shadow-md p-4">
                                <div
                                    class="mx-auto w-12 h-12 rounded-full bg-[#008751]/10 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2a5 5 0 015 5v3h1a2 2 0 012 2v8H4v-8a2 2 0 012-2h1V7a5 5 0 015-5Z"
                                            stroke="currentColor" stroke-width="1.5" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm">Register</p>
                            </div>
                            <div class="rounded-lg border shadow-md p-4">
                                <div
                                    class="mx-auto w-12 h-12 rounded-full bg-[#008751]/10 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5l7 4-7 4-7-4 7-4Z" stroke="currentColor" stroke-width="1.5" />
                                        <circle cx="12" cy="12" r="7" stroke="currentColor"
                                            stroke-width="1.5" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm">Verify Identity</p>
                                <p class="text-xs text-[#1F2937]/70">NIN/Driver’s License</p>
                            </div>
                            <div class="rounded-lg border shadow-md p-4">
                                <div
                                    class="mx-auto w-12 h-12 rounded-full bg-[#008751]/10 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="1.5" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm">Vote Securely</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="how" class="max-w-7xl mx-auto px-8 py-16 scroll-mt-28">
            <h2 class="text-4xl font-bold text-[#008751]">How It Works</h2>
            <div class="mt-8 grid md:grid-cols-3 gap-8">
                <div class="rounded-lg border shadow-md p-6 bg-white hover:scale-105 transition">
                    <div class="w-12 h-12 rounded-full bg-[#008751]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                            <path d="M12 12a5 5 0 100-10 5 5 0 000 10Z" stroke="currentColor" stroke-width="1.5" />
                            <path d="M4 22a8 8 0 1116 0H4Z" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium">Register</p>
                    <p class="mt-2 text-sm text-[#1F2937]/70">Create your account with basic details.</p>
                </div>
                <div class="rounded-lg border shadow-md p-6 bg-white hover:scale-105 transition">
                    <div class="w-12 h-12 rounded-full bg-[#008751]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3l7 4v6c0 4.418-3.582 8-8 8s-8-3.582-8-8V7l9-4Z" stroke="currentColor"
                                stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium">Verify Identity</p>
                    <p class="mt-2 text-sm text-[#1F2937]/70">Use NIN or Driver’s License.</p>
                </div>
                <div class="rounded-lg border shadow-md p-6 bg-white hover:scale-105 transition">
                    <div class="w-12 h-12 rounded-full bg-[#008751]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium">Vote Securely Online</p>
                    <p class="mt-2 text-sm text-[#1F2937]/70">Encrypted and tamper-evident ballots.</p>
                </div>
            </div>
        </section>

        <section id="features" class="max-w-7xl mx-auto px-8 py-16 scroll-mt-28">
            <h2 class="text-4xl font-bold text-[#008751]">Features</h2>
            <div class="mt-8 grid md:grid-cols-4 gap-8">
                <div
                    class="rounded-lg p-6 bg-gradient-to-br from-white to-[#F3F4F6] border shadow-xl hover:scale-105 transition">
                    <div class="w-12 h-12 rounded-lg bg-[#008751]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2l7 4v6c0 4.418-3.582 8-8 8s-8-3.582-8-8V6l9-4Z" stroke="currentColor"
                                stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium">Security</p>
                    <p class="mt-2 text-sm text-[#1F2937]/70">End-to-end encryption and secure identity.</p>
                </div>
                <div
                    class="rounded-lg p-6 bg-gradient-to-br from-white to-[#F3F4F6] border shadow-xl hover:scale-105 transition">
                    <div class="w-12 h-12 rounded-lg bg-[#008751]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                            <path d="M12 5c4 0 7 3 7 7s-3 7-7 7-7-3-7-7 3-7 7-7Zm0-3v6" stroke="currentColor"
                                stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium">Transparency</p>
                    <p class="mt-2 text-sm text-[#1F2937]/70">Auditable records and public verifiability.</p>
                </div>
                <div
                    class="rounded-lg p-6 bg-gradient-to-br from-white to-[#F3F4F6] border shadow-xl hover:scale-105 transition">
                    <div class="w-12 h-12 rounded-lg bg-[#008751]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                            <path d="M4 8h16M4 12h12M4 16h8" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium">Accessibility</p>
                    <p class="mt-2 text-sm text-[#1F2937]/70">Designed for all devices and abilities.</p>
                </div>
                <div
                    class="rounded-lg p-6 bg-gradient-to-br from-white to-[#F3F4F6] border shadow-xl hover:scale-105 transition">
                    <div class="w-12 h-12 rounded-lg bg-[#008751]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#008751]" viewBox="0 0 24 24" fill="none">
                            <path d="M4 18v-6l6-6 6 6v6H4Z" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium">Real-time Results</p>
                    <p class="mt-2 text-sm text-[#1F2937]/70">Live dashboards and secure aggregation.</p>
                </div>
            </div>
        </section>

        <section id="about" class="max-w-7xl mx-auto px-8 py-16 scroll-mt-28">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-[#008751]">About eBallot</h2>
                    <p class="mt-6 text-[#1F2937]">Nigeria’s democratic future thrives when every citizen can vote
                        securely and confidently. eBallot is built to serve citizens and officials with a trusted,
                        efficient, and transparent system that reflects national values.</p>
                    <p class="mt-4 text-[#1F2937]">From identity verification to secure ballot casting, eBallot
                        streamlines the process and improves participation across urban and rural communities.</p>
                </div>
                <div class="relative">
                    <div class="rounded-lg shadow-xl overflow-hidden">
                        <div
                            class="bg-gradient-to-br from-[#008751] via-white to-[#008751] p-8 flex items-center justify-center">
                            <svg class="w-40 h-40 text-[#FBBF24]" viewBox="0 0 24 24" fill="none">
                                <path d="M12 3l7 4v6c0 4.418-3.582 8-8 8s-8-3.582-8-8V7l9-4Z" stroke="currentColor"
                                    stroke-width="1.5" />
                                <path d="M9 12h6v5H9z" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative max-w-7xl mx-auto px-8 py-16 scroll-mt-28">
            <div class="absolute inset-0 pointer-events-none z-0">
                <svg class="absolute right-8 top-8 w-64 h-64 text-[#1F2937]/10" viewBox="0 0 24 24" fill="none">
                    <path d="M12 3l7 4v6c0 4.418-3.582 8-8 8s-8-3.582-8-8V7l9-4Z" stroke="currentColor"
                        stroke-width="1.5" />
                    <path d="M9 12h6v5H9z" stroke="currentColor" stroke-width="1.5" />
                </svg>
            </div>
            <div class="relative z-10">
                <h2 class="text-4xl font-bold text-[#008751]">Testimonials & Trust</h2>
                <div class="mt-8 grid md:grid-cols-3 gap-8">
                    <div class="rounded-lg bg-white border shadow-xl p-6">
                        <p class="text-sm">“I cast my vote online without hassle. The process was clear and secure.”
                        </p>
                        <p class="mt-3 text-xs text-[#1F2937]/70">Test Voter, Lagos</p>
                    </div>
                    <div class="rounded-lg bg-white border shadow-xl p-6">
                        <p class="text-sm">“Transparency reports gave us confidence in the results.”</p>
                        <p class="mt-3 text-xs text-[#1F2937]/70">Civic Group, Abuja</p>
                    </div>
                    <div class="rounded-lg bg-white border shadow-xl p-6">
                        <p class="text-sm">“Identity verification integrated smoothly with our systems.”</p>
                        <p class="mt-3 text-xs text-[#1F2937]/70">Institution Partner</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="contact" class="border-t bg-[#1F2937] text-white scroll-mt-28">
        <div class="max-w-7xl mx-auto px-8 py-10 grid md:grid-cols-3 gap-8">
            <div>
                <p class="text-xl font-bold text-[#FBBF24]">{{ config('app.name') }}</p>
                <p class="mt-2 text-sm text-white/80">Secure online voting for Nigeria.</p>
            </div>
            <div class="text-sm">
                <div class="flex gap-4">
                    <a href="#" class="hover:text-[#FBBF24]">Privacy Policy</a>
                    <a href="#" class="hover:text-[#FBBF24]">Terms</a>
                    <a href="#" class="hover:text-[#FBBF24]">Contact</a>
                </div>
                <p class="mt-4 text-white/70">Copyright © {{ config('app.name') }} {{ date('Y') }}</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="#"
                    class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                        <path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </a>
                <a href="#"
                    class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M7 8c0-1.657 1.343-3 3-3h4c1.657 0 3 1.343 3 3v8c0 1.657-1.343 3-3 3h-4c-1.657 0-3-1.343-3-3V8Z"
                            stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </a>
                <a href="#"
                    class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                        <path d="M4 20v-9l8-7 8 7v9h-6v-6H10v6H4Z" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </a>
            </div>
        </div>
    </footer>
</body>

</html>
