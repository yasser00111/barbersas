import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Field, Input, Select } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import { Pagination, Paginator } from '@/components/ui/Pagination';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface Payment {
    id: number;
    uuid: string;
    invoice_number: string | null;
    tenant: string | null;
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    amount: number;
    has_proof: boolean;
    proof_url: string | null;
    rejection_reason: string | null;
    created_at: string | null;
}

export default function PaymentsIndex({ payments, filters }: { payments: Paginator<Payment>; filters: { status: string; method: string } }) {
    const [status, setStatus] = useState(filters.status);
    const [method, setMethod] = useState(filters.method);
    const [rejectTarget, setRejectTarget] = useState<Payment | null>(null);
    const rejectForm = useForm({ reason: '' });

    const apply = () => router.get(route('super-admin.payments.index'), { status, method }, { preserveState: true, preserveScroll: true });

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Pembayaran</h1>
            <p className="text-sm text-ink-500">Verifikasi pembayaran langganan.</p>
        </div>
    );

    return (
        <SuperAdminLayout title="Pembayaran" heading={heading}>
            <Card className="mb-4">
                <CardBody>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply();
                        }}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="max-w-[200px]">
                            <option value="">Semua status</option>
                            <option value="pending">Menunggu</option>
                            <option value="paid">Berhasil</option>
                            <option value="rejected">Ditolak</option>
                            <option value="failed">Gagal</option>
                            <option value="expired">Kedaluwarsa</option>
                        </Select>
                        <Select value={method} onChange={(e) => setMethod(e.target.value)} className="max-w-[200px]">
                            <option value="">Semua metode</option>
                            <option value="manual">Transfer Manual</option>
                            <option value="midtrans">Online</option>
                        </Select>
                        <Button type="submit" variant="outline">
                            Terapkan
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {payments.data.length === 0 ? (
                        <EmptyState title="Tidak ada pembayaran" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Invoice</th>
                                        <th className="px-4 py-3">Barbershop</th>
                                        <th className="px-4 py-3">Metode</th>
                                        <th className="px-4 py-3">Jumlah</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Waktu</th>
                                        <th className="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {payments.data.map((payment) => (
                                        <tr key={payment.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3 font-semibold text-ink-900">{payment.invoice_number ?? '-'}</td>
                                            <td className="px-4 py-3 text-ink-600">{payment.tenant ?? '-'}</td>
                                            <td className="px-4 py-3 text-ink-600">{payment.method_label}</td>
                                            <td className="px-4 py-3 font-semibold text-gold-600">{formatRupiah(payment.amount)}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={payment.status} label={payment.status_label} />
                                                {payment.rejection_reason && <p className="mt-1 text-xs text-rose-500">{payment.rejection_reason}</p>}
                                            </td>
                                            <td className="px-4 py-3 text-ink-500">{payment.created_at ? formatDateTime(payment.created_at) : '-'}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap justify-end gap-1">
                                                    {payment.proof_url && (
                                                        <a
                                                            href={payment.proof_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="rounded-lg border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:bg-ink-50"
                                                        >
                                                            Bukti
                                                        </a>
                                                    )}
                                                    {payment.status === 'pending' && (
                                                        <>
                                                            <Button
                                                                size="sm"
                                                                onClick={() => router.post(route('super-admin.payments.approve', payment.uuid), {}, { preserveScroll: true })}
                                                            >
                                                                Verifikasi
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="danger"
                                                                onClick={() => {
                                                                    rejectForm.reset();
                                                                    setRejectTarget(payment);
                                                                }}
                                                            >
                                                                Tolak
                                                            </Button>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
                <div className="px-4">
                    <Pagination paginator={payments} />
                </div>
            </Card>

            <Modal
                open={!!rejectTarget}
                onClose={() => setRejectTarget(null)}
                title={`Tolak Pembayaran - ${rejectTarget?.invoice_number}`}
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setRejectTarget(null)}>
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            loading={rejectForm.processing}
                            onClick={() => {
                                if (!rejectTarget) return;
                                rejectForm.post(route('super-admin.payments.reject', rejectTarget.uuid), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        rejectForm.reset();
                                        setRejectTarget(null);
                                    },
                                });
                            }}
                        >
                            Tolak
                        </Button>
                    </>
                }
            >
                <Field label="Alasan Penolakan" error={rejectForm.errors.reason} required>
                    <Input value={rejectForm.data.reason} onChange={(e) => rejectForm.setData('reason', e.target.value)} error={!!rejectForm.errors.reason} />
                </Field>
            </Modal>
        </SuperAdminLayout>
    );
}
