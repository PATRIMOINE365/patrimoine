/*
|--------------------------------------------------------------------------
| Patrimoine Browser Authorization
|--------------------------------------------------------------------------
|
| This module controls presentation only.
|
| The Laravel API capability middleware remains the authoritative security
| boundary. Hiding a browser control never grants or removes server access.
|
| V1.0.3 has exactly three fixed roles and a fixed capability matrix.
|
*/

const ROLE_CAPABILITIES = {
    administrator: new Set([
        'view_operations',
        'manage_operations',
        'manage_finance',
        'delete_records',
        'export_reports',
        'manage_settings',
        'manage_users',
        'view_activity_log',
        'view_financial_journal',
    ]),

    property_manager: new Set([
        'view_operations',
        'manage_operations',
        'manage_finance',
        'export_reports',
    ]),

    viewer: new Set([
        'view_operations',
        'export_reports',
    ]),
};

let activeCapabilities =
    new Set();

let observer =
    null;

/**
 * Existing browser controls grouped by the same capabilities used by the
 * server. Dynamic controls are supported by the MutationObserver below.
 *
 * Export/download controls are deliberately NOT included here because
 * Viewer may use applicable reports, statements and documents.
 */
const CAPABILITY_SELECTORS = {
    manage_operations: [
        '#add-property-button',
        '#add-building-button',
        '#add-unit-button',
        '[data-add-existing-unit]',
        '[data-edit-property]',
        '[data-edit-building]',
        '[data-edit-unit]',

        '#add-party-button',
        '[data-edit-party]',

        '#add-lease-button',
        '[data-extend-lease]',
        '[data-terminate-lease]',
        '[data-delete-lease]',
        '#termination-settlement-complete',
        '#termination-settlement-cancel',

        /*
         * Manual resend actions are operational actions, not passive reads.
         */
        '[data-resend-invoice]',
        '[data-resend-receipt]',
        '[data-send-invoice]',
        '[data-send-receipt]',
    ],

    manage_finance: [
        '#record-payment-button',
        '#add-payment-button',

        '#owner-record-deposit-button',
        '#owner-record-expense-button',
        '#owner-record-payout-button',
        '#owner-record-adjustment-button',

        '#tenant-fund-form',
        '#tenant-funds-reserve-form',
        '#tenant-funds-advance-form',

        '#security-deposit-deduction-form',
        '#security-deposit-settlement-form',

        '#tenant-transaction-actions',
        '#tenant-deposit-form',
        '#tenant-withdrawal-form',
        '#tenant-adjustment-form',
    ],

    delete_records: [
        '[data-delete-property]',
        '[data-delete-building]',
        '[data-delete-unit]',
        '[data-delete-party]',
    ],

    manage_settings: [
        '[data-requires-capability="manage_settings"]',
    ],

    manage_users: [
        '[data-requires-capability="manage_users"]',
    ],

    view_activity_log: [
        '[data-requires-capability="view_activity_log"]',
    ],

    view_financial_journal: [
        '[data-requires-capability="view_financial_journal"]',
    ],
};

/**
 * Return whether the current browser role has one capability.
 */
export function browserCan(capability) {
    return activeCapabilities.has(
        capability
    );
}

/**
 * Apply the fixed browser presentation matrix.
 */
export function applyBrowserAuthorization() {
    Object.entries(
        CAPABILITY_SELECTORS
    ).forEach(
        ([capability, selectors]) => {
            const allowed =
                browserCan(
                    capability
                );

            selectors.forEach(
                (selector) => {
                    document
                        .querySelectorAll(
                            selector
                        )
                        .forEach(
                            (element) => {
                                element
                                    .classList
                                    .toggle(
                                        'rbac-hidden',
                                        ! allowed
                                    );

                                if (allowed) {
                                    element
                                        .removeAttribute(
                                            'data-rbac-hidden'
                                        );
                                } else {
                                    element
                                        .setAttribute(
                                            'data-rbac-hidden',
                                            'true'
                                        );
                                }
                            }
                        );
                }
            );
        }
    );
}

/**
 * Redirect direct visits to Administrator-only workspaces.
 *
 * The API already denies their data. This simply prevents Property Manager
 * and Viewer from landing on a page they cannot use.
 */
function redirectRestrictedWorkspace() {
    const requiredCapability = {
        '/settings':
            'manage_settings',

        '/users':
            'manage_users',

        '/activity-log':
            'view_activity_log',

        '/financial-journal':
            'view_financial_journal',
    }[
        window.location.pathname
    ];

    if (
        requiredCapability
        && ! browserCan(
            requiredCapability
        )
    ) {
        window.location.replace(
            '/dashboard'
        );

        return true;
    }

    return false;
}

/**
 * Initialize role-aware presentation after /api/auth/me succeeds.
 */
export function initializeBrowserAuthorization(
    user
) {
    const role =
        String(
            user?.role
            || ''
        );

    activeCapabilities =
        ROLE_CAPABILITIES[
            role
        ]
        || new Set();

    if (
        redirectRestrictedWorkspace()
    ) {
        return false;
    }

    applyBrowserAuthorization();

    /*
     * Parties, Properties, Leases and other workspaces render action buttons
     * after API data arrives. Reapply authorization whenever new DOM nodes
     * are added.
     */
    observer?.disconnect();

    observer =
        new MutationObserver(
            (mutations) => {
                const nodesAdded =
                    mutations.some(
                        (mutation) =>
                            mutation
                                .addedNodes
                                .length
                            > 0
                    );

                if (nodesAdded) {
                    applyBrowserAuthorization();
                }
            }
        );

    observer.observe(
        document.body,
        {
            childList: true,
            subtree: true,
        }
    );

    return true;
}
