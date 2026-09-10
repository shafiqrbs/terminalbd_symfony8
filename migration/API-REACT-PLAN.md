# Symfony API + React — plan

The Symfony 8 port is a server-rendered application. This is the plan to split it
into a JSON API and a React single-page application, with the React work taking
its structure, components and conventions from
`/home/sreejon/projects/personal/sale-pos`.

The four decisions this plan is built on, taken 2026-09-10:

| | decision |
|---|---|
| where the API lives | `/home/sreejon/projects/personal/terminalbd_api` — its own repo `PosKeeper-ERP/terminalbd_api`, **PHP 8.5** |
| where the React app lives | `/home/sreejon/projects/personal/terminalbd_react` — its own repo `PosKeeper-ERP/terminalbd_react` |
| sequencing | module by module, behind the strangler proxy that already exists |
| authentication | the sale-pos header scheme, `X-Api-Key` + `X-Api-User` |
| scope | what the navigation tree reaches |
| `next/` | retired entirely — the plan ends with it deleted |
| offline | not now, but eventually; the last phase, and it constrains today |

---

## 1. Authentication — decided

The sale-pos scheme is `X-Api-Key` from `VITE_API_KEY`, plus `X-Api-User`
carrying the user id, both set by the client in `prepareHeaders`. That is sound
in sale-pos, because sale-pos is an **Electron desktop app**: the key is baked
into a signed binary and the user id comes from a local SQLite row the renderer
cannot reach without going through the context bridge.

A browser SPA has neither property. `VITE_API_KEY` is compiled into a JavaScript
bundle that anyone can read, and `X-Api-User` is a request header anyone can
set. Shipping that scheme unchanged to a browser means **any visitor can become
any user of any tenant by editing one header** — in an ERP holding 1,088 users'
accounting, payroll and patient data.

The fix keeps the decision and costs almost nothing:

- **Keep the header shape.** `X-Api-Key` and `X-Api-User` stay, so sale-pos's
  `services/` layer, `prepareHeaders`, and every endpoint file port across
  unchanged, and one React codebase can still talk to both back ends.
- **Make `X-Api-User` non-authoritative.** The API reads identity from a
  short-lived signed token in an `httpOnly` cookie, never from the header. The
  header stays as a client hint — logged, compared, and rejected on mismatch,
  which is a useful tamper signal rather than a credential.
- **Demote `X-Api-Key` to a client identifier.** It says *which app* is calling
  (desktop POS, web SPA, Android), not *who*. It can stay public because it no
  longer grants anything.

**Agreed 2026-09-10.** This is the scheme; §0.3 builds it.

---

## 2. Scope: 335 screens, not 3,462 routes

`migration/bin/nav-inventory.php` reads `Appearance\Menu\Builder` — 2,146 lines,
the single place navigation is declared — and resolves every item to a route and
a module. It is read statically on purpose: the builder branches on the signed-in
user's roles and the tenant's enabled modules, so booting it shows one tenant's
slice, and the union is what has to be built.

- **383** menu items link to a route; 135 more are headings and dropdown parents.
- **335** resolve to a controller, across **18 modules**. That is the scope.
- **48** name a route that exists in neither tree. Checked against the legacy
  router, not assumed — none of them is a port defect. They are dead menu entries
  and a product decision, catalogued in `migration/contract/nav-scope.md`.

**Corrected on 2026-09-10, from 365.** The first count read the file with a regex,
which cannot tell live code from dead. Two kinds of declaration look exactly like
navigation and are not: **22 items inside comments** (including all of Customer),
and **67 items in 11 builder methods that nothing calls** — live code, never
invoked from `mainMenu`, `megaMenu` or `footerMenu`. Both are now listed in the
report rather than silently dropped. The inventory is tokenised and walks the call
graph, which is also what makes the role guards readable: `businessMenu()` is *not*
dead, because it is called as `BusinessMenu()`, and PHP does not care about case.

| module | screens | | module | screens |
|---|---:|---|---|---:|
| Accounting | 59 | | Medicine | 54 |
| Business | 58 | | Inventory | 42 |
| | | | the other 14 | 122 |

Every screen but one — the dashboard — sits behind a role guard, and the inventory
now carries them: 334 of 335. They are read from the braces *and* the call graph,
because the whole Tools tree is super-admin-only and says so nowhere inside
`toolsMenu()` — the guard is on the call.

