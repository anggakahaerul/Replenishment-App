<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchShiftController extends Controller
{
    /**
     * Display a listing of master branches.
     */
    public function indexBranches(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $status = $request->get('status', 'all');

        $query = Branch::withCount(['shifts', 'imports'])->orderBy('code');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $branches = $query->get();

        $stats = [
            'total'        => Branch::count(),
            'active'       => Branch::where('is_active', true)->count(),
            'total_shifts' => BranchShift::count(),
        ];

        return view('master.branches.index', compact('branches', 'search', 'status', 'stats'));
    }

    /**
     * Display a listing of branches with their shift schedules.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $branchId = $request->get('branch_id');

        $query = Branch::with(['shifts'])->orderBy('code');

        if ($branchId) {
            $query->where('id', $branchId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $branches = $query->get();
        $allBranches = Branch::orderBy('code')->get(['id', 'code', 'name']);

        return view('master.shifts.index', compact('branches', 'allBranches', 'search', 'branchId'));
    }

    /**
     * Store a newly created branch.
     */
    public function storeBranch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:branches,code'],
            'name'        => ['required', 'string', 'max:100'],
            'timezone'    => ['required', 'string', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'code.unique' => 'Kode cabang sudah terdaftar.',
            'code.required' => 'Kode cabang wajib diisi.',
            'name.required' => 'Nama cabang wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = true;

        $branch = Branch::create($validated);

        ActivityLog::record(
            ActivityLog::ACTION_USER_CREATE,
            "Menambahkan master cabang baru: {$branch->name} ({$branch->code}).",
            ['branch_id' => $branch->id, 'code' => $branch->code],
            ActivityLog::LEVEL_SUCCESS
        );

        return redirect()->back()
            ->with('success', "Cabang {$branch->name} ({$branch->code}) berhasil ditambahkan.");
    }

    /**
     * Update an existing branch.
     */
    public function updateBranch(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch->id)],
            'name'        => ['required', 'string', 'max:100'],
            'timezone'    => ['required', 'string', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->has('is_active');

        $branch->update($validated);

        ActivityLog::record(
            ActivityLog::ACTION_USER_UPDATE,
            "Memperbarui master cabang: {$branch->name} ({$branch->code}).",
            ['branch_id' => $branch->id, 'code' => $branch->code],
            ActivityLog::LEVEL_INFO
        );

        return redirect()->back()
            ->with('success', "Data cabang {$branch->name} ({$branch->code}) berhasil diperbarui.");
    }

    /**
     * Toggle branch active status.
     */
    public function toggleBranch(Branch $branch): RedirectResponse
    {
        $branch->update(['is_active' => !$branch->is_active]);

        $status = $branch->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Cabang {$branch->name} ({$branch->code}) berhasil {$status}.");
    }

    /**
     * Delete a branch and its associated shifts.
     */
    public function destroyBranch(Branch $branch): RedirectResponse
    {
        $name = $branch->name;
        $code = $branch->code;

        $branch->delete();

        ActivityLog::record(
            ActivityLog::ACTION_USER_DELETE,
            "Menghapus master cabang: {$name} ({$code}) beserta seluruh shift terkait.",
            ['code' => $code, 'name' => $name],
            ActivityLog::LEVEL_WARNING
        );

        return redirect()->back()
            ->with('success', "Cabang {$name} ({$code}) berhasil dihapus.");
    }

    /**
     * Store a new shift for a branch.
     */
    public function storeShift(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'shift_name'        => ['required', 'string', 'max:50'],
            'day_type'          => ['nullable', 'string', Rule::in(['weekday', 'saturday', 'all'])],
            'start_time'        => ['required', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'end_time'          => ['required', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'cutoff_time'       => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'pre_shift_minutes' => ['required', 'integer', 'min:0', 'max:720'],
            'normalized_shift'  => ['nullable', 'string', 'max:50'],
            'crosses_midnight'  => ['nullable', 'boolean'],
        ], [
            'shift_name.required' => 'Nama shift wajib diisi.',
            'start_time.required' => 'Jam mulai wajib diisi dengan format HH:MM.',
            'end_time.required'   => 'Jam selesai wajib diisi dengan format HH:MM.',
        ]);

        $validated['branch_id'] = $branch->id;
        $validated['shift_name'] = strtoupper(trim($validated['shift_name']));
        $validated['day_type'] = $validated['day_type'] ?? 'weekday';
        $validated['cutoff_time'] = $validated['cutoff_time'] ?: $validated['end_time'];
        $validated['normalized_shift'] = $validated['normalized_shift'] ? strtoupper(trim($validated['normalized_shift'])) : $validated['shift_name'];
        $validated['crosses_midnight'] = $request->has('crosses_midnight');
        $validated['is_active'] = true;

        $shift = BranchShift::create($validated);

        ActivityLog::record(
            ActivityLog::ACTION_USER_CREATE,
            "Menambahkan jadwal shift {$shift->shift_name} ({$shift->day_type}: {$shift->start_time} - {$shift->end_time}) pada cabang {$branch->name}.",
            ['branch_id' => $branch->id, 'shift_id' => $shift->id, 'day_type' => $shift->day_type],
            ActivityLog::LEVEL_SUCCESS
        );

        return redirect()->route('master.shifts.index')
            ->with('success', "Shift {$shift->shift_name} berhasil ditambahkan untuk cabang {$branch->name}.");
    }

    /**
     * Update an existing shift.
     */
    public function updateShift(Request $request, BranchShift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'shift_name'        => ['required', 'string', 'max:50'],
            'day_type'          => ['nullable', 'string', Rule::in(['weekday', 'saturday', 'all'])],
            'start_time'        => ['required', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'end_time'          => ['required', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'cutoff_time'       => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'pre_shift_minutes' => ['required', 'integer', 'min:0', 'max:720'],
            'normalized_shift'  => ['nullable', 'string', 'max:50'],
            'crosses_midnight'  => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $validated['shift_name'] = strtoupper(trim($validated['shift_name']));
        $validated['day_type'] = $validated['day_type'] ?? 'weekday';
        $validated['cutoff_time'] = $validated['cutoff_time'] ?: $validated['end_time'];
        $validated['normalized_shift'] = $validated['normalized_shift'] ? strtoupper(trim($validated['normalized_shift'])) : $validated['shift_name'];
        $validated['crosses_midnight'] = $request->has('crosses_midnight');
        $validated['is_active'] = $request->has('is_active');

        $shift->update($validated);

        ActivityLog::record(
            ActivityLog::ACTION_USER_UPDATE,
            "Memperbarui jadwal shift {$shift->shift_name} pada cabang {$shift->branch?->name}.",
            ['shift_id' => $shift->id, 'branch_id' => $shift->branch_id],
            ActivityLog::LEVEL_INFO
        );

        return redirect()->route('master.shifts.index')
            ->with('success', "Shift {$shift->shift_name} berhasil diperbarui.");
    }

    /**
     * Delete a shift.
     */
    public function destroyShift(BranchShift $shift): RedirectResponse
    {
        $shiftName = $shift->shift_name;
        $branchName = $shift->branch?->name;

        $shift->delete();

        ActivityLog::record(
            ActivityLog::ACTION_USER_DELETE,
            "Menghapus shift {$shiftName} dari cabang {$branchName}.",
            ['shift_name' => $shiftName],
            ActivityLog::LEVEL_WARNING
        );

        return redirect()->route('master.shifts.index')
            ->with('success', "Shift {$shiftName} berhasil dihapus dari cabang {$branchName}.");
    }
}

