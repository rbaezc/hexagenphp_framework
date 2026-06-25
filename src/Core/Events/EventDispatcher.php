<?php
namespace HexaGen\Core\Events;

/**
 * Event Bus — registra listeners y despacha eventos.
 *
 * Registrar un listener:
 *   EventDispatcher::listen(UserRegistered::class, SendWelcomeEmail::class);
 *   EventDispatcher::listen(UserRegistered::class, function(UserRegistered $e) { ... });
 *
 * Despachar un evento:
 *   EventDispatcher::dispatch(new UserRegistered($user));
 *   // o con el helper global:
 *   event(new UserRegistered($user));
 *
 * Suscriptores (un objeto con múltiples listeners):
 *   EventDispatcher::subscribe(NotificationSubscriber::class);
 *   // El suscriptor debe implementar subscribe(EventDispatcher): void
 */
class EventDispatcher
{
    /** @var array<string, array<callable|string>> */
    private static array $listeners = [];

    public static function listen(string $eventClass, callable|string $listener): void
    {
        self::$listeners[$eventClass][] = $listener;
    }

    public static function subscribe(string $subscriberClass): void
    {
        $subscriber = new $subscriberClass();
        $subscriber->subscribe(new self());
    }

    /**
     * Dispatch an event to all registered listeners.
     * Returns the event after all listeners have processed it.
     */
    public static function dispatch(Event $event): Event
    {
        $eventClass = get_class($event);

        foreach (self::$listeners[$eventClass] ?? [] as $listener) {
            if (is_string($listener)) {
                $instance = new $listener();
                $instance->handle($event);
            } else {
                $listener($event);
            }
        }

        return $event;
    }

    /** Check if any listener is registered for an event. */
    public static function hasListeners(string $eventClass): bool
    {
        return !empty(self::$listeners[$eventClass]);
    }

    /** Remove all listeners for an event (useful in tests). */
    public static function forget(string $eventClass): void
    {
        unset(self::$listeners[$eventClass]);
    }

    /** Remove all registered listeners (useful in tests). */
    public static function flush(): void
    {
        self::$listeners = [];
    }
}
