/*
 * The gates before something irreversible - the browser application's
 * own, in the same words.
 *
 *  - dangerConfirmation: the modal every permanent delete ends with. A
 *    tick-box acknowledging the loss and the person's password, which is
 *    checked against POST /auth/confirm-password before the delete is sent.
 *  - archiveRecord / restoreRecord: the shared Archive and Restore drawers.
 *    Both ask why, because the next person to look for the record deserves
 *    the answer.
 */

import { openSheet, confirmSheet } from './sheet.js';
import { t } from '../i18n/index.js';
import { ApiError } from '../api/errors.js';

export { confirmSheet };

/**
 * @returns {Promise<boolean>} true only once the password has been verified
 */
export async function dangerConfirmation(client, { entityLabel }) {
    const verified = await openSheet({
        title: t('ui.danger.title'),
        description: entityLabel ? `${t('ui.danger.entity_prefix')} ${entityLabel}` : t('ui.danger.entity_generic'),
        submitLabel: t('ui.danger.confirm'),
        submitKind: 'danger',
        cancelLabel: t('ui.danger.cancel'),
        fields: [
            { name: 'acknowledged', type: 'toggle', label: t('ui.danger.acknowledgement'), value: false },
            { name: 'password', type: 'password', label: t('ui.danger.password_label'), autocomplete: 'current-password', required: true },
        ],
        submitDisabled: true,
        onChange: (values, api) => {
            api.setSubmitDisabled(! (values.acknowledged === true && String(values.password ?? '').length > 0));
        },
        onSubmit: async (values) => {
            try {
                await client.post('/auth/confirm-password', { password: values.password });
            } catch (failure) {
                throw new ApiError(failure?.message && failure.status !== 422 ? failure.message : t('ui.danger.verification_failed'), { status: failure?.status, code: failure?.code });
            }

            return true;
        },
    });

    return verified === true;
}

/**
 * Archive a party, building, unit or lease. POST /archive/{kind}/{id}.
 * @returns {Promise<boolean>} whether it was archived
 */
export async function archiveRecord(client, { kind, id, label }) {
    const done = await openSheet({
        title: t('ui.archive.drawer_title'),
        description: label,
        submitLabel: t('ui.archive.archive'),
        submitKind: 'danger',
        fields: [
            { name: 'h', type: 'heading', label: t('ui.archive.what_happens') },
            { name: 'n1', type: 'note', label: `• ${t('ui.archive.effect_lists')}` },
            { name: 'n2', type: 'note', label: `• ${t('ui.archive.effect_pickers')}` },
            { name: 'n3', type: 'note', label: `• ${t('ui.archive.effect_records')}` },
            { name: 'n4', type: 'note', label: `• ${t('ui.archive.effect_reversible')}` },
            {
                name: 'reason', type: 'textarea', rows: 3, maxlength: 500, required: true,
                label: t('ui.archive.reason'), placeholder: t('ui.archive.reason_placeholder'), hint: t('ui.archive.reason_help'),
            },
        ],
        validate: (values) => (values.reason === '' ? { reason: t('ui.archive.reason') } : null),
        onSubmit: async (values) => {
            await client.post(`/archive/${kind}/${id}`, { reason: values.reason });
        },
    });

    return done === true;
}

/**
 * Restore an archived record. DELETE /archive/{kind}/{id}.
 */
export async function restoreRecord(client, { kind, id, label, reason }) {
    const done = await openSheet({
        title: t('ui.archive.restore_title'),
        description: label,
        submitLabel: t('ui.archive.restore'),
        fields: [
            { name: 'h', type: 'heading', label: t('ui.archive.what_happens') },
            { name: 'n1', type: 'note', label: `• ${t('ui.archive.restore_effect_lists')}` },
            { name: 'n2', type: 'note', label: `• ${t('ui.archive.restore_effect_pickers')}` },
            { name: 'n3', type: 'note', label: `• ${t('ui.archive.restore_effect_reason')}` },
            ...(reason ? [{ name: 'original', type: 'readonly', label: t('ui.archive.original_reason'), value: reason }] : []),
            {
                name: 'reason', type: 'textarea', rows: 3, maxlength: 500, required: true,
                label: t('ui.archive.restore_reason'), placeholder: t('ui.archive.restore_reason_placeholder'), hint: t('ui.archive.restore_reason_help'),
            },
        ],
        validate: (values) => (values.reason === '' ? { reason: t('ui.archive.restore_reason') } : null),
        onSubmit: async (values) => {
            await client.delete(`/archive/${kind}/${id}`, { reason: values.reason });
        },
    });

    return done === true;
}
