# Patrimoine design system (v1.0.37)

The single reference for how Patrimoine UI is built — the customer
application, the platform console, the authentication pages, the e-mails and
the PDF documents. The marketing site is built on the same system and keeps
its own mirror of it in `patrimoine365-site/app/globals.css`.

If markup, CSS, or JS disagrees with this document, the code is wrong.

---

## What the system is

**Structure is Untitled UI.** The 4px spacing grid, the type scale, the
radius and shadow ramps, the 4px focus ring, and the anatomy of every
component.

**Colour is the Patrimoine 365 Brand Package v1.0.** The two meet without a
seam because every colour the brand names lands exactly on a canonical
Untitled UI step:

| Step | Hex | The brand's name for it |
|---|---|---|
| `brand-50` | `#E6F7EF` | Mint Wash |
| `brand-400` | `#39D6A3` | Patrimoine Mint |
| `brand-600` | `#0E7A56` | Mint Deep |
| `brand-900` | `#123D35` | Patrimoine Green |
| `gray-50` | `#F2F6F4` | Mist |
| `gray-200` | `#DDE6E2` | Border |
| `gray-500` | `#7E8C87` | Border Strong |
| `gray-600` | `#66736F` | Slate |
| `gray-900` | `#17201E` | Ink |
| `gray-950` | `#0E1614` | Night |

**Warm Ivory `#F7F5EF` is not in the product.** The brand reserves it for
marketing, e-mail and print. It is the marketing site's alternating band and
the e-mail ground; inside the application it never appears.

**Mint has a budget, and it is five things:** the active navigation item, the
focus ring, the selected row, positive figures, and the logo. Nothing else.
Green is the dominant — the sidebar, the primary button, headings.

---

## The files

```
resources/css/
  fonts.css        Inter, self-hosted, two files for every weight
  scale.css        the ramps, the type scale, radii, shadows, panel variants
  tokens.css       the semantic --pm-* tokens, light and dark
  components.css   Untitled UI component anatomy
  app.css          page structure only — grids, shells, calendar, cropper
  hidden-guards.css / flags.css   generated
```

Changing how the product looks means editing **tokens.css**. Changing what a
button *is* means editing **components.css**. `app.css` lays pages out and
must never make an appearance decision of its own.

---

## The rules

1. **No raw colour in markup or page CSS.** Not a hex, not a Tailwind palette
   utility. Markup speaks `--pm-*` — directly (`bg-[var(--pm-surface)]`) or
   through a component class. The whole product recolours from two blocks in
   `tokens.css`.

2. **No `!important`.** There were 504; the component layer removed the need
   for every one. The single exception is authorization visibility
   (`.rbac-hidden`, `.shell-admin-only`, `.pm-hide`), which must be able to
   beat anything feature JavaScript does — a role gate that page code can
   undo is not a gate. `RoleAwareApplicationUiTest` holds `app.css` to zero.

3. **No `#id`-scoped component styling.** Twelve drawers each carried their
   own copy of input, label, placeholder, focus and footer styling. An input
   inside a drawer is a `.pm-input`; it does not need the drawer to tell it
   what colour its border is. An `#id` selector is only acceptable for a
   genuinely unique element — one list, one tooltip, one total pill.

4. **Inside a drawer, size against the PANEL, never the viewport.**
   `md:grid-cols-2` asks the window how wide it is; inside a 28rem panel on a
   27-inch monitor that answers "wide" and the form lays out in two columns
   that do not fit. Use `panel-sm:` / `panel-md:` — custom variants declared
   in `scale.css` and driven by the container on `.pm-drawer-panel`.

5. **Dark mode is never written twice.** Because markup speaks tokens, dark
   mode needs no page-specific CSS. If a page needs a dark-mode rule, the
   page is reaching past a token and the page is wrong.

6. **One icon set, one source.** `resources/icons/untitled-ui.json`. Blade
   reads it through `<x-icon name="…">`; `resources/js/icons.js` is compiled
   from it by `scripts/generate-icons.mjs`; the marketing site keeps a copy
   and generates its own `components/icons.tsx` the same way. An `<svg>`
   pasted inline is an icon nobody can restyle, recolour or find again —
   there were twenty-one in one layout file.

7. **Bilingual always.** Every user-visible string goes through
   `lang/{en,fr}/*.php` **and** `resources/js/translations.js`, wired with
   `data-i18n*` attributes or `translate()`. `data-i18n*` takes a **key**,
   never a translated string.

