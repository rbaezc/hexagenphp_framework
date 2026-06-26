# Testing

## Setup

```php
use HexaGen\Core\Testing\TestCase;

class FlightsTest extends TestCase
{
    public function test_list_flights(): void
    {
        Flight::factory()->count(5)->create();

        $response = $this->get('/api/flights');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data');
    }

    public function test_create_flight(): void
    {
        $response = $this->post('/api/flights', [
            'origin'      => 'MEX',
            'destination' => 'MAD',
            'price'       => 350.00,
            'class'       => 'economy',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.origin', 'MEX');
    }

    public function test_requires_auth(): void
    {
        $this->get('/api/my-bookings')->assertStatus(401);
    }

    public function test_authenticated_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/my-bookings');

        $response->assertStatus(200);
    }
}
```

## TestResponse assertions

```php
$response->assertStatus(200);
$response->assertOk();            // 200
$response->assertCreated();       // 201
$response->assertNoContent();     // 204
$response->assertNotFound();      // 404
$response->assertUnauthorized();  // 401
$response->assertForbidden();     // 403
$response->assertUnprocessable(); // 422

$response->assertJson(['key' => 'value']);
$response->assertJsonPath('data.origin', 'MEX');
$response->assertJsonCount(5, 'data');
$response->assertJsonMissing(['password']);

$response->assertHeader('Content-Type', 'application/json');
$response->assertRedirect('/login');
```

## HTTP methods

```php
$this->get('/flights');
$this->post('/flights', ['origin' => 'MEX']);
$this->put('/flights/1', ['price' => 299]);
$this->patch('/flights/1', ['active' => false]);
$this->delete('/flights/1');

// With headers
$this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/me');

// With JWT
$this->withToken($token)->get('/api/me');
```

## Factories in tests

```php
// Single
$flight = Flight::factory()->create();

// Multiple
$flights = Flight::factory()->count(10)->create();

// With state
$flight = Flight::factory()->state(['class' => 'business'])->create();

// Without saving
$data = Flight::factory()->raw();
```

## TestCase tearDown

`TestCase::tearDown()` automatically runs after each test:

- `AuthManager::reset()` — clears authenticated user
- `CacheManager::flush()` — clears cache
- `EventDispatcher::flush()` — clears event listeners
