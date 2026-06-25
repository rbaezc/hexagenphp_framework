<?php
namespace HexaGen\Core\Support;

class Date
{
    private \DateTimeImmutable $dt;

    private function __construct(\DateTimeImmutable $dt)
    {
        $this->dt = $dt;
    }

    public static function now(string|\DateTimeZone|null $timezone = null): static
    {
        $tz = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone ?? date_default_timezone_get());
        return new static(new \DateTimeImmutable('now', $tz));
    }

    public static function parse(string $time, string|\DateTimeZone|null $timezone = null): static
    {
        $tz = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone ?? date_default_timezone_get());
        return new static(new \DateTimeImmutable($time, $tz));
    }

    public static function create(int $year, int $month = 1, int $day = 1, int $hour = 0, int $minute = 0, int $second = 0): static
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-n-j G:i:s', "$year-$month-$day $hour:$minute:$second");
        return new static($dt);
    }

    public static function fromTimestamp(int $timestamp): static
    {
        return new static((new \DateTimeImmutable())->setTimestamp($timestamp));
    }

    // ── Mutations ──────────────────────────────────────────────────────────────

    public function addDays(int $days): static    { return new static($this->dt->modify("+{$days} days")); }
    public function subDays(int $days): static    { return new static($this->dt->modify("-{$days} days")); }
    public function addWeeks(int $weeks): static  { return new static($this->dt->modify("+{$weeks} weeks")); }
    public function subWeeks(int $weeks): static  { return new static($this->dt->modify("-{$weeks} weeks")); }
    public function addMonths(int $months): static{ return new static($this->dt->modify("+{$months} months")); }
    public function subMonths(int $months): static{ return new static($this->dt->modify("-{$months} months")); }
    public function addYears(int $years): static  { return new static($this->dt->modify("+{$years} years")); }
    public function subYears(int $years): static  { return new static($this->dt->modify("-{$years} years")); }
    public function addHours(int $hours): static  { return new static($this->dt->modify("+{$hours} hours")); }
    public function subHours(int $hours): static  { return new static($this->dt->modify("-{$hours} hours")); }
    public function addMinutes(int $min): static  { return new static($this->dt->modify("+{$min} minutes")); }
    public function subMinutes(int $min): static  { return new static($this->dt->modify("-{$min} minutes")); }

    public function startOfDay(): static
    {
        return new static($this->dt->setTime(0, 0, 0));
    }

    public function endOfDay(): static
    {
        return new static($this->dt->setTime(23, 59, 59));
    }

    public function startOfMonth(): static
    {
        return new static($this->dt->modify('first day of this month')->setTime(0, 0, 0));
    }

    public function endOfMonth(): static
    {
        return new static($this->dt->modify('last day of this month')->setTime(23, 59, 59));
    }

    public function startOfYear(): static
    {
        return new static($this->dt->modify('first day of january this year')->setTime(0, 0, 0));
    }

    public function endOfYear(): static
    {
        return new static($this->dt->modify('last day of december this year')->setTime(23, 59, 59));
    }

    // ── Comparison ─────────────────────────────────────────────────────────────

    public function isBefore(self $other): bool   { return $this->dt < $other->dt; }
    public function isAfter(self $other): bool     { return $this->dt > $other->dt; }
    public function isSameDay(self $other): bool   { return $this->format('Y-m-d') === $other->format('Y-m-d'); }
    public function isToday(): bool                { return $this->isSameDay(static::now()); }
    public function isYesterday(): bool            { return $this->isSameDay(static::now()->subDays(1)); }
    public function isTomorrow(): bool             { return $this->isSameDay(static::now()->addDays(1)); }
    public function isPast(): bool                 { return $this->isBefore(static::now()); }
    public function isFuture(): bool               { return $this->isAfter(static::now()); }

    // ── Formatting ─────────────────────────────────────────────────────────────

    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->dt->format($format);
    }

    public function toDateString(): string     { return $this->format('Y-m-d'); }
    public function toTimeString(): string     { return $this->format('H:i:s'); }
    public function toDateTimeString(): string { return $this->format('Y-m-d H:i:s'); }
    public function toIso8601(): string        { return $this->dt->format(\DateTimeInterface::ATOM); }
    public function timestamp(): int           { return $this->dt->getTimestamp(); }

    public function diffForHumans(?self $other = null, bool $absolute = false): string
    {
        $other   ??= static::now();
        $diff    = $this->dt->diff($other->dt);
        $invert  = !$absolute && $diff->invert;

        $parts = [
            [$diff->y, 'year',   'years'],
            [$diff->m, 'month',  'months'],
            [$diff->d, 'day',    'days'],
            [$diff->h, 'hour',   'hours'],
            [$diff->i, 'minute', 'minutes'],
            [$diff->s, 'second', 'seconds'],
        ];

        foreach ($parts as [$value, $singular, $plural]) {
            if ($value > 0) {
                $unit   = $value === 1 ? $singular : $plural;
                $result = "{$value} {$unit}";
                if ($absolute) return $result;
                return $invert ? "{$result} ago" : "in {$result}";
            }
        }

        return 'just now';
    }

    public function get(): \DateTimeImmutable { return $this->dt; }

    public function __toString(): string { return $this->toDateTimeString(); }

    // Getters
    public function year(): int   { return (int) $this->format('Y'); }
    public function month(): int  { return (int) $this->format('n'); }
    public function day(): int    { return (int) $this->format('j'); }
    public function hour(): int   { return (int) $this->format('G'); }
    public function minute(): int { return (int) $this->format('i'); }
    public function second(): int { return (int) $this->format('s'); }
    public function dayOfWeek(): int { return (int) $this->format('N'); }
    public function dayOfYear(): int { return (int) $this->format('z') + 1; }
    public function weekOfYear(): int{ return (int) $this->format('W'); }
}
