import { AppShell, NavItem } from '@/components/AppShell';
import { CardIcon, CogIcon, HomeIcon, InvoiceIcon, QueueIcon, ScissorsIcon, ShieldIcon, ShopIcon } from '@/components/Icons';
import { PropsWithChildren } from 'react';

export default function SuperAdminLayout({ title, heading, actions, children }: PropsWithChildren<{ title: string; heading?: React.ReactNode; actions?: React.ReactNode }>) {
    const nav: NavItem[] = [
        { label: 'Dasbor', route: 'super-admin.dashboard', url: route('super-admin.dashboard'), active: route().current('super-admin.dashboard'), icon: <HomeIcon /> },
        { label: 'Barbershop', route: 'super-admin.tenants.index', url: route('super-admin.tenants.index'), active: route().current('super-admin.tenants.*'), icon: <ShopIcon /> },
        { label: 'Langganan', route: 'super-admin.subscriptions.index', url: route('super-admin.subscriptions.index'), active: route().current('super-admin.subscriptions.*'), icon: <QueueIcon /> },
        { label: 'Invoice', route: 'super-admin.invoices.index', url: route('super-admin.invoices.index'), active: route().current('super-admin.invoices.*'), icon: <InvoiceIcon /> },
        { label: 'Pembayaran', route: 'super-admin.payments.index', url: route('super-admin.payments.index'), active: route().current('super-admin.payments.*'), icon: <CardIcon /> },
        { label: 'Paket Harga', route: 'super-admin.plans.index', url: route('super-admin.plans.index'), active: route().current('super-admin.plans.*'), icon: <ScissorsIcon /> },
        { label: 'Audit Log', route: 'super-admin.audit-logs.index', url: route('super-admin.audit-logs.index'), active: route().current('super-admin.audit-logs.*'), icon: <ShieldIcon /> },
        { label: 'Pengaturan', route: 'super-admin.settings.edit', url: route('super-admin.settings.edit'), active: route().current('super-admin.settings.*'), icon: <CogIcon /> },
    ];

    return (
        <AppShell title={title} heading={heading} nav={nav} actions={actions}>
            {children}
        </AppShell>
    );
}
