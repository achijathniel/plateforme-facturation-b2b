import { router } from '@inertiajs/react';
import { confirmAction } from './confirmAction';

/**
 * Demande confirmation puis archive un client B2B via DELETE Inertia.
 * Partagé entre l'annuaire clients et la fiche client 360° (principe DRY).
 *
 * @param {{ id: number, name: string }} client
 * @param {{ preserveScroll?: boolean }} [options]
 * @returns {Promise<boolean>}
 */
export async function archiveClient(client, options = { preserveScroll: false }) {
    const confirmed = await confirmAction({
        title: 'Archiver ce client ?',
        text: `Le client « ${client.name} » sera retiré de votre carnet d'adresses actif. Ses factures historiques resteront intactes.`,
        confirmText: 'Oui, archiver',
        icon: 'warning',
        tone: 'danger',
        reverseButtons: true,
    });

    if (!confirmed) {
        return false;
    }

    router.delete(`/portal/clients/${client.id}`, options);
    return true;
}
