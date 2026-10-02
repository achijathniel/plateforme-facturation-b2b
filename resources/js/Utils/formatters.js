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
    const formatted = new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);

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
    return new Intl.NumberFormat('fr-FR').format(num || 0);
};
