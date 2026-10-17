import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function PublicLayout({ title, children, transparent = false }: PropsWithChildren<{ title: string; transparent?: boolean }>) {
    const { props } = usePage<PageProps>();
    const user = props.auth?.user;

    const dashboardUrl = user?.role === 'super_admin'
        ? route('super-admin.dashboard')
        : user?.role === 'barber'
          ? route('barber.dashboard')
          : route('admin.dashboard');

    const logout = () => router.post(route('logout'));

    return (
        <>
            <Head title={title} />
            <div className={cn('min-h-screen', transparent ? 'bg-white' : 'bg-ink-50')}>
                <header className="sticky top-0 z-30 border-b border-ink-200/70 bg-white/80 backdrop-blur">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                        <Link href={route('home')} className="flex items-center gap-2">
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-900 text-xs font-black text-gold-400">
                                BS
                            </span>
                            <span className="text-base font-bold tracking-tight text-ink-900">BarberSaaS</span>
                        </Link>
                        <nav className="flex items-center gap-1 text-sm font-medium">
                            <Link href={route('home')} className="rounded-lg px-3 py-2 text-ink-600 hover:bg-ink-100">
                                Beranda
                            </Link>
                            <Link href={route('pricing')} className="rounded-lg px-3 py-2 text-ink-600 hover:bg-ink-100">
                                Harga
                            </Link>
                            {user ? (
                                <>
                                    <Link
                                        href={dashboardUrl}
                                        className="rounded-lg bg-ink-900 px-3 py-2 text-white hover:bg-ink-800"
                                    >
                                        Dasbor ({user.name.split(' ')[0]})
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={logout}
                                        className="rounded-lg border border-ink-200 px-3 py-2 text-ink-700 hover:bg-ink-50"
                                    >
                                        Keluar
                                    </button>
                                </>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="rounded-lg border border-ink-200 px-3 py-2 text-ink-700 hover:bg-ink-50"
                                    >
                                        Masuk
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="rounded-lg bg-ink-900 px-3 py-2 text-white hover:bg-ink-800"
                                    >
                                        Daftar
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>
                <main>{children}</main>
                <footer className="border-t border-ink-200/70 bg-white">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-8 text-xs text-ink-400 sm:flex-row sm:px-6">
                        <p>&copy; {new Date().getFullYear()} BarberSaaS. Platform antrian barbershop.</p>
                        <div className="flex gap-4">
                            <Link href={route('pricing')}>Harga</Link>
                            <Link href={route('login')}>Masuk</Link>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
