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
        $user = User::with(['privacy', 'group'])
            ->withCount([
                'seedingTorrents',
                'leechingTorrents',
                // Non-anonymous uploads only, matching what the profile page shows
                // to regular users in the public "Torrent count" section.
                'torrents as non_anon_uploads_count' => fn ($q) => $q->where('anon', false),
            ])
            ->where('username', $username)
            ->first();

        // User not found
        if ($user === null) {
            return $this->sendError('User not found.', [], 404);
        }

        // Block if the user is completely hidden (hidden = true) or has set their profile
        // to private (private_profile = 1). These are two distinct flags: isVisible() covers
        // the former, isAllowed() covers the latter.
        if (!auth()->user()->isVisible($user) || !auth()->user()->isAllowed($user)) {
            return $this->sendError('This profile is private.', [], 403);
        }

        // Returns $value if the given profile privacy field is enabled, otherwise 'private'.
        // Uses isAllowed() with the profile-specific field names that the profile page uses,
        // so API behaviour matches what is visible on the site.
        $v = fn (string $type, mixed $value) => auth()->user()->isAllowed($user, 'profile', $type)
            ? $value
            : 'private';

        return $this->sendResponse([
            // Always visible if the profile is not blocked above.
            'username'    => $user->username,
            'group'       => $user->group->name,
            'profile_url' => route('users.show', ['user' => $user->username]),

            // Traffic stats: all gated by show_profile_torrent_ratio, matching
            // the "Traffic Statistics" section on the profile page.
            'uploaded'    => $v('show_profile_torrent_ratio', str_replace("\u{00A0}", ' ', $user->formatted_uploaded)),
            'downloaded'  => $v('show_profile_torrent_ratio', str_replace("\u{00A0}", ' ', $user->formatted_downloaded)),
            'ratio'       => $v('show_profile_torrent_ratio', $user->formatted_ratio),
            'buffer'      => $v('show_profile_torrent_ratio', str_replace("\u{00A0}", ' ', $user->formatted_buffer)),

            // Peer stats: same flag as the seeding section on the profile page.
            'seeding'     => $v('show_profile_torrent_seed', $user->seeding_torrents_count),
            'leeching'    => $v('show_profile_torrent_seed', $user->leeching_torrents_count),

            // Non-anonymous upload count: matches what the profile page shows to regular
            // users in its public "Torrent count" section (anon=false, no status filter).
            'uploads'      => $v('show_profile_torrent_count', $user->non_anon_uploads_count),

            // BON (Bonus Points) and warnings.
            'seedbonus'    => $v('show_profile_bon_extra', str_replace("\u{202F}", ' ', $user->formatted_seedbonus)),
            'hit_and_runs' => $v('show_profile_warning', $user->hitandruns),
        ], 'User profile retrieved successfully.');
    }
}
