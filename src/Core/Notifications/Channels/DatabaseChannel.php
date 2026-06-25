<?php
namespace HexaGen\Core\Notifications\Channels;

use HexaGen\Core\Notifications\Notification;

class DatabaseChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $data = $notification->toDatabase($notifiable);
        if (empty($data)) {
            return;
        }

        $notifiableId   = $notifiable->id ?? null;
        $notifiableType = get_class($notifiable);

        db('notifications')->insert([
            'id'              => $notification->id,
            'type'            => get_class($notification),
            'notifiable_id'   => $notifiableId,
            'notifiable_type' => $notifiableType,
            'data'            => json_encode($data),
            'read_at'         => null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
