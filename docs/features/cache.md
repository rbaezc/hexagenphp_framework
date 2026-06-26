# Cache

## Basic usage

```php
// Store
cache()->set('flights_mex', $flights, ttl: 3600);

// Retrieve
$flights = cache()->get('flights_mex');
$flights = cache()->get('flights_mex', default: []);

// Check
cache()->has('flights_mex');

// Delete
cache()->delete('flights_mex');

// Clear all
cache()->clear();

// Atomic increment
cache()->increment('page_views');
cache()->increment('page_views', by: 5);
```

## Remember

```php
// Fetch from cache or compute and store
$flights = cache()->remember('flights_mex', 600, function () {
    return Flight::query()->where('origin', 'MEX')->get();
});
```

## Drivers

```ini
CACHE_DRIVER=file    # default — stores in storage/cache/
CACHE_DRIVER=redis
CACHE_DRIVER=array   # in-memory, cleared on each request
```

## Named driver

```php
CacheManager::driver('redis')->set('key', 'value', 60);
CacheManager::driver('file')->get('key');
```
