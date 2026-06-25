<?php
namespace HexaGen\Core\Notifications\Channels;

use HexaGen\Core\Notifications\Notification;
use HexaGen\Core\Http\Http;

class SlackChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $payload = $notification->toSlack($notifiable);
        if ($payload === null) {
            return;
        }

        $webhookUrl = method_exists($notifiable, 'routeNotificationForSlack')
            ? $notifiable->routeNotificationForSlack()
            : \HexaGen\Core\Config::get('notifications.slack.webhook_url', '');

        if (!$webhookUrl) {
            return;
        }

        Http::postJson($webhookUrl, $payload);
    }
}
