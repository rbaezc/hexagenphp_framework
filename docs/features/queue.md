# Queue

## Defining a job

```bash
php hexaphp make:job ProcessBookingJob
```

```php
use HexaGen\Core\Queue\Job;

class ProcessBookingJob extends Job
{
    public function __construct(private Booking $booking) {}

    public function handle(): void
    {
        // send confirmation email, update inventory, etc.
        Mail::to($this->booking->email)->send(new BookingConfirmedMail($this->booking));
    }
}
```

## Dispatching

```php
dispatch(new ProcessBookingJob($booking));
dispatch(new ProcessBookingJob($booking))->onQueue('bookings');
dispatch(new ProcessBookingJob($booking))->delay(60); // delay 60 seconds
```

## Running the worker

```bash
php hexaphp queue:work
php hexaphp queue:work --queue=bookings
php hexaphp queue:work --queue=bookings,default
```

## Failed jobs

```bash
php hexaphp queue:failed     # list failed jobs
```

## Drivers

```ini
QUEUE_DRIVER=sync      # runs inline, no worker needed (dev)
QUEUE_DRIVER=database  # stored in jobs table
QUEUE_DRIVER=redis
```

For the `database` driver, install the table first:

```bash
php hexaphp queue:install
php hexaphp migrate
```
