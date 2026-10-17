import { LinkButton } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';
import { cn } from '@/lib/utils';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface QueueStatusData {
    number: number;
    status: string;
    status_label: string;
    position_ahead: number;
    estimated_wait_minutes: number | null;
    service: string | null;
    barber: string | null;
    serving_number: number | null;
    created_at: string | null;
}

interface TenantInfo {
    id: number;
    name: string;
    slug: string;
    phone: string | null;
    address: string | null;
}

export default function QueueStatus({
    tenant,
    queue: initialQueue,
    queueNumber,
    allowCancel,
}: {
    tenant: TenantInfo;
    queue: QueueStatusData | null;
    queueNumber: number;
    allowCancel: boolean;
}) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };
    const [queue, setQueue] = useState<QueueStatusData | null>(initialQueue);

    useEffect(() => {
        const id = setInterval(async () => {
            try {
                const res = await fetch(route('public.queue.poll', { tenant: tenant.slug, queueNumber }), {
                    headers: { Accept: 'application/json' },
                });
                const json = await res.json();
                setQueue(json.queue);
            } catch {
                /* ignore */
            }
        }, 8000);

        return () => clearInterval(id);
    }, [tenant.slug, queueNumber]);

    const cancel = () => {
        if (confirm('Batalkan antrian ini?')) {
            router.post(route('public.queue.cancel', { tenant: tenant.slug, queueNumber }));
        }
    };

    const statusTone =
        queue?.status === 'in_service' || queue?.status === 'called'
            ? 'bg-sky-100 text-sky-700'
            : queue?.status === 'completed'
              ? 'bg-emerald-100 text-emerald-700'
              : queue?.status === 'cancelled' || queue?.status === 'no_show'
                ? 'bg-ink-100 text-ink-500'
                : 'bg-amber-100 text-amber-700';

    return (
        <PublicLayout title={`Antrian #${queueNumber} - ${tenant.name}`} transparent>
            <div className="mx-auto max-w-md px-4 py-10 sm:px-6">
                <p className="text-center text-sm text-ink-500">{tenant.name}</p>

                {flash?.success && (
                    <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {flash.error}
                    </div>
                )}

                {!queue ? (
                    <div className="mt-6 rounded-2xl border border-ink-200/70 bg-white p-8 text-center shadow-sm">
                        <p className="text-sm text-ink-500">Antrian #{queueNumber} tidak ditemukan untuk hari ini.</p>
                        <div className="mt-6">
                            <LinkButton href={route('public.queue.create', tenant.slug)}>Ambil Antrian Baru</LinkButton>
                        </div>
                    </div>
                ) : (
                    <div className="mt-6 overflow-hidden rounded-2xl border border-ink-200/70 bg-white shadow-sm">
                        <div className="bg-ink-950 px-6 py-8 text-center">
                            <p className="text-xs font-medium uppercase tracking-wide text-ink-400">Nomor Antrian Anda</p>
                            <p className="mt-2 text-6xl font-black text-gold-400">{queue.number}</p>
                            <span className={cn('mt-4 inline-block rounded-full px-3 py-1 text-xs font-bold', statusTone)}>
                                {queue.status_label}
                            </span>
                        </div>
                        <div className="space-y-3 px-6 py-6 text-sm">
                            <Row label="Layanan" value={queue.service ?? '-'} />
                            <Row label="Barber" value={queue.barber ?? 'Barber mana saja'} />
                            <Row label="Sedang Dilayani" value={queue.serving_number ? `#${queue.serving_number}` : '-'} />
                            <Row label="Antrian di Depan" value={queue.position_ahead != null ? `${queue.position_ahead} orang` : '-'} />
                            <Row
                                label="Estimasi Tunggu"
                                value={queue.estimated_wait_minutes != null ? `± ${queue.estimated_wait_minutes} menit` : '-'}
                            />
                        </div>
                        {allowCancel && queue.status === 'waiting' && (
                            <div className="border-t border-ink-100 px-6 py-4">
                                <button
                                    type="button"
                                    onClick={cancel}
                                    className="w-full rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50"
                                >
                                    Batalkan Antrian
                                </button>
                            </div>
                        )}
                    </div>
                )}

                <p className="mt-4 text-center text-xs text-ink-400">Halaman ini diperbarui otomatis setiap 8 detik.</p>
            </div>
        </PublicLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-ink-500">{label}</span>
            <span className="font-semibold text-ink-900">{value}</span>
        </div>
    );
}
