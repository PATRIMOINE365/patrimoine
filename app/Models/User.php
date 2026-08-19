<?php

namespace App\Models;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Represents an authenticated Patrimoine application user.
 *
 * Patrimoine users are administrative application accounts and are
 * deliberately separate from Parties such as owners, tenants and agents.
 *
 * API authentication is provided by Laravel Sanctum.
 */
#[Fillable([
    'name',
    'email',
    'phone',
    'password',
    'role',
    'is_active',
    'last_seen_release',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Convert stored attributes to their appropriate PHP representations.
     *
     * Passwords are automatically hashed whenever assigned through the
     * model, preventing plaintext passwords from being persisted.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine whether this user holds the supplied fixed application role.
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Determine whether this user may perform a central Patrimoine
     * capability according to their fixed application role.
     */
    public function canPerform(UserCapability $capability): bool
    {
        return $this->role->allows($capability);
    }

    /**
     * Determine whether this account is currently enabled.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
