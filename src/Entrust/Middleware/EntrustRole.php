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
use Illuminate\Support\Facades\Config;

class EntrustRole
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
	 * @param  $roles
	 * @return mixed
	 */
	public function handle($request, Closure $next, $roles)
	{
		if (!is_array($roles)) {
			$roles = explode(self::DELIMITER, $roles);
		}

		//when do not use Auth::user(), use another table(e.g. "admins")
		if(!empty(Config::get('entrust.auth'))){
			$userModelName = Config::get('entrust.auth.model');
			$userModel = new $userModelName();
			$user = $userModel->where('id', session(Config::get('entrust.auth.user_id_in_session')))->first();
			if (empty($user) || !$user->hasRole($roles)) {
				abort(403);
			}
		}else{
			//default
			if ($this->auth->guest() || !$request->user()->hasRole($roles)) {
				abort(403);
			}
		}

		return $next($request);
	}
}
