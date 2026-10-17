import { AppShell, NavItem } from '@/components/AppShell';
import { CardIcon, ClockIcon, CogIcon, HomeIcon, QrIcon, QueueIcon, ReportIcon, ScissorsIcon, UsersIcon } from '@/components/Icons';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function AdminLayout({ title, heading, actions, children }: PropsWithChildren<{ title: string; heading?: React.ReactNode; actions?: React.ReactNode }>) {
    const { props } = usePage<PageProps>();
    const tenant = props.tenant;

    const nav: NavItem[] = [
        { label: 'Dasbor', route: 'admin.dashboard', url: route('admin.dashboard'), active: route().current('admin.dashboard'), icon: <HomeIcon /> },
        { label: 'Antrian', route: 'admin.queues.index', url: route('admin.queues.index'), active: route().current('admin.queues.*'), icon: <QueueIcon /> },
        { label: 'Barber', route: 'admin.barbers.index', url: route('admin.barbers.index'), active: route().current('admin.barbers.*'), icon: <UsersIcon /> },
        { label: 'Layanan', route: 'admin.services.index', url: route('admin.services.index'), active: route().current('admin.services.*'), icon: <ScissorsIcon /> },
        { label: 'Pelanggan', route: 'admin.customers.index', url: route('admin.customers.index'), active: route().current('admin.customers.*'), icon: <UsersIcon /> },
        { label: 'Laporan', route: 'admin.reports.index', url: route('admin.reports.index'), active: route().current('admin.reports.*'), icon: <ReportIcon /> },
        { label: 'Langganan', route: 'admin.subscription.index', url: route('admin.subscription.index'), active: route().current('admin.subscription.*'), icon: <CardIcon /> },
        { label: 'QR Code', route: 'admin.qr.index', url: route('admin.qr.index'), active: route().current('admin.qr.*'), icon: <QrIcon /> },
        { label: 'Pengaturan', route: 'admin.settings.edit', url: route('admin.settings.edit'), active: route().current('admin.settings.*'), icon: <CogIcon /> },
    ];

    const daysRemaining = tenant?.subscription?.days_remaining;
    const expiringSoon = typeof daysRemaining === 'number' && daysRemaining <= 7;

    return (
        <AppShell title={title} heading={heading} nav={nav} actions={actions}>
            {expiringSoon && (
                <div className="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <span className="flex items-center gap-2">
                        <ClockIcon className="h-4 w-4" />
                        Langganan Anda berakhir dalam {daysRemaining} hari. Segera perpanjang.
                    </span>
                    <a href={route('admin.subscription.index')} className="font-semibold underline">
                        Perpanjang
                    </a>
                </div>
            )}
            {children}
        </AppShell>
    );
}
