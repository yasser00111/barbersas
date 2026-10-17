import { Button } from '@/components/ui/Button';
import { Field, Input } from '@/components/ui/Field';
import GuestLayout from '@/layouts/GuestLayout';
import { cn, formatRupiah } from '@/lib/utils';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';

interface Plan {
    id: number;
    name: string;
    price: number;
    currency: string;
    duration_days: number;
    features: string[];
}

export default function Register({ plans }: { plans: Plan[] }) {
    const { url } = usePage();
    const preselected = useMemo(() => {
        const params = new URL(url, window.location.origin).searchParams;
        const id = params.get('plan');

        return id ? Number(id) : '';
    }, [url]);

    const { data, setData, post, processing, errors } = useForm({
        owner_name: '',
        email: '',
        phone: '',
        business_name: '',
        business_phone: '',
        city: '',
        plan_id: preselected as number | string,
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('register'));
    };

    return (
        <GuestLayout title="Daftar Barbershop">
            <Head title="Daftar" />
            <h1 className="text-xl font-bold text-ink-900">Daftarkan Barbershop Anda</h1>
            <p className="mt-1 text-sm text-ink-500">Buat akun dan pilih paket langganan.</p>

            <form onSubmit={submit} className="mt-6 space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Nama Pemilik" error={errors.owner_name} required>
                        <Input value={data.owner_name} onChange={(e) => setData('owner_name', e.target.value)} error={!!errors.owner_name} />
                    </Field>
                    <Field label="Nama Barbershop" error={errors.business_name} required>
                        <Input value={data.business_name} onChange={(e) => setData('business_name', e.target.value)} error={!!errors.business_name} />
                    </Field>
                </div>

                <Field label="Email" error={errors.email} required>
                    <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={!!errors.email} />
                </Field>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Nomor HP" error={errors.phone} required>
                        <Input value={data.phone} onChange={(e) => setData('phone', e.target.value)} error={!!errors.phone} />
                    </Field>
                    <Field label="Telepon Barbershop" error={errors.business_phone}>
                        <Input value={data.business_phone} onChange={(e) => setData('business_phone', e.target.value)} error={!!errors.business_phone} />
                    </Field>
                </div>

                <Field label="Kota" error={errors.city}>
                    <Input value={data.city} onChange={(e) => setData('city', e.target.value)} error={!!errors.city} />
                </Field>

                <div>
                    <span className="block text-sm font-medium text-ink-700">
                        Paket Langganan <span className="text-rose-500">*</span>
                    </span>
                    <div className="mt-2 grid gap-2 sm:grid-cols-3">
                        {plans.map((plan) => (
                            <button
                                type="button"
                                key={plan.id}
                                onClick={() => setData('plan_id', plan.id)}
                                className={cn(
                                    'rounded-xl border p-3 text-left transition',
                                    Number(data.plan_id) === plan.id
                                        ? 'border-gold-400 bg-gold-50 ring-1 ring-gold-300'
                                        : 'border-ink-200 bg-white hover:border-ink-300',
                                )}
                            >
                                <p className="text-sm font-bold text-ink-900">{plan.name}</p>
                                <p className="mt-0.5 text-xs font-semibold text-gold-600">{formatRupiah(plan.price)}</p>
                                <p className="text-xs text-ink-400">/ {plan.duration_days} hari</p>
                            </button>
                        ))}
                    </div>
                    {errors.plan_id && <p className="mt-1 text-xs font-medium text-rose-600">{errors.plan_id}</p>}
                </div>

                <Field label="Password" error={errors.password} hint="Minimal 8 karakter, kombinasi huruf dan angka" required>
                    <Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} error={!!errors.password} />
                </Field>

                <Field label="Konfirmasi Password" error={errors.password_confirmation} required>
                    <Input
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        error={!!errors.password_confirmation}
                    />
                </Field>

                <Button type="submit" loading={processing} className="w-full" size="lg">
                    Daftar & Lanjut ke Pembayaran
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-ink-500">
                Sudah punya akun?{' '}
                <Link href={route('login')} className="font-semibold text-gold-600 hover:underline">
                    Masuk
                </Link>
            </p>
        </GuestLayout>
    );
}
