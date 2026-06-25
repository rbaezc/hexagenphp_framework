# HexaGen PHP Framework ⚡

> 📖 **Readme versions**: [English Version](README.en.md) | [Versión en Español](README.md)

**HexaGen PHP Framework** es un micro-framework PHP 8.3+ ultra-ligero y de alto rendimiento, diseñado específicamente para brillar bajo el motor de **FrankenPHP** mediante arquitectura de **Slices Verticales** (Vertical Slices) y aplicando estrictamente principios **SOLID**.

Evita el acoplamiento y el peso innecesario de Doctrine o Eloquent utilizando **HexaORM**, un ORM activo y directo sobre PDO nativo.

---

## 🏗️ Arquitectura y Estructura de Directorios

La estructura de carpetas de HexaGen promueve que toda la lógica de una funcionalidad de negocio se autocontenga en un único Slice Vertical, en lugar de separarse en directorios globales:

```
hexagenphp_framework/
├── bin/
│   └── hexaphp            # Wrapper opcional del ejecutable CLI
├── public/
│   └── index.php          # Bucle Worker Loop para FrankenPHP & Fallback
├── src/
│   ├── Core/              # Núcleo del Framework (Inyección de Dependencias, Kernel, ORM)
│   │   ├── Console/       # Comandos del CLI hexaphp
│   │   ├── Controller/    # Controlador Abstracto base
│   │   ├── Database/      # Capa de Abstracción de Datos (HexaORM)
│   │   └── Kernel.php     # Orquestador principal (Symfony Container & Routing)
│   └── Slices/            # Aquí viven tus Slices Verticales (Vuelos, Hoteles, etc.)
│       └── [Nombre]/
│           ├── Controller/ # Controladores específicos de la funcionalidad
│           ├── Domain/     # Modelos / Entidades de Negocio (HexaORM)
│           ├── Routes.php  # Mapeo de rutas local del Slice
│           └── Services.php# Registro de dependencias local en el DIC
├── composer.json          # Gestión de dependencias (Symfony Core Components)
├── hexaphp                # Ejecutable CLI principal
└── database.sqlite        # Base de datos SQLite local (creada dinámicamente)
```

---

## 🛠️ Requisitos e Instalación

1. **PHP 8.3 o superior** (el framework aprovecha características modernas de tipado y atributos).
2. **Composer** (para descargar los componentes esenciales de Symfony).

Para iniciar el entorno local por primera vez, ejecuta:
```bash
composer install
```

*Nota: Durante el desarrollo en Windows/Linux, si no cuentas con el binario de **FrankenPHP**, el comando `server:start` te ofrecerá descargarlo e instalarlo de forma 100% automática.*

---

## 💻 Herramientas de Consola (`hexaphp`)

El CLI `hexaphp` expone comandos listos para mejorar la experiencia de desarrollo (DX):

### 1. Generar un Slice Vertical
Crea una estructura completa y lista para usar de una funcionalidad vertical con su propio controlador, modelo de dominio, rutas y servicios:
```bash
php hexaphp make:slice [Nombre]
```
*Ejemplo:* `php hexaphp make:slice Vuelos`
> **Automatización DX:** Si estás usando SQLite para desarrollo local, este comando creará automáticamente la tabla correspondiente en la base de datos con campos por defecto (`id`, `name`, `created_at`).

### 2. Levantar el Servidor FrankenPHP en modo Worker
Inicia el servidor FrankenPHP de alto rendimiento en modo Worker y con recarga en caliente (watch) activada:
```bash
php hexaphp server:start
```
*   **Host por defecto:** `http://127.0.0.1:8080`
*   **Hot-Reload:** Observa cambios en archivos `.php` y `.env` y reinicia el servidor automáticamente sin perder el estado de memoria en peticiones intermedias.

---

## 🏎️ El Bucle Mágico de FrankenPHP (Worker Loop)

El punto de entrada `public/index.php` mantiene el framework cargado en memoria, eliminando el coste de arranque (bootstrap) de PHP en cada petición:

