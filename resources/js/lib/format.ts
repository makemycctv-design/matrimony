/**
 * Indian-locale formatting helpers (currency in INR with lakh/crore grouping,
 * dates in dd MMM yyyy, and masked mobile numbers).
 */

export function formatCurrency(amountInRupees: number, withDecimals = false): string {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: withDecimals ? 2 : 0,
    }).format(amountInRupees);
}

/** Amounts are stored as integer paise on the backend; convert for display. */
export function formatPaise(amountInPaise: number, withDecimals = true): string {
    return formatCurrency(amountInPaise / 100, withDecimals);
}

export function formatDate(value: string | Date | null | undefined): string {
    if (!value) return '—';
    const date = typeof value === 'string' ? new Date(value) : value;
    return new Intl.DateTimeFormat('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat('en-IN').format(value);
}

export function maskMobile(mobile: string | null | undefined): string {
    if (!mobile) return '—';
    return mobile.replace(/(\+?\d{2,3})(\d+)(\d{3})/, (_, a, b, c) => `${a}${'*'.repeat(b.length)}${c}`);
}

export function heightFromCm(cm: number | null | undefined): string {
    if (!cm) return '—';
    const totalInches = cm / 2.54;
    const feet = Math.floor(totalInches / 12);
    const inches = Math.round(totalInches % 12);
    return `${feet}' ${inches}" (${cm} cm)`;
}
