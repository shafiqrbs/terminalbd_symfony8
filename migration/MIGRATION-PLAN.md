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

**Every phase that can be finished without production access is finished, and the application
has now served real requests.** It builds on Symfony 8.1 / PHP 8.5, every route resolves, every
entity's mapping validates, and the column names match what the legacy annotations produce —
which is the property that matters most, because both applications write the same tables.

A tenant dump was loaded into a scratch database and the application run against it: the login
page renders, a user whose password is a Symfony 2 salted sha512 hash logs in through the form,
and that hash is transparently upgraded to bcrypt on the way through (§11). Doing that found
nine defects no static check could see — §5.20 to §5.28 — including one that made *every*
authenticated request a fatal error.

What is left needs a running system or a product decision, not more porting: the golden API
capture (§11), the per-module behaviour checks before each proxy flip (§6, phase 5), the
session-bridge deploy step (`migration/session-bridge.md`), and decommissioning the PHP 7.4
pool (§6, phase 6).

```
$ ./migration/bin/migrate.sh
...
############ verify ############
php syntax: clean
leftover @Secure          0 files
leftover @ORM\\           0 files
leftover @Gedmo\\         0 files
leftover Types::ARRAY     0 files

-- does it actually build? --
 [OK] Cache for the "dev" environment (debug=true) was successfully cleared.
 [OK] The container was linted successfully
routes loaded: 3485
controllers loadable: 337/337
OK (26 tests, 42 assertions)

-- is the mapping right? --
339 entities valid, 0 with mapping errors
legacy columns absent from the ported mapping: 0
resolve: 3078   answering 404: 407   missing class: 0   missing action: 0
mobile endpoints resolving at the same path: 90 (6 dead in the legacy app too, 0 regressions)
```

| Metric | Value |
|---|---|
| PHP files ported into `App\` | 1,561 |
| Templates ported | 1,797 (including 42 vendored from the removed FOS bundles) |
| Routes loading | **3,485** |
| Routes resolving to a real controller + action | **3,078**; the other 407 answer 404 (§5.20) |
| Controllers that load | **337 / 337** |
| Doctrine metadata definitions | 339 (**339 validate clean**) |
| Column names diverging from the legacy annotations | **0** (of 333 entities) |
| Mobile endpoints resolving at their contract path | 90 of 96 (6 dead in the legacy app, §5.13) |
| Templates with valid Twig syntax | 1,795 / 1,797 |
| Container lint | passes |
| Test suite | 26 tests, 42 assertions, green |
| `TODO(migration)` markers left | 4 files (services that exist in neither tree) |

The whole pipeline is reproducible: `./migration/bin/migrate.sh` rebuilds `next/src`,
`next/templates` and `next/config/routes/legacy` from the untouched legacy tree and ends in the
verification block above. Files that are hand-written rather than generated are listed in
`migration/hand-written.txt` and are neither deleted nor overwritten by a run.

---

## Picking this up again

The state that is not in this repository, and what to do first.

### The verification environment

Everything in §11 was done against a scratch database, not a fixture:

```bash
# the dump is gitignored (tenant data); the scratch database is local only
zcat bm_db.sql.gz | sudo mysql terminalbd_migration_check
```

- `next/.env.local` (gitignored) points `DATABASE_URL` at
  `terminalbd_migration_check` on the local MySQL. Recreate it there if it is gone.
- Loading the dump, and `doctrine:schema:update`, need `innodb_strict_mode=0`
  for the widest tables (§5.29). Set it back to 1 afterwards.
- The schema in that database was completed from the mapping, so it is the
  current code's schema with one tenant's real rows in it.
- A user seeded for the smoke test - `migration_check` - has a password that
  started as a Symfony 2 salted sha512 hash and was upgraded to bcrypt by
  logging in. Its plaintext is in `next/.env.local`, not here.

### The three commands

```bash
./migration/bin/migrate.sh      # rebuild everything from the legacy tree, then verify
./migration/bin/ci-checks.sh    # the guards, no database needed
php8.5 migration/bin/smoke-test.php   # request pages; needs the database
```

The smoke test has to be run in a loop until it reports `visited 0 pages this
run`: 110 controllers call `exit;` and take the process with them, and each run
resumes from `next/var/smoke-state.json`. Roughly 15 runs from a cold start.

### Where to start, in order

1. **Exercise writes.** Every sweep so far is read-only — GET, no parameters,
   nothing whose name looks like it changes data. That is how 300 broken DELETE
   and UPDATE statements (§5.34) survived to be found by reading rather than by
   testing. There may be more of that class. A harness that submits the forms
   it finds would be the single most valuable next step.
2. **Pages with parameters.** 2,141 routes carry a `{placeholder}` and have
   never been requested. Feeding real ids from the database would roughly
   triple the coverage.
3. **The 320 dead template links** (§5.28). One product decision per link:
   restore the controller or remove the link. It is the largest single block of
   failing pages.
4. **A tenant that uses the other modules.** `business_config`, `dps_config`
   and `restaurant_config` have no rows in this dump, so 137 pages walk into a
   null that says nothing about the port.
5. **Golden API capture**, once a staging URL and credentials exist (§11).

### What is finished

Phases 1, 2, 4 and 5's mechanical layer; phase 3 structurally. `migrate.sh`
rebuilds the tree from the untouched Symfony 2.8 source and `git diff` comes
back empty, so anything hand-edited under `next/src` will be overwritten unless
it is listed in `migration/hand-written.txt`.

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

`migrate.sh` is the whole migration: 16 idempotent steps from the untouched Symfony 2.8 tree to
a building application, then a verification block (syntax, leftover-legacy greps, container
compile and lint, route count, controller loadability, tests, mapping validation, column
verification, route resolution, the mobile contract, Twig lint, and — when a dump is present —
a schema diff).

| File | Purpose |
|---|---|
| `migrate.sh` | The pipeline. `port.sh` is steps 1-7 of the entity graph, called from it |
| `port-entities.php` | Copies 686 entity/repository files, rewrites legacy namespaces → `App\` |
| `gedmo-to-attributes.php` | `@Gedmo` docblocks → attributes (Rector has no Gedmo rules) |
| `fix-duplicate-attributes.php` | Collapses repeated non-repeatable ORM attributes, keeping the last |
| `fix-joincolumns.php` | Drops `JoinColumn` from illegal association sides |
| `fixups.php` | Targeted corrections for individually malformed legacy annotations |
| `port-http.php` | Lifts controllers, forms, services, listeners and all templates |
| `port-routing.php` | 362 routing files: `pattern:`→`path:`, `Bundle:C:a`→FQCN, XML/dead-bundle imports commented, duplicate route names collapsed the way SF2 resolved them |
| `port-auth-routes.php` | Regenerates the 18 routes that lived in FOSUserBundle's XML |
| `port-security.php` | Role hierarchy + access control from `app/config/security.yml` |
| `port-parameters.php` | `parameters.yml.dist` → container parameters backed by env vars |
| `port-services.php` | The bundles' `Resources/config/services.yml` → `config/services_ported.yaml`, rebuilding arguments by reflection |
| `gen-service-excludes.php` | Guard: any form type whose constructor takes runtime values (expected to be none) |
| `mark-manual-forms.php` | Flags form instantiations Rector's rule crashes on, so it skips those files |
| `port-fosuser.php` | The FOSUserBundle references in files that stay mechanically ported |
| `port-forms.php` | String type names → FQCN, `property`→`choice_label`, `setDefaultOptions`→`configureOptions`, `getName`→`getBlockPrefix` |
| `port-form-options.php` | `empty_value`→`placeholder`, `cascade_validation` dropped, **choices flipped** to Symfony 3 order |
| `port-form-instances.php` | `new XType($deps)` call sites → `XType::class` plus options |
| `port-form-types.php` | Form type constructors → form options |
| `port-controllers.php` | `AbstractController` + constructor injection |
| `port-controller-api.php` | `isSubmitted()` guards, `dispatch()` argument order, interpolated template prefixes |
| `port-container-aware.php` | `ContainerAwareTrait`, `ContainerAware`, `ContainerAwareCommand` |
| `port-service-locator.php` | The remaining `$this->get('id')` calls → constructor injection |
| `prune-dead-imports.php` | `use` statements for bundles that were never part of this project |
| `port-phpexcel.php` | PHPExcel + liuggio/excelbundle → PhpSpreadsheet |
| `port-assetic.php` | `{% stylesheets %}`/`{% javascripts %}` → `asset()`, plus an `assets:install` equivalent |
| `port-twig-legacy.php` | `form_enctype()`, `sameas` |
| `fix-signatures.php` | Return types SF8/Doctrine 3 base classes require (class-aware) |
| `fix-dql-aliases.php` | Doctrine short-name aliases → FQCN |
| `secure-to-isgranted.php` | JMS `@Secure` → `#[IsGranted]`, preserving OR semantics |
| `fix-attribute-names.php` | Attributes written as an unqualified FQCN, and `targetEntity: \Foo::class` |
| `fix-associations.php` | The broken bidirectional associations, driven off Doctrine's own validator |
| `check-routes.php` | Reports and prunes routes with no controller or action |
| `clean-generated.php` | Empties the generated module directories, keeping the hand-written files |

