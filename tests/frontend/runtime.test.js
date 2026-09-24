import test from 'node:test';
import assert from 'node:assert/strict';
import { createCurrencyFormatter } from '../../resources/js/Services/currency.js';
import { assertOnline, installOnlineMutationGuard, OFFLINE_MUTATION_MESSAGE } from '../../resources/js/Services/onlineMutations.js';

test('currency formats all nominal values consistently and accepts a runtime currency', () => {
    const idr = createCurrencyFormatter('IDR');
    assert.equal(idr.format('12500'), 'Rp\u00a012.500');
    assert.equal(idr.format(0), 'Rp\u00a00');
    assert.equal(idr.format(-5000), '-Rp\u00a05.000');
    assert.equal(idr.format(null), 'Rp\u00a00');
    assert.equal(idr.format(Infinity), 'Rp\u00a00');
    assert.equal(idr.symbol, 'Rp');
    assert.equal(createCurrencyFormatter('USD').format(12500), 'US$12.500,00');
});

test('offline mutations are rejected synchronously with an explicit message', () => {
    assert.throws(() => assertOnline(false), { code: 'OFFLINE_MUTATION', message: OFFLINE_MUTATION_MESSAGE });
    assert.doesNotThrow(() => assertOnline(true));
});

test('Inertia and Axios reject every mutation offline without blocking GET or replaying on reconnect', () => {
    const originalNavigator = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
    let beforeVisit;
    let beforeRequest;
    const messages = [];
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { onLine: false } });
    try {
        installOnlineMutationGuard(
            { on: (event, callback) => { assert.equal(event, 'before'); beforeVisit = callback; } },
            { interceptors: { request: { use: (callback) => { beforeRequest = callback; } } } },
            (message) => messages.push(message),
        );
        for (const method of ['post', 'put', 'patch', 'delete']) {
            assert.equal(beforeVisit({ detail: { visit: { method } } }), false);
            assert.throws(() => beforeRequest({ method }), { code: 'OFFLINE_MUTATION' });
        }
        assert.equal(beforeVisit({ detail: { visit: { method: 'get' } } }), undefined);
        assert.deepEqual(beforeRequest({ method: 'get' }), { method: 'get' });
        assert.deepEqual(messages, Array(4).fill(OFFLINE_MUTATION_MESSAGE));
        globalThis.navigator.onLine = true;
        assert.deepEqual(beforeRequest({ method: 'post' }), { method: 'post' });
        assert.equal(messages.length, 4);
    } finally {
        if (originalNavigator) Object.defineProperty(globalThis, 'navigator', originalNavigator);
        else delete globalThis.navigator;
    }
});
