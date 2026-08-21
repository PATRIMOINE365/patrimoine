# Known issues / deferred work

Recorded 2026-08-21 after a full codebase review. Ordered by priority. Tick items off as they are resolved.

## High

- [ ] **Repository is public on GitHub.** A business financial application with full commit
  history is exposed at github.com/kanippah/patrimoine. Decide whether this is intentional;
  if not, flip to private in repo Settings → Danger Zone.
- [ ] **Rent increments have no HTTP surface.** `RentIncrementService` (schedule / apply /
  cancel) is fully implemented and tested, and the daily commands apply and notify scheduled
  increments — but no API route or UI exists to schedule or cancel one. Today that requires
  tinker. Needs routes under `capability:manage_operations` plus a lease-drawer UI section.

## Medium

- [ ] **Repo hygiene: committed backup artifacts.** ~116 tracked `.bak*` files (mostly
  `*.bak-v104-*` CSS/JS/Blade/lang snapshots), 9 hidden checkpoint directories at repo root
  (`.v104-*`, `.profile-password-*`), and two stray empty files (`fresh`, `role`). All of it
  ships in every deploy. Delete them, and add ignore rules (`*.bak*`, checkpoint dirs) to
  `.gitignore`.
- [ ] **Dashboard has no dark mode.** `resources/views/app/dashboard.blade.php` is the only
  authenticated page still hardcoding `bg-white` / `slate-*` classes instead of the `--pm-*`
  tokens — renders as white cards on a dark page. Migrate it like the other pages (or add a
  `.pm-dashboard-page` override shim as an interim fix).
- [ ] **Invoice numbering is collision-prone.** `InvoiceGenerationService::nextInvoiceNumber()`
  derives `INV-%06d` from `Invoice::max('id') + 1`. Two concurrent generations, or generation
  after any historical deletion, can violate the unique index. Move to a dedicated sequence
  (or retry-on-collision inside the existing transaction).
- [ ] **Sanctum tokens never expire** (`config/sanctum.php` → `'expiration' => null`).
  Revocation only happens on logout, password change/reset, or account disable. Consider a
  finite expiry plus token refresh in the JS shell.

## Low

- [ ] **Two drawers never migrated to `<x-drawer>`:** `#owner-modal`
  (`resources/views/app/properties.blade.php:682`) and `#existing-unit-modal` (`:1153`) are
  hand-rolled with inconsistent z-index (80 vs 70) and backdrop styles.
- [ ] **Drawer open/close state machine is copy-pasted** across ~7 JS modules (`auth.js`,
  `leases.js`, `activity-log.js`, `properties.js`, `payments.js`, `owners.js`, `users.js`).
  Extract into `core.js`.
- [ ] **Stock boilerplate:** `README.md` is the unmodified Laravel skeleton README;
  `resources/views/welcome.blade.php` (72 KB) is unrouted starter content. Replace / delete.
- [ ] **Mail is fully synchronous.** No `ShouldQueue`, no queue worker required — but web
  requests that send invitations/receipts block on the Resend round-trip, and the daily
  reminder run is serial. If volume grows, queue the mailables (infra already supports it:
  `QUEUE_CONNECTION=database` and the jobs table exist).
- [ ] **Docs drift:** `docs/releases/V1.0.3-DEPLOYMENT.md` describes the old Plesk VPS
  workflow. Superseded by the Docker/Coolify deployment (see `Dockerfile`); mark it
  historical or delete it.
