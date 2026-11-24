/**
 * Analyse une URL relative Inertia en séparant le chemin et le paramètre de statut.
 *
 * @param {string|undefined|null} url
 * @returns {{ pathname: string, currentStatus: string|null }}
 */
export function parseInertiaUrl(url) {
    const parsedUrl = new URL(url || '/', 'http://localhost');

    return {
        pathname: parsedUrl.pathname,
        currentStatus: parsedUrl.searchParams.get('status'),
    };
}

const INVOICE_STATUS_META = {
    paid: { label: 'Payées', shortCode: 'P', dotClass: 'dot-emerald' },
    sent: { label: 'Envoyées', shortCode: 'E', dotClass: 'dot-blue' },
    overdue: { label: 'En retard', shortCode: 'R', dotClass: 'dot-rose' },
    draft: { label: 'Brouillons', shortCode: 'B', dotClass: 'dot-slate' },
};

/**
 * Construit les raccourcis de statuts de factures pour la section rapide de la sidebar.
 *
 * @param {{
 *     keyPrefix: string,
 *     basePath: string,
 *     pathname: string,
 *     currentStatus: string|null,
 *     statuses: Array<'paid'|'sent'|'overdue'|'draft'>
 * }} params
 */
export function buildInvoiceStatusQuickItems({
    keyPrefix,
    basePath,
    pathname,
    currentStatus,
    statuses,
}) {
    return statuses
        .filter((status) => INVOICE_STATUS_META[status])
        .map((status) => {
            const meta = INVOICE_STATUS_META[status];

            return {
                key: `${keyPrefix}-${status}`,
                label: meta.label,
                shortCode: meta.shortCode,
                href: `${basePath}?status=${status}`,
                dotClass: meta.dotClass,
                isActive: pathname === basePath && currentStatus === status,
            };
        });
}
