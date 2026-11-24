const currencyNumberFormatter = new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

const integerFormatter = new Intl.NumberFormat('fr-FR');

const shortDateFormatter = new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

const longDateFormatter = new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
});

/**
 * Formate un montant financier avec séparateur de milliers pour la devise XOF / FCFA ou autre devise.
 * Exemple: 142850000 -> "142 850 000", ou (142850000, "XOF") -> "142 850 000 XOF"
 *
 * @param {number|string} amount
 * @param {string|null} [currency]
 * @returns {string}
 */
export const formatCurrency = (amount, currency = null) => {
    const num = parseFloat(amount) || 0;
    const formatted = currencyNumberFormatter.format(num);

    return currency ? `${formatted} ${currency}` : formatted;
};

/**
 * Formate un entier avec séparateurs de milliers.
 * Exemple: 50021 -> "50 021"
 *
 * @param {number|string} num
 * @returns {string}
 */
export const formatNumber = (num) => {
    return integerFormatter.format(num || 0);
};

/**
 * Formate une date ISO ou standard en chaîne lisible en français.
 * Exemple: "2026-11-10" -> "10 nov. 2026"
 *
 * @param {string|Date} date
 * @returns {string}
 */
export const formatDate = (date) => {
    if (!date) return 'N/A';
    try {
        return shortDateFormatter.format(new Date(date));
    } catch {
        return String(date);
    }
};

/**
 * Formate une date ISO ou standard avec le nom complet du mois en français.
 * Exemple: "2026-11-10" -> "10 novembre 2026"
 *
 * @param {string|Date} date
 * @returns {string}
 */
export const formatDateLong = (date) => {
    if (!date) return '-';
    try {
        return longDateFormatter.format(new Date(date));
    } catch {
        return String(date);
    }
};
