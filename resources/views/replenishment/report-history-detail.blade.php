<x-layout title="Detail Report History">
    <div class="p-6">
        <div class="flex items-center gap-2 mb-4">
            <a href="{{ route('replenishment.report-history') }}" class="inline-flex items-center gap-1.5 text-[12px] font-medium text-[#64748B] hover:text-[#0F172A] transition-smooth">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke History Report
            </a>
        </div>

        {{-- ===== HEADER ===== --}}
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-5 mb-5 flex items-center justify-between">
            <div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-[#EFF6FF] text-[#2563EB] mb-1">
                    Replenishment Report
                </span>
                <h1 class="text-[20px] font-bold text-[#0F172A]">{{ $import->file_name }}</h1>
                <p class="text-[12px] text-[#64748B] mt-0.5">
                    Tanggal: <span class="font-semibold text-[#0F172A]">{{ $import->report_date ?? '-' }}</span> • 
                    Di-import pada: <span class="font-semibold text-[#0F172A]">{{ $import->imported_at ? $import->imported_at->format('d/m/Y H:i') : '-' }} WIB</span> • 
                    Total: <span class="font-semibold text-[#0F172A]">{{ $import->replenishmentDetails()->count() }} baris</span>
                </p>
            </div>
            <a href="{{ route('replenishment.index') }}" class="px-4 py-2 text-[12px] font-semibold text-white bg-[#0F172A] hover:bg-[#1E293B] rounded-lg transition-smooth shadow-sm">
                Ke Halaman Matching
            </a>
        </div>

        {{-- ===== DATA TABLE ===== --}}
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F8FAFC]">
                <h3 class="text-[13px] font-semibold text-[#0F172A]">Rincian Data Replenishment Report</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0]">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider w-[50px]">ROW</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">SKU</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">DESKRIPSI PRODUK</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">LOKASI SUMBER</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">LOKASI TUJUAN</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">IN LOCATION</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">MAX LEVEL</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">REFILL NEEDED</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $row)
                            <tr class="border-b border-[#F1F5F9] hover:bg-[#F8FAFC] transition-smooth">
                                <td class="px-4 py-3 text-[12px] text-[#64748B]">{{ $row->row_number }}</td>
                                <td class="px-4 py-3 text-[12px] font-semibold text-[#0F172A]">{{ $row->item_no }}</td>
                                <td class="px-4 py-3 text-[12px] text-[#0F172A]">{{ $row->item_description }}</td>
                                <td class="px-4 py-3 text-[12px]">
                                    <div class="font-medium text-[#2563EB]">{{ $row->source_location_code ?? '-' }}</div>
                                    @if(!empty($row->suggested_sources) && count($row->suggested_sources) > 1)
                                        <div class="text-[10px] text-[#64748B] flex items-center gap-1 mt-0.5" title="{{ implode(', ', $row->suggested_sources) }}">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#3B82F6]"></span>
                                            +{{ count($row->suggested_sources) - 1 }} lokasi pool
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-[12px] font-medium text-[#0F172A]">{{ $row->destination_location_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-[12px] text-right text-[#64748B]">{{ (float)$row->in_location_qty }}</td>
                                <td class="px-4 py-3 text-[12px] text-right text-[#64748B]">{{ (float)$row->max_level }}</td>
                                <td class="px-4 py-3 text-[12px] font-bold text-right text-[#0F172A]">{{ (float)$row->quantity }} {{ $row->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[13px] text-[#64748B]">Tidak ada data detail.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($details->hasPages())
                <div class="px-5 py-3 border-t border-[#E2E8F0]">
                    {{ $details->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout>
