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
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                class="h-5 w-5"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    d="M6 6l12 12M18 6L6 18"
                />
            </svg>
        </button>
    @endif
</div>
