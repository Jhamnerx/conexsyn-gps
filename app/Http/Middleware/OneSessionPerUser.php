<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Tobuli\Entities\User;

class OneSessionPerUser
{
    private static ?Connection $redis = null;

    protected Guard $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!config('addon.one_session_per_user')) {
            return $next($request);
        }

        $user = $this->auth->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user instanceof User) {
            return $next($request);
        }

        if (!$user->only_one_session) {
            return $next($request);
        }

        if (self::hasOtherSession($user)) {
            $this->auth->logout();

            return redirect(route('login'))->with(['message' => trans('front.user_is_currently_logged_in')]);
        }

        self::rememberSession($user);

        return $next($request);
    }

    public static function hasOtherSession(User $user): bool
    {
        $redis = self::getRedisConnection();

        $cacheKey = self::getCacheKey($user);
        $cacheValue = $redis->get($cacheKey);

        $sid = Session::getId();

        return $cacheValue && $cacheValue !== $sid;
    }

    public static function rememberSession(User $user): void
    {
        $cacheKey = self::getCacheKey($user);

        $sid = Session::getId();

        self::getRedisConnection()
            ->set($cacheKey, $sid, 'ex', config('session.lifetime') * 60);
    }

    public static function forgetSession(User $user): void
    {
        $cacheKey = self::getCacheKey($user);

        self::getRedisConnection()->del($cacheKey);
    }

    private static function getCacheKey(User $user): string
    {
        return 'user_' . $user->id . '_sid';
    }

    private static function getRedisConnection(): Connection
    {
        return self::$redis ?: (self::$redis = Redis::connection('session'));
    }
}
