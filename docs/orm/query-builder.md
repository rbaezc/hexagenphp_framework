# Query Builder

Every model exposes a fluent QueryBuilder via `Model::query()`.

## Selecting

```php
Flight::query()->select(['id', 'origin', 'destination', 'price'])->get();
Flight::query()->selectRaw('COUNT(*) as total, origin')->groupBy('origin')->get();
Flight::query()->addSelect('airline_id')->get();
```

## Where clauses

```php
->where('price', '<', 500)
->where('origin', 'MEX')              // defaults to =
->orWhere('class', 'business')
->whereIn('airline_id', [1, 2, 3])
->whereNotIn('status', ['cancelled'])
->whereBetween('price', [100, 500])
->whereRaw('YEAR(created_at) = ?', [2024])
->whereNull('deleted_at')
->whereNotNull('published_at')
```

## Joins

```php
Flight::query()
    ->join('airlines', 'flights.airline_id', '=', 'airlines.id')
    ->leftJoin('airports', 'flights.origin', '=', 'airports.code')
    ->select(['flights.*', 'airlines.name as airline_name'])
    ->get();
```

## Ordering & limiting

```php
->orderBy('price', 'ASC')
->orderByRaw('price * 1.16 ASC')
->latest()        // orderBy created_at DESC
->oldest()        // orderBy created_at ASC
->take(10)        // LIMIT
->skip(20)        // OFFSET
```

## Aggregates

```php
Flight::query()->count();
Flight::query()->where('class', 'economy')->avg('price');
Flight::query()->sum('revenue');
Flight::query()->min('price');
Flight::query()->max('price');
Flight::query()->exists();
Flight::query()->doesntExist();
```

## Pagination

```php
$paginator = Flight::query()->where('active', true)->paginate(perPage: 20, page: 1);

$paginator->items();        // current page records
$paginator->total();        // total records
$paginator->perPage();
$paginator->currentPage();
$paginator->lastPage();
$paginator->hasMorePages();
```

## Pluck

```php
Flight::query()->pluck('destination');           // ['MAD', 'JFK', 'LHR']
Flight::query()->pluck('price', 'destination');  // ['MAD' => 350, 'JFK' => 420]
```

## Lazy loading (cursor)

For large datasets — processes one record at a time without loading all into memory:

```php
foreach (Flight::query()->where('active', true)->cursor() as $flight) {
    // processes one Flight at a time
}
```

## Raw expressions

```php
use HexaGen\Core\Database\RawExpression;

Flight::query()->select([new RawExpression('price * 1.16 as price_with_tax')])->get();
```

## Insert / Update / Delete

```php
// Insert and get generated ID
$id = Flight::query()->insertGetId(['origin' => 'MEX', 'destination' => 'MAD', 'price' => 350]);

// Bulk update
Flight::query()->where('status', 'pending')->update(['status' => 'active']);

// Bulk delete
Flight::query()->where('created_at', '<', '2023-01-01')->delete();

// Truncate
Flight::query()->truncate();
```