```php
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';

// 1. Inicializas el núcleo de tu Framework UNA SOLA VEZ
$kernel = new HexaGen\Core\Kernel();
$kernel->boot();

if (function_exists('frankenphp_handle_request')) {
    // 2. Este es el bucle mágico que mantiene a tu framework vivo en memoria
    $maxRequests = 1000;
    for ($nbRequests = 0; frankenphp_handle_request() && $nbRequests < $maxRequests; ++$nbRequests) {
        
        $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        
        // Limpieza rápida post-petición
        $kernel->terminate($request, $response);
        
        // Prevenir fugas de memoria
        gc_collect_cycles();
    }
} else {
    // Fallback de desarrollo para servidores tradicionales (CGI, Apache/Nginx, php -S)
    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
}
```

---

## 💾 HexaORM (Abstracción PDO ultra-rápida)

En lugar de cargar con el peso y configuración de Doctrine o Eloquent, **HexaORM** implementa el patrón **Active Record** de forma ligera sobre prepared statements de PDO.

### Consultas:
```php
use HexaGen\Slices\Vuelos\Domain\Vuelo;

// Obtener todos los vuelos (retorna un array de objetos Vuelo)
$vuelos = Vuelo::all();

// Encontrar por Clave Primaria (retorna objeto Vuelo o null)
$vuelo = Vuelo::find(1);

// Consulta personalizada usando el QueryBuilder interno
$vuelosBaratos = Vuelo::where('precio', 200, '<')->orderBy('precio', 'ASC')->get();
```

### Inserción / Actualización:
```php
$vuelo = new Vuelo();
$vuelo->name = "Vuelo a Madrid";
$vuelo->created_at = date('Y-m-d H:i:s');
$vuelo->save(); // Inserta en BD y asigna la ID autogenerada al objeto

// Editar
$vuelo->name = "Vuelo a Barcelona";
$vuelo->save(); // Hace update en la BD
```

### Eliminación:
```php
$vuelo->delete();
```

### Relaciones (Eager Loading sin N+1) 🔗
Para evitar el problema de las consultas N+1 de manera estricta y transparente, HexaORM implementa relaciones precargadas en exactamente **2 consultas** usando `WHERE IN` por debajo, sin mágicas e ineficientes consultas tardías (Lazy Loading).

#### Definir relaciones en el Modelo:
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

#### Consultar relaciones (Eager Loading):
```php
// Trae todos los vuelos y sus aerolíneas asociadas en exactamente 2 consultas SQL en total:
$vuelos = Vuelo::query()->with('aerolinea')->get();

foreach ($vuelos as $vuelo) {
    echo $vuelo->name . " pertenece a " . $vuelo->aerolinea->name;
}
```

### Live Slices (Monolito Reactivo y Tiempo Real) ⚡
HexaGen incluye soporte para **Live Slices**, un motor de componentes reactivos del lado del servidor inspirado en Phoenix LiveView y Laravel Livewire. 

Permite actualizar partes de la interfaz web en tiempo real sin recargar la pantalla ni escribir archivos de compilación en JavaScript, usando **HTMX** y un **estado cifrado y firmado** por seguridad.

#### 1. Definir el componente reactivo:
Crea un componente dentro de `src/Slices/[Slice]/Components/[Componente].php`:
```php
namespace HexaGen\Slices\Vuelos\Components;

use HexaGen\Core\Live\LiveComponent;

class ContadorVuelos extends LiveComponent
{
    // Las propiedades públicas representan el estado del componente
    public int $clicks = 0;

    // Métodos que pueden ser gatillados desde la interfaz por eventos HTMX
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

#### 2. Escribir la plantilla Twig del componente (`contador.twig`):
Usa atributos de HTMX para conectar el botón con el método del servidor y enviar el estado cifrado de forma segura:
```twig
<div class="card">
    <h3>Clicks recibidos: {{ clicks }}</h3>
    <button hx-post="/live/ContadorVuelos/incrementar"
            hx-vals='js:{"state": event.target.closest("[data-live-state]").getAttribute("data-live-state")}'>
        Incrementar +1
    </button>
</div>
```

#### 3. Renderizar el componente desde tu controlador:
```php
public function index(Request $request): Response
{
    $contador = new ContadorVuelos();
    
    // view() es el helper global integrado para renderizar vistas Twig
    return view('@Vuelos/index.twig', [
        'liveContador' => $contador->render()
    ]);
}
```
y en tu layout HTML principal (`index.twig`), asegúrate de cargar **HTMX** y renderizar el componente de forma raw:
```twig
<script src="https://unpkg.com/htmx.org"></script>
...
<div>{{ liveContador|raw }}</div>
```


