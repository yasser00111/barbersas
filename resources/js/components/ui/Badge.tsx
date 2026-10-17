import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

type Tone = 'neutral' | 'gold' | 'green' | 'red' | 'amber' | 'blue' | 'gray';

const tones: Record<Tone, string> = {
    neutral: 'bg-ink-100 text-ink-700',
    gold: 'bg-gold-100 text-gold-800',
    green: 'bg-emerald-100 text-emerald-700',
    red: 'bg-rose-100 text-rose-700',
    amber: 'bg-amber-100 text-amber-700',
    blue: 'bg-sky-100 text-sky-700',
    gray: 'bg-ink-100 text-ink-500',
};

export function Badge({ tone = 'neutral', children, className }: { tone?: Tone; children: ReactNode; className?: string }) {
    return (
        <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', tones[tone], className)}>
            {children}
        </span>
    );
}

const statusMap: Record<string, Tone> = {
    active: 'green',
    paid: 'green',
    completed: 'green',
    verified: 'green',
    waiting: 'amber',
    pending: 'amber',
    called: 'blue',
    in_service: 'blue',
    suspended: 'red',
    expired: 'red',
    cancelled: 'red',
    rejected: 'red',
    failed: 'red',
    no_show: 'gray',
    archived: 'gray',
};

export function StatusBadge({ status, label, className }: { status?: string | null; label?: string | null; className?: string }) {
    const key = (status ?? '').toLowerCase();

    return (
        <Badge tone={statusMap[key] ?? 'neutral'} className={className}>
            {label ?? status ?? '-'}
        </Badge>
    );
}
