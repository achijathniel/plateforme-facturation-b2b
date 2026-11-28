import PortalLayout from '../../../Layouts/PortalLayout';
import InvoiceForm from '../../../Components/Invoices/InvoiceForm';

/**
 * Page de création d'une nouvelle facture dans l'espace portail comptable.
 *
 * @param {{
 *     auth: { user: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     defaultDueDate: string,
 *     preselectedClient?: {
 *         id: number,
 *         name: string,
 *         email?: string,
 *         phone?: string,
 *         address?: string,
 *         tax_number?: string
 *     }|null
 * }} props
 */
export default function Create({ auth, organization, defaultDueDate, preselectedClient = null }) {
    return (
        <PortalLayout auth={auth} organization={organization} title="Créer une nouvelle facture">
            <InvoiceForm
                key={preselectedClient?.id ?? 'new'}
                organization={organization}
                preselectedClient={preselectedClient}
                defaultDueDate={defaultDueDate}
                submitUrl="/portal/invoices"
                method="post"
                backUrl={preselectedClient ? `/portal/clients/${preselectedClient.id}` : '/portal-dashboard'}
                backLabel={preselectedClient ? '← Retour à la fiche client' : '← Retour au tableau de bord'}
                isEdit={false}
            />
        </PortalLayout>
    );
}
