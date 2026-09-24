<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ActiveAdminGuard;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected ActiveAdminGuard $activeAdminGuard,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $oldValues = $request->user()->only(['name', 'email']);
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();
        $this->auditLogger->log(
            'UPDATE_PROFILE',
            'User',
            $request->user()->id,
            $oldValues,
            $request->user()->only(['name', 'email']),
        );

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        DB::transaction(function () use ($user): void {
            $this->activeAdminGuard->ensureCanDeactivate($user);
            $oldValues = $user->only(['name', 'email', 'role', 'status']);
            $user->delete();
            $this->auditLogger->log('SOFT_DELETE_USER', 'User', $user->id, $oldValues);
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