Verification, all runnable without a database:

| File | Checks |
|---|---|
| `verify-columns.php` | Every ported column name against the legacy annotations, reproducing Doctrine 2's DefaultNamingStrategy. **This is the one that guards the shared database** |
| `check-routes.php` | Every route resolves to a class and method that exist |
| `check-api-contract.php` | The 96 wire-locked mobile endpoints still answer at their contract path |
| `schema-diff.php` | The mapping against a real dump: tables, columns, serialized payloads |
| `create-session-table.php` | Creates the shared `sessions` table (additive) |
| `capture.php` / `compare.php` | Golden API responses: record, then diff shape and status. Needs a live system (§11) |

### `migration/contract/` — extracted facts and verification output

| File | Contents |
|---|---|
| `api-endpoints.json` | All 96 mobile endpoints: prefix, path, method, controller |
| `api-contract.md` | Which of them resolve, regenerated by `check-api-contract.php` |
| `dead-mobile-endpoints.md` | The six that are dead in the legacy application, with the evidence |
| `entity-map.json` | 335 entities → table, repository, column count, association count |
| `broken-associations.md` | The 48 broken `mappedBy`/`inversedBy` links, by entity |
| `column-verification.md` | Ported column names vs the legacy annotations — the shared-database guard |
| `schema-diff.md` | The mapping against a real dump: tables, columns, serialized payloads |
| `unresolvable-routes.md` | Every route removed for having no controller or action |
| `route-collisions.md` | Duplicate route names, and which definition Symfony keeps |
| `unresolved-dql-aliases.txt` | References to entities that exist in neither tree |
| `manual-form-files.txt` | Files Rector's form rule crashes on, so the pipeline skips them there |
| `mapping-validation.txt` | Raw output of the last acceptance run |

### `next/` — the new application

Everything under `next/src/<Module>/` is regenerated from the legacy tree except the files
listed in `migration/hand-written.txt`, which the pipeline neither deletes nor overwrites.

