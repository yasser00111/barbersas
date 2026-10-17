import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { LinkButton } from '@/components/ui/Button';
import { Card, CardBody, CardHeader, StatCard } from '@/components/ui/Card';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface Stats {
    tenants_total: number;
    tenants_active: number;
    tenants_pending: number;
    tenants_suspended: number;
    subscriptions_active: number;
    subscriptions_expired: number;
    invoices_unpaid: number;
    payments_pending: number;
    revenue_month: number;
    revenue_year: number;
    revenue_total: number;
}

interface RecentTenant {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    created_at: string | null;
}

interface PendingPayment {
    id: number;
    amount: number;
    method: string;
    invoice_number: string | null;
    created_at: string | null;
}

interface RevenuePoint {
    period: string;
    total: number;
}

export default function Dashboard({
    stats,
    recentTenants,
    pendingPayments,
    revenueSeries,
}: {
    stats: Stats;
    recentTenants: RecentTenant[];
    pendingPayments: PendingPayment[];
    revenueSeries: RevenuePoint[];
}) {
    const maxRevenue = Math.max(1, ...revenueSeries.map((r) => r.total));

    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Dasbor Super Admin</h1>
                <p className="text-sm text-ink-500">Ringkasan platform BarberSaaS.</p>
            </div>
            <LinkButton href={route('super-admin.payments.index')}>Verifikasi Pembayaran</LinkButton>
        </div>
    );

    return (
        <SuperAdminLayout title="Dasbor" heading={heading}>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Total Barbershop" value={stats.tenants_total} accent />
                <StatCard label="Aktif" value={stats.tenants_active} />
                <StatCard label="Menunggu Aktivasi" value={stats.tenants_pending} />
                <StatCard label="Ditangguhkan" value={stats.tenants_suspended} />
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Langganan Aktif" value={stats.subscriptions_active} />
                <StatCard label="Invoice Belum Dibayar" value={stats.invoices_unpaid} />
                <StatCard label="Pembayaran Menunggu" value={stats.payments_pending} accent />
                <StatCard label="Pendapatan Bulan Ini" value={formatRupiah(stats.revenue_month)} />
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <StatCard label="Pendapatan Tahun Ini" value={formatRupiah(stats.revenue_year)} />
                <StatCard label="Total Pendapatan" value={formatRupiah(stats.revenue_total)} />
            </div>

            <Card className="mt-6">
                <CardHeader title="Tren Pendapatan" description="12 periode terakhir" />
                <CardBody>
                    {revenueSeries.length === 0 ? (
                        <EmptyState title="Belum ada data pendapatan" />
                    ) : (
                        <div className="flex h-48 items-end gap-2 overflow-x-auto">
                            {[...revenueSeries].reverse().map((row) => (
                                <div key={row.period} className="flex min-w-[40px] flex-1 flex-col items-center gap-1">
                                    <span className="text-[10px] font-semibold text-ink-500">{formatRupiah(row.total)}</span>
                                    <div
                                        className="w-full rounded-t bg-gold-400"
                                        style={{ height: `${Math.max(4, (row.total / maxRevenue) * 160)}px` }}
                                    />
                                    <span className="text-[10px] text-ink-400">{row.period}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </CardBody>
            </Card>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Barbershop Terbaru" action={<LinkButton href={route('super-admin.tenants.index')} variant="ghost" size="sm">Lihat semua</LinkButton>} />
                    <CardBody className="p-0">
                        {recentTenants.length === 0 ? (
                            <EmptyState title="Belum ada barbershop" />
                        ) : (
                            <ul className="divide-y divide-ink-100">
                                {recentTenants.map((tenant) => (
                                    <li key={tenant.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                        <div>
                                            <Link href={route('super-admin.tenants.show', tenant.slug)} className="text-sm font-semibold text-ink-900 hover:underline">
                                                {tenant.name}
                                            </Link>
                                            <p className="text-xs text-ink-500">/{tenant.slug} &middot; {tenant.created_at ? formatDate(tenant.created_at) : '-'}</p>
                                        </div>
                                        <StatusBadge status={tenant.status} label={tenant.status_label} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Pembayaran Menunggu" action={<LinkButton href={route('super-admin.payments.index')} variant="ghost" size="sm">Verifikasi</LinkButton>} />
                    <CardBody className="p-0">
                        {pendingPayments.length === 0 ? (
                            <EmptyState title="Tidak ada pembayaran menunggu" />
                        ) : (
                            <ul className="divide-y divide-ink-100">
                                {pendingPayments.map((payment) => (
                                    <li key={payment.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                        <div>
                                            <p className="text-sm font-semibold text-ink-900">{formatRupiah(payment.amount)}</p>
                                            <p className="text-xs text-ink-500">{payment.invoice_number ?? '-'} &middot; {payment.created_at ? formatDate(payment.created_at) : '-'}</p>
                                        </div>
                                        <span className="text-xs font-medium text-ink-500">{payment.method === 'manual' ? 'Transfer' : 'Online'}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>
            </div>
        </SuperAdminLayout>
    );
}
