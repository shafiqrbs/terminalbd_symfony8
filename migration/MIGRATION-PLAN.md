# Terminalbd → Symfony 8 / PHP 8.5

Strangler migration from Symfony 2.8 / PHP 7.4 onto a parallel Symfony 8.1 application
sharing the same MySQL database, with modules cut over one at a time behind the proxy.

| | From | To |
|---|---|---|
| PHP | 7.4.33 | 8.5.6 |
| Framework | Symfony 2.8 | Symfony 8.1.6 |
| Doctrine ORM | 2.5.4 | 3.6.8 |
| Doctrine DBAL | 2.x | 4.4.4 |
| Twig | 1.23.3 | 3.28.0 |

Constraints this plan is written against: **live production with paying tenants**, mobile API
response shapes are **locked** (shipped Android + Flutter clients), and there is **no test suite**.

Companion summary (charts, tables, roadmap): https://claude.ai/code/artifact/be69c7c3-6440-412d-b00b-3bdbbb30a06f

---

## 1. Status

**Phase 1 complete. Phase 2 partially complete — security layer built and tested; login UI and session sharing outstanding.**

```
$ ./migration/bin/port.sh
...
== verify ==
references to DBAL types removed in v4: 0 (must be 0)
files with leftover @ORM annotations:   0
files with leftover @Gedmo annotations: 0
syntax: clean

$ cd next && php8.5 bin/validate-mapping.php
loaded 339 entity metadata definitions
309 entities valid, 30 with mapping errors
```

| Metric | Value |
|---|---|
| Files ported into `App\` | 686 |
| `#[ORM\...]` attributes generated | 8,388 |
| `#[Gedmo\...]` attributes converted | 432 |
| Metadata definitions loaded | 339 |
| Entities validating clean | 309 |
| Entities with pre-existing mapping errors | 30 |
| Syntax errors on PHP 8.5 | 0 |

The 30 remaining are legacy defects, not port artifacts — see §5.6.

---

## 2. Why strangler rather than in-place version hops

Both were considered. The deciding facts:

1. **No regression safety net.** The 119 `*Test.php` files are `sensio/generator-bundle`
   scaffolding — the first asserts `GET /hello/Fabien` returns "Hello Fabien". Stepping
   2.8 → 3.4 → 4.4 → 5.4 → 6.4 → 7.4 → 8.1 means six blind rewrites of 700k lines.
2. **The dead dependencies die early anyway.** FOSUser, JMS Security, Assetic, FOSRest and
   PHPExcel all have no Symfony 4+ release. You pay that rewrite before reaching Symfony 4,
   then carry the result through four more majors.
3. **In-place leaves production unusable until the final hop.** Strangler ships value
   continuously; rollback is one proxy route flipped back.

The cost accepted in exchange: two applications and two PHP pools run side by side for the
duration, over one shared database.

```
nginx ──┬─> legacy SF2.8 (php7.4)   [routes not yet ported]
        └─> next/  SF8.1 (php8.5)   [routes already ported]
                 └── shared MySQL
```

---

## 3. Why the entity graph moved as one unit

Measured across the tree:

- **48%** of `targetEntity` references cross a bundle boundary (934 of 1,955)
- **60%** of entities (202 of 335) have at least one cross-bundle association
- Every module depends on between **2 and 15** others; `ToolBundle` on 15, `UserBundle` on 13

There is no clean per-module seam in the data model. A partial copy leaves Doctrine unable to
resolve `targetEntity` and the metadata factory fails outright. So the model is the shared
foundation, ported once; the **HTTP layer** is what moves module by module on top of it.

---

## 4. What exists on disk

### `migration/bin/` — the pipeline

