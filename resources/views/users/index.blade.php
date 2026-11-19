<x-layout title="Manajemen Pengguna">
    <style>
        /* Bulletproof layout styles for User Management */
        .users-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .users-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .users-header-left {
            text-align: left;
        }

        .users-header-tag {
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

        .users-header-title {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
            margin: 0 0 4px 0;
            text-align: left;
        }

        .users-header-desc {
            font-size: 13px;
            color: #64748B;
            margin: 0;
            text-align: left;
        }

        /* 4-Column Role Cards */
        .role-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 24px;
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 860px) {
            .role-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        .role-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            min-height: 88px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        .role-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .role-card-desc {
            font-size: 11px;
            color: #64748B;
            margin: 0;
            line-height: 1.5;
        }

        /* Table Card & Toolbar */
        .users-table-card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .users-toolbar {
            padding: 14px 20px;
            border-bottom: 1px solid #E2E8F0;
            background-color: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .users-toolbar-left {
            text-align: left;
        }

        .users-toolbar-title {
            font-size: 15px;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
        }

        .users-toolbar-sub {
            font-size: 12px;
            color: #64748B;
            margin-top: 2px;
        }

        .users-toolbar-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .users-search-wrapper {
            position: relative;
        }

        .users-search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: #94A3B8;
            pointer-events: none;
        }

        .users-search-input {
            height: 36px;
            padding: 0 12px 0 32px;
            font-size: 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background-color: #ffffff;
            color: #0F172A;
            outline: none;
            width: 220px;
            box-sizing: border-box;
            transition: border-color 0.15s ease;
        }

        .users-search-input:focus {
            border-color: #2563EB;
        }

        .users-select {
            height: 36px;
            padding: 0 10px;
            font-size: 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background-color: #ffffff;
            color: #0F172A;
            outline: none;
            box-sizing: border-box;
        }

        .users-btn-add {
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

        .users-btn-add:hover {
            background-color: #1D4ED8;
            border-color: #1D4ED8;
        }

        /* Clean Table */
        .users-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .users-table th {
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

        .users-table td {
            padding: 13px 18px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .users-table tr:hover td {
            background-color: #F8FAFC;
        }

        .users-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #E2E8F0;
            color: #0F172A;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .users-tab-bar {
            display: inline-flex;
            align-items: center;
            background-color: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-radius: 9999px;
            padding: 4px;
            gap: 4px;
            margin-bottom: 20px;
        }

        .users-tab-btn {
            padding: 7px 18px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 600;
            color: #64748B;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .users-tab-btn:hover {
            color: #0F172A;
        }

        .users-tab-btn.active {
            background-color: #FFFFFF;
            color: #0F172A;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .users-role-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .users-role-pill {
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.15s ease;
        }

        .users-role-pill:hover {
            border-color: #CBD5E1;
            background-color: #F8FAFC;
        }

        .users-role-pill.active {
            background-color: #0F172A;
            color: #FFFFFF;
            border-color: #0F172A;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
        }

        .users-role-pill-count {
            font-size: 11px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 9999px;
            background-color: #F1F5F9;
            color: #475569;
        }

        .users-role-pill.active .users-role-pill-count {
            background-color: rgba(255, 255, 255, 0.18);
            color: #FFFFFF;
        }

        .perm-matrix-table {
            width: 100%;
            border-collapse: collapse;
        }

        .perm-matrix-table th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            background-color: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
        }

        .perm-matrix-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }

        .perm-matrix-table tbody tr:hover {
            background-color: #F8FAFC;
        }
    </style>

    <div class="users-container" x-data="{
        activeSection: '{{ request('tab') === 'roles' ? 'roles' : 'users' }}',
        activeRoleSlug: '{{ $roles->first()?->slug ?? 'admin' }}',
        createModalOpen: false,
        editModalOpen: false,
        createRole: 'operator',
        editUser: { id: null, name: '', email: '', role: 'operator', branch_id: '' },
        openEditModal(user) {
            this.editUser = {
                ...user,
                branch_id: user.branch_id ? String(user.branch_id) : ''
            };
            this.editModalOpen = true;
        }
    }">

        {{-- ===== PAGE HEADER ===== --}}
        <div class="users-header" style="margin-bottom: 16px;">
            <div class="users-header-left">
                <h1 class="users-header-title">Manajemen Pengguna &amp; Role</h1>
                <p class="users-header-desc">Kelola akun serta atur permission tiap menu untuk role Admin, Operator, Supervisor, dan Manager</p>
            </div>

            @if(auth()->user()?->hasPermission('users', 'create'))
            <button type="button" @click="createModalOpen = true" class="users-btn-add">
                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Tambah Pengguna
            </button>
            @endif
        </div>

        {{-- ===== SECTION TAB SWITCHER ===== --}}
        <div class="users-tab-bar">
            <button type="button" @click="activeSection = 'users'"
                class="users-tab-btn" :class="{ 'active': activeSection === 'users' }">
                Daftar Pengguna
            </button>
            <button type="button" @click="activeSection = 'roles'"
                class="users-tab-btn" :class="{ 'active': activeSection === 'roles' }">
                Role &amp; Permission
            </button>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div style="margin-bottom: 20px; padding: 12px 16px; background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <svg style="width: 16px; height: 16px; color: #059669; flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div style="margin-bottom: 20px; padding: 12px 16px; background-color: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <svg style="width: 16px; height: 16px; color: #DC2626; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div x-show="activeSection === 'users'">
        {{-- ===== 4-COLUMN ROLE CARDS ===== --}}
        <div class="role-cards-grid">
            {{-- Admin Card --}}
            <div class="role-card">
                <div class="role-card-header">
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background-color: #F3E8FF; color: #7E22CE; border: 1px solid #E9D5FF; letter-spacing: 0.05em;">
                        ADMIN
                    </span>
                    <span style="font-size: 13px; font-weight: 700; color: #0F172A;">Super User</span>
                </div>
                <p class="role-card-desc">Akses penuh ke seluruh menu, master data, manajemen user &amp; role, serta application log.</p>
            </div>

            {{-- Operator Card --}}
            <div class="role-card">
                <div class="role-card-header">
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background-color: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; letter-spacing: 0.05em;">
                        OPERATOR
                    </span>
                    <span style="font-size: 13px; font-weight: 700; color: #0F172A;">Operasional</span>
                </div>
                <p class="role-card-desc">Upload file excel, proses matching replenishment, unduh file, serta lihat dashboard &amp; history.</p>
            </div>

            {{-- Supervisor Card --}}
            <div class="role-card">
                <div class="role-card-header">
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background-color: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; letter-spacing: 0.05em;">
                        SUPERVISOR
                    </span>
                    <span style="font-size: 13px; font-weight: 700; color: #0F172A;">Hanya Lihat</span>
                </div>
                <p class="role-card-desc">Bisa melihat semua menu operasional &amp; master data (tanpa hapus, tanpa User &amp; Application Log).</p>
            </div>

            {{-- Manager Card --}}
            <div class="role-card">
                <div class="role-card-header">
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; background-color: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE; letter-spacing: 0.05em;">
                        MANAGER
                    </span>
                    <span style="font-size: 13px; font-weight: 700; color: #0F172A;">Lihat &amp; Hapus</span>
                </div>
                <p class="role-card-desc">Bisa melihat &amp; menghapus di menu operasional dan master data (kecuali User &amp; Application Log).</p>
            </div>
        </div>

        {{-- ===== DATA TABLE CARD ===== --}}
        <div class="users-table-card">
            {{-- Toolbar: Left title, Right filters --}}
            <div class="users-toolbar">
                <div class="users-toolbar-left">
                    <h2 class="users-toolbar-title">Daftar Pengguna Sistem</h2>
                    <p class="users-toolbar-sub">Total {{ $users->total() }} pengguna terdaftar</p>
                </div>

                <form action="{{ route('users.index') }}" method="GET" class="users-toolbar-form">
                    {{-- Branch Filter --}}
                    <select name="branch_id" onchange="this.form.submit()" class="users-select">
                        <option value="">Semua Cabang</option>
                        <option value="all_access" {{ ($branchFilter ?? '') === 'all_access' ? 'selected' : '' }}>Semua Cabang (Global)</option>
                        @foreach($branches ?? [] as $b)
                            <option value="{{ $b->id }}" {{ (string)($branchFilter ?? '') === (string)$b->id ? 'selected' : '' }}>
                                {{ $b->code }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Role Filter --}}
                    <select name="role" onchange="this.form.submit()" class="users-select">
                        <option value="">Semua Role</option>
                        <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="operator" {{ $roleFilter === 'operator' ? 'selected' : '' }}>Operator</option>
                        <option value="supervisor" {{ $roleFilter === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        <option value="manager" {{ $roleFilter === 'manager' ? 'selected' : '' }}>Manager</option>
                    </select>

                    {{-- Search Input --}}
                    <div class="users-search-wrapper">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Cari nama atau email..."
                               class="users-search-input">
                        <svg class="users-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    @if($search !== '' || $roleFilter !== '' || ($branchFilter ?? '') !== '')
                        <a href="{{ route('users.index') }}" style="font-size: 12px; color: #64748B; text-decoration: underline; padding: 6px;">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- Table Wrapper --}}
            <div class="users-table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO.</th>
                            <th style="width: 240px;">NAMA</th>
                            <th style="width: 240px;">EMAIL</th>
                            <th style="width: 120px; text-align: center;">ROLE</th>
                            <th style="width: 170px; text-align: center;">AKSES CABANG</th>
                            <th style="width: 150px;">TANGGAL DIBUAT</th>
                            <th style="width: 130px; text-align: center;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                            <tr>
                                <td style="text-align: center; color: #64748B; font-weight: 500;">
                                    {{ $users->firstItem() + $index }}
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="users-avatar">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div style="font-weight: 700; color: #0F172A; display: flex; align-items: center; gap: 6px;">
                                            <span>{{ $user->name }}</span>
                                            @if($user->id === auth()->id())
                                                <span style="font-size: 10px; font-weight: 600; color: #2563EB; background: #EFF6FF; padding: 1px 6px; border-radius: 4px; border: 1px solid #BFDBFE;">(Anda)</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="color: #475569; font-size: 13px;">
                                    {{ $user->email }}
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    @if($user->role === 'admin')
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background-color: #F3E8FF; color: #7E22CE; border: 1px solid #E9D5FF;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #7E22CE;"></span>
                                            Admin
                                        </span>
                                    @elseif($user->role === 'operator')
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background-color: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #15803D;"></span>
                                            Operator
                                        </span>
                                    @elseif($user->role === 'manager')
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background-color: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #6D28D9;"></span>
                                            Manager
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background-color: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #0369A1;"></span>
                                            {{ $user->role === 'supervisor' ? 'Supervisor' : ucfirst($user->role) }}
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    @if($user->hasGlobalBranchAccess())
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background-color: #F8FAFC; color: #475569; border: 1px solid #E2E8F0;">
                                            Semua Cabang
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; background-color: #EFF6FF; color: #2563EB; border: 1px solid #DBEAFE;">
                                            {{ $user->branch?->code ?? 'Cabang #' . $user->branch_id }}
                                        </span>
                                    @endif
                                </td>
                                <td style="color: #64748B; font-size: 12px; white-space: nowrap;">
                                    {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        {{-- Edit Button --}}
                                        <button type="button"
                                                @click="openEditModal({{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'branch_id' => $user->branch_id]) }})"
                                                style="padding: 4px 10px; font-size: 11px; font-weight: 600; color: #2563EB; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 6px; cursor: pointer;"
                                                title="Edit Pengguna">
                                            Edit
                                        </button>

                                        {{-- Delete Button (Disabled for self) --}}
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}?');" style="margin: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        style="padding: 4px 10px; font-size: 11px; font-weight: 600; color: #DC2626; background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 6px; cursor: pointer;"
                                                        title="Hapus Pengguna">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 36px 16px; text-align: center; color: #64748B; font-size: 13px;">
                                    Tidak ada data pengguna yang sesuai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div style="padding: 12px 18px; border-top: 1px solid #E2E8F0; background-color: #F8FAFC;">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
        </div>

        {{-- ===== ROLE & PERMISSION MANAGEMENT SECTION ===== --}}
        <div x-show="activeSection === 'roles'" x-cloak>
            {{-- Role Selector Pills --}}
            <div class="users-role-pills">
                @foreach($roles as $roleObj)
                    <button type="button"
                        @click="activeRoleSlug = '{{ $roleObj->slug }}'"
                        class="users-role-pill"
                        :class="{ 'active': activeRoleSlug === '{{ $roleObj->slug }}' }">
                        <span>{{ $roleObj->name }}</span>
                        <span class="users-role-pill-count">{{ $roleObj->users()->count() }}</span>
                    </button>
                @endforeach
            </div>

            @php
                $actionColumns = [
                    'view'     => 'View',
                    'create'   => 'Create',
                    'edit'     => 'Edit',
                    'delete'   => 'Delete',
                    'download' => 'Download',
                ];
            @endphp

            @foreach($roles as $roleObj)
                <div x-show="activeRoleSlug === '{{ $roleObj->slug }}'" x-cloak class="users-table-card">
                    <form action="{{ route('users.roles.update', $roleObj) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="users-toolbar">
                            <div class="users-toolbar-left">
                                <h2 class="users-toolbar-title">Permission Matrix — {{ $roleObj->name }}</h2>
                                <p class="users-toolbar-sub">{{ $roleObj->description }}</p>
                            </div>

                            @if($roleObj->slug === 'admin')
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-size: 11.5px; font-weight: 700; background-color: #F3E8FF; color: #7E22CE; border: 1px solid #E9D5FF;">
                                    Super User — Akses Penuh Terkunci
                                </span>
                            @elseif(auth()->user()?->hasPermission('users', 'edit'))
                                <button type="submit" class="users-btn-add" style="background-color: #2563EB;">
                                    Simpan Permission
                                </button>
                            @endif
                        </div>

                        <div class="users-table-wrapper">
                            <table class="perm-matrix-table">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; width: 280px;">Menu / Modul</th>
                                        @foreach($actionColumns as $actLabel)
                                            <th style="text-align: center; width: 120px;">{{ $actLabel }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissionMenus as $menuKey => $menuMeta)
                                        <tr>
                                            <td>
                                                <div style="font-size: 13px; font-weight: 700; color: #0F172A;">{{ $menuMeta['label'] }}</div>
                                            </td>
                                            @foreach($actionColumns as $actionKey => $actLabel)
                                                <td style="text-align: center;">
                                                    @if(array_key_exists($actionKey, $menuMeta['actions']))
                                                        @php
                                                            $isChecked = $roleObj->slug === 'admin'
                                                                ? true
                                                                : (bool) ($roleObj->permissions[$menuKey][$actionKey] ?? false);
                                                        @endphp
                                                        <label title="{{ $menuMeta['actions'][$actionKey] }}"
                                                               style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; cursor: {{ $roleObj->slug === 'admin' ? 'not-allowed' : 'pointer' }};">
                                                            <input type="checkbox"
                                                                   name="permissions[{{ $menuKey }}][{{ $actionKey }}]"
                                                                   value="1"
                                                                   {{ $isChecked ? 'checked' : '' }}
                                                                   {{ $roleObj->slug === 'admin' ? 'disabled' : '' }}
                                                                   style="width: 17px; height: 17px; accent-color: #2563EB; cursor: {{ $roleObj->slug === 'admin' ? 'not-allowed' : 'pointer' }};">
                                                        </label>
                                                    @else
                                                        <span style="color: #CBD5E1; font-weight: 700;">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

        {{-- ===== MODAL TAMBAH PENGGUNA ===== --}}
        <div x-show="createModalOpen"
             x-cloak
             style="position: fixed; inset: 0; z-index: 50; overflow-y: auto; display: none;"
             role="dialog"
             aria-modal="true">
            
            <div x-show="createModalOpen"
                 @click="createModalOpen = false"
                 style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px);"></div>

            <div style="display: flex; min-height: 100%; align-items: center; justify-content: center; padding: 16px;">
                <div x-show="createModalOpen"
                     @keydown.escape.window="createModalOpen = false"
                     @click.away="createModalOpen = false"
                     style="position: relative; width: 100%; max-width: 440px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #E2E8F0; text-align: left; margin: auto;">
                    
                    <div style="padding: 16px 20px; border-bottom: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; background-color: #F8FAFC;">
                        <h3 style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0;">Tambah Pengguna Baru</h3>
                        <button type="button" @click="createModalOpen = false" style="color: #94A3B8; cursor: pointer; padding: 4px; border: none; background: none;">
                            <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('users.store') }}" method="POST" style="padding: 20px; display: flex; flex-direction: column; gap: 14px; margin: 0;">
                        @csrf

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Nama Lengkap</label>
                            <input type="text" name="name" required placeholder="Contoh: Budi Santoso"
                                   style="width: 100%; height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Email</label>
                            <input type="email" name="email" required placeholder="budi@replenishment.test"
                                   style="width: 100%; height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Role / Peran</label>
                            <select name="role" x-model="createRole" required style="width: 100%; height: 38px; padding: 0 10px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                                <option value="operator">Operator (Upload &amp; proses matching)</option>
                                <option value="supervisor">Supervisor (Hanya lihat semua menu kecuali User &amp; Log)</option>
                                <option value="manager">Manager (Lihat &amp; hapus semua menu kecuali User &amp; Log)</option>
                                <option value="admin">Admin (Super User — akses penuh)</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Akses Scope Cabang</label>
                            <select name="branch_id" style="width: 100%; height: 38px; padding: 0 10px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                                <option value="">Semua Cabang (Akses Global)</option>
                                @foreach($branches ?? [] as $b)
                                    @php
                                        $sameCodeName = strtolower(preg_replace('/[^a-z0-9]/i', '', $b->code)) === strtolower(preg_replace('/[^a-z0-9]/i', '', $b->name));
                                    @endphp
                                    <option value="{{ $b->id }}">{{ !$sameCodeName ? ($b->code . ' • ' . $b->name) : $b->code }}</option>
                                @endforeach
                            </select>
                            <p style="font-size: 11px; color: #94A3B8; margin: 4px 0 0 0;">Pilih "Semua Cabang" untuk akses penuh lintas cabang, atau pilih cabang spesifik.</p>
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Password</label>
                            <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter"
                                   style="width: 100%; height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                        </div>

                        <div style="padding-top: 14px; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 8px;">
                            <button type="button" @click="createModalOpen = false" style="padding: 8px 14px; font-size: 12px; font-weight: 600; color: #64748B; background-color: #F1F5F9; border: none; border-radius: 8px; cursor: pointer;">
                                Batal
                            </button>
                            <button type="submit" style="padding: 8px 16px; font-size: 12px; font-weight: 700; color: #ffffff; background-color: #2563EB; border: none; border-radius: 8px; cursor: pointer;">
                                Simpan Pengguna
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== MODAL EDIT PENGGUNA ===== --}}
        <div x-show="editModalOpen"
             x-cloak
             style="position: fixed; inset: 0; z-index: 50; overflow-y: auto; display: none;"
             role="dialog"
             aria-modal="true">
            
            <div x-show="editModalOpen"
                 @click="editModalOpen = false"
                 style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px);"></div>

            <div style="display: flex; min-height: 100%; align-items: center; justify-content: center; padding: 16px;">
                <div x-show="editModalOpen"
                     @keydown.escape.window="editModalOpen = false"
                     @click.away="editModalOpen = false"
                     style="position: relative; width: 100%; max-width: 440px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #E2E8F0; text-align: left; margin: auto;">
                    
                    <div style="padding: 16px 20px; border-bottom: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between; background-color: #F8FAFC;">
                        <h3 style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0;">Edit Data Pengguna</h3>
                        <button type="button" @click="editModalOpen = false" style="color: #94A3B8; cursor: pointer; padding: 4px; border: none; background: none;">
                            <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form :action="'{{ url('/users') }}/' + editUser.id" method="POST" style="padding: 20px; display: flex; flex-direction: column; gap: 14px; margin: 0;">
                        @csrf
                        @method('PUT')

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Nama Lengkap</label>
                            <input type="text" name="name" x-model="editUser.name" required
                                   style="width: 100%; height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Email</label>
                            <input type="email" name="email" x-model="editUser.email" required
                                   style="width: 100%; height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Role / Peran</label>
                            <select name="role" x-model="editUser.role" required style="width: 100%; height: 38px; padding: 0 10px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                                <option value="operator">Operator (Upload &amp; proses matching)</option>
                                <option value="supervisor">Supervisor (Hanya lihat semua menu kecuali User &amp; Log)</option>
                                <option value="manager">Manager (Lihat &amp; hapus semua menu kecuali User &amp; Log)</option>
                                <option value="admin">Admin (Super User — akses penuh)</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Akses Scope Cabang</label>
                            <select name="branch_id" x-model="editUser.branch_id" style="width: 100%; height: 38px; padding: 0 10px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                                <option value="">Semua Cabang (Akses Global)</option>
                                @foreach($branches ?? [] as $b)
                                    @php
                                        $sameCodeName = strtolower(preg_replace('/[^a-z0-9]/i', '', $b->code)) === strtolower(preg_replace('/[^a-z0-9]/i', '', $b->name));
                                    @endphp
                                    <option value="{{ $b->id }}">{{ !$sameCodeName ? ($b->code . ' • ' . $b->name) : $b->code }}</option>
                                @endforeach
                            </select>
                            <p style="font-size: 11px; color: #94A3B8; margin: 4px 0 0 0;">Pilih "Semua Cabang" untuk akses penuh lintas cabang, atau pilih cabang spesifik.</p>
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;">Password Baru (Opsional)</label>
                            <input type="password" name="password" minlength="6" placeholder="Kosongkan jika tidak ingin mengubah password"
                                   style="width: 100%; height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; box-sizing: border-box;">
                            <p style="font-size: 10px; color: #94A3B8; margin: 4px 0 0 0;">Biarkan kosong bila tidak ingin mengganti password lama.</p>
                        </div>

                        <div style="padding-top: 14px; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 8px;">
                            <button type="button" @click="editModalOpen = false" style="padding: 8px 14px; font-size: 12px; font-weight: 600; color: #64748B; background-color: #F1F5F9; border: none; border-radius: 8px; cursor: pointer;">
                                Batal
                            </button>
                            <button type="submit" style="padding: 8px 16px; font-size: 12px; font-weight: 700; color: #ffffff; background-color: #2563EB; border: none; border-radius: 8px; cursor: pointer;">
                                Perbarui Pengguna
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-layout>
