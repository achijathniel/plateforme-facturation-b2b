import PortalLayout from '../../../Layouts/PortalLayout';
import ClientForm from '../../../Components/Clients/ClientForm';

/**
 * Page de modification d'un client B2B dans le portail comptable.
 *
 * @param {{
 *     auth: { user: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string },
 *     client: {
 *         id: number,
 *         name: string,
 *         tax_number?: string|null,
 *         email?: string|null,
 *         phone?: string|null,
 *         address?: string|null,
 *         notes?: string|null
 *     }
 * }} props
 */
export default function Edit({ auth, organization, client }) {
    return (
        <PortalLayout auth={auth} organization={organization} title={`Modifier ${client.name}`}>
            <ClientForm
                key={client.id}
                client={client}
                isEdit={true}
                submitUrl={`/portal/clients/${client.id}`}
                method="put"
                backUrl={`/portal/clients/${client.id}`}
            />
        </PortalLayout>
    );
}