| File | Purpose |
|---|---|
| `port.sh` | 7-step idempotent pipeline. Rebuilds `next/src/` from the untouched SF2.8 tree. Safe to re-run after any legacy change. |
| `port-entities.php` | Copies 686 entity/repository files, rewrites legacy namespaces → `App\` |
| `gedmo-to-attributes.php` | `@Gedmo` docblocks → attributes (Rector has no Gedmo rules) |
| `fix-duplicate-attributes.php` | Collapses repeated non-repeatable ORM attributes, keeping the last |
| `fix-joincolumns.php` | Drops `JoinColumn` from illegal association sides |
| `fixups.php` | Targeted corrections for individually malformed legacy annotations |
| `port-security.php` | Generates `next/config/packages/security_roles.yaml` from `app/config/security.yml` |
| `capture.php` | Records golden API responses from an app |
| `compare.php` | Diffs response **shape** (keys + value types) and status; exit 1 on drift |

### `migration/contract/` — extracted facts

| File | Contents |
|---|---|
| `api-endpoints.json` | All 96 mobile endpoints: prefix, path, method, controller |
| `entity-map.json` | 335 entities → table, repository, column count, association count |
| `broken-associations.md` | The 48 broken `mappedBy`/`inversedBy` links, by entity |
| `mapping-validation.txt` | Raw output of the last acceptance run |

### `next/` — the new application

Hand-written (regenerated files are everything else under `next/src/<Module>/`):

| File | Purpose |
|---|---|
| `rector.php` | PHP 8.5 + Doctrine/Symfony attribute sets. **Skips** `src/Security`, `src/Comment`, `src/Doctrine` |
| `bin/validate-mapping.php` | Acceptance test: loads all Doctrine metadata, no DB needed |
| `config/packages/doctrine.yaml` | Single `App` mapping over `src/`; registers the legacy DBAL types |
| `src/Doctrine/Type/LegacyArrayType.php` | Restores DBAL's removed `array` type |
| `src/Doctrine/Type/LegacyJsonArrayType.php` | Restores the removed `json_array` type |
| `src/Security/Model/BaseUser.php` | Replaces `FOS\UserBundle\Model\User`, column-for-column |
| `src/Security/Model/BaseGroup.php` | Replaces `FOS\UserBundle\Model\Group` |
| `src/Comment/Model/Base{Comment,Thread}.php` | Replace the FOSCommentBundle superclasses |

### Running it

```bash
./migration/bin/port.sh                      # rebuild next/src/ from legacy (idempotent)
cd next && php8.5 bin/validate-mapping.php   # acceptance test
```

`port.sh` deletes and regenerates the module directories under `next/src/` only. The
hand-written compatibility layer above is never touched.

> **Rector scope is load-bearing.** `rector.php` must keep skipping `src/Security`,
> `src/Comment` and `src/Doctrine`. Without the skip it types the shim properties
> (`protected ?string $username`), which then clash with the untyped properties the ported
> subclasses redeclare, and it rewrites `type: 'array'` to `Types::ARRAY` — a constant DBAL 4
> no longer defines. `port.sh`'s verify step greps for the latter and must report 0.

---

## 5. Findings

All of these are pre-existing — the same text is in the SF 2.8 tree today. Strict attribute
parsing is what surfaced them.

### 5.1 DBAL 4 removed the `array` and `object` types — highest severity

24 live columns hold PHP-serialized payloads, including **`fos_user.roles`**, which carries
every user's role set. Read through a naive `json` replacement, every user on every tenant
silently loses their roles — a total authorization failure with no error.

Fixed by `App\Doctrine\Type\LegacyArrayType`, which reproduces DBAL 3 `serialize`/`unserialize`
semantics exactly. Production rows are read unchanged and **no data migration is required**.

Affected columns (23 app + `fos_user.roles`), e.g.:
`HospitalConfig.invoiceProcess`, `Particular.weeklyOffDay`, `Product.ageGroup`,
`Customer.namePrefix`, `Invoice.tableNos`, `BusinessConfig.stockFormat`,
`GlobalOption.stockFormat`, `SmsBulk.gender`, `User.appRoles`, plus
`RestaurantTableInvoice.serveBy` (`json_array`).

**Do not "modernise" these to `json` as a side effect of a module port.** It needs a
deliberate backfill with its own rollback (§7).

### 5.2 Composite foreign key introduced by automated conversion — 1 case

A stray `*` in a `User` docblock left `@ORM\JoinColumns` malformed. Rector faithfully emitted
*both* join columns as separate attributes on `User::$globalOption`, which ORM 3 reads as a
composite FK against a table that has one column. Doctrine 2's driver had taken the standalone
`@ORM\JoinColumn(onDelete="CASCADE")` and defaulted the column to `globalOption_id`.

Merged back to that single column in `fixups.php`. **This produces no error message** — found
by scanning all 686 files for the pattern.

### 5.3 `JoinColumn` on illegal association sides — 30 cases

On `OneToMany` relations, on inverse (`mappedBy`) sides, and in three cases on plain scalar
`Column`s. Inert under ORM 2, a hard mapping error under ORM 3. Removed by rule in
`fix-joincolumns.php`, which preserves legitimate owning-side `ManyToMany` pairs.

### 5.4 `cascade={"merge"}` after `merge()` was removed — 98 cases

ORM 3 deleted `EntityManager::merge()` and its cascade. The app calls `merge()` exactly once,
in `Core\UserBundle\Command\PlayCommand`, so the cascade was already inert. Dropped and the
option lists deduped across 65 files.

### 5.5 Duplicated non-repeatable annotations — 5 cases

`SiteSetting`, `TransactionMethod`, `Module` and `MobileTheme` each declare `@ORM\Entity`
twice — once bare, once with `repositoryClass`. `ItemSizeGroup::$sizes` declares `@ORM\OrderBy`
twice with conflicting directions (`id DESC` then `name ASC`).

Doctrine 2's AnnotationDriver re-keyed annotations by class name, so the **last** won. PHP 8
rejects the repeat outright. `fix-duplicate-attributes.php` keeps the last occurrence, which
reproduces the ORM 2 outcome — meaning those four custom repository classes *were* in effect,
and the live sort order *was* `name ASC`.

### 5.6 Broken bidirectional associations — 48 refs across 30 entities

`inversedBy` / `mappedBy` pointing at fields that do not exist on the other entity, e.g.
`GlobalOption#employeeTransaction`, `BusinessConfig#itemImports`, `BusinessInvoice#courier`.
Confirmed absent from the legacy entities too. Doctrine only resolves the inverse side lazily,
so they never surfaced at runtime.

