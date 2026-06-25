# HexaGen PHP Framework ⚡

> 📖 **Readme versions**: [English Version](README.en.md) | [Versión en Español](README.md)

**HexaGen PHP Framework** es un framework PHP 8.3+ de alto rendimiento, diseñado específicamente para brillar bajo el motor de **FrankenPHP** mediante arquitectura de **Slices Verticales** y aplicando principios **SOLID** de forma estricta.

Evita el acoplamiento y el peso innecesario de Doctrine o Eloquent usando **HexaORM**, un ORM Active Record directo sobre PDO nativo.

---

## Instalación

```bash
composer create-project rbaezc/hexagenphp-framework mi-app
cd mi-app
cp .env.example .env
php hexaphp migrate
php hexaphp server:start
```

---

## Arquitectura

```
mi-app/
├── public/index.php          # Worker Loop FrankenPHP + fallback tradicional
├── src/
│   ├── Core/                 # Núcleo del framework
│   └── Slices/               # Tus funcionalidades verticales
│       └── [Nombre]/
│           ├── Controller/
│           ├── Domain/
│           ├── Routes.php
│           └── Services.php
├── config/                   # Configuración por módulo
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── lang/                     # Archivos de idioma
├── resources/views/          # Templates Twig
├── storage/                  # Archivos subidos, caché, logs
└── hexaphp                   # CLI principal
```

---

## Características

### Routing
- GET, POST, PUT, PATCH, DELETE, ANY
- Grupos de rutas con prefijo, middleware y namespace
- Rutas nombradas + generación de URLs con `route()`
- Resource routes (CRUD automático)
- Route model binding

```php
Route::get('/vuelos', [VuelosController::class, 'index'])->name('vuelos.index');

Route::group(['prefix' => '/api', 'middleware' => ['auth:jwt']], function () {
    Route::resource('/vuelos', VuelosController::class);
});
```

---

### HexaORM

```php
// Consultas
$vuelos = Vuelo::all();
$vuelo  = Vuelo::find(1);
$vuelo  = Vuelo::findOrFail(1);

// QueryBuilder
Vuelo::query()
    ->select(['id', 'origen', 'destino', 'precio'])
    ->where('precio', '<', 500)
    ->orWhere('clase', 'business')
    ->whereIn('aerolinea_id', [1, 2, 3])
    ->orderBy('precio', 'ASC')
    ->paginate(20);

// Creación / actualización
$vuelo = Vuelo::create(['origen' => 'MEX', 'destino' => 'MAD', 'precio' => 350]);
$vuelo->update(['precio' => 299]);
Vuelo::updateOrCreate(['origen' => 'MEX', 'destino' => 'MAD'], ['precio' => 299]);

// Eliminación
$vuelo->delete();

// Agregados
$total    = Vuelo::query()->count();
$promedio = Vuelo::query()->where('clase', 'economy')->avg('precio');
$existe   = Vuelo::query()->where('destino', 'MAD')->exists();
```

#### Relaciones
```php
class Aerolinea extends Model
{
    public function vuelos(): HasMany      { return $this->hasMany(Vuelo::class, 'aerolinea_id'); }
    public function pais(): BelongsTo     { return $this->belongsTo(Pais::class); }
    public function hub(): HasOne         { return $this->hasOne(Aeropuerto::class); }
}

// Eager loading en exactamente 2 queries (sin N+1)
$aerolineas = Aerolinea::query()->with('vuelos', 'pais')->get();

// Eager loading con constraints
$aerolineas = Aerolinea::query()->with([
    'vuelos' => fn($q) => $q->where('clase', 'business')->orderBy('precio')
])->get();
```

**Relaciones disponibles:** `hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, `hasOneThrough`, `hasManyThrough`, `morphOne`, `morphMany`, `morphTo`

#### Traits de Modelo
```php
class Vuelo extends Model
{
    use HasTimestamps;   // created_at / updated_at automáticos
    use SoftDeletes;     // deleted_at — no borra físicamente
    use HasCasts;        // cast automático de tipos
    use HasScopes;       // scopes locales y globales
    use HasObservers;    // hooks created/updated/deleted
    use HasFactory;      // Vuelo::factory()->create()

