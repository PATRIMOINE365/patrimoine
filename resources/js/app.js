const TOKEN_KEY = 'patrimoine_api_token';

/*
|--------------------------------------------------------------------------
| Authentication Token Storage
|--------------------------------------------------------------------------
|
| Patrimoine currently uses Sanctum personal access tokens for the
| first-party web application as well as the API.
|
| sessionStorage intentionally keeps the token scoped to the current
| browser tab/session instead of persisting it indefinitely.
|
*/

function token() {
    return sessionStorage.getItem(TOKEN_KEY);
}

function saveToken(value) {
    if (
        typeof value !== 'string'
        || value.trim() === ''
    ) {
        throw new Error(
            'Cannot store an empty authentication token.'
        );
    }

    sessionStorage.setItem(
        TOKEN_KEY,
        value
    );
}

function clearToken() {
    sessionStorage.removeItem(TOKEN_KEY);
}

/*
|--------------------------------------------------------------------------
| API Helpers
|--------------------------------------------------------------------------
*/

async function apiRequest(path, options = {}) {
    const headers = new Headers(
        options.headers || {}
    );

    headers.set(
        'Accept',
        'application/json'
    );

    if (
        options.body
        && ! headers.has('Content-Type')
        && ! (options.body instanceof FormData)
    ) {
        headers.set(
            'Content-Type',
            'application/json'
        );
    }

    const authToken = token();

    if (authToken) {
        headers.set(
            'Authorization',
            `Bearer ${authToken}`
        );
    }

    const response = await fetch(
        path,
        {
            ...options,
            headers,
        }
    );

    /*
     * A 401 means the token is missing, expired, revoked or otherwise
     * invalid. Remove it immediately so the browser cannot keep trying
     * to use invalid credentials.
     */
    if (response.status === 401) {
        clearToken();

        if (
            window.location.pathname !== '/login'
        ) {
            window.location.replace('/login');
        }

        throw new Error(
            'Your session has expired. Please sign in again.'
        );
    }

    return response;
}

async function parseJsonResponse(response) {
    const data = await response
        .json()
        .catch(() => ({}));

    if (! response.ok) {
        /*
         * Laravel validation responses normally contain:
         *
         * {
         *     "message": "...",
         *     "errors": {
         *         "email": ["..."]
         *     }
         * }
         *
         * Prefer specific validation errors over the generic top-level
         * message whenever they are available.
         */
        const validationMessage = Object
            .values(data.errors || {})
            .flat()
            .filter(Boolean)
            .join(' ');

        const message =
            validationMessage
            || data.message
            || 'The request could not be completed.';

        throw new Error(message);
    }

    return data;
}

function formatCurrency(value) {
    const numericValue = Number(value);

    const amount = Number.isFinite(numericValue)
        ? numericValue
        : 0;

    return new Intl.NumberFormat(
        'en-GH',
        {
            style: 'currency',
            currency: 'GHS',
            maximumFractionDigits: 0,
        }
    ).format(amount);
}

function initials(name) {
    const normalizedName = String(
        name || 'Property Manager'
    ).trim();

    if (normalizedName === '') {
        return 'PM';
    }

    return normalizedName
        .split(/\s+/)
        .slice(0, 2)
        .map(
            (part) =>
                part.charAt(0).toUpperCase()
        )
        .join('');
}

function escapeHtml(value) {
    const element =
        document.createElement('div');

    element.textContent =
        String(value ?? '');

    return element.innerHTML;
}

function formValue(id) {
    return String(
        document
            .getElementById(id)
            ?.value
        || ''
    ).trim();
}

function nullableFormValue(id) {
    const value =
        formValue(id);

    return value === ''
        ? null
        : value;
}

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

async function initializeLogin() {
    const form =
        document.getElementById('login-form');

    if (! form) {
        return false;
    }

    /*
     * If the browser already has a token, verify it before showing the
     * login form again.
     */
    if (token()) {
        try {
            const response =
                await apiRequest('/api/auth/me');

            if (response.ok) {
                window.location.replace(
                    '/dashboard'
                );

                return true;
            }
        } catch {
            clearToken();
        }
    }

    form.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const button =
                document.getElementById(
                    'login-button'
                );

            const errorBox =
                document.getElementById(
                    'login-error'
                );

            const emailInput =
                document.getElementById(
                    'email'
                );

            const passwordInput =
                document.getElementById(
                    'password'
                );

            if (
                ! button
                || ! errorBox
                || ! emailInput
                || ! passwordInput
            ) {
                return;
            }

            errorBox.classList.add('hidden');
            errorBox.textContent = '';

            button.disabled = true;
            button.textContent = 'Signing in…';

            try {
                const response = await fetch(
                    '/api/auth/login',
                    {
                        method: 'POST',

                        headers: {
                            Accept:
                                'application/json',

                            'Content-Type':
                                'application/json',
                        },

                        body: JSON.stringify({
                            email:
                                emailInput
                                    .value
                                    .trim(),

                            password:
                                passwordInput.value,

                            device_name:
                                'patrimoine-web',
                        }),
                    }
                );

                const data =
                    await parseJsonResponse(
                        response
                    );

                /*
                 * AuthController returns:
                 *
                 * {
                 *     "token_type": "Bearer",
                 *     "access_token": "...",
                 *     "user": {...}
                 * }
                 */
                if (
                    typeof data.access_token
                        !== 'string'
                    || data.access_token.trim()
                        === ''
                ) {
                    throw new Error(
                        'Authentication succeeded but no API token was returned.'
                    );
                }

                saveToken(
                    data.access_token
                );

                window.location.replace(
                    '/dashboard'
                );
            } catch (error) {
                errorBox.textContent =
                    error instanceof Error
                        ? error.message
                        : 'Unable to sign in.';

                errorBox.classList.remove(
                    'hidden'
                );
            } finally {
                button.disabled = false;
                button.textContent = 'Sign in';
            }
        }
    );

    return true;
}

/*
|--------------------------------------------------------------------------
| Authenticated Application Shell
|--------------------------------------------------------------------------
*/

async function initializeAuthenticatedShell() {
    if (
        ! document.body.dataset.authRequired
    ) {
        return false;
    }

    if (! token()) {
        window.location.replace('/login');

        return false;
    }

    try {
        const response =
            await apiRequest('/api/auth/me');

        const user =
            await parseJsonResponse(response);

        const nameElement =
            document.getElementById(
                'sidebar-user-name'
            );

        const roleElement =
            document.getElementById(
                'sidebar-user-role'
            );

        const avatarElement =
            document.getElementById(
                'sidebar-avatar'
            );

        if (nameElement) {
            nameElement.textContent =
                user.name
                || 'Property Manager';
        }

        if (roleElement) {
            roleElement.textContent =
                String(
                    user.role
                    || 'property_manager'
                )
                    .replaceAll('_', ' ')
                    .replace(
                        /\b\w/g,
                        (character) =>
                            character.toUpperCase()
                    );
        }

        if (avatarElement) {
            avatarElement.textContent =
                initials(user.name);
        }
    } catch {
        return false;
    }

    initializeSidebar();
    initializeLogout();

    await loadManagingOrganisation();

    return true;
}

function initializeSidebar() {
    const sidebar =
        document.getElementById('sidebar');

    const overlay =
        document.getElementById(
            'sidebar-overlay'
        );

    const openButton =
        document.getElementById(
            'sidebar-open'
        );

    const closeButton =
        document.getElementById(
            'sidebar-close'
        );

    if (
        ! sidebar
        || ! overlay
        || ! openButton
    ) {
        return;
    }

    const close = () => {
        sidebar.classList.add(
            '-translate-x-full'
        );

        overlay.classList.add(
            'hidden'
        );
    };

    const open = () => {
        sidebar.classList.remove(
            '-translate-x-full'
        );

        overlay.classList.remove(
            'hidden'
        );
    };

    openButton.addEventListener(
        'click',
        open
    );

    overlay.addEventListener(
        'click',
        close
    );

    if (closeButton) {
        closeButton.addEventListener(
            'click',
            close
        );
    }

    document.addEventListener(
        'keydown',
        (event) => {
            if (event.key === 'Escape') {
                close();
            }
        }
    );
}

