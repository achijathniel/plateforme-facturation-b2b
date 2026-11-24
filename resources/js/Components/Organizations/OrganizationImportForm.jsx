import { useRef } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { Download, Upload } from 'lucide-react';
import OrganizationImportReport from './OrganizationImportReport';

/**
 * Formulaire d'importation Excel / CSV d'entreprises avec téléchargement du modèle
 * et délégation de l'affichage du rapport détaillé.
 *
 * @param {{
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
export default function OrganizationImportForm({ importReport = null }) {
    const fileInputRef = useRef(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
        mode: 'skip_errors',
    });

    const handleFileChange = (e) => {
        const selectedFile = e.target.files?.[0] || null;
        setData('file', selectedFile);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (processing) return;

        post('/admin/organizations/import', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('file');
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    };

    return (
        <div className="org-import-container">
            {/* Fil d'Ariane & En-tête */}
            <div className="org-import-header">
                <div className="breadcrumb-nav">
                    <Link href="/admin/organizations" className="breadcrumb-link">
                        ← Retour aux entreprises
                    </Link>
                </div>
                <h1 className="admin-page-title">Importer des entreprises (Excel / CSV)</h1>
                <p className="admin-page-subtitle">
                    Créez plusieurs entreprises en une seule opération à partir d'un fichier tableur structuré.
                </p>
            </div>

            {/* Carte 1 : Modèle Excel & Colonnes attendues */}
            <div className="form-section-card org-import-section-gap">
                <div className="import-template-header">
                    <div>
                        <h3 className="section-title">1. Modèle de fichier &amp; Format attendu</h3>
                        <p className="section-description">
                            Téléchargez le modèle pré-formaté, complétez vos entreprises puis importez-le ci-dessous (max. 5 Mo et 1 000 lignes).
                        </p>
                    </div>
                    <a
                        href="/admin/organizations/import/template"
                        className="btn-secondary import-download-btn"
                        download="modele-import-entreprises.xlsx"
                    >
                        <Download size={15} aria-hidden="true" />
                        <span>Télécharger le modèle Excel (.xlsx)</span>
                    </a>
                </div>

                <div className="import-columns-grid" aria-label="Colonnes attendues dans le tableur">
                    <span className="import-col-chip required">raison_sociale *</span>
                    <span className="import-col-chip required">nif *</span>
                    <span className="import-col-chip required">email *</span>
                    <span className="import-col-chip">telephone</span>
                    <span className="import-col-chip">adresse</span>
                    <span className="import-col-chip">actif (oui/non)</span>
                </div>
            </div>

            {/* Carte 2 : Upload & Stratégie */}
            <form onSubmit={handleSubmit} className="invoice-create-form" noValidate>
                <div className="form-section-card">
                    <h3 className="section-title">2. Sélectionner le fichier et le mode d'import</h3>
                    <p className="section-description">
                        Formats acceptés : <strong>.xlsx</strong> et <strong>.csv</strong> (séparateur virgule ou point-virgule).
                    </p>

                    <div className="form-group org-import-field-gap">
                        <label htmlFor="excel-file" className="form-label required">
                            Fichier Excel ou CSV
                        </label>
                        <div className={`import-dropzone ${errors.file ? 'has-error' : ''}`}>
                            <input
                                ref={fileInputRef}
                                id="excel-file"
                                name="file"
                                type="file"
                                accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                                onChange={handleFileChange}
                                className="import-file-input"
                                aria-required="true"
                                aria-invalid={!!errors.file}
                                aria-describedby={errors.file ? 'excel-file-help excel-file-error' : 'excel-file-help'}
                                required
                            />
                            <p id="excel-file-help" className="import-file-help">
                                {data.file
                                    ? `Fichier sélectionné : ${data.file.name} (${Math.max(1, Math.round(data.file.size / 1024))} Ko)`
                                    : 'Aucun fichier sélectionné — formats .xlsx ou .csv (5 Mo max).'}
                            </p>
                        </div>
                        {errors.file && (
                            <span id="excel-file-error" className="field-error-text" role="alert">
                                {errors.file}
                            </span>
                        )}
                    </div>

                    <fieldset
                        className="import-mode-fieldset org-import-fieldset-gap"
                        aria-invalid={!!errors.mode}
                        aria-describedby={errors.mode ? 'import-mode-error' : undefined}
                    >
                        <legend className="form-label">Comportement en cas de doublon ou d'erreur sur une ligne</legend>
                        <div className="import-mode-options">
                            <label className="import-mode-option" htmlFor="mode-skip">
                                <input
                                    id="mode-skip"
                                    type="radio"
                                    name="mode"
                                    value="skip_errors"
                                    checked={data.mode === 'skip_errors'}
                                    onChange={(e) => setData('mode', e.target.value)}
                                />
                                <span className="import-mode-content">
                                    <span className="import-mode-title">Ignorer les erreurs et importer les lignes valides (Recommandé)</span>
                                    <span className="import-mode-desc">
                                        Les entreprises valides sont créées immédiatement ; les doublons ou lignes invalides sont détaillés dans le rapport.
                                    </span>
                                </span>
                            </label>

                            <label className="import-mode-option" htmlFor="mode-strict">
                                <input
                                    id="mode-strict"
                                    type="radio"
                                    name="mode"
                                    value="strict"
                                    checked={data.mode === 'strict'}
                                    onChange={(e) => setData('mode', e.target.value)}
                                />
                                <span className="import-mode-content">
                                    <span className="import-mode-title">Mode strict (tout ou rien)</span>
                                    <span className="import-mode-desc">
                                        Si une seule ligne comporte une erreur ou un doublon, aucune entreprise n'est créée.
                                    </span>
                                </span>
                            </label>
                        </div>
                        {errors.mode && (
                            <span id="import-mode-error" className="field-error-text" role="alert">
                                {errors.mode}
                            </span>
                        )}
                    </fieldset>
                </div>

                <div className="import-actions-bar">
                    <Link href="/admin/organizations" className="btn-secondary">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        disabled={processing}
                        className="btn-primary"
                    >
                        <Upload size={16} aria-hidden="true" />
                        <span>{processing ? 'Importation en cours...' : "Lancer l'import"}</span>
                    </button>
                </div>
            </form>

            {/* Carte 3 : Rapport d'exécution */}
            <OrganizationImportReport importReport={importReport} />
        </div>
    );
}
