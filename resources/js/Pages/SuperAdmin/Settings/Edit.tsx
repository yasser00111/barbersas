import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Checkbox, Field, Input, Select } from '@/components/ui/Field';
import SuperAdminLayout from '@/layouts/SuperAdminLayout';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Settings {
    smtp_host: string | null;
    smtp_port: number | string | null;
    smtp_username: string | null;
    smtp_encryption: string | null;
    smtp_password_set: boolean;
    mail_from_address: string | null;
    mail_from_name: string | null;
    payment_gateway: string | null;
    midtrans_server_key_set: boolean;
    midtrans_client_key: string | null;
    midtrans_merchant_id: string | null;
    midtrans_is_production: boolean;
    grace_period_days: number;
}

export default function SettingsEdit({ settings, gateways }: { settings: Settings; gateways: string[] }) {
    const form = useForm({
        smtp_host: settings.smtp_host ?? '',
        smtp_port: settings.smtp_port ?? '',
        smtp_username: settings.smtp_username ?? '',
        smtp_password: '',
        smtp_encryption: settings.smtp_encryption ?? '',
        mail_from_address: settings.mail_from_address ?? '',
        mail_from_name: settings.mail_from_name ?? '',
        payment_gateway: settings.payment_gateway ?? 'manual',
        midtrans_server_key: '',
        midtrans_client_key: settings.midtrans_client_key ?? '',
        midtrans_merchant_id: settings.midtrans_merchant_id ?? '',
        midtrans_is_production: settings.midtrans_is_production,
        grace_period_days: settings.grace_period_days,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(route('super-admin.settings.update'), { preserveScroll: true });
    };

    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">Pengaturan Platform</h1>
            <p className="text-sm text-ink-500">Konfigurasi email, pembayaran, dan langganan.</p>
        </div>
    );

    return (
        <SuperAdminLayout title="Pengaturan" heading={heading}>
            <form onSubmit={submit} className="space-y-6">
                <Card>
                    <CardHeader title="Email / SMTP" description="Digunakan untuk mengirim notifikasi ke tenant" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <Field label="SMTP Host" error={form.errors.smtp_host}>
                            <Input value={form.data.smtp_host} onChange={(e) => form.setData('smtp_host', e.target.value)} />
                        </Field>
                        <Field label="SMTP Port" error={form.errors.smtp_port}>
                            <Input type="number" value={form.data.smtp_port} onChange={(e) => form.setData('smtp_port', e.target.value)} />
                        </Field>
                        <Field label="SMTP Username" error={form.errors.smtp_username}>
                            <Input value={form.data.smtp_username} onChange={(e) => form.setData('smtp_username', e.target.value)} />
                        </Field>
                        <Field
                            label="SMTP Password"
                            error={form.errors.smtp_password}
                            hint={settings.smtp_password_set ? 'Sudah diatur. Isi hanya untuk mengubah.' : undefined}
                        >
                            <Input type="password" value={form.data.smtp_password} onChange={(e) => form.setData('smtp_password', e.target.value)} placeholder={settings.smtp_password_set ? '••••••••' : ''} />
                        </Field>
                        <Field label="Enkripsi" error={form.errors.smtp_encryption}>
                            <Select value={form.data.smtp_encryption} onChange={(e) => form.setData('smtp_encryption', e.target.value)}>
                                <option value="">Tidak ada</option>
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="starttls">STARTTLS</option>
                            </Select>
                        </Field>
                        <div />
                        <Field label="Email Pengirim" error={form.errors.mail_from_address}>
                            <Input type="email" value={form.data.mail_from_address} onChange={(e) => form.setData('mail_from_address', e.target.value)} />
                        </Field>
                        <Field label="Nama Pengirim" error={form.errors.mail_from_name}>
                            <Input value={form.data.mail_from_name} onChange={(e) => form.setData('mail_from_name', e.target.value)} />
                        </Field>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Pembayaran" description="Gateway pembayaran aktif" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <Field label="Gateway" error={form.errors.payment_gateway} required>
                            <Select value={form.data.payment_gateway} onChange={(e) => form.setData('payment_gateway', e.target.value)}>
                                {gateways.map((gateway) => (
                                    <option key={gateway} value={gateway}>
                                        {gateway}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <div className="flex items-end">
                            <Checkbox
                                checked={form.data.midtrans_is_production}
                                onChange={(e) => form.setData('midtrans_is_production', e.target.checked)}
                                label="Mode Produksi Midtrans"
                            />
                        </div>
                        <Field
                            label="Midtrans Server Key"
                            error={form.errors.midtrans_server_key}
                            hint={settings.midtrans_server_key_set ? 'Sudah diatur. Isi hanya untuk mengubah.' : undefined}
                        >
                            <Input type="password" value={form.data.midtrans_server_key} onChange={(e) => form.setData('midtrans_server_key', e.target.value)} placeholder={settings.midtrans_server_key_set ? '••••••••' : ''} />
                        </Field>
                        <Field label="Midtrans Client Key" error={form.errors.midtrans_client_key}>
                            <Input value={form.data.midtrans_client_key} onChange={(e) => form.setData('midtrans_client_key', e.target.value)} />
                        </Field>
                        <Field label="Midtrans Merchant ID" error={form.errors.midtrans_merchant_id}>
                            <Input value={form.data.midtrans_merchant_id} onChange={(e) => form.setData('midtrans_merchant_id', e.target.value)} />
                        </Field>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Langganan" />
                    <CardBody className="max-w-xs">
                        <Field label="Masa Tenggang (hari)" error={form.errors.grace_period_days} hint="Setelah langganan berakhir">
                            <Input type="number" min={0} value={form.data.grace_period_days} onChange={(e) => form.setData('grace_period_days', Number(e.target.value))} />
                        </Field>
                    </CardBody>
                </Card>

                <div className="flex items-center justify-between">
                    <div className="flex gap-2">
                        {settings.smtp_password_set && <Badge tone="green">SMTP dikonfigurasi</Badge>}
                        {settings.midtrans_server_key_set && <Badge tone="green">Midtrans dikonfigurasi</Badge>}
                    </div>
                    <Button type="submit" loading={form.processing}>
                        Simpan Pengaturan
                    </Button>
                </div>
            </form>
        </SuperAdminLayout>
    );
}
