import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, StatCard } from '@/components/ui/Card';
import BarberLayout from '@/layouts/BarberLayout';
import { router } from '@inertiajs/react';

interface QueueRow {
    id: number;
    uuid: string;
    number: number;
    status: string;
    status_label: string;
    customer: string | null;
    phone: string | null;
    service: string | null;
    barber_id: number | null;
}

export default function Dashboard({
    barber,
    stats,
    queues,
}: {
    barber: { id: number; name: string } | null;
    stats: { waiting: number; in_service: number; completed: number };
    queues: QueueRow[];
}) {
    return (
        <BarberLayout title="Antrian Saya">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Halo, {barber?.name ?? 'Barber'}</h1>
                <p className="text-sm text-ink-500">Berikut daftar antrian yang ditugaskan kepada Anda atau belum ditugaskan.</p>
            </div>

            <div className="mt-5 grid gap-4 sm:grid-cols-3">
                <StatCard label="Menunggu" value={stats.waiting} accent />
                <StatCard label="Sedang Dilayani" value={stats.in_service} />
                <StatCard label="Selesai Hari Ini" value={stats.completed} />
            </div>

            <Card className="mt-6 overflow-hidden">
                <CardBody className="p-0">
                    {queues.length === 0 ? (
                        <EmptyState title="Tidak ada antrian aktif" description="Antrian baru akan muncul di sini." />
                    ) : (
                        <ul className="divide-y divide-ink-100">
                            {queues.map((queue) => (
                                <li key={queue.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                    <div className="flex items-center gap-3">
                                        <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-ink-900 text-base font-black text-gold-400">
                                            {queue.number}
                                        </span>
                                        <div>
                                            <p className="font-semibold text-ink-900">{queue.customer ?? '-'}</p>
                                            <p className="text-xs text-ink-500">
                                                {queue.service ?? '-'}
                                                {queue.phone ? ` · ${queue.phone}` : ''}
                                            </p>
                                            <div className="mt-1">
                                                <StatusBadge status={queue.status} label={queue.status_label} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {queue.status === 'waiting' && (
                                            <Button size="sm" onClick={() => router.post(route('barber.queues.call', queue.uuid), {}, { preserveScroll: true })}>
                                                Panggil
                                            </Button>
                                        )}
                                        {queue.status === 'called' && (
                                            <Button size="sm" onClick={() => router.post(route('barber.queues.start', queue.uuid), {}, { preserveScroll: true })}>
                                                Mulai Layani
                                            </Button>
                                        )}
                                        {queue.status === 'in_service' && (
                                            <Button size="sm" variant="secondary" onClick={() => router.post(route('barber.queues.complete', queue.uuid), {}, { preserveScroll: true })}>
                                                Selesai
                                            </Button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardBody>
            </Card>
        </BarberLayout>
    );
}
