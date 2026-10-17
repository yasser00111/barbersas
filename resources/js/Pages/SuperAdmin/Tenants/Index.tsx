import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Input, Select } from '@/components/ui/Field';
import { Pagination, Paginator } from '@/components/ui/Pagination';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatDate } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Tenant {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    subscription_status: string | null;
    subscription_ends_at: string | null;
    plan: string | null;
    created_at: string | null;
}

export default function TenantsIndex({ tenants, filters }: { tenants: Paginator<Tenant>; filters: { status: string; search: string } }) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    const apply = () => router.get(route('super-admin.tenants.index'), { search, status }, { preserveState: true, preserveScroll: true });

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Barbershop</h1>
            <p className="text-sm text-ink-500">Kelola semua tenant platform.</p>
        </div>
    );

    return (
        <SuperAdminLayout title="Barbershop" heading={heading}>
            <Card className="mb-4">
                <CardBody>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply();
                        }}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nama atau slug..." className="max-w-xs" />
                        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="max-w-[200px]">
                            <option value="">Semua status</option>
                            <option value="pending">Menunggu</option>
                            <option value="active">Aktif</option>
                            <option value="suspended">Ditangguhkan</option>
                            <option value="archived">Diarsipkan</option>
                        </Select>
                        <Button type="submit" variant="outline">
                            Cari
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {tenants.data.length === 0 ? (
                        <EmptyState title="Tidak ada barbershop" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Barbershop</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Langganan</th>
                                        <th className="px-4 py-3">Berakhir</th>
                                        <th className="px-4 py-3">Terdaftar</th>
                                        <th className="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {tenants.data.map((tenant) => (
                                        <tr key={tenant.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3">
                                                <p className="font-semibold text-ink-900">{tenant.name}</p>
                                                <p className="text-xs text-ink-500">/{tenant.slug}</p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={tenant.status} label={tenant.status_label} />
                                            </td>
                                            <td className="px-4 py-3 text-ink-600">
                                                <p>{tenant.plan ?? '-'}</p>
                                                <p className="text-xs text-ink-400">{tenant.subscription_status ?? '-'}</p>
                                            </td>
                                            <td className="px-4 py-3 text-ink-500">{tenant.subscription_ends_at ? formatDate(tenant.subscription_ends_at) : '-'}</td>
                                            <td className="px-4 py-3 text-ink-500">{tenant.created_at ? formatDate(tenant.created_at) : '-'}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Link
                                                    href={route('super-admin.tenants.show', tenant.slug)}
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
                    <Pagination paginator={tenants} />
                </div>
            </Card>
        </SuperAdminLayout>
    );
}
