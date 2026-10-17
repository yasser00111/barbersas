<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionRenewedNotification extends TenantMailNotification
{
    public function __construct(public Subscription $subscription, public ?string $recipient = null) {}

    protected function recipientName(): ?string
    {
        return $this->recipient;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subscription = $this->subscription;

        return $this->baseMail('Perpanjangan Berhasil')
            ->greeting($this->greetingLine())
            ->line('Perpanjangan langganan Anda berhasil.')
            ->line('Paket: '.($subscription->plan?->name ?? '-'))
            ->when($subscription->ends_at, fn (MailMessage $m) => $m->line('Masa aktif berakhir: '.$subscription->ends_at->translatedFormat('d F Y')))
            ->action('Lihat Langganan', route('admin.subscription.index'))
            ->line('Terima kasih atas kepercayaan Anda.');
    }

    protected function payload(): array
    {
        return [
            'type' => 'subscription_renewed',
            'ends_at' => $this->subscription->ends_at?->toIso8601String(),
        ];
    }
}