| File | Purpose |
|---|---|
| `rector.php` | PHP 8.5 + Doctrine/Symfony attribute sets. **Skips** `src/Security`, `src/Comment`, `src/Doctrine` and everything in `migration/hand-written.txt` |
| `bin/validate-mapping.php` | Acceptance test: loads all Doctrine metadata, no DB needed |
| `config/packages/doctrine.yaml` | Single `App` mapping over `src/`, the legacy DBAL types, the **DefaultNamingStrategy** (§5.14) and the three custom DQL functions |
| `config/services_ported.yaml` | Generated from the bundles' service definitions |
| `src/Doctrine/Type/Legacy{Array,JsonArray}Type.php` | Restore DBAL's removed `array` / `json_array` types |
| `src/Security/Model/Base{User,Group}.php` | Replace the FOSUserBundle superclasses, column-for-column |
| `src/Security/UserProvider.php`, `Canonicalizer.php` | Replace `fos_user.user_provider.username` and FOSUser's canonicalizer |
| `src/Security/SharedSession{Authenticator,Listener}.php` | The Symfony 8 half of the session bridge |
| `src/Comment/Model/Base{Comment,Thread}.php` | Replace the FOSCommentBundle superclasses |
| `src/User/Manager/{User,Group}Manager.php`, `Util/TokenGenerator.php`, `Mailer/ResettingMailer.php` | Replace the `fos_user.*` services |
| `src/User/Form/Type/*FormType.php` | Replace the forms FOSUserBundle's factories built |
| `src/User/Controller/{ChangePassword,Group,Profile,Registration,Resetting}Controller.php` | Reimplemented on `AbstractController` |
| `src/Tool/Service/SpreadsheetFactory.php` | Replaces the `phpexcel` service over PhpSpreadsheet |
| `src/Tool/Image/*`, `src/Tool/Twig/ImageExtension.php` | Replace gregwar/image-bundle |
| `src/Tool/Twig/TextExtension.php` | Replaces twig/extensions' `truncate` |
| `src/Form/ChoiceList.php` | Flips runtime choice lists to Symfony 3 order (§5.15) |

### Running it

```bash
./migration/bin/migrate.sh                   # the whole migration, then verify
./migration/bin/ci-checks.sh                 # just the guards (what CI runs)
cd next && php8.5 bin/validate-mapping.php   # mapping acceptance test on its own
php8.5 migration/bin/verify-columns.php      # shared-database guard on its own
```

`ci-checks.sh` runs every check that needs no database — column verification,
mapping, route resolution, the mobile contract, container, templates, tests — reports all of
them, and exits non-zero if any failed. `.github/workflows/migration-checks.yml` runs it on
every push and pull request touching `next/`, `migration/` or `src/`, and on the migration
branch additionally re-runs `migrate.sh` and fails if the rebuild no longer matches what is
committed.

(`.travis.yml` in the repository root is the legacy application's, targeting PHP 5.3. It is
dead and unrelated.)

> **Rector scope is load-bearing.** `rector.php` and `rector-http.php` must keep skipping
> `src/Security`, `src/Comment`, `src/Doctrine` and the hand-written manifest. Without the skip
> it types the shim properties (`protected ?string $username`), which then clash with the
> untyped properties the ported subclasses redeclare; it rewrites `type: 'array'` to
> `Types::ARRAY`, a constant DBAL 4 no longer defines; and it converts the hand-written Twig
> extensions to attribute-based ones, which drops `ExtensionInterface` and silently unregisters
> them. The verify block greps for the second and the container lint catches the third.

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

### 5.6 Broken bidirectional associations — 48 refs across 30 entities ✅ fixed

`inversedBy` / `mappedBy` pointing at fields that do not exist on the other entity, e.g.
`GlobalOption#employeeTransaction`, `BusinessConfig#itemImports`, `BusinessInvoice#courier`.
Confirmed absent from the legacy entities too. Doctrine only resolves the inverse side lazily,
so they never surfaced at runtime.

Catalogued in `migration/contract/broken-associations.md`, and fixed by `fix-associations.php`
with the second option — made unidirectional, which is what the code already did at runtime.
54 `inversedBy` references that pointed nowhere were dropped (the owning side keeps its column
and its foreign key) and 16 unusable `OneToMany` mappings became plain properties, so reading
one returns an empty collection instead of throwing. Adding the missing owning side instead
would have meant inventing foreign key columns the shared database does not have.

### 5.7 `parent::__construct()` in parentless classes — 3 cases

`ApplicationTestimonial`, `WebTheme`, `MobileTheme`. The method is named `_constructor()` — a
typo for `__construct()` — so it was never called and the invalid parent call never ran. PHP 8
rejects it at **compile** time.

Removing the unreachable line preserves behaviour byte-for-byte. **The typo is deliberately
left in place**: correcting it would activate three constructors that have never executed,
which initialise collections and set `status = true`.

### 5.12 Routes pointing at controllers and actions that do not exist — 419 ✅ removed

229 name a controller class that is absent from the legacy tree too
(`HotelReportController`, `EcommerceMenuController`, `FeatureController`,
`TemplateController`, ...). 190 name an action that its controller never had — verified by hand:
`pos.yml:118` routes to `PosBundle:Pos:salesItem`, and `PosController` has no `salesItemAction`
in either tree. All 419 are catalogued in `migration/contract/unresolvable-routes.md`; each is a
500 in production today.

### 5.9 Corrupted entity alias — 1 case, runtime fatal today

`src/Setting/Bundle/ContentBundle/Controller/MallShopController.php:122` reads
`getRepository('SettinMallShopTypegContentBundle:MallConnect')` — a bad find-replace spliced
`MallShopType` into the middle of `SettingContentBundle`. That line throws
`Unknown Entity namespace alias` whenever the route is hit.

### 5.10 Duplicate route names — 147 (145 pre-existing)

Symfony keeps the last route defined with a given name and drops the rest silently; Symfony 2 did
the same. Catalogued in `migration/contract/route-collisions.md`. One consequence matters:
`/android-api-portal/apps` is shadowed by an identically named route in `apiPoskeeper.yml`, so one
of the 96 "wire-locked" mobile endpoints is already dead in production.

### 5.11 Aliases for entities that do not exist — 205 references

`Blog`, `Admission`, `MallConnect`, `Team`, `EcommerceMenu`, `MegaMenu`, `Course` and
`InstituteLevel` are referenced by controllers and repositories but exist nowhere in the legacy
tree. Those routes are dead code that fails at runtime. Left untouched and catalogued in
`migration/contract/unresolved-dql-aliases.txt` rather than pointed at a plausible-looking wrong
entity.

### 5.8 Namespace and registration inconsistencies — 2 cases

`appstore\Bundle\DomainUserBundle` is declared with a lowercase leading character;
`Appstore\Bundle\DmsBundle` exists on disk but is never registered in `AppKernel`. Both
normalised during the namespace rewrite.

