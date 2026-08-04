<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $token,
        public readonly bool $isAdminReset = false,
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

        $url = config('app.url').'/reset-password?'.http_build_query([
            'email' => $email,
            'token' => $this->token,
        ]);

        $subject = $this->isAdminReset
            ? 'Permintaan Reset Password oleh Admin'
            : 'Permintaan Reset Password Akun';

        $lineMessage = $this->isAdminReset
            ? 'Administrator telah mengirimkan tautan untuk mengatur ulang password akun Anda.'
            : 'Anda menerima email ini karena ada permintaan reset password untuk akun Anda.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo '.($notifiable->v_username ?? 'Pengguna').',')
            ->line($lineMessage)
            ->action('Reset Password', $url)
            ->line('Tautan reset password ini berlaku selama 60 menit.')
            ->line('Jika Anda tidak merasa meminta reset password, tidak ada tindakan lebih lanjut yang diperlukan.');
    }
}
