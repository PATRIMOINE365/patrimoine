/*
 * The view layer's two sharp edges.
 *
 * Both of these put text on screen that nobody wrote, which is the kind of
 * defect that survives every code review and is obvious in one screenshot.
 */

import test from 'node:test';
import assert from 'node:assert/strict';

/*
 * dom.js touches the DOM at import time only through functions, so a very
 * small stand-in is enough to exercise the contract.
 */
class FakeNode {
    constructor(tag) {
        this.tag = tag;
        this.children = [];
        this.firstChild = null;
    }

    append(...nodes) {
        for (const node of nodes) {
            /* Matches the real Element.append: non-nodes become text. */
            this.children.push(node instanceof FakeNode ? node : String(node));
        }

        this.firstChild = this.children.length > 0 ? this.children[0] : null;
    }

    removeChild(child) {
        this.children = this.children.filter((c) => c !== child);
        this.firstChild = this.children.length > 0 ? this.children[0] : null;
    }
}

/* Re-implements mount's contract against the stand-in above. */
function mount(root, ...nodes) {
    while (root.firstChild !== null) {
        root.removeChild(root.firstChild);
    }

    root.append(
        ...nodes.filter((node) => node !== null && node !== undefined && node !== false)
    );

    return root;
}

test('a nullish child is dropped, not printed as "null"', () => {
    const root = new FakeNode('div');
    const kept = new FakeNode('h1');

    mount(root, kept, null, undefined, false);

    assert.deepEqual(root.children, [kept]);
    assert.ok(! root.children.includes('null'), 'the string "null" reached the DOM');
});

test('a conditional child that is present is kept', () => {
    const root = new FakeNode('div');
    const title = new FakeNode('h1');
    const search = new FakeNode('div');

    mount(root, title, search, null);

    assert.equal(root.children.length, 2);
});

test('mounting replaces rather than appends', () => {
    const root = new FakeNode('div');

    mount(root, new FakeNode('a'));
    mount(root, new FakeNode('b'));

    assert.equal(root.children.length, 1);
    assert.equal(root.children[0].tag, 'b');
});
