// Read-only source inventory. Run: node scripts/audit-scripts.cjs [--all]
const fs = require('node:fs');
const path = require('node:path');
const primaryRoots = ['omo', 'common', 'popup', 'views', 'ajax'];
const roots = process.argv.includes('--all')
    ? [...primaryRoots, 'circle', 'pv', 'memo', 'lms', 'survey', 'includes', 'choice', 'timer', 'index.php', 'task.php']
    : primaryRoots;
const files = [];
function walk(file) {
    if (!fs.existsSync(file)) return;
    if (!fs.statSync(file).isDirectory()) {
        if (/\.(php|js)$/.test(file)) files.push(file.replaceAll('\\', '/'));
        return;
    }
    for (const entry of fs.readdirSync(file, { withFileTypes: true })) {
        if (/^(vendor|node_modules|lib|libs|tinymce|ckeditor|summernote|fullcalendar|jstree)$/.test(entry.name)) continue;
        walk(path.join(file, entry.name));
    }
}
roots.forEach(walk);
const inline = [], assets = [], initializers = [];
for (const file of files.sort()) {
    const source = fs.readFileSync(file, 'utf8');
    if (file.endsWith('.js')) {
        assets.push({ file, bytes: Buffer.byteLength(source) });
        continue;
    }
    // Skip PHP expressions in opening tags, including the ?> in asset URLs.
    const blocks = [...source.matchAll(/<script\b((?:<\?[\s\S]*?\?>|[^>])*)>([\s\S]*?)<\/script>/gi)]
        .filter(match => !/\bsrc\s*=/i.test(match[1]) && !/application\/(?:ld\+)?json/i.test(match[1]));
    if (blocks.length) inline.push({
        file,
        blocks: blocks.length,
        bytes: blocks.reduce((sum, match) => sum + Buffer.byteLength(match[2]), 0),
        largestBlock: Math.max(...blocks.map(match => Buffer.byteLength(match[2]))),
    });
    for (const match of source.matchAll(/commonPageScriptTags\(\s*'([^']+)'/g)) {
        initializers.push({ file, asset: match[1] });
    }
}
console.log(JSON.stringify({
    // These are source bytes, not compressed responses or measured network traffic.
    // PHP-generated configurations and data payloads are intentionally not JS assets.
    roots,
    totals: {
        inlineFiles: inline.length,
        inlineBytes: inline.reduce((sum, item) => sum + item.bytes, 0),
        jsFiles: assets.length,
        jsBytes: assets.reduce((sum, item) => sum + item.bytes, 0),
        configuredInitializers: initializers.length,
    },
    inline: inline.sort((a, b) => b.bytes - a.bytes),
    assets: assets.sort((a, b) => b.bytes - a.bytes),
    initializers,
}, null, 2));
