# Live Slices

Server-side reactive components — like Phoenix LiveView or Laravel Livewire, but built into HexaGen. Powered by **HTMX** and **AES-256-GCM encrypted state**.

No JavaScript compilation. No Node build step. Just PHP + Twig + HTMX.

## Creating a component

```php
namespace HexaGen\Slices\Flights\Components;

use HexaGen\Core\Live\LiveComponent;

class FlightSearch extends LiveComponent
{
    // Public properties = reactive state
    public string $origin      = '';
    public string $destination = '';
    public array  $results     = [];

    // Methods called by HTMX become server actions
    public function search(): void
    {
        $this->results = Flight::query()
            ->where('origin', $this->origin)
            ->where('destination', $this->destination)
            ->get();
    }

    public function render(): string
    {
        return $this->renderView('@Flights/components/search.twig');
    }
}
```

## Twig template

```twig
{# resources/views/Flights/components/search.twig #}
<div id="flight-search">
    <input type="text" name="origin"
           value="{{ origin }}"
           hx-trigger="input changed delay:300ms"
           hx-post="/live/FlightSearch/search"
           hx-target="#flight-search"
           hx-vals='js:{"state": document.getElementById("flight-search").dataset.liveState,
                        "origin": this.value,
                        "destination": document.querySelector("[name=destination]").value}'>

    <input type="text" name="destination" value="{{ destination }}">

    <ul>
    {% for flight in results %}
        <li>{{ flight.origin }} → {{ flight.destination }} — ${{ flight.price }}</li>
    {% endfor %}
    </ul>
</div>
```

## Rendering from a controller

```php
public function index(Request $request): Response
{
    $search = new FlightSearch();

    return view('flights/index.twig', [
        'search' => $search->render(),
    ]);
}
```

```twig
{# resources/views/flights/index.twig #}
<script src="https://unpkg.com/htmx.org@1.9"></script>

{{ search | raw }}
```

## How it works

1. Component state is **serialized and encrypted** (AES-256-GCM) into `data-live-state`
2. HTMX sends the encrypted state + new values on each interaction
3. The server decrypts state, applies changes, calls the action method
4. Returns the re-rendered HTML fragment — HTMX swaps it in

State is tamper-proof — users cannot forge or modify component state.

## Live endpoint

The framework registers `/live/{Component}/{method}` automatically. No routing needed.
