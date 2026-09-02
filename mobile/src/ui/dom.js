/*
 * The whole view layer.
 *
 * There is no framework here on purpose: the application is a thin,
 * mostly-list client over an API that already does the thinking, and a
 * framework would be more code than the screens it renders.
 */

/** Build an element. Text is set through textContent, never innerHTML. */
export function el(tag, attributes = {}, children = []) {
    const node = document.createElement(tag);

    for (const [name, value] of Object.entries(attributes)) {
        if (value === null || value === undefined || value === false) {
            continue;
        }

        if (name === 'text') {
            node.textContent = value;
        } else if (name === 'class') {
            node.className = value;
        } else if (name.startsWith('on') && typeof value === 'function') {
            node.addEventListener(name.slice(2).toLowerCase(), value);
        } else if (name === 'dataset') {
            Object.assign(node.dataset, value);
        } else {
            node.setAttribute(name, value === true ? '' : String(value));
        }
    }

    for (const child of [].concat(children)) {
        if (child === null || child === undefined) {
            continue;
        }

        node.append(typeof child === 'string' ? document.createTextNode(child) : child);
    }

    return node;
}

export function clear(node) {
    while (node.firstChild !== null) {
        node.removeChild(node.firstChild);
    }

    return node;
}

export function mount(root, ...nodes) {
    clear(root).append(...nodes);

    return root;
}

/*
 * A server failure is shown as the sentence the server wrote plus its
 * PM-code. The client never paraphrases: support starts from the code, and
 * the sentence is already in the right language.
 */
export function errorLine(error, fallback) {
    const message = error?.isOffline === true
        ? fallback
        : (error?.message ?? fallback);

    return el('p', { class: 'error', role: 'alert' }, [
        el('span', { text: message }),
        error?.code ? el('span', { class: 'error-code', text: ` (${error.code})` }) : null,
    ]);
}

export function field({ id, label, type = 'text', value = '', ...rest }) {
    const input = el('input', { id, type, value, class: 'input', ...rest });

    return {
        input,
        node: el('div', { class: 'field' }, [
            el('label', { class: 'label', for: id, text: label }),
            input,
        ]),
    };
}
