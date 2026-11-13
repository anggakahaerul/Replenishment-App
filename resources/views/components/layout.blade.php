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

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Modern SaaS Table & UI System (Balanced & Comfortable) */
        .modern-page {
            padding: 24px;
            width: 100%;
            box-sizing: border-box;
        }
        .modern-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .modern-card-header {
            padding: 14px 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-variant-numeric: tabular-nums;
            text-align: left;
        }
        .modern-table thead tr {
            background-color: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
        }
        .modern-table th {
            padding: 11px 16px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            color: #64748B !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em !important;
            white-space: nowrap !important;
            border: none !important;
        }
        .modern-table tbody tr {
            border-bottom: 1px solid #F1F5F9 !important;
            transition: background-color 0.15s ease;
        }
        .modern-table tbody tr:last-child {
            border-bottom: none !important;
        }
        .modern-table tbody tr:hover {
            background-color: #F8FAFC !important;
        }
        .modern-table td {
            padding: 12px 16px !important;
            font-size: 13px !important;
            color: #1E293B !important;
            line-height: 1.45 !important;
            vertical-align: middle !important;
            border: none !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            font-variant-numeric: tabular-nums !important;
        }
        .badge-loc-src {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #1D4ED8;
            background-color: #EFF6FF;
            border: 1px solid #DBEAFE;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }
        .badge-loc-dst {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            background-color: #F1F5F9;
            border: 1px solid #E2E8F0;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }
        .badge-loc-err {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #DC2626;
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }
        .badge-more-src {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            font-weight: 600;
            color: #2563EB;
            background-color: #F8FAFC;
            border: 1px solid #CBD5E1;
            padding: 2px 7px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.12s ease;
        }
        .badge-more-src:hover {
            background-color: #EFF6FF;
            border-color: #93C5FD;
        }
        .badge-qty-pill {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            font-size: 12.5px;
            font-weight: 700;
            color: #0F172A;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 2px 8px;
            border-radius: 6px;
        }
        .modern-search-input {
            height: 34px;
            padding: 0 12px 0 32px;
            font-size: 12.5px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            background-color: #F8FAFC;
            color: #0F172A;
            outline: none;
            width: 260px;
            transition: all 0.15s ease;
        }
        .modern-search-input:focus {
            background-color: #ffffff;
            border-color: #2563EB;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        /* Modern Custom Dropdown System */
        .modern-dd-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .modern-dd-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 36px;
            padding: 0 12px;
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-size: 12px;
            color: #0F172A;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            user-select: none;
            white-space: nowrap;
        }
        .modern-dd-btn:hover {
            border-color: #CBD5E1;
            background-color: #F8FAFC;
        }
        .modern-dd-btn-active {
            border-color: #2563EB !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
            background-color: #ffffff !important;
        }
        .modern-dd-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 210px;
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 4px 10px -4px rgba(15, 23, 42, 0.06);
            z-index: 60;
        }
        .modern-dd-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 500;
            color: #334155;
            background: transparent;
            border: none;
            text-align: left;
            cursor: pointer;
            transition: all 0.12s ease;
            box-sizing: border-box;
        }
        .modern-dd-item:hover {
            background-color: #F1F5F9;
            color: #0F172A;
        }
        .modern-dd-item-selected {
            background-color: #EFF6FF !important;
            color: #2563EB !important;
            font-weight: 700 !important;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Universal Bulletproof Centered Modal System */
        .modal-backdrop,
        .custom-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
        }

        .modal-backdrop[style*="display: none"] {
            display: none !important;
        }

        .modal-dialog,
        .custom-modal-dialog {
            margin: auto;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            border: 1px solid #E2E8F0;
            width: 100%;
            max-width: 480px;
            position: relative;
            box-sizing: border-box;
            text-align: left;
        }
    </style>
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
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('dashboard*') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
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

                {{-- Master Data Cabang (Admin only) --}}
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('master.branches.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('master.branches.*') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Master Data Cabang
                </a>
                @endif

                {{-- Master Data Shift (Admin only) --}}
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('master.shifts.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('master.shifts.*') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Master Data Shift
                </a>
                @endif

                {{-- User & Role (Admin only) --}}
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('users.*') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    User & Role
                </a>
                @endif

                {{-- Application Log (Admin only) --}}
                @if(auth()->user()?->isAdmin())
                <a href="{{ route('logs.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-[13px] font-medium transition-smooth
                          {{ request()->routeIs('logs.*') ? 'bg-white text-[#0F1B2D]' : 'text-[#94A3B8] hover:bg-[#1A2B42] hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Application Log
                </a>
                @endif
            </nav>

            {{-- Sidebar Footer --}}
            <div class="px-4 py-4 border-t border-[#1A2B42]">
                <div class="flex items-center justify-between text-[10px]">
                    <div>
                        <div class="text-[#64748B] font-semibold uppercase tracking-wider">AKSES CABANG</div>
                        <div class="text-white font-medium mt-0.5">
                            @auth
                                {{ auth()->user()->hasGlobalBranchAccess() ? 'Semua Cabang' : (auth()->user()->branch?->code ?? 'Cabang #' . auth()->user()->branch_id) }}
                            @else
                                -
                            @endauth
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[#64748B] font-semibold uppercase tracking-wider">STATUS</div>
                        <div class="text-[#22C55E] font-medium mt-0.5">Online</div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ===== MAIN CONTENT AREA ===== --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top Bar --}}
            <header class="h-[52px] bg-white border-b border-[#E2E8F0] flex items-center justify-between px-6 flex-shrink-0">
                <div class="flex items-center gap-3">
                    @auth
                    <div class="flex items-center gap-1.5 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg px-2.5 py-1">
                        <svg class="w-3.5 h-3.5 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span class="text-[11px] font-semibold text-[#0F172A]">
                            {{ auth()->user()->hasGlobalBranchAccess() ? 'Akses: Semua Cabang' : ('Cabang: ' . (auth()->user()->branch?->code ?? '-')) }}
                        </span>
                    </div>
                    @endauth
                </div>

                {{-- User Profile & Logout --}}
                @auth
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-[12px] font-semibold text-[#0F172A] flex items-center justify-end gap-1.5">
                            <span>{{ auth()->user()->name }}</span>
                            @if(auth()->user()->isAdmin())
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#EF4444]/10 text-[#DC2626] border border-[#EF4444]/20 uppercase">Admin</span>
                            @elseif(auth()->user()->isOperator())
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#3B82F6]/10 text-[#2563EB] border border-[#3B82F6]/20 uppercase">Operator</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#64748B]/10 text-[#475569] border border-[#64748B]/20 uppercase">Viewer</span>
                            @endif
                        </div>
                        <div class="text-[10px] text-[#94A3B8]">{{ auth()->user()->email }}</div>
                    </div>
                    <div class="w-9 h-9 bg-[#1E293B] text-white font-bold text-xs rounded-full flex items-center justify-center uppercase shadow-sm">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-1">
                        @csrf
                        <button type="submit" title="Keluar / Logout" class="p-2 text-[#94A3B8] hover:text-[#DC2626] hover:bg-[#FEE2E2] rounded-lg transition-smooth">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
                @else
                <div>
                    <a href="{{ route('login') }}" class="text-xs font-semibold px-3 py-1.5 bg-[#3B82F6] text-white rounded-lg hover:bg-blue-600 transition-smooth">
                        Login
                    </a>
                </div>
                @endauth
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
