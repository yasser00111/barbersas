<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class TenantMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function payload(): array;

    protected function appName(): string
    {
        return (string) config('app.name', 'BarberSaaS');
    }

    protected function greetingLine(): string
    {
        return 'Halo '.($this->recipientName() ?? 'Pengguna').',';
    }

    protected function recipientName(): ?string
    {
        return null;
    }

    protected function baseMail(string $subject): MailMessage
    {
        return (new MailMessage)->subject($subject.($this->appName() ? ' - '.$this->appName() : ''));
    }
}
