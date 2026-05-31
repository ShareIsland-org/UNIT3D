@php
    $stringifyAuditValue = static function (mixed $value): array {
        if ($value === null) {
            return [
                'type' => 'null',
                'full' => 'null',
                'summary' => 'null',
                'isLong' => false,
            ];
        }

        if (is_bool($value)) {
            return [
                'type' => 'boolean',
                'full' => $value ? 'true' : 'false',
                'summary' => $value ? 'true' : 'false',
                'isLong' => false,
            ];
        }

        if (is_array($value)) {
            $full = json_encode(
                $value,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            );

            return [
                'type' => 'json',
                'full' => $full,
                'summary' => count($value).' '.str('item')->plural(count($value)),
                'isLong' => true,
            ];
        }

        $full = (string) $value;
        $summary = preg_replace('/\s+/', ' ', trim($full)) ?: ($full === '' ? 'empty string' : $full);

        return [
            'type' => get_debug_type($value),
            'full' => $full,
            'summary' => (string) str($summary)->limit(120),
            'isLong' => str_contains($full, "\n") || str_contains($full, "\r") || str($full)->length() > 160,
        ];
    };

    $auditValuesAreEqual = static fn (mixed $old, mixed $new): bool => json_encode(
        $old,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
    ) === json_encode(
        $new,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
    );
@endphp

