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

namespace App\Http\Requests\Internal;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class IrcBridgeInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'channel' => [
                'required',
                'string',
                Rule::in([(string) config('irc-bridge.general.channel', '#General')]),
            ],
            'irc_nick' => [
                'required',
                'string',
                'max:255',
            ],
            'text_plain' => [
                'required',
                'string',
                'max:5000',
            ],
            'external_message_id' => [
                'required',
                'string',
                'max:255',
            ],
            'request_id' => [
                'required',
                'string',
                'max:255',
            ],
            'received_at' => [
                'nullable',
                'date',
            ],
            'canonical_username' => [
                'nullable',
                'string',
                'max:255',
            ],
            'provenance' => [
                'nullable',
                'array',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors()->messages(),
        ], 422));
    }
}
