#!/usr/bin/env bash

set -euo pipefail

app_root="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
environment_file="${ETHERCALC_ENV_FILE:-$app_root/../.ethercalc.env}"

if [[ -f "$environment_file" ]]; then
    set -a
    # shellcheck disable=SC1090
    . "$environment_file"
    set +a
fi

port="${PORT:-${ETHERCALC_PORT:-3000}}"
host="${ETHERCALC_HOST:-0.0.0.0}"
data_dir="${ETHERCALC_DATA_DIR:-$app_root/../ethercalc-data}"
assets_dir="${ETHERCALC_ASSETS_DIR:-$app_root/assets}"

mkdir -p "$data_dir/do"
export ETHERCALC_DISABLE_ROOM_INDEX="${ETHERCALC_DISABLE_ROOM_INDEX:-1}"
node "$app_root/bin/patch-room-assets.js"

exec "$app_root/bin/workerd" serve \
    "$app_root/workerd/config.capnp" \
    --socket-addr "http=$host:$port" \
    -ddo="$data_dir/do" \
    -dassets="$assets_dir"
