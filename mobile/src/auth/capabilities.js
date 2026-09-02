/*
 * Who may do what - the browser application's matrix, copied exactly.
 *
 * Presentation only. The server is the authority on every route, and a
 * control hidden here is a courtesy, not a lock: it spares somebody a
 * button that would only answer with a refusal. The matrix is the one in
 * resources/js/permissions.js, and it must not drift from it.
 */

const MATRIX = {
    view_operations: ['administrator', 'property_manager', 'viewer'],
    manage_operations: ['administrator', 'property_manager'],
    manage_finance: ['administrator', 'property_manager'],
    delete_records: ['administrator', 'property_manager'],
    export_reports: ['administrator', 'property_manager', 'viewer'],
    manage_settings: ['administrator'],
    manage_users: ['administrator'],
    view_activity_log: ['administrator'],
    view_financial_journal: ['administrator'],
};

let currentRole = null;
let platformAdmin = false;

/** Called once the signed-in user is known, and again after a refresh. */
export function setUser(user) {
    currentRole = user?.role ?? null;
    platformAdmin = user?.is_platform_admin === true;
}

export function role() {
    return currentRole;
}

export function isPlatformAdmin() {
    return platformAdmin;
}

export function can(capability) {
    const allowed = MATRIX[capability];

    return Array.isArray(allowed) && allowed.includes(currentRole);
}

export const CAPABILITIES = Object.keys(MATRIX);
