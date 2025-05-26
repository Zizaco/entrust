<?php

namespace Zizaco\Entrust\Middleware;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class EntrustPermission
{
    const DELIMITER = '|';

    protected Guard $auth;

    /**
     * Creates a new instance of the middleware.
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
     * @param  string|array|null  $permissions
     * @return mixed
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle(Request $request, Closure $next, $permissions)
    {
        $permissions = $this->normalizePermissions($permissions);

        if ($this->auth->guest() || !$request->user()->can($permissions)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * Normalize permissions to array format.
     *
     * @param  string|array|null  $permissions
     * @return array
     */
    protected function normalizePermissions($permissions): array
    {
        if (is_array($permissions)) {
            return $permissions;
        }

        return explode(self::DELIMITER, (string)($permissions ?? ''));
    }
}
