<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppLogController extends Controller
{
    /**
     * Display the Application Log page.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $actionFilter = $request->get('action', '');
        $dateFilter = $request->get('date', '');

        $query = ActivityLog::query()->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                  ->orWhere('user_name', 'LIKE', "%{$search}%")
                  ->orWhere('action', 'LIKE', "%{$search}%");
            });
        }

        if ($actionFilter !== '') {
            if ($actionFilter === 'USER_MGMT') {
                $query->whereIn('action', [
                    ActivityLog::ACTION_USER_CREATE,
                    ActivityLog::ACTION_USER_UPDATE,
                    ActivityLog::ACTION_USER_DELETE,
                ]);
            } elseif ($actionFilter === 'AUTH') {
                $query->whereIn('action', [
                    ActivityLog::ACTION_LOGIN,
                    ActivityLog::ACTION_LOGOUT,
                ]);
            } else {
                $query->where('action', $actionFilter);
            }
        }

        if ($dateFilter !== '') {
            $query->whereDate('created_at', $dateFilter);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Summary Stats
        $stats = [
            'total'   => ActivityLog::count(),
            'today'   => ActivityLog::whereDate('created_at', today())->count(),
            'imports' => ActivityLog::where('action', ActivityLog::ACTION_IMPORT)->count(),
        ];

        return view('logs.index', compact(
            'logs',
            'search',
            'actionFilter',
            'dateFilter',
            'stats'
        ));
    }

    /**
     * Clear activity logs.
     */
    public function clearActivityLogs(): RedirectResponse
    {
        $count = ActivityLog::count();
        ActivityLog::truncate();

        ActivityLog::record(
            ActivityLog::ACTION_SYSTEM,
            "Riwayat log aktivitas ({$count} entri) telah dibersihkan oleh Admin.",
            [],
            ActivityLog::LEVEL_WARNING
        );

        return redirect()->route('logs.index')
            ->with('success', 'Riwayat log aktivitas berhasil dibersihkan.');
    }
}
