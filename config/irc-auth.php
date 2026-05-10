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

return [
    'enabled' => env('IRC_AUTH_ENABLED', false),

    'internal_bearer_token' => env('IRC_AUTH_INTERNAL_BEARER_TOKEN'),

    'allowed_bot_suffix' => env('IRC_AUTH_ALLOWED_BOT_SUFFIX', '_BOT'),

    'service_account_usernames' => array_values(array_filter(array_map(
        static fn (string $username): string => trim($username),
        explode(',', (string) env('IRC_AUTH_SERVICE_ACCOUNT_USERNAMES', 'YUS')),
    ))),
];
