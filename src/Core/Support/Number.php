<?php
namespace HexaGen\Core\Support;

class Number
{
    public static function format(int|float $number, int $precision = 0, string $decimal = '.', string $thousands = ','): string
    {
        return number_format($number, $precision, $decimal, $thousands);
    }

    public static function fileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow   = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow   = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    public static function percentage(float $value, float $total, int $precision = 2): string
    {
        if ($total == 0) {
            return '0%';
        }
        return round(($value / $total) * 100, $precision) . '%';
    }

    public static function currency(int|float $amount, string $currency = 'USD', string $locale = 'en_US'): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($amount, $currency);
        }
        return $currency . ' ' . static::format($amount, 2);
    }

    public static function abbreviate(int|float $number, int $precision = 1): string
    {
        $absNumber = abs($number);
        if ($absNumber >= 1_000_000_000) {
            return round($number / 1_000_000_000, $precision) . 'B';
        }
        if ($absNumber >= 1_000_000) {
            return round($number / 1_000_000, $precision) . 'M';
        }
        if ($absNumber >= 1_000) {
            return round($number / 1_000, $precision) . 'K';
        }
        return (string) $number;
    }

    public static function ordinal(int $number): string
    {
        $suffix = match (true) {
            ($number % 100 >= 11 && $number % 100 <= 13) => 'th',
            ($number % 10 === 1) => 'st',
            ($number % 10 === 2) => 'nd',
            ($number % 10 === 3) => 'rd',
            default              => 'th',
        };
        return $number . $suffix;
    }

    public static function clamp(int|float $number, int|float $min, int|float $max): int|float
    {
        return max($min, min($max, $number));
    }
}
