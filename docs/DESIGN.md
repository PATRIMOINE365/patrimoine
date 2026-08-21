# Patrimoine design system (v1.0.6)

The single reference for how Patrimoine UI is built. If markup, CSS, or JS
disagrees with this document, the code is wrong.

## Principles

1. **Simple.** One layout shell, one overlay primitive, one form vocabulary.
   New UI reuses what exists; new patterns need a reason to exist.
2. **Tokens are the only source of color.** All markup and JS-rendered HTML
   uses the `--pm-*` semantic variables (via the component classes below or
   Tailwind arbitrary values like `bg-[var(--pm-surface)]`). Raw palette
   utilities — `bg-white`, `text-slate-*`, `border-slate-*`, `bg-red-50`,
   `text-amber-700`, … — are forbidden in application markup. Changing the
   app's look means editing the two token blocks at the top of
   `resources/css/app.css`, nothing else.
3. **Dark mode by construction.** Because markup only speaks tokens, dark
   mode needs zero page-specific CSS. Page-scoped "shim" rules that repaint
   hardcoded utilities (`.pm-x-page .bg-white { … }`) are a legacy pattern —
   delete them as pages are converted; never add new ones. The same goes for
   ID-scoped drawer styling and `!important`.
4. **Bilingual always.** Every user-visible string goes through
   `lang/{en,fr}/*.php` (server render) **and** `resources/js/translations.js`
   (client language switch), wired with `data-i18n*` attributes or
   `translate()`. `data-i18n*` attributes take a **key**, never a translated
   string.
5. **Responsive always.** Mobile-first; wide content (tables) scrolls inside
   its own `overflow-x-auto` container; drawers become full-width sheets
   below 640px. Every page must be usable at 375px wide.
6. **API-first.** Blade serves static shells; every action calls the JSON
   API. No capability may exist only as a console command or only as a UI
   behavior.
7. **Declarative permissions.** Role-gated elements carry
   `data-requires-capability="…"` and are listed in
   `resources/js/permissions.js`. The server middleware is authoritative;
   the client layer is presentation only.

## Color tokens

Defined once per theme in `resources/css/app.css` (`:root` /
`html[data-theme="dark"]`):

| Group | Tokens |
|---|---|
| Surfaces | `--pm-page`, `--pm-surface`, `--pm-surface-subtle`, `--pm-surface-muted`, `--pm-surface-elevated` |
| Text | `--pm-text`, `--pm-text-secondary`, `--pm-text-muted`, `--pm-text-subtle` |
| Borders | `--pm-border`, `--pm-border-subtle`, `--pm-border-strong` |
| Interaction | `--pm-hover`, `--pm-selected`, `--pm-accent` |
| Inputs | `--pm-input-background`, `--pm-input-disabled` |
| Overlay | `--pm-overlay` |
| Status | `--pm-{success,warning,danger,info}-{background,border,text}` |
| Destructive action | `--pm-danger-solid`, `--pm-danger-solid-hover` |

## Components

Six components cover the whole application:

- **`<x-drawer>`** — the only overlay. Right-side sliding panel with exactly
  two sizes: `width="sm"` (28rem — single-record forms, read-only detail)
  and `width="lg"` (42rem — multi-section workflows). Centered dialogs,
  hand-rolled `fixed inset-0` overlays, and CSS width overrides are
  forbidden. Open/close only through `openDrawer` / `closeDrawer` /
  `wireDrawer` from `resources/js/core.js`.
- **`<x-drawer-header>`** — title, optional description, ✕ close button.
- **`<x-drawer-footer>`** — the action bar. Editing drawers:
  `.pm-button-secondary` Cancel (left), then one primary action (right) —
  `.pm-button-primary` labeled Save, or `.pm-button-danger` for destructive
  workflows. Read-only drawers: a single `.pm-button-secondary` Close.
  Footer labels use the shared keys `actions.cancel` / `actions.save` /
  `actions.close` — never per-page variants. Action buttons live in the
  footer, never inside the drawer body.
- **`.pm-button-primary` / `.pm-button-secondary` / `.pm-button-danger`** —
  the only button styles.
- **`.pm-input` + `.pm-field-label`** — every form field. No hand-rolled
  `focus:border-… focus:ring-…` input styling.
- **`.pm-card`** — every content card (`border` + `radius` + `--pm-surface`).

## Utility → token conversion map

When converting legacy markup (Blade or JS-rendered strings), apply:

| Legacy | Replacement |
|---|---|
| `bg-white` | `bg-[var(--pm-surface)]` (or `.pm-card` when it is a card) |
| `bg-slate-50`, `bg-slate-50/70` | `bg-[var(--pm-surface-subtle)]` |
| `bg-slate-100` | `bg-[var(--pm-surface-muted)]` |
| `text-slate-950`, `-900`, `-800` | `text-[var(--pm-text)]` |
| `text-slate-700`, `-600` | `text-[var(--pm-text-secondary)]` |
| `text-slate-500` | `text-[var(--pm-text-muted)]` |
| `text-slate-400`, `-300` | `text-[var(--pm-text-subtle)]` |
| `border-slate-100` | `border-[var(--pm-border-subtle)]` |
| `border-slate-200` | `border-[var(--pm-border)]` |
| `border-slate-300` | `border-[var(--pm-border-strong)]` |
| `divide-slate-200`, `ring-slate-200` | `divide-[var(--pm-border)]`, `ring-[var(--pm-border)]` |
| `bg-slate-950/30…50` (overlays) | `bg-[var(--pm-overlay)]` |
| `hover:bg-slate-50` | `hover:bg-[var(--pm-hover)]` |
| green `bg/border/text-green-*` | `var(--pm-success-*)` |
| amber `bg/border/text-amber-*` | `var(--pm-warning-*)` |
| red `bg/border/text-red-*` (informational) | `var(--pm-danger-*)` |
| red solid buttons (`bg-red-600 text-white`) | `.pm-button-danger` |
| blue `bg/border/text-blue-*` | `var(--pm-info-*)` |
| `bg-patrimoine-950` buttons | `.pm-button-primary` |
| `text-patrimoine-600` accents | `text-[var(--pm-accent)]` |

Anything genuinely brand-colored (the sidebar's `bg-patrimoine-950`, the
auth hero panel) is intentional and stays.

## Layout

- Fixed dark sidebar (`w-72`, off-canvas below `lg`), three nav groups
  (Workspace / Finance / Manage), sticky top bar.
- Pages are either **register pages** (metric tiles → filters → list →
  pagination) or **master-detail pages** (directory left, detail right,
  stacking vertically below ~1100px).
- Typography: Instrument Sans 400/500/600. Currency: `GH₵ 1,255,000` (GHS)
  or `1 255 000 FCFA` — always via `formatCurrency`, never hand-formatted.

## Roles

Three fixed roles — `administrator`, `property_manager` ("Manager" in UI),
`viewer` — mapped to capabilities in `app/Enums/UserRole.php`. No custom
roles, no per-user permissions.
