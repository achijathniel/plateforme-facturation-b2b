import { useId } from 'react';
import { Pie, PieChart, ResponsiveContainer, Sector, Tooltip } from 'recharts';
import { PieChart as PieChartIcon } from 'lucide-react';
import useReducedMotion from '../../Hooks/useReducedMotion';
import { ChartTooltipCard, formatCurrency } from './ChartTooltip';

const STATUS_COLORS = {
    paid: 'var(--chart-paid)',
    sent: 'var(--chart-sent)',
    overdue: 'var(--chart-overdue)',
    draft: 'var(--chart-draft)',
    cancelled: 'var(--chart-cancelled)',
};

/**
 * Graphique en anneau (Donut) interactif affichant la répartition des factures par statut.
 * Utilise <Sector> (Recharts 3) au lieu de <Cell> déprécié et fournit une légende clavier complète.
 *
 * @param {{
 *     breakdown: Array<{ status: string, label: string, count: number, amount: number }>,
 *     collectionRate: number,
 *     selectedStatus?: string,
 *     onSelectStatus?: ((status: string) => void)|null,
 *     currency?: string
 * }} props
 */
export default function StatusDonutChart({
    breakdown = [],
    collectionRate = 0,
    selectedStatus = '',
    onSelectStatus = null,
    currency = 'XOF',
}) {
    const headingId = useId();
    const prefersReducedMotion = useReducedMotion();

    const totalCount = breakdown.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
    const totalAmount = breakdown.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
    const dataKey = totalAmount > 0 ? 'amount' : 'count';
    const totalMetric = totalAmount > 0 ? totalAmount : totalCount;
    const chartData = breakdown
        .filter((item) => (Number(item[dataKey]) || 0) > 0)
        .map((item) => ({
            ...item,
            fill: STATUS_COLORS[item.status] || 'var(--chart-draft)',
        }));

    const renderTooltip = ({ active, payload }) => {
        const item = payload?.[0]?.payload;
        if (!active || !item) {
            return null;
        }

        const color = STATUS_COLORS[item.status] || 'var(--chart-draft)';
        return (
            <ChartTooltipCard
                active={active}
                title={item.label}
                rows={[
                    { label: 'Montant', color, value: formatCurrency(item.amount || 0, currency) },
                    { label: 'Volume', value: `${item.count || 0} facture(s)` },
                ]}
            />
        );
    };

    const renderSector = (props) => {
        const status = props?.payload?.status;
        const color = STATUS_COLORS[status] || 'var(--chart-draft)';
        const isDimmed = Boolean(selectedStatus && selectedStatus !== status);

        return (
            <Sector
                {...props}
                fill={color}
                fillOpacity={isDimmed ? 0.35 : 1}
                style={{ cursor: onSelectStatus ? 'pointer' : 'default' }}
            />
        );
    };

    return (
        <section className="dashboard-chart-card" aria-labelledby={headingId}>
            <div className="dashboard-chart-header">
                <div>
                    <h2 id={headingId} className="dashboard-chart-title">Répartition par statut</h2>
                    <p className="dashboard-chart-subtitle">
                        {onSelectStatus
                            ? "Cliquez sur un statut pour filtrer l'ensemble des indicateurs"
                            : 'Distribution des factures par état'}
                    </p>
                </div>
            </div>

            {totalCount === 0 ? (
                <div className="chart-empty-state">
                    <PieChartIcon size={30} aria-hidden="true" />
                    <p>Aucune facture à répartir sur cette sélection.</p>
                </div>
            ) : (
                <div className="donut-layout">
                    <p className="sr-only">
                        Taux de recouvrement global : {collectionRate} % sur {totalCount} facture(s).
                    </p>

                    <div className="donut-visual-wrap" aria-hidden="true">
                        <ResponsiveContainer width="100%" height="100%" minWidth={0}>
                            <PieChart accessibilityLayer={false}>
                                <Pie
                                    data={chartData}
                                    dataKey={dataKey}
                                    nameKey="label"
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={56}
                                    outerRadius={82}
                                    paddingAngle={2}
                                    rootTabIndex={-1}
                                    shape={renderSector}
                                    isAnimationActive={!prefersReducedMotion}
                                    onClick={(entry) => {
                                        if (onSelectStatus && entry?.status) {
                                            onSelectStatus(entry.status);
                                        }
                                    }}
                                />
                                <Tooltip content={renderTooltip} allowEscapeViewBox={{ x: true, y: true }} />
                            </PieChart>
                        </ResponsiveContainer>

                        <div className="donut-center-metric">
                            <span className="donut-center-value">{collectionRate}%</span>
                            <span className="donut-center-caption">Recouvrement</span>
                        </div>
                    </div>

                    <div
                        className="donut-legend-list"
                        role={onSelectStatus ? 'group' : undefined}
                        aria-label={onSelectStatus ? 'Filtrer par statut de facture' : undefined}
                    >
                        {breakdown.map((item) => {
                            const isSelected = selectedStatus === item.status;
                            const color = STATUS_COLORS[item.status] || 'var(--chart-draft)';
                            const sharePct = totalMetric > 0
                                ? Math.round(((Number(item[dataKey]) || 0) / totalMetric) * 100)
                                : 0;

                            const rowContent = (
                                <>
                                    <span className="donut-legend-left">
                                        <span
                                            className="chart-legend-swatch"
                                            style={{ backgroundColor: color }}
                                            aria-hidden="true"
                                        />
                                        <span className="donut-legend-label">{item.label}</span>
                                        <span className="donut-legend-count">
                                            ({item.count} · {sharePct}%)
                                        </span>
                                    </span>
                                    <span className="donut-legend-amount">
                                        {formatCurrency(item.amount, currency)}
                                    </span>
                                </>
                            );

                            if (!onSelectStatus) {
                                return (
                                    <div key={item.status} className="donut-legend-btn">
                                        {rowContent}
                                    </div>
                                );
                            }

                            return (
                                <button
                                    key={item.status}
                                    type="button"
                                    className={`donut-legend-btn is-interactive ${isSelected ? 'is-active' : ''}`}
                                    aria-pressed={isSelected}
                                    onClick={() => onSelectStatus(item.status)}
                                >
                                    {rowContent}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </section>
    );
}
