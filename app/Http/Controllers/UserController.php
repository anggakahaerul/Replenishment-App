<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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

        if ($roleFilter !== '' && in_array($roleFilter, [User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_VIEWER], true)) {
            $query->where('role', $roleFilter);
        }

        if ($branchFilter === 'all_access') {
            $query->whereNull('branch_id');
        } elseif ($branchFilter !== '' && is_numeric($branchFilter)) {
            $query->where('branch_id', (int) $branchFilter);
        }

        $users = $query->paginate(15)->withQueryString();
        $branches = Branch::orderBy('code')->get();

        return view('users.index', compact('users', 'branches', 'search', 'roleFilter', 'branchFilter'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_VIEWER])],
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
            'role'      => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_VIEWER])],
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
