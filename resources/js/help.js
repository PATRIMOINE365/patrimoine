/*
|--------------------------------------------------------------------------
| Patrimoine Help & Documentation
|--------------------------------------------------------------------------
|
| V1.0.7 in-app documentation, available to every authenticated role.
|
| Two tabs:
|
| - Guide       Client-side documentation topics, searchable and
|               filterable by category. Content lives in `help.`
|               translation keys so both languages stay complete.
| - Update log  Localized release history served by GET /api/release-log.
|
| The #updates location hash deep-links to the Update log tab.
|
*/

import {
    apiRequest,
    escapeHtml,
    parseJsonResponse,
    translate,
} from './core.js';

import {
    translations,
} from './translations.js';

import {
    clientPage,
    pageSizeFor,
    renderPagination,
} from './pagination.js';

/*
|--------------------------------------------------------------------------
| Translation Catalogue
|--------------------------------------------------------------------------
|
| Registered into the shared catalogue at module load, before the
| application bootstrap calls applyTranslations(). Keys already present
| in translations.js are never overridden, so the central catalogue
| remains authoritative when these keys are consolidated there.
|
*/

const helpTranslations = {
    en: {
        'help.title':
            'Help & documentation — Patrimoine',

        'help.eyebrow':
            'Support',

        'help.heading':
            'Help & documentation',

        'help.description':
            'How Patrimoine works, organised by topic, plus the history of application updates.',

        'help.tab_guide':
            'Guide',

        'help.tab_updates':
            'Update log',

        'help.search':
            'Search',

        'help.search_placeholder':
            'Search the guide…',

        'help.category':
            'Category',

        'help.all_categories':
            'All categories',

        'help.no_results':
            'No topics match',

        'help.errors_no_matches':
            'Nothing matches what you typed. Try fewer words, or the code itself.',

        'help.no_results_description':
            'Try different words or clear the category filter.',

        'help.updates_loading':
            'Loading update log…',

        'help.unable_load_updates':
            'Unable to load the update log.',

        'help.current_version':
            'You are running version {version}.',

        /* Getting started */

        /* Properties & units */

        /* Parties */

        /* Leases */

        /* Money in */

        /* Owners */

        /* Invoicing & automation */

        /* Reports & exports */

        /* Financial journal & activity log */

        /* Users & settings */

    },

    fr: {
        'help.title':
            'Aide et documentation — Patrimoine',

        'help.eyebrow':
            'Assistance',

        'help.heading':
            'Aide et documentation',

        'help.description':
            'Le fonctionnement de Patrimoine, organisé par thème, ainsi que l’historique des mises à jour de l’application.',

        'help.tab_guide':
            'Guide',

        'help.tab_updates':
            'Journal des mises à jour',

        'help.search':
            'Rechercher',

        'help.search_placeholder':
            'Rechercher dans le guide…',

        'help.category':
            'Catégorie',

        'help.all_categories':
            'Toutes les catégories',

        'help.no_results':
            'Aucun sujet ne correspond',

        'help.errors_no_matches':
            'Rien ne correspond à votre saisie. Essayez moins de mots, ou le code lui-même.',

        'help.no_results_description':
            'Essayez d’autres termes ou effacez le filtre de catégorie.',

        'help.updates_loading':
            'Chargement du journal des mises à jour…',

        'help.unable_load_updates':
            'Impossible de charger le journal des mises à jour.',

        'help.current_version':
            'Vous utilisez la version {version}.',

        /* Premiers pas */

        /* Propriétés et unités */

        /* Parties */

        /* Baux */

        /* Encaissements */

        /* Propriétaires */

        /* Facturation et automatisation */

        /* Rapports et exports */

        /* Journal financier et journal d’activité */

        /* Utilisateurs et paramètres */

    },
};

Object.entries(
    helpTranslations
).forEach(
    ([language, entries]) => {
        translations[language] =
            translations[language]
            || {};

        Object.entries(
            entries
        ).forEach(
            ([key, value]) => {
                if (
                    ! (
                        key
                        in translations[language]
                    )
                ) {
                    translations[language][key] =
                        value;
                }
            }
        );
    }
);

