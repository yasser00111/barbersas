import { FlashMessages } from '@/components/FlashMessages';
import { Head, Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function GuestLayout({ title, children }: PropsWithChildren<{ title: string }>) {
    return (
        <>
            <Head title={title} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-ink-50 px-4 py-10">
                <Link href={route('home')} className="mb-6 flex items-center gap-2">
                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-ink-900 text-sm font-black text-gold-400">
                        BS
                    </span>
                    <span className="text-lg font-bold tracking-tight text-ink-900">BarberSaaS</span>
                </Link>
                <div className="w-full max-w-md rounded-2xl border border-ink-200/70 bg-white p-6 shadow-sm sm:p-8">
                    <FlashMessages />
                    {children}
                </div>
                <p className="mt-6 text-center text-xs text-ink-400">
                    &copy; {new Date().getFullYear()} BarberSaaS. Platform antrian barbershop.
                </p>
            </div>
        </>
    );
}
