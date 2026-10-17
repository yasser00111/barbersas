import { EmptyState } from '@/components/EmptyState';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Select } from '@/components/ui/Field';
import { Pagination, Paginator } from '@/components/ui/Pagination';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatDateTime } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface EventRow {
    id: number;
    type: string;
    tenant: string | null;
    plan: string | null;
    from_status: string | null;
    to_status: string | null;
    reason: string | null;
    created_at: string | null;
}

const eventTone: Record<string, 'green' | 'amber' | 'red' | 'gray' | 'blue' | 'gold'> = {
    created: 'gray',
    activated: 'green',
    renewed: 'green',
    expanded: 'green',
    expired: 'red',
    suspended: 'amber',
    reactivated: 'blue',
    cancelled: 'red',
    plan_changed: 'gold',
};

export default function SubscriptionsIndex({ events, filters }: { events: Paginator<EventRow>; filters: { type: string } }) {
    const [type, setType] = useState(filters.type);

    const apply = (value: string) => router.get(route('super-admin.subscriptions.index'), { type: value }, { preserveState: true, preserveScroll: true });

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Riwayat Langganan</h1>
            <p className="text-sm text-ink-500">Semua perubahan status langganan di platform.</p>
        </div>
    );

    return (
        <SuperAdminLayout title="Langganan" heading={heading}>
            <Card className="mb-4">
                <CardBody>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply(type);
                        }}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <Select
                            value={type}
                            onChange={(e) => {
                                setType(e.target.value);
                                apply(e.target.value);
                            }}
                            className="max-w-[220px]"
                        >
                            <option value="">Semua jenis</option>
                            <option value="created">Dibuat</option>
                            <option value="activated">Diaktifkan</option>
                            <option value="renewed">Diperpanjang</option>
                            <option value="expired">Kedaluwarsa</option>
                            <option value="suspended">Ditangguhkan</option>
                            <option value="reactivated">Diaktifkan Kembali</option>
                            <option value="cancelled">Dibatalkan</option>
                            <option value="plan_changed">Ganti Paket</option>
                        </Select>
                        <Button type="submit" variant="outline">
                            Terapkan
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {events.data.length === 0 ? (
                        <EmptyState title="Tidak ada riwayat" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Jenis</th>
                                        <th className="px-4 py-3">Barbershop</th>
                                        <th className="px-4 py-3">Paket</th>
                                        <th className="px-4 py-3">Perubahan</th>
                                        <th className="px-4 py-3">Keterangan</th>
                                        <th className="px-4 py-3">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {events.data.map((event) => (
                                        <tr key={event.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3">
                                                <Badge tone={eventTone[event.type] ?? 'neutral'}>{event.type.replace(/_/g, ' ')}</Badge>
                                            </td>
                                            <td className="px-4 py-3 font-medium text-ink-900">{event.tenant ?? '-'}</td>
                                            <td className="px-4 py-3 text-ink-600">{event.plan ?? '-'}</td>
                                            <td className="px-4 py-3 text-xs text-ink-500">
                                                {event.from_status ?? '-'} &rarr; {event.to_status ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 text-ink-500">{event.reason ?? '-'}</td>
                                            <td className="px-4 py-3 text-ink-500">{event.created_at ? formatDateTime(event.created_at) : '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
                <div className="px-4">
                    <Pagination paginator={events} />
                </div>
            </Card>
        </SuperAdminLayout>
    );
}
