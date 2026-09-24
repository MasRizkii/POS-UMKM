<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\POS\Money;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected Money $money,
    ) {}

    public function index(Request $request): Response
    {
        $query = Shift::with('user:id,name')->latest('opened_at');

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        return Inertia::render('Shifts/Index', [
            'activeShift' => Shift::where('user_id', $request->user()->id)->where('status', 'open')->latest()->first(),
            'shifts' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $validated = $request->validate(['opening_cash' => ['required', 'numeric', 'min:0']]);

        try {
            DB::transaction(function () use ($request, $validated): void {
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

                if (Shift::where('user_id', $request->user()->id)->where('status', 'open')->exists()) {
                    abort(422, 'Anda masih memiliki shift aktif. Tutup shift tersebut terlebih dahulu.');
                }

                $openingCash = $this->money->fromMinor($this->money->toMinor($validated['opening_cash']));
                $shift = Shift::create([
                    'user_id' => $request->user()->id,
                    'opening_cash' => $openingCash,
                    'cash_sales' => '0.00',
                    'expected_cash' => $openingCash,
                    'status' => 'open',
                    'opened_at' => now(),
                ]);

                $this->auditLogger->log('OPEN_SHIFT', 'Shift', $shift->id, null, $shift->only(['opening_cash', 'status', 'opened_at']));
            });
        } catch (QueryException $exception) {
            if (Shift::where('user_id', $request->user()->id)->where('status', 'open')->exists()) {
                abort(422, 'Anda masih memiliki shift aktif. Tutup shift tersebut terlebih dahulu.');
            }

            throw $exception;
        }

        return back()->with('success', 'Shift berhasil dibuka.');
    }

    public function close(Request $request, Shift $shift): RedirectResponse
    {
        Gate::authorize('update', $shift);
        $validated = $request->validate(['actual_cash' => ['required', 'numeric', 'min:0']]);

        DB::transaction(function () use ($shift, $validated): void {
            $lockedShift = Shift::lockForUpdate()->findOrFail($shift->id);

            if (! $lockedShift->isOpen()) {
                abort(422, 'Shift ini sudah ditutup.');
            }

            $expectedCashMinor = $this->money->toMinor($lockedShift->opening_cash) + $this->money->toMinor($lockedShift->cash_sales);
            $actualCashMinor = $this->money->toMinor($validated['actual_cash']);
            $lockedShift->update([
                'expected_cash' => $this->money->fromMinor($expectedCashMinor),
                'actual_cash' => $this->money->fromMinor($actualCashMinor),
                'difference' => $this->money->fromMinor($actualCashMinor - $expectedCashMinor),
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            $this->auditLogger->log('CLOSE_SHIFT', 'Shift', $lockedShift->id, ['status' => 'open'], $lockedShift->only(['expected_cash', 'actual_cash', 'difference', 'status', 'closed_at']));
        });

        return back()->with('success', 'Shift berhasil ditutup.');
    }
}