Behind those screens sit **361 form types** and **325 repositories**, which is
where the real work is: a screen is a table and a form, and the form is the part
that carries validation, tenancy and business rules.

---

## 3. Two new projects, beside the old one

```
/home/sreejon/projects/personal/
  terminalbd/        legacy Symfony 2.8 + next/ (Symfony 8) — both shrink, both go
  terminalbd_api/    NEW repo: Symfony 8 on PHP 8.5, JSON only, no Twig ever installed
  terminalbd_react/    NEW repo: React + Vite, modelled on sale-pos
  sale-pos/          the reference, unchanged
```

Each is its own git repo, like every other project in that directory.

### PHP 8.5, pinned

`terminalbd_api/composer.json` declares `"php": ">=8.5"`, not the `>=8.4` that
`next/` carries. That is a tightening, not a jump: the local runtime is already
PHP 8.5.6, and every harness this migration was verified with — the smoke sweep,
the write sweep, the destructive sweep and both sides of the golden capture — has
been running the ported code under 8.5 all along. So the code is already proven
there; `next/`'s `>=8.4` simply understated its floor.

Pinning the floor at 8.5 in a fresh project costs nothing and means the deploy
target cannot quietly drift below what the code has actually been exercised on.

### What the API starts from

`next/` is the Symfony 2.8 → 8.1 and PHP 7.4 → 8.5 upgrade — nothing else here is
on Symfony 8 — so the API does not start from scratch. It starts by **copying the
875 files in `next/src` that are not about rendering HTML**: the classes that
describe your tables and the rules around them.

| copied into `terminalbd_api` | files |
|---|---:|
| Entity + Repository | 687 |
| EventListener + Event | 103 |
| Importer | 29 |
| Service + Manager | 23 |
| Security (`UserProvider`, `Canonicalizer`, session bridge, tenancy) | 8 |
| Permission | 4 |
| Command | 4 |
| Doctrine `Type` + DQL functions | 5 |

The other 703 files — 337 controllers, 361 form types, 5 Twig extensions — are
about rendering HTML and are not copied. React replaces them.

**A copy, not a shared package.** `next/` keeps the copy it already has. That is
the simplest arrangement and it is honest about the situation: `next/` is frozen
and being deleted module by module, so there is no ongoing development in it to
drift away from. The one cost is that while both run, a schema change has to be
applied in both places — in practice you change it in the API and let `next/` run
on its slightly older mapping until it is gone.

### Four settings that must be copied too

Each fails **silently** if forgotten, which is the §5.46 shape, so they are listed
rather than assumed:

- `naming_strategy: doctrine.orm.naming_strategy.default` — the live schema was
  written by Doctrine 2, so columns are `globalOption_id` and `receiveDate`. The
  Symfony default looks for `global_option_id` and finds nothing.
- `LegacyArrayType` and `LegacyJsonArrayType` — 24 live columns hold
  `serialize()` output. Without these they are unreadable.
- `legacy_sha512` and `migrate_from` — 1,088 users hold Symfony 2 salted sha512
  hashes. Without it none of them can log in.
- the three DQL functions — five repositories call `GROUP_CONCAT`, and DQL
  rejects an unregistered function.

Put simply: **`terminalbd_api` is a thin new HTTP front on an upgrade that is
already done, ported and verified.** It supplies routing, serialization and
request validation over classes that already work.

## 4. The API

Only what the 335 screens need, and only when their module's turn comes.

- **Shape.** One resource per screen family: a list endpoint with filters,
  pagination and sort; a show; a create; an update; a delete. The 96 existing
  mobile endpoints stay exactly as they are — they are wire-locked by
  `capture-both.sh` and 89 of 96 are proven compatible. The new API is a
  *second* surface under `/api/v1`, not a rewrite of that one.
- **Forms become request validation.** Each of the 361 form types carries
  constraints, tenancy scoping and choice queries. Those move to the API as
  request DTOs plus a validator; the React form renders fields and shows the
  errors the API returns. **Validation is not duplicated in React** beyond
  "required" and input masking, or the two drift.
- **Tenancy stays server-side.** Every query is already scoped by
  `GlobalOption`. The SPA never sends a tenant id; the API derives it from the
  session, exactly as the Twig application does today.
