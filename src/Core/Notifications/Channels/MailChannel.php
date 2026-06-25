<?php
namespace HexaGen\Core\Notifications\Channels;

use HexaGen\Core\Notifications\Notification;

class MailChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $mailable = $notification->toMail($notifiable);
        if ($mailable === null) {
            return;
        }

        $address = method_exists($notifiable, 'routeNotificationForMail')
            ? $notifiable->routeNotificationForMail()
            : ($notifiable->email ?? null);

        if (!$address) {
            return;
        }

        \HexaGen\Core\Mail\MailManager::to($address)->send($mailable);
    }
}
