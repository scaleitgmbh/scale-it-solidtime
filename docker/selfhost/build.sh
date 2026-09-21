#!/usr/bin/env bash
# Builds the solidtime self-host image from local source.
#
# The production Dockerfile (docker/prod/Dockerfile) expects vendor/ and the
# compiled frontend assets to already exist in the build context, since
# upstream's CI builds those on the runner before invoking `docker build`.
# This script reproduces that step with throwaway containers so no PHP/Node
# needs to be installed on the host, then builds the compose image.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

echo "==> Installing PHP dependencies (composer)"
docker run --rm \
  -v "$REPO_ROOT":/app -w /app \
  composer:2.8 \
  sh -c "cp -n .env.production .env; composer install --no-dev --no-ansi --no-interaction --prefer-dist --ignore-platform-reqs --classmap-authoritative"

echo "==> Building frontend assets (npm)"
docker run --rm \
  -v "$REPO_ROOT":/app -w /app \
  node:20-slim \
  sh -c "npm ci && npm run build"

echo "==> Building solidtime self-host image"
docker compose -f docker/selfhost/docker-compose.yml --env-file docker/selfhost/.env build

echo "==> Done. Start the stack with:"
echo "    docker compose -f docker/selfhost/docker-compose.yml --env-file docker/selfhost/.env up -d"
