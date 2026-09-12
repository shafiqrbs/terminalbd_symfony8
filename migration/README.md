# migration/ on `master` is a record, not a working directory

Nothing in here runs on this branch.

These scripts port the Symfony 2.8 application into the Symfony 8.1 one. They read
the legacy source — `src/` as it was (Core, Setting, Terminalbd, Appstore, Xiidea,
Product, Bindu bundles) and `app/config` — and write a `next/` tree. On `master`
the legacy source is gone and `next/` has been promoted to the repository root, so
every path they resolve is either absent or now means something else: `src/` here
is the **Symfony 8** source, not the bundles they expect.

**The working copy is on the `symfony8-migration` branch**, which still has both
trees side by side. Run anything from there:

```bash
git switch symfony8-migration
./migration/bin/migrate.sh          # rebuild next/ from the legacy source
./migration/bin/ci-checks.sh        # the guards, no database needed
./migration/bin/migrate.sh --check  # prove the rebuild is reproducible
```

The Symfony 2.8 application is also tagged `legacy-symfony2-final` — the last
`master` before the promotion.

Its *working state* is not in git at all — `vendor/`, `app/cache`, `app/logs`,
`app/config/parameters.yml` (real credentials) and `web/`'s compiled assets are all
gitignored, so switching branches cannot restore them. The promotion kept them in
`.legacy-runtime/`, which is also gitignored. To run the legacy application again:

```bash
git switch symfony8-migration
mv .legacy-runtime/app/cache .legacy-runtime/app/logs app/
mv .legacy-runtime/app/config/parameters.yml app/config/
mv .legacy-runtime/vendor .
php7.4 -S 127.0.0.1:8801 -t web migration/bin/serve-router.php
```

`serve-router.php` is not optional: `php -S -t web web/app.php` makes the runtime
`require` whichever static file the request names, so every asset answers 500 with
its own body and the page renders unstyled.

## What is worth reading here

- `MIGRATION-PLAN.md` — the plan, and §5's catalogue of every defect the port
  found. Most of them were found by *running* the application, not by reading it,
  and the reasoning is recorded next to each one.
- `contract/` — the generated reports: the smoke, write and destructive sweeps,
  the schema diff, the column verification, the dead routes and template links.
- `session-bridge.md` — the deploy step that lets one login serve both
  applications. **Ordering-critical**: legacy encodes sha512 and the port rewrites
  to bcrypt on first login, so a user who logs into the port can never
  authenticate on legacy again. Enable the bridge first.

## Where the rest went

The API + React split has its own repository, `PosKeeper-ERP/terminalbd_api`,
which holds that plan, its progress reports and its tooling under `docs/` and
`tools/`. Those tools read a terminalbd checkout and expect the
`symfony8-migration` branch, where both trees exist — `LEGACY_ROOT` and the CI
checkout both name it.
