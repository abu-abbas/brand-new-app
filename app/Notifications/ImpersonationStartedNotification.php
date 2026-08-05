<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ImpersonationStartedNotification extends Notification
{
    public function __construct(
        public readonly string $adminUserId,
        public readonly string $adminName,
        public readonly string $targetGroupId,
        public readonly string $startedAt,
        public readonly string $expiresAt,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pemberitahuan: Sesi Impersonate Dimulai pada Akun Anda')
            ->greeting('Halo, '.$notifiable->name)
            ->line("Administrator ({$this->adminName}) telah memulai sesi impersonate pada akun Anda.")
            ->line('Group Aktif Target: '.$this->targetGroupId)
            ->line('Waktu Mulai: '.$this->formatDateTime($this->startedAt))
            ->line('Waktu Berakhir (TTL): '.$this->formatDateTime($this->expiresAt))
            ->line('Aktivitas ini dicatat dalam audit log sistem demi keamanan data Anda.');
    }

    private function formatDateTime(string $isoString): string
    {
        try {
            return Carbon::parse($isoString)
                ->locale('id')
                ->translatedFormat('j M Y, H:i:s');
        } catch (\Throwable) {
            return $isoString;
        }
    }
}
