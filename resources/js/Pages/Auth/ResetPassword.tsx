import { Button } from '@/components/ui/Button';
import { Field, Input } from '@/components/ui/Field';
import GuestLayout from '@/layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout title="Reset Password">
            <Head title="Reset Password" />
            <h1 className="text-xl font-bold text-ink-900">Atur Password Baru</h1>

            <form onSubmit={submit} className="mt-6 space-y-4">
                <Field label="Email" error={errors.email} required>
                    <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={!!errors.email} />
                </Field>

                <Field label="Password Baru" error={errors.password} hint="Minimal 8 karakter, kombinasi huruf dan angka" required>
                    <Input type="password" value={data.password} autoFocus onChange={(e) => setData('password', e.target.value)} error={!!errors.password} />
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
                    Simpan Password
                </Button>
            </form>
        </GuestLayout>
    );
}