- **No Twig.** `terminalbd_api/` never installs it, so the boundary cannot rot.

---

## 5. The React application

Structure, tooling and conventions come from sale-pos, unchanged where they can
be: Vite, React 19, Mantine 8, Redux Toolkit with RTK Query, React Router,
`react-i18next`, Tabler icons, `mantine-datatable`.

```
terminalbd_react/src/
  app/         store, App shell
  common/      components/{form-builders,modals,drawers,layout,skeletons}, hooks, utils
  modules/     one folder per terminalbd module, mirroring sale-pos's modules/
  services/    RTK Query: api.mjs + one file per resource
  routes/      routes.js — route table, APP_APIS, APP_NAVLINKS
  lang/        en + bn, both updated together
```

Adopted from sale-pos as-is, because they are the "input fields, forms, modals,
segments" this is meant to look like:

- the 18 `common/components/form-builders/` inputs — `InputForm`,
  `SelectServerSideForm`, `VirtualSearchSelect`, `DatePickerForm`,
  `PhoneNumberInput`, and the rest;
- `GlobalModal` and the `common/components/modals/` pattern;
- `useDisclosure` for every modal and drawer — never `useState(boolean)`;
- `showNotification` from `ShowNotificationComponent.jsx`;
- RTK Query per resource with `injectEndpoints`, paths in `APP_APIS`;
- every user-facing string through `t()`, keys added to `en` and `bn` together.

### The skeleton does not change — and the skeleton is more than the shell

**This is a re-skin at constant geometry, not a redesign.** The test to apply to
any screen: overlay the new one on the old and every element is in the same
place, at the same size. What changes is the *surface* of those elements — the
input's border and focus ring, the box's frame and header, the segment's tab
strip — not where any of it sits.

So: the shell, the content grid, how a page divides into sections, the spacing
between them, and the position and size of every control are terminalbd's and do
not move. sale-pos supplies how the things in those positions *look*, plus its
coding conventions.

**The one real tension, and the rule that resolves it.** Restyled controls carry
their own metrics: Mantine's `TextInput` has its own height, padding and font
size, and dropping it in unchanged moves everything below it down the page. The
rule is that the widget's **footprint is preserved and the restyle happens inside
it** — Mantine's look, terminalbd's box. Mechanically that means the harvested
metrics are applied as Mantine theme overrides
(`theme.components.TextInput.defaultProps` and `styles`), set once in phase 0, so
every screen inherits the right size without per-screen fiddling.

Where a sale-pos widget genuinely cannot fit the old footprint, the footprint
wins and the widget is adjusted — not the other way round.

What that geometry actually is, measured across the 1,862 templates: a
Bootstrap 2 / Metronic system, not Bootstrap 3 and not Mantine's.

| construct | uses | what it does | React equivalent |
|---|---:|---|---|
| `row-fluid` + `span1`–`span12` | 7,719 rows, ~15,000 spans | the 12-column fluid content grid | Mantine `<Grid>` / `<Grid.Col span={n}>` — also 12-column, so the mapping is 1:1 and the column divisions carry over exactly |
| `portlet` / `portlet-title` / `portlet-body` | 3,924 / 2,348 | the card every content section sits in | one `<Portlet>` component, geometry copied: `margin-bottom: 25px`, title `margin-bottom: 15px` over `1px solid #eee`, caption `18px/400` |
| `control-group` + `controls` | 3,666 / 3,508 | the label-and-control form row | one `<ControlGroup>` wrapper that keeps the label placement, gutter and row height — **sale-pos's inputs render inside it**, they do not replace it |
| `tabbable` | 1,122 | tabbed sections within a page | Mantine `<Tabs>`, restyled to this geometry |
| `container-fluid` | 1,180 | the page content wrapper | the layout's content region |

The mapping is unusually clean because both grids are 12-column: `span4` becomes
`<Grid.Col span={4}>` and the page divides exactly as it does today.

`overwrite.css` also gives portlets colour variants — `.box.blue`, `.silver`,
`.sky-blue`, `.bottle-green` — that pages use to tell section types apart. Since
positions and structure are preserved, the variants are preserved too, restyled
as tints from the primary ramp rather than as four unrelated hues. If a screen
turns out to depend on those hues carrying meaning, that screen is the argument
for keeping one of them literal. The
Bootstrap 3 leakage (`col-md-*`, `form-group` — a few hundred uses) normalises to
the same grid on the way through.

