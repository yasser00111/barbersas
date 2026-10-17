import { cn } from '@/lib/utils';
import { ReactNode, useEffect } from 'react';

export function Modal({ open, onClose, title, children, footer, maxWidth = 'max-w-lg' }: {
    open: boolean;
    onClose: () => void;
    title?: ReactNode;
    children: ReactNode;
    footer?: ReactNode;
    maxWidth?: string;
}) {
    useEffect(() => {
        if (!open) return;
        const handler = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
        window.addEventListener('keydown', handler);

        return () => window.removeEventListener('keydown', handler);
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-ink-950/50 p-4 sm:items-center">
            <div className="absolute inset-0" onClick={onClose} />
            <div className={cn('relative z-10 w-full overflow-hidden rounded-xl bg-white shadow-xl', maxWidth)}>
                {title && (
                    <div className="border-b border-ink-100 px-5 py-4">
                        <h3 className="text-sm font-semibold text-ink-900">{title}</h3>
                    </div>
                )}
                <div className="max-h-[65vh] overflow-y-auto px-5 py-4">{children}</div>
                {footer && <div className="flex justify-end gap-2 border-t border-ink-100 bg-ink-50 px-5 py-3">{footer}</div>}
            </div>
        </div>
    );
}
