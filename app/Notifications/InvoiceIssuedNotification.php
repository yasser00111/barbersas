<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;

class InvoiceIssuedNotification extends TenantMailNotification
{
    public function __construct(public Invoice $invoice, public ?string $recipient = null) {}

    protected function recipientName(): ?string
    {
        return $this->recipient;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice;

        return $this->baseMail('Invoice Baru '.$invoice->number)
            ->greeting($this->greetingLine())
            ->line('Invoice baru telah dibuat untuk langganan Anda.')
            ->line('Nomor Invoice: '.$invoice->number)
            ->line('Paket: '.($invoice->plan_name ?? '-'))
            ->line('Jumlah: '.$invoice->currency.' '.number_format((float) $invoice->amount, 0, ',', '.'))
            ->when($invoice->due_at, fn (MailMessage $m) => $m->line('Jatuh Tempo: '.$invoice->due_at->translatedFormat('d F Y')))
            ->action('Lihat & Bayar Invoice', route('admin.subscription.index'))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }

    protected function payload(): array
    {
        return [
            'type' => 'invoice_issued',
            'invoice' => $this->invoice->number,
            'amount' => (float) $this->invoice->amount,
        ];
    }
}
