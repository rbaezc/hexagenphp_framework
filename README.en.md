# HexaGen PHP Framework ⚡

> 📖 **Readme versions**: [English Version](README.en.md) | [Versión en Español](README.md)

**HexaGen PHP Framework** is an ultra-lightweight, high-performance PHP 8.3+ micro-framework designed specifically to excel under the **FrankenPHP** engine, utilizing a **Vertical Slices** architecture and strictly applying **SOLID** principles.

Avoid coupling and unnecessary overhead from Doctrine or Eloquent by using **HexaORM**, a direct and lightweight active record ORM running on native PDO.

---

## 🏗️ Architecture and Directory Structure

The folder structure of HexaGen promotes self-containing all business logic for a feature inside a single Vertical Slice, rather than splitting it across global directories:

```
hexagenphp_framework/
├── bin/
│   └── hexaphp            # Optional CLI wrapper executable
├── public/
│   └── index.php          # Worker Loop for FrankenPHP & Fallback handler
├── src/
│   ├── Core/              # Core Framework (Dependency Injection, Kernel, ORM)
│   │   ├── Console/       # CLI commands for hexaphp
│   │   ├── Controller/    # Abstract base Controller
│   │   ├── Database/      # Data Abstraction Layer (HexaORM)
│   │   └── Kernel.php     # Main orchestrator (Symfony Container & Routing)
│   └── Slices/            # This is where your Vertical Slices live (Vuelos, Hoteles, etc.)
│       └── [Name]/
│           ├── Controller/ # Feature-specific controllers
│           ├── Domain/     # Domain Models / Entities (HexaORM)
│           ├── Routes.php  # Local slice route mappings
│           └── Services.php# Local dependency registration in the DIC
├── composer.json          # Dependency management (Symfony Core Components)
├── hexaphp                # Main CLI executable
└── database.sqlite        # Local SQLite database (created dynamically)
```

---

## 🛠️ Requirements & Installation

1. **PHP 8.3 or higher** (the framework leverages modern typing and attribute features).
2. **Composer** (to install essential Symfony components).

To boot up the local environment for the first time, run:
```bash
composer install
```

*Note: During development on Windows/Linux, if you don't have the **FrankenPHP** binary, the `server:start` command will offer to download and install it 100% automatically.*

---

## 💻 Console Tools (`hexaphp`)

The `hexaphp` CLI exposes commands ready to boost developer experience (DX):

### 1. Generate a Vertical Slice
Creates a complete, ready-to-use structure for a vertical feature with its own controller, domain model, routes, and services:
```bash
php hexaphp make:slice [Name]
```
*Example:* `php hexaphp make:slice Vuelos`
> **DX Automation:** If you are using SQLite for local development, this command will automatically create the corresponding database table with default fields (`id`, `name`, `created_at`).

### 2. Start the FrankenPHP Server in Worker Mode
Starts the high-performance FrankenPHP server in Worker mode with hot-reloading (watch) enabled:
```bash
php hexaphp server:start
```
*   **Default Host:** `http://127.0.0.1:8080`
*   **Hot-Reload:** Monitors changes in `.php` and `.env` files and restarts the server automatically without losing in-memory state on intermediate requests.

---

## 🏎️ The FrankenPHP Magic (Worker Loop)

The entry point `public/index.php` keeps the framework loaded in memory, eliminating PHP's boot-up cost on every request:

```php
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';

// 1. Initialize the core Framework ONCE
$kernel = new HexaGen\Core\Kernel();
$kernel->boot();

if (function_exists('frankenphp_handle_request')) {
    // 2. This is the magic loop that keeps your framework alive in memory
    $maxRequests = 1000;
    for ($nbRequests = 0; frankenphp_handle_request() && $nbRequests < $maxRequests; ++$nbRequests) {
        
        $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        
        // Quick post-request cleanup
        $kernel->terminate($request, $response);
        
        // Prevent memory leaks
        gc_collect_cycles();
    }
} else {
    // Fallback for traditional development servers (CGI, Apache/Nginx, php -S)
    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
}
```

---

