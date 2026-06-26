# Relations

HexaORM loads relations in **exactly 2 queries** using `WHERE IN` — never N+1.

## Defining relations

```php
class Airline extends Model
{
    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class, 'airline_id');
    }

    public function hub(): HasOne
    {
        return $this->hasOne(Airport::class, 'airline_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(Passenger::class, 'flight_passengers', 'airline_id', 'passenger_id');
    }
}
```

## Available relations

| Method | Type | Description |
|---|---|---|
| `hasOne(Model, fk)` | One-to-one | Airline → Hub |
| `hasMany(Model, fk)` | One-to-many | Airline → Flights |
| `belongsTo(Model, fk)` | Inverse | Flight → Airline |
| `belongsToMany(Model, pivot, fk, rfk)` | Many-to-many | Flight ↔ Passengers |
| `hasOneThrough(Model, Through, fk1, fk2)` | Through | Country → Hub via Airline |
| `hasManyThrough(Model, Through, fk1, fk2)` | Through | Country → Flights via Airline |
| `morphOne(Model, name)` | Polymorphic one | Post → Image |
| `morphMany(Model, name)` | Polymorphic many | Post → Comments |
| `morphTo()` | Polymorphic inverse | Comment → owner |

## Eager loading

```php
// 2 queries total (not N+1)
$airlines = Airline::query()->with('flights', 'country')->get();

foreach ($airlines as $airline) {
    echo $airline->country->name;
    foreach ($airline->flights as $flight) {
        echo $flight->origin . ' → ' . $flight->destination;
    }
}
```

## Eager loading with constraints

```php
$airlines = Airline::query()->with([
    'flights' => fn($q) => $q->where('class', 'business')->orderBy('price'),
    'country' => fn($q) => $q->select(['id', 'name', 'code']),
])->get();
```

## Accessing relations

```php
$flight = Flight::findOrFail(1);

// Access lazy (runs a query each time)
$airline = $flight->airline;

// Pre-load for a single model
$flight->load('airline', 'passengers');
```

## Polymorphic relations

```php
// A Post and a Flight can both have Comments
class Comment extends Model
{
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// Usage
$comments = Post::find(1)->comments()->with('author')->get();
```
