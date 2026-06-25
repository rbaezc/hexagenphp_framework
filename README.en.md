# HexaGen PHP Framework ⚡

> 📖 **Readme versions**: [English Version](README.en.md) | [Versión en Español](README.md)

**HexaGen PHP Framework** is a high-performance PHP 8.3+ framework designed specifically to shine under the **FrankenPHP** engine, using a **Vertical Slices** architecture and strictly applying **SOLID** principles.

Avoid the coupling and unnecessary overhead of Doctrine or Eloquent by using **HexaORM**, a direct Active Record ORM built on native PDO.

---

## Installation

```bash
composer create-project rbaezc/hexagenphp-framework my-app
cd my-app
cp .env.example .env
php hexaphp migrate
php hexaphp server:start
```

---

## Architecture

```
my-app/
├── public/index.php          # FrankenPHP Worker Loop + traditional fallback
├── src/
│   ├── Core/                 # Framework core
│   └── Slices/               # Your vertical features
│       └── [Name]/
│           ├── Controller/
│           ├── Domain/
│           ├── Routes.php
│           └── Services.php
├── config/                   # Module configuration files
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── lang/                     # Language files
├── resources/views/          # Twig templates
├── storage/                  # Uploads, cache, logs
└── hexaphp                   # Main CLI
```

---

## Features

### Routing
- GET, POST, PUT, PATCH, DELETE, ANY
- Route groups with prefix, middleware, and namespace
- Named routes + URL generation with `route()`
- Resource routes (automatic CRUD)
- Route model binding

```php
Route::get('/flights', [FlightsController::class, 'index'])->name('flights.index');

Route::group(['prefix' => '/api', 'middleware' => ['auth:jwt']], function () {
    Route::resource('/flights', FlightsController::class);
});
```

---

### HexaORM

```php
// Queries
$flights = Flight::all();
$flight  = Flight::find(1);
$flight  = Flight::findOrFail(1);

// QueryBuilder
Flight::query()
    ->select(['id', 'origin', 'destination', 'price'])
    ->where('price', '<', 500)
    ->orWhere('class', 'business')
    ->whereIn('airline_id', [1, 2, 3])
    ->orderBy('price', 'ASC')
    ->paginate(20);

// Create / Update
$flight = Flight::create(['origin' => 'MEX', 'destination' => 'MAD', 'price' => 350]);
$flight->update(['price' => 299]);
Flight::updateOrCreate(['origin' => 'MEX', 'destination' => 'MAD'], ['price' => 299]);

// Delete
$flight->delete();

// Aggregates
$total   = Flight::query()->count();
$average = Flight::query()->where('class', 'economy')->avg('price');
$exists  = Flight::query()->where('destination', 'MAD')->exists();
```

#### Relations
```php
class Airline extends Model
{
    public function flights(): HasMany  { return $this->hasMany(Flight::class, 'airline_id'); }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function hub(): HasOne       { return $this->hasOne(Airport::class); }
}

// Eager loading in exactly 2 queries (no N+1)
$airlines = Airline::query()->with('flights', 'country')->get();

// Eager loading with constraints
$airlines = Airline::query()->with([
    'flights' => fn($q) => $q->where('class', 'business')->orderBy('price')
])->get();
```

**Available relations:** `hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, `hasOneThrough`, `hasManyThrough`, `morphOne`, `morphMany`, `morphTo`

#### Model Traits
```php
class Flight extends Model
{
    use HasTimestamps;   // automatic created_at / updated_at
    use SoftDeletes;     // deleted_at — no physical delete
    use HasCasts;        // automatic type casting
    use HasScopes;       // local and global scopes
    use HasObservers;    // created/updated/deleted hooks
    use HasFactory;      // Flight::factory()->create()

