<x-layout title="Detail Hasil Matching — {{ $reportDate }}">
    <style>
        .md-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        @media (max-width: 900px) {
            .md-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .md-stat-card {
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 14px 16px;
            background-color: #FFFFFF;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .md-stat-card:hover {
            border-color: #CBD5E1;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
        }
        .md-shift-tabs {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #F1F5F9;
            padding: 5px;
            border-radius: 9999px;
            border: 1px solid #E2E8F0;
        }
        .md-shift-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 32px;
            padding: 0 16px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            background-color: transparent;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .md-shift-pill:hover {
            color: #0F172A;
            background-color: rgba(255, 255, 255, 0.6);
        }
        .md-shift-pill.active {
            background-color: #2563EB;
            color: #FFFFFF;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
        }
        .md-status-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: help;
        }
        .md-status-tip {
            position: absolute;
            right: calc(100% + 8px);
            top: 50%;
            transform: translateY(-50%) translateX(4px);
            width: 310px;
            padding: 10px 12px;
            border-radius: 10px;
            background-color: #0F172A;
            color: #F8FAFC;
            font-size: 11.5px;
            font-weight: 400;
            line-height: 1.45;
            text-align: left;
            white-space: normal;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.35);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.15s ease, transform 0.15s ease;
            z-index: 50;
        }
        .md-status-wrap:hover .md-status-tip {
            opacity: 1;
            visibility: visible;
            transform: translateY(-50%) translateX(0);
        }
    </style>

    <div class="modern-page" x-data="matchDetailApp(@js($rows), @js($shiftsPresent))">
        {{-- ===== HEADER ===== --}}
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; flex-wrap: wrap;">
            <div>
                <a href="{{ route('replenishment.index') }}"
                   style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #2563EB; text-decoration: none; margin-bottom: 8px;">
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Matching Report
                </a>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h1 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0;">
                        Detail Hasil Matching
                    </h1>
                    <span style="font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background-color: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;">
                        {{ $reportDate }}
                    </span>
                    @if($branch)
                        <span style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background-color: #0F172A; color: #FFFFFF;">
                            {{ $branch->code }} {{ $branch->name && $branch->name !== $branch->code ? '• ' . $branch->name : '' }}
                        </span>
                    @endif
                    @foreach($shiftsPresent as $sp)
                        <span style="font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; background-color: #F1F5F9; color: #334155; border: 1px solid #CBD5E1;">
                            {{ $sp }}
                        </span>
                    @endforeach
                </div>
                <p style="font-size: 12px; color: #64748B; margin: 4px 0 0 0;">
                    Menampilkan gabungan hasil rekonsiliasi seluruh shift pada tanggal {{ $reportDate }}.
                </p>
            </div>
        </div>

        {{-- ===== 4 SUMMARY CARDS ===== --}}
        <div class="md-summary-grid">
            <div @click="statusFilter = 'all'; currentPage = 1"
                 class="md-stat-card"
                 :style="statusFilter === 'all' ? 'border-color: #0F172A; background-color: #F8FAFC;' : ''">
                <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.04em;">Total Task / Baris</div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 8px;">
                    <span style="font-size: 22px; font-weight: 800; color: #0F172A;" x-text="summary.totalTasks.toLocaleString('id-ID')"></span>
                    <span style="font-size: 11px; font-weight: 600; color: #64748B;" x-text="'(' + summary.totalRows.toLocaleString('id-ID') + ' baris)'"></span>
                </div>
            </div>

            <div @click="statusFilter = (statusFilter === 'MATCH' ? 'all' : 'MATCH'); currentPage = 1"
                 class="md-stat-card"
                 :style="statusFilter === 'MATCH' ? 'border-color: #16A34A; background-color: #F0FDF4;' : ''">
                <div style="font-size: 11px; font-weight: 700; color: #16A34A; text-transform: uppercase; letter-spacing: 0.04em;">Match</div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 8px;">
                    <span style="font-size: 22px; font-weight: 800; color: #16A34A;" x-text="summary.match.toLocaleString('id-ID')"></span>
                    <span style="font-size: 12px; font-weight: 700; color: #16A34A;" x-text="'(' + summary.matchRate + '%)'"></span>
                </div>
            </div>

            <div @click="statusFilter = (statusFilter === 'PENDING' ? 'all' : 'PENDING'); currentPage = 1"
                 class="md-stat-card"
                 :style="statusFilter === 'PENDING' ? 'border-color: #DC2626; background-color: #FEF2F2;' : ''">
                <div style="font-size: 11px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 0.04em;">Pending</div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 8px;">
                    <span style="font-size: 22px; font-weight: 800; color: #DC2626;" x-text="summary.pending.toLocaleString('id-ID')"></span>
                    <span style="font-size: 11px; font-weight: 600; color: #DC2626;">Baris</span>
                </div>
            </div>

            <div @click="statusFilter = (statusFilter === 'OTHERS' ? 'all' : 'OTHERS'); currentPage = 1"
                 class="md-stat-card"
                 :style="statusFilter === 'OTHERS' ? 'border-color: #D97706; background-color: #FFFBEB;' : ''">
                <div style="font-size: 11px; font-weight: 700; color: #D97706; text-transform: uppercase; letter-spacing: 0.04em;">Melebihi POC / Transfer</div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 8px;">
                    <span style="font-size: 22px; font-weight: 800; color: #D97706;" x-text="summary.others.toLocaleString('id-ID')"></span>
                    <span style="font-size: 11px; font-weight: 600; color: #D97706;">Baris</span>
                </div>
            </div>
        </div>

        {{-- ===== TABLE CARD ===== --}}
        <div class="modern-card" style="padding: 20px;">
            {{-- Filter Bar --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                {{-- Shift Tabs --}}
                <div class="md-shift-tabs">
                    <button type="button"
                            @click="shiftFilter = 'all'; currentPage = 1"
                            class="md-shift-pill"
                            :class="shiftFilter === 'all' ? 'active' : ''">
                        Semua Shift
                    </button>
                    <template x-for="sh in shiftsPresent" :key="sh">
                        <button type="button"
                                @click="shiftFilter = sh; currentPage = 1"
                                class="md-shift-pill"
                                :class="shiftFilter === sh ? 'active' : ''"
                                x-text="sh"></button>
                    </template>
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px;">
                    {{-- Search --}}
                    <div style="position: relative; width: 240px;">
                        <svg style="width: 14px; height: 14px; color: #94A3B8; position: absolute; left: 11px; top: 50%; transform: translateY(-50%); pointer-events: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text"
                               x-model="search"
                               @input="currentPage = 1"
                               placeholder="Cari Item No, Nama, Lokasi..."
                               style="width: 100%; height: 34px; padding: 0 10px 0 32px; font-size: 12px; border: 1px solid #E2E8F0; border-radius: 9px; background-color: #F8FAFC; color: #0F172A; outline: none; box-sizing: border-box;">
                    </div>

                    {{-- Category Filter --}}
                    <select x-model="categoryFilter" @change="currentPage = 1"
                            style="height: 34px; padding: 0 10px; font-size: 12px; font-weight: 600; border: 1px solid #E2E8F0; border-radius: 9px; background-color: #FFFFFF; color: #0F172A; outline: none;">
                        <option value="all">Semua Kategori</option>
                        <option value="DRY">DRY</option>
                        <option value="FROZEN">FROZEN</option>
                    </select>

                    {{-- Status Filter --}}
                    <select x-model="statusFilter" @change="currentPage = 1"
                            style="height: 34px; padding: 0 10px; font-size: 12px; font-weight: 600; border: 1px solid #E2E8F0; border-radius: 9px; background-color: #FFFFFF; color: #0F172A; outline: none;">
                        <option value="all">Semua Status</option>
                        <option value="MATCH">MATCH</option>
                        <option value="PENDING">PENDING</option>
                        <option value="OTHERS">MELEBIHI POC / TRANSFER / LAINNYA</option>
                    </select>
                </div>
            </div>

            {{-- Table --}}
            <div style="overflow-x: auto; border: 1px solid #E2E8F0; border-radius: 12px;">
                <table class="modern-table" style="table-layout: fixed; min-width: 1560px; width: 100%;">
                    <thead>
                        <tr>
                            <th style="text-align: center; width: 50px; white-space: nowrap;">No.</th>
                            <th style="width: 90px; white-space: nowrap;">Shift</th>
                            <th style="width: 85px; white-space: nowrap;">Kategori</th>
                            <th style="width: 95px; white-space: nowrap;">Item No</th>
                            <th style="width: 260px; white-space: nowrap;">Nama Barang</th>
                            <th style="width: 120px; white-space: nowrap;">From (Report)</th>
                            <th style="width: 120px; white-space: nowrap;">From (Analisa)</th>
                            <th style="width: 120px; white-space: nowrap;">To (Report)</th>
                            <th style="width: 120px; white-space: nowrap;">To (Analisa)</th>
                            <th style="text-align: right; width: 105px; white-space: nowrap;">Refill Needed</th>
                            <th style="text-align: right; width: 95px; white-space: nowrap;">Qty Analisa</th>
                            <th style="text-align: center; width: 115px; white-space: nowrap;">Status</th>
                            <th style="width: 130px; white-space: nowrap;">Created At</th>
                            <th style="width: 120px; white-space: nowrap;">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="paginatedRows.length === 0">
                            <tr>
                                <td colspan="14" style="padding: 36px 20px; text-align: center; color: #94A3B8;">
                                    Tidak ada data matching yang sesuai dengan filter.
                                </td>
                            </tr>
                        </template>
                        <template x-for="(row, idx) in paginatedRows" :key="row.id ?? idx">
                            <tr>
                                <td style="text-align: center; font-size: 12px; color: #94A3B8; font-weight: 600;"
                                    x-text="(currentPage - 1) * perPage + idx + 1"></td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700;"
                                          :style="row.report_shift === 'SHIFT 1' ? 'background-color: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;' : 'background-color: #F5F3FF; color: #6D28D9; border: 1px solid #DDD6FE;'"
                                          x-text="row.report_shift"></span>
                                </td>
                                <td>
                                    <span style="font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 6px; background-color: #F1F5F9; color: #334155;"
                                          x-text="row.category"></span>
                                </td>
                                <td style="font-family: monospace; font-size: 12px; font-weight: 700; color: #0F172A;" x-text="row.item_no"></td>
                                <td style="font-size: 12px; color: #1E293B; font-weight: 500;" x-text="row.item_name"></td>
                                <td style="font-size: 12px; font-weight: 600; color: #334155;" x-text="row.from_location_report"></td>
                                <td style="font-size: 12px; font-weight: 600; color: #334155;" x-text="row.from_location_analysis"></td>
                                <td style="font-size: 12px; font-weight: 700; color: #0F172A;" x-text="row.to_location_report"></td>
                                <td style="font-size: 12px; font-weight: 700; color: #0F172A;" x-text="row.to_location_analysis"></td>
                                <td style="text-align: right; font-size: 12px; font-weight: 700; color: #0F172A;"
                                    x-text="row.qty_report !== null ? Number(row.qty_report).toLocaleString('id-ID') : '-'"></td>
                                <td style="text-align: right; font-size: 12px; font-weight: 700; color: #0F172A;"
                                    x-text="row.qty_analysis !== null ? Number(row.qty_analysis).toLocaleString('id-ID') : '-'"></td>
                                <td style="text-align: center;">
                                    <span class="md-status-wrap">
                                        <span style="display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 999px; font-size: 10.5px; font-weight: 700;"
                                              :style="statusBadgeStyle(row.status)"
                                              x-text="row.status"></span>
                                        <span class="md-status-tip">
                                            <strong style="display: block; color: #FFFFFF; margin-bottom: 3px;" x-text="'Kenapa ' + row.status + '?'"></strong>
                                            <span style="display: block; color: #E2E8F0;" x-text="statusExplanation(row.status)"></span>
                                            <span style="display: block; margin-top: 7px; padding-top: 7px; border-top: 1px solid rgba(148, 163, 184, 0.25); font-size: 11px; color: #CBD5E1; line-height: 1.5;"
                                                  x-html="statusComparisonHtml(row)"></span>
                                        </span>
                                    </span>
                                </td>
                                <td style="font-size: 12px; color: #475569; white-space: nowrap;" x-text="row.source_created_at || '-'"></td>
                                <td style="font-size: 12px; font-weight: 600; color: #334155;" x-text="row.source_created_by || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 14px; flex-wrap: wrap;">
                <div style="font-size: 12px; color: #64748B;">
                    Menampilkan <span style="font-weight: 700; color: #0F172A;" x-text="filteredRows.length"></span> baris hasil matching
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button"
                            @click="if (currentPage > 1) currentPage--"
                            :disabled="currentPage <= 1"
                            style="height: 32px; padding: 0 12px; border-radius: 8px; border: 1px solid #E2E8F0; background-color: #FFFFFF; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer;"
                            :style="currentPage <= 1 ? 'opacity: 0.45; cursor: not-allowed;' : ''">
                        Sebelumnya
                    </button>
                    <span style="font-size: 12px; font-weight: 600; color: #475569; padding: 0 6px;"
                          x-text="'Hal ' + currentPage + ' / ' + totalPages"></span>
                    <button type="button"
                            @click="if (currentPage < totalPages) currentPage++"
                            :disabled="currentPage >= totalPages"
                            style="height: 32px; padding: 0 12px; border-radius: 8px; border: 1px solid #E2E8F0; background-color: #FFFFFF; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer;"
                            :style="currentPage >= totalPages ? 'opacity: 0.45; cursor: not-allowed;' : ''">
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function matchDetailApp(initialRows, shiftsPresent) {
            return {
                rows: initialRows || [],
                shiftsPresent: shiftsPresent || [],
                shiftFilter: 'all',
                categoryFilter: 'all',
                statusFilter: 'all',
                search: '',
                currentPage: 1,
                perPage: 25,

                get rowsByShift() {
                    if (this.shiftFilter === 'all') return this.rows;
                    return this.rows.filter(r => r.report_shift === this.shiftFilter);
                },

                get summary() {
                    const base = this.rowsByShift;
                    const totalRows = base.length;
                    const taskRows = base.filter(r => r.counts_as_task);
                    const totalTasks = taskRows.length;
                    const match = base.filter(r => r.status === 'MATCH').length;
                    const pending = base.filter(r => r.status === 'PENDING').length;
                    const others = base.filter(r => r.status !== 'MATCH' && r.status !== 'PENDING').length;
                    const matchRate = totalTasks > 0 ? ((match / totalTasks) * 100).toFixed(1) : '0.0';
                    return { totalRows, totalTasks, match, pending, others, matchRate };
                },

                get filteredRows() {
                    const q = this.search.trim().toLowerCase();
                    return this.rowsByShift.filter(r => {
                        if (this.categoryFilter !== 'all' && r.category !== this.categoryFilter) return false;
                        if (this.statusFilter === 'MATCH' && r.status !== 'MATCH') return false;
                        if (this.statusFilter === 'PENDING' && r.status !== 'PENDING') return false;
                        if (this.statusFilter === 'OTHERS' && (r.status === 'MATCH' || r.status === 'PENDING')) return false;
                        if (q !== '') {
                            const hay = `${r.item_no} ${r.item_name} ${r.from_location_report} ${r.from_location_analysis} ${r.to_location_report} ${r.to_location_analysis} ${r.source_created_by || ''}`.toLowerCase();
                            if (!hay.includes(q)) return false;
                        }
                        return true;
                    });
                },

                get totalPages() {
                    return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
                },

                get paginatedRows() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                statusBadgeStyle(status) {
                    if (status === 'MATCH') return 'background-color: #DCFCE7; color: #16A34A; border: 1px solid #BBF7D0;';
                    if (status === 'PENDING') return 'background-color: #FEE2E2; color: #DC2626; border: 1px solid #FECACA;';
                    if (status === 'MELEBIHI POC') return 'background-color: #FEF3C7; color: #B45309; border: 1px solid #FDE68A;';
                    return 'background-color: #DBEAFE; color: #2563EB; border: 1px solid #BFDBFE;';
                },

                statusExplanation(status) {
                    const map = {
                        'MATCH': 'Item, lokasi asal (From), dan lokasi tujuan (To) di Analisa cocok dengan daftar task di Replenishment Report.',
                        'PENDING': 'Task dari Replenishment Report ini belum ketemu pasangannya di file Analisa (belum dikerjakan atau belum masuk di file Analisa).',
                        'MELEBIHI POC': 'Saran sumber di Report melebihi kebutuhan refill (Refill Needed), sedangkan kebutuhan untuk item & lokasi tujuan tersebut sudah terpenuhi.',
                        'INITIATIVE': 'Tag di Analisa bertuliskan Replenishment, tapi kombinasi item, From, dan To tidak ada di daftar task Report (dikerjakan di luar saran Report).',
                        'PUT AWAY': 'Tag di Analisa bukan Replenishment dan lokasi asalnya dari RCVDOCK (aktivitas menaruh barang masuk dari dock penerimaan).',
                        'TRANSFER': 'Tag di Analisa bukan Replenishment dan lokasi asalnya bukan RCVDOCK (aktivitas pindah stok biasa antar lokasi).',
                        'DUPLICATE RAW': 'Kombinasi item, From, dan To ini ada di Report, tapi kuota task-nya sudah habis diklaim oleh baris Analisa sebelumnya.'
                    };
                    return map[status] || 'Status hasil pencocokan antara Replenishment Report dan Analisa Replenishment.';
                },

                statusComparisonHtml(row) {
                    const esc = (v) => String(v ?? '-').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const qtyRep = row.qty_report !== null && row.qty_report !== undefined ? Number(row.qty_report).toLocaleString('id-ID') : '-';
                    const qtyAna = row.qty_analysis !== null && row.qty_analysis !== undefined ? Number(row.qty_analysis).toLocaleString('id-ID') : '-';
                    const item = esc(row.item_no || '-');
                    const fRep = esc(row.from_location_report || '-');
                    const tRep = esc(row.to_location_report || '-');
                    const fAna = esc(row.from_location_analysis || '-');
                    const tAna = esc(row.to_location_analysis || '-');
                    const tag = esc(row.tag_validation || '-');

                    const box = (bg, border, content) =>
                        `<div style="background:${bg}; border:1px solid ${border}; border-radius:8px; padding:7px 9px; margin-top:4px;">${content}</div>`;

                    const pill = (text, color, bg) =>
                        `<span style="display:inline-block; padding:1px 6px; border-radius:5px; font-weight:700; color:${color}; background:${bg}; font-family:monospace; font-size:10.5px;">${text}</span>`;

                    const arrow = (color = '#94A3B8') =>
                        `<span style="color:${color}; font-weight:800; margin:0 3px;">→</span>`;

                    if (row.status === 'MATCH') {
                        return box('rgba(34, 197, 94, 0.12)', 'rgba(74, 222, 128, 0.35)', `
                            <div style="color:#4ADE80; font-weight:700; margin-bottom:4px;">✓ Item ${item} Cocok</div>
                            <div style="margin-bottom:3px;">
                                <span style="color:#86EFAC; font-weight:600;">Report:</span>
                                ${pill(fRep, '#DCFCE7', 'rgba(34,197,94,0.22)')}${arrow('#4ADE80')}${pill(tRep, '#DCFCE7', 'rgba(34,197,94,0.22)')}
                                <span style="color:#86EFAC;">(${qtyRep})</span>
                            </div>
                            <div>
                                <span style="color:#86EFAC; font-weight:600;">Analisa:</span>
                                ${pill(fAna, '#DCFCE7', 'rgba(34,197,94,0.22)')}${arrow('#4ADE80')}${pill(tAna, '#DCFCE7', 'rgba(34,197,94,0.22)')}
                                <span style="color:#86EFAC;">(${qtyAna})</span>
                            </div>
                        `);
                    }

                    if (row.status === 'PENDING') {
                        return box('rgba(239, 68, 68, 0.12)', 'rgba(248, 113, 113, 0.35)', `
                            <div style="color:#FCA5A5; font-weight:700; margin-bottom:4px;">Item ${item} Belum Dikerjakan</div>
                            <div style="margin-bottom:3px;">
                                <span style="color:#FECACA; font-weight:600;">Report:</span>
                                ${pill(fRep, '#FEE2E2', 'rgba(239,68,68,0.22)')}${arrow('#F87171')}${pill(tRep, '#FEE2E2', 'rgba(239,68,68,0.22)')}
                                <span style="color:#FECACA;">(${qtyRep})</span>
                            </div>
                            <div style="color:#F87171; font-weight:600;">Analisa: Belum ada transaksi yang cocok</div>
                        `);
                    }

                    if (row.status === 'MELEBIHI POC') {
                        return box('rgba(245, 158, 11, 0.12)', 'rgba(251, 191, 36, 0.35)', `
                            <div style="color:#FCD34D; font-weight:700; margin-bottom:4px;">Item ${item} Kelebihan Saran</div>
                            <div style="margin-bottom:3px;">
                                <span style="color:#FDE68A; font-weight:600;">Saran:</span>
                                ${pill(fRep, '#FEF3C7', 'rgba(245,158,11,0.22)')}${arrow('#FBBF24')}${pill(tRep, '#FEF3C7', 'rgba(245,158,11,0.22)')}
                                <span style="color:#FDE68A;">(${qtyRep})</span>
                            </div>
                            <div style="color:#FDE68A;">Kebutuhan ${pill(tRep, '#FEF3C7', 'rgba(245,158,11,0.2)')} sudah terpenuhi.</div>
                        `);
                    }

                    if (row.status === 'PUT AWAY') {
                        return box('rgba(59, 130, 246, 0.12)', 'rgba(96, 165, 250, 0.35)', `
                            <div style="color:#93C5FD; font-weight:700; margin-bottom:4px;">Put Away Item ${item}</div>
                            <div style="margin-bottom:3px;">
                                ${pill(fAna, '#DBEAFE', 'rgba(59,130,246,0.25)')}${arrow('#60A5FA')}${pill(tAna, '#DBEAFE', 'rgba(59,130,246,0.25)')}
                                <span style="color:#BFDBFE;">(${qtyAna})</span>
                            </div>
                            <div style="color:#BFDBFE;">Dari dock penerimaan, Tag: <strong style="color:#FFFFFF;">${tag}</strong></div>
                        `);
                    }

                    if (row.status === 'TRANSFER') {
                        return box('rgba(139, 92, 246, 0.12)', 'rgba(167, 139, 250, 0.35)', `
                            <div style="color:#C4B5FD; font-weight:700; margin-bottom:4px;">Transfer Item ${item}</div>
                            <div style="margin-bottom:3px;">
                                ${pill(fAna, '#EDE9FE', 'rgba(139,92,246,0.25)')}${arrow('#A78BFA')}${pill(tAna, '#EDE9FE', 'rgba(139,92,246,0.25)')}
                                <span style="color:#DDD6FE;">(${qtyAna})</span>
                            </div>
                            <div style="color:#DDD6FE;">Pindah antar lokasi, Tag: <strong style="color:#FFFFFF;">${tag}</strong></div>
                        `);
                    }

                    if (row.status === 'INITIATIVE') {
                        return box('rgba(59, 130, 246, 0.12)', 'rgba(96, 165, 250, 0.35)', `
                            <div style="color:#93C5FD; font-weight:700; margin-bottom:4px;">Inisiatif Item ${item}</div>
                            <div style="margin-bottom:3px;">
                                <span style="color:#BFDBFE; font-weight:600;">Analisa:</span>
                                ${pill(fAna, '#DBEAFE', 'rgba(59,130,246,0.25)')}${arrow('#60A5FA')}${pill(tAna, '#DBEAFE', 'rgba(59,130,246,0.25)')}
                                <span style="color:#BFDBFE;">(${qtyAna})</span>
                            </div>
                            <div style="color:#BFDBFE;">Report: Tidak ada task ini (Tag: <strong style="color:#FFFFFF;">${tag}</strong>)</div>
                        `);
                    }

                    return box('rgba(168, 85, 247, 0.12)', 'rgba(192, 132, 252, 0.35)', `
                        <div style="color:#D8B4FE; font-weight:700; margin-bottom:4px;">Item ${item} (${esc(row.status)})</div>
                        <div style="margin-bottom:3px;">
                            <span style="color:#E9D5FF; font-weight:600;">Analisa:</span>
                            ${pill(fAna, '#F3E8FF', 'rgba(168,85,247,0.25)')}${arrow('#C084FC')}${pill(tAna, '#F3E8FF', 'rgba(168,85,247,0.25)')}
                            <span style="color:#E9D5FF;">(${qtyAna})</span>
                        </div>
                        <div style="color:#E9D5FF;">Kuota task ini di Report sudah penuh terpakai.</div>
                    `);
                }
            };
        }
    </script>
</x-layout>

