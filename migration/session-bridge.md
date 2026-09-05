# Session bridge (deploy step)

While both applications serve traffic a user must stay logged in across the
proxy boundary. Two things are needed, and both are deliberate deploy steps
rather than something the migration turns on by itself: the second one moves
every existing session.

## 1. A session store both PHP pools can reach

The Symfony 8 application is already configured for it
(`next/config/packages/framework.yaml`, `session.handler.shared`). The table is
additive and holds no application data:

```sql
CREATE TABLE sessions (
    sess_id       VARBINARY(128) NOT NULL PRIMARY KEY,
    sess_data     BLOB NOT NULL,
    sess_lifetime INTEGER UNSIGNED NOT NULL,
    sess_time     INTEGER UNSIGNED NOT NULL,
    INDEX sessions_sess_lifetime_idx (sess_lifetime)
) COLLATE utf8mb4_bin, ENGINE = InnoDB;
```

`php8.5 migration/bin/create-session-table.php` creates it from `DATABASE_URL`
if it is absent.

Then point the legacy application at the same table, in
`app/config/config.yml`:

```yaml
framework:
    session:
        handler_id: session.handler.pdo

services:
    session.handler.pdo:
        class: Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments:
            - "mysql:host=%database_host%;dbname=%database_name%"
            - { db_table: sessions, db_id_col: sess_id, db_data_col: sess_data, db_lifetime_col: sess_lifetime, db_time_col: sess_time, db_username: "%database_user%", db_password: "%database_password%" }
```

**Everyone logged in at that moment is logged out**, because their session
lives in a file the new handler does not read. Do it in a quiet window, or
accept one round of re-logins.

## 2. The bridge itself

Sharing the store is not enough. Symfony 2 serialises its security token with
the `Serializable` format of its own token classes and Symfony 8 cannot read
that payload; the reverse is equally true. So neither side reads the other's
token, and each instead publishes one neutral key - the user identifier -
which the other adopts when it has no session of its own.

- Symfony 8 half: `App\Security\SharedSessionAuthenticator` (already on the
  firewall) and `App\Security\SharedSessionListener`.
- Legacy half: `Core\UserBundle\EventListener\SharedSessionListener`, which is
  inert until registered. Add to
  `src/Core/UserBundle/Resources/config/services.yml`:

```yaml
  core_user.listener.shared_session:
      class: Core\UserBundle\EventListener\SharedSessionListener
      arguments: ["@security.token_storage", "@fos_user.user_provider.username"]
      tags:
        - { name: kernel.event_listener, event: security.interactive_login, method: onInteractiveLogin }
        - { name: kernel.event_listener, event: kernel.request, method: onKernelRequest, priority: 7 }
```

Priority 7 puts it just after Symfony's own firewall listener, so an existing
token always wins and a logout on either side is not undone by a stale key.

The key carries no credentials: it is trusted only because it lives in a
session store that only these two applications can write.

## Rolling back

Remove the listener registration and set `handler_id: ~` again. The sessions
table can stay; nothing else reads it.