function initializeLogout() {
    const button =
        document.getElementById(
            'logout-button'
        );

    if (! button) {
        return;
    }

    button.addEventListener(
        'click',
        async () => {
            if (button.disabled) {
                return;
            }

            button.disabled = true;

            try {
                await apiRequest(
                    '/api/auth/logout',
                    {
                        method: 'POST',
                    }
                );
            } catch {
                /*
                 * Local logout must still complete if the server token
                 * has already expired or has already been revoked.
                 */
            } finally {
                clearToken();

                window.location.replace(
                    '/login'
                );
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Managing Organisation Identity
|--------------------------------------------------------------------------
*/

async function loadManagingOrganisation() {
    const element =
        document.getElementById(
            'organisation-name'
        );

    if (! element) {
        return;
    }

    try {
        const response =
            await apiRequest(
                '/api/managing-organisation'
            );

        if (response.status === 404) {
            element.textContent =
                'Patrimoine';

            return;
        }

        const organisation =
            await parseJsonResponse(response);

        element.textContent =
            organisation.legal_name
            || organisation.name
            || 'Patrimoine';
    } catch {
        element.textContent =
            'Patrimoine';
    }
}

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

async function initializeDashboard() {
    const buildings =
        document.getElementById(
            'metric-buildings'
        );

    if (! buildings) {
        return;
    }

    const dateElement =
        document.getElementById(
            'dashboard-date'
        );

    const errorBox =
        document.getElementById(
            'dashboard-error'
        );

    if (dateElement) {
        dateElement.textContent =
            new Intl.DateTimeFormat(
                'en-GH',
                {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                }
            ).format(new Date());
    }

    if (errorBox) {
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
    }

    try {
        const [
            summaryResponse,
            overdueResponse,
            upcomingResponse,
        ] = await Promise.all([
            apiRequest('/api/dashboard'),

            apiRequest(
                '/api/dashboard/overdue'
            ),

            apiRequest(
                '/api/dashboard/upcoming'
            ),
        ]);

        const summary =
            await parseJsonResponse(
                summaryResponse
            );

        const overdue =
            await parseJsonResponse(
                overdueResponse
            );

        const upcoming =
            await parseJsonResponse(
                upcomingResponse
            );

        renderDashboardSummary(
            summary
        );

        renderInvoiceList(
            'overdue-list',
            overdue
        );

        renderInvoiceList(
            'upcoming-list',
            upcoming
        );
    } catch (error) {
        if (errorBox) {
            errorBox.textContent =
                error instanceof Error
                    ? error.message
                    : 'Unable to load dashboard information.';

            errorBox.classList.remove(
                'hidden'
            );
        }
    }
}

function setText(id, value) {
    const element =
        document.getElementById(id);

    if (element) {
        element.textContent =
            String(value ?? '');
    }
}

function firstDefined(
    object,
    keys,
    fallback = 0
) {
    for (const key of keys) {
        if (
            object
            && object[key] !== undefined
            && object[key] !== null
        ) {
            return object[key];
        }
    }

    return fallback;
}

function renderDashboardSummary(summary) {
    const metrics =
        summary?.metrics
        && typeof summary.metrics === 'object'
            ? summary.metrics
            : summary;

    setText(
        'metric-buildings',
        firstDefined(
            metrics,
            [
                'total_buildings',
                'buildings',
            ]
        )
    );

    setText(
        'metric-units',
        firstDefined(
            metrics,
            [
                'total_units',
                'units',
            ]
        )
    );

    setText(
        'metric-occupied',
        firstDefined(
            metrics,
            [
                'occupied_units',
                'occupied',
            ]
        )
    );

    setText(
        'metric-vacant',
        firstDefined(
            metrics,
            [
                'vacant_units',
                'vacant',
            ]
        )
    );

    setText(
        'metric-rent-due',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'rent_due',
                    'total_rent_due',
                ]
            )
        )
    );

    setText(
        'metric-rent-overdue',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'rent_overdue',
                    'total_rent_overdue',
                ]
            )
        )
    );

    setText(
        'metric-collected',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'rent_collected_this_month',
                    'collected_this_month',
                    'cash_collected_this_month',
                ]
            )
        )
    );

    setText(
        'metric-owner-funds',
        formatCurrency(
            firstDefined(
                metrics,
                [
                    'owner_funds_held',
                    'owner_funds',
                ]
            )
        )
    );
}

function normalizeItems(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    if (Array.isArray(payload?.invoices)) {
        return payload.invoices;
    }

    if (Array.isArray(payload?.items)) {
        return payload.items;
    }

    if (Array.isArray(payload?.obligations)) {
        return payload.obligations;
    }

    return [];
}

