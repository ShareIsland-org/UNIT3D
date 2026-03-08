@extends('layout.with-main')

@section('title')
    <title>Donate - {{ config('other.title') }}</title>
@endsection

@section('meta')
    <meta name="description" content="Donate" />
@endsection

@section('breadcrumbs')
    <li class="breadcrumb--active">Donate</li>
@endsection

@section('page', 'page__donation--index')

@section('main')
    <section x-data class="panelV2">
        <h2 class="panel__heading">Supporta {{ config('other.title') }}</h2>
        <div class="panel__body">
            <p>{{ config('donation.description') }}</p>
            <div class="donation-packages">
                @foreach ($packages as $package)
                    <div class="donation-package__wrapper">
                        <div class="donation-package">
                            <div class="donation-package__header">
                                <div class="donation-package__name">{{ $package->name }}</div>
                                <div class="donation-package__price-days">
                                    <span class="donation-package__price">
                                        {{ $package->cost }} {{ config('donation.currency') }}
                                    </span>
                                    <span class="donation-package__separator">-</span>
                                    <span class="donation-package__days">
                                        @if ($package->donor_value === null)
                                            Lifetime
                                        @else
                                            {{ $package->donor_value }} days
                                        @endif
                                    </span>
                                </div>
                                <div class="donation-package__description">
                                    {{ $package->description }}
                                </div>
                            </div>
                            <div class="donation-package__benefits-list">
                                <ol class="benefits-list">
                                    @if ($package->donor_value === null)
                                        <li>Unlimited download slots</li>
                                    @endif

                                    @if ($package->donor_value === null)
                                        <li>Custom user icon</li>
                                    @endif

                                    <li>Global freeleech</li>
                                    <li>Immunity to automated warnings (don't abuse)</li>
                                    <li
                                        style="
                                            background-image: url(/img/sparkels.gif);
                                            width: auto;
                                        "
                                    >
                                        Sparkle effect on username
                                    </li>
                                    <li>
                                        Donor star by username
                                        @if ($package->donor_value === null)
                                            <i
                                                id="lifeline"
                                                class="fal fa-star"
                                                title="Lifetime donor"
                                            ></i>
                                        @else
                                            <i class="fal fa-star text-gold" title="Donor"></i>
                                        @endif
                                    </li>
                                    <li>
                                        Warm fuzzy feeling by supporting
                                        {{ config('other.title') }}
                                    </li>
                                    @if ($package->upload_value !== null)
                                        <li>
                                            {{ App\Helpers\StringHelper::formatBytes($package->upload_value) }}
                                            Upload credit
                                        </li>
                                    @endif

                                    @if ($package->bonus_value !== null)
                                        <li>
                                            {{ number_format($package->bonus_value) }} bonus points
                                        </li>
                                    @endif

                                    @if ($package->invite_value !== null)
                                        <li>{{ $package->invite_value }} invites</li>
                                    @endif
                                </ol>
                            </div>
                            <div class="donation-package__footer">
                                <p class="form__group form__group--horizontal">
                                    <button
                                        class="form__button form__button--filled form__button--centered"
                                        x-on:click.stop="$refs.dialog{{ $package->id }}.showModal()"
                                    >
                                        <i class="fas fa-handshake"></i>
                                        Donate
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @foreach ($packages as $package)
            <dialog class="dialog" x-ref="dialog{{ $package->id }}">
                <h4 class="dialog__heading">Donate {{ $package->cost }} {{ config('donation.currency') }}</h4>
                <form
                    class="dialog__form"
                    method="POST"
                    action="{{ route('donations.store') }}"
                    x-on:click.outside="$refs.dialog{{ $package->id }}.close()"
                >
                    @csrf
                    <span class="text-success text-center">
                        Per effettuare una donazione è necessario completare i seguenti passaggi:
                    </span>
                    <div class="form__group--horizontal">
                        @foreach ($gateways->sortBy('position') as $gateway)
			    <p class="form__group">
                                <input
                                    class="form__text"
                                    type="text"
                                    disabled
                                    value="{{ $gateway->address }}"
				    id="{{ 'gateway-' . $gateway->id }}"
				    style="text-align: center;"
                                />
                                <label
                                    for="{{ 'gateway-' . $gateway->id }}"
                                    class="form__label form__label--floating"
                                >
                                    {{ $gateway->name }}
                                </label>
                            </p>
                        @endforeach

                        <p class="text-info">
                            Invia
                            <strong>
                                {{ $package->cost }} {{ config('donation.currency') }}
                            </strong>
                            al gateway di tua scelta. Prendi nota dell'hash, del numero di ricevuta o altro per farti riconoscere e inseriscilo di seguito.
                        </p>
                    </div>
                    <div class="form__group--horizontal">
                        <!-- <p class="form__group">
                            <input
                                class="form__text"
                                type="text"
                                disabled
                                value="{{ $package->cost }}"
                                id="package-cost"
                            />
                            <label for="package-cost" class="form__label form__label--floating">
                                Cost
                            </label>
                        </p> -->
                        <p class="form__group">
                            <input
                                class="form__text"
                                type="text"
                                value=""
                                id="proof"
                                name="transaction"
                            />
                            <label for="proof" class="form__label form__label--floating">
                                Tx hash, Receipt number, Etc
                            </label>
                        </p>
                    </div>
                    <span class="text-warning">
                        * Elaborare la transazione potrebbe richiedere fino a 48 ore.
                    </span>
                    <p class="form__group">
                        <input type="hidden" name="package_id" value="{{ $package->id }}" />
                        <button class="form__button form__button--filled">Donate</button>
                        <button
                            formmethod="dialog"
                            formnovalidate
                            class="form__button form__button--outlined"
                        >
                            {{ __('common.cancel') }}
                        </button>
                    </p>
                </form>
            </dialog>
        @endforeach
    </section>
@endsection
