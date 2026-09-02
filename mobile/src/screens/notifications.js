/*
 * The bell - GET /notifications rendered as the browser's panel: one row
 * per kind, a dot when something is unread or in danger, and the release
 * row that opens "What's new" and marks the release as read.
 */

import { el, mount } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { money } from '../ui/money.js';
import * as store from '../data/store.js';

const LINKS = {
    rent_overdue: 'dashboard', rent_due_soon: 'dashboard', expenses_unpaid: 'tenants', payments_unclassified: 'tenants',
    owner_bills_unpaid: 'owners', leases_expiring: 'leases', increments_upcoming: 'leases',
};

export function unreadOf(payload) {
    const list = payload?.notifications ?? [];

    return list.some((n) => (n.kind === 'release_notes' && n.unread === true) || n.severity === 'danger');
}

function body(notification) {
    const count = Number(notification.count ?? 0);
    const key = `ui.notifications.${notification.kind}_body${count === 1 ? '_one' : ''}`;

    return t(key, { count, amount: notification.amount !== undefined ? money(notification.amount) : '' });
}

/**
 * @param {object} options
 * @param {(section: string) => void} options.onOpenSection
 * @param {() => void} options.onOpenUpdates
 * @param {(unread: boolean) => void} options.onUnread
 */
export function notificationsPanel(client, { onOpenSection, onOpenUpdates, onUnread, onClose }) {
    const list = el('div');
    const releaseBlock = el('div', { class: 'menu-section', hidden: true });
    const panel = el('div', { class: 'popover' }, [
        el('div', { class: 'popover-head', text: t('ui.shell.notifications') }),
        list,
        releaseBlock,
    ]);

    async function load() {
        mount(list, el('p', { class: 'muted-small', style: 'padding:0.75rem 1rem', text: t('ui.notifications.loading') }));

        try {
            const payload = await client.get(endpoints.notifications);
            const notifications = payload?.notifications ?? [];

            store.read('notifications');
            onUnread?.(unreadOf(payload));

            mount(list, notifications.length === 0
                ? el('p', { class: 'muted-small', style: 'padding:0.75rem 1rem', text: t('ui.notifications.empty') })
                : el('div', {}, notifications.map((n) => el('button', {
                    class: 'notice-row', type: 'button',
                    onclick: async () => {
                        if (n.kind === 'release_notes') {
                            releaseBlock.hidden = ! releaseBlock.hidden;

                            if (n.unread) {
                                try {
                                    await client.post('/auth/release-notification/read', {});
                                    n.unread = false;
                                    onUnread?.(unreadOf({ notifications }));
                                } catch {
                                    /* Left unread: it will be tried again next time. */
                                }
                            }

                            return;
                        }

                        onClose?.();
                        onOpenSection?.(LINKS[n.kind] ?? 'dashboard');
                    },
                }, [
                    el('span', { class: `notice-dot${n.severity === 'danger' ? ' is-danger' : n.severity === 'warning' ? ' is-warning' : ''}` }),
                    el('span', { class: 'grow' }, [
                        el('span', { class: 'notice-title', text: n.kind === 'release_notes' ? t('ui.notifications.release_notes_title', { release: n.release ?? '' }) : t(`ui.notifications.${n.kind}_title`) }),
                        el('span', { class: 'notice-body', text: n.kind === 'release_notes' ? t('ui.notifications.release_notes_body') : body(n) }),
                    ]),
                    n.kind === 'release_notes' ? icon('chevron-down', { size: 16 }) : icon('chevron-right', { size: 16 }),
                ]))));

            mount(releaseBlock,
                el('span', { class: 'cell-strong', style: 'display:block', text: t('ui.shell.whats_new') }),
                el('p', { class: 'muted-small', text: t('ui.release.summary_line') }),
                el('button', { class: 'link', type: 'button', text: t('ui.release.view_details'), onclick: () => { onClose?.(); onOpenUpdates?.(); } })
            );
        } catch {
            mount(list, el('p', { class: 'muted-small', style: 'padding:0.75rem 1rem', text: t('ui.notifications.unable_load') }));
        }
    }

    load();

    return { node: panel, reload: load };
}