function renderInvoiceList(
    containerId,
    payload
) {
    const container =
        document.getElementById(
            containerId
        );

    if (! container) {
        return;
    }

    const items =
        normalizeItems(payload);

    if (items.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-lg border border-dashed
                    border-slate-200
                    px-4 py-8 text-center
                    text-sm text-slate-400
                "
            >
                No records to display.
            </div>
        `;

        return;
    }

    container.innerHTML = items
        .slice(0, 6)
        .map((item) => {
            const tenant =
                item.tenant?.name
                || item.tenant?.legal_name
                || item.tenant_name
                || 'Tenant';

            const property =
                item.building?.name
                || (
                    typeof item.building === 'string'
                        ? item.building
                        : ''
                )
                || item.unit?.building?.name
                || '';

            const unit =
                item.unit?.name
                || (
                    typeof item.unit === 'string'
                        ? item.unit
                        : ''
                )
                || '';

            const amount =
                item.outstanding_amount
                ?? item.outstanding
                ?? item.balance
                ?? item.amount
                ?? 0;

            const date =
                item.due_date
                || item.date
                || '';

            const propertyLabel =
                [property, unit]
                    .filter(Boolean)
                    .join(' / ');

            return `
                <div
                    class="
                        flex items-center gap-4
                        border-b border-slate-100
                        py-4 last:border-b-0
                        first:pt-0 last:pb-0
                    "
                >
                    <div class="min-w-0 flex-1">
                        <div
                            class="
                                truncate text-sm font-medium
                                text-slate-900
                            "
                        >
                            ${escapeHtml(tenant)}
                        </div>

                        ${
                            propertyLabel
                                ? `
                                    <div
                                        class="
                                            mt-1 truncate text-xs
                                            text-slate-500
                                        "
                                    >
                                        ${escapeHtml(
                                            propertyLabel
                                        )}
                                    </div>
                                `
                                : ''
                        }

                        ${
                            date
                                ? `
                                    <div
                                        class="
                                            mt-1 text-xs
                                            text-slate-400
                                        "
                                    >
                                        Due ${escapeHtml(date)}
                                    </div>
                                `
                                : ''
                        }
                    </div>

                    <div
                        class="
                            shrink-0 text-sm font-semibold
                            text-slate-900
                        "
                    >
                        ${escapeHtml(
                            formatCurrency(amount)
                        )}
                    </div>
                </div>
            `;
        })
        .join('');
}

/*
|--------------------------------------------------------------------------
| Properties
|--------------------------------------------------------------------------
*/

let propertySearchTimer = null;

/*
 * Building data for the currently rendered page.
 *
 * This lets Unit editing use the already loaded API representation instead
 * of embedding complete JSON records inside HTML attributes.
 */
let loadedPropertiesById =
    new Map();

async function initializeProperties() {
    const container =
        document.getElementById(
            'properties-list'
        );

    if (! container) {
        return;
    }

    const searchInput =
        document.getElementById(
            'property-search'
        );

    if (searchInput) {
        searchInput.addEventListener(
            'input',
            () => {
                clearTimeout(
                    propertySearchTimer
                );

                propertySearchTimer =
                    setTimeout(
                        () => {
                            loadProperties(
                                searchInput
                                    .value
                                    .trim()
                            );
                        },
                        300
                    );
            }
        );
    }

    initializePropertyCreation();

    await loadProperties();
}

async function loadProperties(
    search = '',
    page = 1
) {
    const container =
        document.getElementById(
            'properties-list'
        );

    const errorBox =
        document.getElementById(
            'properties-error'
        );

    if (! container) {
        return;
    }

    if (errorBox) {
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
    }

    container.innerHTML = `
        <div
            class="
                py-10 text-center
                text-sm text-slate-400
            "
        >
            Loading properties…
        </div>
    `;

    try {
        const parameters =
            new URLSearchParams();

        parameters.set(
            'per_page',
            '25'
        );

        parameters.set(
            'page',
            String(page)
        );

        if (search !== '') {
            parameters.set(
                'search',
                search
            );
        }

        const response =
            await apiRequest(
                `/api/buildings?${parameters.toString()}`
            );

        const payload =
            await parseJsonResponse(
                response
            );

        renderProperties(
            payload
        );

        renderPropertiesPagination(
            payload,
            search
        );
    } catch (error) {
        container.innerHTML = '';

        if (errorBox) {
            errorBox.textContent =
                error instanceof Error
                    ? error.message
                    : 'Unable to load properties.';

            errorBox.classList.remove(
                'hidden'
            );
        }
    }
}

function renderProperties(payload) {
    const container =
        document.getElementById(
            'properties-list'
        );

    if (! container) {
        return;
    }

    const buildings =
        Array.isArray(payload?.data)
            ? payload.data
            : [];

    loadedPropertiesById =
        new Map(
            buildings.map(
                (building) => [
                    String(building.id),
                    building,
                ]
            )
        );

    updatePropertyMetrics(
        payload,
        buildings
    );

    if (buildings.length === 0) {
        container.innerHTML = `
            <div
                class="
                    rounded-lg border
                    border-dashed border-slate-200
                    px-6 py-14 text-center
                "
            >
                <div
                    class="
                        mx-auto flex h-11 w-11
                        items-center justify-center
                        rounded-full
                        bg-patrimoine-50
                        text-patrimoine-700
                    "
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M3 21h18"/>
                        <path d="M6 21V5l6-2 6 2v16"/>
                    </svg>
                </div>

                <div
                    class="
                        mt-4 text-sm font-medium
                        text-slate-900
                    "
                >
                    No properties found
                </div>

                <div
                    class="
                        mt-1 text-sm
                        text-slate-500
                    "
                >
                    Add a property or change your search.
                </div>
            </div>
        `;

        return;
    }

    container.innerHTML =
        buildings
            .map(
                (building) =>
                    propertyCard(building)
            )
            .join('');

    container
        .querySelectorAll(
            '[data-property-toggle]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        togglePropertyUnits(
                            button
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-add-existing-unit]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openExistingUnitModal(
                            button.dataset
                                .buildingId,

                            button.dataset
                                .buildingName
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-edit-property]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openEditPropertyModal(
                            button.dataset
                                .buildingId
                        );
                    }
                );
            }
        );

    container
        .querySelectorAll(
            '[data-edit-unit]'
        )
        .forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        openExistingUnitModal(
                            button.dataset
                                .buildingId,

                            button.dataset
                                .buildingName,

                            button.dataset
                                .unitId
                        );
                    }
                );
            }
        );
}

function updatePropertyMetrics(
    payload,
    buildings
) {
    const totalBuildings =
        Number(
            payload?.total
            ?? buildings.length
        );

    const totalUnits =
        buildings.reduce(
            (total, building) =>
                total
                + (
                    Array.isArray(
                        building.units
                    )
                        ? building.units.length
                        : 0
                ),
            0
        );

    const singleUnit =
        buildings.filter(
            (building) =>
                Array.isArray(
                    building.units
                )
                && building.units.length === 1
        ).length;

    const multiUnit =
        buildings.filter(
            (building) =>
                Array.isArray(
                    building.units
                )
                && building.units.length > 1
        ).length;

    setText(
        'properties-building-count',
        totalBuildings
    );

    setText(
        'properties-unit-count',
        totalUnits
    );

    setText(
        'properties-single-unit-count',
        singleUnit
    );

    setText(
        'properties-multi-unit-count',
        multiUnit
    );
}

function propertyCard(building) {
    const units =
        Array.isArray(building.units)
            ? building.units
            : [];

    const ownerships =
        Array.isArray(
            building.ownerships
        )
            ? building.ownerships
            : [];

    const address =
        building.address
        || building.location
        || 'No address provided';

    const buildingName =
        building.name
        || 'Unnamed Property';

    const owners =
        ownerships.length > 0
            ? ownerships
                .map(
                    (ownership) => {
                        const party =
                            ownership.party
                            || {};

                        const name =
                            party.name
                            || party.legal_name
                            || 'Owner';

                        const percentage =
                            ownership
                                .ownership_percentage;

                        return `
                            <span
                                class="
                                    inline-flex items-center
                                    rounded-full
                                    bg-slate-100
                                    px-2.5 py-1
                                    text-xs font-medium
                                    text-slate-600
                                "
                            >
                                ${escapeHtml(name)}
                                ${
                                    percentage
                                        !== undefined
                                        && percentage
                                        !== null
                                            ? ` · ${escapeHtml(
                                                Number(
                                                    percentage
                                                ).toFixed(0)
                                            )}%`
                                            : ''
                                }
                            </span>
                        `;
                    }
                )
                .join('')
            : `
                <span
                    class="
                        text-xs text-slate-400
                    "
                >
                    No ownership information
                </span>
            `;

    const unitRows =
        units.length > 0
            ? units
                .map(
                    (unit) => `
                        <div
                            class="
                                flex items-center
                                justify-between gap-4
                                border-b border-slate-100
                                py-3 last:border-b-0
                            "
                        >
                            <div class="min-w-0 flex-1">
                                <div
                                    class="
                                        text-sm font-medium
                                        text-slate-900
                                    "
                                >
                                    ${escapeHtml(
                                        unit.name
                                        || 'Unnamed Unit'
                                    )}
                                </div>

                                ${
                                    unit.description
                                        ? `
                                            <div
                                                class="
                                                    mt-1 text-xs
                                                    text-slate-500
                                                "
                                            >
                                                ${escapeHtml(
                                                    unit.description
                                                )}
                                            </div>
                                        `
                                        : ''
                                }
                            </div>

                            <div
                                class="
                                    flex shrink-0
                                    items-center gap-2
                                "
                            >
                                <span
                                    class="
                                        rounded-full
                                        bg-slate-100
                                        px-2.5 py-1
                                        text-xs font-medium
                                        text-slate-500
                                    "
                                >
                                    Unit
                                </span>

                                <button
                                    type="button"
                                    data-edit-unit
                                    data-unit-id="${escapeHtml(
                                        unit.id
                                    )}"
                                    data-building-id="${escapeHtml(
                                        building.id
                                    )}"
                                    data-building-name="${escapeHtml(
                                        buildingName
                                    )}"
                                    class="
                                        rounded-lg
                                        border border-slate-200
                                        bg-white px-3 py-1.5
                                        text-xs font-medium
                                        text-slate-700
                                        transition
                                        hover:bg-slate-50
                                    "
                                >
                                    Edit
                                </button>
                            </div>
                        </div>
                    `
                )
                .join('')
            : `
                <div
                    class="
                        py-5 text-sm
                        text-slate-400
                    "
                >
                    No units have been added to this property.
                </div>
            `;

    return `
        <article
            class="
                mb-4 overflow-hidden
                rounded-xl border border-slate-200
                bg-white
                last:mb-0
            "
        >
            <div
                class="
                    flex flex-col gap-5
                    px-5 py-5
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                "
            >
                <div class="min-w-0 flex-1">
                    <div
                        class="
                            flex flex-wrap
                            items-center gap-3
                        "
                    >
                        <h3
                            class="
                                text-base font-semibold
                                text-slate-950
                            "
                        >
                            ${escapeHtml(
                                buildingName
                            )}
                        </h3>

                        <span
                            class="
                                inline-flex
                                rounded-full
                                bg-patrimoine-50
                                px-2.5 py-1
                                text-xs font-medium
                                text-patrimoine-700
                            "
                        >
                            ${units.length}
                            ${
                                units.length === 1
                                    ? 'unit'
                                    : 'units'
                            }
                        </span>
                    </div>

                    <div
                        class="
                            mt-1 flex items-center gap-2
                            text-sm text-slate-500
                        "
                    >
                        <svg
                            class="h-4 w-4 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2"/>
                        </svg>

                        <span class="truncate">
                            ${escapeHtml(address)}
                        </span>
                    </div>

                    <div
                        class="
                            mt-3 flex flex-wrap gap-2
                        "
                    >
                        ${owners}
                    </div>
                </div>

                <div
                    class="
                        flex shrink-0
                        flex-wrap items-center gap-2
                    "
                >
                    <button
                        type="button"
                        data-edit-property
                        data-building-id="${escapeHtml(
                            building.id
                        )}"
                        class="
                            inline-flex items-center gap-2
                            rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                        </svg>

                        Edit
                    </button>

                    <button
                        type="button"
                        data-add-existing-unit
                        data-building-id="${escapeHtml(
                            building.id
                        )}"
                        data-building-name="${escapeHtml(
                            buildingName
                        )}"
                        class="
                            inline-flex items-center gap-2
                            rounded-lg
                            bg-patrimoine-950
                            px-3.5 py-2
                            text-sm font-medium
                            text-white
                            transition
                            hover:bg-patrimoine-900
                        "
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 5v14"/>
                            <path d="M5 12h14"/>
                        </svg>

                        Add Unit
                    </button>

                    <button
                        type="button"
                        data-property-toggle
                        data-building-id="${escapeHtml(
                            building.id
                        )}"
                        aria-expanded="false"
                        class="
                            inline-flex items-center
                            gap-2 rounded-lg
                            border border-slate-200
                            bg-white px-3.5 py-2
                            text-sm font-medium
                            text-slate-700
                            transition
                            hover:bg-slate-50
                        "
                    >
                        <span data-property-toggle-label>
                            View Units
                        </span>

                        <svg
                            data-property-chevron
                            class="
                                h-4 w-4
                                transition-transform
                            "
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div
                id="property-units-${escapeHtml(
                    building.id
                )}"
                class="
                    hidden
                    border-t border-slate-100
                    bg-slate-50/60
                    px-5 py-4
                "
            >
                <div
                    class="
                        mb-2 text-xs font-semibold
                        uppercase tracking-[0.12em]
                        text-slate-400
                    "
                >
                    Units
                </div>

                <div>
                    ${unitRows}
                </div>
            </div>
        </article>
    `;
}

function togglePropertyUnits(button) {
    const buildingId =
        button.dataset.buildingId;

    if (! buildingId) {
        return;
    }

    const panel =
        document.getElementById(
            `property-units-${buildingId}`
        );

    if (! panel) {
        return;
    }

    const expanded =
        button.getAttribute(
            'aria-expanded'
        ) === 'true';

    button.setAttribute(
        'aria-expanded',
        expanded
            ? 'false'
            : 'true'
    );

    panel.classList.toggle(
        'hidden',
        expanded
    );

    const chevron =
        button.querySelector(
            '[data-property-chevron]'
        );

    if (chevron) {
        chevron.classList.toggle(
            'rotate-180',
            ! expanded
        );
    }

    const label =
        button.querySelector(
            '[data-property-toggle-label]'
        );

    if (label) {
        label.textContent =
            expanded
                ? 'View Units'
                : 'Hide Units';
    }
}

function renderPropertiesPagination(
    payload,
    search
) {
    const container =
        document.getElementById(
            'properties-pagination'
        );

    if (! container) {
        return;
    }

    const currentPage =
        Number(
            payload?.current_page
            ?? 1
        );

    const lastPage =
        Number(
            payload?.last_page
            ?? 1
        );

    if (lastPage <= 1) {
        container.classList.add(
            'hidden'
        );

        container.innerHTML = '';

        return;
    }

    container.classList.remove(
        'hidden'
    );

    container.innerHTML = `
        <div
            class="
                flex items-center
                justify-between gap-4
            "
        >
            <div
                class="
                    text-sm text-slate-500
                "
            >
                Page ${currentPage}
                of ${lastPage}
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    id="properties-previous"
                    ${
                        currentPage <= 1
                            ? 'disabled'
                            : ''
                    }
                    class="
                        rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2
                        text-sm font-medium
                        text-slate-700
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    Previous
                </button>

                <button
                    type="button"
                    id="properties-next"
                    ${
                        currentPage >= lastPage
                            ? 'disabled'
                            : ''
                    }
                    class="
                        rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2
                        text-sm font-medium
                        text-slate-700
                        disabled:cursor-not-allowed
                        disabled:opacity-40
                    "
                >
                    Next
                </button>
            </div>
        </div>
    `;

    const previous =
        document.getElementById(
            'properties-previous'
        );

    const next =
        document.getElementById(
            'properties-next'
        );

    previous?.addEventListener(
        'click',
        () => {
            if (currentPage > 1) {
                loadProperties(
                    search,
                    currentPage - 1
                );
            }
        }
    );

    next?.addEventListener(
        'click',
        () => {
            if (
                currentPage < lastPage
            ) {
                loadProperties(
                    search,
                    currentPage + 1
                );
            }
        }
    );
}

/*
|--------------------------------------------------------------------------
| Property Create / Edit
|--------------------------------------------------------------------------
*/

let availableOwnerParties = [];

let propertyFormMode =
    'create';

let editingPropertyId =
    null;

function initializePropertyCreation() {
    const modal =
        document.getElementById(
            'property-modal'
        );

    const form =
        document.getElementById(
            'property-form'
        );

    const openButton =
        document.getElementById(
            'add-property-button'
        );

    if (
        ! modal
        || ! form
        || ! openButton
    ) {
        return;
    }

    const closeButton =
        document.getElementById(
            'property-modal-close'
        );

    const cancelButton =
        document.getElementById(
            'property-cancel-button'
        );

    const backdrop =
        document.getElementById(
            'property-modal-backdrop'
        );

    const addOwnerButton =
        document.getElementById(
            'add-owner-button'
        );

    const addUnitButton =
        document.getElementById(
            'add-unit-button'
        );

    openButton.addEventListener(
        'click',
        openPropertyModal
    );

    closeButton?.addEventListener(
        'click',
        closePropertyModal
    );

    cancelButton?.addEventListener(
        'click',
        closePropertyModal
    );

    backdrop?.addEventListener(
        'click',
        closePropertyModal
    );

    addOwnerButton?.addEventListener(
        'click',
        () => {
            addPropertyOwnerRow();
        }
    );

    addUnitButton?.addEventListener(
        'click',
        () => {
            addPropertyUnitRow();
        }
    );

    form.addEventListener(
        'submit',
        submitPropertyForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && ! modal.classList.contains(
                    'hidden'
                )
            ) {
                closePropertyModal();
            }
        }
    );
}

function configurePropertyModal(mode) {
    propertyFormMode =
        mode === 'edit'
            ? 'edit'
            : 'create';

    const editing =
        propertyFormMode === 'edit';

    const title =
        document.getElementById(
            'property-modal-title'
        );

    const description =
        document.getElementById(
            'property-modal-description'
        );

    const unitsSection =
        document.getElementById(
            'property-units-section'
        );

    const submitButton =
        document.getElementById(
            'property-submit-button'
        );

    /*
     * IDs are optional so this JS remains tolerant while the matching
     * Blade markup is being updated.
     */
    if (title) {
        title.textContent =
            editing
                ? 'Edit Property'
                : 'Add Property';
    }

    if (description) {
        description.textContent =
            editing
                ? 'Update the building details and ownership allocation.'
                : 'Create a building, define its ownership and add its units.';
    }

    if (unitsSection) {
        unitsSection.classList.toggle(
            'hidden',
            editing
        );
    }

    if (submitButton) {
        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Create Property';
    }
}

async function openPropertyModal() {
    const modal =
        document.getElementById(
            'property-modal'
        );

    if (! modal) {
        return;
    }

    resetPropertyForm();

    editingPropertyId =
        null;

    configurePropertyModal(
        'create'
    );

    modal.classList.remove(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    try {
        await loadOwnerParties();

        addPropertyOwnerRow(
            null,
            100
        );

        addPropertyUnitRow();

        document
            .getElementById(
                'property-name'
            )
            ?.focus();
    } catch (error) {
        showPropertyFormError(
            error instanceof Error
                ? error.message
                : 'Unable to load property owners.'
        );
    }
}

async function openEditPropertyModal(
    buildingId
) {
    const modal =
        document.getElementById(
            'property-modal'
        );

    const numericBuildingId =
        Number(buildingId);

    if (
        ! modal
        || ! Number.isInteger(
            numericBuildingId
        )
        || numericBuildingId <= 0
    ) {
        return;
    }

    resetPropertyForm();

    editingPropertyId =
        numericBuildingId;

    configurePropertyModal(
        'edit'
    );

    modal.classList.remove(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    try {
        /*
         * Always fetch the Building being edited from the API. This avoids
         * editing stale ownership information from a previously rendered
         * list page.
         */
        const [
            buildingResponse,
        ] = await Promise.all([
            apiRequest(
                `/api/buildings/${numericBuildingId}`
            ),

            loadOwnerParties(),
        ]);

        const building =
            await parseJsonResponse(
                buildingResponse
            );

        const nameInput =
            document.getElementById(
                'property-name'
            );

        const locationInput =
            document.getElementById(
                'property-location'
            );

        const addressInput =
            document.getElementById(
                'property-address'
            );

        const descriptionInput =
            document.getElementById(
                'property-description'
            );

        if (nameInput) {
            nameInput.value =
                building.name
                || '';
        }

        if (locationInput) {
            locationInput.value =
                building.location
                || '';
        }

        if (addressInput) {
            addressInput.value =
                building.address
                || '';
        }

        if (descriptionInput) {
            descriptionInput.value =
                building.description
                || '';
        }

        const ownerships =
            Array.isArray(
                building.ownerships
            )
                ? building.ownerships
                : [];

        if (ownerships.length > 0) {
            ownerships.forEach(
                (ownership) => {
                    addPropertyOwnerRow(
                        ownership.party_id
                        ?? ownership.party?.id
                        ?? null,

                        ownership
                            .ownership_percentage
                    );
                }
            );
        } else {
            /*
             * This should not normally occur because Patrimoine requires
             * complete Building ownership, but the fallback keeps imported
             * or historical data editable.
             */
            addPropertyOwnerRow(
                null,
                100
            );
        }

        nameInput?.focus();
    } catch (error) {
        showPropertyFormError(
            error instanceof Error
                ? error.message
                : 'Unable to load property.'
        );
    }
}

function closePropertyModal() {
    const modal =
        document.getElementById(
            'property-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    resetPropertyForm();

    editingPropertyId =
        null;

    configurePropertyModal(
        'create'
    );
}

function resetPropertyForm() {
    const form =
        document.getElementById(
            'property-form'
        );

    form?.reset();

    const ownerRows =
        document.getElementById(
            'property-owner-rows'
        );

    const unitRows =
        document.getElementById(
            'property-unit-rows'
        );

    if (ownerRows) {
        ownerRows.innerHTML = '';
    }

    if (unitRows) {
        unitRows.innerHTML = '';
    }

    hidePropertyFormError();

    updateOwnershipTotal();
}

async function loadOwnerParties() {
    const response =
        await apiRequest(
            '/api/parties?role=owner&per_page=100'
        );

    const payload =
        await parseJsonResponse(
            response
        );

    availableOwnerParties =
        Array.isArray(payload?.data)
            ? payload.data
            : [];
}

function ownerPartyDisplayName(party) {
    return party?.name
        || party?.legal_name
        || `Party #${party?.id ?? ''}`;
}

