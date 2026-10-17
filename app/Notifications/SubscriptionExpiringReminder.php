<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiringReminder extends TenantMailNotification
{
    public function __construct(public Subscription $subscription, public int $daysLeft) {}

    public function toMail(object $notifiable): MailMessage
    {
        return $this->baseMail('Pengingat: Langganan Berakhir dalam '.$this->daysLeft.' Hari')
            ->greeting($this->greetingLine())
            ->line('Langganan barbershop Anda akan berakhir dalam '.$this->daysLeft.' hari.')
            ->when($this->subscription->ends_at, fn (MailMessage $m) => $m->line('Tanggal berakhir: '.$this->subscription->ends_at->translatedFormat('d F Y')))
            ->line('Perpanjang sekarang agar layanan tidak terganggu.')
            ->action('Perpanjang Langganan', route('admin.subscription.index'))
            ->line('Terima kasih.');
    }

    protected function payload(): array
    {
        return [
            'type' => 'subscription_reminder',
            'days_left' => $this->daysLeft,
        ];
    }
}
