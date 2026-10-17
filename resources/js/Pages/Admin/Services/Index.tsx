import { EmptyState } from '@/components/EmptyState';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Checkbox, Field, Input, Textarea } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import AdminLayout from '@/layouts/AdminLayout';
import { formatRupiah } from '@/lib/utils';
import { router, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

interface Service {
    id: number;
    name: string;
    description: string | null;
    price: number;
    duration_minutes: number;
    is_active: boolean;
    sort_order: number;
    barber_ids: number[];
}

interface Option {
    id: number;
    name: string;
    is_active: boolean;
}

const empty = {
    name: '',
    description: '',
    price: '' as number | string,
    duration_minutes: 30 as number | string,
    is_active: true,
    sort_order: 0,
    barber_ids: [] as number[],
};

export default function ServicesIndex({ services, barbers }: { services: Service[]; barbers: Option[] }) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Service | null>(null);
    const form = useForm({ ...empty });

    useEffect(() => {
        if (!open) return;
        if (editing) {
            form.setData({
                name: editing.name,
                description: editing.description ?? '',
                price: editing.price,
                duration_minutes: editing.duration_minutes,
                is_active: editing.is_active,
                sort_order: editing.sort_order,
                barber_ids: editing.barber_ids.map(Number),
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
    const openEdit = (service: Service) => {
        setEditing(service);
        setOpen(true);
    };

    const toggleBarber = (id: number) => {
        const current = form.data.barber_ids;
        form.setData('barber_ids', current.includes(id) ? current.filter((b) => b !== id) : [...current, id]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                setEditing(null);
            },
        };

        if (editing) {
            form.put(route('admin.services.update', editing.id), options);
        } else {
            form.post(route('admin.services.store'), options);
        }
    };

    const remove = (service: Service) => {
        if (confirm(`Hapus layanan "${service.name}"?`)) {
            router.delete(route('admin.services.destroy', service.id), { preserveScroll: true });
        }
    };

    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Layanan</h1>
                <p className="text-sm text-ink-500">Atur daftar layanan, harga, dan durasi.</p>
            </div>
            <Button onClick={openCreate}>Tambah Layanan</Button>
        </div>
    );

    return (
        <AdminLayout title="Layanan" heading={heading}>
            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {services.length === 0 ? (
                        <EmptyState title="Belum ada layanan" action={<Button onClick={openCreate}>Tambah Layanan</Button>} />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Layanan</th>
                                        <th className="px-4 py-3">Harga</th>
                                        <th className="px-4 py-3">Durasi</th>
                                        <th className="px-4 py-3">Barber</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {services.map((service) => (
                                        <tr key={service.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3">
                                                <p className="font-semibold text-ink-900">{service.name}</p>
                                                {service.description && <p className="text-xs text-ink-500">{service.description}</p>}
                                            </td>
                                            <td className="px-4 py-3 font-semibold text-gold-600">{formatRupiah(service.price)}</td>
                                            <td className="px-4 py-3 text-ink-600">{service.duration_minutes} menit</td>
                                            <td className="px-4 py-3 text-ink-600">{service.barber_ids.length} barber</td>
                                            <td className="px-4 py-3">
                                                {service.is_active ? <Badge tone="green">Aktif</Badge> : <Badge tone="gray">Nonaktif</Badge>}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button size="sm" variant="outline" onClick={() => openEdit(service)}>
                                                        Ubah
                                                    </Button>
                                                    <Button size="sm" variant="danger" onClick={() => remove(service)}>
                                                        Hapus
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
            </Card>

            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title={editing ? 'Ubah Layanan' : 'Tambah Layanan'}
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
                    <Field label="Nama Layanan" error={form.errors.name} required>
                        <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={!!form.errors.name} />
                    </Field>
                    <Field label="Deskripsi" error={form.errors.description}>
                        <Textarea value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                    </Field>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Harga (Rp)" error={form.errors.price} required>
                            <Input type="number" min={0} value={form.data.price} onChange={(e) => form.setData('price', e.target.value)} error={!!form.errors.price} />
                        </Field>
                        <Field label="Durasi (menit)" error={form.errors.duration_minutes} required>
                            <Input
                                type="number"
                                min={5}
                                value={form.data.duration_minutes}
                                onChange={(e) => form.setData('duration_minutes', e.target.value)}
                                error={!!form.errors.duration_minutes}
                            />
                        </Field>
                    </div>

                    <div>
                        <span className="block text-sm font-medium text-ink-700">Barber yang Menyediakan</span>
                        <div className="mt-2 grid grid-cols-2 gap-2">
                            {barbers.map((barber) => (
                                <Checkbox
                                    key={barber.id}
                                    checked={form.data.barber_ids.includes(barber.id)}
                                    onChange={() => toggleBarber(barber.id)}
                                    label={barber.name}
                                />
                            ))}
                            {barbers.length === 0 && <p className="text-xs text-ink-400">Belum ada barber.</p>}
                        </div>
                    </div>

                    <Checkbox checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} label="Aktif" />
                </form>
            </Modal>
        </AdminLayout>
    );
}
