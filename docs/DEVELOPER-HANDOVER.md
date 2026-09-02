# Patrimoine 365 — developer handover

Everything a new developer needs to find the code, run it, and understand
where it is deployed. Written 2026-09-02, against release **v1.0.49**.

**No passwords, tokens or keys are in this document.** Where a credential is
needed, the section says who holds it and where it lives. Ask Komla
(tech@koaditech.com) for the ones you need.

---

## 1. What the product is

A multi-tenant property, lease and accounting application for Ghana and West
Africa. One organisation manages properties, their owners, tenants, leases,
rent, held funds (rent reserve, consumable advance, security deposit), owner
payouts and expenses, with **real double-entry accounting** behind it.

| | |
| --- | --- |
| Backend | Laravel 13.8 on PHP 8.4 |
| Frontend | Blade + **plain JavaScript, no framework**, Tailwind 4 via `@tailwindcss/vite` |
| Database | MySQL 8 in both deployed environments |
| Tests | PHPUnit, SQLite in memory, ~1,480 cases |
| Languages | English and French, everywhere, including PDFs |
| Money | **Integers, whole currency units.** No minor units, no decimals. A decimal amount is a 422, not a smaller payment. |
| Mobile | Capacitor 8 + Vite 8, plain JS, in the same repository |

Two documents in `docs/` are authoritative and worth reading before writing
code: **`MOBILE-CONTRACT.md`** (the API contract the mobile client is built
against) and **`DESIGN.md`** (the design system). `KNOWN-ISSUES.md` and
`DATA-PROTECTION.md` are also current.

---

## 2. Where the code lives

Two GitHub repositories, kept in sync. Both hold the same `main`.

| Remote | URL | Role |
| --- | --- | --- |
| `origin` | `https://github.com/kanippah/patrimoine.git` | Deploy mirror. Pre-production auto-deploys from this one. |
| `upstream105` | `https://github.com/PATRIMOINE365/patrimoine.git` | The official home, on Igor's organisation. |

Every commit and **every tag goes to both**. Access is granted by Komla on
GitHub; there is no service account.

### Is the mobile app in git? Yes.

It is **not a separate repository**. It lives in the same repo under
**`mobile/`**, on `main`, pushed to both remotes above.

```
patrimoine/
  app/ config/ database/ resources/ routes/ tests/   <- the Laravel application
  docs/                                              <- contracts and runbooks
  mobile/                                            <- the iOS application
    src/                 the client (screens, ui, api, i18n, data)
    scripts/             build steps: design tokens, icons, web strings
    ios/App/             the Xcode project, versioned
    package.json  vite.config.js  capacitor.config.json
```

100 files are tracked under `mobile/`, including the Xcode project. Not
tracked, by design: `node_modules/`, `dist/`, `.env.local`,
`src/generated/` and `public/fonts/` (all produced by the build), and Xcode's
own build artefacts.

Clone either remote and the mobile app comes with it. There is nothing else
to fetch.

---

## 3. The two environments

### Pre-production — `https://patrimoine.koaditech.com`

The working environment. **Data is disposable test data**; wiping or
reseeding it is normal practice.

| | |
| --- | --- |
| Hosting | Self-hosted **Coolify 4.3.7** at `https://coolify.koaditech.com` |
| Server | `Hetzner01`, **5.161.47.69** |
| Runtime | Docker container, MySQL 8 in its own container |
| Deploys | **Automatic**, on every push to `main` of `kanippah/patrimoine`, by webhook |
| Application root in the container | `/app` |
| Coolify application uuid | `dfiygglfad8tmlfwinlacvwr` |

Find the running container by **label, not by name** — the name carries a
build number that changes on every deploy:

```bash
ssh root@5.161.47.69
for n in $(docker ps -q); do \
  docker inspect $n --format '{{json .Config.Labels}}' \
  | grep -q patrimoine.koaditech.com && echo $n; done
```

Then `docker exec <id> php artisan …` for anything you need on the box.

Two traps that have cost real time here:

- **Rollout race.** During a deploy the old and new containers run side by
  side for minutes, and a request can hit the old one. Do not trust
  "one container" as the signal. Fetch the served `app-*.js` and grep it for
  a string only the new commit contains.
- **The Docker image needs `icu-data-full`.** Without it Alpine ships four
  locales, no `fr`, and every server-formatted French date and number is
  silently wrong. It is in the Dockerfile now; do not remove it.

### Live production — `https://app.patrimoine365.com`

Real business data. **Only tagged releases go here, manually.** Never deploy
`main`.

