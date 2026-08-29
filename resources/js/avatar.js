/*
|--------------------------------------------------------------------------
| Profile photographs
|--------------------------------------------------------------------------
|
| Two jobs live here.
|
| Drawing one: an avatar is the photograph if there is one and the
| person's initials on a colour of their own if there is not. The colour
| is derived from the name, so the same person is the same colour on every
| screen and in everybody else's browser — recognisable at a glance, which
| is the entire point of putting a face beside a name.
|
| Choosing one: a round window over the picture that you drag and zoom.
| Round because that is how the result is shown, so what you frame is what
| you get. What leaves the browser is the square under that circle plus
| the whole picture behind it, so the framing can be changed later without
| going to find the file again.
|
*/

import { escapeHtml, initials } from './core.js';

/**
 * Edge of the square sent for the small photo. The server re-encodes to
 * its own size; this only has to be no smaller than that.
 */
const CROP_SIZE = 512;

/**
 * Longest edge of the picture sent for keeping.
 */
const SOURCE_SIZE = 1600;

/*
|--------------------------------------------------------------------------
| Drawing one
|--------------------------------------------------------------------------
*/

/**
 * The colours an avatar without a photograph can take.
 *
 * Chosen to sit against both themes and to stay legible under white
 * lettering; deliberately not the Patrimoine green, which would read as
 * chrome rather than as a person.
 */
const PLACEHOLDER_COLOURS = [
    '#3f6e54',
    '#2f5d78',
    '#6b4f7c',
    '#8a5a3c',
    '#4a5b8c',
    '#7a4b56',
    '#3d6b6b',
    '#775f2e',
];

/**
 * A stable colour for a name.
 *
 * The same name must give the same colour everywhere and for everyone, so
 * this is a plain hash rather than anything remembered.
 */
export function placeholderColour(name) {
    const text = String(name ?? '').trim();

    if (text === '') {
        return PLACEHOLDER_COLOURS[0];
    }

    let hash = 0;

    for (const character of text) {
        hash = (hash * 31 + character.codePointAt(0)) % 100000;
    }

    return PLACEHOLDER_COLOURS[hash % PLACEHOLDER_COLOURS.length];
}

/**
 * The contents of one round avatar.
 *
 * Returns markup rather than an element so it can be dropped into a row
 * being built as a string, which is how the lists in this application are
 * rendered.
 *
 * @param {{name?: string, avatar?: ?string, size?: number, className?: string}} options
 */
export function avatarMarkup({ name = '', avatar = null, size = 32, className = '' }) {
    const label = String(name ?? '').trim();

    if (avatar) {
        return `
            <img
                src="${escapeHtml(avatar)}"
                alt=""
                class="pm-avatar ${escapeHtml(className)}"
                style="width:${size}px;height:${size}px"
                loading="lazy"
                decoding="async"
            >
        `;
    }

    return `
        <span
            class="pm-avatar pm-avatar-initials ${escapeHtml(className)}"
            style="width:${size}px;height:${size}px;background:${placeholderColour(label)};font-size:${Math.round(size * 0.4)}px"
            aria-hidden="true"
        >${escapeHtml(initials(label))}</span>
    `;
}

/**
 * Paint an existing element as an avatar.
 *
 * Used for the ones the server rendered — the button in the top bar —
 * where replacing the element would lose its handlers.
 */
export function paintAvatar(element, { name = '', avatar = null } = {}) {
    if (! element) {
        return;
    }

    const label = String(name ?? '').trim();

    element.classList.add('pm-avatar');

    if (avatar) {
        element.classList.remove('pm-avatar-initials');

        element.textContent = '';

        element.style.background = `center / cover no-repeat url("${avatar}")`;

        return;
    }

    element.classList.add('pm-avatar-initials');

    element.style.background = placeholderColour(label);

    element.textContent = initials(label);
}

/*
|--------------------------------------------------------------------------
| Choosing one
|--------------------------------------------------------------------------
*/

/**
 * Read a file the browser can draw.
 *
 * HEIC is what an iPhone produces by default, and only some browsers can
 * decode it. Where the browser cannot, saying so plainly beats a picture
 * that silently fails to appear.
 */
export async function decodeImage(file) {
    const url = URL.createObjectURL(file);

    try {
        const image = new Image();

        image.decoding = 'async';

        await new Promise((resolve, reject) => {
            image.addEventListener('load', resolve, { once: true });

            image.addEventListener(
                'error',
                () => reject(new Error('undecodable')),
                { once: true }
            );

            image.src = url;
        });

        /*
         * A browser that cannot decode HEIC reports a load with no
         * dimensions rather than an error, on some versions.
         */
        if (! image.naturalWidth || ! image.naturalHeight) {
            throw new Error('undecodable');
        }

        return image;
    } finally {
        /*
         * The bitmap is decoded by now; releasing the URL frees the file.
         */
        setTimeout(() => URL.revokeObjectURL(url), 0);
    }
}

/**
 * A round cropper over one picture.
 *
 * The element passed in is the stage: a square box the picture is drawn
 * into, with a circular window punched out of the overlay above it. The
 * picture is positioned by its centre and a zoom factor, both of which
 * survive being saved and reopened.
 */
export class AvatarCropper {
    /**
     * @param {HTMLElement} stage
     */
    constructor(stage) {
        this.stage = stage;

        this.image = null;

        /* Centre of the frame, as a fraction of the picture. */
        this.centre = { x: 0.5, y: 0.5 };

        /* 1 means the picture exactly fills the circle. */
        this.zoom = 1;

        this.dragging = null;

        this.canvas = document.createElement('canvas');

        this.canvas.className = 'pm-avatar-crop-canvas';

        this.stage.appendChild(this.canvas);

        this.bind();
    }

