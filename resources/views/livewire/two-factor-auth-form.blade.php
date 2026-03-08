<section class="panelV2">
    <header class="panel__header">
        <h2 class="panel__heading">{{ __('Two Factor Authentication') }}</h2>
    </header>
    <div class="panel__body">
        @if ($this->enabled)
            @if ($showingConfirmation)
                <span class="text-warning">
                    {{ __('Completa l abilitazione dell autenticazione a due fattori.') }}
                </span>
            @else
                <span class="text-success">
                    {{ __('Hai abilitato l autenticazione a due fattori.') }}
                </span>
            @endif
        @else
            <span class="text-danger">
                {{ __('Non hai abilitato l autenticazione a due fattori.') }}
            </span>
        @endif

        <div>
            <span class="text-muted">
                {{ __('Quando l autenticazione a due fattori è abilitata, ti verrà richiesto un token sicuro e casuale durante l autenticazione. Puoi recuperare questo token da un app 2FA sincronizzata come Google Authenticator, Authy, BitWarden, ecc.') }}
            </span>
        </div>

        @if ($this->enabled)
            @if ($showingQrCode)
                <div>
                    <p class="text-info">
                        @if ($showingConfirmation)
                            {{ __('Per completare l abilitazione dell autenticazione a due fattori, scansiona il seguente codice QR utilizzando l applicazione di autenticazione del tuo telefono oppure inserisci la chiave di configurazione e fornisci il codice OTP generato.
') }}
                        @else
                            {{ __('L autenticazione a due fattori è ora abilitata. Scansiona il seguente codice QR utilizzando l applicazione di autenticazione del tuo telefono o inserisci la chiave di configurazione..') }}
                        @endif
                    </p>
                </div>

                <div class="twoStep__qrCode">
                    {!! $this->user->twoFactorQrCodeSvg() !!}
                </div>

                <div>
                    <p>{{ __('Setup Key') }}: {{ decrypt($this->user->two_factor_secret) }}</p>
                </div>

                @if ($showingConfirmation)
                    <div>
                        <label for="code" value="{{ __('Code') }}"></label>

                        <input
                            id="code"
                            name="code"
                            class="form__text"
                            type="text"
                            inputmode="numeric"
                            autofocus
                            autocomplete="one-time-code"
                            wire:model.live="code"
                            wire:keydown.enter="confirmTwoFactorAuthentication"
                        />

                        @error('code')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            @endif

            @if ($showingRecoveryCodes)
                <div class="panel__body">
                    <span class="text-danger">
                        {{ __('Conserva questi codici di recupero in un gestore di password sicuro. Possono essere utilizzati per recuperare l accesso al tuo account in caso di smarrimento del dispositivo di autenticazione a due fattori.') }}
                    </span>
                    {{-- format-ignore-start --}}
                    <pre>
                        @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </pre>
                    {{-- format-ignore-end --}}
                </div>
            @endif
        @endif

        <div>
            @if (! $this->enabled)
                <button
                    class="form__button form__button--filled"
                    wire:click="enableTwoFactorAuthentication"
                    wire:loading.attr="disabled"
                >
                    {{ __('Enable') }}
                </button>
            @else
                @if ($showingRecoveryCodes)
                    <button
                        class="form__button form__button--filled"
                        wire:click="regenerateRecoveryCodes"
                    >
                        {{ __('Rigenera i codici di recupero') }}
                    </button>
                @elseif ($showingConfirmation)
                    <button
                        class="form__button form__button--filled"
                        type="button"
                        wire:click="confirmTwoFactorAuthentication"
                        wire:loading.attr="disabled"
                    >
                        {{ __('Confirm') }}
                    </button>
                @else
                    <button
                        class="form__button form__button--filled"
                        wire:click="showRecoveryCodes"
                    >
                        {{ __('Mostra codici di recupero') }}
                    </button>
                @endif

                @if ($showingConfirmation)
                    <button
                        class="form__button form__button--filled"
                        wire:click="disableTwoFactorAuthentication"
                        wire:loading.attr="disabled"
                    >
                        {{ __('Cancel') }}
                    </button>
                @else
                    <button
                        class="form__button form__button--filled"
                        wire:click="disableTwoFactorAuthentication"
                        wire:loading.attr="disabled"
                    >
                        {{ __('Disable') }}
                    </button>
                @endif
            @endif
        </div>
    </div>
</section>