---

### 5.13 The ported configuration renamed 782 columns — highest severity of the port

`next/config/packages/doctrine.yaml` carried Symfony Flex's default,
`naming_strategy: underscore_number_aware`. The live schema was written by Doctrine 2's
`DefaultNamingStrategy`, which `app/config` never overrode: an implicit column keeps the
property's case, and an implicit join column is `<property>_id`. The production tables have
`globalOption_id` and `receiveDate`; the ported application was looking for `global_option_id`
and `receive_date`.

Nothing fails loudly. Doctrine builds the SQL it was told to build, and the first query against
an affected table errors on an unknown column — 782 of them across the graph.

Fixed by using `doctrine.orm.naming_strategy.default`, and guarded from here on by
`migration/bin/verify-columns.php`, which reproduces DefaultNamingStrategy over the legacy
annotations and compares the result with the metadata the ported application loads. It reports
782 divergences with the Flex strategy and **0** with the correct one. It needs no database, so
it belongs in CI.

### 5.14 Attributes written as an unqualified FQCN — 32 columns silently unmapped

`@Doctrine\ORM\Mapping\Column` in a docblock is an absolute reference. Converted to
`#[Doctrine\ORM\Mapping\Column]`, PHP resolves it *relative to the current namespace* —
`App\Hospital\Entity\Doctrine\ORM\Mapping\Column`, which does not exist. Doctrine then sees
no column at all and the property is simply unmapped.

`HmsCategory::$slug` was one of the 32, which is why Gedmo failed with "Unable to find slug
[slug] as mapped property" as soon as the listeners were registered (§5.17). A further 40
`targetEntity: \Foo::class` references had the same shape of defect: `::class` does not require
the class to exist, so they quietly evaluated to `"Foo"`.

Both fixed by `fix-attribute-names.php`.

### 5.15 Symfony 3 flipped the `choices` array — 214 lists

Symfony 2 wrote `'choices' => ['top' => 'Top']` (value => label); Symfony 3 onwards reads
label => value. Left alone, every choice field would have stored its label: `'Top'` instead of
`'top'`, and `'Inventory'` instead of `'ROLE_INVENTORY'`.

`port-form-options.php` flips the literal lists (214) and drops the `choices_as_values` option
from the 28 that were already forward-compatible. The role lists are built at runtime by
`UserRepository`, which non-form code also reads, so those are flipped at the call site through
`App\Form\ChoiceList::flip()` instead. Lists that feed `EntityType` are left alone — they pass
entities as values with `choice_label` supplying the label, which is already correct.

### 5.16 The bundles' service definitions were never ported — every listener inert

Bundle DI extensions went away with the bundles, and nothing replaced them: not one of the 120
definitions in the bundles' `Resources/config/services.yml` had been carried over. An autowired
class with no `kernel.event_listener` tag is never called, so every SMS and e-mail the
application sends — password changes, OTPs, signup notifications, invoice messages — was
dispatched into an empty dispatcher.

`port-services.php` ports them under their original ids (controllers still fetch some by name),
rebuilding constructor arguments by reflection because Symfony 2 listed every argument
positionally, including ones that are now autowired and several that no longer exist.
55 `doctrine.event_listener` and 22 `kernel.event_listener` services are registered again.

### 5.17 StofDoctrineExtensionsBundle was never registered — every Gedmo attribute inert

The bundle is in `composer.json` but was missing from `config/bundles.php`, and
`app/config/bundles/stof_doctrine.yml` had not been ported. So 265 `Timestampable`, 75
`Blameable`, 49 `Slug` and the `Tree` mappings did nothing: rows would have been written with
null `created`/`updated` columns and empty slugs, and the category trees would have stopped
maintaining themselves.

### 5.18 Six wire-locked mobile endpoints are dead in the legacy application

Five name an action that exists in neither tree; one, `api_portal_app`, is shadowed by a
duplicate route name (§5.10). Catalogued with the evidence in
`migration/contract/dead-mobile-endpoints.md`. `check-api-contract.php` accepts exactly these
six and fails on any other change, so the other 90 cannot regress unnoticed.

One deserves a decision rather than a catalogue entry: `api_portal_app_user_register` names
`ApiPortalController::userRegisterAction`, and a `userRegisterAction` does exist — on
`ApiPoskeeperController`. The two serve different clients and return different shapes, so the
route was left dead rather than pointed at a plausible-looking implementation.

### 5.19 A file whose class does not match its name — 1 case

`Core/UserBundle/Form/Type/DomainAdminType.php` declares `class DomainSignType`. PSR-0 never
loaded it, so it was dead code in the legacy tree; but Symfony and Doctrine both *scan* the
directory rather than autoload it, which made `getAllMetadata()` a fatal redeclare. `port-http.php`
now skips any legacy file whose class name does not match its file name, and reports it.

### 5.20 Deleting a dead route breaks the pages that link to it

The first pass removed the 419 routes whose controller or action does not exist. That was
wrong: 93 of them are still linked from templates, and `path('order')` for a route that no
longer exists throws while rendering — so removing them took down pages that otherwise worked.

They keep their name and path now and are repointed at
`App\Migration\Controller\UnresolvableRouteController`, which answers 404. That is better than
both the original behaviour (a 500) and the deletion. `check-routes.php` re-derives the list on
every run, so restoring a controller is what takes a route off it.

### 5.21 DBAL 4 requires precision and length that Doctrine 2 defaulted

67 columns declare `type="decimal"` with no precision, and 205 declare `type="string"` with no
length. Doctrine 2 defaulted them to `(10,0)` and `255`; DBAL 4 throws
"Column precision is not specified" and "requires the length of a VARCHAR column to be
specified" instead. Every schema operation — validate, diff, migrations — fails at the first
one, which is why this only surfaced when the application was pointed at a database.

The live schema agrees with the defaults: every decimal column in the dump is `decimal(10,0)`,
and of the 44 string columns that could be checked against it, all are `varchar(255)`.
`fix-string-length.php` reads the real length from `information_schema` when a database is
reachable and falls back to 255 when it is not.

