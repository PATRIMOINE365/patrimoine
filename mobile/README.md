# Patrimoine 365 — the mobile application

A Capacitor shell around a plain-JavaScript client that talks to this
repository's own `/api/v1`. It is not a WebView of the browser application
and it never will be: it is a separate client against the same contract,
which is written down in [`../docs/MOBILE-CONTRACT.md`](../docs/MOBILE-CONTRACT.md).
Read that first — it is the authority, and everything below assumes it.

## The support floor

**iOS and iPadOS 15.8.8 and above.** That single number decides three
things, and each of them fails quietly rather than loudly if it is
forgotten:

| | |
| --- | --- |
| Capacitor | **8.x** — its iOS deployment target is 15.0. Capacitor 7 is 14.0 and 6 is 13.0, so the current major is the right one; there is no need to go backwards. |
| Vite `build.target` | **`safari15`**, set in `vite.config.js`. Vite's own default is `baseline-widely-available`, which means Safari 16 — left alone it emits syntax a 15.8 device cannot parse, and it fails on the handset, not in the build. |
| CSS | No container queries, no native nesting, no subgrid (Safari 16+). `:has()`, `@layer`, `dialog` and `inert` are all fine on 15.4+. |

Xcode 26's iOS SDK accepts deployment targets down to 12.0, so the toolchain
is not the constraint — Capacitor is.

## Running it

```bash
cd mobile
npm install
cp .env.example .env.local     # then set VITE_API_BASE
npm run dev                    # browser, for layout work
npm test                       # the contract rules, no device needed
```

There is deliberately **no default for `VITE_API_BASE`**. A build that does
not know which server it talks to fails on the first screen rather than
silently pointing at the wrong one.

| Environment | `VITE_API_BASE` |
| --- | --- |
| Pre-production | `https://patrimoine.koaditech.com/api/v1` |
| Production | `https://app.patrimoine365.com/api/v1` |

CORS is already correct for both: the framework default covers `api/*` with
a wildcard origin and no credentials, which is exactly the shape bearer-token
auth needs. Verified live against production and pre-production with
`Origin: capacitor://localhost`.

## Building for a device

iOS builds happen on the iMac, never here — Windows cannot produce them.

```bash
npm run ios      # build, cap sync ios, open Xcode
```

## What is in place

- **The API client** (`src/api/client.js`) — the only place `fetch` is
  called. It enforces the three rules that cannot be enforced screen by
  screen: a JSON body on every POST/PUT/PATCH/DELETE, the client-identity
  headers that fix the token's 60-day/180-day lifetime at mint time, and
  the language header. Covered by `tests/api-client.test.js`.
- **The launch gate** (`src/boot/config.js`) — forced update and
  maintenance, read from `GET /config` before the first screen. This has to
  exist in build one: a floor can only be enforced by a client that already
  shipped with the code to enforce it.
- **Sign-in** (`src/screens/signin.js`) — email, password, six-digit code.
  Sign-up and password reset leave for the browser.
- **The shell** (`src/screens/app-shell.js`) — the settled phone tab bar,
  Properties · Parties · Leases · Finance · More, against live endpoints.

## What is not, and is somebody's decision rather than work remaining

- **Secure storage is not chosen.** `src/auth/session.js` puts the token in
  `@capacitor/preferences`, which on iOS is UserDefaults: a plain plist,
  not encrypted at rest beyond the device's own file protection, and
  included in unencrypted backups. For a token with a 180-day ceiling that
  is a weak home. The file is written so the backend is one interface and
  one line; it must be swapped for a Keychain-backed plugin before release.
- **Biometric unlock** is approved for V1 but not built. When it is: it
  unlocks a token already stored. It must never mint a server token, never
  bypass MFA for a new device, and never be an authentication method on its
  own.
- **Push** — V1 registers devices only, and the server side (`push_devices`,
  one row per app *installation*, never per user) is not built yet.
- **The tablet layout** — a real Dashboard and the web sidebar mirrored
  entry for entry. The phone shell only avoids looking stretched on an iPad.
- **The responsive layout generally** — the bulk of the work, and none of
  it is a contract change.
