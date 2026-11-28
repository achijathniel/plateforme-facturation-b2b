import { useId, useState } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { BarChart3, Table2, TrendingUp } from 'lucide-react';
import useReducedMotion from '../../Hooks/useReducedMotion';
import { ChartTooltipCard, formatCompactCurrencyAxis, formatCurrency } from './ChartTooltip';

const TREND_SERIES = [
    { key: 'billed', label: 'Facturé', color: 'var(--chart-billed)', dashArray: undefined },
    { key: 'paid', label: 'Encaissé', color: 'var(--chart-paid)', dashArray: '6 3' },
    { key: 'overdue', label: 'En retard', color: 'var(--chart-overdue)', dashArray: '2 2' },
];

const CHART_MARGIN = { top: 8, right: 8, left: 0, bottom: 4 };
const X_AXIS_TICK = { fontSize: 11, fill: 'var(--text-muted)' };
const Y_AXIS_TICK = { fontSize: 11, fill: 'var(--text-muted)' };
const AXIS_LINE = { stroke: 'var(--chart-grid)' };
const BAR_RADIUS = [4, 4, 0, 0];

/**
 * Graphique interactif d'évolution temporelle (Barres / Courbes / Tableau accessible).
 *
 * @param {{
 *     series: Array<{ key: string, label: string, billed: number, paid: number, overdue: number, count: number }>,
 *     period?: string,
 *     currency?: string
 * }} props
 */
