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
    restoreButton,
    setButtonBusy,
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
            'Ask us a question, read how a task is done, or look up a code.',

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
            'Try fewer words, or the name of the screen.',

        /* ---- V1.0.36 ---- */

        'help.tab_support':
            'Contact support',

        'help.support_intro':
            'Tell us what you were trying to do and what happened instead. Your name, your organisation and the address we should answer are taken from your account, so there is nothing else to fill in.',

        'help.support_subject':
            'Subject',

        'help.support_body':
            'Your message',

        'help.support_body_help':
            'If a message carried a code beginning PM-, include it.',

        'help.support_send':
            'Send to support',

        'help.support_sent':
            'Your message has been sent.',

        'help.support_sending':
            'Sending…',

        'help.support_incomplete':
            'Write a subject and a message before sending.',

        'help.guide_intro':
            'Every task in Patrimoine, step by step, with pictures of the screens you will be looking at. Choose the part of the work you are doing.',

        'help.guide_back':
            'All guides',

        'help.guide_task_count':
            '{count} tasks',

        'help.guide_task_count_one':
            '1 task',

        'help.guide_on_this_page':
            'On this page',

        'help.guide_who':
            'Who can do this',

        'help.guide_then':
            'Then',

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
            'Posez-nous une question, lisez comment une tâche se fait, ou recherchez un code.',

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
            'Essayez moins de mots, ou le nom de l’écran.',

        /* ---- V1.0.36 ---- */

        'help.tab_support':
            'Contacter le support',

        'help.support_intro':
            'Dites-nous ce que vous tentiez de faire et ce qui s’est produit à la place. Votre nom, votre organisation et l’adresse à laquelle répondre proviennent de votre compte : il n’y a rien d’autre à remplir.',

        'help.support_subject':
            'Objet',

        'help.support_body':
            'Votre message',

        'help.support_body_help':
            'Si un message portait un code commençant par PM-, indiquez-le.',

        'help.support_send':
            'Envoyer au support',

        'help.support_sent':
            'Votre message a été envoyé.',

        'help.support_sending':
            'Envoi…',

        'help.support_incomplete':
            'Saisissez un objet et un message avant d’envoyer.',

        'help.guide_intro':
            'Chaque tâche de Patrimoine, étape par étape, avec les images des écrans que vous aurez sous les yeux. Choisissez la partie du travail qui vous occupe.',

        'help.guide_back':
            'Tous les guides',

        'help.guide_task_count':
            '{count} tâches',

        'help.guide_task_count_one':
            '1 tâche',

        'help.guide_on_this_page':
            'Sur cette page',

        'help.guide_who':
            'Qui peut le faire',

        'help.guide_then':
            'Ensuite',

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
    initializeSupportForm();

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
    { tab: 'support', button: 'help-tab-support', panel: 'help-support-panel', hash: '' },
    { tab: 'guide', button: 'help-tab-guide', panel: 'help-guide-panel', hash: '#guide' },
    { tab: 'errors', button: 'help-tab-errors', panel: 'help-errors-panel', hash: '#errors' },
    { tab: 'updates', button: 'help-tab-updates', panel: 'help-updates-panel', hash: '#updates' },
];

function applyHelpLocationHash() {
    const match = HELP_TABS.find(
        (entry) => entry.hash !== '' && entry.hash === window.location.hash
    );

    selectHelpTab(match ? match.tab : 'support');
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

        renderGuideIndex();
    } catch (error) {
        guide = null;

        const container =
            document.getElementById('help-guide-index-content');

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

/*
|--------------------------------------------------------------------------
| The guide: an index, and one guide at a time
|--------------------------------------------------------------------------
|
| V1.0.36. Every category used to be rendered into the page at once, so
| reading one task meant loading seventy of them and their screenshots.
| The index now lists the categories; choosing one opens it on its own,
| exactly as the public documentation at patrimoine365.com does.
|
| Searching still reaches every word of every task — it simply lists the
| tasks that match instead of filtering a page that was already there.
|
*/

function renderHelpGuide() {
    renderGuideIndex();
}

function renderGuideIndex() {
    const container =
        document.getElementById('help-guide-index-content');

    if (! container || guide === null) {
        return;
    }

    const query = normalizeHelpText(
        document.getElementById('help-search')?.value || ''
    ).trim();

    container.innerHTML = query === ''
        ? guideCategoryCards()
        : guideSearchResults(query);

    container
        .querySelectorAll('[data-guide-open]')
        .forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();

                openGuideCategory(
                    element.dataset.guideOpen,
                    element.dataset.guideTask || null
                );
            });
        });
}

