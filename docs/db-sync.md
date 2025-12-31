# Database copy command

This project includes an Artisan command to copy a database between
production and a local sandbox, using values from `.env`.

Command:
- `php artisan db:copy`

Defaults:
- Source: production
- Target: sandbox

## Environment variables
Production (optional if you already use `DB_*` for production):
- `DB_PROD_HOST`
- `DB_PROD_PORT`
- `DB_PROD_DATABASE`
- `DB_PROD_USERNAME`
- `DB_PROD_PASSWORD`

Sandbox (required to point the target to your local DB):
- `DB_SANDBOX_HOST`
- `DB_SANDBOX_PORT`
- `DB_SANDBOX_DATABASE`
- `DB_SANDBOX_USERNAME`
- `DB_SANDBOX_PASSWORD`

Notes:
- If `DB_PROD_DATABASE` is empty, the command falls back to `DB_DATABASE`
  and related `DB_*` values for production.
- The target database is created automatically if it does not exist.

## Examples
Copy production -> sandbox (default):
```
php artisan db:copy
```

Copy sandbox -> production (requires confirmation or --force):
```
php artisan db:copy --source=sandbox --target=production
```

Force copy to production (no prompt):
```
php artisan db:copy --source=sandbox --target=production --force
```

## Behavior and safety
- The target database is created if missing.
- Tables in the target are dropped and recreated from the source.
- Views are recreated but no data is inserted into them.
- Data is copied in chunks to reduce memory usage.

## Switch the app between production and sandbox
After copying, you can point the app to the sandbox by editing `.env`:
1) Replace `DB_*` values with your sandbox values, or
2) Copy the `DB_SANDBOX_*` values into `DB_*`.

Restart the app/server after changing `.env`.