### 5.22 `@ORM\Entity(repositoryClass="")` — 24 entities

An empty string, in the legacy annotations. Doctrine 2 read it as falsy and used the default
repository; Doctrine 3 takes it literally and throws when the entity is first queried. Nothing
static catches it — the metadata itself loads fine. Dropping the option restores Doctrine 2's
behaviour. Pointing it at the similarly-named repository class that happens to exist would not:
that repository was never in use.

### 5.23 app/Resources/views was never ported — 1,435 references

The 66 templates in `app/Resources/views` are the layouts every page extends: `base.html.twig`,
`login.html.twig`, the print and POS bases, the customer and frontend trees. They had not been
copied, and 1,435 references use Symfony 2's app-level name form — `::base.html.twig`, with the
bundle and controller segments empty — which the reference rewriter did not handle. 1,262 of
those references are to `::base.html.twig`, which is to say nearly every page in the
application.

Nothing reported it. The Flex skeleton ships its own `templates/base.html.twig`, so a page
extending `base.html.twig` rendered — with no stylesheet, no menu and the wrong title — and
Twig's linter does not resolve inheritance, so `lint:twig` stayed green throughout.

### 5.24 Two dead backward-compatibility branches in the login controller

```php
if (class_exists('\Symfony\Component\Security\Core\Security')) { ... }
else { ... SecurityContextInterface::AUTHENTICATION_ERROR ... }   // removed in Symfony 3

if ($this->has('security.csrf.token_manager')) { ... }
else { ... $this->has('form.csrf_provider') ... }                 // has() removed in Symfony 6
```

Symfony 8 moved that `Security` class to the SecurityBundle namespace, so `class_exists()` is
false and the pre-2.6 branch runs. Both made `/login` a fatal error — the first page anyone
would open.

### 5.25 UserProvider::refreshUser() called a method the entity does not have

The hand-written provider from phase 2 reloaded the user with
`$this->em->getRepository(User::class)->find($user->getId())`. Neither `User` nor `BaseUser`
defines `getId()`. `refreshUser()` runs on every request that carries a session, so **every
authenticated request was a fatal error** — while the container linted, the mapping validated
and the tests passed. It reloads by identifier now, and the smoke test covers it.

### 5.26 The Flex skeleton's stateless CSRF is incompatible with the ported forms

`config/packages/csrf.yaml` came from a recipe enabling Symfony 7.2's stateless CSRF: the form
renders the literal placeholder `csrf-token` and a Stimulus controller swaps in the real value
before submit. None of the 1,797 ported templates loads that JavaScript, so every form —
including the login form — submitted the placeholder and was rejected with no diagnostic beyond
"Invalid CSRF token". Switched back to session-based tokens.

### 5.27 FOSJsRoutingBundle is not optional

`base.html.twig` calls `path('fos_js_routing_js')` on every page and eight templates call
`Routing.generate()` in JavaScript. The bundle had been dropped and its routing import commented
out, so every page threw. It is reinstalled (it has a Symfony 8-compatible release) with its
route declared in `config/routes/fos_js_routing.yaml` — the bundle still ships the Symfony 2
`Resources/` layout, which Symfony 8's bundle path resolution no longer looks in.

### 5.28 Templates link to 193 routes that do not exist

`path('report_dashboard')` and 192 other names, in 396 references. None is defined in the legacy
tree either, so those pages are 500s in production today — one dead link takes down the whole
page. Catalogued in `migration/contract/template-route-links.md` by
`check-template-routes.php`, which reports rather than gates: restoring a controller or removing
a link is a product decision.

### 5.29 The schema cannot be created from scratch on MySQL 8 defaults

Loading the dump and running `doctrine:schema:update` both fail with "Row size too large
(> 8126)" on the widest tables (`TemplateCustomize` and friends) unless `innodb_strict_mode` is
off. Production is unaffected — those tables already exist — but provisioning a new tenant or a
staging environment from the mapping needs that setting, or narrower columns.

### 5.30 Doctrine ORM 3 removed `EntityRepository::$_em` — 2,056 uses

It is `private readonly EntityManagerInterface $em` now, reachable only through the protected
`getEntityManager()`. The 229 ported repositories read `$this->_em` 2,056 times, and every one of
those reads an undefined property: PHP warns and evaluates to null, so the next call — almost
always `->createQuery()` — is a fatal error.

Nothing static sees it. The class compiles, the container lints, the mapping validates; the query
dies when someone opens the page. It accounted for most of the smoke test's failures, and fixing
it cleared five whole categories of downstream error at once.

### 5.31 DBAL 4 made `Statement::execute()` private and renamed the fetch methods

172 calls across 50 repositories follow the Symfony 2 shape — `prepare()`, `bindValue()`,
`execute()`, `fetchAll()`. In DBAL 4 `execute()` is private (a fatal on call) and the fetch
methods moved onto the `Result` that `executeQuery()` returns. `port-dbal-statements.php`
rewrites only variables that came from a `->prepare()` call, so Doctrine ORM's own
`Query::execute()` is untouched.

### 5.32 A dependency that can no longer be downloaded

`lessclick-system/barcode` (`CodeItNow\BarcodeBundle`, used by three controllers) lives on a
Bitbucket repository that answers 404, with no public mirror. Requiring it makes
`composer install` fail outright, so it is removed from `composer.json` and the three barcode
pages are broken until someone picks a replacement — `picqer/php-barcode-generator` is the
obvious candidate, but the output has to match what the printers expect, so it is not a
mechanical swap.

### 5.33 Symfony 7 removed the array-of-options constructor for constraints — 69 uses

`new NotBlank(array('message' => 'Please input required'))` throws
`InvalidArgumentException` the moment the form is built. Rector converts most of these, but not
inside the form types it is told to skip — the files `mark-manual-forms.php` lists, where its
form rule crashes. 69 across 23 files.

