import PortalLayout from '../../../Layouts/PortalLayout';
import InvoiceForm from '../../../Components/Invoices/InvoiceForm';

/**
 * Page de modification d'une facture existante dans l'espace portail comptable.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     invoice: {
 *         id: number,
 *         invoice_number: string,
 *         client_name?: string,
 *         client_email?: string,
 *         client_address?: string,
 *         client_tax_number?: string,
 *         client_phone?: string,
 *         status: string,
 *         due_date: string|null,
 *         notes: string|null,
 *         items: Array<{
 *             id: number,
 *             description: string,
 *             quantity: number,
 *             unit_price: string
 *         }>
 *     }
 * }} props
 */
export default function Edit({ auth, organization, invoice }) {
    return (
        <PortalLayout
            auth={auth}
            organization={organization}
            title={`Modifier la facture ${invoice.invoice_number}`}
        >
            <InvoiceForm
                organization={organization}
                invoice={invoice}
                submitUrl={`/portal/invoices/${invoice.id}`}
                method="put"
                backUrl={`/portal/invoices/${invoice.id}`}
                isEdit={true}
            />
        </PortalLayout>
    );
}
