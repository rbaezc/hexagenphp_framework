<?php
namespace HexaGen\Core\Console;

/**
 * Task Scheduler — define tareas cron dentro de la app.
 *
 * En el crontab solo necesitas una entrada:
 *   * * * * * php /ruta/al/proyecto/hexaphp schedule:run >> /dev/null 2>&1
 *
 * En un ServiceProvider o en hexaphp (antes de $application->run()):
 *   Scheduler::call(fn() => Cache::clear())->daily();
 *   Scheduler::command('queue:work')->everyMinute();
 *   Scheduler::job(new GenerateReportJob())->weeklyOn(1, '08:00');
 */
class Scheduler
{
    /** @var ScheduledTask[] */
    private static array $tasks = [];

    public static function call(callable $callback): ScheduledTask
    {
        $task = new ScheduledTask($callback);
        self::$tasks[] = $task;
        return $task;
    }

    public static function command(string $command): ScheduledTask
    {
        $task = new ScheduledTask(function() use ($command) {
            passthru("php " . dirname(__DIR__, 3) . "/hexaphp $command");
        });
        $task->setDescription($command);
        self::$tasks[] = $task;
        return $task;
    }

    public static function job(\HexaGen\Core\Queue\Job $job): ScheduledTask
    {
        $task = new ScheduledTask(fn() => \HexaGen\Core\Queue\QueueManager::push($job));
        $task->setDescription(get_class($job));
        self::$tasks[] = $task;
        return $task;
    }

    public static function getTasks(): array { return self::$tasks; }

    public static function runDue(): void
    {
        $now = new \DateTimeImmutable();
        foreach (self::$tasks as $task) {
            if ($task->isDue($now)) {
                ($task->getCallback())();
            }
        }
    }
}
