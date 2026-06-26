# Notifications

## Creating a notification

```bash
php hexaphp make:notification BookingConfirmed
```

```php
use HexaGen\Core\Notifications\Notification;

class BookingConfirmed extends Notification
{
    public function __construct(private Booking $booking) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): array
    {
        return [
            'subject' => 'Your booking is confirmed!',
            'view'    => 'notifications.booking-confirmed',
            'with'    => ['booking' => $this->booking],
        ];
    }

    public function toDatabase(): array
    {
        return [
            'message'    => 'Booking #' . $this->booking->id . ' confirmed.',
            'booking_id' => $this->booking->id,
            'url'        => '/bookings/' . $this->booking->id,
        ];
    }

    public function toSlack(): array
    {
        return [
            'text' => "New booking #{$this->booking->id} confirmed for {$this->booking->email}",
        ];
    }
}
```

## Sending notifications

```php
// Via the Notifiable trait on the model
$user->notify(new BookingConfirmed($booking));

// Via the helper
notify($user, new BookingConfirmed($booking));
```

## Notifiable trait

Add `Notifiable` to your User model:

```php
use HexaGen\Core\Notifications\Traits\Notifiable;

class User extends Model
{
    use Notifiable;
}
```

## Channels

| Channel | Requires |
|---|---|
| `mail` | `MAIL_DRIVER` configured |
| `database` | `notifications` table (run `queue:install`) |
| `slack` | `SLACK_WEBHOOK_URL` in `.env` |

## Reading database notifications

```php
$notifications = $user->notifications()->get();
$unread        = $user->unreadNotifications()->get();

foreach ($unread as $notification) {
    echo $notification->data['message'];
    $notification->markAsRead();
}
```
