@extends('layouts.app')

@section('title', 'Settings — Patrimoine')

@section('content')

<div class="mx-auto max-w-5xl">

    {{-- Page heading --}}
    <div class="mb-8">
        <p
            class="
                text-sm font-medium
                text-patrimoine-700
            "
        >
            Administration
        </p>

        <h1
            class="
                mt-1 text-3xl font-semibold
                tracking-tight text-slate-950
            "
        >
            Settings
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            Configure the organisation operating this Patrimoine installation.
        </p>
    </div>

    {{-- Page-level error --}}
    <div
        id="settings-error"
        class="
            mb-6 hidden rounded-xl
            border border-red-200
            bg-red-50 px-4 py-3
            text-sm text-red-700
        "
    ></div>

    {{-- Page-level success --}}
    <div
        id="settings-success"
        class="
            mb-6 hidden rounded-xl
            border border-green-200
            bg-green-50 px-4 py-3
            text-sm text-green-700
        "
    ></div>

    <section
        class="
            overflow-hidden rounded-xl
            border border-slate-200
            bg-white shadow-sm
        "
    >
        <div
            class="
                border-b border-slate-100
                px-6 py-5
            "
        >
            <h2
                class="
                    text-base font-semibold
                    text-slate-950
                "
            >
                Managing Organisation
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                This organisation represents the company or entity managing
                the property portfolio in this Patrimoine installation.
            </p>
        </div>

        <form id="managing-organisation-form">

            <div class="space-y-7 px-6 py-6">

                {{-- Organisation identity --}}
                <section>
                    <h3
                        class="
                            mb-4 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        Organisation Details
                    </h3>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div class="md:col-span-2">
                            <label
                                for="organisation-legal-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Legal Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-legal-name"
                                type="text"
                                required
                                maxlength="255"
                                placeholder="e.g. Apotica Company Limited"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="organisation-address"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Address
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                id="organisation-address"
                                rows="2"
                                required
                                placeholder="Organisation address"
                                class="
                                    w-full resize-y rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            ></textarea>
                        </div>

                        <div>
                            <label
                                for="organisation-phone"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Phone
                            </label>

                            <input
                                id="organisation-phone"
                                type="text"
                                maxlength="50"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-alternate-phone"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Alternate Phone
                            </label>

                            <input
                                id="organisation-alternate-phone"
                                type="text"
                                maxlength="50"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="organisation-email"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                General Email
                            </label>

                            <input
                                id="organisation-email"
                                type="email"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Contact person --}}
                <section
                    class="
                        border-t border-slate-100
                        pt-7
                    "
                >
                    <h3
                        class="
                            mb-4 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        Primary Contact
                    </h3>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div class="md:col-span-2">
                            <label
                                for="organisation-contact-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Contact Person
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-contact-name"
                                type="text"
                                required
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-contact-phone"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Contact Phone
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-contact-phone"
                                type="text"
                                required
                                maxlength="50"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-contact-email"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Contact Email
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-contact-email"
                                type="email"
                                required
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Registration --}}
                <section
                    class="
                        border-t border-slate-100
                        pt-7
                    "
                >
                    <h3
                        class="
                            mb-4 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        Registration
                    </h3>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-registration-number"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Registration Number
                            </label>

                            <input
                                id="organisation-registration-number"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-vat-tin"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                VAT / TIN
                            </label>

                            <input
                                id="organisation-vat-tin"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Language and currency --}}
                <section
                    class="
                        border-t border-slate-100
                        pt-7
                    "
                >
                    <h3
                        class="
                            mb-1 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        Language & Currency
                    </h3>

                    <p class="mb-4 text-xs text-slate-500">
                        These settings apply to the entire Managing Organisation.
                        Language and currency are independent.
                    </p>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-language"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Language
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="organisation-language"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                                @foreach(
                                    config('patrimoine.languages', [])
                                    as $code => $definition
                                )
                                    <option value="{{ $code }}">
                                        {{ $definition['name'] ?? $code }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1.5 text-xs text-slate-500">
                                Controls normal user-facing Patrimoine content.
                            </p>
                        </div>

                        <div>
                            <label
                                for="organisation-currency"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Currency
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="organisation-currency"
                                required
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    bg-white px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                                @foreach(
                                    config('patrimoine.currencies', [])
                                    as $code => $definition
                                )
                                    <option value="{{ $code }}">
                                        {{ $definition['name'] ?? $code }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-1.5 text-xs text-slate-500">
                                Changes presentation only. Stored monetary
                                values are never converted.
                            </p>
                        </div>
                    </div>
                </section>


                {{-- Financial defaults --}}
                <section
                    class="
                        border-t border-slate-100
                        pt-7
                    "
                >
                    <h3
                        class="
                            mb-1 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        Financial Defaults
                    </h3>

                    <p class="mb-4 text-xs text-slate-500">
                        Defaults apply to newly created records only.
                        Existing leases and invoices keep their stored values.
                    </p>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-default-vat-rate"
                                class="
                                    mb-1.5 flex items-center gap-1.5
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Default VAT Rate %

                                <x-field-help label="About Default VAT Rate">
                                    This rate is pre-filled when creating a new Lease.
                                    Individual Leases may still override the value,
                                    including using 0% where applicable.
                                    Changing this setting does not alter existing
                                    Leases or historical Invoices.
                                </x-field-help>

                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="organisation-default-vat-rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                required
                                value="18"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    transition
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >

                            <p
                                class="
                                    mt-1.5 text-xs
                                    text-slate-500
                                "
                            >
                                Used as the starting VAT rate for new Leases.
                            </p>
                        </div>
                    </div>
                </section>





                {{-- Banking --}}
                <section
                    class="
                        border-t border-slate-100
                        pt-7
                    "
                >
                    <h3
                        class="
                            mb-1 text-sm font-semibold
                            text-slate-950
                        "
                    >
                        Banking Details
                    </h3>

                    <p class="mb-4 text-xs text-slate-500">
                        Optional.
                    </p>

                    <div
                        class="
                            grid gap-4
                            md:grid-cols-2
                        "
                    >
                        <div>
                            <label
                                for="organisation-bank-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Bank Name
                            </label>

                            <input
                                id="organisation-bank-name"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-bank-branch"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Bank Branch
                            </label>

                            <input
                                id="organisation-bank-branch"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-bank-account-name"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Account Name
                            </label>

                            <input
                                id="organisation-bank-account-name"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>

                        <div>
                            <label
                                for="organisation-bank-account-number"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-slate-700
                                "
                            >
                                Account Number
                            </label>

                            <input
                                id="organisation-bank-account-number"
                                type="text"
                                maxlength="255"
                                class="
                                    w-full rounded-lg
                                    border border-slate-200
                                    px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-patrimoine-500
                                    focus:ring-2
                                    focus:ring-patrimoine-100
                                "
                            >
                        </div>
                    </div>
                </section>

                {{-- Notes --}}
                <section
                    class="
                        border-t border-slate-100
                        pt-7
                    "
                >
                    <label
                        for="organisation-notes"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        Notes
                    </label>

                    <textarea
                        id="organisation-notes"
                        rows="3"
                        class="
                            w-full resize-y rounded-lg
                            border border-slate-200
                            px-3.5 py-2.5
                            text-sm outline-none
                            focus:border-patrimoine-500
                            focus:ring-2
                            focus:ring-patrimoine-100
                        "
                    ></textarea>
                </section>

            </div>

            <div
                class="
                    flex justify-end
                    border-t border-slate-100
                    bg-slate-50/70
                    px-6 py-4
                "
            >
                <button
                    id="managing-organisation-submit-button"
                    type="submit"
                    class="
                        rounded-lg
                        bg-patrimoine-950
                        px-5 py-2.5
                        text-sm font-medium text-white
                        shadow-sm transition
                        hover:bg-patrimoine-900
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
                >
                    Save Organisation
                </button>
            </div>

        </form>
    </section>

</div>

@endsection
