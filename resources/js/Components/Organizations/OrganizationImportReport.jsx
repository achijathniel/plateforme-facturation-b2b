import { Link } from '@inertiajs/react';

/**
 * Affiche le rapport chiffré d'une importation Excel/CSV et le détail des lignes rejetées.
 *
 * @param {{
 *     importReport: {
 *         total_rows: number,
 *         created_count: number,
 *         skipped_count: number,
 *         error_count: number,
 *         mode: string,
 *         aborted: boolean,
 *         errors: Array<{ row: number, name?: string|null, messages: Array<string> }>,
 *         created_names: Array<string>
 *     }
 * }} props
 */
export default function OrganizationImportReport({ importReport }) {
    if (!importReport) {
        return null;
    }

    return (
        <section
            className="form-section-card import-report-card"
            aria-labelledby="import-report-title"
        >
            <div className="import-template-header">
                <div>
                    <h3 id="import-report-title" className="section-title">
                        {importReport.aborted ? 'Importation annulée' : "Rapport d'importation"}
                    </h3>
                    <p
                        className="section-description"
                        role={importReport.aborted ? 'alert' : 'status'}
                    >
                        {importReport.aborted
                            ? 'Aucune entreprise n\'a été enregistrée en base.'
                            : `${importReport.created_count} entreprise(s) créée(s) sur ${importReport.total_rows} ligne(s) analysée(s).`}
                    </p>
                </div>

                {importReport.created_count > 0 && (
                    <Link href="/admin/organizations" className="btn-primary">
                        Voir la liste des entreprises
                    </Link>
                )}
            </div>

            <div className="import-summary-grid">
                <div className="import-stat-box">
                    <span className="import-stat-label">Lignes lues</span>
                    <span className="import-stat-value">{importReport.total_rows}</span>
                </div>
                <div className="import-stat-box stat-success">
                    <span className="import-stat-label">Créées</span>
                    <span className="import-stat-value">{importReport.created_count}</span>
                </div>
                <div className="import-stat-box stat-danger">
                    <span className="import-stat-label">Erreurs / Ignorées</span>
                    <span className="import-stat-value">{importReport.error_count}</span>
                </div>
            </div>

            {importReport.errors && importReport.errors.length > 0 && (
                <div className="data-table-wrapper import-errors-table-wrap">
                    <table className="data-table org-import-errors-table" aria-label="Détail des lignes rejetées">
                        <thead>
                            <tr>
                                <th scope="col" className="import-err-col-row">Ligne</th>
                                <th scope="col" className="import-err-col-name">Entreprise</th>
                                <th scope="col">Motif(s) du rejet</th>
                            </tr>
                        </thead>
                        <tbody>
                            {importReport.errors.map((err, idx) => (
                                <tr key={`${err.row}-${idx}`}>
                                    <td>
                                        <span className="table-code">#{err.row}</span>
                                    </td>
                                    <td>{err.name || 'Non renseignée'}</td>
                                    <td className="import-error-messages">
                                        {err.messages.length > 1 ? (
                                            <ul className="import-error-list">
                                                {err.messages.map((msg, msgIdx) => (
                                                    <li key={msgIdx}>{msg}</li>
                                                ))}
                                            </ul>
                                        ) : (
                                            err.messages[0]
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}
