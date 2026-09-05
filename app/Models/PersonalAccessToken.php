<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * One signed-in device.
 *
 * Sanctum's own model is a bearer credential and nothing more. Patrimoine
 * needs the row to answer a question a person asks out loud — "which of
 * these is the phone I lost?" — so it also carries what the token was
 * minted for and how long it may go on living.
 *
 * @property string|null $client_type
 * @property string|null $platform
 * @property string|null $app_version
 * @property string|null $created_ip
 * @property string|null $last_used_ip
 * @property \Illuminate\Support\Carbon|null $absolute_expires_at
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Clients Patrimoine mints tokens for.
     */
    public const CLIENT_WEB = 'web';

    public const CLIENT_MOBILE = 'mobile';

    public const CLIENT_API = 'api';

    /**
     * V1.0.51: not a client that asks for it — the policy every token
     * minted for platform staff is stamped with, whatever client asked.
     */
    public const CLIENT_PLATFORM = 'platform';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'client_type',
        'platform',
        'app_version',
        'created_ip',
        'last_used_ip',
        'absolute_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'json',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'absolute_expires_at' => 'datetime',
        ];
    }

    /**
     * The lifetime policy this token is governed by.
     *
     * @return array{idle: int, absolute: int}
     */
    public function lifetimePolicy(): array
    {
        return self::policyFor(
            $this->client_type ?? self::CLIENT_WEB
        );
    }

    /**
     * The lifetime policy for a client.
     *
     * @return array{idle: int, absolute: int}
     */
    public static function policyFor(string $clientType): array
    {
        $policy = config(
            'patrimoine.tokens.'.$clientType,
            config('patrimoine.tokens.web')
        );

        return [
            'idle' => (int) ($policy['idle'] ?? 60 * 12),
            'absolute' => (int) ($policy['absolute'] ?? 60 * 24 * 30),
        ];
    }

    /**
     * Whether this token is the one authenticating the current request.
     */
    public function isCurrent(): bool
    {
        $current = request()?->user()?->currentAccessToken();

        return $current instanceof self
            && $current->getKey() === $this->getKey();
    }

    /**
     * A device row, for the person deciding whether to revoke it.
     *
     * The token itself is not here and cannot be: only its hash is
     * stored, and that is the point of storing it that way.
     *
     * @return array<string, mixed>
     */
    public function toDevice(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'client_type' => $this->client_type,
            'platform' => $this->platform,
            'app_version' => $this->app_version,
            'created_ip' => $this->created_ip,
            'last_used_ip' => $this->last_used_ip,
            'created_at' => $this->created_at,
            'last_used_at' => $this->last_used_at,
            'expires_at' => $this->expires_at,
            'absolute_expires_at' => $this->absolute_expires_at,
            'is_current' => $this->isCurrent(),
        ];
    }

}