/*
|--------------------------------------------------------------------------
| Content Model
|--------------------------------------------------------------------------
*/

/*
 * The guide is no longer written here.
 *
 * V1.0.33 moved it to lang/{en,fr}/guide.php, where it carries its own
 * shape — categories, tasks, numbered steps and the screenshot each step
 * is illustrated by — and is served by GET /api/guide in the
 * organisation's language. The same file is exported to the public
 * pages, so the manual a customer reads signed in and the one they read
 * signed out cannot drift apart.
 */

let guide = null;

let guideLoaded = false;

/*
|--------------------------------------------------------------------------
| Initialization
|--------------------------------------------------------------------------
*/

let helpSearchTimer =
    null;

let helpUpdatesLoaded =
    false;

export async function initializeHelp() {
    const workspace =
        document.getElementById(
            'help-workspace'
        );

    if (! workspace) {
        return;
    }

    initializeHelpTabs();
    initializeHelpFilters();

    applyHelpLocationHash();

    window.addEventListener(
        'hashchange',
        applyHelpLocationHash
    );

    await loadGuide();
}

/*
|--------------------------------------------------------------------------
| Tabs
|--------------------------------------------------------------------------
*/

function initializeHelpTabs() {
    /*
     * Three tabs now, so the switch is driven by a table rather than by
     * a boolean. Each carries the hash it puts in the address bar, so a
     * link to #errors opens the Error codes tab directly.
     */
    HELP_TABS.forEach(({ tab, button, hash }) => {
        document
            .getElementById(button)
            ?.addEventListener(
                'click',
                () => {
                    window.history.replaceState(
                        null,
                        '',
                        hash === ''
                            ? window.location.pathname + window.location.search
                            : hash
                    );

                    selectHelpTab(tab);
                }
            );
    });
}

const HELP_TABS = [
    { tab: 'guide', button: 'help-tab-guide', panel: 'help-guide-panel', hash: '' },
    { tab: 'errors', button: 'help-tab-errors', panel: 'help-errors-panel', hash: '#errors' },
    { tab: 'updates', button: 'help-tab-updates', panel: 'help-updates-panel', hash: '#updates' },
];

function applyHelpLocationHash() {
    const match = HELP_TABS.find(
        (entry) => entry.hash !== '' && entry.hash === window.location.hash
    );

    selectHelpTab(match ? match.tab : 'guide');
}

function selectHelpTab(
    tab
) {
    const activeClasses = [
        'bg-[var(--pm-surface)]',
        'text-[var(--pm-text)]',
        'shadow-sm',
    ];

    const inactiveClasses = [
        'text-[var(--pm-text-muted)]',
        'hover:text-[var(--pm-text)]',
    ];

    HELP_TABS.forEach((entry) => {
        const active = entry.tab === tab;

        const button = document.getElementById(entry.button);

        if (button) {
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.classList.remove(...activeClasses, ...inactiveClasses);
            button.classList.add(...(active ? activeClasses : inactiveClasses));
        }

        document
            .getElementById(entry.panel)
            ?.classList.toggle('hidden', ! active);
    });

    /* The guide's own filters belong to the guide alone. */
    document
        .getElementById('help-guide-filters')
        ?.classList.toggle('hidden', tab !== 'guide');

    if (tab === 'updates' && ! helpUpdatesLoaded) {
        helpUpdatesLoaded = true;
        loadHelpUpdates();
    }

    if (tab === 'errors' && ! helpErrorsLoaded) {
        helpErrorsLoaded = true;
        loadHelpErrorCodes();
    }
}


/*
|--------------------------------------------------------------------------
| Error codes
|--------------------------------------------------------------------------
|
| The same catalogue as the public page at /errors, read through the API
| so it arrives in the organisation's language.
|
*/

let helpErrorsLoaded = false;
let helpErrorCodes = [];

/*
 * What the reader is looking at right now: which page of the catalogue,
 * and what they have typed into the search box.
 */