| | |
| --- | --- |
| Hosting | **Plesk 18**, at amen.fr, on Igor's server |
| Server | **31.193.131.75** (SSH alias `patrimoine365`) |
| Application root | `/var/www/vhosts/patrimoine365.com/app.patrimoine365.com` (docroot is its `/public`) |
| Subscription user | `patrimoine365.com_dtjb6oqoog:psacln` |
| PHP | handler `plesk-php84-fpm`; binary at `/opt/plesk/php/8.4/bin/php` |
| Composer | `/usr/lib/plesk-9.0/composer.phar` |
| Database | `patrimoine_prod`, user `patrimoine_prod`; password is in the application's `.env` on the box |
| Node | **Not installed.** 1.8 GB RAM. Assets are built elsewhere and shipped. |
| Scheduler | cron under the subscription user, `schedule:run` every minute |

The same box also serves **`patrimoine365.com`** (the marketing site, a
static Next export) and **`kaba.com.gh`**, an unrelated PrestaShop that must
not be touched.

---

## 4. Releasing to production

The proven runbook, in order. Every step matters; the notes after it are
failures that have actually happened.

1. Iterate on pre-production until Komla approves it.
2. Tag. The tag must point at **exactly what pre-production runs**, and must
   carry its own release bump in `config/patrimoine.php`.
3. Push the tag to **both** remotes.
4. Build assets **locally** from the tag. Node must be **20.12 or newer**;
   if rolldown reports a missing native binding, `rm -rf node_modules && npm ci`.
5. `git archive <tag>` plus a tar of `public/build`, scp both to `/root`.
6. On the server, before anything else: `cp -a` the application directory to
   `.patrimoine-pre-<version>-<timestamp>`, and `mysqldump` to `/root`.
7. Extract the source over the application directory.
8. Delete the files removed since the last release:
   `git diff --name-status --no-renames <old> <new>` and take the `D` rows.
9. `composer install --no-dev` (`COMPOSER_ALLOW_SUPERUSER=1`).
10. Extract the assets tar.
11. `php artisan migrate --force`.
12. Clear, then rebuild, the config, route and view caches.
13. `chown -R` back to the subscription user.
14. Verify the **served** JS hash matches the local build.

- **A stale route cache hides new endpoints.** Step 12 is not optional.
- **`config:cache` and `route:cache` have silently done nothing** when
  chained inside one `sudo`. Run each artisan command in its own SSH call and
  then check the timestamps in `bootstrap/cache/`.
- **Do not use plain `--diff-filter=D`** for step 8. Rename detection prints
  the destination of a rename, so new files look like deletions.
- **A bodyless POST answers 403 on production.** The Plesk box runs a WAF that
  rejects POST, PUT, PATCH and DELETE with no body. Pre-production, behind
  Traefik, does not — so this only ever appears in production. Every client
  must send a JSON body, even `{}`. The mobile API client enforces this
  centrally and has tests for it.

---

## 5. The mobile application

### What it is

Two different clients from one codebase, chosen at launch by the **shorter
screen edge** (768pt and above is a tablet):

- **iPhone** — a read-only field tool. Five icon tabs. No writes, by decision.
- **iPad** — **the full product**. It mirrors the browser application page for
  page: the same sidebar groups, the same drawers, the same figures, the same
  wording, the same capability gates. Erasure, archiving, deletion, the lease
  assistant, all money flows, every report and export.

It is **not a WebView of the web application**. It is a separate client
against `/api/v1`, and `docs/MOBILE-CONTRACT.md` is the authority on that
contract.

### Wording is not hand-copied

`mobile/scripts/build-web-strings.mjs` runs before every dev run and build.
It reads the web application's own catalogues — `lang/{en,fr}/*.php`,
`resources/js/translations.js`, and the Help page's literal inside
`resources/js/help.js` — and compiles them into
`mobile/src/generated/web-strings.js`. The client then asks for them by
Laravel's own keys, so `t('ui.leases.terminate_lease')` resolves exactly as
the browser resolves it. Reword a string on the web and the tablet picks it
up on the next build. A test fails the build if the source asks for a key
neither catalogue has.

### The support floor decides the toolchain

**iOS and iPadOS 15.8.8 and above**, because the test iPad is an iPad mini 4.
Three consequences, each of which fails silently rather than loudly:

- **Capacitor 8** — its iOS deployment target is exactly 15.0.
- **`build.target: safari15`** in `vite.config.js`. Vite's default means
  Safari 16, and the syntax it emits fails **on the device**, not in the build.
- **No Tailwind, no container queries, no CSS nesting, no subgrid.** The
  design tokens are compiled from the product's own stylesheets by
  `scripts/build-tokens.mjs`, which computes every `color-mix()` to a literal.

### Which server it talks to

**`VITE_API_BASE` has no default, on purpose.** A build that does not know
its server fails on the first screen rather than pointing at the wrong one.
`.env.local` is not in git; copy `.env.example` and set it.

| Environment | `VITE_API_BASE` |
| --- | --- |
| Pre-production | `https://patrimoine.koaditech.com/api/v1` |
| Production | `https://app.patrimoine365.com/api/v1` |

**The current build points at pre-production**, and stays there until
somebody decides otherwise. Verified on the build machine on 2026-09-02, both
in `.env.local` and in the compiled bundle.

