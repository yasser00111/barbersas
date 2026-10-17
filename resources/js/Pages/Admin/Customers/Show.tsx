import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Card, CardBody, CardHeader, StatCard } from '@/components/ui/Card';
import AdminLayout from '@/layouts/AdminLayout';
import { formatDate, formatDateTime } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    notes: string | null;
    visits_count: number;
    last_visit_at: string | null;
}

interface HistoryRow {
    id: number;
    date: string | null;
    number: number;
    service: string | null;
    barber: string | null;
    status: string;
    status_label: string;
}

export default function CustomersShow({ customer, history }: { customer: Customer; history: HistoryRow[] }) {
    const heading = (
        <div>
            <Link href={route('admin.customers.index')} className="text-xs font-medium text-ink-400 hover:underline">
                &larr; Kembali ke daftar
            </Link>
            <h1 className="text-xl font-bold text-ink-900">{customer.name}</h1>
        </div>
    );

    return (
        <AdminLayout title="Detail Pelanggan" heading={heading}>
            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard label="Total Kunjungan" value={`${customer.visits_count}x`} accent />
                <StatCard label="Kunjungan Terakhir" value={customer.last_visit_at ? formatDateTime(customer.last_visit_at) : '-'} />
                <StatCard label="Kontak" value={customer.phone ?? '-'} hint={customer.email ?? undefined} />
            </div>

            {customer.notes && (
                <Card className="mt-4">
                    <CardHeader title="Catatan" />
                    <CardBody className="text-sm text-ink-600">{customer.notes}</CardBody>
                </Card>
            )}

            <Card className="mt-4 overflow-hidden">
                <CardHeader title="Riwayat Antrian" description="50 kunjungan terakhir" />
                <CardBody className="p-0">
                    {history.length === 0 ? (
                        <EmptyState title="Belum ada riwayat" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Tanggal</th>
                                        <th className="px-4 py-3">No</th>
                                        <th className="px-4 py-3">Layanan</th>
                                        <th className="px-4 py-3">Barber</th>
                                        <th className="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {history.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3 text-ink-600">{row.date ? formatDate(row.date) : '-'}</td>
                                            <td className="px-4 py-3 font-semibold text-ink-900">#{row.number}</td>
                                            <td className="px-4 py-3 text-ink-600">{row.service ?? '-'}</td>
                                            <td className="px-4 py-3 text-ink-600">{row.barber ?? '-'}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={row.status} label={row.status_label} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
            </Card>
        </AdminLayout>
    );
}
