@props([
    'titleId' => null,
    'descriptionId' => null,
    'closeId' => null,
    'closeLabel' => 'Close',
    'closeLabelKey' => null,
])

<div class="pm-drawer-header">
    <div class="min-w-0 flex-1">
        @isset($title)
            <h2
                @if($titleId)
                    id="{{ $titleId }}"
                @endif
                class="
                    text-lg font-semibold
                    tracking-tight
                    text-[var(--pm-text)]
                "
            >
                {{ $title }}
            </h2>
        @endisset

        @isset($description)
            <div
                @if($descriptionId)
                    id="{{ $descriptionId }}"
                @endif
                class="
                    mt-1 max-w-2xl
                    text-sm leading-6
                    text-[var(--pm-text-muted)]
                "
            >
                {{ $description }}
            </div>
        @endisset
    </div>

    @if($closeId)
        <button
            id="{{ $closeId }}"
            type="button"
            class="pm-icon-button shrink-0"
            aria-label="{{ $closeLabel }}"
            @if($closeLabelKey)
                data-i18n-aria-label="{{ $closeLabelKey }}"
            @endif
        >
            <x-icon name="x-close" />
        </button>
    @endif
</div>
