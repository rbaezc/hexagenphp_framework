# Authorization

## Defining abilities with Gate

```php
use HexaGen\Core\Auth\Gate;

// In a ServiceProvider or bootstrap file
Gate::define('edit-flight', fn(User $user, Flight $flight) => $user->id === $flight->user_id);

Gate::define('admin', fn(User $user) => $user->role === 'admin');
```

## Checking abilities

```php
// Boolean check
if (Gate::allows('edit-flight', $flight)) {
    // ...
}

if (Gate::denies('admin')) {
    abort(403);
}

// From anywhere using the helper
if (can('edit-flight', $flight)) {
    // ...
}
```

## In controllers

```php
use HexaGen\Core\Controller\AbstractController;

class FlightsController extends AbstractController
{
    public function update(Request $request): Response
    {
        $flight = Flight::findOrFail($request->attributes->get('id'));

        // Throws AuthorizationException (403) if denied
        $this->authorize('edit-flight', $flight);

        $flight->update($request->request->all());
        return $this->json($flight->toArray());
    }
}
```

## Middleware

```php
// Protect a route with a Gate ability
Route::delete('/flights/{id}', [FlightsController::class, 'destroy'])
    ->middleware('authorize:admin');
```

## In Twig templates

```twig
{% if can('edit-flight', flight) %}
    <a href="/flights/{{ flight.id }}/edit">Edit</a>
{% endif %}

{% if can('admin') %}
    <a href="/admin">Admin Panel</a>
{% endif %}
```

## Policies (grouping abilities by model)

```php
Gate::policy(Flight::class, FlightPolicy::class);

class FlightPolicy
{
    public function edit(User $user, Flight $flight): bool
    {
        return $user->id === $flight->user_id;
    }

    public function delete(User $user, Flight $flight): bool
    {
        return $user->id === $flight->user_id || $user->role === 'admin';
    }
}

// Usage
Gate::allows('edit', $flight);    // resolves FlightPolicy::edit()
```
