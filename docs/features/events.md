# Events

## Defining an event

```bash
php hexaphp make:event FlightBooked
```

```php
use HexaGen\Core\Events\Event;

class FlightBooked extends Event
{
    public function __construct(
        public readonly Flight  $flight,
        public readonly User    $user,
    ) {}
}
```

## Defining a listener

```php
use HexaGen\Core\Events\ListenerInterface;

class SendBookingConfirmation implements ListenerInterface
{
    public function handle(object $event): void
    {
        Mail::to($event->user->email)
            ->send(new BookingConfirmedMail($event->flight));
    }
}
```

## Registering listeners

```php
// In a ServiceProvider or Services.php
EventDispatcher::listen(FlightBooked::class, SendBookingConfirmation::class);

// Closure listener
EventDispatcher::listen(FlightBooked::class, function (FlightBooked $event) {
    cache()->delete('user_flights_' . $event->user->id);
});
```

## Firing events

```php
event(new FlightBooked($flight, $user));

// Or directly
EventDispatcher::dispatch(new FlightBooked($flight, $user));
```

## Multiple listeners per event

```php
EventDispatcher::listen(FlightBooked::class, SendBookingConfirmation::class);
EventDispatcher::listen(FlightBooked::class, UpdateInventory::class);
EventDispatcher::listen(FlightBooked::class, NotifyAdmins::class);
```

All listeners run in registration order when the event is fired.
