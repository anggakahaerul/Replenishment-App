<x-layout title="Replenishment Matching">
    <style>
        .matching-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 12px !important;
            margin-bottom: 20px !important;
        }

        @media (max-width: 1024px) {
            .matching-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 640px) {
            .matching-summary-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .summary-stat-card {
            background-color: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            position: relative;
        }

        .summary-stat-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        }

        .replenish-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .replenish-table th {
            padding: 7px 10px !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em !important;
            color: #64748B !important;
            background-color: #F8FAFC !important;
            border-bottom: 1px solid #E2E8F0 !important;
            white-space: nowrap !important;
        }

        .replenish-table td {
            padding: 5px 10px !important;
            font-size: 12px !important;
            line-height: 1.25 !important;
            border-bottom: 1px solid #F1F5F9 !important;
            vertical-align: middle !important;
        }

        .replenish-table tbody tr:hover {
            background-color: #F8FAFC !important;
        }

        .dd-chevron {
            width: 13px;
            height: 13px;
            color: #64748B;
            transition: transform 0.15s ease;
            flex-shrink: 0;
        }

        .dd-chevron-open {
            transform: rotate(180deg);
        }

        .history-pct-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .history-pct-high {
            background-color: #F0FDF4;
            color: #16A34A;
            border: 1px solid #BBF7D0;
        }

        .history-pct-normal {
            background-color: #EFF6FF;
            color: #2563EB;
            border: 1px solid #BFDBFE;
        }
    </style>
    <div
        x-data="replenishmentApp()"
        @file-selected-report_file.window="handleFileSelect('report_file', $event.detail.file)"
        @file-selected-analysis_file.window="handleFileSelect('analysis_file', $event.detail.file)"
        class="modern-page"
    >
        @if(session('success'))
            <div style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #166534; font-size: 12.5px; font-weight: 600;">
                <svg style="width: 18px; height: 18px; color: #16A34A; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #991B1B; font-size: 12.5px; font-weight: 600;">
                <svg style="width: 18px; height: 18px; color: #DC2626; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        {{-- ===== PAGE HEADER ===== --}}
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-[22px] font-bold text-[#0F172A] leading-tight">Replenishment Matching</h1>
                <p class="text-[13px] text-[#64748B] mt-0.5">Upload dan rekonsiliasi data replenishment gudang</p>
            </div>

            {{-- Header Badges --}}
            @if(auth()->user()?->hasPermission('matching', 'create'))
            <div class="flex items-center gap-2">
                {{-- Modern Cabang Dropdown --}}
                @if(auth()->user()?->hasGlobalBranchAccess())
                    <div class="modern-dd-wrap" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button"
                                @click="open = !open"
                                class="modern-dd-btn"
                                :class="open ? 'modern-dd-btn-active' : ''">
                            <svg style="width: 14px; height: 14px; color: #64748B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span style="font-size: 11.5px; color: #64748B; font-weight: 500;">Cabang:</span>
                            <span style="font-size: 12px; font-weight: 700; color: #0F172A;"
                                  x-text="currentBranch ? (currentBranch.code && currentBranch.name && currentBranch.code.toLowerCase().replace(/[^a-z0-9]/g,'') !== currentBranch.name.toLowerCase().replace(/[^a-z0-9]/g,'') ? currentBranch.code + ' • ' + currentBranch.name : currentBranch.code) : 'Belum ada cabang'">
                            </span>
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
                            <template x-if="branches.length === 0">
                                <div style="padding: 10px 12px; font-size: 12px; color: #94A3B8; text-align: center;">
                                    Belum ada master cabang
                                </div>
                            </template>
                            <template x-for="b in branches" :key="b.id">
                                <button type="button"
                                        @click="selectedBranchId = b.id; open = false"
                                        class="modern-dd-item"
                                        :class="String(selectedBranchId) === String(b.id) ? 'modern-dd-item-selected' : ''">
                                    <div>
                                        <div style="font-weight: 700; line-height: 1.25;" x-text="b.code"></div>
                                        <div style="font-size: 11px; color: #64748B; font-weight: 500; margin-top: 1px;"
                                             x-show="b.name && b.code.toLowerCase().replace(/[^a-z0-9]/g,'') !== b.name.toLowerCase().replace(/[^a-z0-9]/g,'')"
                                             x-text="b.name"></div>
                                    </div>
                                    <svg x-show="String(selectedBranchId) === String(b.id)"
                                         style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                @else
                    <div class="modern-dd-btn" style="cursor: default;">
                        <svg style="width: 14px; height: 14px; color: #64748B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span style="font-size: 11.5px; color: #64748B; font-weight: 500;">Cabang:</span>
                        <span style="font-size: 12px; font-weight: 700; color: #2563EB;">
                            {{ auth()->user()?->branch?->code ?? 'Belum ada cabang' }}
                        </span>
                    </div>
                @endif
            </div>
            @endif
        </div>

        @if(!auth()->user()?->hasPermission('matching', 'create'))
            {{-- ===== READ-ONLY BANNER ===== --}}
            <div style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; padding: 11px 16px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px;">
                <svg style="width: 16px; height: 16px; color: #2563EB; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span style="font-size: 12.5px; color: #1E40AF; font-weight: 500;">
                    Mode <strong>Read-Only ({{ auth()->user()?->role_label }})</strong> — Anda dapat meninjau riwayat matching, report, dan analisa.
                </span>
            </div>
        @else
            {{-- ===== UPLOAD SECTION ===== --}}
            <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-5 mb-5">
                {{-- Two Upload Cards --}}
                <div class="flex gap-5 mb-4">
                    <x-upload-card
                        number="1"
                        label="Replenishment Report (Awal Shift / Bisa Duluan)"
                        file-key="report_file"
                        accept=".xlsx,.xls"
                    />

                    <x-upload-card
                        number="2"
                        label="Analisa Replenishment (Bisa Menyusul / Bertahap)"
                        file-key="analysis_file"
                        accept=".xlsx,.xls"
                    />
                </div>

                {{-- Dynamic Upload Mode Indicator --}}
                <div class="flex items-center justify-center mb-3">
                    <div x-show="!files.report_file && !files.analysis_file" class="inline-flex items-center gap-2 text-[11px] text-[#64748B]">
                        <svg class="w-3.5 h-3.5 text-[#94A3B8] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Pilih <strong>Report saja</strong> di awal shift, <strong>Analisa saja</strong> secara bertahap, atau <strong>keduanya sekaligus</strong> (.xlsx / .xls).</span>
                    </div>

                    <div x-show="files.report_file && !files.analysis_file" x-cloak class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-medium bg-[#EFF6FF] text-[#1D4ED8] border border-[#BFDBFE]">
                        <span class="w-2 h-2 rounded-full bg-[#2563EB]"></span>
                        <span><strong>Mode: Simpan Report Shift</strong> — task awal dicatat PENDING sampai file Analisa diunggah.</span>
                    </div>

                    <div x-show="!files.report_file && files.analysis_file" x-cloak class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-medium bg-[#F0FDF4] text-[#15803D] border border-[#BBF7D0]">
                        <span class="w-2 h-2 rounded-full bg-[#16A34A]"></span>
                        <span><strong>Mode: Analisa Susulan</strong> — digabung dengan Analisa sebelumnya untuk mengejar sisa PENDING.</span>
                    </div>

                    <div x-show="files.report_file && files.analysis_file" x-cloak class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-medium bg-[#F8FAFC] text-[#0F172A] border border-[#CBD5E1]">
                        <span class="w-2 h-2 rounded-full bg-[#0F172A]"></span>
                        <span><strong>Mode: Matching Lengkap</strong> — Report dan Analisa diproses bersamaan.</span>
                    </div>
                </div>

                {{-- Optional Target Shift (when uploading Analysis only) --}}
                <div x-show="files.analysis_file && !files.report_file" x-cloak class="flex flex-wrap items-center justify-center gap-2 mb-4">
                    <label class="text-[11px] font-medium text-[#475569]">Target Shift Analisa:</label>
                    <select x-model="targetShift" class="text-[11.5px] border border-[#E2E8F0] rounded-lg px-2.5 py-1.5 bg-white text-[#0F172A] outline-none focus:border-[#2563EB] cursor-pointer">
                        <option value="">Otomatis dari jam transaksi</option>
                        <option value="SHIFT 1">SHIFT 1</option>
                        <option value="SHIFT 2">SHIFT 2</option>
                    </select>
                    <span class="text-[10.5px] text-[#94A3B8]">(Biarkan otomatis kecuali ingin memaksa ke shift tertentu)</span>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-center gap-3">
                    {{-- Primary Button --}}
                    <button
                        @click="openCategoryConfirm()"
                        :disabled="!canStartMatching || processing"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-lg text-[13px] font-semibold text-white transition-smooth disabled:cursor-not-allowed"
                        :class="canStartMatching && !processing
                            ? 'bg-[#0F172A] hover:bg-[#1E293B] shadow-md'
                            : 'bg-[#94A3B8] cursor-not-allowed'"
                    >
                        <template x-if="!processing">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </template>
                        <template x-if="processing">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="processing ? 'Memproses...' : actionButtonLabel"></span>
                    </button>

                    {{-- Reset Button --}}
                    <button
                        @click="resetAll()"
                        :disabled="processing"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-[13px] font-medium text-[#475569] bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] transition-smooth disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset File
                    </button>
                </div>
            </div>
        @endif

        {{-- ===== PROGRESS SECTION ===== --}}
        <div
            x-show="processing || completed"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-5 mb-5"
        >
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    {{-- Status Icon --}}
                    <template x-if="completed">
                        <div class="w-6 h-6 rounded-full bg-[#F0FDF4] flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="processing && !completed">
                        <div class="w-6 h-6 rounded-full bg-[#DBEAFE] flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#2563EB] animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                    </template>

                    {{-- Status Text --}}
                    <div>
                        <span class="text-[13px] font-semibold text-[#0F172A]">
                            Status Pemrosesan:
                            <span x-text="completed ? 'Selesai 100%' : 'Memproses ' + progressPercent + '%'"></span>
                        </span>
                        <span class="text-[11px] text-[#64748B] ml-2">•</span>
                        <span class="text-[11px] text-[#64748B] ml-2" x-text="completed ? ((matchingData?.summary?.total_rows ?? matchingData?.summary?.total ?? 0).toLocaleString('id-ID') + ' baris berhasil dicocokkan dengan data slot gudang') : 'Sedang mencocokkan data...'"></span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <span
                        x-show="completed && matchingData?.report_shift"
                        x-cloak
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#EFF6FF] text-[#2563EB] border border-[#BFDBFE]"
                        x-text="(matchingData?.report_shift ?? '') + (matchingData?.report_date ? ' • ' + matchingData.report_date : '')"
                    ></span>
                    <span class="text-[11px] text-[#64748B]" x-show="completed">
                        Durasi: <span class="font-semibold" x-text="matchingData?.summary?.duration_seconds"></span> detik
                    </span>
                    <span
                        x-show="completed"
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-[#F0FDF4] text-[#16A34A] border border-[#BBF7D0]"
                    >
                        Selesai
                    </span>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="w-full bg-[#E2E8F0] rounded-full h-2 overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-500 ease-out"
                    :class="completed ? 'bg-[#2563EB]' : 'bg-[#2563EB] animate-pulse-soft'"
                    :style="'width: ' + progressPercent + '%'"
                ></div>
            </div>
        </div>

        {{-- ===== SUMMARY CARDS (RINGKASAN MATCHING - 4 KOLOM PERSEGI PANJANG) ===== --}}
        <div
            x-show="completed && results.length > 0"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="matching-summary-grid"
        >
            {{-- Card 1: Total Diproses --}}
            <div
                @click="filterStatus = 'all'"
                class="summary-stat-card"
                :style="filterStatus === 'all'
                    ? 'border-color: #0F172A; box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.12); background-color: #F8FAFC;'
                    : 'border-color: #E2E8F0; background-color: #FFFFFF;'"
            >
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;">Total Baris</span>
                    <div style="width: 24px; height: 24px; border-radius: 6px; background-color: #F1F5F9; display: flex; align-items: center; justify-content: center; color: #475569;">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; color: #0F172A; line-height: 1; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;" x-text="matchSummary.total.toLocaleString('id-ID')"></span>
                    <span style="font-size: 11px; font-weight: 600; color: #64748B;">Baris</span>
                </div>
            </div>

            {{-- Card 2: Match --}}
            <div
                @click="filterStatus = (filterStatus === 'MATCH' ? 'all' : 'MATCH')"
                class="summary-stat-card"
                :style="filterStatus === 'MATCH'
                    ? 'border-color: #16A34A; box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2); background-color: #F0FDF4;'
                    : 'border-color: #E2E8F0; background-color: #FFFFFF;'"
            >
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; font-weight: 700; color: #16A34A; text-transform: uppercase; letter-spacing: 0.05em;">Match</span>
                    <div style="width: 24px; height: 24px; border-radius: 6px; background-color: #DCFCE7; display: flex; align-items: center; justify-content: center; color: #16A34A;">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; color: #16A34A; line-height: 1; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;" x-text="matchSummary.match.toLocaleString('id-ID')"></span>
                    <span style="font-size: 11px; font-weight: 700; color: #16A34A;" x-text="'(' + matchSummary.matchRate + '%)'"></span>
                </div>
            </div>

            {{-- Card 3: Pending --}}
            <div
                @click="filterStatus = (filterStatus === 'PENDING' ? 'all' : 'PENDING')"
                class="summary-stat-card"
                :style="filterStatus === 'PENDING'
                    ? 'border-color: #DC2626; box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.2); background-color: #FEF2F2;'
                    : 'border-color: #E2E8F0; background-color: #FFFFFF;'"
            >
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 0.05em;">Pending</span>
                    <div style="width: 24px; height: 24px; border-radius: 6px; background-color: #FEE2E2; display: flex; align-items: center; justify-content: center; color: #DC2626;">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; color: #DC2626; line-height: 1; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;" x-text="matchSummary.pending.toLocaleString('id-ID')"></span>
                    <span style="font-size: 11px; font-weight: 600; color: #DC2626;">Baris</span>
                </div>
            </div>

            {{-- Card 4: Transfer & Lainnya --}}
            <div
                @click="filterStatus = (['OTHERS', 'TRANSFER'].includes(filterStatus) ? 'all' : 'OTHERS')"
                class="summary-stat-card"
                :style="['OTHERS', 'TRANSFER'].includes(filterStatus)
                    ? 'border-color: #2563EB; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2); background-color: #EFF6FF;'
                    : 'border-color: #E2E8F0; background-color: #FFFFFF;'"
            >
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; font-weight: 700; color: #2563EB; text-transform: uppercase; letter-spacing: 0.05em;">Transfer / Lainnya</span>
                    <div style="width: 24px; height: 24px; border-radius: 6px; background-color: #DBEAFE; display: flex; align-items: center; justify-content: center; color: #2563EB;">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                </div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; color: #2563EB; line-height: 1; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;" x-text="matchSummary.others.toLocaleString('id-ID')"></span>
                    <span style="font-size: 11px; font-weight: 600; color: #2563EB;">Baris</span>
                </div>
            </div>
        </div>

        {{-- ===== RESULTS SECTION ===== --}}
        <div
            x-show="completed"
            x-cloak
            x-transition:enter="transition ease-out duration-500 delay-200"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm"
        >
            {{-- Results Header --}}
            <div class="px-5 py-4 border-b border-[#E2E8F0]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-[16px] font-bold text-[#0F172A]">Hasil Matching Report</h2>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-[#F0FDF4] text-[#16A34A] border border-[#BBF7D0]">
                            <span x-text="(matchingData?.summary?.total_rows ?? matchingData?.summary?.total ?? 0).toLocaleString('id-ID')"></span>&nbsp;Baris Terproses
                        </span>
                    </div>
                </div>
            </div>

            {{-- Search & Filter Bar --}}
            <div class="px-5 py-3 border-b border-[#F1F5F9]">
                <div class="flex items-center justify-between gap-4">
                    {{-- Search Input --}}
                    <div class="flex-1 max-w-lg relative">
                        <svg class="w-4 h-4 text-[#94A3B8] absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            x-model="searchQuery"
                            placeholder="Cari SKU, nama produk, atau batch..."
                            class="w-full pl-10 pr-4 py-2 text-[12px] border border-[#E2E8F0] rounded-lg bg-[#F8FAFC] focus:bg-white focus:border-[#2563EB] focus:ring-1 focus:ring-[#2563EB]/20 outline-none transition-smooth"
                        >
                    </div>

                    {{-- Filters --}}
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <label class="text-[11px] text-[#64748B] font-medium">Status:</label>
                            <select x-model="filterStatus" class="text-[11px] border border-[#E2E8F0] rounded-lg px-2.5 py-1.5 bg-white text-[#475569] outline-none focus:border-[#2563EB] cursor-pointer">
                                <option value="all">Semua Status</option>
                                <option value="MATCH">MATCH</option>
                                <option value="PENDING">PENDING</option>
                                <option value="TRANSFER">TRANSFER</option>
                                <option value="OTHERS">TRANSFER & LAINNYA</option>
                                <option value="MELEBIHI POC">MELEBIHI POC</option>
                                <option value="INITIATIVE">INITIATIVE</option>
                                <option value="DUPLICATE RAW">DUPLICATE RAW</option>
                                <option value="PUT AWAY">PUT AWAY</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Results Table --}}
            <div style="overflow-x: auto;">
                <table class="modern-table">
                    <colgroup>
                        <col style="width: 4%;">
                        <col style="width: 9%;">
                        <col style="width: 24%;">
                        <col style="width: 13%;">
                        <col style="width: 13%;">
                        <col style="width: 11%;">
                        <col style="width: 8%;">
                        <col style="width: 8%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="text-align: center;">No.</th>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th>From (Report)</th>
                            <th>From (Analisa)</th>
                            <th>To (Report)</th>
                            <th>To (Analisa)</th>
                            <th style="text-align: right;">Refill Needed</th>
                            <th style="text-align: right;">Qty Analisa</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in filteredResults" :key="index">
                            <tr>
                                {{-- NO --}}
                                <td style="text-align: center; font-size: 12px !important; color: #94A3B8 !important; font-weight: 500;" x-text="row.no"></td>

                                {{-- SKU --}}
                                <td style="font-weight: 700; color: #0F172A !important;" x-text="row.sku"></td>

                                {{-- NAMA PRODUK --}}
                                <td style="font-weight: 500; color: #1E293B !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" :title="row.nama_produk" x-text="row.nama_produk"></td>

                                {{-- FROM LOCATION (REPORT) --}}
                                <td>
                                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                                        <span :class="['unmatch', 'PENDING'].includes(row.status) ? 'badge-loc-err' : 'badge-loc-src'"
                                              x-text="row.from_location_report || '-'"></span>
                                        <span style="font-size: 10.5px; color: #94A3B8; font-weight: 500;" x-text="row.from_location_report_type" x-show="row.from_location_report_type"></span>
                                    </div>
                                </td>

                                {{-- FROM LOCATION (ANALISA) --}}
                                <td>
                                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                                        <span class="badge-loc-src" x-text="row.from_location_analysis || '-'"></span>
                                        <span style="font-size: 10.5px; color: #94A3B8; font-weight: 500;" x-text="row.from_location_analysis_type" x-show="row.from_location_analysis_type"></span>
                                    </div>
                                </td>

                                {{-- TO LOCATION (REPORT) --}}
                                <td>
                                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                                        <span class="badge-loc-dst" x-text="row.to_location_report || '-'"></span>
                                        <span style="font-size: 10.5px; color: #94A3B8; font-weight: 500;" x-text="row.to_location_report_type" x-show="row.to_location_report_type"></span>
                                    </div>
                                </td>

                                {{-- TO LOCATION (ANALISA) --}}
                                <td>
                                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                                        <span class="badge-loc-dst" x-text="row.to_location_analysis || '-'"></span>
                                        <span style="font-size: 10.5px; color: #94A3B8; font-weight: 500;" x-text="row.to_location_analysis_type" x-show="row.to_location_analysis_type"></span>
                                    </div>
                                </td>

                                {{-- QTY REPLENISH --}}
                                <td style="text-align: right;">
                                    <template x-if="row.qty_replenish && row.qty_replenish !== '-'">
                                        <span class="badge-qty-pill">
                                            <span :style="['unmatch', 'PENDING'].includes(row.status) ? 'color: #DC2626;' : 'color: #0F172A;'" x-text="row.qty_replenish"></span>
                                            <span style="font-size: 10.5px; font-weight: 600; color: #64748B;" x-text="row.qty_replenish_unit || 'CTN'"></span>
                                        </span>
                                    </template>
                                    <template x-if="!row.qty_replenish || row.qty_replenish === '-'">
                                        <span style="color: #CBD5E1; font-weight: 700;">—</span>
                                    </template>
                                </td>

                                {{-- QTY ANALISA --}}
                                <td style="text-align: right;">
                                    <template x-if="row.qty_analysis && row.qty_analysis !== '-'">
                                        <span class="badge-qty-pill">
                                            <span x-text="row.qty_analysis"></span>
                                            <span style="font-size: 10.5px; font-weight: 600; color: #64748B;" x-text="row.qty_analysis_unit || 'CTN'"></span>
                                        </span>
                                    </template>
                                    <template x-if="!row.qty_analysis || row.qty_analysis === '-'">
                                        <span style="color: #CBD5E1; font-weight: 700;">—</span>
                                    </template>
                                </td>

                                {{-- STATUS --}}
                                <td style="text-align: center;">
                                    <span
                                        class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase border shadow-2xs"
                                        :class="{
                                            'bg-[#F0FDF4] text-[#16A34A] border-[#BBF7D0]': ['MATCH', 'matched'].includes(row.status),
                                            'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]': ['PENDING', 'unmatch'].includes(row.status),
                                            'bg-[#FEF3C7] text-[#D97706] border-[#FDE68A]': ['MELEBIHI POC', 'melebihi_poc'].includes(row.status),
                                            'bg-[#EFF6FF] text-[#2563EB] border-[#BFDBFE]': row.status === 'INITIATIVE',
                                            'bg-[#F3E8FF] text-[#9333EA] border-[#E9D5FF]': row.status === 'DUPLICATE RAW',
                                            'bg-[#F1F5F9] text-[#475569] border-[#E2E8F0]': ['PUT AWAY', 'TRANSFER'].includes(row.status)
                                        }"
                                        x-text="row.status"
                                    ></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-3 border-t border-[#E2E8F0] flex items-center justify-between">
                <div class="text-[11px] text-[#64748B]">
                    Menampilkan <span class="font-medium" x-text="filteredResults.length"></span>
                    dari <span class="font-medium" x-text="(matchingData?.pagination?.total ?? matchingData?.summary?.total_rows ?? matchingData?.summary?.total ?? 0).toLocaleString('id-ID')"></span> baris
                </div>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="currentPage <= 1"
                        @click="currentPage--"
                        class="px-3 py-1.5 text-[11px] font-medium text-[#475569] bg-white border border-[#E2E8F0] rounded-lg hover:bg-[#F8FAFC] transition-smooth disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sebelumnya
                    </button>
                    <span class="text-[11px] text-[#64748B]">
                        Halaman <span class="font-semibold" x-text="currentPage"></span>
                        dari <span class="font-semibold" x-text="matchingData?.pagination?.total_pages || 1"></span>
                    </span>
                    <button
                        :disabled="currentPage >= (matchingData?.pagination?.total_pages || 1)"
                        @click="currentPage++"
                        class="px-3 py-1.5 text-[11px] font-medium text-[#475569] bg-white border border-[#E2E8F0] rounded-lg hover:bg-[#F8FAFC] transition-smooth disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>

        {{-- ===== TABEL HISTORY MATCHING (DIGABUNG PER TANGGAL & CABANG) ===== --}}
        <div class="modern-card" style="padding: 22px; margin-top: 20px;">
            {{-- Header & Shift Filter --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2 style="font-size: 17px; font-weight: 700; color: #0F172A; margin: 0;">History Matching</h2>
                        <span style="font-size: 11px; font-weight: 700; color: #2563EB; background-color: #EFF6FF; border: 1px solid #BFDBFE; padding: 2px 9px; border-radius: 999px;"
                              x-text="filteredHistoryItems.length + ' Tanggal'"></span>
                    </div>
                    <p style="font-size: 12px; color: #64748B; margin: 3px 0 0 0;">
                        Riwayat rekonsiliasi per tanggal & cabang.
                    </p>
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
                    {{-- Search Input --}}
                    <div style="position: relative; width: 250px;">
                        <svg style="width: 14px; height: 14px; color: #94A3B8; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text"
                               x-model="historySearch"
                               @input="historyPage = 1"
                               placeholder="Cari tanggal, shift, atau cabang..."
                               style="width: 100%; height: 36px; padding: 0 12px 0 34px; font-size: 12px; border: 1px solid #E2E8F0; border-radius: 10px; background-color: #F8FAFC; color: #0F172A; outline: none; box-sizing: border-box;">
                    </div>

                    {{-- Filter Cabang (Modern Dropdown) --}}
                    <div class="modern-dd-wrap" x-data="{ openBranch: false }" @click.outside="openBranch = false">
                        <button type="button"
                                @click="openBranch = !openBranch"
                                class="modern-dd-btn"
                                :class="openBranch ? 'modern-dd-btn-active' : ''">
                            <svg style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span style="font-size: 11.5px; color: #64748B; font-weight: 600;">Cabang:</span>
                            <span style="font-size: 12px; font-weight: 700; color: #0F172A;" x-text="selectedHistoryBranchLabel"></span>
                            <svg :class="openBranch ? 'dd-chevron dd-chevron-open' : 'dd-chevron'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openBranch"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="modern-dd-menu"
                             style="min-width: 210px; max-height: 280px; overflow-y: auto;">
                            {{-- Semua Cabang --}}
                            <button type="button"
                                    @click="historyBranchFilter = 'all'; historyPage = 1; openBranch = false"
                                    class="modern-dd-item"
                                    :class="historyBranchFilter === 'all' ? 'modern-dd-item-selected' : ''">
                                <span>Semua Cabang</span>
                                <svg x-show="historyBranchFilter === 'all'"
                                     style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>

                            <div style="height: 1px; background-color: #F1F5F9; margin: 4px 0;"></div>

                            <template x-for="b in branches" :key="b.id">
                                <button type="button"
                                        @click="historyBranchFilter = String(b.id); historyPage = 1; openBranch = false"
                                        class="modern-dd-item"
                                        :class="historyBranchFilter === String(b.id) ? 'modern-dd-item-selected' : ''">
                                    <div>
                                        <div style="font-weight: 700; line-height: 1.25;" x-text="b.code"></div>
                                        <div style="font-size: 11px; color: #64748B; font-weight: 500; margin-top: 1px;"
                                             x-show="b.name && b.code.toLowerCase().replace(/[^a-z0-9]/g,'') !== b.name.toLowerCase().replace(/[^a-z0-9]/g,'')"
                                             x-text="b.name"></div>
                                    </div>
                                    <svg x-show="historyBranchFilter === String(b.id)"
                                         style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Filter Bulan (Modern Dropdown) --}}
                    <div class="modern-dd-wrap" x-data="{ openMonth: false }" @click.outside="openMonth = false">
                        <button type="button"
                                @click="openMonth = !openMonth"
                                class="modern-dd-btn"
                                :class="openMonth ? 'modern-dd-btn-active' : ''">
                            <svg style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span style="font-size: 11.5px; color: #64748B; font-weight: 600;">Bulan:</span>
                            <span style="font-size: 12px; font-weight: 700; color: #0F172A;" x-text="selectedMonthLabel"></span>
                            <svg :class="openMonth ? 'dd-chevron dd-chevron-open' : 'dd-chevron'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openMonth"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="modern-dd-menu"
                             style="min-width: 230px; max-height: 300px; overflow-y: auto;">
                            {{-- Semua Bulan --}}
                            <button type="button"
                                    @click="historyMonthFilter = 'all'; historyPage = 1; openMonth = false"
                                    class="modern-dd-item"
                                    :class="historyMonthFilter === 'all' ? 'modern-dd-item-selected' : ''">
                                <span>Semua Bulan</span>
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 10.5px; font-weight: 700; padding: 1.5px 7px; border-radius: 999px; background-color: #F1F5F9; color: #475569;"
                                          x-text="matchHistoryItems.length"></span>
                                    <svg x-show="historyMonthFilter === 'all'"
                                         style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </button>

                            <div style="height: 1px; background-color: #F1F5F9; margin: 4px 0;"></div>

                            <template x-for="m in availableMonths" :key="m.key">
                                <button type="button"
                                        @click="historyMonthFilter = m.key; historyPage = 1; openMonth = false"
                                        class="modern-dd-item"
                                        :class="historyMonthFilter === m.key ? 'modern-dd-item-selected' : ''">
                                    <span x-text="m.label"></span>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <template x-if="m.count > 0">
                                            <span style="font-size: 10.5px; font-weight: 700; padding: 1.5px 8px; border-radius: 999px; background-color: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;"
                                                  x-text="m.count + ' Tgl'"></span>
                                        </template>
                                        <svg x-show="historyMonthFilter === m.key"
                                             style="width: 14px; height: 14px; color: #2563EB; flex-shrink: 0;"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Reset Filter Button --}}
                    <template x-if="historyMonthFilter !== 'all' || historyBranchFilter !== 'all' || historySearch.trim() !== ''">
                        <button type="button"
                                @click="historyMonthFilter = 'all'; historyBranchFilter = 'all'; historySearch = ''; historyPage = 1"
                                style="display: inline-flex; align-items: center; gap: 5px; height: 36px; padding: 0 12px; border-radius: 10px; font-size: 11.5px; font-weight: 600; color: #64748B; background-color: #F8FAFC; border: 1px solid #E2E8F0; cursor: pointer;">
                            <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Reset
                        </button>
                    </template>
                </div>
            </div>

            {{-- Table --}}
            <div style="overflow-x: auto; border: 1px solid #E2E8F0; border-radius: 12px;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="text-align: center; width: 42px;">No.</th>
                            <th style="width: 145px; white-space: nowrap;">Tanggal Report</th>
                            <th style="min-width: 180px;">Replenishment Report</th>
                            <th style="min-width: 180px;">Analisa Replenishment</th>
                            <th style="width: 148px; white-space: nowrap;">Hasil Matching</th>
                            <th style="width: 180px;">Status & Ringkasan</th>
                            <th style="width: 130px;">Waktu Import</th>
                            @if(auth()->user()?->hasPermission('matching', 'delete'))
                                <th style="text-align: center; width: 88px;">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="paginatedHistoryItems.length === 0">
                            <tr>
                                <td colspan="8" style="padding: 40px 20px; text-align: center; color: #94A3B8;">
                                    <div style="font-size: 13px; font-weight: 600; color: #64748B;">Belum ada data History Matching</div>
                                    <div style="font-size: 11.5px; color: #94A3B8; margin-top: 3px;">Unggah kedua berkas Excel di atas untuk mulai merekonsiliasi data.</div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="(hRow, hIdx) in paginatedHistoryItems" :key="hRow.id">
                            <tr>
                                {{-- No --}}
                                <td style="text-align: center; font-size: 12px !important; color: #94A3B8 !important; font-weight: 600;"
                                    x-text="(historyPage - 1) * historyPerPage + hIdx + 1"></td>

                                {{-- Tanggal Report + Cabang --}}
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 34px; height: 34px; border-radius: 9px; background-color: #EFF6FF; border: 1px solid #BFDBFE; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #2563EB;">
                                            <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div style="font-size: 13px; font-weight: 700; color: #0F172A; white-space: nowrap;" x-text="hRow.report_date"></div>
                                            <div style="display: flex; align-items: center; gap: 4px; margin-top: 3px; flex-wrap: wrap;">
                                                <template x-if="hRow.branch_code && hRow.branch_code !== '-'">
                                                    <span style="font-size: 10px; font-weight: 700; padding: 1.5px 7px; border-radius: 5px; background-color: #0F172A; color: #FFFFFF; white-space: nowrap;"
                                                          x-text="hRow.branch_code"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Replenishment Report: Pill Buttons Shift 1 & Shift 2 Berdampingan --}}
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <template x-for="slotName in getVisibleShiftSlots(hRow)" :key="'rep-' + slotName">
                                            <div>
                                                <template x-if="findShift(hRow, slotName)">
                                                    <a :href="findShift(hRow, slotName).report_url"
                                                       :title="findShift(hRow, slotName).report_file_name + ' (' + (findShift(hRow, slotName).report_row_count || findShift(hRow, slotName).total_tasks) + ' baris)'"
                                                       style="display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 15px; border-radius: 9999px; background-color: #2563EB; color: #FFFFFF; font-size: 12px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 5px rgba(37, 99, 235, 0.22); transition: all 0.15s; white-space: nowrap;">
                                                        <svg style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        <span x-text="formatShiftLabel(slotName)"></span>
                                                    </a>
                                                </template>
                                                <template x-if="!findShift(hRow, slotName)">
                                                    <span style="display: inline-flex; align-items: center; gap: 5px; height: 34px; padding: 0 14px; border-radius: 9999px; background-color: #F8FAFC; color: #94A3B8; border: 1px dashed #CBD5E1; font-size: 11.5px; font-weight: 600; white-space: nowrap;"
                                                          title="Belum ada berkas untuk shift ini">
                                                        <span x-text="formatShiftLabel(slotName)"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </td>

                                {{-- Analisa Replenishment: Pill Buttons Shift 1 & Shift 2 Berdampingan --}}
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <template x-for="slotName in getVisibleShiftSlots(hRow)" :key="'ana-' + slotName">
                                            <div>
                                                <template x-if="findShift(hRow, slotName)">
                                                    <a :href="findShift(hRow, slotName).analysis_url"
                                                       :title="findShift(hRow, slotName).analysis_file_name + ' (' + findShift(hRow, slotName).analysis_row_count + ' baris)'"
                                                       style="display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 15px; border-radius: 9999px; background-color: #2563EB; color: #FFFFFF; font-size: 12px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 5px rgba(37, 99, 235, 0.22); transition: all 0.15s; white-space: nowrap;">
                                                        <svg style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        <span x-text="formatShiftLabel(slotName)"></span>
                                                    </a>
                                                </template>
                                                <template x-if="!findShift(hRow, slotName)">
                                                    <span style="display: inline-flex; align-items: center; gap: 5px; height: 34px; padding: 0 14px; border-radius: 9999px; background-color: #F8FAFC; color: #94A3B8; border: 1px dashed #CBD5E1; font-size: 11.5px; font-weight: 600; white-space: nowrap;"
                                                          title="Belum ada berkas untuk shift ini">
                                                        <span x-text="formatShiftLabel(slotName)"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </td>

                                {{-- Hasil Matching (Gabungan Shift 1 & Shift 2) --}}
                                <td>
                                    <a :href="hRow.match_detail_url"
                                       title="Lihat detail hasil matching gabungan Shift 1 & Shift 2"
                                       style="display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 15px; border-radius: 9999px; background-color: #0F172A; color: #FFFFFF; font-size: 12px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 5px rgba(15, 23, 42, 0.16); transition: all 0.15s; white-space: nowrap;">
                                        <svg style="width: 14px; height: 14px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        <span>Detail Matching</span>
                                    </a>
                                </td>

                                {{-- Status & Ringkasan Match --}}
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span style="font-size: 12.5px; font-weight: 700; color: #0F172A; white-space: nowrap;"
                                                  x-text="hRow.match_count + ' / ' + hRow.total_tasks + ' Matched'"></span>
                                            <span :class="hRow.match_pct_num >= 80 ? 'history-pct-badge history-pct-high' : 'history-pct-badge history-pct-normal'"
                                                  x-text="hRow.match_pct"></span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 11px;">
                                            <span style="color: #DC2626; font-weight: 600;" x-text="hRow.pending_count + ' Pending'"></span>
                                            <template x-if="hRow.surplus_poc_count > 0">
                                                <span style="color: #D97706; font-weight: 600;" x-text="'• ' + hRow.surplus_poc_count + ' Melebihi POC'"></span>
                                            </template>
                                        </div>
                                    </div>
                                </td>

                                {{-- Waktu Import --}}
                                <td style="font-size: 12px !important; color: #64748B !important; white-space: nowrap;" x-text="hRow.imported_at"></td>

                                {{-- Aksi (Hapus) --}}
                                @if(auth()->user()?->hasPermission('matching', 'delete'))
                                    <td style="text-align: center;">
                                        <button type="button"
                                                @click.stop="openDeleteModal(hRow)"
                                                style="display: inline-flex; align-items: center; gap: 4px; height: 32px; padding: 0 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 600; color: #DC2626; background-color: #FEF2F2; border: 1px solid #FECACA; cursor: pointer;"
                                                title="Hapus Riwayat Matching Tanggal Ini">
                                            <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination History Matching --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; padding-top: 14px; border-top: 1px solid #F1F5F9;">
                <div style="font-size: 12px; color: #64748B;">
                    Menampilkan <span style="font-weight: 700; color: #0F172A;" x-text="paginatedHistoryItems.length"></span>
                    dari <span style="font-weight: 700; color: #0F172A;" x-text="filteredHistoryItems.length"></span> tanggal riwayat matching
                </div>

                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button"
                            @click="historyPage > 1 && historyPage--"
                            :disabled="historyPage <= 1"
                            class="px-3.5 py-1.5 rounded-lg text-[12px] font-medium text-[#475569] bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] disabled:opacity-40 disabled:cursor-not-allowed">
                        Sebelumnya
                    </button>
                    <span style="font-size: 12px; color: #475569; padding: 0 8px;">
                        Hal <strong x-text="historyPage"></strong> / <strong x-text="historyTotalPages"></strong>
                    </span>
                    <button type="button"
                            @click="historyPage < historyTotalPages && historyPage++"
                            :disabled="historyPage >= historyTotalPages"
                            class="px-3.5 py-1.5 rounded-lg text-[12px] font-medium text-[#475569] bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] disabled:opacity-40 disabled:cursor-not-allowed">
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>

        {{-- ===== MODAL KONFIRMASI HAPUS HISTORY MATCHING ===== --}}
        <div x-show="deleteTarget !== null"
             x-cloak
             class="modal-backdrop"
             @click.self="deleteTarget = null">
            <div class="modal-dialog"
                 @click.stop
                 style="max-width: 400px; padding: 20px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <h3 style="font-size: 15px; font-weight: 700; color: #0F172A; margin: 0;">Hapus Riwayat Matching</h3>
                        <p style="font-size: 12px; color: #64748B; margin: 2px 0 0 0;" x-text="(deleteTarget?.report_date || '') + (deleteTarget?.branch_code && deleteTarget?.branch_code !== '-' ? ' • ' + deleteTarget.branch_code : '')"></p>
                    </div>
                    <button type="button" @click="deleteTarget = null" style="background: none; border: none; color: #94A3B8; cursor: pointer; padding: 2px;">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p style="font-size: 12px; color: #475569; margin: 0 0 10px 0;">
                    Pilih shift yang ingin dihapus:
                </p>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <template x-for="sh in (deleteTarget?.shifts || [])" :key="sh.report_shift">
                        <form action="{{ route('dashboard.match-batch.destroy') }}" method="POST" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="report_date" :value="deleteTarget?.report_date || ''">
                            <input type="hidden" name="report_shift" :value="sh.report_shift">
                            <input type="hidden" name="branch_id" :value="deleteTarget?.branch_id ?? ''">

                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 10px; background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #0F172A;" x-text="formatShiftLabel(sh.report_shift)"></div>
                                    <div style="font-size: 11px; color: #64748B; margin-top: 1px;" x-text="sh.match_count + '/' + sh.total_tasks + ' Matched'"></div>
                                </div>
                                <button type="submit"
                                        style="height: 30px; padding: 0 12px; border-radius: 9999px; font-size: 11.5px; font-weight: 600; color: #DC2626; background-color: #FEF2F2; border: 1px solid #FECACA; cursor: pointer; flex-shrink: 0;">
                                    Hapus
                                </button>
                            </div>
                        </form>
                    </template>
                </div>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #F1F5F9; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <button type="button"
                            @click="deleteTarget = null"
                            style="height: 34px; padding: 0 14px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #475569; background-color: #F1F5F9; border: 1px solid #E2E8F0; cursor: pointer;">
                        Batal
                    </button>

                    <template x-if="(deleteTarget?.shifts || []).length > 1">
                        <form action="{{ route('dashboard.match-batch.destroy') }}" method="POST" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="report_date" :value="deleteTarget?.report_date || ''">
                            <input type="hidden" name="report_shift" value="ALL">
                            <input type="hidden" name="branch_id" :value="deleteTarget?.branch_id ?? ''">

                            <button type="submit"
                                    style="height: 34px; padding: 0 14px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #FFFFFF; background-color: #DC2626; border: none; cursor: pointer;">
                                Hapus Semua Shift
                            </button>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        {{-- ===== CATEGORY VERIFICATION MODAL ===== --}}
        <div
            x-show="showCategoryConfirmModal"
            x-cloak
            style="position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 16px; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px);"
            @keydown.escape.window="showCategoryConfirmModal = false"
        >
            <div
                @click.outside="showCategoryConfirmModal = false"
                style="background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.18), 0 8px 10px -6px rgba(15, 23, 42, 0.1); width: 100%; max-width: 460px; padding: 22px 24px;"
            >
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background-color: #EFF6FF; border: 1px solid #BFDBFE; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg style="width: 19px; height: 19px; color: #2563EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size: 15.5px; font-weight: 800; color: #0F172A; margin: 0;">Verifikasi Kategori Data</h3>
                            <p style="font-size: 12px; color: #64748B; margin: 3px 0 0 0; line-height: 1.4;">
                                Pilih kategori data sebelum diproses agar masuk ke ringkasan yang tepat.
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showCategoryConfirmModal = false" style="background: none; border: none; color: #94A3B8; cursor: pointer; padding: 2px;">
                        <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px;">
                    <button type="button" @click="categoryMode = 'DRY'"
                        :style="categoryMode === 'DRY'
                            ? 'width: 100%; text-align: left; padding: 12px 14px; border-radius: 12px; border: 1.5px solid #2563EB; background-color: #EFF6FF; display: flex; align-items: flex-start; gap: 11px; cursor: pointer;'
                            : 'width: 100%; text-align: left; padding: 12px 14px; border-radius: 12px; border: 1px solid #E2E8F0; background-color: #FFFFFF; display: flex; align-items: flex-start; gap: 11px; cursor: pointer;'">
                        <span :style="categoryMode === 'DRY'
                            ? 'margin-top: 2px; width: 16px; height: 16px; border-radius: 999px; border: 5px solid #2563EB; background-color: #FFFFFF; flex-shrink: 0;'
                            : 'margin-top: 2px; width: 16px; height: 16px; border-radius: 999px; border: 1.5px solid #CBD5E1; background-color: #FFFFFF; flex-shrink: 0;'"></span>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #0F172A;">DRY</div>
                            <div style="font-size: 11.5px; color: #64748B; margin-top: 2px; line-height: 1.4;">Semua data pada upload ini disimpan sebagai kategori <strong style="color: #334155;">DRY</strong>.</div>
                        </div>
                    </button>

                    <button type="button" @click="categoryMode = 'FROZEN'"
                        :style="categoryMode === 'FROZEN'
                            ? 'width: 100%; text-align: left; padding: 12px 14px; border-radius: 12px; border: 1.5px solid #4F46E5; background-color: #EEF2FF; display: flex; align-items: flex-start; gap: 11px; cursor: pointer;'
                            : 'width: 100%; text-align: left; padding: 12px 14px; border-radius: 12px; border: 1px solid #E2E8F0; background-color: #FFFFFF; display: flex; align-items: flex-start; gap: 11px; cursor: pointer;'">
                        <span :style="categoryMode === 'FROZEN'
                            ? 'margin-top: 2px; width: 16px; height: 16px; border-radius: 999px; border: 5px solid #4F46E5; background-color: #FFFFFF; flex-shrink: 0;'
                            : 'margin-top: 2px; width: 16px; height: 16px; border-radius: 999px; border: 1.5px solid #CBD5E1; background-color: #FFFFFF; flex-shrink: 0;'"></span>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #0F172A;">FROZEN</div>
                            <div style="font-size: 11.5px; color: #64748B; margin-top: 2px; line-height: 1.4;">Semua data pada upload ini disimpan sebagai kategori <strong style="color: #334155;">FROZEN</strong>.</div>
                        </div>
                    </button>

                    <button type="button" @click="categoryMode = 'AUTO'"
                        :style="categoryMode === 'AUTO'
                            ? 'width: 100%; text-align: left; padding: 12px 14px; border-radius: 12px; border: 1.5px solid #0F172A; background-color: #F8FAFC; display: flex; align-items: flex-start; gap: 11px; cursor: pointer;'
                            : 'width: 100%; text-align: left; padding: 12px 14px; border-radius: 12px; border: 1px solid #E2E8F0; background-color: #FFFFFF; display: flex; align-items: flex-start; gap: 11px; cursor: pointer;'">
                        <span :style="categoryMode === 'AUTO'
                            ? 'margin-top: 2px; width: 16px; height: 16px; border-radius: 999px; border: 5px solid #0F172A; background-color: #FFFFFF; flex-shrink: 0;'
                            : 'margin-top: 2px; width: 16px; height: 16px; border-radius: 999px; border: 1.5px solid #CBD5E1; background-color: #FFFFFF; flex-shrink: 0;'"></span>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #0F172A;">Campuran (Deteksi Otomatis)</div>
                            <div style="font-size: 11.5px; color: #64748B; margin-top: 2px; line-height: 1.4;">1 file berisi Dry &amp; Frozen sekaligus (cek kolom Storage / kode lokasi).</div>
                        </div>
                    </button>
                </div>

                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding-top: 12px; border-top: 1px solid #F1F5F9;">
                    <button type="button" @click="showCategoryConfirmModal = false"
                        style="height: 36px; padding: 0 16px; border-radius: 9px; font-size: 12.5px; font-weight: 600; color: #475569; background-color: #FFFFFF; border: 1px solid #E2E8F0; cursor: pointer;">
                        Batal
                    </button>
                    <button type="button" @click="confirmAndStartMatching()"
                        style="height: 36px; padding: 0 18px; border-radius: 9px; font-size: 12.5px; font-weight: 600; color: #FFFFFF; background-color: #0F172A; border: none; cursor: pointer; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2);">
                        Konfirmasi &amp; Proses
                    </button>
                </div>
            </div>
        </div>

        {{-- ===== SUCCESS NOTIFICATION TOAST ===== --}}
        <div
            x-show="showNotification"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 right-6 z-50 bg-white rounded-xl border border-[#BBF7D0] shadow-lg p-4 max-w-sm"
        >
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-[#F0FDF4] flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <div class="text-[13px] font-semibold text-[#0F172A]">Import Berhasil!</div>
                    <div class="text-[11px] text-[#64748B] mt-0.5" x-text="notificationMessage"></div>
                </div>
                <button @click="showNotification = false" class="text-[#94A3B8] hover:text-[#475569] transition-smooth flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- ===== ERROR NOTIFICATION TOAST ===== --}}
        <div
            x-show="showError"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 right-6 z-50 bg-white rounded-xl border border-[#FECACA] shadow-lg p-4 max-w-sm"
        >
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-[#FEF2F2] flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-[#DC2626]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <div class="text-[13px] font-semibold text-[#0F172A]">Terjadi Kesalahan</div>
                    <div class="text-[11px] text-[#64748B] mt-0.5" x-text="errorMessage"></div>
                </div>
                <button @click="showError = false" class="text-[#94A3B8] hover:text-[#475569] transition-smooth flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        function replenishmentApp() {
            const initialData = @json($initialRunData ?? null);
            const initialBranches = @json($branches ?? []);
            const initialHistory = @json($matchHistoryItems ?? []);
            return {
                // Branch state
                branches: initialBranches,
                selectedBranchId: initialBranches.length ? initialBranches[0].id : null,
                get currentBranch() {
                    return this.branches.find(b => b.id == this.selectedBranchId) || (this.branches.length ? this.branches[0] : null);
                },

                // History Matching state
                matchHistoryItems: initialHistory,
                historySearch: '',
                historyMonthFilter: 'all',
                historyBranchFilter: 'all',
                historyPage: 1,
                historyPerPage: 10,
                deleteTarget: null,

                // File state
                files: {
                    report_file: null,
                    analysis_file: null,
                },

                // Processing state
                processing: false,
                completed: Boolean(initialData && Array.isArray(initialData.results) && initialData.results.length > 0),
                progressPercent: initialData ? 100 : 0,

                // Results
                matchingData: initialData,
                currentPage: 1,

                // Search & filters
                searchQuery: '',
                filterStatus: 'all',
                filterZone: 'all',
                targetShift: '',
                categoryMode: 'AUTO',
                showCategoryConfirmModal: false,

                // Notifications
                showNotification: false,
                notificationMessage: '',
                showError: false,
                errorMessage: '',

                // Time
                currentTime: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),

                // Computed
                get canStartMatching() {
                    return Boolean(this.files.report_file || this.files.analysis_file);
                },

                get actionButtonLabel() {
                    if (this.files.report_file && this.files.analysis_file) return 'Jalankan Rekonsiliasi';
                    if (this.files.report_file) return 'Simpan Report Shift';
                    if (this.files.analysis_file) return 'Upload & Update Matching';
                    return 'Jalankan Rekonsiliasi';
                },

                get matchSummary() {
                    const s = this.matchingData?.summary;
                    const results = this.matchingData?.results || [];

                    const total = s?.total_rows ?? s?.total ?? results.length;
                    const match = s?.MATCH ?? s?.matched_rows ?? results.filter(r => ['MATCH', 'matched'].includes(r.status)).length;
                    const pending = s?.PENDING ?? s?.unmatched_rows ?? results.filter(r => ['PENDING', 'unmatch'].includes(r.status)).length;
                    const others = Math.max(0, total - match - pending);
                    const matchRate = total > 0 ? Math.round((match / total) * 100) : 0;

                    return { total, match, pending, others, matchRate };
                },

                get filteredResults() {
                    if (!this.matchingData?.results) return [];
                    let results = [...this.matchingData.results];

                    // Filter by search query
                    if (this.searchQuery) {
                        const q = this.searchQuery.toLowerCase();
                        results = results.filter(row =>
                            (row.sku || '').toLowerCase().includes(q) ||
                            (row.nama_produk || '').toLowerCase().includes(q) ||
                            (row.from_location_report || '').toLowerCase().includes(q) ||
                            (row.from_location_analysis || '').toLowerCase().includes(q) ||
                            (row.to_location_report || '').toLowerCase().includes(q) ||
                            (row.to_location_analysis || '').toLowerCase().includes(q)
                        );
                    }

                    // Filter by status
                    if (this.filterStatus !== 'all') {
                        if (this.filterStatus === 'OTHERS') {
                            results = results.filter(row => !['MATCH', 'matched', 'PENDING', 'unmatch'].includes(row.status));
                        } else {
                            results = results.filter(row => (row.status || '').toUpperCase() === this.filterStatus.toUpperCase());
                        }
                    }

                    // Filter by zone
                    if (this.filterZone !== 'all') {
                        results = results.filter(row =>
                            (row.from_location_report || '').startsWith(this.filterZone)
                        );
                    }

                    return results;
                },

                // Methods
                handleFileSelect(key, file) {
                    if (file && (file.name.match(/\.(xlsx|xls)$/i))) {
                        this.files[key] = file;
                    }
                },

                removeFile(key) {
                    this.files[key] = null;
                    // If results are showing and a file is removed, reset
                    if (this.completed) {
                        this.completed = false;
                        this.matchingData = null;
                        this.progressPercent = 0;
                    }
                },

                formatFileSize(bytes) {
                    if (!bytes) return '0 B';
                    const units = ['B', 'KB', 'MB', 'GB'];
                    let i = 0;
                    let size = bytes;
                    while (size >= 1024 && i < units.length - 1) {
                        size /= 1024;
                        i++;
                    }
                    return size.toFixed(1) + ' ' + units[i];
                },

                openCategoryConfirm() {
                    if (!this.canStartMatching || this.processing) return;
                    const repName = (this.files.report_file?.name || '').toLowerCase();
                    const anaName = (this.files.analysis_file?.name || '').toLowerCase();
                    const combined = `${repName} ${anaName}`;
                    const hasDry = combined.includes('dry');
                    const hasFrozen = combined.includes('frozen');
                    if (hasFrozen && !hasDry) {
                        this.categoryMode = 'FROZEN';
                    } else if (hasDry && !hasFrozen) {
                        this.categoryMode = 'DRY';
                    } else {
                        this.categoryMode = 'AUTO';
                    }
                    this.showCategoryConfirmModal = true;
                },

                confirmAndStartMatching() {
                    this.showCategoryConfirmModal = false;
                    this.startMatching();
                },

                async startMatching() {
                    if (!this.canStartMatching || this.processing) return;

                    this.processing = true;
                    this.completed = false;
                    this.progressPercent = 0;
                    this.showError = false;

                    // Animate progress bar
                    const progressInterval = setInterval(() => {
                        if (this.progressPercent < 90) {
                            this.progressPercent += Math.random() * 15 + 5;
                            if (this.progressPercent > 90) this.progressPercent = 90;
                        }
                    }, 200);

                    try {
                        const formData = new FormData();
                        if (this.files.report_file) {
                            formData.append('report_file', this.files.report_file);
                        }
                        if (this.files.analysis_file) {
                            formData.append('analysis_file', this.files.analysis_file);
                        }
                        if (this.targetShift && this.files.analysis_file && !this.files.report_file) {
                            formData.append('target_shift', this.targetShift);
                        }
                        formData.append('category_mode', this.categoryMode || 'AUTO');
                        if (this.selectedBranchId) {
                            formData.append('branch_id', this.selectedBranchId);
                        }

                        const response = await fetch('{{ route("replenishment.import") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        clearInterval(progressInterval);

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Terjadi kesalahan saat memproses file.');
                        }

                        const data = await response.json();
                        this.matchingData = data;
                        this.progressPercent = 100;
                        this.completed = true;
                        this.processing = false;

                        // Update time
                        this.currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                        // Show success notification
                        const totalRows = (data?.summary?.total_rows ?? data?.summary?.total ?? 0).toLocaleString('id-ID');
                        const shiftName = data?.report_shift || 'Shift';
                        const repDate = data?.report_date || '';
                        const matchPctLabel = data?.summary?.match_percentage ?? 0;
                        if (data?.upload_mode === 'report_only') {
                            this.notificationMessage = `Report ${shiftName} (${repDate}) berhasil disimpan (${totalRows} task, progres ${matchPctLabel}%). Versi lama tetap tersimpan di history.`;
                        } else if (data?.upload_mode === 'analysis_only') {
                            this.notificationMessage = `Analisa susulan ${shiftName} (${repDate}) berhasil digabung & di-match ulang. Progres sekarang ${matchPctLabel}%.`;
                        } else if (data?.was_replaced) {
                            this.notificationMessage = `Data ${shiftName} (${repDate}) berhasil diperbarui (history versi lama tetap tersimpan). ${totalRows} baris diproses.`;
                        } else {
                            this.notificationMessage = `Data ${shiftName} (${repDate}) berhasil di-import dan dikalkulasi ke ringkasan harian. ${totalRows} baris diproses.`;
                        }
                        this.showNotification = true;
                        setTimeout(() => { this.showNotification = false; }, 6000);

                        // Prepend / merge new entry into History Matching table immediately
                        const mCount = data?.summary?.match_count ?? data?.summary?.MATCH ?? 0;
                        const pCount = data?.summary?.pending_count ?? data?.summary?.PENDING ?? 0;
                        const tTasks = data?.summary?.total_tasks ?? (mCount + pCount);
                        const mPctNum = data?.summary?.match_percentage ?? (tTasks > 0 ? Math.round((mCount / tTasks) * 1000) / 10 : 0);
                        const nowStr = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + this.currentTime + ' WIB';
                        const bId = data?.branch_id ?? this.selectedBranchId;

                        const newShiftObj = {
                            branch_id: bId,
                            branch_code: this.currentBranch?.code || '-',
                            report_date: repDate,
                            report_shift: shiftName,
                            total_rows: data?.summary?.total_rows ?? 0,
                            total_tasks: tTasks,
                            match_count: mCount,
                            pending_count: pCount,
                            surplus_poc_count: data?.summary?.MELEBIHI_POC ?? 0,
                            match_pct_num: mPctNum,
                            match_pct: Number(mPctNum).toFixed(1) + '%',
                            imported_at: nowStr,
                            report_import_id: data?.report_import_id,
                            report_file_name: data?.report_file_name || (`Replenish Report - ${repDate} - ${shiftName}.xlsx`),
                            report_row_count: tTasks,
                            report_url: data?.report_import_id ? `{{ url('/replenishment/history') }}/${data.report_import_id}` : `{{ route('replenishment.report-history') }}`,
                            report_download_url: data?.report_import_id ? `{{ url('/replenishment/history') }}/${data.report_import_id}/download` : null,
                            analysis_import_id: data?.analysis_import_id,
                            analysis_file_name: data?.analysis_file_name || (`Analisa Report - ${repDate} - ${shiftName}.xlsx`),
                            analysis_row_count: data?.summary?.total_rows ?? 0,
                            analysis_url: data?.analysis_import_id ? `{{ url('/replenishment/history') }}/${data.analysis_import_id}` : `{{ route('replenishment.analysis-history') }}`,
                            analysis_download_url: data?.analysis_import_id ? `{{ url('/replenishment/history') }}/${data.analysis_import_id}/download` : null,
                        };

                        const existingIdx = this.matchHistoryItems.findIndex(
                            item => item.report_date === repDate && String(item.branch_id || 0) === String(bId || 0)
                        );

                        if (existingIdx !== -1) {
                            const existingGroup = this.matchHistoryItems[existingIdx];
                            const filteredShifts = (existingGroup.shifts || []).filter(s => s.report_shift !== shiftName);
                            filteredShifts.push(newShiftObj);
                            filteredShifts.sort((a, b) => String(a.report_shift).localeCompare(String(b.report_shift)));
                            existingGroup.shifts = filteredShifts;
                            existingGroup.imported_at = nowStr;
                        } else {
                            this.matchHistoryItems.unshift({
                                id: `${repDate}-${bId || 0}`,
                                branch_id: bId,
                                branch_code: this.currentBranch?.code || '-',
                                report_date: repDate,
                                total_tasks: tTasks,
                                match_count: mCount,
                                pending_count: pCount,
                                surplus_poc_count: data?.summary?.MELEBIHI_POC ?? 0,
                                match_pct_num: mPctNum,
                                match_pct: Number(mPctNum).toFixed(1) + '%',
                                imported_at: nowStr,
                                shifts: [newShiftObj],
                            });
                        }

                    } catch (error) {
                        clearInterval(progressInterval);
                        this.processing = false;
                        this.progressPercent = 0;
                        this.errorMessage = error.message || 'Terjadi kesalahan yang tidak diketahui.';
                        this.showError = true;
                        setTimeout(() => { this.showError = false; }, 8000);
                    }
                },

                resetAll() {
                    this.files = { report_file: null, analysis_file: null };
                    this.processing = false;
                    this.completed = false;
                    this.progressPercent = 0;
                    this.matchingData = null;
                    this.currentPage = 1;
                    this.searchQuery = '';
                    this.filterStatus = 'all';
                    this.filterZone = 'all';
                    this.showNotification = false;
                    this.showError = false;

                    // Reset file inputs
                    document.querySelectorAll('input[type="file"]').forEach(input => {
                        input.value = '';
                    });
                },

                refreshResults() {
                    // Re-trigger with existing data (simulated)
                    if (this.matchingData) {
                        this.currentPage = 1;
                        this.searchQuery = '';
                        this.filterStatus = 'all';
                        this.filterZone = 'all';
                    }
                },

                openDeleteModal(row) {
                    this.deleteTarget = row;
                },

                get availableMonths() {
                    const monthNames = [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ];
                    const counts = {};
                    let defaultYear = new Date().getFullYear();

                    (this.matchHistoryItems || []).forEach(item => {
                        const d = String(item.report_date || '');
                        if (d.length >= 7) {
                            const ym = d.slice(0, 7);
                            counts[ym] = (counts[ym] || 0) + 1;
                            const yr = parseInt(d.slice(0, 4), 10);
                            if (!isNaN(yr)) defaultYear = yr;
                        }
                    });

                    // Ensure all 12 months of the active year + any months present in data are available
                    const monthSet = new Set(Object.keys(counts));
                    for (let m = 1; m <= 12; m++) {
                        const mm = String(m).padStart(2, '0');
                        monthSet.add(`${defaultYear}-${mm}`);
                    }

                    const list = Array.from(monthSet).map(ym => {
                        const [yr, mm] = ym.split('-');
                        const mIdx = parseInt(mm, 10) - 1;
                        const label = `${monthNames[mIdx] || mm} ${yr}`;
                        return {
                            key: ym,
                            label,
                            count: counts[ym] || 0,
                        };
                    });

                    // Sort: months that have data first (descending), then chronological for the rest
                    return list.sort((a, b) => {
                        if ((a.count > 0) !== (b.count > 0)) {
                            return b.count > 0 ? 1 : -1;
                        }
                        return a.key.localeCompare(b.key);
                    });
                },

                get selectedMonthLabel() {
                    if (this.historyMonthFilter === 'all') return 'Semua Bulan';
                    const found = this.availableMonths.find(m => m.key === this.historyMonthFilter);
                    return found ? found.label : this.historyMonthFilter;
                },

                get selectedHistoryBranchLabel() {
                    if (this.historyBranchFilter === 'all') return 'Semua Cabang';
                    const found = (this.branches || []).find(b => String(b.id) === String(this.historyBranchFilter));
                    return found ? found.code : 'Semua Cabang';
                },

                getBranchConfiguredShiftSlots(branchId) {
                    const branch = (this.branches || []).find(b => String(b.id) === String(branchId));
                    const activeShifts = branch?.active_shifts || branch?.shifts || [];
                    const slots = [];

                    activeShifts.forEach(s => {
                        const effective = String(s.normalized_shift || s.shift_name || '').trim().toUpperCase();
                        if (effective && !slots.includes(effective)) {
                            slots.push(effective);
                        }
                    });

                    return slots.length > 0 ? slots : ['SHIFT 1', 'SHIFT 2'];
                },

                getBranchScheduleSummary(branch) {
                    const activeShifts = branch?.active_shifts || branch?.shifts || [];
                    if (!activeShifts.length) {
                        return 'Belum ada konfigurasi shift aktif (menggunakan default Shift 1 & Shift 2)';
                    }
                    return activeShifts.map(s => {
                        const dayLabel = s.day_type === 'saturday' ? 'Sabtu' : 'Sen-Jum';
                        const start = String(s.start_time || '').slice(0, 5);
                        const end = String(s.end_time || '').slice(0, 5);
                        const norm = s.normalized_shift && s.normalized_shift !== s.shift_name ? ` -> ${s.normalized_shift}` : '';
                        return `${dayLabel} ${s.shift_name} (${start}-${end}${norm})`;
                    }).join(' | ');
                },

                getVisibleShiftSlots(hRow) {
                    const slots = [...this.getBranchConfiguredShiftSlots(hRow.branch_id)];
                    (hRow.shifts || []).forEach(s => {
                        if (s.report_shift && !slots.includes(s.report_shift)) {
                            slots.push(s.report_shift);
                        }
                    });
                    return slots;
                },

                findShift(hRow, slotName) {
                    return (hRow.shifts || []).find(s => s.report_shift === slotName) || null;
                },

                formatShiftLabel(slotName) {
                    if (!slotName) return '-';
                    return String(slotName).toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                },

                get filteredHistoryItems() {
                    let res = [...(this.matchHistoryItems || [])];

                    if (this.historyMonthFilter !== 'all') {
                        res = res.filter(r => String(r.report_date || '').startsWith(this.historyMonthFilter));
                    }

                    if (this.historyBranchFilter !== 'all') {
                        res = res.filter(r => String(r.branch_id || '') === String(this.historyBranchFilter));
                    }

                    if (this.historySearch.trim() !== '') {
                        const q = this.historySearch.toLowerCase();
                        res = res.filter(r =>
                            String(r.report_date || '').toLowerCase().includes(q) ||
                            String(r.branch_code || '').toLowerCase().includes(q) ||
                            (r.shifts || []).some(s =>
                                String(s.report_shift || '').toLowerCase().includes(q) ||
                                String(s.report_file_name || '').toLowerCase().includes(q) ||
                                String(s.analysis_file_name || '').toLowerCase().includes(q)
                            )
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
                },
            };
        }
    </script>
</x-layout>
