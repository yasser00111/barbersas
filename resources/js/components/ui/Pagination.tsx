import { Link } from '@inertiajs/react';

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export function Pagination({ paginator }: { paginator: Paginator<unknown> }) {
    if (!paginator || paginator.last_page <= 1) return null;

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 px-1 py-3">
            <p className="text-xs text-ink-500">
                Menampilkan {paginator.from ?? 0}–{paginator.to ?? 0} dari {paginator.total} data
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {paginator.links.map((link, i) => {
                    const label = link.label
                        .replace('&laquo;', '«')
                        .replace('&raquo;', '»')
                        .replace('Previous', 'Sebelumnya')
                        .replace('Next', 'Berikutnya');

                    if (!link.url) {
                        return (
                            <span key={i} className="rounded-md px-3 py-1.5 text-xs text-ink-300">
                                {label}
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={i}
                            href={link.url}
                            preserveScroll
                            className={
                                'rounded-md px-3 py-1.5 text-xs font-medium transition ' +
                                (link.active ? 'bg-ink-900 text-white' : 'text-ink-600 hover:bg-ink-100')
                            }
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                })}
            </div>
        </div>
    );
}
