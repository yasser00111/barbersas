<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentFailedNotification extends TenantMailNotification
{
    public function __construct(public Payment $payment, public ?string $recipient = null) {}

    protected function recipientName(): ?string
    {
        return $this->recipient;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment;

        return $this->baseMail('Pembayaran Tidak Berhasil')
            ->greeting($this->greetingLine())
            ->line('Pembayaran Anda tidak dapat kami proses.')
            ->line('Invoice: '.($payment->invoice?->number ?? '-'))
            ->line('Jumlah: '.$payment->currency.' '.number_format((float) $payment->amount, 0, ',', '.'))
            ->when($payment->rejection_reason, fn (MailMessage $m) => $m->line('Alasan: '.$payment->rejection_reason))
            ->line('Silakan lakukan pembayaran ulang atau hubungi dukungan.')
            ->action('Coba Bayar Lagi', route('admin.subscription.index'));
    }

    protected function payload(): array
    {
        return [
            'type' => 'payment_failed',
            'invoice' => $this->payment->invoice?->number,
        ];
    }
}