Catalogued in `migration/contract/broken-associations.md`. **Fix each as its module is
ported** — add the missing inverse field, or make the relation unidirectional.

### 5.7 `parent::__construct()` in parentless classes — 3 cases

`ApplicationTestimonial`, `WebTheme`, `MobileTheme`. The method is named `_constructor()` — a
typo for `__construct()` — so it was never called and the invalid parent call never ran. PHP 8
rejects it at **compile** time.

Removing the unreachable line preserves behaviour byte-for-byte. **The typo is deliberately
left in place**: correcting it would activate three constructors that have never executed,
which initialise collections and set `status = true`.

### 5.8 Namespace and registration inconsistencies — 2 cases

`appstore\Bundle\DomainUserBundle` is declared with a lowercase leading character;
`Appstore\Bundle\DmsBundle` exists on disk but is never registered in `AppKernel`. Both
normalised during the namespace rewrite.

---

## 6. Phases

### Phase 1 — Skeleton and entity graph ✅ done

Symfony 8.1.6 on PHP 8.5 in `next/`. All 335 entities and 327 repositories lifted into `App\`,
annotations converted to attributes, mappings validated. FOSUser and FOSComment superclasses
reproduced from their locked revisions (`6ccff96`, `fa2a676`) so they map onto `fos_user`,
`user_group` and the comment tables untouched.

### Phase 2 — Authentication and tenancy ← in progress

The gate for everything else: no module can move until a request can be authenticated.

- [x] `App\Security\UserProvider` replacing `fos_user.user_provider.username`. Looks up by
      `usernameCanonical`, as FOSUser did — the login form submits a raw string whose case may
      differ from the stored value. Implements `PasswordUpgraderInterface` so rehashes persist.
- [x] `App\Security\Canonicalizer` reproducing `FOS\UserBundle\Util\Canonicalizer`. The
      `username_canonical` / `email_canonical` columns in production were written with this exact
      transform; a divergence means no existing user can log in.
- [x] Role hierarchy + access control generated by `migration/bin/port-security.php`
      (114 hierarchy entries, 44 access rules; the other 79 legacy roles are self-referential
      `ROLE_X: [ROLE_X]` no-ops, preserved as comments so the vocabulary stays visible).
      All 193 legacy role names verified present.
- [x] `App\Security\TenantRoutingListener` — port of `DomainAttributeListener`, copying
      `subdomain`/`domain` into the router context and restoring the parent request's values on
      sub-request finish.
- [x] Migrating password hasher — verify existing salted `sha512`, transparently rehash to a modern algorithm on next successful login. **No forced reset for any tenant.**
      ```yaml
      password_hashers:
          App\User\Entity\User:
              algorithm: auto
              migrate_from: [legacy_sha512]
          legacy_sha512:
              algorithm: message_digest
              hash_algorithm: sha512
              encode_as_base64: true
              iterations: 5000
      ```
      Verified: Symfony 8's `MessageDigestPasswordHasher` defaults (sha512 / base64 / 5000
      iterations) and its `$password.'{'.$salt.'}'` salt merge are identical to Symfony 2's
      `sha512` shorthand.

**Proven by test** (`next/tests/Security/`, 20 tests / 33 assertions, green). The legacy hash is
reproduced from Symfony 2's algorithm transcribed by hand rather than by calling Symfony 8's
hasher, so it is a real cross-check and not the framework agreeing with itself:

- a Symfony 2 sha512 hash verifies under Symfony 8, and a wrong password does not
- the migrating hasher accepts the legacy format and reports `needsRehash()`
- an upgraded hash is stable and still verifies
- dropping the salt makes verification fail — which is why `BaseUser` must keep implementing
  `LegacyPasswordAuthenticatedUserInterface`
- canonicalization matches FOSUserBundle byte-for-byte, including unicode and `ISTANBUL`

Remaining in this phase:

- [ ] Login / logout / registration / password-reset controllers and templates
      (12 `fos_user_*` route names). Templates depend on Phase 4.
- [ ] Shared session store so a user stays logged in across the proxy boundary while both apps
      serve traffic
- [ ] Convert 112 files of `@Secure` / `@PreAuthorize` → `#[IsGranted]` (lands with each module port)
- [ ] Replace `symfony/security-acl` usage (9 files) with voters

