<x-layout title="Application Log">
    <style>
        /* Bulletproof layout styles for Application Log */
        .log-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .log-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .log-header-left {
            text-align: left;
        }

        .log-header-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .log-header-title {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
            margin: 0 0 4px 0;
            text-align: left;
        }

        .log-header-desc {
            font-size: 13px;
            color: #64748B;
            margin: 0;
            text-align: left;
        }

        /* 3-Column Stat Cards */
        .log-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            width: 100%;
        }

        @media (max-width: 860px) {
            .log-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .log-stat-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 86px;
            box-sizing: border-box;
        }

        .log-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .log-stat-value {
            font-size: 26px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1;
        }

        .log-stat-sub {
            font-size: 11px;
            color: #94A3B8;
            margin-top: 4px;
            font-weight: 500;
        }

        .log-stat-icon-box {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Filter Box */
        .log-filter-box {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
            width: 100%;
            box-sizing: border-box;
        }

        .log-filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            width: 100%;
        }

        .log-search-wrapper {
            position: relative;
            flex: 1;
            min-width: 240px;
        }

        .log-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: #94A3B8;
            pointer-events: none;
        }

        .log-input {
            width: 100%;
            height: 38px;
            font-size: 13px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background-color: #F8FAFC;
            color: #0F172A;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .log-input:focus {
            border-color: #2563EB;
            background-color: #ffffff;
        }

        .log-search-input {
            padding: 0 12px 0 36px;
        }

        .log-select {
            height: 38px;
            padding: 0 12px;
            font-size: 13px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background-color: #F8FAFC;
            color: #0F172A;
            box-sizing: border-box;
            min-width: 190px;
            outline: none;
        }

        .log-date-input {
            height: 38px;
            padding: 0 12px;
            font-size: 13px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background-color: #F8FAFC;
            color: #0F172A;
            box-sizing: border-box;
            min-width: 150px;
            outline: none;
        }

        .log-btn-primary {
            height: 38px;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            background-color: #0F172A;
            border: 1px solid #0F172A;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .log-btn-primary:hover {
            background-color: #1E293B;
            border-color: #1E293B;
        }

        .log-btn-reset {
            height: 38px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 500;
            color: #64748B;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
        }

        .log-btn-reset:hover {
            color: #0F172A;
        }

        /* Clean Table */
        .log-table-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .log-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .log-table th {
            background-color: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            padding: 12px 18px;
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .log-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .log-table tr:hover td {
            background-color: #F8FAFC;
        }

        .log-badge-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .log-badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .log-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .log-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #0F172A;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            flex-shrink: 0;
        }
    </style>

    <div class="log-container">

        {{-- ===== PAGE HEADER ===== --}}
        <div class="log-header">
            <div class="log-header-left">
                <h1 class="log-header-title">Application Log</h1>
                <p class="log-header-desc">Catatan riwayat aktivitas pengguna dan proses operasional rekonsiliasi gudang.</p>
            </div>

            {{-- Clear Logs Action Button --}}
            @if($stats['total'] > 0)
                <div>
                    <form method="POST" action="{{ route('logs.clear') }}" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan seluruh riwayat log aktivitas?');">
                        @csrf
                        <button
                            type="submit"
                            style="height: 38px; padding: 0 14px; font-size: 12px; font-weight: 600; color: #DC2626; background: #ffffff; border: 1px solid #FECACA; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);"
                        >
                            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Bersihkan Riwayat
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Flash Notification --}}
        @if(session('success'))
            <div style="margin-bottom: 20px; padding: 12px 16px; background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <svg style="width: 16px; height: 16px; color: #059669; flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- ===== 3-COLUMN STAT CARDS (Width & Height Balanced) ===== --}}
        <div class="log-stats-grid">
            {{-- Card 1: Total Log --}}
            <div class="log-stat-card">
                <div>
                    <div class="log-stat-label">Total Catatan Log</div>
                    <div class="log-stat-value">{{ number_format($stats['total']) }}</div>
                    <div class="log-stat-sub">Seluruh riwayat tercatat</div>
                </div>
                <div class="log-stat-icon-box" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; color: #475569;">
                    <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>

            {{-- Card 2: Hari Ini --}}
            <div class="log-stat-card">
                <div>
                    <div class="log-stat-label">Aktivitas Hari Ini</div>
                    <div class="log-stat-value">{{ number_format($stats['today']) }}</div>
                    <div class="log-stat-sub" style="color: #059669; font-weight: 600;">Sesi aktif terkini</div>
                </div>
                <div class="log-stat-icon-box" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #059669;">
                    <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            {{-- Card 3: Rekonsiliasi --}}
            <div class="log-stat-card">
                <div>
                    <div class="log-stat-label">Proses Rekonsiliasi</div>
                    <div class="log-stat-value">{{ number_format($stats['imports']) }}</div>
                    <div class="log-stat-sub" style="color: #2563EB; font-weight: 600;">Upload & matching data</div>
                </div>
                <div class="log-stat-icon-box" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; color: #2563EB;">
                    <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- ===== FILTER BAR ===== --}}
        <div class="log-filter-box">
            <form method="GET" action="{{ route('logs.index') }}" class="log-filter-form">
                {{-- Search Box --}}
                <div class="log-search-wrapper">
                    <svg class="log-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari aktivitas atau nama pengguna..."
                        class="log-input log-search-input"
                    >
                </div>

                {{-- Action Filter --}}
                <div>
                    <select name="action" class="log-select">
                        <option value="">Semua Aktivitas</option>
                        <option value="IMPORT" {{ $actionFilter === 'IMPORT' ? 'selected' : '' }}>Upload & Rekonsiliasi</option>
                        <option value="DOWNLOAD" {{ $actionFilter === 'DOWNLOAD' ? 'selected' : '' }}>Unduh File Excel</option>
                        <option value="AUTH" {{ $actionFilter === 'AUTH' ? 'selected' : '' }}>Login / Logout</option>
                        <option value="USER_MGMT" {{ $actionFilter === 'USER_MGMT' ? 'selected' : '' }}>Manajemen Pengguna</option>
                        <option value="SYSTEM" {{ $actionFilter === 'SYSTEM' ? 'selected' : '' }}>Sistem</option>
                    </select>
                </div>

                {{-- Date Filter --}}
                <div>
                    <input
                        type="date"
                        name="date"
                        value="{{ $dateFilter }}"
                        class="log-date-input"
                    >
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="log-btn-primary">
                    Terapkan
                </button>

                @if($search !== '' || $actionFilter !== '' || $dateFilter !== '')
                    <a href="{{ route('logs.index') }}" class="log-btn-reset">
                        Reset Filter
                    </a>
                @endif
            </form>
        </div>

        {{-- ===== CLEAN ESSENTIAL TABLE ===== --}}
        <div class="log-table-card">
            <div class="log-table-wrapper">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Waktu</th>
                            <th style="width: 200px;">Pengguna</th>
                            <th style="width: 170px;">Aksi / Modul</th>
                            <th>Deskripsi Aktivitas</th>
                            <th style="width: 120px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                {{-- Waktu --}}
                                <td style="white-space: nowrap;">
                                    <div style="font-weight: 700; color: #0F172A;">{{ $log->created_at->format('d M Y, H:i') }} WIB</div>
                                    <div style="font-size: 11px; color: #94A3B8; margin-top: 2px;">{{ $log->created_at->diffForHumans() }}</div>
                                </td>

                                {{-- Pengguna --}}
                                <td style="white-space: nowrap;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="log-avatar">
                                            {{ substr($log->user_name ?? 'SY', 0, 2) }}
                                        </div>
                                        <div style="font-weight: 700; color: #0F172A;">
                                            {{ $log->user_name ?? 'System' }}
                                        </div>
                                    </div>
                                </td>

                                {{-- Aksi / Modul --}}
                                <td style="white-space: nowrap;">
                                    @if($log->action === 'IMPORT')
                                        <span class="log-badge-action" style="background-color: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;">
                                            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            Rekonsiliasi
                                        </span>
                                    @elseif($log->action === 'DOWNLOAD')
                                        <span class="log-badge-action" style="background-color: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">
                                            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Unduh Berkas
                                        </span>
                                    @elseif($log->action === 'LOGIN')
                                        <span class="log-badge-action" style="background-color: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE;">
                                            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                            </svg>
                                            Masuk (Login)
                                        </span>
                                    @elseif($log->action === 'LOGOUT')
                                        <span class="log-badge-action" style="background-color: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;">
                                            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Keluar (Logout)
                                        </span>
                                    @elseif(str_starts_with($log->action, 'USER_'))
                                        <span class="log-badge-action" style="background-color: #FFFBEB; color: #92400E; border: 1px solid #FDE68A;">
                                            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Kelola User
                                        </span>
                                    @else
                                        <span class="log-badge-action" style="background-color: #F3F4F6; color: #374151; border: 1px solid #E5E7EB;">
                                            Sistem
                                        </span>
                                    @endif
                                </td>

                                {{-- Deskripsi --}}
                                <td style="line-height: 1.5; max-width: 420px;">
                                    <span title="{{ $log->description }}" style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ \Illuminate\Support\Str::limit($log->description, 95) }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td style="text-align: center; white-space: nowrap;">
                                    @if($log->level === 'success')
                                        <span class="log-badge-status" style="background-color: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">
                                            <span class="log-status-dot" style="background-color: #10B981;"></span>
                                            Berhasil
                                        </span>
                                    @elseif($log->level === 'warning')
                                        <span class="log-badge-status" style="background-color: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;">
                                            <span class="log-status-dot" style="background-color: #F59E0B;"></span>
                                            Peringatan
                                        </span>
                                    @elseif($log->level === 'error')
                                        <span class="log-badge-status" style="background-color: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA;">
                                            <span class="log-status-dot" style="background-color: #EF4444;"></span>
                                            Gagal
                                        </span>
                                    @else
                                        <span class="log-badge-status" style="background-color: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;">
                                            <span class="log-status-dot" style="background-color: #94A3B8;"></span>
                                            Info
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 48px 16px; text-align: center; color: #94A3B8;">
                                    <svg style="width: 40px; height: 40px; margin: 0 auto 8px auto; color: #CBD5E1;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div style="font-size: 14px; font-weight: 600; color: #475569;">Belum ada riwayat aktivitas</div>
                                    <div style="font-size: 12px; margin-top: 2px;">Aktivitas baru akan otomatis tercatat di sini saat pengguna menjalankan proses.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            @if($logs->hasPages())
                <div style="padding: 12px 18px; border-top: 1px solid #E2E8F0; background-color: #F8FAFC;">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layout>
