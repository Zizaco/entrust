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

class EntrustAbility
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
	 * @param \Illuminate\Http\Request $request
	 * @param Closure $next
	 * @param $roles
	 * @param $permissions
	 * @param bool $validateAll
	 * @return mixed
	 */
	public function handle($request, Closure $next, $roles, $permissions, $validateAll = false)
	{
		if (!is_array($roles)) {
			$roles = explode(self::DELIMITER, $roles);
		}

		if (!is_array($permissions)) {
			$permissions = explode(self::DELIMITER, $permissions);
		}

		if (!is_bool($validateAll)) {
			$validateAll = filter_var($validateAll, FILTER_VALIDATE_BOOLEAN);
		}

		if ($this->auth->guest() || !$request->user()->ability($roles, $permissions, [ 'validate_all' => $validateAll ])) {
			abort(403);
		}

		return $next($request);
	}
}
