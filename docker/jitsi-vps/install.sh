#!/usr/bin/env bash
set -euo pipefail
umask 077

root=/opt/jitsi
version=stable-11248-3
archive_sha256=2507d873c4f3a628a1be32a5248d7899b045afee9c61f13a8ddb4e7f990b3de7

if [[ $(id -u) -ne 1000 ]]; then
    echo 'Run as the VPS user with uid 1000, matching the rootless Jitsi containers.' >&2
    exit 1
fi
[[ $(realpath "$root") == /opt/jitsi ]]
[[ ! -e "$root/.env" ]]
[[ ! -e "$root/upstream" ]]

curl --fail --location --silent --show-error \
    "https://api.github.com/repos/jitsi/docker-jitsi-meet/tarball/$version" \
    --output "$root/upstream.tar.gz"
printf '%s  %s\n' "$archive_sha256" "$root/upstream.tar.gz" | sha256sum --check --status
mkdir "$root/upstream"
tar -xzf "$root/upstream.tar.gz" --strip-components=1 --no-same-owner -C "$root/upstream"
cp "$root/.env.private.example" "$root/upstream/.env"
bash "$root/upstream/gen-passwords.sh"
mv "$root/upstream/.env" "$root/.env"
chmod 600 "$root/.env"

for path in web web/custom-ca prosody/config prosody/prosody-plugins-custom \
    prosody/custom-ca jicofo jicofo/custom-ca jvb jvb/custom-ca \
    storage/web storage/prosody storage/transcripts tmp/web-load-test; do
    mkdir -p "$root/config/$path"
    chmod 700 "$root/config/$path"
done

docker compose -p jitsi --env-file "$root/.env" \
    -f "$root/upstream/docker-compose.yml" -f "$root/compose.vps.yaml" config --quiet
docker compose -p jitsi --env-file "$root/.env" \
    -f "$root/upstream/docker-compose.yml" -f "$root/compose.vps.yaml" pull
docker compose -p jitsi --env-file "$root/.env" \
    -f "$root/upstream/docker-compose.yml" -f "$root/compose.vps.yaml" up -d
echo 'Jitsi containers started; DNS, TLS, organizer account and a real call still need verification.'
