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

final class UserProfileController extends BaseController
{
    /**
     * Show a user's public profile by username.
     *
     * GET /api/users/{username}
     */
    final public function show(string $username): JsonResponse
    {
        // Retrieve the user with the necessary relations
        $user = User::with(['privacy', 'group'])
            ->withCount(['seedingTorrents', 'leechingTorrents'])
            ->where('username', $username)
            ->first();

        // User not found
        if ($user === null) {
            return $this->sendError('User not found.', [], 404);
        }

        // Fully hidden profile (hidden = true)
        // isVisible() with only the target and no type checks the hidden flag
        if (!auth()->user()->isVisible($user)) {
            return $this->sendError('This profile is private.', [], 403);
        }

        // Helper: returns the value if the privacy field is visible,
        // otherwise the string "private".
        // isVisible($target, $group, $type) checks $target->privacy->$type
        $v = fn (string $type, mixed $value) => auth()->user()->isVisible($user, 'profile', $type)
            ? $value
            : 'private';

        return $this->sendResponse([
            // Base data — always visible if the profile is not hidden
            'username'     => $user->username,
            'group'        => $user->group->name,

            // Statistics subject to privacy settings
            'uploaded'     => $v('show_upload', str_replace("\u{00A0}", ' ', $user->formatted_uploaded)),
            'downloaded'   => $v('show_download', str_replace("\u{00A0}", ' ', $user->formatted_downloaded)),
            'ratio'        => $v('show_upload', $user->formatted_ratio),   // ratio depends on upload
            'buffer'       => $v('show_upload', str_replace("\u{00A0}", ' ', $user->formatted_buffer)),
            'seeding'      => $v('show_peer', $user->seeding_torrents_count),
            'leeching'     => $v('show_peer', $user->leeching_torrents_count),
            'seedbonus'    => $v('show_bon', $user->seedbonus),
            'hit_and_runs' => $v('show_profile_warning', $user->hitandruns),
        ], 'User profile retrieved successfully.');
    }
}
