import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const applicationRoot = path.resolve(scriptDirectory, '..');
const workerFile = path.join(applicationRoot, 'workerd', 'worker', 'index.js');
const defaultHomepageRoute = `app.get("/", async (c) => {
    const defaultRoom = c.env.ETHERCALC_DEFAULT_ROOM;
    if (defaultRoom) {
      const basepath = c.env.BASEPATH ?? "";
      return new Response("", {
        status: 302,
        headers: { Location: \`${'${basepath}'}/${'${defaultRoom}'}\` }
      });
    }
    return serveAsset(c.env, "/index.html");
  });`;
const restrictedHomepageRoute = `app.get("/", async () => new Response("This EtherCalc server is available through OMO only.", {
    status: 403,
    headers: { "Content-Type": "text/plain; charset=utf-8" }
  }));`;
const defaultStartRoute = 'app.get("/_start", async (c) => serveAsset(c.env, "/start.html"));';
const restrictedStartRoute = `app.get("/_start", async () => new Response("This EtherCalc server is available through OMO only.", {
    status: 403,
    headers: { "Content-Type": "text/plain; charset=utf-8" }
  }));`;

let source = fs.readFileSync(workerFile, 'utf8');
if (!source.includes(restrictedHomepageRoute)) {
    if (!source.includes(defaultHomepageRoute)) {
        throw new Error(`Unable to patch the EtherCalc homepage route in ${workerFile}.`);
    }
    source = source.replace(defaultHomepageRoute, restrictedHomepageRoute);
}

if (!source.includes(restrictedStartRoute)) {
    if (!source.includes(defaultStartRoute)) {
        throw new Error(`Unable to patch the EtherCalc start route in ${workerFile}.`);
    }
    source = source.replace(defaultStartRoute, restrictedStartRoute);
}

fs.writeFileSync(workerFile, source);
