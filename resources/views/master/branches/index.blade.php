<x-layout title="Master Data Cabang">
    <style>
        .branches-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .branches-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .branches-header-tag {
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

        .branches-header-title {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
            margin: 0 0 4px 0;
        }

        .branches-header-desc {
            font-size: 13px;
            color: #64748B;
            margin: 0;
        }

        /* 3-Column Stat Cards */
        .branch-stats-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 12px !important;
            margin-bottom: 20px !important;
        }

        @media (max-width: 768px) {
            .branch-stats-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .branch-stat-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 12px 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        /* Table Card */
        .branches-table-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .branches-toolbar {
            padding: 12px 18px;
            border-bottom: 1px solid #E2E8F0;
            background-color: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .branches-search-wrap {
            position: relative;
            width: 280px;
            max-width: 100%;
        }

        .branches-search-input {
            width: 100%;
            height: 34px;
            padding: 0 12px 0 32px;
            font-size: 12px;
            border: 1px solid #CBD5E1;
            border-radius: 7px;
            background-color: #ffffff;
            color: #0F172A;
            outline: none;
            box-sizing: border-box;
        }

        .branches-search-input:focus {
            border-color: #2563EB;
        }

        .branches-select {
            height: 34px;
            padding: 0 10px;
            font-size: 12px;
            border: 1px solid #CBD5E1;
            border-radius: 7px;
            background-color: #ffffff;
            color: #475569;
            outline: none;
            cursor: pointer;
        }

        /* Table */
        .branches-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .branches-table th {
            padding: 10px 16px;
            background-color: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .branches-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .branches-table tbody tr:last-child td {
            border-bottom: none;
        }

        .branches-table tbody tr:hover {
            background-color: #F8FAFC;
        }

        /* Badges & Buttons */
        .code-badge {
            display: inline-block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            background-color: #0F172A;
            color: #ffffff;
            letter-spacing: 0.03em;
        }

        .shift-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 6px;
            background-color: #EFF6FF;
            color: #2563EB;
            border: 1px solid #BFDBFE;
            text-decoration: none;
            transition: background-color 0.15s ease;
        }

        .shift-count-badge:hover {
            background-color: #DBEAFE;
        }

        .status-pill-active {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 999px;
            background-color: #F0FDF4;
            color: #16A34A;
            border: 1px solid #BBF7D0;
        }

        .status-pill-off {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 999px;
            background-color: #F1F5F9;
            color: #64748B;
            border: 1px solid #CBD5E1;
        }

        .btn-primary {
            height: 36px;
            padding: 0 14px;
            background-color: #2563EB;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #2563EB;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s ease;
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: #1D4ED8;
        }

        .btn-action {
            height: 28px;
            padding: 0 9px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            transition: all 0.15s ease;
            background-color: #ffffff;
            border: 1px solid #CBD5E1;
            color: #475569;
        }

        .btn-action:hover {
            background-color: #F8FAFC;
            color: #0F172A;
            border-color: #94A3B8;
        }

        .btn-action-danger {
            border-color: #FECACA;
            color: #DC2626;
        }

        .btn-action-danger:hover {
            background-color: #FEF2F2;
            border-color: #F87171;
            color: #B91C1C;
        }

        /* Modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-dialog {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.12);
            border: 1px solid #E2E8F0;
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            margin: auto;
            position: relative;
        }

        .modal-header {
            padding: 14px 20px;
            background-color: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
        }

        .modal-body {
            padding: 18px 20px;
        }

        .modal-footer {
            padding: 12px 20px;
            background-color: #F8FAFC;
            border-top: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .form-input {
            width: 100%;
            height: 36px;
            padding: 0 10px;
            font-size: 12px;
            border: 1px solid #CBD5E1;
            border-radius: 7px;
            color: #0F172A;
            background-color: #ffffff;
            box-sizing: border-box;
            outline: none;
        }

        .form-input:focus {
            border-color: #2563EB;
        }
    </style>

    <div class="branches-container" x-data="branchMasterApp()">

        {{-- ===== HEADER ===== --}}
        <div class="branches-header">
            <div>
                <h1 class="branches-header-title">Master Data Cabang</h1>
                <p class="branches-header-desc">Kelola daftar cabang gudang, kode lokasi, dan zona waktu operasional</p>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <a href="{{ route('master.shifts.index') }}" class="btn-action" style="height: 36px; padding: 0 12px; font-size: 12px;">
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Atur Jadwal Shift
                </a>
                <button type="button" @click="openAddModal()" class="btn-primary">
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Cabang
                </button>
            </div>
        </div>

        {{-- ===== ALERTS ===== --}}
        @if(session('success'))
            <div style="margin-bottom: 16px; padding: 10px 14px; background-color: #F0FDF4; border: 1px solid #BBF7D0; color: #16A34A; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <svg style="width: 15px; height: 15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div style="margin-bottom: 16px; padding: 10px 14px; background-color: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; border-radius: 8px; font-size: 12px; font-weight: 600;">
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- ===== 3 KPI SUMMARY CARDS ===== --}}
        <div class="branch-stats-grid">
            <div class="branch-stat-card">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;">Total Cabang</span>
                    <div style="width: 24px; height: 24px; border-radius: 6px; background-color: #F1F5F9; display: flex; align-items: center; justify-content: center; color: #475569;">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; color: #0F172A; line-height: 1; font-family: ui-monospace, monospace;">{{ $stats['total'] }}</span>
                    <span style="font-size: 11px; font-weight: 600; color: #64748B;">Cabang</span>
                </div>
            </div>

            <div class="branch-stat-card">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; font-weight: 700; color: #16A34A; text-transform: uppercase; letter-spacing: 0.05em;">Cabang Aktif</span>
                    <div style="width: 24px; height: 24px; border-radius: 6px; background-color: #DCFCE7; display: flex; align-items: center; justify-content: center; color: #16A34A;">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; color: #16A34A; line-height: 1; font-family: ui-monospace, monospace;">{{ $stats['active'] }}</span>
                    <span style="font-size: 11px; font-weight: 600; color: #16A34A;">Beroperasi</span>
                </div>
            </div>

            <div class="branch-stat-card">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 11px; font-weight: 700; color: #2563EB; text-transform: uppercase; letter-spacing: 0.05em;">Total Jadwal Shift</span>
                    <div style="width: 24px; height: 24px; border-radius: 6px; background-color: #DBEAFE; display: flex; align-items: center; justify-content: center; color: #2563EB;">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div style="margin-top: 6px; display: flex; align-items: baseline; gap: 6px;">
                    <span style="font-size: 22px; font-weight: 800; color: #2563EB; line-height: 1; font-family: ui-monospace, monospace;">{{ $stats['total_shifts'] }}</span>
                    <span style="font-size: 11px; font-weight: 600; color: #2563EB;">Shift Terdaftar</span>
                </div>
            </div>
        </div>

        {{-- ===== TABLE CARD ===== --}}
        <div class="branches-table-card">
            {{-- Toolbar --}}
            <form method="GET" action="{{ route('master.branches.index') }}" class="branches-toolbar">
                <div class="branches-search-wrap">
                    <svg style="width: 14px; height: 14px; color: #94A3B8; position: absolute; left: 10px; top: 50%; transform: translateY(-50%);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari kode atau nama cabang..."
                        class="branches-search-input"
                    >
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <select name="status" onchange="this.form.submit()" class="branches-select">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                    @if($search !== '' || $status !== 'all')
                        <a href="{{ route('master.branches.index') }}" class="btn-action" style="height: 34px;">Reset</a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div style="overflow-x: auto;">
                <table class="branches-table">
                    <colgroup>
                        <col style="width: 48px;">
                        <col style="width: 120px;">
                        <col style="min-width: 240px;">
                        <col style="width: 160px;">
                        <col style="width: 130px;">
                        <col style="width: 110px;">
                        <col style="width: 240px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="text-align: center;">NO.</th>
                            <th>KODE CABANG</th>
                            <th>NAMA CABANG</th>
                            <th>ZONA WAKTU</th>
                            <th>JADWAL SHIFT</th>
                            <th>STATUS</th>
                            <th style="text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $index => $branch)
                            <tr>
                                <td style="text-align: center; font-family: ui-monospace, monospace; font-size: 11px; color: #64748B;">
                                    {{ $index + 1 }}
                                </td>
                                <td>
                                    <span class="code-badge">{{ $branch->code }}</span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0F172A; font-size: 13px;">{{ $branch->name }}</div>
                                    @if($branch->description)
                                        <div style="font-size: 11px; color: #64748B; margin-top: 2px;">{{ $branch->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $tzLabel = match($branch->timezone) {
                                            'Asia/Jakarta' => 'WIB (Asia/Jakarta)',
                                            'Asia/Makassar' => 'WITA (Asia/Makassar)',
                                            'Asia/Jayapura' => 'WIT (Asia/Jayapura)',
                                            default => $branch->timezone,
                                        };
                                    @endphp
                                    <span style="font-size: 12px; color: #475569; font-weight: 500;">{{ $tzLabel }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('master.shifts.index', ['branch_id' => $branch->id]) }}" class="shift-count-badge" title="Lihat & atur shift cabang ini">
                                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $branch->shifts_count }} Shift
                                    </a>
                                </td>
                                <td>
                                    <span class="{{ $branch->is_active ? 'status-pill-active' : 'status-pill-off' }}">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background-color: {{ $branch->is_active ? '#16A34A' : '#94A3B8' }};"></span>
                                        {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                        <a href="{{ route('master.shifts.index', ['branch_id' => $branch->id]) }}" class="btn-action" style="color: #2563EB; border-color: #BFDBFE; background-color: #EFF6FF;">
                                            Shift
                                        </a>
                                        <button
                                            type="button"
                                            @click="openEditModal({{ json_encode($branch) }})"
                                            class="btn-action"
                                        >
                                            Edit
                                        </button>
                                        <form action="{{ route('master.branches.toggle', $branch->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-action">
                                                {{ $branch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('master.branches.destroy', $branch->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Hapus cabang {{ addslashes($branch->name) }} ({{ $branch->code }})? Semua jadwal shift di cabang ini juga akan dihapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action btn-action-danger" title="Hapus cabang">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px; color: #94A3B8;">
                                    Belum ada data cabang yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== MODAL TAMBAH / EDIT CABANG ===== --}}
        <div x-show="showModal" x-cloak class="modal-backdrop" @keydown.escape.window="showModal = false">
            <div class="modal-dialog" @click.away="showModal = false">
                <div class="modal-header">
                    <h3 class="modal-title" x-text="isEditing ? 'Edit Master Cabang' : 'Tambah Cabang Baru'"></h3>
                    <button type="button" @click="showModal = false" style="background: none; border: none; color: #94A3B8; cursor: pointer;">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="formAction" method="POST">
                    @csrf
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="modal-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                            <div>
                                <label class="form-label">Kode Cabang</label>
                                <input type="text" name="code" x-model="form.code" required placeholder="JKT-GI / SDA-CT" class="form-input" style="font-family: ui-monospace, monospace; text-transform: uppercase;">
                            </div>
                            <div>
                                <label class="form-label">Zona Waktu</label>
                                <select name="timezone" x-model="form.timezone" class="form-input">
                                    <option value="Asia/Jakarta">WIB (Asia/Jakarta)</option>
                                    <option value="Asia/Makassar">WITA (Asia/Makassar)</option>
                                    <option value="Asia/Jayapura">WIT (Asia/Jayapura)</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label class="form-label">Nama Cabang</label>
                            <input type="text" name="name" x-model="form.name" required placeholder="Contoh: JKT-GI / SDA-CT" class="form-input">
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label class="form-label">Keterangan / Lokasi (Opsional)</label>
                            <input type="text" name="description" x-model="form.description" placeholder="Contoh: Gudang Utama Sidoarjo" class="form-input">
                        </div>

                        <template x-if="isEditing">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 12px; font-weight: 600; color: #334155;">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active">
                                Cabang Aktif Beroperasi
                            </label>
                        </template>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showModal = false" class="btn-action" style="height: 34px; padding: 0 12px;">Batal</button>
                        <button type="submit" class="btn-primary" style="height: 34px;">Simpan Cabang</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function branchMasterApp() {
            return {
                showModal: false,
                isEditing: false,
                formAction: '{{ route("master.branches.store") }}',
                form: {
                    code: '',
                    name: '',
                    timezone: 'Asia/Jakarta',
                    description: '',
                    is_active: true,
                },

                openAddModal() {
                    this.isEditing = false;
                    this.formAction = '{{ route("master.branches.store") }}';
                    this.form = {
                        code: '',
                        name: '',
                        timezone: 'Asia/Jakarta',
                        description: '',
                        is_active: true,
                    };
                    this.showModal = true;
                },

                openEditModal(branch) {
                    this.isEditing = true;
                    this.formAction = `/master/branches/${branch.id}`;
                    this.form = {
                        code: branch.code,
                        name: branch.name,
                        timezone: branch.timezone || 'Asia/Jakarta',
                        description: branch.description || '',
                        is_active: !!branch.is_active,
                    };
                    this.showModal = true;
                }
            };
        }
    </script>
</x-layout>

