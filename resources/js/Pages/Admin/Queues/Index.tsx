import { EmptyState } from '@/components/EmptyState';
import { StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, StatCard } from '@/components/ui/Card';
import { Field, Input, Select } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import AdminLayout from '@/layouts/AdminLayout';
import { router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface QueueRow {
    id: number;
    uuid: string;
    number: number;
    status: string;
    status_label: string;
    source: string;
    customer: string | null;
    phone: string | null;
    service: string | null;
    barber: string | null;
    barber_id: number | null;
    estimated_wait_minutes: number | null;
    created_at: string | null;
}

interface Option {
    id: number;
    name: string;
}

export default function QueuesIndex({
    queues,
    counts,
    filters,
    services,
    barbers,
}: {
    queues: QueueRow[];
    counts: { waiting: number; called: number; in_service: number; completed: number; cancelled: number };
    filters: { date: string; status: string };
    services: Option[];
    barbers: Option[];
}) {
    const [walkInOpen, setWalkInOpen] = useState(false);
    const [cancelTarget, setCancelTarget] = useState<QueueRow | null>(null);

    const walkInForm = useForm({ name: '', phone: '', service_id: '' as number | string, barber_id: '' as number | string, notes: '' });
    const cancelForm = useForm({ reason: '' });

    const applyFilter = (key: string, value: string) => {
        router.get(route('admin.queues.index'), { ...filters, [key]: value }, { preserveState: true, preserveScroll: true });
    };

    const submitWalkIn = (e: FormEvent) => {
        e.preventDefault();
        walkInForm.post(route('admin.queues.store'), {
            onSuccess: () => {
                walkInForm.reset();
                setWalkInOpen(false);
            },
        });
    };

    const callNext = () => router.post(route('admin.queues.call-next'), {}, { preserveScroll: true });

    const heading = (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 className="text-xl font-bold text-ink-900">Antrian</h1>
                <p className="text-sm text-ink-500">Kelola antrian harian barbershop.</p>
            </div>
            <div className="flex flex-wrap gap-2">
                <Button variant="outline" onClick={callNext}>
                    Panggil Berikutnya
                </Button>
                <Button onClick={() => setWalkInOpen(true)}>Tambah Walk-in</Button>
            </div>
        </div>
    );

    return (
        <AdminLayout title="Antrian" heading={heading}>
            <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <StatCard label="Menunggu" value={counts.waiting} accent />
                <StatCard label="Dipanggil" value={counts.called} />
                <StatCard label="Dilayani" value={counts.in_service} />
                <StatCard label="Selesai" value={counts.completed} />
                <StatCard label="Batal / No-show" value={counts.cancelled} />
            </div>

            <Card className="mt-6">
                <CardBody className="flex flex-wrap items-end gap-3">
                    <Field label="Tanggal">
                        <Input type="date" value={filters.date} onChange={(e) => applyFilter('date', e.target.value)} />
                    </Field>
                    <Field label="Status">
                        <Select value={filters.status} onChange={(e) => applyFilter('status', e.target.value)}>
                            <option value="">Semua</option>
                            <option value="waiting">Menunggu</option>
                            <option value="called">Dipanggil</option>
                            <option value="in_service">Sedang Dilayani</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                            <option value="no_show">Tidak Hadir</option>
                        </Select>
                    </Field>
                </CardBody>
            </Card>

            <Card className="mt-4 overflow-hidden">
                <CardBody className="p-0">
                    {queues.length === 0 ? (
                        <EmptyState title="Tidak ada antrian" description="Belum ada antrian pada tanggal ini." />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">No</th>
                                        <th className="px-4 py-3">Pelanggan</th>
                                        <th className="px-4 py-3">Layanan</th>
                                        <th className="px-4 py-3">Barber</th>
                                        <th className="px-4 py-3">Sumber</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {queues.map((queue) => (
                                        <tr key={queue.id} className="hover:bg-ink-50/60">
                                            <td className="px-4 py-3">
                                                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-900 text-xs font-bold text-gold-400">
                                                    {queue.number}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <p className="font-semibold text-ink-900">{queue.customer ?? '-'}</p>
                                                <p className="text-xs text-ink-500">{queue.phone ?? '-'}</p>
                                            </td>
                                            <td className="px-4 py-3 text-ink-600">{queue.service ?? '-'}</td>
                                            <td className="px-4 py-3 text-ink-600">{queue.barber ?? 'Barber mana saja'}</td>
                                            <td className="px-4 py-3 text-xs text-ink-500">{queue.source === 'online' ? 'Online' : 'Walk-in'}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={queue.status} label={queue.status_label} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap justify-end gap-1">
                                                    {queue.status === 'waiting' && (
                                                        <Button size="sm" variant="outline" onClick={() => router.post(route('admin.queues.call', queue.uuid), {}, { preserveScroll: true })}>
                                                            Panggil
                                                        </Button>
                                                    )}
                                                    {queue.status === 'called' && (
                                                        <>
                                                            <Button size="sm" variant="outline" onClick={() => router.post(route('admin.queues.recall', queue.uuid), {}, { preserveScroll: true })}>
                                                                Panggil Ulang
                                                            </Button>
                                                            <Button size="sm" onClick={() => router.post(route('admin.queues.start', queue.uuid), {}, { preserveScroll: true })}>
                                                                Mulai
                                                            </Button>
                                                        </>
                                                    )}
                                                    {queue.status === 'in_service' && (
                                                        <Button size="sm" variant="secondary" onClick={() => router.post(route('admin.queues.complete', queue.uuid), {}, { preserveScroll: true })}>
                                                            Selesai
                                                        </Button>
                                                    )}
                                                    {['waiting', 'called', 'in_service'].includes(queue.status) && (
                                                        <>
                                                            <Button size="sm" variant="ghost" onClick={() => router.post(route('admin.queues.no-show', queue.uuid), {}, { preserveScroll: true })}>
                                                                No-show
                                                            </Button>
                                                            <Button size="sm" variant="danger" onClick={() => setCancelTarget(queue)}>
                                                                Batalkan
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
            </Card>

            <Modal
                open={walkInOpen}
                onClose={() => setWalkInOpen(false)}
                title="Tambah Antrian Walk-in"
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setWalkInOpen(false)}>
                            Batal
                        </Button>
                        <Button loading={walkInForm.processing} onClick={submitWalkIn}>
                            Simpan
                        </Button>
                    </>
                }
            >
                <form onSubmit={submitWalkIn} className="space-y-4">
                    <Field label="Nama" error={walkInForm.errors.name} required>
                        <Input value={walkInForm.data.name} onChange={(e) => walkInForm.setData('name', e.target.value)} error={!!walkInForm.errors.name} />
                    </Field>
                    <Field label="Nomor HP" error={walkInForm.errors.phone} required>
                        <Input value={walkInForm.data.phone} onChange={(e) => walkInForm.setData('phone', e.target.value)} error={!!walkInForm.errors.phone} />
                    </Field>
                    <Field label="Layanan" error={walkInForm.errors.service_id} required>
                        <Select value={walkInForm.data.service_id} onChange={(e) => walkInForm.setData('service_id', e.target.value)} error={!!walkInForm.errors.service_id}>
                            <option value="">Pilih layanan</option>
                            {services.map((service) => (
                                <option key={service.id} value={service.id}>
                                    {service.name}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Barber" error={walkInForm.errors.barber_id} hint="Opsional">
                        <Select value={walkInForm.data.barber_id} onChange={(e) => walkInForm.setData('barber_id', e.target.value)} error={!!walkInForm.errors.barber_id}>
                            <option value="">Barber mana saja</option>
                            {barbers.map((barber) => (
                                <option key={barber.id} value={barber.id}>
                                    {barber.name}
                                </option>
                            ))}
                        </Select>
                    </Field>
                </form>
            </Modal>

            <Modal
                open={!!cancelTarget}
                onClose={() => setCancelTarget(null)}
                title={`Batalkan Antrian #${cancelTarget?.number}`}
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setCancelTarget(null)}>
                            Tutup
                        </Button>
                        <Button
                            variant="danger"
                            loading={cancelForm.processing}
                            onClick={() => {
                                if (!cancelTarget) return;
                                cancelForm.post(route('admin.queues.cancel', cancelTarget.uuid), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        cancelForm.reset();
                                        setCancelTarget(null);
                                    },
                                });
                            }}
                        >
                            Batalkan
                        </Button>
                    </>
                }
            >
                <Field label="Alasan" error={cancelForm.errors.reason} hint="Opsional">
                    <Input value={cancelForm.data.reason} onChange={(e) => cancelForm.setData('reason', e.target.value)} />
                </Field>
            </Modal>
        </AdminLayout>
    );
}
