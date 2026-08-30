@props([
    'name',
    'size' => 20,
])

{{--
    One icon from the Untitled UI set.

        <x-icon name="building-02" />
        <x-icon name="trash-01" class="text-[var(--pm-danger-text)]" />
        <x-icon name="grid-01" size="24" />

    Decorative by default: an icon that sits beside its own label is noise to
    a screen reader, and every icon in this product does. Where an icon is the
    only thing in a control, the control carries the label, not the icon.
--}}

<svg
    {{ $attributes->merge(['class' => 'shrink-0']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! \App\Support\Icons::paths($name) !!}</svg>
