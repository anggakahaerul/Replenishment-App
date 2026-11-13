<x-layout title="Detail Analisa History">
    <div class="modern-page" x-data="{
        search: '',
        matchesSearch(sku, desc, src, dst) {
            if (!this.search) return true;
            const q = this.search.toLowerCase();
            return String(sku).toLowerCase().includes(q)
                || String(desc).toLowerCase().includes(q)
                || String(src).toLowerCase().includes(q)
                || String(dst).toLowerCase().includes(q);
        }
    }">
        <div style="margin-bottom: 12px;">
            <a href="{{ route('replenishment.analysis-history') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #64748B; text-decoration: none;">
                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke History Analisa
            </a>
        </div>

        {{-- ===== HEADER CARD ===== --}}
        <div class="modern-card" style="padding: 16px 20px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; background-color: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0;">
                        Analisa Replenishment
                    </span>
                    @if($import->branch || $import->warehouse)
                        <span class="badge-loc-dst" style="font-size: 10.5px;">
                            {{ $import->branch?->code ?? $import->warehouse }}
                        </span>
                    @endif
                    <span style="font-size: 11px; color: #64748B; font-weight: 500;">
                        {{ $import->report_date ?? '-' }} • {{ $import->imported_at ? $import->imported_at->format('d/m/Y H:i') : '-' }} WIB
                    </span>
                </div>
                <h1 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0;">{{ $import->file_name }}</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                @if($import->file_path)
                    <a href="{{ route('replenishment.history.download', $import->id) }}" style="display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 12px; font-size: 12px; font-weight: 600; color: #15803D; background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; text-decoration: none;">
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Unduh Excel
                    </a>
                @endif
                <a href="{{ route('replenishment.index') }}" style="display: inline-flex; align-items: center; height: 34px; padding: 0 14px; font-size: 12px; font-weight: 600; color: #ffffff; background-color: #0F172A; border-radius: 8px; text-decoration: none;">
                    Ke Halaman Matching
                </a>
            </div>
        </div>

        {{-- ===== DATA TABLE CARD ===== --}}
        <div class="modern-card">
            <div class="modern-card-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 700; color: #0F172A;">Rincian Item Analisa</span>
                    <span style="font-size: 11px; font-weight: 600; color: #475569; background-color: #F1F5F9; padding: 2px 8px; border-radius: 999px;">
                        {{ $import->analysisDetails()->count() ?: $import->row_count }} Baris
                    </span>
                </div>
                <div style="position: relative;">
                    <svg style="width: 13px; height: 13px; color: #94A3B8; position: absolute; left: 10px; top: 50%; transform: translateY(-50%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" x-model="search" placeholder="Filter SKU, produk, lokasi..." class="modern-search-input">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="modern-table">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 10%;">
                        <col style="width: 28%;">
                        <col style="width: 13%;">
                        <col style="width: 13%;">
                        <col style="width: 10%;">
                        <col style="width: 9%;">
                        <col style="width: 12%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="text-align: center;">Row</th>
                            <th>Inventory ID</th>
                            <th>Deskripsi Produk</th>
                            <th>From Location</th>
                            <th>To Location</th>
                            <th style="text-align: right;">Whole Qty</th>
                            <th style="text-align: right;">Loose Qty</th>
                            <th style="text-align: center;">Tag Validation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $row)
                            <tr x-show="matchesSearch(@js($row->item_no), @js($row->item_description ?? ''), @js($row->source_location_code ?? ''), @js($row->destination_location_code ?? ''))">
                                <td style="text-align: center; font-size: 12px !important; color: #94A3B8 !important; font-weight: 500;">
                                    {{ $row->row_number }}
                                </td>
                                <td style="font-weight: 700; color: #0F172A !important;">
                                    {{ $row->item_no }}
                                </td>
                                <td style="font-weight: 500; color: #1E293B !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $row->item_description }}">
                                    {{ $row->item_description }}
                                </td>
                                <td>
                                    <span class="badge-loc-src">{{ $row->source_location_code ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge-loc-dst">{{ $row->destination_location_code ?? '-' }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <span class="badge-qty-pill">
                                        <span>{{ (float)$row->whole_qty }}</span>
                                        <span style="font-size: 10.5px; font-weight: 600; color: #64748B;">{{ $row->whole_uom }}</span>
                                    </span>
                                </td>
                                <td style="text-align: right; color: #64748B !important; font-weight: 500;">
                                    {{ (float)$row->loose_qty }} <span style="font-size: 10.5px; color: #94A3B8;">{{ $row->loose_uom }}</span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row->tag_validation === 'Replenishment')
                                        <span style="display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background-color: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0;">
                                            {{ $row->tag_validation }}
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background-color: #F1F5F9; color: #475569; border: 1px solid #E2E8F0;">
                                            {{ $row->tag_validation ?? '-' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 32px !important; text-align: center; color: #94A3B8 !important;">Tidak ada data detail.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($details->hasPages())
                <div style="padding: 10px 16px; border-top: 1px solid #F1F5F9; background-color: #FAFAFB;">
                    {{ $details->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout>
