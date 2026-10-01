// Read-only inventory of application styles. Run: node scripts/audit-styles.cjs
const fs = require('node:fs');
const path = require('node:path');
const roots = ['omo', 'common', 'popup', 'views', 'ajax'];
const files = [];
function walk(dir) {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        if (/^(vendor|node_modules|lib|libs|tinymce|ckeditor|summernote|fullcalendar|jstree)$/.test(entry.name)) continue;
        const file = path.join(dir, entry.name);
        if (entry.isDirectory()) walk(file);
        else if (/\.(css|php)$/.test(file)) files.push(file.replaceAll('\\', '/'));
    }
}
roots.forEach(walk);
const inline = [], inlineAttributes = [], stylesheets = [];
const bodies = new Map();
for (const file of files.sort()) {
    const source = fs.readFileSync(file, 'utf8');
    if (file.endsWith('.php')) {
        const attributes = [...source.matchAll(/\bstyle="([^"<>]*)"/g)];
        if (attributes.length) inlineAttributes.push({ file, count: attributes.length, bytes: attributes.reduce((n, m) => n + Buffer.byteLength(m[1]), 0) });
    }
    const blocks = file.endsWith('.css') ? [source] : [...source.matchAll(/<style\b[^>]*>([\s\S]*?)<\/style>/gi)].map(m => m[1]);
    if (file.endsWith('.css')) stylesheets.push({ file, bytes: Buffer.byteLength(source) });
    else if (blocks.length) inline.push({ file, blocks: blocks.length, bytes: blocks.reduce((n, s) => n + Buffer.byteLength(s), 0), dynamic: blocks.some(s => s.includes('<?') || /\$[a-zA-Z_]/.test(s)) });
    // Candidates only: matching declarations are not proof that rules can be removed.
    for (const block of blocks) {
        for (const match of block.replace(/\/\*[\s\S]*?\*\//g, '').matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
            const body = match[2].replace(/\s+/g, ' ').trim();
            if (body.length < 75 || body.includes('<?') || /\$[a-zA-Z_]/.test(body)) continue;
            const occurrences = bodies.get(body) || [];
            occurrences.push({ file, selector: match[1].trim() });
            bodies.set(body, occurrences);
        }
    }
}
const duplicateCandidates = [...bodies.entries()]
    .filter(([, occurrences]) => new Set(occurrences.map(x => x.file)).size >= 2)
    .map(([declarations, occurrences]) => ({ declarations, occurrences, repeatedBytes: (occurrences.length - 1) * Buffer.byteLength(declarations) }))
    .sort((a, b) => b.repeatedBytes - a.repeatedBytes);
console.log(JSON.stringify({
    totals: { cssFiles: stylesheets.length, cssBytes: stylesheets.reduce((n, x) => n + x.bytes, 0), inlineFiles: inline.length, inlineBytes: inline.reduce((n, x) => n + x.bytes, 0) },
    inline: inline.sort((a, b) => b.bytes - a.bytes),
    stylesheets: stylesheets.sort((a, b) => b.bytes - a.bytes),
    // Source candidates, including PHP/JS-generated markup, not measured HTTP traffic.
    inlineAttributes: inlineAttributes.sort((a, b) => b.bytes - a.bytes),
    duplicateCandidates,
}, null, 2));
