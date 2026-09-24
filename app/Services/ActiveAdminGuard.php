<?php

namespace App\Services;

use App\Models\User;

class ActiveAdminGuard
{
    public function ensureCanDeactivate(User $user): void
    {
        if (! $user->isAdmin() || $user->status !== 'active') {
            return;
        }

        $activeAdminIds = User::where('role', 'admin')
            ->where('status', 'active')
            ->lockForUpdate()
            ->pluck('id');

        if ($activeAdminIds->count() <= 1 && $activeAdminIds->contains($user->id)) {
            abort(422, 'Sistem wajib memiliki minimal satu admin aktif.');
        }
    }
}