function ownerPartyOptions(
    selectedPartyId = null
) {
    const options =
        availableOwnerParties
            .map(
                (party) => `
                    <option
                        value="${escapeHtml(
                            party.id
                        )}"
                        ${
                            String(party.id)
                                === String(
                                    selectedPartyId
                                )
                                ? 'selected'
                                : ''
                        }
                    >
                        ${escapeHtml(
                            ownerPartyDisplayName(
                                party
                            )
                        )}
                    </option>
                `
            )
            .join('');

    return `
        <option value="">
            ${
                availableOwnerParties.length === 0
                    ? 'Create an owner first…'
                    : 'Select owner…'
            }
        </option>

        ${options}
    `;
}

function addPropertyOwnerRow(
    selectedPartyId = null,
    percentage = ''
) {
    const container =
        document.getElementById(
            'property-owner-rows'
        );

    if (! container) {
        return;
    }

    const row =
        document.createElement('div');

    row.className =
        'property-owner-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-3 sm:grid-cols-[1fr_150px_auto] sm:items-end';

    row.innerHTML = `
        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Owner
            </label>

            <div class="flex gap-2">
                <select
                    data-owner-party
                    required
                    class="
                        min-w-0 flex-1 rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm
                        outline-none transition
                        focus:border-patrimoine-500
                        focus:ring-2
                        focus:ring-patrimoine-100
                    "
                >
                    ${ownerPartyOptions(
                        selectedPartyId
                    )}
                </select>

                <button
                    type="button"
                    data-create-owner
                    class="
                        shrink-0 rounded-lg
                        border border-slate-200
                        bg-white px-3 py-2.5
                        text-sm font-medium
                        text-patrimoine-800
                        transition
                        hover:bg-patrimoine-50
                    "
                    title="Create a new owner"
                >
                    + New
                </button>
            </div>

            ${
                availableOwnerParties.length === 0
                    ? `
                        <p
                            class="
                                mt-1.5 text-xs
                                text-slate-500
                            "
                        >
                            No owners yet. Create the first Owner Party.
                        </p>
                    `
                    : ''
            }
        </div>

        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Ownership %
            </label>

            <input
                data-owner-percentage
                type="number"
                required
                min="0.01"
                max="100"
                step="0.01"
                value="${escapeHtml(
                    percentage
                )}"
                class="
                    w-full rounded-lg
                    border border-slate-200
                    bg-white px-3 py-2.5
                    text-sm
                    outline-none transition
                    focus:border-patrimoine-500
                    focus:ring-2
                    focus:ring-patrimoine-100
                "
            >
        </div>

        <button
            type="button"
            data-remove-owner
            class="
                inline-flex h-10 items-center
                justify-center rounded-lg
                border border-slate-200
                bg-white px-3
                text-sm text-slate-500
                transition
                hover:border-red-200
                hover:bg-red-50
                hover:text-red-600
            "
        >
            Remove
        </button>
    `;

    container.appendChild(row);

    row
        .querySelector(
            '[data-owner-percentage]'
        )
        ?.addEventListener(
            'input',
            updateOwnershipTotal
        );

    row
        .querySelector(
            '[data-create-owner]'
        )
        ?.addEventListener(
            'click',
            () => {
                openOwnerModal(row);
            }
        );

    row
        .querySelector(
            '[data-remove-owner]'
        )
        ?.addEventListener(
            'click',
            () => {
                const rows =
                    container.querySelectorAll(
                        '.property-owner-row'
                    );

                if (rows.length <= 1) {
                    showPropertyFormError(
                        'A property must have at least one owner.'
                    );

                    return;
                }

                row.remove();

                updateOwnershipTotal();
            }
        );

    updateOwnershipTotal();
}