function guideCategoryCards() {
    return `
        <div class="grid gap-4 sm:grid-cols-2">
            ${guide.categories.map(guideCategoryCard).join('')}
        </div>
    `;
}

function guideCategoryCard(category) {
    const count = category.tasks.length;

    const label = count === 1
        ? translate('help.guide_task_count_one')
        : translate('help.guide_task_count').replace('{count}', String(count));

    return `
        <button
            type="button"
            data-guide-open="${escapeHtml(category.id)}"
            class="pm-card p-5 text-left transition hover:border-[var(--pm-accent)]"
        >
            <span class="block text-base font-semibold text-[var(--pm-text)]">
                ${escapeHtml(category.title)}
            </span>

            <span class="mt-2 block text-sm leading-6 text-[var(--pm-text-muted)]">
                ${escapeHtml(category.summary)}
            </span>

            <span class="mt-4 block text-xs font-semibold uppercase tracking-wide text-[var(--pm-text-subtle)]">
                ${escapeHtml(label)}
            </span>
        </button>
    `;
}

function guideSearchResults(query) {
    const matches = guide.categories
        .map((category) => ({
            ...category,
            tasks: category.tasks.filter((task) => guideTaskMatches(task, query)),
        }))
        .filter((category) => category.tasks.length > 0);

    if (matches.length === 0) {
        return `
            <div class="pm-card px-6 py-14 text-center">
                <p class="text-sm font-semibold text-[var(--pm-text)]">
                    ${escapeHtml(translate('help.no_results'))}
                </p>

                <p class="mt-2 text-sm text-[var(--pm-text-muted)]">
                    ${escapeHtml(translate('help.no_results_description'))}
                </p>
            </div>
        `;
    }

    return `
        <div class="grid gap-8">
            ${matches.map((category) => `
                <section>
                    <h2 class="border-b border-[var(--pm-border)] pb-2 text-sm font-semibold uppercase tracking-[0.14em] text-[var(--pm-text-muted)]">
                        ${escapeHtml(category.title)}
                    </h2>

                    <ul class="mt-4 grid gap-3">
                        ${category.tasks.map((task) => `
                            <li>
                                <button
                                    type="button"
                                    data-guide-open="${escapeHtml(category.id)}"
                                    data-guide-task="${escapeHtml(task.id)}"
                                    class="text-left text-sm font-medium text-[var(--pm-accent)] hover:underline"
                                >${escapeHtml(task.title)}</button>

                                <span class="ml-2 text-sm text-[var(--pm-text-muted)]">
                                    ${escapeHtml(task.intro)}
                                </span>
                            </li>
                        `).join('')}
                    </ul>
                </section>
            `).join('')}
        </div>
    `;
}

/**
 * Open one guide on its own.
 */
function openGuideCategory(categoryId, taskId) {
    const category = guide?.categories.find((entry) => entry.id === categoryId);

    const index = document.getElementById('help-guide-index');
    const detail = document.getElementById('help-guide-detail');

    if (! category || ! index || ! detail) {
        return;
    }

    detail.innerHTML = guideDetailMarkup(category);

    index.classList.add('hidden');
    detail.classList.remove('hidden');

    detail
        .querySelector('[data-guide-back]')
        ?.addEventListener('click', (event) => {
            event.preventDefault();
            closeGuideCategory();
        });

    sizeGuideShots(detail);

    const target = taskId
        ? detail.querySelector('#guide-task-' + CSS.escape(taskId))
        : null;

    (target ?? detail).scrollIntoView({ block: 'start' });
}

function closeGuideCategory() {
    const index = document.getElementById('help-guide-index');
    const detail = document.getElementById('help-guide-detail');

    if (! index || ! detail) {
        return;
    }

    detail.classList.add('hidden');
    detail.innerHTML = '';
    index.classList.remove('hidden');
    index.scrollIntoView({ block: 'start' });
}