<section class="panelV2">
    <header class="panel__header">
        <h2 class="panel__heading">{{ __('staff.audit-log') }}</h2>
        <div class="panel__actions">
            <div class="panel__action">
                <div class="form__group">
                    <input
                        id="username"
                        class="form__text"
                        type="search"
                        autocomplete="off"
                        wire:model.live="username"
                        placeholder=" "
                    />
                    <label class="form__label form__label--floating" for="username">Username</label>
                </div>
            </div>
            <div class="panel__action">
                <div class="form__group">
                    <select id="model" class="form__select" wire:model.live="modelName">
                        <option selected value="">All</option>
                        @foreach ($modelNames as $modelName)
                            <option value="{{ $modelName }}">{{ $modelName }}</option>
                        @endforeach
                    </select>
                    <label class="form__label form__label--floating" for="model">Model name</label>
                </div>
            </div>
            <div class="panel__action">
                <div class="form__group">
                    <input
                        id="modelId"
                        class="form__text"
                        type="search"
                        autocomplete="off"
                        wire:model.live="modelId"
                        placeholder=" "
                    />
                    <label class="form__label form__label--floating" for="modelId">Model ID</label>
                </div>
            </div>
            <div class="panel__action">
                <div class="form__group">
                    <select id="action" class="form__select" wire:model.live="action">
                        <option selected value="">All</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                    </select>
                    <label class="form__label form__label--floating" for="action">Action</label>
                </div>
            </div>
            <div class="panel__action">
                <div class="form__group">
                    <input
                        id="record"
                        class="form__text"
                        type="search"
                        autocomplete="off"
                        wire:model.live="record"
                        placeholder=" "
                    />
                    <label class="form__label form__label--floating" for="record">Record</label>
                </div>
            </div>
            <div class="panel__action">
                <div class="form__group">
                    <select id="quantity" class="form__select" wire:model.live="perPage" required>
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
                    </select>
                    <label class="form__label form__label--floating" for="quantity">
                        {{ __('common.quantity') }}
                    </label>
                </div>
            </div>
        </div>
    </header>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('common.action') }}</th>
                    <th>Model</th>
                    <th>Model ID</th>
                    <th>By</th>
                    <th>Changes</th>
                    <th>{{ __('user.created-on') }}</th>
                    <th>{{ __('common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($audits as $audit)
                    <tr>
                        <td>{{ $audit->id }}</td>
                        <td>
                            <span
                                class="@if($audit->action === 'create') text-green @elseif($audit->action === 'update') text-yellow @elseif($audit->action === 'delete') text-red @endif"
                            >
                                {{ strtoupper($audit->action) }}
                            </span>
                        </td>
                        <td>{{ class_basename($audit->auditable_type) }}</td>
                        <td>{{ $audit->auditable_id }}</td>
                        <td>
                            <a href="{{ route('users.show', ['user' => $audit->user]) }}">
                                {{ $audit->user->username }}
                            </a>
                        </td>
                        <td style="min-width: 520px; max-width: 900px">
                            @php
                                $changes = collect($audit->values ?? [])
                                    ->map(function ($value, $key) use ($auditValuesAreEqual, $stringifyAuditValue): array {
                                        $old = is_array($value) && array_key_exists('old', $value) ? $value['old'] : null;
                                        $new = is_array($value) && array_key_exists('new', $value) ? $value['new'] : null;

                                        return [
                                            'field' => $key,
                                            'old' => $stringifyAuditValue($old),
                                            'new' => $stringifyAuditValue($new),
                                            'isChanged' => ! $auditValuesAreEqual($old, $new),
                                        ];
                                    });
                                $changedFields = $changes->where('isChanged', true)->values();
                                $unchangedFields = $changes->where('isChanged', false)->values();
                            @endphp

                            <div style="display: grid; gap: 0.5rem">
                                <div>
                                    <strong>{{ trans_choice('staff.changed-fields', $changedFields->count()) }}</strong>

                                    @if ($unchangedFields->isNotEmpty())
                                        <span style="opacity: 0.75">
                                            &middot;
                                            {{ trans_choice('staff.unchanged-fields-hidden', $unchangedFields->count()) }}
                                        </span>
                                    @endif
                                </div>

                                @if ($changedFields->isNotEmpty())
                                    <div style="max-height: 36rem; overflow: auto">
                                        <table class="data-table" style="table-layout: fixed; min-width: 640px">
                                            <thead>
                                                <tr>
                                                    <th style="width: 12rem">{{ __('staff.field') }}</th>
                                                    <th>{{ __('staff.old-value') }}</th>
                                                    <th>{{ __('staff.new-value') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($changedFields as $change)
                                                    <tr>
                                                        <td style="vertical-align: top">
                                                            <code>{{ $change['field'] }}</code>
                                                        </td>
                                                        @foreach (['old' => 'text-red', 'new' => 'text-green'] as $side => $class)
                                                            @php $formatted = $change[$side]; @endphp
                                                            <td
                                                                class="{{ $class }}"
                                                                style="
                                                                    vertical-align: top;
                                                                    word-break: break-word;
                                                                    overflow-wrap: anywhere;
                                                                "
                                                            >
                                                                @if ($formatted['type'] === 'null')
                                                                    <em style="opacity: 0.75">null</em>
                                                                @elseif ($formatted['isLong'])
                                                                    <details>
                                                                        <summary style="cursor: pointer">
                                                                            {{ $formatted['summary'] }}
                                                                        </summary>
                                                                        <pre style="white-space: pre-wrap; max-height: 24rem; overflow: auto; margin: 0.5rem 0 0"><code>{{ $formatted['full'] }}</code></pre>
                                                                    </details>
                                                                @else
                                                                    <code style="white-space: pre-wrap">{{ $formatted['full'] }}</code>
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if ($unchangedFields->isNotEmpty())
                                    <details>
                                        <summary style="cursor: pointer">
                                            {{ __('staff.show-unchanged-fields') }}
                                        </summary>
                                        <p style="margin-bottom: 0; word-break: break-word">
                                            @foreach ($unchangedFields as $unchangedField)
                                                <code>{{ $unchangedField['field'] }}</code>@if (! $loop->last), @endif
                                            @endforeach
                                        </p>
                                    </details>
                                @endif
                            </div>
                        </td>
                        <td>
                            <time
                                datetime="{{ $audit->created_at }}"
                                title="{{ $audit->created_at }}"
                            >
                                {{ $audit->created_at->diffForHumans() }}
                            </time>
                        </td>
                        <td>
                            <menu class="data-table__actions">
                                <li class="data-table__action">
                                    <form
                                        method="POST"
                                        action="{{ route('staff.audits.destroy', ['audit' => $audit]) }}"
                                        x-data="confirmation"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            x-on:click.prevent="confirmAction"
                                            data-b64-deletion-message="{{ base64_encode('Are you sure you want to delete this audit log entry?') }}"
                                            class="form__button form__button--text"
                                        >
                                            {{ __('common.delete') }}
                                        </button>
                                    </form>
                                </li>
                            </menu>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No audits</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $audits->links('partials.pagination') }}
</section>
