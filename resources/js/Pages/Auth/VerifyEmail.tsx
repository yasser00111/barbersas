import { Button, LinkButton } from '@/components/ui/Button';
import GuestLayout from '@/layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout title="Verifikasi Email">
            <Head title="Verifikasi Email" />
            <h1 className="text-xl font-bold text-ink-900">Verifikasi Email Anda</h1>
            <p className="mt-2 text-sm text-ink-500">
                Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi email Anda dengan mengklik tautan yang
                telah kami kirimkan. Jika tidak menerima email, kami dapat mengirim ulang.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    Tautan verifikasi baru telah dikirim ke email Anda.
                </div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-3">
                <Button type="submit" loading={processing} className="w-full" size="lg">
                    Kirim Ulang Email Verifikasi
                </Button>
                <LinkButton href={route('logout')} method="post" as="button" variant="ghost" className="w-full">
                    Keluar
                </LinkButton>
            </form>
        </GuestLayout>
    );
}