function guideDetailMarkup(category) {
    return `
        <button
            type="button"
            data-guide-back
            class="text-sm font-semibold text-[var(--pm-accent)] hover:underline"
        >&larr; ${escapeHtml(translate('help.guide_back'))}</button>

        <h2 class="mt-3 text-xl font-semibold tracking-tight text-[var(--pm-text)]">
            ${escapeHtml(category.title)}
        </h2>

        <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--pm-text-muted)]">
            ${escapeHtml(category.summary)}
        </p>

        <nav class="mt-5 rounded-xl border border-[var(--pm-border)] bg-[var(--pm-surface-subtle)] p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--pm-text-subtle)]">
                ${escapeHtml(translate('help.guide_on_this_page'))}
            </p>

            <ul class="mt-2 grid gap-1.5">
                ${category.tasks.map((task) => `
                    <li>
                        <a
                            href="#guide-task-${escapeHtml(task.id)}"
                            class="text-sm text-[var(--pm-text-muted)] hover:text-[var(--pm-accent)]"
                        >${escapeHtml(task.title)}</a>
                    </li>
                `).join('')}
            </ul>
        </nav>

        <div class="mt-6 grid grid-cols-1 gap-4">
            ${category.tasks.map(guideTaskCard).join('')}
        </div>
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
            class="pm-card scroll-mt-32 p-5"
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
 * The picture is lazy: a guide can carry a dozen of them and nobody
 * scrolls through all of it at once.
 */
function guideStep(step) {
    const shot = step.shot
        ? `
            <figure class="pm-guide-figure mt-3 overflow-hidden rounded-xl border border-[var(--pm-border)]">
                <img
                    src="${escapeHtml(guide.shots)}/${escapeHtml(step.shot)}.webp"
                    alt="${escapeHtml(step.text)}"
                    loading="lazy"
                    decoding="async"
                    class="pm-guide-shot block w-full"
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

/**
 * A screenshot of a drawer is 896 by 1800; one of a whole page is 1440
 * by 900. Given the same width both render at their own proportions, so
 * the drawer arrives three times taller than the page beside it and
 * dwarfs the words it illustrates.
 *
 * A picture taller than it is wide is therefore boxed at the proportions
 * of a full-page shot and fitted by height, which makes every screenshot
 * in the guide about the same height whatever shape it is. The
 * measurement is taken from the file rather than guessed, so a shot
 * recaptured at some other size still lands in the right box.
 */
function sizeGuideShots(root) {
    root.querySelectorAll('img.pm-guide-shot').forEach((image) => {
        const measure = () => {
            if (image.naturalHeight > image.naturalWidth) {
                image
                    .closest('figure')
                    ?.classList.add('pm-guide-figure-tall');
            }
        };

        if (image.complete && image.naturalWidth > 0) {
            measure();

            return;
        }

        image.addEventListener('load', measure, { once: true });
    });
}

/*
|--------------------------------------------------------------------------
| Writing to support
|--------------------------------------------------------------------------
|
| V1.0.36. Only a subject and a message: who is writing, which
| organisation they belong to and where the answer should go are read
| from the session, because an address typed into a form is one anybody
| could put somebody else's name against.
|
*/

function initializeSupportForm() {
    const form = document.getElementById('help-support-form');

    if (! form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const subject = document.getElementById('help-support-subject');
        const body = document.getElementById('help-support-body');
        const button = document.getElementById('help-support-submit');

        const subjectValue = (subject?.value ?? '').trim();
        const bodyValue = (body?.value ?? '').trim();

        if (subjectValue === '' || bodyValue === '') {
            showSupportMessage(translate('help.support_incomplete'), false);

            return;
        }

        setButtonBusy(button, 'help.support_sending');

        try {
            const response = await apiRequest(
                '/api/support-messages',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        subject: subjectValue,
                        message: bodyValue,
                    }),
                }
            );

            const payload = await parseJsonResponse(response);

            showSupportMessage(
                payload.message ?? translate('help.support_sent'),
                true
            );

            if (subject) {
                subject.value = '';
            }

            if (body) {
                body.value = '';
            }
        } catch (error) {
            showSupportMessage(
                error instanceof Error
                    ? error.message
                    : translate('core.request_failed'),
                false
            );
        } finally {
            restoreButton(button);
        }
    });
}

function showSupportMessage(text, success) {
    const box = document.getElementById('help-support-message');

    if (! box) {
        return;
    }

    box.textContent = text;

    box.className = success
        ? 'rounded-lg border px-4 py-3 text-sm border-[var(--pm-success-border)] bg-[var(--pm-success-background)] text-[var(--pm-success-text)]'
        : 'rounded-lg border px-4 py-3 text-sm border-[var(--pm-danger-border)] bg-[var(--pm-danger-background)] text-[var(--pm-danger-text)]';
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
