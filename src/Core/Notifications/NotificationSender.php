<?php
namespace HexaGen\Core\Notifications;

use HexaGen\Core\Notifications\Channels\MailChannel;
use HexaGen\Core\Notifications\Channels\DatabaseChannel;
use HexaGen\Core\Notifications\Channels\SlackChannel;

class NotificationSender
{
    private static array $customChannels = [];

    public static function send(mixed $notifiables, Notification $notification): void
    {
        foreach ((array) $notifiables as $notifiable) {
            static::sendToNotifiable($notifiable, $notification);
        }
    }

    private static function sendToNotifiable(mixed $notifiable, Notification $notification): void
    {
        $channels = $notification->via($notifiable);

        foreach ($channels as $channel) {
            static::resolveChannel($channel)->send($notifiable, $notification);
        }
    }

    private static function resolveChannel(string $channel): object
    {
        if (isset(static::$customChannels[$channel])) {
            return new (static::$customChannels[$channel])();
        }

        return match ($channel) {
            'mail'     => new MailChannel(),
            'database' => new DatabaseChannel(),
            'slack'    => new SlackChannel(),
            default    => throw new \InvalidArgumentException("Unknown notification channel: {$channel}"),
        };
    }

    public static function extend(string $name, string $channelClass): void
    {
        static::$customChannels[$name] = $channelClass;
    }
}
