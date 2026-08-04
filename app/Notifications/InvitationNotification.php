<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $token,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? $notifiable->getEmailForPasswordReset()
            : ($notifiable->v_email ?? '');

        $url = config('app.url') . '/reset-password?' . http_build_query([
            'email' => $email,
            'token' => $this->token,
        ]);

        return (new MailMessage)
            ->subject('Undangan Aktivasi Akun Pengguna')
            ->greeting('Halo ' . ($notifiable->v_username ?? 'Pengguna') . ',')
            ->line('Akun Anda telah dibuat. Silakan atur password Anda melalui tautan di bawah ini untuk mengaktifkan akun.')
            ->action('Atur Password Akun', $url)
            ->line('Tautan ini berlaku selama 60 menit.')
            ->line('Jika Anda tidak merasa meminta pembuatan akun ini, abaikan pesan email ini.');
    }
}
