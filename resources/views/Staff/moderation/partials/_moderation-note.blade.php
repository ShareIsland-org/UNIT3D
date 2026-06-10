@if ($torrent->moderation_note)
    <div x-data="{ open: false }" style="margin-top: 6px;">
        <button
            type="button"
            class="form__button form__button--text"
            style="margin: 0; padding: 2px 6px; font-size: 12px;"
            x-on:click="open = !open"
        >
            <i
                class="{{ config('other.font-awesome') }} fa-chevron-right"
                style="transition: transform 200ms;"
                :style="open ? 'transform: rotate(90deg)' : ''"
            ></i>
            {{ __('common.reason') }}
        </button>
        <div class="bbcode-rendered" x-show="open" x-cloak x-transition style="margin-top: 4px;">
            <blockquote>@bbcode($torrent->moderation_note)</blockquote>
        </div>
    </div>
@endif
