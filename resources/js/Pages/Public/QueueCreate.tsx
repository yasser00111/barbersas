import { Button } from '@/components/ui/Button';
import { Field, Input, Select, Textarea } from '@/components/ui/Field';
import { useForm } from '@inertiajs/react';
import PublicLayout from '@/layouts/PublicLayout';
import { formatRupiah } from '@/lib/utils';
import { FormEvent, useMemo } from 'react';

interface Service {
    id: number;
    name: string;
    price: number;
    duration_minutes: number;
}

interface Barber {
    id: number;
    name: string;
    service_ids: number[];
}

interface TenantInfo {
    id: number;
    name: string;
    slug: string;
    phone: string | null;
    address: string | null;
}

export default function QueueCreate({
    tenant,
    services,
    barbers,
    available,
    unavailableReason,
    requireBarberSelection,
}: {
    tenant: TenantInfo;
    services: Service[];
    barbers: Barber[];
    available: boolean;
    unavailableReason: string | null;
    requireBarberSelection: boolean;
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone: '',
        email: '',
        service_id: '' as number | string,
        barber_id: '' as number | string,
        notes: '',
    });

    const eligibleBarbers = useMemo(() => {
        if (!data.service_id) return barbers;

        return barbers.filter((b) => b.service_ids.map(Number).includes(Number(data.service_id)));
    }, [barbers, data.service_id]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('public.queue.store', tenant.slug));
    };

    return (
        <PublicLayout title={`Ambil Antrian - ${tenant.name}`} transparent>
            <div className="mx-auto max-w-lg px-4 py-10 sm:px-6">
                <h1 className="text-2xl font-bold text-ink-900">Ambil Antrian</h1>
                <p className="mt-1 text-sm text-ink-500">{tenant.name}</p>

                {!available ? (
                    <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                        <p className="font-semibold">Antrian belum dibuka</p>
                        <p className="mt-1">{unavailableReason ?? 'Silakan coba lagi nanti.'}</p>
                    </div>
                ) : (
                    <form onSubmit={submit} className="mt-6 space-y-4 rounded-2xl border border-ink-200/70 bg-white p-5 shadow-sm">
                        <Field label="Nama" error={errors.name} required>
                            <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Nama Anda" error={!!errors.name} />
                        </Field>

                        <Field label="Nomor HP" error={errors.phone} required>
                            <Input value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="08xxxxxxxxxx" error={!!errors.phone} />
                        </Field>

                        <Field label="Email" error={errors.email} hint="Opsional, untuk notifikasi">
                            <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="email@contoh.com" error={!!errors.email} />
                        </Field>

                        <Field label="Layanan" error={errors.service_id} required>
                            <Select
                                value={data.service_id}
                                onChange={(e) => {
                                    setData('service_id', e.target.value);
                                    setData('barber_id', '');
                                }}
                                error={!!errors.service_id}
                            >
                                <option value="">Pilih layanan</option>
                                {services.map((service) => (
                                    <option key={service.id} value={service.id}>
                                        {service.name} - {formatRupiah(service.price)}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field
                            label="Barber"
                            error={errors.barber_id}
                            required={requireBarberSelection}
                            hint={requireBarberSelection ? undefined : 'Opsional, biarkan kosong untuk barber mana saja'}
                        >
                            <Select value={data.barber_id} onChange={(e) => setData('barber_id', e.target.value)} error={!!errors.barber_id}>
                                <option value="">{requireBarberSelection ? 'Pilih barber' : 'Barber mana saja'}</option>
                                {eligibleBarbers.map((barber) => (
                                    <option key={barber.id} value={barber.id}>
                                        {barber.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field label="Catatan" error={errors.notes}>
                            <Textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Catatan tambahan (opsional)" />
                        </Field>

                        <Button type="submit" loading={processing} className="w-full" size="lg">
                            Ambil Nomor Antrian
                        </Button>
                    </form>
                )}
            </div>
        </PublicLayout>
    );
}
