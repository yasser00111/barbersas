import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Checkbox, Field, Input, Select, Textarea } from '@/components/ui/Field';
import AdminLayout from '@/layouts/AdminLayout';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface BusinessHour {
    day_of_week: number;
    day_name: string;
    is_open: boolean;
    open_time: string | null;
    close_time: string | null;
    break_start: string | null;
    break_end: string | null;
}

interface Settings {
    tagline: string | null;
    booking_enabled: boolean;
    require_barber_selection: boolean;
    allow_online_cancel: boolean;
    daily_queue_limit: number;
    assignment_policy: string | null;
    payment_bank_name: string | null;
    payment_bank_account: string | null;
    payment_account_name: string | null;
    payment_instructions: string | null;
}

interface Profile {
    name: string;
    slug: string;
    phone: string | null;
    address: string | null;
    city: string | null;
    logo_path: string | null;
}

export default function SettingsEdit({
    profile,
    settings,
    businessHours,
}: {
    profile: Profile;
    settings: Settings;
    businessHours: BusinessHour[];
}) {
    const form = useForm({
        name: profile.name,
        phone: profile.phone ?? '',
        address: profile.address ?? '',
        city: profile.city ?? '',
        logo: null as File | null,
        tagline: settings.tagline ?? '',
        booking_enabled: settings.booking_enabled,
        require_barber_selection: settings.require_barber_selection,
        allow_online_cancel: settings.allow_online_cancel,
        daily_queue_limit: settings.daily_queue_limit,
        assignment_policy: settings.assignment_policy ?? 'least_busy',
        payment_bank_name: settings.payment_bank_name ?? '',
        payment_bank_account: settings.payment_bank_account ?? '',
        payment_account_name: settings.payment_account_name ?? '',
        payment_instructions: settings.payment_instructions ?? '',
        business_hours: businessHours.map((h) => ({
            day_of_week: h.day_of_week,
            is_open: h.is_open,
            open_time: h.open_time ?? '',
            close_time: h.close_time ?? '',
            break_start: h.break_start ?? '',
            break_end: h.break_end ?? '',
        })),
    });

    const updateHour = (index: number, key: string, value: string | boolean) => {
        const hours = [...form.data.business_hours];
        hours[index] = { ...hours[index], [key]: value };
        form.setData('business_hours', hours);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('admin.settings.update'), { forceFormData: true, preserveScroll: true });
    };

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Pengaturan</h1>
            <p className="text-sm text-ink-500">Profil barbershop, jam operasional, dan pembayaran.</p>
        </div>
    );

    return (
        <AdminLayout title="Pengaturan" heading={heading}>
            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader title="Profil Barbershop" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <Field label="Nama Barbershop" error={form.errors.name} required>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={!!form.errors.name} />
                        </Field>
                        <Field label="Slug (URL)" hint={`/b/${profile.slug}`}>
                            <Input value={profile.slug} disabled />
                        </Field>
                        <Field label="Telepon" error={form.errors.phone}>
                            <Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                        </Field>
                        <Field label="Kota" error={form.errors.city}>
                            <Input value={form.data.city} onChange={(e) => form.setData('city', e.target.value)} />
                        </Field>
                        <Field label="Alamat" error={form.errors.address}>
                            <Input value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
                        </Field>
                        <Field label="Logo" error={form.errors.logo} hint="PNG/JPG maks 2MB">
                            <input
                                type="file"
                                accept="image/*"
                                onChange={(e) => form.setData('logo', e.target.files?.[0] ?? null)}
                                className="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-ink-800"
                            />
                        </Field>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Operasional" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <Field label="Tagline" error={form.errors.tagline} hint="Tampil di halaman publik">
                            <Input value={form.data.tagline} onChange={(e) => form.setData('tagline', e.target.value)} />
                        </Field>
                        <Field label="Batasan Antrian Harian" error={form.errors.daily_queue_limit} hint="0 = tanpa batas">
                            <Input type="number" min={0} value={form.data.daily_queue_limit} onChange={(e) => form.setData('daily_queue_limit', Number(e.target.value))} />
                        </Field>
                        <Field label="Kebijakan Penugasan Barber" error={form.errors.assignment_policy}>
                            <Select value={form.data.assignment_policy} onChange={(e) => form.setData('assignment_policy', e.target.value)}>
                                <option value="least_busy">Paling senggang</option>
                                <option value="round_robin">Bergiliran</option>
                                <option value="manual">Manual</option>
                            </Select>
                        </Field>
                        <div className="flex flex-col justify-end gap-2">
                            <Checkbox checked={form.data.booking_enabled} onChange={(e) => form.setData('booking_enabled', e.target.checked)} label="Aktifkan antrian online" />
                            <Checkbox checked={form.data.require_barber_selection} onChange={(e) => form.setData('require_barber_selection', e.target.checked)} label="Wajib pilih barber" />
                            <Checkbox checked={form.data.allow_online_cancel} onChange={(e) => form.setData('allow_online_cancel', e.target.checked)} label="Izinkan pembatalan online" />
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Jam Operasional" />
                    <CardBody className="space-y-3">
                        {form.data.business_hours.map((hour, index) => (
                            <div key={hour.day_of_week} className="flex flex-wrap items-center gap-3 rounded-lg border border-ink-100 p-3">
                                <div className="w-24">
                                    <Checkbox checked={hour.is_open} onChange={(e) => updateHour(index, 'is_open', e.target.checked)} label={businessHours[index].day_name} />
                                </div>
                                <Input type="time" value={hour.open_time} onChange={(e) => updateHour(index, 'open_time', e.target.value)} className="max-w-[140px]" />
                                <span className="text-ink-400">-</span>
                                <Input type="time" value={hour.close_time} onChange={(e) => updateHour(index, 'close_time', e.target.value)} className="max-w-[140px]" />
                            </div>
                        ))}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Instruksi Pembayaran Manual" description="Ditampilkan ke admin saat membayar tagihan" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <Field label="Nama Bank">
                            <Input value={form.data.payment_bank_name} onChange={(e) => form.setData('payment_bank_name', e.target.value)} />
                        </Field>
                        <Field label="Nomor Rekening">
                            <Input value={form.data.payment_bank_account} onChange={(e) => form.setData('payment_bank_account', e.target.value)} />
                        </Field>
                        <Field label="Atas Nama">
                            <Input value={form.data.payment_account_name} onChange={(e) => form.setData('payment_account_name', e.target.value)} />
                        </Field>
                        <Field label="Instruksi Tambahan">
                            <Textarea value={form.data.payment_instructions} onChange={(e) => form.setData('payment_instructions', e.target.value)} />
                        </Field>
                    </CardBody>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" loading={form.processing}>
                        Simpan Pengaturan
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