let helpErrorsPage = 1;
let helpErrorsSearch = '';
let helpErrorsPayload = null;

async function loadHelpErrorCodes() {
    const container = document.getElementById('help-errors-content');

    if (! container) {
        return;
    }

    try {
        const response = await apiRequest('/api/error-codes');
        const payload = await parseJsonResponse(response);

        helpErrorCodes = payload.codes ?? [];

        helpErrorsPayload = payload;

        renderHelpErrorCodes();
    } catch (error) {
        container.innerHTML = `
            <p class="px-5 py-12 text-center text-sm text-[var(--pm-text-muted)]">
                ${escapeHtml(error.message)}
            </p>
        `;
    }
}

/**
 * Which codes the search currently admits.
 *
 * @returns {Array}
 */
function visibleHelpErrorCodes() {
    const needle = helpErrorsSearch.trim().toLowerCase();

    return helpErrorCodes.filter((entry) => {
        if (entry.hidden) {
            return false;
        }

        if (needle === '') {
            return true;
        }

        const haystack = [
            entry.code,
            entry.title,
            entry.what,
            entry.fix,
        ].join(' ').toLowerCase();

        return haystack.includes(needle);
    });
}

/**
 * Draw one page of the catalogue.
 *
 * Only the families represented on this page get a heading, so a page of
 * money codes is not preceded by eight empty section titles.
 */
function renderHelpErrorCodes() {
    const container = document.getElementById('help-errors-content');

    if (! container || ! helpErrorsPayload) {
        return;
    }

    const contact = helpErrorsPayload.contact ?? {};

    const { rows, meta } = clientPage(
        visibleHelpErrorCodes(),
        helpErrorsPage,
        pageSizeFor('help-error-codes')
    );

    if (rows.length === 0) {
        container.innerHTML = `
            <p class="px-5 py-12 text-center text-sm text-[var(--pm-text-muted)]">
                ${escapeHtml(translate('help.errors_no_matches'))}
            </p>
        `;
    } else {
        container.innerHTML = (helpErrorsPayload.families ?? [])
            .map((family) => {
                const codes = rows.filter(
                    (entry) => entry.family === family.family
                );

                if (codes.length === 0) {
                    return '';
                }

                return `
                    <section class="mt-8" data-help-error-family>
                        <h3 class="border-b border-[var(--pm-border-subtle)] pb-2 text-base font-semibold">
                            ${escapeHtml(family.name)}
                            <span class="text-sm font-normal text-[var(--pm-text-muted)]">· ${family.family}xxx</span>
                        </h3>

                        <div class="mt-4 grid gap-3">
                            ${codes.map((entry) => errorCodeCard(entry, contact)).join('')}
                        </div>
                    </section>
                `;
            })
            .join('');
    }

    renderPagination(
        'help-errors-pagination',
        meta,
        {
            storageKey: 'help-error-codes',
            onPage: (page) => {
                helpErrorsPage = page;

                renderHelpErrorCodes();
            },
            onPageSize: () => {
                helpErrorsPage = 1;

                renderHelpErrorCodes();
            },
        }
    );
}

function errorCodeCard(entry, contact) {
    const support = entry.needs_support
        ? `
            <p class="mt-3 rounded-lg border border-[var(--pm-border-subtle)] bg-[var(--pm-surface-subtle)] px-3 py-2 text-xs">
                <a class="underline" href="tel:${escapeHtml(contact.phone ?? '')}">${escapeHtml(contact.phone_display ?? '')}</a>
                ·
                <a class="underline" target="_blank" rel="noopener" href="https://wa.me/${escapeHtml((contact.whatsapp ?? '').replace('+', ''))}">WhatsApp</a>
                ·
                <a class="underline" href="mailto:${escapeHtml(contact.email ?? '')}">${escapeHtml(contact.email ?? '')}</a>
            </p>
        `
        : '';

    return `
        <article
            class="rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface)] p-4"
            data-help-error-code="${escapeHtml(entry.code)}"
            data-help-error-haystack="${escapeHtml((entry.code + ' ' + entry.title + ' ' + entry.what + ' ' + entry.fix).toLowerCase())}"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h4 class="text-sm font-semibold">${escapeHtml(entry.title)}</h4>
                <span class="shrink-0 rounded-full border border-[var(--pm-border)] px-2 py-0.5 font-mono text-xs text-[var(--pm-text-secondary)]">${escapeHtml(entry.code)}</span>
            </div>

            <p class="mt-2 text-sm text-[var(--pm-text-muted)]">${escapeHtml(entry.what)}</p>
            <p class="mt-1.5 text-sm text-[var(--pm-text-secondary)]">${escapeHtml(entry.fix)}</p>

            ${support}
        </article>
    `;
}

