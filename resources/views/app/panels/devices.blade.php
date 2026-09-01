{{--
    The Devices tab of Settings.

    Every place this account is currently signed in. It exists because a
    token that outlives the tab it was minted in is a credential sitting
    on a physical object, and a phone is lost, sold or handed on. The
    person it happened to has to be able to see the list and take an
    entry out of it themselves, at the moment they realise, without
    asking an administrator for anything.

    Driven by resources/js/settings.js, which finds it by #devices-list.
--}}
<div id="devices-workspace">
    <div
        class="
            flex flex-col gap-5
            sm:flex-row
            sm:items-start
            sm:justify-between
        "
    >
        <div>
            <h2
                class="
                    text-xl font-semibold
                    tracking-tight text-[var(--pm-text)]
                "
            >
                <span data-i18n="devices.heading">{{ __('ui.devices.heading') }}</span>
            </h2>

            <p
                class="
                    mt-2 max-w-3xl
                    text-sm leading-6 text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="devices.description">{{ __('ui.devices.description') }}</span>
            </p>
        </div>

        <button
            id="devices-revoke-others"
            type="button"
            class="pm-button-secondary gap-2"
        >
            <span data-i18n="devices.sign_out_others">{{ __('ui.devices.sign_out_others') }}</span>
        </button>
    </div>

    <div
        id="settings-devices-error"
        class="
            mt-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)]
            text-[var(--pm-danger-text)]
        "
        role="alert"
    ></div>

    <div
        id="settings-devices-success"
        class="
            mt-6 hidden rounded-xl
            border px-4 py-3 text-sm
            border-[var(--pm-success-border)]
            bg-[var(--pm-success-background)]
            text-[var(--pm-success-text)]
        "
        role="status"
    ></div>

    <section
        class="
            mt-7 rounded-xl
            border border-[var(--pm-border)]
            bg-[var(--pm-surface)]
        "
    >
        <div
            id="devices-list"
            class="divide-y divide-[var(--pm-border)]"
        >
            <div
                class="
                    px-5 py-12 text-center
                    text-sm text-[var(--pm-text-muted)]
                "
            >
                <span data-i18n="devices.loading">{{ __('ui.devices.loading') }}</span>
            </div>
        </div>
    </section>

    <p
        class="
            mt-4 text-sm leading-6
            text-[var(--pm-text-muted)]
        "
    >
        <span data-i18n="devices.expiry_note">{{ __('ui.devices.expiry_note') }}</span>
    </p>
</div>
