<x-layout title="Replenishment Matching">
    <div
        x-data="replenishmentApp()"
        @file-selected-report_file.window="handleFileSelect('report_file', $event.detail.file)"
        @file-selected-analysis_file.window="handleFileSelect('analysis_file', $event.detail.file)"
        class="p-6"
    >
        {{-- ===== PAGE HEADER ===== --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <div class="flex items-center gap-1.5 text-[10px] font-semibold text-[#64748B] tracking-wider uppercase mb-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    MANAJEMEN OPERASIONAL GUDANG
                </div>
                <h1 class="text-[22px] font-bold text-[#0F172A] leading-tight">Replenishment Matching</h1>
                <p class="text-[13px] text-[#64748B] mt-1">Upload dan rekonsiliasi data replenishment gudang</p>
            </div>

            {{-- Header Badges --}}
            <div class="flex items-center gap-2.5">
                {{-- Shift Badge --}}
                <div class="flex items-center gap-1.5 border border-[#E2E8F0] rounded-lg px-3 py-2 bg-white">
                    <span class="w-2 h-2 rounded-full bg-[#22C55E]"></span>
                    <span class="text-[11px] font-semibold text-[#0F172A]">Shift 2 •</span>
                    <span class="text-[11px] text-[#64748B]">Berjalan</span>
                </div>

                {{-- Update Time --}}
                <div class="flex items-center gap-1.5 border border-[#E2E8F0] rounded-lg px-3 py-2 bg-white">
                    <svg class="w-3.5 h-3.5 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-[11px] text-[#475569]">Pembaruan: <span class="font-semibold" x-text="currentTime">14:30</span> WIB</span>
                </div>

                {{-- Download Template --}}
                <button class="flex items-center gap-1.5 border border-[#E2E8F0] rounded-lg px-3 py-2 bg-white hover:bg-[#F8FAFC] transition-smooth">
                    <svg class="w-3.5 h-3.5 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="text-[11px] font-medium text-[#475569]">Unduh Template .xlsx</span>
                </button>
            </div>
        </div>

        {{-- ===== UPLOAD SECTION ===== --}}
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-5 mb-5">
            {{-- Two Upload Cards --}}
            <div class="flex gap-5 mb-4">
                <x-upload-card
                    number="1"
                    label="File Replenishment Report (.xlsx, .xls)"
                    file-key="report_file"
                    accept=".xlsx,.xls"
                />

                <x-upload-card
                    number="2"
                    label="File Analisa Replenishment (.xlsx, .xls)"
                    file-key="analysis_file"
                    accept=".xlsx,.xls"
                />
            </div>

            {{-- Warning Note --}}
            <div class="flex items-center justify-center gap-2 text-[11px] text-[#64748B] mb-4">
                <svg class="w-3.5 h-3.5 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pastikan kedua berkas telah diunggah dalam format .xlsx sebelum menjalankan proses matching.
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-center gap-3">
                {{-- Primary Button --}}
                <button
                    @click="startMatching()"
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
                    <span x-text="processing ? 'Memproses...' : 'Jalankan Rekonsiliasi'"></span>
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
                        <span class="text-[11px] text-[#64748B] ml-2" x-text="completed ? matchingData?.summary?.total_rows?.toLocaleString('id-ID') + ' baris berhasil dicocokkan dengan data slot gudang' : 'Sedang mencocokkan data...'"></span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
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
                            <span x-text="matchingData?.summary?.total_rows?.toLocaleString('id-ID')"></span>&nbsp;Baris Terproses
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Export Button --}}
                        <button class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[12px] font-medium text-[#475569] bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] transition-smooth">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Ekspor .xlsx
                        </button>

                        {{-- Refresh Button --}}
                        <button @click="refreshResults()" class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[12px] font-medium text-[#475569] bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] transition-smooth">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh
                        </button>
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
                                <option value="matched">Matched</option>
                                <option value="unmatch">Unmatch</option>
                                <option value="melebihi_poc">Melebihi POC</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <label class="text-[11px] text-[#64748B] font-medium">Zona:</label>
                            <select x-model="filterZone" class="text-[11px] border border-[#E2E8F0] rounded-lg px-2.5 py-1.5 bg-white text-[#475569] outline-none focus:border-[#2563EB] cursor-pointer">
                                <option value="all">Semua Status</option>
                                <option value="A">Zone A</option>
                                <option value="B">Zone B</option>
                                <option value="HB">High-Bay</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Results Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0]">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider w-[50px]">NO.</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider w-[70px]">SKU</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[140px]">NAMA PRODUK</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[120px]">FROM LOCATION (REPORT)</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[120px]">FROM LOCATION (ANALISA)</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[90px]">TO LOCATION</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[90px]">QTY REPLENISH</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[90px]">QTY ANALISA</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[100px]">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in filteredResults" :key="index">
                            <tr class="border-b border-[#F1F5F9] table-row-hover transition-smooth">
                                <td class="px-4 py-3.5 text-[12px] text-[#64748B]" x-text="row.no"></td>
                                <td class="px-4 py-3.5 text-[12px] font-semibold text-[#0F172A]" x-text="row.sku"></td>
                                <td class="px-4 py-3.5">
                                    <div class="text-[12px] text-[#0F172A]" x-text="row.nama_produk"></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-[12px] font-semibold" :class="row.status === 'unmatch' ? 'text-[#DC2626]' : 'text-[#2563EB]'" x-text="row.from_location_report"></div>
                                    <div class="text-[10px] text-[#94A3B8]" x-text="row.from_location_report_type"></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-[12px] font-semibold" :class="row.status === 'unmatch' ? 'text-[#DC2626]' : 'text-[#2563EB]'" x-text="row.from_location_analysis"></div>
                                    <div class="text-[10px] text-[#94A3B8]" x-text="row.from_location_analysis_type"></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-[12px] font-semibold text-[#0F172A]" x-text="row.to_location"></div>
                                    <div class="text-[10px] text-[#94A3B8]" x-text="row.to_location_type"></div>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <span class="text-[12px] font-medium" :class="row.status !== 'matched' ? 'text-[#DC2626]' : 'text-[#0F172A]'" x-text="row.qty_replenish + ' ' + row.qty_replenish_unit"></span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <span class="text-[12px] font-medium text-[#0F172A]" x-text="row.qty_analysis + ' ' + row.qty_analysis_unit"></span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    {{-- Inline status badge for Alpine.js dynamic rendering --}}
                                    <template x-if="row.status === 'matched'">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-[#F0FDF4] text-[#16A34A]">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Matched
                                        </span>
                                    </template>
                                    <template x-if="row.status === 'unmatch'">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-[#FEF2F2] text-[#DC2626]">
                                            <span class="w-2 h-2 rounded-full bg-[#EF4444]"></span>
                                            Unmatch
                                        </span>
                                    </template>
                                    <template x-if="row.status === 'melebihi_poc'">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-[#FEF3C7] text-[#D97706]">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                            </svg>
                                            Melebihi POC
                                        </span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-3 border-t border-[#E2E8F0] flex items-center justify-between">
                <div class="text-[11px] text-[#64748B]">
                    Menampilkan <span class="font-medium" x-text="matchingData?.results?.length || 0"></span>
                    dari <span class="font-medium" x-text="matchingData?.pagination?.total?.toLocaleString('id-ID') || 0"></span> baris
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
            return {
                // File state
                files: {
                    report_file: null,
                    analysis_file: null,
                },

                // Processing state
                processing: false,
                completed: initialData ? true : false,
                progressPercent: initialData ? 100 : 0,

                // Results
                matchingData: initialData,
                currentPage: 1,

                // Search & filters
                searchQuery: '',
                filterStatus: 'all',
                filterZone: 'all',

                // Notifications
                showNotification: false,
                notificationMessage: '',
                showError: false,
                errorMessage: '',

                // Time
                currentTime: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),

                // Computed
                get canStartMatching() {
                    return this.files.report_file && this.files.analysis_file;
                },

                get filteredResults() {
                    if (!this.matchingData?.results) return [];
                    let results = [...this.matchingData.results];

                    // Filter by search query
                    if (this.searchQuery) {
                        const q = this.searchQuery.toLowerCase();
                        results = results.filter(row =>
                            row.sku.toLowerCase().includes(q) ||
                            row.nama_produk.toLowerCase().includes(q) ||
                            row.from_location_report.toLowerCase().includes(q)
                        );
                    }

                    // Filter by status
                    if (this.filterStatus !== 'all') {
                        results = results.filter(row => row.status === this.filterStatus);
                    }

                    // Filter by zone
                    if (this.filterZone !== 'all') {
                        results = results.filter(row =>
                            row.from_location_report.startsWith(this.filterZone)
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
                        formData.append('report_file', this.files.report_file);
                        formData.append('analysis_file', this.files.analysis_file);

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
                        this.notificationMessage = `Data Replenishment Report & Analisa Replenishment telah berhasil di-import ke History. ${data.summary.total_rows.toLocaleString('id-ID')} baris diproses.`;
                        this.showNotification = true;
                        setTimeout(() => { this.showNotification = false; }, 5000);

                    } catch (error) {
                        clearInterval(progressInterval);
                        this.processing = false;
                        this.progressPercent = 0;
                        this.errorMessage = error.message || 'Terjadi kesalahan yang tidak diketahui.';
                        this.showError = true;
                        setTimeout(() => { this.showError = false; }, 5000);
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
            };
        }
    </script>
</x-layout>
