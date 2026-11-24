const TONE_COLORS = {
    primary: '#2563eb',
    danger: '#be123c',
    success: '#15803d',
};

/**
 * Affiche une modale de confirmation accessible via import dynamique de SweetAlert2.
 * Préserve le code-splitting et garantit des contrastes WCAG 2.2 AA (>= 4,5:1).
 *
 * @param {{
 *     title: string,
 *     text: string,
 *     confirmText: string,
 *     cancelText?: string,
 *     icon?: 'question' | 'warning' | 'info' | 'error' | 'success',
 *     tone?: 'primary' | 'danger' | 'success',
 *     reverseButtons?: boolean
 * }} options
 * @returns {Promise<boolean>}
 */
export async function confirmAction({
    title,
    text,
    confirmText,
    cancelText = 'Annuler',
    icon = 'warning',
    tone = 'primary',
    reverseButtons = false,
}) {
    const { default: Swal } = await import('sweetalert2');

    const result = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: TONE_COLORS[tone] ?? TONE_COLORS.primary,
        cancelButtonColor: '#475569',
        reverseButtons,
    });

    return Boolean(result.isConfirmed);
}
