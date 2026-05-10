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

namespace App\Http\Controllers\API\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\IrcAuthRequest;
use App\Services\IrcAuthService;
use Illuminate\Http\JsonResponse;

class IrcAuthController extends Controller
{
    public function __invoke(IrcAuthRequest $request, IrcAuthService $ircAuthService): JsonResponse
    {
        return response()->json($ircAuthService->authenticate(
            username: (string) $request->string('username'),
            ircKey: (string) $request->string('irc_key'),
            requestedNick: (string) $request->string('requested_nick'),
            requestId: (string) $request->string('request_id'),
        ));
    }
}
