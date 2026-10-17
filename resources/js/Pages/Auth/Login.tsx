import { Button } from '@/components/ui/Button';
import { Checkbox, Field, Input } from '@/components/ui/Field';
import GuestLayout from '@/layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout title="Masuk">
            <Head title="Masuk" />
            <h1 className="text-xl font-bold text-ink-900">Masuk ke akun Anda</h1>
            <p className="mt-1 text-sm text-ink-500">Kelola antrian dan barbershop Anda.</p>

            {status && <div className="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{status}</div>}

            <form onSubmit={submit} className="mt-6 space-y-4">
                <Field label="Email" error={errors.email} required>
                    <Input
                        type="email"
                        value={data.email}
                        autoComplete="username"
                        autoFocus
                        onChange={(e) => setData('email', e.target.value)}
                        error={!!errors.email}
                    />
                </Field>

                <Field label="Password" error={errors.password} required>
                    <Input
                        type="password"
                        value={data.password}
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                        error={!!errors.password}
                    />
                </Field>

                <div className="flex items-center justify-between">
                    <Checkbox
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        label="Ingat saya"
                    />
                    <Link href={route('password.request')} className="text-sm font-medium text-gold-600 hover:underline">
                        Lupa password?
                    </Link>
                </div>

                <Button type="submit" loading={processing} className="w-full" size="lg">
                    Masuk
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-ink-500">
                Belum punya akun?{' '}
                <Link href={route('register')} className="font-semibold text-gold-600 hover:underline">
                    Daftar barbershop
                </Link>
            </p>
        </GuestLayout>
    );
}
