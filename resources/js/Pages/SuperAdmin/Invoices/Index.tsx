import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Input, Select } from '@/components/ui/Field';
import { Pagination, Paginator } from '@/components/ui/Pagination';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface InvoiceRow {
    id: number;
    number: string;
    tenant: string | null;
    status: string;
    status_label: string;
    amount: number;
    currency: string;
    issued_at: string | null;
    paid_at: string | null;
    payments_count: number;
}

export default function InvoicesIndex({ invoices, filters }: { invoices: Paginator<InvoiceRow>; filters: { status: string; search: string } }) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    const apply = () => router.get(route('super-admin.invoices.index'), { search, status }, { preserveState: true, preserveScroll: true });

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Invoice</h1>
            <p className="text-sm text-ink-500">Semua tagihan langganan platform.</p>
        </div>
    );

    return (
        <SuperAdminLayout title="Invoice" heading={heading}>
            <Card className="mb-4">
                <CardBody>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply();
                        }}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor invoice..." className="max-w-xs" />
                        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="max-w-[200px]">
                            <option value="">Semua status</option>
                            <option value="unpaid">Belum Dibayar</option>
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="paid">Lunas</option>
                            <option value="rejected">Ditolak</option>
                            <option value="expired">Kedaluwarsa</option>
                            <option value="cancelled">Dibatalkan</option>
                        </Select>
                        <Button type="submit" variant="outline">
                            Cari
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {invoices.data.length === 0 ? (
                        <EmptyState title="Tidak ada invoice" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Nomor</th>
                                        <th className="px-4 py-3">Barbershop</th>
                                        <th className="px-4 py-3">Jumlah</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Bayar</th>
                                        <th className="px-4 py-3">Terbit</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {invoices.data.map((invoice) => (
                                        <tr key={invoice.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3 font-semibold text-ink-900">{invoice.number}</td>
                                            <td className="px-4 py-3 text-ink-600">{invoice.tenant ?? '-'}</td>
                                            <td className="px-4 py-3 font-semibold text-gold-600">{formatRupiah(invoice.amount)}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={invoice.status} label={invoice.status_label} />
                                            </td>
                                            <td className="px-4 py-3 text-ink-500">{invoice.payments_count} pembayaran</td>
                                            <td className="px-4 py-3 text-ink-500">{invoice.issued_at ? formatDate(invoice.issued_at) : '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
                <div className="px-4">
                    <Pagination paginator={invoices} />
                </div>
            </Card>
        </SuperAdminLayout>
    );
}
