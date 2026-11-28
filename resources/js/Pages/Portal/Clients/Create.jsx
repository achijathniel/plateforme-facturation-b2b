import PortalLayout from '../../../Layouts/PortalLayout';
import ClientForm from '../../../Components/Clients/ClientForm';

/**
 * Page de création d'un nouveau client B2B dans le portail comptable.
 *
 * @param {{
 *     auth: { user: { name: string, role: string, organization?: { id: number, name: string } } },
 *     organization: { id: number, name: string }
 * }} props
 */
export default function Create({ auth, organization }) {
    return (
        <PortalLayout auth={auth} organization={organization} title="Nouveau Client">
            <ClientForm
                submitUrl="/portal/clients"
                backUrl="/portal/clients"
            />
        </PortalLayout>
    );
}
