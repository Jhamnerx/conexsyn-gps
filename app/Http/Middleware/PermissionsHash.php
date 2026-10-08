<?php namespace App\Http\Middleware;

use Closure;
use App;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tobuli\Entities\User;

class PermissionsHash {

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

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
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        /** @var User $user */
        $user = $this->auth->user();

        if ($user && !($response instanceof BinaryFileResponse)) {
            $hash = md5(json_encode($user->getPermissions()));
            $response->header('X-Permission-Hash', $hash);
        }

        return $response;
    }

}
