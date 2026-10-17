import { LinkButton } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import AdminLayout from '@/layouts/AdminLayout';

export default function QrCodeIndex({
    publicUrl,
    queueUrl,
    svg,
    tenant,
}: {
    publicUrl: string;
    queueUrl: string;
    svg: string;
    tenant: { name: string; slug: string };
}) {
    const heading = (
        <div>
            <h1 className="text-xl font-bold text-ink-900">QR Code</h1>
            <p className="text-sm text-ink-500">Tempel QR code ini di barbershop agar pelanggan mudah mendaftar antrian.</p>
        </div>
    );

    return (
        <AdminLayout title="QR Code" heading={heading}>
            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader title="QR Code Barbershop" description={tenant.name} />
                    <CardBody className="flex flex-col items-center gap-4">
                        <div className="rounded-xl border border-ink-200 bg-white p-4" dangerouslySetInnerHTML={{ __html: svg }} />
                        <p className="break-all text-center text-xs text-ink-500">{publicUrl}</p>
                        <LinkButton href={route('admin.qr.download')} variant="outline">
                            Unduh SVG
                        </LinkButton>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Tautan Antrian & Info" />
                    <CardBody className="space-y-4 text-sm">
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-ink-400">Halaman Publik</p>
                            <a href={publicUrl} target="_blank" rel="noreferrer" className="mt-1 block break-all font-medium text-gold-600 hover:underline">
                                {publicUrl}
                            </a>
                        </div>
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-ink-400">Ambil Antrian</p>
                            <a href={queueUrl} target="_blank" rel="noreferrer" className="mt-1 block break-all font-medium text-gold-600 hover:underline">
                                {queueUrl}
                            </a>
                        </div>
                        <div className="rounded-lg bg-ink-50 p-4 text-xs text-ink-500">
                            <p className="font-semibold text-ink-700">Tips</p>
                            <p className="mt-1">
                                Cetak QR code ini dan letakkan di meja kasir atau pintu masuk. Pelanggan cukup memindai untuk
                                mengambil nomor antrian tanpa mengunduh aplikasi.
                            </p>
                        </div>
                    </CardBody>
                </Card>
            </div>
        </AdminLayout>
    );
}
