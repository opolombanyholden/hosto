<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Core\Models\ImpersonationSession;
use Illuminate\Http\Request;

final class ImpersonationService
{
    public function start(User $admin, User $target, string $reason, Request $request): ImpersonationSession
    {
        if ($admin->id === $target->id) {
            throw new \DomainException('Cannot impersonate yourself');
        }
        if ($this->isSuperAdmin($target)) {
            throw new \DomainException('Cannot impersonate a super_admin');
        }
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException('reason is required');
        }

        return ImpersonationSession::create([
            'admin_user_id' => $admin->id,
            'target_user_id' => $target->id,
            'reason' => $reason,
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }

    public function stop(): void
    {
        $session = $this->currentSession();
        if ($session) {
            $session->update(['ended_at' => now()]);
        }
        session()->forget(['impersonation_session_id', 'impersonator_id']);
    }

    public function currentSession(): ?ImpersonationSession
    {
        $uuid = session('impersonation_session_id');
        if (! $uuid) {
            return null;
        }

        return ImpersonationSession::where('uuid', $uuid)->whereNull('ended_at')->first();
    }

    public function isImpersonating(): bool
    {
        return $this->currentSession() !== null;
    }

    private function isSuperAdmin(User $u): bool
    {
        return $u->hasRole('super_admin');
    }
}
