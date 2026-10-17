import { AppShell, NavItem } from '@/components/AppShell';
import { HomeIcon } from '@/components/Icons';
import { PropsWithChildren } from 'react';

export default function BarberLayout({ title, children }: PropsWithChildren<{ title: string }>) {
    const nav: NavItem[] = [
        { label: 'Antrian Saya', route: 'barber.dashboard', url: route('barber.dashboard'), active: route().current('barber.dashboard'), icon: <HomeIcon /> },
    ];

    return (
        <AppShell title={title} nav={nav}>
            {children}
        </AppShell>
    );
}
