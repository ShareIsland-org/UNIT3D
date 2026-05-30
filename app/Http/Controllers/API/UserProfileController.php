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

use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserProfileController extends BaseController
{
    /**
     * Mostra il profilo pubblico di un utente per username.
     *
     * GET /api/users/{username}
     */
    final public function show(string $username): JsonResponse
    {
        // Recupera l'utente con le relazioni necessarie
        $user = User::with(['privacy', 'group', 'seedingTorrents', 'leechingTorrents'])
            ->where('username', $username)
            ->first();

        // Utente non trovato
        if ($user === null) {
            return $this->sendError('User not found.', [], 404);
        }

        // Profilo completamente nascosto (hidden = true)
        // isVisible() con solo il target e nessun type controlla il flag hidden
        if (!auth()->user()->isVisible($user)) {
            return $this->sendError('This profile is private.', [], 403);
        }

        // Helper: restituisce il valore se il campo privacy è visibile,
        // altrimenti la stringa "private".
        // isVisible($target, $group, $type) controlla $target->privacy->$type
        $v = fn (string $type, mixed $value) => auth()->user()->isVisible($user, 'profile', $type)
            ? $value
            : 'private';

        return $this->sendResponse([
            // Dati base — sempre visibili se il profilo non è hidden
            'username'     => $user->username,
            'group'        => $user->group->name,

            // Statistiche soggette alle impostazioni di privacy
            'uploaded'     => $v('show_upload', str_replace("\u{00A0}", ' ', $user->formatted_uploaded)),
            'downloaded'   => $v('show_download', str_replace("\u{00A0}", ' ', $user->formatted_downloaded)),
            'ratio'        => $v('show_upload', $user->formatted_ratio),   // ratio dipende da upload
            'buffer'       => $v('show_upload', str_replace("\u{00A0}", ' ', $user->formatted_buffer)),
            'seeding'      => $v('show_peer', \count($user->seedingTorrents)),
            'leeching'     => $v('show_peer', \count($user->leechingTorrents)),
            'seedbonus'    => $v('show_bon', $user->seedbonus),
            'hit_and_runs' => $v('show_profile_warning', $user->hitandruns),
        ], 'User profile retrieved successfully.');
    }
}
