import { ReactNode } from 'react';

export function EmptyState({ title, description, action }: { title: string; description?: string; action?: ReactNode }) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-ink-100 text-ink-400">
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M20 13V7a2 2 0 00-2-2h-5l-2-2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4" />
                </svg>
            </div>
            <p className="text-sm font-semibold text-ink-700">{title}</p>
            {description && <p className="max-w-sm text-xs text-ink-500">{description}</p>}
            {action && <div className="mt-2">{action}</div>}
        </div>
    );
}
