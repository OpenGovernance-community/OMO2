import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const applicationRoot = path.resolve(scriptDirectory, '..');
const workerFile = path.join(applicationRoot, 'workerd', 'worker', 'index.js');
const assetRoute = 'app.get("/images/*", async (c) => serveAsset(c.env, c.req.path));';
const roomAssetRoute = `app.get("/:room/images/*", async (c) => {
    const assetPath = c.req.path.replace(/^\\/[^/]+(?=\\/images\\/)/, "");
    return serveAsset(c.env, assetPath);
  });`;

const source = fs.readFileSync(workerFile, 'utf8');
if (source.includes('app.get("/:room/images/*"')) {
    process.exit(0);
}

if (!source.includes(assetRoute)) {
    throw new Error(`Unable to patch EtherCalc room asset routes in ${workerFile}.`);
}

fs.writeFileSync(workerFile, source.replace(assetRoute, `${assetRoute}\n  ${roomAssetRoute}`));
