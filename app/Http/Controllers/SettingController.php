<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    public function index(): Response
    {
        $setting = Setting::current();

        return Inertia::render('Settings/Index', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'currency' => ['required', 'in:IDR'],
            'invoice_prefix' => ['required', 'string', 'alpha_dash', 'max:10'],
            'timezone' => ['required', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
            'tax_enabled' => ['required', 'boolean'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge_enabled' => ['required', 'boolean'],
            'service_charge_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'cash_enabled' => ['required', 'boolean'],
            'qris_enabled' => ['required', 'boolean'],
        ]);

        // Aturan Bisnis PRD SET-4: Minimal satu metode bayar harus tetap aktif
        if (! $validated['cash_enabled'] && ! $validated['qris_enabled']) {
            return back()->withErrors([
                'cash_enabled' => 'Minimal satu metode pembayaran (Tunai atau QRIS Fisik) harus tetap aktif.',
            ]);
        }

        $setting = Setting::current();
        $oldValues = $setting->toArray();

        if ($request->hasFile('store_logo')) {
            $validated['store_logo'] = '/storage/'.$request->file('store_logo')->store('logos', 'public');
        } else {
            unset($validated['store_logo']);
        }

        $setting->update($validated);

        // Audit Log (PRD SET-5)
        $this->auditLogger->log(
            action: 'UPDATE_SETTINGS',
            entity: 'Setting',
            entityId: $setting->id,
            oldValues: $oldValues,
            newValues: $validated
        );

        return back()->with('success', 'Pengaturan toko berhasil diperbarui.');
    }
}
