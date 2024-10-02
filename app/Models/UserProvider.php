<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OauthProvider;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperUserProvider
 */
class UserProvider extends Model
{
    protected $fillable = [
        'provider_id',
        'name',
    ];

    #[\Override]
    public function casts(): array
    {
        return [
            'name' => OauthProvider::class,
        ];
    }

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