export default function RevenueTrendChart({ series = [], period = '12m', currency = 'XOF' }) {
    const [chartMode, setChartMode] = useState('bar');
    const headingId = useId();
    const gradientPrefix = useId().replace(/:/g, '');
    const prefersReducedMotion = useReducedMotion();

    const hasValues = series.some((item) => item.billed > 0 || item.paid > 0 || item.overdue > 0);
    const granularityText = period === '30d' ? 'par jour' : 'par mois';
    const billedFillId = `billedFill-${gradientPrefix}`;
    const paidFillId = `paidFill-${gradientPrefix}`;

    const renderTooltip = ({ active, payload, label }) => {
        const row = payload?.[0]?.payload;
        if (!active || !row) {
            return null;
        }

        const rows = [
            ...TREND_SERIES.map((serie) => ({
                label: serie.label,
                color: serie.color,
                value: formatCurrency(row[serie.key] || 0, currency),
            })),
            { label: 'Volume', value: `${row.count || 0} facture(s)` },
        ];

        return <ChartTooltipCard active={active} title={label} rows={rows} />;
    };

    const totalPeriodBilled = series.reduce((acc, item) => acc + (Number(item.billed) || 0), 0);
    const totalPeriodPaid = series.reduce((acc, item) => acc + (Number(item.paid) || 0), 0);

    return (
        <section className="dashboard-chart-card" aria-labelledby={headingId}>
            <div className="dashboard-chart-header">
                <div>
                    <h2 id={headingId} className="dashboard-chart-title">
                        Évolution financière ({granularityText})
                    </h2>
                    <p className="dashboard-chart-subtitle">
                        Comparatif des montants facturés, encaissés et en retard ({currency})
                    </p>
                </div>

                <div className="chart-mode-switch" role="group" aria-label="Mode d'affichage des données">
                    <button
                        type="button"
                        className={`chart-mode-btn ${chartMode === 'bar' ? 'is-active' : ''}`}
                        aria-pressed={chartMode === 'bar'}
                        onClick={() => setChartMode('bar')}
                    >
                        <BarChart3 size={14} aria-hidden="true" />
                        <span>Barres</span>
                    </button>
                    <button
                        type="button"
                        className={`chart-mode-btn ${chartMode === 'area' ? 'is-active' : ''}`}
                        aria-pressed={chartMode === 'area'}
                        onClick={() => setChartMode('area')}
                    >
                        <TrendingUp size={14} aria-hidden="true" />
                        <span>Courbes</span>
                    </button>
                    <button
                        type="button"
                        className={`chart-mode-btn ${chartMode === 'table' ? 'is-active' : ''}`}
                        aria-pressed={chartMode === 'table'}
                        onClick={() => setChartMode('table')}
                    >
                        <Table2 size={14} aria-hidden="true" />
                        <span>Tableau</span>
                    </button>
                </div>
            </div>

            {chartMode !== 'table' && (
                <div className="chart-series-legend" aria-hidden="true">
                    {TREND_SERIES.map((serie) => (
                        <span key={serie.key} className="chart-series-legend-item">
                            <span className="chart-legend-swatch" style={{ backgroundColor: serie.color }} />
                            <span>{serie.label}</span>
                        </span>
                    ))}
                </div>
            )}

            <p className="sr-only">
                Cumul sur la période : {formatCurrency(totalPeriodBilled, currency)} facturés et{' '}
                {formatCurrency(totalPeriodPaid, currency)} encaissés. Utilisez le bouton « Tableau » pour lire le détail complet au clavier.
            </p>

            {!hasValues ? (
                <div className="chart-empty-state">
                    <BarChart3 size={30} aria-hidden="true" />
                    <p>Aucune donnée financière sur cette période et ces filtres.</p>
                </div>
            ) : chartMode === 'table' ? (
                <div className="chart-data-table-wrap">
                    <table className="chart-data-table" aria-label={`Détail financier ${granularityText}`}>
                        <thead>
                            <tr>
                                <th scope="col">Période</th>
                                <th scope="col" className="is-num">Factures</th>
                                <th scope="col" className="is-num">Facturé ({currency})</th>
                                <th scope="col" className="is-num">Encaissé ({currency})</th>
                                <th scope="col" className="is-num">En retard ({currency})</th>
                            </tr>
                        </thead>
                        <tbody>
                            {series.map((point) => (
                                <tr key={point.key}>
                                    <th scope="row">{point.label}</th>
                                    <td className="is-num">{point.count}</td>
                                    <td className="is-num">{formatCurrency(point.billed)}</td>
                                    <td className="is-num">{formatCurrency(point.paid)}</td>
                                    <td className="is-num">{formatCurrency(point.overdue)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="chart-canvas-wrap" aria-hidden="true">
                    <ResponsiveContainer width="100%" height="100%" minWidth={0}>
                        {chartMode === 'bar' ? (
                            <BarChart data={series} margin={CHART_MARGIN} accessibilityLayer={false}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--chart-grid)" />
                                <XAxis
                                    dataKey="label"
                                    tick={X_AXIS_TICK}
                                    axisLine={AXIS_LINE}
                                    tickLine={false}
                                    minTickGap={16}
                                />
                                <YAxis
                                    tickFormatter={formatCompactCurrencyAxis}
                                    tick={Y_AXIS_TICK}
                                    axisLine={false}
                                    tickLine={false}
                                    width={48}
                                />
                                <Tooltip content={renderTooltip} allowEscapeViewBox={{ x: true, y: true }} />
                                {TREND_SERIES.map((serie) => (
                                    <Bar
                                        key={serie.key}
                                        dataKey={serie.key}
                                        name={serie.label}
                                        fill={serie.color}
                                        radius={BAR_RADIUS}
                                        maxBarSize={26}
                                        isAnimationActive={!prefersReducedMotion}
                                    />
                                ))}
                            </BarChart>
                        ) : (
                            <AreaChart data={series} margin={CHART_MARGIN} accessibilityLayer={false}>
                                <defs>
                                    <linearGradient id={billedFillId} x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="var(--chart-billed)" stopOpacity={0.22} />
                                        <stop offset="95%" stopColor="var(--chart-billed)" stopOpacity={0.02} />
                                    </linearGradient>
                                    <linearGradient id={paidFillId} x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="var(--chart-paid)" stopOpacity={0.24} />
                                        <stop offset="95%" stopColor="var(--chart-paid)" stopOpacity={0.02} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--chart-grid)" />
                                <XAxis
                                    dataKey="label"
                                    tick={X_AXIS_TICK}
                                    axisLine={AXIS_LINE}
                                    tickLine={false}
                                    minTickGap={16}
                                />
                                <YAxis
                                    tickFormatter={formatCompactCurrencyAxis}
                                    tick={Y_AXIS_TICK}
                                    axisLine={false}
                                    tickLine={false}
                                    width={48}
                                />
                                <Tooltip content={renderTooltip} allowEscapeViewBox={{ x: true, y: true }} />
                                <Area
                                    type="monotone"
                                    dataKey="billed"
                                    name="Facturé"
                                    stroke="var(--chart-billed)"
                                    strokeWidth={2}
                                    fill={`url(#${billedFillId})`}
                                    isAnimationActive={!prefersReducedMotion}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="paid"
                                    name="Encaissé"
                                    stroke="var(--chart-paid)"
                                    strokeWidth={2}
                                    strokeDasharray="6 3"
                                    fill={`url(#${paidFillId})`}
                                    isAnimationActive={!prefersReducedMotion}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="overdue"
                                    name="En retard"
                                    stroke="var(--chart-overdue)"
                                    strokeWidth={2}
                                    strokeDasharray="2 2"
                                    fill="none"
                                    isAnimationActive={!prefersReducedMotion}
                                />
                            </AreaChart>
                        )}
                    </ResponsiveContainer>
                </div>
            )}
        </section>
    );
}
