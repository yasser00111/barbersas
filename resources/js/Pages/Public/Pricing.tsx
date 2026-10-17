import { PlanCard, PlanCardPlan } from '@/components/PlanCard';
import PublicLayout from '@/layouts/PublicLayout';

export default function Pricing({ plans }: { plans: PlanCardPlan[] }) {
    return (
        <PublicLayout title="Harga - BarberSaaS">
            <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <div className="text-center">
                    <h1 className="text-4xl font-extrabold text-ink-900">Paket Harga</h1>
                    <p className="mt-3 text-ink-500">
                        Semua paket mencakup sistem antrian, manajemen barber, layanan, dan laporan. Tanpa biaya tersembunyi.
                    </p>
                </div>

                <div className="mt-12 grid gap-6 md:grid-cols-3">
                    {plans.map((plan, i) => (
                        <PlanCard key={plan.id} plan={plan} featured={i === 1} />
                    ))}
                </div>

                {plans.length === 0 && (
                    <p className="mt-12 text-center text-sm text-ink-400">Belum ada paket yang tersedia.</p>
                )}

                <div className="mx-auto mt-16 max-w-3xl space-y-4">
                    <h2 className="text-center text-xl font-bold text-ink-900">Pertanyaan Umum</h2>
                    {[
                        { q: 'Apakah ada masa uji coba gratis?', a: 'Langganan dihitung per periode (mis. 30 hari) dan aktif setelah pembayaran diverifikasi.' },
                        { q: 'Metode pembayaran apa yang didukung?', a: 'Transfer manual (unggah bukti) dan pembayaran online melalui gateway yang tersedia.' },
                        { q: 'Apakah bisa upgrade paket?', a: 'Bisa. Paket dapat diubah dan berlaku untuk periode berikutnya.' },
                    ].map((item) => (
                        <div key={item.q} className="rounded-xl border border-ink-200/70 bg-white p-5">
                            <p className="text-sm font-semibold text-ink-900">{item.q}</p>
                            <p className="mt-1 text-sm text-ink-500">{item.a}</p>
                        </div>
                    ))}
                </div>
            </section>
        </PublicLayout>
    );
}
