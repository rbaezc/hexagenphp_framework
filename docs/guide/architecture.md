# Architecture

HexaGen uses **Vertical Slice Architecture** — every feature owns all its layers in one place.

## Directory structure

```
my-app/
├── public/
│   └── index.php           # Entry point (FrankenPHP worker loop + fallback)
├── src/
│   ├── Core/               # Framework internals (do not edit)
│   └── Slices/             # Your application features
│       └── Flights/
│           ├── Controller/
│           │   └── FlightsController.php
│           ├── Domain/
│           │   └── Flight.php       # HexaORM model
│           ├── Routes.php           # Route definitions
│           └── Services.php         # DI bindings
├── config/                 # App configuration files
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── lang/                   # Translation files
├── resources/
│   └── views/              # Twig templates
├── storage/                # Uploads, cache, logs
├── tests/
└── hexaphp                 # CLI binary
```

## Creating a slice

```bash
php hexaphp make:slice Flights
```

This generates the full slice structure with a controller, model, routes, and services file — all wired together.

## A slice in practice

**`src/Slices/Flights/Routes.php`**
```php
Route::get('/flights', [FlightsController::class, 'index'])->name('flights.index');
Route::post('/flights', [FlightsController::class, 'store']);
Route::get('/flights/{id}', [FlightsController::class, 'show']);
```

**`src/Slices/Flights/Domain/Flight.php`**
```php
namespace HexaGen\Slices\Flights\Domain;

use HexaGen\Core\Database\Model;
use HexaGen\Core\Database\Traits\HasTimestamps;

class Flight extends Model
{
    use HasTimestamps;

    protected static string $table    = 'flights';
    protected static array  $fillable = ['origin', 'destination', 'price'];
}
```

**`src/Slices/Flights/Controller/FlightsController.php`**
```php
namespace HexaGen\Slices\Flights\Controller;

use HexaGen\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FlightsController extends AbstractController
{
    public function index(Request $request): Response
    {
        $flights = Flight::query()->orderBy('price')->paginate(20);
        return $this->json($flights);
    }
}
```

## Why Vertical Slices?

| Traditional layered | Vertical slices |
|---|---|
| `Controllers/FlightsController.php` | `Slices/Flights/Controller/FlightsController.php` |
| `Models/Flight.php` | `Slices/Flights/Domain/Flight.php` |
| `Services/FlightService.php` | `Slices/Flights/Services.php` |
| Changes touch 4+ directories | Changes touch 1 directory |

When you delete a feature, you delete one folder. When you add a feature, you add one folder.
