import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import vm from 'node:vm';

const origin = 'https://pos.example.test';
const manifest = JSON.parse(readFileSync(new URL('../../public/build/manifest.webmanifest', import.meta.url)));
const sw = readFileSync(new URL('../../public/sw.js', import.meta.url), 'utf8');

function worker() {
    const listeners = new Map();
    const location = new URL('/sw.js', origin);
    const cache = { match: async () => new Response('static asset'), put: async () => {}, keys: async () => [] };
    const caches = { open: async () => cache, match: cache.match, keys: async () => [] };
    const self = {
        location, caches, registration: { scope: `${origin}/` },
        addEventListener(event, callback) {
            listeners.set(event, [...(listeners.get(event) || []), callback]);
        },
    };
    vm.runInNewContext(sw, { self, location, caches, URL, Request, Response, Headers, FetchEvent: class {},
        fetch: async () => { throw new Error('Network must not be used by these assertions'); }, console });
    return listeners;
}

test('built manifest declares root scope, standalone and real PNG icons of correct dimensions', () => {
    for (const key of ['name', 'short_name', 'theme_color', 'background_color']) assert.ok(manifest[key]);
    assert.equal(manifest.start_url, '/');
    assert.equal(manifest.scope, '/');
    assert.equal(manifest.display, 'standalone');
    for (const size of [192, 512]) {
        const icon = manifest.icons.find((icon) => icon.sizes === `${size}x${size}`);
        assert.ok(icon);
        const png = readFileSync(new URL(`../../public${icon.src}`, import.meta.url));
        assert.equal(png.subarray(1, 4).toString(), 'PNG');
        assert.equal(png.readUInt32BE(16), size);
        assert.equal(png.readUInt32BE(20), size);
    }
});

test('built worker never intercepts mutation or financial/user-specific GET responses', () => {
    const listeners = worker();
    assert.ok(listeners.has('install'));
    assert.ok(listeners.has('activate'));
    assert.ok(listeners.has('fetch'));
    assert.equal(listeners.has('sync'), false);
    for (const path of ['/pos/checkout', '/transactions/1/void', '/shifts/open', '/shifts/1/close', '/pos', '/reports', '/dashboard', '/settings', '/users', '/login']) {
        for (const method of ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']) {
            let intercepted = false;
            for (const listener of listeners.get('fetch')) {
                listener({ request: new Request(origin + path, { method }), respondWith: () => { intercepted = true; } });
            }
            assert.equal(intercepted, false, `${method} ${path} must always go to the server`);
        }
    }
});

test('precache contains only existing build JS/CSS, manifest and public icons', () => {
    const entries = [...sw.matchAll(/\{url:"([^"]+)",revision:/g)].map((match) => match[1]);
    assert.ok(entries.length > 2);
    for (const entry of entries) {
        assert.match(entry, /^\/(build\/assets\/.+\.(js|css)|build\/manifest\.webmanifest|icons\/pos-(192|512)\.png)$/);
        assert.ok(existsSync(new URL(`../../public${entry}`, import.meta.url)), entry);
    }
});

test('worker serves an allowlisted static asset, never caches a POST to the same asset', async () => {
    const listeners = worker();
    let response;
    const pending = [];
    for (const listener of listeners.get('fetch')) {
        listener({ request: new Request(`${origin}/icons/pos-192.png`),
            respondWith: (result) => { response = result; }, waitUntil: (result) => pending.push(result) });
    }
    assert.ok(response);
    assert.equal(await (await response).text(), 'static asset');
    await Promise.all(pending);
    for (const listener of listeners.get('fetch')) {
        listener({ request: new Request(`${origin}/icons/pos-192.png`, { method: 'POST' }),
            respondWith: () => assert.fail('POST must not be intercepted') });
    }
});