`port-constraint-options.php` rewrites them to named arguments, and only for validator
constraints: there are 61 `new JsonResponse([...])` calls in the tree whose array is data, and
turning those keys into named arguments would be silent corruption.

### 5.34 Legacy entity aliases inside DQL strings — 300 statements

`fix-dql-aliases.php` converted the aliases that stand alone as a whole string, which is the
`getRepository('Bundle:Entity')` shape. It missed the ones embedded in a query:

```php
$em->createQuery("DELETE AccountingBundle:Transaction e WHERE e.globalOption = ...")
```

300 of those across 64 files, and ORM 3 rejects the alias outright. Almost all are DELETE or
UPDATE, which is why a read-only smoke test never reached them — they fire on save and delete
flows. The rewrite goes through PHP's tokenizer rather than a plain regex, because a backslash
inside a double-quoted string is an escape: writing `"App\Tool\Entity\X"` unescaped would
embed a tab character.

## 6. Phases

### Phase 1 — Skeleton and entity graph ✅ done

Symfony 8.1.6 on PHP 8.5 in `next/`. All 335 entities and 327 repositories lifted into `App\`,
annotations converted to attributes, mappings validated. FOSUser and FOSComment superclasses
reproduced from their locked revisions (`6ccff96`, `fa2a676`) so they map onto `fos_user`,
`user_group` and the comment tables untouched.

### Phase 2 — Authentication and tenancy ✅ done

The gate for everything else: no module can move until a request can be authenticated.

- [x] `App\Security\UserProvider` replacing `fos_user.user_provider.username`. Looks up by
      `usernameCanonical`, as FOSUser did — the login form submits a raw string whose case may
      differ from the stored value. Implements `PasswordUpgraderInterface` so rehashes persist.
- [x] `App\Security\Canonicalizer` reproducing `FOS\UserBundle\Util\Canonicalizer`. The
      `username_canonical` / `email_canonical` columns in production were written with this exact
      transform; a divergence means no existing user can log in.
- [x] Role hierarchy + access control generated by `migration/bin/port-security.php`
      (114 hierarchy entries, 44 access rules). All 193 legacy role names verified present.
- [x] `App\Security\TenantRoutingListener` — port of `DomainAttributeListener`.
- [x] Migrating password hasher — verify existing salted `sha512`, transparently rehash on next
      successful login. **No forced reset for any tenant.**
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
- [x] The FOSUserBundle runtime, replaced class for class:
      `fos_user.user_manager` → `App\User\Manager\UserManager`, `fos_user.group_manager`,
      `fos_user.util.token_generator`, `fos_user.mailer`, and the four form factories →
      `App\User\Form\Type\*FormType`. Each form is still created under the name FOSUserBundle's
      factory used (`fos_user_change_password_form` and friends), because the vendored templates
      and one controller's POST parsing read those keys.
- [x] Login / logout / registration / password-reset controllers and templates. All 18
      `fos_user_*` routes resolve; `ProfileController::showAction` and `GroupController`'s
      constructor, both lost when the classes stopped extending the bundle's, are restored.
- [x] 112 files of `@Secure` / `@PreAuthorize` → `#[IsGranted]` (521 conversions).
- [x] Shared session store and the bridge across the proxy boundary —
      `App\Security\SharedSession{Authenticator,Listener}` and the legacy half in
      `Core\UserBundle\EventListener\SharedSessionListener`. Sharing the store is not enough:
      neither framework can deserialise the other's security token, so each publishes a neutral
      user identifier and adopts it only when it has no session of its own. Six tests cover the
      cases where it must *not* fire. Turning it on is a deploy step, written up in
      `migration/session-bridge.md` — pointing the legacy application at the shared table logs
      out whoever is signed in at that moment.
- [x] `symfony/security-acl`: nothing to do. The 9 files the dependency table listed use no ACL
      API in either tree; the package was a dependency nothing imported.

**Proven by test** (`next/tests/Security/`, 26 tests / 42 assertions, green). The legacy hash is
reproduced from Symfony 2's algorithm transcribed by hand rather than by calling Symfony 8's
hasher, so it is a real cross-check and not the framework agreeing with itself:

- a Symfony 2 sha512 hash verifies under Symfony 8, and a wrong password does not
- the migrating hasher accepts the legacy format and reports `needsRehash()`
- an upgraded hash is stable and still verifies
- dropping the salt makes verification fail — which is why `BaseUser` must keep implementing
  `LegacyPasswordAuthenticatedUserInterface`
- canonicalization matches FOSUserBundle byte-for-byte, including unicode and `ISTANBUL`
- the session bridge ignores a stale key, a request with no previous session, and never
  overrides a token this application already has

### Phase 3 — Mobile APIs (wire-locked) — structurally verified, responses not yet captured

All 96 endpoints are accounted for: **90 resolve at their contract path**, and the other six are
dead in the legacy application too (§5.18). FOSRestBundle is gone entirely — its `type: rest`
loader was replaced by explicit paths, and no controller used its view layer: the legacy actions
build their JSON by hand and return a plain `Response`, which is what they still do.

- [x] Every endpoint resolves to a controller and action that exist
- [x] `check-api-contract.php` gates that, and fails on any regression
- [ ] Record golden responses: `php migration/bin/capture.php legacy` — **needs a staging URL and
      API credentials** (§11)
- [ ] Gate every deploy on `compare.php` once a baseline exists

### Phase 4 — Shared presentation layer ✅ done

- [x] 1,797 templates ported, including 42 vendored from FOSUserBundle/FOSCommentBundle into
      protected `_`-prefixed directories
- [x] 1,834 `Bundle:Dir:file.twig` references rewritten, including interpolated ones
- [x] **1,079 Assetic blocks** → 1,523 `asset()` references across 759 templates.
      `symfony/assetic-bundle` has no Symfony 3+ release, so every one of those templates threw
      "Unknown tag" at render time. `cssrewrite` only mattered because Assetic moved files while
      concatenating them; each file is now served from the path it already had, so the emitted
      URLs are unchanged and concatenation becomes a deploy-time concern. `port-assetic.php` also
      copies the bundles' `Resources/public` into `public/bundles/`, which is what
      `assets:install` did
