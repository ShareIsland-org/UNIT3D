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
    'enabled' => env('IRC_BRIDGE_WEBHOOK_ENABLED', false),

    'host' => env('IRC_BRIDGE_WEBHOOK_HOST', '127.0.0.1'),

    'port' => env('IRC_BRIDGE_WEBHOOK_PORT', 7646),

    'unix_socket' => env('IRC_BRIDGE_WEBHOOK_UNIX_SOCKET'),

    'api_token' => env('IRC_BRIDGE_WEBHOOK_API_TOKEN'),

    'inbound_bearer_token' => env('IRC_BRIDGE_GENERAL_INBOUND_BEARER_TOKEN'),

    'general_route' => env('IRC_BRIDGE_WEBHOOK_GENERAL_ROUTE', '/api/webhook/bridge/general'),

    'timeout_seconds' => (float) env('IRC_BRIDGE_WEBHOOK_TIMEOUT_SECONDS', 5),

    'connect_timeout_seconds' => (float) env('IRC_BRIDGE_WEBHOOK_CONNECT_TIMEOUT_SECONDS', 2),
];
