<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLoggerService
{
    /**
     * Mencatat aksi sensitif ke dalam audit_logs.
     * Sesuai PRD AUDIT-1 & AUDIT-2: Mencatat login berhasil, void, open shift, close shift,
     * perubahan harga, perubahan settings, dan perubahan user.
     */
    public function log(string $action, ?string $entity = null, ?int $entityId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
}
