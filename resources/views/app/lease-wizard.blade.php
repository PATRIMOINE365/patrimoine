@extends('layouts.app')

@section('title', __('ui.wizard.title'))
@section('title-i18n', 'wizard.title')

@section('content')

{{--
    Guided lease creation.

    ================================================================
    V1.0.43: the assistant is the lease drawer, paginated
    ================================================================

    The wizard and the Add lease drawer had drifted into two different
    products. The drawer asked for eighteen things the assistant did not,
    it grouped them differently, and — worst of the three — it called the
    rent field "Monthly Rent" while the assistant called it "Rent" and put
    "Paid every: Quarter" underneath. Somebody entering 1,000 a quarter in
    the assistant got a quarterly invoice of 3,000, because the engine has
    always read that number as a month's rent. The lease was wrong and
    nothing on the screen had lied to them.

    So the assistant is now the drawer, one section per page, in the
    drawer's own order and in the drawer's own words:

        0  Information       what these words mean
        1  Property & Tenant  the unit, its owners, the tenant, the agent
        2  Lease Period       start, duration, end, notice
        3  Rent Terms         rent, frequency, due day, VAT, proration,
                              deposit — and receiving the deposit
        4  Advance Payment    advance, reserve, consumable, receipt
        5  Rent Increment     type, value, next date
        6  Fees & Commission  management fee, agent commission, notes
        7  Review

    Everything the drawer can ask is here. The one thing the drawer cannot
    do — create the property, its units, its owners, the tenant and the
    agent as it goes — stays on page 1, because it is the whole reason the
    assistant exists.

    Nothing is written until the last page, so somebody can walk the whole
    way through, change their mind, and leave the registry exactly as they
    found it.

    The party field blocks (new owner, new tenant, new agent) are rendered
    by lease-wizard.js from a single function, so the three can never
    drift apart.
--}}

