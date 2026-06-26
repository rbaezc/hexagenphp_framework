<?php
namespace HexaGen\Core\Log;

use HexaGen\Core\Config;

/**
 * PSR-3 compatible structured logger.
 * Supports file, stderr, and stack (multi-channel) drivers.
 */
class Logger
{
    private string $channel;
    private array $config;

    // PSR-3 log levels in severity order
    private const LEVELS = [
        'debug'     => 0,
        'info'      => 1,
        'notice'    => 2,
        'warning'   => 3,
        'error'     => 4,
        'critical'  => 5,
        'alert'     => 6,
        'emergency' => 7,
    ];

    public function __construct(string $channel = 'default')
    {
        $channelName   = $channel === 'default'
            ? Config::get('logging.default', 'stderr')
            : $channel;

        $this->channel = $channelName;
        $this->config  = Config::get("logging.channels.$channelName", [
            'driver' => 'stderr',
            'level'  => 'debug',
        ]);
    }

    public function emergency(string $message, array $context = []): void { $this->log('emergency', $message, $context); }
    public function alert(string $message, array $context = []): void     { $this->log('alert',     $message, $context); }
    public function critical(string $message, array $context = []): void  { $this->log('critical',  $message, $context); }
    public function error(string $message, array $context = []): void     { $this->log('error',     $message, $context); }
    public function warning(string $message, array $context = []): void   { $this->log('warning',   $message, $context); }
    public function notice(string $message, array $context = []): void    { $this->log('notice',    $message, $context); }
    public function info(string $message, array $context = []): void      { $this->log('info',      $message, $context); }
    public function debug(string $message, array $context = []): void     { $this->log('debug',     $message, $context); }

    public function log(string $level, string $message, array $context = []): void
    {
        $driver = $this->config['driver'] ?? 'stderr';

        if ($driver === 'stack') {
            foreach ($this->config['channels'] as $channelName) {
                (new self($channelName))->log($level, $message, $context);
            }
            return;
        }

        $minLevel = $this->config['level'] ?? 'debug';
        if ((self::LEVELS[$level] ?? 0) < (self::LEVELS[$minLevel] ?? 0)) {
            return;
        }

        $entry = $this->format($level, $message, $context);

        match ($driver) {
            'file'   => $this->writeToFile($entry),
            default  => $this->writeToStderr($entry),
        };
    }

    private function format(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $ctx       = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return "[$timestamp] $this->channel." . strtoupper($level) . ": $message$ctx" . PHP_EOL;
    }

    private function writeToFile(string $entry): void
    {
        $path = $this->config['path'] ?? (dirname(__DIR__, 3) . '/var/log/app.log');
        $dir  = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
    }

    private function writeToStderr(string $entry): void
    {
        $stream = defined('STDERR') ? \STDERR : @fopen('php://stderr', 'w');
        if ($stream) {
            fwrite($stream, $entry);
            if (!defined('STDERR')) {
                @fclose($stream);
            }
        }
    }
}
