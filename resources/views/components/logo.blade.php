@props([
    'size' => 40,
])

{{--
    The Patrimoine 365 mark, from the Brand Package: two pillars carrying
    three ledger bars.

    Inlined rather than linked, and painted from two tokens rather than two
    files. The brand specifies different colours per ground — Green pillars
    and Mint Deep bars on light, white pillars and Mint bars on dark — which
    with an <img> means shipping two SVGs and swapping them in JavaScript or
    hiding one with CSS. As tokens it is one element that simply follows
    whatever it is standing on: the sidebar and the authentication hero set
    the reverse pair because they are always a dark band, and everywhere
    else follows the theme.
--}}

<svg
    {{ $attributes->class(['pm-logo']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 64 64"
    role="img"
    aria-label="Patrimoine 365"
>
    <title>Patrimoine 365</title>

    <rect x="2"  y="4"  width="10" height="56" rx="2" fill="var(--pm-logo-pillar)"/>
    <rect x="52" y="4"  width="10" height="56" rx="2" fill="var(--pm-logo-pillar)"/>

    <rect x="18" y="9"  width="28" height="10" rx="2" fill="var(--pm-logo-bar)"/>
    <rect x="18" y="27" width="28" height="10" rx="2" fill="var(--pm-logo-bar)"/>
    <rect x="18" y="45" width="28" height="10" rx="2" fill="var(--pm-logo-bar)"/>
</svg>
