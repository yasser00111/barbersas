import { EmptyState } from '@/components/EmptyState';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Checkbox, Field, Input, Textarea } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import AdminLayout from '@/layouts/AdminLayout';
import { router, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

interface Barber {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    bio: string | null;
    photo_path: string | null;
    is_active: boolean;
    service_ids: number[];
    has_account: boolean;
    account_email: string | null;
}

interface Option {
    id: number;
    name: string;
}

const empty = {
    name: '',
    phone: '',
    email: '',
    bio: '',
    is_active: true,
    sort_order: 0,
    photo: null as File | null,
    service_ids: [] as number[],
    create_account: false,
    account_email: '',
    account_password: '',
};

export default function BarbersIndex({ barbers, services }: { barbers: Barber[]; services: Option[] }) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Barber | null>(null);

    const form = useForm({ ...empty });

    useEffect(() => {
        if (!open) return;
        if (editing) {
            form.setData({
                name: editing.name,
                phone: editing.phone ?? '',
                email: editing.email ?? '',
                bio: editing.bio ?? '',
                is_active: editing.is_active,
                sort_order: 0,
                photo: null,
                service_ids: editing.service_ids.map(Number),
                create_account: false,
                account_email: '',
                account_password: '',
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

    const openEdit = (barber: Barber) => {
        setEditing(barber);
        setOpen(true);
    };

    const toggleService = (id: number) => {
        const current = form.data.service_ids;
        form.setData('service_ids', current.includes(id) ? current.filter((s) => s !== id) : [...current, id]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = {
            forceFormData: true,
            onSuccess: () => {
                setOpen(false);
                setEditing(null);
            },
        };

        if (editing) {
            form.put(route('admin.barbers.update', editing.id), options);
        } else {
            form.post(route('admin.barbers.store'), options);
        }
    };

    const remove = (barber: Barber) => {
        if (confirm(`Hapus barber "${barber.name}"?`)) {
            router.delete(route('admin.barbers.destroy', barber.id), { preserveScroll: true });
        }
    };

    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Barber</h1>
                <p className="text-sm text-ink-500">Kelola data barber dan akun login mereka.</p>
            </div>
            <Button onClick={openCreate}>Tambah Barber</Button>
        </div>
    );

    return (
        <AdminLayout title="Barber" heading={heading}>
            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {barbers.length === 0 ? (
                        <EmptyState title="Belum ada barber" action={<Button onClick={openCreate}>Tambah Barber</Button>} />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Nama</th>
                                        <th className="px-4 py-3">Kontak</th>
                                        <th className="px-4 py-3">Akun</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {barbers.map((barber) => (
                                        <tr key={barber.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-ink-100 text-xs font-bold text-ink-500">
                                                        {barber.photo_path ? (
                                                            <img src={`/storage/${barber.photo_path}`} alt={barber.name} className="h-full w-full object-cover" />
                                                        ) : (
                                                            barber.name.charAt(0)
                                                        )}
                                                    </div>
                                                    <span className="font-semibold text-ink-900">{barber.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-ink-500">
                                                <p>{barber.phone ?? '-'}</p>
                                                <p className="text-xs">{barber.email ?? ''}</p>
                                            </td>
                                            <td className="px-4 py-3">
                                                {barber.has_account ? (
                                                    <Badge tone="green">Ada</Badge>
                                                ) : (
                                                    <Badge tone="gray">Tidak</Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {barber.is_active ? <Badge tone="green">Aktif</Badge> : <Badge tone="gray">Nonaktif</Badge>}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button size="sm" variant="outline" onClick={() => openEdit(barber)}>
                                                        Ubah
                                                    </Button>
                                                    <Button size="sm" variant="danger" onClick={() => remove(barber)}>
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
                title={editing ? 'Ubah Barber' : 'Tambah Barber'}
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
                        <Field label="Nama" error={form.errors.name} required>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={!!form.errors.name} />
                        </Field>
                        <Field label="Nomor HP" error={form.errors.phone}>
                            <Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} error={!!form.errors.phone} />
                        </Field>
                    </div>

                    <Field label="Email (kontak)" error={form.errors.email}>
                        <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={!!form.errors.email} />
                    </Field>

                    <Field label="Bio" error={form.errors.bio}>
                        <Textarea value={form.data.bio} onChange={(e) => form.setData('bio', e.target.value)} />
                    </Field>

                    <Field label="Foto" error={form.errors.photo} hint="JPG/PNG, maks 2MB">
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => form.setData('photo', e.target.files?.[0] ?? null)}
                            className="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-ink-800"
                        />
                    </Field>

                    <div>
                        <span className="block text-sm font-medium text-ink-700">Layanan yang Dikuasai</span>
                        <div className="mt-2 grid grid-cols-2 gap-2">
                            {services.map((service) => (
                                <Checkbox
                                    key={service.id}
                                    checked={form.data.service_ids.includes(service.id)}
                                    onChange={() => toggleService(service.id)}
                                    label={service.name}
                                />
                            ))}
                        </div>
                    </div>

                    <Checkbox checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} label="Aktif" />

                    {!editing?.has_account && (
                        <div className="rounded-lg border border-ink-200 p-4">
                            <Checkbox
                                checked={form.data.create_account}
                                onChange={(e) => form.setData('create_account', e.target.checked)}
                                label="Buatkan akun login untuk barber ini"
                            />
                            {form.data.create_account && (
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    <Field label="Email Akun" error={form.errors.account_email} required>
                                        <Input
                                            type="email"
                                            value={form.data.account_email}
                                            onChange={(e) => form.setData('account_email', e.target.value)}
                                            error={!!form.errors.account_email}
                                        />
                                    </Field>
                                    <Field label="Password Awal" error={form.errors.account_password} required>
                                        <Input
                                            type="text"
                                            value={form.data.account_password}
                                            onChange={(e) => form.setData('account_password', e.target.value)}
                                            error={!!form.errors.account_password}
                                        />
                                    </Field>
                                </div>
                            )}
                        </div>
                    )}
                </form>
            </Modal>
        </AdminLayout>
    );
}
