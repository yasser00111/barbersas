export function cn(...classes: Array<string | false | null | undefined>): string {
    return classes.filter(Boolean).join(' ');
}

export function formatRupiah(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function formatTime(value: string | null | undefined): string {
    if (!value) return '-';

    return value.slice(0, 5);
}