8. **Responsive always.** Mobile-first; wide content scrolls inside its own
   `overflow-x-auto`; drawers become full-width sheets below 640px. Every
   page must be usable at 375px.

9. **API-first.** Blade serves static shells; every action calls the JSON
   API. No capability may exist only as a console command or only as a UI
   behaviour.

10. **Declarative permissions.** Role-gated elements carry
    `data-requires-capability="…"` and are listed in
    `resources/js/permissions.js`. The server middleware is authoritative;
    the client layer is presentation only.

---

## Two deliberate departures from stock Untitled UI

Both come from the brand package's contrast ledger, and both are
improvements rather than compromises.

**Control outlines are `gray-500` (3.5:1), not Untitled UI's `gray-300`
(1.5:1).** WCAG 1.4.11 requires 3:1 on the boundary of a control a user has
to find. `gray-200` remains the divider between things that are not
controls.

**Success IS the brand ramp.** Two greens a few degrees apart read as a
mistake, and the brand already says positive figures are mint.

---

## The contrast ledger

Every pair below was measured, not eyeballed.

| Pair | Ratio |
|---|---|
| Ink on white / on Mist | 16.6 / 15.3 |
| `gray-800` on white | 11.0 |
| `gray-700` on white | 7.1 |
| Slate on white / on Mist | 4.9 / 4.5 |
| White on Patrimoine Green (the primary button) | 12.0 |
| Mint Deep on white (links, focus, positive figures) | 5.3 |
| Mint on Patrimoine Green (the sidebar accent) | 6.5 |
| Border Strong on white (control outlines) | 3.5 |
| Dark: Mist on Night / on Ink | 16.9 / 15.3 |
| Dark: Mint on Ink | 9.0 |
| Dark: white on Mint Deep (the primary button) | 5.3 |
| Dark: `#626F6A` on Ink (control outlines) | 3.2 |

`--pm-text-placeholder` (`gray-500`, 3.5:1) is the one token below 4.5:1. It
is for placeholder and disabled copy only — never a label, never a value.

---

## Colour tokens

Defined once per theme in `tokens.css`.

| Group | Tokens |
|---|---|
| Grounds | `--pm-page`, `--pm-surface`, `--pm-surface-subtle`, `--pm-surface-muted`, `--pm-surface-sunken`, `--pm-surface-elevated` |
| Text | `--pm-text`, `--pm-text-secondary`, `--pm-text-muted`, `--pm-text-subtle`, `--pm-text-placeholder` |
| Lines | `--pm-border`, `--pm-border-subtle`, `--pm-border-strong` |
| Interaction | `--pm-hover`, `--pm-selected`, `--pm-accent`, `--pm-overlay` |
| Inputs | `--pm-input-background`, `--pm-input-disabled` |
| Actions | `--pm-primary`, `--pm-primary-hover`, `--pm-primary-text`, `--pm-danger-solid`, `--pm-danger-solid-hover` |
| Status | `--pm-{success,warning,danger,info}-{background,border,text}` |
| Elevation | `--pm-shadow-{xs,sm,md,lg,xl,2xl}`, `--pm-ring-{brand,gray,error}` |
| The dark band | `--pm-band`, `--pm-band-elevated`, `--pm-band-text`, `--pm-band-text-muted`, `--pm-band-border`, `--pm-band-hover`, `--pm-band-selected`, `--pm-band-accent` |
| The logo | `--pm-logo-pillar`, `--pm-logo-bar` |

**The band** is the sidebar and the authentication hero: a Patrimoine Green
surface inside a light product. Everything inside reads `--pm-band-*`, so
nothing has to know it is standing on a dark ground. In dark mode the band
stops being a contrast device and becomes a slightly raised surface — that
decision lives in `tokens.css`, not in any page.

---

## Components

`components.css`. A component earns its place when the same thing appears on
three pages; below that it is page structure.

- **`<x-drawer>`** — the only overlay. `width="sm"` (28rem, one record) or
  `width="lg"` (42rem, a workflow). The panel declares a container, so its
  contents size against it. Centred dialogs and hand-rolled `fixed inset-0`
  overlays are forbidden. Open and close only through `openDrawer` /
  `closeDrawer` / `wireDrawer` in `core.js`.
- **`<x-drawer-header>` / `<x-drawer-footer>`** — the footer's actions share
  its width equally: `.pm-button-secondary` Cancel, then one primary —
  `.pm-button-primary` Save, or `.pm-button-danger` for a destructive
  workflow, never both. Labels use `actions.cancel` / `actions.save` /
  `actions.close`. Actions live in the footer, never in the body.
