<?php 

namespace Zizaco\Entrust\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */
class EntrustRole
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
     * @param  Closure $next
     * @param  string|array  $roles
     * @return Response
     */
    public function handle($request, Closure $next, $roles): Response
    {
        $roles = $this->normalizeRoles($roles);

        if ($this->unauthorized($roles)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * Normalize roles to array format
     */
    protected function normalizeRoles($roles): array
    {
        if (is_array($roles)) {
            return $roles;
        }

        return explode(self::DELIMITER, (string)($roles ?? ''));
    }

    /**
     * Check if user is unauthorized
     */
    protected function unauthorized(array $roles): bool
    {
        return $this->auth->guest() || !$this->auth->user()->hasRole($roles);
    }
}
