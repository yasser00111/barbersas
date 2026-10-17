<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiredNotification extends TenantMailNotification
{
    public function __construct(public Subscription $subscription, public ?string $recipient = null) {}

    protected function recipientName(): ?string
    {
        return $this->recipient;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->baseMail('Langganan Berakhir')
            ->greeting($this->greetingLine())
            ->line('Masa langganan barbershop Anda telah berakhir.')
            ->line('Fitur operasional (antrian, barber, layanan) dinonaktifkan sementara.')
            ->line('Data Anda tetap aman dan tidak dihapus.')
            ->action('Perpanjang Sekarang', route('admin.subscription.index'))
            ->line('Silakan perpanjang untuk mengaktifkan kembali layanan.');
    }

    protected function payload(): array
    {
        return ['type' => 'subscription_expired'];
    }
}
