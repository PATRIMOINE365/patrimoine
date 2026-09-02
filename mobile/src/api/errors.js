/*
 * Every failure the application can show a person.
 *
 * Patrimoine answers failures with a PM-code (PM-1043, PM-3097 …) and a
 * sentence already written in the requested language. The client never
 * composes its own wording for a server failure: it shows what it was
 * given, and the code beside it so a support call can start from the code
 * rather than from a paraphrase.
 */
export class ApiError extends Error {
    constructor(message, { status = null, code = null, errors = null, cause = null } = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.code = code;
        this.errors = errors;
        this.cause = cause;
    }

    static unreachable(cause) {
        return new ApiError('unreachable', { status: 0, cause });
    }

    static fromResponse(response, payload) {
        return new ApiError(
            payload?.message ?? `HTTP ${response.status}`,
            {
                status: response.status,
                code: payload?.code ?? null,
                /* Laravel's 422 shape: { message, errors: { field: [ … ] } } */
                errors: payload?.errors ?? null,
            }
        );
    }

    /** True when the network never reached the server at all. */
    get isOffline() {
        return this.status === 0;
    }

    get isValidation() {
        return this.status === 422;
    }

    /** The first message for a field, for inline display beside the input. */
    fieldError(field) {
        return this.errors?.[field]?.[0] ?? null;
    }
}
