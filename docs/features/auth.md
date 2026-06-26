# Authentication

## Session guard (web)

```php
// Login
if (auth()->attempt(['email' => $email, 'password' => $password])) {
    return redirect('/dashboard');
}

// Get authenticated user
$user = auth()->user();

// Check if authenticated
if (auth()->check()) { ... }

// Logout
auth()->logout();
```

## JWT guard (API)

```php
// Login — returns a signed JWT
$token = auth('jwt')->attempt(['email' => $email, 'password' => $password]);
// returns null if credentials are wrong

// Get user from token (reads Authorization: Bearer header automatically)
$user = auth('jwt')->user();

// Check
auth('jwt')->check();
```

### JWT token response pattern

```php
public function login(Request $request): Response
{
    $token = auth('jwt')->attempt([
        'email'    => $request->request->get('email'),
        'password' => $request->request->get('password'),
    ]);

    if (!$token) {
        return $this->json(['message' => 'Invalid credentials'], 401);
    }

    return $this->json(['token' => $token, 'type' => 'Bearer']);
}
```

## Protecting routes

```php
// Require session login
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');

// Require JWT
Route::get('/api/me', [ProfileController::class, 'show'])
    ->middleware('auth:jwt');

// Redirect if already logged in
Route::get('/login', [AuthController::class, 'form'])
    ->middleware('guest');
```

## User model

Your user model must implement `Authenticatable`:

```php
use HexaGen\Core\Auth\Authenticatable;
use HexaGen\Core\Database\Model;

class User extends Model implements Authenticatable
{
    protected static array $fillable = ['name', 'email', 'password'];

    public function getAuthIdentifier(): mixed  { return $this->id; }
    public function getAuthPassword(): string   { return $this->password; }
    public function setAuthPassword(string $h): void { $this->password = $h; }
    public function getAuthIdentifierName(): string { return 'id'; }
}
```

## Configuration

```php
// config/auth.php
return [
    'default' => 'session',
    'guards'  => [
        'session' => ['driver' => 'session', 'provider' => 'users'],
        'jwt'     => ['driver' => 'jwt',     'provider' => 'users'],
    ],
    'providers' => [
        'users' => ['driver' => 'model', 'model' => App\User::class],
    ],
];
```
