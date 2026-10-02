import PortalLayout from '../../../Layouts/PortalLayout';
import InvoiceForm from '../../../Components/Invoices/InvoiceForm';

/**
 * Page de création d'une nouvelle facture dans l'espace portail comptable.
 *
 * @param {{
 *     auth: { user: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     defaultDueDate: string
 * }} props
 */
export default function Create({ auth, organization, defaultDueDate }) {
    return (
        <PortalLayout auth={auth} organization={organization} title="Créer une nouvelle facture">
            <InvoiceForm
                organization={organization}
                defaultDueDate={defaultDueDate}
                submitUrl="/portal/invoices"
                method="post"
                backUrl="/portal-dashboard"
                isEdit={false}
            />
        </PortalLayout>
    );
}
