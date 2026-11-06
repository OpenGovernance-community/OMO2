# Keep the official frontend; only clarify the French lobby fallback action.
FROM ghcr.io/jitsi/web:stable-11248-3
USER root
RUN jq -e '.lobby.backToKnockModeButton | type == "string"' /usr/share/jitsi-meet/lang/main-fr.json > /dev/null \
    && jq '.lobby.backToKnockModeButton = "Je n’ai pas de mot de passe, je vais simplement attendre…"' \
        /usr/share/jitsi-meet/lang/main-fr.json > /tmp/main-fr.json \
    && cat /tmp/main-fr.json > /usr/share/jitsi-meet/lang/main-fr.json \
    && rm /tmp/main-fr.json
USER 1000
