import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface Flash {
    success?: string | null;
    error?: string | null;
}

export function FlashMessages() {
    const { flash } = usePage().props as { flash?: Flash };
    const [visible, setVisible] = useState<Flash>({});

    useEffect(() => {
        setVisible({ success: flash?.success ?? undefined, error: flash?.error ?? undefined });

        if (flash?.success || flash?.error) {
            const timer = setTimeout(() => setVisible({}), 4000);

            return () => clearTimeout(timer);
        }
    }, [flash?.success, flash?.error]);

    if (!visible.success && !visible.error) return null;

    const isSuccess = Boolean(visible.success);

    return (
        <div
            className={
                'mb-4 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm ' +
                (isSuccess ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800')
            }
        >
            <span>{visible.success ?? visible.error}</span>
            <button type="button" onClick={() => setVisible({})} className="text-xs font-semibold opacity-60 hover:opacity-100">
                Tutup
            </button>
        </div>
    );
}
