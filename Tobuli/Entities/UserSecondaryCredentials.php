<?php

namespace Tobuli\Entities;

use App\Events\UserSecondaryCredentialsPasswordChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Tobuli\Traits\Searchable;

class UserSecondaryCredentials extends AbstractEntity
{
    use Searchable;

    protected $table = 'user_secondary_credentials';

    protected $fillable = ['user_id', 'password', 'email', 'readonly'];

    protected $hidden = ['password', 'api_hash'];

    protected $searchable = ['email'];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::saving(function (UserSecondaryCredentials $cred) {
            if ($cred->isDirty('password')) {
                while (self::where([
                    'api_hash' => $hash = Hash::make("{$cred->email}:{$cred->password}")
                ])->first()) {
                }

                $cred->api_hash = $hash;
            }
        });

        static::updated(function (UserSecondaryCredentials $cred) {
            \Cache::forget('secondary_cred_' . $cred->id);
        });

        static::deleted(function (UserSecondaryCredentials $cred) {
            \Cache::forget('secondary_cred_' . $cred->id);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUserOwned(Builder $query, User $user): Builder
    {
        return $query->where(['user_id' => $user->id]);
    }

    public function scopeUserAccessible(Builder $query, User $user): Builder
    {
        return $query->whereHas('user', fn (Builder $query) => $query->userAccessible($user));
    }

    public function scopeUserControllable(Builder $query, User $user): Builder
    {
        return $query->whereHas('user', fn (Builder $query) => $query->userControllable($user));
    }

    public function setPasswordAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        $this->attributes['password'] = Hash::make($value);

        event(new UserSecondaryCredentialsPasswordChanged($this));
    }
}
