<?php 

namespace Zizaco\Entrust\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class EntrustAbility
{
    const DELIMITER = '|';

    /**
     * The guard instance.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new middleware instance.
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
     * @param  string|array  $roles
     * @param  string|array  $permissions
     * @param  bool|string  $validateAll
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next, $roles, $permissions, $validateAll = false)
    {
        $roles = $this->normalizeInput($roles);
        $permissions = $this->normalizeInput($permissions);
        $validateAll = $this->normalizeBoolean($validateAll);

        if ($this->unauthorized($request, $roles, $permissions, $validateAll)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * Normalize the input to an array.
     *
     * @param  string|array  $input
     * @return array
     */
    protected function normalizeInput($input): array
    {
        if (is_array($input)) {
            return $input;
        }

        return explode(self::DELIMITER, $input ?? '');
    }

    /**
     * Normalize a boolean input.
     *
     * @param  bool|string  $value
     * @return bool
     */
    protected function normalizeBoolean($value): bool
    {
        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Determine if the request is unauthorized.
     *
     * @param  Request  $request
     * @param  array  $roles
     * @param  array  $permissions
     * @param  bool  $validateAll
     * @return bool
     */
    protected function unauthorized(Request $request, array $roles, array $permissions, bool $validateAll): bool
    {
        return $this->auth->guest() || 
               !$request->user()->ability($roles, $permissions, ['validate_all' => $validateAll]);
    }
}
