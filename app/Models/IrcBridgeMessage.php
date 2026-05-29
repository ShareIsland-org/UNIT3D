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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\IrcBridgeMessage.
 *
 * @property int                             $id
 * @property string                          $direction
 * @property string                          $transport
 * @property int|null                        $local_message_id
 * @property int|null                        $chatroom_id
 * @property string|null                     $remote_channel
 * @property string|null                     $external_message_id
 * @property string                          $idempotency_key
 * @property string|null                     $canonical_username
 * @property string|null                     $irc_nick
 * @property string                          $delivery_state
 * @property string|null                     $payload_hash
 * @property array<string, mixed>|null       $provenance
 * @property \Illuminate\Support\Carbon|null $transport_submission_at
 * @property int|null                        $transport_response_code
 * @property string|null                     $transport_error
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class IrcBridgeMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'direction',
        'transport',
        'local_message_id',
        'chatroom_id',
        'remote_channel',
        'external_message_id',
        'idempotency_key',
        'canonical_username',
        'irc_nick',
        'delivery_state',
        'payload_hash',
        'provenance',
        'transport_submission_at',
        'transport_response_code',
        'transport_error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provenance'              => 'array',
            'transport_submission_at' => 'datetime',
            'transport_response_code' => 'integer',
        ];
    }

    /**
     * The linked local message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Message, $this>
     */
    public function localMessage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Message::class, 'local_message_id');
    }

    /**
     * The associated chatroom.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Chatroom, $this>
     */
    public function chatroom(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chatroom::class);
    }
}
