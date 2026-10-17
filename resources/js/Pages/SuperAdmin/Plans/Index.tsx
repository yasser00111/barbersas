import { EmptyState } from '@/components/EmptyState';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Checkbox, Field, Input, Textarea } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatRupiah } from '@/lib/utils';
import { router, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

interface Plan {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    currency: string;
    duration_days: number;
    features: string[];
    max_barbers: number | null;
    max_services: number | null;
    is_active: boolean;
}

interface PlanForm {
    name: string;
    slug: string;
    description: string;
    price: number | string;
    currency: string;
    duration_days: number | string;
    features_text: string;
    max_barbers: number | string;
    max_services: number | string;
    is_active: boolean;
    sort_order: number;
}

const empty: PlanForm = {
    name: '',
    slug: '',
    description: '',
    price: '',
    currency: 'IDR',
    duration_days: 30,
    features_text: '',
    max_barbers: '',
    max_services: '',
    is_active: true,
    sort_order: 0,
};

export default function PlansIndex({ plans }: { plans: Plan[] }) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Plan | null>(null);
    const form = useForm<PlanForm>({ ...empty });

    useEffect(() => {
        if (!open) return;
        if (editing) {
            form.setData({
                name: editing.name,
                slug: editing.slug,
                description: editing.description ?? '',
                price: editing.price,
                currency: editing.currency,
                duration_days: editing.duration_days,
                features_text: (editing.features ?? []).join('\n'),
                max_barbers: editing.max_barbers ?? '',
                max_services: editing.max_services ?? '',
                is_active: editing.is_active,
                sort_order: 0,
            });
        } else {
            form.reset();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, editing]);

    const openCreate = () => {
        setEditing(null);
        setOpen(true);
    };
    const openEdit = (plan: Plan) => {
        setEditing(plan);
        setOpen(true);
    };

    const toFeatures = (text: string) =>
        text
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const payload = {
            name: form.data.name,
            slug: form.data.slug,
            description: form.data.description,
            price: form.data.price,
            currency: form.data.currency,
            duration_days: form.data.duration_days,
            features: toFeatures(form.data.features_text),
            max_barbers: form.data.max_barbers === '' ? null : form.data.max_barbers,
            max_services: form.data.max_services === '' ? null : form.data.max_services,
            is_active: form.data.is_active,
            sort_order: form.data.sort_order,
        };

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                setEditing(null);
            },
        };

        form.transform(() => payload);
        if (editing) {
            form.put(route('super-admin.plans.update', editing.id), options);
        } else {
            form.post(route('super-admin.plans.store'), options);
        }
    };

    const remove = (plan: Plan) => {
        if (confirm(`Hapus paket "${plan.name}"?`)) {
            router.delete(route('super-admin.plans.destroy', plan.id), { preserveScroll: true });
        }
    };

    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Paket Harga</h1>
                <p className="text-sm text-ink-500">Atur paket langganan yang ditawarkan.</p>
            </div>
            <Button onClick={openCreate}>Tambah Paket</Button>
        </div>
    );

    return (
        <SuperAdminLayout title="Paket Harga" heading={heading}>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {plans.map((plan) => (
                    <Card key={plan.id}>
                        <CardBody>
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="font-bold text-ink-900">{plan.name}</h3>
                                    <p className="text-xs text-ink-400">/{plan.slug}</p>
                                </div>
                                {plan.is_active ? <Badge tone="green">Aktif</Badge> : <Badge tone="gray">Nonaktif</Badge>}
                            </div>
                            <p className="mt-3 text-2xl font-extrabold text-ink-900">
                                {formatRupiah(plan.price)}
                                <span className="text-sm font-medium text-ink-400"> / {plan.duration_days} hari</span>
                            </p>
                            {plan.description && <p className="mt-2 text-sm text-ink-500">{plan.description}</p>}
                            <ul className="mt-3 space-y-1 text-xs text-ink-500">
                                {plan.max_barbers ? <li>Maks {plan.max_barbers} barber</li> : null}
                                {plan.max_services ? <li>Maks {plan.max_services} layanan</li> : null}
                                {(plan.features ?? []).map((feature) => <li key={feature}>• {feature}</li>)}
                            </ul>
                            <div className="mt-4 flex gap-2">
                                <Button size="sm" variant="outline" onClick={() => openEdit(plan)}>
                                    Ubah
                                </Button>
                                <Button size="sm" variant="danger" onClick={() => remove(plan)}>
                                    Hapus
                                </Button>
                            </div>
                        </CardBody>
                    </Card>
                ))}
                {plans.length === 0 && (
                    <Card className="md:col-span-2 lg:col-span-3">
                        <EmptyState title="Belum ada paket" action={<Button onClick={openCreate}>Tambah Paket</Button>} />
                    </Card>
                )}
            </div>

            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title={editing ? 'Ubah Paket' : 'Tambah Paket'}
                maxWidth="max-w-xl"
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setOpen(false)}>
                            Batal
                        </Button>
                        <Button loading={form.processing} onClick={submit}>
                            Simpan
                        </Button>
                    </>
                }
            >
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Nama Paket" error={form.errors.name} required>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={!!form.errors.name} />
                        </Field>
                        <Field label="Slug" error={form.errors.slug} hint="Kosongkan untuk otomatis">
                            <Input value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} error={!!form.errors.slug} />
                        </Field>
                    </div>

                    <Field label="Deskripsi" error={form.errors.description}>
                        <Textarea value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field label="Harga" error={form.errors.price} required>
                            <Input type="number" min={0} value={form.data.price} onChange={(e) => form.setData('price', e.target.value)} error={!!form.errors.price} />
                        </Field>
                        <Field label="Mata Uang" error={form.errors.currency}>
                            <Input value={form.data.currency} maxLength={3} onChange={(e) => form.setData('currency', e.target.value.toUpperCase())} />
                        </Field>
                        <Field label="Durasi (hari)" error={form.errors.duration_days} required>
                            <Input type="number" min={1} value={form.data.duration_days} onChange={(e) => form.setData('duration_days', e.target.value)} error={!!form.errors.duration_days} />
                        </Field>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Maks Barber" error={form.errors.max_barbers} hint="Kosong = tanpa batas">
                            <Input type="number" min={0} value={form.data.max_barbers} onChange={(e) => form.setData('max_barbers', e.target.value)} />
                        </Field>
                        <Field label="Maks Layanan" error={form.errors.max_services} hint="Kosong = tanpa batas">
                            <Input type="number" min={0} value={form.data.max_services} onChange={(e) => form.setData('max_services', e.target.value)} />
                        </Field>
                    </div>

                    <Field label="Fitur" error={(form.errors as Record<string, string>).features} hint="Satu fitur per baris">
                        <Textarea
                            value={form.data.features_text}
                            onChange={(e) => form.setData('features_text', e.target.value)}
                            placeholder={'Antrian online\nLaporan pendapatan'}
                        />
                    </Field>

                    <Checkbox checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} label="Aktifkan paket ini" />
                </form>
            </Modal>
        </SuperAdminLayout>
    );
}
