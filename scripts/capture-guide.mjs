/*
 * Capture the screenshots the guide is built from.
 *
 * The guide shows people the actual application, so the pictures in it have
 * to BE the actual application. Taking them by hand does not survive
 * contact with a redesign: v1.0.30 alone moved the Manage group, v1.0.31
 * changed the top bar and v1.0.32 rebuilt Settings. Anything hand-shot
 * would already be wrong three times over.
 *
 * So they are captured by a script, from the real pre-production site,
 * against the seeded demonstration organisation. When a screen changes, the
 * fix is to run this again rather than to find and retake one picture.
 *
 * It runs inside the Playwright image on Hetzner01, because this machine
 * has no browser the script can drive:
 *
 *   docker run --rm \
 *     -e GUIDE_TOKEN=... -e GUIDE_LOCALE=en \
 *     -v /root/guide:/work -w /work \
 *     mcr.microsoft.com/playwright:v1.56.0-noble \
 *     node capture-guide.mjs
 *
 * The bearer token is minted outside and passed in: signing in needs a
 * six-digit code from an inbox the container cannot read.
 *
 * Each shot names the page it lives on, what to do when it gets there, what
 * to wait for, and what to photograph. `clip` photographs one element;
 * without it the visible page is taken.
 */

import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

const BASE = process.env.GUIDE_BASE ?? 'https://patrimoine.koaditech.com';
const TOKEN = process.env.GUIDE_TOKEN ?? '';
const LOCALE = process.env.GUIDE_LOCALE ?? 'en';
const OUT = process.env.GUIDE_OUT ?? join('/work', 'shots', LOCALE);
const ONLY = (process.env.GUIDE_ONLY ?? '').split(',').filter(Boolean);

/*
 * The sign-in screens are the only ones photographed signed OUT, and the
 * only ones that need a password. It is passed in rather than written into
 * the manifest, which is committed.
 */
const DEMO_PASSWORD = process.env.GUIDE_DEMO_PASSWORD ?? '';

/* A desktop the screenshots can be read at, not a billboard. */
const VIEWPORT = { width: 1440, height: 900 };

if (TOKEN === '') {
    console.error('GUIDE_TOKEN is required.');

    process.exit(1);
}

const shots = JSON.parse(
    await (await import('node:fs/promises')).readFile(
        join(process.cwd(), 'guide-shots.json'),
        'utf8'
    )
);

/**
 * Take one photograph and write it out.
 */
async function capture(page, id, out, options = {}) {
    const target = options.clip
        ? page.locator(options.clip).first()
        : page;

    const buffer = await target.screenshot({
        type: 'png',
        animations: 'disabled',
        caret: 'hide',
        ...(options.fullPage && ! options.clip ? { fullPage: true } : {}),
    });

    await writeFile(join(out, `${id}.png`), buffer);
}

/**
 * Settle the page: fonts loaded, network quiet, animations finished.
 *
 * Drawers slide in over 200ms and the theme paints on load. Photographing
 * either half-done produces a picture nobody recognises.
 */
async function settle(page, extra = 0) {
    await page.waitForLoadState('networkidle').catch(() => {});

    await page.evaluate(() => document.fonts?.ready).catch(() => {});

    await page.waitForTimeout(450 + extra);

    /*
     * Refuse to photograph an unstyled page.
     *
     * Pre-production runs two containers side by side for a minute or two
     * after a deploy. The HTML can come from the new one, naming a bundle
     * the OLD one has never heard of, and the stylesheet then 404s — which
     * produces a perfectly valid screenshot of the application with no CSS
     * at all: blue underlined links down the left where the sidebar should
     * be. It happened, and it would have shipped into the guide.
     *
     * The application sets Inter on the body. If the computed font is
     * anything else, the stylesheet did not arrive.
     */
    const styled = await page.evaluate(
        () => getComputedStyle(document.body).fontFamily.includes('Inter')
    );

    if (! styled) {
        throw new Error(
            'stylesheet did not load - refusing to photograph an unstyled page'
        );
    }
}

