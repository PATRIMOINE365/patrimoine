@props([
    /* The id the page already reads. It stays on the hidden E.164 input. */
    'id',

    /* Translation key for the label, e.g. parties.phone. */
    'label' => null,

    /*
     * A literal label, for the platform console — staff screens are
     * deliberately English only and have no catalogue behind them.
     */
    'labelText' => null,

    /* Extra classes the surrounding form gives its labels. */
    'labelClass' => '',

    /* Whether the label shows the required asterisk. */
    'required' => false,

    /* Layout classes for the wrapper, where the surrounding grid needs them. */
    'wrapper' => '',
])

@php
    $nationalId = $id.'-number';
@endphp

<div class="{{ $wrapper }}">
    <label
        for="{{ $nationalId }}"
        class="pm-field-label {{ $labelClass }}"
    >
        @if($label)
            <span data-i18n="{{ $label }}">{{ __('ui.'.$label) }}</span>
        @else
            {{ $labelText }}
        @endif

        @if($required)
            <span class="text-[var(--pm-danger-text)]">*</span>
        @endif
    </label>

    {{--
        Three inputs, one field. The country and the number are separate
        answers; what the server stores is the two joined in E.164, which
        is the only form a message gateway can dial.
    --}}
    <div class="pm-phone" data-phone-field>
        <button
            type="button"
            data-phone-toggle
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-label="{{ __('ui.phone.country') }}"
            data-i18n-aria-label="phone.country"
            class="pm-phone-country pm-phone-country-empty"
        >
            <span class="pm-flag pm-flag-empty" data-phone-flag></span>

            <span class="pm-phone-code" data-phone-code data-i18n="phone.select">
                {{ __('ui.phone.select') }}
            </span>

            <x-icon name="chevron-down" class="pm-phone-chevron" />
        </button>

        <input
            id="{{ $nationalId }}"
            type="tel"
            inputmode="tel"
            autocomplete="tel-national"
            maxlength="20"
            class="pm-phone-number"
            data-phone-national
            @if($required) required @endif
        >

        <input id="{{ $id }}" type="hidden" data-phone-value>
        <input id="{{ $id }}-country" type="hidden" data-phone-country>

        <div class="pm-phone-menu" data-phone-menu hidden>
            <input
                type="search"
                autocomplete="off"
                role="combobox"
                aria-expanded="true"
                aria-autocomplete="list"
                class="pm-phone-search"
                data-phone-search
                placeholder="{{ __('ui.phone.search') }}"
                data-i18n-placeholder="phone.search"
            >

            <ul class="pm-phone-list" role="listbox" data-phone-list></ul>
        </div>
    </div>
</div>
