<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Replenishment Matching App - Upload dan rekonsiliasi data replenishment gudang">

    <title>{{ $title ?? 'Replenishment Matching' }} - Replenishment App</title>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F1F5F9] font-sans antialiased">
    <div class="flex h-screen overflow-hidden">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="w-[220px] bg-[#0F1B2D] flex flex-col flex-shrink-0 overflow-y-auto">
            {{-- Logo --}}
            <div class="px-5 pt-5 pb-4">
                <a href="{{ route('replenishment.index') }}" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-white text-[13px] font-bold leading-tight">Replenishment</div>
                        <div class="text-[#64748B] text-[10px] font-medium tracking-wider uppercase">MATCHING APP</div>
                    </div>
                </a>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 space-y-0.5">
                {{-- Matching Report (Active) --}}
                <a href="{{ route('replenishment.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('replenishment.index') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Matching Report
                </a>

                {{-- Dashboard --}}
                <a href="#"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white transition-smooth">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </a>

                {{-- Replenishment Report History --}}
                <a href="{{ route('replenishment.report-history') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('replenishment.report-history') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Replenishment Report History
                </a>

                {{-- Analisa Replenishment Report History --}}
                <a href="{{ route('replenishment.analysis-history') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('replenishment.analysis-history') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Analisa Replenishment Report History
                </a>

                {{-- Separator --}}
                <div class="!mt-4 !mb-2 px-3">
                    <div class="text-[10px] font-semibold text-[#475569] tracking-wider uppercase">System Setting</div>
                </div>

                {{-- User & Role --}}
                <a href="#"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white transition-smooth">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    User & Role
                </a>

                {{-- Application Log --}}
                <a href="#"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white transition-smooth">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Application Log
                </a>
            </nav>

            {{-- Sidebar Footer --}}
            <div class="px-4 py-4 border-t border-[#1A2B42]">
                <div class="flex items-center justify-between text-[10px]">
                    <div>
                        <div class="text-[#64748B] font-semibold uppercase tracking-wider">FACILITY</div>
                        <div class="text-white font-medium mt-0.5">DC-North 4</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[#64748B] font-semibold uppercase tracking-wider">Node/Bays</div>
                        <div class="text-white font-medium mt-0.5">14 / 16</div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="w-2 h-2 rounded-full bg-[#22C55E] animate-pulse"></span>
                    <span class="text-[#22C55E] text-[10px] font-medium">Active</span>
                </div>
            </div>
        </aside>

        {{-- ===== MAIN CONTENT AREA ===== --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top Bar --}}
            <header class="h-[52px] bg-white border-b border-[#E2E8F0] flex items-center justify-between px-6 flex-shrink-0">
                <div class="flex items-center gap-3">
                    {{-- Shift Badge --}}
                    <div class="flex items-center gap-1.5 bg-[#F0FDF4] border border-[#BBF7D0] rounded-full px-3 py-1">
                        <span class="w-2 h-2 rounded-full bg-[#22C55E]"></span>
                        <span class="text-[11px] font-semibold text-[#15803D]">Shift 2</span>
                        <span class="text-[10px] text-[#16A34A]">Active</span>
                    </div>
                    {{-- Zone Info --}}
                    <div class="flex items-center gap-1.5 text-[12px] text-[#475569]">
                        <svg class="w-3.5 h-3.5 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Zone A, B, High-Bay</span>
                    </div>
                </div>

                {{-- User Profile --}}
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-[12px] font-semibold text-[#0F172A]">Marcus Vance</div>
                        <div class="text-[10px] text-[#94A3B8]">Warehouse Lead (WH-882)</div>
                    </div>
                    <div class="w-9 h-9 bg-[#E2E8F0] rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