    /**
     * Show a picture, optionally reopening a saved framing.
     */
    load(image, framing = null) {
        this.image = image;

        this.centre = {
            x: framing?.x ?? 0.5,
            y: framing?.y ?? 0.5,
        };

        this.zoom = framing?.zoom ?? 1;

        this.draw();
    }

    /**
     * The framing, in the form that survives storage.
     */
    framing() {
        return {
            x: Number(this.centre.x.toFixed(4)),
            y: Number(this.centre.y.toFixed(4)),
            zoom: Number(this.zoom.toFixed(4)),
        };
    }

    /**
     * How many source pixels the circle covers, and where it starts.
     */
    window() {
        const { naturalWidth: width, naturalHeight: height } = this.image;

        /*
         * At zoom 1 the frame is the largest square the picture holds.
         */
        const edge = Math.min(width, height) / this.zoom;

        const half = edge / 2;

        /*
         * The centre cannot wander so far that the frame leaves the
         * picture — there is nothing out there to show.
         */
        const x = Math.min(
            Math.max(this.centre.x * width, half),
            width - half
        );

        const y = Math.min(
            Math.max(this.centre.y * height, half),
            height - half
        );

        this.centre = { x: x / width, y: y / height };

        return { left: x - half, top: y - half, edge };
    }

    draw() {
        if (! this.image) {
            return;
        }

        const size = this.stage.clientWidth || 320;

        const ratio = Math.min(window.devicePixelRatio || 1, 2);

        this.canvas.width = size * ratio;
        this.canvas.height = size * ratio;

        this.canvas.style.width = `${size}px`;
        this.canvas.style.height = `${size}px`;

        const context = this.canvas.getContext('2d');

        context.clearRect(0, 0, this.canvas.width, this.canvas.height);

        const { left, top, edge } = this.window();

        context.drawImage(
            this.image,
            left,
            top,
            edge,
            edge,
            0,
            0,
            this.canvas.width,
            this.canvas.height
        );
    }

    /**
     * The square under the circle, as a file ready to upload.
     */
    async crop() {
        const canvas = document.createElement('canvas');

        canvas.width = CROP_SIZE;
        canvas.height = CROP_SIZE;

        const context = canvas.getContext('2d');

        context.fillStyle = '#ffffff';

        context.fillRect(0, 0, CROP_SIZE, CROP_SIZE);

        const { left, top, edge } = this.window();

        context.drawImage(
            this.image,
            left,
            top,
            edge,
            edge,
            0,
            0,
            CROP_SIZE,
            CROP_SIZE
        );

        return toFile(canvas, 'photo.jpg');
    }

    /**
     * The whole picture, shrunk for keeping.
     */
    async source() {
        const { naturalWidth: width, naturalHeight: height } = this.image;

        const scale = Math.min(1, SOURCE_SIZE / Math.max(width, height));

        const canvas = document.createElement('canvas');

        canvas.width = Math.max(1, Math.round(width * scale));
        canvas.height = Math.max(1, Math.round(height * scale));

        const context = canvas.getContext('2d');

        context.fillStyle = '#ffffff';

        context.fillRect(0, 0, canvas.width, canvas.height);

        context.drawImage(this.image, 0, 0, canvas.width, canvas.height);

        return toFile(canvas, 'source.jpg');
    }

    /**
     * Zoom, keeping whatever is under the middle of the circle there.
     */
    setZoom(zoom) {
        this.zoom = Math.min(Math.max(zoom, 1), 6);

        this.draw();
    }

    bind() {
        const pointerDown = (event) => {
            if (! this.image) {
                return;
            }

            this.dragging = {
                x: event.clientX,
                y: event.clientY,
                centre: { ...this.centre },
            };

            this.stage.setPointerCapture?.(event.pointerId);
        };

        const pointerMove = (event) => {
            if (! this.dragging || ! this.image) {
                return;
            }

            const size = this.stage.clientWidth || 320;

            const { edge } = this.window();

            /*
             * A pixel moved on screen is edge/size pixels of picture.
             */
            const perPixel = edge / size;

            const { naturalWidth: width, naturalHeight: height } = this.image;

            this.centre = {
                x: this.dragging.centre.x
                    - ((event.clientX - this.dragging.x) * perPixel) / width,

                y: this.dragging.centre.y
                    - ((event.clientY - this.dragging.y) * perPixel) / height,
            };

            this.draw();
        };

        const pointerUp = (event) => {
            this.dragging = null;

            this.stage.releasePointerCapture?.(event.pointerId);
        };

        this.stage.addEventListener('pointerdown', pointerDown);
        this.stage.addEventListener('pointermove', pointerMove);
        this.stage.addEventListener('pointerup', pointerUp);
        this.stage.addEventListener('pointercancel', pointerUp);

        this.stage.addEventListener(
            'wheel',
            (event) => {
                if (! this.image) {
                    return;
                }

                event.preventDefault();

                this.setZoom(this.zoom * (event.deltaY < 0 ? 1.1 : 1 / 1.1));
            },
            { passive: false }
        );
    }
}

/**
 * A canvas as a JPEG file.
 */
function toFile(canvas, name) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => {
                if (! blob) {
                    reject(new Error('encode-failed'));

                    return;
                }

                resolve(new File([blob], name, { type: 'image/jpeg' }));
            },
            'image/jpeg',
            0.9
        );
    });
}
