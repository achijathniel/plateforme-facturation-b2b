import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';

/**
 * Composant global d'écoute et d'affichage des messages flash de session.
 * Déclenche des Toasts animés élégants et non-bloquants via SweetAlert2 (chargé à la demande).
 */
export default function FlashMessages() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (!flash || (!flash.success && !flash.error && !flash.warning && !flash.info)) {
            return;
        }

        let active = true;

        import('sweetalert2').then(({ default: Swal }) => {
            if (!active) return;

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                customClass: {
                    popup: 'dealtoo-toast-popup',
                    title: 'dealtoo-toast-title',
                },
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                },
            });

            if (flash.success) {
                Toast.fire({
                    icon: 'success',
                    title: flash.success,
                    iconColor: '#059669',
                });
            } else if (flash.error) {
                Toast.fire({
                    icon: 'error',
                    title: flash.error,
                    iconColor: '#e11d48',
                });
            } else if (flash.warning) {
                Toast.fire({
                    icon: 'warning',
                    title: flash.warning,
                    iconColor: '#d97706',
                });
            } else if (flash.info) {
                Toast.fire({
                    icon: 'info',
                    title: flash.info,
                    iconColor: '#2563eb',
                });
            }
        });

        return () => {
            active = false;
        };
    }, [flash]);

    return null;
}