<div class="pm-wizard-page pm-page mx-auto max-w-[900px]">

    {{-- ============================================================
         Header
    ============================================================ --}}

    <div class="mb-6">
        <p
            class="
                text-xs font-semibold uppercase tracking-[0.14em]
                text-[var(--pm-text-muted)]
            "
        >
            <span data-i18n="wizard.eyebrow">{{ __('ui.wizard.eyebrow') }}</span>
        </p>

        <h1
            class="
                mt-1 text-3xl font-semibold
                tracking-tight text-[var(--pm-text)]
            "
        >
            <span data-i18n="wizard.heading">{{ __('ui.wizard.heading') }}</span>
        </h1>

        <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
            <span data-i18n="wizard.subtitle">{{ __('ui.wizard.subtitle') }}</span>
        </p>
    </div>

    {{--
        V1.0.43: leaving, and keeping.

        Save as draft used to sit at the far right, in the place the eye
        goes for the action that finishes the job — so the button that
        stopped short looked like the button that completed. It moves up
        here beside Cancel, where the two things that take you off the
        page live, and is drawn as an ordinary button. Back and Next take
        the footer, and Next is the one that looks like the primary
        action, because on the last page it is the one that creates the
        letting.
    --}}
    <div class="mb-6 flex flex-wrap items-center justify-end gap-3">
        <button
            id="wizard-draft"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="wizard.save_draft">{{ __('ui.wizard.save_draft') }}</span>
        </button>

        <a
            href="/leases"
            class="pm-button-danger-outline"
        >
            <span data-i18n="wizard.cancel">{{ __('ui.wizard.cancel') }}</span>
        </a>
    </div>

    {{-- ============================================================
         Progress
    ============================================================ --}}

    <div class="mb-6">
        <div class="mb-2 flex items-baseline justify-between gap-4">
            <p
                id="wizard-step-title"
                class="text-sm font-semibold text-[var(--pm-text)]"
            ></p>

            <p
                id="wizard-step-counter"
                class="text-xs text-[var(--pm-text-muted)]"
            ></p>
        </div>

        <div
            class="
                h-1.5 w-full overflow-hidden rounded-full
                bg-[var(--pm-surface-elevated)]
            "
        >
            <div
                id="wizard-progress-bar"
                class="h-full rounded-full bg-[var(--pm-accent)]"
                style="width: 12.5%"
            ></div>
        </div>
    </div>

    {{-- Submission errors --}}
    <div
        id="wizard-error"
        class="
            mb-6 hidden rounded-lg
            border border-[var(--pm-danger-border)]
            bg-[var(--pm-danger-background)] px-4 py-3
            text-sm text-[var(--pm-danger-text)]
        "
    ></div>

    {{-- ============================================================
         Steps
    ============================================================ --}}

    {{--
        The card is held at the height of the tallest page, measured once
        the wizard is wired, so Back and Next stay where the eye left them
        instead of jumping up and down between pages.
    --}}
    <div id="wizard-steps" class="pm-card p-6">

        {{-- ------------------------------------------------------------
             0. Information
        ------------------------------------------------------------ --}}
        <section data-wizard-step="1">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step1_title">{{ __('ui.wizard.step1_title') }}</span>
            </h2>

            <dl class="pm-wizard-glossary">
                <div>
                    <dt data-i18n="wizard.glossary_organisation_term">{{ __('ui.wizard.glossary_organisation_term') }}</dt>
                    <dd data-i18n="wizard.glossary_organisation_text">{{ __('ui.wizard.glossary_organisation_text') }}</dd>
                </div>

                <div>
                    <dt data-i18n="wizard.glossary_party_term">{{ __('ui.wizard.glossary_party_term') }}</dt>
                    <dd data-i18n="wizard.glossary_party_text">{{ __('ui.wizard.glossary_party_text') }}</dd>
                </div>

                <div>
                    <dt data-i18n="wizard.glossary_owner_term">{{ __('ui.wizard.glossary_owner_term') }}</dt>
                    <dd data-i18n="wizard.glossary_owner_text">{{ __('ui.wizard.glossary_owner_text') }}</dd>
                </div>

                <div>
                    <dt data-i18n="wizard.glossary_tenant_term">{{ __('ui.wizard.glossary_tenant_term') }}</dt>
                    <dd data-i18n="wizard.glossary_tenant_text">{{ __('ui.wizard.glossary_tenant_text') }}</dd>
                </div>

                <div>
                    <dt data-i18n="wizard.glossary_agent_term">{{ __('ui.wizard.glossary_agent_term') }}</dt>
                    <dd data-i18n="wizard.glossary_agent_text">{{ __('ui.wizard.glossary_agent_text') }}</dd>
                </div>

                <div>
                    <dt data-i18n="wizard.glossary_property_term">{{ __('ui.wizard.glossary_property_term') }}</dt>
                    <dd data-i18n="wizard.glossary_property_text">{{ __('ui.wizard.glossary_property_text') }}</dd>
                </div>

                <div>
                    <dt data-i18n="wizard.glossary_unit_term">{{ __('ui.wizard.glossary_unit_term') }}</dt>
                    <dd data-i18n="wizard.glossary_unit_text">{{ __('ui.wizard.glossary_unit_text') }}</dd>
                </div>

                <div>
                    <dt data-i18n="wizard.glossary_lease_term">{{ __('ui.wizard.glossary_lease_term') }}</dt>
                    <dd data-i18n="wizard.glossary_lease_text">{{ __('ui.wizard.glossary_lease_text') }}</dd>
                </div>
            </dl>

            <p class="pm-wizard-note">
                <span data-i18n="wizard.step1_note">{{ __('ui.wizard.step1_note') }}</span>
            </p>
        </section>

        {{-- ------------------------------------------------------------
             1. Property & Tenant

             The drawer's first section, plus the one thing the drawer
             cannot do: create the property, the unit and the people as it
             goes. The owners block appears only when the property is new
             or has no ownership recorded, because that is the only time
             there is anything to ask.
        ------------------------------------------------------------ --}}
        <section data-wizard-step="2" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step2_title">{{ __('ui.wizard.step2_title') }}</span>
            </h2>

            <p class="pm-wizard-note">
                <span data-i18n="wizard.step2_note">{{ __('ui.wizard.step2_note') }}</span>
            </p>

            <div class="pm-wizard-fields">
                <div>
                    <label for="wizard-building-mode" class="pm-field-label">
                        <span data-i18n="wizard.property">{{ __('ui.wizard.property') }}</span>
                    </label>

                    <select id="wizard-building-mode" class="pm-input">
                        <option value="existing" data-i18n="wizard.use_existing_property">{{ __('ui.wizard.use_existing_property') }}</option>
                        <option value="new" data-i18n="wizard.add_new_property">{{ __('ui.wizard.add_new_property') }}</option>
                    </select>
                </div>

