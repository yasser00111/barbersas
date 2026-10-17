import { Button } from '@/components/ui/Button';
import { Field, Input } from '@/components/ui/Field';
import GuestLayout from '@/layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout title="Lupa Password">
            <Head title="Lupa Password" />
            <h1 className="text-xl font-bold text-ink-900">Lupa Password</h1>
            <p className="mt-1 text-sm text-ink-500">Masukkan email Anda untuk menerima tautan reset password.</p>

            {status && <div className="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{status}</div>}

            <form onSubmit={submit} className="mt-6 space-y-4">
                <Field label="Email" error={errors.email} required>
                    <Input type="email" value={data.email} autoFocus onChange={(e) => setData('email', e.target.value)} error={!!errors.email} />
                </Field>

                <Button type="submit" loading={processing} className="w-full" size="lg">
                    Kirim Tautan Reset
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-ink-500">
                <Link href={route('login')} className="font-semibold text-gold-600 hover:underline">
                    Kembali ke halaman masuk
                </Link>
            </p>
        </GuestLayout>
    );
}