function updateOwnershipTotal() {
    const output =
        document.getElementById(
            'ownership-total'
        );

    if (! output) {
        return;
    }

    const inputs =
        document.querySelectorAll(
            '#property-owner-rows [data-owner-percentage]'
        );

    const total =
        Array.from(inputs)
            .reduce(
                (sum, input) => {
                    const value =
                        Number(input.value);

                    return sum
                        + (
                            Number.isFinite(value)
                                ? value
                                : 0
                        );
                },
                0
            );

    const normalized =
        Math.round(total * 100)
        / 100;

    output.textContent =
        `Total: ${normalized}%`;

    output.classList.remove(
        'bg-slate-100',
        'text-slate-600',
        'bg-green-100',
        'text-green-700',
        'bg-red-100',
        'text-red-700'
    );

    if (
        Math.abs(
            total - 100
        ) < 0.001
    ) {
        output.classList.add(
            'bg-green-100',
            'text-green-700'
        );
    } else if (total > 100) {
        output.classList.add(
            'bg-red-100',
            'text-red-700'
        );
    } else {
        output.classList.add(
            'bg-slate-100',
            'text-slate-600'
        );
    }
}

function addPropertyUnitRow(
    name = '',
    description = ''
) {
    const container =
        document.getElementById(
            'property-unit-rows'
        );

    if (! container) {
        return;
    }

    const row =
        document.createElement('div');

    row.className =
        'property-unit-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-3 sm:grid-cols-[220px_1fr_auto] sm:items-end';

    row.innerHTML = `
        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Unit Name / Number
            </label>

            <input
                data-unit-name
                type="text"
                required
                maxlength="255"
                value="${escapeHtml(name)}"
                placeholder="e.g. Apartment A1"
                class="
                    w-full rounded-lg
                    border border-slate-200
                    bg-white px-3 py-2.5
                    text-sm
                    outline-none transition
                    focus:border-patrimoine-500
                    focus:ring-2
                    focus:ring-patrimoine-100
                "
            >
        </div>

        <div>
            <label
                class="
                    mb-1.5 block
                    text-xs font-medium
                    text-slate-600
                "
            >
                Description
            </label>

            <input
                data-unit-description
                type="text"
                value="${escapeHtml(
                    description
                )}"
                placeholder="Optional description"
                class="
                    w-full rounded-lg
                    border border-slate-200
                    bg-white px-3 py-2.5
                    text-sm
                    outline-none transition
                    focus:border-patrimoine-500
                    focus:ring-2
                    focus:ring-patrimoine-100
                "
            >
        </div>

        <button
            type="button"
            data-remove-unit
            class="
                inline-flex h-10 items-center
                justify-center rounded-lg
                border border-slate-200
                bg-white px-3
                text-sm text-slate-500
                transition
                hover:border-red-200
                hover:bg-red-50
                hover:text-red-600
            "
        >
            Remove
        </button>
    `;

    container.appendChild(row);

    row
        .querySelector(
            '[data-remove-unit]'
        )
        ?.addEventListener(
            'click',
            () => {
                const rows =
                    container.querySelectorAll(
                        '.property-unit-row'
                    );

                if (rows.length <= 1) {
                    showPropertyFormError(
                        'A property must have at least one unit.'
                    );

                    return;
                }

                row.remove();
            }
        );
}

