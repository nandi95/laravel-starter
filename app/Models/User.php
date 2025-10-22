<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Impersonate;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens,
        HasFactory,
        HasRoles,
        HasUlids,
        Impersonate,
        Notifiable,
        Notifiable,
        SoftDeletes;

    /**
     * The number of days to wait before deleting the user after soft deletion.
     */
    public const int DELETION_DELAY = 30;

    /**
     * The number of days to wait until user gets soft-deleted if their email isn't verified.
     */
    public const int VERIFICATION_DELAY = 7;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'avatar',
        'password',
        'ulid',
        'email_verified_at',
    ];

    /**
     * {@inheritDoc}
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * {@inheritDoc}
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed'
        ];
    }

    /**
     * @return HasMany<UserProvider>
     */
    public function userProviders(): HasMany
    {
        return $this->hasMany(UserProvider::class);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