async function run() {
    const browser = await chromium.launch({
        args: ['--force-color-profile=srgb', '--font-render-hinting=none'],
    });

    const context = await browser.newContext({
        viewport: VIEWPORT,
        deviceScaleFactor: 2,
        reducedMotion: 'reduce',
        colorScheme: 'light',
    });

    const signedInPage = await context.newPage();

    /*
     * The token has to be in sessionStorage before the application boots,
     * so it is planted on every navigation rather than set once. A shot
     * marked `anonymous` is taken in a context of its own, with no token
     * at all — otherwise /login would simply redirect to the dashboard.
     */
    await context.addInitScript((token) => {
        try {
            sessionStorage.setItem('patrimoine_api_token', token);
        } catch (error) {
            // A context that refuses storage will simply fail visibly.
        }
    }, TOKEN);

    const anonymous = await browser.newContext({
        viewport: VIEWPORT,
        deviceScaleFactor: 2,
        reducedMotion: 'reduce',
        colorScheme: 'light',
    });

    const anonymousPage = await anonymous.newPage();

    await mkdir(OUT, { recursive: true });

    const taken = [];
    const failed = [];

    for (const shot of shots) {
        if (ONLY.length > 0 && ! ONLY.includes(shot.id ?? shot.name)) {
            continue;
        }

        const page = shot.anonymous ? anonymousPage : signedInPage;

        try {
            await page.goto(BASE + shot.path, { waitUntil: 'domcontentloaded' });

            await settle(page);

            for (const step of shot.do ?? []) {
                if (step.click) {
                    await page.click(step.click, { timeout: 15000 });
                }

                if (step.fill) {
                    await page.fill(
                        step.fill.selector,
                        step.fill.value === 'GUIDE_DEMO_PASSWORD'
                            ? DEMO_PASSWORD
                            : step.fill.value
                    );
                }

                if (step.select) {
                    await page.selectOption(
                        step.select.selector,
                        step.select.value
                    );
                }

                if (step.evaluate) {
                    await page.evaluate(step.evaluate);
                }

                if (step.wait) {
                    await page.waitForSelector(step.wait, { timeout: 15000 });
                }

                /*
                 * An <option> is never "visible", so waiting for one the
                 * ordinary way can only time out. This waits for it to be
                 * in the document, which is the real question when a select
                 * is filled from the API.
                 */
                if (step.waitAttached) {
                    await page.waitForSelector(step.waitAttached, {
                        state: 'attached',
                        timeout: 15000,
                    });
                }

                await page.waitForTimeout(step.pause ?? 250);

                /*
                 * A sequence photographs as it goes. The lease assistant is
                 * ten pages, and each one has to be reached the way an
                 * operator reaches it rather than revealed by force.
                 */
                if (step.capture) {
                    await settle(page, step.settle ?? 0);

                    await capture(page, step.capture, OUT, step);

                    taken.push(step.capture);

                    console.log(`  ok   ${step.capture}`);
                }
            }

            if (shot.wait) {
                await page.waitForSelector(shot.wait, { timeout: 20000 });
            }

            await settle(page, shot.settle ?? 0);

            /*
             * A sequence that has already photographed everything it came
             * for needs no picture of its own.
             */
            if (shot.id !== null && shot.id !== undefined) {
                await capture(page, shot.id, OUT, shot);

                taken.push(shot.id);

                console.log(`  ok   ${shot.id}`);
            }
        } catch (error) {
            failed.push({ id: shot.id, message: String(error).split('\n')[0] });

            console.log(`  FAIL ${shot.id}: ${String(error).split('\n')[0]}`);
        }
    }

    await browser.close();

    console.log(
        `\n${LOCALE}: ${taken.length} captured, ${failed.length} failed`
    );

    await writeFile(
        join(OUT, '_result.json'),
        JSON.stringify({ locale: LOCALE, taken, failed }, null, 2)
    );

    if (failed.length > 0) {
        process.exitCode = 1;
    }
}

await run();
