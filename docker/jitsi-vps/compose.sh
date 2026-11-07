#!/bin/sh
# Management entry point after enabling the OMO JWT profile on the VPS.
set -eu
root=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
exec docker compose -p jitsi --env-file "$root/.env" --env-file "$root/omo-integration/jwt.env" \
  -f "$root/upstream/docker-compose.yml" -f "$root/compose.vps.yaml" \
  -f "$root/omo-integration/compose.omo.yaml" "$@"
