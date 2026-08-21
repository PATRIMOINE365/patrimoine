{{--
    Right-side editing drawer — the only overlay primitive in Patrimoine.

    Exactly two sizes exist (docs/DESIGN.md):
      sm — 28rem — single-record forms and read-only detail views
      lg — 42rem — multi-section workflows (lease lifecycle)

    Do not reintroduce other widths or size a drawer via CSS overrides.
--}}
@props([
    'id',
    'backdropId' => null,
    'width' => 'lg',
])

@php
    $widthClass = match ($width) {
        'sm' => 'max-w-md',
        default => 'max-w-2xl',
    };
@endphp

<div
    id="{{ $id }}"
    data-drawer-width="{{ $width }}"
    {{ $attributes->class([
        'pm-drawer fixed inset-0 z-[70] flex',
    ]) }}
    aria-hidden="true"
    hidden
>
    <div
        @if($backdropId)
            id="{{ $backdropId }}"
        @endif
        class="pm-drawer-backdrop absolute inset-0"
    ></div>

    <div
        class="
            pm-drawer-panel
            absolute inset-y-0 right-0
            flex w-full {{ $widthClass }}
            flex-col
        "
        role="dialog"
        aria-modal="true"
    >
        {{ $slot }}
    </div>
</div>
