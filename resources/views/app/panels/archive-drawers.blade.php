{{--
    Archiving, and putting back.

    V1.0.43. Archiving used to happen on one press. That was defended on
    the grounds that it changes nothing — the record is untouched, every
    invoice and journal line still names it, and one press brings it back —
    but that reasoning only holds for the person who knows the archive page
    exists. To everybody else the record has simply gone: it is in no list
    and in no picker, and nothing on screen says where it went.

    So it asks, the way deletion asks. The drawer says what archiving does
    and, just as importantly, what it does not, and it wants a reason —
    because the question somebody asks a month later is never "was this
    archived" but "why is this not in the list any more".

    Restoring asks too. Putting a record back into every list and every
    picker in the product is the same size of change as taking it out, and
    the reason goes to the activity log, which is where something that has
    already happened belongs.

    Both live in the layout rather than in the four pages that archive
    things, so the wording cannot drift between them.
--}}

<x-drawer
    id="archive-drawer"
    backdrop-id="archive-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="archive-drawer-title"
        close-id="archive-drawer-close"
        close-label="Close"
        close-label-key="actions.close"
    >
        <x-slot:title>
            <span data-i18n="archive.drawer_title">{{ __('ui.archive.drawer_title') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span id="archive-drawer-record" class="font-medium text-[var(--pm-text)]"></span>
        </x-slot:description>
    </x-drawer-header>

    <form id="archive-drawer-form" class="pm-drawer-body">
        <div
            id="archive-drawer-error"
            class="
                mb-5 hidden rounded-xl
                border border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)] px-4 py-3
                text-sm text-[var(--pm-danger-text)]
            "
        ></div>

        <div
            class="
                rounded-xl border border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)] p-4
            "
        >
            <h3 class="text-sm font-semibold text-[var(--pm-text)]">
                <span data-i18n="archive.what_happens">{{ __('ui.archive.what_happens') }}</span>
            </h3>

            <ul
                class="
                    mt-3 list-disc space-y-2 pl-5
                    text-sm leading-6 text-[var(--pm-text-secondary)]
                "
            >
                <li>
                    <span data-i18n="archive.effect_lists">{{ __('ui.archive.effect_lists') }}</span>
                </li>
                <li>
                    <span data-i18n="archive.effect_pickers">{{ __('ui.archive.effect_pickers') }}</span>
                </li>
                <li>
                    <span data-i18n="archive.effect_records">{{ __('ui.archive.effect_records') }}</span>
                </li>
                <li>
                    <span data-i18n="archive.effect_reversible">{{ __('ui.archive.effect_reversible') }}</span>
                </li>
            </ul>
        </div>

        <div class="mt-5">
            <label for="archive-drawer-reason" class="pm-field-label">
                <span data-i18n="archive.reason">{{ __('ui.archive.reason') }}</span>
                <span class="text-[var(--pm-danger-text)]">*</span>
            </label>

            <textarea
                id="archive-drawer-reason"
                rows="3"
                maxlength="500"
                required
                data-i18n-placeholder="archive.reason_placeholder"
                placeholder="{{ __('ui.archive.reason_placeholder') }}"
                class="pm-input"
            ></textarea>

            <p class="mt-1.5 text-xs text-[var(--pm-text-muted)]">
                <span data-i18n="archive.reason_help">{{ __('ui.archive.reason_help') }}</span>
            </p>
        </div>
    </form>

    <x-drawer-footer>
        <button
            id="archive-drawer-cancel"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="actions.cancel">{{ __('ui.actions.cancel') }}</span>
        </button>

        <button
            id="archive-drawer-submit"
            type="submit"
            form="archive-drawer-form"
            class="pm-button-danger"
        >
            <span data-i18n="archive.archive">{{ __('ui.archive.archive') }}</span>
        </button>
    </x-drawer-footer>
</x-drawer>


<x-drawer
    id="archive-restore-drawer"
    backdrop-id="archive-restore-drawer-backdrop"
    width="sm"
>
    <x-drawer-header
        title-id="archive-restore-drawer-title"
        close-id="archive-restore-drawer-close"
        close-label="Close"
        close-label-key="actions.close"
    >
        <x-slot:title>
            <span data-i18n="archive.restore_title">{{ __('ui.archive.restore_title') }}</span>
        </x-slot:title>

        <x-slot:description>
            <span id="archive-restore-drawer-record" class="font-medium text-[var(--pm-text)]"></span>
        </x-slot:description>
    </x-drawer-header>

    <form id="archive-restore-drawer-form" class="pm-drawer-body">
        <div
            id="archive-restore-drawer-error"
            class="
                mb-5 hidden rounded-xl
                border border-[var(--pm-danger-border)]
                bg-[var(--pm-danger-background)] px-4 py-3
                text-sm text-[var(--pm-danger-text)]
            "
        ></div>

        <div
            class="
                rounded-xl border border-[var(--pm-border)]
                bg-[var(--pm-surface-subtle)] p-4
            "
        >
            <h3 class="text-sm font-semibold text-[var(--pm-text)]">
                <span data-i18n="archive.what_happens">{{ __('ui.archive.what_happens') }}</span>
            </h3>

            <ul
                class="
                    mt-3 list-disc space-y-2 pl-5
                    text-sm leading-6 text-[var(--pm-text-secondary)]
                "
            >
                <li>
                    <span data-i18n="archive.restore_effect_lists">{{ __('ui.archive.restore_effect_lists') }}</span>
                </li>
                <li>
                    <span data-i18n="archive.restore_effect_pickers">{{ __('ui.archive.restore_effect_pickers') }}</span>
                </li>
                <li>
                    <span data-i18n="archive.restore_effect_reason">{{ __('ui.archive.restore_effect_reason') }}</span>
                </li>
            </ul>
        </div>

        <div
            id="archive-restore-drawer-original"
            class="mt-5 hidden"
        >
            <div class="pm-field-label">
                <span data-i18n="archive.original_reason">{{ __('ui.archive.original_reason') }}</span>
            </div>

            <p
                id="archive-restore-drawer-original-reason"
                class="
                    mt-1 rounded-xl border border-[var(--pm-border-subtle)]
                    bg-[var(--pm-surface-subtle)] px-4 py-3
                    text-sm leading-6 text-[var(--pm-text-secondary)]
                "
            ></p>
        </div>

        <div class="mt-5">
            <label for="archive-restore-drawer-reason" class="pm-field-label">
                <span data-i18n="archive.restore_reason">{{ __('ui.archive.restore_reason') }}</span>
                <span class="text-[var(--pm-danger-text)]">*</span>
            </label>

            <textarea
                id="archive-restore-drawer-reason"
                rows="3"
                maxlength="500"
                required
                data-i18n-placeholder="archive.restore_reason_placeholder"
                placeholder="{{ __('ui.archive.restore_reason_placeholder') }}"
                class="pm-input"
            ></textarea>

            <p class="mt-1.5 text-xs text-[var(--pm-text-muted)]">
                <span data-i18n="archive.restore_reason_help">{{ __('ui.archive.restore_reason_help') }}</span>
            </p>
        </div>
    </form>

    <x-drawer-footer>
        <button
            id="archive-restore-drawer-cancel"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="actions.cancel">{{ __('ui.actions.cancel') }}</span>
        </button>

        <button
            id="archive-restore-drawer-submit"
            type="submit"
            form="archive-restore-drawer-form"
            class="pm-button-primary"
        >
            <span data-i18n="archive.restore">{{ __('ui.archive.restore') }}</span>
        </button>
    </x-drawer-footer>
</x-drawer>
