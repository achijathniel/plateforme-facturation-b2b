import { Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import AdminLayout from '../../../Layouts/AdminLayout';
import MetricCard from '../../../Components/MetricCard';
import StatusBadge from '../../../Components/StatusBadge';
import { formatCurrency, formatDate } from '../../../Utils/formatters';

/**
 * Fiche détaillée 360° d'une entreprise / organisation.
 *
 * @param {{
 *     auth: { user?: { name: string, role: string } },
 *     organization: {
 *         id: number,
 *         name: string,
 *         tax_number?: string|null,
 *         email: string,
 *         phone?: string|null,
 *         address?: string|null,
 *         is_active: boolean,
 *         created_at: string,
 *         users_count?: number,
 *         invoices_count?: number,
 *         total_billed?: number|string|null,
 *         total_paid?: number|string|null,
 *         users?: Array<{
 *             id: number,
 *             name: string,
 *             email: string,
 *             role: string,
 *             created_at: string
 *         }>,
 *         invoices?: Array<{
 *             id: number,
 *             invoice_number: string,
 *             client_name?: string,
 *             issue_date: string,
 *             due_date?: string|null,
 *             total: number|string,
 *             currency?: string,
 *             status: string
 *         }>
 *     }
 * }} props
 */
export default function Show({ auth, organization }) {
    const handleToggleStatus = () => {
        const actionVerb = organization.is_active ? 'suspendre' : 'activer';
        const confirmColor = organization.is_active ? '#e11d48' : '#16a34a';

        Swal.fire({
            title: `${organization.is_active ? 'Suspendre' : 'Activer'} l'entreprise ?`,
            text: organization.is_active
                ? `L'entreprise « ${organization.name} » ne pourra plus émettre de factures ni se connecter à la plateforme.`
                : `L'entreprise « ${organization.name} » recouvrera immédiatement tous ses accès opérationnels.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Oui, ${actionVerb}`,
            cancelButtonText: 'Annuler',
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#64748b',
        }).then((result) => {
            if (result.isConfirmed) {
                router.patch(`/admin/organizations/${organization.id}/toggle-status`, {}, {
                    preserveScroll: true,
                });
            }
        });
    };

    return (
        <AdminLayout auth={auth} title={`Entreprise : ${organization.name}`}>
            {/* Fil d'Ariane & En-tête */}
            <div className="admin-page-header">
                <div>
                    <div className="breadcrumb-nav">
                        <Link href="/admin/organizations" className="breadcrumb-link">
                            ← Retour aux entreprises
                        </Link>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginTop: '8px' }}>
                        <h2 className="admin-page-title" style={{ margin: 0 }}>
                            {organization.name}
                        </h2>
                        <span className={`status-badge ${organization.is_active ? 'badge-active' : 'badge-inactive'}`}>
                            {organization.is_active ? 'Active' : 'Suspendue'}
                        </span>
                    </div>
                    <p className="admin-page-subtitle">
                        Fiche 360° de supervision et historique d'activité.
                    </p>
                </div>
                <div style={{ display: 'flex', gap: '8px' }}>
                    <Link
                        href={`/admin/organizations/${organization.id}/edit`}
                        className="btn-secondary"
                        style={{ textDecoration: 'none' }}
                        aria-label={`Modifier les informations de ${organization.name}`}
                    >
                        <span aria-hidden="true">✏️</span> Modifier
                    </Link>
                    <button
                        type="button"
                        onClick={handleToggleStatus}
                        className={`btn-secondary ${organization.is_active ? 'is-active-btn' : ''}`}
                        aria-label={`${organization.is_active ? 'Suspendre' : 'Activer'} ${organization.name}`}
                    >
                        <span aria-hidden="true">{organization.is_active ? '⏸️' : '▶️'}</span>{' '}
                        {organization.is_active ? 'Suspendre' : 'Activer'}
                    </button>
                </div>
            </div>

            {/* Cartes d'indicateurs clés (KPIs 360°) */}
            <div className="metrics-grid" style={{ marginBottom: '24px' }}>
                <MetricCard
                    label="Total Facturé"
                    value={formatCurrency(organization.total_billed || 0)}
                    currency="XOF"
                    icon="💰"
                    subtext="Chiffre d'affaires émis"
                />
                <MetricCard
                    label="Total Encaissé"
                    value={formatCurrency(organization.total_paid || 0)}
                    currency="XOF"
                    icon="✅"
                    subtext="Factures réglées"
                    valueColor="var(--green-text)"
                />
                <MetricCard
                    label="Factures Émises"
                    value={organization.invoices_count || 0}
                    icon="📑"
                    subtext="Historique complet"
                />
                <MetricCard
                    label="Utilisateurs"
                    value={organization.users_count || 0}
                    icon="👥"
                    subtext="Comptes rattachés"
                />
            </div>

            {/* Informations Juridiques & Coordonnées */}
            <div className="org-info-card" style={{ marginBottom: '24px' }}>
                <div className="org-info-card-header">
                    <h3 className="org-info-card-title">
                        <span aria-hidden="true">🏛️</span>
                        <span>Identité Juridique & Coordonnées</span>
                    </h3>
                </div>
                <div className="org-overview-grid" style={{ marginBottom: 0 }}>
                    <div className="org-detail-item">
                        <span className="org-detail-label">Raison Sociale</span>
                        <span className="org-detail-value">{organization.name}</span>
                    </div>
                    <div className="org-detail-item">
                        <span className="org-detail-label">N° Contribuable / NIF</span>
                        <span className="org-detail-value">{organization.tax_number || 'Non renseigné'}</span>
                    </div>
                    <div className="org-detail-item">
                        <span className="org-detail-label">Email Officiel</span>
                        <span className="org-detail-value">
                            <a href={`mailto:${organization.email}`}>{organization.email}</a>
                        </span>
                    </div>
                    <div className="org-detail-item">
                        <span className="org-detail-label">Téléphone Standard</span>
                        <span className="org-detail-value">{organization.phone || 'Non renseigné'}</span>
                    </div>
                    <div className="org-detail-item">
                        <span className="org-detail-label">Adresse Géographique</span>
                        <span className="org-detail-value">{organization.address || 'Non renseignée'}</span>
                    </div>
                    <div className="org-detail-item">
                        <span className="org-detail-label">Date d'Enregistrement</span>
                        <span className="org-detail-value">
                            {organization.created_at ? formatDate(organization.created_at) : 'N/A'}
                        </span>
                    </div>
                </div>
            </div>

            {/* Grille Utilisateurs & Dernières Factures */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '24px' }}>
                {/* Utilisateurs rattachés */}
                <div className="data-card">
                    <div className="org-info-card-header" style={{ padding: '16px 20px', margin: 0 }}>
                        <h3 className="org-info-card-title">
                            <span aria-hidden="true">👥</span>
                            <span>Utilisateurs Rattachés ({organization.users?.length || 0})</span>
                        </h3>
                    </div>
                    <div className="data-table-wrapper">
                        <table className="data-table" aria-label="Liste des collaborateurs de cette entreprise">
                            <thead>
                                <tr>
                                    <th scope="col">Nom</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Rôle</th>
                                </tr>
                            </thead>
                            <tbody>
                                {organization.users && organization.users.length > 0 ? (
                                    organization.users.map((user) => (
                                        <tr key={user.id}>
                                            <td><strong>{user.name}</strong></td>
                                            <td>
                                                <a href={`mailto:${user.email}`} style={{ color: 'var(--blue-primary)', textDecoration: 'none' }}>
                                                    {user.email}
                                                </a>
                                            </td>
                                            <td>
                                                <span className="table-code">{user.role}</span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={3} className="table-empty-cell" style={{ padding: '24px' }}>
                                            Aucun utilisateur rattaché pour le moment.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Dernières Factures */}
                <div className="data-card">
                    <div className="org-info-card-header" style={{ padding: '16px 20px', margin: 0 }}>
                        <h3 className="org-info-card-title">
                            <span aria-hidden="true">📑</span>
                            <span>Dernières Factures Émises</span>
                        </h3>
                    </div>
                    <div className="data-table-wrapper">
                        <table className="data-table" aria-label="5 dernières factures émises par cette organisation">
                            <thead>
                                <tr>
                                    <th scope="col">Numéro</th>
                                    <th scope="col">Client</th>
                                    <th scope="col">Montant TTC</th>
                                    <th scope="col">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {organization.invoices && organization.invoices.length > 0 ? (
                                    organization.invoices.map((inv) => (
                                        <tr key={inv.id}>
                                            <td>
                                                <span className="table-code">{inv.invoice_number}</span>
                                            </td>
                                            <td>{inv.client_name || 'Client N/A'}</td>
                                            <td>
                                                <span className="table-amount">
                                                    {formatCurrency(inv.total)}
                                                </span>
                                                <span className="table-currency"> {inv.currency || 'XOF'}</span>
                                            </td>
                                            <td>
                                                <StatusBadge status={inv.status} />
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={4} className="table-empty-cell" style={{ padding: '24px' }}>
                                            Aucune facture émise pour le moment.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