/*
 * Filtering the catalogue, the same way the public page does: the search
 * narrows the whole catalogue and the reader is returned to its first page,
 * because a match three pages away announces itself to nobody.
 */
document.addEventListener('input', (event) => {
    if (event.target?.id !== 'help-error-search') {
        return;
    }

    helpErrorsSearch = event.target.value;

    helpErrorsPage = 1;

    renderHelpErrorCodes();
});

/*
|--------------------------------------------------------------------------
| Guide Filters
|--------------------------------------------------------------------------
*/

function initializeHelpFilters() {
    document
        .getElementById(
            'help-search'
        )
        ?.addEventListener(
            'input',
            () => {
                clearTimeout(
                    helpSearchTimer
                );

                helpSearchTimer =
                    setTimeout(
                        renderHelpGuide,
                        150
                    );
            }
        );

    document
        .getElementById(
            'help-category'
        )
        ?.addEventListener(
            'change',
            renderHelpGuide
        );
}

/**
 * Normalize text for accent- and case-insensitive matching.
 *
 * @param {string} value
 * @returns {string}
 */
function normalizeHelpText(
    value
) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(
            /[̀-ͯ]/g,
            ''
        );
}

/**
 * Fetch the manual, once, in the organisation's language.
 */
async function loadGuide() {
    if (guideLoaded) {
        return;
    }

    guideLoaded = true;

    try {
        const response = await apiRequest('/api/guide');

        guide = await parseJsonResponse(response);

        populateGuideCategories();

        renderHelpGuide();
    } catch (error) {
        guide = null;

        const container =
            document.getElementById('help-guide-content');

        if (container) {
            container.innerHTML = `
                <p class="px-5 py-12 text-center text-sm text-[var(--pm-text-muted)]">
                    ${escapeHtml(error.message)}
                </p>
            `;
        }
    }
}

/**
 * Fill the category filter from the manual itself.
 *
 * The list used to be hardcoded in two places and could disagree with the
 * content. Now there is one source and the filter simply reads it.
 */
function populateGuideCategories() {
    const select = document.getElementById('help-category');

    if (! select || guide === null) {
        return;
    }

    const all = translate('help.all_categories');

    select.innerHTML = [`<option value="">${escapeHtml(all)}</option>`]
        .concat(
            guide.categories.map(
                (category) => `
                    <option value="${escapeHtml(category.id)}">
                        ${escapeHtml(category.title)}
                    </option>
                `
            )
        )
        .join('');
}

/**
 * Does this task answer what was typed?
 *
 * Title, introduction and every step are searched, because somebody hunting
 * "deposit" should find the step that mentions it even when the task is
 * called something else.
 *
 * @param {object} task
 * @param {string} query
 * @returns {boolean}
 */
function guideTaskMatches(task, query) {
    if (query === '') {
        return true;
    }

    const haystack = normalizeHelpText(
        [
            task.title,
            task.intro,
            task.after ?? '',
            ...task.steps.map((step) => step.text),
        ].join(' ')
    );

    return query
        .split(/\s+/)
        .filter(Boolean)
        .every((word) => haystack.includes(word));
}

