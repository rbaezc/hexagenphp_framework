# Seeders & Factories

## Factories

Factories generate model instances for tests and seeding, powered by Faker.

```bash
php hexaphp make:factory FlightFactory
```

```php
// database/factories/FlightFactory.php
use HexaGen\Core\Testing\ModelFactory;

class FlightFactory extends ModelFactory
{
    protected string $model = Flight::class;

    public function definition(): array
    {
        return [
            'origin'      => $this->faker->lexify('???'),
            'destination' => $this->faker->lexify('???'),
            'price'       => $this->faker->randomFloat(2, 50, 2000),
            'class'       => $this->faker->randomElement(['economy', 'business', 'first']),
            'active'      => true,
        ];
    }
}
```

### Using factories

```php
// Single instance (not saved)
$flight = Flight::factory()->make();

// Single instance saved to DB
$flight = Flight::factory()->create();

// Multiple instances
$flights = Flight::factory()->count(10)->create();

// With overrides
$flight = Flight::factory()->state(['class' => 'business', 'price' => 999])->create();

// Just the raw array (no model)
$data = Flight::factory()->raw();
```

### Enabling `factory()` on a model

```php
class Flight extends Model
{
    use HasFactory;  // adds Flight::factory()
}
```

## Seeders

```bash
php hexaphp make:seeder FlightSeeder
```

```php
// database/seeders/FlightSeeder.php
use HexaGen\Core\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        Flight::factory()->count(50)->create();

        // Or manual inserts
        Flight::create([
            'origin'      => 'MEX',
            'destination' => 'MAD',
            'price'       => 350.00,
            'class'       => 'economy',
        ]);
    }
}
```

### DatabaseSeeder

The main seeder that calls others:

```php
// database/seeders/DatabaseSeeder.php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FlightSeeder::class);
        $this->call(AirlineSeeder::class);
    }
}
```

### Running seeders

```bash
php hexaphp db:seed                          # runs DatabaseSeeder
php hexaphp db:seed --class=FlightSeeder    # runs a specific seeder
php hexaphp migrate:fresh --seed            # fresh migration + seed
```
