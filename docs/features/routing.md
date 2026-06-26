# Routing

## Basic routes

```php
// src/Slices/Flights/Routes.php
Route::get('/flights', [FlightsController::class, 'index']);
Route::post('/flights', [FlightsController::class, 'store']);
Route::get('/flights/{id}', [FlightsController::class, 'show']);
Route::put('/flights/{id}', [FlightsController::class, 'update']);
Route::delete('/flights/{id}', [FlightsController::class, 'destroy']);
Route::any('/webhook', [WebhookController::class, 'handle']);
```

## Named routes

```php
Route::get('/flights', [FlightsController::class, 'index'])->name('flights.index');
Route::get('/flights/{id}', [FlightsController::class, 'show'])->name('flights.show');
```

```php
// Generate URLs
route('flights.index');            // /flights
route('flights.show', ['id' => 5]); // /flights/5
```

## Resource routes

```php
// Generates all 7 CRUD routes
Route::resource('/flights', FlightsController::class);
```

| Method | URI | Action | Name |
|---|---|---|---|
| GET | `/flights` | index | `flights.index` |
| GET | `/flights/create` | create | `flights.create` |
| POST | `/flights` | store | `flights.store` |
| GET | `/flights/{id}` | show | `flights.show` |
| GET | `/flights/{id}/edit` | edit | `flights.edit` |
| PUT/PATCH | `/flights/{id}` | update | `flights.update` |
| DELETE | `/flights/{id}` | destroy | `flights.destroy` |

## Route groups

```php
Route::group(['prefix' => '/api/v1', 'middleware' => ['auth:jwt']], function () {
    Route::get('/flights', [FlightsController::class, 'index']);
    Route::resource('/bookings', BookingsController::class);
});

Route::group(['prefix' => '/admin', 'middleware' => ['auth', 'authorize:admin']], function () {
    Route::get('/dashboard', [AdminController::class, 'index']);
});
```

## Middleware on routes

```php
Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware(['auth', 'verified']);
```

## Route model binding

```php
// Route: GET /flights/{flight}
public function show(Request $request, Flight $flight): Response
{
    // $flight is automatically resolved from the DB
    return $this->json($flight->toArray());
}
```

## Accessing route parameters

```php
public function show(Request $request): Response
{
    $id = $request->attributes->get('id');
    $flight = Flight::findOrFail($id);
    return $this->json($flight->toArray());
}
```
