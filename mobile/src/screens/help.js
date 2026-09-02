/*
 * Support - the browser application's Help page: contact support, the
 * guide, the error codes and the update log. Every role sees it.
 */

import { el, mount } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { openSheet } from '../ui/sheet.js';
import { pagination, pageSize, loading, emptyState, badge, button, section } from '../ui/table.js';
import { clientPage } from '../ui/table.js';
import { formatDate } from '../ui/format.js';
import { screenHead, bannerHost, showError, showSuccess, tabs } from './common.js';
import { searchField } from '../ui/search.js';

function normalise(text) {
    return String(text ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

function supportTab(client, errors, apiBase) {
    const wrap = el('div', { class: 'stack' });

    mount(wrap,
        el('p', { class: 'record-card-sub', text: t('ui.help.support_intro') }),
        button(t('ui.help.support_send'), { kind: 'primary', iconName: 'send', compact: false, onClick: async () => {
            const sent = await openSheet({
                title: t('ui.help.tab_support'),
                submitLabel: t('ui.help.support_send'),
                fields: [
                    { name: 'subject', type: 'text', label: t('ui.help.support_subject'), required: true, maxlength: 150 },
                    { name: 'message', type: 'textarea', rows: 8, label: t('ui.help.support_body'), hint: t('ui.help.support_body_help'), required: true, maxlength: 5000 },
                ],
                validate: (values) => (values.subject === '' || values.message === '' ? { _: t('ui.help.support_incomplete') } : null),
                onSubmit: async (values) => client.post('/support-messages', { subject: values.subject, message: values.message }),
            });

            if (sent) {
                showSuccess(errors, sent?.message ?? t('ui.help.support_sent'));
            }
        } })
    );

    return wrap;
}

function guideTab(client, errors, apiBase) {
    const wrap = el('div', { class: 'stack' });
    const body = el('div');
    let guide = null;
    let query = '';
    let open = null;

    const search = searchField((value) => { query = normalise(value); paintIndex(); });

    search.input.placeholder = t('ui.help.search_placeholder');

    function taskMatches(task) {
        if (query === '') {
            return true;
        }

        const haystack = normalise([task.title, task.intro, task.after, ...(task.steps ?? []).map((s) => `${s.text ?? ''} ${s.note ?? ''}`)].join(' '));

        return query.split(/\s+/).filter(Boolean).every((word) => haystack.includes(word));
    }

    function shotUrl(shot) {
        if (! shot) {
            return null;
        }

        const origin = apiBase.replace(/\/api(\/v\d+)?\/?$/, '');

        return `${origin}${guide.shots}/${shot}.webp`;
    }

    function taskCard(task) {
        return el('article', { class: 'record-card', id: `guide-task-${task.id}` }, [
            el('div', { class: 'record-card-head' }, [
                el('h3', { class: 'record-card-title', text: task.title }),
                task.who ? badge(`${t('ui.help.guide_who')}: ${task.who}`, 'info') : null,
            ]),
            task.intro ? el('p', { class: 'record-card-sub', text: task.intro }) : null,
            el('ol', { class: 'guide-steps' }, (task.steps ?? []).map((step) => el('li', {}, [
                el('span', { text: step.text ?? '' }),
                step.note ? el('p', { class: 'muted-small', text: step.note }) : null,
                step.shot ? el('img', { src: shotUrl(step.shot), alt: '', loading: 'lazy' }) : null,
            ]))),
            task.after ? el('p', { class: 'sheet-note is-info', text: `${t('ui.help.guide_then')}: ${task.after}` }) : null,
        ]);
    }

    function paintCategory(category) {
        const tasks = category.tasks ?? [];

        mount(body,
            el('button', { class: 'link', type: 'button', text: `← ${t('ui.help.guide_back')}`, onclick: () => { open = null; paintIndex(); } }),
            el('h2', { class: 'card-title', text: category.title }),
            category.summary ? el('p', { class: 'screen-sub', text: category.summary }) : null,
            el('nav', { class: 'dl-block' }, [
                el('span', { class: 'cell-strong', text: t('ui.help.guide_on_this_page') }),
                el('ul', { class: 'stack' }, tasks.map((task) => el('li', {}, [el('button', { class: 'link', type: 'button', text: task.title, onclick: () => document.getElementById(`guide-task-${task.id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' }) })]))),
            ]),
            ...tasks.map(taskCard)
        );
    }

    function paintIndex() {
        const categories = guide?.categories ?? [];

        if (open !== null && query === '') {
            paintCategory(open);

            return;
        }

        if (query !== '') {
            const groups = categories.map((category) => ({ category, tasks: (category.tasks ?? []).filter(taskMatches) })).filter((group) => group.tasks.length > 0);

            mount(body, groups.length === 0
                ? emptyState('book-open', t('ui.help.no_results'), t('ui.help.no_results_description'))
                : el('div', { class: 'stack' }, groups.map((group) => section(group.category.title, group.tasks.map((task) => el('div', {}, [
                    el('button', { class: 'link', type: 'button', text: task.title, onclick: () => { open = group.category; query = ''; search.clear(); paintCategory(group.category); document.getElementById(`guide-task-${task.id}`)?.scrollIntoView({ block: 'start' }); } }),
                    task.intro ? el('p', { class: 'muted-small', text: task.intro }) : null,
                ]))))));

            return;
        }

        mount(body, el('div', { class: 'guide-grid' }, categories.map((category) => {
            const count = (category.tasks ?? []).length;

            return el('button', { class: 'guide-card', type: 'button', onclick: () => { open = category; paintCategory(category); } }, [
                el('h3', { class: 'record-card-title', text: category.title }),
                category.summary ? el('p', { class: 'record-card-sub', text: category.summary }) : null,
                el('p', { class: 'muted-small', text: count === 1 ? t('ui.help.guide_task_count_one') : t('ui.help.guide_task_count', { count }) }),
            ]);
        })));
    }

    mount(wrap, search.node, body);
    mount(body, loading());

    client.get(endpoints.guide).then((payload) => {
        guide = payload;
        paintIndex();
    }).catch((failure) => {
        mount(body);
        showError(errors, failure);
    });

    return wrap;
}

function errorsTab(client, errors) {
    const wrap = el('div', { class: 'stack' });
    const body = el('div');
    const size = pageSize('help-error-codes');
    let catalogue = null;
    let query = '';
    let page = 1;

    const search = searchField((value) => { query = normalise(value); page = 1; paint(); });

    search.input.placeholder = t('ui.errors.search_placeholder');

    function familyName(family) {
        const found = (catalogue?.families ?? []).find((f) => String(f.family) === String(family));
        const key = `ui.errors.family_${family}`;

        return found?.name ?? (t(key) === key ? String(family) : t(key));
    }

    function paint() {
        const codes = (catalogue?.codes ?? []).filter((code) => code.hidden !== true).filter((code) => {
            if (query === '') {
                return true;
            }

            return normalise([code.code, code.title, code.what, code.fix].join(' ')).includes(query);
        });

        const { rows: slice, meta } = clientPage(codes, page, size.get());
        const families = [...new Set(slice.map((code) => code.family))];
        const contact = catalogue?.contact ?? {};

        mount(body,
            codes.length === 0
                ? emptyState('alert-circle', t('ui.help.errors_no_matches'))
                : el('div', { class: 'stack' }, families.map((family) => el('div', { class: 'stack' }, [
                    el('h3', { class: 'sheet-heading-title', text: familyName(family) }),
                    ...slice.filter((code) => code.family === family).map((code) => el('div', { class: 'record-card' }, [
                        el('div', { class: 'record-card-head' }, [
                            el('h4', { class: 'record-card-title', text: code.title }),
                            badge(code.code, 'mono'),
                        ]),
                        el('p', { class: 'record-card-sub', text: `${t('ui.errors.what_happened')}: ${code.what ?? ''}` }),
                        el('p', { class: 'record-card-sub', text: `${t('ui.errors.what_to_do')}: ${code.fix ?? ''}` }),
                        code.needs_support ? el('div', { class: 'inline' }, [
                            contact.phone ? el('a', { class: 'link', href: `tel:${contact.phone}`, text: `${t('ui.errors.contact_phone')}: ${contact.phone_display ?? contact.phone}` }) : null,
                            contact.whatsapp ? el('a', { class: 'link', href: `https://wa.me/${String(contact.whatsapp).replace(/^\+/, '')}`, target: '_blank', text: t('ui.errors.contact_whatsapp') }) : null,
                            contact.email ? el('a', { class: 'link', href: `mailto:${contact.email}`, text: `${t('ui.errors.contact_email')}: ${contact.email}` }) : null,
                        ]) : null,
                    ])),
                ]))),
            pagination(meta, size, (next) => { page = next; paint(); })
        );
    }

    mount(wrap, el('p', { class: 'record-card-sub', text: t('ui.errors.intro') }), search.node, body);
    mount(body, loading());

    client.get(endpoints.errorCodes).then((payload) => {
        catalogue = payload;
        paint();
    }).catch((failure) => {
        mount(body);
        showError(errors, failure);
    });

    return wrap;
}

function updatesTab(client, errors) {
    const wrap = el('div', { class: 'stack' });

    mount(wrap, loading(t('ui.help.updates_loading')));

    client.get('/release-log').then((payload) => {
        const entries = Array.isArray(payload?.entries) ? payload.entries : [];

        mount(wrap,
            el('p', { class: 'sheet-note is-info', text: t('ui.help.current_version', { version: payload?.current_version ?? '' }) }),
            el('div', { class: 'timeline' }, entries.map((entry) => el('div', { class: 'timeline-entry' }, [
                el('div', { class: 'inline' }, [badge(`v${entry.version}`, 'mono'), el('span', { class: 'muted-small', text: formatDate(entry.date) })]),
                el('p', { class: 'record-card-sub', text: entry.summary ?? '' }),
            ])))
        );
    }).catch((failure) => {
        mount(wrap);
        showError(errors, failure, t('ui.help.unable_load_updates'));
    });

    return wrap;
}

export function helpScreen(client, { apiBase = '', initialTab } = {}) {
    const host = el('div', { class: 'screen' });
    const errors = bannerHost();
    const body = el('div');
    let active = initialTab ?? 'support';

    function paint() {
        const render = { support: () => supportTab(client, errors, apiBase), guide: () => guideTab(client, errors, apiBase), errors: () => errorsTab(client, errors), updates: () => updatesTab(client, errors) }[active];

        mount(body,
            tabs([
                { id: 'support', label: t('ui.help.tab_support') },
                { id: 'guide', label: t('ui.help.tab_guide') },
                { id: 'errors', label: t('ui.errors.heading') },
                { id: 'updates', label: t('ui.help.tab_updates') },
            ], active, (id) => { active = id; paint(); }),
            render()
        );
    }

    mount(host,
        screenHead({ eyebrow: t('ui.help.eyebrow'), title: t('ui.help.heading'), sub: t('ui.help.description') }),
        errors,
        body
    );

    paint();

    return { node: host, reload: paint, show(tab) { active = tab; paint(); } };
}
