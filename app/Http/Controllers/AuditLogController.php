<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\POS\StoreTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(protected StoreTime $storeTime) {}

    public function index(Request $request): Response
    {
        $query = AuditLog::with('user:id,name,email,role')->latest('id');

        // 1. Search Action / Entity
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('entity', 'like', "%{$search}%");
            });
        }

        // 2. Filter User
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // 3. Filter Action Specific
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        $datePreset = $request->input('date_preset', 'all');
        $range = $this->storeTime->rangeFromRequest($request);
        if ($range !== null) {
            $query->where('created_at', '>=', $range[0])->where('created_at', '<', $range[1]);
        }

        // Pagination 20 item per halaman (PRD AUDIT-2)
        $audits = $query->paginate(20)->withQueryString();

        // Daftar pengguna untuk dropdown filter
        $users = User::withTrashed()->get(['id', 'name', 'role']);

        return Inertia::render('Audits/Index', [
            'audits' => $audits,
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'user_id' => $request->input('user_id', ''),
                'action' => $request->input('action', ''),
                'date_preset' => $datePreset,
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
        ]);
    }
}
