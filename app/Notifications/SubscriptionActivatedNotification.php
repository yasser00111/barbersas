<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionActivatedNotification extends TenantMailNotification
{
    public function __construct(public Subscription $subscription, public ?string $recipient = null) {}

    protected function recipientName(): ?string
    {
        return $this->recipient;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subscription = $this->subscription;

        return $this->baseMail('Langganan Aktif')
            ->greeting($this->greetingLine())
            ->line('Langganan barbershop Anda telah aktif.')
            ->line('Paket: '.($subscription->plan?->name ?? '-'))
            ->when($subscription->starts_at, fn (MailMessage $m) => $m->line('Mulai: '.$subscription->starts_at->translatedFormat('d F Y')))
            ->when($subscription->ends_at, fn (MailMessage $m) => $m->line('Berakhir: '.$subscription->ends_at->translatedFormat('d F Y')))
            ->action('Buka Dashboard', route('admin.dashboard'))
            ->line('Selamat menjalankan bisnis Anda.');
    }

    protected function payload(): array
    {
        return [
            'type' => 'subscription_activated',
            'ends_at' => $this->subscription->ends_at?->toIso8601String(),
        ];
    }
}
