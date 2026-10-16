<x-layout title="Detail Analisa History">
    <div class="p-6">
        <div class="flex items-center gap-2 mb-4">
            <a href="{{ route('replenishment.analysis-history') }}" class="inline-flex items-center gap-1.5 text-[12px] font-medium text-[#64748B] hover:text-[#0F172A] transition-smooth">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke History Analisa
            </a>
        </div>

        {{-- ===== HEADER ===== --}}
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-5 mb-5 flex items-center justify-between">
            <div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-[#F0FDF4] text-[#16A34A] mb-1">
                    Analisa Replenishment
                </span>
                <h1 class="text-[20px] font-bold text-[#0F172A]">{{ $import->file_name }}</h1>
                <p class="text-[12px] text-[#64748B] mt-0.5">
                    Tanggal: <span class="font-semibold text-[#0F172A]">{{ $import->report_date ?? '-' }}</span> • 
                    Di-import pada: <span class="font-semibold text-[#0F172A]">{{ $import->imported_at ? $import->imported_at->format('d/m/Y H:i') : '-' }} WIB</span> • 
                    Total: <span class="font-semibold text-[#0F172A]">{{ $import->analysisDetails()->count() }} baris</span>
                </p>
            </div>
            <a href="{{ route('replenishment.index') }}" class="px-4 py-2 text-[12px] font-semibold text-white bg-[#0F172A] hover:bg-[#1E293B] rounded-lg transition-smooth shadow-sm">
                Ke Halaman Matching
            </a>
        </div>

        {{-- ===== DATA TABLE ===== --}}
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F8FAFC]">
                <h3 class="text-[13px] font-semibold text-[#0F172A]">Rincian Data Analisa Replenishment</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0]">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider w-[50px]">ROW</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">INVENTORY ID</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">DESKRIPSI PRODUK</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">FROM LOCATION</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">TO LOCATION</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">WHOLE QTY</th>
                            <th class="px-4 py-3 text-right text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">LOOSE QTY</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold text-[#64748B] uppercase tracking-wider">TAG VALIDATION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $row)
                            <tr class="border-b border-[#F1F5F9] hover:bg-[#F8FAFC] transition-smooth">
                                <td class="px-4 py-3 text-[12px] text-[#64748B]">{{ $row->row_number }}</td>
                                <td class="px-4 py-3 text-[12px] font-semibold text-[#0F172A]">{{ $row->item_no }}</td>
                                <td class="px-4 py-3 text-[12px] text-[#0F172A]">{{ $row->item_description }}</td>
                                <td class="px-4 py-3 text-[12px] font-medium text-[#2563EB]">{{ $row->source_location_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-[12px] font-medium text-[#0F172A]">{{ $row->destination_location_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-[12px] font-bold text-right text-[#0F172A]">{{ (float)$row->whole_qty }} {{ $row->whole_uom }}</td>
                                <td class="px-4 py-3 text-[12px] text-right text-[#64748B]">{{ (float)$row->loose_qty }} {{ $row->loose_uom }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $row->tag_validation === 'Replenishment' ? 'bg-[#F0FDF4] text-[#16A34A]' : 'bg-[#F1F5F9] text-[#64748B]' }}">
                                        {{ $row->tag_validation ?? '-' }}
                                    </span>
                                </td>
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
