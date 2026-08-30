@extends('layouts.app')

@section('title', __('ui.wizard.title'))
@section('title-i18n', 'wizard.title')

@section('content')

{{--
    V1.0.29 guided lease creation.

    Ten pages, one submission. Nothing is written until the last page, so
    somebody can walk the whole way through, change their mind, and leave
    the registry exactly as they found it.

    The party field blocks (new owner, new tenant, new agent) are rendered
    by lease-wizard.js from a single function, so the three can never
    drift apart.
--}}

<div class="pm-wizard-page pm-page mx-auto max-w-[900px]">

    {{-- ============================================================
         Header
    ============================================================ --}}

    <div
        class="
            mb-6 flex flex-col gap-4
            sm:flex-row sm:items-end sm:justify-between
        "
    >
        <div>
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

        <a
            href="/leases"
            class="pm-button-danger-outline self-start sm:self-auto"
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
                style="width: 10%"
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

        {{-- 1. What these words mean --}}
        <section data-wizard-step="1">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step1_title">{{ __('ui.wizard.step1_title') }}</span>
            </h2>

            <dl class="pm-wizard-glossary">
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

        {{-- 2. Property and unit --}}
        <section data-wizard-step="2" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step2_title">{{ __('ui.wizard.step2_title') }}</span>
            </h2>

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
                    <label for="wizard-building-id" class="pm-field-label">
                        <span data-i18n="wizard.choose_property">{{ __('ui.wizard.choose_property') }}</span>
                    </label>

                    <select id="wizard-building-id" class="pm-input"></select>
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
                    <label for="wizard-unit-id" class="pm-field-label">
                        <span data-i18n="wizard.choose_unit">{{ __('ui.wizard.choose_unit') }}</span>
                    </label>

                    <select id="wizard-unit-id" class="pm-input"></select>

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

                    <label class="pm-wizard-checkbox">
                        <input id="wizard-unit-commercial" type="checkbox">
                        <span data-i18n="wizard.unit_commercial">{{ __('ui.wizard.unit_commercial') }}</span>
                    </label>
                </div>
            </div>
        </section>

        {{-- 3. Owners --}}
        <section data-wizard-step="3" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step3_title">{{ __('ui.wizard.step3_title') }}</span>
            </h2>

            <p class="pm-wizard-note">
                <span data-i18n="wizard.step3_note">{{ __('ui.wizard.step3_note') }}</span>
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
        </section>

        {{-- 4. Tenant --}}
        <section data-wizard-step="4" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step4_title">{{ __('ui.wizard.step4_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
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
                    <label for="wizard-tenant-id" class="pm-field-label">
                        <span data-i18n="wizard.choose_tenant">{{ __('ui.wizard.choose_tenant') }}</span>
                    </label>

                    <select id="wizard-tenant-id" class="pm-input"></select>
                </div>

                <div id="wizard-tenant-new" class="hidden"></div>
            </div>
        </section>

        {{-- 5. Agent --}}
        <section data-wizard-step="5" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step5_title">{{ __('ui.wizard.step5_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
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
                    <label for="wizard-agent-id" class="pm-field-label">
                        <span data-i18n="wizard.choose_agent">{{ __('ui.wizard.choose_agent') }}</span>
                    </label>

                    <select id="wizard-agent-id" class="pm-input"></select>
                </div>

                <div id="wizard-agent-new" class="hidden"></div>

                <div id="wizard-agent-commission-field" class="hidden">
                    <label for="wizard-agent-commission" class="pm-field-label">
                        <span data-i18n="wizard.agent_commission">{{ __('ui.wizard.agent_commission') }}</span>
                    </label>

                    <input id="wizard-agent-commission" type="text" inputmode="numeric" class="pm-input" value="0">

                    <p class="pm-wizard-help">
                        <span data-i18n="wizard.agent_commission_help">{{ __('ui.wizard.agent_commission_help') }}</span>
                    </p>
                </div>
            </div>
        </section>

        {{-- 6. Duration --}}
        <section data-wizard-step="6" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step6_title">{{ __('ui.wizard.step6_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
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
                </div>

                <p class="pm-wizard-help">
                    <span data-i18n="wizard.end_date_help">{{ __('ui.wizard.end_date_help') }}</span>
                </p>
            </div>
        </section>

        {{-- 7. Notice and increments --}}
        <section data-wizard-step="7" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step7_title">{{ __('ui.wizard.step7_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
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

                <div class="pm-wizard-divider"></div>

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

                        <input id="wizard-increment-value" type="text" inputmode="decimal" class="pm-input" value="0">
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
                    </div>
                </div>
            </div>
        </section>

        {{-- 8. Rent terms --}}
        <section data-wizard-step="8" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step8_title">{{ __('ui.wizard.step8_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
                <div class="pm-wizard-grid">
                    <div>
                        <label for="wizard-rent-amount" class="pm-field-label">
                            <span data-i18n="wizard.rent_amount">{{ __('ui.wizard.rent_amount') }}</span>
                            <span class="text-[var(--pm-danger-text)]">*</span>
                        </label>

                        <input id="wizard-rent-amount" type="text" inputmode="numeric" class="pm-input">
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
                        <label for="wizard-proration" class="pm-field-label">
                            <span data-i18n="wizard.proration">{{ __('ui.wizard.proration') }}</span>
                        </label>

                        <input id="wizard-proration" type="text" inputmode="numeric" class="pm-input">

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.proration_help">{{ __('ui.wizard.proration_help') }}</span>
                        </p>
                    </div>

                    <div>
                        <label for="wizard-deposit" class="pm-field-label">
                            <span data-i18n="wizard.security_deposit">{{ __('ui.wizard.security_deposit') }}</span>
                        </label>

                        <input id="wizard-deposit" type="text" inputmode="numeric" class="pm-input" value="0">
                    </div>

                    <div>
                        <label for="wizard-reserve" class="pm-field-label">
                            <span data-i18n="wizard.rent_reserve">{{ __('ui.wizard.rent_reserve') }}</span>
                        </label>

                        <input id="wizard-reserve" type="text" inputmode="numeric" class="pm-input" value="0">
                    </div>
                </div>

                <div class="pm-wizard-divider"></div>

                <div>
                    <label for="wizard-advance-amount" class="pm-field-label">
                        <span data-i18n="wizard.advance_amount">{{ __('ui.wizard.advance_amount') }}</span>
                    </label>

                    <input id="wizard-advance-amount" type="text" inputmode="numeric" class="pm-input" value="0">
                </div>

                <label class="pm-wizard-checkbox">
                    <input id="wizard-advance-received" type="checkbox" checked>
                    <span data-i18n="wizard.advance_received">{{ __('ui.wizard.advance_received') }}</span>
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
                            <option value="mobile_money" data-i18n="wizard.method_mobile_money">{{ __('ui.wizard.method_mobile_money') }}</option>
                        </select>
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

        {{-- 9. Fees and commission --}}
        <section data-wizard-step="9" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step9_title">{{ __('ui.wizard.step9_title') }}</span>
            </h2>

            <div class="pm-wizard-fields">
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

                        <input id="wizard-fee-value" type="text" inputmode="decimal" class="pm-input" value="0">
                    </div>

                    <div>
                        <label for="wizard-vat-rate" class="pm-field-label">
                            <span data-i18n="wizard.fee_vat">{{ __('ui.wizard.fee_vat') }}</span>
                        </label>

                        <input id="wizard-vat-rate" type="text" inputmode="decimal" class="pm-input" value="0">

                        <p class="pm-wizard-help">
                            <span data-i18n="wizard.fee_vat_help">{{ __('ui.wizard.fee_vat_help') }}</span>
                        </p>
                    </div>
                </div>

                <div class="pm-wizard-divider"></div>

                <p class="text-sm text-[var(--pm-text-secondary)]">
                    <span data-i18n="wizard.commission_echo">{{ __('ui.wizard.commission_echo') }}</span>
                    <strong id="wizard-commission-echo"></strong>
                </p>
            </div>
        </section>

        {{-- 10. Review --}}
        <section data-wizard-step="10" class="hidden">
            <h2 class="pm-wizard-step-heading">
                <span data-i18n="wizard.step10_title">{{ __('ui.wizard.step10_title') }}</span>
            </h2>

            <dl id="wizard-summary" class="pm-wizard-summary"></dl>

            <p class="pm-wizard-note">
                <span data-i18n="wizard.step10_note">{{ __('ui.wizard.step10_note') }}</span>
            </p>
        </section>

    </div>

    {{-- ============================================================
         Footer
    ============================================================ --}}

    {{--
        Back and Next travel together, and the button that actually writes
        something sits last. On the review page that slot stops offering a
        draft and offers the letting itself; anybody who wants a draft
        instead steps back one page and takes it there.
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
            class="pm-button-secondary"
        >
            <span data-i18n="wizard.next">{{ __('ui.wizard.next') }}</span>
        </button>

        <button
            id="wizard-draft"
            type="button"
            class="pm-button-primary"
        >
            <span data-i18n="wizard.save_draft">{{ __('ui.wizard.save_draft') }}</span>
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
