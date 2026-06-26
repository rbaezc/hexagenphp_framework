# HTTP Client

Fluent HTTP client built on Guzzle. Access via the `Http` facade or the `http()` helper.

## Basic requests

```php
use HexaGen\Core\Http\Http;

$response = Http::get('https://api.example.com/flights');
$response = Http::post('https://api.example.com/flights', ['origin' => 'MEX']);
$response = Http::put('https://api.example.com/flights/1', ['price' => 299]);
$response = Http::delete('https://api.example.com/flights/1');
```

## Working with responses

```php
$response->json();           // decoded JSON as array
$response->body();           // raw string body
$response->status();         // HTTP status code (int)
$response->ok();             // true if 200-299
$response->successful();     // alias for ok()
$response->failed();         // true if 400+
$response->clientError();    // true if 400-499
$response->serverError();    // true if 500+
$response->header('X-RateLimit-Remaining');
```

## Authentication

```php
Http::withToken($jwtToken)->get('/api/me');

Http::withBasicAuth($user, $password)->get('/protected');
```

## Headers & options

```php
Http::withHeaders([
    'X-API-Key' => $apiKey,
    'Accept'    => 'application/json',
])->get('/endpoint');
```

## Retries

```php
Http::retry(times: 3, sleepMs: 100)
    ->post('https://api.example.com/flights', $data);
```

## Timeout

```php
Http::timeout(30)->get('https://slow-api.example.com/data');
```

## Sending JSON

```php
Http::asJson()
    ->post('https://api.example.com/flights', ['origin' => 'MEX', 'price' => 350]);
```

## Sending form data

```php
Http::asForm()
    ->post('https://api.example.com/login', ['email' => $email, 'password' => $pass]);
```

## Streaming

```php
Http::withOptions(['stream' => true])
    ->get('https://files.example.com/large-file.csv');
```
