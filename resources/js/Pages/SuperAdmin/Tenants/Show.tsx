import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader, StatCard } from '@/components/ui/Card';
import { Field, Input } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/utils';
import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface TenantDetail {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    created_at: string | null;
    suspended_reason: string | null;
    owner: { name: string; email: string } | null;
}

interface SubscriptionInfo {
    id: number;
    status: string;
    status_label: string;
    starts_at: string | null;
    ends_at: string | null;
    plan: string | null;
    price: number;
}

interface Member {
    id: number;
    role: string;
    name: string | null;
    email: string | null;
}

interface InvoiceRow {
    id: number;
    number: string;
    status: string;
    status_label: string;
    amount: number;
    issued_at: string | null;
    paid_at: string | null;
}

interface EventRow {
    id: number;
    type: string;
    from_status: string | null;
    to_status: string | null;
    reason: string | null;
    created_at: string | null;
}

export default function TenantsShow({
    tenant,
    subscription,
    members,
    invoices,
    events,
}: {
    tenant: TenantDetail;
    subscription: SubscriptionInfo | null;
    members: Member[];
    invoices: InvoiceRow[];
    events: EventRow[];
}) {
    const [suspendOpen, setSuspendOpen] = useState(false);
    const [archiveOpen, setArchiveOpen] = useState(false);
    const suspendForm = useForm({ reason: '' });

    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href={route('super-admin.tenants.index')} className="text-xs font-medium text-ink-400 hover:underline">
                    &larr; Kembali ke daftar
                </Link>
                <h1 className="flex items-center gap-2 text-xl font-bold text-ink-900">
                    {tenant.name}
                    <StatusBadge status={tenant.status} label={tenant.status_label} />
                </h1>
                <p className="text-sm text-ink-500">/{tenant.slug}</p>
            </div>
            <div className="flex flex-wrap gap-2">
                {tenant.status !== 'active' && (
                    <Button onClick={() => router.post(route('super-admin.tenants.activate', tenant.slug), {}, { preserveScroll: true })}>Aktifkan</Button>
                )}
                {tenant.status === 'active' && (
                    <Button variant="danger" onClick={() => setSuspendOpen(true)}>
                        Tangguhkan
                    </Button>
                )}
                {tenant.status === 'suspended' && (
                    <Button onClick={() => router.post(route('super-admin.tenants.reactivate', tenant.slug), {}, { preserveScroll: true })}>Aktifkan Kembali</Button>
                )}
                {tenant.status !== 'archived' && (
                    <Button variant="ghost" onClick={() => setArchiveOpen(true)}>
                        Arsipkan
                    </Button>
                )}
            </div>
        </div>
    );

    return (
        <SuperAdminLayout title="Detail Barbershop" heading={heading}>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Pemilik" value={tenant.owner?.name ?? '-'} hint={tenant.owner?.email ?? undefined} />
                <StatCard label="Paket" value={subscription?.plan ?? '-'} hint={subscription?.status_label} />
                <StatCard label="Harga" value={subscription ? formatRupiah(subscription.price) : '-'} />
                <StatCard label="Berakhir" value={subscription?.ends_at ? formatDate(subscription.ends_at) : '-'} />
            </div>

            {tenant.suspended_reason && (
                <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    Alasan penangguhan: {tenant.suspended_reason}
                </div>
            )}

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Info Barbershop" />
                    <CardBody className="space-y-2 text-sm">
                        <InfoRow label="Email" value={tenant.email ?? '-'} />
                        <InfoRow label="Telepon" value={tenant.phone ?? '-'} />
                        <InfoRow label="Alamat" value={tenant.address ?? '-'} />
                        <InfoRow label="Kota" value={tenant.city ?? '-'} />
                        <InfoRow label="Terdaftar" value={tenant.created_at ? formatDateTime(tenant.created_at) : '-'} />
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Anggota" />
                    <CardBody className="p-0">
                        {members.length === 0 ? (
                            <EmptyState title="Belum ada anggota" />
                        ) : (
                            <ul className="divide-y divide-ink-100">
                                {members.map((member) => (
                                    <li key={member.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                        <div>
                                            <p className="text-sm font-semibold text-ink-900">{member.name ?? '-'}</p>
                                            <p className="text-xs text-ink-500">{member.email ?? '-'}</p>
                                        </div>
                                        <span className="rounded-full bg-ink-100 px-2.5 py-0.5 text-xs font-semibold text-ink-600">
                                            {member.role}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card className="overflow-hidden">
                    <CardHeader title="Invoice" />
                    <CardBody className="p-0">
                        {invoices.length === 0 ? (
                            <EmptyState title="Belum ada invoice" />
                        ) : (
                            <ul className="divide-y divide-ink-100">
                                {invoices.map((invoice) => (
                                    <li key={invoice.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                        <div>
                                            <p className="text-sm font-semibold text-ink-900">{invoice.number}</p>
                                            <p className="text-xs text-ink-500">{formatRupiah(invoice.amount)}</p>
                                        </div>
                                        <StatusBadge status={invoice.status} label={invoice.status_label} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>

                <Card className="overflow-hidden">
                    <CardHeader title="Riwayat Langganan" />
                    <CardBody className="p-0">
                        {events.length === 0 ? (
                            <EmptyState title="Belum ada riwayat" />
                        ) : (
                            <ul className="divide-y divide-ink-100">
                                {events.map((event) => (
                                    <li key={event.id} className="px-5 py-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-semibold capitalize text-ink-900">{event.type.replace(/_/g, ' ')}</span>
                                            <span className="text-xs text-ink-400">{event.created_at ? formatDate(event.created_at) : '-'}</span>
                                        </div>
                                        {event.reason && <p className="mt-0.5 text-xs text-ink-500">{event.reason}</p>}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>
            </div>

            <Modal
                open={suspendOpen}
                onClose={() => setSuspendOpen(false)}
                title="Tangguhkan Barbershop"
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setSuspendOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            loading={suspendForm.processing}
                            onClick={() =>
                                suspendForm.post(route('super-admin.tenants.suspend', tenant.slug), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        suspendForm.reset();
                                        setSuspendOpen(false);
                                    },
                                })
                            }
                        >
                            Tangguhkan
                        </Button>
                    </>
                }
            >
                <Field label="Alasan Penangguhan" error={suspendForm.errors.reason} required>
                    <Input value={suspendForm.data.reason} onChange={(e) => suspendForm.setData('reason', e.target.value)} error={!!suspendForm.errors.reason} />
                </Field>
            </Modal>

            <Modal
                open={archiveOpen}
                onClose={() => setArchiveOpen(false)}
                title="Arsipkan Barbershop"
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setArchiveOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() =>
                                router.post(route('super-admin.tenants.archive', tenant.slug), {}, {
                                    preserveScroll: true,
                                    onSuccess: () => setArchiveOpen(false),
                                })
                            }
                        >
                            Arsipkan
                        </Button>
                    </>
                }
            >
                <p className="text-sm text-ink-600">Barbershop akan diarsipkan dan tidak dapat diakses. Tindakan ini dapat dibalik oleh Super Admin.</p>
            </Modal>
        </SuperAdminLayout>
    );
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4">
            <span className="text-ink-500">{label}</span>
            <span className="text-right font-medium text-ink-900">{value}</span>
        </div>
    );
}
