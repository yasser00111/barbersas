<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffAccountInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $staffName,
        public string $businessName,
        public string $setPasswordUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Barber Anda - '.$this->businessName)
            ->greeting('Halo '.$this->staffName.',')
            ->line('Akun Anda untuk '.$this->businessName.' telah dibuat.')
            ->line('Demi keamanan, silakan atur password Anda melalui tautan di bawah ini. Tautan ini berlaku terbatas dan hanya dapat digunakan sekali.')
            ->action('Atur Password', $this->setPasswordUrl)
            ->line('Jika Anda tidak merasa memiliki akun ini, abaikan email ini.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'staff_account_invitation',
            'business' => $this->businessName,
        ];
    }
}
