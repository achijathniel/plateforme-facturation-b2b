import { useId } from 'react';
import { Calendar, X } from 'lucide-react';

const PERIOD_OPTIONS = [
    { value: '30d', label: '30 jours' },
    { value: '90d', label: '3 mois' },
    { value: '6m', label: '6 mois' },
    { value: '12m', label: '12 mois' },
    { value: 'all', label: 'Tout' },
];

/**
 * Barre de filtres interactive partagée pour les tableaux de bord Admin et Portail.
 * Permet de piloter la période, le statut des factures et, côté Admin, l'entreprise cliente.
 *
 * @param {{
 *     values: { period: string, status: string, organization_id?: string },
 *     statuses: Array<{ value: string, label: string }>,
 *     organizations?: Array<{ id: number, name: string }>,
 *     showOrganizationFilter?: boolean,
 *     hasActiveFilters: boolean,
 *     onFieldChange: (key: string, value: string) => void,
 *     onReset: () => void
 * }} props
 */
export default function DashboardFilterBar({
    values,
    statuses = [],
    organizations = [],
    showOrganizationFilter = false,
    hasActiveFilters,
    onFieldChange,
    onReset,
}) {
    const uid = useId();
    const orgFilterId = `dashboard-organization-filter-${uid}`;
    const statusFilterId = `dashboard-status-filter-${uid}`;

    return (
        <section className="dashboard-filter-bar" aria-label="Filtres analytiques du tableau de bord">
            <div className="dashboard-filter-left">
                <span className="dashboard-filter-title">
                    <Calendar size={14} aria-hidden="true" />
                    <span>Période</span>
                </span>

                <div className="period-pills" role="group" aria-label="Sélectionner la période d'analyse">
                    {PERIOD_OPTIONS.map((option) => {
                        const isActive = values.period === option.value;

                        return (
                            <button
                                key={option.value}
                                type="button"
                                className={`period-pill ${isActive ? 'is-active' : ''}`}
                                aria-pressed={isActive}
                                onClick={() => onFieldChange('period', option.value)}
                            >
                                {option.label}
                            </button>
                        );
                    })}
                </div>
            </div>

            <div className="dashboard-filter-controls">
                {showOrganizationFilter && (
                    <div className="filter-select-group">
                        <label htmlFor={orgFilterId} className="filter-label">
                            Entreprise :
                        </label>
                        <select
                            id={orgFilterId}
                            value={values.organization_id || ''}
                            onChange={(e) => onFieldChange('organization_id', e.target.value)}
                            className="filter-select"
                        >
                            <option value="">Toutes les entreprises</option>
                            {organizations.map((org) => (
                                <option key={org.id} value={String(org.id)}>
                                    {org.name}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                <div className="filter-select-group">
                    <label htmlFor={statusFilterId} className="filter-label">
                        Statut :
                    </label>
                    <select
                        id={statusFilterId}
                        value={values.status || ''}
                        onChange={(e) => onFieldChange('status', e.target.value)}
                        className="filter-select"
                    >
                        <option value="">Tous les statuts</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </div>

                {hasActiveFilters && (
                    <button
                        type="button"
                        onClick={onReset}
                        className="btn-reset-filter"
                        title="Réinitialiser tous les filtres du tableau de bord"
                    >
                        <X size={13} aria-hidden="true" />
                        <span>Réinitialiser</span>
                    </button>
                )}
            </div>
        </section>
    );
}
