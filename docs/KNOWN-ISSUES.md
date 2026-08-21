# Known issues / deferred work

Recorded 2026-08-21 after a full codebase review. Ordered by priority. Tick items off as they are resolved.

## High

- [ ] **Repository is public on GitHub.** A business financial application with full commit
  history is exposed at github.com/kanippah/patrimoine. Decide whether this is intentional;
  if not, flip to private in repo Settings → Danger Zone.
- [x] **Rent increments have no HTTP surface.** ~~Today that requires tinker.~~ Resolved in
  v1.0.6: `GET/POST /api/leases/{lease}/rent-increments` and
  `POST /api/rent-increments/{id}/cancel` (RentIncrementController, RentIncrementApiTest).
  Applying stays scheduler-only by design. A dedicated lease-drawer UI section for the
  increment history remains a possible follow-up (scheduling is already reachable through
  the lease-extension drawer).

## Medium

- [x] **Repo hygiene: committed backup artifacts.** Resolved in v1.0.6: all 163 artifacts
  deleted and `.gitignore` rules added.
- [x] **Dashboard has no dark mode.** Resolved in v1.0.6: every page is token-native
  (`docs/DESIGN.md`); the shim layer in `app.css` was deleted.
- [ ] **Invoice numbering is collision-prone.** `InvoiceGenerationService::nextInvoiceNumber()`
  derives `INV-%06d` from `Invoice::max('id') + 1`. Two concurrent generations, or generation
  after any historical deletion, can violate the unique index. Move to a dedicated sequence
  (or retry-on-collision inside the existing transaction).
- [ ] **Sanctum tokens never expire** (`config/sanctum.php` → `'expiration' => null`).
  Revocation only happens on logout, password change/reset, or account disable. Consider a
  finite expiry plus token refresh in the JS shell.

## Low

- [x] **Two drawers never migrated to `<x-drawer>`:** resolved in v1.0.6 — every overlay is
  an `<x-drawer>` (`sm` or `lg`), and the orphaned change-password dialog was removed.
- [x] **Drawer open/close state machine is copy-pasted:** resolved in v1.0.6 —
  `openDrawer`/`closeDrawer`/`wireDrawer` live in `core.js`; all page modules delegate.
- [ ] **Stock boilerplate:** `README.md` is still the unmodified Laravel skeleton README
  (`welcome.blade.php` was deleted in v1.0.6). Write a real README.
- [ ] **Mail is fully synchronous.** No `ShouldQueue`, no queue worker required — but web
  requests that send invitations/receipts block on the Resend round-trip, and the daily
  reminder run is serial. If volume grows, queue the mailables (infra already supports it:
  `QUEUE_CONNECTION=database` and the jobs table exist).
- [ ] **Docs drift:** `docs/releases/V1.0.3-DEPLOYMENT.md` describes the old Plesk VPS
  workflow. Superseded by the Docker/Coolify deployment (see `Dockerfile`); mark it
  historical or delete it.
