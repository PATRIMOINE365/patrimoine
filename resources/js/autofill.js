/*
|--------------------------------------------------------------------------
| Autofill policy
|--------------------------------------------------------------------------
|
| The browser may fill in the sign-in and sign-up pages. It may not fill in
| anything else.
|
| Inside the application the fields are not the person using it: they are a
| tenant's telephone number, an owner's e-mail address, a property's
| address, a bank reference. The browser does not know that, so it offers
| the operator's OWN saved details — and a saved address quietly landing in
| a tenant's record is a data error nobody sees happen, in a system whose
| whole purpose is holding somebody else's records straight.
|
| The application shell asks for this by carrying data-autofill="off" on
| the body. The sign-in and sign-up pages do not, so a password manager
| works there exactly as it did.
|
| Two things this does NOT do, and cannot:
|
| - A password manager's own extension is not the browser's autofill and
|   answers to nothing on the page. It can still fill anything it likes.
| - Chrome reserves the right to offer address and payment suggestions on
|   fields it is confident it recognises, whatever the page asks for.
|
| Fields arriving later are covered too. Nearly every form in Patrimoine is
| inside a drawer that is rendered when it opens, so a one-off pass over
| the document at boot would cover almost nothing.
|
*/

/**
 * What a field should say when it must not be filled.
 *
 * A password field is told `new-password` rather than `off`: managers
 * treat `off` as advice and `new-password` as an instruction, and the
 * password boxes inside the application are all confirmations of the
 * signed-in user's own password before something irreversible — the one
 * place a silently filled value is worst.
 *
 * @param {Element} field
 * @returns {string}
 */
function policyFor(field) {
    return field instanceof HTMLInputElement && field.type === 'password'
        ? 'new-password'
        : 'off';
}

/**
 * Apply the policy to one element and, if it is a container, its fields.
 *
 * @param {Element} root
 */
function apply(root) {
    if (root.matches?.('input, select, textarea, form')) {
        const wanted = policyFor(root);

        if (root.getAttribute('autocomplete') !== wanted) {
            root.setAttribute('autocomplete', wanted);
        }
    }

    root.querySelectorAll?.('input, select, textarea, form')
        .forEach((field) => {
            const wanted = policyFor(field);

            if (field.getAttribute('autocomplete') !== wanted) {
                field.setAttribute('autocomplete', wanted);
            }
        });
}

/**
 * Refuse browser autofill everywhere in the application.
 *
 * Does nothing unless the page asks for it, so the auth pages are
 * unaffected by being part of the same bundle.
 */
export function initializeAutofillPolicy() {
    if (document.body?.dataset.autofill !== 'off') {
        return;
    }

    apply(document.body);

    /*
     * Watching attributes as well as children: a drawer that re-renders
     * its own markup would otherwise put the browser's default back, and
     * the observer would not hear about it because no node was added.
     */
    new MutationObserver((records) => {
        records.forEach((record) => {
            if (record.type === 'attributes') {
                apply(record.target);

                return;
            }

            record.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    apply(node);
                }
            });
        });
    }).observe(
        document.body,
        {
            childList: true,
            subtree: true,
            attributeFilter: ['autocomplete'],
        }
    );
}