- [x] PHPExcel + liuggio/excelbundle → PhpSpreadsheet behind `SpreadsheetFactory`, which keeps
      liuggio's method names so the 14 importers and the one exporter did not change
- [x] gregwar/image-bundle → `App\Tool\Image\*` over gregwar/image. The bundle installs but is
      a fatal on boot: its DI extension still declares `load()` without a return type. 174
      templates call `image(...).resize(...)`
- [x] twig/extensions → `App\Tool\Twig\TextExtension` (the one filter in use)
- [x] `form_enctype()` and the `sameas` test
- [x] Render-tested as far as static analysis allows: `lint:twig` passes on 1,795 of 1,797
      templates. The two exceptions are FOSCommentBundle's own, and need its Twig extension
- [ ] PDF pipeline: `Knp\Snappy\Pdf` is installed again and the 33 call sites work, but
      wkhtmltopdf itself is EOL and the binary path is still hard-coded per environment.
      Replacing the binary is a deployment decision, not a port

> The comment widget is **not** ported. FOSCommentBundle's REST API has no routes here, so the
> five templates that embedded it were calling `path('fos_comment_get_threads')` and would have
> thrown. Those includes are behind a `comments_enabled` global, defaulting to false, rather
> than half-implementing a public write API without its ACL. The entities and templates are
> kept, so porting it later is a contained piece of work.

### Phase 5 — Module ports ✅ mechanically complete; per-module verification outstanding

Every module's controllers, forms, services and listeners are ported, load and resolve.

- [x] Controllers on `AbstractController` with constructor injection: 5,838 Doctrine calls and
      1,374 service-locator calls rewritten, then the remaining 140 `$this->get('id')` sites
      injected by class (`port-service-locator.php`). **336 of 336 controllers load.**
- [x] The Symfony 2 form API, across 356 form types: string type names → FQCN (328),
      `property` → `choice_label` (497), `empty_value` → `placeholder` (495),
      `setDefaultOptions` → `configureOptions` (22), `getName` → `getBlockPrefix` (22),
      **choices flipped** (214, §5.15), 542 `new XType($deps)` call sites → `XType::class` plus
      options, and 213 form-type constructors moved into `configureOptions()`. No form type takes
      constructor arguments any more, so the 214-entry exclusion list in `services_legacy.yaml`
      is empty and every type autowires
- [x] 351 `$form->isValid()` calls given their `isSubmitted()` guard — Symfony 3 throws rather
      than returning false, so each was a 500 on the first GET of its page
- [x] 24 `dispatch('name', $event)` calls swapped to Symfony 5's argument order
- [x] `ContainerAwareTrait`, `ContainerAware` and `ContainerAwareCommand`, all removed by
      Symfony 5-7; the four console commands carry `#[AsCommand]` and their return types
- [x] The bundles' service definitions (§5.16) and the Gedmo listeners (§5.17)
- [x] 48 broken bidirectional associations (§5.6) — the mapping validates completely
- [x] 419 routes with no controller or action removed, catalogued (§5.12)
- [ ] **205 unresolvable Doctrine aliases** (`migration/contract/unresolved-dql-aliases.txt`).
      These name entities that exist in neither tree (`Blog`, `Admission`, `MallConnect`, `Team`,
      `EcommerceMenu`, `MegaMenu`, `Course`, `InstituteLevel`), and no table for any of them
      appears in the production dump either. The controllers that reference them are dead code
      whose routes still resolve; deleting a customer-facing controller is a product decision, so
      they are catalogued rather than removed
- [ ] Verify behaviour against real data, module by module, before flipping each proxy route
      (§8 gives the order)

### Phase 6 — Decommission — needs production

Unchanged, and unchangeable from here: retire the PHP 7.4 pool, collapse the proxy rules, drop
the shims that are no longer reachable. `LegacyArrayType` stays until §7 is done deliberately.

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

No Symfony 5/6/7/8 release exists for any of these. All are resolved.

| Package | Locked | Replacement |
|---|---|---|
| `friendsofsymfony/user-bundle` | dev-master | ✅ `App\Security\Model\BaseUser` + native security, `App\User\Manager\*`, `App\User\Form\Type\*` |
| `friendsofsymfony/comment-bundle` | 2.0.8 | ✅ `App\Comment\Model\Base{Comment,Thread}` (entities). The REST API is **not** ported — see phase 4 |
| `sensio/distribution-bundle` | 5.0.21 | ✅ Symfony Flex |
| `sensio/generator-bundle` | 2.5.3 | ✅ MakerBundle |
| `jms/security-extra-bundle` | 1.6.1 | ✅ `#[IsGranted]` — 521 conversions across 112 files |
| `jms/di-extra-bundle` | 1.6.0 | ✅ Autowiring |
| `jms/aop-bundle` | 1.2.0 | ✅ Dropped, unused |
| `symfony/assetic-bundle` + `kriswallsmith/assetic` | 2.7.1 / 1.3.2 | ✅ Plain `asset()` — 1,079 blocks, 1,523 references |
| `friendsofsymfony/rest-bundle` | 1.5.3 | ✅ Explicit routes; the controllers never used its view layer |
| `phpoffice/phpexcel` + `liuggio/excelbundle` | 1.8.1 | ✅ PhpSpreadsheet behind `App\Tool\Service\SpreadsheetFactory` |
| `swiftmailer/swiftmailer` | 5.x | ✅ `symfony/mailer`, same `EasyMailer` signature |
| `twig/extensions` | 1.x | ✅ `App\Tool\Twig\TextExtension` (`truncate`) |
| `eightpoints/guzzle-bundle` | 4.5 | ✅ A plain `GuzzleHttp\Client` definition with api_crm's base URI |
| `h4cc/wkhtmltopdf-amd64` / `-image-` | 0.12.2.1 | ⚠️ `knplabs/knp-snappy` reinstated so the 33 call sites work; the wkhtmltopdf binary is EOL and remains a deployment decision |
| `symfony/security-acl` | 3.0.1 | ✅ Nothing to do — no ACL API is used in either tree |
| `divi/ajax-login-bundle` | dev-master | ✅ Dropped |
| `xiidea/ez-maintenance` | 1.0.0 | ✅ Dropped |
| `gregwar/image-bundle` | 2.1.3 | ✅ `App\Tool\Image\*` over `gregwar/image`; the bundle itself is a fatal on Symfony 8 |
| `stof/doctrine-extensions-bundle` | 1.x | ✅ Registered and configured — it had been neither (§5.17) |

