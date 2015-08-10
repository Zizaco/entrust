<?php namespace Zizaco\Entrust;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

 class EntrustRole
 {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param null $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        if (! $request->user()->hasRole(explode('|', $roles))) {
            abort(403);
        }

        return $next($request);
    }
}
