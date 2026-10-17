import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader, StatCard } from '@/components/ui/Card';
import { Field, Input } from '@/components/ui/Field';
import AdminLayout from '@/layouts/AdminLayout';
import { formatRupiah } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface Summary {
    total_queues: number;
    completed: number;
    cancelled: number;
    revenue: number;
}

interface DailyRow {
    date: string;
    total: number;
}

interface ServiceRow {
    service: string;
    transactions: number;
    revenue: number;
}

interface BarberRow {
    barber: string;
    transactions: number;
    revenue: number;
}

export default function ReportsIndex({
    filters,
    summary,
    daily,
    revenueByService,
    revenueByBarber,
}: {
    filters: { from: string; to: string };
    summary: Summary;
    daily: DailyRow[];
    revenueByService: ServiceRow[];
    revenueByBarber: BarberRow[];
}) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const apply = () => {
        router.get(route('admin.reports.index'), { from, to }, { preserveState: true, preserveScroll: true });
    };

    const maxDaily = Math.max(1, ...daily.map((d) => d.total));

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Laporan</h1>
            <p className="text-sm text-ink-500">Analisis operasional dan pendapatan.</p>
        </div>
    );

    return (
        <AdminLayout title="Laporan" heading={heading}>
            <Card className="mb-4">
                <CardBody className="flex flex-wrap items-end gap-3">
                    <Field label="Dari">
                        <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                    </Field>
                    <Field label="Sampai">
                        <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                    </Field>
                    <Button onClick={apply}>Terapkan</Button>
                </CardBody>
            </Card>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Total Antrian" value={summary.total_queues} />
                <StatCard label="Selesai" value={summary.completed} />
                <StatCard label="Batal / No-show" value={summary.cancelled} />
                <StatCard label="Pendapatan" value={formatRupiah(summary.revenue)} accent />
            </div>

            <Card className="mt-6">
                <CardHeader title="Antrian Harian" />
                <CardBody>
                    {daily.length === 0 ? (
                        <EmptyState title="Tidak ada data" />
                    ) : (
                        <div className="flex h-48 items-end gap-1 overflow-x-auto">
                            {daily.map((row) => (
                                <div key={row.date} className="flex min-w-[28px] flex-1 flex-col items-center gap-1">
                                    <span className="text-[10px] font-semibold text-ink-500">{row.total}</span>
                                    <div
                                        className="w-full rounded-t bg-gold-400"
                                        style={{ height: `${Math.max(4, (row.total / maxDaily) * 160)}px` }}
                                        title={`${row.date}: ${row.total}`}
                                    />
                                    <span className="whitespace-nowrap text-[10px] text-ink-400">{row.date.slice(5)}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </CardBody>
            </Card>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card className="overflow-hidden">
                    <CardHeader title="Pendapatan per Layanan" />
                    <CardBody className="p-0">
                        {revenueByService.length === 0 ? (
                            <EmptyState title="Tidak ada data" />
                        ) : (
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Layanan</th>
                                        <th className="px-4 py-3 text-right">Transaksi</th>
                                        <th className="px-4 py-3 text-right">Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {revenueByService.map((row) => (
                                        <tr key={row.service}>
                                            <td className="px-4 py-3 text-ink-700">{row.service}</td>
                                            <td className="px-4 py-3 text-right text-ink-500">{row.transactions}</td>
                                            <td className="px-4 py-3 text-right font-semibold text-ink-900">{formatRupiah(row.revenue)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardBody>
                </Card>

                <Card className="overflow-hidden">
                    <CardHeader title="Pendapatan per Barber" />
                    <CardBody className="p-0">
                        {revenueByBarber.length === 0 ? (
                            <EmptyState title="Tidak ada data" />
                        ) : (
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Barber</th>
                                        <th className="px-4 py-3 text-right">Transaksi</th>
                                        <th className="px-4 py-3 text-right">Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {revenueByBarber.map((row) => (
                                        <tr key={row.barber}>
                                            <td className="px-4 py-3 text-ink-700">{row.barber}</td>
                                            <td className="px-4 py-3 text-right text-ink-500">{row.transactions}</td>
                                            <td className="px-4 py-3 text-right font-semibold text-ink-900">{formatRupiah(row.revenue)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardBody>
                </Card>
            </div>
        </AdminLayout>
    );
}
