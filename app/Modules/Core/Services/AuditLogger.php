<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * AuditLogger.
 *
 * Single entry point for writing audit records. Every row is:
 *   - chained: previous_hash references the latest row's signature
 *   - signed:  HMAC-SHA256 over canonical payload + previous_hash
 *
 * Synchronous by design. A queued audit is an audit you may lose on
 * crash; HOSTO cannot afford that.
 *
 * @see docs/adr/0004-audit-trail-global.md
 */
final class AuditLogger
{
    /**
     * Supported action verbs. Kept as constants to prevent typos that
     * would make future queries miss rows.
     */
    public const ACTION_READ = 'read';

    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGIN_FAILED = 'login.failed';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_CONSENT_GRANTED = 'consent.granted';

    public const ACTION_CONSENT_REVOKED = 'consent.revoked';

    public const ACTION_2FA_ENABLED = '2fa.enabled';

    public const ACTION_2FA_CHALLENGED = '2fa.challenged';

    /**
     * Record an audited event.
     *
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        string $action,
        ?string $resourceType = null,
        ?string $resourceUuid = null,
        ?array $payload = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): void {
        if (! config('hosto.audit.enabled', true)) {
            return;
        }

        $request = $request ?? request();
        /** @var User|null $user */
        $user = $request->user();

        DB::transaction(function () use (
            $action,
            $resourceType,
            $resourceUuid,
            $payload,
            $metadata,
            $request,
            $user,
        ): void {
            $previousHash = $this->fetchLatestSignature();

            $uuid = (string) Str::uuid7();
            $occurredAt = now();

            // Canonical form for signature (raw bytes).
            $canonicalParts = [
                $uuid,
                $occurredAt->toIso8601String(),
                $user?->uuid,
                $action,
                $resourceType,
                $resourceUuid,
                $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                $previousHash !== null ? bin2hex($previousHash) : null,
            ];
            $canonical = json_encode($canonicalParts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $signature = hash_hmac('sha256', $canonical, $this->signingKey(), true);

            // PostgreSQL bytea requires hex input; use parameterized raw SQL.
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::insert(
                    'INSERT INTO audit_logs (
                        uuid, occurred_at, actor_uuid, actor_type, actor_ip, actor_ua,
                        session_uuid, action, resource_type, resource_uuid, structure_uuid,
                        payload, metadata, previous_hash, signature
                    ) VALUES (?, ?, ?, ?, ?::inet, ?, ?, ?, ?, ?, ?, ?::jsonb, ?::jsonb, decode(?, \'hex\'), decode(?, \'hex\'))',
                    [
                        $uuid,
                        $occurredAt,
                        $user?->uuid,
                        $this->resolveActorType($user),
                        $request->ip(),
                        $request->userAgent(),
                        $request->attributes->get('session_uuid'),
                        $action,
                        $resourceType,
                        $resourceUuid,
                        config('hosto.structure_uuid'),
                        $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                        $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                        $previousHash !== null ? bin2hex($previousHash) : null,
                        bin2hex($signature),
                    ],
                );

                return;
            }

            // SQLite fallback (tests).
            DB::table('audit_logs')->insert([
                'uuid' => $uuid,
                'occurred_at' => $occurredAt,
                'actor_uuid' => $user?->uuid,
                'actor_type' => $this->resolveActorType($user),
                'actor_ip' => $request->ip(),
                'actor_ua' => $request->userAgent(),
                'session_uuid' => $request->attributes->get('session_uuid'),
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_uuid' => $resourceUuid,
                'structure_uuid' => config('hosto.structure_uuid'),
                'payload' => $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'metadata' => $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'previous_hash' => $previousHash,
                'signature' => $signature,
            ]);
        });
    }

    private function resolveActorType(mixed $user): string
    {
        if ($user === null) {
            return 'system';
        }

        // Will be extended in Phase 3 (Usager) and Phase 5 (Pro) to return
        // 'patient', 'professionnel', 'admin', etc. based on user->role.
        return 'authenticated';
    }

    private function fetchLatestSignature(): ?string
    {
        $latest = DB::table('audit_logs')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(1)
            ->value('signature');

        if ($latest === null) {
            return null;
        }

        // PostgreSQL bytea may come as resource or hex-prefixed string.
        if (is_resource($latest)) {
            return stream_get_contents($latest);
        }

        // "\x..." prefix on pgsql ; raw bytes on sqlite.
        if (is_string($latest) && str_starts_with($latest, '\x')) {
            return hex2bin(substr($latest, 2));
        }

        return (string) $latest;
    }

    private function signingKey(): string
    {
        $key = config('hosto.audit.signing_key') ?: config('app.key');

        if (empty($key)) {
            throw new RuntimeException(
                'HOSTO_AUDIT_SIGNING_KEY (or APP_KEY fallback) is required to sign audit logs.',
            );
        }

        // APP_KEY is base64:XXX ; strip prefix for raw bytes
        if (str_starts_with($key, 'base64:')) {
            return (string) base64_decode(substr($key, 7), true);
        }

        return $key;
    }
}
