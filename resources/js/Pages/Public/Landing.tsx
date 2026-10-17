import { PlanCard, PlanCardPlan } from '@/components/PlanCard';
import { LinkButton } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function Landing({ plans }: { plans: PlanCardPlan[] }) {
    const { props } = usePage<PageProps>();
    const user = props.auth?.user;

    const ctaHref = user
        ? user.role === 'super_admin'
            ? route('super-admin.dashboard')
            : user.role === 'barber'
              ? route('barber.dashboard')
              : route('admin.dashboard')
        : route('register');

    const ctaLabel = user ? 'Buka Dasbor' : 'Mulai Gratis';
    const bottomCtaLabel = user ? 'Buka Dasbor' : 'Daftar Sekarang';

    const features = [
        { title: 'Antrian Online', desc: 'Pelanggan mendaftar antrian dari mana saja lewat QR code atau tautan.' },
        { title: 'Kelola Barber', desc: 'Atur jadwal, layanan, dan beban kerja tiap barber secara rapi.' },
        { title: 'Laporan Real-time', desc: 'Pantau pendapatan harian, mingguan, dan bulanan secara otomatis.' },
        { title: 'Multi Cabang', desc: 'Satu akun untuk mengelola beberapa cabang barbershop Anda.' },
    ];

    return (
        <PublicLayout title="BarberSaaS - Platform Antrian Barbershop">
            <section className="relative overflow-hidden bg-ink-950">
                <div className="absolute inset-0 opacity-20" style={{ background: 'radial-gradient(circle at 30% 20%, #d4af37 0, transparent 45%)' }} />
                <div className="relative mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-28">
                    <div className="max-w-2xl">
                        <span className="inline-block rounded-full border border-gold-400/40 bg-gold-400/10 px-3 py-1 text-xs font-semibold text-gold-300">
                            Solusi Barbershop Modern
                        </span>
                        <h1 className="mt-5 text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                            Kelola Antrian Barbershop <span className="text-gold-400">Tanpa Ribet</span>
                        </h1>
                        <p className="mt-5 text-lg text-ink-300">
                            Sistem antrian digital lengkap dengan manajemen barber, layanan, pelanggan, dan laporan.
                            Pelanggan daftar online, Anda fokus melayani.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <LinkButton href={ctaHref} variant="secondary" size="lg">
                                {ctaLabel}
                            </LinkButton>
                            <LinkButton href={route('pricing')} variant="outline" size="lg" className="border-ink-700 bg-transparent text-white hover:bg-ink-900">
                                Lihat Harga
                            </LinkButton>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {features.map((feature) => (
                        <div key={feature.title} className="rounded-xl border border-ink-200/70 bg-white p-5 shadow-sm">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-100 text-gold-700">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 className="mt-4 text-sm font-bold text-ink-900">{feature.title}</h3>
                            <p className="mt-1 text-sm text-ink-500">{feature.desc}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="bg-white py-16">
                <div className="mx-auto max-w-6xl px-4 sm:px-6">
                    <div className="text-center">
                        <h2 className="text-3xl font-bold text-ink-900">Paket Harga Sederhana</h2>
                        <p className="mt-2 text-ink-500">Pilih paket sesuai ukuran barbershop Anda.</p>
                    </div>
                    <div className="mt-10 grid gap-6 md:grid-cols-3">
                        {plans.map((plan, i) => (
                            <PlanCard key={plan.id} plan={plan} featured={i === 1} />
                        ))}
                    </div>
                    <div className="mt-8 text-center">
                        <Link href={route('pricing')} className="text-sm font-semibold text-gold-600 hover:underline">
                            Bandingkan semua paket &rarr;
                        </Link>
                    </div>
                </div>
            </section>

            <section className="bg-ink-950 py-16">
                <div className="mx-auto max-w-3xl px-4 text-center sm:px-6">
                    <h2 className="text-3xl font-bold text-white">Siap memodernisasi barbershop Anda?</h2>
                    <p className="mt-3 text-ink-300">Daftar sekarang dan aktifkan dalam hitungan menit.</p>
                    <div className="mt-8 flex justify-center">
                        <LinkButton href={ctaHref} variant="secondary" size="lg">
                            {bottomCtaLabel}
                        </LinkButton>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
