<?php namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\Device;
use Tobuli\Entities\User;
use Tobuli\Entities\UserSecondaryCredentials;
use Tobuli\Services\ScheduleService;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware{

    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    private $azure;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(\Illuminate\Contracts\Auth\Factory $auth, Azure $azure)
    {
        $this->auth = $auth;
        $this->azure = $azure;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        if ($this->auth->guest()) {
            return $this->redirect($request);
		}

        if (Auth::User() instanceof Device) {
            return $next($request);
        }

        if ($request->hasSession() && $secondaryCredId = $request->session()->get('secondary_cred_id')) {
            $credCacheKey = 'secondary_cred_' . $secondaryCredId;

            $secondaryCred = Cache::remember($credCacheKey, 300, fn() => UserSecondaryCredentials::find($secondaryCredId));

            if ($secondaryCred === null) {
                Auth::logout();

                return $this->redirect($request);
            }

            Auth::user()->setLoginSecondaryCredentials($secondaryCred);
        }

        if ($this->checkDisabledUser())
            return $this->redirect($request, trans('front.login_suspended'));

        if ($this->checkPasswordChange($request))
            return $this->redirect($request);

        if ($message = $this->checkLoginPeriods()) {
            return $this->redirect($request, $message);
        }

        setActingUser(Auth::User());

        if ($request->hasSession()) {
            $this->azure->handle($request, $next);
        }

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        throw new AuthenticationException(
            'Unauthenticated.', $guards, $this->redirectTo($request)
        );
    }

    public function terminate($request, $response)
    {
        $user = Auth::User();

        if ($user && strtotime($user->loged_at) < (time() - 1)) {
            User::where('id', $user->id)->update([
                'loged_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function checkDisabledUser()
    {
        return !$this->auth->user()->active;
    }

    private function checkPasswordChange($request)
    {
        if (!$request->hasSession())
            return null;

        $passwordHash = Auth::User()->password_hash;

        if (!$request->session()->has('hash')) {
            $request->session()->put('hash', $passwordHash);

            return false;
        }

        if ($request->session()->get('hash') === $passwordHash) {
            return false;
        }

        return $this->redirect($request);
    }

    private function checkLoginPeriods(): string
    {
        if (settings('login_periods.enabled')
            && Auth::User()->login_periods
            && ($scheduleService = new ScheduleService(Auth::User()->login_periods ?? []))->outSchedules(Carbon::now())
        ) {
            return trans('front.login_time_restricted_until', [
                'datetime' => $scheduleService->closestScheduleTime(Carbon::now())
            ]);
        }

        return '';
    }

	private function redirect(Request $request, $message = 'Unauthorized.')
    {
        if ( (!$this->auth->guest()) && $this->auth->guard() instanceof StatefulGuard) {
            $this->auth->logout();
        }

        if ($request->expectsJson() || $request->ajax())
            return response($message, 401);

        if (isPublic()) {
            return redirect()->guest(config('tobuli.frontend_login').'/?server='.config('app.server'));
        }

        if ($request->hasSession()) {
            $request->session()->forget('login_redirect');
            $request->session()->put('login_redirect', $request->getRequestUri());
        }

        return redirect(route('login'))->with(['message' => $message]);
    }
}
