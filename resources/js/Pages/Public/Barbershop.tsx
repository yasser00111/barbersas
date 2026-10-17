import { LinkButton } from '@/components/ui/Button';
import { formatRupiah, formatTime } from '@/lib/utils';
import PublicLayout from '@/layouts/PublicLayout';

interface Service {
    id: number;
    name: string;
    description: string | null;
    price: number;
    duration_minutes: number;
}

interface Barber {
    id: number;
    name: string;
    bio: string | null;
    photo_path: string | null;
    service_ids: number[];
}

interface BusinessHour {
    day_of_week: number;
    day_name: string;
    is_open: boolean;
    open_time: string | null;
    close_time: string | null;
}

interface TenantInfo {
    id: number;
    name: string;
    slug: string;
    logo_path: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
}

export default function Barbershop({
    tenant,
    services,
    barbers,
    businessHours,
    settings,
}: {
    tenant: TenantInfo;
    services: Service[];
    barbers: Barber[];
    businessHours: BusinessHour[];
    settings: { require_barber_selection: boolean; tagline: string | null };
}) {
    return (
        <PublicLayout title={`${tenant.name} - Antrian`} transparent>
            <section className="relative overflow-hidden bg-ink-950">
                <div className="relative mx-auto max-w-4xl px-4 py-14 text-center sm:px-6">
                    <div className="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-ink-900">
                        {tenant.logo_path ? (
                            <img src={`/storage/${tenant.logo_path}`} alt={tenant.name} className="h-full w-full object-cover" />
                        ) : (
                            <span className="text-xl font-black text-gold-400">{tenant.name.charAt(0)}</span>
                        )}
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold text-white">{tenant.name}</h1>
                    {settings.tagline && <p className="mt-2 text-ink-300">{settings.tagline}</p>}
                    <p className="mt-2 text-sm text-ink-400">
                        {[tenant.address, tenant.city].filter(Boolean).join(', ') || 'Alamat belum diisi'}
                    </p>
                    <div className="mt-7">
                        <LinkButton href={route('public.queue.create', tenant.slug)} variant="secondary" size="lg">
                            Ambil Antrian Sekarang
                        </LinkButton>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
                <h2 className="text-xl font-bold text-ink-900">Layanan</h2>
                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                    {services.map((service) => (
                        <div key={service.id} className="rounded-xl border border-ink-200/70 bg-white p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="font-semibold text-ink-900">{service.name}</p>
                                    {service.description && <p className="mt-0.5 text-xs text-ink-500">{service.description}</p>}
                                    <p className="mt-1 text-xs text-ink-400">{service.duration_minutes} menit</p>
                                </div>
                                <span className="whitespace-nowrap text-sm font-bold text-gold-600">{formatRupiah(service.price)}</span>
                            </div>
                        </div>
                    ))}
                    {services.length === 0 && <p className="text-sm text-ink-400">Belum ada layanan.</p>}
                </div>

                <h2 className="mt-10 text-xl font-bold text-ink-900">Barber Kami</h2>
                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {barbers.map((barber) => (
                        <div key={barber.id} className="flex items-center gap-3 rounded-xl border border-ink-200/70 bg-white p-4">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-ink-100 text-sm font-bold text-ink-500">
                                {barber.photo_path ? (
                                    <img src={`/storage/${barber.photo_path}`} alt={barber.name} className="h-full w-full object-cover" />
                                ) : (
                                    barber.name.charAt(0)
                                )}
                            </div>
                            <div className="min-w-0">
                                <p className="truncate font-semibold text-ink-900">{barber.name}</p>
                                {barber.bio && <p className="truncate text-xs text-ink-500">{barber.bio}</p>}
                            </div>
                        </div>
                    ))}
                    {barbers.length === 0 && <p className="text-sm text-ink-400">Belum ada barber.</p>}
                </div>

                <h2 className="mt-10 text-xl font-bold text-ink-900">Jam Operasional</h2>
                <div className="mt-4 overflow-hidden rounded-xl border border-ink-200/70 bg-white">
                    <table className="w-full text-sm">
                        <tbody>
                            {businessHours.map((hour) => (
                                <tr key={hour.day_of_week} className="border-b border-ink-100 last:border-0">
                                    <td className="px-4 py-2.5 font-medium text-ink-700">{hour.day_name}</td>
                                    <td className="px-4 py-2.5 text-right text-ink-500">
                                        {hour.is_open ? `${formatTime(hour.open_time)} - ${formatTime(hour.close_time)}` : 'Tutup'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>
        </PublicLayout>
    );
}
