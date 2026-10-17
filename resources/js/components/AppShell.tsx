import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';
import { FlashMessages } from '@/components/FlashMessages';

export interface NavItem {
    label: string;
    route: string;
    url: string;
    active: boolean;
    icon?: ReactNode;
}

export function AppShell({
    title,
    heading,
    nav,
    actions,
    children,
    fab,
}: PropsWithChildren<{
    title: string;
    heading?: ReactNode;
    nav: NavItem[];
    actions?: ReactNode;
    fab?: ReactNode;
}>) {
    const { props } = usePage<PageProps>();
    const user = props.auth.user;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);

    const logout = () => router.post(route('logout'));

    return (
        <>
            <div className="min-h-screen bg-ink-50">
                <div
                    className={cn(
                        'fixed inset-0 z-30 bg-ink-950/40 lg:hidden',
                        mobileOpen ? 'block' : 'hidden',
                    )}
                    onClick={() => setMobileOpen(false)}
                />

                <aside
                    className={cn(
                        'fixed inset-y-0 left-0 z-40 w-64 transform border-r border-ink-800 bg-ink-950 transition-transform lg:translate-x-0',
                        mobileOpen ? 'translate-x-0' : '-translate-x-full',
                    )}
                >
                    <div className="flex h-16 items-center gap-2 border-b border-ink-800 px-5">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gold-400 text-xs font-black text-ink-950">
                            BS
                        </span>
                        <span className="text-sm font-bold text-white">BarberSaaS</span>
                    </div>
                    <nav className="flex flex-col gap-1 p-3">
                        {nav.map((item) => (
                            <Link
                                key={item.route}
                                href={item.url}
                                className={cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                    item.active
                                        ? 'bg-gold-400/15 text-gold-300'
                                        : 'text-ink-300 hover:bg-ink-900 hover:text-white',
                                )}
                            >
                                {item.icon && <span className="h-4 w-4 shrink-0">{item.icon}</span>}
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                </aside>

                <div className="lg:pl-64">
                    <header className="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-ink-200 bg-white/90 px-4 backdrop-blur sm:px-6">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                className="rounded-lg p-2 text-ink-500 hover:bg-ink-100 lg:hidden"
                                onClick={() => setMobileOpen(true)}
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <span className="text-sm font-semibold text-ink-900">{title}</span>
                        </div>
                        <div className="flex items-center gap-3">
                            {actions}
                            <div className="relative">
                                <button
                                    type="button"
                                    onClick={() => setMenuOpen((v) => !v)}
                                    className="flex items-center gap-2 rounded-full border border-ink-200 py-1 pl-1 pr-3 text-sm hover:bg-ink-50"
                                >
                                    <span className="flex h-7 w-7 items-center justify-center rounded-full bg-ink-900 text-xs font-bold text-gold-400">
                                        {user?.name?.charAt(0)?.toUpperCase() ?? '?'}
                                    </span>
                                    <span className="hidden max-w-[120px] truncate font-medium text-ink-700 sm:block">
                                        {user?.name}
                                    </span>
                                </button>
                                {menuOpen && (
                                    <>
                                        <div className="fixed inset-0 z-10" onClick={() => setMenuOpen(false)} />
                                        <div className="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-xl border border-ink-200 bg-white shadow-lg">
                                            <div className="border-b border-ink-100 px-4 py-3">
                                                <p className="truncate text-sm font-semibold text-ink-900">{user?.name}</p>
                                                <p className="truncate text-xs text-ink-500">{user?.email}</p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={logout}
                                                className="block w-full px-4 py-2.5 text-left text-sm font-medium text-rose-600 hover:bg-rose-50"
                                            >
                                                Keluar
                                            </button>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </header>

                    <main className="px-4 py-6 sm:px-6 lg:px-8">
                        {heading && <div className="mb-5">{heading}</div>}
                        <FlashMessages />
                        {children}
                    </main>
                </div>
                {fab}
            </div>
        </>
    );
}