## 💾 HexaORM (Ultra-fast PDO Abstraction)

Instead of bearing the weight and configuration of Doctrine or Eloquent, **HexaORM** implements the **Active Record** pattern lightweight on top of PDO prepared statements.

### Queries:
```php
use HexaGen\Slices\Vuelos\Domain\Vuelo;

// Get all flights (returns an array of Vuelo objects)
$vuelos = Vuelo::all();

// Find by Primary Key (returns Vuelo object or null)
$vuelo = Vuelo::find(1);

// Custom query using the internal QueryBuilder
$cheapFlights = Vuelo::where('precio', 200, '<')->orderBy('precio', 'ASC')->get();
```

### Insert / Update:
```php
$vuelo = new Vuelo();
$vuelo->name = "Flight to Madrid";
$vuelo->created_at = date('Y-m-d H:i:s');
$vuelo->save(); // Inserts into DB and assigns the auto-generated ID to the object

// Edit
$vuelo->name = "Flight to Barcelona";
$vuelo->save(); // Updates in the DB
```

### Delete:
```php
$vuelo->delete();
```

### Relations (Eager Loading without N+1) 🔗
To strictly and transparently avoid the N+1 queries issue, HexaORM implements relationships preloaded in exactly **2 queries** using `WHERE IN` behind the scenes, without magical and inefficient lazy loading queries.

#### Define relations in the Model:
```php
use HexaGen\Core\Database\Model;
use HexaGen\Core\Database\Relations\BelongsTo;
use HexaGen\Core\Database\Relations\HasMany;

class Aerolinea extends Model
{
    public function vuelos(): HasMany
    {
        return $this->hasMany(Vuelo::class, 'aerolinea_id');
    }
}

class Vuelo extends Model
{
    public function aerolinea(): BelongsTo
    {
        return $this->belongsTo(Aerolinea::class, 'aerolinea_id');
    }
}
```

#### Query relations (Eager Loading):
```php
// Fetch all flights and their associated airlines in exactly 2 total SQL queries:
$vuelos = Vuelo::query()->with('aerolinea')->get();

foreach ($vuelos as $vuelo) {
    echo $vuelo->name . " belongs to " . $vuelo->aerolinea->name;
}
```

### Live Slices (Reactive Monolith & Real-Time) ⚡
HexaGen includes support for **Live Slices**, a server-side reactive component engine inspired by Phoenix LiveView and Laravel Livewire. 

It allows you to update parts of the web interface in real time without page reloads or writing compilation steps in JavaScript, using **HTMX** and an **encrypted and signed state** for security.

#### 1. Define the reactive component:
Create a component in `src/Slices/[Slice]/Components/[Component].php`:
```php
namespace HexaGen\Slices\Vuelos\Components;

use HexaGen\Core\Live\LiveComponent;

class ContadorVuelos extends LiveComponent
{
    // Public properties represent the state of the component
    public int $clicks = 0;

    // Methods that can be triggered from the interface by HTMX events
    public function incrementar(): void
    {
        $this->clicks++;
    }

    public function render(): string
    {
        return $this->renderView('@Vuelos/contador.twig');
    }
}
```

#### 2. Write the component's Twig template (`contador.twig`):
Use HTMX attributes to connect the button to the server method and safely transmit the encrypted state:
```twig
<div class="card">
    <h3>Clicks received: {{ clicks }}</h3>
    <button hx-post="/live/ContadorVuelos/incrementar"
            hx-vals='js:{"state": event.target.closest("[data-live-state]").getAttribute("data-live-state")}'>
        Increment +1
    </button>
</div>
```

#### 3. Render the component from your controller:
```php
public function index(Request $request): Response
{
    $contador = new ContadorVuelos();
    
    // view() is the global helper helper to render Twig views
    return view('@Vuelos/index.twig', [
        'liveContador' => $contador->render()
    ]);
}
```
And in your main HTML layout (`index.twig`), make sure to load **HTMX** and render the component raw:
```twig
<script src="https://unpkg.com/htmx.org"></script>
...
<div>{{ liveContador|raw }}</div>
```
