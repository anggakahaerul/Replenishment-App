<x-layout title="Master Shift & Cabang">
    <style>
        .shift-mgmt-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .shift-mgmt-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .shift-mgmt-title {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
            margin: 0 0 4px 0;
        }

        .shift-mgmt-desc {
            font-size: 13px;
            color: #64748B;
            margin: 0;
        }

        .shift-btn-primary {
            height: 38px;
            padding: 0 16px;
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            background-color: #2563EB;
            border: 1px solid #2563EB;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.15s ease;
        }

        .shift-btn-primary:hover {
            background-color: #1D4ED8;
            border-color: #1D4ED8;
        }

        .shift-btn-sm {
            height: 30px;
            padding: 0 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .shift-btn-ghost {
            background-color: #ffffff;
            border: 1px solid #CBD5E1;
            color: #475569;
        }

        .shift-btn-ghost:hover {
            background-color: #F8FAFC;
            border-color: #94A3B8;
            color: #0F172A;
        }

        .shift-btn-blue {
            background-color: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #2563EB;
        }

        .shift-btn-blue:hover {
            background-color: #DBEAFE;
            color: #1D4ED8;
        }

        .shift-btn-danger-ghost {
            background-color: #ffffff;
            border: 1px solid #FECACA;
            color: #DC2626;
        }

        .shift-btn-danger-ghost:hover {
            background-color: #FEF2F2;
            border-color: #F87171;
            color: #B91C1C;
        }

        /* Branch Card */
        .branch-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .branch-card-header {
            padding: 14px 20px;
            background-color: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .branch-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            background-color: #0F172A;
            color: #ffffff;
            letter-spacing: 0.03em;
        }

        .branch-name {
            font-size: 15px;
            font-weight: 700;
            color: #0F172A;
        }

        .branch-meta {
            font-size: 12px;
            color: #64748B;
        }

        /* Table */
        .shift-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .shift-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .shift-table th {
            background-color: #ffffff;
            border-bottom: 1px solid #E2E8F0;
            padding: 11px 18px;
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .shift-table td {
            padding: 12px 18px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .shift-table tr:hover td {
            background-color: #F8FAFC;
        }

        /* Data Badges */
        .pill-cutoff {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 11px;
            font-weight: 700;
            color: #DC2626;
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-time {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 12px;
            font-weight: 600;
            color: #0F172A;
        }

        .pill-norm {
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 11px;
            font-weight: 700;
            color: #D97706;
            background-color: #FEF3C7;
            border: 1px solid #FDE68A;
            padding: 2px 7px;
            border-radius: 5px;
        }

        .pill-status-active {
            font-size: 10px;
            font-weight: 700;
            color: #16A34A;
            background-color: #F0FDF4;
            border: 1px solid #BBF7D0;
            padding: 2px 7px;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .pill-status-off {
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            background-color: #F1F5F9;
            border: 1px solid #E2E8F0;
            padding: 2px 7px;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        /* Modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-dialog {
            background-color: #ffffff;
            border-radius: 14px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
            transition: border-color 0.15s ease;
        }

        .form-input:focus {
            border-color: #2563EB;
        }
    </style>

    <div class="shift-mgmt-container" x-data="shiftManagementApp()">

        {{-- ===== HEADER ===== --}}
        <div class="shift-mgmt-header">
            <div>
                <h1 class="shift-mgmt-title">Master Data Shift</h1>
                <p class="shift-mgmt-desc">Kelola jam operasional, cut-off analisa, dan toleransi shift per cabang.</p>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <a href="{{ route('master.branches.index') }}" class="shift-btn-sm shift-btn-ghost" style="height: 36px; padding: 0 12px;">
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Kelola Master Cabang
                </a>
                <button type="button" @click="openAddBranchModal()" class="shift-btn-primary">
                    <svg style="width: 15px; height: 15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Cabang
                </button>
            </div>
        </div>

        {{-- Filter Cabang Pills --}}
        @if(isset($allBranches) && $allBranches->count() > 1)
            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 16px; flex-wrap: wrap;">
                <a href="{{ route('master.shifts.index') }}"
                   class="shift-btn-sm"
                   style="{{ empty($branchId) ? 'background-color: #0F172A; color: #ffffff; border: 1px solid #0F172A;' : 'background-color: #ffffff; color: #475569; border: 1px solid #CBD5E1;' }}">
                    Semua Cabang
                </a>
                @foreach($allBranches as $b)
                    <a href="{{ route('master.shifts.index', ['branch_id' => $b->id]) }}"
                       class="shift-btn-sm"
                       style="{{ (string)$branchId === (string)$b->id ? 'background-color: #2563EB; color: #ffffff; border: 1px solid #2563EB;' : 'background-color: #ffffff; color: #475569; border: 1px solid #CBD5E1;' }}">
                        {{ $b->code }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Alerts --}}
        @if(session('success'))
            <div style="margin-bottom: 18px; padding: 10px 14px; background-color: #F0FDF4; border: 1px solid #BBF7D0; color: #16A34A; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <svg style="width: 15px; height: 15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div style="margin-bottom: 18px; padding: 10px 14px; background-color: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; border-radius: 8px; font-size: 12px; font-weight: 600;">
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- ===== DAFTAR CABANG & SHIFT ===== --}}
        <div>
            @forelse($branches as $branch)
                <div class="branch-card">
                    {{-- Branch Header Bar --}}
                    <div class="branch-card-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="branch-badge">{{ $branch->code }}</span>
                            <span class="branch-name">{{ $branch->name }}</span>
                            <span class="branch-meta">({{ $branch->timezone }})</span>
                            <span class="{{ $branch->is_active ? 'pill-status-active' : 'pill-status-off' }}">
                                {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button
                                type="button"
                                @click="openAddShiftModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                                class="shift-btn-sm shift-btn-blue"
                            >
                                <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Shift
                            </button>
                            <button
                                type="button"
                                @click="openEditBranchModal({{ json_encode($branch) }})"
                                class="shift-btn-sm shift-btn-ghost"
                            >
                                Edit Cabang
                            </button>
                            <form action="{{ route('master.shifts.branch.toggle', $branch->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="shift-btn-sm shift-btn-ghost" style="color: #64748B;">
                                    {{ $branch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form action="{{ route('master.shifts.branch.destroy', $branch->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Hapus cabang {{ addslashes($branch->name) }} ({{ $branch->code }})? Semua jadwal shift di cabang ini juga akan dihapus.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="shift-btn-sm shift-btn-danger-ghost" title="Hapus cabang ini">
                                    <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="shift-table-wrap">
                        <table class="shift-table">
                            <thead>
                                <tr>
                                    <th style="width: 14%;">Shift</th>
                                    <th style="width: 15%;">Hari Berlaku</th>
                                    <th style="width: 19%;">Jam Kerja</th>
                                    <th style="width: 16%;">Cut-Off Analisa</th>
                                    <th style="width: 13%;">Toleransi Cetak</th>
                                    <th style="width: 11%;">Normalisasi</th>
                                    <th style="width: 12%; text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branch->shifts as $shift)
                                    <tr style="{{ !$shift->is_active ? 'opacity: 0.5;' : '' }}">
                                        <td style="font-weight: 700; color: #0F172A;">
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background-color: {{ $shift->is_active ? '#16A34A' : '#94A3B8' }};"></span>
                                                <span>{{ $shift->shift_name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if(($shift->day_type ?? 'weekday') === 'saturday')
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #B45309; background-color: #FEF3C7; border: 1px solid #FDE68A; padding: 2px 8px; border-radius: 6px;">
                                                    Khusus Sabtu
                                                </span>
                                            @elseif(($shift->day_type ?? 'weekday') === 'all')
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: #15803D; background-color: #F0FDF4; border: 1px solid #BBF7D0; padding: 2px 8px; border-radius: 6px;">
                                                    Setiap Hari
                                                </span>
                                            @else
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: #1E40AF; background-color: #EFF6FF; border: 1px solid #DBEAFE; padding: 2px 8px; border-radius: 6px;">
                                                    Senin – Jumat
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pill-time">{{ $shift->start_time }} – {{ $shift->end_time }}</span>
                                            @if($shift->crosses_midnight)
                                                <span style="font-size: 10px; font-weight: 700; color: #7C3AED; background: #F5F3FF; border: 1px solid #DDD6FE; padding: 1px 5px; border-radius: 4px; margin-left: 4px;">+1 HARI</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pill-cutoff">
                                                <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ $shift->cutoff_time ?: $shift->end_time }}
                                            </span>
                                        </td>
                                        <td style="font-family: ui-monospace, monospace; font-size: 12px; color: #475569;">
                                            {{ $shift->pre_shift_minutes }} mnt
                                        </td>
                                        <td>
                                            @if($shift->normalized_shift && $shift->normalized_shift !== $shift->shift_name)
                                                <span class="pill-norm">{{ $shift->normalized_shift }}</span>
                                            @else
                                                <span style="color: #CBD5E1; font-weight: bold;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                                <button
                                                    type="button"
                                                    @click="openEditShiftModal({{ json_encode($shift) }}, '{{ addslashes($branch->name) }}')"
                                                    style="font-size: 12px; font-weight: 600; color: #2563EB; background: none; border: none; cursor: pointer; padding: 0;"
                                                >
                                                    Edit
                                                </button>
                                                <span style="color: #E2E8F0;">•</span>
                                                <form action="{{ route('master.shifts.shift.destroy', $shift->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal shift ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="font-size: 12px; font-weight: 500; color: #94A3B8; background: none; border: none; cursor: pointer; padding: 0;" onmouseover="this.style.color='#DC2626'" onmouseout="this.style.color='#94A3B8'">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: #94A3B8; font-size: 12px; padding: 18px;">
                                            Belum ada shift. Klik "+ Shift" untuk menambahkan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div style="background: white; border: 1px solid #E2E8F0; border-radius: 12px; padding: 36px; text-align: center; color: #94A3B8; font-size: 13px;">
                    Belum ada data cabang.
                </div>
            @endforelse
        </div>

        {{-- ===== MODAL TAMBAH CABANG ===== --}}
        <div x-show="showAddBranchModal" x-cloak class="modal-backdrop">
            <div class="modal-dialog" @click.stop>
                <form action="{{ route('master.shifts.branch.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Tambah Cabang Baru</h3>
                        <button type="button" @click="showAddBranchModal = false" style="background: none; border: none; cursor: pointer; color: #94A3B8;">✕</button>
                    </div>
                    <div class="modal-body" style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label class="form-label">Kode Cabang</label>
                            <input type="text" name="code" required placeholder="Contoh: JKT-GI, SDA-CT" class="form-input" style="text-transform: uppercase; font-family: monospace;">
                        </div>
                        <div>
                            <label class="form-label">Nama Cabang / Fasilitas</label>
                            <input type="text" name="name" required placeholder="Contoh: JKT-GI" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Zona Waktu</label>
                            <select name="timezone" class="form-input">
                                <option value="Asia/Jakarta">WIB (Asia/Jakarta)</option>
                                <option value="Asia/Makassar">WITA (Asia/Makassar)</option>
                                <option value="Asia/Jayapura">WIT (Asia/Jayapura)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="showAddBranchModal = false" class="shift-btn-sm shift-btn-ghost">Batal</button>
                        <button type="submit" class="shift-btn-sm" style="background: #2563EB; color: white; border: 1px solid #2563EB;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== MODAL EDIT CABANG ===== --}}
        <div x-show="showEditBranchModal" x-cloak class="modal-backdrop">
            <div class="modal-dialog" @click.stop>
                <form :action="'{{ url('master/shifts/branch') }}/' + editingBranch.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h3 class="modal-title">Edit Cabang</h3>
                        <button type="button" @click="showEditBranchModal = false" style="background: none; border: none; cursor: pointer; color: #94A3B8;">✕</button>
                    </div>
                    <div class="modal-body" style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label class="form-label">Kode Cabang</label>
                            <input type="text" name="code" x-model="editingBranch.code" required class="form-input" style="text-transform: uppercase; font-family: monospace;">
                        </div>
                        <div>
                            <label class="form-label">Nama Cabang</label>
                            <input type="text" name="name" x-model="editingBranch.name" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Zona Waktu</label>
                            <select name="timezone" x-model="editingBranch.timezone" class="form-input">
                                <option value="Asia/Jakarta">WIB (Asia/Jakarta)</option>
                                <option value="Asia/Makassar">WITA (Asia/Makassar)</option>
                                <option value="Asia/Jayapura">WIT (Asia/Jayapura)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="showEditBranchModal = false" class="shift-btn-sm shift-btn-ghost">Batal</button>
                        <button type="submit" class="shift-btn-sm" style="background: #2563EB; color: white; border: 1px solid #2563EB;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== MODAL TAMBAH SHIFT ===== --}}
        <div x-show="showAddShiftModal" x-cloak class="modal-backdrop">
            <div class="modal-dialog" @click.stop>
                <form :action="'{{ url('master/shifts/branch') }}/' + currentBranchId + '/shift'" method="POST">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title">Tambah Shift</h3>
                            <span style="font-size: 11px; color: #64748B;" x-text="currentBranchName"></span>
                        </div>
                        <button type="button" @click="showAddShiftModal = false" style="background: none; border: none; cursor: pointer; color: #94A3B8;">✕</button>
                    </div>
                    <div class="modal-body" style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="form-label">Nama Shift</label>
                                <input type="text" name="shift_name" required placeholder="SHIFT 1" class="form-input" style="text-transform: uppercase; font-weight: 700;">
                            </div>
                            <div>
                                <label class="form-label">Hari Berlaku</label>
                                <select name="day_type" class="form-input" style="font-weight: 600;">
                                    <option value="weekday">Senin – Jumat (Reguler)</option>
                                    <option value="saturday">Khusus Sabtu</option>
                                    <option value="all">Setiap Hari (Senin – Sabtu)</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="form-label">Jam Mulai</label>
                                <input type="text" name="start_time" required placeholder="06:00" class="form-input" style="font-family: monospace;">
                            </div>
                            <div>
                                <label class="form-label">Jam Selesai</label>
                                <input type="text" name="end_time" required placeholder="14:00" class="form-input" style="font-family: monospace;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="form-label" style="color: #DC2626;">Cut-Off Analisa</label>
                                <input type="text" name="cutoff_time" placeholder="14:00" class="form-input" style="font-family: monospace; border-color: #FECACA; background: #FEF2F2;">
                            </div>
                            <div>
                                <label class="form-label">Toleransi Report (Mnt)</label>
                                <input type="number" name="pre_shift_minutes" value="120" min="0" required class="form-input" style="font-family: monospace;">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Normalisasi Shift (Opsional)</label>
                            <input type="text" name="normalized_shift" placeholder="SHIFT 2" class="form-input" style="text-transform: uppercase; font-family: monospace;">
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                            <input type="checkbox" name="crosses_midnight" id="shift_midnight" value="1">
                            <label for="shift_midnight" style="font-size: 12px; color: #475569; cursor: pointer;">Shift Malam (+1 Hari, contoh 22:00 - 06:00)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="showAddShiftModal = false" class="shift-btn-sm shift-btn-ghost">Batal</button>
                        <button type="submit" class="shift-btn-sm" style="background: #2563EB; color: white; border: 1px solid #2563EB;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== MODAL EDIT SHIFT ===== --}}
        <div x-show="showEditShiftModal" x-cloak class="modal-backdrop">
            <div class="modal-dialog" @click.stop>
                <form :action="'{{ url('master/shifts/shift') }}/' + editingShift.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h3 class="modal-title">Edit Shift</h3>
                            <span style="font-size: 11px; color: #64748B;" x-text="currentBranchName"></span>
                        </div>
                        <button type="button" @click="showEditShiftModal = false" style="background: none; border: none; cursor: pointer; color: #94A3B8;">✕</button>
                    </div>
                    <div class="modal-body" style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="form-label">Nama Shift</label>
                                <input type="text" name="shift_name" x-model="editingShift.shift_name" required class="form-input" style="text-transform: uppercase; font-weight: 700;">
                            </div>
                            <div>
                                <label class="form-label">Hari Berlaku</label>
                                <select name="day_type" x-model="editingShift.day_type" class="form-input" style="font-weight: 600;">
                                    <option value="weekday">Senin – Jumat (Reguler)</option>
                                    <option value="saturday">Khusus Sabtu</option>
                                    <option value="all">Setiap Hari (Senin – Sabtu)</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="form-label">Jam Mulai</label>
                                <input type="text" name="start_time" x-model="editingShift.start_time" required class="form-input" style="font-family: monospace;">
                            </div>
                            <div>
                                <label class="form-label">Jam Selesai</label>
                                <input type="text" name="end_time" x-model="editingShift.end_time" required class="form-input" style="font-family: monospace;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="form-label" style="color: #DC2626;">Cut-Off Analisa</label>
                                <input type="text" name="cutoff_time" x-model="editingShift.cutoff_time" class="form-input" style="font-family: monospace; border-color: #FECACA; background: #FEF2F2;">
                            </div>
                            <div>
                                <label class="form-label">Toleransi Report (Mnt)</label>
                                <input type="number" name="pre_shift_minutes" x-model="editingShift.pre_shift_minutes" min="0" required class="form-input" style="font-family: monospace;">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Normalisasi Shift</label>
                            <input type="text" name="normalized_shift" x-model="editingShift.normalized_shift" class="form-input" style="text-transform: uppercase; font-family: monospace;">
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #475569; cursor: pointer;">
                                <input type="checkbox" name="crosses_midnight" value="1" :checked="editingShift.crosses_midnight">
                                Shift Malam (+1 Hari)
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #475569; cursor: pointer;">
                                <input type="checkbox" name="is_active" value="1" :checked="editingShift.is_active">
                                Aktif
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="showEditShiftModal = false" class="shift-btn-sm shift-btn-ghost">Batal</button>
                        <button type="submit" class="shift-btn-sm" style="background: #2563EB; color: white; border: 1px solid #2563EB;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function shiftManagementApp() {
            return {
                showAddBranchModal: false,
                showEditBranchModal: false,
                editingBranch: {},
                showAddShiftModal: false,
                showEditShiftModal: false,
                currentBranchId: null,
                currentBranchName: '',
                editingShift: {},

                openAddBranchModal() {
                    this.showAddBranchModal = true;
                },
                openEditBranchModal(branch) {
                    this.editingBranch = { ...branch };
                    this.showEditBranchModal = true;
                },
                openAddShiftModal(branchId, branchName) {
                    this.currentBranchId = branchId;
                    this.currentBranchName = branchName;
                    this.showAddShiftModal = true;
                },
                openEditShiftModal(shift, branchName) {
                    this.editingShift = {
                        ...shift,
                        day_type: shift.day_type || 'weekday'
                    };
                    this.currentBranchName = branchName;
                    this.showEditShiftModal = true;
                }
            };
        }
    </script>
</x-layout>
