# Symfony API + React — plan

The Symfony 8 port is a server-rendered application. This is the plan to split it
into a JSON API and a React single-page application, with the React work taking
its structure, components and conventions from
`/home/sreejon/projects/personal/sale-pos`.

The four decisions this plan is built on, taken 2026-09-10:

| | decision |
|---|---|
| where the API lives | a new `api/` project; the entity layer is shared, not copied |
| sequencing | module by module, behind the strangler proxy that already exists |
| authentication | the sale-pos header scheme, `X-Api-Key` + `X-Api-User` |
| scope | what the navigation tree reaches |

---

## 1. Read this before anything else: the auth decision needs one change

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

Everything below assumes that adjustment. If you want the header trusted as-is,
say so and I will write it that way — but not without this paragraph in the
plan.

---

## 2. Scope: 365 screens, not 3,462 routes

`migration/bin/nav-inventory.php` reads `Appearance\Menu\Builder` — 2,146 lines,
the single place navigation is declared — and resolves every item to a route and
a module. It is read statically on purpose: the builder branches on the signed-in
user's roles and the tenant's enabled modules, so booting it shows one tenant's
slice, and the union is what has to be built.

- **472** menu items link to a route; 142 more are headings and dropdown parents.
- **365** resolve to a controller, across **19 modules**. That is the scope.
- **107** name a route that exists in neither tree. Checked against the legacy
  router, not assumed — none of them is a port defect. They are dead menu entries
  and a product decision, catalogued in `migration/contract/nav-scope.md`.

| module | screens | | module | screens |
|---|---:|---|---|---:|
| Accounting | 59 | | Hospital | 34 |
| Business | 58 | | Tool | 28 |
| Medicine | 56 | | DomainUser | 18 |
| Inventory | 45 | | Restaurant | 17 |
| | | | the other 11 | 50 |

Behind those screens sit **361 form types** and **325 repositories**, which is
where the real work is: a screen is a table and a form, and the form is the part
that carries validation, tenancy and business rules.

---

## 3. Three projects, one entity layer

```
terminalbd/
  src/, app/, web/     legacy Symfony 2.8 — unchanged, still proxied to
  next/                Symfony 8, server-rendered — shrinks as modules move
  core/                NEW: the shared domain package
  api/                 NEW: Symfony 8, JSON only, no Twig ever installed
  web-ui/              NEW: React + Vite, modelled on sale-pos
```

**`core/` is the point of the shared-entity decision.** 687 of `next/src`'s 1,578
files — 362 entities and 325 repositories — are the part this migration spent its
whole verification budget on: the mapping validates, `verify-columns.php` reports
zero differences against the legacy annotations, and the schema diff is
accounted for. Copying that into a second project forks it on day one. Instead it
becomes a Composer path package that both `next/` and `api/` require:

```json
{ "repositories": [{ "type": "path", "url": "../core" }] }
```

The namespace stays `App\` so nothing in the ported tree has to be rewritten.

**This changes the pipeline, and that has to be designed, not discovered.**
`migrate.sh` currently regenerates all of `next/src` from the legacy tree and
leaves `git diff` empty — the invariant every guard rests on. It gains a step
that emits entities and repositories into `core/` and the rest into `next/`. The
guards (`verify-columns.php`, `check-routes.php`, `validate-mapping.php`) move
with the code they check.

---

## 4. The API

Only what the 365 screens need, and only when their module's turn comes.

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
- **No Twig.** `api/` never installs it, so the boundary cannot rot.

---

## 5. The React application

Structure, tooling and conventions come from sale-pos, unchanged where they can
be: Vite, React 19, Mantine 8, Redux Toolkit with RTK Query, React Router,
`react-i18next`, Tabler icons, `mantine-datatable`.

```
web-ui/src/
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

### The skeleton does not change

The current shell stays: fixed header, collapsible left sidebar, content area —
`page-header-fixed`, `page-sidebar`, `page-content` in today's `base.html.twig`,
including the sidebar-collapsed state the tenant's `isSidebar` flag controls.
sale-pos supplies the *contents* of that shell — its inputs, tables, modals and
spacing — not its geometry. Where sale-pos's own layout disagrees with this
skeleton, the skeleton wins.

The navigation tree is ported from the inventory: 365 items, their grouping, and
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

`createTheme({ primaryColor: "indigo" })`, Open Sans, and sale-pos's extra
`fontSizes`/`spacing` steps.

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
| 0 | — | — | `core/` extracted, `api/` and `web-ui/` skeletons, auth, layout shell, nav tree, theme |
| 1 | **Tool** | 28 | settings and `GlobalOption`; everything else reads it |
| 2 | **User** + **DomainUser** | 20 | login, roles, branches — the 122 nav role checks need them |
| 3 | **Medicine** | 56 | best evidence here: 82% of visited pages render, 38% reach |
| 4 | **Inventory** | 45 | large, well exercised |
| 5 | **Accounting** | 59 | largest, and depends on 1–4 |
| 6 | **Business**, **Hospital** | 92 | large, moderate coverage |
| 7 | the small modules | 25 | Product, Content, Appearance, Media, Location, Pos |
| 8 | **Customer**, **Restaurant**, **DoctorPrescription** | 28 | blocked: no `business_config`, `restaurant_config` or `dps_config` rows in this dump |

Phase 0 is the one that decides everything after it — it is where the component
library, the API conventions, the form-to-DTO pattern and the theme are settled.
Treat module 1 as still part of it: if Tool needs a convention invented, that
convention is wrong and belongs in phase 0.

Each module ships the same way the strangler already works: its API endpoints and
its React screens land, the module's proxy routes flip from `next/` to `web-ui/`,
and its Twig templates and web controllers are deleted from `next/`. Rollback is
one proxy rule.

---

## 7. What carries over from this migration

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
  `validate-mapping.php` — move to `core/` and keep working.

And the standing lesson from §5.46 applies to every screen here: a menu item, an
endpoint or a field that nobody translates does not fail, it evaporates. Check
the source construct, not the router.

---

## 8. Open questions

1. **Does `next/` survive?** This plan shrinks it to nothing over 8 phases. If the
   answer is "it should be gone entirely", the last phase deletes it and the
   pipeline stops regenerating a web tier — worth deciding now, because it
   changes how much effort the `migrate.sh` split deserves.
2. **The 107 dead menu items** — delete or restore, per case.
3. **The 96 mobile endpoints** — do the Android/Flutter clients move to `/api/v1`
   eventually, or is the old surface frozen forever?
4. **Another tenant's dump** is still the binding constraint. Modules in phase 8
   cannot be verified without one.
5. **Offline.** sale-pos is offline-first with local SQLite. A browser SPA is not.
   If any screen must work offline, it needs a different design and should be
   named now.
