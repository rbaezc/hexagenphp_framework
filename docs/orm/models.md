# Models

HexaGen's ORM (**HexaORM**) implements Active Record on top of native PDO prepared statements — no Doctrine, no Eloquent overhead.

## Defining a model

```php
namespace HexaGen\Slices\Flights\Domain;

use HexaGen\Core\Database\Model;
use HexaGen\Core\Database\Traits\HasTimestamps;
use HexaGen\Core\Database\Traits\SoftDeletes;
use HexaGen\Core\Database\Traits\HasFactory;

class Flight extends Model
{
    use HasTimestamps;
    use SoftDeletes;
    use HasFactory;

    protected static string $table      = 'flights';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['origin', 'destination', 'price', 'class'];
    protected static array $guarded  = ['id'];

    protected static array $casts = [
        'price'  => 'float',
        'active' => 'bool',
        'meta'   => 'array',    // auto JSON encode/decode
    ];
}
```

## Basic queries

```php
// All records
$flights = Flight::all();

// Find by primary key
$flight = Flight::find(1);        // returns null if not found
$flight = Flight::findOrFail(1);  // throws ModelNotFoundException

// First match
$flight = Flight::query()->where('origin', 'MEX')->first();
$flight = Flight::query()->where('origin', 'MEX')->firstOrFail();
```

## Creating records

```php
// Using create() — respects $fillable
$flight = Flight::create([
    'origin'      => 'MEX',
    'destination' => 'MAD',
    'price'       => 350.00,
]);

// Manual instantiation
$flight = new Flight();
$flight->origin      = 'MEX';
$flight->destination = 'MAD';
$flight->save();
```

## Updating records

```php
$flight->price = 299.00;
$flight->save();

// Or with update()
$flight->update(['price' => 299.00]);

// Upsert
Flight::updateOrCreate(
    ['origin' => 'MEX', 'destination' => 'MAD'],  // match criteria
    ['price' => 299.00]                             // values to set
);
```

## Deleting records

```php
$flight->delete();

// With SoftDeletes — sets deleted_at, does not remove the row
$flight->delete();
$flight->restore();    // undo soft delete
$flight->forceDelete(); // permanently removes
```

## Mass assignment

```php
$flight = new Flight();
$flight->fill(['origin' => 'MEX', 'price' => 350]); // respects $fillable
```

## Refreshing a model

```php
$flight->refresh(); // reloads from DB, updates current instance
$fresh = $flight->fresh(); // returns a new instance from DB
```

## Serialization

```php
$flight->toArray();  // ['id' => 1, 'origin' => 'MEX', ...]
$flight->toJson();   // '{"id":1,"origin":"MEX",...}'
```

## Available traits

| Trait | Adds |
|---|---|
| `HasTimestamps` | `created_at`, `updated_at` auto-filled on save |
| `SoftDeletes` | `deleted_at`, `delete()`, `restore()`, `forceDelete()`, `withTrashed()` |
| `HasCasts` | Automatic type casting via `$casts` |
| `HasScopes` | Local scopes (`scopeActive()` → `Flight::query()->active()`) |
| `HasObservers` | `created`, `updated`, `deleted` lifecycle hooks |
| `HasFactory` | `Flight::factory()->create()` |
