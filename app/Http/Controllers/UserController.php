<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $roleFilter = $request->get('role', '');
        $branchFilter = $request->get('branch_id', '');

        $query = User::with('branch')->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $allowedRoles = [User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_SUPERVISOR, User::ROLE_MANAGER];
        if ($roleFilter !== '' && in_array($roleFilter, $allowedRoles, true)) {
            $query->where('role', $roleFilter);
        }

        if ($branchFilter === 'all_access') {
            $query->whereNull('branch_id');
        } elseif ($branchFilter !== '' && is_numeric($branchFilter)) {
            $query->where('branch_id', (int) $branchFilter);
        }

        $users = $query->paginate(15)->withQueryString();
        $branches = Branch::orderBy('code')->get();
        $roles = Role::orderBy('id')->get();
        $permissionMenus = Role::MENU_PERMISSIONS;

        return view('users.index', compact('users', 'branches', 'roles', 'permissionMenus', 'search', 'roleFilter', 'branchFilter'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_SUPERVISOR, User::ROLE_MANAGER])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'password'  => ['required', 'string', 'min:6'],
        ], [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email ini sudah terdaftar.',
            'role.required'     => 'Role wajib dipilih.',
            'branch_id.exists'  => 'Cabang yang dipilih tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        $branchId = !empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'branch_id' => $branchId,
            'password'  => Hash::make($validated['password']),
        ]);

        \App\Models\ActivityLog::record(
            \App\Models\ActivityLog::ACTION_USER_CREATE,
            "Menambahkan pengguna baru '{$validated['name']}' ({$validated['email']}) dengan peran '{$validated['role']}'.",
            ['name' => $validated['name'], 'email' => $validated['email'], 'role' => $validated['role'], 'branch_id' => $branchId],
            \App\Models\ActivityLog::LEVEL_SUCCESS
        );

        return redirect()->route('users.index')
            ->with('success', "Pengguna baru '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'      => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_SUPERVISOR, User::ROLE_MANAGER])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'password'  => ['nullable', 'string', 'min:6'],
        ], [
            'name.required'    => 'Nama pengguna wajib diisi.',
            'email.required'   => 'Email wajib diisi.',
            'email.unique'     => 'Email ini sudah digunakan oleh akun lain.',
            'role.required'    => 'Role wajib dipilih.',
            'branch_id.exists' => 'Cabang yang dipilih tidak valid.',
            'password.min'     => 'Password minimal 6 karakter.',
        ]);

        $branchId = !empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        $updateData = [
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'branch_id' => $branchId,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        \App\Models\ActivityLog::record(
            \App\Models\ActivityLog::ACTION_USER_UPDATE,
            "Memperbarui data pengguna '{$user->name}' (ID: {$user->id}).",
            ['user_id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'branch_id' => $branchId],
            \App\Models\ActivityLog::LEVEL_INFO
        );

        return redirect()->route('users.index')
            ->with('success', "Data pengguna '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Update permission matrix for a specific role.
     */
    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        if ($role->slug === 'admin') {
            return redirect()->route('users.index', ['tab' => 'roles'])
                ->with('info', 'Role Admin (Super User) selalu memiliki akses penuh ke seluruh menu.');
        }

        $submitted = $request->input('permissions', []);
        $matrix = [];

        foreach (Role::MENU_PERMISSIONS as $menu => $meta) {
            $matrix[$menu] = [];
            foreach (array_keys($meta['actions']) as $action) {
                $matrix[$menu][$action] = !empty($submitted[$menu][$action]);
            }
        }

        $role->update(['permissions' => $matrix]);

        \App\Models\ActivityLog::record(
            \App\Models\ActivityLog::ACTION_USER_UPDATE,
            "Memperbarui permission role '{$role->name}'.",
            ['role_id' => $role->id, 'slug' => $role->slug],
            \App\Models\ActivityLog::LEVEL_INFO
        );

        return redirect()->route('users.index', ['tab' => 'roles'])
            ->with('success', "Permission role '{$role->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $name = $user->name;
        $email = $user->email;
        $id = $user->id;

        $user->delete();

        \App\Models\ActivityLog::record(
            \App\Models\ActivityLog::ACTION_USER_DELETE,
            "Menghapus pengguna '{$name}' ({$email}).",
            ['user_id' => $id, 'name' => $name, 'email' => $email],
            \App\Models\ActivityLog::LEVEL_WARNING
        );

        return redirect()->route('users.index')
            ->with('success', "Pengguna '{$name}' telah berhasil dihapus.");
    }
}
