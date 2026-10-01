FROM etherpad/etherpad:2.6.1

USER root

COPY --chown=etherpad:root plugins/ep_omo_theme /opt/etherpad-lite/local_plugins/ep_omo_theme

USER etherpad

RUN cd /opt/etherpad-lite \
    && ETHERPAD_LOCAL_PLUGINS=ep_omo_theme bin/installLocalPlugins.sh