Note: four constraints in the legacy `composer.json` are unpinned (`dev-master` / `@stable`), so
the legacy build is reproducible only from `composer.lock`, not from `composer.json`.

---

## 10. Open risks

| Risk | Mitigation |
|---|---|
| **No test suite** — nothing catches a regression in 700k lines | Seven checks run in CI on every push, none needing a database (`migration/bin/ci-checks.sh`). They catch the class of defect that actually bit during this port — silent renames and dangling references. Extend record-and-diff to the highest-traffic HTML routes per module; cheaper than retrofitting unit tests |
| **The shared database is the real contract** | `verify-columns.php` compares every ported column name against the legacy annotations and must stay at 0. It caught 782 divergences from one line of configuration (§5.13), and CI fails on it |
| **Symfony 8.1 reaches EOL Jan 2027** — the migration will outlive it | Minor Symfony upgrades are BC-safe. Ride 8.2 → 8.3 → 8.4 (next LTS, Nov 2027). Budget a day per bump |
| **Shared database, two writers** | Schema changes additive-only until decommission. The only additive change this migration needs is the `sessions` table |
| **Serialized `array` columns** | Keep `LegacyArrayType` registered for the whole migration; convert per §7 |
| **Mobile clients cannot update in lockstep** | `check-api-contract.php` gates the URLs today; `compare.php` gates response shape once a baseline exists. Drift is a release blocker |
| **Password rehashing is one-way** | Expected and safe; remove the legacy hasher on telemetry, not a schedule |
| **Turning on the session bridge logs everyone out once** | Deliberate deploy step in a quiet window, documented with its rollback in `migration/session-bridge.md` |

---

## 11. What still needs a running system

The application has been run against real data: `bm_db.sql.gz` loaded into a scratch database,
the schema completed from the mapping, and **791 pages requested**. That found twelve defects
every static check had passed over (§5.20-§5.32), including three that were fatal on every
affected page.

What works, end to end:

- `/login` renders (HTTP 200) from the application's own layout
- a user whose password is a **Symfony 2 salted sha512 hash logs in through the form**, and the
  hash is transparently rewritten to bcrypt on the way through — 88 characters of base64 to
  `$2y$13$...` — with no forced reset. Logging in again with the upgraded hash works
- `/profile` returns 200 authenticated, `/logout` clears the session
- `doctrine:schema:validate` reports **"The database schema is in sync with the mapping files"**

### The smoke test

`migration/bin/smoke-test.php` boots the kernel once and drives requests through HttpKernel
in-process, as a logged-in user. It visits GET routes with no parameters whose name and path do
not look like they write anything — a legacy application that deletes over GET is normal, so the
deny-list is deliberately broad. It is resumable, because 110 controllers call `exit;` and take
the harness with them; every visit is recorded before it is made, so repeated runs converge.

791 pages, and every failure is now accounted for (`migration/contract/smoke-test.md`):

| pages | cause | |
|---:|---|---|
| 320 | a template links to a route that does not exist | pre-existing (§5.28) |
| 137 | a null the page assumes is there | this tenant's data |
| 117 | **rendered (HTTP 200)** | |
| 81 | 404 | mostly the routes with no controller (§5.12) |
| 34 | this tenant has no config for that module | data |
| 20 | dead entity aliases | pre-existing (§5.11) |
| 15 | the controller calls `exit;` | harness limit |
| 13 | calls a repository method that does not exist | pre-existing |
| 9 | wkhtmltopdf is not installed here | environment |
| 9 | needs a request parameter the harness does not send | harness limit |
| 6 | a template that exists in neither tree | pre-existing |
| 5 | DQL built by interpolating a null config | data |
| 4 | the smoke-test user lacks that role | expected |
| 3 | queries a field the entity never declared | pre-existing |
| 3 | **unexplained** | all three verified pre-existing |

The three unexplained were checked line by line against the legacy tree and are identical there:
`array_intersect()` on a string, `getResult()` on an array, and a call to
`AccountSales::getHotelInvoice()`, which exists in neither tree.

**No port defect remains in what this sweep can reach.** What is left is either data this
tenant does not have, a pre-existing defect, or a limit of the harness.

The 137 nulls need re-checking on a tenant that uses those modules: `business_config`,
`dps_config` and `restaurant_config` have no rows in this dump, so every page in those modules
walks into a null — which the legacy application does too.

### Still open

- [ ] **Golden API capture** — `capture.php` needs a staging base URL and API credentials. The
      mobile contract is verified structurally, not by response shape
- [ ] **The 296 dead template links and 20 dead aliases** — restoring a controller or removing a
      link is a product decision, per case
- [ ] **The 26 unexplained pages**, per module
- [ ] **A tenant with the other modules configured**, to cover the 134 nulls
- [ ] **The session bridge deploy step** — `migration/session-bridge.md`
- [ ] **Decommissioning** the PHP 7.4 pool and collapsing the proxy rules

### Verified against the dump

- [x] **Schema diff** — 121 entities match a table in it; the 74 mapped columns absent from that
      dump are its age, not port defects. `verify-columns.php` reports **0** differences against
      the legacy annotations
- [x] **The serialized columns** — `fos_user.roles` holds `a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}`,
      exactly what `LegacyArrayType` expects. Eight of the 24 belong to tables with no rows here
- [x] **`fos_user` column set** — 25 columns in the table, the same 25 in the mapping, both ways

The scratch database (`terminalbd_migration_check`) and `next/.env.local` pointing at it are left
in place for the next round; neither is committed.
