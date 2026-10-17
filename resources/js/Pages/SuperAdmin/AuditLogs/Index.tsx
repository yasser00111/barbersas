import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Input } from '@/components/ui/Field';
import { Pagination, Paginator } from '@/components/ui/Pagination';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { formatDateTime } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { Fragment, useState } from 'react';

interface AuditLog {
    id: number;
    event: string;
    description: string | null;
    user: string | null;
    tenant: string | null;
    ip_address: string | null;
    created_at: string | null;
    changes: { old: Record<string, unknown> | null; new: Record<string, unknown> | null };
}

export default function AuditLogsIndex({ logs, filters }: { logs: Paginator<AuditLog>; filters: { event: string } }) {
    const [event, setEvent] = useState(filters.event);
    const [expanded, setExpanded] = useState<number | null>(null);

    const apply = () => router.get(route('super-admin.audit-logs.index'), { event }, { preserveState: true, preserveScroll: true });

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Audit Log</h1>
            <p className="text-sm text-ink-500">Jejak aktivitas penting di platform.</p>
        </div>
    );

    return (
        <SuperAdminLayout title="Audit Log" heading={heading}>
            <Card className="mb-4">
                <CardBody>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply();
                        }}
                        className="flex flex-wrap gap-2"
                    >
                        <Input value={event} onChange={(e) => setEvent(e.target.value)} placeholder="Cari jenis event (mis. tenant.)" className="max-w-xs" />
                        <Button type="submit" variant="outline">
                            Cari
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card className="overflow-hidden">
                <CardBody className="p-0">
                    {logs.data.length === 0 ? (
                        <EmptyState title="Tidak ada log" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                                    <tr>
                                        <th className="px-4 py-3">Event</th>
                                        <th className="px-4 py-3">Deskripsi</th>
                                        <th className="px-4 py-3">Pengguna</th>
                                        <th className="px-4 py-3">Tenant</th>
                                        <th className="px-4 py-3">IP</th>
                                        <th className="px-4 py-3">Waktu</th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {logs.data.map((log) => {
                                        const hasChanges = Boolean(log.changes.old || log.changes.new);

                                        return (
                                            <Fragment key={log.id}>
                                                <tr className="hover:bg-ink-50/60">
                                                    <td className="px-4 py-3 font-mono text-xs font-semibold text-ink-900">{log.event}</td>
                                                    <td className="px-4 py-3 text-ink-600">{log.description ?? '-'}</td>
                                                    <td className="px-4 py-3 text-ink-500">{log.user ?? '-'}</td>
                                                    <td className="px-4 py-3 text-ink-500">{log.tenant ?? '-'}</td>
                                                    <td className="px-4 py-3 text-ink-400">{log.ip_address ?? '-'}</td>
                                                    <td className="px-4 py-3 text-ink-500">{log.created_at ? formatDateTime(log.created_at) : '-'}</td>
                                                    <td className="px-4 py-3 text-right">
                                                        {hasChanges && (
                                                            <button
                                                                type="button"
                                                                onClick={() => setExpanded(expanded === log.id ? null : log.id)}
                                                                className="text-xs font-semibold text-gold-600 hover:underline"
                                                            >
                                                                {expanded === log.id ? 'Tutup' : 'Detail'}
                                                            </button>
                                                        )}
                                                    </td>
                                                </tr>
                                                {expanded === log.id && hasChanges && (
                                                    <tr className="bg-ink-50/60">
                                                        <td colSpan={7} className="px-4 py-3">
                                                            <div className="grid gap-3 sm:grid-cols-2">
                                                                <ChangeBlock title="Sebelum" data={log.changes.old} />
                                                                <ChangeBlock title="Sesudah" data={log.changes.new} />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </Fragment>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardBody>
                <div className="px-4">
                    <Pagination paginator={logs} />
                </div>
            </Card>
        </SuperAdminLayout>
    );
}

function ChangeBlock({ title, data }: { title: string; data: Record<string, unknown> | null }) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-ink-400">{title}</p>
            <pre className="mt-1 overflow-x-auto rounded-lg bg-ink-950 p-3 text-xs text-ink-100">
                {data && Object.keys(data).length > 0 ? JSON.stringify(data, null, 2) : '-'}
            </pre>
        </div>
    );
}
