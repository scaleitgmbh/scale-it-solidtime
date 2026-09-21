# Local self-hosted stack

Spins up solidtime the same way it's meant to be self-hosted (based on
[solidtime-io/self-hosting-examples](https://github.com/solidtime-io/self-hosting-examples)),
except the image is built from this repo's source instead of the published
`solidtime/solidtime` image, so local changes are reflected when you rebuild.

This is separate from the root `docker-compose.yml`, which is the Laravel
Sail setup used for day-to-day application development (Vite HMR, Xdebug,
Playwright, etc). Use this stack when you want to run/test solidtime the way
it would run in production.

## 1. Configure environment

```bash
cp .env.example .env
cp laravel.env.example laravel.env
```

Adjust `DB_PASSWORD` in both files (they must match) if you want something
other than the default.

## 2. Build the image

No PHP or Node needs to be installed locally — this uses throwaway containers
to install composer/npm dependencies, matching what upstream's CI does before
building the production image.

```bash
./build.sh
```

## 3. Generate application keys

```bash
docker compose --env-file .env run --rm scheduler php artisan self-host:generate-keys
```

Copy the `APP_KEY`, `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` values it
prints into `laravel.env`.

## 4. Start the stack

```bash
docker compose --env-file .env up -d
```

## 5. Run migrations

```bash
docker compose --env-file .env exec scheduler php artisan migrate --force
```

## 6. Create a user

Registration is disabled by default, so create the first user via the CLI:

```bash
docker compose --env-file .env exec scheduler php artisan admin:user:create "Your Name" "you@example.com" --verify-email
```

To make that user a super admin (access to `/admin`), add their email to
`SUPER_ADMINS` in `laravel.env` and restart:

```bash
docker compose --env-file .env down && docker compose --env-file .env up -d
```

## 7. Open the app

http://localhost:8000 (or whatever `FORWARD_APP_PORT` you set in `.env`).

## Rebuilding after code changes

```bash
./build.sh
docker compose --env-file .env up -d
```

## Notes

- The database port is exposed on `FORWARD_DB_PORT` (default `5432`) for
  local inspection. Don't do this on a stack exposed to the internet.
- The Invoicing extension (`extensions/Invoicing`) is a separate private
  repository and is intentionally not part of this build — this is the
  open-source self-host variant.
