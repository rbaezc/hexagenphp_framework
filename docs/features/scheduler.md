# Scheduler

Define recurring tasks in code — no crontab management per task.

## Defining tasks

```php
// src/Core/Console/Scheduler.php
protected function schedule(Schedule $schedule): void
{
    // Artisan-style commands
    $schedule->command('flights:sync')->hourly();
    $schedule->command('reports:generate')->dailyAt('06:00');
    $schedule->command('cache:clear')->weekly();

    // Closure tasks
    $schedule->call(function () {
        Flight::query()->where('status', 'expired')->delete();
    })->everyFiveMinutes();

    // Shell commands
    $schedule->exec('mysqldump my_app > /backups/db.sql')->daily();
}
```

## Frequency options

| Method | Runs |
|---|---|
| `->everyMinute()` | Every minute |
| `->everyFiveMinutes()` | Every 5 minutes |
| `->everyTenMinutes()` | Every 10 minutes |
| `->everyThirtyMinutes()` | Every 30 minutes |
| `->hourly()` | Every hour at :00 |
| `->hourlyAt(15)` | Every hour at :15 |
| `->daily()` | Every day at midnight |
| `->dailyAt('08:00')` | Every day at 08:00 |
| `->weekly()` | Every Sunday at midnight |
| `->weeklyOn(1, '09:00')` | Every Monday at 09:00 |
| `->monthly()` | First day of month |
| `->cron('0 */6 * * *')` | Custom cron expression |

## Running the scheduler

**Option 1 — Single cron entry (simplest):**

```bash
# Add to server crontab
* * * * * cd /path/to/app && php hexaphp schedule:run >> /dev/null 2>&1
```

**Option 2 — Daemon (recommended with Supervisor):**

```bash
php hexaphp schedule:work
```

The daemon runs in a loop, sleeping until the next due task. Handles `SIGTERM` gracefully — finishes the current task before stopping.

## Viewing scheduled tasks

```bash
php hexaphp schedule:list
```

Output:

```
┌─────────────────────────┬──────────────────┬──────────────────┐
│ Command                 │ Schedule         │ Next Due         │
├─────────────────────────┼──────────────────┼──────────────────┤
│ flights:sync            │ 0 * * * *        │ 2024-01-15 14:00 │
│ reports:generate        │ 0 6 * * *        │ 2024-01-16 06:00 │
│ Closure                 │ */5 * * * *      │ 2024-01-15 13:35 │
└─────────────────────────┴──────────────────┴──────────────────┘
```