function collectPropertyOwners() {
    const rows =
        document.querySelectorAll(
            '#property-owner-rows .property-owner-row'
        );

    return Array
        .from(rows)
        .map(
            (row) => ({
                party_id:
                    Number(
                        row.querySelector(
                            '[data-owner-party]'
                        )?.value
                    ),

                ownership_percentage:
                    Number(
                        row.querySelector(
                            '[data-owner-percentage]'
                        )?.value
                    ),
            })
        );
}

function collectPropertyUnits() {
    const rows =
        document.querySelectorAll(
            '#property-unit-rows .property-unit-row'
        );

    return Array
        .from(rows)
        .map(
            (row) => ({
                name:
                    String(
                        row.querySelector(
                            '[data-unit-name]'
                        )?.value
                        || ''
                    ).trim(),

                description:
                    String(
                        row.querySelector(
                            '[data-unit-description]'
                        )?.value
                        || ''
                    ).trim(),
            })
        );
}

function validatePropertyOwnership(
    owners
) {
    if (owners.length === 0) {
        throw new Error(
            'A property must have at least one owner.'
        );
    }

    const ownerIds =
        owners.map(
            (owner) =>
                owner.party_id
        );

    if (
        ownerIds.some(
            (id) =>
                ! Number.isInteger(id)
                || id <= 0
        )
    ) {
        throw new Error(
            'Select an owner for every ownership row.'
        );
    }

    if (
        new Set(ownerIds).size
        !== ownerIds.length
    ) {
        throw new Error(
            'The same owner cannot be added more than once.'
        );
    }

    if (
        owners.some(
            (owner) =>
                ! Number.isFinite(
                    owner.ownership_percentage
                )
                || owner.ownership_percentage
                    <= 0
                || owner.ownership_percentage
                    > 100
        )
    ) {
        throw new Error(
            'Enter a valid ownership percentage for every owner.'
        );
    }

    const totalOwnership =
        owners.reduce(
            (total, owner) =>
                total
                + owner
                    .ownership_percentage,
            0
        );

    if (
        Math.abs(
            totalOwnership - 100
        ) > 0.001
    ) {
        throw new Error(
            'Property ownership must total exactly 100%.'
        );
    }
}

function validatePropertyCreation(
    owners,
    units
) {
    validatePropertyOwnership(
        owners
    );

    if (units.length === 0) {
        throw new Error(
            'A property must have at least one unit.'
        );
    }

    if (
        units.some(
            (unit) =>
                unit.name === ''
        )
    ) {
        throw new Error(
            'Every unit must have a name or number.'
        );
    }

    const normalizedUnitNames =
        units.map(
            (unit) =>
                unit.name
                    .toLowerCase()
        );

    if (
        new Set(
            normalizedUnitNames
        ).size
        !== normalizedUnitNames.length
    ) {
        throw new Error(
            'Unit names must be unique within the property.'
        );
    }
}

