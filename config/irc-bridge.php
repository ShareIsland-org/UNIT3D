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
    'enabled' => env('IRC_BRIDGE_ENABLED', false),

    'record_foundation' => env('IRC_BRIDGE_RECORD_FOUNDATION', false),

    'transport' => 'irc',

    'announce' => [
        'pilot_enabled'  => env('IRC_BRIDGE_ANNOUNCE_PILOT_ENABLED', false),
        'shadow_only'    => env('IRC_BRIDGE_ANNOUNCE_SHADOW_ONLY', false),
        'channel'        => env('IRC_BRIDGE_ANNOUNCE_CHANNEL', '#announce'),
        'allowed_events' => array_values(array_filter(array_map(
            static fn (string $event): string => trim($event),
            explode(',', (string) env('IRC_BRIDGE_ANNOUNCE_ALLOWED_EVENTS', 'torrent-approved')),
        ))),
        'legacy_override_enabled' => env('IRC_BRIDGE_ANNOUNCE_LEGACY_OVERRIDE_ENABLED', false),
        'transport_mode'          => env('IRC_BRIDGE_ANNOUNCE_TRANSPORT_MODE', 'legacy'),
    ],

    'general' => [
        'stage'             => (int) env('IRC_BRIDGE_GENERAL_STAGE', 0),
        'chatroom_id'       => (int) env('IRC_BRIDGE_GENERAL_CHATROOM_ID', 1),
        'channel'           => env('IRC_BRIDGE_GENERAL_CHANNEL', '#General'),
        'transport_mode'    => 'external_persistent',
        'own_nick'          => env('IRC_BRIDGE_GENERAL_OWN_NICK', 'YUS'),
        'reject_bot_suffix' => env('IRC_BRIDGE_GENERAL_REJECT_BOT_SUFFIX', env('IRC_AUTH_ALLOWED_BOT_SUFFIX', '_BOT')),
    ],
];
