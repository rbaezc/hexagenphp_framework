<?php
namespace HexaGen\Core\Notifications\Traits;

use HexaGen\Core\Notifications\Notification;
use HexaGen\Core\Notifications\NotificationSender;

trait Notifiable
{
    public function notify(Notification $notification): void
    {
        NotificationSender::send($this, $notification);
    }

    public function notifyNow(Notification $notification): void
    {
        NotificationSender::send($this, $notification);
    }

    public function notifications(): array
    {
        return db('notifications')
            ->where('notifiable_id', $this->id)
            ->where('notifiable_type', static::class)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function unreadNotifications(): array
    {
        return db('notifications')
            ->where('notifiable_id', $this->id)
            ->where('notifiable_type', static::class)
            ->whereNull('read_at')
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
