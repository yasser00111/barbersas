import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

export function Card({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div className={cn('rounded-xl border border-ink-200/70 bg-white shadow-sm', className)}>
            {children}
        </div>
    );
}

export function CardHeader({ title, description, action, className }: { title: ReactNode; description?: ReactNode; action?: ReactNode; className?: string }) {
    return (
        <div className={cn('flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-5 py-4', className)}>
            <div>
                <h3 className="text-sm font-semibold text-ink-900">{title}</h3>
                {description && <p className="mt-0.5 text-xs text-ink-500">{description}</p>}
            </div>
            {action}
        </div>
    );
}

export function CardBody({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('p-5', className)}>{children}</div>;
}

export function StatCard({ label, value, hint, accent }: { label: string; value: ReactNode; hint?: ReactNode; accent?: boolean }) {
    return (
        <div className={cn('rounded-xl border border-ink-200/70 bg-white p-4 shadow-sm', accent && 'border-gold-200 bg-gold-50')}>
            <p className="text-xs font-medium uppercase tracking-wide text-ink-500">{label}</p>
            <p className="mt-1 text-2xl font-bold text-ink-900">{value}</p>
            {hint && <p className="mt-1 text-xs text-ink-500">{hint}</p>}
        </div>
    );
}
