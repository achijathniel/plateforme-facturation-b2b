/**
 * Composant carte d'indicateur statistique et financier.
 *
 * @param {{
 *     label: string,
 *     value: string|number,
 *     currency?: string,
 *     icon: string,
 *     subtext?: string,
 *     highlightBlue?: boolean,
 *     valueColor?: string
 * }} props
 */
export default function MetricCard({
    label,
    value,
    currency,
    icon,
    subtext,
    highlightBlue = false,
    valueColor,
}) {
    return (
        <div className="metric-card">
            <div className="metric-card-top">
                <span className="metric-label">{label}</span>
                <div className="metric-icon-box">
                    <span role="img" aria-hidden="true">{icon}</span>
                </div>
            </div>
            <p className="metric-value" style={valueColor ? { color: valueColor } : undefined}>
                {value}
                {currency && (
                    <span
                        className="metric-currency"
                        style={valueColor ? { color: valueColor } : undefined}
                    >
                        {' '}{currency}
                    </span>
                )}
            </p>
            {subtext && (
                <p className={`metric-subtext ${highlightBlue ? 'highlight-blue' : ''}`}>
                    {subtext}
                </p>
            )}
        </div>
    );
}
