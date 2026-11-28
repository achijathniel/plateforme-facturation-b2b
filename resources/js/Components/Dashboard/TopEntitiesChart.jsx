import { useId } from 'react';
import { Award } from 'lucide-react';
import { formatCurrency } from '../../Utils/formatters';

/**
 * Classement interactif Top 5 (Entreprises côté Admin ou Clients B2B côté Portail)
 * avec double barre contrastée (Total facturé et Encaissé) et sémantique <ol>/<li>.
 *
 * @param {{
 *     title: string,
 *     subtitle: string,
 *     entities: Array<{
 *         id: number|null,
 *         name: string,
 *         invoices_count: number,
 *         total_billed: number,
 *         total_paid: number
 *     }>,
 *     selectedEntityId?: string|number|null,
 *     onSelectEntity?: ((entity: any) => void)|null,
 *     currency?: string
 * }} props
 */
export default function TopEntitiesChart({
    title,
    subtitle,
    entities = [],
    selectedEntityId = null,
    onSelectEntity = null,
    currency = 'XOF',
}) {
    const headingId = useId();
    const maxBilled = entities.reduce(
        (max, item) => Math.max(max, Number(item.total_billed) || 0),
        0
    );

    return (
        <section className="dashboard-chart-card top-entities-card" aria-labelledby={headingId}>
            <div className="dashboard-chart-header">
                <div>
                    <h2 id={headingId} className="dashboard-chart-title">{title}</h2>
                    <p className="dashboard-chart-subtitle">{subtitle}</p>
                </div>
                <div className="chart-series-legend" aria-hidden="true">
                    <span className="chart-series-legend-item">
                        <span
                            className="chart-legend-swatch"
                            style={{ backgroundColor: 'var(--chart-billed)' }}
                        />
                        <span>Total facturé</span>
                    </span>
                    <span className="chart-series-legend-item">
                        <span
                            className="chart-legend-swatch"
                            style={{ backgroundColor: 'var(--chart-paid)' }}
                        />
                        <span>Encaissé</span>
                    </span>
                </div>
            </div>

            {entities.length === 0 ? (
                <div className="chart-empty-state">
                    <Award size={28} aria-hidden="true" />
                    <p>Aucune activité enregistrée pour les filtres sélectionnés.</p>
                </div>
            ) : (
                <ol className="top-entities-list">
                    {entities.map((entity, index) => {
                        const billed = Number(entity.total_billed) || 0;
                        const paid = Number(entity.total_paid) || 0;
                        const billedPct = maxBilled > 0
                            ? Math.min(100, Math.max(0, Math.round((billed / maxBilled) * 100)))
                            : 0;
                        const paidPct = maxBilled > 0
                            ? Math.min(100, Math.max(0, Math.round((paid / maxBilled) * 100)))
                            : 0;
                        const collectionPct = billed > 0
                            ? Math.min(100, Math.max(0, Math.round((paid / billed) * 100)))
                            : 0;

                        const isInteractive = Boolean(onSelectEntity && entity.id != null);
                        const isSelected =
                            selectedEntityId != null &&
                            selectedEntityId !== '' &&
                            String(selectedEntityId) === String(entity.id);

                        const stableKey = entity.id != null
                            ? `${entity.id}-${entity.name}`
                            : `${entity.name}-${index}`;

                        const content = (
                            <>
                                <span className="top-entity-row-top">
                                    <span className="top-entity-identity">
                                        <span className="top-entity-rank">{index + 1}.</span>
                                        <span className="top-entity-name">{entity.name}</span>
                                    </span>

                                    <span className="top-entity-meta">
                                        <span className="top-entity-count">
                                            {entity.invoices_count} facture(s) · Encaissé :{' '}
                                            {formatCurrency(paid, currency)} ({collectionPct}%)
                                        </span>
                                        <span className="top-entity-amount">
                                            {formatCurrency(billed, currency)}
                                        </span>
                                    </span>
                                </span>

                                <span className="top-entity-bars" aria-hidden="true">
                                    <span className="top-entity-track">
                                        <span
                                            className="top-entity-fill-billed"
                                            style={{ width: `${billedPct}%` }}
                                        />
                                    </span>
                                    <span className="top-entity-track">
                                        <span
                                            className="top-entity-fill-paid"
                                            style={{ width: `${paidPct}%` }}
                                        />
                                    </span>
                                </span>
                            </>
                        );

                        return (
                            <li key={stableKey}>
                                {isInteractive ? (
                                    <button
                                        type="button"
                                        onClick={() => onSelectEntity(entity)}
                                        className={`top-entity-item is-clickable ${isSelected ? 'is-active' : ''}`}
                                        aria-pressed={isSelected}
                                        title={
                                            isSelected
                                                ? `Retirer le filtre sur ${entity.name}`
                                                : `Filtrer le tableau de bord sur ${entity.name}`
                                        }
                                    >
                                        {content}
                                    </button>
                                ) : (
                                    <div className="top-entity-item">{content}</div>
                                )}
                            </li>
                        );
                    })}
                </ol>
            )}
        </section>
    );
}