**Phase 0 harvests the geometry, and it must be measured from the running app,
not read out of the CSS.** Six stylesheets cascade here — `style.css`,
`components.css`, `plugins.css`, `overwrite.css`, `custom.css`,
`style-responsive.css` — and the app's own tuning is thin and scattered
(`custom.css` sets `.form-control { border: 1px solid #666 }` and little else, so
control heights come from the Metronic base several files up). Grepping for a
value gives you one declaration, not the one that wins.

The reliable method is to read **computed styles from the rendered page**, which
costs nothing now that both applications are served locally: load a representative
list screen and a representative form screen, and record, for each of the
constructs in the table above, the computed box metrics — width, height, padding,
margin, border, font-size, line-height. Those become the CSS custom properties
`terminalbd_react/` consumes and the Mantine component overrides described above.

This is the highest-leverage task in phase 0. A wrong control height is invisible
on the first screen and unfixable by the fiftieth, and it is the one error that
breaks the overlay test everywhere at once.

The navigation tree is ported from the inventory: 335 items, their grouping, and
the 122 role checks that decide who sees what.

### Colour

Primary is sale-pos's, unchanged:

```
--theme-primary-color-0  rgb(245 245 249)      -5  #4f46e5
--theme-primary-color-1  #c7d2fe               -6  #4338ca
--theme-primary-color-2  #a5b4fc               -7  #3730a3
--theme-primary-color-3  #818cf8               -8  #1e40af
--theme-primary-color-4  #6366f1               -9  #1e3a8a
```

`createTheme({ primaryColor: "indigo" })` and Open Sans. sale-pos's extra
`fontSizes` steps come across as well — but **not its `spacing` scale as the
layout rhythm**: spacing is skeleton, so it comes from the metrics measured off
the running app, above. sale-pos's steps stay available for spacing *within* a widget, where they
do not affect the page geometry.

Everything else is a **lighter variation of the primary** rather than a new hue:
the semantic tokens sale-pos maps to green/red/yellow/blue keep their *roles*,
but tints, surfaces, hovers, borders, table stripes and empty states are drawn
from the 0–3 end of the primary ramp. Status colours that must stay
distinguishable — success, error, warning — stay as they are; a lighter indigo
for "error" would be worse design, not more consistent.

Dark mode is a first-class requirement from day one, per sale-pos's CLAUDE.md:
never a hardcoded `#fff`/`white`/`black`, always `light-dark()` or a scheme-aware
token, and `--mantine-color-dark-4` for borders. Retrofitting dark mode is far
more expensive than building with it.

---

## 6. Order of work

Dependencies first, then evidence, then the modules this dump cannot prove.

| # | module | screens | why here |
|---|---|---:|---|
| 0 | ~~—~~ | — | **done 2026-09-10.** the 874 files copied into `terminalbd_api`, pipeline switched to checking-only, `terminalbd_api/` and `terminalbd_react/` skeletons, auth, **geometry tokens measured from the running app**, layout shell, grid, `Portlet`/`ControlGroup`, nav tree, theme |
| 1 | ~~**Tool**~~ | 18 | **done 2026-09-10.** All 18 agree with the Twig originals row for row — `migration/contract/tool-module-screens.md` |
| 2 | ~~**User** + **DomainUser**~~ | 13 | **done 2026-09-10.** 31 of 335 screens built; `migration/contract/module-screens.md` |
| 3 | ~~**Medicine**~~ | 54 | **done 2026-09-10.** 85 of 335 screens built; 24 of these are reports |
| 4 | **Inventory** | 42 | large, well exercised |
| 5 | **Accounting** | 59 | largest, and depends on 1–4 |
| 6 | **Business**, **Hospital** | 92 | large, moderate coverage |
| 7 | the small modules | 18 | Product, Content, Appearance, Media, Location, Pos, Migration |
| 8 | **Restaurant**, **DoctorPrescription** | 25 | blocked: no `restaurant_config` or `dps_config` rows in this dump. Customer is gone from the scope entirely — both its items are commented out |
| 9 | **the mobile API moves** | — | the 96 wire-locked endpoints leave `next/` for `terminalbd_api/`, byte-compatible |
| 10 | **`next/` deleted** | — | the presentation half of the upgrade; the API already has the rest (§7) |
| 11 | **offline** | — | see §8 |

