import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Input } from '@/components/ui/Field';
import { Pagination, Paginator } from '@/components/ui/Pagination';
import AdminLayout from '@/layouts/AdminLayout';
import { formatDateTime } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Customer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    visits_count: number;
    last_visit_at: string | null;
}

export default function CustomersIndex({ customers, filters }: { customers: Paginator<Customer>; filters: { search: string } }) {
    const [search, setSearch] = useState(filters.search);

    const applySearch = () => {
        router.get(route('admin.customers.index'), { search }, { preserveState: true, preserveScroll: true });
    };

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Pelanggan</h1>
            <p className="text-sm text-ink-500">Daftar pelanggan dan riwayat kunjungan.</p>
        </div>
    );

    return (
        <AdminLayout title="Pelanggan" heading={heading}>
            <Card className="mb-4">
                <CardBody>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applySearch();
                        }}
                        className="flex flex-wrap gap-2"
                    >
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama atau nomor HP..."
                            className="max-w-sm"
                        />
                        <Button type="submit" variant="outline">
                            Cari
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {customers.data.length === 0 ? (
                        <EmptyState title="Belum ada pelanggan" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Nama</th>
                                        <th className="px-4 py-3">Kontak</th>
                                        <th className="px-4 py-3">Kunjungan</th>
                                        <th className="px-4 py-3">Terakhir</th>
                                        <th className="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {customers.data.map((customer) => (
                                        <tr key={customer.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3 font-semibold text-ink-900">{customer.name}</td>
                                            <td className="px-4 py-3 text-ink-500">
                                                <p>{customer.phone ?? '-'}</p>
                                                <p className="text-xs">{customer.email ?? ''}</p>
                                            </td>
                                            <td className="px-4 py-3 text-ink-600">{customer.visits_count}x</td>
                                            <td className="px-4 py-3 text-ink-500">{customer.last_visit_at ? formatDateTime(customer.last_visit_at) : '-'}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Link
                                                    href={route('admin.customers.show', customer.id)}
                                                    className="text-sm font-semibold text-gold-600 hover:underline"
                                                >
                                                    Detail
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
                <div className="px-4">
                    <Pagination paginator={customers} />
                </div>
            </Card>
        </AdminLayout>
    );
}