    protected static array $fillable = ['origin', 'destination', 'price', 'class'];
    protected static array $casts    = ['price' => 'float', 'active' => 'bool'];
}
```

---

### Migrations

```bash
php hexaphp migrate              # Run pending migrations
php hexaphp migrate:rollback     # Rollback last batch
php hexaphp migrate:rollback --step=3
php hexaphp migrate:fresh        # Drop all + re-run
php hexaphp migrate:status       # Show status (Ran / Pending)
```

```php
// database/migrations/2024_01_01_create_flights_table.php
return new class extends Migration {
    public function up(\PDO $pdo): void {
        $pdo->exec("CREATE TABLE flights (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            origin      VARCHAR(3) NOT NULL,
            destination VARCHAR(3) NOT NULL,
            price       DECIMAL(10,2),
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    public function down(\PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS flights");
    }
};
```

---

### Authentication & Authorization

```php
// Session guard
auth()->attempt(['email' => $email, 'password' => $password]);
auth()->user();
auth()->logout();

// JWT guard
auth('jwt')->attempt($credentials);
$token = auth('jwt')->token();

// Gate / Policies
Gate::define('edit-flight', fn($user, $flight) => $user->id === $flight->user_id);

if (Gate::allows('edit-flight', $flight)) { /* ... */ }
// or in controller:
$this->authorize('edit-flight', $flight);
```

Available middlewares: `auth`, `auth:jwt`, `guest`, `authorize:permission`

---

### Validation

```php
class CreateFlightRequest extends FormRequest
{
    public function rules(): array {
        return [
            'origin'      => 'required|min:3|max:3',
            'destination' => 'required|min:3|max:3|different:origin',
            'price'       => 'required|numeric|min:0',
            'class'       => 'required|in:economy,business,first',
            'date'        => 'required|date|after:today',
            'email'       => 'required|email|unique:flights,email',
        ];
    }

    public function messages(): array {
        return ['origin.required' => 'The origin field is required.'];
    }
}
```

**Available rules:** `required`, `nullable`, `sometimes`, `string`, `numeric`, `integer`, `boolean`, `array`, `email`, `url`, `min`, `max`, `between`, `size`, `in`, `not_in`, `same`, `different`, `confirmed`, `unique`, `exists`, `required_if`, `after`, `before`, `starts_with`, `ends_with`, `accepted`, `ip`, `ipv4`, `ipv6`, `uuid`, `json`, `digits`, `digits_between`

---

### Mail

```php
class WelcomeMail extends Mailable
{
    public function build(): static {
        return $this->subject('Welcome!')
                    ->view('emails.welcome')
                    ->with(['user' => $this->user]);
    }
}

Mail::to('user@example.com')->send(new WelcomeMail($user));
```

Drivers: `smtp`, `log`, `null`

---

### Queue System

```php
// Define a job
class ProcessBookingJob extends Job
{
    public function handle(): void {
        // job logic
    }
}

// Dispatch
dispatch(new ProcessBookingJob($booking));
dispatch(new ProcessBookingJob($booking))->onQueue('bookings');

// Worker
php hexaphp queue:work
php hexaphp queue:work --queue=bookings
php hexaphp queue:failed
```

Drivers: `sync`, `database`, `redis`

---

### Storage

```php
Storage::put('flights/photo.jpg', $content);
Storage::get('flights/photo.jpg');
Storage::delete('flights/photo.jpg');
Storage::url('flights/photo.jpg');
Storage::exists('flights/photo.jpg');
Storage::disk('s3')->put('backup.zip', $content);
```

Drivers: `local`, `s3`

---

### Cache

```php
cache()->set('popular_flights', $flights, ttl: 3600);
cache()->get('popular_flights');
cache()->remember('flights_mex', 600, fn() => Flight::query()->where('origin', 'MEX')->get());
cache()->delete('popular_flights');
```

Drivers: `file`, `redis`, `array`

---

### Events

```php
// Define
class FlightBooked extends Event
{
    public function __construct(public readonly Flight $flight) {}
}

// Listener
class SendConfirmation implements ListenerInterface
{
    public function handle(object $event): void {
        Mail::to($event->flight->email)->send(new ConfirmationMail($event->flight));
    }
}

// Fire
event(new FlightBooked($flight));
```

---

### Notifications

```php
class BookingConfirmed extends Notification
{
    public function via(): array    { return ['mail', 'database']; }
    public function toMail(): array { return ['subject' => 'Booking confirmed', ...]; }
}

$user->notify(new BookingConfirmed($booking));
// or: notify($user, new BookingConfirmed($booking));
```

Channels: `mail`, `database`, `slack`

---

### Broadcasting (SSE)

```php
class FlightUpdated extends Event implements ShouldBroadcast
{
    public function broadcastOn(): string  { return 'flights'; }
    public function broadcastAs(): string  { return 'updated'; }
}

// JavaScript client
const source = new EventSource('/broadcast/flights');
source.addEventListener('updated', e => console.log(JSON.parse(e.data)));
```

---

### Internationalization

```php
// lang/en/flights.php
return ['welcome' => 'Welcome, :name'];

__('flights.welcome', ['name' => 'Raúl']);   // "Welcome, Raúl"
trans_choice('flights.seats', $n);            // pipe-syntax pluralization
```

---

### Twig Templates

```twig
{# Available Twig functions #}
{{ route('flights.index') }}
{{ asset('css/app.css') }}
{{ vite('resources/js/app.js') }}
{{ __('flights.welcome', {name: 'Raúl'}) }}
{% if auth().check() %}Hello, {{ auth().user().name }}{% endif %}
{% if can('edit-flight', flight) %}...{% endif %}
```

```php
// View composers — automatic data in views
View::composer('layouts/*', function (ViewData $data) {
    $data->set('currencies', Currency::all());
});

// Globally shared data
View::share('app_name', config('app.name'));
```

---

### HTTP Client

```php
$response = Http::withToken($token)
    ->retry(3, 100)
    ->post('https://api.airline.com/bookings', ['flight_id' => 1]);

$response->json();
$response->status();
$response->successful();
```

---

### Scheduler

```php
// src/Core/Console/Scheduler.php
$schedule->command('flights:sync')->hourly();
$schedule->command('reports:generate')->dailyAt('06:00');
$schedule->call(fn() => Cache::clear())->everyFiveMinutes();
```

```bash
php hexaphp schedule:list          # View scheduled tasks
php hexaphp schedule:work          # Daemon with graceful shutdown (SIGTERM)
```

---

### Tinker (REPL)

```bash
php hexaphp tinker
>>> Flight::query()->where('destination', 'MAD')->count()
>>> dispatch(new ProcessBookingJob($flight))
```

---

### Testing

```php
class FlightsTest extends TestCase
{
    public function test_create_flight(): void
    {
        $flight = Flight::factory()->create(['origin' => 'MEX', 'destination' => 'MAD']);

        $response = $this->post('/api/flights', ['origin' => 'MEX', 'destination' => 'MAD', 'price' => 350]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.origin', 'MEX');
    }

    public function test_requires_authentication(): void
    {
        $this->get('/api/my-bookings')->assertStatus(401);
    }
}
```

---

### Factories

```php
// database/factories/FlightFactory.php
class FlightFactory extends ModelFactory
{
    protected string $model = Flight::class;

    public function definition(): array {
        return [
            'origin'      => $this->faker->lexify('???'),
            'destination' => $this->faker->lexify('???'),
            'price'       => $this->faker->randomFloat(2, 50, 1500),
            'class'       => $this->faker->randomElement(['economy', 'business']),
        ];
    }
}

// Usage
Flight::factory()->make();
Flight::factory()->count(10)->create();
Flight::factory()->state(['class' => 'business'])->create();
```

---

### OpenAPI

```bash
php hexaphp openapi:generate       # Generates openapi.json from your routes
```

---

### Live Slices (Reactive without writing JS)

```php
class FlightCounter extends LiveComponent
{
    public int $clicks = 0;

    public function increment(): void { $this->clicks++; }

    public function render(): string  { return $this->renderView('@Flights/counter.twig'); }
}
```

```twig
<div>
    <h3>Clicks: {{ clicks }}</h3>
    <button hx-post="/live/FlightCounter/increment"
            hx-vals='js:{"state": document.querySelector("[data-live-state]").dataset.liveState}'>
        +1
    </button>
</div>
```

---

### Full CLI (`hexaphp`)

```bash
# Generators
php hexaphp make:slice Name
php hexaphp make:model Name
php hexaphp make:controller Name
php hexaphp make:request Name
php hexaphp make:mail Name
php hexaphp make:job Name
php hexaphp make:event Name
php hexaphp make:middleware Name
php hexaphp make:seeder Name
php hexaphp make:factory Name
php hexaphp make:provider Name
php hexaphp make:notification Name
php hexaphp make:resource Name
php hexaphp make:test Name
php hexaphp make:migration create_flights_table

# Database
php hexaphp migrate
php hexaphp migrate:rollback [--step=N]
php hexaphp migrate:fresh
php hexaphp migrate:status
php hexaphp db:seed

# Queues
php hexaphp queue:work [--queue=name]
php hexaphp queue:install
php hexaphp queue:failed

# Scheduler
php hexaphp schedule:run
php hexaphp schedule:work
php hexaphp schedule:list

# Cache & Optimization
php hexaphp config:cache
php hexaphp config:clear
php hexaphp optimize
php hexaphp optimize:clear

# OpenAPI
php hexaphp openapi:generate

# Maintenance
php hexaphp down
php hexaphp up

# Dev
php hexaphp tinker
php hexaphp server:start
```

---

## Worker Mode (FrankenPHP)

The kernel boots **once** and serves thousands of requests per process — zero cold start:

```php
// public/index.php
$kernel = new HexaGen\Core\Kernel();
$kernel->boot();

if (function_exists('frankenphp_handle_request')) {
    for ($i = 0; frankenphp_handle_request() && $i < 1000; ++$i) {
        $request  = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response); // clears auth + request per request
        gc_collect_cycles();
    }
}
```

Worker mode optimizations:
- UrlMatcher cached (built once)
- ReflectionMethod/Class cached statically
- Middleware instances cached
- `AuthManager::reset()` + `CurrentRequest::reset()` on every `terminate()` — no identity leaks

---

## Security

- CSRF with `hash_equals` (timing-safe)
- JWT: alg:none blocked, cache-based blacklist, jti, nbf/exp
- Sessions: `session_regenerate_id` on login/logout
- Passwords: bcrypt with automatic rehash when cost is outdated
- SQL injection: quoted identifiers + operator whitelist
- Security headers: CSP, HSTS, X-Frame-Options, X-Content-Type-Options
- Stack traces only with `APP_DEBUG=true`
- `.env` never in version control

---

## Requirements

- PHP 8.3+
- Composer
- SQLite (development) / MySQL / PostgreSQL (production)
- Redis (optional, for cache and queues)
- FrankenPHP (optional, recommended for production)
