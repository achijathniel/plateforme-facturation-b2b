import AdminLayout from '../../../Layouts/AdminLayout';
import OrganizationForm from '../../../Components/Organizations/OrganizationForm';

/**
 * Page de création d'une nouvelle entreprise pour les administrateurs.
 *
 * @param {{ auth: { user: { name: string, role: string } } }} props
 */
export default function Create({ auth }) {
    return (
        <AdminLayout auth={auth} title="Nouvelle Entreprise">
            <OrganizationForm
                submitUrl="/admin/organizations"
                backUrl="/admin/organizations"
            />
        </AdminLayout>
    );
}
