<x-layout title="Dashboard Riwayat Analisis">
    <style>
        /* Bulletproof Grid & Flexbox styles independent of utility compiler */
        .shift-cards-container {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            align-items: stretch;
            margin-bottom: 20px;
        }

        @media (max-width: 1024px) {
            .shift-cards-container {
                grid-template-columns: 1fr;
            }
        }

        .compact-shift-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            box-sizing: border-box;
        }

        .metrics-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
            margin-bottom: 12px;
        }

        .metrics-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }

        .metric-item-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .metric-item-box-blue {
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            padding: 10px 12px;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .metric-item-box-red {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 10px 12px;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .metric-tag-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .metric-value-num {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
            margin-top: 4px;
        }

        .progress-bar-track {
            width: 100%;
            height: 8px;
            border-radius: 9999px;
            background-color: #e2e8f0;
            overflow: hidden;
            margin-top: 6px;
        }

        .all-shift-wrapper {
            max-width: 900px;
            margin-bottom: 24px;
        }

        .dash-th-wrap {
            position: relative;
            display: inline-flex;
            flex-direction: column;
            align-items: inherit;
            cursor: help;
        }

        .dash-th-label {
            border-bottom: 1px dotted #94A3B8;
            padding-bottom: 1px;
        }

        .dash-th-tip {
            position: absolute;
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%) translateY(4px);
            width: 240px;
            padding: 10px 12px;
            border-radius: 10px;
            background-color: #0F172A;
            color: #F8FAFC;
            font-size: 11.5px;
            font-weight: 400;
            line-height: 1.45;
            text-align: left;
            text-transform: none;
            letter-spacing: normal;
            white-space: normal;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.35);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.15s ease, transform 0.15s ease;
            z-index: 50;
        }

        .dash-th-tip.dash-th-tip-right {
            left: auto;
            right: 0;
            transform: translateY(4px);
        }

        .dash-th-wrap:hover .dash-th-tip {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .dash-th-wrap:hover .dash-th-tip.dash-th-tip-right {
            transform: translateY(0);
        }

        .dash-th-tip strong {
            color: #FFFFFF;
            font-weight: 700;
        }

        .dash-th-tip-src {
            display: block;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid rgba(148, 163, 184, 0.25);
            color: #CBD5E1;
            font-size: 11px;
        }
    </style>

    <div
        x-data="dashboardAnalytics({
            items: {{ Js::from($items) }},
            cumulative: {{ Js::from($cumulative) }},
            shift1: {{ Js::from($shift1Card) }},
            shift2: {{ Js::from($shift2Card) }},
            allShift: {{ Js::from($allShiftCard) }},
            cardDailyData: {{ Js::from($cardDailyData ?? []) }},
            trendData: {{ Js::from($trendData) }},
            matchHistoryItems: {{ Js::from($matchHistoryItems ?? []) }}
        })"
        class="p-6 space-y-6"
    >
        @if(session('success'))
            <div style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; color: #166534; font-size: 12.5px; font-weight: 600;">
                <svg style="width: 18px; height: 18px; color: #16A34A; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; color: #991B1B; font-size: 12.5px; font-weight: 600;">
                <svg style="width: 18px; height: 18px; color: #DC2626; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        {{-- ===== 1. DASHBOARD HEADER & BRANCH FILTER ===== --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <h1 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0;">Dashboard Replenishment</h1>
                <p style="font-size: 12.5px; color: #64748B; margin: 2px 0 0 0;">Ringkasan performa matching per shift dan tren harian</p>
            </div>

            {{-- Filter Cabang (Modern Dropdown) --}}
            @php
                $selectedBranchObj = ($branches ?? collect())->firstWhere('id', (int)($selectedBranchId ?? 0));
                $selectedBranchLabel = $selectedBranchObj
                    ? (preg_replace('/[^a-z0-9]/i', '', $selectedBranchObj->code) !== preg_replace('/[^a-z0-9]/i', '', $selectedBranchObj->name)
                        ? $selectedBranchObj->code . ' • ' . $selectedBranchObj->name
                        : $selectedBranchObj->code)
                    : 'Semua Cabang';
            @endphp
            <form action="{{ route('dashboard') }}" method="GET" style="display: flex; align-items: center; gap: 8px; margin: 0;" x-data="{ open: false, selectedVal: '{{ $selectedBranchId ?? '' }}' }">
                @if(($search ?? '') !== '')
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <input type="hidden" name="branch_id" x-model="selectedVal">

                @if(auth()->user()?->hasGlobalBranchAccess())
                    <div class="modern-dd-wrap" @click.outside="open = false">
                        <button type="button"
                                @click="open = !open"
                                class="modern-dd-btn"
                                :class="open ? 'modern-dd-btn-active' : ''">
                            <svg style="width: 14px; height: 14px; color: #64748B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span style="font-size: 11.5px; font-weight: 600; color: #64748B;">Cabang:</span>
                            <span style="font-size: 12px; font-weight: 700; color: #0F172A;">{{ $selectedBranchLabel }}</span>
                            <svg style="width: 13px; height: 13px; color: #64748B; transition: transform 0.15s ease;"
                                 :style="open ? 'transform: rotate(180deg)' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="modern-dd-menu">
                            <button type="button"
                                    @click="selectedVal = ''; $nextTick(() => $el.closest('form').submit())"
                                    class="modern-dd-item {{ ($selectedBranchId ?? '') === '' ? 'modern-dd-item-selected' : '' }}">
                                <span>Semua Cabang</span>
                                @if(($selectedBranchId ?? '') === '')
                                    <svg style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </button>
                            @foreach($branches ?? [] as $b)
                                @php
                                    $isSameName = strtolower(preg_replace('/[^a-z0-9]/i', '', $b->code)) === strtolower(preg_replace('/[^a-z0-9]/i', '', $b->name));
                                    $isSelected = (string)($selectedBranchId ?? '') === (string)$b->id;
                                @endphp
                                <button type="button"
                                        @click="selectedVal = '{{ $b->id }}'; $nextTick(() => $el.closest('form').submit())"
                                        class="modern-dd-item {{ $isSelected ? 'modern-dd-item-selected' : '' }}">
                                    <div>
                                        <div style="font-weight: 700; line-height: 1.25;">{{ $b->code }}</div>
                                        @if(!$isSameName)
                                            <div style="font-size: 11px; color: #64748B; font-weight: 500; margin-top: 1px;">{{ $b->name }}</div>
                                        @endif
                                    </div>
                                    @if($isSelected)
                                        <svg style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="modern-dd-btn" style="cursor: default;">
                        <svg style="width: 14px; height: 14px; color: #64748B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span style="font-size: 11.5px; font-weight: 600; color: #64748B;">Cabang:</span>
                        <span style="font-size: 12px; font-weight: 700; color: #2563EB;">
                            {{ auth()->user()?->branch?->code ?? '-' }}
                        </span>
                    </div>
                @endif
            </form>
        </div>

        {{-- ===== 2. SHIFT CARDS PERIOD FILTER (HARIAN / MINGGUAN / BULANAN / DATE PICKER) ===== --}}
        <div class="flex flex-wrap items-center justify-between gap-3" style="margin-bottom: 12px;">
            <div class="flex flex-wrap items-center gap-2.5">
                <div class="inline-flex items-center bg-[#F1F5F9] border border-[#E2E8F0] rounded-full p-1 gap-1">
                    <button type="button" @click="cardPeriod = 'latest'"
                        class="px-3.5 py-1 rounded-full text-[12px] transition-all cursor-pointer"
                        :class="cardPeriod === 'latest' ? 'bg-white text-[#0F172A] shadow-sm font-semibold' : 'text-[#64748B] hover:text-[#0F172A] font-medium'">
                        Hari Terakhir
                    </button>
                    <button type="button" @click="cardPeriod = 'week'"
                        class="px-3.5 py-1 rounded-full text-[12px] transition-all cursor-pointer"
                        :class="cardPeriod === 'week' ? 'bg-white text-[#0F172A] shadow-sm font-semibold' : 'text-[#64748B] hover:text-[#0F172A] font-medium'">
                        Minggu Ini (7H)
                    </button>
                    <button type="button" @click="selectCardMonthMode()"
                        class="px-3.5 py-1 rounded-full text-[12px] transition-all cursor-pointer"
                        :class="cardPeriod === 'month' ? 'bg-white text-[#0F172A] shadow-sm font-semibold' : 'text-[#64748B] hover:text-[#0F172A] font-medium'">
                        Bulanan
                    </button>
                    <button type="button" @click="cardPeriod = 'all'"
                        class="px-3.5 py-1 rounded-full text-[12px] transition-all cursor-pointer"
                        :class="cardPeriod === 'all' ? 'bg-white text-[#0F172A] shadow-sm font-semibold' : 'text-[#64748B] hover:text-[#0F172A] font-medium'">
                        Semua
                    </button>
                    <button type="button" @click="selectCardCustomMode()"
                        class="px-3.5 py-1 rounded-full text-[12px] transition-all inline-flex items-center gap-1.5 cursor-pointer"
                        :class="cardPeriod === 'custom' ? 'bg-white text-[#2563EB] shadow-sm font-semibold' : 'text-[#64748B] hover:text-[#0F172A] font-medium'">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Pilih Tanggal
                    </button>
                </div>

                {{-- Month Selector when Bulanan is active --}}
                <template x-if="cardPeriod === 'month' && availableCardMonths.length > 0">
                    <select x-model="cardMonth"
                        class="h-[34px] px-3 rounded-full border border-[#CBD5E1] bg-white text-[12px] font-semibold text-[#0F172A] outline-none focus:border-[#2563EB] cursor-pointer shadow-2xs">
                        <template x-for="m in availableCardMonths" :key="m.key">
                            <option :value="m.key" x-text="m.label"></option>
                        </template>
                    </select>
                </template>

                {{-- Date Picker Range when Pilih Tanggal is active --}}
                <template x-if="cardPeriod === 'custom'">
                    <div class="inline-flex items-center gap-2 bg-white border border-[#BFDBFE] rounded-full px-3.5 h-[34px] shadow-2xs">
                        <input type="date" x-model="cardDateFrom"
                            class="text-[12px] font-semibold text-[#0F172A] border-none outline-none bg-transparent cursor-pointer">
                        <span class="text-[11px] text-[#94A3B8] font-semibold">s/d</span>
                        <input type="date" x-model="cardDateTo"
                            class="text-[12px] font-semibold text-[#0F172A] border-none outline-none bg-transparent cursor-pointer">
                    </div>
                </template>
            </div>

            <div class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#64748B]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#2563EB]"></span>
                <span x-text="cardPeriodLabel"></span>
            </div>
        </div>

        {{-- ===== 2. 3 COMPACT SHIFT CARDS (SHIFT 1, SHIFT 2, ALL SHIFT) ===== --}}
        <div class="shift-cards-container">
            <template x-for="(card, cIdx) in [
                { data: activeShiftCards.shift1, label: 'Shift 1', time: shift1.time_range || '06:00 - 14:00 WIB', badge: activeShiftCards.shift1.status || 'Selesai', accent: '#0F172A', isAll: false },
                { data: activeShiftCards.shift2, label: 'Shift 2', time: shift2.time_range || '14:00 - 22:00 WIB', badge: activeShiftCards.shift2.status || 'Selesai', accent: '#2563EB', isAll: false },
                { data: activeShiftCards.allShift, label: 'All Shift', time: 'Gabungan Shift 1 & 2', badge: activeShiftCards.allShift.status || 'Total', accent: '#16A34A', isAll: true }
            ]" :key="cIdx">
                <div class="compact-shift-card" :style="card.isAll ? 'border-color: #BFDBFE; background-color: #FAFCFF;' : ''">
                    {{-- Header --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span style="font-size: 16px; font-weight: 800; color: #0F172A;" x-text="card.label"></span>
                            <span style="font-size: 10.5px; font-weight: 600; color: #64748B; background-color: #F1F5F9; border: 1px solid #E2E8F0; padding: 2px 8px; border-radius: 999px;"
                                  x-text="card.time"></span>
                        </div>
                        <span style="font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 999px;"
                              :style="card.isAll ? 'background-color: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;' : 'background-color: #F1F5F9; color: #475569; border: 1px solid #E2E8F0;'"
                              x-text="card.badge"></span>
                    </div>

                    {{-- Main KPI + Progress --}}
                    <div>
                        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <span style="font-size: 24px; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums;"
                                      :style="'color: ' + card.accent"
                                      x-text="card.data?.completion || '0.0%'"></span>
                                <span style="font-size: 12px; font-weight: 700; color: #334155;"
                                      x-text="(card.data?.match_count || 0) + ' / ' + (card.data?.total_tasks || 0) + ' Matched'"></span>
                            </div>
                            <span style="font-size: 11.5px; font-weight: 700; color: #DC2626;"
                                  x-text="(card.data?.pending_count || 0) + ' Pending'"></span>
                        </div>
                        <div style="width: 100%; height: 6px; background-color: #E2E8F0; border-radius: 999px; overflow: hidden;">
                            <div style="height: 100%; border-radius: 999px; transition: width 0.4s ease;"
                                 :style="'width: ' + Math.min(100, card.data?.completion_num || 0) + '%; background-color: ' + (card.isAll ? '#2563EB' : card.accent)"></div>
                        </div>
                    </div>

                    {{-- Compact Footer Stats --}}
                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; padding-top: 8px; border-top: 1px solid #F1F5F9;">
                        <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 6px 8px;">
                            <div style="font-size: 9.5px; font-weight: 700; color: #2563EB; text-transform: uppercase;">Put Away</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0F172A; margin-top: 1px;" x-text="card.data?.put_away || 0"></div>
                        </div>
                        <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 6px 8px;">
                            <div style="font-size: 9.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Transfer</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0F172A; margin-top: 1px;" x-text="card.data?.transfer || 0"></div>
                        </div>
                        <div style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 6px 8px;">
                            <div style="font-size: 9.5px; font-weight: 700; color: #DC2626; text-transform: uppercase;">Melebihi POC</div>
                            <div style="font-size: 14px; font-weight: 800; color: #DC2626; margin-top: 1px;" x-text="card.data?.melebihi_poc || 0"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- ===== 3. TREN MATCHING PERTANGGAL (COMBO BAR & LINE CHART CARD) ===== --}}
        <div class="bg-white rounded-2xl border border-[#CBD5E1] shadow-sm p-6">
            {{-- Header & Interactive Filters --}}
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                {{-- Title & Subtitle --}}
                <div>
                    <h2 class="text-[20px] font-bold text-[#0F172A] tracking-tight">Tren Matching Pertanggal</h2>
                    <p class="text-[13px] text-[#64748B] mt-0.5">Perbandingan volume task, matching, dan selisih POC harian.</p>
                </div>

                {{-- Filters on Right Side --}}
                <div class="flex flex-col items-start lg:items-end gap-2.5">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Metric Mode Toggle (Jumlah vs Persentase) --}}
                        <div class="inline-flex items-center bg-[#F1F5F9] border border-[#E2E8F0] rounded-full p-1 gap-1">
                            <button
                                type="button"
                                @click="setMetricMode('count')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendMetricMode === 'count'
                                    ? 'bg-white text-[#0F172A] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Jumlah
                            </button>
                            <button
                                type="button"
                                @click="setMetricMode('percent')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendMetricMode === 'percent'
                                    ? 'bg-white text-[#2563EB] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Persentase (%)
                            </button>
                        </div>

                        {{-- Shift Filter Pills --}}
                        <div class="inline-flex items-center bg-[#F1F5F9] border border-[#E2E8F0] rounded-full p-1 gap-1">
                            <button
                                type="button"
                                @click="setShift('all')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendShift === 'all'
                                    ? 'bg-white text-[#0F172A] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Semua Shift
                            </button>
                            <button
                                type="button"
                                @click="setShift('shift1')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendShift === 'shift1'
                                    ? 'bg-white text-[#0F172A] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Shift 1
                            </button>
                            <button
                                type="button"
                                @click="setShift('shift2')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendShift === 'shift2'
                                    ? 'bg-white text-[#0F172A] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Shift 2
                            </button>
                        </div>

                        {{-- Category Filter Pills --}}
                        <div class="inline-flex items-center bg-[#F1F5F9] border border-[#E2E8F0] rounded-full p-1 gap-1">
                            <button
                                type="button"
                                @click="setCategory('all')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendCategory === 'all'
                                    ? 'bg-white text-[#0F172A] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Semua Kategori
                            </button>
                            <button
                                type="button"
                                @click="setCategory('dry')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendCategory === 'dry'
                                    ? 'bg-white text-[#0F172A] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Dry
                            </button>
                            <button
                                type="button"
                                @click="setCategory('frozen')"
                                class="px-3.5 py-1 rounded-full text-[12px] transition-all"
                                :class="trendCategory === 'frozen'
                                    ? 'bg-white text-[#0F172A] shadow-sm font-semibold'
                                    : 'text-[#64748B] hover:text-[#0F172A] font-medium'"
                            >
                                Frozen
                            </button>
                        </div>
                    </div>

                    {{-- Date Range & Month Dropdown --}}
                    <style>
                        .dash-range-item {
                            width: 100%;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 12px;
                            padding: 8px 12px;
                            border-radius: 8px;
                            font-size: 12px;
                            font-weight: 600;
                            border: none;
                            background-color: transparent;
                            color: #334155;
                            cursor: pointer;
                            text-align: left;
                            white-space: nowrap;
                            transition: background-color 0.12s ease, color 0.12s ease;
                        }
                        .dash-range-item:hover {
                            background-color: #F8FAFC;
                            color: #0F172A;
                        }
                        .dash-range-item.active {
                            background-color: #EFF6FF;
                            color: #2563EB;
                            font-weight: 700;
                        }
                    </style>
                    <div class="relative" x-data="{ openRange: false }" @click.outside="openRange = false">
                        <button type="button"
                                @click="openRange = !openRange"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[12px] font-semibold text-[#2563EB] bg-[#EFF6FF] border border-[#BFDBFE] hover:bg-[#DBEAFE] transition-all cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-[#2563EB]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span x-text="trendRangeLabel">7 Hari Terakhir</span>
                            <svg class="w-3 h-3 text-[#2563EB] transition-transform" :style="openRange ? 'transform: rotate(180deg)' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openRange"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="position: absolute; right: 0; top: calc(100% + 6px); min-width: 215px; max-height: 290px; overflow-y: auto; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14); padding: 6px; z-index: 40;">
                            <template x-for="opt in [
                                { key: '7d', label: '7 Hari Terakhir' },
                                { key: '14d', label: '14 Hari Terakhir' },
                                { key: '30d', label: '30 Hari Terakhir' },
                                { key: 'all', label: 'Semua Data' }
                            ]" :key="opt.key">
                                <button type="button"
                                        @click="setTrendRange(opt.key); openRange = false"
                                        class="dash-range-item"
                                        :class="trendRange === opt.key ? 'active' : ''">
                                    <span x-text="opt.label"></span>
                                    <svg x-show="trendRange === opt.key" style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </template>

                            <template x-if="(trendData?.months || []).length > 0">
                                <div>
                                    <div style="height: 1px; background-color: #F1F5F9; margin: 5px 0;"></div>
                                    <div style="padding: 4px 12px; font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.05em;">Per Bulan</div>
                                    <template x-for="m in (trendData?.months || [])" :key="m.key">
                                        <button type="button"
                                                @click="setTrendRange('month:' + m.key); openRange = false"
                                                class="dash-range-item"
                                                :class="trendRange === ('month:' + m.key) ? 'active' : ''">
                                            <span x-text="m.label"></span>
                                            <svg x-show="trendRange === ('month:' + m.key)" style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Legend & Statistics Subheader --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mt-6 pt-4 border-t border-[#F1F5F9]">
                {{-- Custom Legend Items --}}
                <div class="flex flex-wrap items-center gap-5">
                    {{-- Matched Legend --}}
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded bg-[#3E52B7] flex-shrink-0"></span>
                        <span class="text-[12px] font-semibold text-[#334155]">Matched</span>
                    </div>

                    {{-- Unmatch Legend --}}
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded bg-[#FECDD3] border border-[#FCA5A5] flex-shrink-0"></span>
                        <span class="text-[12px] font-semibold text-[#334155]">Unmatch / Selisih POC</span>
                    </div>

                    {{-- Trend Line Legend --}}
                    <div class="flex items-center gap-2">
                        <div class="flex items-center w-6 justify-center">
                            <div class="w-2 h-0.5 bg-[#0F172A]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#0F172A] border-2 border-white ring-1 ring-[#0F172A]"></div>
                            <div class="w-2 h-0.5 bg-[#0F172A]"></div>
                        </div>
                        <span class="text-[12px] font-semibold text-[#334155]">Target / Total Tasks (Trend Line)</span>
                    </div>
                </div>

                {{-- Right Stats & Badge --}}
                <div class="flex items-center gap-3">
                    <div class="text-[12px] text-[#475569]">
                        Rata-rata Match Rate (<span x-text="trendRangeLabel">7 Hari Terakhir</span>):
                        <span class="font-bold text-[#3E52B7]" x-text="currentAvgRate">92.4%</span>
                    </div>

                    <span class="px-2.5 py-0.5 rounded text-[11px] font-medium text-[#475569] bg-[#F1F5F9] border border-[#E2E8F0]">
                        Combo Bar & Line
                    </span>
                </div>
            </div>

            {{-- Chart Canvas Container --}}
            <template x-if="(trendData?.dates || []).length === 0">
                <div class="w-full h-[280px] mt-4 rounded-xl border border-dashed border-[#E2E8F0] bg-[#F8FAFC] flex flex-col items-center justify-center text-center p-6">
                    <svg class="w-10 h-10 text-[#CBD5E1] mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <div class="text-[13px] font-semibold text-[#64748B]">Belum ada data tren harian</div>
                    <div class="text-[11.5px] text-[#94A3B8] mt-0.5">Grafik tren akan otomatis terbentuk setelah Anda meng-import data di menu Matching Report.</div>
                </div>
            </template>
            <div x-show="(trendData?.dates || []).length > 0" class="relative w-full h-[320px] mt-4">
                <canvas id="trendMatchingChart"></canvas>
            </div>
        </div>

        {{-- ===== 4. MAIN RIWAYAT ANALISIS TABLE CARD ===== --}}
        <div class="bg-white rounded-2xl border border-[#CBD5E1] shadow-sm p-6">

            {{-- Top Header & Tabs --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-[20px] font-bold text-[#0F172A] tracking-tight">Riwayat Analisis</h1>
                    <p class="text-[13px] text-[#64748B] mt-0.5">Riwayat dan log agregasi performa matching replenishment per kategori dan shift.</p>
                </div>
            </div>

            {{-- Search & Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-5">
                {{-- Search Box --}}
                <div class="relative w-full sm:w-80">
                    <svg class="w-4 h-4 text-[#94A3B8] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Cari Kategori atau Tanggal..."
                        class="w-full pl-10 pr-4 py-2 text-[12px] border border-[#CBD5E1] rounded-xl bg-white focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10 outline-none transition-smooth placeholder:text-[#94A3B8]"
                    >
                </div>

                {{-- Action Buttons: Filter Tambahan Popover --}}
                <div class="relative flex items-center gap-2.5 w-full sm:w-auto justify-end" @click.outside="showFilterModal = false">
                    <button
                        type="button"
                        @click="showFilterModal = !showFilterModal"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-[12px] font-semibold border transition-smooth cursor-pointer"
                        :class="activeExtraFilterCount > 0 ? 'text-[#2563EB] bg-[#EFF6FF] border-[#BFDBFE]' : 'text-[#475569] bg-[#F8FAFC] border-[#E2E8F0] hover:bg-[#F1F5F9]'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span>Filter Tambahan</span>
                        <span x-show="activeExtraFilterCount > 0"
                              x-cloak
                              style="display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background-color: #2563EB; color: #FFFFFF; font-size: 10px; font-weight: 700;"
                              x-text="activeExtraFilterCount"></span>
                    </button>

                    {{-- Filter Tambahan Popover --}}
                    <div x-show="showFilterModal"
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         style="position: absolute; right: 0; top: calc(100% + 8px); width: 300px; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14); padding: 16px; z-index: 40;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <span style="font-size: 13px; font-weight: 700; color: #0F172A;">Filter Tambahan</span>
                            <button type="button"
                                    @click="resetExtraFilters()"
                                    style="font-size: 11.5px; font-weight: 600; color: #2563EB; background: transparent; border: none; cursor: pointer; padding: 0;">
                                Reset
                            </button>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 11px;">
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Kategori</label>
                                <select x-model="filterCategory" @change="currentPage = 1"
                                        style="width: 100%; height: 34px; padding: 0 10px; font-size: 12px; font-weight: 600; border: 1px solid #CBD5E1; border-radius: 8px; background-color: #F8FAFC; color: #0F172A; outline: none;">
                                    <option value="all">Semua Kategori</option>
                                    <option value="Dry">Dry</option>
                                    <option value="Frozen">Frozen</option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Bulan</label>
                                <select x-model="filterMonth" @change="currentPage = 1"
                                        style="width: 100%; height: 34px; padding: 0 10px; font-size: 12px; font-weight: 600; border: 1px solid #CBD5E1; border-radius: 8px; background-color: #F8FAFC; color: #0F172A; outline: none;">
                                    <option value="all">Semua Bulan</option>
                                    <template x-for="m in availableItemMonths" :key="m.key">
                                        <option :value="m.key" x-text="m.label"></option>
                                    </template>
                                </select>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div>
                                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Dari Tanggal</label>
                                    <input type="date" x-model="filterDateFrom" @change="currentPage = 1"
                                           style="width: 100%; height: 34px; padding: 0 8px; font-size: 11.5px; border: 1px solid #CBD5E1; border-radius: 8px; background-color: #F8FAFC; color: #0F172A; outline: none; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Sampai</label>
                                    <input type="date" x-model="filterDateTo" @change="currentPage = 1"
                                           style="width: 100%; height: 34px; padding: 0 8px; font-size: 11.5px; border: 1px solid #CBD5E1; border-radius: 8px; background-color: #F8FAFC; color: #0F172A; outline: none; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 14px; display: flex; justify-content: flex-end;">
                            <button type="button"
                                    @click="showFilterModal = false"
                                    style="height: 32px; padding: 0 14px; border-radius: 8px; background-color: #2563EB; color: #FFFFFF; font-size: 12px; font-weight: 600; border: none; cursor: pointer;">
                                Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Analytics Table --}}
            <div class="overflow-x-auto rounded-xl border border-[#E2E8F0] bg-white">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-[#F1F5F9]/80 border-b border-[#E2E8F0]">
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-[#475569] tracking-wider uppercase whitespace-nowrap">DATE</th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold text-[#475569] tracking-wider uppercase whitespace-nowrap">CATEGORY</th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#475569] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">SHIFT 1<br><span class="font-normal text-[10px]">MATCH</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Shift 1 Match</strong><br>
                                        Task Shift 1 yang sudah dikerjakan &amp; cocok.
                                        <span class="dash-th-tip-src">Sumber: Task dari Report Shift 1 yang ketemu pasangannya (MATCH) di file Analisa Shift 1.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#2563EB] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">SHIFT 2<br><span class="font-normal text-[10px]">MATCH</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Shift 2 Match</strong><br>
                                        Task Shift 2 yang sudah dikerjakan &amp; cocok.
                                        <span class="dash-th-tip-src">Sumber: Task dari Report Shift 2 yang ketemu pasangannya (MATCH) di file Analisa Shift 2.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#475569] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">SHIFT 1<br><span class="font-normal text-[10px]">TOTAL</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Shift 1 Total</strong><br>
                                        Total target task yang harus dikerjakan di Shift 1.
                                        <span class="dash-th-tip-src">Sumber: Jumlah task unik di Report Shift 1 (di luar Put Away, Transfer, &amp; Melebihi POC).</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#475569] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">SHIFT 2<br><span class="font-normal text-[10px]">TOTAL</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Shift 2 Total</strong><br>
                                        Total target task yang harus dikerjakan di Shift 2.
                                        <span class="dash-th-tip-src">Sumber: Jumlah task unik di Report Shift 2 (di luar Put Away, Transfer, &amp; Melebihi POC).</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold text-[#475569] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: center;">
                                    <span class="dash-th-label">SHIFT 1<br><span class="font-normal text-[10px]">COMPL.</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Shift 1 Completion</strong><br>
                                        Persentase kerja yang selesai di Shift 1.
                                        <span class="dash-th-tip-src">Hitungannya: (Shift 1 Match ÷ Shift 1 Total) × 100%.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold text-[#475569] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: center;">
                                    <span class="dash-th-label">SHIFT 2<br><span class="font-normal text-[10px]">COMPL.</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Shift 2 Completion</strong><br>
                                        Persentase kerja yang selesai di Shift 2.
                                        <span class="dash-th-tip-src">Hitungannya: (Shift 2 Match ÷ Shift 2 Total) × 100%.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#0F172A] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">TOTAL<br><span class="font-normal text-[10px]">TASKS</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Total Tasks Harian</strong><br>
                                        Total keseluruhan task unik dalam sehari (gabungan Shift 1 &amp; 2).
                                        <span class="dash-th-tip-src">Sumber: Gabungan task unik Report Shift 1 &amp; 2 (task yang sama tidak dihitung dobel).</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#2563EB] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">MATCH<br><span class="font-normal text-[10px]">COUNT</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Match Count Harian</strong><br>
                                        Jumlah task unik yang sudah selesai dikerjakan hari itu.
                                        <span class="dash-th-tip-src">Sumber: Task unik dari Total Tasks yang sudah berstatus MATCH di Shift 1 atau Shift 2.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#64748B] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">PENDING<br><span class="font-normal text-[10px]">S1</span></span>
                                    <span class="dash-th-tip">
                                        <strong>Pending Shift 1</strong><br>
                                        Sisa task Shift 1 yang belum dikerjakan atau belum ketemu di Analisa.
                                        <span class="dash-th-tip-src">Hitungannya: Shift 1 Total dikurangi Shift 1 Match.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#64748B] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">PENDING<br><span class="font-normal text-[10px]">S2</span></span>
                                    <span class="dash-th-tip dash-th-tip-right">
                                        <strong>Pending Shift 2</strong><br>
                                        Sisa task Shift 2 yang belum dikerjakan atau belum ketemu di Analisa.
                                        <span class="dash-th-tip-src">Hitungannya: Shift 2 Total dikurangi Shift 2 Match.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-[#2563EB] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: flex-end;">
                                    <span class="dash-th-label">MATCH<br><span class="font-normal text-[10px]">%</span></span>
                                    <span class="dash-th-tip dash-th-tip-right">
                                        <strong>Match Rate Harian</strong><br>
                                        Persentase keberhasilan penyelesaian task harian secara keseluruhan.
                                        <span class="dash-th-tip-src">Hitungannya: (Match Count ÷ Total Tasks) × 100%.</span>
                                    </span>
                                </span>
                            </th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold text-[#DC2626] tracking-wider uppercase whitespace-nowrap">
                                <span class="dash-th-wrap" style="align-items: center;">
                                    <span class="dash-th-label">UNMATCH</span>
                                    <span class="dash-th-tip dash-th-tip-right">
                                        <strong>Unmatch (Sisa Pending)</strong><br>
                                        Sisa task harian yang belum ketemu pasangannya di Analisa Shift 1 maupun Shift 2.
                                        <span class="dash-th-tip-src">Hitungannya: Total Tasks dikurangi Match Count.</span>
                                    </span>
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F1F5F9]">
                        {{-- Empty State --}}
                        <template x-if="paginatedItems.length === 0">
                            <tr>
                                <td colspan="14" class="px-6 py-12 text-center text-[#94A3B8]">
                                    <svg class="w-10 h-10 mx-auto text-[#CBD5E1] mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="text-[13px] font-medium text-[#64748B]">Belum ada data riwayat analisis</div>
                                    <div class="text-[11px] text-[#94A3B8] mt-0.5">Jalankan matching pada menu Matching Report untuk melihat kalkulasi performa.</div>
                                </td>
                            </tr>
                        </template>

                        {{-- Item Rows --}}
                        <template x-for="(row, idx) in paginatedItems" :key="row.id || idx">
                            <tr class="hover:bg-[#F8FAFC]/80 transition-colors">
                                {{-- Date with Calendar Icon --}}
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-[#F8FAFC] border border-[#E2E8F0] flex items-center justify-center flex-shrink-0 text-[#2563EB]">
                                            <svg class="w-4 h-4 text-[#3B82F6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="leading-tight">
                                            <div class="text-[13px] font-bold text-[#0F172A]" x-text="row.day"></div>
                                            <div class="text-[11px] text-[#64748B]" x-text="row.month_year"></div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Category Badge --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold"
                                        :class="row.category.toLowerCase() === 'dry'
                                            ? 'bg-[#EFF6FF] text-[#2563EB] border border-[#BFDBFE]'
                                            : 'bg-[#EEF2FF] text-[#4F46E5] border border-[#C7D2FE]'"
                                        x-text="row.category"
                                    ></span>
                                </td>

                                {{-- Shift 1 Match --}}
                                <td class="px-4 py-3.5 text-right font-medium text-[12px] text-[#0F172A] whitespace-nowrap" x-text="row.shift_1_match"></td>

                                {{-- Shift 2 Match --}}
                                <td class="px-4 py-3.5 text-right font-semibold text-[12px] text-[#2563EB] whitespace-nowrap" x-text="row.shift_2_match"></td>

                                {{-- Shift 1 Total --}}
                                <td class="px-4 py-3.5 text-right font-medium text-[12px] text-[#64748B] whitespace-nowrap" x-text="row.shift_1_total"></td>

                                {{-- Shift 2 Total --}}
                                <td class="px-4 py-3.5 text-right font-medium text-[12px] text-[#64748B] whitespace-nowrap" x-text="row.shift_2_total"></td>

                                {{-- Shift 1 Completion --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-[#F1F5F9] text-[#334155]" x-text="row.shift_1_compl"></span>
                                </td>

                                {{-- Shift 2 Completion --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-[#EEF2FF] text-[#4338CA]" x-text="row.shift_2_compl"></span>
                                </td>

                                {{-- Total Tasks --}}
                                <td class="px-4 py-3.5 text-right font-bold text-[13px] text-[#0F172A] whitespace-nowrap" x-text="row.total_tasks"></td>

                                {{-- Match Count --}}
                                <td class="px-4 py-3.5 text-right font-bold text-[13px] text-[#2563EB] whitespace-nowrap" x-text="row.match_count"></td>

                                {{-- Pending S1 --}}
                                <td class="px-4 py-3.5 text-right font-medium text-[12px] text-[#64748B] whitespace-nowrap" x-text="row.pending_s1"></td>

                                {{-- Pending S2 --}}
                                <td class="px-4 py-3.5 text-right font-medium text-[12px] text-[#64748B] whitespace-nowrap" x-text="row.pending_s2"></td>

                                {{-- Match % --}}
                                <td class="px-4 py-3.5 text-right font-bold text-[13px] text-[#2563EB] whitespace-nowrap" x-text="row.match_pct"></td>

                                {{-- Unmatch --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-[#FEF2F2] text-[#DC2626] border border-[#FECACA]" x-text="row.unmatch"></span>
                                </td>
                            </tr>
                        </template>

                        {{-- Footer Cumulative Row (Hari Ini: Dry + Frozen) --}}
                        <tr class="bg-[#F8FAFC] border-t-2 border-[#E2E8F0] font-bold">
                            <td colspan="2" class="px-4 py-3.5 text-[12px] font-bold text-[#0F172A] whitespace-nowrap">
                                <span x-text="'Total Kumulatif (' + cumulative.date_label + ')'"></span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-[13px] font-bold text-[#0F172A] whitespace-nowrap" x-text="cumulative.shift_1_match"></td>
                            <td class="px-4 py-3.5 text-right text-[13px] font-bold text-[#2563EB] whitespace-nowrap" x-text="cumulative.shift_2_match"></td>
                            <td class="px-4 py-3.5 text-right text-[13px] font-bold text-[#475569] whitespace-nowrap" x-text="cumulative.shift_1_total"></td>
                            <td class="px-4 py-3.5 text-right text-[13px] font-bold text-[#475569] whitespace-nowrap" x-text="cumulative.shift_2_total"></td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold bg-[#E2E8F0] text-[#0F172A]" x-text="cumulative.shift_1_compl"></span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold bg-[#DBEAFE] text-[#1D4ED8]" x-text="cumulative.shift_2_compl"></span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-[13px] font-bold text-[#0F172A] whitespace-nowrap" x-text="cumulative.total_tasks"></td>
                            <td class="px-4 py-3.5 text-right text-[13px] font-bold text-[#2563EB] whitespace-nowrap" x-text="cumulative.match_count"></td>
                            <td class="px-4 py-3.5 text-right text-[12px] font-bold text-[#64748B] whitespace-nowrap" x-text="cumulative.pending_s1"></td>
                            <td class="px-4 py-3.5 text-right text-[12px] font-bold text-[#64748B] whitespace-nowrap" x-text="cumulative.pending_s2"></td>
                            <td class="px-4 py-3.5 text-right text-[13px] font-bold text-[#2563EB] whitespace-nowrap" x-text="cumulative.match_pct"></td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-[#FEE2E2] text-[#DC2626] border border-[#FCA5A5]" x-text="cumulative.unmatch"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Bottom Pagination & Info --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-5 pt-4 border-t border-[#F1F5F9]">
                <div class="text-[12px] text-[#64748B]">
                    Menampilkan <span class="font-semibold text-[#0F172A]" x-text="filteredItems.length"></span> data kategori Dry & Frozen (Shift 1 & 2 Kumulatif)
                </div>

                {{-- Pagination Buttons --}}
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click="currentPage > 1 && currentPage--"
                        :disabled="currentPage <= 1"
                        class="px-3.5 py-1.5 rounded-lg text-[12px] font-medium text-[#475569] bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] disabled:opacity-40 disabled:cursor-not-allowed transition-smooth"
                    >
                        Sebelumnya
                    </button>

                    <template x-for="p in totalPages" :key="p">
                        <button
                            type="button"
                            @click="currentPage = p"
                            class="w-8 h-8 rounded-lg text-[12px] font-semibold transition-smooth flex items-center justify-center"
                            :class="currentPage === p ? 'bg-[#0F172A] text-white shadow-sm' : 'bg-white border border-[#E2E8F0] text-[#475569] hover:bg-[#F8FAFC]'"
                            x-text="p"
                        ></button>
                    </template>

                    <button
                        type="button"
                        @click="currentPage < totalPages && currentPage++"
                        :disabled="currentPage >= totalPages"
                        class="px-3.5 py-1.5 rounded-lg text-[12px] font-medium text-[#475569] bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] disabled:opacity-40 disabled:cursor-not-allowed transition-smooth"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        function dashboardAnalytics({ items, cumulative, shift1, shift2, allShift, cardDailyData, trendData }) {
            let trendChartInstance = null;

            return {
                items: items || [],
                cumulative: cumulative || {},
                shift1: shift1 || {},
                shift2: shift2 || {},
                allShift: allShift || {},
                cardDailyData: cardDailyData || [],
                cardPeriod: 'latest', // 'latest' | 'week' | 'month' | 'all' | 'custom'
                cardMonth: '',
                cardDateFrom: '',
                cardDateTo: '',
                trendData: trendData || {},
                trendShift: 'all', // 'all', 'shift1', 'shift2'
                trendCategory: 'all', // 'all', 'dry', 'frozen'
                trendRange: '7d', // '7d', '14d', '30d', 'all', 'month:YYYY-MM'
                trendMetricMode: 'count', // 'count' | 'percent'
                currentAvgRate: trendData?.avg_rate || '0.0%',
                shiftViewMode: 'split', // 'split' | 'all'
                searchQuery: '',
                activeTab: 'all',
                showFilterModal: false,
                filterCategory: 'all',
                filterMonth: 'all',
                filterDateFrom: '',
                filterDateTo: '',
                currentPage: 1,
                perPage: 10,

                init() {
                    if (this.availableCardMonths.length > 0) {
                        this.cardMonth = this.availableCardMonths[this.availableCardMonths.length - 1].key;
                    }
                    if (this.cardDailyData.length > 0) {
                        const last = this.cardDailyData[this.cardDailyData.length - 1].date;
                        const first = this.cardDailyData[Math.max(0, this.cardDailyData.length - 7)].date;
                        this.cardDateFrom = first;
                        this.cardDateTo = last;
                    }
                    this.$nextTick(() => {
                        this.initChart();
                    });
                },

                get availableCardMonths() {
                    const monthNames = {
                        '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
                        '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
                        '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember'
                    };
                    const seen = new Map();
                    (this.cardDailyData || []).forEach(d => {
                        if (d.month && !seen.has(d.month)) {
                            const [y, m] = d.month.split('-');
                            seen.set(d.month, (monthNames[m] || m) + ' ' + y);
                        }
                    });
                    return Array.from(seen.entries()).map(([key, label]) => ({ key, label }));
                },

                selectCardMonthMode() {
                    if (!this.cardMonth && this.availableCardMonths.length > 0) {
                        this.cardMonth = this.availableCardMonths[this.availableCardMonths.length - 1].key;
                    }
                    this.cardPeriod = 'month';
                },

                selectCardCustomMode() {
                    if ((!this.cardDateFrom || !this.cardDateTo) && this.cardDailyData.length > 0) {
                        this.cardDateTo = this.cardDailyData[this.cardDailyData.length - 1].date;
                        this.cardDateFrom = this.cardDailyData[Math.max(0, this.cardDailyData.length - 7)].date;
                    }
                    this.cardPeriod = 'custom';
                },

                get filteredCardDays() {
                    const days = this.cardDailyData || [];
                    if (!days.length) return [];

                    if (this.cardPeriod === 'latest') {
                        return [days[days.length - 1]];
                    }
                    if (this.cardPeriod === 'week') {
                        return days.slice(-7);
                    }
                    if (this.cardPeriod === 'month') {
                        return days.filter(d => d.month === this.cardMonth);
                    }
                    if (this.cardPeriod === 'custom') {
                        return days.filter(d => {
                            if (this.cardDateFrom && d.date < this.cardDateFrom) return false;
                            if (this.cardDateTo && d.date > this.cardDateTo) return false;
                            return true;
                        });
                    }
                    return days;
                },

                get cardPeriodLabel() {
                    const days = this.filteredCardDays;
                    if (!days.length) return 'Tidak ada data di rentang ini';
                    if (this.cardPeriod === 'latest') return 'Data Terakhir: ' + days[0].date;
                    if (this.cardPeriod === 'week') return `Akumulasi ${days.length} Hari Terakhir`;
                    if (this.cardPeriod === 'month') {
                        const found = this.availableCardMonths.find(m => m.key === this.cardMonth);
                        return `Akumulasi ${found ? found.label : this.cardMonth} (${days.length} hari)`;
                    }
                    if (this.cardPeriod === 'all') return `Akumulasi Semua (${days.length} hari)`;
                    if (days.length === 1) return `${days[0].date} (1 hari)`;
                    return `${days[0].date} s/d ${days[days.length - 1].date} (${days.length} hari)`;
                },

                get activeShiftCards() {
                    const days = this.filteredCardDays;
                    if (this.cardPeriod === 'latest' && days.length <= 1 && (!days.length || days[0]?.date === this.allShift?.status)) {
                        return { shift1: this.shift1, shift2: this.shift2, allShift: this.allShift };
                    }

                    const sumShift = (key, isAll = false) => {
                        let total = 0, match = 0, pending = 0, putAway = 0, transfer = 0, poc = 0;
                        days.forEach(d => {
                            const s = d[key] || {};
                            total += (s.total || 0);
                            match += (s.match || 0);
                            pending += (s.pending || 0);
                            putAway += (s.put_away || 0);
                            transfer += (s.transfer || 0);
                            poc += (s.melebihi_poc || 0);
                        });
                        const pct = total > 0 ? Number(((match / total) * 100).toFixed(1)) : 0;
                        const statusLabel = isAll
                            ? (days.length === 1 ? days[0].date : `${days.length} Hari`)
                            : (total > 0 ? 'Selesai' : 'Belum Ada Data');
                        return {
                            total_tasks: total,
                            match_count: match,
                            pending_count: pending,
                            completion: pct.toFixed(1) + '%',
                            completion_num: pct,
                            put_away: putAway,
                            transfer: transfer,
                            melebihi_poc: poc,
                            status: statusLabel,
                        };
                    };

                    return {
                        shift1: sumShift('s1', false),
                        shift2: sumShift('s2', false),
                        allShift: sumShift('all', true),
                    };
                },

                initChart() {
                    const canvas = document.getElementById('trendMatchingChart');
                    if (!canvas || typeof Chart === 'undefined') return;

                    const self = this;
                    const active = this.getTrendSeries();
                    const dates = active.dates || [];
                    const matched = active.matched || [];
                    const unmatch = active.unmatch || [];
                    const total = active.total || [];
                    this.currentAvgRate = active.avg_rate || '0.0%';

                    // Custom Plugin for floating badges on top of bars
                    const customBarBadges = {
                        id: 'customBarBadges',
                        afterDatasetsDraw(chart) {
                            const { ctx, data } = chart;
                            const metaLine = chart.getDatasetMeta(2);
                            if (!metaLine || !metaLine.data) return;

                            metaLine.data.forEach((point, index) => {
                                const val = data.datasets[2].data[index];
                                if (val === undefined || val === null) return;

                                const isLast = index === metaLine.data.length - 1;
                                const rawSeries = self.getTrendSeries();
                                const mRaw = rawSeries.matchedRaw[index] ?? 0;
                                const tRaw = rawSeries.totalRaw[index] ?? 0;
                                const text = self.trendMetricMode === 'percent'
                                    ? (tRaw > 0 ? ((mRaw / tRaw) * 100).toFixed(1) + '%' : '0.0%')
                                    : String(val);

                                ctx.save();
                                ctx.font = isLast ? 'bold 12px Inter, sans-serif' : '600 12px Inter, sans-serif';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';

                                const paddingX = 12;
                                const pillHeight = 22;
                                const textWidth = ctx.measureText(text).width;
                                const pillWidth = Math.max(textWidth + paddingX * 2, 46);

                                const pillX = point.x - pillWidth / 2;
                                const pillY = point.y - 25;
                                const radius = 6;

                                ctx.beginPath();
                                if (typeof ctx.roundRect === 'function') {
                                    ctx.roundRect(pillX, pillY, pillWidth, pillHeight, radius);
                                } else {
                                    ctx.rect(pillX, pillY, pillWidth, pillHeight);
                                }

                                if (isLast) {
                                    ctx.fillStyle = '#1E3A8A';
                                    ctx.fill();
                                    ctx.fillStyle = '#FFFFFF';
                                } else {
                                    ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
                                    ctx.fill();
                                    ctx.lineWidth = 1;
                                    ctx.strokeStyle = '#CBD5E1';
                                    ctx.stroke();
                                    ctx.fillStyle = '#0F172A';
                                }

                                ctx.fillText(text, point.x, pillY + pillHeight / 2);
                                ctx.restore();
                            });
                        }
                    };

                    if (trendChartInstance) {
                        trendChartInstance.destroy();
                    }

                    trendChartInstance = new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: dates,
                            datasets: [
                                {
                                    type: 'bar',
                                    label: 'Matched',
                                    data: matched,
                                    backgroundColor: '#3E52B7',
                                    stack: 'volume',
                                    barPercentage: 0.38,
                                    categoryPercentage: 0.8,
                                    order: 2
                                },
                                {
                                    type: 'bar',
                                    label: 'Unmatch / Selisih POC',
                                    data: unmatch,
                                    backgroundColor: (context) => (context.dataIndex === dates.length - 1 ? '#991B1B' : '#FECDD3'),
                                    borderColor: (context) => (context.dataIndex === dates.length - 1 ? '#7F1D1D' : '#FCA5A5'),
                                    borderWidth: { top: 1, left: 1, right: 1, bottom: 0 },
                                    borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                                    borderSkipped: 'bottom',
                                    stack: 'volume',
                                    barPercentage: 0.38,
                                    categoryPercentage: 0.8,
                                    order: 1
                                },
                                {
                                    type: 'line',
                                    label: 'Target / Total Tasks (Trend Line)',
                                    data: total,
                                    borderColor: '#0F172A',
                                    borderWidth: 2.5,
                                    pointBackgroundColor: '#0F172A',
                                    pointBorderColor: '#FFFFFF',
                                    pointBorderWidth: 2,
                                    pointRadius: 4.5,
                                    pointHoverRadius: 6,
                                    tension: 0.1,
                                    order: 0
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#e2e8f0',
                                    padding: 10,
                                    cornerRadius: 8,
                                    boxPadding: 4,
                                    callbacks: {
                                        label: function(ctx) {
                                            const i = ctx.dataIndex;
                                            const s = self.getTrendSeries();
                                            const mRaw = s.matchedRaw[i] ?? 0;
                                            const uRaw = s.unmatchRaw[i] ?? 0;
                                            const tRaw = s.totalRaw[i] ?? 0;
                                            const pct = tRaw > 0 ? ((ctx.datasetIndex === 0 ? mRaw : (ctx.datasetIndex === 1 ? uRaw : tRaw)) / tRaw * 100).toFixed(1) : '0.0';

                                            if (self.trendMetricMode === 'percent') {
                                                if (ctx.datasetIndex === 0) return ` Matched: ${ctx.parsed.y}% (${mRaw} / ${tRaw})`;
                                                if (ctx.datasetIndex === 1) return ` Unmatch / Selisih POC: ${ctx.parsed.y}% (${uRaw} / ${tRaw})`;
                                                return ` Target: 100% (${tRaw} tasks)`;
                                            }
                                            if (ctx.datasetIndex === 0) return ` Matched: ${mRaw} (${pct}%)`;
                                            if (ctx.datasetIndex === 1) return ` Unmatch / Selisih POC: ${uRaw} (${pct}%)`;
                                            return ` Target / Total Tasks: ${tRaw}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: { display: false, drawBorder: false },
                                    ticks: {
                                        color: (context) => (context.index === dates.length - 1 ? '#1E40AF' : '#64748B'),
                                        font: (context) => ({
                                            family: 'Inter, system-ui, sans-serif',
                                            size: 12,
                                            weight: context.index === dates.length - 1 ? '700' : '500'
                                        })
                                    }
                                },
                                y: {
                                    stacked: false,
                                    beginAtZero: true,
                                    grace: '15%',
                                    ticks: {
                                        color: '#64748B',
                                        font: { family: 'Inter, system-ui, sans-serif', size: 11, weight: '500' },
                                        callback: (val) => self.trendMetricMode === 'percent' ? (val <= 100 ? val + '%' : '') : val
                                    },
                                    grid: {
                                        color: '#F1F5F9',
                                        borderDash: [4, 4],
                                        drawBorder: false
                                    }
                                }
                            }
                        },
                        plugins: [customBarBadges]
                    });
                },

                get trendRangeLabel() {
                    if (this.trendRange === '7d') return '7 Hari Terakhir';
                    if (this.trendRange === '14d') return '14 Hari Terakhir';
                    if (this.trendRange === '30d') return '30 Hari Terakhir';
                    if (this.trendRange === 'all') return 'Semua Data';
                    if (this.trendRange.startsWith('month:')) {
                        const key = this.trendRange.slice(6);
                        const found = (this.trendData?.months || []).find(m => m.key === key);
                        return found ? found.label : key;
                    }
                    return '7 Hari Terakhir';
                },

                getTrendSeries() {
                    const points = this.trendData?.points || [];
                    let dates = [];
                    let matchedRaw = [];
                    let unmatchRaw = [];
                    let totalRaw = [];

                    if (!points.length) {
                        const fallback = this.trendData?.[this.trendShift]?.[this.trendCategory] || {};
                        dates = this.trendData?.dates || [];
                        matchedRaw = fallback.matched || [];
                        unmatchRaw = fallback.unmatch || [];
                        totalRaw = fallback.total || [];
                    } else {
                        let filteredPoints = points;
                        if (this.trendRange === '7d') filteredPoints = points.slice(-7);
                        else if (this.trendRange === '14d') filteredPoints = points.slice(-14);
                        else if (this.trendRange === '30d') filteredPoints = points.slice(-30);
                        else if (this.trendRange.startsWith('month:')) {
                            const mKey = this.trendRange.slice(6);
                            filteredPoints = points.filter(p => p.month === mKey);
                        }

                        filteredPoints.forEach(p => {
                            const vals = p?.[this.trendShift]?.[this.trendCategory] || [0, 0, 0];
                            dates.push(p.label);
                            matchedRaw.push(vals[0] || 0);
                            unmatchRaw.push(vals[1] || 0);
                            totalRaw.push(vals[2] || 0);
                        });
                    }

                    let sumMatched = 0;
                    let sumTotal = 0;
                    matchedRaw.forEach(v => { sumMatched += (v || 0); });
                    totalRaw.forEach(v => { sumTotal += (v || 0); });
                    const avg_rate = sumTotal > 0 ? ((sumMatched / sumTotal) * 100).toFixed(1) + '%' : '0.0%';

                    if (this.trendMetricMode === 'percent') {
                        const matchedPct = matchedRaw.map((m, i) => {
                            const t = totalRaw[i] || 0;
                            return t > 0 ? Number(((m / t) * 100).toFixed(1)) : 0;
                        });
                        const unmatchPct = unmatchRaw.map((u, i) => {
                            const t = totalRaw[i] || 0;
                            return t > 0 ? Number(((u / t) * 100).toFixed(1)) : 0;
                        });
                        const targetPct = totalRaw.map(t => (t > 0 ? 100 : 0));
                        return { dates, matched: matchedPct, unmatch: unmatchPct, total: targetPct, matchedRaw, unmatchRaw, totalRaw, avg_rate };
                    }

                    return { dates, matched: matchedRaw, unmatch: unmatchRaw, total: totalRaw, matchedRaw, unmatchRaw, totalRaw, avg_rate };
                },

                setMetricMode(val) {
                    this.trendMetricMode = val;
                    this.updateChart();
                },

                setTrendRange(val) {
                    this.trendRange = val;
                    this.updateChart();
                },

                setShift(val) {
                    this.trendShift = val;
                    this.updateChart();
                },

                setCategory(val) {
                    this.trendCategory = val;
                    this.updateChart();
                },

                updateChart() {
                    const active = this.getTrendSeries();
                    this.currentAvgRate = active.avg_rate || '0.0%';

                    if (trendChartInstance) {
                        trendChartInstance.data.labels = [...(active.dates || [])];
                        trendChartInstance.data.datasets[0].data = [...(active.matched || [])];
                        trendChartInstance.data.datasets[1].data = [...(active.unmatch || [])];
                        trendChartInstance.data.datasets[2].data = [...(active.total || [])];
                        trendChartInstance.options.scales.y.max = this.trendMetricMode === 'percent' ? 115 : undefined;
                        trendChartInstance.update();
                    }
                },

                get availableItemMonths() {
                    const map = new Map();
                    this.items.forEach(item => {
                        if (item.raw_date && item.raw_date.length >= 7) {
                            const key = item.raw_date.slice(0, 7);
                            if (!map.has(key)) {
                                map.set(key, item.month_year || key);
                            }
                        }
                    });
                    return Array.from(map.entries()).map(([key, label]) => ({ key, label }));
                },

                get activeExtraFilterCount() {
                    let c = 0;
                    if (this.filterCategory !== 'all') c++;
                    if (this.filterMonth !== 'all') c++;
                    if (this.filterDateFrom) c++;
                    if (this.filterDateTo) c++;
                    return c;
                },

                resetExtraFilters() {
                    this.filterCategory = 'all';
                    this.filterMonth = 'all';
                    this.filterDateFrom = '';
                    this.filterDateTo = '';
                    this.currentPage = 1;
                },

                get filteredItems() {
                    let res = [...this.items];

                    // Filter search query
                    if (this.searchQuery.trim() !== '') {
                        const q = this.searchQuery.toLowerCase();
                        res = res.filter(item =>
                            item.category.toLowerCase().includes(q) ||
                            item.raw_date.toLowerCase().includes(q) ||
                            item.month_year.toLowerCase().includes(q) ||
                            item.day.includes(q)
                        );
                    }

                    // Extra filters
                    if (this.filterCategory !== 'all') {
                        res = res.filter(item => item.category === this.filterCategory);
                    }
                    if (this.filterMonth !== 'all') {
                        res = res.filter(item => (item.raw_date || '').startsWith(this.filterMonth));
                    }
                    if (this.filterDateFrom) {
                        res = res.filter(item => (item.raw_date || '') >= this.filterDateFrom);
                    }
                    if (this.filterDateTo) {
                        res = res.filter(item => (item.raw_date || '') <= this.filterDateTo);
                    }

                    // Filter tab
                    if (this.activeTab === 'matched') {
                        res = res.filter(item => item.match_count > 0);
                    } else if (this.activeTab === 'unmatch') {
                        res = res.filter(item => item.unmatch > 0);
                    } else if (this.activeTab === 'melebihi_poc') {
                        res = res.filter(item => item.surplus_poc_count > 0);
                    }

                    return res;
                },

                get totalPages() {
                    const pages = Math.ceil(this.filteredItems.length / this.perPage);
                    return pages > 0 ? pages : 1;
                },

                get paginatedItems() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.filteredItems.slice(start, start + this.perPage);
                },

                openDeleteModal(row) {
                    this.deleteTarget = row;
                },

                get filteredHistoryItems() {
                    let res = [...this.matchHistoryItems];

                    if (this.historyShiftFilter !== 'all') {
                        res = res.filter(r => r.report_shift === this.historyShiftFilter);
                    }

                    if (this.historySearch.trim() !== '') {
                        const q = this.historySearch.toLowerCase();
                        res = res.filter(r =>
                            String(r.report_date || '').toLowerCase().includes(q) ||
                            String(r.report_shift || '').toLowerCase().includes(q) ||
                            String(r.branch_code || '').toLowerCase().includes(q) ||
                            String(r.report_file_name || '').toLowerCase().includes(q) ||
                            String(r.analysis_file_name || '').toLowerCase().includes(q)
                        );
                    }

                    return res;
                },

                get historyTotalPages() {
                    const pages = Math.ceil(this.filteredHistoryItems.length / this.historyPerPage);
                    return pages > 0 ? pages : 1;
                },

                get paginatedHistoryItems() {
                    const start = (this.historyPage - 1) * this.historyPerPage;
                    return this.filteredHistoryItems.slice(start, start + this.historyPerPage);
                }
            };
        }
    </script>
</x-layout>
