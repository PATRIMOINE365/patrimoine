@props([
    'href',
    'icon',
    'label',
    'active' => null,
    'capability' => null,
])

@php
    /*
     * Which paths count as "this page". Defaults to the link's own path, so
     * an item only needs to say so when it owns a subtree (/admin and
     * /admin/*) or answers to more than one route.
     */
    $patterns = $active !== null
        ? (array) $active
        : [ltrim($href, '/')];

    $isCurrent = request()->is(...$patterns);
@endphp

{{--
    One row of the sidebar.

    Before this, each of the eleven rows carried its own copy of the same
    twelve utility classes, its own ternary for the active state and its own
    inline <svg> — twenty-one of them in this file alone. Everything visual
    now lives in .pm-nav-item, and the mint on an active row is one of the
    five jobs the brand gives mint inside the product.
--}}

<a
    href="{{ $href }}"
    {{ $attributes->class(['pm-nav-item', 'pm-nav-active' => $isCurrent]) }}
    @if ($isCurrent) aria-current="page" @endif
    @if ($capability) data-requires-capability="{{ $capability }}" @endif
>
    <x-icon :name="$icon" />

    <span data-i18n="{{ $label }}">{{ __('ui.' . $label) }}</span>
</a>
