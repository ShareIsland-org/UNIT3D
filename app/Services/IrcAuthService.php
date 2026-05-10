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

namespace App\Services;

class IrcAuthService
{
    public function __construct(private readonly IrcIdentityService $identityService)
    {
    }

    /**
     * @return array{ok: bool, canonical_username?: string, account_name?: string, allowed_nick?: string, nick_type?: string, request_id: string, reason?: string}
     */
    public function authenticate(string $username, string $ircKey, string $requestedNick, string $requestId): array
    {
        $user = $this->identityService->findByCanonicalUsername($username);

        if ($user === null) {
            return $this->failure('unknown_user', $requestId);
        }

        if (!hash_equals($user->irc_key, $ircKey)) {
            return $this->failure('invalid_irc_key', $requestId);
        }

        $nickType = $this->identityService->classifyRequestedNick($user, $requestedNick);

        if ($nickType === null) {
            return $this->failure('invalid_requested_nick', $requestId);
        }

        $group = $user->group;
        $groupSlug = $group?->slug;

        if ($groupSlug === 'banned') {
            return $this->failure('banned', $requestId);
        }

        if ($groupSlug === 'pruned') {
            return $this->failure('pruned', $requestId);
        }

        if ($groupSlug === 'guest') {
            return $this->failure('guest', $requestId);
        }

        if ($groupSlug === 'validating') {
            return $this->failure('validating', $requestId);
        }

        if ($user->trashed()) {
            return $this->failure('soft_deleted', $requestId);
        }

        $canChat = $user->can_chat ?? $group?->can_chat ?? false;

        if ($groupSlug === 'disabled' || $user->disabled_at !== null || (!$canChat && !$this->isServiceAccount($user->username))) {
            return $this->failure('chat_disabled', $requestId);
        }

        return [
            'ok'                 => true,
            'canonical_username' => $user->username,
            'account_name'       => $user->username,
            'allowed_nick'       => $this->identityService->allowedNick($user, $nickType),
            'nick_type'          => $nickType,
            'request_id'         => $requestId,
        ];
    }

    private function isServiceAccount(?string $username): bool
    {
        if ($username === null) {
            return false;
        }

        return \in_array($username, config('irc-auth.service_account_usernames', []), true);
    }

    /**
     * @return array{ok: false, reason: string, request_id: string}
     */
    private function failure(string $reason, string $requestId): array
    {
        return [
            'ok'         => false,
            'reason'     => $reason,
            'request_id' => $requestId,
        ];
    }
}