<div id="wizard-building-existing">
                    <label for="wizard-building-id-search" class="pm-field-label">
                        <span data-i18n="wizard.choose_property">{{ __('ui.wizard.choose_property') }}</span>
                    </label>

                    {{--
                        V1.0.45: a searchable picker, built by
                        resources/js/lease-wizard.js. The dropdown it
                        replaces could only ever offer the first hundred
                        properties, and said nothing about the rest.
                    --}}
                    <div id="wizard-building-picker"></div>
                </div>

                <div id="wizard-building-new" class="hidden pm-wizard-subfields">
                    <div>
                        <label for="wizard-building-name" class="pm-field-label">
                            <span data-i18n="wizard.property_name">{{ __('ui.wizard.property_name') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input id="wizard-building-name" type="text" maxlength="255" class="pm-input">
                    </div>

                    <div>
                        <label for="wizard-building-address" class="pm-field-label">
                            <span data-i18n="wizard.property_address">{{ __('ui.wizard.property_address') }}</span>
                        </label>

                        <input id="wizard-building-address" type="text" class="pm-input">
                    </div>
                </div>

                <div class="pm-wizard-divider"></div>

                <div>
                    <label for="wizard-unit-mode" class="pm-field-label">
                        <span data-i18n="wizard.unit">{{ __('ui.wizard.unit') }}</span>
                    </label>

                    <select id="wizard-unit-mode" class="pm-input">
                        <option value="existing" data-i18n="wizard.use_existing_unit">{{ __('ui.wizard.use_existing_unit') }}</option>
                        <option value="new" data-i18n="wizard.add_new_unit">{{ __('ui.wizard.add_new_unit') }}</option>
                    </select>
                </div>

<div id="wizard-unit-existing">
                    <label for="wizard-unit-id-search" class="pm-field-label">
                        <span data-i18n="wizard.choose_unit">{{ __('ui.wizard.choose_unit') }}</span>
                    </label>

                    <div id="wizard-unit-picker"></div>

                    <p class="pm-wizard-help">
                        <span data-i18n="wizard.vacant_units_only">{{ __('ui.wizard.vacant_units_only') }}</span>
                    </p>
                </div>

                <div id="wizard-unit-new" class="hidden pm-wizard-subfields">
                    <div>
                        <label for="wizard-unit-name" class="pm-field-label">
                            <span data-i18n="wizard.unit_name">{{ __('ui.wizard.unit_name') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input id="wizard-unit-name" type="text" maxlength="255" class="pm-input">
                    </div>

{{--
                        V1.0.45: a switch rather than a tickbox. A tickbox
                        asks "is this true?"; a switch says "this is on,
                        and here is how to turn it off", which is what
                        these two actually are.
                    --}}
                    <label class="pm-toggle-field">
                        <input id="wizard-unit-commercial" type="checkbox" class="pm-toggle">

                        <span class="min-w-0">
                            <span class="pm-toggle-label">
                                <span data-i18n="wizard.unit_commercial">{{ __('ui.wizard.unit_commercial') }}</span>
                            </span>
                        </span>
                    </label>
                </div>

                {{-- Ownership, when the property does not have it yet --}}
                <div id="wizard-owners-block" class="hidden">
                    <div class="pm-wizard-divider"></div>

                    <h3 class="pm-field-label">
                        <span data-i18n="wizard.ownership">{{ __('ui.wizard.ownership') }}</span>
                    </h3>

                    <p class="pm-wizard-help">
                        <span data-i18n="wizard.ownership_note">{{ __('ui.wizard.ownership_note') }}</span>
                    </p>

                    <div id="wizard-owner-rows" class="pm-wizard-fields"></div>

                    <div class="mt-4 flex items-center justify-between gap-4">
                        <button
                            id="wizard-add-owner"
                            type="button"
                            class="pm-button-secondary"
                        >
                            <span data-i18n="wizard.add_owner">{{ __('ui.wizard.add_owner') }}</span>
                        </button>

                        <p id="wizard-owner-total" class="text-sm text-[var(--pm-text-muted)]"></p>
                    </div>
                </div>

                <div class="pm-wizard-divider"></div>

                <div>
                    <label for="wizard-tenant-mode" class="pm-field-label">
                        <span data-i18n="wizard.tenant">{{ __('ui.wizard.tenant') }}</span>
                    </label>

                    <select id="wizard-tenant-mode" class="pm-input">
                        <option value="existing" data-i18n="wizard.use_existing_party">{{ __('ui.wizard.use_existing_party') }}</option>
                        <option value="new" data-i18n="wizard.add_new_party">{{ __('ui.wizard.add_new_party') }}</option>
                    </select>
                </div>

<div id="wizard-tenant-existing">
                    <label for="wizard-tenant-id-search" class="pm-field-label">
                        <span data-i18n="wizard.choose_tenant">{{ __('ui.wizard.choose_tenant') }}</span>
                    </label>

                    <div id="wizard-tenant-picker"></div>
                </div>

                <div id="wizard-tenant-new" class="hidden"></div>

            </div>
        </section>

        {{-- ------------------------------------------------------------
             2. Lease Period
        ------------------------------------------------------------ --}}
        <section data-wizard-step="3" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step3_title">{{ __('ui.wizard.step3_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
                <div class="pm-wizard-grid">
                    <div>
                        <label for="wizard-start-date" class="pm-field-label">
                            <span data-i18n="wizard.start_date">{{ __('ui.wizard.start_date') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input
                            id="wizard-start-date"
                            type="text"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                            class="pm-input"
                        >

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.start_date_help">{{ __('ui.wizard.start_date_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-duration" class="pm-field-label">
                            <span data-i18n="wizard.duration">{{ __('ui.wizard.duration') }}</span>
                        </label>

                        <select id="wizard-duration" class="pm-input">
                            <option value="12" data-i18n="wizard.duration_12">{{ __('ui.wizard.duration_12') }}</option>
                            <option value="6" data-i18n="wizard.duration_6">{{ __('ui.wizard.duration_6') }}</option>
                            <option value="24" data-i18n="wizard.duration_24">{{ __('ui.wizard.duration_24') }}</option>
                            <option value="custom" data-i18n="wizard.duration_custom">{{ __('ui.wizard.duration_custom') }}</option>
                            <option value="open" data-i18n="wizard.duration_open">{{ __('ui.wizard.duration_open') }}</option>
                        </select>
                    </div>

                    <div id="wizard-end-date-field">
                        <label for="wizard-end-date" class="pm-field-label">
                            <span data-i18n="wizard.end_date">{{ __('ui.wizard.end_date') }}</span>
                        </label>

                        <input
                            id="wizard-end-date"
                            type="text"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                            class="pm-input"
                        >

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.end_date_help">{{ __('ui.wizard.end_date_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-notice-date" class="pm-field-label">
                            <span data-i18n="wizard.notice_date">{{ __('ui.wizard.notice_date') }}</span>
                        </label>

                        <input
                            id="wizard-notice-date"
                            type="text"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                            class="pm-input"
                        >

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.notice_date_help">{{ __('ui.wizard.notice_date_help') }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------
             3. Rent Terms
        ------------------------------------------------------------ --}}
        <section data-wizard-step="4" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step4_title">{{ __('ui.wizard.step4_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
                <div class="pm-wizard-grid">
                    <div>
                        {{--
                            V1.0.43: "Monthly rent", as the drawer has
                            always called it. This field said "Rent" with
                            "Paid every: Quarter" beneath it, so 1,000
                            entered as a quarter's rent was billed at
                            3,000 — the engine reads this number as one
                            month and multiplies by the frequency.
                        --}}
                        <label for="wizard-rent-amount" class="pm-field-label">
                            <span data-i18n="wizard.rent_amount">{{ __('ui.wizard.rent_amount') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-rent-amount"
                                type="text"
                                inputmode="numeric"
                                data-money-input
                                class="pm-input pr-14"
                            >

                            <span id="wizard-rent-amount-unit" class="pm-input-unit"></span>
                        </div>

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.rent_amount_help">{{ __('ui.wizard.rent_amount_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-frequency" class="pm-field-label">
                            <span data-i18n="wizard.frequency">{{ __('ui.wizard.frequency') }}</span>
                        </label>

                        <select id="wizard-frequency" class="pm-input">
                            <option value="monthly" data-i18n="wizard.frequency_monthly">{{ __('ui.wizard.frequency_monthly') }}</option>
                            <option value="quarterly" data-i18n="wizard.frequency_quarterly">{{ __('ui.wizard.frequency_quarterly') }}</option>
                            <option value="bi_yearly" data-i18n="wizard.frequency_bi_yearly">{{ __('ui.wizard.frequency_bi_yearly') }}</option>
                            <option value="yearly" data-i18n="wizard.frequency_yearly">{{ __('ui.wizard.frequency_yearly') }}</option>
                        </select>

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.frequency_help">{{ __('ui.wizard.frequency_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-due-day" class="pm-field-label">
                            <span data-i18n="wizard.due_day">{{ __('ui.wizard.due_day') }}</span>
                        </label>

                        <input id="wizard-due-day" type="number" min="1" max="31" class="pm-input">

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.due_day_help">{{ __('ui.wizard.due_day_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-vat-rate" class="pm-field-label">
                            <span data-i18n="wizard.fee_vat">{{ __('ui.wizard.fee_vat') }}</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-vat-rate"
                                type="text"
                                inputmode="decimal"
                                class="pm-input pr-14"
                                value="0"
                            >

                            <span class="pm-input-unit">%</span>
                        </div>

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.fee_vat_help">{{ __('ui.wizard.fee_vat_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-proration" class="pm-field-label">
                            <span data-i18n="wizard.proration">{{ __('ui.wizard.proration') }}</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-proration"
                                type="text"
                                inputmode="numeric"
                                data-money-input
                                class="pm-input pr-14"
                            >

                            <span id="wizard-proration-unit" class="pm-input-unit"></span>
                        </div>

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.proration_help">{{ __('ui.wizard.proration_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-deposit" class="pm-field-label">
                            <span data-i18n="wizard.security_deposit">{{ __('ui.wizard.security_deposit') }}</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-deposit"
                                type="text"
                                inputmode="numeric"
                                data-money-input
                                class="pm-input pr-14" value="0"
                            >

                            <span id="wizard-deposit-unit" class="pm-input-unit"></span>
                        </div>
                    </div>
                </div>

                {{--
                    V1.0.43: receiving the deposit.

                    Entering a deposit receives it into the lease's own
                    Security Deposit account. These three only say when it
                    changed hands and how — and the date is deliberately
                    free of the lease start, because a deposit is usually
                    what secures the unit, weeks before anybody moves in.
                --}}
                <div id="wizard-deposit-receipt" class="hidden">
                    <div class="pm-wizard-divider"></div>

                    <p class="pm-wizard-help">
                        <span data-i18n="leases.security_deposit_received_help">{{ __('ui.leases.security_deposit_received_help') }}</span>
                    </p>

                    <div class="pm-wizard-subfields pm-wizard-subfields-row">
                        <div>
                            <label for="wizard-deposit-date" class="pm-field-label">
                                <span data-i18n="wizard.advance_date">{{ __('ui.wizard.advance_date') }}</span>
                            </label>

                            <input
                                id="wizard-deposit-date"
                                type="text"
                                data-pm-date-input
                                inputmode="numeric"
                                maxlength="10"
                                placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                                class="pm-input"
                            >
                        </div>

                        <div>
                            <label for="wizard-deposit-method" class="pm-field-label">
                                <span data-i18n="wizard.advance_method">{{ __('ui.wizard.advance_method') }}</span>
                            </label>

                            <select id="wizard-deposit-method" class="pm-input">
                                <option value="bank_transfer" data-i18n="wizard.method_bank_transfer">{{ __('ui.wizard.method_bank_transfer') }}</option>
                                <option value="momo" data-i18n="wizard.method_mobile_money">{{ __('ui.wizard.method_mobile_money') }}</option>
                                <option value="cash" data-i18n="wizard.method_cash">{{ __('ui.wizard.method_cash') }}</option>
                                <option value="cheque" data-i18n="wizard.method_cheque">{{ __('ui.wizard.method_cheque') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="wizard-deposit-reference" class="pm-field-label">
                                <span data-i18n="wizard.advance_reference">{{ __('ui.wizard.advance_reference') }}</span>
                            </label>

                            <input id="wizard-deposit-reference" type="text" maxlength="255" class="pm-input">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------
             4. Advance Payment
        ------------------------------------------------------------ --}}
        <section data-wizard-step="5" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step5_title">{{ __('ui.wizard.step5_title') }}</span>
            </h2>

            <p class="pm-wizard-note">
                <span data-i18n="wizard.step5_note">{{ __('ui.wizard.step5_note') }}</span>
            </p>

            <div class="pm-wizard-fields">
                <div class="pm-wizard-grid">
                    <div>
                        <label for="wizard-advance-amount" class="pm-field-label">
                            <span data-i18n="wizard.advance_amount">{{ __('ui.wizard.advance_amount') }}</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-advance-amount"
                                type="text"
                                inputmode="numeric"
                                data-money-input
                                class="pm-input pr-14" value="0"
                            >

                            <span id="wizard-advance-amount-unit" class="pm-input-unit"></span>
                        </div>
                    </div>

                    <div>
                        <label for="wizard-reserve" class="pm-field-label">
                            <span data-i18n="wizard.rent_reserve">{{ __('ui.wizard.rent_reserve') }}</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-reserve"
                                type="text"
                                inputmode="numeric"
                                data-money-input
                                class="pm-input pr-14" value="0"
                            >

                            <span id="wizard-reserve-unit" class="pm-input-unit"></span>
                        </div>

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.rent_reserve_help">{{ __('ui.wizard.rent_reserve_help') }}</span>
                        </p>
                    </div>

                    {{--
                        What is left of the advance once the reserve is
                        taken out of it. The drawer shows this; the
                        assistant did not, so the one number that says
                        what the tenant can actually spend on rent was
                        the reader's own arithmetic.
                    --}}
                    <div>
                        <div class="pm-field-label">
                            <span data-i18n="wizard.consumable_advance">{{ __('ui.wizard.consumable_advance') }}</span>
                        </div>

                        <p
                            id="wizard-consumable-advance"
                            class="
                                mt-1 text-sm font-semibold
                                text-[var(--pm-text)]
                            "
                        ></p>

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.consumable_advance_help">{{ __('ui.wizard.consumable_advance_help') }}</span>
                        </p>
                    </div>
                </div>

                <div class="pm-wizard-divider"></div>

                <label class="pm-toggle-field">
                    <input id="wizard-advance-received" type="checkbox" class="pm-toggle" checked>

                    <span class="min-w-0">
                        <span class="pm-toggle-label">
                            <span data-i18n="wizard.advance_received">{{ __('ui.wizard.advance_received') }}</span>
                        </span>
                    </span>
                </label>

                {{--
                    V1.0.36: three stacked fields under the tick made
                    this step taller than any other, which pushed Back
                    and Next down the page and moved them every time the
                    box was ticked. Side by side the step is the height
                    of its neighbours and the buttons stay put.
                --}}
                <div id="wizard-advance-details" class="pm-wizard-subfields pm-wizard-subfields-row">
                    <div>
                        <label for="wizard-advance-date" class="pm-field-label">
                            <span data-i18n="wizard.advance_date">{{ __('ui.wizard.advance_date') }}</span>
                        </label>

                        <input
                            id="wizard-advance-date"
                            type="text"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label for="wizard-advance-method" class="pm-field-label">
                            <span data-i18n="wizard.advance_method">{{ __('ui.wizard.advance_method') }}</span>
                        </label>

                        <select id="wizard-advance-method" class="pm-input">
                            <option value="cash" data-i18n="wizard.method_cash">{{ __('ui.wizard.method_cash') }}</option>
                            <option value="bank_transfer" data-i18n="wizard.method_bank_transfer">{{ __('ui.wizard.method_bank_transfer') }}</option>
                            <option value="cheque" data-i18n="wizard.method_cheque">{{ __('ui.wizard.method_cheque') }}</option>
                            <option value="momo" data-i18n="wizard.method_mobile_money">{{ __('ui.wizard.method_mobile_money') }}</option>
                        </select>
                    </div>

                    <div id="wizard-advance-collector-field">
                        <label for="wizard-advance-collector" class="pm-field-label">
                            <span data-i18n="wizard.cashier">{{ __('ui.wizard.cashier') }}</span>
                        </label>

                        <input
                            id="wizard-advance-collector"
                            type="text"
                            readonly
                            data-i18n-placeholder="wizard.cashier_placeholder"
                            placeholder="{{ __('ui.wizard.cashier_placeholder') }}"
                            class="pm-input"
                        >
                    </div>

                    <div>
                        <label for="wizard-advance-reference" class="pm-field-label">
                            <span data-i18n="wizard.advance_reference">{{ __('ui.wizard.advance_reference') }}</span>
                        </label>

                        <input id="wizard-advance-reference" type="text" maxlength="255" class="pm-input">
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------
             5. Rent Increment
        ------------------------------------------------------------ --}}
        <section data-wizard-step="6" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step6_title">{{ __('ui.wizard.step6_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
                <div>
                    <label for="wizard-increment-type" class="pm-field-label">
                        <span data-i18n="wizard.increment_type">{{ __('ui.wizard.increment_type') }}</span>
                    </label>

                    <select id="wizard-increment-type" class="pm-input">
                        <option value="none" data-i18n="wizard.increment_none">{{ __('ui.wizard.increment_none') }}</option>
                        <option value="percentage" data-i18n="wizard.increment_percentage">{{ __('ui.wizard.increment_percentage') }}</option>
                        <option value="fixed" data-i18n="wizard.increment_fixed">{{ __('ui.wizard.increment_fixed') }}</option>
                    </select>
                </div>

                <div id="wizard-increment-details" class="hidden pm-wizard-subfields">
                    <div>
                        <label for="wizard-increment-value" class="pm-field-label">
                            <span data-i18n="wizard.increment_value">{{ __('ui.wizard.increment_value') }}</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-increment-value"
                                type="text"
                                inputmode="decimal"
                                class="pm-input pr-14"
                                value="0"
                            >

                            <span id="wizard-increment-unit" class="pm-input-unit"></span>
                        </div>
                    </div>

                    <div>
                        <label for="wizard-increment-date" class="pm-field-label">
                            <span data-i18n="wizard.increment_date">{{ __('ui.wizard.increment_date') }}</span>
                        </label>

                        <input
                            id="wizard-increment-date"
                            type="text"
                            data-pm-date-input
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="{{ app()->getLocale() === 'fr' ? 'jj-mm-aaaa' : 'dd/mm/yyyy' }}"
                            class="pm-input"
                        >

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.increment_date_help">{{ __('ui.wizard.increment_date_help') }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------
             6. Fees & Commission
        ------------------------------------------------------------ --}}
        <section data-wizard-step="7" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step7_title">{{ __('ui.wizard.step7_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
                {{--
                    V1.0.45: the agent is chosen here rather than on the
                    Property & Tenant page.

                    Their commission has always lived with the fees, which
                    left the person who had just picked an agent two pages
                    earlier reading a money field with no name attached to
                    it. Choosing the agent and saying what they are owed is
                    one decision, so it is now one place.
                --}}
                <div>
                    <label for="wizard-agent-mode" class="pm-field-label">
                        <span data-i18n="wizard.agent">{{ __('ui.wizard.agent') }}</span>
                    </label>

                    <select id="wizard-agent-mode" class="pm-input">
                        <option value="none" data-i18n="wizard.no_agent">{{ __('ui.wizard.no_agent') }}</option>
                        <option value="existing" data-i18n="wizard.use_existing_party">{{ __('ui.wizard.use_existing_party') }}</option>
                        <option value="new" data-i18n="wizard.add_new_party">{{ __('ui.wizard.add_new_party') }}</option>
                    </select>
                </div>

<div id="wizard-agent-existing" class="hidden">
                    <label for="wizard-agent-id-search" class="pm-field-label">
                        <span data-i18n="wizard.choose_agent">{{ __('ui.wizard.choose_agent') }}</span>
                    </label>

                    <div id="wizard-agent-picker"></div>
                </div>

                <div id="wizard-agent-new" class="hidden"></div>

                {{--
                    Directly under the agent, so the amount is read as
                    theirs and nobody has to guess what the commission is
                    for.
                --}}
                <div id="wizard-agent-commission-field" class="hidden">
                    <label for="wizard-agent-commission" class="pm-field-label">
                        <span data-i18n="wizard.agent_commission">{{ __('ui.wizard.agent_commission') }}</span>
                    </label>

                    <div class="pm-input-affix">
                        <input
                            id="wizard-agent-commission"
                            type="text"
                            inputmode="numeric"
                            data-money-input
                            class="pm-input pr-14" value="0"
                        >

                        <span id="wizard-agent-commission-unit" class="pm-input-unit"></span>
                    </div>

                    <p class="pm-wizard-help">
                        <span data-i18n="wizard.agent_commission_help">{{ __('ui.wizard.agent_commission_help') }}</span>
                    </p>
                </div>

                <div class="pm-wizard-divider"></div>

                <div class="pm-wizard-grid">
                    <div>
                        <label for="wizard-fee-type" class="pm-field-label">
                            <span data-i18n="wizard.fee_type">{{ __('ui.wizard.fee_type') }}</span>
                        </label>

                        <select id="wizard-fee-type" class="pm-input">
                            <option value="percentage" data-i18n="wizard.fee_percentage">{{ __('ui.wizard.fee_percentage') }}</option>
                            <option value="fixed" data-i18n="wizard.fee_fixed">{{ __('ui.wizard.fee_fixed') }}</option>
                            <option value="none" data-i18n="wizard.fee_none">{{ __('ui.wizard.fee_none') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="wizard-fee-value" class="pm-field-label">
                            <span data-i18n="wizard.fee_value">{{ __('ui.wizard.fee_value') }}</span>
                        </label>

                        <div class="pm-input-affix">
                            <input
                                id="wizard-fee-value"
                                type="text"
                                inputmode="decimal"
                                class="pm-input pr-14"
                                value="0"
                            >

                            <span id="wizard-fee-unit" class="pm-input-unit"></span>
                        </div>
                    </div>
                </div>

                <div class="pm-wizard-divider"></div>

                <div>
                    <label for="wizard-notes" class="pm-field-label">
                        <span data-i18n="wizard.notes">{{ __('ui.wizard.notes') }}</span>
                    </label>

                    <textarea
                        id="wizard-notes"
                        rows="3"
                        class="pm-input"
                    ></textarea>

                    <p class="pm-wizard-help">
                        <span data-i18n="wizard.notes_help">{{ __('ui.wizard.notes_help') }}</span>
                    </p>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------
             7. Review
        ------------------------------------------------------------ --}}
        <section data-wizard-step="8" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step8_title">{{ __('ui.wizard.step8_title') }}</span>
            </h2>

            <dl id="wizard-summary" class="pm-wizard-summary"></dl>

            <p class="pm-wizard-note">
                <span data-i18n="wizard.step8_note">{{ __('ui.wizard.step8_note') }}</span>
            </p>
        </section>

    </div>

    {{-- ============================================================
         Footer
    ============================================================ --}}

    {{--
        Back and Next, and nothing else. Next is the primary action all
        the way through and becomes Create and activate on the last page,
        so the button the eye goes to is always the one that carries on.
        Save as draft and Cancel are up at the top, where leaving lives.
    --}}
    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
        <button
            id="wizard-back"
            type="button"
            class="pm-button-secondary"
        >
            <span data-i18n="wizard.back">{{ __('ui.wizard.back') }}</span>
        </button>

        <button
            id="wizard-next"
            type="button"
            class="pm-button-primary"
        >
            <span data-i18n="wizard.next">{{ __('ui.wizard.next') }}</span>
        </button>

        <button
            id="wizard-submit"
            type="button"
            class="pm-button-primary hidden"
        >
            <span data-i18n="wizard.create_activate">{{ __('ui.wizard.create_activate') }}</span>
        </button>
    </div>

</div>

@endsection
