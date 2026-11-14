FROM audreyt/ethercalc:0.20260612.4

USER root

COPY omo-resize.js /app/assets/static/omo-resize.js
COPY omo-layout.css /app/assets/static/omo-layout.css
COPY standalone/bin/patch-share-access.cjs /usr/local/bin/omo-patch-share-access.cjs

RUN bun /usr/local/bin/omo-patch-share-access.cjs /app/packages/worker/workerd/worker/index.js /app/packages/worker/workerd/config.capnp

RUN sed -i 's#</head>#<link rel="stylesheet" href="/static/omo-layout.css?v=4"><script src="/static/omo-resize.js?v=3"></script></head>#' /app/assets/index.html
