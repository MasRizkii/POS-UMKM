<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
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

        // 4. Filter Rentang Waktu
        $datePreset = $request->input('date_preset', 'all');
        $now = Carbon::now();

        if ($datePreset === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($datePreset === 'yesterday') {
            $query->whereDate('created_at', Carbon::yesterday());
        } elseif ($datePreset === '7days') {
            $query->where('created_at', '>=', Carbon::today()->subDays(7));
        } elseif ($datePreset === 'month') {
            $query->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
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