### Phase 3 — Mobile APIs (wire-locked)

96 endpoints: `/flutter-api` (66), `/android-api` (24), `/android-api-portal` (6). Clients are
shipped and cannot be updated in lockstep.

- [ ] Fill in `migration/capture-config.json` (base URL, `X-API-KEY`, auth, per-endpoint params)
- [ ] `php migration/bin/capture.php legacy` against production/staging
- [ ] Port controllers off FOSRestBundle 1.5 → native controllers + Serializer
- [ ] `php migration/bin/capture.php next && php migration/bin/compare.php`
- [ ] **Gate every API deploy on `compare.php` exiting 0.** Treat drift as a release blocker.

`compare.php` diffs shape and status — keys and value types, not volatile ids or timestamps —
so it does not produce false failures on changing data.

### Phase 4 — Shared presentation layer

Built once, consumed by every module port that follows.

- [ ] Base layouts on Twig 3
- [ ] `Bundle:Dir:file.twig` → `@Dir/file.html.twig` across **1,854** references
- [ ] Assetic → AssetMapper: **428** `{% stylesheets %}` / `{% javascripts %}` blocks
- [ ] PDF pipeline off the abandoned `h4cc/wkhtmltopdf-amd64` shims (wkhtmltopdf itself is EOL)
- [ ] ESC/POS thermal receipt printing (`mike42/escpos-php` is maintained — verify SF8 wiring)
- [ ] PHPExcel → PhpSpreadsheet
- [ ] 8,426 legacy Twig form helper calls; 85 `app.request.*` accesses

