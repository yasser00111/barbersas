import { LinkButton } from '@/components/ui/Button';
import { cn, formatRupiah } from '@/lib/utils';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export interface PlanCardPlan {
    id: number;
    name: string;
    slug?: string;
    description?: string | null;
    price: number;
    currency: string;
    duration_days: number;
    features: string[] | null;
    max_barbers?: number | null;
    max_services?: number | null;
}

export function PlanCard({ plan, featured, action }: { plan: PlanCardPlan; featured?: boolean; action?: React.ReactNode }) {
    const { props } = usePage<PageProps>();
    const user = props.auth?.user;

    const defaultHref = user
        ? user.role === 'super_admin'
            ? route('super-admin.plans.index')
            : route('admin.subscription.index')
        : route('register', { plan: plan.id });

    const defaultLabel = user
        ? user.role === 'super_admin'
            ? 'Kelola Paket'
            : 'Pilih di Dasbor'
        : 'Pilih Paket';
    return (
        <div
            className={cn(
                'flex flex-col rounded-2xl border bg-white p-6 shadow-sm',
                featured ? 'border-gold-400 ring-2 ring-gold-200' : 'border-ink-200/70',
            )}
        >
            {featured && (
                <span className="mb-3 w-fit rounded-full bg-gold-400 px-3 py-0.5 text-xs font-bold text-ink-950">
                    Populer
                </span>
            )}
            <h3 className="text-lg font-bold text-ink-900">{plan.name}</h3>
            {plan.description && <p className="mt-1 text-sm text-ink-500">{plan.description}</p>}
            <p className="mt-4 text-3xl font-extrabold text-ink-900">
                {formatRupiah(plan.price)}
                <span className="text-sm font-medium text-ink-400"> / {plan.duration_days} hari</span>
            </p>

            <ul className="mt-5 flex-1 space-y-2 text-sm text-ink-600">
                {plan.max_barbers ? <li>Maksimal {plan.max_barbers} barber</li> : null}
                {plan.max_services ? <li>Maksimal {plan.max_services} layanan</li> : null}
                {(plan.features ?? []).map((feature) => (
                    <li key={feature} className="flex items-start gap-2">
                        <svg className="mt-0.5 h-4 w-4 shrink-0 text-gold-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        {feature}
                    </li>
                ))}
            </ul>

            <div className="mt-6">
                {action ?? (
                    <LinkButton href={defaultHref} variant={featured ? 'secondary' : 'primary'} className="w-full">
                        {defaultLabel}
                    </LinkButton>
                )}
            </div>
        </div>
    );
}
