<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentVerifiedNotification extends TenantMailNotification
{
    public function __construct(public Payment $payment, public ?string $recipient = null) {}

    protected function recipientName(): ?string
    {
        return $this->recipient;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment;

        return $this->baseMail('Pembayaran Berhasil')
            ->greeting($this->greetingLine())
            ->line('Pembayaran Anda telah berhasil diverifikasi.')
            ->line('Invoice: '.($payment->invoice?->number ?? '-'))
            ->line('Jumlah: '.$payment->currency.' '.number_format((float) $payment->amount, 0, ',', '.'))
            ->line('Metode: '.$payment->method->label())
            ->action('Lihat Langganan', route('admin.subscription.index'))
            ->line('Langganan Anda kini aktif. Terima kasih.');
    }

    protected function payload(): array
    {
        return [
            'type' => 'payment_verified',
            'invoice' => $this->payment->invoice?->number,
            'amount' => (float) $this->payment->amount,
        ];
    }
}