async function submitPropertyForm(event) {
    event.preventDefault();

    const form =
        document.getElementById(
            'property-form'
        );

    const submitButton =
        document.getElementById(
            'property-submit-button'
        );

    if (
        ! form
        || ! submitButton
    ) {
        return;
    }

    hidePropertyFormError();

    if (! form.reportValidity()) {
        return;
    }

    const owners =
        collectPropertyOwners();

    const editing =
        propertyFormMode === 'edit'
        && Number.isInteger(
            editingPropertyId
        );

    const units =
        editing
            ? []
            : collectPropertyUnits();

    try {
        if (editing) {
            validatePropertyOwnership(
                owners
            );
        } else {
            validatePropertyCreation(
                owners,
                units
            );
        }

        submitButton.disabled = true;

        submitButton.textContent =
            editing
                ? 'Saving Changes…'
                : 'Creating Property…';

        const buildingPayload = {
            name:
                formValue(
                    'property-name'
                ),

            location:
                nullableFormValue(
                    'property-location'
                ),

            address:
                nullableFormValue(
                    'property-address'
                ),

            description:
                nullableFormValue(
                    'property-description'
                ),

            owners,
        };

        /*
         * Editing a Building deliberately does not change its Units.
         * Units have their own independent edit workflow.
         */
        if (editing) {
            const response =
                await apiRequest(
                    `/api/buildings/${editingPropertyId}`,
                    {
                        method: 'PATCH',

                        body:
                            JSON.stringify(
                                buildingPayload
                            ),
                    }
                );

            await parseJsonResponse(
                response
            );

            closePropertyModal();

            const search =
                formValue(
                    'property-search'
                );

            await loadProperties(
                search,
                1
            );

            return;
        }

        /*
         * Create the Building before Units because Unit creation requires
         * a valid Building ID.
         */
        const buildingResponse =
            await apiRequest(
                '/api/buildings',
                {
                    method: 'POST',

                    body:
                        JSON.stringify(
                            buildingPayload
                        ),
                }
            );

        const building =
            await parseJsonResponse(
                buildingResponse
            );

        for (const unit of units) {
            const unitResponse =
                await apiRequest(
                    '/api/units',
                    {
                        method: 'POST',

                        body:
                            JSON.stringify({
                                building_id:
                                    building.id,

                                name:
                                    unit.name,

                                description:
                                    unit.description
                                    || null,
                            }),
                    }
                );

            await parseJsonResponse(
                unitResponse
            );
        }

        closePropertyModal();

        const search =
            formValue(
                'property-search'
            );

        await loadProperties(
            search,
            1
        );
    } catch (error) {
        showPropertyFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? 'Unable to update property.'
                        : 'Unable to create property.'
                )
        );
    } finally {
        submitButton.disabled = false;

        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Create Property';
    }
}

