/**
 * Formate un montant financier avec séparateur de milliers pour la devise XOF / FCFA.
 * Exemple: 142850000 -> "142 850 000"
 *
 * @param {number|string} amount
 * @returns {string}
 */
export const formatCurrency = (amount) => {
    const num = parseFloat(amount) || 0;
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
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