function renderHelpGuide() {
    const container =
        document.getElementById('help-guide-content');

    if (! container) {
        return;
    }

    if (guide === null) {
        return;
    }

    const query = normalizeHelpText(
        document.getElementById('help-search')?.value || ''
    ).trim();

    const category =
        document.getElementById('help-category')?.value || '';

    const sections = guide.categories
        .filter((entry) => category === '' || entry.id === category)
        .map((entry) => ({
            ...entry,
            tasks: entry.tasks.filter(
                (task) => guideTaskMatches(task, query)
            ),
        }))
        .filter((entry) => entry.tasks.length > 0);

    if (sections.length === 0) {
        container.innerHTML = `
            <div class="pm-card px-6 py-14 text-center">
                <p class="text-sm font-semibold text-[var(--pm-text)]">
                    ${escapeHtml(translate('help.no_results'))}
                </p>

                <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                    ${escapeHtml(translate('help.no_results_description'))}
                </p>
            </div>
        `;

        return;
    }

    container.innerHTML = sections.map(guideCategorySection).join('');
}

function guideCategorySection(category) {
    return `
        <section class="mt-10 first:mt-2" id="guide-${escapeHtml(category.id)}">
            <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--pm-text-muted)]">
                ${escapeHtml(category.title)}
            </h2>

            <p class="mt-2 max-w-2xl text-sm text-[var(--pm-text-muted)]">
                ${escapeHtml(category.summary)}
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4">
                ${category.tasks.map(guideTaskCard).join('')}
            </div>
        </section>
    `;
}

/**
 * One task: what it is for, who may do it, and the steps.
 */
function guideTaskCard(task) {
    const who = task.who
        ? `
            <span class="rounded-full border border-[var(--pm-border)] px-2.5 py-0.5 text-xs text-[var(--pm-text-muted)]">
                ${escapeHtml(task.who)}
            </span>
        `
        : '';

    const after = task.after
        ? `
            <p class="mt-4 rounded-lg border border-[var(--pm-border-subtle)] bg-[var(--pm-surface-subtle)] px-4 py-3 text-sm text-[var(--pm-text-secondary)]">
                ${escapeHtml(task.after)}
            </p>
        `
        : '';

    return `
        <article
            class="pm-card p-5"
            id="guide-task-${escapeHtml(task.id)}"
            data-guide-task="${escapeHtml(task.id)}"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h3 class="text-base font-semibold text-[var(--pm-text)]">
                    ${escapeHtml(task.title)}
                </h3>

                ${who}
            </div>

            <p class="mt-2 text-sm leading-6 text-[var(--pm-text-muted)]">
                ${escapeHtml(task.intro)}
            </p>

            <ol class="mt-4 grid gap-4">
                ${task.steps.map(guideStep).join('')}
            </ol>

            ${after}
        </article>
    `;
}

/**
 * One numbered step, with its screenshot underneath when it has one.
 *
 * The picture is lazy: a category can carry a dozen of them and nobody
 * scrolls through all of it at once.
 */
function guideStep(step) {
    const shot = step.shot
        ? `
            <figure class="mt-3 overflow-hidden rounded-xl border border-[var(--pm-border)]">
                <img
                    src="${escapeHtml(guide.shots)}/${escapeHtml(step.shot)}.webp"
                    alt="${escapeHtml(step.text)}"
                    loading="lazy"
                    decoding="async"
                    class="block w-full"
                >
            </figure>
        `
        : '';

    const note = step.note
        ? `
            <p class="mt-2 text-xs text-[var(--pm-text-muted)]">
                ${escapeHtml(step.note)}
            </p>
        `
        : '';

    return `
        <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-3">
            <span
                class="
                    mt-0.5 inline-flex h-7 w-7 items-center justify-center
                    rounded-full bg-[var(--pm-surface-elevated)]
                    text-xs font-semibold text-[var(--pm-text)]
                "
                aria-hidden="true"
            >${step.number}</span>

            <div>
                <p class="text-sm leading-6 text-[var(--pm-text)]">
                    ${escapeHtml(step.text)}
                </p>

                ${note}
                ${shot}
            </div>
        </li>
    `;
}
async function loadHelpUpdates() {
    const container =
        document.getElementById(
            'help-updates-content'
        );

    if (! container) {
        return;
    }

    hideHelpError();

    container.innerHTML = `
        <div
            class="
                px-5 py-12 text-center
                text-sm
                text-[var(--pm-text-muted)]
            "
        >
            ${escapeHtml(
                translate(
                    'help.updates_loading'
                )
            )}
        </div>
    `;

    try {
        const response =
            await apiRequest(
                '/api/release-log'
            );

        const payload =
            await parseJsonResponse(
                response
            );

        container.innerHTML =
            helpUpdatesMarkup(
                payload
            );
    } catch (error) {
        container.innerHTML =
            '';

        /*
         * Allow a retry the next time the tab is opened.
         */
        helpUpdatesLoaded =
            false;

        showHelpError(
            error instanceof Error
                ? error.message
                : translate(
                    'help.unable_load_updates'
                )
        );
    }
}

