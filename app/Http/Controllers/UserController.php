<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActiveAdminGuard;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger, protected ActiveAdminGuard $activeAdminGuard
    ) {}

    public function index(Request $request): Response
    {
        $query = User::latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Pagination 15 pengguna per halaman (PRD USER-1)
        $users = $query->paginate(15)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,cashier'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        $this->auditLogger->log(
            action: 'CREATE_USER',
            entity: 'User',
            entityId: $user->id,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ]
        );

        return back()->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,cashier'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            if ($user->isAdmin() && $user->status === 'active' && ($validated['role'] !== 'admin' || $validated['status'] !== 'active')) {
                $this->activeAdminGuard->ensureCanDeactivate($user);
            }

            $oldValues = $user->only(['name', 'email', 'role', 'status']);
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->status = $validated['status'];

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            $this->auditLogger->log(
                action: 'UPDATE_USER',
                entity: 'User',
                entityId: $user->id,
                oldValues: $oldValues,
                newValues: $user->only(['name', 'email', 'role', 'status'])
            );
        });

        return back()->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Safeguard 1: Pengguna tidak boleh menghapus akunnya sendiri
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan atau menghapus akun Anda sendiri.');
        }

        DB::transaction(function () use ($user): void {
            $this->activeAdminGuard->ensureCanDeactivate($user);
            $user->delete();
            $this->auditLogger->log(
                action: 'SOFT_DELETE_USER',
                entity: 'User',
                entityId: $user->id,
                oldValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            );
        });

        return back()->with('success', "Pengguna {$user->name} berhasil dinonaktifkan (Soft Delete). Histori transaksi kasir tetap aman.");
    }
}
