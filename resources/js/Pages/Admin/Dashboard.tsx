import { LinkButton } from '@/components/ui/Button';
import { Card, CardBody, CardHeader, StatCard } from '@/components/ui/Card';
import { StatusBadge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/EmptyState';
import AdminLayout from '@/layouts/AdminLayout';
import { formatRupiah } from '@/lib/utils';

interface QueueRow {
    id: number;
    number: number;
    status: string;
    status_label: string;
    customer: string | null;
    phone: string | null;
    service: string | null;
    barber: string | null;
}

interface Stats {
    today_total: number;
    today_waiting: number;
    today_in_service: number;
    today_completed: number;
    today_cancelled: number;
    active_barbers: number;
    active_services: number;
    revenue_today: number;
    revenue_week: number;
    revenue_month: number;
}

export default function Dashboard({ stats, nowServing, waitingList }: { stats: Stats; nowServing: QueueRow[]; waitingList: QueueRow[] }) {
    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Dasbor</h1>
                <p className="text-sm text-ink-500">Ringkasan operasional barbershop hari ini.</p>
            </div>
            <LinkButton href={route('admin.queues.index')}>Kelola Antrian</LinkButton>
        </div>
    );

    return (
        <AdminLayout title="Dasbor" heading={heading}>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Antrian Hari Ini" value={stats.today_total} accent />
                <StatCard label="Menunggu" value={stats.today_waiting} />
                <StatCard label="Sedang Dilayani" value={stats.today_in_service} />
                <StatCard label="Selesai" value={stats.today_completed} />
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Pendapatan Hari Ini" value={formatRupiah(stats.revenue_today)} accent />
                <StatCard label="Pendapatan Minggu Ini" value={formatRupiah(stats.revenue_week)} />
                <StatCard label="Pendapatan Bulan Ini" value={formatRupiah(stats.revenue_month)} />
                <StatCard label="Barber / Layanan Aktif" value={`${stats.active_barbers} / ${stats.active_services}`} />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Sedang Dilayani" description="Antrian yang dipanggil atau dalam layanan" action={<LinkButton href={route('admin.queues.index')} variant="ghost" size="sm">Lihat semua</LinkButton>} />
                    <CardBody className="p-0">
                        {nowServing.length === 0 ? (
                            <EmptyState title="Belum ada antrian aktif" />
                        ) : (
                            <ul className="divide-y divide-ink-100">
                                {nowServing.map((queue) => (
                                    <li key={queue.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-ink-900 text-sm font-bold text-gold-400">
                                                {queue.number}
                                            </span>
                                            <div>
                                                <p className="text-sm font-semibold text-ink-900">{queue.customer ?? '-'}</p>
                                                <p className="text-xs text-ink-500">{queue.service ?? '-'} &middot; {queue.barber ?? 'Barber mana saja'}</p>
                                            </div>
                                        </div>
                                        <StatusBadge status={queue.status} label={queue.status_label} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Menunggu" description="10 antrian berikutnya" />
                    <CardBody className="p-0">
                        {waitingList.length === 0 ? (
                            <EmptyState title="Tidak ada yang menunggu" />
                        ) : (
                            <ul className="divide-y divide-ink-100">
                                {waitingList.map((queue) => (
                                    <li key={queue.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-ink-100 text-sm font-bold text-ink-700">
                                                {queue.number}
                                            </span>
                                            <div>
                                                <p className="text-sm font-semibold text-ink-900">{queue.customer ?? '-'}</p>
                                                <p className="text-xs text-ink-500">{queue.service ?? '-'}</p>
                                            </div>
                                        </div>
                                        <StatusBadge status={queue.status} label={queue.status_label} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>
            </div>
        </AdminLayout>
    );
}
