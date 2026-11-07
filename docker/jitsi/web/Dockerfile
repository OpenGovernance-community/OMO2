# Keep the official frontend and use its native translation override mechanism.
FROM ghcr.io/jitsi/web:stable-11248-3
USER root
ARG OMO_VISIO_INVITE_URL=https://beta.opengov.tools/visio
COPY labels.json /tmp/omo-labels.json
RUN mkdir /usr/share/jitsi-meet/static/omo-labels \
    && for locale in $(jq -r '.labels | keys[]' /tmp/omo-labels.json); do \
        jq --arg locale "$locale" '.labels[$locale]' /tmp/omo-labels.json \
            > "/usr/share/jitsi-meet/static/omo-labels/$locale.json"; \
    done \
    && jq --arg inviteDomain "$OMO_VISIO_INVITE_URL" '{inviteDomain: $inviteDomain, labels: (.labels | with_entries(.value = ("/static/omo-labels/" + .key + ".json")))}' \
        /tmp/omo-labels.json > /usr/share/jitsi-meet/static/omo-labels.json \
    && rm /tmp/omo-labels.json \
    && printf '%s\n' 'config.dynamicBrandingUrl = "/static/omo-labels.json?v=20261107-invite";' >> /defaults/system-config.js \
    && printf '%s\n' 'config.defaultLanguage = new URLSearchParams(window.location.search).get("lang") || config.defaultLanguage;' >> /defaults/settings-config.js
USER 1000
