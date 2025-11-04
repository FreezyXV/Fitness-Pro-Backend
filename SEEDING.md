# Production Seeding (Neon PostgreSQL)

Your Laravel backend already contains a dedicated `ProductionSeeder` that loads
the same canonical datasets (`ExerciseSeeder`, `WorkoutPlansSeeder`, `GoalsSeeder`,
`AlimentSeeder`, etc.). To apply these seeders against the Neon-hosted production
database, run the migrations/seeds with the production connection details in the
environment.

## Quick Command (one-off run)

```bash
cd backend
DATABASE_URL="postgresql://neondb_owner:YOUR_PASSWORD@ep-bitter-sun-ag6xf9vh-pooler.c-2.eu-central-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require" \
php artisan migrate --force --seed --class=ProductionSeeder
```

Notes:
- Replace the connection string with an env var or secret manager entry; avoid
  committing credentials to the repository.
- `DATABASE_URL` overrides the individual `DB_HOST`, `DB_DATABASE`, etc. values.
  Alternatively, you can set those variables explicitly in a `.env.production`
  file and load it with `php artisan config:clear && php artisan config:cache`.
- The `--force` flag is required when running in production to bypass safety
  checks.

## Running from Render/CI

1. Add the Neon credentials to the Render dashboard (environment variables or
   secret files).
2. Trigger the seed from a shell command:

   ```bash
   php artisan migrate --force
   php artisan db:seed --force --class=ProductionSeeder
   ```

   You can wire this into a one-off job, deploy hook, or CI workflow so the
   production data stays in sync after each deployment.

## Verifying Data

After running the seed:

- Execute `php artisan tinker` and check model counts (`Exercise::count()`, etc.).
- Or connect via `psql` using the same Neon URL and run SQL queries (`SELECT COUNT(*) FROM exercises;`).

This process leaves local development unaffected while ensuring production shares
the same baseline dataset as your local environment.
