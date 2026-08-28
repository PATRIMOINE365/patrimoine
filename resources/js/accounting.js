/*
|--------------------------------------------------------------------------
| Patrimoine Accounting
|--------------------------------------------------------------------------
|
| The managing organisation's own money, as opposed to the Tenant and Owner
| workspaces which show everyone else's.
|
| Two figures matter here and they must not be conflated: management fee
| income, which the organisation keeps, and the VAT charged on those fees,
| which is collected on behalf of the tax authority and owed onward. The
| API derives both from the Owner ledger, so this module only renders.
|
*/

import {
    apiRequest,
    escapeHtml,
    formatCurrency,
    formatDate,
    parseJsonResponse,
    translate,
} from './core.js';

import {
    dateForApi,
    initializeDateInputs,
} from './date-input.js';

/** Matches the server-side cap in AccountingController. */
const TRANSACTION_LIMIT = 200;

/**
 * Initialize the Accounting workspace.
 *
 * A no-op on every other page.
 */
export async function initializeAccounting() {
    const rows =
        document.getElementById(
            'accounting-rows'
        );

    if (! rows) {
        return;
    }

    initializeDateInputs();

    document
        .getElementById('accounting-apply')
        ?.addEventListener('click', () => {
            void loadAccounting();
        });

    document
        .getElementById('accounting-reset')
        ?.addEventListener('click', () => {
            const from = document.getElementById('accounting-from');
            const to = document.getElementById('accounting-to');

            if (from) {
                from.value = '';
            }

            if (to) {
                to.value = '';
            }

            void loadAccounting();
        });

    await loadAccounting();
}

/**
 * Fetch and render the current period.
 */
async function loadAccounting() {
    hideError();

    const parameters = new URLSearchParams();

    const from = dateForApi(
        document.getElementById('accounting-from')?.value
    );

    const to = dateForApi(
        document.getElementById('accounting-to')?.value
    );

    if (from) {
        parameters.set('from', from);
    }

    if (to) {
        parameters.set('to', to);
    }

    const query = parameters.toString();

    try {
        const response = await apiRequest(
            '/api/accounting/summary'
            + (query ? '?' + query : '')
        );

        const payload = await parseJsonResponse(response);

        renderTotals(payload.totals ?? {});
        renderTransactions(payload.transactions ?? []);
    } catch (error) {
        showError(
            error instanceof Error
                ? error.message
                : translate('accounting.empty')
        );
    }
}

/**
 * @param {object} totals
 */
function renderTotals(totals) {
    setMoney(
        'accounting-fee-income',
        totals.management_fee
    );

    setMoney(
        'accounting-vat-charged',
        totals.management_fee_vat
    );

    setMoney(
        'accounting-charged-total',
        totals.charged_to_owners
    );
}

/**
 * @param {string} id
 * @param {number|undefined} amount
 */
function setMoney(id, amount) {
    const element = document.getElementById(id);

    if (! element) {
        return;
    }

    element.textContent = formatCurrency(
        Number(amount ?? 0)
    );
}

/**
 * @param {Array<object>} transactions
 */
function renderTransactions(transactions) {
    const body =
        document.getElementById('accounting-rows');

    if (! body) {
        return;
    }

    const capped =
        document.getElementById('accounting-capped');

    capped?.classList.toggle(
        'hidden',
        transactions.length < TRANSACTION_LIMIT
    );

    if (transactions.length === 0) {
        body.innerHTML = `
            <tr>
                <td colspan="6" class="py-6 text-center text-[var(--pm-text-muted)]">
                    ${escapeHtml(translate('accounting.empty'))}
                </td>
            </tr>
        `;

        return;
    }

    body.innerHTML = transactions
        .map((transaction) => {
            const property = [
                transaction.building_name,
                transaction.unit_name,
            ]
                .filter(Boolean)
                .join(' — ');

            return `
                <tr>
                    <td>${escapeHtml(formatDate(transaction.transaction_date) || '—')}</td>
                    <td>${escapeHtml(translate('accounting.' + transaction.category))}</td>
                    <td>${escapeHtml(transaction.owner_name || '—')}</td>
                    <td>${escapeHtml(property || '—')}</td>
                    <td>${escapeHtml(transaction.reference || '—')}</td>
                    <td class="text-right">${escapeHtml(formatCurrency(Number(transaction.amount ?? 0)))}</td>
                </tr>
            `;
        })
        .join('');
}

function showError(message) {
    const element =
        document.getElementById('accounting-error');

    if (! element) {
        return;
    }

    element.textContent = message;
    element.classList.remove('hidden');
}

function hideError() {
    document
        .getElementById('accounting-error')
        ?.classList.add('hidden');
}