- **Buttons** — `.pm-button-primary` / `-secondary` / `-tertiary` /
  `-danger` / `-danger-outline`, `.pm-button-sm` / `-lg` for size,
  `.pm-icon-button` for icon-only. 40px is the default height, so a toolbar
  of mixed buttons keeps one baseline.
- **`.pm-input` + `.pm-field-label` + `.pm-field-hint`** — every form field.
  No hand-rolled focus styling.
- **`.pm-form-grid`** with `.pm-form-grid-2` / `-3`, or `panel-md:` — form
  layout inside a panel.
- **`.pm-option-row` + `.pm-checkbox`** — a checkbox that is a choice rather
  than a consent.
- **`.pm-card`** (+ `-header` / `-title` / `-note` / `-body` / `-footer` /
  `.pm-card-flush`) — every content card.
- **`.pm-table`** — `.pm-cell-primary` for the identifying column,
  `.pm-cell-numeric` for figures.
- **`.pm-badge`** (+ `-success` / `-warning` / `-danger` / `-info` /
  `-brand`, and `.pm-badge-dot`), **`.pm-count-pill`**, **`.pm-chip`**.
- **`.pm-metric`** — a dashboard tile; the figure is display-md.
- **`.pm-alert`**, **`.pm-empty`**, **`.pm-tabs`**, **`.pm-pagination`**.
- **`<x-icon>`**, **`<x-logo>`**, **`<x-nav-item>`**.

---

## Type

Inter, self-hosted from `public/fonts`, one variable file per unicode range
covering every weight. It is **not** loaded from Google Fonts: that sends
every visitor's IP to Google before the first paint, in a product whose
privacy policy tells customers there are no third parties in the page.

Untitled UI's scale, verbatim. Display sizes carry -2% tracking because
Inter's default spacing is drawn for text sizes and reads loose above 30px.

`display-2xl` 72 · `display-xl` 60 · `display-lg` 48 · `display-md` 36 ·
`display-sm` 30 · `display-xs` 24 · `xl` 20 · `lg` 18 · `md` 16 · `sm` 14 ·
`xs` 12.

Numbers that can be compared down a column are `tabular-nums`. Currency is
always `formatCurrency` — `GH₵ 1,255,000` or `1 255 000 FCFA` — never
hand-formatted.

---

## E-mail

`resources/views/emails/`. Warm Ivory ground, Patrimoine Green masthead,
white body card, 600px, table layout and inline styles because that is what
e-mail clients accept. The mark is a PNG served from the marketing site;
e-mail clients do not render SVG, and its `alt` is empty on purpose because
the wordmark beside it is live text.

Every colour is a brand colour and every pair was checked: Ink 15.3:1, the
supporting grey 6.5:1, Slate 4.5:1, Mint Deep 4.9:1, and on the masthead the
muted line 7.4:1 and Mint 6.5:1.

---

## PDF documents

`resources/views/documents/`, `reports/`, `registry/`, `pdf/`, and the
`*/export.blade.php` files.

**The paper stays white.** The brand puts documents on Warm Ivory; paper does
not work that way — a full ivory flood costs toner on every page and prints
muddy on an office laser, and these are invoices people file. Ivory is the
*fill*: table headers, callouts. The identity arrives through the letterhead,
the green headings and the ivory table chrome.

**The letterhead is the CUSTOMER's identity**, not ours. These are their
invoices, sent to their tenants. Patrimoine appears once, small, in the
footer.

Inter is registered with dompdf through `documents/partials/fonts.blade.php`,
which every PDF template includes. **DejaVu Sans stays in every font stack
behind it**: it is dompdf's own bundled family, so if a font file ever fails
to resolve — an unwritable font cache after a deploy, a missing file — the
document still renders. An invoice that looks slightly wrong is recoverable;
one that does not render is not.

---

## Layout

- Fixed green sidebar (`w-72`, off-canvas below `lg`), three nav groups
  (Workspace / Finance / Manage) built from `<x-nav-item>`, sticky top bar.
- Pages are either **register pages** (metric tiles → filters → list →
  pagination) or **master-detail pages** (directory left, detail right,
  stacking below ~1100px).

## Roles

Three fixed roles — `administrator`, `property_manager` ("Manager" in UI),
and `viewer`.
