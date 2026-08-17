@props([
    'id',
    'backdropId' => null,
    'width' => 'lg',
])

@php
    $widthClass = match ($width) {
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'xl' => 'max-w-3xl',
        '2xl' => 'max-w-4xl',
        default => 'max-w-2xl',
    };
@endphp

<div
    id="{{ $id }}"
    data-drawer-width="{{ $width }}"
    {{ $attributes->class([
        'pm-drawer fixed inset-0 z-[70] hidden',
    ]) }}
    aria-hidden="true"
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
