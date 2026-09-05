# Patrimoine 365 — the mobile application

A Capacitor shell around a plain-JavaScript client that talks to this
repository's own `/api/v1`. It is not a WebView of the browser application
and it never will be: it is a separate client against the same contract,
which is written down in [`../docs/MOBILE-CONTRACT.md`](../docs/MOBILE-CONTRACT.md).
Read that first — it is the authority, and everything below assumes it.

## Two clients in one store

`src/ui/device.js` chooses by the **shorter screen edge** (768pt and above is
a tablet). Rotating a device never changes the choice.

| | iPhone (`screens/app-shell.js`) | iPad (`screens/tablet-shell.js`) |
| --- | --- | --- |
| What it is | A reading tool for the field | **The full product** |
| Navigation | Five icon tabs | The web sidebar, group for group: Workspace, Finance, Administration |
| Writes | None, by decision | Everything the browser can do |

**The iPad mirrors the browser application page for page.** Every screen
below is the web page rebuilt for touch, with the same figures, filters,
tables, drawers, confirmations, documents and capability gates:

- Dashboard, Properties (buildings, ownership, units), Parties (with the
  data-protection tools Data and Erase when Settings turns them on), Leases
  (the eight-page assistant with server drafts, the whole letting, financial
  history, rent increments, extend, terminate, the termination settlement
  with security-deposit deductions, delete with its impact preview, archive)
- Tenants (six panels; Accounts and Transfer, Deposit, Withdrawal, Expense,
  Adjustment, Pay invoice, Cancel payment, every receipt and resend), Owners
  (ledger, withdrawals, transfers, expense bills; Deposit, Withdrawal with
  Withdraw all, Adjustment, Expense bill with the split preview, Pay bill,
  Cancel payment, Transfer, Accounts, Statement), Accounting, Reports (all
  nine, with subjects, periods, payment filters, stale-result guard and the
  three exports)
- Settings (Organisation with account closure, Users, License, Preferences,
  Devices, Data with backup, download-everything, dry-run restore, About),
  Audit (Activity monitor and Financial journal, filters, details, XLSX and
  CSV), Archive (search, kind chips, restore), Support (contact form, guide,
  error codes, update log)
- The top bar: organisation, date, Refresh, the bell with the release notes,
  and the avatar menu with My profile (photograph, details, password, the
  three-step e-mail change, download my data), Appearance, Support, Sign out

## One vocabulary

Every label the tablet shares with the web is **read from the web's own
catalogues at build time**: `scripts/build-web-strings.mjs` parses
`lang/{en,fr}/*.php`, `resources/js/translations.js` and the Help page's
literal in `resources/js/help.js` into `src/generated/web-strings.js`, and
`t('ui.leases.terminate_lease')` resolves exactly as the browser resolves
it. Nothing is hand-copied, so a reword on the web reaches the tablet on the
next build. The mobile-only strings live in `src/i18n/{en,fr}.js`.

`tests/i18n-usage.test.js` fails the build if the source asks for a key
neither catalogue has.

## The support floor

**iOS and iPadOS 15.8.8 and above.** That single number decides three
things, and each of them fails quietly rather than loudly if it is
forgotten:

| | |
| --- | --- |
| Capacitor | **8.x** — its iOS deployment target is 15.0. |
| Vite `build.target` | **`safari15`**, set in `vite.config.js`. Vite's own default is Safari 16 — left alone it emits syntax a 15.8 device cannot parse, and it fails on the handset, not in the build. |
| CSS | No container queries, no native nesting, no subgrid (Safari 16+). `:has()`, `@layer`, `dialog` and `inert` are all fine on 15.4+. |

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
| Pre-production | `https://patrimoine-dev.kalitygroup.com/api/v1` (internal, nx-vps-01) |
| Production | `https://app.patrimoine365.com/api/v1` |

## Building for a device

iOS builds happen on the iMac, never here — Windows cannot produce them.

```bash
../.claude/scripts/imac-sync.sh push          # source -> iMac (with resources/, lang/, public/fonts)
ssh imac 'cd ~/patrimoine/mobile && npm run build && npx cap sync ios'
```

Then press Run in Xcode: signing needs the login keychain, which an SSH
session cannot open.

## What is in place

- **The API client** (`src/api/client.js`) — the only place `fetch` is
  called. A JSON body on every POST/PUT/PATCH/DELETE, the client-identity
  headers, the language header, plus `upload()` for multipart (photograph,
  registry backup) and `download()` for the files the browser saves.
- **Documents** — PDFs open through the signed-link exchange
  (`src/data/exports.js`); CSV, XLSX and JSON are fetched with the token,
  written to the app's cache and handed to the share sheet
  (`src/data/files.js`), because a WebView has nowhere to put a download.
- **The form sheet** (`src/ui/sheet.js`) — every drawer the browser has,
  expressed as fields: text, money (whole units, always), native dates,
  selects, toggles, radios, a searchable picker, telephone with country,
  repeating lines, read-only rows, conditional fields, a review page before
  Confirm, and Laravel's 422 landing on the field it belongs to.
- **Capabilities** (`src/auth/capabilities.js`) — the browser's matrix,
  copied exactly. Presentation only; the server decides.
- **The launch gate** (`src/boot/config.js`) — forced update and
  maintenance, read from `GET /config` before the first screen.

## Not settled

- **Secure storage.** `src/auth/session.js` puts the token in
  `@capacitor/preferences` (UserDefaults). One interface, one line to swap
  for a Keychain-backed plugin before release.
- **Biometric unlock** is approved for V1 but not built.
- **Push** — V1 registers devices only, and the server side is not built.
