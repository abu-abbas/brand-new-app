<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ImpersonationEndedNotification extends Notification
{
    public function __construct(
        public readonly string $adminUserId,
        public readonly string $adminName,
        public readonly string $reason,
        public readonly string $startedAt,
        public readonly string $endedAt,
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
        $reasonLabels = [
            'manual_leave' => 'Penghentian Manual oleh Admin',
            'ttl_expired' => 'Batas Waktu Sesi (TTL 60 Menit) Berakhir',
            'session_invalidated' => 'Pembatalan Sesi Otomatis (Perubahan Assignment)',
            'logout' => 'Admin Logout dari Aplikasi',
        ];

        $reasonText = $reasonLabels[$this->reason] ?? $this->reason;
        $durationMinutes = $this->calculateDurationMinutes($this->startedAt, $this->endedAt);

        return (new MailMessage)
            ->subject('Pemberitahuan: Sesi Impersonate Telah Berakhir')
            ->greeting('Halo, '.$notifiable->name)
            ->line("Sesi impersonate oleh Administrator ({$this->adminName}) pada akun Anda telah berakhir.")
            ->line('Alasan Berakhir: '.$reasonText)
            ->line('Waktu Mulai: '.$this->formatDateTime($this->startedAt))
            ->line('Waktu Selesai: '.$this->formatDateTime($this->endedAt))
            ->line('Durasi Sesi: '.$durationMinutes.' menit')
            ->line('Seluruh aktivitas selama sesi tetap tersimpan secara permanen dalam audit log sistem.');
    }

    private function calculateDurationMinutes(string $startIso, string $endIso): int
    {
        try {
            $start = Carbon::parse($startIso);
            $end = Carbon::parse($endIso);

            return max(0, (int) $start->diffInMinutes($end));
        } catch (\Throwable) {
            return 0;
        }
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
