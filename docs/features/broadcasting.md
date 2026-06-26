# Broadcasting

Real-time events over **Server-Sent Events (SSE)** — no WebSocket server needed.

## Defining a broadcastable event

```php
use HexaGen\Core\Events\Event;
use HexaGen\Core\Broadcasting\ShouldBroadcast;

class FlightStatusUpdated extends Event implements ShouldBroadcast
{
    public function __construct(public readonly Flight $flight) {}

    public function broadcastOn(): string
    {
        return 'flights';   // channel name
    }

    public function broadcastAs(): string
    {
        return 'status.updated';   // event name
    }

    public function broadcastWith(): array
    {
        return [
            'id'     => $this->flight->id,
            'status' => $this->flight->status,
        ];
    }
}
```

## Firing the event

```php
event(new FlightStatusUpdated($flight));
// Broadcasting happens automatically because it implements ShouldBroadcast
```

## Subscribing on the client

```js
const source = new EventSource('/broadcast/flights');

source.addEventListener('status.updated', (e) => {
    const data = JSON.parse(e.data);
    console.log('Flight', data.id, 'is now', data.status);
});

source.onerror = () => source.close();
```

## SSE endpoint

The framework registers `/broadcast/{channel}` automatically. No configuration needed.

## Private channels (authenticated)

```js
// Pass the session cookie automatically via fetch + EventSource
const source = new EventSource('/broadcast/user.' + userId, {
    withCredentials: true
});
```

Validate access in a middleware or in the broadcastOn logic with the authenticated user.
