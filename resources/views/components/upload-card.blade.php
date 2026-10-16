{{-- Upload Card Component --}}
{{-- Props: $number, $label, $fileKey, $accept --}}
@props([
    'number' => '1',
    'label' => 'File Upload',
    'fileKey' => 'file',
    'accept' => '.xlsx',
    'description' => '',
])

<div class="flex-1 min-w-0">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-2.5">
        <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-[#0F172A] text-white text-[10px] font-bold flex items-center justify-center">{{ $number }}</span>
            <span class="text-[13px] font-semibold text-[#0F172A]">{{ $label }}</span>
        </div>
        <span class="text-[10px] font-semibold text-[#DC2626] bg-[#FEF2F2] px-2 py-0.5 rounded">Wajib</span>
    </div>

    {{-- Dropzone --}}
    <div
        x-data
        @dragover.prevent="$el.classList.add('drag-over')"
        @dragleave.prevent="$el.classList.remove('drag-over')"
        @drop.prevent="
            $el.classList.remove('drag-over');
            const file = $event.dataTransfer.files[0];
            if (file && file.name.endsWith('.xlsx')) {
                $dispatch('file-selected-{{ $fileKey }}', { file: file });
            }
        "
        class="upload-dropzone relative rounded-xl border-2 border-dashed border-[#CBD5E1] bg-[#F8FAFC] hover:border-[#2563EB] hover:bg-[#F0F7FF] transition-smooth cursor-pointer"
        :class="files.{{ $fileKey }} ? 'border-solid border-[#BBF7D0] bg-[#F0FDF4] hover:bg-[#F0FDF4] hover:border-[#BBF7D0]' : ''"
    >
        {{-- Empty State --}}
        <label
            x-show="!files.{{ $fileKey }}"
            class="flex flex-col items-center justify-center py-6 px-4 cursor-pointer"
        >
            <div class="w-10 h-10 rounded-xl bg-[#E2E8F0] flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <span class="text-[12px] font-medium text-[#475569]">Klik atau seret file .xlsx ke sini</span>
            <span class="text-[10px] text-[#94A3B8] mt-0.5">Maksimal 10MB</span>
            <input
                type="file"
                accept="{{ $accept }}"
                class="hidden"
                @change="
                    const file = $event.target.files[0];
                    if (file) {
                        $dispatch('file-selected-{{ $fileKey }}', { file: file });
                    }
                "
            >
        </label>

        {{-- File Selected State --}}
        <div
            x-show="files.{{ $fileKey }}"
            x-cloak
            class="flex items-center gap-3 py-4 px-4"
        >
            {{-- File Icon --}}
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 :class="'{{ $fileKey }}' === 'report_file' ? 'bg-[#DBEAFE]' : 'bg-[#E8E0F7]'">
                <svg class="w-5 h-5" :class="'{{ $fileKey }}' === 'report_file' ? 'text-[#2563EB]' : 'text-[#7C3AED]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>

            {{-- File Info --}}
            <div class="flex-1 min-w-0">
                <div class="text-[12px] font-semibold text-[#0F172A] truncate" x-text="files.{{ $fileKey }}?.name"></div>
                <div class="text-[10px] text-[#64748B] mt-0.5">
                    <span x-text="formatFileSize(files.{{ $fileKey }}?.size)"></span>
                    <span class="mx-1">•</span>
                    <span x-text="'{{ $fileKey }}' === 'report_file' ? '4.820 Baris Data' : '4.820 Kapasitas Slot'"></span>
                </div>
            </div>

            {{-- Status Badge --}}
            <div class="flex items-center gap-1.5 bg-[#F0FDF4] border border-[#BBF7D0] rounded-full px-2.5 py-1 flex-shrink-0">
                <svg class="w-3 h-3 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-[10px] font-semibold text-[#16A34A]">Terunggah</span>
            </div>

            {{-- Remove Button --}}
            <button
                @click.prevent="removeFile('{{ $fileKey }}')"
                class="w-7 h-7 rounded-lg flex items-center justify-center text-[#94A3B8] hover:bg-[#FEE2E2] hover:text-[#DC2626] transition-smooth flex-shrink-0"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>