function helpUpdatesMarkup(
    payload
) {
    const entries =
        Array.isArray(
            payload?.entries
        )
            ? payload.entries
            : [];

    return `
        <div
            class="
                rounded-xl border
                px-5 py-4
                border-[var(--pm-info-border)]
                bg-[var(--pm-info-background)]
            "
        >
            <div
                class="
                    text-sm font-semibold
                    text-[var(--pm-info-text)]
                "
            >
                ${escapeHtml(
                    translate(
                        'help.current_version',
                        {
                            version:
                                payload?.current_version
                                || '',
                        }
                    )
                )}
            </div>
        </div>

        <div
            class="
                mt-6 space-y-6
                border-l-2
                border-[var(--pm-border)]
                pl-5
                sm:pl-6
            "
        >
            ${entries
                .map(helpReleaseCard)
                .join('')}
        </div>
    `;
}

/**
 * One entry of the update log.
 *
 * An entry covers the releases up to and including its own version, in a
 * couple of sentences. The release-by-release detail lives in the
 * administration console.
 */
function helpReleaseCard(
    entry
) {
    return `
        <article class="relative">
            <span
                class="
                    absolute top-6
                    -left-[25px] h-3 w-3
                    rounded-full border-2
                    border-[var(--pm-border-strong)]
                    bg-[var(--pm-surface)]
                    sm:-left-[29px]
                "
                aria-hidden="true"
            ></span>

            <div class="pm-card p-5">
                <div
                    class="
                        flex flex-wrap
                        items-baseline gap-x-3
                        gap-y-1
                    "
                >
                    <span
                        class="
                            font-mono text-sm
                            font-semibold
                            text-[var(--pm-accent)]
                        "
                    >
                        v${escapeHtml(
                            entry?.version
                            || ''
                        )}
                    </span>

                    <span
                        class="
                            text-xs
                            text-[var(--pm-text-muted)]
                        "
                    >
                        ${escapeHtml(
                            formatHelpReleaseDate(
                                entry?.date
                            )
                        )}
                    </span>
                </div>

                <p
                    class="
                        mt-3 text-sm leading-6
                        text-[var(--pm-text-secondary)]
                    "
                >
                    ${escapeHtml(
                        entry?.summary
                        || ''
                    )}
                </p>
            </div>
        </article>
    `;
}

function formatHelpReleaseDate(
    value
) {
    if (! value) {
        return '';
    }

    const parts =
        String(value)
            .slice(0, 10)
            .split('-');

    if (parts.length !== 3) {
        return String(value);
    }

    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}

/*
|--------------------------------------------------------------------------
| Errors
|--------------------------------------------------------------------------
*/

function showHelpError(
    message
) {
    const element =
        document.getElementById(
            'help-error'
        );

    if (! element) {
        return;
    }

    element.textContent =
        message;

    element.classList.remove(
        'hidden'
    );
}

function hideHelpError() {
    const element =
        document.getElementById(
            'help-error'
        );

    if (! element) {
        return;
    }

    element.textContent = '';

    element.classList.add(
        'hidden'
    );
}
