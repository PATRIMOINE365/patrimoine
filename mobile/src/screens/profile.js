/*
 * My profile - the drawer behind the avatar: the photograph (choose,
 * reframe, remove), given names and surname, telephone, the password
 * change, the three-step e-mail change, and the download of one's own
 * data. As the browser has it, field for field.
 */

import { el, mount } from '../ui/dom.js';
import { icon } from '../ui/icon.js';
import { t } from '../i18n/index.js';
import { endpoints } from '../api/endpoints.js';
import { openSheet, informSheet } from '../ui/sheet.js';
import { button, badge } from '../ui/table.js';
import { downloadFile } from '../data/files.js';
import * as store from '../data/store.js';
import { session } from '../auth/session.js';
import { avatar, roleLabel, showError } from './common.js';
import { ApiError } from '../api/errors.js';

/* ------------------------------------------------------------ cropper */

async function fileToImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();

        img.onload = () => resolve({ img, url });
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('unreadable')); };
        img.src = url;
    });
}

/**
 * A round crop window with pinch/drag and a zoom slider. Returns the
 * framed JPEG blob and the crop description the server keeps for Reframe.
 */
async function cropSheet(source, initialCrop = null) {
    let image;

    try {
        image = await fileToImage(source);
    } catch {
        await informSheet({ title: t('ui.profile.photo_choose'), body: t('ui.profile.photo_unreadable'), tone: 'danger' });

        return null;
    }

    const SIZE = 256;
    const { img } = image;
    const base = Math.max(SIZE / img.naturalWidth, SIZE / img.naturalHeight);
    let zoom = 1;
    let x = 0;
    let y = 0;

    if (initialCrop) {
        try {
            const parsed = JSON.parse(initialCrop);

            zoom = parsed.zoom ?? 1;
            x = parsed.x ?? 0;
            y = parsed.y ?? 0;
        } catch {
            /* A crop written by another build: start centred. */
        }
    }

    const picture = el('img', { src: image.url, alt: '' });
    const frame = el('div', { class: 'cropper' }, [picture]);
    const slider = el('input', { type: 'range', min: 100, max: 400, value: String(Math.round(zoom * 100)), class: 'input' });

    function clamp() {
        const scale = base * zoom;
        const w = img.naturalWidth * scale;
        const h = img.naturalHeight * scale;

        x = Math.min(0, Math.max(SIZE - w, x));
        y = Math.min(0, Math.max(SIZE - h, y));
    }

    function paint() {
        clamp();
        const scale = base * zoom;

        picture.style.transform = `translate(${x}px, ${y}px) scale(${scale})`;
    }

    slider.addEventListener('input', () => {
        const next = Number(slider.value) / 100;
        const scaleBefore = base * zoom;
        const scaleAfter = base * next;

        /* Zoom about the centre of the window. */
        x = SIZE / 2 - ((SIZE / 2 - x) / scaleBefore) * scaleAfter;
        y = SIZE / 2 - ((SIZE / 2 - y) / scaleBefore) * scaleAfter;
        zoom = next;
        paint();
    });

    let dragging = null;

    frame.addEventListener('pointerdown', (event) => {
        dragging = { px: event.clientX, py: event.clientY, x, y };
        frame.setPointerCapture(event.pointerId);
    });
    frame.addEventListener('pointermove', (event) => {
        if (dragging === null) return;
        x = dragging.x + (event.clientX - dragging.px);
        y = dragging.y + (event.clientY - dragging.py);
        paint();
    });
    frame.addEventListener('pointerup', () => { dragging = null; });

    paint();

    const done = await openSheet({
        title: t('ui.profile.photo_choose'),
        submitLabel: t('ui.profile.photo_save'),
        cancelLabel: t('ui.profile.photo_cancel'),
        fields: [
            { name: 'frame', type: 'note', label: '' },
            { name: 'hint', type: 'note', label: t('ui.profile.photo_drag') },
            { name: 'zoom', type: 'note', label: '' },
        ],
        onChange: (values, api, changed) => {
            if (changed === null) {
                mount(api.get('frame').node, frame);
                mount(api.get('zoom').node, el('label', { class: 'field' }, [el('span', { class: 'label', text: t('ui.profile.photo_zoom') }), slider]));
            }
        },
        onSubmit: async () => {
            const canvas = document.createElement('canvas');
            const OUT = 512;

            canvas.width = OUT;
            canvas.height = OUT;

            const ctx = canvas.getContext('2d');
            const scale = base * zoom * (OUT / SIZE);

            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, OUT, OUT);
            ctx.drawImage(img, x * (OUT / SIZE), y * (OUT / SIZE), img.naturalWidth * scale, img.naturalHeight * scale);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));

            return { blob, crop: JSON.stringify({ zoom, x, y }) };
        },
    });

    URL.revokeObjectURL(image.url);

    return done || null;
}

/* ------------------------------------------------------------ profile */

