FROM node:14-bullseye

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        build-essential \
        ca-certificates \
        graphicsmagick \
        python3 \
        python-is-python3 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

RUN npm install --global npm@8.19.4

COPY fork/package.json fork/package-lock.json ./

RUN npm ci --omit=dev \
    && mkdir -p /app/storage /app/database \
    && chown -R node:node /app

COPY --chown=node:node fork/ /app/
COPY --chown=node:node ensure-local-demo.js /usr/local/bin/ensure-local-demo.js

USER node

EXPOSE 9666

CMD ["/bin/sh", "-ec", "node spacedeck.js & spacedeck_pid=$!; node /usr/local/bin/ensure-local-demo.js; wait \"$spacedeck_pid\""]
