FROM collabora/code:25.04.10.3.1

USER root

COPY branding.js /usr/share/coolwsd/browser/dist/omo-branding-20260822-1.js

RUN sed -i \
    's#<!--%BRANDING_JS%-->#<!--%BRANDING_JS%--><script src="%SERVICE_ROOT%/browser/%VERSION%/omo-branding-20260822-1.js"></script>#' \
    /usr/share/coolwsd/browser/dist/cool.html

USER 1001
