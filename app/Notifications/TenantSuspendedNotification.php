<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class TenantSuspendedNotification extends TenantMailNotification
{
    public function __construct(public ?string $recipient = null) {}

    protected function recipientName(): ?string
    {
        return $this->recipient;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->baseMail('Akun Barbershop Ditangguhkan')
            ->greeting($this->greetingLine())
            ->line('Akun barbershop Anda telah ditangguhkan oleh administrator platform.')
            ->line('Selama penangguhan, fitur operasional tidak dapat digunakan.')
            ->line('Silakan hubungi dukungan untuk informasi lebih lanjut.');
    }

    protected function payload(): array
    {
        return ['type' => 'tenant_suspended'];
    }
}
