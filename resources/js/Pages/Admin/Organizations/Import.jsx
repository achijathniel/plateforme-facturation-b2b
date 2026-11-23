import AdminLayout from '../../../Layouts/AdminLayout';
import OrganizationImportForm from '../../../Components/Organizations/OrganizationImportForm';

/**
 * Page d'importation massive d'entreprises par fichier Excel (.xlsx / .csv).
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     importReport?: {
 *         total_rows: number,
 *         created_count: number,
 *         skipped_count: number,
 *         error_count: number,
 *         mode: string,
 *         aborted: boolean,
 *         errors: Array<{ row: number, name?: string|null, messages: Array<string> }>,
 *         created_names: Array<string>
 *     }|null
 * }} props
 */
export default function Import({ auth, importReport = null }) {
    return (
        <AdminLayout auth={auth} title="Importer des Entreprises">
            <OrganizationImportForm importReport={importReport} />
        </AdminLayout>
    );
}