export async function profileSheet(client, { onUpdated, onSignedOut } = {}) {
    let user = (await client.get(endpoints.auth.me).catch(() => store.read('me').data)) ?? {};

    user = user.user ?? user;

    const photo = el('div', { class: 'inline' });
    const fileInput = el('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,.heic,.heif', class: 'hidden-input' });

    async function upload(file, crop, source) {
        const form = new FormData();

        form.append('photo', file, 'avatar.jpg');

        if (source) {
            form.append('source', source);
            form.append('crop', crop);
        }

        const saved = await client.upload(endpoints.auth.avatar, form);

        user.avatar = saved?.avatar ?? user.avatar;
        await refreshMe();
        paintPhoto();
    }

    async function refreshMe() {
        await store.fetchKey(client, 'me', endpoints.auth.me);
        const fresh = store.read('me').data;

        user = fresh?.user ?? fresh ?? user;
        onUpdated?.(user);
    }

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];

        fileInput.value = '';

        if (! file) return;

        const framed = await cropSheet(file, null);

        if (framed) {
            try {
                await upload(framed.blob, framed.crop, file);
            } catch (failure) {
                await informSheet({ title: t('ui.profile.photo_choose'), body: `${failure?.message ?? ''}${failure?.code ? ` (${failure.code})` : ''}`, tone: 'danger' });
            }
        }
    });

    function paintPhoto() {
        mount(photo,
            avatar(user, { size: 'lg' }),
            el('span', { class: 'inline' }, [
                el('label', { class: 'button button-secondary button-compact' }, [icon('upload-01', { size: 16 }), el('span', { text: t('ui.profile.photo_choose') }), fileInput]),
                user.avatar ? button(t('ui.profile.photo_reframe'), { iconName: 'edit-02', onClick: async () => {
                    try {
                        const held = await client.get(`${endpoints.auth.avatar}/source`);

                        if (! held?.source) return;

                        const blob = await (await fetch(held.source)).blob();
                        const framed = await cropSheet(blob, held.crop ?? null);

                        if (framed) {
                            await upload(framed.blob, framed.crop, blob);
                        }
                    } catch (failure) {
                        await informSheet({ title: t('ui.profile.photo_reframe'), body: `${failure?.message ?? ''}${failure?.code ? ` (${failure.code})` : ''}`, tone: 'danger' });
                    }
                } }) : null,
                user.avatar ? button(t('ui.profile.photo_remove'), { kind: 'danger-outline', iconName: 'trash-01', onClick: async () => {
                    await client.delete(endpoints.auth.avatar);
                    user.avatar = null;
                    await refreshMe();
                    paintPhoto();
                } }) : null,
            ].filter(Boolean))
        );
    }

    paintPhoto();

    const saved = await openSheet({
        title: t('ui.shell.my_profile'),
        description: t('ui.shell.profile_description'),
        width: 'lg',
        submitLabel: t('ui.actions.save'),
        fields: [
            { name: 'photo', type: 'note', label: '' },
            { name: 'given_names', type: 'text', label: t('ui.users.given_names'), value: user.given_names ?? '', maxlength: 255 },
            { name: 'surname', type: 'text', label: t('ui.users.surname'), value: user.surname ?? '', maxlength: 255, required: true },
            { name: 'email', type: 'email', label: t('ui.users.email'), value: user.email ?? '', readonly: true },
            { name: 'email_change', type: 'note', label: '' },
            { name: 'phone', type: 'phone', label: t('ui.users.phone'), value: user.phone, country: user.phone_country },
            { name: 'role', type: 'readonly', label: t('ui.users.role'), value: roleLabel(user.role) },
            { name: 'status', type: 'readonly', label: t('ui.users.status'), value: user.is_active === false ? t('ui.users.inactive') : t('ui.users.active') },
            { name: 'h_password', type: 'heading', label: t('ui.password.section') },
            { name: 'password', type: 'password', label: t('ui.password.new_password'), hint: t('ui.password.profile_new_help'), autocomplete: 'new-password' },
            { name: 'current_password', type: 'password', label: t('ui.password.current_password'), hint: t('ui.password.profile_current_help'), autocomplete: 'current-password' },
            { name: 'h_data', type: 'heading', label: t('ui.profile.data_section') },
            { name: 'data', type: 'note', label: '' },
        ],
        onChange: (values, api, changed) => {
            if (changed !== null) return;

            mount(api.get('photo').node, photo);
            mount(api.get('email_change').node, button(t('ui.email_change.open_button'), { iconName: 'mail-01', onClick: async () => {
                const newEmail = await emailChangeSheet(client);

                if (newEmail) {
                    api.set('email', newEmail);
                    user.email = newEmail;
                    await refreshMe();
                }
            } }));
            mount(api.get('data').node, el('div', { class: 'stack' }, [
                el('p', { class: 'muted-small', text: t('ui.profile.download_data_help') }),
                button(t('ui.profile.download_data'), { iconName: 'download-01', onClick: async (node) => {
                    node.querySelector('span').textContent = t('ui.profile.downloading');

                    try {
                        await downloadFile(client, `${endpoints.auth.me}/data`, 'patrimoine-my-data.json');
                    } catch (failure) {
                        await informSheet({ title: t('ui.profile.download_data'), body: `${failure?.message ?? ''}${failure?.code ? ` (${failure.code})` : ''}`, tone: 'danger' });
                    } finally {
                        node.querySelector('span').textContent = t('ui.profile.download_data');
                    }
                } }),
            ]));
        },
        validate: (values) => {
            if (values.surname === '') {
                return { surname: t('ui.users.surname') };
            }

            if (values.password !== '' && values.current_password === '') {
                return { current_password: t('ui.password.profile_current_required') };
            }

            return null;
        },
        onSubmit: async (values) => {
            const payload = {
                given_names: values.given_names || null,
                surname: values.surname,
                phone: values.phone.number || null,
                phone_country: values.phone.country,
            };

            if (values.password !== '') {
                payload.current_password = values.current_password;
                payload.password = values.password;
                payload.password_confirmation = values.password;
            }

            await client.patch(endpoints.auth.me, payload);

            return values.password !== '' ? 'password' : true;
        },
    });

    if (saved === 'password') {
        /* A new password retires the token: back to sign-in, as the web does. */
        await session.clear();
        onSignedOut?.();

        return;
    }

    if (saved) {
        await refreshMe();
        await informSheet({ title: t('ui.shell.my_profile'), body: t('ui.password.profile_updated'), tone: 'success' });
    }
}

