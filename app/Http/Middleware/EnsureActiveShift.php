<?php

namespace App\Http\Middleware;

use App\Models\Shift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveShift
{
    /**
     * Memastikan kasir wajib memiliki shift yang sedang aktif sebelum mengakses/memproses POS.
     * Sesuai PRD SHIFT-1: Kasir wajib membuka shift sebelum dapat memproses transaksi POS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'cashier') {
            $activeShift = Shift::where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if (! $activeShift) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Anda belum memiliki shift aktif. Silakan buka shift terlebih dahulu.',
                    ], 422);
                }

                return redirect()->route('shifts.index')->with('warning', 'Silakan buka shift terlebih dahulu sebelum memproses POS.');
            }
        }

        return $next($request);
    }
}
