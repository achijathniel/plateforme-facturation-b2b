import { formatCurrency } from '../../Utils/formatters';

/**
 * Infobulle partagée pour les graphiques Recharts (courbes/barres et donut).
 *
 * @param {{
 *     active?: boolean,
 *     title: string,
 *     rows: Array<{ label: string, value: string, color?: string }>
 * }} props
 */
export function ChartTooltipCard({ active, title, rows }) {
    if (!active || !rows || rows.length === 0) {
        return null;
    }

    return (
        <div className="chart-tooltip">
            <p className="chart-tooltip-label">{title}</p>
            {rows.map((row) => (
                <div key={row.label} className="chart-tooltip-row">
                    <span className="chart-tooltip-name">
                        {row.color && (
                            <span
                                className="chart-legend-swatch"
                                style={{ backgroundColor: row.color }}
                            />
                        )}
                        <span>{row.label}</span>
                    </span>
                    <span className="chart-tooltip-value">{row.value}</span>
                </div>
            ))}
        </div>
    );
}

/**
 * Formate un axe numérique en notation compacte lisible (k, M).
 *
 * @param {number|string} value
 * @returns {string}
 */
export function formatCompactCurrencyAxis(value) {
    const num = Number(value) || 0;
    if (num >= 1_000_000) {
        return `${(num / 1_000_000).toFixed(1).replace('.0', '')}M`;
    }
    if (num >= 1_000) {
        return `${Math.round(num / 1_000)}k`;
    }
    return String(num);
}

export { formatCurrency };
