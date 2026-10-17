import { LinkButton } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';

interface TenantInfo {
    id: number;
    name: string;
    slug: string;
    logo_path: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
}

export default function Unavailable({ tenant, reason }: { tenant: TenantInfo; reason: 'suspended' | 'inactive' }) {
    return (
        <PublicLayout title={`${tenant.name} - Tidak Tersedia`} transparent>
            <div className="mx-auto flex max-w-lg flex-col items-center px-4 py-24 text-center">
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <h1 className="mt-6 text-2xl font-bold text-ink-900">{tenant.name}</h1>
                <p className="mt-2 text-ink-500">
                    {reason === 'suspended'
                        ? 'Barbershop ini sedang ditangguhkan sementara. Silakan hubungi pihak barbershop.'
                        : 'Barbershop ini sedang tidak menerima antrian saat ini.'}
                </p>
                {tenant.phone && <p className="mt-4 text-sm text-ink-500">Telepon: {tenant.phone}</p>}
                <div className="mt-8">
                    <LinkButton href={route('home')} variant="outline">
                        Kembali ke Beranda
                    </LinkButton>
                </div>
            </div>
        </PublicLayout>
    );
}
