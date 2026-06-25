<?php
namespace HexaGen\Core\Mail;

use HexaGen\Core\Mail\Drivers\SmtpDriver;
use HexaGen\Core\Mail\Drivers\LogDriver;
use HexaGen\Core\Mail\Drivers\NullDriver;

class MailManager
{
    private static array $drivers = [];

    public static function driver(?string $driver = null): MailDriverInterface
    {
        $driver ??= (string) \HexaGen\Core\Config::get('mail.driver', 'log');

        if (!isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::createDriver($driver);
        }

        return static::$drivers[$driver];
    }

    private static function createDriver(string $driver): MailDriverInterface
    {
        return match ($driver) {
            'smtp'  => new SmtpDriver(),
            'log'   => new LogDriver(),
            'null'  => new NullDriver(),
            default => throw new \InvalidArgumentException("Unsupported mail driver: {$driver}"),
        };
    }

    public static function extend(string $driver, MailDriverInterface $instance): void
    {
        static::$drivers[$driver] = $instance;
    }

    public static function to(string|array $address, string $name = ''): MailPendingSend
    {
        return (new MailPendingSend(static::driver()))->to($address, $name);
    }

    public static function send(Mailable $mailable): void
    {
        $mailable->build();
        static::driver()->send($mailable);
    }
}