### Building it

Any Mac with Xcode can do this. Clone the repository, then:

```bash
cd mobile
npm install
cp .env.example .env.local     # set VITE_API_BASE
npm run dev                    # a browser, for layout work
npm test                       # 51 cases, no device needed
npm run ios                    # build, cap sync ios, open Xcode
```

Then press **Run** in Xcode. Code signing reads the login keychain, which an
SSH session cannot open, so a device install always starts from the Mac's own
screen.

### The current build machine

Igor's iMac, used because Windows cannot build iOS.

| | |
| --- | --- |
| Reached by | `ssh imac`, over Tailscale at **100.91.214.22**, user `elom` |
| macOS / Xcode | 26.6.2 arm64 / Xcode 26.6, iOS 26.5 SDK |
| Node | v24 at `~/.local/node`; PATH lives in `~/.zshenv`, which is the file a non-interactive SSH shell reads |
| Capacitor | uses **Swift Package Manager, not CocoaPods**. There is no Podfile. |

That Mac has **no GitHub credentials**, so the source is copied to it by
`imac-sync.sh` rather than cloned. That script lives in Komla's working
directory, not in the repository — a developer with their own Mac should just
clone the repo and skip it entirely.

### Signing and distribution

| | |
| --- | --- |
| Bundle identifier | `com.patrimoine365.app` |
| Team | `9Q5SF57YL6`, shown as "ELOM KUTSIENYO" — a **personal** team on apps@kalitygroup.com |
| Style | Automatic |

This is **free provisioning**, not a paid membership. Consequences: profiles
expire after seven days, each device must be plugged into the Mac to be
registered, and **push notifications and associated domains cannot be granted
at all** — so neither can be tested on these builds however the code is
written.

Enrolling in the Apple Developer Program removes all of that and opens
TestFlight, which needs no cables and no device registration. Individual
enrolment needs only an Apple ID and the fee; organisation enrolment needs a
D-U-N-S number, which is still unresolved.

---

## 6. Access a developer will need

Nothing below is in this document. Each line says who to ask.

| What | Where it lives | Ask |
| --- | --- | --- |
| GitHub access to both repositories | GitHub | Komla |
| Pre-production sign-in | the application | Komla |
| Production sign-in | the application | Komla |
| SSH to the Plesk production box | key-based, root | Komla |
| SSH to Hetzner01 / the Coolify host | key-based, root | Komla |
| Coolify web UI and its API token | the encrypted vault on Komla's machine, entry `COOLIFY` | Komla |
| Database credentials | the `.env` on each server; never in git | on the box |
| Resend (email) API key | the `.env` on each server | on the box |
| Apple signing | the personal team above | Komla / Igor |

Komla keeps every API key in a **local DPAPI-encrypted vault** on his Windows
machine, never in `.env` files on workstations, never in chat. If you need one
in a script, it is decrypted into the child process and never printed.

Two notes on the current state that a new developer should know:

- Both environments share **one Resend account**. Production deserves its own
  key and sending domain before it matters.
- Two API access tokens were exposed in earlier working transcripts and still
  need revoking.

---

## 7. Testing

```bash
php artisan test          # ~1,480 cases, SQLite in memory, no services needed
```

CI runs the full suite on GitHub Actions for every push to `main`, every tag,
and every pull request, on PHP 8.4 with `intl`, `gd` and built Vite assets so
the Blade and browser tests can render.

There is also a **QA harness** kept at `Claude Code/patrimoine-qa/`, outside
the repository. `setup-harness.php` is idempotent, runs inside the pre-prod
container, and mints API tokens for an administrator, a manager, a viewer and
a platform staff account against a dedicated organisation, **QA Harness Ltd
(org 12)**, so probes never touch org 9, whose records are the source of every
screenshot in the user guide. It prints the tokens; they are not stored.

---

## 8. Conventions worth knowing before the first pull request

- **Money is an integer in whole currency units**, in the database, in the
  API, and in every form. The mobile client's money field reduces what is
  typed to whole units as it is typed, and the reason it is careful is that
  the obvious implementation multiplies the amount by a hundred.
- **`PUT`/`PATCH` on parties, buildings, units and leases are full
  replacements**, not partial patches. The update request classes are
  identical to the store ones. Omitting a required field blanks it.
- **The API is not a `{data, meta}` envelope.** There are no API Resource
  classes. Paginated endpoints return Laravel's flat paginator.
- **Errors carry a PM-code.** Every failure answers with a code and a sentence
  already written in the requested language. Clients display what they are
  given and never paraphrase, because support starts from the code and the
  code is recovered by matching the rendered sentence.
- **A feature is not finished** until its error codes, the in-app guide and,
  if a screen changed, the guide screenshots are updated too.
- **Archive and delete are alternatives.** A record that can still be deleted
  cannot be archived, and the list rows ask the API which button to show.
