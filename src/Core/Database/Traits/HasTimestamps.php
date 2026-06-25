<?php
namespace HexaGen\Core\Database\Traits;

/**
 * Automatically manages created_at and updated_at columns.
 * Include this trait in any Model subclass.
 */
trait HasTimestamps
{
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected bool $timestamps = true;

    protected function touchTimestamps(bool $creating = false): void
    {
        if (!$this->timestamps) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        if ($creating && $this->created_at === null) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
    }
}
