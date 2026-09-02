import { fileURLToPath } from 'node:url';

import { defineConfig } from 'vite';

/*
 * The oldest supported handset runs iOS/iPadOS 15.8.8, whose WebKit is
 * roughly Safari 15.6. Vite's default target is `baseline-widely-available`
 * (Safari 16), which compiles to syntax those devices cannot parse — and it
 * fails at runtime on the device, not at build time here. The target below
 * is therefore load-bearing, not a preference.
 *
 * The same floor rules out, in CSS: container queries, native nesting and
 * subgrid (all Safari 16+). `:has()`, `@layer`, dialog and `inert` are fine.
 */
export default defineConfig({
    /*
     * Anchored to this file rather than to the working directory, so the
     * dev server and the build behave identically whether they are started
     * from mobile/ or from the repository root.
     */
    root: fileURLToPath(new URL('.', import.meta.url)),
    build: {
        outDir: 'dist',
        target: ['es2020', 'safari15'],
        cssTarget: 'safari15',
        sourcemap: true,
    },
    server: {
        host: true,
        port: 5180,
    },
});
