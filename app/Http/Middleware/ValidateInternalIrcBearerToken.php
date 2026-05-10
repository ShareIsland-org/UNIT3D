<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     suncokret
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInternalIrcBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('irc-auth.enabled')) {
            abort(404);
        }

        $configuredToken = (string) (config('irc-auth.internal_bearer_token') ?? '');
        $providedToken = $request->bearerToken();

        if ($configuredToken === '' || !\is_string($providedToken) || $providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'ok'     => false,
                'reason' => 'unauthorized_internal_client',
            ], 401);
        }

        return $next($request);
    }
}
