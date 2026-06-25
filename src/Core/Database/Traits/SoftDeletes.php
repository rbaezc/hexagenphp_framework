<?php
namespace HexaGen\Core\Database\Traits;

/**
 * Adds soft-delete support: sets deleted_at instead of hard-deleting rows.
 * Queries automatically exclude soft-deleted records.
 *
 * Add a `deleted_at TEXT` column to your migration to use this trait.
 */
trait SoftDeletes
{
    public ?string $deleted_at = null;

    public static function softDeleteColumn(): string
    {
        return 'deleted_at';
    }

    /** Soft-delete this record. */
    public function softDelete(): bool
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /** Restore a soft-deleted record. */
    public function restore(): bool
    {
        $this->deleted_at = null;
        return $this->save();
    }

    /** Hard-delete: permanently removes the record from the database. */
    public function forceDelete(): bool
    {
        return static::query()->delete($this->id);
    }

    public function isTrashed(): bool
    {
        return $this->deleted_at !== null;
    }
}
