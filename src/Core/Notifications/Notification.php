<?php
namespace HexaGen\Core\Notifications;

abstract class Notification
{
    public string $id;

    public function __construct()
    {
        $this->id = \HexaGen\Core\Support\Str::uuid();
    }

    abstract public function via(mixed $notifiable): array;

    public function toMail(mixed $notifiable): ?\HexaGen\Core\Mail\Mailable
    {
        return null;
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [];
    }

    public function toSlack(mixed $notifiable): ?array
    {
        return null;
    }

    public function toBroadcast(mixed $notifiable): array
    {
        return [];
    }
}
