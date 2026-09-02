/*
 * The API surface this application uses, named once.
 *
 * Paths are relative to the /api/v1 base. A breaking change gets a new
 * version segment; it never lands on v1, so these strings are stable for
 * the life of every build that has ever been installed.
 */

export const endpoints = {
    /* Public - no token. */
    config: '/config',

    auth: {
        login: '/auth/login',
        mfaVerify: '/auth/mfa/verify',
        mfaResend: '/auth/mfa/resend',
        logout: '/auth/logout',
        me: '/auth/me',
        avatar: '/auth/me/avatar',
        devices: '/auth/devices',
        device: (id) => `/auth/devices/${id}`,
    },

    /* The eight derived kinds behind both the bell and the dashboard. */
    notifications: '/notifications',

    dashboard: {
        summary: '/dashboard',
        overdue: '/dashboard/overdue',
        upcoming: '/dashboard/upcoming',
    },

    /* Properties = buildings and their units. */
    buildings: '/buildings',
    building: (id) => `/buildings/${id}`,

    parties: '/parties',
    party: (id) => `/parties/${id}`,

    leases: '/leases',
    lease: (id) => `/leases/${id}`,
    leaseFinancialHistory: (id) => `/leases/${id}/financial-history`,

    /* Finance: tenant accounts, owner accounts, payouts, expenses. */
    ownerAccounts: '/owner-accounts',
    ownerAccount: (id) => `/owner-accounts/${id}`,
    accountingSummary: '/accounting/summary',

    license: '/license',
    managingOrganisation: '/managing-organisation',
    guide: '/guide',
    errorCodes: '/error-codes',
    archive: '/archive',
    activityLog: '/activity-log',
    financialJournal: '/financial-journal',
};

/*
 * Sign-in is two calls, always. A correct password does not sign anybody
 * in: it starts a challenge, and the six-digit code emailed to the account
 * is exchanged for the token. Biometric unlock never replaces this - it
 * unlocks a token already stored, it never mints one.
 */
export async function login(client, { email, password, deviceName }) {
    return client.post(endpoints.auth.login, {
        email,
        password,
        /*
         * The handset knows which handset it is and the server never will.
         * Without this the Devices list reads "Safari on iOS" for every
         * phone somebody owns.
         */
        device_name: deviceName,
    });
}

export async function verifyMfa(client, { challengeToken, code }) {
    return client.post(endpoints.auth.mfaVerify, {
        challenge_token: challengeToken,
        code,
    });
}

export async function resendMfa(client, { challengeToken }) {
    return client.post(endpoints.auth.mfaResend, {
        challenge_token: challengeToken,
    });
}