    protected static array $fillable = ['origen', 'destino', 'precio', 'clase'];
    protected static array $casts    = ['precio' => 'float', 'activo' => 'bool'];
}
```

---

### Migraciones

```bash
php hexaphp migrate              # Corre migraciones pendientes
php hexaphp migrate:rollback     # Revierte el último batch
php hexaphp migrate:rollback --step=3
php hexaphp migrate:fresh        # Drop all + re-run
php hexaphp migrate:status       # Muestra estado (Ran / Pending)
```

```php
// database/migrations/2024_01_01_create_vuelos_table.php
return new class extends Migration {
    public function up(\PDO $pdo): void {
        $pdo->exec("CREATE TABLE vuelos (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            origen    VARCHAR(3) NOT NULL,
            destino   VARCHAR(3) NOT NULL,
            precio    DECIMAL(10,2),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    public function down(\PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS vuelos");
    }
};
```

---

### Autenticación & Autorización

```php
// Session guard
auth()->attempt(['email' => $email, 'password' => $password]);
auth()->user();
auth()->logout();

// JWT guard
auth('jwt')->attempt($credentials);
$token = auth('jwt')->token();

// Gate / Policies
Gate::define('editar-vuelo', fn($user, $vuelo) => $user->id === $vuelo->user_id);

if (Gate::allows('editar-vuelo', $vuelo)) { /* ... */ }
// o en controlador:
$this->authorize('editar-vuelo', $vuelo);
```

Middlewares disponibles: `auth`, `auth:jwt`, `guest`, `authorize:permiso`

---

### Validación

```php
class CrearVueloRequest extends FormRequest
{
    public function rules(): array {
        return [
            'origen'  => 'required|min:3|max:3',
            'destino' => 'required|min:3|max:3|different:origen',
            'precio'  => 'required|numeric|min:0',
            'clase'   => 'required|in:economy,business,first',
            'fecha'   => 'required|date|after:today',
            'email'   => 'required|email|unique:vuelos,email',
        ];
    }

    public function messages(): array {
        return ['origen.required' => 'El origen es obligatorio.'];
    }
}
```

**Reglas disponibles:** `required`, `nullable`, `sometimes`, `string`, `numeric`, `integer`, `boolean`, `array`, `email`, `url`, `min`, `max`, `between`, `size`, `in`, `not_in`, `same`, `different`, `confirmed`, `unique`, `exists`, `required_if`, `after`, `before`, `starts_with`, `ends_with`, `accepted`, `ip`, `ipv4`, `ipv6`, `uuid`, `json`, `digits`, `digits_between`

---

### Correo

```php
// app/Mail/BienvenidaMail.php
class BienvenidaMail extends Mailable
{
    public function build(): static {
        return $this->subject('Bienvenido')
                    ->view('emails.bienvenida')
                    ->with(['usuario' => $this->usuario]);
    }
}

Mail::to('cliente@ejemplo.com')->send(new BienvenidaMail($usuario));
```

Drivers: `smtp`, `log`, `null`

---

### Sistema de Colas

```php
// Definir un job
class ProcesarReservaJob extends Job
{
    public function handle(): void {
        // lógica del job
    }
}

// Despachar
dispatch(new ProcesarReservaJob($reserva));
dispatch(new ProcesarReservaJob($reserva))->onQueue('reservas');

// Worker
php hexaphp queue:work
php hexaphp queue:work --queue=reservas
php hexaphp queue:failed
```

Drivers: `sync`, `database`, `redis`

---

### Storage

```php
Storage::put('vuelos/foto.jpg', $contenido);
Storage::get('vuelos/foto.jpg');
Storage::delete('vuelos/foto.jpg');
Storage::url('vuelos/foto.jpg');
Storage::exists('vuelos/foto.jpg');
Storage::disk('s3')->put('backup.zip', $contenido);
```

Drivers: `local`, `s3`

---

### Caché

```php
cache()->set('vuelos_populares', $vuelos, ttl: 3600);
cache()->get('vuelos_populares');
cache()->remember('vuelos_mex', 600, fn() => Vuelo::query()->where('origen', 'MEX')->get());
cache()->delete('vuelos_populares');
```

Drivers: `file`, `redis`, `array`

---

### Eventos

```php
// Definir
class VueloReservado extends Event
{
    public function __construct(public readonly Vuelo $vuelo) {}
}

// Listener
class EnviarConfirmacion implements ListenerInterface
{
    public function handle(object $event): void {
        Mail::to($event->vuelo->email)->send(new ConfirmacionMail($event->vuelo));
    }
}

// Disparar
event(new VueloReservado($vuelo));
```

---

### Notificaciones

```php
class ReservaConfirmada extends Notification
{
    public function via(): array    { return ['mail', 'database']; }
    public function toMail(): array { return ['subject' => 'Reserva confirmada', ...]; }
}

$usuario->notify(new ReservaConfirmada($reserva));
// o: notify($usuario, new ReservaConfirmada($reserva));
```

Canales: `mail`, `database`, `slack`

---

### Broadcasting (SSE)

```php
class VueloActualizado extends Event implements ShouldBroadcast
{
    public function broadcastOn(): string  { return 'vuelos'; }
    public function broadcastAs(): string  { return 'actualizado'; }
}

// Cliente JavaScript
const source = new EventSource('/broadcast/vuelos');
source.addEventListener('actualizado', e => console.log(JSON.parse(e.data)));
```

---

### Internacionalización

```php
// lang/es/vuelos.php
return ['bienvenida' => 'Bienvenido, :nombre'];

__('vuelos.bienvenida', ['nombre' => 'Raúl']);   // "Bienvenido, Raúl"
trans_choice('vuelos.asientos', $n);               // pluralización con pipes
```

---

### Templates Twig

```twig
{# Funciones disponibles en Twig #}
{{ route('vuelos.index') }}
{{ asset('css/app.css') }}
{{ vite('resources/js/app.js') }}
{{ __('vuelos.bienvenida', {nombre: 'Raúl'}) }}
{% if auth().check() %}Hola, {{ auth().user().name }}{% endif %}
{% if can('editar-vuelo', vuelo) %}...{% endif %}
```

```php
// View composers — datos automáticos en vistas
View::composer('layouts/*', function (ViewData $data) {
    $data->set('monedas', Moneda::all());
});

// Datos compartidos globalmente
View::share('app_name', config('app.name'));
```

---

### HTTP Client

```php
$response = Http::withToken($token)
    ->retry(3, 100)
    ->post('https://api.aerolinea.com/reservas', ['vuelo_id' => 1]);

$response->json();
$response->status();
$response->successful();
```

---

### Scheduler

```php
// src/Core/Console/Scheduler.php
$schedule->command('vuelos:sync')->hourly();
$schedule->command('reportes:generar')->dailyAt('06:00');
$schedule->call(fn() => Cache::clear())->everyFiveMinutes();
```

```bash
php hexaphp schedule:list          # Ver tareas programadas
php hexaphp schedule:work          # Daemon con graceful shutdown (SIGTERM)
```

---

### Tinker (REPL)

```bash
php hexaphp tinker
>>> Vuelo::query()->where('destino', 'MAD')->count()
>>> dispatch(new ProcesarReservaJob($vuelo))
```

---

### Testing

```php
class VuelosTest extends TestCase
{
    public function test_crear_vuelo(): void
    {
        $vuelo = Vuelo::factory()->create(['origen' => 'MEX', 'destino' => 'MAD']);

        $response = $this->post('/api/vuelos', ['origen' => 'MEX', 'destino' => 'MAD', 'precio' => 350]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.origen', 'MEX');
    }

    public function test_requiere_autenticacion(): void
    {
        $this->get('/api/mis-reservas')->assertStatus(401);
    }
}
```

---

### Factories

```php
// database/factories/VueloFactory.php
class VueloFactory extends ModelFactory
{
    protected string $model = Vuelo::class;

    public function definition(): array {
        return [
            'origen'  => $this->faker->lexify('???'),
            'destino' => $this->faker->lexify('???'),
            'precio'  => $this->faker->randomFloat(2, 50, 1500),
            'clase'   => $this->faker->randomElement(['economy', 'business']),
        ];
    }
}

// Uso
Vuelo::factory()->make();
Vuelo::factory()->count(10)->create();
Vuelo::factory()->state(['clase' => 'business'])->create();
```

---

### OpenAPI

```bash
php hexaphp openapi:generate       # Genera openapi.json desde tus rutas
```

---

### Live Slices (Reactivo sin JS)

```php
class ContadorVuelos extends LiveComponent
{
    public int $clicks = 0;

    public function incrementar(): void { $this->clicks++; }

    public function render(): string   { return $this->renderView('@Vuelos/contador.twig'); }
}
```

```twig
<div>
    <h3>Clicks: {{ clicks }}</h3>
    <button hx-post="/live/ContadorVuelos/incrementar"
            hx-vals='js:{"state": document.querySelector("[data-live-state]").dataset.liveState}'>
        +1
    </button>
</div>
```

---

### CLI completo (`hexaphp`)

```bash
# Generadores
php hexaphp make:slice NombreSlice
php hexaphp make:model NombreModel
php hexaphp make:controller NombreController
php hexaphp make:request NombreRequest
php hexaphp make:mail NombreMail
php hexaphp make:job NombreJob
php hexaphp make:event NombreEvent
php hexaphp make:middleware NombreMiddleware
php hexaphp make:seeder NombreSeeder
php hexaphp make:factory NombreFactory
php hexaphp make:provider NombreProvider
php hexaphp make:notification NombreNotification
php hexaphp make:resource NombreResource
php hexaphp make:test NombreTest
php hexaphp make:migration crear_tabla_vuelos

# Base de datos
php hexaphp migrate
php hexaphp migrate:rollback [--step=N]
php hexaphp migrate:fresh
php hexaphp migrate:status
php hexaphp db:seed

# Colas
php hexaphp queue:work [--queue=nombre]
php hexaphp queue:install
php hexaphp queue:failed

# Scheduler
php hexaphp schedule:run
php hexaphp schedule:work
php hexaphp schedule:list

# Caché & Optimización
php hexaphp config:cache
php hexaphp config:clear
php hexaphp optimize
php hexaphp optimize:clear

# OpenAPI
php hexaphp openapi:generate

# Mantenimiento
php hexaphp down
php hexaphp up

# Dev
php hexaphp tinker
php hexaphp server:start
```

---

## Worker Mode (FrankenPHP)

El kernel arranca **una sola vez** y sirve miles de requests por proceso — sin cold start:

```php
// public/index.php
$kernel = new HexaGen\Core\Kernel();
$kernel->boot();

if (function_exists('frankenphp_handle_request')) {
    for ($i = 0; frankenphp_handle_request() && $i < 1000; ++$i) {
        $request  = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response); // limpia auth + request por request
        gc_collect_cycles();
    }
}
```

Optimizaciones de worker mode:
- UrlMatcher cacheado (se construye una vez)
- ReflectionMethod/Class cacheado estáticamente
- Instancias de middleware cacheadas
- `AuthManager::reset()` + `CurrentRequest::reset()` en cada `terminate()` — sin fugas de identidad

---

## Seguridad

- CSRF con `hash_equals` (timing-safe)
- JWT: alg:none bloqueado, blacklist por caché, jti, nbf/exp
- Sesiones: `session_regenerate_id` en login/logout
- Passwords: bcrypt con rehash automático al detectar costo obsoleto
- SQL injection: identificadores entrecomillados + whitelist de operadores
- Headers de seguridad: CSP, HSTS, X-Frame-Options, X-Content-Type-Options
- Stack traces solo con `APP_DEBUG=true`
- `.env` nunca en control de versiones

---

## Requisitos

- PHP 8.3+
- Composer
- SQLite (desarrollo) / MySQL / PostgreSQL (producción)
- Redis (opcional, para caché y colas)
- FrankenPHP (opcional, recomendado para producción)
