# Templates (Twig)

HexaGen uses **Twig 3** as its template engine. Templates live in `resources/views/`.

## Rendering from a controller

```php
return view('flights/index.twig', ['flights' => $flights]);

// Or via the controller helper
return $this->render('flights/index.twig', ['flights' => $flights]);
```

## Global Twig functions

| Function | Returns |
|---|---|
| `route('name', params)` | Named route URL |
| `asset('path/file.css')` | URL with cache-busting mtime |
| `vite('resources/js/app.js')` | Vite asset URL (HMR in dev) |
| `__('key', params)` | Translated string |
| `trans_choice('key', n)` | Pluralized translation |
| `auth()` | Current auth guard instance |
| `can('ability', model)` | Gate check — returns bool |
| `app('binding')` | Resolve from DI container |
| `config('key')` | Config value |
| `now()` | Current DateTime |
| `csrf_token()` | CSRF token string |

## Example template

```twig
{# resources/views/flights/index.twig #}
{% extends 'layouts/app.twig' %}

{% block title %}Flights{% endblock %}

{% block content %}
<h1>Available Flights</h1>

{% for flight in flights %}
    <div class="flight-card">
        <h3>{{ flight.origin }} → {{ flight.destination }}</h3>
        <p>{{ flight.price | number_format(2) }}</p>

        {% if can('edit-flight', flight) %}
            <a href="{{ route('flights.edit', {id: flight.id}) }}">Edit</a>
        {% endif %}
    </div>
{% else %}
    <p>No flights available.</p>
{% endfor %}

<form method="POST" action="{{ route('flights.store') }}">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    ...
</form>
{% endblock %}
```

## View composers

Automatically inject data into matching views:

```php
use HexaGen\Core\View\View;

// In a ServiceProvider
View::composer('layouts/*', function ($data) {
    $data->set('currentUser', auth()->user());
    $data->set('unreadCount', auth()->user()?->unreadNotifications()->count() ?? 0);
});
```

## Shared data

```php
// Available in ALL views
View::share('appName', config('app.name'));
View::share('year', date('Y'));
```

## Assets

```twig
{# Traditional asset with cache-busting #}
<link rel="stylesheet" href="{{ asset('css/app.css') }}">

{# Vite (dev HMR + production manifest) #}
<script type="module" src="{{ vite('resources/js/app.js') }}"></script>
```
