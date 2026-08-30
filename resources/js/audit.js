/*
|--------------------------------------------------------------------------
| Audit
|--------------------------------------------------------------------------
|
| The tab strip over the activity log and the financial journal.
|
| It does nothing but show one panel and hide the other: the two workspaces
| load themselves, through activity-log.js and financial-journal.js, exactly
| as they did when each had a page of its own.
|
| The switching is Settings' switching, down to the class names. Two
| administration pages that hold several things should not feel like two
| different products, and the alternative — a second, subtly different tab
| implementation — is how that happens.
|
| The hash is honoured on arrival, so /activity-log and /financial-journal
| can redirect to #activity and #journal and land people where they meant
| to go.
|
*/

const AUDIT_TABS = ['activity', 'journal'];

const DEFAULT_AUDIT_TAB = 'activity';

/**
 * Reveal one tab and hide the other.
 *
 * @param {string} activeTab
 */
function selectAuditTab(activeTab) {
    const activeClasses = [
        'bg-[var(--pm-surface)]',
        'text-[var(--pm-text)]',
        'shadow-sm',
    ];

    const inactiveClasses = [
        'text-[var(--pm-text-muted)]',
        'hover:text-[var(--pm-text)]',
    ];

    AUDIT_TABS.forEach((tab) => {
        const active = tab === activeTab;

        const button = document.getElementById(`audit-tab-${tab}`);

        if (button) {
            button.setAttribute(
                'aria-selected',
                active ? 'true' : 'false'
            );

            button.classList.remove(
                ...activeClasses,
                ...inactiveClasses
            );

            button.classList.add(
                ...(active ? activeClasses : inactiveClasses)
            );
        }

        document
            .getElementById(`audit-${tab}-panel`)
            ?.classList.toggle('hidden', ! active);
    });
}

/**
 * Which tab the address bar is asking for.
 *
 * @returns {string}
 */
function requestedAuditTab() {
    const requested = window.location.hash
        .replace('#', '')
        .trim()
        .toLowerCase();

    return AUDIT_TABS.includes(requested)
        ? requested
        : DEFAULT_AUDIT_TAB;
}

/**
 * Wire the Audit page, if this is it.
 */
export async function initializeAudit() {
    const page = document.getElementById('audit-page');

    if (! page) {
        return;
    }

    AUDIT_TABS.forEach((tab) => {
        document
            .getElementById(`audit-tab-${tab}`)
            ?.addEventListener('click', () => {
                selectAuditTab(tab);

                /*
                 * Replace rather than push: the tabs are one page, and a
                 * reader pressing Back expects to leave Audit, not to walk
                 * backwards through the tabs they looked at.
                 */
                window.history.replaceState(null, '', `#${tab}`);
            });
    });

    window.addEventListener('hashchange', () => {
        selectAuditTab(requestedAuditTab());
    });

    selectAuditTab(requestedAuditTab());
}