function showPropertyFormError(message) {
    const box =
        document.getElementById(
            'property-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );

    box.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
}

function hidePropertyFormError() {
    const box =
        document.getElementById(
            'property-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Inline Owner Creation
|--------------------------------------------------------------------------
*/

let ownerTargetRow = null;

function initializeOwnerCreation() {
    const modal =
        document.getElementById(
            'owner-modal'
        );

    const form =
        document.getElementById(
            'owner-form'
        );

    if (! modal || ! form) {
        return;
    }

    document
        .getElementById(
            'owner-modal-close'
        )
        ?.addEventListener(
            'click',
            closeOwnerModal
        );

    document
        .getElementById(
            'owner-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeOwnerModal
        );

    document
        .getElementById(
            'owner-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeOwnerModal
        );

    document
        .getElementById(
            'owner-type'
        )
        ?.addEventListener(
            'change',
            updateOwnerTypeFields
        );

    form.addEventListener(
        'submit',
        submitOwnerForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && ! modal.classList.contains(
                    'hidden'
                )
            ) {
                closeOwnerModal();
            }
        }
    );
}

function openOwnerModal(targetRow) {
    const modal =
        document.getElementById(
            'owner-modal'
        );

    const form =
        document.getElementById(
            'owner-form'
        );

    if (! modal || ! form) {
        return;
    }

    ownerTargetRow =
        targetRow;

    form.reset();

    hideOwnerFormError();

    updateOwnerTypeFields();

    modal.classList.remove(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document
        .getElementById(
            'owner-name'
        )
        ?.focus();
}

function closeOwnerModal() {
    const modal =
        document.getElementById(
            'owner-modal'
        );

    if (! modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    ownerTargetRow =
        null;

    hideOwnerFormError();
}

function updateOwnerTypeFields() {
    const type =
        document
            .getElementById(
                'owner-type'
            )
            ?.value
        || 'person';

    const personFields =
        document.getElementById(
            'owner-person-fields'
        );

    const organisationFields =
        document.getElementById(
            'owner-organisation-fields'
        );

    const person =
        type === 'person';

    personFields?.classList.toggle(
        'hidden',
        ! person
    );

    organisationFields?.classList.toggle(
        'hidden',
        person
    );
}

async function submitOwnerForm(event) {
    event.preventDefault();

    const type =
        document
            .getElementById(
                'owner-type'
            )
            ?.value
        || 'person';

    const submitButton =
        document.getElementById(
            'owner-submit-button'
        );

    if (! submitButton) {
        return;
    }

    hideOwnerFormError();

    let payload;

    if (type === 'person') {
        const name =
            formValue('owner-name');

        const phone =
            formValue('owner-phone');

        const email =
            formValue('owner-email');

        if (
            name === ''
            || phone === ''
            || email === ''
        ) {
            showOwnerFormError(
                'Name, phone and email are required for a person.'
            );

            return;
        }

        payload = {
            type: 'person',
            name,
            phone,
            email,

            address:
                nullableFormValue(
                    'owner-address'
                ),

            roles: [
                'owner',
            ],
        };
    } else {
        const legalName =
            formValue(
                'owner-legal-name'
            );

        const contactName =
            formValue(
                'owner-contact-name'
            );

        const contactPhone =
            formValue(
                'owner-contact-phone'
            );

        const contactEmail =
            formValue(
                'owner-contact-email'
            );

        if (
            legalName === ''
            || contactName === ''
            || contactPhone === ''
            || contactEmail === ''
        ) {
            showOwnerFormError(
                'Legal name and contact person details are required.'
            );

            return;
        }

        payload = {
            type,

            legal_name:
                legalName,

            address:
                nullableFormValue(
                    'owner-address'
                ),

            contact_person_name:
                contactName,

            contact_person_phone:
                contactPhone,

            contact_person_email:
                contactEmail,

            roles: [
                'owner',
            ],
        };
    }

    try {
        submitButton.disabled = true;

        submitButton.textContent =
            'Creating Owner…';

        const response =
            await apiRequest(
                '/api/parties',
                {
                    method: 'POST',

                    body:
                        JSON.stringify(
                            payload
                        ),
                }
            );

        const owner =
            await parseJsonResponse(
                response
            );

        availableOwnerParties.push(
            owner
        );

        availableOwnerParties.sort(
            (a, b) =>
                ownerPartyDisplayName(a)
                    .localeCompare(
                        ownerPartyDisplayName(b)
                    )
        );

        refreshOwnerSelects(
            owner.id
        );

        closeOwnerModal();

        hidePropertyFormError();
    } catch (error) {
        showOwnerFormError(
            error instanceof Error
                ? error.message
                : 'Unable to create owner.'
        );
    } finally {
        submitButton.disabled = false;

        submitButton.textContent =
            'Create Owner';
    }
}

function refreshOwnerSelects(
    newlyCreatedOwnerId = null
) {
    const rows =
        document.querySelectorAll(
            '#property-owner-rows .property-owner-row'
        );

    rows.forEach(
        (row) => {
            const select =
                row.querySelector(
                    '[data-owner-party]'
                );

            if (! select) {
                return;
            }

            const previousValue =
                select.value;

            const shouldSelectNewOwner =
                ownerTargetRow === row
                && newlyCreatedOwnerId
                    !== null;

            select.innerHTML =
                ownerPartyOptions(
                    shouldSelectNewOwner
                        ? newlyCreatedOwnerId
                        : previousValue
                );
        }
    );
}

function showOwnerFormError(message) {
    const box =
        document.getElementById(
            'owner-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );
}

function hideOwnerFormError() {
    const box =
        document.getElementById(
            'owner-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Add / Edit Unit on Existing Property
|--------------------------------------------------------------------------
*/

let existingUnitFormMode =
    'create';

let editingUnitId =
    null;

function initializeExistingUnitCreation() {
    const modal =
        document.getElementById(
            'existing-unit-modal'
        );

    const form =
        document.getElementById(
            'existing-unit-form'
        );

    if (! modal || ! form) {
        return;
    }

    document
        .getElementById(
            'existing-unit-modal-close'
        )
        ?.addEventListener(
            'click',
            closeExistingUnitModal
        );

    document
        .getElementById(
            'existing-unit-cancel-button'
        )
        ?.addEventListener(
            'click',
            closeExistingUnitModal
        );

    document
        .getElementById(
            'existing-unit-modal-backdrop'
        )
        ?.addEventListener(
            'click',
            closeExistingUnitModal
        );

    form.addEventListener(
        'submit',
        submitExistingUnitForm
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key === 'Escape'
                && ! modal.classList.contains(
                    'hidden'
                )
            ) {
                closeExistingUnitModal();
            }
        }
    );
}

function openExistingUnitModal(
    buildingId,
    buildingName,
    unitId = null
) {
    const modal =
        document.getElementById(
            'existing-unit-modal'
        );

    const form =
        document.getElementById(
            'existing-unit-form'
        );

    if (! modal || ! form) {
        return;
    }

    form.reset();

    hideExistingUnitFormError();

    const numericBuildingId =
        Number(buildingId);

    const numericUnitId =
        unitId !== null
            ? Number(unitId)
            : null;

    if (
        ! Number.isInteger(
            numericBuildingId
        )
        || numericBuildingId <= 0
    ) {
        return;
    }

    const editing =
        Number.isInteger(
            numericUnitId
        )
        && numericUnitId > 0;

    existingUnitFormMode =
        editing
            ? 'edit'
            : 'create';

    editingUnitId =
        editing
            ? numericUnitId
            : null;

    const buildingIdInput =
        document.getElementById(
            'existing-unit-building-id'
        );

    const buildingNameElement =
        document.getElementById(
            'existing-unit-building-name'
        );

    const title =
        document.getElementById(
            'existing-unit-modal-title'
        );

    const descriptionElement =
        document.getElementById(
            'existing-unit-modal-description'
        );

    const submitButton =
        document.getElementById(
            'existing-unit-submit-button'
        );

    if (buildingIdInput) {
        buildingIdInput.value =
            String(numericBuildingId);
    }

    if (buildingNameElement) {
        buildingNameElement.textContent =
            buildingName
            || 'Property';
    }

    if (title) {
        title.textContent =
            editing
                ? 'Edit Unit'
                : 'Add Unit';
    }

    if (descriptionElement) {
        descriptionElement.textContent =
            editing
                ? 'Update this unit\'s name or description.'
                : 'Add a leasable unit to an existing property.';
    }

    if (submitButton) {
        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Add Unit';
    }

    if (editing) {
        const building =
            loadedPropertiesById.get(
                String(
                    numericBuildingId
                )
            );

        const unit =
            Array.isArray(
                building?.units
            )
                ? building.units.find(
                    (candidate) =>
                        Number(candidate.id)
                        === numericUnitId
                )
                : null;

        if (! unit) {
            showExistingUnitFormError(
                'Unable to locate this unit.'
            );

            return;
        }

        const nameInput =
            document.getElementById(
                'existing-unit-name'
            );

        const descriptionInput =
            document.getElementById(
                'existing-unit-description'
            );

        if (nameInput) {
            nameInput.value =
                unit.name
                || '';
        }

        if (descriptionInput) {
            descriptionInput.value =
                unit.description
                || '';
        }
    }

    modal.classList.remove(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

    document
        .getElementById(
            'existing-unit-name'
        )
        ?.focus();
}

function closeExistingUnitModal() {
    const modal =
        document.getElementById(
            'existing-unit-modal'
        );

    const form =
        document.getElementById(
            'existing-unit-form'
        );

    if (! modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    form?.reset();

    const buildingNameElement =
        document.getElementById(
            'existing-unit-building-name'
        );

    if (buildingNameElement) {
        buildingNameElement.textContent =
            '—';
    }

    existingUnitFormMode =
        'create';

    editingUnitId =
        null;

    const title =
        document.getElementById(
            'existing-unit-modal-title'
        );

    const descriptionElement =
        document.getElementById(
            'existing-unit-modal-description'
        );

    const submitButton =
        document.getElementById(
            'existing-unit-submit-button'
        );

    if (title) {
        title.textContent =
            'Add Unit';
    }

    if (descriptionElement) {
        descriptionElement.textContent =
            'Add a leasable unit to an existing property.';
    }

    if (submitButton) {
        submitButton.textContent =
            'Add Unit';
    }

    hideExistingUnitFormError();
}

async function submitExistingUnitForm(
    event
) {
    event.preventDefault();

    const form =
        document.getElementById(
            'existing-unit-form'
        );

    const submitButton =
        document.getElementById(
            'existing-unit-submit-button'
        );

    if (! form || ! submitButton) {
        return;
    }

    hideExistingUnitFormError();

    if (! form.reportValidity()) {
        return;
    }

    const buildingId =
        Number(
            document
                .getElementById(
                    'existing-unit-building-id'
                )
                ?.value
        );

    const name =
        formValue(
            'existing-unit-name'
        );

    const description =
        nullableFormValue(
            'existing-unit-description'
        );

    if (
        ! Number.isInteger(buildingId)
        || buildingId <= 0
    ) {
        showExistingUnitFormError(
            'A valid property must be selected.'
        );

        return;
    }

    if (name === '') {
        showExistingUnitFormError(
            'Unit name or number is required.'
        );

        return;
    }

    const editing =
        existingUnitFormMode === 'edit'
        && Number.isInteger(
            editingUnitId
        )
        && editingUnitId > 0;

    try {
        submitButton.disabled = true;

        submitButton.textContent =
            editing
                ? 'Saving Changes…'
                : 'Adding Unit…';

        const endpoint =
            editing
                ? `/api/units/${editingUnitId}`
                : '/api/units';

        const response =
            await apiRequest(
                endpoint,
                {
                    method:
                        editing
                            ? 'PATCH'
                            : 'POST',

                    body:
                        JSON.stringify({
                            building_id:
                                buildingId,

                            name,

                            description,
                        }),
                }
            );

        await parseJsonResponse(
            response
        );

        closeExistingUnitModal();

        const search =
            formValue(
                'property-search'
            );

        /*
         * Refresh the portfolio so counts, unit information and property
         * classification immediately reflect the change.
         */
        await loadProperties(
            search,
            1
        );
    } catch (error) {
        showExistingUnitFormError(
            error instanceof Error
                ? error.message
                : (
                    editing
                        ? 'Unable to update unit.'
                        : 'Unable to add unit.'
                )
        );
    } finally {
        submitButton.disabled = false;

        submitButton.textContent =
            editing
                ? 'Save Changes'
                : 'Add Unit';
    }
}

function showExistingUnitFormError(
    message
) {
    const box =
        document.getElementById(
            'existing-unit-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent =
        message;

    box.classList.remove(
        'hidden'
    );
}

function hideExistingUnitFormError() {
    const box =
        document.getElementById(
            'existing-unit-form-error'
        );

    if (! box) {
        return;
    }

    box.textContent = '';

    box.classList.add(
        'hidden'
    );
}

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    async () => {
        /*
         * Login and authenticated application pages are mutually exclusive.
         */
        const loginPage =
            await initializeLogin();

        if (loginPage) {
            return;
        }

        const authenticated =
            await initializeAuthenticatedShell();

        if (! authenticated) {
            return;
        }

        await initializeDashboard();

        await initializeProperties();

        initializeOwnerCreation();

        initializeExistingUnitCreation();
    }
);
