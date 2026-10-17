import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader, StatCard } from '@/components/ui/Card';
import { Field, Input } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import AdminLayout from '@/layouts/AdminLayout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface SubscriptionInfo {
    id: number;
    status: string;
    status_label: string;
    starts_at: string | null;
    ends_at: string | null;
    days_remaining: number | null;
    price: number;
    currency: string;
    duration_days: number;
    plan: { id: number; name: string } | null;
}

interface Payment {
    id: number;
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    rejection_reason: string | null;
    created_at: string | null;
}

interface InvoiceRow {
    id: number;
    uuid: string;
    number: string;
    type: string;
    status: string;
    status_label: string;
    amount: number;
    currency: string;
    plan_name: string | null;
    description: string | null;
    issued_at: string | null;
    due_at: string | null;
    paid_at: string | null;
    is_payable: boolean;
    has_pending_payment: boolean;
    payments: Payment[];
}

interface Instructions {
    bank_name: string | null;
    bank_account: string | null;
    account_name: string | null;
    instructions: string | null;
}

export default function SubscriptionIndex({
    subscription,
    invoices,
    paymentInstructions,
    gatewayEnabled,
}: {
    subscription: SubscriptionInfo | null;
    invoices: InvoiceRow[];
    paymentInstructions: Instructions;
    gateway: string;
    gatewayEnabled: boolean;
}) {
    const [proofTarget, setProofTarget] = useState<InvoiceRow | null>(null);
    const proofForm = useForm({ proof: null as File | null, amount: '' as number | string });

    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Langganan</h1>
                <p className="text-sm text-ink-500">Status langganan dan riwayat tagihan Anda.</p>
            </div>
            <Button onClick={() => router.post(route('admin.subscription.renew'), {}, { preserveScroll: true })}>Perpanjang</Button>
        </div>
    );

    const submitProof = () => {
        if (!proofTarget) return;
        proofForm.post(route('admin.subscription.proof', proofTarget.uuid), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                proofForm.reset();
                setProofTarget(null);
            },
        });
    };

    const payOnline = (invoice: InvoiceRow) => {
        router.post(route('admin.subscription.pay', invoice.uuid), {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Langganan" heading={heading}>
            {subscription ? (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Status" value={<StatusBadge status={subscription.status} label={subscription.status_label} />} accent />
                    <StatCard label="Paket" value={subscription.plan?.name ?? '-'} />
                    <StatCard label="Berakhir" value={subscription.ends_at ? formatDate(subscription.ends_at) : '-'} hint={subscription.days_remaining != null ? `${subscription.days_remaining} hari lagi` : undefined} />
                    <StatCard label="Harga" value={formatRupiah(subscription.price)} hint={`per ${subscription.duration_days} hari`} />
                </div>
            ) : (
                <Card>
                    <CardBody>
                        <EmptyState title="Belum ada langganan" />
                    </CardBody>
                </Card>
            )}

            {(paymentInstructions.bank_name || paymentInstructions.bank_account || paymentInstructions.instructions) && (
                <Card className="mt-4">
                    <CardHeader title="Instruksi Pembayaran Transfer" />
                    <CardBody className="space-y-1 text-sm text-ink-600">
                        {paymentInstructions.bank_name && <p>Bank: <span className="font-semibold text-ink-900">{paymentInstructions.bank_name}</span></p>}
                        {paymentInstructions.bank_account && <p>No. Rekening: <span className="font-semibold text-ink-900">{paymentInstructions.bank_account}</span></p>}
                        {paymentInstructions.account_name && <p>Atas Nama: <span className="font-semibold text-ink-900">{paymentInstructions.account_name}</span></p>}
                        {paymentInstructions.instructions && <p className="pt-1 text-xs text-ink-500">{paymentInstructions.instructions}</p>}
                    </CardBody>
                </Card>
            )}

            <Card className="mt-6 overflow-hidden">
                <CardHeader title="Tagihan" description="Semua invoice langganan Anda" />
                <CardBody className="p-0">
                    {invoices.length === 0 ? (
                        <EmptyState title="Belum ada tagihan" />
                    ) : (
                        <div className="divide-y divide-ink-100">
                            {invoices.map((invoice) => (
                                <div key={invoice.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="font-semibold text-ink-900">{invoice.number}</span>
                                            <StatusBadge status={invoice.status} label={invoice.status_label} />
                                        </div>
                                        <p className="mt-0.5 text-xs text-ink-500">
                                            {invoice.plan_name ?? '-'} &middot; {formatRupiah(invoice.amount)} &middot; Terbit {invoice.issued_at ? formatDate(invoice.issued_at) : '-'}
                                        </p>
                                        {invoice.payments.map((payment) => (
                                            <p key={payment.id} className="mt-0.5 text-xs text-ink-400">
                                                {payment.method_label}: {payment.status_label}
                                                {payment.rejection_reason ? ` (${payment.rejection_reason})` : ''}
                                            </p>
                                        ))}
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {invoice.is_payable && !invoice.has_pending_payment && gatewayEnabled && (
                                            <Button size="sm" onClick={() => payOnline(invoice)}>
                                                Bayar Online
                                            </Button>
                                        )}
                                        {invoice.is_payable && !invoice.has_pending_payment && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => {
                                                    proofForm.reset();
                                                    setProofTarget(invoice);
                                                }}
                                            >
                                                Unggah Bukti
                                            </Button>
                                        )}
                                        {invoice.has_pending_payment && <span className="text-xs font-medium text-amber-600">Menunggu verifikasi</span>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardBody>
            </Card>

            <Modal
                open={!!proofTarget}
                onClose={() => setProofTarget(null)}
                title={`Unggah Bukti Transfer - ${proofTarget?.number}`}
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setProofTarget(null)}>
                            Batal
                        </Button>
                        <Button loading={proofForm.processing} onClick={submitProof}>
                            Kirim
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <Field label="Bukti Transfer" error={proofForm.errors.proof} hint="JPG/PNG/PDF, maks 4MB" required>
                        <input
                            type="file"
                            accept="image/*,application/pdf"
                            onChange={(e) => proofForm.setData('proof', e.target.files?.[0] ?? null)}
                            className="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-ink-800"
                        />
                    </Field>
                    <Field label="Jumlah Transfer (Rp)" error={proofForm.errors.amount} hint="Opsional">
                        <Input type="number" min={0} value={proofForm.data.amount} onChange={(e) => proofForm.setData('amount', e.target.value)} />
                    </Field>
                </div>
            </Modal>
        </AdminLayout>
    );
}
