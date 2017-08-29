<?php namespace Zizaco\Entrust\Middleware;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Closure;
use Illuminate\Contracts\Auth\Guard;

class EntrustPermission
{
	const DELIMITER = '|';

	protected $auth;

	/**
	 * Creates a new instance of the middleware.
	 *
	 * @param Guard $auth
	 */
	public function __construct(Guard $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Closure $next
	 * @param  $permissions
	 * @return mixed
	 */
	public function handle($request, Closure $next, $permissions)
	{
		if (!is_array($permissions)) {
			$permissions = explode(self::DELIMITER, $permissions);
		}

		//when do not use Auth::user(), use another table(e.g. "admins")
		if(!empty(config('entrust.auth'))){
            $userModelName = config('entrust.auth.model');
            $userModel = new $userModelName();
            $user = $userModel->where('id', session(config('entrust.auth.user_id_in_session')))->first();
            if (empty($user) || !$user->can($permissions)) {
				abort(403);
			}
        }else{
            //default
            if ($this->auth->guest() || !$request->user()->can($permissions)) {
				abort(403);
			}
        }

		return $next($request);
	}
}
