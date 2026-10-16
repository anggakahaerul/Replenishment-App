{{-- Status Badge Component --}}
@props([
    'status' => 'matched',
])

@php
    $config = match($status) {
        'matched' => [
            'label' => 'Matched',
            'bg' => 'bg-[#F0FDF4]',
            'text' => 'text-[#16A34A]',
            'dot' => 'bg-[#22C55E]',
            'icon' => 'check',
        ],
        'unmatch' => [
            'label' => 'Unmatch',
            'bg' => 'bg-[#FEF2F2]',
            'text' => 'text-[#DC2626]',
            'dot' => 'bg-[#EF4444]',
            'icon' => 'dot',
        ],
        'melebihi_poc' => [
            'label' => 'Melebihi POC',
            'bg' => 'bg-[#FEF3C7]',
            'text' => 'text-[#D97706]',
            'dot' => 'bg-[#F59E0B]',
            'icon' => 'warning',
        ],
        default => [
            'label' => 'Unknown',
            'bg' => 'bg-[#F1F5F9]',
            'text' => 'text-[#64748B]',
            'dot' => 'bg-[#94A3B8]',
            'icon' => 'dot',
        ],
    };
@endphp

<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold {{ $config['bg'] }} {{ $config['text'] }}">
    @if($config['icon'] === 'check')
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    @elseif($config['icon'] === 'warning')
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    @else
        <span class="w-2 h-2 rounded-full {{ $config['dot'] }}"></span>
    @endif
    {{ $config['label'] }}
</span>