### Phase 5 — Module ports

Order in §8. Each port converts controllers to autowired services, form types to the SF8 API,
templates to Twig 3, and fixes that module's share of the 48 broken associations.

Per-module checklist:
- [ ] Controllers: drop the SF2 `Controller` base, inject dependencies (`$this->getDoctrine()` ×5,845 and `$this->get()` ×1,434 app-wide)
- [ ] Form types: `setDefaultOptions()` → `configureOptions()`, remove `getName()` (873 methods app-wide)
- [ ] Templates → Twig 3
- [ ] Fix that module's broken associations
- [ ] Record + diff golden responses for its highest-traffic routes
- [ ] Flip the proxy route; keep the legacy route ready for rollback

### Phase 6 — Decommission

- [ ] Retire the PHP 7.4 pool once the last route is served by `next/`
- [ ] Collapse proxy rules
- [ ] Drop unreachable compatibility shims
- [ ] `LegacyArrayType` stays until §7 is done deliberately

---

## 7. Deferred work — deliberately not done during the migration

**Serialized `array` columns → `json`.** 24 columns. Requires a backfill migration that reads
via `unserialize` and writes JSON, with both apps quiesced or a dual-read window, plus its own
rollback. Doing it as a side effect of a module port risks silent data loss. Schedule after
decommission.

**Removing the legacy password hasher.** Users who never log in during the transition keep
`sha512` hashes. The migrating hasher verifies both formats indefinitely. Remove the legacy
algorithm when telemetry shows zero legacy hashes remain — not on a date.

**Fixing the `_constructor()` typo.** See §5.7.

---

## 8. Module port order

Risk-adjusted size: small modules first to shake out the pipeline while a mistake is cheap;
the four largest last, when the patterns are settled. Template line count is the best proxy for
effort, since Twig 1 → 3 dominates each port.

| # | Module | Ctrls | Forms | Tmpl LOC | `$this->get` | `@Secure` | Note |
|---:|---|---:|---:|---:|---:|---:|---|
| 1 | Location | 7 | 1 | 250 | 2 | 0 | Reference data; pipeline warm-up |
| 2 | Media | 7 | 2 | 595 | 10 | 0 | Exercises file handling early |
| 3 | Pos | 4 | 1 | 1,500 | 25 | 2 | Thermal receipt path |
| 4 | Product | 13 | 6 | 2,835 | 19 | 0 | — |
| 5 | HumanResource | 12 | 10 | 4,474 | 28 | 0 | No security annotations |
| 6 | Report | 2 | 0 | 7,352 | 2 | 1 | 83 templates, almost no PHP |
| 7 | DomainUser | 18 | 17 | 7,785 | 59 | 6 | Customer records; tenancy-adjacent |
| 8 | Appearance | 19 | 10 | 9,192 | 15 | 1 | Heavy on `array` columns |
| 9 | Restaurant | 16 | 18 | 11,098 | 72 | 7 | — |
| 10 | Content | 69 | 36 | 13,011 | 96 | 0 | CMS; carries the comment entities |
| 11 | DoctorPrescription | 12 | 9 | 14,252 | 56 | 2 | — |
| 12 | Tool | 58 | 43 | 14,958 | 134 | 5 | Settings; holds `GlobalOption` |
| 13 | Medicine | 26 | 34 | 24,765 | 127 | 5 | Pharmacy |
| 14 | Accounting | 45 | 41 | 38,295 | 170 | 25 | Most `@Secure` in the app |
| 15 | Inventory | 56 | 42 | 39,483 | 177 | 13 | — |
| 16 | Business | 32 | 29 | 39,636 | 138 | 18 | — |
| 17 | Hospital | 35 | 38 | 41,925 | 201 | 12 | Largest template surface |

