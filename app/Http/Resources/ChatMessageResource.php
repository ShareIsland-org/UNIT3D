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
 * @author     HDVinnie <hdinnovations@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Resources;

use App\Helpers\Bbcode;
use App\Helpers\Linkify;
use hdvinnie\LaravelJoyPixels\LaravelJoyPixels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Message
 */
class ChatMessageResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     bot: BotResource,
     *     user: ChatUserResource,
     *     receiver: ChatUserResource,
     *     chatroom: ChatRoomResource,
     *     message: string,
     *     source: string,
     *     remote_nick: string|null,
     *     created_at: string,
     *     updated_at: string,
     * }
     */
    public function toArray(Request $request): array
    {
        $emojiOne = new LaravelJoyPixels();
        $bridgeMessage = $this->relationLoaded('ircBridgeMessage') ? $this->resource->getRelation('ircBridgeMessage') : null;
        $isInboundIrcMessage = $bridgeMessage !== null && $bridgeMessage->direction === 'inbound';

        $logger = $isInboundIrcMessage
            ? $this->renderIrcMessage($emojiOne)
            : $this->renderWebMessage($emojiOne);

        return [
            'id'          => $this->id,
            'bot'         => new BotResource($this->whenLoaded('bot')),
            'user'        => new ChatUserResource($this->whenLoaded('user')),
            'receiver'    => new ChatUserResource($this->whenLoaded('receiver')),
            'chatroom'    => new ChatRoomResource($this->whenLoaded('chatroom')),
            'message'     => $logger,
            'source'      => $isInboundIrcMessage ? 'irc' : 'web',
            'remote_nick' => $isInboundIrcMessage ? $bridgeMessage?->irc_nick : null,
            'created_at'  => $this->created_at->toIso8601String(),
            'updated_at'  => $this->updated_at->toIso8601String(),
        ];
    }

    private function renderWebMessage(LaravelJoyPixels $emojiOne): string
    {
        $bbcode = new Bbcode();
        $logger = $bbcode->parse($this->message);
        $logger = $emojiOne->toImage($logger);

        if ($this->user_id == 1) {
            $logger = str_replace('a href="/#', 'a trigger="bot" class="chatTrigger" href="/#', $logger);
        }

        return $logger;
    }

    private function renderIrcMessage(LaravelJoyPixels $emojiOne): string
    {
        $escaped = e($this->message);
        $linked = app(Linkify::class)->linky($escaped);

        return $emojiOne->toImage($linked);
    }
}
