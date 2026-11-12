#!/usr/bin/env node
// Descarga el log de Aveonline de una tienda usando el token de "Revisión por token".
// Uso: node scripts/review-log.js [TIPO] [--print]
// Lee AVSHME_REVIEW_URL y AVSHME_REVIEW_TOKEN de scripts/.env (o del entorno).
const fs = require('fs');
const path = require('path');

const envFile = path.join(__dirname, '.env');
if (fs.existsSync(envFile)) {
    for (const line of fs.readFileSync(envFile, 'utf8').split(/\r?\n/)) {
        const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/);
        if (m && process.env[m[1]] === undefined) {
            process.env[m[1]] = m[2].replace(/^(['"])(.*)\1$/, '$2');
        }
    }
}

const base = (process.env.AVSHME_REVIEW_URL || '').replace(/\/+$/, '');
const token = process.env.AVSHME_REVIEW_TOKEN || '';
const args = process.argv.slice(2);
const print = args.includes('--print');
const type = args.find((a) => !a.startsWith('--')) || '';

if (!base || !token) {
    console.error('Falta AVSHME_REVIEW_URL o AVSHME_REVIEW_TOKEN en scripts/.env (ver scripts/.env.example)');
    process.exit(1);
}

const url = new URL(base + '/wp-json/aveonline/v1/review/log');
if (type) url.searchParams.set('type', type);

(async () => {
    const res = await fetch(url, { headers: { 'X-Avshme-Token': token, Accept: 'application/json' } });
    const text = await res.text();
    let body;
    try {
        body = JSON.parse(text);
    } catch (e) {
        console.error(`Respuesta no JSON (HTTP ${res.status}):\n${text.slice(0, 500)}`);
        process.exit(1);
    }
    if (!res.ok) {
        console.error(`HTTP ${res.status}: ${body.message || text}`);
        process.exit(1);
    }

    const json = JSON.stringify(body, null, 2);
    if (print) {
        console.log(json);
        return;
    }

    const dir = path.join(__dirname, 'logs');
    fs.mkdirSync(dir, { recursive: true });
    const host = new URL(base).hostname;
    const stamp = new Date().toISOString().replace(/[:.]/g, '-');
    const file = path.join(dir, `${host}${type ? '-' + type : ''}-${stamp}.json`);
    fs.writeFileSync(file, json);

    const left = Math.max(0, Math.round((body.expires - body.now) / 60));
    console.log(`${body.site} (v${body.version}) — token vence en ~${left} min`);
    for (const [t, n] of Object.entries(body.types || {})) console.log(`  ${t}: ${n}`);
    console.log(`Guardado en ${file}`);
})().catch((e) => {
    console.error(e.message);
    process.exit(1);
});
