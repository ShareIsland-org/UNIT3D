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
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Controllers\API;

use App\Models\Gift;
use App\Models\User;
use App\Notifications\NewBon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BonController extends BaseController
{
    /**
     * Send BON points to another user via API.
     *
     * Used by the IRC bot (Sopel) to handle tips between users.
     * Does not post to site chat to avoid duplicates with the IRC bridge.
     *
     * POST /api/gifts
     *
     * Body JSON:
     * {
     *   "recipient_username": "mario",
     *   "bon": 100,
     *   "message": "Great quiz answer!",
     *   "irc_sender": "Mario"  // optional — IRC nick of the real sender, prepended to the message
     * }
     */
    final public function gift(Request $request): JsonResponse
    {
        $sender = $request->user();

        // Validation — mirrors StoreGiftRequest already used on the site
        $validated = $request->validate([
            'recipient_username' => [
                'required',
                'string',
                Rule::exists('users', 'username')->whereNot('username', $sender->username),
            ],
            'bon' => [
                'required',
                'numeric',
                'min:1',
                'max:'.$sender->seedbonus,
            ],
            'message' => [
                'required',
                'string',
                'max:255',
            ],
            'irc_sender' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $recipient = User::where('username', '=', $validated['recipient_username'])->sole();

        DB::transaction(function () use ($sender, $recipient, $validated): void {
            $sender->decrement('seedbonus', $validated['bon']);
            $recipient->increment('seedbonus', $validated['bon']);

            $gift = Gift::create([
                'bon'          => $validated['bon'],
                'sender_id'    => $sender->id,
                'recipient_id' => $recipient->id,
                'message'      => isset($validated['irc_sender'])
                    ? '[IRC tip da '.$validated['irc_sender'].'] '.$validated['message']
                    : $validated['message'],
            ]);

            // Internal site notification — recipient sees the alert
            // in their notification panel even without looking at IRC
            $recipient->notify((new NewBon($gift))->afterCommit());
        });

        return $this->sendResponse([
            'sender'    => $sender->username,
            'recipient' => $recipient->username,
            'bon'       => $validated['bon'],
            'message'   => $validated['message'],
        ], 'BON sent successfully.');
    }
}
