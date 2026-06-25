<?php
namespace HexaGen\Core\Console;

class ScheduledTask
{
    private string $expression  = '* * * * *';
    private string $description = '';

    public function __construct(private \Closure|callable $callback) {}

    public function setDescription(string $desc): void { $this->description = $desc; }
    public function getCallback(): callable             { return $this->callback; }
    public function getDescription(): string            { return $this->description; }
    public function getCron(): string                   { return $this->expression; }
    public function describe(string $desc): static      { $this->description = $desc; return $this; }

    // ── Frequency helpers ────────────────────────────────────────────────

    public function everyMinute(): static              { return $this->cron('* * * * *'); }
    public function everyFiveMinutes(): static         { return $this->cron('*/5 * * * *'); }
    public function everyTenMinutes(): static          { return $this->cron('*/10 * * * *'); }
    public function everyThirtyMinutes(): static       { return $this->cron('*/30 * * * *'); }
    public function hourly(): static                   { return $this->cron('0 * * * *'); }
    public function hourlyAt(int $minute): static      { return $this->cron("$minute * * * *"); }
    public function daily(): static                    { return $this->cron('0 0 * * *'); }
    public function dailyAt(string $time): static
    {
        [$hour, $minute] = explode(':', $time);
        return $this->cron("$minute $hour * * *");
    }
    public function weekly(): static                   { return $this->cron('0 0 * * 0'); }
    public function weeklyOn(int $day, string $time = '0:0'): static
    {
        [$hour, $minute] = explode(':', $time);
        return $this->cron("$minute $hour * * $day");
    }
    public function monthly(): static                  { return $this->cron('0 0 1 * *'); }
    public function monthlyOn(int $day, string $time = '0:0'): static
    {
        [$hour, $minute] = explode(':', $time);
        return $this->cron("$minute $hour $day * *");
    }
    public function cron(string $expression): static
    {
        $this->expression = $expression;
        return $this;
    }

    public function isDue(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();
    {
        [$min, $hour, $day, $month, $weekday] = explode(' ', $this->expression);
        return $this->matchesCronField($min, (int)$now->format('i'))
            && $this->matchesCronField($hour, (int)$now->format('H'))
            && $this->matchesCronField($day, (int)$now->format('d'))
            && $this->matchesCronField($month, (int)$now->format('m'))
            && $this->matchesCronField($weekday, (int)$now->format('w'));
    }

    private function matchesCronField(string $field, int $value): bool
    {
        if ($field === '*') {
            return true;
        }
        if (str_contains($field, '/')) {
            [, $step] = explode('/', $field);
            return $value % (int)$step === 0;
        }
        if (str_contains($field, ',')) {
            return in_array($value, array_map('intval', explode(',', $field)), true);
        }
        return (int)$field === $value;
    }
}
