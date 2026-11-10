import AdminLayout from '../../../Layouts/AdminLayout';
import OrganizationForm from '../../../Components/Organizations/OrganizationForm';

/**
 * Page de modification d'une entreprise pour les administrateurs.
 *
 * @param {{
 *     auth: { user: { name: string, role: string } },
 *     organization: {
 *         id: number,
 *         name: string,
 *         tax_number?: string|null,
 *         email: string,
 *         phone?: string|null,
 *         address?: string|null,
 *         is_active?: boolean
 *     }
 * }} props
 */
export default function Edit({ auth, organization }) {
    return (
        <AdminLayout auth={auth} title={`Modifier ${organization.name}`}>
            <OrganizationForm
                organization={organization}
                isEdit={true}
                submitUrl={`/admin/organizations/${organization.id}`}
                method="put"
                backUrl={`/admin/organizations/${organization.id}`}
            />
        </AdminLayout>
    );
}
