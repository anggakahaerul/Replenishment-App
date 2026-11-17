<x-layout title="Analisa Replenishment Report History">
    <div class="modern-page">
        {{-- ===== PAGE HEADER ===== --}}
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; flex-wrap: wrap;">
            <div>
                <h1 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0;">Analisa Replenishment History</h1>
                <p style="font-size: 12.5px; color: #64748B; margin: 3px 0 0 0;">Riwayat file Analisa Replenishment yang telah di-import ke sistem</p>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                {{-- Filter Cabang (Modern Dropdown) --}}
                @php
                    $selectedBranchObj = ($branches ?? collect())->firstWhere('id', (int)($selectedBranchId ?? 0));
                    $selectedBranchLabel = $selectedBranchObj
                        ? (strtolower(preg_replace('/[^a-z0-9]/i', '', $selectedBranchObj->code)) !== strtolower(preg_replace('/[^a-z0-9]/i', '', $selectedBranchObj->name))
                            ? $selectedBranchObj->code . ' • ' . $selectedBranchObj->name
                            : $selectedBranchObj->code)
                        : 'Semua Cabang';
                @endphp
                <form action="{{ route('replenishment.analysis-history') }}" method="GET" style="margin: 0;" x-data="{ open: false, selectedVal: '{{ $selectedBranchId ?? '' }}' }">
                    <input type="hidden" name="branch_id" x-model="selectedVal">
                    @if(auth()->user()?->hasGlobalBranchAccess())
                        <div class="modern-dd-wrap" @click.outside="open = false">
                            <button type="button"
                                    @click="open = !open"
                                    class="modern-dd-btn"
                                    :class="open ? 'modern-dd-btn-active' : ''">
                                <svg style="width: 13px; height: 13px; color: #64748B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                            <svg style="width: 13px; height: 13px; color: #64748B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span style="font-size: 11.5px; font-weight: 600; color: #64748B;">Cabang:</span>
                            <span style="font-size: 12px; font-weight: 700; color: #2563EB;">
                                {{ auth()->user()?->branch?->code ?? '-' }}
                            </span>
                        </div>
                    @endif
                </form>

                <a href="{{ route('replenishment.index') }}" style="display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; font-size: 12px; font-weight: 600; color: #ffffff; background-color: #0F172A; border-radius: 10px; text-decoration: none;">
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload & Matching Baru
                </a>
            </div>
        </div>

        @if($analyses->isEmpty())
            <div class="modern-card" style="padding: 48px; text-align: center;">
                <h2 style="font-size: 15px; font-weight: 700; color: #0F172A; margin: 0 0 4px 0;">Belum ada data history</h2>
                <p style="font-size: 12.5px; color: #64748B; margin: 0 0 16px 0;">Import file Analisa Replenishment di halaman Matching Report untuk mulai mencatat history.</p>
                <a href="{{ route('replenishment.index') }}" style="display: inline-flex; align-items: center; height: 34px; padding: 0 14px; font-size: 12px; font-weight: 600; color: #ffffff; background-color: #2563EB; border-radius: 8px; text-decoration: none;">
                    Ke Halaman Matching
                </a>
            </div>
        @else
            <div class="modern-card">
                <div class="modern-card-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: #0F172A;">Daftar File Analisa Ter-import</span>
                        <span style="font-size: 11px; font-weight: 600; color: #475569; background-color: #F1F5F9; padding: 2px 8px; border-radius: 999px;">
                            {{ $analyses->total() }} File
                        </span>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="modern-table">
                        <colgroup>
                            <col style="width: 5%;">
                            <col style="width: 25%;">
                            <col style="width: 11%;">
                            <col style="width: 12%;">
                            <col style="width: 11%;">
                            <col style="width: 14%;">
                            <col style="width: 10%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th style="text-align: center;">#</th>
                                <th>Nama File</th>
                                <th>Cabang</th>
                                <th>Tanggal Report</th>
                                <th style="text-align: right;">Total Baris</th>
                                <th>Waktu Import</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analyses as $index => $item)
                                <tr>
                                    <td style="text-align: center; font-size: 12px !important; color: #94A3B8 !important;">
                                        {{ $analyses->firstItem() + $index }}
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 34px; height: 34px; border-radius: 8px; background-color: #F0FDF4; color: #16A34A; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div style="font-size: 13px; font-weight: 700; color: #0F172A; line-height: 1.3;">{{ $item->file_name }}</div>
                                                <div style="font-size: 11px; color: #94A3B8; margin-top: 2px;">Hash: {{ substr($item->file_hash ?? '', 0, 10) }}...</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-loc-dst">{{ $item->branch?->code ?? ($item->warehouse ?? '-') }}</span>
                                    </td>
                                    <td style="color: #475569 !important; font-weight: 500;">
                                        {{ $item->report_date ?? '-' }}
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="badge-qty-pill">
                                            <span>{{ number_format($item->analysis_details_count ?: $item->row_count, 0, ',', '.') }}</span>
                                            <span style="font-size: 11px; font-weight: 500; color: #64748B;">baris</span>
                                        </span>
                                    </td>
                                    <td style="color: #64748B !important; font-size: 12.5px !important;">
                                        {{ $item->imported_at ? $item->imported_at->format('d/m/Y H:i') : '-' }} WIB
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background-color: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #16A34A;"></span>
                                            Tersimpan
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                            @if($item->has_file)
                                                <a href="{{ route('replenishment.history.download', $item->id) }}" style="display: inline-flex; align-items: center; gap: 4px; height: 30px; padding: 0 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600; color: #15803D; background-color: #F0FDF4; border: 1px solid #BBF7D0; text-decoration: none;">
                                                    Unduh
                                                </a>
                                            @endif
                                            <a href="{{ route('replenishment.history.show', $item->id) }}" style="display: inline-flex; align-items: center; gap: 4px; height: 30px; padding: 0 12px; border-radius: 6px; font-size: 11.5px; font-weight: 600; color: #2563EB; background-color: #EFF6FF; border: 1px solid #DBEAFE; text-decoration: none;">
                                                Lihat Data
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($analyses->hasPages())
                    <div style="padding: 10px 16px; border-top: 1px solid #F1F5F9; background-color: #FAFAFB;">
                        {{ $analyses->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layout>
