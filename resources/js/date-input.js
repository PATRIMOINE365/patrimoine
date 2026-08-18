/*
|--------------------------------------------------------------------------
| Patrimoine Date Input Helpers
|--------------------------------------------------------------------------
|
| User-facing business dates use Patrimoine's DD-MM-YYYY convention.
|
| API payloads continue to use ISO YYYY-MM-DD.
|
| This module intentionally handles presentation/input conversion only.
| Business date calculations remain in the existing application services.
|
*/

/**
 * Convert ISO YYYY-MM-DD into DD-MM-YYYY for display.
 *
 * Existing non-ISO values are returned unchanged.
 *
 * @param {string|null|undefined} value
 * @returns {string}
 */
export function dateForDisplay(
    value
) {
    if (! value) {
        return '';
    }

    const text =
        String(value);

    const match =
        text.match(
            /^(\d{4})-(\d{2})-(\d{2})$/
        );

    if (! match) {
        return text;
    }

    return `${match[3]}-${match[2]}-${match[1]}`;
}


/**
 * Convert DD-MM-YYYY into ISO YYYY-MM-DD for API submission.
 *
 * Existing ISO values are accepted unchanged.
 *
 * Unknown/incomplete values are returned unchanged so the existing
 * validation/API layers remain responsible for rejecting invalid dates.
 *
 * @param {string|null|undefined} value
 * @returns {string}
 */
export function dateForApi(
    value
) {
    const text =
        String(
            value
            ?? ''
        ).trim();

    if (
        /^\d{4}-\d{2}-\d{2}$/.test(
            text
        )
    ) {
        return text;
    }

    const match =
        text.match(
            /^(\d{2})-(\d{2})-(\d{4})$/
        );

    if (! match) {
        return text;
    }

    return `${match[3]}-${match[2]}-${match[1]}`;
}


/**
 * Apply Patrimoine's DD-MM-YYYY typing behaviour to matching inputs.
 *
 * Digits entered as DDMMYYYY are progressively rendered:
 *
 *     18
 *     18-08
 *     18-08-2026
 *
 * The function is safe to call more than once. Each initialized input is
 * marked so duplicate event listeners are not attached.
 *
 * @param {string} selector
 */
export function initializeDateInputs(
    selector = '[data-pm-date-input]'
) {
    document
        .querySelectorAll(
            selector
        )
        .forEach(
            (input) => {
                if (
                    input.dataset
                        .pmDateInitialized
                    === 'true'
                ) {
                    return;
                }

                input.dataset
                    .pmDateInitialized =
                    'true';

                input.addEventListener(
                    'input',
                    () => {
                        const digits =
                            input.value
                                .replace(
                                    /\D/g,
                                    ''
                                )
                                .slice(
                                    0,
                                    8
                                );

                        let formatted =
                            digits.slice(
                                0,
                                2
                            );

                        if (
                            digits.length
                            > 2
                        ) {
                            formatted +=
                                '-'
                                + digits.slice(
                                    2,
                                    4
                                );
                        }

                        if (
                            digits.length
                            > 4
                        ) {
                            formatted +=
                                '-'
                                + digits.slice(
                                    4,
                                    8
                                );
                        }

                        input.value =
                            formatted;
                    }
                );
            }
        );
}
