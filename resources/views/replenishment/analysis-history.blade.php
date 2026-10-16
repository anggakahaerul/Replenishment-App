<x-layout title="Analysis Replenishment History">
    <div class="p-6">
        {{-- ===== PAGE HEADER ===== --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <div class="flex items-center gap-1.5 text-[10px] font-semibold text-[#64748B] tracking-wider uppercase mb-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    MANAJEMEN OPERASIONAL GUDANG
                </div>
                <h1 class="text-[22px] font-bold text-[#0F172A] leading-tight">Analisa Replenishment Report History</h1>
                <p class="text-[13px] text-[#64748B] mt-1">Riwayat data Analisa Replenishment yang telah berhasil di-import ke sistem</p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('replenishment.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-semibold text-white bg-[#0F172A] hover:bg-[#1E293B] shadow-sm transition-smooth">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload & Matching Baru
                </a>
            </div>
        </div>

        @if($analyses->isEmpty())
            <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-[#F1F5F9] flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h2 class="text-[16px] font-semibold text-[#0F172A] mb-1">Belum ada data history</h2>
                <p class="text-[13px] text-[#64748B] mb-4">Import file Analisa Replenishment di halaman Matching Report untuk mulai mencatat history.</p>
                <a href="{{ route('replenishment.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-[13px] font-semibold text-white bg-[#0F172A] hover:bg-[#1E293B] transition-smooth">
                    Ke Halaman Matching
                </a>
            </div>
        @else
            <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-[#E2E8F0] flex items-center justify-between">
                    <div>
                        <h2 class="text-[15px] font-bold text-[#0F172A]">Daftar File Analisa Ter-import</h2>
                        <p class="text-[12px] text-[#64748B]">Total {{ $analyses->total() }} file Analisa Replenishment tersimpan di database</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0]">
                                <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider w-[50px]">NO.</th>
                                <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider min-w-[200px]">NAMA FILE</th>
                                <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">TANGGAL REPORT</th>
                                <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">TOTAL BARIS</th>
                                <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">WAKTU IMPORT</th>
                                <th class="px-4 py-3 text-center text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">STATUS</th>
                                <th class="px-4 py-3 text-center text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analyses as $index => $item)
                                <tr class="border-b border-[#F1F5F9] hover:bg-[#F8FAFC] transition-smooth">
                                    <td class="px-4 py-3.5 text-[12px] text-[#64748B]">{{ $analyses->firstItem() + $index }}</td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded bg-[#F1F5F9] flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-[13px] font-semibold text-[#0F172A]">{{ $item->file_name }}</div>
                                                <div class="text-[10px] text-[#94A3B8]">Hash: {{ substr($item->file_hash ?? '', 0, 10) }}...</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-[12px] text-[#475569]">{{ $item->report_date ?? '-' }}</td>
                                    <td class="px-4 py-3.5 text-[12px] font-semibold text-[#0F172A] text-right">
                                        {{ number_format($item->analysis_details_count ?? $item->row_count, 0, ',', '.') }} baris
                                    </td>
                                    <td class="px-4 py-3.5 text-[12px] text-[#64748B]">
                                        {{ $item->imported_at ? $item->imported_at->format('d/m/Y H:i') : '-' }} WIB
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-[#F0FDF4] text-[#16A34A] border border-[#BBF7D0]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#16A34A]"></span>
                                            Tersimpan
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <a href="{{ route('replenishment.history.show', $item->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-semibold text-[#2563EB] bg-[#EFF6FF] hover:bg-[#DBEAFE] transition-smooth">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Lihat Data
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($analyses->hasPages())
                    <div class="px-5 py-3 border-t border-[#E2E8F0]">
                        {{ $analyses->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layout>