Customer (9 controllers, 5,730 template lines) folds into DomainUser. Bindu is the mobile API
layer and ships in Phase 3.

---

## 9. Dependency replacement table

No Symfony 5/6/7/8 release exists for any of these.

| Package | Locked | Replacement |
|---|---|---|
| `friendsofsymfony/user-bundle` | dev-master | ✅ `App\Security\Model\BaseUser` + native security |
| `friendsofsymfony/comment-bundle` | 2.0.8 | ✅ `App\Comment\Model\Base{Comment,Thread}` |
| `sensio/distribution-bundle` | 5.0.21 | ✅ Symfony Flex |
| `sensio/generator-bundle` | 2.5.3 | ✅ MakerBundle |
| `jms/security-extra-bundle` | 1.6.1 | `#[IsGranted]` — 112 files |
| `jms/di-extra-bundle` | 1.6.0 | Autowiring |
| `jms/aop-bundle` | 1.2.0 | Drop |
| `symfony/assetic-bundle` + `kriswallsmith/assetic` | 2.7.1 / 1.3.2 | AssetMapper — 428 blocks |
| `friendsofsymfony/rest-bundle` | 1.5.3 | Native controllers + Serializer — 96 endpoints |
| `phpoffice/phpexcel` + `liuggio/excelbundle` | 1.8.1 | PhpSpreadsheet |
| `h4cc/wkhtmltopdf-amd64` / `-image-` | 0.12.2.1 | Re-plumb; wkhtmltopdf is EOL |
| `symfony/security-acl` | 3.0.1 | Voters — 9 files |
| `divi/ajax-login-bundle` | dev-master | Drop |
| `xiidea/ez-maintenance` | 1.0.0 | Drop |
| `gregwar/image-bundle` | 2.1.3 | Evaluate; 13 model files reference it |

Note: four constraints in `composer.json` are unpinned (`dev-master` / `@stable`), so the
legacy build is reproducible only from `composer.lock`, not from `composer.json`.

---

## 10. Open risks

| Risk | Mitigation |
|---|---|
| **No test suite** — nothing catches a regression in 700k lines | Golden-response harness covers the 96 mobile endpoints. Extend record-and-diff to the highest-traffic HTML routes per module; cheaper than retrofitting unit tests |
| **Symfony 8.1 reaches EOL Jan 2027** — the migration will outlive it | Minor Symfony upgrades are BC-safe. Ride 8.2 → 8.3 → 8.4 (next LTS, Nov 2027). Budget a day per bump |
| **Shared database, two writers** | Schema changes additive-only until decommission. Never let a port alter a column the legacy app still reads |
| **Serialized `array` columns** | Keep `LegacyArrayType` registered for the whole migration; convert per §7 |
| **Mobile clients cannot update in lockstep** | `compare.php` gates every API deploy; drift is a release blocker |
| **Password rehashing is one-way** | Expected and safe; remove the legacy hasher on telemetry, not a schedule |

---

## 11. Not yet possible in this environment

Nothing has been run against real data. The legacy app has no `vendor/`, no
`app/config/parameters.yml`, and no reachable database, so these remain open:

- [ ] **Golden API capture** — needs a staging base URL and API credentials in `migration/capture-config.json`
- [ ] **Schema diff** — compare the ported mapping against the live `terminalbd` schema and confirm every one of the 335 entities matches its real table. Needs a real dump or a read-only connection. (`ammar_db.sql.gz` in the repo root is *not* usable: `file` identifies it as an HTML document, not gzipped SQL — probably a failed download.)
- [ ] **Verify the 24 serialized columns** actually contain `serialize()` output and not a mix of formats
- [ ] **Confirm `fos_user` column set** matches the reproduced `BaseUser` on the real database