Phase 0 is the one that decides everything after it — it is where the component
library, the API conventions, the form-to-DTO pattern and the theme are settled.
Treat module 1 as still part of it: if Tool needs a convention invented, that
convention is wrong and belongs in phase 0.

### Phase 0 in detail

Everything after this copies phase 0. A convention that is wrong here is wrong
335 times, so it is written out rather than left to be improvised.

#### 0.1 — Two repositories

`terminalbd_api` and `terminalbd_react` in `/home/sreejon/projects/personal/`, each
`git init`ed like every sibling there. Nothing is added to the `terminalbd` repo;
it only shrinks from here.

#### 0.2 — The API reads the database

1. Symfony 8.1 skeleton, `"php": ">=8.5"`, **Twig never installed** — that is the
   boundary, and it holds only if the package is absent.
2. Copy the 875 non-presentation files from `next/src` (§3). Namespace stays
   `App\`, so nothing inside them is edited.
3. Copy the four settings that fail silently (§3): the Doctrine 2 naming
   strategy, `LegacyArrayType` + `LegacyJsonArrayType`, `legacy_sha512` +
   `migrate_from`, and the three DQL functions.
4. Point `DATABASE_URL` at `terminalbd_migration_check`, the scratch database the
   whole migration was verified against.

**Proof it worked, in this order** — each catches a different one of the four:

| check | catches |
|---|---|
| `doctrine:schema:validate` | mapping copied wrong |
| a repository read returning real rows (`GlobalOption`, 6 rows) | naming strategy — wrong columns find nothing |
| read a row whose column holds `serialize()` output | the legacy array types |
| authenticate `migration_check` | `legacy_sha512` — 1,088 users depend on it |
| a repository calling `GROUP_CONCAT` | the DQL functions |

Copy `verify-columns.php`, `schema-diff.php` and `validate-mapping.php` across
and wire them into the new project's CI. They are the guards that do not need the
legacy tree.

#### 0.3 — Authentication

The scheme from §1, in full.

**Login.** React posts credentials to the API. The API verifies them through the
`UserProvider` and hashers it already copied — which is why `legacy_sha512` and
`migrate_from` had to come across, and why a user's hash is still upgraded to
bcrypt on first login exactly as it is today. On success the API sets a
**signed, `httpOnly`, `SameSite=Lax`, `Secure` cookie** holding a short-lived
token. React never sees it and never stores a credential.

**Every request afterwards.** Identity comes from that cookie and only from
there.

- `X-Api-Key` identifies *which client* is calling — web, desktop, Android. It is
  public, it is in the JS bundle, and it grants nothing.
- `X-Api-User` is a **hint**. The API compares it to the cookie's identity and
  rejects the request on mismatch. It is a tamper signal, never a credential.
- Tenancy is derived server-side from the authenticated user. The SPA never sends
  a tenant id, exactly as the Twig application never trusted one.

**CSRF.** A cookie is sent automatically, so state-changing requests need more
than the cookie. `SameSite=Lax` plus the required `X-Api-Key` header covers it:
a cross-site form post cannot set a custom header, and a cross-site
`fetch` cannot read the response without CORS approval. If any endpoint must
accept a plain cross-site form post, it needs a real CSRF token — none should.

**CORS.** `terminalbd_react` runs on a different origin in development (Vite on
:3000). The API allows that origin explicitly, with credentials, and an
allowlist — never `*`, which cannot carry credentials anyway.

**The session bridge — do not skip this.** While the proxy still routes some
paths to the legacy application, an API login has to be visible to it, and there
is a hard reason: the legacy encodes `sha512` and cannot verify a bcrypt hash, so
**an upgraded user can never log in there again** (§`session-bridge.md`). The
bridge is the only way they reach an unported route.

So the API does what `next/`'s `SharedSessionListener` already does on
`InteractiveLoginEvent`: write the user identifier to `_migration_user` in the
**shared `sessions` table**, and remove it on logout. That listener is among the
875 files copied, so this is wiring rather than new code. The legacy half adopts
it — verified end to end, after the anonymous-token fix.

Consequence worth stating plainly: **the API's own cookie is not enough on its
own.** Until `next/` and the legacy application are both gone, a login has to
land in two places — the API's cookie and the shared session row.

#### 0.4 — Measure the geometry

The highest-leverage task here, and the one thing that must not be eyeballed.
**Measured from the rendered page, never read out of the CSS** — six stylesheets
cascade and the winning declaration is rarely the one grep finds (§5).

Two screens, chosen because between them they carry every construct, and because
both render 200 against the scratch database today:

| screen | route | carries |
|---|---|---|
| `/admin/designation/` | `designation` | list, table, 4 portlets, tab strip |
| `/domain/invoicemodule/{id}/modify` | `invoicemodule_modify` | **12 `control-group` form rows**, 7 portlets, tab strip |

For each construct in §5's table, record the computed box: `width`, `height`,
`padding`, `margin`, `border`, `border-radius`, `font-size`, `line-height`. At
minimum: the grid gutter, portlet margin/padding and title metrics, the
`control-group` label and control heights and the gap between rows, table header
and row heights, and the tab strip.

Two outputs, and both are needed:

- **CSS custom properties** — `--tbd-grid-gutter`, `--tbd-portlet-margin-bottom`,
  `--tbd-control-height` and so on, in `terminalbd_react`'s global stylesheet.
- **Mantine component overrides** — `theme.components.TextInput.defaultProps` and
  `styles`, and the same for `Select`, `Textarea`, `Button`, `Table`, `Tabs`.
  This is the mechanism that makes the re-skin work: sale-pos's *look* rendering
  at terminalbd's *metrics*, set once instead of fought per screen.

#### 0.5 — The React skeleton

Vite + React 19 + Mantine 8 + Redux Toolkit + React Router + `react-i18next`,
laid out exactly as sale-pos is (§5), including its `@components` / `@services`
aliases, ESLint and Prettier config.

Then the four components every screen is built from:

- **`<AppShell>`** — fixed header, collapsible left sidebar, content region,
  honouring the tenant's `isSidebar` flag.
- **`<Portlet>`** — the box: title, tools, body, and the colour variants
  (`blue`, `silver`, `sky-blue`, `bottle-green`) as tints of the primary ramp.
- **`<ControlGroup>`** — the label-and-control row, 7,174 uses in the old tree.
  sale-pos's inputs render *inside* it.
- **Grid** — Mantine's, 12 columns, gutter from 0.4. `span4` → `<Grid.Col
  span={4}>`.

Plus the theme: `primaryColor: "indigo"`, Open Sans, sale-pos's `fontSizes`, and
the primary ramp from §5 — but **not** sale-pos's `spacing` scale as the layout
rhythm.

#### 0.6 — The navigation

Port the tree from `migration/contract/nav-scope.json`: 335 items, their grouping,
and the role guard on each — 334 of 335 have one. Roles come from the API, so this
is the first thing that needs 0.3.

Screens that do not exist yet render a placeholder. A visibly incomplete nav is
better than a nav that grows a new shape per module.

#### 0.7 — Done when

- the API boots, and all five checks in 0.2 pass;
- `terminalbd_react` renders the shell and the full nav against the API;
- one screen — `/admin/designation/` — is rebuilt in React and **passes the
  overlay test** against the Twig original: same positions, same sizes, new
  surface;
- the geometry tokens and Mantine overrides are committed, and that screen uses
  them rather than any local value;
- `migrate.sh` runs in checking-only mode (§7) and reports no unexplained
  difference against the API's copy;
- a user logs in through React and is **also** authenticated on a legacy route
  through the proxy — proving the bridge wiring in 0.3, which is the thing that
  silently rots if it is left until later.

The last one matters most. If `designation` needed a single hand-tuned pixel
outside the tokens, 0.4 is not finished, and every screen after it inherits the
gap.

**Phase 0 closed 2026-09-10.** What proves each part, so a later change can be
checked rather than argued about:

| | proof |
|---|---|
| the API reads the database | `terminalbd_api/tools/verify-copy.php` — 5/5 |
| the mapping, without a database | `terminalbd_api/tools/validate-mapping.php` — 339 entities |
| the geometry | `terminalbd_react/tools/overlay-test.mjs` — identical |
| the nav and its guards | 335 screens, 334 guarded, generated from `nav-scope.json` |
| the pipeline still agrees | `migration/bin/migrate.sh --check` — tree identical, 36 recorded differences |
| the session bridge | `terminalbd_react/tools/bridge-check.mjs`, through the proxy |

The proxy is `migration/bin/strangler-proxy.mjs`: the production nginx
arrangement on one port, reading the ported screens out of the React app's
generated route table so a module is routed there by having landed. It is not a
convenience — three applications on three ports are three origins, and a browser
will not lend one origin's cookie to another, so the bridge cannot be tested in a
browser without it.

Each module ships the same way the strangler already works: its API endpoints and
its React screens land, the module's proxy routes flip from `next/` to `terminalbd_react/`,
and its Twig templates and web controllers are deleted from `next/`. Rollback is
one proxy rule.

Two harnesses decide whether a module has landed, and Tool established both:

- `migration/bin/compare-module-screens.php` renders every Twig screen in every landed module and reads
  the API resource that replaces it, comparing row counts under the same paging
  and the same tenancy. Not markup — the two render different HTML by design —
  but they must be looking at the same rows. A screen that quietly lost its
  tenant filter shows the whole database here.
- `terminalbd_react/tools/module-check.mjs` opens every ported screen in a
  browser and round-trips a write through the form's own endpoints.

Between them they found, in the first two modules: a screen that lists three
tables and not the entity it is named after, a screen that is a 500 in both
trees, an invoice list missing its `process != 'Created'` filter, the whole role
hierarchy missing from the API, and every API error arriving as an HTML page.

Five shapes have turned up so far that a list-and-form does not cover, and each
is now a capability rather than a special case:

- a **repository method** instead of `findBy` — named by the resource and *run*,
  not re-implemented, so conditions nobody would think to copy (Customer excludes
  the row named "Default" and requires a mobile number) come across intact;
- a **singleton** — one row per tenant, created on first read, which is what a
  settings screen is;
- a **decision** — `/backend` is 81 lines of branching that always ends in a
  redirect, so its port is an endpoint that answers where to go;
- a **report** — several aggregate queries shown together, rows that are not
  entities and have no form. Twenty-four of Medicine's fifty-four are this, and a
  set can declare what it needs before it means anything: Vendor Accounts runs its
  ledgers only when given a date range, exactly as its controller does;
- a **point-of-sale terminal** — Add Sales and Add Purchase. The cart they carry
  is ported; the item entry and the invoice creation behind them are eleven
  endpoints with accounting side effects, and are the next real piece of work.

A report is compared differently, and has to be: a Twig report page draws a
skeleton — twelve months, thirty-one days — whether or not the data fills it, and
puts the totals that matter in boxes outside any table. Counting rows there
compares layout, not data. What the harness compares is that both sides answer and
run the same queries.

---

## 7. Retiring `next/` entirely

`next/` is deleted at the end, and two things have to happen first.

### The mobile API moves before the lights go out

`next/src/Bindu/` holds the 96 endpoints the Android and Flutter clients in the
field depend on. Those clients do not update in lockstep with a deploy, so the
move has to be byte-compatible or it breaks phones.

The good news, checked rather than assumed: all three controllers —
`ApiController`, `ApiPortalController`, `ApiPoskeeperController`, 197 KB — contain
**no `render()` call at all**. They build JSON by hand and return a plain
`Response`. So they lift into `terminalbd_api/` as-is, and `capture-both.sh` gates the move
exactly as it gated the port: capture from `next/`, capture from `terminalbd_api/`, and
`compare.php` fails on any drift outside the seven accepted entries.

This is phase 9 and not phase 0 on purpose. Moving them early means maintaining
one surface across two projects for the whole migration; moving them last means
one careful, gated lift when everything else has already stopped changing.

### The pipeline keeps checking, even after the copy

Once the API has its own copy, those files are ordinary source: an entity change
is a normal edit, not a pipeline step.

But `migrate.sh` is not retired. Today it rebuilds `next/src` from the untouched
legacy tree and `git diff` comes back empty, and four of the eleven guards in
`ci-checks.sh` depend on reading that legacy tree: **column names vs the legacy
annotations**, **route resolution**, **route annotations converted to
attributes**, and the **mobile API contract**. Those are the four that catch a
whole construct evaporating — the §5.35 and §5.46 failure mode, which is what has
bitten this project hardest.

So the pipeline keeps running, into a scratch directory, and diffs its output
against the API's copy. It no longer produces anything; it answers one question
per CI run: does the copy still agree with what the legacy tree implies?
Deliberate differences are recorded the way `hand-written.txt` already records
the 24 files that were never mechanical ports.

That costs one scratch build and keeps those four guards working for as long as
the legacy tree exists. When the legacy is finally decommissioned there is
nothing left to check against, and `migration/bin/` is archived rather than
deleted — it is the provenance of every line that was copied.

`schema-diff.php` and `validate-mapping.php` never needed the legacy tree. They
read the database and the mapping, and are unaffected either way.

## 8. Built so offline can be added later

Offline is not in this plan's scope, and it is the last phase. But "eventually"
is a design constraint now, because the difference between an app that can grow
an offline mode and one that cannot is decided in the data layer on day one — and
retrofitting it means rewriting every screen.

sale-pos is already the proof: it is offline-first, with `better-sqlite3` behind
an IPC bridge, and its React code reads through `window.dbAPI` rather than the
network. A browser SPA cannot do that, but it can be built so the seam exists.

Cheap now, expensive later — adopt all four from the start:

- **No component ever calls the network.** Every read and write goes through an
  RTK Query endpoint in `services/`. That is already sale-pos's convention, and
  it is the seam a sync layer is inserted at. One `fetch()` in a component is one
  screen that will not work offline.
- **Every table carries `updated_at` and a soft-delete flag.** Sync needs to know
  what changed and what was removed; a hard `DELETE` is invisible to a client
  that was offline when it happened. Adding these columns to 362 entities later
  is a migration across the whole schema — adding them as each module moves is
  nearly free.
- **Writes carry a client-generated idempotency key.** A queued offline write
  will be retried, and without a key a retry is a duplicate invoice.
- **Server time, not client time.** Offline devices have wrong clocks. Every
  `created`/`updated` value is the server's.

What is deliberately *not* done now: no local store, no queue, no conflict
resolution, no service worker. Those are phase 11, and by then the shape of the
data is right for them.

One open question for that phase, worth answering before it starts: offline in a
**browser** (IndexedDB, service worker, background sync) and offline in
**Electron** (sale-pos's model, `better-sqlite3`, a real local database) are
different products. sale-pos already solves the second. If the eventual answer is
"package `terminalbd_react/` with Electron", much of phase 11 is already written and the
browser work is wasted.

## 9. What carries over from this migration

Not starting from nothing:

- **`capture-both.sh` and `compare.php`** already gate the mobile API by response
  shape. The same harness points at `/api/v1` and gates the new one.
- **`module-verification.md`** gives per-module reach, which is what ordered the
  table above.
- **`nav-scope.md`** is the screen checklist, regenerated by the pipeline.
- **The session bridge** (`migration/session-bridge.md`) already carries a login
  across the proxy boundary and is verified. The SPA is a third participant in
  the same arrangement.
- **The schema guards** — `verify-columns.php`, `schema-diff.php`,
  `validate-mapping.php` — keep working against the copy.

And the standing lesson from §5.46 applies to every screen here: a menu item, an
endpoint or a field that nobody translates does not fail, it evaporates. Check
the source construct, not the router.

---

## 10. Open questions

*(Authentication was one of these; settled 2026-09-10, §1.)*

1. **The 107 dead menu items** — delete or restore, per case.
2. **The 96 mobile endpoints** — phase 9 moves them unchanged. Separately: do the
   Android and Flutter clients ever adopt `/api/v1`, or is that surface frozen for
   good? It costs nothing to freeze it, but it means two API styles forever.
3. **Another tenant's dump** is still the binding constraint. Modules in phase 8
   cannot be verified without one.
4. **Browser offline or Electron offline** (§8). Answering this early decides
   whether phase 11 is mostly new work or mostly sale-pos's existing work.
5. **When the legacy tree is finally retired**, the four guards that read it go
   with it (§7). Worth knowing which of them you would miss before that day
   arrives rather than after.
