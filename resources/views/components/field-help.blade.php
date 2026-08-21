@props([
    'text' => null,
    'label' => 'More information',
    'textKey' => null,
    'labelKey' => null,
])

{{--
|--------------------------------------------------------------------------
| Field Help
|--------------------------------------------------------------------------
|
| Reusable contextual-help control for Patrimoine form fields.
|
| Help content can be supplied either through the "text" attribute:
|
| <x-field-help
|     text="Explanation shown to the user."
| />
|
| Or through the component slot:
|
| <x-field-help>
|     Explanation shown to the user.
| </x-field-help>
|
| Supporting both forms allows existing Patrimoine forms to migrate to
| the shared tooltip implementation without breaking older markup.
|
| The explanatory text is stored on the trigger and displayed by the
| shared JavaScript tooltip. The tooltip itself is rendered outside the
| modal's scroll container so it cannot be clipped by modal overflow.
|
--}}

@php
    /*
     * Prefer the explicit text attribute when supplied. Otherwise use
     * the component slot so existing <x-field-help>...</x-field-help>
     * instances continue to work.
     */
    $helpText = $text ?? trim((string) $slot);
@endphp

<button
    type="button"
    class="
        ml-1 inline-flex h-5 w-5
        shrink-0 items-center justify-center
        rounded-full
        border border-patrimoine-300
        bg-[var(--pm-surface)]
        text-[11px] font-semibold
        leading-none
        text-[var(--pm-accent)]
        transition
        hover:border-patrimoine-500
        hover:bg-[var(--pm-hover)]
        hover:text-[var(--pm-text)]
        focus:outline-none
        focus:ring-2
        focus:ring-patrimoine-200
    "
    data-field-help
    data-field-help-text="{{ $helpText }}"
    @if($textKey)
        data-i18n-field-help="{{ $textKey }}"
    @endif
    aria-label="{{ $label }}"
    @if($labelKey)
        data-i18n-aria-label="{{ $labelKey }}"
    @endif
    aria-haspopup="true"
>
    ?
</button>
