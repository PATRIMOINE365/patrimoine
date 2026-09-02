/*
 * The launch gate.
 *
 * GET /api/v1/config is public and is called before anything is shown. It
 * is what makes an installed build recoverable: raising a floor or closing
 * the service is an .env change on the server plus config:cache — no
 * release, no store review.
 *
 * There is deliberately no server-side 426. The floor is enforced here, by
 * the client, which only works because this code shipped in the first build.
 * That is why this file exists before there are any screens to gate.
 */

/**
 * Compare two dotted versions. Returns true when `version` is below `floor`.
 * Missing segments count as zero, so "1.2" is below "1.2.1".
 */
export function isBelow(version, floor) {
    const left = String(version).split('.').map((part) => parseInt(part, 10) || 0);
    const right = String(floor).split('.').map((part) => parseInt(part, 10) || 0);
    const length = Math.max(left.length, right.length);

    for (let index = 0; index < length; index += 1) {
        const a = left[index] ?? 0;
        const b = right[index] ?? 0;

        if (a !== b) {
            return a < b;
        }
    }

    return false;
}

export const LAUNCH_OK = 'ok';
export const LAUNCH_UPDATE_REQUIRED = 'update_required';
export const LAUNCH_MAINTENANCE = 'maintenance';
export const LAUNCH_UNREACHABLE = 'unreachable';

/**
 * Decide what the first screen is. Pure, so the three refusals are testable
 * without a network or a device.
 */
export function evaluate(config, { appVersion, platform }) {
    if (config === null) {
        return { state: LAUNCH_UNREACHABLE };
    }

    const floor = config.minimum_version?.[platform] ?? null;

    if (floor !== null && isBelow(appVersion, floor)) {
        return {
            state: LAUNCH_UPDATE_REQUIRED,
            storeUrl: config.store_url?.[platform] ?? null,
            required: floor,
        };
    }

    /*
     * Checked after the floor: a service closed for maintenance that also
     * requires an update should send the person to the store, because the
     * update is the thing they can act on.
     */
    if (config.maintenance?.active === true) {
        return {
            state: LAUNCH_MAINTENANCE,
            message: config.maintenance.message ?? null,
        };
    }

    return { state: LAUNCH_OK, config };
}

export async function fetchConfig(client) {
    try {
        return await client.get('/config');
    } catch {
        /*
         * Unreachable is not the same as forbidden. The launch screen says
         * "cannot reach Patrimoine" and offers retry; it does not sign
         * anybody out, because a token that is still valid is still valid.
         */
        return null;
    }
}