/* ------------------------------------------------------- email change */

export async function emailChangeSheet(client) {
    let pending = null;

    try {
        pending = (await client.get('/auth/email-change'))?.pending ?? null;
    } catch {
        pending = null;
    }

    let step = pending?.step === 'verify_proposed' ? 'verify_proposed' : pending?.step === 'verify_current' ? 'verify_current' : 'start';
    let token = pending?.token ?? null;
    let proposed = pending?.proposed_email ?? '';
    let finalEmail = null;

    while (true) {
        if (step === 'start') {
            const started = await openSheet({
                title: t('ui.email_change.title'),
                description: t('ui.email_change.description'),
                submitLabel: t('ui.email_change.start_button'),
                fields: [
                    { name: 'email', type: 'email', label: t('ui.email_change.new_email_label'), maxlength: 255, required: true },
                    { name: 'current_password', type: 'password', label: t('ui.email_change.current_password_label'), autocomplete: 'current-password', required: true },
                    { name: 'note', type: 'note', label: t('ui.email_change.keep_active_note') },
                ],
                validate: (values) => (values.email === '' || values.current_password === '' ? { _: t('ui.email_change.missing_fields') } : null),
                onSubmit: async (values) => client.post('/auth/email-change', { email: values.email, current_password: values.current_password }),
            });

            if (! started) return null;

            token = started?.change?.token ?? token;
            proposed = started?.change?.proposed_email ?? proposed;
            step = 'verify_current';
            continue;
        }

        if (step === 'verify_current' || step === 'verify_proposed') {
            const current = step === 'verify_current';

            const verified = await openSheet({
                title: t('ui.email_change.title'),
                description: current ? t('ui.email_change.current_step_note') : t('ui.email_change.new_step_note'),
                submitLabel: t('ui.email_change.verify_button'),
                cancelLabel: t('ui.email_change.cancel_button'),
                fields: [
                    { name: 'proposed', type: 'readonly', label: t('ui.email_change.proposed_label'), value: proposed },
                    { name: 'code', type: 'text', label: t('ui.email_change.code_label'), inputmode: 'numeric', maxlength: 6, autocomplete: 'one-time-code', required: true },
                    { name: 'resend', type: 'note', label: '' },
                    { name: 'expiry', type: 'note', label: t('ui.email_change.code_expiry_note') },
                ],
                onChange: (values, api, changed) => {
                    if (changed === null) {
                        mount(api.get('resend').node, button(t('ui.email_change.resend_button'), { iconName: 'send', onClick: async () => {
                            try {
                                await client.post('/auth/email-change/resend', { token });
                            } catch (failure) {
                                api.error('code', `${failure?.message ?? ''}${failure?.code ? ` (${failure.code})` : ''}`);
                            }
                        } }));
                    }
                },
                validate: (values) => (values.code === '' ? { code: t('ui.email_change.missing_code') } : null),
                onSubmit: async (values) => {
                    try {
                        return await client.post(current ? '/auth/email-change/verify-current' : '/auth/email-change/verify-new', { token, code: values.code });
                    } catch (failure) {
                        throw new ApiError(failure?.message ?? t('signin.offline'), { status: failure?.status, code: failure?.code, errors: failure?.errors });
                    }
                },
            });

            if (! verified) {
                await client.delete('/auth/email-change').catch(() => {});

                return null;
            }

            if (current) {
                step = 'verify_proposed';
                continue;
            }

            /* verify-new hands back a fresh API token: every session was rotated. */
            if (verified?.token) {
                await session.start(verified.token, session.user);
            }

            finalEmail = verified?.email ?? proposed;
            step = 'done';
            continue;
        }

        await informSheet({ title: t('ui.email_change.title'), body: t('ui.email_change.done_note'), tone: 'success' });

        return finalEmail;
    }
}
