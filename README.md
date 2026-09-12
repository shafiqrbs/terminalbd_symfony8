# Terminalbd

Multi-tenant ERP / POS — accounting, inventory, medicine, restaurant, hospital and
business modules — on **Symfony 8.1 / PHP 8.5**.

```bash
composer install
php bin/console lint:container     # services build and type-check
php bin/validate-mapping.php       # 339 entities, no database needed
php vendor/bin/phpunit
symfony serve -d                   # or: php -S 127.0.0.1:8000 -t public
```

`.env` is committed and holds defaults only; real values go in `.env.local`
(git-ignored) or the secrets vault.

## History: this was a Symfony 2.8 application

It was ported to Symfony 8.1 / PHP 8.5 as a strangler migration — a parallel
application sharing one MySQL database, cut over module by module behind a proxy
— and that port is what produced this tree. Until the promotion it lived in a
`next/` subdirectory beside the Symfony 2.8 source; it is the repository root now.

- The Symfony 2.8 application, the pipeline that ported it, and the three
  verification harnesses are on the **`symfony8-migration`** branch, which has
  both trees side by side. That is where the migration tooling runs.
- The last `master` before the promotion is tagged **`legacy-symfony2-final`**.
- `migration/` is kept here as a record — see `migration/README.md`. Its plan,
  `migration/MIGRATION-PLAN.md`, catalogues every defect the port found and why,
  which is still the best description of this codebase's sharp edges.

Two things it is worth knowing before changing anything:

- **The database is the contract.** The Symfony 2.8 application still runs against
  the same schema, so a renamed column silently splits the data rather than
  failing. `migration/bin/verify-columns.php` on the migration branch is what
  checks that.
- **The 96 mobile endpoints are wire-locked.** Android and Flutter clients already
  in the field call those URLs and cannot be updated in lockstep. Do not change a
  path, a verb, or a response shape.

## Related repositories

| | |
|---|---|
| `PosKeeper-ERP/terminalbd_api` | the JSON API this is being split into, plus the API/React plan and its tooling |
| `PosKeeper-ERP/terminalbd_react` | the React SPA replacing the server-rendered screens |

## Known issue

14 templates still carry Twig 1 / Symfony 2 tags (`spaceless`, `render`) and fail
`lint:twig`. They have been broken since the port; the check that covered them
piped to `tail`, so its failure never reached CI. They are reported, not gated, in
`.github/workflows/checks.yml`.
