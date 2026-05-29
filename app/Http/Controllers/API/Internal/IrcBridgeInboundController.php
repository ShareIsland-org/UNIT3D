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
use App\Http\Requests\Internal\IrcBridgeInboundRequest;
use App\Services\IrcBridge\IrcGeneralInboundPersistenceService;
use Illuminate\Http\JsonResponse;

class IrcBridgeInboundController extends Controller
{
    public function __invoke(IrcBridgeInboundRequest $request, IrcGeneralInboundPersistenceService $service): JsonResponse
    {
        return response()->json($service->persist($request->validated()));
    }
}
